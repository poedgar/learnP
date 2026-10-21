<?php

namespace Tests\Concerns;

use Illuminate\Process\Pool;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Runs many small PHP scripts with the CLI, several at a time. Content tests run thousands
 * of snippets, each in a process of its own so classes can't clash; running them in parallel
 * batches instead of one by one keeps the suite fast.
 */
trait RunsPhpScripts
{
    /**
     * @param  array<array-key, array{files: array<string, string>, entry: string}>  $scripts
     * @return array<array-key, array{output: string, errors: string, successful: bool}> each script's standard output and error output, and whether it exited cleanly, with the same keys
     */
    protected function runPhpScripts(array $scripts): array
    {
        $outputs = [];
        $root = sys_get_temp_dir().'/learnp-scripts-'.uniqid();

        try {
            foreach (array_chunk($scripts, $this->phpScriptConcurrency(), preserve_keys: true) as $batch) {
                $results = Process::pool(function (Pool $pool) use ($batch, $root) {
                    foreach ($batch as $key => $script) {
                        $directory = $root.'/'.md5((string) $key);
                        File::ensureDirectoryExists($directory);
                        foreach ($script['files'] as $name => $contents) {
                            File::put($directory.'/'.$name, $contents);
                        }

                        $pool->as((string) $key)->path($directory)->command([PHP_BINARY, '-d', 'display_errors=1', $script['entry']]);
                    }
                })->start()->wait();

                foreach (array_keys($batch) as $key) {
                    $result = $results[(string) $key];
                    $outputs[$key] = ['output' => $result->output(), 'errors' => $result->errorOutput(), 'successful' => $result->successful()];
                }
            }
        } finally {
            File::deleteDirectory($root);
        }

        return $outputs;
    }

    private function phpScriptConcurrency(): int
    {
        $cores = (int) trim((string) @shell_exec('getconf _NPROCESSORS_ONLN 2>/dev/null'));

        return max(2, min(16, $cores ?: 4));
    }
}
