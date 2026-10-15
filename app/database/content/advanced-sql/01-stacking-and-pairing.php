<?php

// Lesson 1 of the Advanced SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop_plus.sql.

return [
    'slug' => 'stacking-and-pairing',
    'title' => 'Stacking and pairing',
    'summary' => 'Stack query results with UNION, INTERSECT and EXCEPT, join a table to itself, and use CROSS, RIGHT and FULL OUTER joins.',
    'starter_code' => "-- Every city with a customer or a member of staff. Try UNION ALL, INTERSECT and EXCEPT too.\nSELECT city FROM customers\nUNION\nSELECT city FROM staff;",

    'quiz' => [
        [
            'question' => 'How many rows does this return? (There are 10 customers and 10 staff.)',
            'code' => "SELECT city FROM customers\nUNION ALL\nSELECT city FROM staff;",
            'options' => ['7', '10', '17', '20'],
            'correct_option' => 3,
            'explanation' => '`UNION ALL` keeps every row from both queries, duplicates included: 10 + 10 = 20. `UNION` would remove the duplicates and give 7.',
        ],
        [
            'question' => 'What is the result\'s column called?',
            'code' => "SELECT name AS person FROM customers\nUNION\nSELECT name AS employee FROM staff;",
            'options' => ['`person`', '`employee`', '`name`', 'There are two columns, one of each'],
            'correct_option' => 0,
            'explanation' => 'A stacked result takes its column names from the first query.',
        ],
        [
            'question' => 'Why does this query fail?',
            'code' => "SELECT name, city FROM customers\nUNION\nSELECT name FROM staff;",
            'options' => [
                'The column names are different',
                'The queries return different numbers of columns',
                '`UNION` needs an `ORDER BY`',
                '`staff` has no `city` column',
            ],
            'correct_option' => 1,
            'explanation' => 'Both queries must return the same number of columns, because columns are matched by position.',
        ],
        [
            'question' => 'Customers live in Leeds, London, Dublin, Manchester and Edinburgh; staff in London, Leeds and Bristol. What does `SELECT city FROM staff EXCEPT SELECT city FROM customers` return (ignoring empty cities)?',
            'options' => ['Bristol', 'Dublin, Manchester and Edinburgh', 'Leeds and London', 'Every city'],
            'correct_option' => 0,
            'explanation' => '`EXCEPT` keeps the rows of the first query that the second doesn\'t return. Only Bristol has staff but no customers.',
        ],
        [
            'question' => 'How many rows does this return?',
            'code' => "SELECT city FROM customers\nINTERSECT\nSELECT city FROM staff;",
            'options' => ['2', '3', '6', '0'],
            'correct_option' => 1,
            'explanation' => 'Leeds, London, and `NULL`: two customers and one member of staff have no city, and stacking operations count two `NULL`s as the same.',
        ],
        [
            'question' => 'Why does a self-join need table aliases?',
            'options' => [
                'Aliases make the query faster',
                'Without them, the database can\'t tell which copy of the table a column belongs to',
                'SQLite doesn\'t allow a table name to appear twice without them',
                'They\'re optional; they only make the query shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'Both copies have the same columns. Aliases like `s` and `m` say whether you mean the member of staff\'s `name` or the manager\'s.',
        ],
        [
            'question' => 'How many rows does `SELECT * FROM books CROSS JOIN customers` return? (16 books, 10 customers.)',
            'options' => ['16', '26', '160', '10'],
            'correct_option' => 2,
            'explanation' => 'A cross join pairs every row with every row: 16 × 10 = 160.',
        ],
        [
            'question' => 'What does a `FULL OUTER JOIN` keep?',
            'options' => [
                'Only rows that match on both sides',
                'Every row of the first table',
                'Every row of the second table',
                'Matched rows, plus unmatched rows from both sides',
            ],
            'correct_option' => 3,
            'explanation' => 'It\'s a `LEFT JOIN` and a `RIGHT JOIN` at once. Columns from the side without a match are `NULL`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Everyone\'s name',
            'prompt' => 'Make one alphabetical list of the `name` of every customer and every member of staff.',
            'starter_code' => null,
            'solution' => 'SELECT name FROM customers UNION SELECT name FROM staff ORDER BY name;',
            'hint' => 'Stack the two queries with `UNION` (or `UNION ALL`) and put a single `ORDER BY name` at the very end.',
            'order_matters' => true,
        ],
        [
            'title' => 'Wished for, never sold',
            'prompt' => 'Show the `title` of every book that is on someone\'s wishlist but has never been ordered.',
            'starter_code' => null,
            'solution' => 'SELECT title FROM books WHERE id IN (SELECT book_id FROM wishlist EXCEPT SELECT book_id FROM order_items);',
            'hint' => '`SELECT book_id FROM wishlist EXCEPT SELECT book_id FROM order_items` gives the ids. Use it inside `WHERE id IN ( … )` to get the titles.',
            'order_matters' => false,
        ],
        [
            'title' => 'Paid more than the boss',
            'prompt' => 'Find the staff who earn more than their manager. Show their `name`, their `salary`, and their manager\'s salary in a column named `manager_salary`.',
            'starter_code' => null,
            'solution' => 'SELECT s.name, s.salary, m.salary AS manager_salary FROM staff AS s JOIN staff AS m ON m.id = s.manager_id WHERE s.salary > m.salary;',
            'hint' => 'Join `staff AS s` to `staff AS m` on `m.id = s.manager_id`, then compare `s.salary > m.salary`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Wishes that came true',
            'prompt' => 'Some customers later bought a book from their own wishlist. Show each such customer\'s `name` and the book\'s `title`.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, b.title FROM (SELECT customer_id, book_id FROM wishlist INTERSECT SELECT o.customer_id, oi.book_id FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id) AS w JOIN customers AS c ON c.id = w.customer_id JOIN books AS b ON b.id = w.book_id;',
            'hint' => '`INTERSECT` the (customer, book) pairs from `wishlist` with the pairs from `orders` joined to `order_items`. Then join the result to `customers` and `books` for the names.',
            'order_matters' => false,
        ],
        [
            'title' => 'Every genre, every month',
            'prompt' => 'For a sales report with no gaps, list every combination of a `genre` from `books` and a `month` in which orders were placed (written like `2026-03`).',
            'starter_code' => null,
            'solution' => "SELECT g.genre, m.month FROM (SELECT DISTINCT genre FROM books) AS g CROSS JOIN (SELECT DISTINCT strftime('%Y-%m', ordered_on) AS month FROM orders) AS m;",
            'hint' => "Cross join `(SELECT DISTINCT genre FROM books)` with `(SELECT DISTINCT strftime('%Y-%m', ordered_on) AS month FROM orders)`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Cities everywhere',
            'prompt' => 'For every city (ignore people with no city), show the `city`, the number of `customers` there and the number of `staff` there, with 0 rather than an empty value. Include cities with only customers or only staff, and sort by city.',
            'starter_code' => null,
            'solution' => 'SELECT COALESCE(c.city, s.city) AS city, COALESCE(c.customers, 0) AS customers, COALESCE(s.staff, 0) AS staff FROM (SELECT city, COUNT(*) AS customers FROM customers WHERE city IS NOT NULL GROUP BY city) AS c FULL OUTER JOIN (SELECT city, COUNT(*) AS staff FROM staff WHERE city IS NOT NULL GROUP BY city) AS s ON s.city = c.city ORDER BY city;',
            'hint' => 'Count customers per city and staff per city in two subqueries, then `FULL OUTER JOIN` them on the city. `COALESCE` fills in the city name and the missing counts.',
            'order_matters' => true,
        ],
    ],
];
