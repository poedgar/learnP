<?php

// Lesson 9 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'types-and-null-safety',
    'title' => 'Types and null safety',
    'summary' => 'Describe values precisely with nullable, union, intersection, mixed and never types, and handle missing values with ??, ??= and the nullsafe ?-> operator.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function publishedYear(?DateTimeImmutable $published): string
        {
            return $published?->format('Y') ?? 'unknown';
        }

        echo publishedYear(new DateTimeImmutable('1965-08-01')) . "\n";
        echo publishedYear(null);
        PHP,

    'quiz' => [
        [
            'question' => 'Which values does a parameter typed `?string` accept?',
            'options' => ['Only strings', 'Strings and `null`', 'Anything', 'Only `null`'],
            'correct_option' => 1,
            'explanation' => '`?string` is short for `string|null`.',
        ],
        [
            'question' => '`strpos()` is declared to return `int|false`. What does that mean?',
            'options' => [
                'It returns an int or throws',
                'It returns either an int or the value `false`',
                'It returns an int that may be 0',
                'It returns a bool',
            ],
            'correct_option' => 1,
            'explanation' => 'A union type lists every type the value may have. That\'s why you compare its result with `=== false`.',
        ],
        [
            'question' => 'What is the difference between `void` and `never` as return types?',
            'options' => [
                'There is none',
                '`void` returns without a value; `never` never returns at all, because it always throws or exits',
                '`never` returns `null`',
                '`void` is only for methods',
            ],
            'correct_option' => 1,
            'explanation' => 'Code after a call to a `never` function can\'t run, and PHP enforces that the function doesn\'t return.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = null;
                $a ??= 'first';
                $a ??= 'second';
                echo $a;
                PHP,
            'options' => ['`first`', '`second`', '`firstsecond`', 'Nothing'],
            'correct_option' => 0,
            'explanation' => '`??=` assigns only when the variable is missing or `null`. The second time, it already holds `\'first\'`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $published = null;
                var_dump($published?->format('Y'));
                PHP,
            'options' => ['`NULL`', '`string(0) ""`', 'An error', '`bool(false)`'],
            'correct_option' => 0,
            'explanation' => '`?->` sees `null` and gives `null` instead of calling the method.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => "echo (0 ?? 5) . ' ' . (0 ?: 5);",
            'options' => ['`5 5`', '`0 0`', '`0 5`', '`5 0`'],
            'correct_option' => 2,
            'explanation' => '`??` only falls back for missing or `null` values, so 0 stays. `?:` falls back for any falsy value, and 0 is falsy.',
        ],
        [
            'question' => 'Which type accepts **any** value, `null` included?',
            'options' => ['`object`', '`mixed`', '`?string`', '`never`'],
            'correct_option' => 1,
            'explanation' => '`mixed` accepts everything. It\'s honest when true, but gives up the checks a precise type would give.',
        ],
        [
            'question' => 'What does the type `Countable&Traversable` require of a value?',
            'options' => [
                'That it\'s either countable or traversable',
                'That it\'s both countable and traversable',
                'That it\'s an array',
                'Nothing; `&` means it\'s optional',
            ],
            'correct_option' => 1,
            'explanation' => '`&` makes an intersection type: the value must satisfy every listed type at once. `|` means "any of".',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An optional discount',
            'prompt' => 'Write a function `discounted(float $price, ?int $percent = null): float`. With no discount (`null`), it returns the price unchanged; otherwise the price with that percentage off, rounded to 2 decimal places.',
            'starter_code' => <<<'PHP'
                <?php

                function discounted(float $price, int $percent): float
                {
                    return round($price * (100 - $percent) / 100, 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function discounted(float $price, ?int $percent = null): float
                {
                    if ($percent === null) {
                        return $price;
                    }

                    return round($price * (100 - $percent) / 100, 2);
                }
                PHP,
            'hint' => 'Make the parameter `?int $percent = null`, and return the price early when it\'s `null`.',
            'checks' => [
                ['label' => 'With a discount', 'code' => 'return expect_same(discounted(20.0, 25), 15.0);'],
                ['label' => 'Without one, the price is unchanged', 'code' => 'return expect_same([discounted(19.99), discounted(19.99, null)], [19.99, 19.99]);'],
                ['label' => '$percent is a nullable int', 'code' => "return (string) (new ReflectionFunction('discounted'))->getParameters()[1]->getType() === '?int' ?: 'Declare \$percent as ?int.';"],
            ],
        ],
        [
            'title' => 'A number or some words',
            'prompt' => "Write a function `quantityLabel(int|string \$quantity): string`. For an int, return `'1 item'` or `'N items'`; for a string, return it trimmed, such as `'a few'`.",
            'starter_code' => <<<'PHP'
                <?php

                function quantityLabel(int $quantity): string
                {
                    return "$quantity items";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function quantityLabel(int|string $quantity): string
                {
                    if (is_string($quantity)) {
                        return trim($quantity);
                    }

                    return $quantity === 1 ? '1 item' : "$quantity items";
                }
                PHP,
            'hint' => 'Declare `int|string`, then check `is_string()` to decide what to do.',
            'checks' => [
                ['label' => 'Numbers', 'code' => "return expect_same([quantityLabel(1), quantityLabel(3), quantityLabel(0)], ['1 item', '3 items', '0 items']);"],
                ['label' => 'Words', 'code' => "return expect_same(quantityLabel('  a few '), 'a few');"],
                ['label' => 'The parameter is int|string', 'code' => <<<'PHP'
                    $type = (new ReflectionFunction('quantityLabel'))->getParameters()[0]->getType();
                    return in_array((string) $type, ['int|string', 'string|int'], true) ?: 'Declare the parameter as int|string.';
                    PHP],
            ],
        ],
        [
            'title' => 'Read a price',
            'prompt' => "Write a function `parsePrice(string \$text): float|false` that reads a price such as `'£12.50'`, `'12.5'` or `' 3 '` (an optional pound sign, then a number) and returns it as a float, or returns `false` if the text isn't a price, like `'free'` or `'£'`.",
            'starter_code' => <<<'PHP'
                <?php

                function parsePrice(string $text): float|false
                {
                    return (float) $text;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parsePrice(string $text): float|false
                {
                    $number = ltrim(trim($text), '£');

                    return is_numeric($number) ? (float) $number : false;
                }
                PHP,
            'hint' => 'Trim spaces, take off a leading `£` with `ltrim(…, \'£\')`, then check `is_numeric()` before converting.',
            'checks' => [
                ['label' => 'Prices are read', 'code' => "return expect_same([parsePrice('£12.50'), parsePrice('12.5'), parsePrice(' 3 ')], [12.5, 12.5, 3.0]);"],
                ['label' => 'Anything else is false', 'code' => "return expect_same([parsePrice('free'), parsePrice('£'), parsePrice('')], [false, false, false]);"],
            ],
        ],
        [
            'title' => 'Fail loudly',
            'prompt' => "Write a function `fail(string \$message): never` that throws a `RuntimeException` with that message. Then write `checkedStock(int \$stock): int`, which returns `\$stock` when it's above 0 and otherwise calls `fail('Out of stock')`.",
            'starter_code' => <<<'PHP'
                <?php

                function fail(string $message): void
                {
                    echo $message;
                }

                function checkedStock(int $stock): int
                {
                    if ($stock <= 0) {
                        fail('Out of stock');
                    }

                    return $stock;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function fail(string $message): never
                {
                    throw new RuntimeException($message);
                }

                function checkedStock(int $stock): int
                {
                    return $stock > 0 ? $stock : fail('Out of stock');
                }
                PHP,
            'hint' => 'Give `fail()` the return type `never` and `throw new RuntimeException($message);` inside it.',
            'checks' => [
                ['label' => 'fail() returns never', 'code' => "return (string) (new ReflectionFunction('fail'))->getReturnType() === 'never' ?: 'Give fail() the return type never.';"],
                ['label' => 'Stock above 0 is returned', 'code' => 'return expect_same(checkedStock(4), 4);'],
                ['label' => "No stock throws 'Out of stock'", 'code' => <<<'PHP'
                    try {
                        checkedStock(0);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Out of stock');
                    }
                    return 'checkedStock(0) should throw a RuntimeException.';
                    PHP],
            ],
        ],
        [
            'title' => 'Fill in the defaults',
            'prompt' => "Write a function `withDefaults(array \$settings): array` that uses `??=` to set `'theme'` to `'light'`, `'perPage'` to 20 and `'language'` to `'en'`, but only where the setting is missing or `null`. Settings already given (even `0` or `''`) are kept.",
            'starter_code' => <<<'PHP'
                <?php

                function withDefaults(array $settings): array
                {
                    return $settings;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function withDefaults(array $settings): array
                {
                    $settings['theme'] ??= 'light';
                    $settings['perPage'] ??= 20;
                    $settings['language'] ??= 'en';

                    return $settings;
                }
                PHP,
            'hint' => 'One line per setting: `$settings[\'theme\'] ??= \'light\';`, and so on.',
            'checks' => [
                ['label' => 'Missing settings get their defaults', 'code' => "return expect_same(withDefaults([]), ['theme' => 'light', 'perPage' => 20, 'language' => 'en']);"],
                ['label' => 'null settings get defaults, others are kept', 'code' => "return expect_same(withDefaults(['theme' => 'dark', 'perPage' => null, 'language' => '']), ['theme' => 'dark', 'perPage' => 20, 'language' => '']);"],
                ['label' => '0 is kept', 'code' => "return expect_same(withDefaults(['perPage' => 0])['perPage'], 0);"],
            ],
        ],
        [
            'title' => 'Who reviewed it?',
            'prompt' => "Write a function `reviewedOn(?DateTimeImmutable \$reviewed): string` returning the date formatted like `'8 Apr 2026'`, or `'not reviewed'` when it's `null`. Use the nullsafe operator.",
            'starter_code' => <<<'PHP'
                <?php

                function reviewedOn(?DateTimeImmutable $reviewed): string
                {
                    return $reviewed->format('j M Y');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function reviewedOn(?DateTimeImmutable $reviewed): string
                {
                    return $reviewed?->format('j M Y') ?? 'not reviewed';
                }
                PHP,
            'hint' => '`$reviewed?->format(\'j M Y\') ?? \'not reviewed\'`.',
            'checks' => [
                ['label' => 'A date is formatted', 'code' => "return expect_same(reviewedOn(new DateTimeImmutable('2026-04-08')), '8 Apr 2026');"],
                ['label' => "null gives 'not reviewed'", 'code' => "return expect_same(reviewedOn(null), 'not reviewed');"],
                ['label' => 'It uses ?->', 'code' => "return str_contains(learnp_code(), '?->') ?: 'Use the nullsafe operator ?->.';"],
            ],
        ],
    ],
];
