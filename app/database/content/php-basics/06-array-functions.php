<?php

// Lesson 6 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'array-functions',
    'title' => 'Array functions',
    'summary' => 'Replace loops with PHP\'s array functions: search, map, filter, sum, sort with usort, slice, combine and more.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $prices = ['Dune' => 19.99, 'Emma' => 7.99, 'Circe' => 13.99];

        $cheap = array_filter($prices, fn ($price) => $price < 15);
        asort($cheap);
        print_r($cheap);
        echo 'Total: ' . array_sum($prices);
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => 'print_r(array_map(fn ($n) => $n * 10, [1, 2, 3]));',
            'options' => [
                '`Array ( [0] => 10 [1] => 20 [2] => 30 )`',
                '`Array ( [0] => 1 [1] => 2 [2] => 3 )`',
                '`60`',
                '`Array ( [0] => 10 )`',
            ],
            'correct_option' => 0,
            'explanation' => '`array_map()` returns a new array with the function applied to every item.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'print_r(array_filter([5, 12, 8, 20], fn ($n) => $n > 10));',
            'options' => [
                '`Array ( [0] => 12 [1] => 20 )`',
                '`Array ( [1] => 12 [3] => 20 )`',
                '`Array ( [0] => 5 [2] => 8 )`',
                '`2`',
            ],
            'correct_option' => 1,
            'explanation' => '`array_filter()` keeps the original keys. Wrap it in `array_values()` to renumber them.',
        ],
        [
            'question' => 'Which function adds up all the values in an array?',
            'options' => ['`count()`', '`array_sum()`', '`max()`', '`array_merge()`'],
            'correct_option' => 1,
            'explanation' => '`count()` counts items; `array_sum()` adds their values.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $books = [['title' => 'Dune', 'year' => 1965], ['title' => 'Emma', 'year' => 1815]];
                print_r(array_column($books, 'year', 'title'));
                PHP,
            'options' => [
                '`Array ( [0] => 1965 [1] => 1815 )`',
                '`Array ( [Dune] => 1965 [Emma] => 1815 )`',
                '`Array ( [1965] => Dune [1815] => Emma )`',
                '`Array ( [Dune] => Emma )`',
            ],
            'correct_option' => 1,
            'explanation' => 'The second argument picks the values, the third the keys.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $prices = ['Dune' => 19.99, 'Emma' => 7.99];
                sort($prices);
                print_r($prices);
                PHP,
            'options' => [
                '`Array ( [Emma] => 7.99 [Dune] => 19.99 )`',
                '`Array ( [0] => 7.99 [1] => 19.99 )`',
                '`Array ( [Dune] => 19.99 [Emma] => 7.99 )`',
                '`Array ( [0] => Dune [1] => Emma )`',
            ],
            'correct_option' => 1,
            'explanation' => '`sort()` sorts the values and throws the keys away. `asort()` would keep each title with its price.',
        ],
        [
            'question' => 'In `usort($books, fn ($a, $b) => $b[\'price\'] <=> $a[\'price\'])`, which way are the books sorted?',
            'options' => [
                'Cheapest first',
                'Most expensive first',
                'By title',
                'They aren\'t; `usort()` needs a named function',
            ],
            'correct_option' => 1,
            'explanation' => 'With `$b` on the left, the comparison is reversed, so larger prices come first.',
        ],
        [
            'question' => 'What does `array_slice([\'a\', \'b\', \'c\', \'d\'], 1, 2)` return?',
            'options' => ["`['a', 'b']`", "`['b', 'c']`", "`['b', 'c', 'd']`", "`['c', 'd']`"],
            'correct_option' => 1,
            'explanation' => 'Start at index 1 and take 2 items.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'echo implode(\',\', array_unique([3, 1, 3, 2, 1]));',
            'options' => ['`3,1,2`', '`1,2,3`', '`3,1,3,2,1`', '`3`'],
            'correct_option' => 0,
            'explanation' => '`array_unique()` keeps the first of each value, in their original order.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Prices with tax',
            'prompt' => 'Using `array_map()`, set `$withTax` to every price in `$prices` with 20% added, rounded to 2 decimal places.',
            'starter_code' => <<<'PHP'
                <?php

                $prices = [10.0, 7.99, 13.5];
                $withTax = $prices;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $prices = [10.0, 7.99, 13.5];
                $withTax = array_map(fn ($price) => round($price * 1.2, 2), $prices);
                PHP,
            'hint' => '`array_map(fn ($price) => round($price * 1.2, 2), $prices)`.',
            'checks' => [
                ['label' => '$withTax has the taxed prices', 'code' => "return expect_same(\$GLOBALS['withTax'] ?? null, [12.0, 9.59, 16.2]);"],
                ['label' => 'It uses array_map()', 'code' => "return str_contains(learnp_code(), 'array_map(') ?: 'Use array_map().';"],
            ],
        ],
        [
            'title' => 'In stock only',
            'prompt' => "Set `\$available` to the titles of the books in `\$stock` that have at least one copy, as a plain list numbered from 0: `['Dune', 'Circe']`.",
            'starter_code' => <<<'PHP'
                <?php

                $stock = ['Dune' => 3, 'Emma' => 0, 'Circe' => 5, 'Beloved' => 0];
                $available = array_keys($stock);
                PHP,
            'solution' => <<<'PHP'
                <?php

                $stock = ['Dune' => 3, 'Emma' => 0, 'Circe' => 5, 'Beloved' => 0];
                $available = array_keys(array_filter($stock, fn ($copies) => $copies > 0));
                PHP,
            'hint' => 'Filter the stock to the entries with more than 0 copies, then take `array_keys()` of the result.',
            'checks' => [
                ['label' => "\$available is ['Dune', 'Circe']", 'code' => "return expect_same(\$GLOBALS['available'] ?? null, ['Dune', 'Circe']);"],
            ],
        ],
        [
            'title' => 'Order total',
            'prompt' => "Each line in `\$lines` has a `price` and a `quantity`. Set `\$total` to the order's total (price × quantity for each line, added up), rounded to 2 decimal places, without writing a loop.",
            'starter_code' => <<<'PHP'
                <?php

                $lines = [
                    ['title' => 'Dune', 'price' => 19.99, 'quantity' => 1],
                    ['title' => 'Emma', 'price' => 7.99, 'quantity' => 3],
                    ['title' => 'Circe', 'price' => 13.99, 'quantity' => 2],
                ];
                $total = 0;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $lines = [
                    ['title' => 'Dune', 'price' => 19.99, 'quantity' => 1],
                    ['title' => 'Emma', 'price' => 7.99, 'quantity' => 3],
                    ['title' => 'Circe', 'price' => 13.99, 'quantity' => 2],
                ];
                $total = round(array_sum(array_map(fn ($line) => $line['price'] * $line['quantity'], $lines)), 2);
                PHP,
            'hint' => '`array_map()` each line to `price * quantity`, then `array_sum()`, then `round()`.',
            'checks' => [
                ['label' => '$total is 71.94', 'code' => "return expect_same(\$GLOBALS['total'] ?? null, 71.94);"],
                ['label' => 'No loop', 'code' => "return ! preg_match('/\\b(foreach|for|while)\\s*\\(/', learnp_code()) ?: 'Use array functions instead of a loop.';"],
            ],
        ],
        [
            'title' => 'Longest books first',
            'prompt' => 'Sort `$books` with `usort()` so the books with the most pages come first, and books with the same number of pages are in title order. Then set `$order` to the list of titles.',
            'starter_code' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Emma', 'pages' => 474],
                    ['title' => 'Circe', 'pages' => 393],
                    ['title' => 'Dune', 'pages' => 412],
                    ['title' => 'Beloved', 'pages' => 412],
                ];
                $order = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Emma', 'pages' => 474],
                    ['title' => 'Circe', 'pages' => 393],
                    ['title' => 'Dune', 'pages' => 412],
                    ['title' => 'Beloved', 'pages' => 412],
                ];

                usort($books, fn ($a, $b) => [$b['pages'], $a['title']] <=> [$a['pages'], $b['title']]);
                $order = array_column($books, 'title');
                PHP,
            'hint' => 'Compare pages with `$b` first (most first), and when they\'re equal compare titles with `$a` first. `array_column($books, \'title\')` gives the titles.',
            'checks' => [
                ['label' => '$order is right', 'code' => "return expect_same(\$GLOBALS['order'] ?? null, ['Emma', 'Beloved', 'Dune', 'Circe']);"],
                ['label' => 'It uses usort()', 'code' => "return str_contains(learnp_code(), 'usort(') ?: 'Sort with usort().';"],
            ],
        ],
        [
            'title' => 'Top three titles',
            'prompt' => "`\$sales` maps titles to copies sold. Set `\$top` to the titles of the three best sellers, best first: `['Dune', 'Circe', 'Emma']`.",
            'starter_code' => <<<'PHP'
                <?php

                $sales = ['Emma' => 12, 'Dune' => 40, 'Beloved' => 3, 'Circe' => 25, 'Dracula' => 9];
                $top = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $sales = ['Emma' => 12, 'Dune' => 40, 'Beloved' => 3, 'Circe' => 25, 'Dracula' => 9];

                arsort($sales);
                $top = array_slice(array_keys($sales), 0, 3);
                PHP,
            'hint' => '`arsort()` sorts by value, largest first, keeping the titles as keys. Then take the first three `array_keys()` with `array_slice()`.',
            'checks' => [
                ['label' => "\$top is ['Dune', 'Circe', 'Emma']", 'code' => "return expect_same(\$GLOBALS['top'] ?? null, ['Dune', 'Circe', 'Emma']);"],
            ],
        ],
        [
            'title' => 'Words in a title',
            'prompt' => "Set `\$words` to the distinct words of `\$title`, in lower case and sorted alphabetically. For `'The Lord of the Rings'` that's `['lord', 'of', 'rings', 'the']`.",
            'starter_code' => <<<'PHP'
                <?php

                $title = 'The Lord of the Rings';
                $words = explode(' ', $title);
                PHP,
            'solution' => <<<'PHP'
                <?php

                $title = 'The Lord of the Rings';

                $words = array_values(array_unique(explode(' ', strtolower($title))));
                sort($words);
                PHP,
            'hint' => '`strtolower()`, then `explode(\' \', …)`, then `array_unique()`, then `sort()`.',
            'checks' => [
                ['label' => '$words is right', 'code' => "return expect_same(\$GLOBALS['words'] ?? null, ['lord', 'of', 'rings', 'the']);"],
                ['label' => 'It works for any title', 'code' => <<<'PHP'
                    $variant = str_replace("'The Lord of the Rings'", "'Emma by Emma'", learnp_code());
                    return expect_same(learnp_run($variant . "\necho implode(',', \$words);"), 'by,emma');
                    PHP],
            ],
        ],
    ],
];
