<?php

/*
 * Child process used by App\Sandbox\SqlSandbox. It reads a JSON payload
 * ({"setup": "...", "sql": "..."}) from STDIN, runs the SQL in a fresh
 * in-memory database and writes the JSON result to STDOUT. Running it in its
 * own process lets the parent kill queries that never finish.
 */

use App\Sandbox\SqlExecutor;

// Load only what's needed instead of the Composer autoloader: it keeps each run fast.
require __DIR__.'/../app/Sandbox/StatementSplitter.php';
require __DIR__.'/../app/Sandbox/SqlExecutor.php';

$payload = json_decode(stream_get_contents(STDIN), true);

if (! is_array($payload) || ! is_string($payload['setup'] ?? null) || ! is_string($payload['sql'] ?? null)) {
    fwrite(STDERR, 'Invalid payload');
    exit(2);
}

$result = (new SqlExecutor(maxRows: (int) ($payload['max_rows'] ?? 500)))
    ->execute($payload['setup'], $payload['sql']);

echo json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
