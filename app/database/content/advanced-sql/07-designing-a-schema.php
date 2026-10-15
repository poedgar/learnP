<?php

// Lesson 7 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution; their
// results are compared. Table checks try rows against the learner's tables (INSERT OR
// IGNORE skips rows that break NOT NULL, UNIQUE or PRIMARY KEY) and read their keys.
// Quiz answers that quote numbers were checked against database/datasets/bookshop_plus.sql.

return [
    'slug' => 'designing-a-schema',
    'title' => 'Designing a schema',
    'summary' => 'Spot the problems of one big table, normalise it so each fact lives in one place, model many-to-many relationships, and choose what happens on delete.',
    'starter_code' => "-- Orders typed in from paper. What's wrong with this table?\nSELECT * FROM paper_orders;",

    'quiz' => [
        [
            'question' => 'What is the main problem with having `book_1`, `book_2` and `book_3` columns?',
            'options' => [
                'Text columns are slow',
                'A fourth book has nowhere to go, and every question about books must check three columns',
                'SQLite allows at most two text columns',
                'Nothing; it\'s the normal design',
            ],
            'correct_option' => 1,
            'explanation' => 'Repeating columns limit how many items fit and make queries awkward. The books belong in rows of another table.',
        ],
        [
            'question' => 'Paul\'s city is York on one paper order and Leeds on another. What causes this kind of problem?',
            'options' => [
                'A missing `NOT NULL`',
                'The same fact stored in more than one place',
                'Using `TEXT` for cities',
                'Too few rows',
            ],
            'correct_option' => 1,
            'explanation' => 'When a fact is stored twice, the copies can disagree. Stored once, in a customers table, it can\'t.',
        ],
        [
            'question' => 'Which design follows "first normal form"?',
            'options' => [
                "A `books` column holding `'Circe, Dune'`",
                'Columns `phone_1`, `phone_2` and `phone_3`',
                'A separate table with one row per order and book',
                'A JSON array of book ids in every order',
            ],
            'correct_option' => 2,
            'explanation' => 'First normal form means one value per cell and no repeating groups. Lists become rows in another table.',
        ],
        [
            'question' => 'Books can be in many categories, and categories hold many books. How is that stored?',
            'options' => [
                'A `category_id` column in `books`',
                'A `book_id` column in `categories`',
                'A junction table with one row per book–category pair',
                'It can\'t be stored in SQL',
            ],
            'correct_option' => 2,
            'explanation' => 'Neither side can point at just one row of the other, so a third table holds the pairs, with a foreign key to each side.',
        ],
        [
            'question' => 'What does `PRIMARY KEY (book_id, category_id)` on `book_categories` prevent?',
            'options' => [
                'A book being in more than one category',
                'A category holding more than one book',
                'The same book being put in the same category twice',
                'Deleting categories',
            ],
            'correct_option' => 2,
            'explanation' => 'The pair must be unique. Each book can still appear with many categories, and each category with many books.',
        ],
        [
            'question' => 'A note\'s `staff_id` is declared `REFERENCES staff (id) ON DELETE CASCADE`. What happens to the note when its member of staff is deleted?',
            'options' => [
                'The delete is refused',
                'The note is deleted too',
                'The note\'s `staff_id` becomes `NULL`',
                'Nothing; the note keeps pointing at the old id',
            ],
            'correct_option' => 1,
            'explanation' => '`CASCADE` passes the delete on to the rows that point at the deleted row.',
        ],
        [
            'question' => 'Which rule keeps the pointing rows but clears their link when the parent is deleted?',
            'options' => ['`ON DELETE CASCADE`', '`ON DELETE SET NULL`', '`ON DELETE RESTRICT`', 'No `ON DELETE` at all'],
            'correct_option' => 1,
            'explanation' => '`SET NULL` keeps the rows and sets their foreign key to `NULL`. Without an `ON DELETE`, the delete is refused.',
        ],
        [
            'question' => '`order_items.unit_price` holds a book\'s price, which is also in `books.price`. Is that a mistake?',
            'options' => [
                'Yes, it should be removed',
                'No: it records the price at the time of the sale, which is a different fact',
                'Yes, because prices must be in one table',
                'No, because SQLite needs it for joins',
            ],
            'correct_option' => 1,
            'explanation' => 'The shop\'s price can change; what a customer paid can\'t. The two columns store different facts.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Facts that disagree',
            'prompt' => 'In `paper_orders`, find the customers (by `customer_email`) who appear with more than one city. Show the email and the number of different `cities`.',
            'starter_code' => null,
            'solution' => 'SELECT customer_email, COUNT(DISTINCT customer_city) AS cities FROM paper_orders GROUP BY customer_email HAVING COUNT(DISTINCT customer_city) > 1;',
            'hint' => 'Group by `customer_email` and keep the groups where `COUNT(DISTINCT customer_city)` is more than 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'Books in two places',
            'prompt' => 'Show the `title` of every book that is in more than one category, with its number of `categories`.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, COUNT(*) AS categories FROM book_categories AS bc JOIN books AS b ON b.id = bc.book_id GROUP BY b.id HAVING COUNT(*) > 1;',
            'hint' => 'Each row of `book_categories` is one pairing. Group them by book and keep groups with more than one row.',
            'order_matters' => false,
        ],
        [
            'title' => 'Le Guin\'s shelves',
            'prompt' => 'List the `name` of each category that holds at least one book by Ursula K. Le Guin, once each.',
            'starter_code' => null,
            'solution' => "SELECT DISTINCT c.name FROM books AS b JOIN book_categories AS bc ON bc.book_id = b.id JOIN categories AS c ON c.id = bc.category_id WHERE b.author = 'Ursula K. Le Guin';",
            'hint' => 'Go from `books` through `book_categories` to `categories`, filter on the author, and use `DISTINCT`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Paper order lines, properly',
            'prompt' => 'Create a table `fair_order_lines` with an `order_ref` (`TEXT`) and a `title` (`TEXT`), both required, where the same title can\'t appear twice in one order. Fill it with every book from `paper_orders`, one row per book.',
            'starter_code' => "CREATE TABLE fair_order_lines (\n);\n\nINSERT INTO fair_order_lines (order_ref, title)\n…;",
            'solution' => 'CREATE TABLE fair_order_lines (order_ref TEXT NOT NULL, title TEXT NOT NULL, PRIMARY KEY (order_ref, title)); INSERT INTO fair_order_lines (order_ref, title) SELECT order_ref, book_1 FROM paper_orders UNION ALL SELECT order_ref, book_2 FROM paper_orders WHERE book_2 IS NOT NULL UNION ALL SELECT order_ref, book_3 FROM paper_orders WHERE book_3 IS NOT NULL;',
            'check_sql' => "INSERT OR IGNORE INTO fair_order_lines (order_ref, title) VALUES ('F1', 'Circe');\nINSERT OR IGNORE INTO fair_order_lines (order_ref, title) VALUES ('F9', NULL);\nINSERT OR IGNORE INTO fair_order_lines (order_ref, title) VALUES (NULL, 'Dune');\nSELECT order_ref, title FROM fair_order_lines ORDER BY order_ref, title",
            'hint' => 'Use `PRIMARY KEY (order_ref, title)` (or `UNIQUE`) after the columns, then `INSERT INTO … SELECT` the three book columns stacked with `UNION ALL`, skipping empty ones.',
            'order_matters' => false,
        ],
        [
            'title' => 'Tags',
            'prompt' => 'Books need free-form tags such as "cosy" or "dragons". Create `tags`, with an `id` (`INTEGER PRIMARY KEY`) and a required, unique `name` (`TEXT`), and a junction table `book_tags` linking a `book_id` (referring to a book) and a `tag_id` (referring to a tag), where a book can\'t get the same tag twice.',
            'starter_code' => null,
            'solution' => 'CREATE TABLE tags (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE); CREATE TABLE book_tags (book_id INTEGER NOT NULL REFERENCES books (id), tag_id INTEGER NOT NULL REFERENCES tags (id), PRIMARY KEY (book_id, tag_id));',
            'check_sql' => "INSERT OR IGNORE INTO tags (name) VALUES ('cosy');\nINSERT OR IGNORE INTO tags (name) VALUES ('cosy');\nINSERT OR IGNORE INTO tags (name) VALUES (NULL);\nINSERT OR IGNORE INTO tags (name) VALUES ('dragons');\nINSERT OR IGNORE INTO book_tags (book_id, tag_id) VALUES (4, 1);\nINSERT OR IGNORE INTO book_tags (book_id, tag_id) VALUES (4, 1);\nINSERT OR IGNORE INTO book_tags (book_id, tag_id) VALUES (4, 2);\nSELECT (SELECT group_concat(name, ', ') FROM (SELECT name FROM tags ORDER BY name)) AS tags, (SELECT COUNT(*) FROM book_tags) AS book_tags, (SELECT group_concat(lower(\"table\") || '.' || \"from\", ', ') FROM (SELECT \"table\", \"from\" FROM pragma_foreign_key_list('book_tags') ORDER BY \"from\")) AS foreign_keys",
            'hint' => '`name TEXT NOT NULL UNIQUE` in `tags`; in `book_tags`, two `REFERENCES` and `PRIMARY KEY (book_id, tag_id)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shifts that go with their owner',
            'prompt' => 'Create `staff_shifts` with an `id` (`INTEGER PRIMARY KEY`), a required `staff_id` that refers to a member of staff, and a required `shift_on` (`TEXT`). When a member of staff is deleted, their shifts must be deleted automatically.',
            'starter_code' => null,
            'solution' => 'CREATE TABLE staff_shifts (id INTEGER PRIMARY KEY, staff_id INTEGER NOT NULL REFERENCES staff (id) ON DELETE CASCADE, shift_on TEXT NOT NULL);',
            'check_sql' => "INSERT INTO staff_shifts (staff_id, shift_on) VALUES (10, '2026-04-10'), (10, '2026-04-11'), (4, '2026-04-10');\nDELETE FROM staff WHERE id = 10;\nSELECT staff_id, shift_on, (SELECT upper(on_delete) FROM pragma_foreign_key_list('staff_shifts')) AS on_delete FROM staff_shifts",
            'hint' => 'Add `ON DELETE CASCADE` after `REFERENCES staff (id)`.',
            'order_matters' => false,
        ],
    ],
];
