<?php

// Lesson 4 of the SQL practice: product analytics course. The theory is in the .md file with the
// same name. Quiz answers that quote numbers were checked against database/datasets/analytics.sql.

return [
    'slug' => 'sessions-and-engagement',
    'title' => 'Sessions and engagement',
    'summary' => 'How people use the app: averages and medians of session length, top users with DENSE_RANK, busy days and times, and streaks of consecutive days.',
    'starter_code' => "-- Sessions per device. Add the average length in minutes.\nSELECT device, COUNT(*) AS sessions\nFROM sessions\nGROUP BY device\nORDER BY sessions DESC;",

    'quiz' => [
        [
            'question' => 'Which device has the most sessions?',
            'options' => ['desktop', 'mobile', 'tablet', 'They are equal'],
            'correct_option' => 1,
            'explanation' => '78 of the 147 sessions were on mobile.',
        ],
        [
            'question' => 'Why is the median session length often more useful than the average?',
            'options' => [
                'It is easier to compute',
                'A few unusually long sessions pull the average up, but barely move the median',
                'The average ignores short sessions',
                'SQLite\'s AVG is inaccurate',
            ],
            'correct_option' => 1,
            'explanation' => 'The median is the middle value, so extremes on either side don\'t drag it.',
        ],
        [
            'question' => 'There are 147 sessions. Which positions does `WHERE position IN ((total + 1) / 2, (total + 2) / 2)` keep?',
            'options' => ['73 and 74', '74 only', '74 and 75', '147'],
            'correct_option' => 1,
            'explanation' => 'With integer division, (148)/2 = 74 and (149)/2 = 74: one middle row for an odd count.',
        ],
        [
            'question' => 'What does `DENSE_RANK()` do with two users who both have 9 sessions?',
            'options' => [
                'Gives them different ranks at random',
                'Gives them the same rank, and the next user gets the very next number',
                'Skips both',
                'Gives them the same rank, then skips a number',
            ],
            'correct_option' => 1,
            'explanation' => '`RANK()` would skip a number after a tie; `DENSE_RANK()` doesn\'t.',
        ],
        [
            'question' => 'What does `strftime(\'%w\', \'2026-06-21\')` return? (21 June 2026 is a Sunday.)',
            'options' => ['`\'7\'`', '`\'0\'`', '`\'1\'`', '`\'Sunday\'`'],
            'correct_option' => 1,
            'explanation' => '`%w` counts days of the week from 0 for Sunday, as text.',
        ],
        [
            'question' => 'On which two days did the most different users visit (5 each)?',
            'options' => ['2026-05-13 and 2026-06-20', '2026-06-10 and 2026-06-20', '2026-01-10 and 2026-05-13', '2026-06-29 and 2026-06-30'],
            'correct_option' => 0,
            'explanation' => 'Group by `date(started_at)` and count distinct users.',
        ],
        [
            'question' => 'In the streak query, why select `DISTINCT user_id, date(started_at)` first?',
            'options' => [
                'To make it faster',
                'Two visits on one day would give two rows for that day and break the day-minus-row-number pattern',
                'Because dates must be unique in SQLite',
                'It isn\'t needed',
            ],
            'correct_option' => 1,
            'explanation' => 'The trick needs exactly one row per day.',
        ],
        [
            'question' => 'Which user has the most sessions, and how many?',
            'options' => ['Ben Carter, 11', 'Chloe Martin, 16', 'Farah Khan, 10', 'Priya Sharma, 9'],
            'correct_option' => 1,
            'explanation' => 'Chloe Martin (user 3) has 16 sessions.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Device mix',
            'prompt' => 'For each `device`, show the number of `sessions` and the `total_hours` spent, rounded to one decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT device, COUNT(*) AS sessions, ROUND(SUM(duration_seconds) / 3600.0, 1) AS total_hours FROM sessions GROUP BY device;',
            'hint' => 'Sum the seconds per device and divide by `3600.0`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Top three',
            'prompt' => 'Show the `name` and number of `sessions` of the users ranked in the top 3 by sessions, where tied users share a rank (so there may be more than three people).',
            'starter_code' => null,
            'solution' => 'SELECT u.name, c.sessions FROM (SELECT user_id, COUNT(*) AS sessions, DENSE_RANK() OVER (ORDER BY COUNT(*) DESC) AS rnk FROM sessions GROUP BY user_id) AS c JOIN users AS u ON u.id = c.user_id WHERE c.rnk <= 3;',
            'hint' => 'Count sessions per user with a `DENSE_RANK()` over that count in a subquery, then keep ranks 1 to 3.',
            'order_matters' => false,
        ],
        [
            'title' => 'Busy days',
            'prompt' => 'Show each `day` (`YYYY-MM-DD`) on which at least 4 different users had a session, with the number of `active_users`.',
            'starter_code' => null,
            'solution' => 'SELECT date(started_at) AS day, COUNT(DISTINCT user_id) AS active_users FROM sessions GROUP BY day HAVING COUNT(DISTINCT user_id) >= 4;',
            'hint' => 'Group by `date(started_at)` and keep groups with `HAVING COUNT(DISTINCT user_id) >= 4`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Parts of the day',
            'prompt' => 'Count `sessions` per `part` of the day by start hour: `night` (0–5), `morning` (6–11), `afternoon` (12–17) and `evening` (18–23). Show only parts that have sessions.',
            'starter_code' => null,
            'solution' => "SELECT CASE WHEN CAST(strftime('%H', started_at) AS INTEGER) < 6 THEN 'night' WHEN CAST(strftime('%H', started_at) AS INTEGER) < 12 THEN 'morning' WHEN CAST(strftime('%H', started_at) AS INTEGER) < 18 THEN 'afternoon' ELSE 'evening' END AS part, COUNT(*) AS sessions FROM sessions GROUP BY part;",
            'hint' => 'Cast `strftime(\'%H\', started_at)` to an integer and use a `CASE` with `<` checks in order, then group by the result.',
            'order_matters' => false,
        ],
        [
            'title' => 'Long sessions',
            'prompt' => 'For each `device`, show `long_pct`: the percentage of its sessions that lasted more than 30 minutes (1800 seconds), rounded to one decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT device, ROUND(100.0 * COUNT(*) FILTER (WHERE duration_seconds > 1800) / COUNT(*), 1) AS long_pct FROM sessions GROUP BY device;',
            'hint' => 'Long sessions with `FILTER`, divided by all sessions of the device, times `100.0`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Longest streak',
            'prompt' => 'For every user with sessions, show their `name` and `longest_streak`: the most consecutive days on which they had at least one session (1 if they never visited two days in a row).',
            'starter_code' => null,
            'solution' => 'WITH days AS (SELECT DISTINCT user_id, date(started_at) AS day FROM sessions), islands AS (SELECT user_id, julianday(day) - ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY day) AS island FROM days), runs AS (SELECT user_id, COUNT(*) AS length FROM islands GROUP BY user_id, island) SELECT u.name, MAX(r.length) AS longest_streak FROM runs AS r JOIN users AS u ON u.id = r.user_id GROUP BY u.id;',
            'hint' => 'Build the islands as in the lesson, count each island\'s days, then take each user\'s `MAX`.',
            'order_matters' => false,
        ],
    ],
];
