<?php

namespace App\Runner;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs untrusted PHP in a child process. Every run gets its own temp folder,
 * which is deleted afterwards whatever happens.
 */
class PhpRunner
{
    public function __construct(
        private readonly string $workRoot,
        private readonly int $timeLimitSeconds = 5,
        private readonly int $maxOutputBytes = 65_536,
    ) {}

    /**
     * @param  array<string, string>  $files  File name => contents, all written to the same folder.
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    public function run(array $files, string $entry): array
    {
        $directory = $this->workRoot.DIRECTORY_SEPARATOR.'run-'.Str::random(16);
        File::ensureDirectoryExists($directory, 0700);

        try {
            $directory = realpath($directory);

            foreach ($files as $name => $contents) {
                File::put($directory.DIRECTORY_SEPARATOR.$name, $contents);
            }

            return $this->execute($directory, $entry);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    private function execute(string $directory, string $entry): array
    {
        // Symfony merges the parent environment into the child's; `false` removes a variable.
        $environment = array_fill_keys(array_keys(getenv() + $_ENV + $_SERVER), false);
        $process = new Process([$this->phpBinary(), '-n', ...$this->iniFlags($directory), $entry], $directory, $environment);
        // PHP's max_execution_time stops busy loops with a proper error; this catches code that sleeps or blocks.
        $process->setTimeout($this->timeLimitSeconds + 1);

        $stdout = '';
        $timedOut = false;
        $truncated = false;

        try {
            $process->start();
            $truncated = $process->waitUntil(function (string $type, string $buffer) use (&$stdout) {
                if ($type === Process::OUT) {
                    $stdout .= $buffer;
                }

                return strlen($stdout) > $this->maxOutputBytes;
            });
        } catch (ProcessTimedOutException) {
            $timedOut = true;
        }

        if ($truncated) {
            $process->stop(0);
        }

        return [
            'stdout' => substr($stdout, 0, $this->maxOutputBytes),
            'exitCode' => $timedOut || $truncated ? -1 : ($process->getExitCode() ?? -1),
            'timedOut' => $timedOut,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return list<string>
     */
    private function iniFlags(string $directory): array
    {
        $settings = [
            'open_basedir' => $directory,
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'ffi.enable' => '0',
            'disable_functions' => implode(',', DisabledFunctions::for($this->phpBinary())),
            'memory_limit' => '64M',
            'max_execution_time' => (string) $this->timeLimitSeconds,
            'display_errors' => 'stdout',
            'log_errors' => '0',
            'html_errors' => '0',
            'error_reporting' => (string) E_ALL,
            'date.timezone' => 'UTC',
        ];

        return collect($settings)->flatMap(fn (string $value, string $key) => ['-d', "$key=$value"])->values()->all();
    }

    private function phpBinary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}
