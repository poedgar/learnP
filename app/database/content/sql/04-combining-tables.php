<?php

// Lesson 4 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'combining-tables',
    'title' => 'Combining tables',
    'summary' => 'Combine tables with JOIN, find rows with no match using LEFT JOIN, and summarise joined data with GROUP BY.',
    'starter_code' => "-- Each order with the name of the customer who placed it.\nSELECT o.id, o.ordered_on, c.name\nFROM orders AS o\nJOIN customers AS c ON c.id = o.customer_id;",

    'quiz' => [
        [
            'question' => 'What does `orders.customer_id` hold?',
            'options' => [
                'The customer\'s name',
                'The `id` of the customer who placed the order',
                'How many orders the customer has placed',
                'A copy of every customer\'s `id`',
            ],
            'correct_option' => 1,
            'explanation' => '`customer_id` is a foreign key: it holds the primary key of one row in `customers`, the customer who placed the order.',
        ],
        [
            'question' => 'How many rows does this query return?',
            'code' => "SELECT c.name, o.id\nFROM customers AS c\nJOIN orders AS o ON o.customer_id = c.id;",
            'options' => ['10', '20', '22', '200'],
            'correct_option' => 1,
            'explanation' => 'Every one of the 20 orders matches exactly one customer. The two customers with no orders have nothing to pair with, so an inner join drops them.',
        ],
        [
            'question' => 'And with a `LEFT JOIN`?',
            'code' => "SELECT c.name, o.id\nFROM customers AS c\nLEFT JOIN orders AS o ON o.customer_id = c.id;",
            'options' => ['10', '20', '22', '200'],
            'correct_option' => 2,
            'explanation' => 'The 20 matched rows, plus one row for each of the two customers with no orders, with `NULL` in `o.id`.',
        ],
        [
            'question' => 'What happens when you run this query?',
            'code' => "SELECT id, name\nFROM orders\nJOIN customers ON customers.id = orders.customer_id;",
            'options' => [
                'It lists each order\'s id with the customer\'s name',
                'It lists each customer\'s id with their name',
                'It returns no rows',
                'An error: ambiguous column name: id',
            ],
            'correct_option' => 3,
            'explanation' => 'Both tables have an `id` column, so the database can\'t tell which you mean. Write `orders.id`, or use aliases such as `o.id`.',
        ],
        [
            'question' => 'You forgot the `ON`. How many rows does this return in SQLite?',
            'code' => "SELECT COUNT(*)\nFROM customers\nJOIN orders;",
            'options' => ['10', '20', '30', '200'],
            'correct_option' => 3,
            'explanation' => 'Without `ON`, every customer is paired with every order: 10 × 20 = 200 rows. Some databases refuse to run a `JOIN` without `ON`.',
        ],
        [
            'question' => 'Which condition completes this query so it finds books that have never been sold?',
            'code' => "SELECT b.title\nFROM books AS b\nLEFT JOIN order_items AS oi ON oi.book_id = b.id\nWHERE …;",
            'options' => ['`oi.book_id = NULL`', '`oi.book_id IS NULL`', '`b.id IS NULL`', '`oi.quantity = 0`'],
            'correct_option' => 1,
            'explanation' => 'A book with no sales gets `NULL` in every `oi` column. `= NULL` never matches, `b.id` is never `NULL`, and there are no rows with a quantity of 0.',
        ],
        [
            'question' => 'Why should revenue use `order_items.unit_price` rather than `books.price`?',
            'options' => [
                '`unit_price` is faster to read',
                '`books.price` is today\'s price; `unit_price` is what the customer actually paid',
                'They are always the same, so it doesn\'t matter',
                '`books.price` can be `NULL`',
            ],
            'correct_option' => 1,
            'explanation' => 'Prices change and sales happen. Two order lines here were sold below today\'s price, so using `books.price` overstates revenue by 10.00.',
        ],
        [
            'question' => 'In `FROM orders AS o`, what is `o`?',
            'options' => [
                'A new table',
                'A copy of the orders data',
                'A short name for `orders`, used only in this query',
                'The first column of `orders`',
            ],
            'correct_option' => 2,
            'explanation' => 'An alias renames the table for the rest of the query, so you can write `o.id` instead of `orders.id`. Nothing in the database changes.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Who placed each order',
            'prompt' => 'For every order, show its `id`, its `ordered_on` date and the `name` of the customer who placed it.',
            'starter_code' => "SELECT o.id, o.ordered_on, c.name\nFROM orders AS o\n",
            'solution' => 'SELECT o.id, o.ordered_on, c.name FROM orders AS o JOIN customers AS c ON c.id = o.customer_id;',
            'hint' => 'Join `customers` on `c.id = o.customer_id`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Siobhan\'s bookshelf',
            'prompt' => 'List the `title` of every book Siobhan O\'Brien has bought.',
            'starter_code' => null,
            'solution' => "SELECT b.title FROM customers AS c JOIN orders AS o ON o.customer_id = c.id JOIN order_items AS oi ON oi.order_id = o.id JOIN books AS b ON b.id = oi.book_id WHERE c.name = 'Siobhan O''Brien';",
            'hint' => "Follow the links from `customers` to `orders` to `order_items` to `books`. Inside quotes, write her apostrophe twice: `'Siobhan O''Brien'`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Books nobody has bought',
            'prompt' => 'List the `title` of every book that has **never** been ordered.',
            'starter_code' => null,
            'solution' => 'SELECT b.title FROM books AS b LEFT JOIN order_items AS oi ON oi.book_id = b.id WHERE oi.book_id IS NULL;',
            'hint' => '`LEFT JOIN` from `books` to `order_items`, then keep the rows where the `order_items` side is `NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Orders per customer',
            'prompt' => 'List every customer\'s `name` with the number of orders they have placed. Customers who have never ordered should appear with **0**.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, COUNT(o.id) AS orders FROM customers AS c LEFT JOIN orders AS o ON o.customer_id = c.id GROUP BY c.id, c.name;',
            'hint' => 'A `LEFT JOIN` keeps customers with no orders. Count `o.id`, not `*`: `COUNT(*)` would count their empty row as 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'Best customers',
            'prompt' => 'Show each customer\'s `name` and how much they have spent in total (the sum of `quantity * unit_price` over all their orders, rounded to 2 places), biggest spender first. Leave out customers who haven\'t ordered.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS spent FROM customers AS c JOIN orders AS o ON o.customer_id = c.id JOIN order_items AS oi ON oi.order_id = o.id GROUP BY c.id, c.name ORDER BY spent DESC;',
            'hint' => 'Join `customers`, `orders` and `order_items`, group by customer, and sort by the total, descending.',
            'order_matters' => true,
        ],
        [
            'title' => 'Loyal customers',
            'prompt' => 'List the `name` of every customer who has placed **at least 3 orders**, with how many orders they placed. Put the most orders first, and break ties alphabetically by name.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, COUNT(o.id) AS orders FROM customers AS c JOIN orders AS o ON o.customer_id = c.id GROUP BY c.id, c.name HAVING COUNT(o.id) >= 3 ORDER BY orders DESC, c.name;',
            'hint' => 'Join, group by customer, keep groups with `HAVING COUNT(o.id) >= 3`, then `ORDER BY orders DESC, c.name`.',
            'order_matters' => true,
        ],
    ],
];
