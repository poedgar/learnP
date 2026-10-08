<?php

// Lesson 8 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'shaping-values',
    'title' => 'Shaping values',
    'summary' => 'Choose values with CASE, and reshape text, numbers and dates with SQLite\'s built-in functions.',
    'starter_code' => "-- Label each book by its length, then try your own CASE.\nSELECT title, pages,\n       CASE WHEN pages < 300 THEN 'short' ELSE 'long' END AS length\nFROM books;",

    'quiz' => [
        [
            'question' => 'What does `SELECT 7 / 2;` return in SQLite?',
            'options' => ['3', '3.5', '4', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Both numbers are whole, so SQLite does integer division and drops the remainder. Write `7 / 2.0` to get 3.5.',
        ],
        [
            'question' => 'A `CASE` has no `ELSE`, and none of its conditions is true for a row. What does that row get?',
            'options' => ['0', 'An empty text', '`NULL`', 'The first `THEN` value'],
            'correct_option' => 2,
            'explanation' => 'Without an `ELSE`, a row that matches no `WHEN` gets `NULL`.',
        ],
        [
            'question' => 'What does this return for a book that costs 8.50?',
            'code' => "SELECT CASE\n           WHEN 8.50 < 20 THEN 'under 20'\n           WHEN 8.50 < 10 THEN 'under 10'\n       END;",
            'options' => ["`'under 20'`", "`'under 10'`", 'Both labels', '`NULL`'],
            'correct_option' => 0,
            'explanation' => 'Conditions are tried in order and the first true one wins. Put the narrower condition first if that\'s the one you mean.',
        ],
        [
            'question' => "What does `date('2026-03-31', '+1 day')` return?",
            'options' => ["`'2026-03-32'`", "`'2026-04-01'`", "`'2026-03-30'`", '`NULL`'],
            'correct_option' => 1,
            'explanation' => 'Date arithmetic knows how long each month is, so the day after 31 March is 1 April.',
        ],
        [
            'question' => "What does `strftime('%m', '2026-03-14')` return?",
            'options' => ["`'March'`", '`3`', "`'03'`", "`'14'`"],
            'correct_option' => 2,
            'explanation' => '`%m` is the month as two digits, and `strftime` always returns text.',
        ],
        [
            'question' => 'What does `SELECT 10 / 0;` return in SQLite?',
            'options' => ['0', 'An error', 'Infinity', '`NULL`'],
            'correct_option' => 3,
            'explanation' => 'SQLite returns `NULL` for division by zero; most other databases stop with an error. `NULLIF(divisor, 0)` makes the intention clear in both.',
        ],
        [
            'question' => "What does `'Dune' || ' (' || 1965 || ')'` give?",
            'options' => ["`'Dune (1965)'`", "`'Dune1965'`", '`1965`', 'An error, because 1965 is a number'],
            'correct_option' => 0,
            'explanation' => '`||` joins values as text, converting numbers along the way.',
        ],
        [
            'question' => 'What does `julianday(\'2026-03-18\') - julianday(\'2026-03-14\')` return?',
            'options' => ['`4.0`', '`\'4 days\'`', '`18`', '`NULL`'],
            'correct_option' => 0,
            'explanation' => '`julianday` turns each date into a day number, so the difference is the number of days between them, as a decimal.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Price bands',
            'prompt' => 'Show each book\'s `title`, `price` and a band: `Budget` under 10, `Standard` from 10 up to (but not including) 15, and `Premium` from 15 up.',
            'starter_code' => "SELECT title, price,\n       CASE\n       END AS band\nFROM books;",
            'solution' => "SELECT title, price, CASE WHEN price < 10 THEN 'Budget' WHEN price < 15 THEN 'Standard' ELSE 'Premium' END AS band FROM books;",
            'hint' => 'Check the cheapest band first: `WHEN price < 10 THEN \'Budget\'`, then `WHEN price < 15`, then `ELSE`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Order months',
            'prompt' => 'Show each order\'s `id` and the month it was placed, written like `2026-03`.',
            'starter_code' => null,
            'solution' => "SELECT id, strftime('%Y-%m', ordered_on) AS month FROM orders;",
            'hint' => "`strftime('%Y-%m', ordered_on)` gives the year and month.",
            'order_matters' => false,
        ],
        [
            'title' => 'How long shipping took',
            'prompt' => 'For every order, show its `id` and how many days it took to ship. For orders that haven\'t shipped, show the text `not shipped` instead.',
            'starter_code' => null,
            'solution' => "SELECT id, COALESCE(julianday(shipped_on) - julianday(ordered_on), 'not shipped') AS days FROM orders;",
            'hint' => 'Subtract the `julianday` of the two dates. When `shipped_on` is `NULL` the difference is `NULL`, so `COALESCE` (or a `CASE`) can replace it.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shelf labels',
            'prompt' => 'Shelf labels fit 10 characters. Show each book\'s `title` and its label: the title itself if it is 10 characters or fewer, otherwise its first 10 characters followed by `...` (three full stops).',
            'starter_code' => null,
            'solution' => "SELECT title, CASE WHEN LENGTH(title) > 10 THEN SUBSTR(title, 1, 10) || '...' ELSE title END AS label FROM books;",
            'hint' => 'Use `CASE WHEN LENGTH(title) > 10`, `SUBSTR(title, 1, 10)` and `||`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shipped and waiting',
            'prompt' => 'In one row, show how many orders have shipped and how many are still waiting to ship.',
            'starter_code' => null,
            'solution' => 'SELECT SUM(CASE WHEN shipped_on IS NULL THEN 0 ELSE 1 END) AS shipped, SUM(CASE WHEN shipped_on IS NULL THEN 1 ELSE 0 END) AS waiting FROM orders;',
            'hint' => 'Two `SUM(CASE …)` columns, one counting orders with a `shipped_on` date and one counting those without.',
            'order_matters' => false,
        ],
        [
            'title' => 'Slow shipping by month',
            'prompt' => 'For each month (written like `2026-03`), show the number of orders placed and how many of them took **more than 2 days** to ship (orders that haven\'t shipped yet don\'t count as slow), in month order.',
            'starter_code' => null,
            'solution' => "SELECT strftime('%Y-%m', ordered_on) AS month, COUNT(*) AS orders, SUM(CASE WHEN julianday(shipped_on) - julianday(ordered_on) > 2 THEN 1 ELSE 0 END) AS slow FROM orders GROUP BY month ORDER BY month;",
            'hint' => 'Group by the `strftime` month. Count slow orders with `SUM(CASE WHEN … > 2 THEN 1 ELSE 0 END)`; unshipped orders give `NULL`, which isn\'t greater than 2.',
            'order_matters' => true,
        ],
    ],
];
