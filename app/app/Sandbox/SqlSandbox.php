<?php

namespace App\Sandbox;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Entry point for running learner SQL. Every run gets a brand-new in-memory
 * copy of the lesson dataset, so learners can INSERT, UPDATE or DROP freely.
 */
class SqlSandbox
{
    public const MAX_SQL_LENGTH = 10_000;

    public function __construct(
        private readonly string $datasetPath,
        private readonly float $timeoutSeconds = 3.0,
        private readonly int $maxRows = 500,
    ) {}

    /**
     * @return array{results: list<array<string, mixed>>, error: ?array{message: string, statement: ?int}}
     */
    public function run(string $dataset, string $sql): array
    {
        $process = new Process([$this->phpBinary(), '-d', 'memory_limit=128M', base_path('bin/sql-runner.php')]);
        $process->setInput(json_encode([
            'setup' => $this->setupSql($dataset),
            'sql' => $sql,
            'max_rows' => $this->maxRows,
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return ['results' => [], 'error' => [
                'message' => sprintf(
                    'Your SQL ran for more than %s seconds and was stopped. Look for a query that never finishes, such as a recursive CTE without a stopping condition.',
                    rtrim(rtrim(number_format($this->timeoutSeconds, 1), '0'), '.'),
                ),
                'statement' => null,
            ]];
        }

        $output = json_decode($process->getOutput(), true);

        if (! $process->isSuccessful() || ! is_array($output)) {
            report(new RuntimeException('SQL runner failed: '.$process->getErrorOutput()));

            return ['results' => [], 'error' => [
                'message' => 'The sandbox could not run this SQL. It may use too much memory; try a smaller query.',
                'statement' => null,
            ]];
        }

        return $output;
    }

    /**
     * Runs the learner's SQL and the reference solution, then compares the last
     * result set each of them produced. With a check query, the check runs after
     * each of them and its results are compared instead, so a challenge can test
     * what the SQL changed.
     *
     * @return array{passed: bool, message: string, run: array, expected: ?array}
     */
    public function check(string $dataset, string $sql, string $solutionSql, bool $orderMatters, ?string $checkSql = null): array
    {
        // The newline ends a trailing "--" comment in the learner's SQL before the check starts.
        $withCheck = fn (string $script) => $checkSql === null ? $script : $script."\n;\n".$checkSql;

        $run = $this->run($dataset, $withCheck($sql));
        $expected = $this->lastRowSet($this->run($dataset, $withCheck($solutionSql))['results']);

        if ($expected === null) {
            throw new RuntimeException('Challenge solution does not return rows.');
        }

        $fail = fn (string $message) => ['passed' => false, 'message' => $message, 'run' => $run, 'expected' => $expected];

        if ($run['error'] !== null) {
            $failedInCheck = $checkSql !== null
                && $run['error']['statement'] !== null
                && $run['error']['statement'] >= count(StatementSplitter::split($sql));

            return $fail($failedInCheck
                ? 'Your SQL ran, but checking what it changed failed: '.$run['error']['message'].'. Make sure you made every change the challenge asks for.'
                : 'Your SQL has an error. Fix it and check again.');
        }

        $actual = $this->lastRowSet($run['results']);

        if ($actual === null) {
            return $fail('Your SQL ran but returned no result table. Finish with a SELECT query.');
        }

        $expectedColumns = count($expected['columns']);
        $actualColumns = count($actual['columns']);
        if ($expectedColumns !== $actualColumns) {
            return $fail("The result should have $expectedColumns ".str('column')->plural($expectedColumns).", yours has $actualColumns.");
        }

        $expectedRows = count($expected['rows']);
        $actualRows = count($actual['rows']);
        if ($expectedRows !== $actualRows) {
            return $fail("The result should have $expectedRows ".str('row')->plural($expectedRows).", yours has $actualRows.");
        }

        $wanted = $this->normalize($expected['rows']);
        $got = $this->normalize($actual['rows']);

        if ($wanted === $got) {
            return ['passed' => true, 'message' => 'Correct! Your query returns exactly the expected result.', 'run' => $run, 'expected' => $expected];
        }

        sort($wanted);
        sort($got);

        if ($wanted === $got) {
            return $orderMatters
                ? $fail('You have the right rows, but in the wrong order. Check your ORDER BY.')
                : ['passed' => true, 'message' => 'Correct! Your query returns exactly the expected result.', 'run' => $run, 'expected' => $expected];
        }

        return $fail('The shape is right, but some values differ from the expected result. Compare the two tables below.');
    }

    public function describe(string $dataset): array
    {
        return (new SqlExecutor)->describe($this->setupSql($dataset));
    }

    public function setupSql(string $dataset): string
    {
        $path = $this->datasetPath.DIRECTORY_SEPARATOR.$dataset.'.sql';

        if (preg_match('/^[a-z0-9_-]+$/', $dataset) !== 1 || ! is_file($path)) {
            throw new InvalidArgumentException("Unknown dataset [$dataset].");
        }

        return file_get_contents($path);
    }

    private function lastRowSet(array $results): ?array
    {
        $rowSets = array_values(array_filter($results, fn (array $result) => $result['type'] === 'rows'));

        return $rowSets === [] ? null : end($rowSets);
    }

    /**
     * Turns rows into comparable strings so 12.5, 12.50 and "12.5" all match.
     *
     * @return list<string>
     */
    private function normalize(array $rows): array
    {
        return array_map(fn (array $row) => json_encode(array_map(
            fn ($value) => is_int($value) || is_float($value) ? (string) round($value, 4) : $value,
            $row,
        )), $rows);
    }

    private function phpBinary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}
