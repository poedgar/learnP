<?php

// Lesson 7 of the SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql tries rows against the learner's table (INSERT OR
// IGNORE skips rows that break NOT NULL, UNIQUE, PRIMARY KEY or CHECK) or reads
// the table's foreign keys, then selects what's left; the results are compared
// with the same check run after the solution.

return [
    'slug' => 'designing-tables',
    'title' => 'Designing tables',
    'summary' => 'Create your own tables with CREATE TABLE, and make them protect their data with keys, NOT NULL, DEFAULT, UNIQUE, CHECK and foreign keys.',
    'starter_code' => "CREATE TABLE publishers (\n    id      INTEGER PRIMARY KEY,\n    name    TEXT NOT NULL,\n    founded INTEGER\n);\n\nINSERT INTO publishers (name, founded) VALUES ('Bloomsbury', 1986);\nSELECT * FROM publishers;",

    'quiz' => [
        [
            'question' => 'What `id`s do these two rows get?',
            'code' => "CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT);\nINSERT INTO tags (name) VALUES ('classic');\nINSERT INTO tags (name) VALUES ('signed');",
            'options' => ['1 and 2', '0 and 1', '`NULL` and `NULL`', 'An error: `id` is missing'],
            'correct_option' => 0,
            'explanation' => 'An `INTEGER PRIMARY KEY` that you leave out is filled in with the next number, starting from 1.',
        ],
        [
            'question' => 'A column is declared `email TEXT UNIQUE`. How many rows can have a `NULL` email?',
            'options' => ['None', 'One', 'Two', 'As many as you like'],
            'correct_option' => 3,
            'explanation' => '`NULL` means "unknown", so two `NULL`s don\'t count as the same value. Add `NOT NULL` if every row needs an email.',
        ],
        [
            'question' => 'The column is `stars INTEGER CHECK (stars BETWEEN 1 AND 5)`. What happens when you insert a row with 6 stars?',
            'options' => [
                'The row is stored with 5 stars',
                'An error: the CHECK constraint fails',
                'The row is stored with `NULL` stars',
                'The row is stored as it is',
            ],
            'correct_option' => 1,
            'explanation' => 'A row that makes the `CHECK` condition false is refused, and the `INSERT` fails.',
        ],
        [
            'question' => 'What does SQLite do with this?',
            'code' => "CREATE TABLE notes (pages INTEGER);\nINSERT INTO notes VALUES ('lots');",
            'options' => [
                'It refuses the row',
                'It stores 0',
                'It stores the text \'lots\'',
                'It stores `NULL`',
            ],
            'correct_option' => 2,
            'explanation' => 'In SQLite, a column\'s type is a preference: text that doesn\'t look like a number is stored as text. A `STRICT` table would refuse it.',
        ],
        [
            'question' => 'When is a column\'s `DEFAULT` value used?',
            'options' => [
                'When the `INSERT` leaves the column out',
                'When the `INSERT` gives the column `NULL`',
                'Every time a row is updated',
                'Only for the first row',
            ],
            'correct_option' => 0,
            'explanation' => 'A default fills in a column you didn\'t mention. Writing `NULL` yourself stores `NULL` (or breaks `NOT NULL`).',
        ],
        [
            'question' => 'There is no book 99. What happens when you run this?',
            'code' => "CREATE TABLE loans (\n    customer_id INTEGER NOT NULL REFERENCES customers (id),\n    book_id     INTEGER NOT NULL REFERENCES books (id)\n);\nINSERT INTO loans (customer_id, book_id)\nVALUES (1, 99);",
            'options' => [
                'The loan is stored',
                'Book 99 is created',
                'The loan is stored with `NULL` as its `book_id`',
                'An error: FOREIGN KEY constraint failed',
            ],
            'correct_option' => 3,
            'explanation' => 'The foreign key says `book_id` must match an `id` in `books`. In this sandbox foreign keys are switched on, so the row is refused.',
        ],
        [
            'question' => 'What is the difference between `DROP TABLE books` and `DELETE FROM books`?',
            'options' => [
                'There is no difference',
                '`DROP TABLE` removes the table itself; `DELETE FROM` removes its rows and keeps the empty table',
                '`DELETE FROM` removes the table; `DROP TABLE` only removes rows',
                '`DROP TABLE` can be undone; `DELETE FROM` can\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'After `DELETE FROM books` you can still insert into `books`. After `DROP TABLE books`, there is no `books` table.',
        ],
        [
            'question' => 'A table has `PRIMARY KEY (customer_id, book_id)`. What does that allow?',
            'options' => [
                'Each customer can appear only once',
                'Each book can appear only once',
                'The same customer with different books, but never the same customer and book twice',
                'Any rows at all, as long as both columns are filled in',
            ],
            'correct_option' => 2,
            'explanation' => 'A primary key over two columns makes the *pair* unique. Each column on its own can repeat.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An authors table',
            'prompt' => 'Create a table `authors` with an `id` that is an `INTEGER PRIMARY KEY` and a `name` (`TEXT`) that is required.',
            'starter_code' => "CREATE TABLE authors (\n\n);",
            'solution' => "CREATE TABLE authors (\n    id   INTEGER PRIMARY KEY,\n    name TEXT NOT NULL\n);",
            'check_sql' => "DELETE FROM authors;\nINSERT OR IGNORE INTO authors (name) VALUES (NULL);\nINSERT INTO authors (name) VALUES ('Ursula K. Le Guin');\nINSERT INTO authors (name) VALUES ('Susanna Clarke');\nSELECT name, typeof(id) AS id_type FROM authors ORDER BY name",
            'hint' => 'List the columns inside the parentheses, separated by a comma. "Required" means `NOT NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Newsletter sign-ups',
            'prompt' => 'Create `newsletter_signups` with an `email` (`TEXT`) that is required and can\'t be signed up twice, and a `signed_up_on` (`TEXT`) that is required and defaults to `\'2026-04-08\'`.',
            'starter_code' => null,
            'solution' => "CREATE TABLE newsletter_signups (\n    email        TEXT NOT NULL UNIQUE,\n    signed_up_on TEXT NOT NULL DEFAULT '2026-04-08'\n);",
            'check_sql' => "DELETE FROM newsletter_signups;\nINSERT OR IGNORE INTO newsletter_signups (email) VALUES ('amara@example.com');\nINSERT OR IGNORE INTO newsletter_signups (email) VALUES ('amara@example.com');\nINSERT OR IGNORE INTO newsletter_signups (email) VALUES (NULL);\nINSERT OR IGNORE INTO newsletter_signups (email) VALUES ('ben.h@example.com');\nINSERT OR IGNORE INTO newsletter_signups (email, signed_up_on) VALUES ('cara@example.com', NULL);\nSELECT email, signed_up_on FROM newsletter_signups ORDER BY email",
            'hint' => '`email TEXT NOT NULL UNIQUE`, and `DEFAULT \'2026-04-08\'` on the date column.',
            'order_matters' => false,
        ],
        [
            'title' => 'Star ratings',
            'prompt' => 'Create `ratings` with an `id` (`INTEGER PRIMARY KEY`), a required `book_id` (`INTEGER`), and a required `stars` (`INTEGER`) that must be **between 1 and 5**.',
            'starter_code' => null,
            'solution' => "CREATE TABLE ratings (\n    id      INTEGER PRIMARY KEY,\n    book_id INTEGER NOT NULL,\n    stars   INTEGER NOT NULL CHECK (stars BETWEEN 1 AND 5)\n);",
            'check_sql' => "DELETE FROM ratings;\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (NULL, 4);\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (1, 0);\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (1, 3);\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (1, 5);\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (1, 6);\nINSERT OR IGNORE INTO ratings (book_id, stars) VALUES (1, NULL);\nSELECT stars FROM ratings ORDER BY stars",
            'hint' => 'Add `CHECK (stars BETWEEN 1 AND 5)` to the `stars` column, along with `NOT NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Reviews that point somewhere',
            'prompt' => 'Create `reviews` with an `id` (`INTEGER PRIMARY KEY`), a `book_id` that must refer to a book, a `customer_id` that must refer to a customer, and a required `body` (`TEXT`).',
            'starter_code' => null,
            'solution' => "CREATE TABLE reviews (\n    id          INTEGER PRIMARY KEY,\n    book_id     INTEGER NOT NULL REFERENCES books (id),\n    customer_id INTEGER NOT NULL REFERENCES customers (id),\n    body        TEXT NOT NULL\n);",
            'check_sql' => "SELECT lower(\"table\") AS refers_to, \"from\" AS column_name, (SELECT \"notnull\" FROM pragma_table_info('reviews') WHERE name = 'body') AS body_required FROM pragma_foreign_key_list('reviews') ORDER BY \"from\"",
            'hint' => 'Give each linking column `REFERENCES books (id)` or `REFERENCES customers (id)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'A phone number for every customer',
            'prompt' => 'Add an optional `phone` column (`TEXT`) to `customers`. Existing customers start with no phone number (`NULL`).',
            'starter_code' => null,
            'solution' => 'ALTER TABLE customers ADD COLUMN phone TEXT;',
            'check_sql' => "UPDATE customers SET phone = '0113 496 0000' WHERE id = 1;\nSELECT id, phone FROM customers WHERE id <= 2 ORDER BY id",
            'hint' => '`ALTER TABLE customers ADD COLUMN …`',
            'order_matters' => false,
        ],
        [
            'title' => 'Wish lists',
            'prompt' => 'Create `wishlist_items`: a `customer_id` that refers to a customer, a `book_id` that refers to a book, and a required `added_on` (`TEXT`) that defaults to `\'2026-04-08\'`. A customer can\'t add the same book twice.',
            'starter_code' => null,
            'solution' => "CREATE TABLE wishlist_items (\n    customer_id INTEGER NOT NULL REFERENCES customers (id),\n    book_id     INTEGER NOT NULL REFERENCES books (id),\n    added_on    TEXT    NOT NULL DEFAULT '2026-04-08',\n    PRIMARY KEY (customer_id, book_id)\n);",
            'check_sql' => "DELETE FROM wishlist_items;\nINSERT OR IGNORE INTO wishlist_items (customer_id, book_id) VALUES (1, 4);\nINSERT OR IGNORE INTO wishlist_items (customer_id, book_id) VALUES (1, 4);\nINSERT OR IGNORE INTO wishlist_items (customer_id, book_id, added_on) VALUES (1, 6, NULL);\nINSERT OR IGNORE INTO wishlist_items (customer_id, book_id) VALUES (2, 4);\nSELECT customer_id, book_id, added_on, (SELECT group_concat(lower(\"table\") || '.' || \"from\", ', ') FROM (SELECT \"table\", \"from\" FROM pragma_foreign_key_list('wishlist_items') ORDER BY \"from\")) AS foreign_keys FROM wishlist_items ORDER BY customer_id, book_id",
            'hint' => 'Two `REFERENCES`, a `DEFAULT` on `added_on`, and `PRIMARY KEY (customer_id, book_id)` (or `UNIQUE (customer_id, book_id)`) after the columns.',
            'order_matters' => false,
        ],
    ],
];
