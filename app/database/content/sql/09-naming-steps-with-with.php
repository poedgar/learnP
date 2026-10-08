<?php

// Lesson 9 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'naming-steps-with-with',
    'title' => 'Naming steps with WITH',
    'summary' => 'Build answers in readable, named steps with WITH, and generate rows such as a calendar with recursive queries.',
    'starter_code' => "-- Each book's copies sold, as a named step.\nWITH sales AS (\n    SELECT book_id, SUM(quantity) AS copies\n    FROM order_items\n    GROUP BY book_id\n)\nSELECT * FROM sales;",

    'quiz' => [
        [
            'question' => 'What does `WITH sales AS ( … )` do?',
            'options' => [
                'It creates a table called `sales` in the database',
                'It names a query, so the rest of the statement can use it like a table',
                'It renames the `sales` column',
                'It saves the query for later statements',
            ],
            'correct_option' => 1,
            'explanation' => 'A CTE exists only while its statement runs. Nothing is stored in the database.',
        ],
        [
            'question' => 'In `WITH a AS ( … ), b AS ( … )`, which can use which?',
            'options' => [
                '`b` can use `a`',
                'Neither can use the other',
                '`a` can use `b`, but not the other way round',
                'They can only be used in the final `SELECT`',
            ],
            'correct_option' => 0,
            'explanation' => 'Each step can use the steps written before it.',
        ],
        [
            'question' => 'What are the two parts of a recursive CTE, joined by `UNION ALL`?',
            'options' => [
                'A table and an index',
                'A `SELECT` and an `ORDER BY`',
                'A starting row, and a part that uses the CTE\'s own rows to make the next ones',
                'Two copies of the same query',
            ],
            'correct_option' => 2,
            'explanation' => 'The starting row comes first; the recursive part runs again and again on the newest rows until it makes no more.',
        ],
        [
            'question' => 'How many rows does this return?',
            'code' => "WITH RECURSIVE n(x) AS (\n    SELECT 1\n    UNION ALL\n    SELECT x + 2 FROM n WHERE x < 9\n)\nSELECT x FROM n;",
            'options' => ['4', '5', '9', '10'],
            'correct_option' => 1,
            'explanation' => 'It makes 1, 3, 5, 7 and 9. From 9, `x < 9` is false, so no more rows are made.',
        ],
        [
            'question' => 'What happens when a recursive CTE has no stopping condition?',
            'options' => [
                'It stops after 10 rows',
                'It returns no rows',
                'An error straight away',
                'It never finishes on its own; the sandbox stops it after 3 seconds',
            ],
            'correct_option' => 3,
            'explanation' => 'Each round makes a new row, forever. Always give the recursive part a `WHERE` that eventually becomes false.',
        ],
        [
            'question' => 'Why join orders onto a calendar with `LEFT JOIN` rather than `JOIN`?',
            'options' => [
                'It\'s faster',
                'To keep the days that have no orders',
                'Because dates can\'t be joined with `JOIN`',
                'To remove duplicate days',
            ],
            'correct_option' => 1,
            'explanation' => 'An inner join would drop every day without a matching order, which are exactly the days you\'re looking for.',
        ],
        [
            'question' => 'After the statement that uses it, what happens to a CTE?',
            'options' => [
                'It stays until you `DROP` it',
                'It becomes a view',
                'It is gone: it only exists while its statement runs',
                'It is saved as a table',
            ],
            'correct_option' => 2,
            'explanation' => 'Each statement must define the CTEs it uses.',
        ],
        [
            'question' => 'You count each calendar day\'s orders with `COUNT(*)` after a `LEFT JOIN`, and days with no orders show 1. What should you count instead?',
            'options' => ['`COUNT(d.day)`', '`COUNT(o.id)`', '`SUM(1)`', '`COUNT(DISTINCT d.day)`'],
            'correct_option' => 1,
            'explanation' => 'An empty day still has one joined row, with `NULL` order columns. `COUNT(o.id)` skips that `NULL` and gives 0.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Above-average spenders, step by step',
            'prompt' => 'Using `WITH`, find the customers who have spent more than the average customer (among those who have ordered). Show their `name` and total spent (`quantity * unit_price` summed, rounded to 2 places).',
            'starter_code' => "WITH spending AS (\n    \n)\nSELECT …;",
            'solution' => 'WITH spending AS (SELECT o.customer_id, SUM(oi.quantity * oi.unit_price) AS spent FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.customer_id) SELECT c.name, ROUND(s.spent, 2) AS spent FROM spending AS s JOIN customers AS c ON c.id = s.customer_id WHERE s.spent > (SELECT AVG(spent) FROM spending);',
            'hint' => 'One step, `spending`, with each customer\'s total. Use it twice: joined to `customers`, and as `(SELECT AVG(spent) FROM spending)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Monthly revenue',
            'prompt' => 'Show each month (written like `2026-03`) and its revenue (`quantity * unit_price` summed, rounded to 2 places), in month order.',
            'starter_code' => null,
            'solution' => "WITH lines AS (SELECT strftime('%Y-%m', o.ordered_on) AS month, oi.quantity * oi.unit_price AS amount FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id) SELECT month, ROUND(SUM(amount), 2) AS revenue FROM lines GROUP BY month ORDER BY month;",
            'hint' => 'Join `orders` to `order_items`, take each line\'s month and amount, then group by month.',
            'order_matters' => true,
        ],
        [
            'title' => 'Every day in April',
            'prompt' => 'Show every day of April 2026 (all 30 of them) with the number of orders placed that day, including days with **0**, earliest first.',
            'starter_code' => null,
            'solution' => "WITH RECURSIVE days(day) AS (SELECT '2026-04-01' UNION ALL SELECT date(day, '+1 day') FROM days WHERE day < '2026-04-30') SELECT d.day, COUNT(o.id) AS orders FROM days AS d LEFT JOIN orders AS o ON o.ordered_on = d.day GROUP BY d.day ORDER BY d.day;",
            'hint' => 'Build an April calendar with `WITH RECURSIVE`, `LEFT JOIN` the orders onto it, and count `o.id` per day.',
            'order_matters' => true,
        ],
        [
            'title' => 'Even numbers',
            'prompt' => 'Using a recursive `WITH`, list the first 10 even numbers, 2 to 20, smallest first.',
            'starter_code' => null,
            'solution' => 'WITH RECURSIVE evens(n) AS (SELECT 2 UNION ALL SELECT n + 2 FROM evens WHERE n < 20) SELECT n FROM evens;',
            'hint' => 'Start from 2 and add 2 each time, stopping when you reach 20.',
            'order_matters' => true,
        ],
        [
            'title' => 'Best-selling genre each month',
            'prompt' => 'For each month (written like `2026-03`), show the genre that sold the most **copies** that month, and how many. If genres tie, show each of them.',
            'starter_code' => null,
            'solution' => "WITH genre_months AS (SELECT strftime('%Y-%m', o.ordered_on) AS month, b.genre, SUM(oi.quantity) AS copies FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id JOIN books AS b ON b.id = oi.book_id GROUP BY month, b.genre) SELECT month, genre, copies FROM genre_months AS g WHERE copies = (SELECT MAX(copies) FROM genre_months WHERE month = g.month);",
            'hint' => 'One step with copies per month and genre. Keep the rows whose copies equal the month\'s maximum, using a correlated subquery on that step.',
            'order_matters' => false,
        ],
        [
            'title' => 'Share of the takings',
            'prompt' => 'For each customer who has ordered, show their `name` and their share of the shop\'s total revenue, as a percentage rounded to 1 place, biggest share first.',
            'starter_code' => null,
            'solution' => 'WITH spending AS (SELECT o.customer_id, SUM(oi.quantity * oi.unit_price) AS spent FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.customer_id), shop AS (SELECT SUM(spent) AS total FROM spending) SELECT c.name, ROUND(100.0 * s.spent / shop.total, 1) AS share FROM spending AS s JOIN customers AS c ON c.id = s.customer_id CROSS JOIN shop ORDER BY share DESC;',
            'hint' => 'Two steps: each customer\'s spending, then the shop\'s total from that. `CROSS JOIN` the total onto every customer and divide.',
            'order_matters' => true,
        ],
    ],
];
