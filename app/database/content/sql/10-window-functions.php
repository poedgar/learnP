<?php

// Lesson 10 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'window-functions',
    'title' => 'Window functions',
    'summary' => 'Compare each row with its group using OVER and PARTITION BY: rankings, running totals, and the rows before and after.',
    'starter_code' => "-- Each book next to its genre's average price.\nSELECT title, genre, price,\n       ROUND(AVG(price) OVER (PARTITION BY genre), 2) AS genre_average\nFROM books;",

    'quiz' => [
        [
            'question' => 'How many rows does this query return?',
            'code' => "SELECT title, AVG(price) OVER (PARTITION BY genre)\nFROM books;",
            'options' => ['1', '5', '16', 'An error'],
            'correct_option' => 2,
            'explanation' => 'Unlike `GROUP BY`, a window keeps every row: all 16 books, each with its genre\'s average.',
        ],
        [
            'question' => 'Three scores are 10, 10 and 8. What does `RANK() OVER (ORDER BY score DESC)` give them?',
            'options' => ['1, 2, 3', '1, 1, 2', '1, 1, 3', '1, 2, 2'],
            'correct_option' => 2,
            'explanation' => '`RANK` gives tied rows the same number and then skips. `DENSE_RANK` would give 1, 1, 2, and `ROW_NUMBER` 1, 2, 3.',
        ],
        [
            'question' => 'What does `PARTITION BY customer_id` do inside `OVER`?',
            'options' => [
                'It sorts the rows by customer',
                'It keeps only one row per customer',
                'It splits the rows into a separate window for each customer',
                'It removes customers with no orders',
            ],
            'correct_option' => 2,
            'explanation' => 'Each customer\'s rows form their own window, so numbering and totals restart for every customer.',
        ],
        [
            'question' => 'What does `LAG(ordered_on) OVER (ORDER BY ordered_on)` give the very first row?',
            'options' => ['Its own date', '`NULL`', 'The last row\'s date', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The first row has no previous row, so `LAG` returns `NULL`.',
        ],
        [
            'question' => 'Why does this query fail?',
            'code' => "SELECT title\nFROM books\nWHERE ROW_NUMBER() OVER (ORDER BY price) <= 3;",
            'options' => [
                '`ROW_NUMBER` needs a column name',
                '`OVER` must come before `SELECT`',
                'Window functions are worked out after `WHERE`, so `WHERE` can\'t use them. Number the rows in a `WITH` step first',
                'You can\'t order by `price`',
            ],
            'correct_option' => 2,
            'explanation' => 'Windows run late, after filtering. Put the window in a `WITH` step, then filter on its result.',
        ],
        [
            'question' => 'Two orders share the date 7 March. With `COUNT(*) OVER (ORDER BY ordered_on)`, what do they show, if 1 order came before them?',
            'options' => ['2 and 3', '3 and 3', '1 and 2', '2 and 2'],
            'correct_option' => 1,
            'explanation' => 'Rows with the same sort value are counted together, so both show 3. Add a tie-breaker such as `id` to the `ORDER BY` to count them one at a time.',
        ],
        [
            'question' => 'What does `LEAD(total) OVER (ORDER BY order_id)` give?',
            'options' => [
                'The biggest total',
                'The previous order\'s total',
                'The first order\'s total',
                'The next order\'s total',
            ],
            'correct_option' => 3,
            'explanation' => '`LEAD` looks forward to the next row; `LAG` looks back.',
        ],
        [
            'question' => 'At most how many rows does `ROWS BETWEEN 2 PRECEDING AND CURRENT ROW` include?',
            'options' => ['2', '3', '5', 'All the rows before the current one'],
            'correct_option' => 1,
            'explanation' => 'The two rows before and the current one: three. Near the start there are fewer.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Against the genre average',
            'prompt' => 'Show each book\'s `title`, `genre` and `price`, and whether its price is `above`, `below` or `at` the average price of its genre.',
            'starter_code' => null,
            'solution' => "SELECT title, genre, price, CASE WHEN price > AVG(price) OVER (PARTITION BY genre) THEN 'above' WHEN price < AVG(price) OVER (PARTITION BY genre) THEN 'below' ELSE 'at' END AS compared FROM books;",
            'hint' => '`AVG(price) OVER (PARTITION BY genre)` gives the genre average on every row; compare `price` with it inside a `CASE`.',
            'order_matters' => false,
        ],
        [
            'title' => 'First, second, third order',
            'prompt' => 'Show every order\'s `customer_id`, `id` and `ordered_on`, with a number counting that customer\'s orders from their **oldest** (1) upwards.',
            'starter_code' => null,
            'solution' => 'SELECT customer_id, id, ordered_on, ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY ordered_on) AS order_number FROM orders;',
            'hint' => '`ROW_NUMBER()` with a window that partitions by customer and orders by date.',
            'order_matters' => false,
        ],
        [
            'title' => 'Top two in each genre',
            'prompt' => 'Show the `genre`, `title` and `price` of the **two most expensive** books in each genre. (A genre with only one book shows just that one.)',
            'starter_code' => null,
            'solution' => 'WITH ranked AS (SELECT genre, title, price, ROW_NUMBER() OVER (PARTITION BY genre ORDER BY price DESC) AS position FROM books) SELECT genre, title, price FROM ranked WHERE position <= 2;',
            'hint' => 'Number the books within each genre, most expensive first, in a `WITH` step; then keep positions 1 and 2.',
            'order_matters' => false,
        ],
        [
            'title' => 'Running revenue',
            'prompt' => 'For each date with orders, show the date (`ordered_on`), that day\'s revenue, and the running total of revenue up to and including that day, both rounded to 2 places, earliest first.',
            'starter_code' => null,
            'solution' => 'SELECT o.ordered_on, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS revenue, ROUND(SUM(SUM(oi.quantity * oi.unit_price)) OVER (ORDER BY o.ordered_on), 2) AS running_total FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id GROUP BY o.ordered_on ORDER BY o.ordered_on;',
            'hint' => 'Group by date for each day\'s revenue. The running total is `SUM(…)` of that, as a window ordered by date: `SUM(SUM(…)) OVER (ORDER BY o.ordered_on)`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Time between orders',
            'prompt' => 'For every order, show its `customer_id`, `id`, `ordered_on`, and how many days have passed since that customer\'s previous order (`NULL` for their first order).',
            'starter_code' => null,
            'solution' => 'SELECT customer_id, id, ordered_on, julianday(ordered_on) - julianday(LAG(ordered_on) OVER (PARTITION BY customer_id ORDER BY ordered_on)) AS days_since_previous FROM orders;',
            'hint' => '`LAG(ordered_on)` over each customer\'s orders by date gives the previous date; subtract with `julianday`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Ranked by copies sold',
            'prompt' => 'Rank every book that has sold by the number of copies sold, most first, using `RANK` so that tied books share a rank. Show the `title`, copies sold and the rank, ordered by rank and then by title.',
            'starter_code' => null,
            'solution' => 'WITH sales AS (SELECT book_id, SUM(quantity) AS copies FROM order_items GROUP BY book_id) SELECT b.title, s.copies, RANK() OVER (ORDER BY s.copies DESC) AS position FROM sales AS s JOIN books AS b ON b.id = s.book_id ORDER BY position, b.title;',
            'hint' => 'Total the copies per book in a `WITH` step, join the titles, and add `RANK() OVER (ORDER BY copies DESC)`.',
            'order_matters' => true,
        ],
    ],
];
