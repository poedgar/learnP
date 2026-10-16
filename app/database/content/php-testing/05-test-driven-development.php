<?php

// Lesson 5 of the Testing without frameworks course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Every challenge is a kata: its checks are the tests, in the order you might write them in TDD.

return [
    'slug' => 'test-driven-development',
    'title' => 'Test-driven development',
    'summary' => 'Write the test first: red, green, refactor; fake it, triangulate, and choose the next test; then practise the rhythm on classic katas.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // Red: this test fails. Make it green with the simplest change, then add the next test.
        function greet(string $name): string
        {
            return '';
        }

        $tests = [
            'greets by name' => greet('Amara') === 'Hello, Amara!',
        ];

        foreach ($tests as $name => $passed) {
            echo ($passed ? '✓' : '✗') . " $name\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What is the order of the TDD loop?',
            'options' => [
                'Code, test, refactor',
                'Red (a failing test), green (just enough code to pass), refactor (tidy while green)',
                'Refactor, red, green',
                'Write all tests, then all code',
            ],
            'correct_option' => 1,
            'explanation' => 'Each loop is small: one test, the simplest code that passes it, then clean-up.',
        ],
        [
            'question' => 'Why watch the new test fail before writing the code?',
            'options' => [
                'It\'s a tradition with no purpose',
                'It proves the test can fail; a test that passes before the code exists isn\'t testing anything',
                'Failing tests run faster',
                'To check that PHP is installed',
            ],
            'correct_option' => 1,
            'explanation' => 'Red first is how you know the test is wired up correctly.',
        ],
        [
            'question' => 'What is "triangulation" in TDD?',
            'options' => [
                'Testing three times',
                'Adding a second example that a faked answer can\'t pass, which forces a general solution',
                'Drawing a diagram of the code',
                'Running tests in three environments',
            ],
            'correct_option' => 1,
            'explanation' => 'After `add(\'4\') === 4` can be faked, `add(\'1,2\') === 3` forces real addition.',
        ],
        [
            'question' => 'When should you refactor in TDD?',
            'options' => [
                'Only when tests are red',
                'Only when all tests pass, running them after each small change',
                'Never',
                'Before writing any tests',
            ],
            'correct_option' => 1,
            'explanation' => 'Green tests are what tell you the behaviour hasn\'t changed.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function add(string $numbers): int
                {
                    return $numbers === '' ? 0 : array_sum(array_map('intval', explode(',', $numbers)));
                }

                echo add('') . ' ' . add('7') . ' ' . add('1,2,3');
                PHP,
            'options' => ['`0 7 6`', '` 7 6`', '`0 7 123`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'An empty string gives 0; otherwise the parts are converted to integers and summed.',
        ],
        [
            'question' => 'In which order is it usually best to write tests?',
            'options' => [
                'The hardest case first',
                'Degenerate cases (empty, zero, one), then the simplest real case, then variations, then errors',
                'Errors first',
                'Random order',
            ],
            'correct_option' => 1,
            'explanation' => 'Each test should move the code forward one small step.',
        ],
        [
            'question' => 'A test goes red in the middle of a refactoring. What should you do?',
            'options' => [
                'Delete the test',
                'Undo the last small step and try a smaller one',
                'Keep refactoring until it passes',
                'Ignore it',
            ],
            'correct_option' => 1,
            'explanation' => 'A refactoring shouldn\'t change behaviour; going back to green is quicker than debugging forwards.',
        ],
        [
            'question' => 'What is a kata?',
            'options' => [
                'A testing framework',
                'A small exercise repeated to practise a technique, such as FizzBuzz or Roman numerals',
                'A kind of test double',
                'A PHP extension',
            ],
            'correct_option' => 1,
            'explanation' => 'Katas are practice: the goal is the rhythm, not the answer.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Prime factors',
            'prompt' => 'Write `primeFactors(int $n): array`, the prime factors of `$n` from smallest to largest, repeated as often as they divide it: `primeFactors(12)` is `[2, 2, 3]`, and `primeFactors(1)` is `[]`. Work through the checks one at a time.',
            'starter_code' => <<<'PHP'
                <?php

                function primeFactors(int $n): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function primeFactors(int $n): array
                {
                    $factors = [];

                    for ($divisor = 2; $n > 1; $divisor++) {
                        while ($n % $divisor === 0) {
                            $factors[] = $divisor;
                            $n = intdiv($n, $divisor);
                        }
                    }

                    return $factors;
                }
                PHP,
            'hint' => 'Try each divisor from 2 upwards; while it divides `$n`, record it and divide `$n` by it. Stop when `$n` reaches 1.',
            'checks' => [
                ['label' => '1 has no prime factors', 'code' => 'return expect_same(primeFactors(1), []);'],
                ['label' => 'A prime is its own factor', 'code' => 'return expect_same([primeFactors(2), primeFactors(3), primeFactors(13)], [[2], [3], [13]]);'],
                ['label' => 'Repeated factors', 'code' => 'return expect_same([primeFactors(4), primeFactors(8), primeFactors(9)], [[2, 2], [2, 2, 2], [3, 3]]);'],
                ['label' => 'Mixed factors, smallest first', 'code' => 'return expect_same([primeFactors(12), primeFactors(30030), primeFactors(9797)], [[2, 2, 3], [2, 3, 5, 7, 11, 13], [97, 101]]);'],
            ],
        ],
        [
            'title' => 'Roman numerals',
            'prompt' => "Write `toRoman(int \$n): string` for 1 to 3999, using subtractive pairs such as `IV`, `IX`, `XL`, `XC`, `CD` and `CM`: `toRoman(1994)` is `'MCMXCIV'`.",
            'starter_code' => <<<'PHP'
                <?php

                function toRoman(int $n): string
                {
                    return str_repeat('I', $n);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function toRoman(int $n): string
                {
                    $values = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
                    $roman = '';

                    foreach ($values as $symbol => $value) {
                        while ($n >= $value) {
                            $roman .= $symbol;
                            $n -= $value;
                        }
                    }

                    return $roman;
                }
                PHP,
            'hint' => 'List every symbol and subtractive pair with its value, largest first. For each, append it while `$n` is at least its value, taking the value off.',
            'checks' => [
                ['label' => '1 to 3', 'code' => "return expect_same([toRoman(1), toRoman(2), toRoman(3)], ['I', 'II', 'III']);"],
                ['label' => '4, 5 and 9', 'code' => "return expect_same([toRoman(4), toRoman(5), toRoman(9)], ['IV', 'V', 'IX']);"],
                ['label' => 'Tens and hundreds', 'code' => "return expect_same([toRoman(14), toRoman(40), toRoman(90), toRoman(400), toRoman(900)], ['XIV', 'XL', 'XC', 'CD', 'CM']);"],
                ['label' => 'Whole years', 'code' => "return expect_same([toRoman(1994), toRoman(2026), toRoman(3999)], ['MCMXCIV', 'MMXXVI', 'MMMCMXCIX']);"],
            ],
        ],
        [
            'title' => 'The bowling game',
            'prompt' => "Write `bowlingScore(array \$rolls): int` for a complete ten-pin game, given every roll's pins. A frame is two rolls. A **spare** (10 in a frame) scores 10 plus the next roll; a **strike** (10 on the first roll, ending the frame) scores 10 plus the next two rolls. The tenth frame's bonus rolls only count as bonuses.",
            'starter_code' => <<<'PHP'
                <?php

                function bowlingScore(array $rolls): int
                {
                    return array_sum($rolls);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bowlingScore(array $rolls): int
                {
                    $score = 0;
                    $roll = 0;

                    for ($frame = 0; $frame < 10; $frame++) {
                        if ($rolls[$roll] === 10) {
                            $score += 10 + $rolls[$roll + 1] + $rolls[$roll + 2];
                            $roll += 1;
                        } elseif ($rolls[$roll] + $rolls[$roll + 1] === 10) {
                            $score += 10 + $rolls[$roll + 2];
                            $roll += 2;
                        } else {
                            $score += $rolls[$roll] + $rolls[$roll + 1];
                            $roll += 2;
                        }
                    }

                    return $score;
                }
                PHP,
            'hint' => 'Loop over exactly 10 frames with an index into the rolls. A strike moves the index on by 1, any other frame by 2.',
            'checks' => [
                ['label' => 'A gutter game scores 0', 'code' => 'return expect_same(bowlingScore(array_fill(0, 20, 0)), 0);'],
                ['label' => 'All ones scores 20', 'code' => 'return expect_same(bowlingScore(array_fill(0, 20, 1)), 20);'],
                ['label' => 'One spare', 'code' => 'return expect_same(bowlingScore([5, 5, 3, ...array_fill(0, 17, 0)]), 16);'],
                ['label' => 'One strike', 'code' => 'return expect_same(bowlingScore([10, 3, 4, ...array_fill(0, 16, 0)]), 24);'],
                ['label' => 'A perfect game scores 300', 'code' => 'return expect_same(bowlingScore(array_fill(0, 12, 10)), 300);'],
                ['label' => 'All spares of five', 'code' => 'return expect_same(bowlingScore(array_fill(0, 21, 5)), 150);'],
            ],
        ],
        [
            'title' => 'Word wrap',
            'prompt' => 'Write `wrapWords(string $text, int $width): string`, which breaks single-spaced text into lines of at most `$width` characters, joined by `\\n`. Break at spaces, fitting as many words on a line as possible. A word longer than `$width` is cut into pieces of `$width` characters.',
            'starter_code' => <<<'PHP'
                <?php

                function wrapWords(string $text, int $width): string
                {
                    return str_replace(' ', "\n", $text);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function wrapWords(string $text, int $width): string
                {
                    $lines = [];
                    $line = '';

                    foreach (explode(' ', $text) as $word) {
                        while (strlen($word) > $width) {
                            if ($line !== '') {
                                $lines[] = $line;
                                $line = '';
                            }
                            $lines[] = substr($word, 0, $width);
                            $word = substr($word, $width);
                        }

                        if ($line === '') {
                            $line = $word;
                        } elseif (strlen($line) + 1 + strlen($word) <= $width) {
                            $line .= ' ' . $word;
                        } else {
                            $lines[] = $line;
                            $line = $word;
                        }
                    }

                    if ($line !== '' || $lines === []) {
                        $lines[] = $line;
                    }

                    return implode("\n", $lines);
                }
                PHP,
            'hint' => 'Build the current line word by word. If adding a space and the next word would go past `$width`, finish the line and start a new one. Cut long words with `substr()` first.',
            'checks' => [
                ['label' => 'Empty text', 'code' => "return expect_same(wrapWords('', 5), '');"],
                ['label' => 'Short text is unchanged', 'code' => "return expect_same([wrapWords('word', 5), wrapWords('a b c', 10)], ['word', 'a b c']);"],
                ['label' => 'Breaks at a space', 'code' => "return expect_same(wrapWords('hello world', 5), \"hello\\nworld\");"],
                ['label' => 'Fits as many words as possible', 'code' => "return expect_same([wrapWords('a b c d', 3), wrapWords('the quick brown fox', 10)], [\"a b\\nc d\", \"the quick\\nbrown fox\"]);"],
                ['label' => 'Cuts long words', 'code' => "return expect_same([wrapWords('abcdefgh', 3), wrapWords('to extraordinary', 5)], [\"abc\\ndef\\ngh\", \"to\\nextra\\nordin\\nary\"]);"],
            ],
        ],
        [
            'title' => 'A shopping basket with offers',
            'prompt' => 'Write `basketTotal(array $items): int`, the total in pence of a list of item names. Prices: `apple` 30, `banana` 20, `cheese` 250. Offers: apples are **3 for 2** (every third apple is free), and cheese is **2 for 400**. An unknown item throws `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function basketTotal(array $items): int
                {
                    $prices = ['apple' => 30, 'banana' => 20, 'cheese' => 250];

                    return array_sum(array_map(fn ($item) => $prices[$item] ?? 0, $items));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function basketTotal(array $items): int
                {
                    $prices = ['apple' => 30, 'banana' => 20, 'cheese' => 250];
                    $counts = array_fill_keys(array_keys($prices), 0);

                    foreach ($items as $item) {
                        if (! isset($prices[$item])) {
                            throw new InvalidArgumentException("Unknown item: $item");
                        }
                        $counts[$item]++;
                    }

                    $apples = ($counts['apple'] - intdiv($counts['apple'], 3)) * $prices['apple'];
                    $cheese = intdiv($counts['cheese'], 2) * 400 + $counts['cheese'] % 2 * $prices['cheese'];

                    return $apples + $counts['banana'] * $prices['banana'] + $cheese;
                }
                PHP,
            'hint' => 'Count each item first. Apples: pay for the count minus `intdiv($count, 3)`. Cheese: `intdiv($count, 2)` pairs at 400, plus 250 for an odd one.',
            'checks' => [
                ['label' => 'An empty basket is 0', 'code' => 'return expect_same(basketTotal([]), 0);'],
                ['label' => 'Single items', 'code' => "return expect_same([basketTotal(['apple']), basketTotal(['apple', 'banana'])], [30, 50]);"],
                ['label' => 'Apples are 3 for 2', 'code' => "return expect_same([basketTotal(array_fill(0, 3, 'apple')), basketTotal(array_fill(0, 4, 'apple')), basketTotal(array_fill(0, 6, 'apple'))], [60, 90, 120]);"],
                ['label' => 'Cheese is 2 for 400', 'code' => "return expect_same([basketTotal(['cheese', 'cheese']), basketTotal(['cheese', 'apple', 'cheese', 'cheese'])], [400, 680]);"],
                ['label' => 'Unknown items are rejected', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => basketTotal(['apple', 'durian']));"],
            ],
        ],
        [
            'title' => 'Parsing durations',
            'prompt' => "Write `parseDuration(string \$text): int`, which turns text like `'1h 30m'` into minutes. Parts are a whole number followed by `d` (day), `h` or `m`, optionally separated by spaces, and are added up. An empty string is `0`; anything else, such as `'5x'` or `'1h soon'`, throws `InvalidArgumentException`.",
            'starter_code' => <<<'PHP'
                <?php

                function parseDuration(string $text): int
                {
                    return (int) $text;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseDuration(string $text): int
                {
                    if (trim($text) === '') {
                        return 0;
                    }

                    if (preg_match('/^\s*(\d+[dhm]\s*)+$/', $text) !== 1) {
                        throw new InvalidArgumentException("Not a duration: $text");
                    }

                    preg_match_all('/(\d+)([dhm])/', $text, $parts, PREG_SET_ORDER);
                    $minutes = ['d' => 1440, 'h' => 60, 'm' => 1];

                    return array_sum(array_map(fn (array $part) => (int) $part[1] * $minutes[$part[2]], $parts));
                }
                PHP,
            'hint' => 'First check that the whole text matches `/^\s*(\d+[dhm]\s*)+$/`; then `preg_match_all()` the parts and add each number times its unit\'s minutes.',
            'checks' => [
                ['label' => 'Empty is 0', 'code' => "return expect_same(parseDuration(''), 0);"],
                ['label' => 'Single units', 'code' => "return expect_same([parseDuration('45m'), parseDuration('2h'), parseDuration('1d')], [45, 120, 1440]);"],
                ['label' => 'Parts are added', 'code' => "return expect_same([parseDuration('1h 30m'), parseDuration('1h30m'), parseDuration('1d 2h 5m')], [90, 90, 1565]);"],
                ['label' => 'Nonsense is rejected', 'code' => <<<'PHP'
                    foreach (['5x', 'abc', '1h soon', 'h'] as $input) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => parseDuration($input));
                        if ($result !== true) {
                            return "'$input': $result";
                        }
                    }
                    return true;
                    PHP],
            ],
        ],
    ],
];
