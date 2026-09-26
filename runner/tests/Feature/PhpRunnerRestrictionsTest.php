<?php

namespace Tests\Feature;

use App\Runner\PhpRunner;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Proves that learner code can't reach outside its temp folder. Each case is
 * something a learner could try on purpose.
 */
class PhpRunnerRestrictionsTest extends TestCase
{
    private string $workRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workRoot = sys_get_temp_dir().'/runner-test-'.uniqid();
        File::ensureDirectoryExists($this->workRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workRoot);

        parent::tearDown();
    }

    private function runCode(string $code): string
    {
        return (new PhpRunner($this->workRoot, 1))->run(['main.php' => $code], 'main.php')['stdout'];
    }

    #[DataProvider('blockedFunctionCalls')]
    public function test_functions_that_reach_outside_the_sandbox_are_disabled(string $code, string $function): void
    {
        $this->assertStringContainsString("Call to undefined function $function()", $this->runCode("<?php $code"));
    }

    public static function blockedFunctionCalls(): array
    {
        return [
            'exec' => ['exec("id");', 'exec'],
            'shell_exec' => ['shell_exec("id");', 'shell_exec'],
            'backticks' => ['echo `id`;', 'shell_exec'],
            'system' => ['system("id");', 'system'],
            'proc_open' => ['proc_open("id", [], $pipes);', 'proc_open'],
            'putenv' => ['putenv("A=1");', 'putenv'],
            'ini_set' => ['ini_set("open_basedir", "/");', 'ini_set'],
            'set_time_limit' => ['set_time_limit(0);', 'set_time_limit'],
            'mail' => ['mail("a@example.com", "s", "b");', 'mail'],
            'fsockopen' => ['fsockopen("example.com", 80);', 'fsockopen'],
            'stream_socket_client' => ['stream_socket_client("tcp://example.com:80");', 'stream_socket_client'],
            'gethostbyname' => ['gethostbyname("example.com");', 'gethostbyname'],
            'symlink' => ['symlink("/etc/passwd", "p");', 'symlink'],
            'stream_wrapper_restore' => ['stream_wrapper_restore("php");', 'stream_wrapper_restore'],
        ];
    }

    public function test_prefixed_function_families_are_disabled_when_the_binary_has_them(): void
    {
        $output = $this->runCode('<?php echo json_encode(array_values(array_filter(get_defined_functions()["internal"], fn ($f) => preg_match("/^(pcntl_|posix_|socket_|curl_|ftp_|proc_)/", $f))));');

        $this->assertSame('[]', $output);
    }

    public function test_files_outside_the_run_folder_cannot_be_read(): void
    {
        $output = $this->runCode('<?php var_dump(file_get_contents("/etc/passwd"));');

        $this->assertStringContainsString('open_basedir restriction in effect', $output);
        $this->assertStringContainsString('bool(false)', $output);
    }

    public function test_files_in_the_run_folder_can_be_read(): void
    {
        $result = (new PhpRunner($this->workRoot, 1))->run([
            'checks.json' => '[1,2]',
            'main.php' => '<?php echo file_get_contents(__DIR__."/checks.json");',
        ], 'main.php');

        $this->assertSame('[1,2]', $result['stdout']);
    }

    public function test_url_wrappers_are_disabled(): void
    {
        $this->assertStringContainsString('wrapper is disabled', $this->runCode('<?php file_get_contents("http://example.com");'));
    }

    public function test_inherited_file_descriptors_cannot_be_reached(): void
    {
        $marker = 'do-not-leak-'.bin2hex(random_bytes(16));
        $outsidePath = tempnam(sys_get_temp_dir(), 'runner-fd-test-');
        file_put_contents($outsidePath, $marker);
        $handle = fopen($outsidePath, 'r');

        try {
            // Bounded reads, capped well under PhpRunner's maxOutputBytes (65536):
            // some inherited fd in this range is a large/never-EOF stream (observed:
            // fd 3 alone yields up to 65536 bytes), and an unbounded read on it fills
            // PhpRunner's output budget and gets the process killed for truncation
            // before the loop ever reaches the fd holding the marker — making the
            // test pass vacuously without proving anything. Reading only a couple
            // hundred bytes per fd keeps the whole run well under that budget.
            $output = $this->runCode('<?php
                echo "ran|";
                for ($i = 0; $i < 256; $i++) {
                    try {
                        $h = @fopen("php://fd/$i", "r");
                        if ($h) {
                            echo @stream_get_contents($h, 200);
                        }
                    } catch (\Throwable $e) {
                    }
                }
            ');
        } finally {
            fclose($handle);
            unlink($outsidePath);
        }

        $this->assertStringStartsWith('ran|', $output);
        $this->assertStringNotContainsString($marker, $output);
    }

    public function test_ordinary_output_still_works_after_the_sandbox_prepend(): void
    {
        $this->assertSame('ok', $this->runCode('<?php echo "ok";'));
    }

    public function test_the_stdout_constant_survives_unregistering_the_php_wrapper(): void
    {
        $this->assertSame('x', $this->runCode('<?php fwrite(STDOUT, "x");'));
    }

    public function test_learner_code_sees_an_empty_environment(): void
    {
        $_ENV['RUNNER_SECRET'] = $_SERVER['RUNNER_SECRET'] = 'do-not-leak';
        putenv('RUNNER_SECRET=do-not-leak');

        try {
            $output = $this->runCode('<?php echo json_encode(["env" => array_keys(getenv()), "ENV" => array_keys($_ENV), "secret" => getenv("RUNNER_SECRET"), "server" => isset($_SERVER["RUNNER_SECRET"])]);');
        } finally {
            unset($_ENV['RUNNER_SECRET'], $_SERVER['RUNNER_SECRET']);
            putenv('RUNNER_SECRET');
        }

        $result = json_decode($output, true);

        // macOS adds __CF_USER_TEXT_ENCODING inside the child process.
        $this->assertSame([], array_diff($result['env'], ['__CF_USER_TEXT_ENCODING']));
        $this->assertSame([], array_diff($result['ENV'], ['__CF_USER_TEXT_ENCODING']));
        $this->assertFalse($result['secret']);
        $this->assertFalse($result['server']);
    }

    public function test_ffi_is_off(): void
    {
        $output = $this->runCode('<?php FFI::cdef("int getpid(void);");');

        $this->assertMatchesRegularExpression('/ffi\.enable|Class "FFI" not found/', $output);
    }

    public function test_memory_is_limited(): void
    {
        $output = $this->runCode('<?php $a = str_repeat("x", 128 * 1024 * 1024);');

        $this->assertStringContainsString('Allowed memory size', $output);
    }

    public function test_tmpfile_writes_land_in_the_run_folder_not_the_system_temp_dir(): void
    {
        $result = (new PhpRunner($this->workRoot, 1))->run([
            'main.php' => '<?php $f = tmpfile(); fwrite($f, str_repeat("x", 1024)); echo stream_get_meta_data($f)["uri"]; sleep(30);',
        ], 'main.php');

        $this->assertStringStartsWith(realpath($this->workRoot).DIRECTORY_SEPARATOR.'run-', $result['stdout']);
        $this->assertSame([], File::directories($this->workRoot));
    }

    // SQLite3::loadExtension() is gated by the `sqlite3.extension_dir` ini setting: PHP
    // refuses to enable extension loading at all when it's empty, before ever touching the
    // filesystem. Asserting the specific "disabled" message (not just any failure) proves
    // it's this gate that fired, not merely a missing file.
    public function test_sqlite3_extension_loading_is_blocked(): void
    {
        if (! class_exists('SQLite3')) {
            $this->markTestSkipped('SQLite3 is not available on this PHP build.');
        }

        $output = $this->runCode('<?php
            $db = new SQLite3(":memory:");
            var_dump($db->loadExtension("evil.so"));
        ');

        $this->assertStringContainsString('SQLite Extensions are disabled', $output);
        $this->assertStringContainsString('bool(false)', $output);
    }

    // Pdo\Sqlite::loadExtension() (PHP 8.4+) and PDO's own sqlite driver have no equivalent
    // gate: ext/pdo_sqlite registers no ini settings at all (confirmed via
    // ReflectionExtension('pdo_sqlite')->getINIEntries()), and setAuthorizer(), disable_functions
    // and open_basedir were all confirmed by hand (outside the repo, with a real compiled native
    // library) to have no effect on it either, because it calls libsqlite3's sqlite3_load_extension()
    // (a dlopen) directly, bypassing every PHP-level control. No test is added for it here because
    // any test built around a missing/invalid file would pass whether or not a real gate exists,
    // which is worse than no test. See runner/README.md "Known limits" and the fix report for the
    // full probe and the residual risk.
}
