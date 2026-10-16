<?php

// Lesson 2 of the Testing without frameworks course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'a-tiny-test-runner',
    'title' => 'A tiny test runner',
    'summary' => 'Run many tests and report every failure: catching exceptions per test, fresh state for each test, test classes found with reflection, data providers and captured output.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class AssertionFailed extends Exception {}

        $tests = [
            'counts words' => function () {
                if (str_word_count('the left hand of darkness') !== 5) {
                    throw new AssertionFailed('expected 5 words');
                }
            },
            'reverses' => function () {
                if (strrev('abc') !== 'cab') {
                    throw new AssertionFailed('expected cab');
                }
            },
        ];

        foreach ($tests as $name => $test) {
            try {
                $test();
                echo "✓ $name\n";
            } catch (Throwable $e) {
                echo "✗ $name: {$e->getMessage()}\n";
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Why does a test runner wrap each test in its own `try`/`catch`?',
            'options' => [
                'To hide failures',
                'So one failing test is recorded and the remaining tests still run',
                'Because tests always throw',
                'To make the tests faster',
            ],
            'correct_option' => 1,
            'explanation' => 'Without it, the first failure stops the run and you never learn about the others.',
        ],
        [
            'question' => 'Why catch `Throwable` rather than only `AssertionFailed`?',
            'options' => [
                'There\'s no difference',
                'A `TypeError` or a bug in the code under test should be reported as a failing test, not crash the whole run',
                '`AssertionFailed` can\'t be caught',
                'So that errors are ignored',
            ],
            'correct_option' => 1,
            'explanation' => '`Throwable` covers both exceptions and errors such as `TypeError` and `DivisionByZeroError`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class AssertionFailed extends Exception {}

                $results = [];
                foreach ([fn () => null, fn () => throw new AssertionFailed('x'), fn () => intdiv(1, 0)] as $test) {
                    try {
                        $test();
                        $results[] = 'P';
                    } catch (AssertionFailed) {
                        $results[] = 'F';
                    } catch (Throwable) {
                        $results[] = 'E';
                    }
                }
                echo implode('', $results);
                PHP,
            'options' => ['`PFE`', '`PFF`', '`PEE`', '`P`'],
            'correct_option' => 0,
            'explanation' => 'The second test fails an assertion; the third breaks with a `DivisionByZeroError`, which is an error.',
        ],
        [
            'question' => 'Why should every test get fresh objects?',
            'options' => [
                'It uses less memory',
                'So tests don\'t depend on each other: a test that changes shared state could make a later one pass or fail depending on the order',
                'PHP can\'t reuse objects',
                'To make tests slower and more realistic',
            ],
            'correct_option' => 1,
            'explanation' => 'Independent tests give the same results in any order, and on their own.',
        ],
        [
            'question' => 'In a PHPUnit-style test class, which methods are tests?',
            'options' => [
                'Every method',
                'Public methods whose names start with `test`',
                'Static methods',
                'Methods with a return type of `bool`',
            ],
            'correct_option' => 1,
            'explanation' => 'Reflection lists the public methods, and the runner keeps those named `test…`.',
        ],
        [
            'question' => 'What is a data provider?',
            'options' => [
                'A database used by tests',
                'A list of input and expected-output cases fed to one test, each reported separately',
                'A function that creates random data',
                'A mock object',
            ],
            'correct_option' => 1,
            'explanation' => 'One test, many cases: the leap-year example checks four years with the same code.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                ob_start();
                echo 'inside';
                $caught = ob_get_clean();
                echo strtoupper($caught);
                PHP,
            'options' => ['`INSIDE`', '`insideINSIDE`', '`inside`', 'Nothing'],
            'correct_option' => 0,
            'explanation' => 'The first `echo` goes into the buffer, which `ob_get_clean()` returns and closes. Only the upper-cased copy is printed.',
        ],
        [
            'question' => 'Why close the output buffer in a `finally` block when capturing a test\'s output?',
            'options' => [
                'Buffers close themselves anyway',
                'If the code throws between `ob_start()` and `ob_get_clean()`, the buffer would stay open and swallow later output',
                '`finally` makes it faster',
                'It\'s required by PHP',
            ],
            'correct_option' => 1,
            'explanation' => '`finally` runs whether or not the code threw, so the buffer is always closed.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Run them all',
            'prompt' => "Write `runTests(array \$tests): array`. `\$tests` maps names to closures. Run each one, and return `['passed' => [...names], 'failed' => [name => message, ...]]`, both in the order the tests were given. Anything thrown (`Throwable`) is a failure, with the exception's message.",
            'starter_code' => <<<'PHP'
                <?php

                function runTests(array $tests): array
                {
                    $passed = [];
                    foreach ($tests as $name => $test) {
                        $test();
                        $passed[] = $name;
                    }

                    return ['passed' => $passed, 'failed' => []];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runTests(array $tests): array
                {
                    $results = ['passed' => [], 'failed' => []];

                    foreach ($tests as $name => $test) {
                        try {
                            $test();
                            $results['passed'][] = $name;
                        } catch (Throwable $e) {
                            $results['failed'][$name] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Put the call in `try`, add the name to `passed` after it, and record `$e->getMessage()` in `failed` in the `catch (Throwable $e)`.',
            'checks' => [
                ['label' => 'All passing', 'code' => "return expect_same(runTests(['a' => fn () => 1, 'b' => fn () => 2]), ['passed' => ['a', 'b'], 'failed' => []]);"],
                ['label' => 'Failures are recorded and the rest still run', 'code' => "\$tests = [\n    'one' => fn () => null,\n    'two' => fn () => throw new RuntimeException('broken'),\n    'three' => fn () => intdiv(1, 0),\n    'four' => fn () => null,\n];\nreturn expect_same(runTests(\$tests), ['passed' => ['one', 'four'], 'failed' => ['two' => 'broken', 'three' => 'Division by zero']]);"],
            ],
        ],
        [
            'title' => 'The report',
            'prompt' => 'Write `report(array $results): string` for results shaped like `runTests()` returns. With no failures it returns `OK (N tests)`. Otherwise it returns `FAILURES! P passed, F failed`, followed by one line per failure: `- name: message`, joined with `\\n`.',
            'starter_code' => <<<'PHP'
                <?php

                function report(array $results): string
                {
                    return count($results['passed']) . ' passed';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function report(array $results): string
                {
                    $passed = count($results['passed']);
                    $failed = count($results['failed']);

                    if ($failed === 0) {
                        return "OK ($passed tests)";
                    }

                    $lines = ["FAILURES! $passed passed, $failed failed"];
                    foreach ($results['failed'] as $name => $message) {
                        $lines[] = "- $name: $message";
                    }

                    return implode("\n", $lines);
                }
                PHP,
            'hint' => 'Count both lists. For failures, build an array of lines starting with the summary, then `implode("\n", $lines)`.',
            'checks' => [
                ['label' => 'All passing', 'code' => "return expect_same(report(['passed' => ['a', 'b', 'c'], 'failed' => []]), 'OK (3 tests)');"],
                ['label' => 'Failures listed', 'code' => "return expect_same(report(['passed' => ['a'], 'failed' => ['b' => 'wrong total', 'c' => 'Division by zero']]), \"FAILURES! 1 passed, 2 failed\\n- b: wrong total\\n- c: Division by zero\");"],
            ],
        ],
        [
            'title' => 'Test classes',
            'prompt' => "Write `runTestCase(string \$class): array`. For each public method of `\$class` whose name starts with `test`, in the order written: make a **new** object, call its `setUp()` method if it has one, then call the test method. Return `['passed' => [...], 'failed' => [method => message]]`.",
            'starter_code' => <<<'PHP'
                <?php

                function runTestCase(string $class): array
                {
                    $case = new $class();
                    $results = ['passed' => [], 'failed' => []];

                    foreach (get_class_methods($case) as $method) {
                        try {
                            $case->$method();
                            $results['passed'][] = $method;
                        } catch (Throwable $e) {
                            $results['failed'][$method] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runTestCase(string $class): array
                {
                    $results = ['passed' => [], 'failed' => []];
                    $methods = (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        $name = $method->getName();
                        if (! str_starts_with($name, 'test')) {
                            continue;
                        }

                        $case = new $class();
                        try {
                            if (method_exists($case, 'setUp')) {
                                $case->setUp();
                            }
                            $case->$name();
                            $results['passed'][] = $name;
                        } catch (Throwable $e) {
                            $results['failed'][$name] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Use `(new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC)`, skip names not starting with `test`, and create `new $class()` inside the loop.',
            'checks' => [
                ['label' => 'Only test methods run, each on a fresh object with setUp()', 'code' => "eval('final class BasketTest\n{\n    private array \$items;\n\n    public function setUp(): void\n    {\n        \$this->items = [\"Dune\"];\n    }\n\n    public function testStartsWithOne(): void\n    {\n        if (count(\$this->items) !== 1) {\n            throw new Exception(\"expected 1 item\");\n        }\n    }\n\n    public function testAdding(): void\n    {\n        \$this->items[] = \"Emma\";\n        if (count(\$this->items) !== 2) {\n            throw new Exception(\"expected 2 items\");\n        }\n    }\n\n    public function testStillOne(): void\n    {\n        if (count(\$this->items) !== 1) {\n            throw new Exception(\"state leaked between tests\");\n        }\n    }\n\n    public function helperNotATest(): void\n    {\n        throw new Exception(\"helpers must not run\");\n    }\n}');\nreturn expect_same(runTestCase('BasketTest'), ['passed' => ['testStartsWithOne', 'testAdding', 'testStillOne'], 'failed' => []]);"],
                ['label' => 'Failures are reported by method name', 'code' => "eval('final class MathTest\n{\n    public function testAdds(): void {}\n\n    public function testBreaks(): void\n    {\n        throw new Exception(\"no\");\n    }\n\n    private function testPrivate(): void\n    {\n        throw new Exception(\"private methods are not tests\");\n    }\n}');\nreturn expect_same(runTestCase('MathTest'), ['passed' => ['testAdds'], 'failed' => ['testBreaks' => 'no']]);"],
            ],
        ],
        [
            'title' => 'A data provider',
            'prompt' => "Write `runWithData(string \$name, Closure \$test, array \$cases): array`. Each case is an array of arguments; call `\$test(...\$case)` for every case, and name each run `name [key]` using the case's key. Return `['passed' => [...], 'failed' => [runName => message]]`.",
            'starter_code' => <<<'PHP'
                <?php

                function runWithData(string $name, Closure $test, array $cases): array
                {
                    try {
                        foreach ($cases as $case) {
                            $test(...$case);
                        }
                    } catch (Throwable $e) {
                        return ['passed' => [], 'failed' => [$name => $e->getMessage()]];
                    }

                    return ['passed' => [$name], 'failed' => []];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runWithData(string $name, Closure $test, array $cases): array
                {
                    $results = ['passed' => [], 'failed' => []];

                    foreach ($cases as $key => $case) {
                        $runName = "$name [$key]";
                        try {
                            $test(...$case);
                            $results['passed'][] = $runName;
                        } catch (Throwable $e) {
                            $results['failed'][$runName] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Move the `try` inside the loop so every case is reported on its own, and build the name with `"$name [$key]"`.',
            'checks' => [
                ['label' => 'Every case is reported', 'code' => "\$test = function (int \$a, int \$b, int \$sum) {\n    if (\$a + \$b !== \$sum) {\n        throw new Exception(\"\$a + \$b is not \$sum\");\n    }\n};\n\$cases = ['small' => [1, 2, 3], 'wrong' => [2, 2, 5], 'negative' => [-1, -1, -2]];\nreturn expect_same(runWithData('adds', \$test, \$cases), ['passed' => ['adds [small]', 'adds [negative]'], 'failed' => ['adds [wrong]' => '2 + 2 is not 5']]);"],
                ['label' => 'Numbered cases', 'code' => "return expect_same(runWithData('positive', fn (int \$n) => \$n > 0 ? null : throw new Exception('not positive'), [[3], [0]]), ['passed' => ['positive [0]'], 'failed' => ['positive [1]' => 'not positive']]);"],
            ],
        ],
        [
            'title' => 'Capturing output safely',
            'prompt' => 'Write `captureOutput(callable $fn): string`, which runs `$fn` and returns everything it printed. If `$fn` throws, the exception must still reach the caller, and the output buffer must be closed first.',
            'starter_code' => <<<'PHP'
                <?php

                function captureOutput(callable $fn): string
                {
                    ob_start();
                    $fn();

                    return ob_get_clean();
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function captureOutput(callable $fn): string
                {
                    ob_start();
                    try {
                        $fn();
                    } finally {
                        $output = ob_get_clean();
                    }

                    return $output;
                }
                PHP,
            'hint' => 'Put `$fn()` in a `try` and call `ob_get_clean()` in `finally`. Don\'t catch the exception, so it carries on to the caller.',
            'checks' => [
                ['label' => 'Returns what was printed', 'code' => "return expect_same(captureOutput(function () {\n    echo 'Hello, ';\n    printf('%s!', 'Amara');\n}), 'Hello, Amara!');"],
                ['label' => 'Exceptions get through and the buffer is closed', 'code' => "\$level = ob_get_level();\ntry {\n    captureOutput(function () {\n        echo 'partial';\n        throw new RuntimeException('boom');\n    });\n    return 'The exception did not reach the caller.';\n} catch (RuntimeException \$e) {\n    \$after = ob_get_level();\n    while (ob_get_level() > \$level) {\n        ob_end_clean();\n    }\n    return expect_same([\$e->getMessage(), \$after], ['boom', \$level]);\n}"],
            ],
        ],
        [
            'title' => 'Risky tests',
            'prompt' => "A test that makes no assertions proves nothing. Using the `Assert` class in the starter, write `runCounted(array \$tests): array`, which returns `['passed' => [...], 'failed' => [name => message], 'risky' => [...]]`. A test that throws is failed; one that finishes having made **no** assertions is risky, not passed.",
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                class Assert
                {
                    public static int $count = 0;

                    public static function same(mixed $expected, mixed $actual): void
                    {
                        self::$count++;
                        if ($expected !== $actual) {
                            throw new AssertionFailed('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
                        }
                    }
                }

                function runCounted(array $tests): array
                {
                    $results = ['passed' => [], 'failed' => [], 'risky' => []];

                    foreach ($tests as $name => $test) {
                        try {
                            $test();
                            $results['passed'][] = $name;
                        } catch (Throwable $e) {
                            $results['failed'][$name] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                class Assert
                {
                    public static int $count = 0;

                    public static function same(mixed $expected, mixed $actual): void
                    {
                        self::$count++;
                        if ($expected !== $actual) {
                            throw new AssertionFailed('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
                        }
                    }
                }

                function runCounted(array $tests): array
                {
                    $results = ['passed' => [], 'failed' => [], 'risky' => []];

                    foreach ($tests as $name => $test) {
                        Assert::$count = 0;
                        try {
                            $test();
                            $results[Assert::$count === 0 ? 'risky' : 'passed'][] = $name;
                        } catch (Throwable $e) {
                            $results['failed'][$name] = $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Reset `Assert::$count = 0` before each test, and after it finishes put the name in `risky` if the count is still 0.',
            'checks' => [
                ['label' => 'Tests without assertions are risky', 'code' => "\$tests = [\n    'checks' => fn () => Assert::same(2, 1 + 1),\n    'forgot to assert' => fn () => strtoupper('x'),\n    'checks twice' => function () {\n        Assert::same('a', 'a');\n        Assert::same(1, 1);\n    },\n    'fails' => fn () => Assert::same(1, 2),\n];\nreturn expect_same(runCounted(\$tests), ['passed' => ['checks', 'checks twice'], 'failed' => ['fails' => 'Expected 1, got 2'], 'risky' => ['forgot to assert']]);"],
                ['label' => 'Counts are per test', 'code' => "Assert::\$count = 5;\nreturn expect_same(runCounted(['empty' => fn () => null, 'one' => fn () => Assert::same(1, 1), 'empty again' => fn () => null]), ['passed' => ['one'], 'failed' => [], 'risky' => ['empty', 'empty again']]);"],
            ],
        ],
    ],
];
