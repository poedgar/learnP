<?php

namespace App\Sandbox;

use PDO;
use Pdo\Sqlite;
use PDOException;
use PDOStatement;

/**
 * Runs learner SQL against a throwaway in-memory SQLite database.
 *
 * This class does no time limiting on its own: SqlSandbox runs it in a child
 * process (bin/sql-runner.php) so a runaway query can be killed safely.
 */
final class SqlExecutor
{
    /** Commands that could touch the file system or the SQLite engine itself. */
    private const FORBIDDEN_KEYWORDS = ['ATTACH', 'DETACH', 'VACUUM', 'LOAD_EXTENSION'];

    /** Read-only PRAGMAs that help learners inspect a table. */
    private const ALLOWED_PRAGMAS = ['TABLE_INFO', 'TABLE_XINFO', 'TABLE_LIST', 'INDEX_LIST', 'FOREIGN_KEY_LIST'];

    // SQLite authorizer action codes (sqlite3.h).
    private const SQLITE_PRAGMA = 19;

    private const SQLITE_ATTACH = 24;

    private const SQLITE_DETACH = 25;

    private const SQLITE_FUNCTION = 31;

    public function __construct(
        private readonly int $maxRows = 500,
        private readonly int $maxStatements = 25,
    ) {}

    /**
     * @return array{results: list<array<string, mixed>>, error: ?array{message: string, statement: ?int}}
     */
    public function execute(string $setupSql, string $script): array
    {
        $statements = StatementSplitter::split($script);
        $results = [];

        if ($statements === []) {
            return ['results' => [], 'error' => ['message' => 'There is no SQL to run. Write a query first.', 'statement' => null]];
        }

        if (count($statements) > $this->maxStatements) {
            return ['results' => [], 'error' => [
                'message' => "Run at most {$this->maxStatements} statements at a time.",
                'statement' => null,
            ]];
        }

        $pdo = $this->connect($setupSql);

        foreach ($statements as $index => $statement) {
            $problem = $this->forbiddenReason($statement['keywords']);

            if ($problem !== null) {
                return ['results' => $results, 'error' => ['message' => $problem, 'statement' => $index]];
            }

            try {
                $results[] = $this->runStatement($pdo, $statement['sql'], $statement['keywords']);
            } catch (PDOException $e) {
                return ['results' => $results, 'error' => [
                    'message' => $this->cleanMessage($e),
                    'statement' => $index,
                ]];
            }
        }

        return ['results' => $results, 'error' => null];
    }

    /**
     * Describes every table in a dataset: its columns and how many rows it holds.
     *
     * @return list<array{name: string, row_count: int, columns: list<array{name: string, type: string, primary_key: bool}>}>
     */
    public function describe(string $setupSql): array
    {
        $pdo = $this->connect($setupSql);
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_map(function (string $table) use ($pdo) {
            $quoted = '"'.str_replace('"', '""', $table).'"';
            $columns = $pdo->query("PRAGMA table_info($quoted)")->fetchAll(PDO::FETCH_ASSOC);

            return [
                'name' => $table,
                'row_count' => (int) $pdo->query("SELECT COUNT(*) FROM $quoted")->fetchColumn(),
                'columns' => array_map(fn (array $column) => [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'primary_key' => (bool) $column['pk'],
                ], $columns),
            ];
        }, $tables);
    }

    private function connect(string $setupSql): PDO
    {
        $pdo = class_exists(Sqlite::class)
            ? new Sqlite('sqlite::memory:')
            : new PDO('sqlite::memory:');

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec($setupSql);

        // Defence in depth on top of the keyword check: let SQLite itself
        // refuse dangerous operations when the PHP build supports it.
        if (method_exists($pdo, 'setAuthorizer')) {
            $pdo->setAuthorizer(function (int $action, ?string $arg1, ?string $arg2) {
                return match (true) {
                    $action === self::SQLITE_ATTACH, $action === self::SQLITE_DETACH => Sqlite::DENY,
                    $action === self::SQLITE_PRAGMA => in_array(strtoupper((string) $arg1), self::ALLOWED_PRAGMAS, true)
                        ? Sqlite::OK
                        : Sqlite::DENY,
                    $action === self::SQLITE_FUNCTION => strtolower((string) $arg2) === 'load_extension'
                        ? Sqlite::DENY
                        : Sqlite::OK,
                    default => Sqlite::OK,
                };
            });
        }

        return $pdo;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function forbiddenReason(array $keywords): ?string
    {
        foreach ($keywords as $position => $keyword) {
            if (in_array($keyword, self::FORBIDDEN_KEYWORDS, true)) {
                return "$keyword isn't available in the sandbox.";
            }

            if ($keyword === 'PRAGMA' && ! in_array($keywords[$position + 1] ?? '', self::ALLOWED_PRAGMAS, true)) {
                return 'Only read-only PRAGMAs such as PRAGMA table_info(books) are available in the sandbox.';
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $keywords
     * @return array<string, mixed>
     */
    private function runStatement(PDO $pdo, string $sql, array $keywords): array
    {
        $started = hrtime(true);
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $columnCount = $statement->columnCount();

        if ($columnCount === 0) {
            $isDataChange = in_array($keywords[0], ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'], true)
                || ($keywords[0] === 'WITH' && array_intersect(['INSERT', 'UPDATE', 'DELETE', 'REPLACE'], $keywords) !== []);

            return [
                'type' => 'command',
                'sql' => $sql,
                'affected_rows' => $isDataChange ? $statement->rowCount() : null,
                'duration_ms' => $this->elapsed($started),
            ];
        }

        return ['type' => 'rows', 'sql' => $sql, ...$this->fetchRows($statement, $columnCount), 'duration_ms' => $this->elapsed($started)];
    }

    private function fetchRows(PDOStatement $statement, int $columnCount): array
    {
        $columns = [];
        for ($i = 0; $i < $columnCount; $i++) {
            $columns[] = $statement->getColumnMeta($i)['name'] ?? "column_$i";
        }

        $rows = [];
        $truncated = false;

        while (($row = $statement->fetch(PDO::FETCH_NUM)) !== false) {
            if (count($rows) === $this->maxRows) {
                $truncated = true;
                break;
            }
            $rows[] = array_map(
                fn ($value) => is_string($value) && ! mb_check_encoding($value, 'UTF-8') ? '<binary data>' : $value,
                $row,
            );
        }

        $statement->closeCursor();

        return ['columns' => $columns, 'rows' => $rows, 'truncated' => $truncated];
    }

    private function cleanMessage(PDOException $e): string
    {
        $message = $e->errorInfo[2] ?? $e->getMessage();

        if (str_contains($message, 'not authorized')) {
            return "That command isn't available in the sandbox.";
        }

        return ucfirst(preg_replace('/^SQLSTATE\[\w+\]: [^:]+: \d+ /', '', $message));
    }

    private function elapsed(int $started): float
    {
        return round((hrtime(true) - $started) / 1e6, 2);
    }
}
