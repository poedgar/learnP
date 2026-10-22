<?php

// Lesson 1 of the SQL practice: product analytics course. The theory is in the .md file with the
// same name. Each challenge's solution is compared with the learner's result. Quiz answers that quote
// numbers were checked against database/datasets/analytics.sql.

return [
    'slug' => 'growth-and-revenue',
    'title' => 'Growth and revenue',
    'summary' => 'Meet the Pagewise data, then answer the first questions every product team asks: signups per month, running totals, month-over-month growth, revenue and shares of a whole.',
    'starter_code' => "-- Signups per month. Try counting by country instead.\nSELECT substr(signed_up_on, 1, 7) AS month, COUNT(*) AS signups\nFROM users\nGROUP BY month\nORDER BY month;",

    'quiz' => [
        [
            'question' => 'Why is money stored as `amount_cents` rather than as a decimal amount?',
            'options' => [
                'It takes less space',
                'Whole numbers add up exactly, while decimal fractions stored as floating point can be slightly off',
                'SQLite has no decimal type at all',
                'Cents are easier to read',
            ],
            'correct_option' => 1,
            'explanation' => 'Add cents up exactly, and divide by `100.0` only to display the result.',
        ],
        [
            'question' => 'How many users had signed up by the end of March 2026?',
            'options' => ['6', '9', '15', '20'],
            'correct_option' => 2,
            'explanation' => '4 in January, 5 in February and 6 in March: a running total gives 15.',
        ],
        [
            'question' => 'In `SUM(COUNT(*)) OVER (ORDER BY month)`, why can the window function use `COUNT(*)`?',
            'options' => [
                'It can\'t; that\'s an error',
                'Window functions run after `GROUP BY`, so they work on the grouped rows and their aggregates',
                'Because `COUNT(*)` is a window function too',
                'Because of the `ORDER BY`',
            ],
            'correct_option' => 1,
            'explanation' => 'Grouping happens first, then the window adds up the groups\' counts in order.',
        ],
        [
            'question' => 'What does `LAG(signups) OVER (ORDER BY month)` give for the first month?',
            'options' => ['0', 'The first month\'s signups', '`NULL`', 'An error'],
            'correct_option' => 2,
            'explanation' => 'There is no earlier row. `LAG(signups, 1, 0)` would give 0 instead.',
        ],
        [
            'question' => 'Signups went from 6 in March to 5 in April. What is the month-over-month growth, to one decimal place?',
            'options' => ['-1.0%', '-16.7%', '-20.0%', '16.7%'],
            'correct_option' => 1,
            'explanation' => '(5 - 6) / 6 × 100 = -16.7%.',
        ],
        [
            'question' => 'How much paid revenue did Pagewise take from January to June, refunds excluded?',
            'options' => ['159.80', '167.79', '15980', '21 payments\' worth'],
            'correct_option' => 0,
            'explanation' => '20 paid payments of 7.99. The one refunded payment doesn\'t count.',
        ],
        [
            'question' => 'What does `SUM(COUNT(*)) OVER ()` (an empty window) add up?',
            'options' => [
                'The count of the current group only',
                'The counts of every group in the result: the grand total',
                'The previous group\'s count',
                'Nothing; it is always NULL',
            ],
            'correct_option' => 1,
            'explanation' => 'An empty window covers every row of the result, which makes shares of the total easy.',
        ],
        [
            'question' => 'What share of users have no referrer, to one decimal place?',
            'options' => ['8.0%', '20.0%', '23.3%', '26.7%'],
            'correct_option' => 3,
            'explanation' => '8 of the 30 users have a `NULL` referrer: 26.7%.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Where users live',
            'prompt' => 'Show each `country` and how many `users` signed up from it.',
            'starter_code' => null,
            'solution' => 'SELECT country, COUNT(*) AS users FROM users GROUP BY country;',
            'hint' => 'Group the users by country and count each group.',
            'order_matters' => false,
        ],
        [
            'title' => 'Revenue by country',
            'prompt' => 'Show each `country` that has paid revenue, and its `revenue` in whole units (cents divided by 100.0). Count only payments with status `paid`.',
            'starter_code' => null,
            'solution' => "SELECT u.country, SUM(p.amount_cents) / 100.0 AS revenue FROM payments AS p JOIN users AS u ON u.id = p.user_id WHERE p.status = 'paid' GROUP BY u.country;",
            'hint' => 'Join `payments` to `users` for the country, keep the paid payments, then group by country.',
            'order_matters' => false,
        ],
        [
            'title' => 'Revenue so far',
            'prompt' => 'For each month with paid payments, show the `month` (`YYYY-MM`), that month\'s `revenue` and the `running_total` of revenue up to and including it, both in whole units, ordered by month.',
            'starter_code' => null,
            'solution' => "SELECT substr(paid_on, 1, 7) AS month, SUM(amount_cents) / 100.0 AS revenue, SUM(SUM(amount_cents)) OVER (ORDER BY substr(paid_on, 1, 7)) / 100.0 AS running_total FROM payments WHERE status = 'paid' GROUP BY month ORDER BY month;",
            'hint' => 'Group paid payments by month, then add a window: `SUM(SUM(amount_cents)) OVER (ORDER BY ...)`.',
            'order_matters' => true,
        ],
        [
            'title' => 'Up or down',
            'prompt' => 'For each signup month, ordered by month, show the `month`, its `signups` and the `change` from the month before (`NULL` for the first month).',
            'starter_code' => null,
            'solution' => 'SELECT substr(signed_up_on, 1, 7) AS month, COUNT(*) AS signups, COUNT(*) - LAG(COUNT(*)) OVER (ORDER BY substr(signed_up_on, 1, 7)) AS change FROM users GROUP BY month ORDER BY month;',
            'hint' => '`LAG()` over the grouped counts, ordered by month, gives last month\'s number to subtract.',
            'order_matters' => true,
        ],
        [
            'title' => 'Monthly active users',
            'prompt' => 'For each month, show the `month` and the number of `active_users`: different users with at least one session that month.',
            'starter_code' => null,
            'solution' => 'SELECT substr(started_at, 1, 7) AS month, COUNT(DISTINCT user_id) AS active_users FROM sessions GROUP BY month;',
            'hint' => 'Group sessions by month and count `DISTINCT user_id`: one user with ten sessions is still one active user.',
            'order_matters' => false,
        ],
        [
            'title' => 'Best customers',
            'prompt' => 'Show the `name` of every user who has paid at least once and their `total_paid` in whole units (paid payments only).',
            'starter_code' => null,
            'solution' => "SELECT u.name, SUM(p.amount_cents) / 100.0 AS total_paid FROM users AS u JOIN payments AS p ON p.user_id = u.id WHERE p.status = 'paid' GROUP BY u.id;",
            'hint' => 'Join users to their paid payments and group by the user.',
            'order_matters' => false,
        ],
    ],
];
