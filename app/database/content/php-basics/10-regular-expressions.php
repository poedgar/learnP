<?php

// Lesson 10 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'regular-expressions',
    'title' => 'Regular expressions',
    'summary' => 'Match, capture, find, replace and split text by pattern with preg_match, preg_match_all, preg_replace and preg_split.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $text = 'Orders A-1001, A-1002 and A-77 are ready.';

        preg_match_all('/A-(\d+)/', $text, $matches);
        print_r($matches[1]);

        echo preg_replace('/A-(\d+)/', '#$1', $text);
        PHP,

    'quiz' => [
        [
            'question' => "What does `preg_match('/emma/i', 'Emma by Jane Austen')` return?",
            'options' => ['`0`', '`1`', "`'Emma'`", '`true`'],
            'correct_option' => 1,
            'explanation' => 'The `i` flag ignores case, so it matches. `preg_match()` returns 1 for a match and 0 for none.',
        ],
        [
            'question' => 'What does `\d{3}` match?',
            'options' => ['The letter d three times', 'Exactly three digits', 'Up to three digits', 'Any three characters'],
            'correct_option' => 1,
            'explanation' => '`\d` is a digit and `{3}` means exactly three of them.',
        ],
        [
            'question' => "Why does `preg_match('/\d{4}/', 'Room 123456')` return 1 when you wanted exactly four digits?",
            'options' => [
                'Because `\d` also matches letters',
                'Because without `^` and `$`, four digits anywhere in the text are enough',
                'Because `{4}` means "at least 4"',
                'It doesn\'t; it returns 0',
            ],
            'correct_option' => 1,
            'explanation' => 'Anchor validation patterns at both ends: `/^\d{4}$/`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                preg_match('/(\w+)@(\w+)\.com/', 'Write to amara@example.com today', $m);
                echo $m[2];
                PHP,
            'options' => ['`amara`', '`example`', '`amara@example.com`', '`com`'],
            'correct_option' => 1,
            'explanation' => 'Index 0 is the whole match; 1 and 2 are the groups in order.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => "echo preg_match_all('/o/', 'Room for books');",
            'options' => ['`1`', '`3`', '`5`', '`4`'],
            'correct_option' => 2,
            'explanation' => '`preg_match_all()` returns how many matches it found: two in "Room", one in "for" and two in "books".',
        ],
        [
            'question' => 'What does this code print?',
            'code' => "echo preg_replace('/(\\d+)-(\\d+)/', '$2-$1', 'Pages 10-25');",
            'options' => ['`Pages 10-25`', '`Pages 25-10`', '`Pages $2-$1`', '`Pages 25`'],
            'correct_option' => 1,
            'explanation' => 'In the replacement, `$1` and `$2` stand for what each group matched, so they swap.',
        ],
        [
            'question' => "What is in `\$m[1]` after `preg_match('/\"(.*)\"/', 'say \"hi\" and \"bye\"', \$m)`?",
            'options' => ['`hi`', '`bye`', '`hi" and "bye`', 'Nothing'],
            'correct_option' => 2,
            'explanation' => '`.*` is greedy, so it runs to the **last** quote. `(.*?)` would stop at the first, giving `hi`.',
        ],
        [
            'question' => "What does `preg_split('/\\s*,\\s*/', 'a , b,c')` return?",
            'options' => ["`['a', 'b', 'c']`", "`['a ', ' b', 'c']`", "`['a , b,c']`", "`['a', 'b,c']`"],
            'correct_option' => 0,
            'explanation' => 'The pattern swallows the spaces around each comma, so the pieces come out clean.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A UK-style order number',
            'prompt' => "Write a function `isOrderNumber(string \$text): bool` that is `true` only for order numbers made of two capital letters, a dash and exactly four digits, such as `'AB-1234'`, and nothing before or after.",
            'starter_code' => <<<'PHP'
                <?php

                function isOrderNumber(string $text): bool
                {
                    return preg_match('/[A-Z]{2}-\d{4}/', $text) === 1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isOrderNumber(string $text): bool
                {
                    return preg_match('/^[A-Z]{2}-\d{4}$/', $text) === 1;
                }
                PHP,
            'hint' => 'Anchor the pattern at both ends with `^` and `$`.',
            'checks' => [
                ['label' => 'Valid order numbers', 'code' => "return expect_same([isOrderNumber('AB-1234'), isOrderNumber('ZZ-0000')], [true, true]);"],
                ['label' => 'Wrong shapes are refused', 'code' => "return expect_same(array_map('isOrderNumber', ['ab-1234', 'AB-123', 'ABC-1234', 'AB1234']), [false, false, false, false]);"],
                ['label' => 'Nothing before or after', 'code' => "return expect_same([isOrderNumber('XAB-1234'), isOrderNumber('AB-12345'), isOrderNumber(' AB-1234')], [false, false, false]);"],
            ],
        ],
        [
            'title' => 'Year, month and day',
            'prompt' => "Write a function `dateParts(string \$date): ?array` that reads a date like `'2026-04-08'` and returns `['year' => 2026, 'month' => 4, 'day' => 8]` (as ints), or `null` if the text isn't in that shape.",
            'starter_code' => <<<'PHP'
                <?php

                function dateParts(string $date): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function dateParts(string $date): ?array
                {
                    if (! preg_match('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/', $date, $parts)) {
                        return null;
                    }

                    return ['year' => (int) $parts['year'], 'month' => (int) $parts['month'], 'day' => (int) $parts['day']];
                }
                PHP,
            'hint' => 'Three groups, `(\d{4})-(\d{2})-(\d{2})`, anchored. Named groups `(?<year>…)` make the result easy to read. Cast each part with `(int)`.',
            'checks' => [
                ['label' => 'A date is split into ints', 'code' => "return expect_same(dateParts('2026-04-08'), ['year' => 2026, 'month' => 4, 'day' => 8]);"],
                ['label' => 'Anything else gives null', 'code' => "return expect_same([dateParts('08/04/2026'), dateParts('2026-4-8'), dateParts('in 2026-04-08')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Every hashtag',
            'prompt' => "Write a function `hashtags(string \$text): array` returning every hashtag in the text, without the `#`, in lower case and in order: `'Loved #Dune and #SciFi!'` gives `['dune', 'scifi']`. A hashtag is `#` followed by one or more letters, digits or underscores.",
            'starter_code' => <<<'PHP'
                <?php

                function hashtags(string $text): array
                {
                    preg_match('/#(\w+)/', $text, $match);

                    return [$match[1] ?? ''];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function hashtags(string $text): array
                {
                    preg_match_all('/#(\w+)/', $text, $matches);

                    return array_map('strtolower', $matches[1]);
                }
                PHP,
            'hint' => '`preg_match_all(\'/#(\w+)/\', $text, $matches)`, then lower-case `$matches[1]`.',
            'checks' => [
                ['label' => 'All the hashtags, lower case', 'code' => "return expect_same(hashtags('Loved #Dune and #SciFi!'), ['dune', 'scifi']);"],
                ['label' => 'Digits and underscores, and no hashtags at all', 'code' => "return expect_same([hashtags('#book_club_2026 meets'), hashtags('no tags here')], [['book_club_2026'], []]);"],
            ],
        ],
        [
            'title' => 'Tidy the spaces',
            'prompt' => 'Write a function `tidySpaces(string $text): string` that replaces every run of spaces, tabs and new lines with a single space, and removes them from both ends.',
            'starter_code' => <<<'PHP'
                <?php

                function tidySpaces(string $text): string
                {
                    return str_replace('  ', ' ', $text);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function tidySpaces(string $text): string
                {
                    return trim(preg_replace('/\s+/', ' ', $text));
                }
                PHP,
            'hint' => '`preg_replace(\'/\s+/\', \' \', $text)`, then `trim()`.',
            'checks' => [
                ['label' => 'Runs of spaces become one', 'code' => "return expect_same(tidySpaces('The     Name   of the Wind'), 'The Name of the Wind');"],
                ['label' => 'Tabs, new lines and the ends', 'code' => "return expect_same(tidySpaces(\"  Dune\\t\\tby\\n Frank  Herbert \"), 'Dune by Frank Herbert');"],
            ],
        ],
        [
            'title' => 'Surname first',
            'prompt' => "Write a function `surnameFirst(string \$name): string` that turns `'Jane Austen'` into `'Austen, Jane'` and `'Ursula K. Le Guin'` into `'Le Guin, Ursula K.'`: the first word stays at the front of the given names, and a surname is the **last** word, or the last two words when the second-to-last is `Le`, `De` or `Van`. Use `preg_replace()`.",
            'starter_code' => <<<'PHP'
                <?php

                function surnameFirst(string $name): string
                {
                    return $name;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function surnameFirst(string $name): string
                {
                    return preg_replace('/^(.+?) ((?:(?:Le|De|Van) )?\S+)$/', '$2, $1', $name);
                }
                PHP,
            'hint' => 'Capture the given names lazily `(.+?)`, then a space, then the surname: an optional `(?:Le|De|Van) ` followed by `\S+`, at the end. Replace with `$2, $1`.',
            'checks' => [
                ['label' => 'Two names', 'code' => "return expect_same(surnameFirst('Jane Austen'), 'Austen, Jane');"],
                ['label' => 'Middle initials stay with the given names', 'code' => "return expect_same(surnameFirst('Ursula K. Le Guin'), 'Le Guin, Ursula K.');"],
                ['label' => 'More names', 'code' => "return expect_same([surnameFirst('Toni Morrison'), surnameFirst('Ludwig Van Beethoven'), surnameFirst('J. R. R. Tolkien')], ['Morrison, Toni', 'Van Beethoven, Ludwig', 'Tolkien, J. R. R.']);"],
            ],
        ],
        [
            'title' => 'Split a tag list',
            'prompt' => "Tags are typed with commas, semicolons or slashes between them, and random spaces. Write a function `splitTags(string \$tags): array` returning the tags in order, without spaces around them, and without empty ones: `'fantasy ; classic,, dragons / maps'` gives `['fantasy', 'classic', 'dragons', 'maps']`.",
            'starter_code' => <<<'PHP'
                <?php

                function splitTags(string $tags): array
                {
                    return explode(',', $tags);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function splitTags(string $tags): array
                {
                    return preg_split('/\s*[,;\/]+\s*/', trim($tags), -1, PREG_SPLIT_NO_EMPTY);
                }
                PHP,
            'hint' => '`preg_split()` with a pattern like `/\s*[,;\/]+\s*/`. Its fourth argument, `PREG_SPLIT_NO_EMPTY`, drops empty pieces (pass `-1` as the third, meaning "no limit").',
            'checks' => [
                ['label' => 'Mixed separators', 'code' => "return expect_same(splitTags('fantasy ; classic,, dragons / maps'), ['fantasy', 'classic', 'dragons', 'maps']);"],
                ['label' => 'Separators at the ends, and a single tag', 'code' => "return expect_same([splitTags(', horror ;'), splitTags('romance')], [['horror'], ['romance']]);"],
            ],
        ],
    ],
];
