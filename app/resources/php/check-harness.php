<?php

/*
 * Checks a learner's answer to a PHP challenge.
 *
 * Expects two files next to it: main.php (the learner's code) and checks.json
 * ([{"label": "...", "code": "..."}]). It runs main.php, then each check's code
 * with eval(). A check passes when its code returns true; returning a string
 * fails it with that message. The report is printed as JSON after REPORT_MARKER.
 *
 * Used in the browser by resources/js/lib/php/checkChallenge.js and on the
 * server by tests/Feature/PhpChallengeSolutionsTest.php.
 */

const LEARNP_REPORT_MARKER = "\n@@LEARNP_REPORT@@\n";

/** Compares strictly and explains the difference. For use inside check code. */
function expect_same(mixed $actual, mixed $expected): true|string
{
    if ($actual === $expected) {
        return true;
    }

    return 'Expected '.learnp_describe($expected).', got '.learnp_describe($actual).'.';
}

/** Passes when $callback throws $class. For use inside check code. */
function expect_throws(string $class, callable $callback): true|string
{
    try {
        $callback();
    } catch (Throwable $e) {
        return $e instanceof $class
            ? true
            : 'Expected '.$class.' to be thrown, got '.$e::class.': '.$e->getMessage();
    }

    return "Expected $class to be thrown, but nothing was thrown.";
}

/** Everything the learner's code has printed. For use inside check code. */
function learnp_output(): string
{
    return (string) ob_get_contents();
}

/** The learner's code, as written. For use inside check code. */
function learnp_code(): string
{
    return (string) file_get_contents(__DIR__.'/main.php');
}

/**
 * Runs a variant of a simple script (one that declares no functions or classes)
 * in its own scope, and returns what it printed. For use inside check code, to
 * see that a result is worked out rather than typed in.
 */
function learnp_run(string $code): string
{
    ob_start();
    try {
        (static function (string $learnpCode): void {
            eval('?>'.$learnpCode);
        })($code);
    } finally {
        $output = (string) ob_get_clean();
    }

    return $output;
}

/**
 * A Dockerfile's instructions, for checks on the Docker course: comments and blank lines are
 * skipped, lines ending in a backslash are joined, runs of whitespace become one space, and each
 * instruction comes back as ['instruction' => 'FROM', 'args' => 'php:8.4-cli']. For use inside check code.
 *
 * @return list<array{instruction: string, args: string}>
 */
function learnp_dockerfile(string $dockerfile): array
{
    $instructions = [];
    $pending = '';

    foreach (preg_split('/\r?\n/', $dockerfile) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if (str_ends_with($trimmed, '\\')) {
            $pending .= substr($trimmed, 0, -1).' ';

            continue;
        }

        $full = trim(preg_replace('/\s+/', ' ', $pending.$trimmed));
        $pending = '';
        [$instruction, $args] = array_pad(explode(' ', $full, 2), 2, '');
        $instructions[] = ['instruction' => strtoupper($instruction), 'args' => $args];
    }

    if (trim($pending) !== '') {
        $full = trim(preg_replace('/\s+/', ' ', $pending));
        [$instruction, $args] = array_pad(explode(' ', $full, 2), 2, '');
        $instructions[] = ['instruction' => strtoupper($instruction), 'args' => $args];
    }

    return $instructions;
}

/**
 * Splits a shell command into words as a shell would for ordinary commands: whitespace separates
 * words, single and double quotes group them, a backslash escapes the next character, and a
 * backslash before a line break joins the lines. For use inside check code.
 *
 * @return list<string>
 */
function learnp_shell_words(string $command): array
{
    $command = preg_replace('/\\\\\r?\n/', ' ', $command);
    $words = [];
    $word = null;
    $quote = null;
    $length = strlen($command);

    for ($i = 0; $i < $length; $i++) {
        $char = $command[$i];

        if ($quote === "'") {
            $char === "'" ? $quote = null : $word .= $char;
        } elseif ($quote === '"') {
            if ($char === '\\' && $i + 1 < $length && in_array($command[$i + 1], ['"', '\\', '$'], true)) {
                $word .= $command[++$i];
            } elseif ($char === '"') {
                $quote = null;
            } else {
                $word .= $char;
            }
        } elseif ($char === "'" || $char === '"') {
            $quote = $char;
            $word ??= '';
        } elseif ($char === '\\' && $i + 1 < $length) {
            $word = ($word ?? '').$command[++$i];
        } elseif (learnp_is_space($char)) {
            if ($word !== null) {
                $words[] = $word;
                $word = null;
            }
        } else {
            $word = ($word ?? '').$char;
        }
    }

    if ($word !== null) {
        $words[] = $word;
    }

    return $words;
}

