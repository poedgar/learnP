<?php

// Lesson 2 of the Advanced SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop_plus.sql.

return [
    'slug' => 'the-truth-about-null',
    'title' => 'The truth about NULL',
    'summary' => 'How NULL behaves in conditions, NOT, calculations, aggregates, sorting, joins and constraints, and how to handle it safely with IS and COALESCE.',
    'starter_code' => "-- Compare these two answers. Why don't they add up to 20 orders?\nSELECT COUNT(*) FROM orders WHERE shipped_on > '2026-03-01';\nSELECT COUNT(*) FROM orders WHERE NOT (shipped_on > '2026-03-01');",

    'quiz' => [
        [
            'question' => 'What does `SELECT NULL = NULL;` return?',
            'options' => ['1 (true)', '0 (false)', '`NULL` (unknown)', 'An error'],
            'correct_option' => 2,
            'explanation' => 'Two unknown values might or might not be equal, so the answer is unknown. Use `IS` to compare `NULL`s.',
        ],
        [
            'question' => 'Which rows does `WHERE` keep?',
            'options' => [
                'Rows where the condition is true',
                'Rows where the condition is true or unknown',
                'Rows where the condition isn\'t false',
                'Every row, but with `NULL` in failed columns',
            ],
            'correct_option' => 0,
            'explanation' => '`WHERE` keeps only true. Rows where the condition is false **or** unknown are dropped.',
        ],
        [
            'question' => 'There are 20 orders, and 4 haven\'t shipped. 9 orders shipped after 1 March. How many rows does this count?',
            'code' => "SELECT COUNT(*)\nFROM orders\nWHERE NOT (shipped_on > '2026-03-01');",
            'options' => ['11', '7', '20', '0'],
            'correct_option' => 1,
            'explanation' => 'For the 4 unshipped orders, the comparison is unknown, and `NOT` unknown is still unknown. They\'re dropped both ways: 20 − 9 − 4 = 7.',
        ],
        [
            'question' => 'The `reviews` table has 23 rows, and 4 of them have no rating. What does `SELECT COUNT(rating) FROM reviews;` return?',
            'options' => ['23', '19', '4', '`NULL`'],
            'correct_option' => 1,
            'explanation' => '`COUNT(column)` skips `NULL`s. `COUNT(*)` would count all 23 rows.',
        ],
        [
            'question' => 'A book has three reviews, rated 4, `NULL` and 2. What is `AVG(rating)` for it?',
            'options' => ['2', '3', '`NULL`', '6'],
            'correct_option' => 1,
            'explanation' => '`AVG` skips the `NULL` and averages the other two: (4 + 2) / 2 = 3.',
        ],
        [
            'question' => "What does `SELECT 'Dune' || NULL;` return?",
            'options' => ["`'Dune'`", "`'DuneNULL'`", '`NULL`', 'An error'],
            'correct_option' => 2,
            'explanation' => 'Joining text with an unknown value gives an unknown value. Wrap the `NULL` side in `COALESCE` to supply a fallback.',
        ],
        [
            'question' => 'A column is declared `email TEXT UNIQUE`. Can two rows both have `NULL` as their email?',
            'options' => [
                'No, because `UNIQUE` forbids duplicates',
                'Yes, because two `NULL`s aren\'t known to be equal',
                'Only if the column also says `DEFAULT NULL`',
                'Only one row can ever hold `NULL`',
            ],
            'correct_option' => 1,
            'explanation' => '`UNIQUE` lets many rows hold `NULL`. Add `NOT NULL` if every row must have an email.',
        ],
        [
            'question' => 'Which condition finds the staff whose city is unknown?',
            'options' => ['`WHERE city = NULL`', '`WHERE city IS NULL`', "`WHERE city = ''`", '`WHERE NOT city`'],
            'correct_option' => 1,
            'explanation' => '`city = NULL` is unknown for every row, so it finds nothing. `IS NULL` is the test for a missing value.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Rated and unrated',
            'prompt' => 'In one row, show how many reviews have a star rating (`rated`) and how many don\'t (`unrated`).',
            'starter_code' => null,
            'solution' => 'SELECT COUNT(rating) AS rated, COUNT(*) - COUNT(rating) AS unrated FROM reviews;',
            'hint' => '`COUNT(rating)` counts only the rows that have a rating, and `COUNT(*)` counts every row.',
            'order_matters' => false,
        ],
        [
            'title' => 'Outside London',
            'prompt' => 'List the `name` of every customer who isn\'t known to live in London, **including** customers whose city is unknown.',
            'starter_code' => null,
            'solution' => "SELECT name FROM customers WHERE city IS NOT 'London';",
            'hint' => "`city <> 'London'` drops the customers with no city. `IS NOT` treats `NULL` as a value of its own.",
            'order_matters' => false,
        ],
        [
            'title' => 'Reviews for every book',
            'prompt' => 'For every book, show its `title`, its number of `reviews` and its `average` rating rounded to 1 decimal place. Books without any reviews should show 0 reviews.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, COUNT(r.id) AS reviews, ROUND(AVG(r.rating), 1) AS average FROM books AS b LEFT JOIN reviews AS r ON r.book_id = b.id GROUP BY b.id;',
            'hint' => 'A `LEFT JOIN` keeps books with no reviews. Count a column from `reviews` (such as `r.id`), not `*`, or those books count as 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'Unknown cities last',
            'prompt' => 'List every member of staff\'s `name` and `city`, sorted by city from A to Z with unknown cities **last**, and by name within each city.',
            'starter_code' => null,
            'solution' => 'SELECT name, city FROM staff ORDER BY city NULLS LAST, name;',
            'hint' => 'Ascending order puts `NULL` first. Add `NULLS LAST` after `city`, then sort by `name`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Neighbours',
            'prompt' => 'List every pair of customers who live in the same city, counting two **unknown** cities as the same. Show each pair once, as `name` and `other_name`.',
            'starter_code' => null,
            'solution' => 'SELECT a.name, b.name AS other_name FROM customers AS a JOIN customers AS b ON a.city IS b.city AND a.id < b.id;',
            'hint' => 'Join `customers` to itself. `a.city = b.city` never matches two `NULL`s, but `a.city IS b.city` does. `a.id < b.id` keeps each pair once.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shipping status',
            'prompt' => 'Show each order\'s `id` and a `status`: `waiting` if it hasn\'t shipped, `fast` if it shipped within 2 days of being ordered, and `slow` otherwise.',
            'starter_code' => null,
            'solution' => "SELECT id, CASE WHEN shipped_on IS NULL THEN 'waiting' WHEN julianday(shipped_on) - julianday(ordered_on) <= 2 THEN 'fast' ELSE 'slow' END AS status FROM orders;",
            'hint' => 'Check `shipped_on IS NULL` first, in a searched `CASE WHEN …`. Days between dates are `julianday(shipped_on) - julianday(ordered_on)`.',
            'order_matters' => false,
        ],
    ],
];
