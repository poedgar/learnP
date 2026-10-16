<?php

// Lesson 2 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'strings-and-formatting',
    'title' => 'Strings and formatting',
    'summary' => 'Work with text: interpolation, the everyday string functions, splitting and joining, sprintf formatting, heredoc and nowdoc, and comparing strings.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $title = '  the name of the wind ';
        $price = 21.5;

        $title = ucwords(trim($title));
        printf("%s costs £%.2f (%d characters)\n", $title, $price, strlen($title));
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $n = 2;
                $thing = 'book';
                echo "$n {$thing}s";
                PHP,
            'options' => ['`2 books`', '`$n {$thing}s`', '`2 {book}s`', '`2 `'],
            'correct_option' => 0,
            'explanation' => 'In double quotes, `{$thing}` marks exactly where the variable name ends, so the `s` that follows stays text.',
        ],
        [
            'question' => 'What does `substr(\'Circe\', -2)` return?',
            'options' => ["`'Ci'`", "`'ce'`", "`'e'`", "`'rce'`"],
            'correct_option' => 1,
            'explanation' => 'A negative start counts from the end: the last two characters.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $position = strpos('Dune', 'D');
                if (! $position) {
                    echo 'missing';
                } else {
                    echo "found at $position";
                }
                PHP,
            'options' => ['`found at 0`', '`missing`', '`found at 1`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`D` is at position 0, and `! 0` is true, so it wrongly says "missing". Compare with `strpos(…) === false` instead.',
        ],
        [
            'question' => 'What does `explode(\' \', \'The Hobbit\')` return?',
            'options' => [
                "`'TheHobbit'`",
                "An array: `['The', 'Hobbit']`",
                "An array: `['T', 'h', 'e', …]`",
                '`2`',
            ],
            'correct_option' => 1,
            'explanation' => '`explode()` splits a string at each separator and returns the pieces as an array. `implode()` does the reverse.',
        ],
        [
            'question' => "What does `sprintf('%.2f', 3.14159)` return?",
            'options' => ["`'3.14159'`", "`'3.14'`", "`'3'`", "`'3.15'`"],
            'correct_option' => 1,
            'explanation' => '`%.2f` formats a number with exactly two decimal places, rounding as needed.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $name = 'Amara';
                echo <<<'TEXT'
                    Hello $name
                    TEXT;
                PHP,
            'options' => ['`Hello Amara`', '`Hello $name`', '`    Hello $name`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => 'With the name in single quotes, it\'s a nowdoc: variables aren\'t replaced. The closing marker\'s indentation is removed from every line.',
        ],
        [
            'question' => "What does `var_dump('Emma' === 'emma');` print?",
            'options' => ['`bool(true)`', '`bool(false)`', '`int(0)`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`===` compares strings exactly, letter case included. Use `strcasecmp()` to ignore case.',
        ],
        [
            'question' => 'What does `str_replace(\'a\', \'o\', \'banana\')` return?',
            'options' => ["`'bonana'`", "`'bonono'`", "`'banano'`", "`'banana'`"],
            'correct_option' => 1,
            'explanation' => '`str_replace()` replaces every occurrence, not just the first.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Tidy a title',
            'prompt' => "Set `\$clean` to `\$raw` with the spaces trimmed from both ends and the first letter of every word in capitals. For `'  the remains of the day '` that's `'The Remains Of The Day'`.",
            'starter_code' => <<<'PHP'
                <?php

                $raw = '  the remains of the day ';
                $clean = $raw;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $raw = '  the remains of the day ';
                $clean = ucwords(trim($raw));
                PHP,
            'hint' => 'Combine `trim()` and `ucwords()`: `ucwords(trim($raw))`.',
            'checks' => [
                ['label' => "\$clean is 'The Remains Of The Day'", 'code' => "return expect_same(\$GLOBALS['clean'] ?? null, 'The Remains Of The Day');"],
                ['label' => 'It works for any title', 'code' => <<<'PHP'
                    $variant = str_replace("'  the remains of the day '", "' circe  '", learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$clean;"), 'Circe');
                    PHP],
            ],
        ],
        [
            'title' => 'Initials',
            'prompt' => "Set `\$initials` to the first letter of `\$first` and of `\$last`, in capitals, each followed by a full stop. For `'toni'` and `'morrison'` that's `'T.M.'`.",
            'starter_code' => <<<'PHP'
                <?php

                $first = 'toni';
                $last = 'morrison';
                $initials = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $first = 'toni';
                $last = 'morrison';
                $initials = strtoupper(substr($first, 0, 1)) . '.' . strtoupper(substr($last, 0, 1)) . '.';
                PHP,
            'hint' => '`substr($first, 0, 1)` is the first letter; `strtoupper()` makes it a capital. Join the pieces with `.`.',
            'checks' => [
                ['label' => "\$initials is 'T.M.'", 'code' => "return expect_same(\$GLOBALS['initials'] ?? null, 'T.M.');"],
                ['label' => 'It works for any name', 'code' => <<<'PHP'
                    $variant = str_replace(["'toni'", "'morrison'"], ["'kazuo'", "'ishiguro'"], learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$initials;"), 'K.I.');
                    PHP],
            ],
        ],
        [
            'title' => 'Tags to a sentence',
            'prompt' => "`\$tags` is a comma-separated list. Set `\$sentence` to the tags joined by `, `, with ` and ` between the last two: `'fantasy,classic,dragons'` becomes `'fantasy, classic and dragons'`. With a single tag, it's just that tag.",
            'starter_code' => <<<'PHP'
                <?php

                $tags = 'fantasy,classic,dragons';
                $sentence = str_replace(',', ', ', $tags);
                PHP,
            'solution' => <<<'PHP'
                <?php

                $tags = 'fantasy,classic,dragons';

                $parts = explode(',', $tags);
                $last = array_pop($parts);
                $sentence = $parts ? implode(', ', $parts) . ' and ' . $last : $last;
                PHP,
            'hint' => '`explode()` the tags, take the last one off with `array_pop()`, `implode(\', \', …)` the rest, and add `\' and \'` and the last one. Handle the single-tag case.',
            'checks' => [
                ['label' => 'Three tags', 'code' => "return expect_same(\$GLOBALS['sentence'] ?? null, 'fantasy, classic and dragons');"],
                ['label' => 'Two tags', 'code' => <<<'PHP'
                    $variant = str_replace("'fantasy,classic,dragons'", "'horror,gothic'", learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$sentence;"), 'horror and gothic');
                    PHP],
                ['label' => 'One tag', 'code' => <<<'PHP'
                    $variant = str_replace("'fantasy,classic,dragons'", "'romance'", learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$sentence;"), 'romance');
                    PHP],
            ],
        ],
        [
            'title' => 'A price tag',
            'prompt' => "Using `sprintf()`, set `\$tag` to the title, a colon, and the price with a pound sign and exactly two decimals: `'Emma: £8.50'` for `'Emma'` and `8.5`.",
            'starter_code' => <<<'PHP'
                <?php

                $title = 'Emma';
                $price = 8.5;
                $tag = $title . ': £' . $price;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $title = 'Emma';
                $price = 8.5;
                $tag = sprintf('%s: £%.2f', $title, $price);
                PHP,
            'hint' => 'The pattern is `\'%s: £%.2f\'`.',
            'checks' => [
                ['label' => "\$tag is 'Emma: £8.50'", 'code' => "return expect_same(\$GLOBALS['tag'] ?? null, 'Emma: £8.50');"],
                ['label' => 'It uses sprintf()', 'code' => "return str_contains(learnp_code(), 'sprintf(') ?: 'Build the tag with sprintf().';"],
                ['label' => 'Whole prices get two decimals too', 'code' => <<<'PHP'
                    $variant = str_replace('$price = 8.5;', '$price = 12;', learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$tag;"), 'Emma: £12.00');
                    PHP],
            ],
        ],
        [
            'title' => 'Hide the email',
            'prompt' => "For privacy, set `\$masked` to the email with everything before the `@` replaced by its first letter followed by `***`: `'amara@example.com'` becomes `'a***@example.com'`.",
            'starter_code' => <<<'PHP'
                <?php

                $email = 'amara@example.com';
                $masked = $email;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $email = 'amara@example.com';

                $at = strpos($email, '@');
                $masked = substr($email, 0, 1) . '***' . substr($email, $at);
                PHP,
            'hint' => '`strpos($email, \'@\')` finds the `@`. Keep `substr($email, 0, 1)`, add `***`, then `substr($email, $at)` from the `@` onwards.',
            'checks' => [
                ['label' => "\$masked is 'a***@example.com'", 'code' => "return expect_same(\$GLOBALS['masked'] ?? null, 'a***@example.com');"],
                ['label' => 'It works for any email', 'code' => <<<'PHP'
                    $variant = str_replace("'amara@example.com'", "'siobhan.obrien@shop.ie'", learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$masked;"), 's***@shop.ie');
                    PHP],
            ],
        ],
        [
            'title' => 'A shipping email',
            'prompt' => "Using a **heredoc**, set `\$email` to these three lines (with the values filled in from the variables, and no indentation left in the result):\n\n```\nDear Amara,\nOrder A-1001 has shipped.\nThank you!\n```",
            'starter_code' => <<<'PHP'
                <?php

                $name = 'Amara';
                $order = 'A-1001';
                $email = '';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $name = 'Amara';
                $order = 'A-1001';
                $email = <<<TEXT
                    Dear $name,
                    Order $order has shipped.
                    Thank you!
                    TEXT;
                PHP,
            'hint' => 'Start with `<<<TEXT`, write the three lines indented like the closing `TEXT;`, which goes on its own line.',
            'checks' => [
                ['label' => 'The email has the three lines', 'code' => "return expect_same(\$GLOBALS['email'] ?? null, \"Dear Amara,\\nOrder A-1001 has shipped.\\nThank you!\");"],
                ['label' => 'It uses a heredoc', 'code' => "return str_contains(learnp_code(), '<<<') ?: 'Build the email with a heredoc.';"],
            ],
        ],
    ],
];