/**
 * Reads a `docker run` (or `docker container run`) command for checks on the Docker course, or
 * returns null for any other command. Short options are named like their long forms (-d is detach,
 * -p is publish, -it is interactive and tty), so either spelling passes:
 * ['options' => ['publish' => ['8080:80']], 'flags' => ['detach'], 'image' => 'nginx:1.27', 'args' => []].
 *
 * @return array{options: array<string, list<string>>, flags: list<string>, image: ?string, args: list<string>}|null
 */
function learnp_docker_run(string $command): ?array
{
    $words = learnp_shell_words($command);
    $start = match (true) {
        array_slice($words, 0, 2) === ['docker', 'run'] => 2,
        array_slice($words, 0, 3) === ['docker', 'container', 'run'] => 3,
        default => null,
    };
    if ($start === null) {
        return null;
    }

    $short = ['d' => 'detach', 'i' => 'interactive', 't' => 'tty', 'p' => 'publish', 'e' => 'env', 'v' => 'volume', 'w' => 'workdir', 'u' => 'user', 'l' => 'label'];
    $takesValue = ['publish', 'env', 'name', 'volume', 'network', 'workdir', 'user', 'env-file', 'mount', 'restart', 'label', 'entrypoint', 'hostname', 'platform', 'memory', 'cpus', 'add-host', 'expose'];
    $options = [];
    $flags = [];

    for ($i = $start; $i < count($words); $i++) {
        $word = $words[$i];

        if (str_starts_with($word, '--')) {
            [$name, $value] = array_pad(explode('=', substr($word, 2), 2), 2, null);
            if (in_array($name, $takesValue, true)) {
                $options[$name][] = $value ?? ($words[++$i] ?? '');
            } else {
                $flags[] = $name;
            }

            continue;
        }

        if (str_starts_with($word, '-') && strlen($word) > 1) {
            $letters = substr($word, 1);
            for ($j = 0; $j < strlen($letters); $j++) {
                $name = $short[$letters[$j]] ?? $letters[$j];
                if (in_array($name, $takesValue, true)) {
                    $rest = substr($letters, $j + 1);
                    $options[$name][] = $rest !== '' ? ltrim($rest, '=') : ($words[++$i] ?? '');

                    break;
                }
                $flags[] = $name;
            }

            continue;
        }

        return ['options' => $options, 'flags' => array_values(array_unique($flags)), 'image' => $word, 'args' => array_slice($words, $i + 1)];
    }

    return ['options' => $options, 'flags' => array_values(array_unique($flags)), 'image' => null, 'args' => []];
}

function learnp_is_space(string $char): bool
{
    return $char === ' ' || $char === "\t" || $char === "\n" || $char === "\r";
}

function learnp_describe(mixed $value): string
{
    if (is_array($value)) {
        // Short arrays are shown in full, so the learner can see what differs.
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return $json !== false && strlen($json) <= 160 ? $json : 'an array with '.count($value).' items';
    }

    return match (true) {
        is_object($value) => 'an object of class '.$value::class,
        default => var_export($value, true),
    };
}

$learnpState = [
    'checks' => json_decode(file_get_contents(__DIR__.'/checks.json'), true),
    'loaded' => false,
    'results' => [],
];

register_shutdown_function(function () use (&$learnpState) {
    $output = '';
    while (ob_get_level() > 0) {
        $output = ob_get_clean().$output;
    }

    $fatal = null;
    $last = error_get_last();
    if ($last !== null && in_array($last['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        $fatal = $last['message'].' on line '.$last['line'];
    }

    // A fatal error stops the script, so some checks may never have run.
    foreach (array_slice($learnpState['checks'], count($learnpState['results'])) as $check) {
        $learnpState['results'][] = [
            'label' => $check['label'],
            'passed' => false,
            'message' => $learnpState['loaded'] ? 'Stopped by a fatal error: '.$fatal : 'Fix the error in your code first.',
        ];
    }

    echo $output, LEARNP_REPORT_MARKER, json_encode([
        'loaded' => $learnpState['loaded'],
        'error' => $learnpState['error'] ?? $fatal,
        'output' => $output,
        'checks' => $learnpState['results'],
    ], JSON_INVALID_UTF8_SUBSTITUTE);
});

ob_start();

try {
    require __DIR__.'/main.php';
    $learnpState['loaded'] = true;
} catch (Throwable $e) {
    $learnpState['error'] = $e::class.': '.$e->getMessage().' on line '.$e->getLine();
}

foreach ($learnpState['checks'] as $check) {
    if (! $learnpState['loaded']) {
        $learnpState['results'][] = ['label' => $check['label'], 'passed' => false, 'message' => 'Fix the error in your code first.'];

        continue;
    }

    try {
        $result = (static fn () => eval($check['code']))();
        $learnpState['results'][] = [
            'label' => $check['label'],
            'passed' => $result === true,
            'message' => $result === true ? null : (is_string($result) ? $result : 'This check did not pass.'),
        ];
    } catch (Throwable $e) {
        $learnpState['results'][] = ['label' => $check['label'], 'passed' => false, 'message' => $e->getMessage()];
    }
}
