<?php

// Lesson 2 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'finding-the-rows-you-want',
    'title' => 'Finding the rows you want',
    'summary' => 'Find rows by pattern with LIKE, check lists with IN and ranges with BETWEEN, and handle missing values with NULL and COALESCE.',
    'starter_code' => "-- Try changing the pattern, then press Run (Ctrl+Enter).\nSELECT title, author\nFROM books\nWHERE title LIKE '%the%';",

    'quiz' => [
        [
            'question' => 'Which pattern finds titles that start with "The"?',
            'options' => ["`'%The'`", "`'The%'`", "`'%The%'`", "`'The_'`"],
            'correct_option' => 1,
            'explanation' => "`%` stands for any number of characters, so `'The%'` means \"The, then anything\". `'%The'` would mean \"ends with The\".",
        ],
        [
            'question' => 'How many characters does `_` match in a `LIKE` pattern?',
            'options' => ['Exactly one', 'Any number, including none', 'One or more', 'One whole word'],
            'correct_option' => 0,
            'explanation' => '`_` matches exactly one character. `%` is the one that matches any number, including none.',
        ],
        [
            'question' => 'In SQLite, which condition finds the book *Dune*?',
            'options' => ["`WHERE title = 'dune'`", "`WHERE title LIKE 'DUNE'`", "`WHERE title LIKE 'dun'`", "`WHERE title = 'Dune%'`"],
            'correct_option' => 1,
            'explanation' => "`LIKE` ignores letter case in SQLite, so `'DUNE'` matches *Dune*. `=` is case-sensitive, `'dun'` has no `%` so it must match the whole title, and `=` treats `%` as an ordinary character.",
        ],
        [
            'question' => 'How many books does this query return?',
            'code' => "SELECT title\nFROM books\nWHERE price BETWEEN 12.50 AND 14.00;",
            'options' => ['3', '4', '5', '6'],
            'correct_option' => 2,
            'explanation' => '`BETWEEN` includes both ends, so *The Haunting of Hill House* (12.50) and *Neuromancer* (14.00) count too, along with *Nineteen Eighty-Four*, *The Remains of the Day* and *Circe*.',
        ],
        [
            'question' => 'How many rows does this query return?',
            'code' => "SELECT name\nFROM customers\nWHERE city = NULL;",
            'options' => ['0', '2', '8', '10'],
            'correct_option' => 0,
            'explanation' => 'Comparing anything with `NULL` gives *unknown*, never *true*, so no row passes. Use `WHERE city IS NULL` to find the two customers with no city.',
        ],
        [
            'question' => 'How do you find the customer named Siobhan O\'Brien?',
            'options' => [
                "`WHERE name = 'Siobhan O'Brien'`",
                "`WHERE name = 'Siobhan O\\'Brien'`",
                "`WHERE name = Siobhan O'Brien`",
                "`WHERE name = 'Siobhan O''Brien'`",
            ],
            'correct_option' => 3,
            'explanation' => "Inside single quotes, write a quote twice: `''`. A single `'` would end the text early, and SQL doesn't use a backslash to escape quotes.",
        ],
        [
            'question' => 'What does this query return?',
            'code' => "SELECT name\nFROM customers\nWHERE city NOT IN ('London', NULL);",
            'options' => [
                'Every customer who doesn\'t live in London',
                'Every customer outside London, plus those with no city',
                'No rows at all',
                'An error',
            ],
            'correct_option' => 2,
            'explanation' => '`NOT IN` checks `city <> \'London\' AND city <> NULL`. The comparison with `NULL` is never true, so no row passes. Keep `NULL` out of `NOT IN` lists.',
        ],
        [
            'question' => "What does `COALESCE(shipped_on, 'not yet')` show for an order that hasn't shipped?",
            'options' => ['`NULL`', "`'not yet'`", 'The date it was ordered', 'An empty cell'],
            'correct_option' => 1,
            'explanation' => '`COALESCE` returns its first argument that isn\'t `NULL`. `shipped_on` is `NULL`, so you get `\'not yet\'`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Authors with initials',
            'prompt' => 'Some authors go by their initials. List the `title` and `author` of every book whose author\'s name contains a full stop (`.`).',
            'starter_code' => "SELECT title, author\nFROM books\nWHERE author LIKE '';",
            'solution' => "SELECT title, author FROM books WHERE author LIKE '%.%';",
            'hint' => "`%` matches anything, so `'%.%'` means \"a full stop somewhere in the name\".",
            'order_matters' => false,
        ],
        [
            'title' => 'Gothic favourites',
            'prompt' => 'List the `title` and `author` of the books by Jane Austen, Mary Shelley and Bram Stoker. Use `IN` rather than three `OR`s.',
            'starter_code' => null,
            'solution' => "SELECT title, author FROM books WHERE author IN ('Jane Austen', 'Mary Shelley', 'Bram Stoker');",
            'hint' => 'Put the three names, each in single quotes, inside `IN ( … )`, separated by commas.',
            'order_matters' => false,
        ],
        [
            'title' => 'March orders',
            'prompt' => 'Show the `id` and `ordered_on` date of every order placed in **March 2026**, earliest first. When two orders share a date, put the lower `id` first.',
            'starter_code' => null,
            'solution' => "SELECT id, ordered_on FROM orders WHERE ordered_on BETWEEN '2026-03-01' AND '2026-03-31' ORDER BY ordered_on, id;",
            'hint' => "Use `BETWEEN '2026-03-01' AND '2026-03-31'`, then `ORDER BY ordered_on, id`.",
            'order_matters' => true,
        ],
        [
            'title' => 'Waiting to ship',
            'prompt' => 'List the `id` and `ordered_on` date of every order that **hasn\'t shipped yet**.',
            'starter_code' => null,
            'solution' => 'SELECT id, ordered_on FROM orders WHERE shipped_on IS NULL;',
            'hint' => 'An order that hasn\'t shipped has no `shipped_on` date. `= NULL` never matches; use `IS NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Where our customers live',
            'prompt' => 'List every customer\'s `name` and `city`. Where the city is missing, show `Unknown` instead.',
            'starter_code' => null,
            'solution' => "SELECT name, COALESCE(city, 'Unknown') AS city FROM customers;",
            'hint' => "`COALESCE(city, 'Unknown')` gives the city, or `Unknown` when it is `NULL`.",
            'order_matters' => false,
        ],
        [
            'title' => 'New customers in London and Leeds',
            'prompt' => 'Find the customers who live in **London or Leeds** and **joined in 2026**. Show their `name` and `city`, in alphabetical order by name.',
            'starter_code' => null,
            'solution' => "SELECT name, city FROM customers WHERE city IN ('London', 'Leeds') AND joined_on BETWEEN '2026-01-01' AND '2026-12-31' ORDER BY name;",
            'hint' => 'Combine `city IN (…)` with a date range on `joined_on` using `AND`, then sort with `ORDER BY name`.',
            'order_matters' => true,
        ],
    ],
];
