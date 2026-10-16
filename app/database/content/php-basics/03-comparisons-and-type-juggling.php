<?php

// Lesson 3 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'comparisons-and-type-juggling',
    'title' => 'Comparisons and type juggling',
    'summary' => 'Compare values with == and ===, learn which values are truthy or falsy, convert types with casts, check them with is_*() functions, and switch on strict types.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        var_dump('42' == 42);
        var_dump('42' === 42);
        var_dump((int) '42 apples');
        var_dump('0' ? 'truthy' : 'falsy');
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                var_dump('7' == 7);
                var_dump('7' === 7);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
            ],
            'correct_option' => 1,
            'explanation' => '`==` converts the string to a number first. `===` also checks the type, and a string isn\'t an int.',
        ],
        [
            'question' => 'In PHP 8, what does `var_dump(0 == \'a\');` print?',
            'options' => ['`bool(true)`', '`bool(false)`', '`int(0)`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`\'a\'` isn\'t numeric, so PHP 8 compares them as strings: `\'0\'` and `\'a\'` differ. PHP 7 said `true`.',
        ],
        [
            'question' => 'Which of these values is **falsy**?',
            'options' => ["`'false'`", "`' '`", "`'0'`", "`'0.0'`"],
            'correct_option' => 2,
            'explanation' => 'Of all the strings, only `\'\'` and `\'0\'` are falsy. Any other text, even `\'false\'`, is truthy.',
        ],
        [
            'question' => "What does `var_dump((int) '12.9 kg');` print?",
            'options' => ['`int(13)`', '`int(12)`', '`float(12.9)`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Casting to int reads the number at the start, stops at the first character that isn\'t part of it, and drops the fraction.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                var_dump(null == false);
                var_dump(null === false);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(false)`',
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
            ],
            'correct_option' => 2,
            'explanation' => 'Loosely, `null` converts to `false`. Strictly, they\'re different types.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => "var_dump('100' == '1e2');",
            'options' => ['`bool(false)`', '`bool(true)`', '`int(100)`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Both strings look like numbers, so `==` compares them as numbers: 100 and 1×10², which are equal. `===` would say false.',
        ],
        [
            'question' => 'What does `declare(strict_types=1);` at the top of a file change?',
            'options' => [
                'It makes every variable need a type',
                'Calls from that file to typed parameters no longer convert values; the wrong type is an error',
                'It makes `==` behave like `===`',
                'It stops casts like `(int)` from working',
            ],
            'correct_option' => 1,
            'explanation' => 'It only affects how typed parameters and return values accept values, for calls written in that file.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $calls = 0;

                function check(): bool
                {
                    global $calls;
                    $calls++;
                    return true;
                }

                $a = false && check();
                $b = true || check();
                $c = true && check();

                echo $calls;
                PHP,
            'options' => ['`0`', '`1`', '`2`', '`3`'],
            'correct_option' => 1,
            'explanation' => '`false && …` and `true || …` already know their answer, so they skip `check()`. Only `true && check()` calls it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Truthy or falsy?',
            'prompt' => "Write a function `truthiness(mixed \$value): string` that returns `'truthy'` or `'falsy'`, depending on how PHP treats the value in an `if`.",
            'starter_code' => <<<'PHP'
                <?php

                function truthiness(mixed $value): string
                {
                    return $value === false ? 'falsy' : 'truthy';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function truthiness(mixed $value): string
                {
                    return $value ? 'truthy' : 'falsy';
                }
                PHP,
            'hint' => 'Let PHP decide: `return $value ? \'truthy\' : \'falsy\';`.',
            'checks' => [
                ['label' => 'Falsy values', 'code' => "return expect_same(array_map('truthiness', [false, 0, 0.0, '', '0', [], null]), array_fill(0, 7, 'falsy'));"],
                ['label' => 'Surprising truthy values', 'code' => "return expect_same(array_map('truthiness', ['false', ' ', '0.0', -1, [0]]), array_fill(0, 5, 'truthy'));"],
            ],
        ],
        [
            'title' => 'Count the falsy values',
            'prompt' => 'Set `$falsy` to the number of falsy values in `$values`.',
            'starter_code' => <<<'PHP'
                <?php

                $values = [0, 'a', '', null, '0', [], 'false', 0.0, true, 'null'];
                $falsy = 0;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $values = [0, 'a', '', null, '0', [], 'false', 0.0, true, 'null'];

                $falsy = 0;
                foreach ($values as $value) {
                    if (! $value) {
                        $falsy++;
                    }
                }
                PHP,
            'hint' => 'Loop with `foreach ($values as $value)` and add one when `! $value`.',
            'checks' => [
                ['label' => '$falsy is 6', 'code' => "return expect_same(\$GLOBALS['falsy'] ?? null, 6);"],
                ['label' => 'It works for other values', 'code' => <<<'PHP'
                    $variant = preg_replace('/\$values = \[.*?\];/s', "\$values = ['0', 'no', 0, 1];", learnp_code(), 1);
                    return expect_same(learnp_run($variant . "\necho \$falsy;"), '2');
                    PHP],
            ],
        ],
        [
            'title' => 'A safe whole number',
            'prompt' => "Write a function `toWholeNumber(string \$input): ?int` that returns the whole number in `\$input`, ignoring spaces at either end, or `null` if it isn't exactly a whole number. `' 42 '` gives 42 and `'-3'` gives -3, but `'4.5'`, `'42 apples'`, `''` and `'abc'` give `null`.",
            'starter_code' => <<<'PHP'
                <?php

                function toWholeNumber(string $input): ?int
                {
                    return (int) $input;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function toWholeNumber(string $input): ?int
                {
                    $clean = trim($input);

                    return preg_match('/^-?\d+$/', $clean) ? (int) $clean : null;
                }
                PHP,
            'hint' => 'Trim first. `(string) (int) $clean === $clean` is one test; a regular expression such as `/^-?\d+$/` is another.',
            'checks' => [
                ['label' => 'Whole numbers are converted', 'code' => "return expect_same([toWholeNumber(' 42 '), toWholeNumber('-3'), toWholeNumber('0')], [42, -3, 0]);"],
                ['label' => 'Anything else gives null', 'code' => "return expect_same(array_map('toWholeNumber', ['4.5', '42 apples', '', 'abc', '1e3']), [null, null, null, null, null]);"],
            ],
        ],
        [
            'title' => 'The leading zero bug',
            'prompt' => "Staff codes such as `'007'` are text, but `hasCode()` says `'7'` is in the list, because loose comparison treats both as numbers. Fix it so codes must match exactly.",
            'starter_code' => <<<'PHP'
                <?php

                function hasCode(array $codes, string $code): bool
                {
                    return in_array($code, $codes);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function hasCode(array $codes, string $code): bool
                {
                    return in_array($code, $codes, true);
                }
                PHP,
            'hint' => '`in_array()` takes a third argument: pass `true` to compare with `===`.',
            'checks' => [
                ['label' => "'007' is found", 'code' => "return expect_same(hasCode(['007', '042'], '007'), true);"],
                ['label' => "'7' is not the same code as '007'", 'code' => "return expect_same(hasCode(['007', '042'], '7'), false);"],
                ['label' => "'42.0' is not '042'", 'code' => "return expect_same(hasCode(['007', '042'], '42.0'), false);"],
            ],
        ],
        [
            'title' => 'Sort by length',
            'prompt' => 'Write a function `byLength(string $a, string $b): int` for `usort()` that orders words from shortest to longest, and words of the same length alphabetically. Use the spaceship operator `<=>`.',
            'starter_code' => <<<'PHP'
                <?php

                function byLength(string $a, string $b): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function byLength(string $a, string $b): int
                {
                    return [strlen($a), $a] <=> [strlen($b), $b];
                }
                PHP,
            'hint' => 'Compare `strlen($a) <=> strlen($b)`; when that\'s 0, compare `$a <=> $b`. Comparing the arrays `[strlen($a), $a] <=> [strlen($b), $b]` does both.',
            'checks' => [
                ['label' => 'Shorter words first, then alphabetical', 'code' => <<<'PHP'
                    $words = ['Dune', 'Circe', 'It', 'Emma', 'Beloved'];
                    usort($words, 'byLength');
                    return expect_same($words, ['It', 'Dune', 'Emma', 'Circe', 'Beloved']);
                    PHP],
                ['label' => 'Equal words compare as 0', 'code' => "return expect_same(byLength('Dune', 'Dune'), 0);"],
            ],
        ],
        [
            'title' => 'Between the limits',
            'prompt' => 'Write a function `inRange(mixed $value, int $min, int $max): bool` that is `true` only when `$value` is an actual `int` (not a string or float) from `$min` to `$max`, both included.',
            'starter_code' => <<<'PHP'
                <?php

                function inRange(mixed $value, int $min, int $max): bool
                {
                    return $value >= $min && $value <= $max;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function inRange(mixed $value, int $min, int $max): bool
                {
                    return is_int($value) && $value >= $min && $value <= $max;
                }
                PHP,
            'hint' => 'Add `is_int($value) &&` in front. Thanks to short-circuiting, the comparisons only run for ints.',
            'checks' => [
                ['label' => 'Ints inside the range, ends included', 'code' => 'return expect_same([inRange(1, 1, 10), inRange(10, 1, 10), inRange(5, 1, 10)], [true, true, true]);'],
                ['label' => 'Ints outside the range', 'code' => 'return expect_same([inRange(0, 1, 10), inRange(11, 1, 10)], [false, false]);'],
                ['label' => 'Strings, floats and other types are refused', 'code' => "return expect_same([inRange('5', 1, 10), inRange(5.0, 1, 10), inRange(true, 1, 10), inRange(null, 0, 10)], [false, false, false, false]);"],
            ],
        ],
    ],
];
