<?php

// Lesson 5 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'queries-inside-queries',
    'title' => 'Queries inside queries',
    'summary' => 'Use one query\'s answer inside another: compare with a single value, check lists with IN and EXISTS, and query the result of a query.',
    'starter_code' => "-- Books that cost more than the average price.\nSELECT title, price\nFROM books\nWHERE price > (SELECT AVG(price) FROM books);",

    'quiz' => [
        [
            'question' => 'How many books does this query return?',
            'code' => "SELECT title\nFROM books\nWHERE price > (SELECT AVG(price) FROM books);",
            'options' => ['5', '6', '7', '8'],
            'correct_option' => 2,
            'explanation' => 'The average price is 14.62…, and 7 books cost more: from *The Hobbit* at 14.99 up to *Jonathan Strange & Mr Norrell* at 22.00.',
        ],
        [
            'question' => 'SQLite runs this query without an error. What does it return?',
            'code' => "SELECT title\nFROM books\nWHERE price = (SELECT price FROM books WHERE genre = 'Horror');",
            'options' => [
                'Every horror book',
                'One book: SQLite uses only the first price the subquery returns',
                'No rows',
                'Every book that costs the same as any horror book',
            ],
            'correct_option' => 1,
            'explanation' => 'With `=`, the subquery should return one value. It returns three, so SQLite quietly takes the first (8.50) and you get just *Frankenstein*. Use `IN` when a subquery returns a list.',
        ],
        [
            'question' => 'Daniel Kim has no city. What does this query return?',
            'code' => "SELECT name\nFROM customers\nWHERE city NOT IN (SELECT city FROM customers WHERE name LIKE 'D%');",
            'options' => [
                'Every customer except Daniel Kim',
                'Every customer with a city',
                'No rows, because the subquery returns a NULL',
                'An error',
            ],
            'correct_option' => 2,
            'explanation' => 'The subquery returns Daniel\'s city, `NULL`. A comparison with `NULL` is never true, so `NOT IN` keeps no rows. `NOT EXISTS` doesn\'t have this problem.',
        ],
        [
            'question' => 'When is `EXISTS (subquery)` true?',
            'options' => [
                'When the subquery returns at least one row',
                'When the subquery\'s first value is true',
                'When the subquery\'s value isn\'t NULL',
                'When the subquery returns exactly one row',
            ],
            'correct_option' => 0,
            'explanation' => '`EXISTS` only asks whether there is a row. What the row contains doesn\'t matter, which is why `SELECT 1` is the usual choice inside it.',
        ],
        [
            'question' => 'What makes a subquery *correlated*?',
            'options' => [
                'It uses an aggregate function',
                'It appears in the `FROM` clause',
                'It returns more than one row',
                'It refers to a column of the outer query, so it is worked out for each outer row',
            ],
            'correct_option' => 3,
            'explanation' => 'A correlated subquery uses a value from the current outer row, such as `b.genre`, so its answer can differ from row to row.',
        ],
        [
            'question' => 'What does this query return?',
            'code' => "SELECT MAX(total)\nFROM (\n    SELECT order_id, SUM(quantity * unit_price) AS total\n    FROM order_items\n    GROUP BY order_id\n) AS order_totals;",
            'options' => ['22.00', '49.75', '55.48', '593.24'],
            'correct_option' => 2,
            'explanation' => 'The inner query gives each order\'s total; the outer one picks the largest. Order 11 (*The Name of the Wind*, *The Fellowship of the Ring* and *The Hobbit*) comes to 55.48.',
        ],
        [
            'question' => 'A subquery returns many order IDs. Which operator compares a column with all of them?',
            'options' => ['`=`', '`IN`', '`LIKE`', '`BETWEEN`'],
            'correct_option' => 1,
            'explanation' => '`IN (subquery)` keeps rows whose value appears anywhere in the subquery\'s results.',
        ],
        [
            'question' => 'Why can\'t `WHERE customer_id IN (SELECT id FROM customers WHERE …)` show the customer\'s name in the result?',
            'options' => [
                'Because `IN` only works with numbers',
                'Because subqueries can\'t read text columns',
                'Because the subquery would return too many rows',
                'Because a subquery in `WHERE` only decides which rows to keep; to show its columns, use a join or a subquery in `SELECT`',
            ],
            'correct_option' => 3,
            'explanation' => 'Nothing from a `WHERE` subquery reaches the result. When you need columns from the other table, join it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Longer than average',
            'prompt' => 'Show the `title` and `pages` of every book with **more pages than the average** book.',
            'starter_code' => "SELECT title, pages\nFROM books\nWHERE pages > ();",
            'solution' => 'SELECT title, pages FROM books WHERE pages > (SELECT AVG(pages) FROM books);',
            'hint' => 'Put `SELECT AVG(pages) FROM books` inside the parentheses.',
            'order_matters' => false,
        ],
        [
            'title' => 'January customers',
            'prompt' => 'List the `name` of every customer who placed an order in **January 2026**. Use a subquery with `IN`.',
            'starter_code' => null,
            'solution' => "SELECT name FROM customers WHERE id IN (SELECT customer_id FROM orders WHERE ordered_on BETWEEN '2026-01-01' AND '2026-01-31');",
            'hint' => 'The subquery picks `customer_id` from `orders` with a date range; the outer query keeps customers whose `id` is in it.',
            'order_matters' => false,
        ],
        [
            'title' => 'Nothing waiting to ship',
            'prompt' => 'List the `name` of every customer with **no unshipped orders**. Customers who have never ordered count too.',
            'starter_code' => null,
            'solution' => 'SELECT name FROM customers AS c WHERE NOT EXISTS (SELECT 1 FROM orders AS o WHERE o.customer_id = c.id AND o.shipped_on IS NULL);',
            'hint' => 'Use `NOT EXISTS` with a correlated subquery that looks for this customer\'s orders where `shipped_on IS NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Top of each genre',
            'prompt' => 'For each genre, show the `genre`, `title` and `price` of its **most expensive** book.',
            'starter_code' => null,
            'solution' => 'SELECT genre, title, price FROM books AS b WHERE price = (SELECT MAX(price) FROM books AS g WHERE g.genre = b.genre);',
            'hint' => 'Compare each book\'s price with a correlated subquery: the `MAX(price)` of books in the same genre.',
            'order_matters' => false,
        ],
        [
            'title' => 'Big baskets',
            'prompt' => 'Each order has a total: the sum of `quantity * unit_price` over its lines. Show the `name` of every customer whose **average order total is above 30**, with that average rounded to 2 places.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, ROUND(AVG(t.total), 2) AS average_order FROM customers AS c JOIN orders AS o ON o.customer_id = c.id JOIN (SELECT order_id, SUM(quantity * unit_price) AS total FROM order_items GROUP BY order_id) AS t ON t.order_id = o.id GROUP BY c.id, c.name HAVING AVG(t.total) > 30;',
            'hint' => 'Work out each order\'s total in a subquery in `FROM`, join it to `orders` and `customers`, then group by customer.',
            'order_matters' => false,
        ],
        [
            'title' => 'Above-average spenders',
            'prompt' => 'Among customers who have ordered, find those who have spent **more than the average customer**. Show their `name` and total spent (the sum of `quantity * unit_price`, rounded to 2 places), biggest spender first.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, ROUND(s.spent, 2) AS spent FROM customers AS c JOIN (SELECT o.customer_id, SUM(oi.quantity * oi.unit_price) AS spent FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.customer_id) AS s ON s.customer_id = c.id WHERE s.spent > (SELECT AVG(spent) FROM (SELECT SUM(oi.quantity * oi.unit_price) AS spent FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.customer_id) AS per_customer) ORDER BY s.spent DESC;',
            'hint' => 'Total each customer\'s spending in a subquery in `FROM`. The average customer\'s spending is the `AVG` of that same subquery, used as a single value in `WHERE`.',
            'order_matters' => true,
        ],
    ],
];
