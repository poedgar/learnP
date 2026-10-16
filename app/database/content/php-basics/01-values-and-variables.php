<?php

// Lesson 1 of the PHP basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'values-and-variables',
    'title' => 'Values and variables',
    'summary' => 'Print with echo, store values in variables, meet PHP\'s basic types, and do arithmetic with numbers.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $title = 'Dune';
        $price = 19.99;
        $copies = 2;

        echo "$copies copies of $title cost " . $price * $copies;
        PHP,

    'quiz' => [
        [
            'question' => 'Which of these is a valid PHP variable name?',
            'options' => ['`title`', '`$title`', '`#title`', '`@title`'],
            'correct_option' => 1,
            'explanation' => 'Variable names start with `$`, followed by a letter or underscore.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $copies = 5;
                $copies = $copies - 2;
                echo $copies;
                PHP,
            'options' => ['`5`', '`3`', '`-2`', '`$copies`'],
            'correct_option' => 1,
            'explanation' => 'The right-hand side is worked out first, using the old value 5, and the result 3 is stored back.',
        ],
        [
            'question' => 'What type is the value `19.99`?',
            'options' => ['`int`', '`string`', '`float`', '`bool`'],
            'correct_option' => 2,
            'explanation' => 'A number with a fractional part is a `float`. `"19.99"` in quotes would be a `string`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'echo 10 / 4 . " " . intdiv(10, 4) . " " . 10 % 4;',
            'options' => ['`2.5 2 2`', '`2 2 2`', '`2.5 2.5 2`', '`2.5 2 0`'],
            'correct_option' => 0,
            'explanation' => '`/` gives the exact answer, `intdiv()` drops the fraction, and `%` gives the remainder: 10 is 2 fours with 2 left over.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => 'echo 1 + 2 * 3 ** 2;',
            'options' => ['`81`', '`19`', '`27`', '`49`'],
            'correct_option' => 1,
            'explanation' => '`**` happens first (9), then `*` (18), then `+` (19). Use brackets when in doubt.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $name = 'Amara';
                echo 'Hello, $name! ' . "Hello, $name!";
                PHP,
            'options' => [
                '`Hello, Amara! Hello, Amara!`',
                '`Hello, $name! Hello, Amara!`',
                '`Hello, Amara! Hello, $name!`',
                '`Hello, $name! Hello, $name!`',
            ],
            'correct_option' => 1,
            'explanation' => 'Single quotes print text exactly as written. Double quotes replace variables with their values.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $count = 1;
                $count++;
                $count += 10;
                $count *= 2;
                echo $count;
                PHP,
            'options' => ['`22`', '`24`', '`13`', '`26`'],
            'correct_option' => 1,
            'explanation' => '1, then 2 after `++`, then 12 after `+= 10`, then 24 after `*= 2`.',
        ],
        [
            'question' => 'Which function shows both a value and its type?',
            'options' => ['`echo`', '`print`', '`var_dump()`', '`round()`'],
            'correct_option' => 2,
            'explanation' => '`var_dump(19.99)` prints `float(19.99)`. `echo` shows only the value, and prints nothing at all for `false` or `null`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Say hello',
            'prompt' => 'Store the text `Ursula K. Le Guin` in a variable called `$author`, then print `Author: Ursula K. Le Guin` using that variable.',
            'starter_code' => <<<'PHP'
                <?php

                PHP,
            'solution' => <<<'PHP'
                <?php

                $author = 'Ursula K. Le Guin';
                echo "Author: $author";
                PHP,
            'hint' => 'Assign with `$author = \'…\';`, then `echo "Author: $author";` (double quotes, so the variable is replaced).',
            'checks' => [
                ['label' => '$author holds the name', 'code' => "return expect_same(\$GLOBALS['author'] ?? null, 'Ursula K. Le Guin');"],
                ['label' => "It prints 'Author: Ursula K. Le Guin'", 'code' => "return expect_same(learnp_output(), 'Author: Ursula K. Le Guin');"],
            ],
        ],
        [
            'title' => 'The cost of an order',
            'prompt' => "A book costs `\$price` and a customer buys `\$copies` of it. Set `\$total` to the price of all the copies, rounded to 2 decimal places. For 3 copies at 12.99, that's 38.97.",
            'starter_code' => <<<'PHP'
                <?php

                $price = 12.99;
                $copies = 3;
                $total = 0;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $price = 12.99;
                $copies = 3;
                $total = round($price * $copies, 2);
                PHP,
            'hint' => 'Multiply `$price * $copies`, and wrap it in `round(…, 2)`.',
            'checks' => [
                ['label' => '$total is 38.97', 'code' => "return expect_same(\$GLOBALS['total'] ?? null, 38.97);"],
                ['label' => '$total is worked out from the other variables', 'code' => <<<'PHP'
                    $variant = str_replace('$copies = 3;', '$copies = 2;', learnp_code());
                    return expect_same(learnp_run($variant . "\necho \$total;"), '25.98');
                    PHP],
            ],
        ],
        [
            'title' => 'Boxes of books',
            'prompt' => 'Books are packed 6 to a box. For `$books` books, set `$fullBoxes` to the number of **full** boxes and `$leftOver` to the number of books left over. 20 books make 3 full boxes with 2 left over.',
            'starter_code' => <<<'PHP'
                <?php

                $books = 20;
                $fullBoxes = $books / 6;
                $leftOver = 0;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $books = 20;
                $fullBoxes = intdiv($books, 6);
                $leftOver = $books % 6;
                PHP,
            'hint' => '`intdiv($books, 6)` gives whole boxes; `$books % 6` gives what\'s left.',
            'checks' => [
                ['label' => '20 books make 3 full boxes', 'code' => "return expect_same(\$GLOBALS['fullBoxes'] ?? null, 3);"],
                ['label' => '2 books are left over', 'code' => "return expect_same(\$GLOBALS['leftOver'] ?? null, 2);"],
            ],
        ],
        [
            'title' => 'Build a sentence',
            'prompt' => "Start with `\$sentence = 'Dune';`. Then, using `.=` twice, add ` by Frank Herbert` and then ` (1965)`, so `\$sentence` ends up as `Dune by Frank Herbert (1965)`.",
            'starter_code' => <<<'PHP'
                <?php

                $sentence = 'Dune';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $sentence = 'Dune';
                $sentence .= ' by Frank Herbert';
                $sentence .= ' (1965)';
                PHP,
            'hint' => '`$sentence .= \' by Frank Herbert\';` adds text to the end. Mind the spaces.',
            'checks' => [
                ['label' => "\$sentence is 'Dune by Frank Herbert (1965)'", 'code' => "return expect_same(\$GLOBALS['sentence'] ?? null, 'Dune by Frank Herbert (1965)');"],
                ['label' => 'It uses .= twice', 'code' => "return substr_count(learnp_code(), '.=') >= 2 ?: 'Add each piece with .=';"],
            ],
        ],
        [
            'title' => 'Know your types',
            'prompt' => 'Create four variables: `$title` holding the text `Emma`, `$pages` holding the whole number 474, `$price` holding the number 7.99, and `$inStock` holding `true`.',
            'starter_code' => <<<'PHP'
                <?php

                $title = Emma;
                $pages = '474';
                $price = '7.99';
                $inStock = 'true';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $title = 'Emma';
                $pages = 474;
                $price = 7.99;
                $inStock = true;
                PHP,
            'hint' => 'Only text goes in quotes. Numbers and `true` are written without quotes.',
            'checks' => [
                ['label' => '$title is the string Emma', 'code' => "return expect_same(\$GLOBALS['title'] ?? null, 'Emma');"],
                ['label' => '$pages is the int 474', 'code' => "return expect_same(\$GLOBALS['pages'] ?? null, 474);"],
                ['label' => '$price is the float 7.99', 'code' => "return expect_same(\$GLOBALS['price'] ?? null, 7.99);"],
                ['label' => '$inStock is the bool true', 'code' => "return expect_same(\$GLOBALS['inStock'] ?? null, true);"],
            ],
        ],
        [
            'title' => 'A receipt line',
            'prompt' => "Using the variables given, print exactly:\n\n```\n2 x The Hobbit = 29.98\n```\n\nThe total must be worked out from `\$copies` and `\$price`.",
            'starter_code' => <<<'PHP'
                <?php

                $title = 'The Hobbit';
                $price = 14.99;
                $copies = 2;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $title = 'The Hobbit';
                $price = 14.99;
                $copies = 2;

                $total = $copies * $price;
                echo "$copies x $title = $total";
                PHP,
            'hint' => 'Work out `$total = $copies * $price;` first, then put all three variables in one double-quoted string.',
            'checks' => [
                ['label' => "It prints '2 x The Hobbit = 29.98'", 'code' => "return expect_same(learnp_output(), '2 x The Hobbit = 29.98');"],
                ['label' => 'The line follows the variables', 'code' => <<<'PHP'
                    $code = preg_replace(['/\$copies\s*=\s*2;/', '/\$price\s*=\s*14\.99;/'], ['$copies = 3;', '$price = 10.0;'], learnp_code());
                    return expect_same(learnp_run($code), '3 x The Hobbit = 30');
                    PHP],
            ],
        ],
    ],
];
