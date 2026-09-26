<?php

namespace App\Runner;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs untrusted PHP in a child process. Every run gets its own temp folder,
 * which is deleted afterwards whatever happens.
 */
class PhpRunner
{
    // Unregistering the `php` stream wrapper closes the php://fd/N loophole: the child
    // inherits the parent's open file descriptors (log files, DB/Redis sockets, the
    // FastCGI connection), and php://fd lets learner code read them directly, bypassing
    // both open_basedir and the disabled socket functions. No closing tag and no
    // trailing newline, so this produces no stdout (display_errors goes to stdout too).
    private const SANDBOX_PREPEND = <<<'PHP'
        <?php stream_wrapper_unregister('php');
        PHP;

    public function __construct(
        private readonly string $workRoot,
        private readonly int $timeLimitSeconds = 5,
        private readonly int $maxOutputBytes = 65_536,
        // How much longer than $timeLimitSeconds the wall-clock kill waits, on top of PHP's own
        // max_execution_time. Under load, PHP's CPU-time check and Symfony's wall-clock timeout
        // race each other; a bigger margin here (tests raise it) avoids flaking on a busy machine.
        private readonly int $wallClockMarginSeconds = 1,
    ) {}

    /**
     * @param  array<string, string>  $files  File name => contents, all written to the same folder.
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    public function run(array $files, string $entry): array
    {
        $unresolvedDirectory = $this->workRoot.DIRECTORY_SEPARATOR.'run-'.Str::random(16);
        File::ensureDirectoryExists($unresolvedDirectory, 0700);

        try {
            // realpath() failing must not silently leave $directory as `false`: an empty
            // open_basedir setting below would mean no restriction at all.
            $directory = realpath($unresolvedDirectory) ?: throw new RuntimeException('Could not create the run folder.');

            foreach ($files as $name => $contents) {
                File::put($directory.DIRECTORY_SEPARATOR.$name, $contents);
            }

            File::put($directory.DIRECTORY_SEPARATOR.'.sandbox-prepend.php', self::SANDBOX_PREPEND);

            return $this->execute($directory, $entry);
        } finally {
            File::deleteDirectory($unresolvedDirectory);
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
        $process->setTimeout($this->timeLimitSeconds + $this->wallClockMarginSeconds);

        $stdout = '';
        $stderrBytes = 0;
        $timedOut = false;
        $truncated = false;

        try {
            $process->start();
            // stderr isn't returned to the caller, but it still counts toward the output budget:
            // otherwise a learner could write past the cap on stderr instead of stdout and run
            // unbounded until the (much longer) wall-clock timeout kills it.
            $truncated = $process->waitUntil(function (string $type, string $buffer) use (&$stdout, &$stderrBytes) {
                if ($type === Process::OUT) {
                    $stdout .= $buffer;
                } else {
                    $stderrBytes += strlen($buffer);
                }

                return strlen($stdout) + $stderrBytes > $this->maxOutputBytes;
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
            // tmpfile()/tempnam() ignore open_basedir and default to the system temp dir; pointing
            // them at the run folder means a killed run's leftover file is deleted with it instead
            // of accumulating on disk forever.
            'sys_temp_dir' => $directory,
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'ffi.enable' => '0',
            // Deliberately NOT setting sqlite3.extension_dir here: leaving it truly unset is what
            // makes SQLite3::loadExtension() refuse outright ("SQLite Extensions are disabled").
            // Explicitly setting it to '' looks equivalent (ini_get() returns "" either way) but
            // isn't: ext/sqlite3 treats "configured, even empty" as enabling extension loading with
            // an empty path prefix, which lets an absolute path load and run. Confirmed by hand.
            // Pdo\Sqlite::loadExtension() has no ini gate at all; see runner/README.md "Known limits".
            'auto_prepend_file' => $directory.DIRECTORY_SEPARATOR.'.sandbox-prepend.php',
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
