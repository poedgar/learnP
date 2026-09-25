<?php

namespace Tests\Feature;

use App\Runner\PhpRunner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhpRunnerTest extends TestCase
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

    private function runner(int $timeLimitSeconds = 1, int $maxOutputBytes = 65_536): PhpRunner
    {
        return new PhpRunner($this->workRoot, $timeLimitSeconds, $maxOutputBytes);
    }

    private function runCode(string $code, ?PhpRunner $runner = null): array
    {
        return ($runner ?? $this->runner())->run(['main.php' => $code], 'main.php');
    }

    public function test_output_and_exit_code_are_returned(): void
    {
        $this->assertSame(
            ['stdout' => 'hello', 'exitCode' => 0, 'timedOut' => false, 'truncated' => false],
            $this->runCode('<?php echo "hello";'),
        );
    }

    public function test_fatal_errors_are_printed_and_exit_with_255(): void
    {
        $result = $this->runCode('<?php undefined_function();');

        $this->assertSame(255, $result['exitCode']);
        $this->assertStringContainsString('Call to undefined function undefined_function()', $result['stdout']);
    }

    public function test_the_entry_file_can_load_the_other_files(): void
    {
        $result = $this->runner()->run([
            'helper.php' => '<?php return 42;',
            'main.php' => '<?php echo require __DIR__."/helper.php";',
        ], 'main.php');

        $this->assertSame('42', $result['stdout']);
    }

    public function test_cpu_bound_code_is_stopped_by_php_with_a_readable_error(): void
    {
        $result = $this->runCode('<?php while (true) {}');

        $this->assertFalse($result['timedOut']);
        $this->assertSame(255, $result['exitCode']);
        $this->assertStringContainsString('Maximum execution time of 1 second exceeded', $result['stdout']);
    }

    public function test_code_that_waits_forever_is_killed(): void
    {
        $result = $this->runCode('<?php echo "before"; sleep(30);');

        $this->assertTrue($result['timedOut']);
        $this->assertSame(-1, $result['exitCode']);
        $this->assertSame('before', $result['stdout']);
    }

    public function test_output_is_capped(): void
    {
        $result = $this->runCode('<?php echo str_repeat("x", 5000);', $this->runner(maxOutputBytes: 1000));

        $this->assertTrue($result['truncated']);
        $this->assertSame(-1, $result['exitCode']);
        $this->assertSame(1000, strlen($result['stdout']));
    }

    public function test_endless_output_is_cut_off_without_waiting_for_the_timeout(): void
    {
        $started = microtime(true);
        $result = $this->runCode('<?php while (true) { echo "x"; }', $this->runner(timeLimitSeconds: 5, maxOutputBytes: 1000));

        $this->assertTrue($result['truncated']);
        $this->assertFalse($result['timedOut']);
        $this->assertLessThan(3, microtime(true) - $started);
    }

    public function test_temp_folders_are_deleted_after_every_kind_of_run(): void
    {
        $this->runCode('<?php echo 1;');
        $this->runCode('<?php undefined_function();');
        $this->runCode('<?php sleep(30);');

        $this->assertSame([], File::directories($this->workRoot));
    }
}
