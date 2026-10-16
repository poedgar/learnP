<?php

// Lesson 11 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'handling-errors',
    'title' => 'Handling errors',
    'summary' => 'Spot failure in return values, take warnings seriously, throw exceptions with clear messages, and handle them with try, catch and finally.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function quantity(int $copies): int
        {
            if ($copies < 1) {
                throw new InvalidArgumentException('Order at least one copy.');
            }
            return $copies;
        }

        foreach ([2, 0, 5] as $copies) {
            try {
                echo 'Ordering ' . quantity($copies) . "\n";
            } catch (InvalidArgumentException $e) {
                echo 'Problem: ' . $e->getMessage() . "\n";
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => "Why check `strpos(\$text, 'x') === false` rather than `! strpos(\$text, 'x')`?",
            'options' => [
                '`!` doesn\'t work with functions',
                'Because a match at position 0 is falsy too, so `!` would treat it as "not found"',
                '`=== false` is faster',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => '`strpos()` returns `int|false`, and the int 0 is falsy. Only `=== false` tells them apart.',
        ],
        [
            'question' => 'What happens when an exception is thrown and nothing catches it?',
            'options' => [
                'PHP ignores it and carries on',
                'The script stops with an "Uncaught" error',
                'It turns into a warning',
                'The function returns `null`',
            ],
            'correct_option' => 1,
            'explanation' => 'An exception travels up through the callers looking for a `catch`. With none, the script ends.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function check(int $n): string
                {
                    if ($n > 3) {
                        throw new RangeException('too big');
                    }
                    return 'ok';
                }

                try {
                    echo check(1) . ' ';
                    echo check(5) . ' ';
                    echo check(2) . ' ';
                } catch (RangeException $e) {
                    echo $e->getMessage();
                }
                PHP,
            'options' => ['`ok too big ok `', '`ok too big`', '`ok ok too big`', '`too big`'],
            'correct_option' => 1,
            'explanation' => 'The throw jumps straight to the `catch`, so `check(2)` inside the `try` never runs.',
        ],
        [
            'question' => 'When does a `finally` block run?',
            'options' => [
                'Only when an exception was thrown',
                'Only when no exception was thrown',
                'Always, after the `try` (and any `catch`) finishes',
                'Only at the end of the script',
            ],
            'correct_option' => 2,
            'explanation' => '`finally` is for code that must run whatever happens, such as tidying up.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                try {
                    throw new InvalidArgumentException('bad input');
                } catch (RuntimeException $e) {
                    echo 'runtime';
                } catch (InvalidArgumentException $e) {
                    echo 'argument';
                } finally {
                    echo '!';
                }
                PHP,
            'options' => ['`runtime!`', '`argument!`', '`argument`', '`!`'],
            'correct_option' => 1,
            'explanation' => 'PHP picks the first `catch` whose class matches, then runs `finally`.',
        ],
        [
            'question' => 'Which exception class suits a function that receives a negative quantity?',
            'options' => ['`InvalidArgumentException`', '`DivisionByZeroError`', '`ParseError`', '`TypeError`'],
            'correct_option' => 0,
            'explanation' => 'The argument has the right type but an unacceptable value, which is what `InvalidArgumentException` is for.',
        ],
        [
            'question' => 'What does `catch (Throwable $e)` catch?',
            'options' => [
                'Only exceptions you throw yourself',
                'Every exception and every PHP `Error`',
                'Only warnings',
                'Nothing; `Throwable` isn\'t a class',
            ],
            'correct_option' => 1,
            'explanation' => '`Exception` and `Error` both implement `Throwable`. Catching everything can hide bugs, so keep it for the top of a program.',
        ],
        [
            'question' => 'What does PHP do when you read an array key that doesn\'t exist, without `isset()` or `??`?',
            'options' => [
                'Throws an exception that stops the script',
                'Gives a warning and `null`, then carries on',
                'Silently gives `null`',
                'Creates the key',
            ],
            'correct_option' => 1,
            'explanation' => 'The script carries on with a wrong value, which is why warnings deserve fixing.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Refuse bad quantities',
            'prompt' => "Write a function `orderQuantity(int \$copies): int` that returns `\$copies` when it's from 1 to 10, and throws an `InvalidArgumentException` with the message `'Order between 1 and 10 copies.'` otherwise.",
            'starter_code' => <<<'PHP'
                <?php

                function orderQuantity(int $copies): int
                {
                    return $copies;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderQuantity(int $copies): int
                {
                    if ($copies < 1 || $copies > 10) {
                        throw new InvalidArgumentException('Order between 1 and 10 copies.');
                    }

                    return $copies;
                }
                PHP,
            'hint' => 'Check `$copies < 1 || $copies > 10` first, and `throw new InvalidArgumentException(…)`.',
            'checks' => [
                ['label' => 'Quantities from 1 to 10 are returned', 'code' => 'return expect_same([orderQuantity(1), orderQuantity(10)], [1, 10]);'],
                ['label' => 'Too few or too many throw', 'code' => <<<'PHP'
                    foreach ([0, 11, -3] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => orderQuantity($bad));
                        if ($result !== true) return "For $bad: $result";
                    }
                    return true;
                    PHP],
                ['label' => 'The message explains the rule', 'code' => <<<'PHP'
                    try {
                        orderQuantity(0);
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), 'Order between 1 and 10 copies.');
                    }
                    return 'orderQuantity(0) should throw.';
                    PHP],
            ],
        ],
        [
            'title' => 'Catch and carry on',
            'prompt' => '`orderQuantity()` (given) throws for bad quantities. Set `$accepted` to the quantities from `$requests` that it accepts, and `$errors` to the number of requests it refused, by calling it inside `try`/`catch`.',
            'starter_code' => <<<'PHP'
                <?php

                function orderQuantity(int $copies): int
                {
                    if ($copies < 1 || $copies > 10) {
                        throw new InvalidArgumentException('Order between 1 and 10 copies.');
                    }

                    return $copies;
                }

                $requests = [3, 0, 12, 7, -1, 10];
                $accepted = [];
                $errors = 0;

                foreach ($requests as $copies) {
                    $accepted[] = orderQuantity($copies);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderQuantity(int $copies): int
                {
                    if ($copies < 1 || $copies > 10) {
                        throw new InvalidArgumentException('Order between 1 and 10 copies.');
                    }

                    return $copies;
                }

                $requests = [3, 0, 12, 7, -1, 10];
                $accepted = [];
                $errors = 0;

                foreach ($requests as $copies) {
                    try {
                        $accepted[] = orderQuantity($copies);
                    } catch (InvalidArgumentException $e) {
                        $errors++;
                    }
                }
                PHP,
            'hint' => 'Wrap the line inside the loop in `try { … } catch (InvalidArgumentException $e) { $errors++; }`.',
            'checks' => [
                ['label' => '$accepted holds the good quantities', 'code' => "return expect_same(\$GLOBALS['accepted'] ?? null, [3, 7, 10]);"],
                ['label' => '$errors counts the refused ones', 'code' => "return expect_same(\$GLOBALS['errors'] ?? null, 3);"],
            ],
        ],
        [
            'title' => 'Two kinds of problem',
            'prompt' => "Write a function `readAge(string \$input): int`. It throws an `InvalidArgumentException` when the input isn't a whole number, and a `RangeException` when it is, but isn't from 0 to 130. Otherwise it returns the age as an int.",
            'starter_code' => <<<'PHP'
                <?php

                function readAge(string $input): int
                {
                    return (int) $input;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function readAge(string $input): int
                {
                    if (! preg_match('/^\d+$/', trim($input))) {
                        throw new InvalidArgumentException("'$input' isn't a whole number.");
                    }

                    $age = (int) trim($input);
                    if ($age > 130) {
                        throw new RangeException('An age must be from 0 to 130.');
                    }

                    return $age;
                }
                PHP,
            'hint' => 'Check the shape first, with a regular expression like `/^\d+$/`, then the range. Negative numbers fail the shape check, because of the minus sign.',
            'checks' => [
                ['label' => 'Ages are read', 'code' => "return expect_same([readAge('42'), readAge(' 7 '), readAge('0')], [42, 7, 0]);"],
                ['label' => 'Non-numbers throw InvalidArgumentException', 'code' => <<<'PHP'
                    foreach (['forty', '4.5', ''] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => readAge($bad));
                        if ($result !== true) return "For '$bad': $result";
                    }
                    return true;
                    PHP],
                ['label' => 'Impossible ages throw RangeException', 'code' => "return expect_throws(RangeException::class, fn () => readAge('200'));"],
            ],
        ],
        [
            'title' => 'Always log',
            'prompt' => "Write a function `attempt(callable \$task, array &\$log): string` that runs `\$task()` and returns its result, or returns `'failed: '` followed by the exception's message if it throws a `RuntimeException`. Whatever happens, add `'done'` to the end of `\$log`, using `finally`.",
            'starter_code' => <<<'PHP'
                <?php

                function attempt(callable $task, array &$log): string
                {
                    return $task();
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function attempt(callable $task, array &$log): string
                {
                    try {
                        return $task();
                    } catch (RuntimeException $e) {
                        return 'failed: ' . $e->getMessage();
                    } finally {
                        $log[] = 'done';
                    }
                }
                PHP,
            'hint' => '`try { return $task(); } catch (RuntimeException $e) { return \'failed: \' . …; } finally { $log[] = \'done\'; }`. `finally` runs even after a `return`.',
            'checks' => [
                ['label' => 'A task that works', 'code' => <<<'PHP'
                    $log = [];
                    $result = attempt(fn () => 'shipped', $log);
                    return expect_same([$result, $log], ['shipped', ['done']]);
                    PHP],
                ['label' => 'A task that fails', 'code' => <<<'PHP'
                    $log = [];
                    $result = attempt(function () { throw new RuntimeException('printer jammed'); }, $log);
                    return expect_same([$result, $log], ['failed: printer jammed', ['done']]);
                    PHP],
                ['label' => 'It uses finally', 'code' => "return str_contains(learnp_code(), 'finally') ?: 'Add to the log in a finally block.';"],
            ],
        ],
        [
            'title' => 'Safe division',
            'prompt' => "Write a function `perPerson(int \$total, int \$people): ?int` that shares `\$total` equally with `intdiv()`, returning `null` instead of crashing when `\$people` is 0. Catch PHP's own `DivisionByZeroError` rather than checking for 0 first.",
            'starter_code' => <<<'PHP'
                <?php

                function perPerson(int $total, int $people): ?int
                {
                    return intdiv($total, $people);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function perPerson(int $total, int $people): ?int
                {
                    try {
                        return intdiv($total, $people);
                    } catch (DivisionByZeroError $e) {
                        return null;
                    }
                }
                PHP,
            'hint' => 'Wrap the `intdiv()` in `try` and `catch (DivisionByZeroError $e) { return null; }`.',
            'checks' => [
                ['label' => 'Sharing works', 'code' => 'return expect_same([perPerson(10, 3), perPerson(12, 4)], [3, 3]);'],
                ['label' => 'Nobody to share with gives null', 'code' => 'return expect_same(perPerson(10, 0), null);'],
                ['label' => 'It catches DivisionByZeroError', 'code' => "return str_contains(learnp_code(), 'DivisionByZeroError') ?: 'Catch DivisionByZeroError.';"],
            ],
        ],
        [
            'title' => 'A missing date',
            'prompt' => "Write a function `deliveryDay(string \$date): string` that reads a British date like `'08/04/2026'` and returns its day name, such as `'Wednesday'`. When the date can't be read, throw an `InvalidArgumentException` with the message `\"Can't read the date '…'.\"` (with the input in place of `…`), instead of letting PHP fail with a confusing error.",
            'starter_code' => <<<'PHP'
                <?php

                function deliveryDay(string $date): string
                {
                    return DateTimeImmutable::createFromFormat('!d/m/Y', $date)->format('l');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function deliveryDay(string $date): string
                {
                    $parsed = DateTimeImmutable::createFromFormat('!d/m/Y', $date);

                    if ($parsed === false) {
                        throw new InvalidArgumentException("Can't read the date '$date'.");
                    }

                    return $parsed->format('l');
                }
                PHP,
            'hint' => 'Store the result of `createFromFormat()`, and throw when it\'s `false` before calling `format()` on it.',
            'checks' => [
                ['label' => 'A real date gives its day', 'code' => "return expect_same(deliveryDay('08/04/2026'), 'Wednesday');"],
                ['label' => 'Nonsense throws a clear InvalidArgumentException', 'code' => <<<'PHP'
                    try {
                        deliveryDay('tomorrow-ish');
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), "Can't read the date 'tomorrow-ish'.");
                    } catch (Throwable $e) {
                        return 'Expected an InvalidArgumentException, got ' . get_class($e) . '.';
                    }
                    return 'deliveryDay() should throw for nonsense.';
                    PHP],
            ],
        ],
    ],
];
