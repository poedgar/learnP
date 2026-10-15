<?php

// Lesson 4 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution;
// their results are compared. Quiz answers that quote numbers were checked against
// database/datasets/bookshop_plus.sql.

return [
    'slug' => 'everyday-query-patterns',
    'title' => 'Everyday query patterns',
    'summary' => 'Pivot rows into columns and back, find and remove duplicates, keep the latest row of each group, and find gaps and runs in a sequence.',
    'starter_code' => "-- Copies sold per genre, one column per month. Add a column for April.\nSELECT b.genre,\n       SUM(CASE WHEN o.ordered_on LIKE '2026-01%' THEN oi.quantity ELSE 0 END) AS jan,\n       SUM(CASE WHEN o.ordered_on LIKE '2026-02%' THEN oi.quantity ELSE 0 END) AS feb,\n       SUM(CASE WHEN o.ordered_on LIKE '2026-03%' THEN oi.quantity ELSE 0 END) AS mar\nFROM order_items AS oi\nJOIN orders AS o ON o.id = oi.order_id\nJOIN books AS b ON b.id = oi.book_id\nGROUP BY b.genre;",

    'quiz' => [
        [
            'question' => 'In a pivot, what does `SUM(CASE WHEN month = \'jan\' THEN quantity ELSE 0 END)` add up?',
            'options' => [
                'Every quantity in the group',
                'Only the quantities from January rows; other rows add 0',
                'The number of January rows',
                'The January quantity of the first row only',
            ],
            'correct_option' => 1,
            'explanation' => 'The `CASE` passes a row\'s quantity through only when it belongs to January, so the `SUM` totals January alone.',
        ],
        [
            'question' => 'Which gives the same result as `COUNT(*) FILTER (WHERE rating = 5)`?',
            'options' => [
                '`COUNT(rating = 5)`',
                '`SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END)`',
                '`MAX(rating)`',
                '`COUNT(*) WHERE rating = 5`',
            ],
            'correct_option' => 1,
            'explanation' => 'Both count only the rows rated 5. `COUNT(rating = 5)` counts every rating, because `rating = 5` is 0 or 1, never `NULL`, for rated rows.',
        ],
        [
            'question' => '`sales_targets` has 5 genres and 4 month columns. How many rows does unpivoting it with four `SELECT`s joined by `UNION ALL` give?',
            'options' => ['4', '5', '9', '20'],
            'correct_option' => 3,
            'explanation' => 'Each of the four `SELECT`s returns one row per genre: 4 × 5 = 20.',
        ],
        [
            'question' => 'How do you find rows that appear more than once?',
            'options' => [
                '`SELECT DISTINCT *`',
                'Group by the columns that should be unique, and keep groups with `HAVING COUNT(*) > 1`',
                '`WHERE COUNT(*) > 1`',
                'Sort the table and look',
            ],
            'correct_option' => 1,
            'explanation' => '`DISTINCT` hides duplicates rather than finding them. `WHERE` can\'t use aggregates; `HAVING` filters the groups.',
        ],
        [
            'question' => 'To keep exactly one newest row per book, why use `ROW_NUMBER()` rather than `RANK()`?',
            'options' => [
                '`RANK()` doesn\'t work with `PARTITION BY`',
                '`ROW_NUMBER()` is faster',
                'Rows that tie both get rank 1, so `RANK()` can keep more than one',
                'There is no difference',
            ],
            'correct_option' => 2,
            'explanation' => '`RANK()` gives tied rows the same number. `ROW_NUMBER()` numbers every row differently, so `n = 1` keeps exactly one.',
        ],
        [
            'question' => '`supplier_prices` has no `id` column. How can you tell two identical rows apart to delete one?',
            'options' => [
                'You can\'t; delete both and insert one again',
                'With `rowid`, the hidden id every SQLite table has',
                'With `LIMIT 1`',
                'With `DISTINCT`',
            ],
            'correct_option' => 1,
            'explanation' => 'Every ordinary SQLite table has a hidden `rowid`, unique for each row, even when the visible columns are identical.',
        ],
        [
            'question' => 'In the islands trick, why does `julianday(visited_on) - ROW_NUMBER() OVER (…)` stay the same within a run of consecutive days?',
            'options' => [
                'Because `ROW_NUMBER()` resets every day',
                'Because the date and the row number both go up by 1 from one row to the next',
                'Because `julianday` ignores the day',
                'It doesn\'t; it changes on every row',
            ],
            'correct_option' => 1,
            'explanation' => 'Two numbers that both rise by 1 keep the same difference. A skipped day makes the date jump by 2 while the row number rises by 1, so the difference changes.',
        ],
        [
            'question' => 'With `next_id` from `LEAD(id) OVER (ORDER BY id)`, what does `next_id > id + 1` tell you?',
            'options' => [
                'The row is the last one',
                'The ids are in the wrong order',
                'One or more ids are missing after this row',
                'The row is a duplicate',
            ],
            'correct_option' => 2,
            'explanation' => 'Without a gap, the next id is exactly `id + 1`. Anything bigger means ids in between are missing.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Star chart',
            'prompt' => 'For every book that has at least one review, show its `title` and how many reviews gave it 5 stars (`five`), 4 stars (`four`) and 3 stars or fewer (`three_or_less`). Reviews without a rating don\'t count in any column.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, COUNT(*) FILTER (WHERE r.rating = 5) AS five, COUNT(*) FILTER (WHERE r.rating = 4) AS four, COUNT(*) FILTER (WHERE r.rating <= 3) AS three_or_less FROM reviews AS r JOIN books AS b ON b.id = r.book_id GROUP BY b.id;',
            'hint' => 'Group the reviews by book, and count each column with `COUNT(*) FILTER (WHERE …)` or `SUM(CASE WHEN … THEN 1 ELSE 0 END)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Paper order lines',
            'prompt' => '`paper_orders` has up to three books per row, in `book_1`, `book_2` and `book_3`. Turn it into one row per book: show the `order_ref` and the book in a column named `title`, skipping the empty slots.',
            'starter_code' => null,
            'solution' => 'SELECT order_ref, book_1 AS title FROM paper_orders UNION ALL SELECT order_ref, book_2 FROM paper_orders WHERE book_2 IS NOT NULL UNION ALL SELECT order_ref, book_3 FROM paper_orders WHERE book_3 IS NOT NULL;',
            'hint' => 'One `SELECT` per book column, joined with `UNION ALL`. Filter out the `NULL` slots with `WHERE … IS NOT NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Said it twice',
            'prompt' => 'Some reviews were submitted twice: same customer, same book, same text. Show the customer\'s `name`, the book\'s `title` and the number of `copies` of each such review.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, b.title, COUNT(*) AS copies FROM reviews AS r JOIN customers AS c ON c.id = r.customer_id JOIN books AS b ON b.id = r.book_id GROUP BY r.customer_id, r.book_id, r.body HAVING COUNT(*) > 1;',
            'hint' => 'Group by `customer_id`, `book_id` and `body`, keep the groups with `HAVING COUNT(*) > 1`, and join `customers` and `books` for the names.',
            'order_matters' => false,
        ],
        [
            'title' => 'Clean up the reviews',
            'prompt' => 'Delete the repeated reviews (same customer, book and text), keeping the copy with the lowest `id` of each.',
            'starter_code' => null,
            'solution' => 'DELETE FROM reviews WHERE id NOT IN (SELECT MIN(id) FROM reviews GROUP BY customer_id, book_id, body);',
            'check_sql' => 'SELECT id FROM reviews ORDER BY id',
            'hint' => 'Keep `MIN(id)` of each group of `customer_id`, `book_id` and `body`, and delete every row whose id isn\'t one of those.',
            'order_matters' => false,
        ],
        [
            'title' => 'Latest word',
            'prompt' => 'For every book that has reviews, show its `title` and its most recent review: `reviewed_on` and `body`.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, r.reviewed_on, r.body FROM (SELECT book_id, reviewed_on, body, ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY reviewed_on DESC) AS n FROM reviews) AS r JOIN books AS b ON b.id = r.book_id WHERE r.n = 1;',
            'hint' => 'Number each book\'s reviews with `ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY reviewed_on DESC)` in a subquery, keep `n = 1`, and join `books` for the title.',
            'order_matters' => false,
        ],
        [
            'title' => 'Longest streak',
            'prompt' => 'For every customer in `visits`, show their `name` and their `longest_streak`: the most consecutive days they visited the website.',
            'starter_code' => null,
            'solution' => 'SELECT c.name, MAX(i.days) AS longest_streak FROM (SELECT customer_id, COUNT(*) AS days FROM (SELECT customer_id, julianday(visited_on) - ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY visited_on) AS island FROM visits) GROUP BY customer_id, island) AS i JOIN customers AS c ON c.id = i.customer_id GROUP BY c.id;',
            'hint' => 'Count the days in each island as in the lesson, then take the `MAX` of those counts per customer, and join `customers` for the name.',
            'order_matters' => false,
        ],
    ],
];
