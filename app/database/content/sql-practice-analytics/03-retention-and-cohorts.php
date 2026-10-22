<?php

// Lesson 3 of the SQL practice: product analytics course. The theory is in the .md file with the
// same name. Quiz answers that quote numbers were checked against database/datasets/analytics.sql.

return [
    'slug' => 'retention-and-cohorts',
    'title' => 'Retention and cohorts',
    'summary' => 'Do people come back? Signup cohorts, months since signup, a retention table, last-seen dates for users at risk, and churn among subscribers.',
    'starter_code' => "-- When was each user last seen? Try showing only those not seen since May.\nSELECT u.name, MAX(s.started_at) AS last_seen\nFROM users AS u\nJOIN sessions AS s ON s.user_id = u.id\nGROUP BY u.id\nORDER BY last_seen;",

    'quiz' => [
        [
            'question' => 'What is a cohort?',
            'options' => [
                'A user who pays',
                'A group of users who started at the same time, such as the same signup month',
                'A table of sessions',
                'A country',
            ],
            'correct_option' => 1,
            'explanation' => 'Comparing cohorts shows whether newer users stay better than older ones.',
        ],
        [
            'question' => 'A user signed up on 2026-01-28 and visited on 2026-03-02. Which month number is that visit?',
            'options' => ['0', '1', '2', '3'],
            'correct_option' => 2,
            'explanation' => 'Months are counted by calendar month: January is 0, February 1, March 2, even though only five weeks passed.',
        ],
        [
            'question' => 'How many of the March 2026 cohort\'s 6 users were active in month 1 (April)?',
            'options' => ['3', '4', '5', '6'],
            'correct_option' => 2,
            'explanation' => 'The retention table\'s `m1` column for the 2026-03 row is 5.',
        ],
        [
            'question' => 'The June cohort shows 0 users in month 1. Why shouldn\'t you conclude that they all left?',
            'options' => [
                'Because June is short',
                'Because month 1 for them is July, which isn\'t in the data yet',
                'Because June users don\'t have sessions',
                'Because of a bug in FILTER',
            ],
            'correct_option' => 1,
            'explanation' => 'Months that haven\'t happened yet look like zeros; leave them blank in a report.',
        ],
        [
            'question' => 'How many users had no session at all in June 2026?',
            'options' => ['4', '6', '8', '22'],
            'correct_option' => 2,
            'explanation' => '`NOT EXISTS` a session on or after 2026-06-01: 8 users.',
        ],
        [
            'question' => 'Why `HAVING MAX(s.started_at) < \'2026-05-01\'` rather than `WHERE`?',
            'options' => [
                'WHERE is slower',
                'The condition is on each user\'s latest session, an aggregate, which only exists after grouping',
                'HAVING works on text',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => '`WHERE s.started_at < ...` would keep old sessions of users who were also active later.',
        ],
        [
            'question' => 'How many users have visited on more than one day?',
            'options' => ['8', '15', '22', '30'],
            'correct_option' => 2,
            'explanation' => 'Group sessions by user and keep `COUNT(DISTINCT date(started_at)) > 1`.',
        ],
        [
            'question' => 'Of the 7 users who ever subscribed, 3 cancelled. Why show the counts next to a churn rate?',
            'options' => [
                'Because percentages are always wrong',
                'With so few people, one cancellation changes the rate a lot, and the counts make that clear',
                'Because SQL can\'t compute percentages',
                'They don\'t need showing',
            ],
            'correct_option' => 1,
            'explanation' => '3 of 7 is "43%", which sounds dramatic until you see it\'s three people.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Pro by cohort',
            'prompt' => 'For each signup month as `cohort` (`YYYY-MM`), show how many `users` it has and how many of them are on the `pro` plan now (`pro_users`).',
            'starter_code' => null,
            'solution' => "SELECT substr(signed_up_on, 1, 7) AS cohort, COUNT(*) AS users, COUNT(*) FILTER (WHERE plan = 'pro') AS pro_users FROM users GROUP BY cohort;",
            'hint' => 'Group users by signup month; count all of them, and count the Pro ones with `FILTER (WHERE plan = \'pro\')`.',
            'order_matters' => false,
        ],
        [
            'title' => 'First and last seen',
            'prompt' => 'For every user with sessions, show their `name`, `first_seen` (earliest `started_at`) and `last_seen` (latest).',
            'starter_code' => null,
            'solution' => 'SELECT u.name, MIN(s.started_at) AS first_seen, MAX(s.started_at) AS last_seen FROM users AS u JOIN sessions AS s ON s.user_id = u.id GROUP BY u.id;',
            'hint' => 'Join users to sessions, group by user, and take `MIN` and `MAX` of `started_at`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Quiet in June',
            'prompt' => 'Show the `name` of every user who has no session on or after 2026-06-01.',
            'starter_code' => null,
            'solution' => "SELECT u.name FROM users AS u WHERE NOT EXISTS (SELECT 1 FROM sessions AS s WHERE s.user_id = u.id AND s.started_at >= '2026-06-01');",
            'hint' => '`NOT EXISTS` a session for that user on or after the first of June.',
            'order_matters' => false,
        ],
        [
            'title' => 'Month-one retention',
            'prompt' => 'For each signup `cohort` (`YYYY-MM`), show its `users` and how many were `retained`: had a session in the calendar month right after their signup month.',
            'starter_code' => null,
            'solution' => "SELECT substr(u.signed_up_on, 1, 7) AS cohort, COUNT(*) AS users, COUNT(*) FILTER (WHERE EXISTS (SELECT 1 FROM sessions AS s WHERE s.user_id = u.id AND substr(s.started_at, 1, 7) = strftime('%Y-%m', date(u.signed_up_on, 'start of month', '+1 month')))) AS retained FROM users AS u GROUP BY cohort;",
            'hint' => 'The month after signup is `strftime(\'%Y-%m\', date(signed_up_on, \'start of month\', \'+1 month\'))`. Count users for whom a session in that month `EXISTS`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Coming back',
            'prompt' => 'For each `country`, show how many `returning_users` it has: users with sessions on at least two different days.',
            'starter_code' => null,
            'solution' => 'SELECT u.country, COUNT(*) AS returning_users FROM users AS u WHERE (SELECT COUNT(DISTINCT date(s.started_at)) FROM sessions AS s WHERE s.user_id = u.id) >= 2 GROUP BY u.country;',
            'hint' => 'For each user, count `DISTINCT date(started_at)` of their sessions (a subquery works well), keep those with 2 or more, then group by country.',
            'order_matters' => false,
        ],
        [
            'title' => 'Who cancelled',
            'prompt' => 'For every user who cancelled, show their `name`, `subscribed_on` and `cancelled_on` (dates, `YYYY-MM-DD`).',
            'starter_code' => null,
            'solution' => "SELECT u.name, date(s.happened_at) AS subscribed_on, date(c.happened_at) AS cancelled_on FROM users AS u JOIN events AS s ON s.user_id = u.id AND s.event = 'subscribe' JOIN events AS c ON c.user_id = u.id AND c.event = 'cancel';",
            'hint' => 'Join `events` twice: once for the subscribe event and once for the cancel event.',
            'order_matters' => false,
        ],
    ],
];
