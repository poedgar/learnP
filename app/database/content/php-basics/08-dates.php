<?php

// Lesson 8 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Checks only use fixed dates, never "now", so they give the same result every day.

return [
    'slug' => 'dates',
    'title' => 'Dates',
    'summary' => 'Create, format, read, move and compare dates with DateTimeImmutable, measure the time between them, and avoid the month and time zone traps.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $borrowed = new DateTimeImmutable('2026-04-08');
        $due = $borrowed->modify('+14 days');

        echo 'Borrowed ' . $borrowed->format('l j F') . "\n";
        echo 'Due back ' . $due->format('l j F');
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => "echo (new DateTimeImmutable('2026-04-08'))->format('d/m/y');",
            'options' => ['`08/04/2026`', '`08/04/26`', '`8/4/26`', '`2026/04/08`'],
            'correct_option' => 1,
            'explanation' => '`d` and `m` have leading zeros; lower-case `y` is the two-digit year.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $date = new DateTimeImmutable('2026-04-08');
                $date->modify('+1 day');
                echo $date->format('j');
                PHP,
            'options' => ['`8`', '`9`', '`7`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'An immutable date never changes. `modify()` returned a new date, which was thrown away. PHP 8.5 even warns when that result is ignored.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => "echo (new DateTimeImmutable('2026-01-31'))->modify('+1 month')->format('Y-m-d');",
            'options' => ['`2026-02-28`', '`2026-02-31`', '`2026-03-03`', '`2026-03-01`'],
            'correct_option' => 2,
            'explanation' => 'Adding a month gives "31 February", which overflows by three days in 2026 into 3 March. Use `last day of next month` for the end of February.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = new DateTimeImmutable('2026-04-01');
                $b = new DateTimeImmutable('2026-04-11');
                echo $a->diff($b)->days;
                PHP,
            'options' => ['`10`', '`11`', '`1`', '`-10`'],
            'correct_option' => 0,
            'explanation' => '`diff()` measures the gap; `days` is the total number of whole days between them.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $due = new DateTimeImmutable('2026-04-22');
                $today = new DateTimeImmutable('2026-04-20');
                var_dump($today > $due);
                PHP,
            'options' => ['`bool(true)`', '`bool(false)`', '`int(-2)`', 'An error: objects can\'t be compared'],
            'correct_option' => 1,
            'explanation' => 'Dates compare by when they happen, and 20 April isn\'t after 22 April.',
        ],
        [
            'question' => "Why start a `createFromFormat()` pattern with `!`, as in `'!d/m/Y'`?",
            'options' => [
                'It makes the date immutable',
                'Parts the pattern doesn\'t mention, such as the time, are set to zero instead of the current time',
                'It makes invalid dates throw an exception',
                'It switches to UTC',
            ],
            'correct_option' => 1,
            'explanation' => 'Without `!`, reading `\'08/04/2026\'` gives that date at the current time of day, so two reads can differ.',
        ],
        [
            'question' => "What does `(new DateTimeImmutable('2026-04-08'))->format('D jS M')` return?",
            'options' => ["`'Wed 8th Apr'`", "`'Wednesday 8 April'`", "`'Wed 08 Apr'`", "`'W 8th A'`"],
            'correct_option' => 0,
            'explanation' => '`D` is the short day name, `j` the day without a zero, `S` its English ending, and `M` the short month name.',
        ],
        [
            'question' => 'Why prefer `DateTimeImmutable` over `DateTime`?',
            'options' => [
                'It\'s faster',
                'Its methods return new dates instead of changing the object, so a shared date can\'t change behind your back',
                'It supports time zones and `DateTime` doesn\'t',
                '`DateTime` is deprecated',
            ],
            'correct_option' => 1,
            'explanation' => 'With `DateTime`, `$due = $borrowed->modify(…)` would change `$borrowed` too.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A friendly date',
            'prompt' => "Write a function `friendlyDate(string \$date): string` that turns a date like `'2026-04-08'` into `'Wednesday 8 April 2026'`.",
            'starter_code' => <<<'PHP'
                <?php

                function friendlyDate(string $date): string
                {
                    return $date;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function friendlyDate(string $date): string
                {
                    return (new DateTimeImmutable($date))->format('l j F Y');
                }
                PHP,
            'hint' => 'Create a `DateTimeImmutable` and `format(\'l j F Y\')`.',
            'checks' => [
                ['label' => "'2026-04-08' is 'Wednesday 8 April 2026'", 'code' => "return expect_same(friendlyDate('2026-04-08'), 'Wednesday 8 April 2026');"],
                ['label' => 'Another date', 'code' => "return expect_same(friendlyDate('1965-08-01'), 'Sunday 1 August 1965');"],
            ],
        ],
        [
            'title' => 'British to ISO',
            'prompt' => "Write a function `ukToIso(string \$date): ?string` that turns a British date like `'08/04/2026'` into `'2026-04-08'`. Return `null` for text that isn't a real date in that format, including impossible dates like `'31/02/2026'`.",
            'starter_code' => <<<'PHP'
                <?php

                function ukToIso(string $date): ?string
                {
                    return (new DateTimeImmutable($date))->format('Y-m-d');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function ukToIso(string $date): ?string
                {
                    $parsed = DateTimeImmutable::createFromFormat('!d/m/Y', $date);

                    if ($parsed === false || $parsed->format('d/m/Y') !== $date) {
                        return null;
                    }

                    return $parsed->format('Y-m-d');
                }
                PHP,
            'hint' => 'Use `createFromFormat(\'!d/m/Y\', $date)`. It gives `false` for nonsense; for impossible dates, format the result back to `d/m/Y` and check it matches the input.',
            'checks' => [
                ['label' => "'08/04/2026' becomes '2026-04-08'", 'code' => "return expect_same(ukToIso('08/04/2026'), '2026-04-08');"],
                ['label' => 'Nonsense gives null', 'code' => "return expect_same([ukToIso('next Tuesday'), ukToIso('2026-04-08')], [null, null]);"],
                ['label' => 'Impossible dates give null', 'code' => "return expect_same(ukToIso('31/02/2026'), null);"],
            ],
        ],
        [
            'title' => 'Due back',
            'prompt' => "Books are lent for 3 weeks. Write a function `dueDate(string \$borrowed): string` returning the due date as `Y-m-d`: `'2026-04-08'` gives `'2026-04-29'`.",
            'starter_code' => <<<'PHP'
                <?php

                function dueDate(string $borrowed): string
                {
                    return $borrowed;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function dueDate(string $borrowed): string
                {
                    return (new DateTimeImmutable($borrowed))->modify('+3 weeks')->format('Y-m-d');
                }
                PHP,
            'hint' => '`->modify(\'+3 weeks\')` (or `+21 days`), then `format(\'Y-m-d\')`.',
            'checks' => [
                ['label' => "'2026-04-08' is due '2026-04-29'", 'code' => "return expect_same(dueDate('2026-04-08'), '2026-04-29');"],
                ['label' => 'Across a month and a year', 'code' => "return expect_same([dueDate('2026-12-20'), dueDate('2028-02-15')], ['2027-01-10', '2028-03-07']);"],
            ],
        ],
        [
            'title' => 'Days between',
            'prompt' => 'Write a function `daysBetween(string $from, string $to): int` returning the number of days between two dates, always as a positive number, whichever comes first.',
            'starter_code' => <<<'PHP'
                <?php

                function daysBetween(string $from, string $to): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function daysBetween(string $from, string $to): int
                {
                    return (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days;
                }
                PHP,
            'hint' => '`$a->diff($b)->days` is always the positive number of days.',
            'checks' => [
                ['label' => 'From 14 March to 2 April', 'code' => "return expect_same(daysBetween('2026-03-14', '2026-04-02'), 19);"],
                ['label' => 'Backwards gives the same', 'code' => "return expect_same(daysBetween('2026-04-02', '2026-03-14'), 19);"],
                ['label' => 'A leap year counts its extra day', 'code' => "return expect_same(daysBetween('2028-02-01', '2028-03-01'), 29);"],
            ],
        ],
        [
            'title' => 'Overdue?',
            'prompt' => "Write a function `isOverdue(string \$due, string \$today): bool` that is `true` only when `\$today` is after the due date. A book returned on its due date isn't overdue.",
            'starter_code' => <<<'PHP'
                <?php

                function isOverdue(string $due, string $today): bool
                {
                    return $today >= $due;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isOverdue(string $due, string $today): bool
                {
                    return new DateTimeImmutable($today) > new DateTimeImmutable($due);
                }
                PHP,
            'hint' => 'Compare two `DateTimeImmutable` objects with `>`.',
            'checks' => [
                ['label' => 'After the due date', 'code' => "return expect_same(isOverdue('2026-04-22', '2026-04-23'), true);"],
                ['label' => 'On or before the due date', 'code' => "return expect_same([isOverdue('2026-04-22', '2026-04-22'), isOverdue('2026-04-22', '2026-04-01')], [false, false]);"],
                ['label' => 'Different date formats still compare correctly', 'code' => "return expect_same(isOverdue('22 April 2026', '2026-04-23'), true);"],
            ],
        ],
        [
            'title' => 'End of the month',
            'prompt' => "Write a function `monthEnd(string \$date): string` returning the last day of that date's month as `Y-m-d`: `'2026-02-10'` gives `'2026-02-28'`.",
            'starter_code' => <<<'PHP'
                <?php

                function monthEnd(string $date): string
                {
                    return (new DateTimeImmutable($date))->modify('+1 month')->format('Y-m-d');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function monthEnd(string $date): string
                {
                    return (new DateTimeImmutable($date))->modify('last day of this month')->format('Y-m-d');
                }
                PHP,
            'hint' => '`modify(\'last day of this month\')`.',
            'checks' => [
                ['label' => 'February 2026', 'code' => "return expect_same(monthEnd('2026-02-10'), '2026-02-28');"],
                ['label' => 'A leap February, and a 31-day month', 'code' => "return expect_same([monthEnd('2028-02-03'), monthEnd('2026-12-31')], ['2028-02-29', '2026-12-31']);"],
            ],
        ],
    ],
];
