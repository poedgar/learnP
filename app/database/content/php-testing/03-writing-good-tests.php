<?php

// Lesson 3 of the Testing without frameworks course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Most challenges here ask for tests rather than code: the checks run the learner's tests against a
// correct implementation (they must pass) and against mutants (each must make them throw).

return [
    'slug' => 'writing-good-tests',
    'title' => 'Writing good tests',
    'summary' => 'Arrange, act and assert; one behaviour per test; equivalence classes and boundary values; unhappy paths; characterisation tests; and checking your tests catch bugs with mutants.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $isAdult = fn (int $age) => $age >= 18;
        $mutant = fn (int $age) => $age > 18;

        $tests = fn (Closure $f) => $f(30) === true && $f(5) === false;

        echo 'Real code: ' . ($tests($isAdult) ? 'tests pass' : 'tests fail') . "\n";
        echo 'Mutant: ' . ($tests($mutant) ? 'tests pass, so the mutant survived' : 'tests fail, so the mutant was caught') . "\n";
        PHP,

    'quiz' => [
        [
            'question' => 'What are the three parts of a clear test, in order?',
            'options' => [
                'Assert, act, arrange',
                'Arrange the inputs, act by calling the code, assert what should be true',
                'Setup, teardown, report',
                'Write, run, delete',
            ],
            'correct_option' => 1,
            'explanation' => 'Arrange, act, assert: keeping them apart makes a test easy to read.',
        ],
        [
            'question' => 'Which is the best name for a test?',
            'options' => [
                '`testBasket`',
                '`test1`',
                '`an empty basket totals zero`',
                '`basketWorks`',
            ],
            'correct_option' => 2,
            'explanation' => 'A name that states one behaviour tells you exactly what broke when it fails.',
        ],
        [
            'question' => 'A function gives free delivery for orders of £50 or more. Which inputs test the boundary best?',
            'options' => [
                '10 and 100',
                '49.99 and 50',
                '50 only',
                '0 and 1000',
            ],
            'correct_option' => 1,
            'explanation' => 'Test on and just below the boundary: that\'s where `>` written instead of `>=` shows up.',
        ],
        [
            'question' => 'What is an equivalence class?',
            'options' => [
                'A PHP class that implements `==`',
                'A group of inputs the code should treat the same way, so testing one of them stands for the rest',
                'Two tests that check the same thing',
                'A class with no methods',
            ],
            'correct_option' => 1,
            'explanation' => 'For a grade function: fail, pass, distinction and invalid scores are four classes.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $real = fn (int $n) => $n >= 10;
                $mutant = fn (int $n) => $n > 10;

                $tests = fn (Closure $f) => $f(20) && ! $f(3);

                echo ($tests($real) ? 'pass' : 'fail') . ' ' . ($tests($mutant) ? 'pass' : 'fail');
                PHP,
            'options' => ['`pass pass`', '`pass fail`', '`fail pass`', '`fail fail`'],
            'correct_option' => 0,
            'explanation' => 'The tests never try 10, so the mutant survives. Adding a check for `$f(10)` would catch it.',
        ],
        [
            'question' => 'In mutation testing, what does a surviving mutant tell you?',
            'options' => [
                'The code is correct',
                'A deliberate bug went unnoticed, so a test is missing',
                'The tests are too slow',
                'The mutant is equivalent to the code and must be deleted',
            ],
            'correct_option' => 1,
            'explanation' => 'Every mutant should make at least one test fail.',
        ],
        [
            'question' => 'What is a characterisation test?',
            'options' => [
                'A test written before the code',
                'A test that records what existing code actually does now, right or wrong, so accidental changes show up',
                'A test of a character string',
                'A test that only checks types',
            ],
            'correct_option' => 1,
            'explanation' => 'Characterise first, then refactor: the recorded behaviour is the safety net.',
        ],
        [
            'question' => 'Why test invalid input, not just valid input?',
            'options' => [
                'It isn\'t needed: invalid input is the caller\'s problem',
                'Rejecting bad input in the right way, such as with the right exception class, is behaviour too, and can break',
                'To make the test suite longer',
                'Because valid input can\'t be tested',
            ],
            'correct_option' => 1,
            'explanation' => 'A function that silently returns `-1` instead of throwing is a bug worth catching.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Tests for slugify()',
            'prompt' => "`slugify()` should lower-case a title, turn each run of characters that aren't letters or digits into **one** dash, and remove dashes from the ends: `'  The Left-Hand, of Darkness! '` → `'the-left-hand-of-darkness'`, `'Catch 22'` → `'catch-22'`. Write `slugifyTests(callable \$slugify): void` that throws (use `check()`) if `\$slugify` gets any of this wrong. Your tests must pass the correct version and catch four buggy ones.",
            'starter_code' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function slugifyTests(callable $slugify): void
                {
                    check($slugify('Dune') === 'dune', 'lower-cases');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function slugifyTests(callable $slugify): void
                {
                    check($slugify('Dune') === 'dune', 'lower-cases');
                    check($slugify('the left hand') === 'the-left-hand', 'joins words with dashes');
                    check($slugify('Emma:  a novel') === 'emma-a-novel', 'one dash for a run of other characters');
                    check($slugify('  Circe!  ') === 'circe', 'no dashes at the ends');
                    check($slugify('Catch 22') === 'catch-22', 'keeps digits');
                }
                PHP,
            'hint' => 'Write one `check()` per rule, with an input that only a correct slugify gets right: a run of several spaces or punctuation, punctuation at the ends, and a digit.',
            'checks' => [
                ['label' => 'Your tests pass the correct slugify()', 'code' => <<<'PHP'
                    try {
                        slugifyTests(fn (string $s) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-'));
                        return true;
                    } catch (Throwable $e) {
                        return 'Your tests fail the correct slugify(): ' . $e->getMessage();
                    }
                    PHP],
                ['label' => 'Catches a slugify() that keeps capitals', 'code' => <<<'PHP'
                    try {
                        slugifyTests(fn (string $s) => trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a slugify() that keeps capital letters.';
                    PHP],
                ['label' => 'Catches one dash per character instead of per run', 'code' => <<<'PHP'
                    try {
                        slugifyTests(fn (string $s) => trim(preg_replace('/[^a-z0-9]/', '-', strtolower($s)), '-'));
                    } catch (Throwable) {
                        return true;
                    }
                    return "Your tests passed a slugify() that turns 'a, b' into 'a--b'.";
                    PHP],
                ['label' => 'Catches dashes left at the ends', 'code' => <<<'PHP'
                    try {
                        slugifyTests(fn (string $s) => preg_replace('/[^a-z0-9]+/', '-', strtolower($s)));
                    } catch (Throwable) {
                        return true;
                    }
                    return "Your tests passed a slugify() that turns ' Circe! ' into '-circe-'.";
                    PHP],
                ['label' => 'Catches dropped digits', 'code' => <<<'PHP'
                    try {
                        slugifyTests(fn (string $s) => trim(preg_replace('/[^a-z]+/', '-', strtolower($s)), '-'));
                    } catch (Throwable) {
                        return true;
                    }
                    return "Your tests passed a slugify() that turns 'Catch 22' into 'catch'.";
                    PHP],
            ],
        ],
        [
            'title' => 'Tests on the boundaries',
            'prompt' => '`discount(float $total): float` gives no discount below £50, 10% from £50 up to (not including) £100, and 20% from £100, rounded to 2 places: `discount(50.0)` is `5.0`, `discount(100.0)` is `20.0`. Write `discountTests(callable $discount): void` that throws if `$discount` is wrong. It must pass the correct version and catch four buggy ones.',
            'starter_code' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function discountTests(callable $discount): void
                {
                    check($discount(20.0) === 0.0, 'small orders get nothing');
                    check($discount(70.0) === 7.0, 'middle orders get 10%');
                    check($discount(200.0) === 40.0, 'big orders get 20%');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function discountTests(callable $discount): void
                {
                    check($discount(49.99) === 0.0, 'just under £50 gets nothing');
                    check($discount(50.0) === 5.0, '£50 exactly gets 10%');
                    check($discount(99.99) === 10.0, 'just under £100 gets 10%');
                    check($discount(100.0) === 20.0, '£100 exactly gets 20%');
                }
                PHP,
            'hint' => 'The middle of each range is already covered. Test on each boundary and just below it: 49.99, 50, 99.99 and 100.',
            'checks' => [
                ['label' => 'Your tests pass the correct discount()', 'code' => <<<'PHP'
                    try {
                        discountTests(fn (float $t) => round($t >= 100 ? $t * 0.2 : ($t >= 50 ? $t * 0.1 : 0.0), 2));
                        return true;
                    } catch (Throwable $e) {
                        return 'Your tests fail the correct discount(): ' . $e->getMessage();
                    }
                    PHP],
                ['label' => 'Catches > 50 instead of >= 50', 'code' => <<<'PHP'
                    try {
                        discountTests(fn (float $t) => round($t >= 100 ? $t * 0.2 : ($t > 50 ? $t * 0.1 : 0.0), 2));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a discount() that gives nothing for exactly £50.';
                    PHP],
                ['label' => 'Catches > 100 instead of >= 100', 'code' => <<<'PHP'
                    try {
                        discountTests(fn (float $t) => round($t > 100 ? $t * 0.2 : ($t >= 50 ? $t * 0.1 : 0.0), 2));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a discount() that gives only 10% for exactly £100.';
                    PHP],
                ['label' => 'Catches the 20% starting too early', 'code' => <<<'PHP'
                    try {
                        discountTests(fn (float $t) => round($t >= 99 ? $t * 0.2 : ($t >= 50 ? $t * 0.1 : 0.0), 2));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a discount() that gives 20% from £99.';
                    PHP],
                ['label' => 'Catches the 10% starting too early', 'code' => <<<'PHP'
                    try {
                        discountTests(fn (float $t) => round($t >= 100 ? $t * 0.2 : ($t >= 49 ? $t * 0.1 : 0.0), 2));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a discount() that gives 10% from £49.';
                    PHP],
            ],
        ],
        [
            'title' => 'Tests for bad input',
            'prompt' => "`parseAge(string \$text): int` returns the age for whole numbers from `'0'` to `'150'`, and throws `InvalidArgumentException` for anything else (`'-1'`, `'151'`, `'abc'`, `''`). Write `parseAgeTests(callable \$parseAge): void` that throws if `\$parseAge` is wrong. It must pass the correct version and catch four buggy ones, including one that throws the wrong kind of exception.",
            'starter_code' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function parseAgeTests(callable $parseAge): void
                {
                    check($parseAge('42') === 42, 'reads a number');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function rejects(callable $parseAge, string $input): void
                {
                    try {
                        $parseAge($input);
                    } catch (InvalidArgumentException) {
                        return;
                    }

                    throw new Exception("'$input' should throw InvalidArgumentException");
                }

                function parseAgeTests(callable $parseAge): void
                {
                    check($parseAge('42') === 42, 'reads a number');
                    check($parseAge('0') === 0, '0 is allowed');
                    check($parseAge('150') === 150, '150 is allowed');
                    rejects($parseAge, '151');
                    rejects($parseAge, 'abc');
                    rejects($parseAge, '-1');
                }
                PHP,
            'hint' => 'A helper `rejects($parseAge, $input)` can call it in `try`, `return` from `catch (InvalidArgumentException)`, and throw after the `try` if nothing (or something else) was thrown. Don\'t catch `Throwable` there, or the wrong exception class would pass.',
            'checks' => [
                ['label' => 'Your tests pass the correct parseAge()', 'code' => <<<'PHP'
                    try {
                        parseAgeTests(fn (string $t) => preg_match('/^\d+$/', $t) === 1 && (int) $t <= 150 ? (int) $t : throw new InvalidArgumentException("Not an age: $t"));
                        return true;
                    } catch (Throwable $e) {
                        return 'Your tests fail the correct parseAge(): ' . $e->getMessage();
                    }
                    PHP],
                ['label' => 'Catches -1 returned instead of throwing', 'code' => <<<'PHP'
                    try {
                        parseAgeTests(fn (string $t) => preg_match('/^\d+$/', $t) === 1 && (int) $t <= 150 ? (int) $t : -1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a parseAge() that returns -1 for bad input instead of throwing.';
                    PHP],
                ['label' => 'Catches the wrong exception class', 'code' => <<<'PHP'
                    try {
                        parseAgeTests(fn (string $t) => preg_match('/^\d+$/', $t) === 1 && (int) $t <= 150 ? (int) $t : throw new RuntimeException("Not an age: $t"));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a parseAge() that throws RuntimeException instead of InvalidArgumentException.';
                    PHP],
                ['label' => 'Catches 151 being accepted', 'code' => <<<'PHP'
                    try {
                        parseAgeTests(fn (string $t) => preg_match('/^\d+$/', $t) === 1 && (int) $t <= 151 ? (int) $t : throw new InvalidArgumentException("Not an age: $t"));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed a parseAge() that accepts 151.';
                    PHP],
                ['label' => 'Catches 0 being rejected', 'code' => <<<'PHP'
                    try {
                        parseAgeTests(fn (string $t) => preg_match('/^[1-9]\d*$/', $t) === 1 && (int) $t <= 150 ? (int) $t : throw new InvalidArgumentException("Not an age: $t"));
                    } catch (Throwable) {
                        return true;
                    }
                    return "Your tests passed a parseAge() that rejects '0'.";
                    PHP],
            ],
        ],
        [
            'title' => 'One test per rule',
            'prompt' => '`isStrong(string $password): bool` is true only when the password has **at least 10** characters, a digit, an upper-case letter and a lower-case letter. Write `isStrongTests(callable $isStrong): void` that throws if `$isStrong` is wrong. It must pass the correct version and catch five buggy ones, each of which gets a single rule wrong.',
            'starter_code' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function isStrongTests(callable $isStrong): void
                {
                    check($isStrong('Correct1Horse') === true, 'a good password');
                    check($isStrong('abc') === false, 'a bad password');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function isStrongTests(callable $isStrong): void
                {
                    check($isStrong('Abcdefgh12') === true, '10 characters with all three kinds is strong');
                    check($isStrong('Abcdefgh1') === false, '9 characters is too short');
                    check($isStrong('Abcdefghij') === false, 'needs a digit');
                    check($isStrong('abcdefgh12') === false, 'needs an upper-case letter');
                    check($isStrong('ABCDEFGH12') === false, 'needs a lower-case letter');
                }
                PHP,
            'hint' => 'Start from a password that just passes, exactly 10 characters, then break one rule at a time: one character shorter, no digit, no upper-case, no lower-case.',
            'checks' => [
                ['label' => 'Your tests pass the correct isStrong()', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) >= 10 && preg_match('/\d/', $p) === 1 && preg_match('/[A-Z]/', $p) === 1 && preg_match('/[a-z]/', $p) === 1);
                        return true;
                    } catch (Throwable $e) {
                        return 'Your tests fail the correct isStrong(): ' . $e->getMessage();
                    }
                    PHP],
                ['label' => 'Catches a length rule of more than 10', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) > 10 && preg_match('/\d/', $p) === 1 && preg_match('/[A-Z]/', $p) === 1 && preg_match('/[a-z]/', $p) === 1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isStrong() that needs 11 characters.';
                    PHP],
                ['label' => 'Catches a length rule of 9', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) >= 9 && preg_match('/\d/', $p) === 1 && preg_match('/[A-Z]/', $p) === 1 && preg_match('/[a-z]/', $p) === 1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isStrong() that accepts 9 characters.';
                    PHP],
                ['label' => 'Catches a missing digit rule', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) >= 10 && preg_match('/[A-Z]/', $p) === 1 && preg_match('/[a-z]/', $p) === 1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isStrong() that doesn\'t need a digit.';
                    PHP],
                ['label' => 'Catches a missing upper-case rule', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) >= 10 && preg_match('/\d/', $p) === 1 && preg_match('/[a-z]/', $p) === 1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isStrong() that doesn\'t need an upper-case letter.';
                    PHP],
                ['label' => 'Catches a missing lower-case rule', 'code' => <<<'PHP'
                    try {
                        isStrongTests(fn (string $p) => strlen($p) >= 10 && preg_match('/\d/', $p) === 1 && preg_match('/[A-Z]/', $p) === 1);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isStrong() that doesn\'t need a lower-case letter.';
                    PHP],
            ],
        ],
        [
            'title' => 'Characterise legacy code',
            'prompt' => "Write `characterise(callable \$f, array \$inputs): array`, which records what `\$f` does for each input, in order: `['input' => \$input, 'output' => \$result]`, or `['input' => \$input, 'throws' => ExceptionClass]` if it throws. Keep outputs exactly as returned (an `int` stays an `int`).",
            'starter_code' => <<<'PHP'
                <?php

                function characterise(callable $f, array $inputs): array
                {
                    return array_map(fn ($input) => ['input' => $input, 'output' => $f($input)], $inputs);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function characterise(callable $f, array $inputs): array
                {
                    $records = [];

                    foreach ($inputs as $input) {
                        try {
                            $records[] = ['input' => $input, 'output' => $f($input)];
                        } catch (Throwable $e) {
                            $records[] = ['input' => $input, 'throws' => $e::class];
                        }
                    }

                    return $records;
                }
                PHP,
            'hint' => 'Loop over the inputs with a `try`/`catch (Throwable $e)` around each call, and record `$e::class` when it throws.',
            'checks' => [
                ['label' => 'Records outputs exactly', 'code' => <<<'PHP'
                    $legacy = fn (float $w) => $w <= 0 ? 0 : ($w < 2 ? 3.5 : 3.5 + ceil($w - 2) * 1.25);
                    return expect_same(characterise($legacy, [0.0, 1.0, 2.5]), [
                        ['input' => 0.0, 'output' => 0],
                        ['input' => 1.0, 'output' => 3.5],
                        ['input' => 2.5, 'output' => 4.75],
                    ]);
                    PHP],
                ['label' => 'Records exceptions and carries on', 'code' => <<<'PHP'
                    $legacy = fn (int $n) => $n === 0 ? throw new DivisionByZeroError('zero') : intdiv(100, $n);
                    return expect_same(characterise($legacy, [4, 0, 3]), [
                        ['input' => 4, 'output' => 25],
                        ['input' => 0, 'throws' => 'DivisionByZeroError'],
                        ['input' => 3, 'output' => 33],
                    ]);
                    PHP],
            ],
        ],
        [
            'title' => 'Tests for leap years',
            'prompt' => 'A year is a leap year if it divides by 4, except years dividing by 100, which are leap years only if they also divide by 400. Write `leapYearTests(callable $isLeap): void` that throws if `$isLeap(int $year): bool` is wrong. It must pass the correct version and catch four buggy ones.',
            'starter_code' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function leapYearTests(callable $isLeap): void
                {
                    check($isLeap(2024) === true, '2024 is a leap year');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function check(bool $condition, string $message): void
                {
                    if (! $condition) {
                        throw new Exception($message);
                    }
                }

                function leapYearTests(callable $isLeap): void
                {
                    check($isLeap(2024) === true, 'divisible by 4 is a leap year');
                    check($isLeap(2023) === false, 'not divisible by 4 is not');
                    check($isLeap(2022) === false, 'even but not divisible by 4 is not');
                    check($isLeap(1900) === false, 'centuries are not');
                    check($isLeap(2000) === true, 'unless divisible by 400');
                }
                PHP,
            'hint' => 'Pick one year from each class: divisible by 4, not divisible by 4 (try an even one), a century such as 1900, and a multiple of 400 such as 2000.',
            'checks' => [
                ['label' => 'Your tests pass the correct isLeap()', 'code' => <<<'PHP'
                    try {
                        leapYearTests(fn (int $y) => $y % 4 === 0 && ($y % 100 !== 0 || $y % 400 === 0));
                        return true;
                    } catch (Throwable $e) {
                        return 'Your tests fail the correct isLeap(): ' . $e->getMessage();
                    }
                    PHP],
                ['label' => 'Catches a forgotten century rule', 'code' => <<<'PHP'
                    try {
                        leapYearTests(fn (int $y) => $y % 4 === 0);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isLeap() that says 1900 is a leap year.';
                    PHP],
                ['label' => 'Catches a forgotten 400 rule', 'code' => <<<'PHP'
                    try {
                        leapYearTests(fn (int $y) => $y % 4 === 0 && $y % 100 !== 0);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isLeap() that says 2000 is not a leap year.';
                    PHP],
                ['label' => 'Catches every even year being a leap year', 'code' => <<<'PHP'
                    try {
                        leapYearTests(fn (int $y) => $y % 2 === 0 && ($y % 100 !== 0 || $y % 400 === 0));
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isLeap() that says 2022 is a leap year.';
                    PHP],
                ['label' => 'Catches only multiples of 400 counting', 'code' => <<<'PHP'
                    try {
                        leapYearTests(fn (int $y) => $y % 400 === 0);
                    } catch (Throwable) {
                        return true;
                    }
                    return 'Your tests passed an isLeap() that says 2024 is not a leap year.';
                    PHP],
            ],
        ],
    ],
];
