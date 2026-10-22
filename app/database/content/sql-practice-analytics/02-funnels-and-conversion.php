<?php

// Lesson 2 of the SQL practice: product analytics course. The theory is in the .md file with the
// same name. Quiz answers that quote numbers were checked against database/datasets/analytics.sql.

return [
    'slug' => 'funnels-and-conversion',
    'title' => 'Funnels and conversion',
    'summary' => 'Follow users from signup to subscription: counting each step, conversion rates overall and per group, finding the people who got stuck, time between steps, and leaving out those who haven\'t had time yet.',
    'starter_code' => "-- The funnel, one column per step. Add the subscribe step.\nSELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'signup') AS signed_up,\n       COUNT(DISTINCT user_id) FILTER (WHERE event = 'view_book') AS viewed,\n       COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial') AS trialled\nFROM events;",

    'quiz' => [
        [
            'question' => 'Why count `DISTINCT user_id` for each funnel step rather than `COUNT(*)`?',
            'options' => [
                'It is faster',
                'A user can do a step many times (view many books), but a funnel counts people',
                'COUNT(*) ignores NULLs',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Ten book views by one person are one person who viewed a book.',
        ],
        [
            'question' => 'How many users have reached each step: signed up, viewed a book, started a trial, subscribed?',
            'options' => ['30, 25, 20, 7', '30, 21, 9, 5', '30, 30, 20, 7', '66 events in all'],
            'correct_option' => 0,
            'explanation' => 'Each step loses some people: 30 → 25 → 20 → 7.',
        ],
        [
            'question' => 'What share of users who started a trial went on to subscribe, to one decimal place?',
            'options' => ['23.3%', '28.0%', '35.0%', '80.0%'],
            'correct_option' => 2,
            'explanation' => '7 of 20: 35.0%.',
        ],
        [
            'question' => 'Which referrer brought 6 users and not a single subscriber?',
            'options' => ['ads', 'friend', 'search', 'social'],
            'correct_option' => 2,
            'explanation' => 'Group users by referrer and count those with a subscribe event.',
        ],
        [
            'question' => 'What does `julianday(b) - julianday(a)` give?',
            'options' => ['The number of months between them', 'The number of days from a to b, with a fraction for hours', 'A date', 'Always a whole number'],
            'correct_option' => 1,
            'explanation' => 'Julian day numbers count days, so subtracting gives days (12 hours is 0.5).',
        ],
        [
            'question' => 'How do you find users who started a trial but never subscribed?',
            'options' => [
                '`WHERE event = \'start_trial\' AND event != \'subscribe\'`',
                '`EXISTS` for the trial event and `NOT EXISTS` for the subscribe event',
                '`GROUP BY event`',
                '`LEFT JOIN events` with no condition',
            ],
            'correct_option' => 1,
            'explanation' => 'A single events row can\'t be both; you need to ask about two different rows for the same user.',
        ],
        [
            'question' => 'Why leave out trials that started less than 14 days ago when measuring trial conversion?',
            'options' => [
                'They are test accounts',
                'Those users can\'t have subscribed yet, so counting them as lost makes conversion look worse than it is',
                'Their events are incomplete',
                'SQLite can\'t compare recent dates',
            ],
            'correct_option' => 1,
            'explanation' => 'Only judge people who have had the full chance to convert.',
        ],
        [
            'question' => 'What is `date(\'2026-07-01\', \'-14 days\')`?',
            'options' => ['`2026-06-14`', '`2026-06-17`', '`2026-06-16`', '`2026-07-15`'],
            'correct_option' => 1,
            'explanation' => 'June has 30 days: 1 July minus 14 days is 17 June.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Every step',
            'prompt' => 'Show each `event` type and how many different `users` did it, one row per event type.',
            'starter_code' => null,
            'solution' => 'SELECT event, COUNT(DISTINCT user_id) AS users FROM events GROUP BY event;',
            'hint' => 'Group the events by `event` and count distinct users.',
            'order_matters' => false,
        ],
        [
            'title' => 'Trial conversion',
            'prompt' => 'In one row, show how many users started a trial (`trials`), how many subscribed (`subscribers`), and `conversion_pct`: subscribers as a percentage of trials, rounded to one decimal place.',
            'starter_code' => null,
            'solution' => "SELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial') AS trials, COUNT(DISTINCT user_id) FILTER (WHERE event = 'subscribe') AS subscribers, ROUND(100.0 * COUNT(DISTINCT user_id) FILTER (WHERE event = 'subscribe') / COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial'), 1) AS conversion_pct FROM events;",
            'hint' => 'Two `COUNT(DISTINCT user_id) FILTER (...)` columns, and their ratio times `100.0`, rounded.',
            'order_matters' => false,
        ],
        [
            'title' => 'Looked, didn\'t try',
            'prompt' => 'Show the `name` of every user who viewed a book but never started a trial.',
            'starter_code' => null,
            'solution' => "SELECT u.name FROM users AS u WHERE EXISTS (SELECT 1 FROM events AS e WHERE e.user_id = u.id AND e.event = 'view_book') AND NOT EXISTS (SELECT 1 FROM events AS e WHERE e.user_id = u.id AND e.event = 'start_trial');",
            'hint' => '`EXISTS` for the view, `NOT EXISTS` for the trial, both correlated on the user.',
            'order_matters' => false,
        ],
        [
            'title' => 'Days to a trial',
            'prompt' => 'For each user who started a trial, show their `name` and `days_to_trial`: the whole number of calendar days from `signed_up_on` to the date of their trial.',
            'starter_code' => null,
            'solution' => "SELECT u.name, CAST(julianday(date(e.happened_at)) - julianday(u.signed_up_on) AS INTEGER) AS days_to_trial FROM users AS u JOIN events AS e ON e.user_id = u.id AND e.event = 'start_trial';",
            'hint' => 'Take the trial\'s `date(happened_at)` so both sides are plain dates, subtract their `julianday()`s and cast to an integer.',
            'order_matters' => false,
        ],
        [
            'title' => 'Which channel converts',
            'prompt' => 'For each referrer, show `referrer` (`unknown` for users without one), how many `users` it brought, and how many of them have ever subscribed (`subscribers`).',
            'starter_code' => null,
            'solution' => "SELECT COALESCE(u.referrer, 'unknown') AS referrer, COUNT(*) AS users, COUNT(*) FILTER (WHERE EXISTS (SELECT 1 FROM events AS e WHERE e.user_id = u.id AND e.event = 'subscribe')) AS subscribers FROM users AS u GROUP BY u.referrer;",
            'hint' => 'Group users by referrer. Count all of them, and count those for whom a subscribe event `EXISTS`, for example with `FILTER` or `SUM(CASE ...)`. A `LEFT JOIN` to the subscribe events with `COUNT(DISTINCT ...)` works too.',
            'order_matters' => false,
        ],
        [
            'title' => 'Still deciding',
            'prompt' => 'Today is 2026-07-01 and trials last 14 days. Show the `name` and `trial_started` (the date, `YYYY-MM-DD`) of users whose trial started on or after `date(\'2026-07-01\', \'-14 days\')` and who haven\'t subscribed.',
            'starter_code' => null,
            'solution' => "SELECT u.name, date(e.happened_at) AS trial_started FROM users AS u JOIN events AS e ON e.user_id = u.id AND e.event = 'start_trial' WHERE e.happened_at >= date('2026-07-01', '-14 days') AND NOT EXISTS (SELECT 1 FROM events AS s WHERE s.user_id = u.id AND s.event = 'subscribe');",
            'hint' => 'Join users to their trial event, keep recent ones, and rule out subscribers with `NOT EXISTS`.',
            'order_matters' => false,
        ],
    ],
];
