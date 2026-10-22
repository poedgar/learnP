<?php

// Lesson 2 of the SQL practice: online shop course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/shop.sql.

return [
    'slug' => 'customers',
    'title' => 'Customers',
    'summary' => 'Who keeps the shop going: customers with no orders, lifetime value, one-time and repeat buyers, recency and frequency, and the best customer in each city.',
    'starter_code' => "-- Orders per customer, including those with none. Try sorting by name.\nSELECT c.name, COUNT(o.id) AS orders\nFROM customers AS c\nLEFT JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled'\nGROUP BY c.id\nORDER BY orders DESC;",

    'quiz' => [
        [
            'question' => 'In a `LEFT JOIN` from customers to orders, why put `o.status <> \'cancelled\'` in the `ON` clause rather than in `WHERE`?',
            'options' => [
                'It runs faster',
                'In WHERE it would drop the customers with no matching order (their status is NULL), so they wouldn\'t show 0',
                'WHERE can\'t refer to the joined table',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => '`NULL <> \'cancelled\'` isn\'t true, so a WHERE filter throws away the unmatched rows the LEFT JOIN kept.',
        ],
        [
            'question' => 'How many customers have no orders (other than cancelled ones)?',
            'options' => ['2', '3', '4', '5'],
            'correct_option' => 2,
            'explanation' => 'Keira Walsh, Sven Larsson, Tess Hall and Victor Hugo. Keira\'s one order was cancelled.',
        ],
        [
            'question' => 'Why `COUNT(o.id)` rather than `COUNT(*)` after the LEFT JOIN?',
            'options' => [
                'COUNT(*) is slower',
                'A customer with no orders still has one joined row (full of NULLs), which COUNT(*) would count as 1',
                'COUNT(*) ignores the GROUP BY',
                'They are the same',
            ],
            'correct_option' => 1,
            'explanation' => '`COUNT(column)` skips NULLs, so it gives 0 for those customers.',
        ],
        [
            'question' => 'Who has spent the most, and how much?',
            'options' => ['Alice Brown, £155.00', 'Maya Patel, £179.00', 'Carmen Ruiz, £136.00', 'Grace Lee, £116.00'],
            'correct_option' => 1,
            'explanation' => 'Maya Patel\'s four orders add up to £179.00.',
        ],
        [
            'question' => 'How many customers have ordered exactly once?',
            'options' => ['4', '7', '9', '16'],
            'correct_option' => 1,
            'explanation' => '16 customers have orders: 7 of them have just one, and 9 have come back.',
        ],
        [
            'question' => 'What do the letters RFM stand for?',
            'options' => [
                'Revenue, Frequency, Margin',
                'Recency, Frequency, Monetary value',
                'Returns, Fees, Margin',
                'Rank, Filter, Merge',
            ],
            'correct_option' => 1,
            'explanation' => 'How recently, how often and how much a customer buys.',
        ],
        [
            'question' => 'What does `PARTITION BY city` do in `RANK() OVER (PARTITION BY city ORDER BY spent DESC)`?',
            'options' => [
                'Sorts the result by city',
                'Restarts the ranking for each city, so every city has its own number 1',
                'Groups the rows like GROUP BY, one per city',
                'Removes customers without a city',
            ],
            'correct_option' => 1,
            'explanation' => 'Unlike GROUP BY, the window keeps every row and just numbers them within each city.',
        ],
        [
            'question' => 'Who is the best customer in Leeds?',
            'options' => ['Bilal Aziz', 'Ines Costa', 'Carmen Ruiz', 'Maya Patel'],
            'correct_option' => 2,
            'explanation' => 'Carmen Ruiz spent £136.00, ahead of Ines Costa\'s £102.00. Maya Patel lives in Manchester.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Never bought',
            'prompt' => 'Show the `name` and `email` of customers who have no orders apart from cancelled ones.',
            'starter_code' => null,
            'solution' => "SELECT c.name, c.email FROM customers AS c WHERE NOT EXISTS (SELECT 1 FROM orders AS o WHERE o.customer_id = c.id AND o.status <> 'cancelled');",
            'hint' => '`NOT EXISTS` an order that wasn\'t cancelled, or a `LEFT JOIN` with the condition in `ON` and `WHERE o.id IS NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Once or more',
            'prompt' => 'Among customers with orders (not cancelled), show `kind` (`one-time` for exactly one order, `repeat` for more) and how many `customers` are of each kind.',
            'starter_code' => null,
            'solution' => "SELECT CASE WHEN orders = 1 THEN 'one-time' ELSE 'repeat' END AS kind, COUNT(*) AS customers FROM (SELECT customer_id, COUNT(*) AS orders FROM orders WHERE status <> 'cancelled' GROUP BY customer_id) GROUP BY kind;",
            'hint' => 'Count orders per customer in a subquery, then group those counts with a `CASE`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Last seen',
            'prompt' => 'For each customer with orders (not cancelled), show their `name`, the date of their `last_order` (`YYYY-MM-DD`) and `days_since` that date until 2026-07-01, as a whole number.',
            'starter_code' => null,
            'solution' => "SELECT c.name, date(MAX(o.placed_at)) AS last_order, CAST(julianday('2026-07-01') - julianday(date(MAX(o.placed_at))) AS INTEGER) AS days_since FROM customers AS c JOIN orders AS o ON o.customer_id = c.id WHERE o.status <> 'cancelled' GROUP BY c.id;",
            'hint' => '`MAX(placed_at)` per customer, `date()` it, and subtract `julianday()`s.',
            'order_matters' => false,
        ],
        [
            'title' => 'Average basket',
            'prompt' => 'For each customer with at least two orders (not cancelled), show their `name`, number of `orders` and `average_order`: the average order total in pounds, rounded to 2 decimal places.',
            'starter_code' => null,
            'solution' => "WITH totals AS (SELECT o.id, o.customer_id, SUM(oi.quantity * oi.unit_price_cents) AS total FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id) SELECT c.name, COUNT(*) AS orders, ROUND(AVG(t.total) / 100.0, 2) AS average_order FROM totals AS t JOIN customers AS c ON c.id = t.customer_id GROUP BY c.id HAVING COUNT(*) >= 2;",
            'hint' => 'Total each order first (a CTE), then group those totals by customer and keep groups with at least two.',
            'order_matters' => false,
        ],
        [
            'title' => 'Top of each city',
            'prompt' => 'For each city (leave out customers with no city), show the `city`, the `name` of the customer who spent the most there and how much they `spent` in pounds.',
            'starter_code' => null,
            'solution' => "WITH spend AS (SELECT c.city, c.name, SUM(oi.quantity * oi.unit_price_cents) AS spent, RANK() OVER (PARTITION BY c.city ORDER BY SUM(oi.quantity * oi.unit_price_cents) DESC) AS place FROM customers AS c JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled' JOIN order_items AS oi ON oi.order_id = o.id WHERE c.city IS NOT NULL GROUP BY c.id) SELECT city, name, spent / 100.0 AS spent FROM spend WHERE place = 1;",
            'hint' => 'Rank each customer\'s total within their city with `RANK() OVER (PARTITION BY city ...)`, then keep rank 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'The top three\'s share',
            'prompt' => 'In one row, show `top3_pct`: the percentage of all revenue (orders not cancelled) that came from the three customers who spent the most, rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => "WITH spend AS (SELECT o.customer_id, SUM(oi.quantity * oi.unit_price_cents) AS spent FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.customer_id) SELECT ROUND(100.0 * (SELECT SUM(spent) FROM (SELECT spent FROM spend ORDER BY spent DESC LIMIT 3)) / (SELECT SUM(spent) FROM spend), 1) AS top3_pct;",
            'hint' => 'Total spend per customer in a CTE. Sum the top three (`ORDER BY spent DESC LIMIT 3` in a subquery) and divide by the sum of all.',
            'order_matters' => false,
        ],
    ],
];
