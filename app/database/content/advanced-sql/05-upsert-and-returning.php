<?php

// Lesson 5 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution;
// their results are compared. Quiz answers that quote numbers were checked against
// database/datasets/bookshop_plus.sql.

return [
    'slug' => 'upsert-and-returning',
    'title' => 'Upsert and RETURNING',
    'summary' => 'Insert a row or update it if it already exists with ON CONFLICT, avoid the traps of INSERT OR REPLACE, and see what a change did with RETURNING.',
    'starter_code' => "-- A delivery: add the copies to existing rows, or create new ones.\nINSERT INTO stock_levels (book_id, quantity, updated_on)\nVALUES (4, 5, '2026-04-09'), (12, 3, '2026-04-09')\nON CONFLICT (book_id) DO UPDATE SET\n    quantity = quantity + excluded.quantity,\n    updated_on = excluded.updated_on\nRETURNING *;",

    'quiz' => [
        [
            'question' => 'Book 4 already has a row in `stock_levels`, whose primary key is `book_id`. What does a plain `INSERT` of another row for book 4 do?',
            'options' => [
                'Adds a second row for book 4',
                'Replaces the existing row',
                'Fails with a UNIQUE constraint error',
                'Does nothing, silently',
            ],
            'correct_option' => 2,
            'explanation' => 'A primary key must be unique, so the insert is refused. An upsert says what to do instead.',
        ],
        [
            'question' => 'What does this do, if book 4 has a row and book 12 doesn\'t?',
            'code' => "INSERT OR IGNORE INTO stock_levels (book_id, quantity, updated_on)\nVALUES (4, 5, '2026-04-09'), (12, 3, '2026-04-09');",
            'options' => [
                'Inserts book 12 and leaves book 4 unchanged',
                'Fails, and inserts nothing',
                'Inserts book 12 and sets book 4 to 5 copies',
                'Inserts nothing, because one row conflicts',
            ],
            'correct_option' => 0,
            'explanation' => '`OR IGNORE` skips only the rows that conflict. The others are inserted as normal.',
        ],
        [
            'question' => 'A table has a column `body TEXT DEFAULT \'empty\'`. Row 1 has a body of `\'Milk\'`. What is its body after `INSERT OR REPLACE INTO notes (id, title) VALUES (1, \'Groceries\')`?',
            'options' => ["`'Milk'`", "`'empty'`", '`NULL`', 'The statement fails'],
            'correct_option' => 1,
            'explanation' => '`OR REPLACE` deletes the old row and inserts a new one. Columns you don\'t mention get their default, so the old body is lost.',
        ],
        [
            'question' => 'In `ON CONFLICT (book_id) DO UPDATE SET quantity = quantity + excluded.quantity`, what is `excluded.quantity`?',
            'options' => [
                'The quantity already in the table',
                'The quantity in the row you tried to insert',
                'The quantity of rows that were skipped',
                'Always 0',
            ],
            'correct_option' => 1,
            'explanation' => '`excluded` is the new row that couldn\'t be inserted. The plain `quantity` is the existing row\'s value.',
        ],
        [
            'question' => 'What must be true of the column named in `ON CONFLICT (book_id)`?',
            'options' => [
                'It must be called `id`',
                'It must be `NOT NULL`',
                'It must be the primary key or have a `UNIQUE` rule',
                'It must be an `INTEGER`',
            ],
            'correct_option' => 2,
            'explanation' => 'A conflict means breaking a uniqueness rule. Without one on that column, there\'s nothing to conflict with.',
        ],
        [
            'question' => 'Why does an upsert written as `INSERT … SELECT … FROM wishlist ON CONFLICT …` need a `WHERE` (even `WHERE true`) in SQLite?',
            'options' => [
                'To skip rows with `NULL`s',
                'Without it, SQLite reads `ON` as the start of a join condition',
                'To make the insert faster',
                'It doesn\'t; the `WHERE` is optional',
            ],
            'correct_option' => 1,
            'explanation' => 'After `FROM wishlist`, the word `ON` could start a join condition. A `WHERE` clause ends the `FROM` part, so `ON CONFLICT` is read correctly.',
        ],
        [
            'question' => 'What does `RETURNING id, title` on a `DELETE` return?',
            'options' => [
                'The rows that are left',
                'The rows that were deleted',
                'The number of deleted rows',
                'Nothing; `RETURNING` only works with `INSERT`',
            ],
            'correct_option' => 1,
            'explanation' => '`RETURNING` works with `INSERT`, `UPDATE` and `DELETE`, and returns the rows the statement changed. For a `DELETE`, that\'s the removed rows.',
        ],
        [
            'question' => 'An upsert ends with `DO UPDATE SET … WHERE excluded.updated_on > stock_levels.updated_on`. What happens to an existing row when the new date is older?',
            'options' => [
                'It\'s updated anyway',
                'It\'s deleted',
                'It\'s left unchanged, without an error',
                'The whole statement fails',
            ],
            'correct_option' => 2,
            'explanation' => 'When the `WHERE` is false, the conflicting row is simply left as it was, and the statement carries on.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A new delivery',
            'prompt' => 'On 2026-04-09, 6 copies of book 12 and 3 copies of book 7 arrived. Add them to `stock_levels` in one statement: add to the quantity of a book that already has a row, create a row for a book that doesn\'t, and set `updated_on` to the delivery date either way.',
            'starter_code' => "INSERT INTO stock_levels (book_id, quantity, updated_on)\nVALUES (12, 6, '2026-04-09'), (7, 3, '2026-04-09')\nON CONFLICT (book_id) DO UPDATE SET\n    …;",
            'solution' => "INSERT INTO stock_levels (book_id, quantity, updated_on) VALUES (12, 6, '2026-04-09'), (7, 3, '2026-04-09') ON CONFLICT (book_id) DO UPDATE SET quantity = quantity + excluded.quantity, updated_on = excluded.updated_on;",
            'check_sql' => 'SELECT * FROM stock_levels ORDER BY book_id',
            'hint' => '`quantity = quantity + excluded.quantity` adds the delivery to what\'s there, and `updated_on = excluded.updated_on` records the date.',
            'order_matters' => false,
        ],
        [
            'title' => 'Track every book',
            'prompt' => 'Make sure every book has a row in `stock_levels`. Give each book without one a quantity of 0 and an `updated_on` of 2026-04-09, and leave existing rows exactly as they are.',
            'starter_code' => null,
            'solution' => "INSERT INTO stock_levels (book_id, quantity, updated_on) SELECT id, 0, '2026-04-09' FROM books WHERE true ON CONFLICT (book_id) DO NOTHING;",
            'check_sql' => 'SELECT * FROM stock_levels ORDER BY book_id',
            'hint' => 'Insert from `SELECT id, 0, \'2026-04-09\' FROM books`, and skip the books that already have a row with `ON CONFLICT (book_id) DO NOTHING`. Remember SQLite\'s `WHERE true`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Stocktake',
            'prompt' => 'A stocktake on 2026-04-10 counted 5 copies of book 3, 2 copies of book 6 and 1 copy of book 9. Record these exact counts, replacing (not adding to) any existing quantity, creating rows where needed, and set `updated_on` to 2026-04-10.',
            'starter_code' => null,
            'solution' => "INSERT INTO stock_levels (book_id, quantity, updated_on) VALUES (3, 5, '2026-04-10'), (6, 2, '2026-04-10'), (9, 1, '2026-04-10') ON CONFLICT (book_id) DO UPDATE SET quantity = excluded.quantity, updated_on = excluded.updated_on;",
            'check_sql' => 'SELECT * FROM stock_levels ORDER BY book_id',
            'hint' => 'This time `SET quantity = excluded.quantity`, without adding. Don\'t use `INSERT OR REPLACE`; it works here, but the `ON CONFLICT` form is the safe habit.',
            'order_matters' => false,
        ],
        [
            'title' => 'Never go back in time',
            'prompt' => 'An old spreadsheet has these counts: book 4: 7 copies on 2026-03-28, book 2: 9 copies on 2026-04-05, book 5: 2 copies on 2026-04-02. Upsert them into `stock_levels`, but only replace an existing row if the spreadsheet\'s date is **newer** than the row\'s `updated_on`.',
            'starter_code' => null,
            'solution' => "INSERT INTO stock_levels (book_id, quantity, updated_on) VALUES (4, 7, '2026-03-28'), (2, 9, '2026-04-05'), (5, 2, '2026-04-02') ON CONFLICT (book_id) DO UPDATE SET quantity = excluded.quantity, updated_on = excluded.updated_on WHERE excluded.updated_on > stock_levels.updated_on;",
            'check_sql' => 'SELECT * FROM stock_levels ORDER BY book_id',
            'hint' => 'Add `WHERE excluded.updated_on > stock_levels.updated_on` after the `SET` list. Rows without a conflict are inserted whatever their date.',
            'order_matters' => false,
        ],
        [
            'title' => 'Welcome, Kofi',
            'prompt' => 'Add a customer: Kofi Mensah, email `kofi@example.com`, who joined on 2026-04-08, with no city. In the same statement, return his new `id` and his `joined_on`.',
            'starter_code' => null,
            'solution' => "INSERT INTO customers (name, email, joined_on) VALUES ('Kofi Mensah', 'kofi@example.com', '2026-04-08') RETURNING id, joined_on;",
            'hint' => 'End the `INSERT` with `RETURNING id, joined_on`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Supplier price update',
            'prompt' => 'Set the `price` of every book in `supplier_prices` to the price in its most recent line (the latest `sent_at`). Leave other books alone.',
            'starter_code' => null,
            'solution' => 'UPDATE books SET price = latest.price FROM (SELECT book_id, price FROM (SELECT book_id, price, ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY sent_at DESC) AS n FROM supplier_prices) WHERE n = 1) AS latest WHERE latest.book_id = books.id;',
            'check_sql' => 'SELECT id, price FROM books ORDER BY id',
            'hint' => 'Find each book\'s latest line with `ROW_NUMBER()` (lesson 4), then `UPDATE books SET price = latest.price FROM ( … ) AS latest WHERE latest.book_id = books.id`. A correlated subquery with `ORDER BY sent_at DESC LIMIT 1` works too.',
            'order_matters' => false,
        ],
    ],
];
