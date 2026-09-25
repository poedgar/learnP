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

    public function test_learner_code_sees_an_empty_environment(): void
    {
        $_ENV['RUNNER_SECRET'] = $_SERVER['RUNNER_SECRET'] = 'do-not-leak';
        putenv('RUNNER_SECRET=do-not-leak');

        try {
            $output = $this->runCode('<?php echo json_encode(getenv()), "|", json_encode($_ENV), "|", isset($_SERVER["RUNNER_SECRET"]) ? "leak" : "clean";');
        } finally {
            unset($_ENV['RUNNER_SECRET'], $_SERVER['RUNNER_SECRET']);
            putenv('RUNNER_SECRET');
        }

        $this->assertSame('[]|[]|clean', $output);
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
}
