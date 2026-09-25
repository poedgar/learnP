<?php

namespace App\Sandbox;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Runs learner PHP on the runner app (runner/README.md). The runner holds no
 * data or secrets, so learner code never runs next to this app's database.
 */
class HttpPhpSandbox implements PhpSandbox
{
    private const REPORT_MARKER = "\n@@LEARNP_REPORT@@\n";

    public function __construct(
        private readonly ?string $url,
        private readonly ?string $secret,
        private readonly string $harnessPath,
        private readonly int $timeLimitSeconds = 5,
    ) {}

    public function run(string $code): array
    {
        $started = hrtime(true);
        $result = $this->send(['main.php' => $this->withOpeningTag($code)], 'main.php');

        return [
            'output' => $this->describeStop(ltrim($result['stdout'], "\n"), $result),
            'fatal' => $result['exitCode'] !== 0 || $result['timedOut'],
            'durationMs' => (int) round((hrtime(true) - $started) / 1_000_000),
        ];
    }

    public function check(string $code, array $checks): array
    {
        $result = $this->send([
            'main.php' => $this->withOpeningTag($code),
            'checks.json' => json_encode($checks, JSON_THROW_ON_ERROR),
            'harness.php' => file_get_contents($this->harnessPath),
        ], 'harness.php');

        // The harness prints its report last, so a marker echoed by learner code comes earlier.
        $position = strrpos($result['stdout'], self::REPORT_MARKER);
        $report = $position === false ? null : json_decode(substr($result['stdout'], $position + strlen(self::REPORT_MARKER)), true);

        if (! $this->isValidReport($report)) {
            return [
                'passed' => false,
                'loaded' => false,
                'error' => match (true) {
                    $result['timedOut'] => "Your code ran for more than {$this->timeLimitSeconds} seconds and was stopped. Look for a loop that never ends.",
                    $result['truncated'] => 'Your code printed more output than the checker can read. Look for an echo inside a loop that never ends.',
                    default => 'The checker could not read your code. Run it first to look for errors.',
                },
                'output' => $this->describeStop(ltrim($result['stdout'], "\n"), $result),
                'checks' => array_map(
                    fn (array $check) => ['label' => $check['label'], 'passed' => false, 'message' => 'Fix the error in your code first.'],
                    $checks,
                ),
            ];
        }

        return [
            ...$report,
            'output' => ltrim($report['output'], "\n"),
            'passed' => $report['loaded'] && collect($report['checks'])->every(fn (array $check) => $check['passed'] === true),
        ];
    }

    /**
     * @param  array<string, string>  $files
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    private function send(array $files, string $entry): array
    {
        if (blank($this->url) || blank($this->secret)) {
            throw new PhpSandboxUnavailable('RUNNER_URL and RUNNER_SECRET must be set.');
        }

        $body = json_encode(['files' => $files, 'entry' => $entry], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->getTimestamp();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Timestamp' => $timestamp,
                'X-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, $this->secret),
            ])
                ->withBody($body, 'application/json')
                ->timeout($this->timeLimitSeconds * 2)
                ->post(rtrim($this->url, '/').'/run');
        } catch (ConnectionException $e) {
            throw new PhpSandboxUnavailable('Could not reach the runner: '.$e->getMessage(), previous: $e);
        }

        $result = $response->json();

        if (! $response->successful() || ! is_array($result)
            || ! is_string($result['stdout'] ?? null)
            || ! is_int($result['exitCode'] ?? null)
            || ! is_bool($result['timedOut'] ?? null)
            || ! is_bool($result['truncated'] ?? null)
        ) {
            throw new PhpSandboxUnavailable("The runner responded with {$response->status()}: ".Str::limit($response->body(), 200));
        }

        return $result;
    }

    /** Guards against a harness report (or a lookalike printed by learner code) that doesn't match the expected shape. */
    private function isValidReport(mixed $report): bool
    {
        return is_array($report)
            && is_bool($report['loaded'] ?? null)
            && is_string($report['output'] ?? null)
            && is_array($report['checks'] ?? null)
            && collect($report['checks'])->every(fn (mixed $check) => is_array($check)
                && is_string($check['label'] ?? null)
                && is_bool($check['passed'] ?? null));
    }

    private function describeStop(string $output, array $result): string
    {
        return match (true) {
            $result['timedOut'] => $output."\n\nStopped after {$this->timeLimitSeconds} seconds.",
            $result['truncated'] => $output."\n…output truncated",
            default => $output,
        };
    }

    /** Snippets from the theory may leave out the opening tag, as in the web sandbox. */
    private function withOpeningTag(string $code): string
    {
        return str_starts_with(ltrim($code), '<?php') ? $code : "<?php\n\n".$code;
    }
}
