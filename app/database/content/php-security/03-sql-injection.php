<?php

// Lesson 3 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'sql-injection',
    'title' => 'SQL injection',
    'summary' => 'How glued-together SQL lets input rewrite a query, and how placeholders and bindings stop it: lists, allow-listed identifiers, LIMIT, LIKE wildcards and readable query logs.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try your own "email" to see the query change.
        $email = "x' OR '1'='1";

        $glued = "SELECT * FROM users WHERE email = '$email'";
        $safe = ['sql' => 'SELECT * FROM users WHERE email = ?', 'bindings' => [$email]];

        echo $glued . PHP_EOL;
        echo $safe['sql'] . '  with ' . json_encode($safe['bindings']);
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $id = '7 OR 1=1';

                echo "DELETE FROM reviews WHERE id = $id";
                PHP,
            'options' => [
                '`DELETE FROM reviews WHERE id = 7`',
                '`DELETE FROM reviews WHERE id = 7 OR 1=1`, which deletes every review',
                '`DELETE FROM reviews WHERE id = \'7 OR 1=1\'`',
                'An error',
            ],
            'correct_option' => 1,
            'explanation' => 'The input became part of the condition. No quotes were even needed, because the id was written unquoted.',
        ],
        [
            'question' => 'Why do placeholders and bindings prevent SQL injection?',
            'options' => [
                'They escape the values more thoroughly',
                'The database gets the query\'s structure and the values separately, so a value can never be read as SQL',
                'They make queries faster',
                'They remove quotes from the input',
            ],
            'correct_option' => 1,
            'explanation' => 'Whatever the value contains, it is only ever data.',
        ],
        [
            'question' => 'Which of these can be a placeholder?',
            'options' => [
                'The table name in `FROM books`',
                'The `DESC` in `ORDER BY price DESC`',
                'The value in `WHERE author = ...`',
                'The column name in `ORDER BY ...`',
            ],
            'correct_option' => 2,
            'explanation' => 'Only values can be bound. Identifiers and keywords need an allow-list.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $allowed = ['title' => 'title', 'newest' => 'published_at'];

                foreach (['newest', 'price; DROP TABLE books', 'title'] as $input) {
                    echo ($allowed[$input] ?? 'title') . ' ';
                }
                PHP,
            'options' => [
                '`published_at price; DROP TABLE books title `',
                '`published_at title title `',
                '`newest title title `',
                '`title title title `',
            ],
            'correct_option' => 1,
            'explanation' => 'Only keys of the allow-list map to a column; everything else falls back to `title`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $ids = [3, 8, 13];

                echo 'SELECT * FROM books WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')';
                PHP,
            'options' => [
                '`SELECT * FROM books WHERE id IN (3, 8, 13)`',
                '`SELECT * FROM books WHERE id IN (?)`',
                '`SELECT * FROM books WHERE id IN (?, ?, ?)`',
                '`SELECT * FROM books WHERE id IN (?, ?, ?, ?)`',
            ],
            'correct_option' => 2,
            'explanation' => 'One placeholder per value; the ids themselves go in the bindings.',
        ],
        [
            'question' => 'A search binds `\'%\' . $term . \'%\'` to `title LIKE ?`. The user searches for `100%`. What goes wrong?',
            'options' => [
                'SQL injection',
                'Nothing',
                'The `%` in their term is a wildcard, so it matches every title starting with "100", not just ones containing "100%"',
                'The query fails',
            ],
            'correct_option' => 2,
            'explanation' => 'It\'s not injection, but it is wrong. Escape `%`, `_` and the escape character itself, and add `ESCAPE`.',
        ],
        [
            'question' => 'Why is `addslashes()` not a good defence against SQL injection?',
            'options' => [
                'It is too slow',
                'It\'s easy to forget, depends on the character set, and does nothing for unquoted numbers or identifiers',
                'It was removed from PHP',
                'It only works with MySQL',
            ],
            'correct_option' => 1,
            'explanation' => 'Bindings make the safe way the default instead of something to remember.',
        ],
        [
            'question' => 'A username was saved safely with a binding. Later, a report builds `"... WHERE name = \'$name\'"` from the stored value. Is that safe?',
            'options' => [
                'Yes, the value came from our own database',
                'No: it\'s second-order injection, because the stored value was still written by a user',
                'Yes, if the report runs at night',
                'Only if the name is short',
            ],
            'correct_option' => 1,
            'explanation' => 'Where the value comes from doesn\'t matter: every value goes in as a binding.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Bind the value',
            'prompt' => '`findUser()` glues the email into the SQL. Rewrite it to return `[\'sql\' => \'SELECT * FROM users WHERE email = ? AND active = ?\', \'bindings\' => [$email, 1]]`, so no input ever appears in the SQL.',
            'starter_code' => <<<'PHP'
                <?php

                function findUser(string $email): array
                {
                    return ['sql' => "SELECT * FROM users WHERE email = '$email' AND active = 1", 'bindings' => []];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function findUser(string $email): array
                {
                    return [
                        'sql' => 'SELECT * FROM users WHERE email = ? AND active = ?',
                        'bindings' => [$email, 1],
                    ];
                }
                PHP,
            'hint' => 'Put a `?` where each value was and list the values, in the same order, under `bindings`.',
            'checks' => [
                ['label' => 'The SQL has placeholders', 'code' => "return expect_same(findUser('ada@example.com'), ['sql' => 'SELECT * FROM users WHERE email = ? AND active = ?', 'bindings' => ['ada@example.com', 1]]);"],
                ['label' => 'An attack stays a value', 'code' => "\$q = findUser(\"x' OR '1'='1\");\nreturn ! str_contains(\$q['sql'], 'OR') && \$q['bindings'][0] === \"x' OR '1'='1\" ?: 'The input reached the SQL.';"],
            ],
        ],
        [
            'title' => 'Many ids',
            'prompt' => 'Write `booksWithIds(array $ids): array` returning `sql` and `bindings` for `SELECT * FROM books WHERE id IN (?, ?, ?)`, one placeholder per id. Each id is converted to an `int`, and duplicates are removed (keeping the first). With no ids, the SQL is `SELECT * FROM books WHERE 0 = 1` and there are no bindings.',
            'starter_code' => <<<'PHP'
                <?php

                function booksWithIds(array $ids): array
                {
                    return ['sql' => 'SELECT * FROM books WHERE id IN (' . implode(', ', $ids) . ')', 'bindings' => []];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function booksWithIds(array $ids): array
                {
                    $ids = array_values(array_unique(array_map('intval', $ids)));
                    if ($ids === []) {
                        return ['sql' => 'SELECT * FROM books WHERE 0 = 1', 'bindings' => []];
                    }

                    $placeholders = implode(', ', array_fill(0, count($ids), '?'));

                    return ['sql' => "SELECT * FROM books WHERE id IN ($placeholders)", 'bindings' => $ids];
                }
                PHP,
            'hint' => '`array_values(array_unique(array_map(\'intval\', $ids)))` cleans the list. `array_fill(0, count($ids), \'?\')` makes the placeholders.',
            'checks' => [
                ['label' => 'One placeholder per id', 'code' => "return expect_same(booksWithIds([3, 8, 13]), ['sql' => 'SELECT * FROM books WHERE id IN (?, ?, ?)', 'bindings' => [3, 8, 13]]);"],
                ['label' => 'Ids become ints, duplicates go', 'code' => "return expect_same(booksWithIds(['5', '5', 9, '1 OR 1=1']), ['sql' => 'SELECT * FROM books WHERE id IN (?, ?, ?)', 'bindings' => [5, 9, 1]]);"],
                ['label' => 'No ids, no IN ()', 'code' => "return expect_same(booksWithIds([]), ['sql' => 'SELECT * FROM books WHERE 0 = 1', 'bindings' => []]);"],
            ],
        ],
        [
            'title' => 'Sort by an allowed column',
            'prompt' => 'Write `orderClause(?string $sort, ?string $direction): string`. `$sort` may be `title`, `newest` (the column `published_at`) or `price`; anything else means `title`. `$direction` may be `asc` or `desc` in any case; anything else means `asc`. Return text like `ORDER BY published_at DESC`, built only from your own strings.',
            'starter_code' => <<<'PHP'
                <?php

                function orderClause(?string $sort, ?string $direction): string
                {
                    return "ORDER BY $sort $direction";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderClause(?string $sort, ?string $direction): string
                {
                    $columns = ['title' => 'title', 'newest' => 'published_at', 'price' => 'price'];
                    $column = $columns[$sort ?? ''] ?? 'title';
                    $order = strtolower($direction ?? '') === 'desc' ? 'DESC' : 'ASC';

                    return "ORDER BY $column $order";
                }
                PHP,
            'hint' => 'Look the sort up in an array of allowed columns, falling back with `??`. For the direction, compare `strtolower()` of it with `desc`.',
            'checks' => [
                ['label' => 'Allowed values', 'code' => "return expect_same([orderClause('newest', 'DESC'), orderClause('price', 'asc'), orderClause('title', 'Desc')], ['ORDER BY published_at DESC', 'ORDER BY price ASC', 'ORDER BY title DESC']);"],
                ['label' => 'Attacks fall back', 'code' => "return expect_same([orderClause('price; DROP TABLE books', 'asc'), orderClause('price', 'desc, (SELECT 1)')], ['ORDER BY title ASC', 'ORDER BY price ASC']);"],
                ['label' => 'Missing values fall back', 'code' => "return expect_same(orderClause(null, null), 'ORDER BY title ASC');"],
                ['label' => 'Column names themselves aren\'t accepted', 'code' => "return expect_same(orderClause('published_at', 'asc'), 'ORDER BY title ASC');"],
            ],
        ],
        [
            'title' => 'Pages',
            'prompt' => 'Write `pageClause(mixed $page, mixed $perPage): string`, returning `LIMIT 20 OFFSET 40` for page 3 of 20. Both values arrive from the query string as anything at all: convert them to `int`, then clamp the page to at least 1 and the page size to between 1 and 100. Only digits may ever reach the SQL.',
            'starter_code' => <<<'PHP'
                <?php

                function pageClause(mixed $page, mixed $perPage): string
                {
                    $offset = ($page - 1) * $perPage;

                    return "LIMIT $perPage OFFSET $offset";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pageClause(mixed $page, mixed $perPage): string
                {
                    $page = max(1, is_numeric($page) ? (int) $page : 1);
                    $perPage = min(100, max(1, is_numeric($perPage) ? (int) $perPage : 20));
                    $offset = ($page - 1) * $perPage;

                    return "LIMIT $perPage OFFSET $offset";
                }
                PHP,
            'hint' => 'Convert with `(int)` (guarding against arrays and other odd types with `is_numeric()`), then clamp with `max()` and `min()`.',
            'checks' => [
                ['label' => 'Normal pages', 'code' => "return expect_same([pageClause('3', '20'), pageClause(1, 10)], ['LIMIT 20 OFFSET 40', 'LIMIT 10 OFFSET 0']);"],
                ['label' => 'Clamped values', 'code' => "return expect_same([pageClause('0', '20'), pageClause('-5', '500'), pageClause(2, 0)], ['LIMIT 20 OFFSET 0', 'LIMIT 100 OFFSET 0', 'LIMIT 1 OFFSET 1']);"],
                ['label' => 'Attacks become digits', 'code' => "\$sql = pageClause('2; DROP TABLE books', '10 UNION SELECT password FROM users');\nreturn preg_match('/^LIMIT \\d+ OFFSET \\d+\$/', \$sql) === 1 ?: \"Not only digits: \$sql\";"],
                ['label' => 'Odd types don\'t break it', 'code' => "\$sql = pageClause(['1'], null);\nreturn preg_match('/^LIMIT \\d+ OFFSET \\d+\$/', \$sql) === 1 ?: \"Unexpected: \$sql\";"],
            ],
        ],
        [
            'title' => 'Search with LIKE',
            'prompt' => 'Write `titleSearch(string $term): array` returning `sql` `SELECT * FROM books WHERE title LIKE ? ESCAPE \'!\'` and one binding: the term with `!`, `%` and `_` each escaped with a `!` in front (escape `!` first), wrapped in `%` on both sides.',
            'starter_code' => <<<'PHP'
                <?php

                function titleSearch(string $term): array
                {
                    return ['sql' => 'SELECT * FROM books WHERE title LIKE ?', 'bindings' => ['%' . $term . '%']];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function titleSearch(string $term): array
                {
                    $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

                    return [
                        'sql' => "SELECT * FROM books WHERE title LIKE ? ESCAPE '!'",
                        'bindings' => ['%' . $escaped . '%'],
                    ];
                }
                PHP,
            'hint' => '`str_replace()` with arrays replaces in order, but it works on the original string each time, so `[\'!\', \'%\', \'_\']` → `[\'!!\', \'!%\', \'!_\']` is safe.',
            'checks' => [
                ['label' => 'The SQL names the escape character', 'code' => "return expect_same(titleSearch('dune')['sql'], \"SELECT * FROM books WHERE title LIKE ? ESCAPE '!'\");"],
                ['label' => 'Ordinary terms', 'code' => "return expect_same(titleSearch('dune')['bindings'], ['%dune%']);"],
                ['label' => 'Wildcards are escaped', 'code' => "return expect_same(titleSearch('100% _real_')['bindings'], ['%100!% !_real!_%']);"],
                ['label' => 'The escape character is escaped too', 'code' => "return expect_same(titleSearch('Wow! 50%')['bindings'], ['%Wow!! 50!%%']);"],
            ],
        ],
        [
            'title' => 'A query for the log',
            'prompt' => 'Write `toRawSql(string $sql, array $bindings): string` for logs only. Replace each `?`, in order, with its binding: strings in single quotes with any `\'` doubled, integers and floats as they are, `true`/`false` as `1`/`0`, and `null` as `NULL`. If the number of `?` and bindings differ, throw `InvalidArgumentException`. A `?` inside a binding must not be replaced again.',
            'starter_code' => <<<'PHP'
                <?php

                function toRawSql(string $sql, array $bindings): string
                {
                    foreach ($bindings as $binding) {
                        $sql = preg_replace('/\?/', (string) $binding, $sql, 1);
                    }

                    return $sql;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function toRawSql(string $sql, array $bindings): string
                {
                    $parts = explode('?', $sql);
                    if (count($parts) - 1 !== count($bindings)) {
                        throw new InvalidArgumentException('The number of placeholders and bindings differ.');
                    }

                    $quote = fn (mixed $value): string => match (true) {
                        $value === null => 'NULL',
                        is_bool($value) => $value ? '1' : '0',
                        is_int($value), is_float($value) => (string) $value,
                        default => "'" . str_replace("'", "''", (string) $value) . "'",
                    };

                    $raw = array_shift($parts);
                    foreach (array_values($bindings) as $index => $binding) {
                        $raw .= $quote($binding) . $parts[$index];
                    }

                    return $raw;
                }
                PHP,
            'hint' => '`explode(\'?\', $sql)` splits the SQL into pieces around the placeholders; glue them back together with a quoted binding between each pair. That way a `?` inside a value is never seen.',
            'checks' => [
                ['label' => 'Values are filled in and quoted', 'code' => "return expect_same(toRawSql('select * from books where author = ? and price < ? and stock > ?', ['Herbert', 9.99, 0]), \"select * from books where author = 'Herbert' and price < 9.99 and stock > 0\");"],
                ['label' => 'Quotes are doubled', 'code' => "return expect_same(toRawSql('select * from authors where name = ?', [\"O'Brien\"]), \"select * from authors where name = 'O''Brien'\");"],
                ['label' => 'null and booleans', 'code' => "return expect_same(toRawSql('update books set note = ?, featured = ?, sold = ?', [null, true, false]), 'update books set note = NULL, featured = 1, sold = 0');"],
                ['label' => 'A ? inside a value stays', 'code' => "return expect_same(toRawSql('select * from faq where q = ? and id = ?', ['Why?', 3]), \"select * from faq where q = 'Why?' and id = 3\");"],
                ['label' => 'Mismatched counts throw', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => toRawSql('select ?, ?', [1]));"],
            ],
        ],
    ],
];
