<?php

// Lesson 8 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution; their
// results are compared. Index checks read the columns of the indexes the learner created
// (origin 'c'), so any index name works. Quiz answers that quote plans were checked
// against database/datasets/bookshop_plus.sql.

return [
    'slug' => 'indexes-and-query-plans',
    'title' => 'Indexes and query plans',
    'summary' => 'Read query plans with EXPLAIN QUERY PLAN, create single-column, multi-column, covering, unique and expression indexes, and know when an index can\'t help.',
    'starter_code' => "-- Run this, then add an index and run it again. Watch SCAN turn into SEARCH.\n-- CREATE INDEX orders_customer ON orders (customer_id);\nEXPLAIN QUERY PLAN\nSELECT * FROM orders WHERE customer_id = 3;",

    'quiz' => [
        [
            'question' => 'A query plan says `SCAN orders`. What does that mean?',
            'options' => [
                'The database reads every row of `orders`',
                'The database uses an index on `orders`',
                'The query failed',
                '`orders` is empty',
            ],
            'correct_option' => 0,
            'explanation' => 'A scan reads the whole table. `SEARCH` would mean jumping straight to the matching rows.',
        ],
        [
            'question' => 'What changes when you create an index that a query can use?',
            'options' => [
                'The query\'s result',
                'Nothing at all',
                'How the database finds the rows; the result stays the same',
                'You have to name the index in the query',
            ],
            'correct_option' => 2,
            'explanation' => 'Indexes change the plan, never the result. The database chooses by itself when to use one.',
        ],
        [
            'question' => 'There is an index on `books (author, published_year)`. Which condition can **not** use it?',
            'options' => [
                "`WHERE author = 'Jane Austen'`",
                "`WHERE author = 'Jane Austen' AND published_year = 1813`",
                '`WHERE published_year = 1813`',
                "`WHERE author = 'Jane Austen' AND published_year > 1800`",
            ],
            'correct_option' => 2,
            'explanation' => 'A multi-column index is sorted by its first column. Without a condition on `author`, it\'s like searching a phone book by first name.',
        ],
        [
            'question' => 'What does `USING COVERING INDEX` in a plan mean?',
            'options' => [
                'The index covers every table',
                'The index holds every column the query needs, so the table itself isn\'t read',
                'The index is unique',
                'The query is scanning the index and the table',
            ],
            'correct_option' => 1,
            'explanation' => 'An index stores its own columns. When those are all the query needs, the database answers from the index alone.',
        ],
        [
            'question' => "There is an index on `books (title)`. Why can't `WHERE lower(title) = 'dune'` use it?",
            'options' => [
                'Indexes don\'t work with text',
                'The index holds `title` as it is, not `lower(title)`',
                '`lower` is not allowed in `WHERE`',
                'It can use it',
            ],
            'correct_option' => 1,
            'explanation' => 'Changing the column with a function hides it from a plain index. An index on `lower(title)` would work.',
        ],
        [
            'question' => 'There is an index on `books (author)`. Which condition can use it?',
            'options' => ["`author LIKE '%Tolkien'`", "`author = 'J.R.R. Tolkien'`", "`upper(author) = 'J.R.R. TOLKIEN'`", "`author || '' = 'J.R.R. Tolkien'`"],
            'correct_option' => 1,
            'explanation' => 'Only the plain comparison uses the column as it is. A wildcard at the start, or a function or calculation on the column, can\'t use a sorted list of values.',
        ],
        [
            'question' => 'What is the cost of adding an index?',
            'options' => [
                'Queries return fewer rows',
                'Every insert, update and delete has to update the index, and it takes space',
                'The table can no longer be changed',
                'There is no cost',
            ],
            'correct_option' => 1,
            'explanation' => 'Indexes speed up reading but slow down writing. Index what your frequent queries need, not every column.',
        ],
        [
            'question' => '`visits` was created with `PRIMARY KEY (customer_id, visited_on)`. What did that create, without a `CREATE INDEX`?',
            'options' => [
                'Nothing; only `CREATE INDEX` makes indexes',
                'An index on `(customer_id, visited_on)`',
                'One index on each column',
                'An index on `visited_on` only',
            ],
            'correct_option' => 1,
            'explanation' => 'SQLite creates an index for every primary key and `UNIQUE` rule (except a plain `INTEGER PRIMARY KEY`), named like `sqlite_autoindex_visits_1`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Tables without an index',
            'prompt' => 'List the `name` of every table that has no index at all, not even an automatic one.',
            'starter_code' => null,
            'solution' => "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT IN (SELECT tbl_name FROM sqlite_master WHERE type = 'index');",
            'hint' => "Tables are the rows of `sqlite_master` with `type = 'table'`, and each index row names its table in `tbl_name`. Keep the tables that no index names.",
            'order_matters' => false,
        ],
        [
            'title' => 'Reviews by book',
            'prompt' => 'Reviews are often looked up by book, as in `SELECT * FROM reviews WHERE book_id = 4`. Create an index on `reviews` covering just the `book_id` column, so that query becomes a `SEARCH`.',
            'starter_code' => null,
            'solution' => 'CREATE INDEX reviews_book ON reviews (book_id);',
            'check_sql' => "SELECT ii.name AS column_name FROM pragma_index_list('reviews') AS il JOIN pragma_index_info(il.name) AS ii WHERE il.origin = 'c' ORDER BY il.name, ii.seqno",
            'hint' => '`CREATE INDEX some_name ON reviews (book_id);` Check the plan with `EXPLAIN QUERY PLAN`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Find them and sort them',
            'prompt' => 'The book page runs `SELECT * FROM reviews WHERE book_id = ? ORDER BY reviewed_on DESC`. Create one index on `reviews` that lets it find the book\'s reviews **and** read them in date order, so the plan no longer needs `USE TEMP B-TREE FOR ORDER BY`.',
            'starter_code' => null,
            'solution' => 'CREATE INDEX reviews_book_date ON reviews (book_id, reviewed_on);',
            'check_sql' => "SELECT ii.name AS column_name FROM pragma_index_list('reviews') AS il JOIN pragma_index_info(il.name) AS ii WHERE il.origin = 'c' ORDER BY il.name, ii.seqno",
            'hint' => 'The filtered column comes first, then the sorted one: `(book_id, reviewed_on)`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Visits on a given day',
            'prompt' => 'A report asks who visited on a given day: `SELECT customer_id FROM visits WHERE visited_on = \'2026-03-05\'`. The primary key on `(customer_id, visited_on)` doesn\'t help it. Create an index that does.',
            'starter_code' => null,
            'solution' => 'CREATE INDEX visits_day ON visits (visited_on);',
            'check_sql' => "SELECT ii.name AS column_name FROM pragma_index_list('visits') AS il JOIN pragma_index_info(il.name) AS ii WHERE il.origin = 'c' AND ii.seqno = 0",
            'hint' => 'The primary key\'s index is sorted by `customer_id` first, so it can\'t find a day. An index whose first column is `visited_on` can.',
            'order_matters' => false,
        ],
        [
            'title' => 'One account per email',
            'prompt' => 'Make sure no two customers can ever share an email address, without changing the `customers` table\'s definition.',
            'starter_code' => null,
            'solution' => 'CREATE UNIQUE INDEX customers_email ON customers (email);',
            'check_sql' => "INSERT OR IGNORE INTO customers (name, email, joined_on) VALUES ('Amara Again', 'amara@example.com', '2026-04-09');\nINSERT OR IGNORE INTO customers (name, email, joined_on) VALUES ('Kofi Mensah', 'kofi@example.com', '2026-04-09');\nSELECT name FROM customers ORDER BY id",
            'hint' => '`CREATE UNIQUE INDEX … ON customers (email);`',
            'order_matters' => false,
        ],
        [
            'title' => 'Case-insensitive titles',
            'prompt' => 'The search box runs `SELECT * FROM books WHERE lower(title) = ?`. Create an index that this query can use.',
            'starter_code' => null,
            'solution' => 'CREATE INDEX books_lower_title ON books (lower(title));',
            'check_sql' => "SELECT COUNT(*) AS lower_title_indexes FROM sqlite_master WHERE type = 'index' AND tbl_name = 'books' AND replace(replace(lower(sql), ' ', ''), '\"', '') LIKE '%(lower(title))'",
            'hint' => 'Index the expression itself: `CREATE INDEX … ON books (lower(title));`',
            'order_matters' => false,
        ],
    ],
];
