<?php

// Lesson 1 of the Functional PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'pure-functions',
    'title' => 'Pure functions and immutability',
    'summary' => 'Write functions whose results depend only on their arguments and that change nothing, work with immutable data, and keep side effects at the edges.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function addItem(array $items, string $title): array
        {
            return [...$items, $title];
        }

        $before = ['Dune'];
        $after = addItem($before, 'Emma');

        echo count($before) . ' item before, ' . count($after) . ' after';
        PHP,

    'quiz' => [
        [
            'question' => 'Which two things make a function pure?',
            'options' => [
                'It\'s short and has a return type',
                'Its result depends only on its arguments, and it has no side effects',
                'It uses only arrow functions',
                'It doesn\'t use classes',
            ],
            'correct_option' => 1,
            'explanation' => 'Same input, same output, and nothing outside it changes.',
        ],
        [
            'question' => 'Which of these functions is pure?',
            'options' => [
                '`fn (int $n) => $n * 2`',
                '`fn () => time()`',
                '`function (int $n) { echo $n; return $n; }`',
                '`function () { global $count; return ++$count; }`',
            ],
            'correct_option' => 0,
            'explanation' => 'The others depend on the clock, print, or change a global.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function addTag(array $tags, string $tag): array
                {
                    $tags[] = $tag;
                    return $tags;
                }

                $tags = ['fantasy'];
                $new = addTag($tags, 'classic');
                echo count($tags) . count($new);
                PHP,
            'options' => ['`12`', '`22`', '`11`', '`21`'],
            'correct_option' => 0,
            'explanation' => 'Arrays are copied when passed in, so the function changed only its own copy.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Counter { public int $n = 0; }

                function bump(Counter $c): int
                {
                    $c->n++;
                    return $c->n;
                }

                $counter = new Counter();
                bump($counter);
                bump($counter);
                echo $counter->n;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error'],
            'correct_option' => 2,
            'explanation' => 'Objects are passed as handles, so `bump()` changes the caller\'s counter: a side effect.',
        ],
        [
            'question' => 'Why are pure functions easy to test?',
            'options' => [
                'They\'re always short',
                'You only need to supply inputs and check the output; nothing needs setting up or faking',
                'PHPUnit has special support for them',
                'They can\'t fail',
            ],
            'correct_option' => 1,
            'explanation' => 'There\'s no hidden state, so a test is just "this input gives that output".',
        ],
        [
            'question' => 'What does "immutable" mean for a value?',
            'options' => [
                'It can\'t be read',
                'It never changes after it\'s created; a change means making a new value',
                'It can only be a number',
                'It\'s stored in a constant',
            ],
            'correct_option' => 1,
            'explanation' => 'Readonly classes with `with…()` methods are immutable objects in PHP.',
        ],
        [
            'question' => 'What does "keep side effects at the edges" mean?',
            'options' => [
                'Never print anything',
                'Put the logic in pure functions, and do the printing, saving or sending in a thin outer layer',
                'Put side effects at the end of each function',
                'Use global variables for side effects',
            ],
            'correct_option' => 1,
            'explanation' => 'The pure core can be tested easily; the shell that acts on it stays small.',
        ],
        [
            'question' => 'Why can a pure function\'s results be safely cached?',
            'options' => [
                'Because it\'s fast',
                'Because the same arguments always give the same result',
                'Because it has no parameters',
                'They can\'t be cached',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s exactly what memoization relies on.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Make it pure',
            'prompt' => '`priceWithVat()` reads the VAT rate from a global, so its result can change behind your back. Rewrite it as a pure function `priceWithVat(float $price, float $rate): float`, rounding to 2 places, with no `global` and no other outside values.',
            'starter_code' => <<<'PHP'
                <?php

                $vatRate = 0.2;

                function priceWithVat(float $price): float
                {
                    global $vatRate;

                    return round($price * (1 + $vatRate), 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function priceWithVat(float $price, float $rate): float
                {
                    return round($price * (1 + $rate), 2);
                }
                PHP,
            'hint' => 'Pass the rate in as a parameter instead of reading it from outside.',
            'checks' => [
                ['label' => 'Prices with VAT', 'code' => 'return expect_same([priceWithVat(10.0, 0.2), priceWithVat(19.99, 0.05)], [12.0, 20.99]);'],
                ['label' => 'No global', 'code' => "return ! preg_match('/\\\\bglobal\\\\b|\\\\\$GLOBALS/', learnp_code()) ?: 'Remove the global; take the rate as a parameter.';"],
            ],
        ],
        [
            'title' => 'Don\'t change the caller\'s order',
            'prompt' => "`addLine()` changes the order object it's given. Rewrite the code so `Order` is immutable: a `readonly` class with a `withLine(string \$title, float \$price): Order` method that returns a **new** order with the extra line, and a `total(): float` method. The original order never changes.",
            'starter_code' => <<<'PHP'
                <?php

                class Order
                {
                    public array $lines = [];
                }

                function addLine(Order $order, string $title, float $price): Order
                {
                    $order->lines[] = ['title' => $title, 'price' => $price];

                    return $order;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                readonly class Order
                {
                    public function __construct(public array $lines = []) {}

                    public function withLine(string $title, float $price): Order
                    {
                        return new Order([...$this->lines, ['title' => $title, 'price' => $price]]);
                    }

                    public function total(): float
                    {
                        return round(array_sum(array_column($this->lines, 'price')), 2);
                    }
                }
                PHP,
            'hint' => 'Make `Order` a `readonly class` with its lines in the constructor. `withLine()` returns `new Order([...$this->lines, $newLine])`.',
            'checks' => [
                ['label' => 'withLine() returns a new order and leaves the old one alone', 'code' => <<<'PHP'
                    $empty = new Order();
                    $one = $empty->withLine('Dune', 19.99);
                    $two = $one->withLine('Emma', 7.99);
                    return expect_same([count($empty->lines), count($one->lines), $two->total()], [0, 1, 27.98]);
                    PHP],
                ['label' => 'Order is readonly', 'code' => "return (new ReflectionClass('Order'))->isReadOnly() ?: 'Make Order a readonly class.';"],
            ],
        ],
        [
            'title' => 'Pure or not?',
            'prompt' => "Set `\$pure` to an array mapping each function name below to `true` if it's pure or `false` if it isn't:\n\n- `'double'`: `fn (int \$n) => \$n * 2`\n- `'now'`: `fn () => date('H:i')`\n- `'greet'`: `function (string \$name) { echo \"Hi \$name\"; }`\n- `'longest'`: `fn (array \$words) => max(array_map('strlen', \$words))`\n- `'push'`: `function (array &\$list, \$item) { \$list[] = \$item; }`\n- `'lookup'`: `fn (array \$prices, string \$title) => \$prices[\$title] ?? null`",
            'starter_code' => <<<'PHP'
                <?php

                $pure = [
                    'double' => null,
                    'now' => null,
                    'greet' => null,
                    'longest' => null,
                    'push' => null,
                    'lookup' => null,
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $pure = [
                    'double' => true,
                    'now' => false,
                    'greet' => false,
                    'longest' => true,
                    'push' => false,
                    'lookup' => true,
                ];
                PHP,
            'hint' => 'Ask of each: does it depend on anything but its arguments (like the clock)? Does it print, or change anything outside, including an argument passed by reference?',
            'checks' => [
                ['label' => 'double, now and greet', 'code' => "\$p = \$GLOBALS['pure'] ?? [];\nreturn expect_same([\$p['double'] ?? null, \$p['now'] ?? null, \$p['greet'] ?? null], [true, false, false]);"],
                ['label' => 'longest, push and lookup', 'code' => "\$p = \$GLOBALS['pure'] ?? [];\nreturn expect_same([\$p['longest'] ?? null, \$p['push'] ?? null, \$p['lookup'] ?? null], [true, false, true]);"],
            ],
        ],
        [
            'title' => 'A pure core',
            'prompt' => '`sendReminders()` both decides who to remind and prints the messages. Pull the decision out into a pure function `overdueMessages(array $loans, string $today): array` returning the messages (in order) for loans whose `due` date is before `$today`, in the form `"Amara: Dune is overdue"`. Keep the printing out of it.',
            'starter_code' => <<<'PHP'
                <?php

                function sendReminders(array $loans, string $today): void
                {
                    foreach ($loans as $loan) {
                        if ($loan['due'] < $today) {
                            echo "{$loan['name']}: {$loan['title']} is overdue\n";
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function overdueMessages(array $loans, string $today): array
                {
                    $overdue = array_filter($loans, fn (array $loan) => $loan['due'] < $today);

                    return array_values(array_map(fn (array $loan) => "{$loan['name']}: {$loan['title']} is overdue", $overdue));
                }

                function sendReminders(array $loans, string $today): void
                {
                    foreach (overdueMessages($loans, $today) as $message) {
                        echo $message . "\n";
                    }
                }
                PHP,
            'hint' => 'Filter the overdue loans and map each to its message. `sendReminders()` can then just print what `overdueMessages()` returns.',
            'checks' => [
                ['label' => 'The right messages, in order', 'code' => "\$loans = [\n    ['name' => 'Amara', 'title' => 'Dune', 'due' => '2026-04-01'],\n    ['name' => 'Ben', 'title' => 'Emma', 'due' => '2026-05-01'],\n    ['name' => 'Cy', 'title' => 'Circe', 'due' => '2026-04-10'],\n];\nreturn expect_same(overdueMessages(\$loans, '2026-04-20'), ['Amara: Dune is overdue', 'Cy: Circe is overdue']);"],
                ['label' => 'It prints nothing', 'code' => "ob_start();\noverdueMessages([['name' => 'A', 'title' => 'B', 'due' => '2000-01-01']], '2026-01-01');\nreturn ob_get_clean() === '' ?: 'overdueMessages() should return messages, not print them.';"],
            ],
        ],
        [
            'title' => 'Sort without changing',
            'prompt' => "PHP's `sort()` changes the array it's given. Write a pure function `sortedCopy(array \$items): array` that returns a sorted copy and leaves the original exactly as it was, and `sortedBy(array \$rows, string \$key): array` that returns a copy of the rows sorted by that key.",
            'starter_code' => <<<'PHP'
                <?php

                function sortedCopy(array $items): array
                {
                    return $items;
                }

                function sortedBy(array $rows, string $key): array
                {
                    return $rows;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sortedCopy(array $items): array
                {
                    sort($items);

                    return $items;
                }

                function sortedBy(array $rows, string $key): array
                {
                    usort($rows, fn (array $a, array $b) => $a[$key] <=> $b[$key]);

                    return $rows;
                }
                PHP,
            'hint' => 'The parameter is already a copy, because arrays are passed by value, so sorting it inside the function is safe.',
            'checks' => [
                ['label' => 'A sorted copy, original unchanged', 'code' => "\$prices = [19.99, 7.99, 13.99];\n\$sorted = sortedCopy(\$prices);\nreturn expect_same([\$sorted, \$prices], [[7.99, 13.99, 19.99], [19.99, 7.99, 13.99]]);"],
                ['label' => 'Rows sorted by a key', 'code' => "\$rows = [['t' => 'Emma'], ['t' => 'Circe'], ['t' => 'Dune']];\nreturn expect_same([array_column(sortedBy(\$rows, 't'), 't'), array_column(\$rows, 't')], [['Circe', 'Dune', 'Emma'], ['Emma', 'Circe', 'Dune']]);"],
            ],
        ],
        [
            'title' => 'Expressions instead of loops',
            'prompt' => "Rewrite `summary()` without any loop or variable that changes: return `['count' => …, 'total' => …, 'titles' => …]` for the books cheaper than `\$limit` (titles in order, total rounded to 2 places), using array functions.",
            'starter_code' => <<<'PHP'
                <?php

                function summary(array $books, float $limit): array
                {
                    $count = 0;
                    $total = 0;
                    $titles = [];
                    foreach ($books as $book) {
                        if ($book['price'] < $limit) {
                            $count++;
                            $total += $book['price'];
                            $titles[] = $book['title'];
                        }
                    }

                    return ['count' => $count, 'total' => round($total, 2), 'titles' => $titles];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function summary(array $books, float $limit): array
                {
                    $cheap = array_values(array_filter($books, fn (array $book) => $book['price'] < $limit));

                    return [
                        'count' => count($cheap),
                        'total' => round(array_sum(array_column($cheap, 'price')), 2),
                        'titles' => array_column($cheap, 'title'),
                    ];
                }
                PHP,
            'hint' => 'Filter the cheap books once, then build the result from `count()`, `array_sum(array_column(…))` and `array_column(…)`.',
            'checks' => [
                ['label' => 'The summary is right', 'code' => "\$books = [['title' => 'Dune', 'price' => 19.99], ['title' => 'Emma', 'price' => 7.99], ['title' => 'Circe', 'price' => 13.99]];\nreturn expect_same(summary(\$books, 15.0), ['count' => 2, 'total' => 21.98, 'titles' => ['Emma', 'Circe']]);"],
                ['label' => 'No loops', 'code' => "return ! preg_match('/\\\\b(for|foreach|while)\\\\b/', learnp_code()) ?: 'Use array functions instead of a loop.';"],
            ],
        ],
    ],
];
