<?php

// Lesson 9 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution; their
// results are compared. Checks change the data after the learner's SQL ran, to see that a
// view stays up to date, a trigger fires, or a generated column follows its inputs.

return [
    'slug' => 'views-triggers-and-generated-columns',
    'title' => 'Views, triggers and generated columns',
    'summary' => 'Save queries as views, make the database react to changes with triggers, and let it calculate columns for you with generated columns.',
    'starter_code' => "-- Save a query as a view, then use it like a table.\nCREATE VIEW order_totals AS\nSELECT o.id AS order_id, o.customer_id, SUM(oi.quantity * oi.unit_price) AS total\nFROM orders AS o\nJOIN order_items AS oi ON oi.order_id = o.id\nGROUP BY o.id;\n\nSELECT * FROM order_totals ORDER BY total DESC;",

    'quiz' => [
        [
            'question' => 'What does a view store?',
            'options' => [
                'A copy of the query\'s results',
                'The query itself, which runs again each time you use the view',
                'An index on the tables it reads',
                'Nothing; it disappears after one use',
            ],
            'correct_option' => 1,
            'explanation' => 'A view is a saved query. Its results are worked out afresh every time, so they\'re always current.',
        ],
        [
            'question' => 'You create the view `order_totals`, then insert a new order with some order lines. What does `SELECT * FROM order_totals` show?',
            'options' => [
                'The orders as they were when the view was created',
                'Every order, including the new one',
                'Only the new order',
                'An error, until the view is recreated',
            ],
            'correct_option' => 1,
            'explanation' => 'The view\'s query runs when you select from it, so it sees the new rows.',
        ],
        [
            'question' => 'What happens if you run `INSERT INTO order_totals …` against a view in SQLite?',
            'options' => [
                'The row is added to the view only',
                'The row is added to the tables underneath',
                'An error: a view can\'t be modified directly',
                'Nothing, silently',
            ],
            'correct_option' => 2,
            'explanation' => 'SQLite refuses to change a view. Change the tables it reads instead.',
        ],
        [
            'question' => 'Inside an `AFTER UPDATE` trigger on `books`, what are `OLD.price` and `NEW.price`?',
            'options' => [
                'The cheapest and the most expensive price',
                'The price before and after the update',
                'The prices of the previous and next book',
                'Both are the new price',
            ],
            'correct_option' => 1,
            'explanation' => '`OLD` is the row before the change and `NEW` the row after it. A `DELETE` trigger has only `OLD`, an `INSERT` trigger only `NEW`.',
        ],
        [
            'question' => 'What does `WHEN NEW.price <> OLD.price` do in a trigger?',
            'options' => [
                'It makes the trigger run only for rows whose price actually changed',
                'It changes the price back',
                'It runs the trigger twice',
                'It\'s required in every update trigger',
            ],
            'correct_option' => 0,
            'explanation' => '`WHEN` is a condition for each row. `SET price = price` would otherwise fire the trigger without any real change.',
        ],
        [
            'question' => 'A `BEFORE UPDATE` trigger runs `SELECT RAISE(ABORT, \'Stock cannot go below zero\')`. What happens to the update?',
            'options' => [
                'It goes ahead, with a warning',
                'It fails with that message, and none of its changes are kept',
                'Only the rows checked so far are updated',
                'The quantity is set to zero',
            ],
            'correct_option' => 1,
            'explanation' => '`RAISE(ABORT, …)` stops the whole statement with your message and undoes what it had changed.',
        ],
        [
            'question' => 'What is the difference between a `VIRTUAL` and a `STORED` generated column?',
            'options' => [
                'A `VIRTUAL` column can be written to',
                '`VIRTUAL` is calculated when read; `STORED` is calculated when the row is written and saved',
                '`STORED` columns are recalculated every second',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Both always hold the right value. `STORED` trades space for faster reads; SQLite can only add `VIRTUAL` columns with `ALTER TABLE`.',
        ],
        [
            'question' => 'A table has `line_total REAL GENERATED ALWAYS AS (quantity * unit_price)`. What happens to `line_total` when you update a row\'s `quantity`?',
            'options' => [
                'Nothing; you must update it yourself',
                'It follows automatically',
                'The update fails',
                'It becomes `NULL`',
            ],
            'correct_option' => 1,
            'explanation' => 'A generated column is always calculated from the row\'s current values. You can\'t write to it directly.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'What each customer spent',
            'prompt' => 'Create a view named `customer_spending` with each customer\'s `name`, their number of `orders`, and the total they have `spent` (quantity × unit price over all their order lines, rounded to 2 decimal places). Include only customers who have ordered.',
            'starter_code' => "CREATE VIEW customer_spending AS\n…;",
            'solution' => 'CREATE VIEW customer_spending AS SELECT c.name, COUNT(DISTINCT o.id) AS orders, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS spent FROM customers AS c JOIN orders AS o ON o.customer_id = c.id JOIN order_items AS oi ON oi.order_id = o.id GROUP BY c.id;',
            'check_sql' => 'SELECT * FROM customer_spending',
            'hint' => 'Join `customers`, `orders` and `order_items`, group by customer, and count `DISTINCT o.id`: joining the order lines repeats each order once per line.',
            'order_matters' => false,
        ],
        [
            'title' => 'Running low',
            'prompt' => 'Create a view named `low_stock` showing the `title` and `quantity` of every book with fewer than 3 copies in `stock_levels`.',
            'starter_code' => null,
            'solution' => 'CREATE VIEW low_stock AS SELECT b.title, s.quantity FROM stock_levels AS s JOIN books AS b ON b.id = s.book_id WHERE s.quantity < 3;',
            'check_sql' => "UPDATE stock_levels SET quantity = 1 WHERE book_id = 4;\nUPDATE stock_levels SET quantity = 9 WHERE book_id = 6;\nSELECT * FROM low_stock",
            'hint' => 'Join `stock_levels` to `books` and keep `quantity < 3`. Because a view runs its query every time, it will notice later changes to the stock.',
            'order_matters' => false,
        ],
        [
            'title' => 'Price changes',
            'prompt' => 'Create a table `price_changes` with `book_id`, `old_price` and `new_price`, and a trigger that adds a row to it whenever a book\'s price is updated to a **different** value.',
            'starter_code' => null,
            'solution' => 'CREATE TABLE price_changes (book_id INTEGER, old_price REAL, new_price REAL); CREATE TRIGGER books_price_changes AFTER UPDATE OF price ON books WHEN NEW.price <> OLD.price BEGIN INSERT INTO price_changes (book_id, old_price, new_price) VALUES (OLD.id, OLD.price, NEW.price); END;',
            'check_sql' => "UPDATE books SET price = 10 WHERE id IN (1, 2);\nUPDATE books SET price = 10 WHERE id = 1;\nUPDATE books SET title = 'Dune (Special Edition)' WHERE id = 6;\nSELECT book_id, old_price, new_price FROM price_changes ORDER BY rowid",
            'hint' => '`AFTER UPDATE OF price ON books WHEN NEW.price <> OLD.price`, inserting `OLD.id, OLD.price, NEW.price`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Off the shelf',
            'prompt' => 'Create a trigger so that whenever an order line is added to `order_items`, its quantity is taken off that book\'s `quantity` in `stock_levels`.',
            'starter_code' => null,
            'solution' => 'CREATE TRIGGER order_items_off_shelf AFTER INSERT ON order_items BEGIN UPDATE stock_levels SET quantity = quantity - NEW.quantity WHERE book_id = NEW.book_id; END;',
            'check_sql' => "INSERT INTO order_items (order_id, book_id, quantity, unit_price) VALUES (20, 6, 1, 19.99);\nINSERT INTO order_items (order_id, book_id, quantity, unit_price) VALUES (19, 4, 2, 14.99);\nSELECT book_id, quantity FROM stock_levels ORDER BY book_id",
            'hint' => '`AFTER INSERT ON order_items`, then `UPDATE stock_levels SET quantity = quantity - NEW.quantity WHERE book_id = NEW.book_id`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Keep what was deleted',
            'prompt' => 'Create a table `deleted_reviews` with `id`, `book_id` and `body`, and a trigger that copies each review into it when the review is deleted.',
            'starter_code' => null,
            'solution' => 'CREATE TABLE deleted_reviews (id INTEGER, book_id INTEGER, body TEXT); CREATE TRIGGER reviews_deleted AFTER DELETE ON reviews BEGIN INSERT INTO deleted_reviews (id, book_id, body) VALUES (OLD.id, OLD.book_id, OLD.body); END;',
            'check_sql' => "DELETE FROM reviews WHERE rating = 2 OR id = 6;\nSELECT id, book_id, body FROM deleted_reviews ORDER BY id",
            'hint' => 'In a `DELETE` trigger, `OLD` is the row being deleted.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shelf labels, generated',
            'prompt' => 'Add a generated column `label` to `books` that is always the title followed by the year in brackets, like `Dune (1965)`.',
            'starter_code' => null,
            'solution' => "ALTER TABLE books ADD COLUMN label TEXT GENERATED ALWAYS AS (title || ' (' || published_year || ')') VIRTUAL;",
            'check_sql' => "UPDATE books SET title = 'Nineteen Eighty-Four: Centenary Edition' WHERE id = 3;\nSELECT id, label FROM books ORDER BY id",
            'hint' => "`ALTER TABLE books ADD COLUMN label TEXT GENERATED ALWAYS AS ( … ) VIRTUAL;`, joining the title, `' ('`, the year and `')'` with `||`.",
            'order_matters' => false,
        ],
    ],
];
