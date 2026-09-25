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

function learnp_describe(mixed $value): string
{
    return match (true) {
        is_object($value) => 'an object of class '.$value::class,
        is_array($value) => 'an array with '.count($value).' items',
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
