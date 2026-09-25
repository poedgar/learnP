<?php

namespace Tests\Feature;

use App\Sandbox\HttpPhpSandbox;
use App\Sandbox\PhpSandboxUnavailable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpPhpSandboxTest extends TestCase
{
    private const MARKER = "\n@@LEARNP_REPORT@@\n";

    private function sandbox(?string $url = 'https://runner.test', ?string $secret = 'secret'): HttpPhpSandbox
    {
        return new HttpPhpSandbox($url, $secret, resource_path('php/check-harness.php'));
    }

    private function runnerReturns(string $stdout, int $exitCode = 0, bool $timedOut = false, bool $truncated = false): void
    {
        Http::fake(['runner.test/run' => Http::response(compact('stdout', 'exitCode', 'timedOut', 'truncated'))]);
    }

    private function report(bool $loaded, array $passes, string $output = ''): string
    {
        return json_encode([
            'loaded' => $loaded,
            'error' => $loaded ? null : 'ParseError: syntax error on line 1',
            'output' => $output,
            'checks' => array_map(fn (bool $passed, int $i) => ['label' => "check $i", 'passed' => $passed, 'message' => $passed ? null : 'Nope.'], $passes, array_keys($passes)),
        ]);
    }

    public function test_run_sends_a_signed_request_with_the_code(): void
    {
        $this->freezeTime();
        $this->runnerReturns("\nhi");

        $result = $this->sandbox()->run('echo "hi";');

        $this->assertSame('hi', $result['output']);
        $this->assertFalse($result['fatal']);
        $this->assertIsInt($result['durationMs']);

        Http::assertSent(function (Request $request) {
            $timestamp = $request->header('X-Timestamp')[0];

            return $request->url() === 'https://runner.test/run'
                && $timestamp === (string) now()->getTimestamp()
                && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->body(), 'secret'), $request->header('X-Signature')[0])
                && $request['files'] === ['main.php' => "<?php\n\necho \"hi\";"]
                && $request['entry'] === 'main.php';
        });
    }

    public function test_code_with_an_opening_tag_is_sent_unchanged(): void
    {
        $this->runnerReturns('');

        $this->sandbox()->run("<?php\necho 1;");

        Http::assertSent(fn (Request $request) => $request['files'] === ['main.php' => "<?php\necho 1;"]);
    }

    public function test_fatal_errors_are_flagged(): void
    {
        $this->runnerReturns('PHP Fatal error: Uncaught Error', exitCode: 255);

        $this->assertTrue($this->sandbox()->run('nope();')['fatal']);
    }

    public function test_timeouts_are_flagged_and_explained(): void
    {
        $this->runnerReturns('before', exitCode: -1, timedOut: true);

        $result = $this->sandbox()->run('sleep(30);');

        $this->assertTrue($result['fatal']);
        $this->assertSame("before\n\nStopped after 5 seconds.", $result['output']);
    }

    public function test_truncated_output_is_marked(): void
    {
        $this->runnerReturns('xxxx', exitCode: -1, truncated: true);

        $this->assertSame("xxxx\n…output truncated", $this->sandbox()->run('while (true) echo "x";')['output']);
    }

    public function test_check_sends_the_harness_and_checks_and_reads_the_report(): void
    {
        $checks = [['label' => 'check 0', 'code' => 'return true;']];
        $this->runnerReturns('hello'.self::MARKER.$this->report(true, [true], "\nhello"));

        $result = $this->sandbox()->check('<?php echo "hello";', $checks);

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['loaded']);
        $this->assertSame('hello', $result['output']);
        $this->assertSame([['label' => 'check 0', 'passed' => true, 'message' => null]], $result['checks']);

        Http::assertSent(fn (Request $request) => $request['entry'] === 'harness.php'
            && $request['files']['main.php'] === '<?php echo "hello";'
            && $request['files']['checks.json'] === json_encode($checks)
            && $request['files']['harness.php'] === file_get_contents(resource_path('php/check-harness.php')));
    }

    public function test_one_failing_check_fails_the_challenge(): void
    {
        $this->runnerReturns(self::MARKER.$this->report(true, [true, false]));

        $this->assertFalse($this->sandbox()->check('<?php', [])['passed']);
    }

    public function test_code_that_does_not_load_fails_the_challenge(): void
    {
        $this->runnerReturns(self::MARKER.$this->report(false, []));

        $result = $this->sandbox()->check('<?php class {', []);

        $this->assertFalse($result['passed']);
        $this->assertSame('ParseError: syntax error on line 1', $result['error']);
    }

    public function test_a_fake_report_printed_by_learner_code_is_ignored(): void
    {
        $fake = self::MARKER.$this->report(true, [true]);
        $this->runnerReturns($fake.self::MARKER.$this->report(true, [false], $fake));

        $this->assertFalse($this->sandbox()->check('<?php echo "fake";', [['label' => 'check 0', 'code' => 'return false;']])['passed']);
    }

    public function test_a_missing_report_fails_every_check_with_an_explanation(): void
    {
        $this->runnerReturns('Segmentation fault', exitCode: 139);

        $result = $this->sandbox()->check('<?php', [['label' => 'a', 'code' => ''], ['label' => 'b', 'code' => '']]);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['loaded']);
        $this->assertSame('The checker could not read your code. Run it first to look for errors.', $result['error']);
        $this->assertSame(['a', 'b'], array_column($result['checks'], 'label'));
        $this->assertSame([false, false], array_column($result['checks'], 'passed'));
    }

    public function test_a_check_that_times_out_explains_the_timeout(): void
    {
        $this->runnerReturns('', exitCode: -1, timedOut: true);

        $result = $this->sandbox()->check('<?php sleep(30);', [['label' => 'a', 'code' => '']]);

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('ran for more than 5 seconds', $result['error']);
    }

    public function test_a_check_with_too_much_output_explains_it(): void
    {
        $this->runnerReturns('xxxx', exitCode: -1, truncated: true);

        $result = $this->sandbox()->check('<?php while (true) echo "x";', [['label' => 'a', 'code' => '']]);

        $this->assertStringContainsString('printed more output than the checker can read', $result['error']);
    }

    #[DataProvider('brokenRunnerResponses')]
    public function test_a_broken_runner_makes_the_sandbox_unavailable(callable $response): void
    {
        Http::fake(['runner.test/run' => $response()]);

        $this->expectException(PhpSandboxUnavailable::class);

        $this->sandbox()->run('echo 1;');
    }

    public static function brokenRunnerResponses(): array
    {
        return [
            'wrong secret' => [fn () => Http::response(['message' => 'Invalid signature.'], 401)],
            'rejected body' => [fn () => Http::response(['message' => 'Invalid.'], 422)],
            'server error' => [fn () => Http::response('Server Error', 500)],
            'html page with 200' => [fn () => Http::response('<html>Cloud is waking up</html>', 200)],
            'json missing fields' => [fn () => Http::response(['stdout' => 'x'], 200)],
            'stdout not a string' => [fn () => Http::response(['stdout' => ['x'], 'exitCode' => 0, 'timedOut' => false, 'truncated' => false])],
            'exitCode not an int' => [fn () => Http::response(['stdout' => '', 'exitCode' => '0', 'timedOut' => false, 'truncated' => false])],
            'connection refused' => [fn () => Http::failedConnection()],
        ];
    }

    public function test_an_unconfigured_runner_makes_the_sandbox_unavailable(): void
    {
        Http::fake();

        $this->expectException(PhpSandboxUnavailable::class);

        $this->sandbox(url: null, secret: null)->run('echo 1;');
    }

    public function test_a_malformed_report_is_treated_as_missing(): void
    {
        $this->runnerReturns(self::MARKER.json_encode(['loaded' => true, 'output' => '', 'checks' => 'nope']));

        $result = $this->sandbox()->check('<?php echo 1;', []);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['loaded']);
        $this->assertSame('The checker could not read your code. Run it first to look for errors.', $result['error']);
    }
}
