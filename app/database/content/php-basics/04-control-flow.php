<?php

// Lesson 4 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'control-flow',
    'title' => 'Control flow',
    'summary' => 'Make decisions with if, the ternary operator and match, and repeat work with while, for and foreach, using break and continue.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        for ($i = 1; $i <= 5; $i++) {
            $label = match (true) {
                $i % 2 === 0 => 'even',
                default => 'odd',
            };
            echo "$i is $label\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $stock = 0;
                if ($stock > 10) {
                    echo 'plenty';
                } elseif ($stock > 0) {
                    echo 'some';
                } else {
                    echo 'none';
                }
                PHP,
            'options' => ['`plenty`', '`some`', '`none`', '`somenone`'],
            'correct_option' => 2,
            'explanation' => 'Neither condition is true for 0, so the `else` block runs.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $n = 3;
                echo $n > 5 ? 'big' : 'small';
                PHP,
            'options' => ['`big`', '`small`', '`3`', '`bigsmall`'],
            'correct_option' => 1,
            'explanation' => 'The condition is false, so the value after `:` is used.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                echo match ('2') {
                    2 => 'number',
                    '2' => 'text',
                    default => 'other',
                };
                PHP,
            'options' => ['`number`', '`text`', '`other`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`match` compares with `===`, so the string `\'2\'` doesn\'t match the int 2.',
        ],
        [
            'question' => 'What happens if a `match` has no `default` and no case matches?',
            'options' => [
                'It returns `null`',
                'It returns the first case',
                'It stops with an `UnhandledMatchError`',
                'Nothing; the code carries on',
            ],
            'correct_option' => 2,
            'explanation' => 'An unmatched value is an error, so a forgotten case is noticed instead of quietly giving nothing.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                for ($i = 0; $i < 3; $i++) {
                    echo $i;
                }
                PHP,
            'options' => ['`123`', '`012`', '`0123`', '`3`'],
            'correct_option' => 1,
            'explanation' => 'It starts at 0, and stops before 3, because `$i < 3` is false when `$i` is 3.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                foreach ([1, 2, 3, 4, 5] as $n) {
                    if ($n === 2) {
                        continue;
                    }
                    if ($n === 4) {
                        break;
                    }
                    echo $n;
                }
                PHP,
            'options' => ['`1345`', '`13`', '`135`', '`1`'],
            'correct_option' => 1,
            'explanation' => '`continue` skips 2; `break` stops the loop entirely at 4, before 4 or 5 are printed.',
        ],
        [
            'question' => 'How many times does this loop print?',
            'code' => <<<'PHP'
                $n = 10;
                while ($n > 0) {
                    echo '*';
                    $n -= 4;
                }
                PHP,
            'options' => ['2', '3', '4', 'Forever'],
            'correct_option' => 1,
            'explanation' => '`$n` goes 10, 6, 2, then -2, which stops the loop: three stars.',
        ],
        [
            'question' => 'Which loop is the natural choice for visiting every item of an array?',
            'options' => ['`while`', '`for`', '`foreach`', '`match`'],
            'correct_option' => 2,
            'explanation' => '`foreach` visits each item (and optionally its key) without any counting.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Stock message',
            'prompt' => "Set `\$message` from `\$stock`: `'Sold out'` for 0, `'Last copy!'` for 1, `'Only N left'` (with the number) for 2 to 4, and `'In stock'` for 5 or more.",
            'starter_code' => <<<'PHP'
                <?php

                $stock = 3;
                $message = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $stock = 3;

                if ($stock === 0) {
                    $message = 'Sold out';
                } elseif ($stock === 1) {
                    $message = 'Last copy!';
                } elseif ($stock < 5) {
                    $message = "Only $stock left";
                } else {
                    $message = 'In stock';
                }
                PHP,
            'hint' => 'An `if` / `elseif` / `else` chain, checking 0, then 1, then less than 5. Double quotes put the number in the message.',
            'checks' => [
                ['label' => "3 copies: 'Only 3 left'", 'code' => "return expect_same(\$GLOBALS['message'] ?? null, 'Only 3 left');"],
                ['label' => 'Every stock level gets its message', 'code' => <<<'PHP'
                    $results = [];
                    foreach ([0, 1, 4, 5, 40] as $stock) {
                        $variant = str_replace('$stock = 3;', "\$stock = $stock;", learnp_code());
                        $results[] = learnp_run($variant . "\necho \$message;");
                    }
                    return expect_same($results, ['Sold out', 'Last copy!', 'Only 4 left', 'In stock', 'In stock']);
                    PHP],
            ],
        ],
        [
            'title' => 'Day names',
            'prompt' => "Using `match`, set `\$type` to `'weekend'` when `\$day` is `'Saturday'` or `'Sunday'`, `'weekday'` for the other five days, and `'unknown'` for anything else.",
            'starter_code' => <<<'PHP'
                <?php

                $day = 'Sunday';
                $type = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $day = 'Sunday';

                $type = match ($day) {
                    'Saturday', 'Sunday' => 'weekend',
                    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' => 'weekday',
                    default => 'unknown',
                };
                PHP,
            'hint' => 'List several values in one case, separated by commas, and finish with `default`.',
            'checks' => [
                ['label' => "Sunday is 'weekend'", 'code' => "return expect_same(\$GLOBALS['type'] ?? null, 'weekend');"],
                ['label' => 'It uses match', 'code' => "return str_contains(learnp_code(), 'match') ?: 'Use match.';"],
                ['label' => 'Every day, and nonsense', 'code' => <<<'PHP'
                    $results = [];
                    foreach (['Saturday', 'Wednesday', 'Friday', 'Funday'] as $day) {
                        $results[] = learnp_run(str_replace("\$day = 'Sunday';", "\$day = '$day';", learnp_code()) . "\necho \$type;");
                    }
                    return expect_same($results, ['weekend', 'weekday', 'weekday', 'unknown']);
                    PHP],
            ],
        ],
        [
            'title' => 'FizzBuzz',
            'prompt' => 'Print the numbers from 1 to 15, one per line, but print `Fizz` instead of numbers divisible by 3, `Buzz` instead of numbers divisible by 5, and `FizzBuzz` for numbers divisible by both.',
            'starter_code' => <<<'PHP'
                <?php

                for ($i = 1; $i <= 15; $i++) {
                    echo "$i\n";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                for ($i = 1; $i <= 15; $i++) {
                    echo match (true) {
                        $i % 15 === 0 => 'FizzBuzz',
                        $i % 3 === 0 => 'Fizz',
                        $i % 5 === 0 => 'Buzz',
                        default => $i,
                    } . "\n";
                }
                PHP,
            'hint' => 'Check "divisible by both" (`$i % 15 === 0`) **first**, then 3, then 5. `match (true)` or `if`/`elseif` both work.',
            'checks' => [
                ['label' => 'The first 15 lines are right', 'code' => "return expect_same(trim(learnp_output()), implode(\"\\n\", ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz']));"],
            ],
        ],
        [
            'title' => 'Add it up',
            'prompt' => "Using a loop, set `\$sum` to the total of all the whole numbers from 1 to `\$n`. For `\$n = 100` that's 5050.",
            'starter_code' => <<<'PHP'
                <?php

                $n = 100;
                $sum = 0;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $n = 100;
                $sum = 0;

                for ($i = 1; $i <= $n; $i++) {
                    $sum += $i;
                }
                PHP,
            'hint' => '`for ($i = 1; $i <= $n; $i++)`, adding `$i` to `$sum` each time.',
            'checks' => [
                ['label' => 'For 100 the sum is 5050', 'code' => "return expect_same(\$GLOBALS['sum'] ?? null, 5050);"],
                ['label' => 'It works for any n', 'code' => "return expect_same([learnp_run(str_replace('\$n = 100;', '\$n = 4;', learnp_code()) . \"\\necho \\\$sum;\"), learnp_run(str_replace('\$n = 100;', '\$n = 0;', learnp_code()) . \"\\necho \\\$sum;\")], ['10', '0']);"],
            ],
        ],
        [
            'title' => 'First book over budget',
            'prompt' => 'Go through `$prices` in order and set `$first` to the first price above `$budget`, stopping the loop as soon as you find it. If none is above the budget, `$first` stays `null`.',
            'starter_code' => <<<'PHP'
                <?php

                $prices = [7.99, 12.50, 21.00, 9.99, 35.00];
                $budget = 15.0;
                $first = null;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $prices = [7.99, 12.50, 21.00, 9.99, 35.00];
                $budget = 15.0;
                $first = null;

                foreach ($prices as $price) {
                    if ($price > $budget) {
                        $first = $price;
                        break;
                    }
                }
                PHP,
            'hint' => 'In a `foreach`, when `$price > $budget`, store it and `break`.',
            'checks' => [
                ['label' => '$first is 21.0', 'code' => "return expect_same(\$GLOBALS['first'] ?? null, 21.0);"],
                ['label' => 'With a big budget, $first stays null', 'code' => "return expect_same(learnp_run(str_replace('\$budget = 15.0;', '\$budget = 100.0;', learnp_code()) . \"\\nvar_export(\\\$first);\"), 'NULL');"],
                ['label' => 'The loop stops early', 'code' => "return str_contains(learnp_code(), 'break') ?: 'Stop the loop with break once you find it.';"],
            ],
        ],
        [
            'title' => 'A times table',
            'prompt' => 'Print the times table for `$number` from 1 to 5, one line each, in the form `3 x 4 = 12`.',
            'starter_code' => <<<'PHP'
                <?php

                $number = 7;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $number = 7;

                for ($i = 1; $i <= 5; $i++) {
                    echo "$number x $i = " . $number * $i . "\n";
                }
                PHP,
            'hint' => 'A `for` loop from 1 to 5, printing `"$number x $i = "` and the product, then `"\n"`.',
            'checks' => [
                ['label' => 'The 7 times table', 'code' => 'return expect_same(trim(learnp_output()), "7 x 1 = 7\\n7 x 2 = 14\\n7 x 3 = 21\\n7 x 4 = 28\\n7 x 5 = 35");'],
                ['label' => 'It follows $number', 'code' => "return expect_same(trim(learnp_run(str_replace('\$number = 7;', '\$number = 2;', learnp_code()))), \"2 x 1 = 2\\n2 x 2 = 4\\n2 x 3 = 6\\n2 x 4 = 8\\n2 x 5 = 10\");"],
            ],
        ],
    ],
];
