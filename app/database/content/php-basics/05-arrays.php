<?php

// Lesson 5 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'arrays',
    'title' => 'Arrays',
    'summary' => 'Store lists and named values in arrays: indexes and keys, adding and removing, safe lookups, nested arrays, looping, copying, spreading and destructuring.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $book = ['title' => 'Dune', 'author' => 'Frank Herbert', 'price' => 19.99];
        $book['inStock'] = true;

        foreach ($book as $key => $value) {
            echo "$key: " . var_export($value, true) . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $titles = ['Dune', 'Emma', 'Circe'];
                echo $titles[1];
                PHP,
            'options' => ['`Dune`', '`Emma`', '`Circe`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Indexes start at 0, so `[1]` is the second item.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $stack = ['a', 'b', 'c'];
                $stack[] = 'd';
                array_shift($stack);
                echo implode('', $stack);
                PHP,
            'options' => ['`abcd`', '`bcd`', '`abc`', '`dabc`'],
            'correct_option' => 1,
            'explanation' => '`[]` adds `d` to the end, and `array_shift()` removes the first item, `a`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $prices = ['Dune' => 19.99];
                $prices['Dune'] = 9.99;
                $prices['Emma'] = 7.99;
                echo count($prices);
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`9.99`'],
            'correct_option' => 1,
            'explanation' => 'Keys are unique: assigning to `\'Dune\'` again replaces its value, so there are two keys.',
        ],
        [
            'question' => "What does `\$stock['Circe'] ?? 0` give when `\$stock` has no key `'Circe'`?",
            'options' => ['`null`', '`0`', 'A warning and `null`', "`'Circe'`"],
            'correct_option' => 1,
            'explanation' => '`??` uses the fallback when the left side is missing or `null`, without any warning.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $books = [
                    ['title' => 'Dune', 'tags' => ['classic', 'desert']],
                    ['title' => 'Emma', 'tags' => ['romance']],
                ];
                echo $books[0]['tags'][1];
                PHP,
            'options' => ['`classic`', '`desert`', '`Dune`', '`romance`'],
            'correct_option' => 1,
            'explanation' => 'The first book, its tags, the second tag.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $prices = [10, 20];
                foreach ($prices as $price) {
                    $price = $price * 2;
                }
                echo implode(',', $prices);
                PHP,
            'options' => ['`20,40`', '`10,20`', '`10,20,20,40`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`$price` is a copy of each item, so changing it leaves the array alone. Assign to `$prices[$key]` to change the array.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = [1, 2];
                $b = $a;
                $b[] = 3;
                echo count($a);
                PHP,
            'options' => ['`2`', '`3`', '`1`', '`5`'],
            'correct_option' => 0,
            'explanation' => 'Assigning an array copies it, so adding to `$b` leaves `$a` with its two items.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                [$first, , $third] = ['Dune', 'Emma', 'Circe'];
                echo "$first $third";
                PHP,
            'options' => ['`Dune Emma`', '`Dune Circe`', '`Emma Circe`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Destructuring by position; leaving a gap skips that item.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'First and last',
            'prompt' => 'Set `$first` to the first title in `$titles` and `$last` to the last one, without changing the array. It must work for a list of any length.',
            'starter_code' => <<<'PHP'
                <?php

                $titles = ['Dune', 'Emma', 'Circe', 'Beloved'];
                $first = $titles[0];
                $last = $titles[3];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $titles = ['Dune', 'Emma', 'Circe', 'Beloved'];
                $first = $titles[0];
                $last = $titles[count($titles) - 1];
                PHP,
            'hint' => 'The last index is `count($titles) - 1`.',
            'checks' => [
                ['label' => "\$first is 'Dune' and \$last is 'Beloved'", 'code' => "return expect_same([\$GLOBALS['first'] ?? null, \$GLOBALS['last'] ?? null], ['Dune', 'Beloved']);"],
                ['label' => 'It works for a shorter list', 'code' => <<<'PHP'
                    $variant = preg_replace('/\$titles = \[.*?\];/', "\$titles = ['A', 'B'];", learnp_code(), 1);
                    return expect_same(learnp_run($variant . "\necho \"\$first \$last\";"), 'A B');
                    PHP],
                ['label' => 'The array is unchanged', 'code' => "return expect_same(count(\$GLOBALS['titles']), 4);"],
            ],
        ],
        [
            'title' => 'Describe a book',
            'prompt' => "`\$book` is an associative array. Add a key `'pages'` with the value 412, change the price to 9.99, and set `\$line` to `'Dune by Frank Herbert, 412 pages, £9.99'`, built from the array.",
            'starter_code' => <<<'PHP'
                <?php

                $book = ['title' => 'Dune', 'author' => 'Frank Herbert', 'price' => 19.99];
                $line = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $book = ['title' => 'Dune', 'author' => 'Frank Herbert', 'price' => 19.99];
                $book['pages'] = 412;
                $book['price'] = 9.99;

                $line = "{$book['title']} by {$book['author']}, {$book['pages']} pages, £{$book['price']}";
                PHP,
            'hint' => 'Assign `$book[\'pages\'] = 412;` and `$book[\'price\'] = 9.99;`, then build the line with `{$book[\'title\']}` and friends inside double quotes.',
            'checks' => [
                ['label' => 'The array has the new pages and price', 'code' => "return expect_same(\$GLOBALS['book'] ?? null, ['title' => 'Dune', 'author' => 'Frank Herbert', 'price' => 9.99, 'pages' => 412]);"],
                ['label' => '$line describes the book', 'code' => "return expect_same(\$GLOBALS['line'] ?? null, 'Dune by Frank Herbert, 412 pages, £9.99');"],
            ],
        ],
        [
            'title' => 'Stock lookup',
            'prompt' => "Set `\$report` to one line per title in `\$wanted`, joined by `\\n`: `Dune: 3` when it's in `\$stock`, or `Circe: not stocked` when it isn't.",
            'starter_code' => <<<'PHP'
                <?php

                $stock = ['Dune' => 3, 'Emma' => 0, 'Beloved' => 5];
                $wanted = ['Dune', 'Circe', 'Emma'];
                $report = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $stock = ['Dune' => 3, 'Emma' => 0, 'Beloved' => 5];
                $wanted = ['Dune', 'Circe', 'Emma'];

                $lines = [];
                foreach ($wanted as $title) {
                    $lines[] = "$title: " . ($stock[$title] ?? 'not stocked');
                }
                $report = implode("\n", $lines);
                PHP,
            'hint' => 'Loop over `$wanted`, add `"$title: " . ($stock[$title] ?? \'not stocked\')` to an array, then `implode("\n", …)`.',
            'checks' => [
                ['label' => 'The report is right', 'code' => "return expect_same(\$GLOBALS['report'] ?? null, \"Dune: 3\\nCirce: not stocked\\nEmma: 0\");"],
            ],
        ],
        [
            'title' => 'The cheapest book',
            'prompt' => '`$books` is a list of books, each with a `title` and a `price`. Set `$cheapest` to the **title** of the cheapest one, using a loop.',
            'starter_code' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Dune', 'price' => 19.99],
                    ['title' => 'Emma', 'price' => 7.99],
                    ['title' => 'Circe', 'price' => 13.99],
                ];
                $cheapest = $books[0]['title'];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Dune', 'price' => 19.99],
                    ['title' => 'Emma', 'price' => 7.99],
                    ['title' => 'Circe', 'price' => 13.99],
                ];

                $best = $books[0];
                foreach ($books as $book) {
                    if ($book['price'] < $best['price']) {
                        $best = $book;
                    }
                }
                $cheapest = $best['title'];
                PHP,
            'hint' => 'Keep the cheapest book seen so far, starting with the first. In the loop, replace it whenever a book is cheaper.',
            'checks' => [
                ['label' => "\$cheapest is 'Emma'", 'code' => "return expect_same(\$GLOBALS['cheapest'] ?? null, 'Emma');"],
                ['label' => 'It works when the cheapest is last', 'code' => <<<'PHP'
                    $variant = str_replace("['title' => 'Circe', 'price' => 13.99]", "['title' => 'Circe', 'price' => 1.99]", learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$cheapest;"), 'Circe');
                    PHP],
            ],
        ],
        [
            'title' => 'Group by genre',
            'prompt' => "Set `\$byGenre` to an associative array with one key per genre, each holding the list of titles in that genre, in order: `['Fantasy' => ['The Hobbit', 'Circe'], 'Horror' => ['Dracula']]`.",
            'starter_code' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'The Hobbit', 'genre' => 'Fantasy'],
                    ['title' => 'Dracula', 'genre' => 'Horror'],
                    ['title' => 'Circe', 'genre' => 'Fantasy'],
                ];
                $byGenre = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'The Hobbit', 'genre' => 'Fantasy'],
                    ['title' => 'Dracula', 'genre' => 'Horror'],
                    ['title' => 'Circe', 'genre' => 'Fantasy'],
                ];

                $byGenre = [];
                foreach ($books as $book) {
                    $byGenre[$book['genre']][] = $book['title'];
                }
                PHP,
            'hint' => '`$byGenre[$book[\'genre\']][] = $book[\'title\'];` creates the genre\'s list the first time and adds to it after that.',
            'checks' => [
                ['label' => 'Titles are grouped by genre', 'code' => "return expect_same(\$GLOBALS['byGenre'] ?? null, ['Fantasy' => ['The Hobbit', 'Circe'], 'Horror' => ['Dracula']]);"],
            ],
        ],
        [
            'title' => 'Merge two shelves',
            'prompt' => 'Set `$all` to every title from `$shelfA` followed by every title from `$shelfB`, using the spread operator, and then destructure `$all` so that `$firstTitle` and `$secondTitle` hold its first two titles.',
            'starter_code' => <<<'PHP'
                <?php

                $shelfA = ['Dune', 'Emma'];
                $shelfB = ['Circe', 'Beloved'];
                $all = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $shelfA = ['Dune', 'Emma'];
                $shelfB = ['Circe', 'Beloved'];
                $all = [...$shelfA, ...$shelfB];

                [$firstTitle, $secondTitle] = $all;
                PHP,
            'hint' => '`[...$shelfA, ...$shelfB]` joins them; `[$firstTitle, $secondTitle] = $all;` unpacks the first two.',
            'checks' => [
                ['label' => '$all has every title, in order', 'code' => "return expect_same(\$GLOBALS['all'] ?? null, ['Dune', 'Emma', 'Circe', 'Beloved']);"],
                ['label' => 'The first two titles are unpacked', 'code' => "return expect_same([\$GLOBALS['firstTitle'] ?? null, \$GLOBALS['secondTitle'] ?? null], ['Dune', 'Emma']);"],
                ['label' => 'It uses ... and destructuring', 'code' => "return (str_contains(learnp_code(), '...') && preg_match('/\\[\\s*\\\$firstTitle/', learnp_code())) ? true : 'Use the spread operator ... and [\$firstTitle, \$secondTitle] = \$all;';"],
            ],
        ],
    ],
];
