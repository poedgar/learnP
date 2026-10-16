<?php

// Lesson 1 of the Testing without frameworks course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'writing-assertions',
    'title' => 'Writing assertions',
    'summary' => 'Check your code with your own assertion functions: strict comparisons, clear failure messages, tolerances for floats, arrays in any order, and expected exceptions.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class AssertionFailed extends Exception {}

        function assertTrue(bool $condition, string $message = 'Expected true, got false'): void
        {
            if (! $condition) {
                throw new AssertionFailed($message);
            }
        }

        function double(int $n): int
        {
            return $n * 2;
        }

        try {
            assertTrue(double(2) === 4);
            assertTrue(double(-3) === -6, 'double(-3) should be -6');
            echo 'All tests passed';
        } catch (AssertionFailed $e) {
            echo 'FAIL: ' . $e->getMessage();
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Why does an assertion function throw an exception when the check fails?',
            'options' => [
                'Exceptions are faster than `if`',
                'It stops the test at the first problem, and whatever runs the tests can catch it and report the message',
                'PHP requires it',
                'So the program crashes',
            ],
            'correct_option' => 1,
            'explanation' => 'A test runner wraps each test in `try`/`catch` and turns the exception into a failure report.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                var_dump('1.0' == '1', '1.0' === '1');
                PHP,
            'options' => [
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(false)`',
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(true)`',
            ],
            'correct_option' => 0,
            'explanation' => 'Two numeric strings are compared as numbers with `==`, so 1.0 equals 1. `===` compares the strings exactly.',
        ],
        [
            'question' => 'Why should tests compare with `===` rather than `==`?',
            'options' => [
                '`===` is shorter',
                '`==` converts types, so a wrong answer like `false` instead of `0` can pass',
                '`==` doesn\'t work on arrays',
                'There\'s no difference in tests',
            ],
            'correct_option' => 1,
            'explanation' => 'A test should fail when the type is wrong, and `===` makes sure it does.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                echo var_export('3', true) . ' ' . var_export(3, true) . ' ' . var_export(null, true);
                PHP,
            'options' => ['`\'3\' 3 NULL`', '`3 3 `', '`"3" 3 null`', '`3 3 NULL`'],
            'correct_option' => 0,
            'explanation' => '`var_export()` writes values as PHP code, so the string and the integer look different. That\'s why it\'s good for failure messages.',
        ],
        [
            'question' => 'Why is `0.1 + 0.2 === 0.3` false in PHP?',
            'options' => [
                'PHP has a bug in addition',
                'Floats can\'t store most decimals exactly, so the sum is 0.30000000000000004',
                '`===` doesn\'t work with floats',
                'The numbers must be cast to strings first',
            ],
            'correct_option' => 1,
            'explanation' => 'Compare floats within a small delta, or keep money in whole pence.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = ['x' => 1, 'y' => 2];
                $b = ['y' => 2, 'x' => 1];
                var_dump($a == $b, $a === $b);
                PHP,
            'options' => [
                '`bool(true)` then `bool(false)`',
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
            ],
            'correct_option' => 0,
            'explanation' => '`==` checks the same key and value pairs in any order; `===` also needs the same order.',
        ],
        [
            'question' => 'How do you test that a function throws when it should?',
            'options' => [
                'You can\'t test exceptions',
                'Call it inside `try`; if the next line after the call runs, nothing was thrown and the test fails. Catch the expected exception as a pass',
                'Check that it returns `false`',
                'Use `@` to hide the error',
            ],
            'correct_option' => 1,
            'explanation' => 'The failure is the case where the code carries on normally.',
        ],
        [
            'question' => 'Why don\'t test suites rely on PHP\'s built-in `assert()`?',
            'options' => [
                'It was removed in PHP 8',
                'Whether it runs depends on the `zend.assertions` setting, which is usually off in production; it\'s meant for checking assumptions in code',
                'It only works with integers',
                'It prints instead of throwing',
            ],
            'correct_option' => 1,
            'explanation' => 'Test code uses its own assertions, which always run.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'assertSame',
            'prompt' => "Write `assertSame(mixed \$expected, mixed \$actual, string \$message = ''): void`. If the two aren't identical (`===`), throw `AssertionFailed` with the message `Expected X, got Y`, where X and Y come from `var_export()`. If a `\$message` is given, add it in brackets at the end: `Expected 1, got 2 (total)`.",
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertSame(mixed $expected, mixed $actual, string $message = ''): void
                {
                    if ($expected != $actual) {
                        throw new AssertionFailed('Not the same');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertSame(mixed $expected, mixed $actual, string $message = ''): void
                {
                    if ($expected === $actual) {
                        return;
                    }

                    $text = 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true);
                    if ($message !== '') {
                        $text .= " ($message)";
                    }

                    throw new AssertionFailed($text);
                }
                PHP,
            'hint' => 'Compare with `===`. Build the message from `var_export($value, true)`, and add ` ($message)` only when it isn\'t empty.',
            'checks' => [
                ['label' => 'Identical values pass', 'code' => "assertSame(3, 3);\nassertSame('Dune', 'Dune');\nassertSame([1, 2], [1, 2]);\nassertSame(null, null);\nreturn true;"],
                ['label' => 'Different types fail', 'code' => "return (function () {\n    try {\n        assertSame(3, '3');\n    } catch (AssertionFailed \$e) {\n        return expect_same(\$e->getMessage(), \"Expected 3, got '3'\");\n    }\n    return 'Nothing was thrown.';\n})();"],
                ['label' => 'The message is added in brackets', 'code' => "return (function () {\n    try {\n        assertSame(1, 2, 'total');\n    } catch (AssertionFailed \$e) {\n        return expect_same(\$e->getMessage(), 'Expected 1, got 2 (total)');\n    }\n    return 'Nothing was thrown.';\n})();"],
                ['label' => 'false is not 0', 'code' => "return (function () {\n    try {\n        assertSame(0, false);\n    } catch (AssertionFailed \$e) {\n        return expect_same(\$e->getMessage(), 'Expected 0, got false');\n    }\n    return 'Nothing was thrown.';\n})();"],
            ],
        ],
        [
            'title' => 'Floats within a tolerance',
            'prompt' => 'Write `assertEqualsWithDelta(float $expected, float $actual, float $delta = 0.0001): void`, which passes when the two differ by **at most** `$delta` and otherwise throws `AssertionFailed`.',
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertEqualsWithDelta(float $expected, float $actual, float $delta = 0.0001): void
                {
                    if ($expected !== $actual) {
                        throw new AssertionFailed("Expected $expected, got $actual");
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertEqualsWithDelta(float $expected, float $actual, float $delta = 0.0001): void
                {
                    if (abs($expected - $actual) > $delta) {
                        throw new AssertionFailed("Expected $expected ± $delta, got $actual");
                    }
                }
                PHP,
            'hint' => 'Fail when `abs($expected - $actual) > $delta`.',
            'checks' => [
                ['label' => 'Close enough passes', 'code' => "assertEqualsWithDelta(0.3, 0.1 + 0.2);\nassertEqualsWithDelta(1.0, 1.5, 0.5);\nassertEqualsWithDelta(-2.0, -2.00001);\nreturn true;"],
                ['label' => 'Too far fails', 'code' => 'return expect_throws(AssertionFailed::class, fn () => assertEqualsWithDelta(1.0, 1.01));'],
                ['label' => 'Too far fails, in either direction', 'code' => 'return expect_throws(AssertionFailed::class, fn () => assertEqualsWithDelta(5.0, 4.0, 0.5));'],
            ],
        ],
        [
            'title' => 'Expecting an exception',
            'prompt' => 'Write `assertThrows(string $class, callable $fn): Throwable`. It calls `$fn` and returns the exception if one of class `$class` (or a subclass) was thrown. If nothing was thrown, or an exception of another class was, it throws `AssertionFailed`.',
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertThrows(string $class, callable $fn): Throwable
                {
                    try {
                        $fn();
                    } catch (Throwable $e) {
                        return $e;
                    }

                    return new Exception('nothing');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertThrows(string $class, callable $fn): Throwable
                {
                    try {
                        $fn();
                    } catch (Throwable $e) {
                        if ($e instanceof $class) {
                            return $e;
                        }

                        throw new AssertionFailed("Expected $class, got " . $e::class);
                    }

                    throw new AssertionFailed("Expected $class, but nothing was thrown");
                }
                PHP,
            'hint' => 'Inside the `catch`, return the exception only if `$e instanceof $class`. After the `try`, reaching the end means nothing was thrown.',
            'checks' => [
                ['label' => 'Returns the expected exception', 'code' => "\$e = assertThrows(InvalidArgumentException::class, fn () => throw new InvalidArgumentException('bad'));\nreturn expect_same(\$e->getMessage(), 'bad');"],
                ['label' => 'A subclass counts', 'code' => "\$e = assertThrows(LogicException::class, fn () => throw new DomainException('sub'));\nreturn expect_same(\$e::class, 'DomainException');"],
                ['label' => 'Nothing thrown fails', 'code' => 'return expect_throws(AssertionFailed::class, fn () => assertThrows(RuntimeException::class, fn () => 42));'],
                ['label' => 'Another class fails', 'code' => "return expect_throws(AssertionFailed::class, fn () => assertThrows(RuntimeException::class, fn () => throw new TypeError('t')));"],
            ],
        ],
        [
            'title' => 'The same items in any order',
            'prompt' => "Write `assertSameItems(array \$expected, array \$actual): void`, which passes when both arrays hold the same values the same number of times, in any order (keys don't matter), and otherwise throws `AssertionFailed`. Values are compared strictly, so `1` and `'1'` differ.",
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertSameItems(array $expected, array $actual): void
                {
                    if (array_diff($expected, $actual) !== []) {
                        throw new AssertionFailed('Different items');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertSameItems(array $expected, array $actual): void
                {
                    $describe = fn (array $items) => array_map(fn ($item) => var_export($item, true), array_values($items));
                    $a = $describe($expected);
                    $b = $describe($actual);
                    sort($a);
                    sort($b);

                    if ($a !== $b) {
                        throw new AssertionFailed('Expected the items ' . implode(', ', $a) . ', got ' . implode(', ', $b));
                    }
                }
                PHP,
            'hint' => 'Sort copies of both lists and compare them with `===`. To keep `1` and `\'1\'` apart, sort a version of each value that shows its type, such as `var_export($item, true)`.',
            'checks' => [
                ['label' => 'Any order passes', 'code' => "assertSameItems([1, 2, 3], [3, 1, 2]);\nassertSameItems(['a' => 'x', 'b' => 'y'], ['y', 'x']);\nassertSameItems([], []);\nreturn true;"],
                ['label' => 'Counts matter', 'code' => 'return expect_throws(AssertionFailed::class, fn () => assertSameItems([1, 1, 2], [1, 2, 2]));'],
                ['label' => 'Extra items fail', 'code' => 'return expect_throws(AssertionFailed::class, fn () => assertSameItems([1, 2], [1, 2, 3]));'],
                ['label' => 'Types matter', 'code' => "return expect_throws(AssertionFailed::class, fn () => assertSameItems([1, 2], ['1', 2]));"],
            ],
        ],
        [
            'title' => 'Required keys',
            'prompt' => 'Write `assertHasKeys(array $keys, array $actual): void`. If any of `$keys` is missing from `$actual`, throw `AssertionFailed` listing **all** the missing keys in order: `Missing keys: email, age`. A key whose value is `null` still counts as present.',
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertHasKeys(array $keys, array $actual): void
                {
                    foreach ($keys as $key) {
                        if (! isset($actual[$key])) {
                            throw new AssertionFailed("Missing keys: $key");
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                function assertHasKeys(array $keys, array $actual): void
                {
                    $missing = array_filter($keys, fn ($key) => ! array_key_exists($key, $actual));

                    if ($missing !== []) {
                        throw new AssertionFailed('Missing keys: ' . implode(', ', $missing));
                    }
                }
                PHP,
            'hint' => '`isset()` is false for `null` values; `array_key_exists()` isn\'t. Collect every missing key before throwing.',
            'checks' => [
                ['label' => 'All present passes', 'code' => "assertHasKeys(['name', 'email'], ['name' => 'Amara', 'email' => 'a@example.com', 'age' => 30]);\nreturn true;"],
                ['label' => 'null values count as present', 'code' => "assertHasKeys(['name', 'phone'], ['name' => 'Ben', 'phone' => null]);\nreturn true;"],
                ['label' => 'Lists every missing key', 'code' => "return (function () {\n    try {\n        assertHasKeys(['name', 'email', 'age'], ['name' => 'Cy']);\n    } catch (AssertionFailed \$e) {\n        return expect_same(\$e->getMessage(), 'Missing keys: email, age');\n    }\n    return 'Nothing was thrown.';\n})();"],
            ],
        ],
        [
            'title' => 'Counting assertions',
            'prompt' => 'Write a class `Assert` with a public static int `$count` starting at 0, and static methods `same(mixed $expected, mixed $actual): void` and `true(bool $condition): void`. Each call adds 1 to `Assert::$count`, whether it passes or fails, and a failure throws `AssertionFailed`.',
            'starter_code' => <<<'PHP'
                <?php

                class AssertionFailed extends Exception {}

                class Assert
                {
                    public static function same(mixed $expected, mixed $actual): void
                    {
                        if ($expected !== $actual) {
                            throw new AssertionFailed('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
                        }
                    }
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

                    public static function true(bool $condition): void
                    {
                        self::$count++;

                        if (! $condition) {
                            throw new AssertionFailed('Expected true, got false');
                        }
                    }
                }
                PHP,
            'hint' => 'Add `public static int $count = 0;` and increase it with `self::$count++` at the start of each method, before the check.',
            'checks' => [
                ['label' => 'Both assertions work', 'code' => "Assert::same('a', 'a');\nAssert::true(1 < 2);\n\$a = expect_throws(AssertionFailed::class, fn () => Assert::same(1, '1'));\nreturn \$a === true ? expect_throws(AssertionFailed::class, fn () => Assert::true(false)) : \$a;"],
                ['label' => 'Every call is counted, pass or fail', 'code' => "Assert::\$count = 0;\nAssert::same(1, 1);\nAssert::true(true);\ntry {\n    Assert::same(1, 2);\n} catch (AssertionFailed) {\n}\nreturn expect_same(Assert::\$count, 3);"],
            ],
        ],
    ],
];
