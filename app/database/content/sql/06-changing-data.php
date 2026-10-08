<?php

// Lesson 6 of the SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution;
// their results are compared. Quiz answers that quote numbers were checked
// against database/datasets/bookshop.sql.

return [
    'slug' => 'changing-data',
    'title' => 'Changing data',
    'summary' => 'Add rows with INSERT, change them with UPDATE, remove them with DELETE, and group changes into transactions.',
    'starter_code' => "-- Change a customer's city, then look at the result.\nUPDATE customers\nSET city = 'Leeds'\nWHERE id = 4;\n\nSELECT id, name, city FROM customers;",

    'quiz' => [
        [
            'question' => 'What does this statement do?',
            'code' => "UPDATE books\nSET price = 5;",
            'options' => [
                'Nothing, because there is no `WHERE`',
                'It changes the price of the first book',
                'It sets every book\'s price to 5',
                'It adds a new book that costs 5',
            ],
            'correct_option' => 2,
            'explanation' => 'Without a `WHERE`, `UPDATE` changes every row in the table.',
        ],
        [
            'question' => 'The bookshop has customers 1 to 10. What `id` does this new customer get?',
            'code' => "INSERT INTO customers (name, email, joined_on)\nVALUES ('Tomasz Nowak', 'tomasz@example.com', '2026-04-09');",
            'options' => ['0', '10', '11', '`NULL`'],
            'correct_option' => 2,
            'explanation' => 'When you leave out `id`, the database gives the new row the next number, 11.',
        ],
        [
            'question' => 'What happens when you run this?',
            'code' => "DELETE FROM customers\nWHERE id = 1;",
            'options' => [
                'Customer 1 is deleted',
                'Customer 1 and all their orders are deleted',
                'Nothing, because customer 1 doesn\'t exist',
                'An error: customer 1 still has orders, and the foreign key protects them',
            ],
            'correct_option' => 3,
            'explanation' => 'Amara Okafor\'s orders point at her through `orders.customer_id`. Deleting her would leave them pointing at nothing, so the database refuses.',
        ],
        [
            'question' => 'How many rows does this statement change?',
            'code' => "UPDATE orders\nSET shipped_on = '2026-04-08'\nWHERE shipped_on IS NULL;",
            'options' => ['1', '4', '16', '20'],
            'correct_option' => 1,
            'explanation' => 'Four orders (17 to 20) haven\'t shipped, so four rows match the `WHERE`.',
        ],
        [
            'question' => 'What does `ROLLBACK` do?',
            'options' => [
                'It undoes every change made since `BEGIN`',
                'It undoes only the last statement',
                'It keeps the changes made since `BEGIN`',
                'It deletes the table',
            ],
            'correct_option' => 0,
            'explanation' => '`ROLLBACK` throws away the whole transaction. `COMMIT` is the one that keeps it.',
        ],
        [
            'question' => 'Which statement removes rows but keeps the table?',
            'options' => ['`DROP TABLE`', '`DELETE FROM`', '`REMOVE FROM`', '`ALTER TABLE`'],
            'correct_option' => 1,
            'explanation' => '`DELETE FROM` removes rows. `DROP TABLE` removes the table itself, and `REMOVE` isn\'t SQL.',
        ],
        [
            'question' => 'How many rows does this statement add?',
            'code' => "INSERT INTO order_items (order_id, book_id, quantity, unit_price)\nSELECT id, 12, 1, 0\nFROM orders\nWHERE shipped_on IS NULL;",
            'options' => ['1', '4', '20', '36'],
            'correct_option' => 1,
            'explanation' => 'The `SELECT` returns one row per unshipped order, and there are four, so four order lines are added.',
        ],
        [
            'question' => 'Why run a `SELECT` with the same `WHERE` before an `UPDATE` or `DELETE`?',
            'options' => [
                'Because `UPDATE` needs a `SELECT` first to work',
                'To make the change faster',
                'To start a transaction',
                'To see exactly which rows the change will affect before you make it',
            ],
            'correct_option' => 3,
            'explanation' => 'A `SELECT` changes nothing, so it\'s a safe way to check your `WHERE` matches the rows you mean.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A new book',
            'prompt' => 'The shop has a new book: *Piranesi* by Susanna Clarke, a Fantasy book published in 2020, with 272 pages, priced at 12.99. Add it to `books`.',
            'starter_code' => "INSERT INTO books (title, author, genre, published_year, pages, price)\nVALUES ();",
            'solution' => "INSERT INTO books (title, author, genre, published_year, pages, price) VALUES ('Piranesi', 'Susanna Clarke', 'Fantasy', 2020, 272, 12.99);",
            'check_sql' => 'SELECT * FROM books ORDER BY id',
            'hint' => 'Put the values inside `VALUES ( … )` in the same order as the columns. Text goes in single quotes; `id` and `in_stock` fill themselves in.',
            'order_matters' => false,
        ],
        [
            'title' => 'A customer without a city',
            'prompt' => 'Add a customer: Kofi Mensah, email `kofi@example.com`, who joined on 2026-04-08. He didn\'t give a city, so leave it empty (`NULL`).',
            'starter_code' => null,
            'solution' => "INSERT INTO customers (name, email, joined_on) VALUES ('Kofi Mensah', 'kofi@example.com', '2026-04-08');",
            'check_sql' => 'SELECT * FROM customers ORDER BY id',
            'hint' => 'Leave `city` out of the column list (or give it `NULL`). Don\'t use an empty string `\'\'`; that isn\'t the same as `NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Order 17 is on its way',
            'prompt' => 'Order 17 shipped on 2026-04-08. Record that in `orders`, and change nothing else.',
            'starter_code' => null,
            'solution' => "UPDATE orders SET shipped_on = '2026-04-08' WHERE id = 17;",
            'check_sql' => 'SELECT id, shipped_on FROM orders ORDER BY id',
            'hint' => '`UPDATE orders SET shipped_on = … WHERE id = 17`. Without the `WHERE`, every order would be marked as shipped.',
            'order_matters' => false,
        ],
        [
            'title' => 'Horror sale',
            'prompt' => 'Take **10% off** the price of every horror book.',
            'starter_code' => null,
            'solution' => "UPDATE books SET price = price * 0.9 WHERE genre = 'Horror';",
            'check_sql' => 'SELECT id, ROUND(price, 2) FROM books ORDER BY id',
            'hint' => '10% off means paying 90%: `SET price = price * 0.9`, for the horror books only.',
            'order_matters' => false,
        ],
        [
            'title' => 'Clear out dead stock',
            'prompt' => 'Delete every book that is **out of stock** (`in_stock = 0`) and has **never been ordered**.',
            'starter_code' => null,
            'solution' => 'DELETE FROM books WHERE in_stock = 0 AND NOT EXISTS (SELECT 1 FROM order_items AS oi WHERE oi.book_id = books.id);',
            'check_sql' => 'SELECT id FROM books ORDER BY id',
            'hint' => 'Combine `in_stock = 0` with `NOT EXISTS` (or `NOT IN`) on `order_items`. If you get a foreign key error, you are trying to delete a book that has been ordered.',
            'order_matters' => false,
        ],
        [
            'title' => 'A cancelled order',
            'prompt' => 'Hiroshi Tanaka has cancelled order 17. Remove the order **and** its order lines.',
            'starter_code' => null,
            'solution' => "DELETE FROM order_items WHERE order_id = 17;\nDELETE FROM orders WHERE id = 17;",
            'check_sql' => 'SELECT o.id, COUNT(oi.book_id) AS lines FROM orders AS o LEFT JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.id ORDER BY o.id',
            'hint' => 'Two `DELETE`s. The order lines point at the order, so delete them first; the other way round, the foreign key stops you.',
            'order_matters' => false,
        ],
    ],
];
