<?php

// Lesson 2 of the Functional PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'higher-order-functions',
    'title' => 'Higher-order functions and reduce',
    'summary' => 'Functions that take and return functions: map, filter and reduce, folding a list into any shape with array_reduce, and building and combining predicates.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $prices = [7.99, 19.99, 13.99];

        $total = array_reduce($prices, fn (float $sum, float $price) => $sum + $price, 0.0);
        $most = array_reduce($prices, fn (float $best, float $price) => max($best, $price), 0.0);

        echo "Total $total, most expensive $most";
        PHP,

    'quiz' => [
        [
            'question' => 'What makes a function "higher-order"?',
            'options' => [
                'It has more than three parameters',
                'It takes a function as an argument or returns one',
                'It\'s declared at the top of a file',
                'It uses recursion',
            ],
            'correct_option' => 1,
            'explanation' => '`array_map()` is higher-order because you give it a function to apply.',
        ],
        [
            'question' => 'Which of map, filter and reduce can turn a list into a single number?',
            'options' => ['Map', 'Filter', 'Reduce', 'None of them'],
            'correct_option' => 2,
            'explanation' => 'Map keeps the count, filter keeps (some of) the items; reduce combines them into one value.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'echo array_reduce([2, 3, 4], fn ($carry, $n) => $carry * $n, 1);',
            'options' => ['`9`', '`24`', '`0`', '`10`'],
            'correct_option' => 1,
            'explanation' => 'Starting from 1: 1 × 2 = 2, 2 × 3 = 6, 6 × 4 = 24.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'var_dump(array_reduce([], fn ($carry, $n) => $carry + $n));',
            'options' => ['`int(0)`', '`NULL`', '`bool(false)`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Without an initial value the carry starts as `null`, and an empty list returns it unchanged. Always pass the initial value.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $result = array_reduce(['a', 'b', 'c'], fn (string $carry, string $letter) => $letter . $carry, '');
                echo $result;
                PHP,
            'options' => ['`abc`', '`cba`', '`a`', '`c`'],
            'correct_option' => 1,
            'explanation' => 'Each letter is put in front of the carry so far, which reverses the order.',
        ],
        [
            'question' => 'What does `priceBelow(10)` return, if `priceBelow()` is written as `fn ($book) => $book[\'price\'] < $limit` inside a function?',
            'options' => [
                'A list of cheap books',
                'A closure that tests whether one book is cheaper than 10',
                '`true` or `false`',
                'The number 10',
            ],
            'correct_option' => 1,
            'explanation' => 'It builds a predicate you can pass to `array_filter()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $isLong = fn (string $t) => strlen($t) > 4;
                $startsWithD = fn (string $t) => $t[0] === 'D';
                $both = fn (string $t) => $isLong($t) && $startsWithD($t);

                echo implode(',', array_filter(['Dune', 'Dracula', 'Emma', 'Daisy Jones'], $both));
                PHP,
            'options' => ['`Dune,Dracula,Daisy Jones`', '`Dracula,Daisy Jones`', '`Dracula`', '`Emma`'],
            'correct_option' => 1,
            'explanation' => '"Dune" is too short and "Emma" doesn\'t start with D.',
        ],
        [
            'question' => 'How do you make `array_filter()` pass both the key and the value to your function?',
            'options' => [
                'It always does',
                'Pass `ARRAY_FILTER_USE_BOTH` as the third argument',
                'Use `array_map()` instead',
                'It can\'t',
            ],
            'correct_option' => 1,
            'explanation' => '`ARRAY_FILTER_USE_KEY` passes just the key; `ARRAY_FILTER_USE_BOTH` passes the value and the key.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Total with reduce',
            'prompt' => "Write `orderTotal(array \$lines): float` returning the total of every line's `price × quantity`, rounded to 2 places, using `array_reduce()`.",
            'starter_code' => <<<'PHP'
                <?php

                function orderTotal(array $lines): float
                {
                    return 0.0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderTotal(array $lines): float
                {
                    $total = array_reduce($lines, fn (float $sum, array $line) => $sum + $line['price'] * $line['quantity'], 0.0);

                    return round($total, 2);
                }
                PHP,
            'hint' => 'Start the carry at `0.0` and add `$line[\'price\'] * $line[\'quantity\']` each time.',
            'checks' => [
                ['label' => 'Totals', 'code' => "return expect_same([orderTotal([['price' => 19.99, 'quantity' => 1], ['price' => 7.99, 'quantity' => 3]]), orderTotal([])], [43.96, 0.0]);"],
                ['label' => 'It uses array_reduce()', 'code' => "return str_contains(learnp_code(), 'array_reduce(') ?: 'Use array_reduce().';"],
            ],
        ],
        [
            'title' => 'Map and filter, by hand',
            'prompt' => 'Write your own `myMap(Closure $f, array $items): array` and `myFilter(Closure $keep, array $items): array` (returning a list numbered from 0), each in a single expression using `array_reduce()`, without loops or `array_map`/`array_filter`.',
            'starter_code' => <<<'PHP'
                <?php

                function myMap(Closure $f, array $items): array
                {
                    return $items;
                }

                function myFilter(Closure $keep, array $items): array
                {
                    return $items;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function myMap(Closure $f, array $items): array
                {
                    return array_reduce($items, fn (array $carry, mixed $item) => [...$carry, $f($item)], []);
                }

                function myFilter(Closure $keep, array $items): array
                {
                    return array_reduce($items, fn (array $carry, mixed $item) => $keep($item) ? [...$carry, $item] : $carry, []);
                }
                PHP,
            'hint' => 'For map, each step adds `$f($item)` to the carry. For filter, each step adds the item only when `$keep($item)` is true.',
            'checks' => [
                ['label' => 'myMap()', 'code' => "return expect_same([myMap(fn (\$n) => \$n * 10, [1, 2, 3]), myMap(strtoupper(...), ['a'])], [[10, 20, 30], ['A']]);"],
                ['label' => 'myFilter()', 'code' => 'return expect_same([myFilter(fn ($n) => $n > 2, [1, 3, 2, 5]), myFilter(fn ($n) => false, [1])], [[3, 5], []]);'],
                ['label' => 'No loops or built-in map/filter', 'code' => "return ! preg_match('/\\\\b(for|foreach|while|array_map|array_filter)\\\\b/', learnp_code()) ?: 'Use array_reduce() only.';"],
            ],
        ],
        [
            'title' => 'Index by id',
            'prompt' => 'Write `indexBy(array $rows, string $key): array` returning an associative array where each row is stored under the value of its `$key` field, using `array_reduce()`. Later rows with the same key replace earlier ones.',
            'starter_code' => <<<'PHP'
                <?php

                function indexBy(array $rows, string $key): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function indexBy(array $rows, string $key): array
                {
                    return array_reduce($rows, function (array $index, array $row) use ($key): array {
                        $index[$row[$key]] = $row;

                        return $index;
                    }, []);
                }
                PHP,
            'hint' => 'The carry is the index so far; each step stores the row under `$row[$key]` and returns the index.',
            'checks' => [
                ['label' => 'Rows indexed by id', 'code' => "\$rows = [['id' => 7, 'title' => 'Dune'], ['id' => 3, 'title' => 'Emma']];\nreturn expect_same(indexBy(\$rows, 'id'), [7 => ['id' => 7, 'title' => 'Dune'], 3 => ['id' => 3, 'title' => 'Emma']]);"],
                ['label' => 'Later rows win, and any key works', 'code' => "\$rows = [['code' => 'a', 'v' => 1], ['code' => 'a', 'v' => 2]];\nreturn expect_same(indexBy(\$rows, 'code'), ['a' => ['code' => 'a', 'v' => 2]]);"],
            ],
        ],
        [
            'title' => 'Predicate factories',
            'prompt' => 'Write three functions that each **return a predicate** (a closure taking a book array and returning a bool): `cheaperThan(float $limit)`, `inGenre(string $genre)`, and `allOf(Closure ...$predicates)`, which is true only when every given predicate is true.',
            'starter_code' => <<<'PHP'
                <?php

                function cheaperThan(float $limit): Closure
                {
                    return fn (array $book) => true;
                }

                function inGenre(string $genre): Closure
                {
                    return fn (array $book) => true;
                }

                function allOf(Closure ...$predicates): Closure
                {
                    return fn (array $book) => true;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cheaperThan(float $limit): Closure
                {
                    return fn (array $book) => $book['price'] < $limit;
                }

                function inGenre(string $genre): Closure
                {
                    return fn (array $book) => $book['genre'] === $genre;
                }

                function allOf(Closure ...$predicates): Closure
                {
                    return function (array $book) use ($predicates): bool {
                        foreach ($predicates as $predicate) {
                            if (! $predicate($book)) {
                                return false;
                            }
                        }

                        return true;
                    };
                }
                PHP,
            'hint' => 'The first two capture their argument in an arrow function. `allOf()` returns a closure that checks each predicate in turn.',
            'checks' => [
                ['label' => 'Single predicates', 'code' => "\$dune = ['price' => 19.99, 'genre' => 'Science Fiction'];\nreturn expect_same([cheaperThan(20)(\$dune), cheaperThan(10)(\$dune), inGenre('Science Fiction')(\$dune), inGenre('Horror')(\$dune)], [true, false, true, false]);"],
                ['label' => 'allOf() combines them', 'code' => <<<'PHP'
                    $books = [
                        ['title' => 'Dune', 'price' => 19.99, 'genre' => 'Science Fiction'],
                        ['title' => 'Neuromancer', 'price' => 14.00, 'genre' => 'Science Fiction'],
                        ['title' => 'Emma', 'price' => 7.99, 'genre' => 'Romance'],
                    ];
                    $cheapSciFi = array_values(array_filter($books, allOf(cheaperThan(15), inGenre('Science Fiction'))));
                    return expect_same([array_column($cheapSciFi, 'title'), allOf()(['price' => 1, 'genre' => 'x'])], [['Neuromancer'], true]);
                    PHP],
            ],
        ],
        [
            'title' => 'Apply n times',
            'prompt' => 'Write a higher-order function `times(int $n, Closure $f): Closure` returning a closure that applies `$f` to its argument `$n` times in a row. `times(3, fn ($x) => $x * 2)(1)` is 8, and `times(0, …)` returns its argument unchanged.',
            'starter_code' => <<<'PHP'
                <?php

                function times(int $n, Closure $f): Closure
                {
                    return $f;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function times(int $n, Closure $f): Closure
                {
                    return function (mixed $x) use ($n, $f): mixed {
                        for ($i = 0; $i < $n; $i++) {
                            $x = $f($x);
                        }

                        return $x;
                    };
                }
                PHP,
            'hint' => 'Return a closure that loops `$n` times, replacing its value with `$f($value)` each time.',
            'checks' => [
                ['label' => 'Repeated application', 'code' => "return expect_same([times(3, fn (\$x) => \$x * 2)(1), times(2, fn (\$s) => \$s . '!')('Hi')], [8, 'Hi!!']);"],
                ['label' => 'Zero times', 'code' => 'return expect_same(times(0, fn ($x) => $x + 1)(5), 5);'],
            ],
        ],
        [
            'title' => 'Counts per genre',
            'prompt' => 'Write `genreReport(array $books): string` returning a line per genre, alphabetically, like `Fantasy: 2`, joined by `\\n`, with no loops: use `array_reduce()` to count and `array_map()` to build the lines.',
            'starter_code' => <<<'PHP'
                <?php

                function genreReport(array $books): string
                {
                    return '';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function genreReport(array $books): string
                {
                    $counts = array_reduce(
                        $books,
                        fn (array $counts, array $book) => [...$counts, $book['genre'] => ($counts[$book['genre']] ?? 0) + 1],
                        [],
                    );
                    ksort($counts);

                    return implode("\n", array_map(fn (string $genre, int $count) => "$genre: $count", array_keys($counts), $counts));
                }
                PHP,
            'hint' => 'Reduce to a genre => count map, `ksort()` it, then `array_map()` over the keys and values together (`array_map($f, array_keys($counts), $counts)`).',
            'checks' => [
                ['label' => 'The report', 'code' => "\$books = [['genre' => 'Horror'], ['genre' => 'Fantasy'], ['genre' => 'Horror'], ['genre' => 'Fantasy'], ['genre' => 'Romance']];\nreturn expect_same(genreReport(\$books), \"Fantasy: 2\\nHorror: 2\\nRomance: 1\");"],
                ['label' => 'No loops', 'code' => "return ! preg_match('/\\\\b(for|foreach|while)\\\\b/', learnp_code()) ?: 'Use array_reduce() and array_map() instead of loops.';"],
            ],
        ],
    ],
];
