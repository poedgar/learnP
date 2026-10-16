<?php

// Lesson 7 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'functions',
    'title' => 'Functions',
    'summary' => 'Write your own functions: typed parameters and return values, defaults, named arguments, variadics, scope, references and recursion.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function priceWithTax(float $price, float $rate = 0.2): float
        {
            return round($price * (1 + $rate), 2);
        }

        echo priceWithTax(10.0) . "\n";
        echo priceWithTax(rate: 0.05, price: 10.0);
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function greet(string $name = 'friend'): string
                {
                    return "Hello, $name!";
                }

                echo greet() . ' ' . greet('Amara');
                PHP,
            'options' => [
                '`Hello, friend! Hello, Amara!`',
                '`Hello, ! Hello, Amara!`',
                '`Hello, friend! Hello, friend!`',
                'An error: `greet()` needs an argument',
            ],
            'correct_option' => 0,
            'explanation' => 'Without an argument, the default `\'friend\'` is used.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function box(int $width = 1, int $height = 1, int $depth = 1): int
                {
                    return $width * $height * $depth;
                }

                echo box(depth: 5, width: 2);
                PHP,
            'options' => ['`10`', '`2`', '`5`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Named arguments can come in any order, and `$height` keeps its default of 1: 2 × 1 × 5.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function countArgs(string ...$words): int
                {
                    return count($words);
                }

                echo countArgs('a', 'b', 'c') + countArgs();
                PHP,
            'options' => ['`3`', '`4`', '`0`', 'An error: `countArgs()` needs an argument'],
            'correct_option' => 0,
            'explanation' => 'A variadic parameter collects any number of arguments, including none, into an array.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $count = 5;

                function bump(int $count): int
                {
                    $count++;
                    return $count;
                }

                echo bump($count) . ' ' . $count;
                PHP,
            'options' => ['`6 6`', '`6 5`', '`5 5`', '`1 5`'],
            'correct_option' => 1,
            'explanation' => 'The function gets a copy of the value. Its `$count` is a separate variable, so the outside one stays 5.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function double(array &$numbers): void
                {
                    foreach ($numbers as $i => $n) {
                        $numbers[$i] = $n * 2;
                    }
                }

                $list = [1, 2, 3];
                double($list);
                echo implode(',', $list);
                PHP,
            'options' => ['`1,2,3`', '`2,4,6`', '`1,2,3,2,4,6`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => 'The `&` passes the caller\'s array itself, so the function changes it.',
        ],
        [
            'question' => 'What does the return type `: void` mean?',
            'options' => [
                'The function can return anything',
                'The function returns nothing',
                'The function returns `null` or a value',
                'The function can\'t have parameters',
            ],
            'correct_option' => 1,
            'explanation' => 'A `void` function does its work (printing, changing something) without handing back a value.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function sumTo(int $n): int
                {
                    if ($n === 0) {
                        return 0;
                    }
                    return $n + sumTo($n - 1);
                }

                echo sumTo(4);
                PHP,
            'options' => ['`4`', '`10`', '`0`', 'It never stops'],
            'correct_option' => 1,
            'explanation' => '4 + 3 + 2 + 1 + 0. The `$n === 0` case stops the recursion.',
        ],
        [
            'question' => 'Which call spreads `$prices = [1, 2, 3]` into three separate arguments?',
            'options' => ['`total($prices)`', '`total(...$prices)`', '`total(&$prices)`', '`total([$prices])`'],
            'correct_option' => 1,
            'explanation' => '`...` in a call unpacks the array; in a parameter list, it collects arguments into one.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Average price',
            'prompt' => 'Write a function `average(array $prices): float` that returns the average of the prices, rounded to 2 decimal places, or `0.0` for an empty list.',
            'starter_code' => <<<'PHP'
                <?php

                function average(array $prices): float
                {
                    return array_sum($prices) / count($prices);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function average(array $prices): float
                {
                    if ($prices === []) {
                        return 0.0;
                    }

                    return round(array_sum($prices) / count($prices), 2);
                }
                PHP,
            'hint' => 'Return `0.0` early when the array is empty, then `round(array_sum(…) / count(…), 2)`.',
            'checks' => [
                ['label' => 'average([10, 20, 31]) is 20.33', 'code' => 'return expect_same(average([10, 20, 31]), 20.33);'],
                ['label' => 'An empty list gives 0.0', 'code' => 'return expect_same(average([]), 0.0);'],
            ],
        ],
        [
            'title' => 'A flexible label',
            'prompt' => "Write a function `shelfLabel(string \$title, string \$author = 'Unknown', bool \$upper = false): string` returning `'Title — Author'` (with an em dash and spaces), in capitals when `\$upper` is true.",
            'starter_code' => <<<'PHP'
                <?php

                function shelfLabel(string $title, string $author, bool $upper): string
                {
                    return '';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shelfLabel(string $title, string $author = 'Unknown', bool $upper = false): string
                {
                    $label = "$title — $author";

                    return $upper ? strtoupper($label) : $label;
                }
                PHP,
            'hint' => 'Give `$author` and `$upper` default values, build the label, and `strtoupper()` it when `$upper` is true.',
            'checks' => [
                ['label' => 'Title and author', 'code' => "return expect_same(shelfLabel('Dune', 'Frank Herbert'), 'Dune — Frank Herbert');"],
                ['label' => 'The author defaults to Unknown', 'code' => "return expect_same(shelfLabel('Beowulf'), 'Beowulf — Unknown');"],
                ['label' => 'Named arguments work, in capitals', 'code' => "return expect_same(shelfLabel(upper: true, title: 'Emma'), 'EMMA — UNKNOWN');"],
            ],
        ],
        [
            'title' => 'The longest word',
            'prompt' => 'Write a variadic function `longest(string ...$words): string` that returns the longest of its arguments (the first one, if several are equally long), or an empty string when there are none.',
            'starter_code' => <<<'PHP'
                <?php

                function longest(array $words): string
                {
                    return $words[0];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function longest(string ...$words): string
                {
                    $best = '';
                    foreach ($words as $word) {
                        if (strlen($word) > strlen($best)) {
                            $best = $word;
                        }
                    }

                    return $best;
                }
                PHP,
            'hint' => 'Declare the parameter as `string ...$words`. Start with `$best = \'\'` and replace it whenever a word is **strictly** longer.',
            'checks' => [
                ['label' => 'It finds the longest', 'code' => "return expect_same(longest('Dune', 'Beloved', 'Emma'), 'Beloved');"],
                ['label' => 'Ties go to the first', 'code' => "return expect_same(longest('Dune', 'Emma'), 'Dune');"],
                ['label' => 'No words gives an empty string', 'code' => "return expect_same(longest(), '');"],
            ],
        ],
        [
            'title' => 'Minimum and maximum together',
            'prompt' => "Write a function `stats(array \$numbers): array` returning `['min' => …, 'max' => …, 'total' => …]` for a non-empty list of numbers.",
            'starter_code' => <<<'PHP'
                <?php

                function stats(array $numbers): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function stats(array $numbers): array
                {
                    return ['min' => min($numbers), 'max' => max($numbers), 'total' => array_sum($numbers)];
                }
                PHP,
            'hint' => 'Return an associative array built with `min()`, `max()` and `array_sum()`.',
            'checks' => [
                ['label' => 'stats() of 4, 9 and 2', 'code' => "return expect_same(stats([4, 9, 2]), ['min' => 2, 'max' => 9, 'total' => 15]);"],
                ['label' => 'A single number', 'code' => "return expect_same(stats([7]), ['min' => 7, 'max' => 7, 'total' => 7]);"],
            ],
        ],
        [
            'title' => 'Apply a discount in place',
            'prompt' => "Write a function `discountAll(array &\$prices, int \$percent): void` that takes `\$percent` off every price **in the caller's array**, rounding each to 2 decimal places, and returns nothing.",
            'starter_code' => <<<'PHP'
                <?php

                function discountAll(array $prices, int $percent): void
                {
                    foreach ($prices as $i => $price) {
                        $prices[$i] = round($price * (100 - $percent) / 100, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function discountAll(array &$prices, int $percent): void
                {
                    foreach ($prices as $i => $price) {
                        $prices[$i] = round($price * (100 - $percent) / 100, 2);
                    }
                }
                PHP,
            'hint' => 'The loop is fine; the parameter needs an `&` so the function works on the caller\'s array.',
            'checks' => [
                ['label' => 'The caller\'s prices change', 'code' => <<<'PHP'
                    $prices = ['Dune' => 20.0, 'Emma' => 7.99];
                    discountAll($prices, 25);
                    return expect_same($prices, ['Dune' => 15.0, 'Emma' => 5.99]);
                    PHP],
            ],
        ],
        [
            'title' => 'Flatten a list',
            'prompt' => "Write a recursive function `flatten(array \$items): array` that returns every non-array value from `\$items` and from any arrays inside it, at any depth, as one flat list, in order.\n\n`flatten(['Dune', ['Emma', ['Circe']], 'Beloved'])` gives `['Dune', 'Emma', 'Circe', 'Beloved']`.",
            'starter_code' => <<<'PHP'
                <?php

                function flatten(array $items): array
                {
                    return $items;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function flatten(array $items): array
                {
                    $flat = [];
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            $flat = [...$flat, ...flatten($item)];
                        } else {
                            $flat[] = $item;
                        }
                    }

                    return $flat;
                }
                PHP,
            'hint' => 'Loop over the items. For an array, call `flatten()` on it and add all of its results; otherwise add the item itself.',
            'checks' => [
                ['label' => 'Nested lists are flattened in order', 'code' => "return expect_same(flatten(['Dune', ['Emma', ['Circe']], 'Beloved']), ['Dune', 'Emma', 'Circe', 'Beloved']);"],
                ['label' => 'Deeply nested and empty lists', 'code' => 'return expect_same(flatten([[[[1]]], [], [2, [3, []]]]), [1, 2, 3]);'],
            ],
        ],
    ],
];
