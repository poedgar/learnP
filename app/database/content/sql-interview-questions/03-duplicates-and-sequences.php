<?php

// Lesson 3 of the SQL interview questions course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/company.sql.

return [
    'slug' => 'duplicates-and-sequences',
    'title' => 'Duplicates and sequences',
    'summary' => 'Finding and removing duplicates (and deciding what "the same" means), first and last rows per group, consecutive days with gaps and islands, and finding what\'s missing without the NOT IN trap.',
    'starter_code' => "-- Logins per employee. Try counting distinct days as well.\nSELECT employee_id, MIN(logged_in) AS first_login, COUNT(*) AS logins\nFROM logins\nGROUP BY employee_id;",

    'quiz' => [
        [
            'question' => 'Why does `GROUP BY email HAVING COUNT(*) > 1` find no duplicates here?',
            'options' => [
                'There are no duplicates',
                'The duplicate emails differ in capital letters, and text comparison is case-sensitive',
                'HAVING is wrong',
                'Emails can\'t be grouped',
            ],
            'correct_option' => 1,
            'explanation' => 'Group by `lower(email)` to treat them as the same.',
        ],
        [
            'question' => 'Which ids does the Omar Farouk duplicate have, and which one is kept by `MIN(id)`?',
            'options' => ['20 and 21; 20 is kept', '20 and 21; 21 is kept', '10 and 20; 10 is kept', 'There is only one'],
            'correct_option' => 0,
            'explanation' => 'Keeping the lowest id is a common, predictable rule.',
        ],
        [
            'question' => 'What happens with `WHERE id NOT IN (subquery)` if the subquery returns a NULL?',
            'options' => [
                'The NULL is ignored',
                'No rows are returned at all, because `x NOT IN (..., NULL)` is never true',
                'An error',
                'Every row is returned',
            ],
            'correct_option' => 1,
            'explanation' => '`NOT EXISTS` avoids the problem.',
        ],
        [
            'question' => 'Why reduce logins to one row per employee per day before finding streaks?',
            'options' => [
                'To save memory',
                'Two logins on one day would give that day two rows and break the date-minus-row-number pattern',
                'Because of time zones',
                'It isn\'t needed',
            ],
            'correct_option' => 1,
            'explanation' => 'The trick needs exactly one row per day.',
        ],
        [
            'question' => 'Ada Ferreira logged in on 1, 2, 3 (twice), 4 and 6 October. How long is her longest streak?',
            'options' => ['3 days', '4 days', '5 days', '6 days'],
            'correct_option' => 1,
            'explanation' => '1 to 4 October; the gap on the 5th ends it.',
        ],
        [
            'question' => 'How many employees logged in on at least three consecutive days?',
            'options' => ['2', '3', '4', '5'],
            'correct_option' => 2,
            'explanation' => 'Ada Ferreira, Priya Nair, Ken Adeyemi and Lena Vogel.',
        ],
        [
            'question' => 'To get the whole row of each employee\'s first login (not just its time), what do you use?',
            'options' => [
                '`MIN(*)`',
                '`ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY logged_in)` and keep row 1',
                '`GROUP BY id`',
                '`DISTINCT ON`',
            ],
            'correct_option' => 1,
            'explanation' => '`MIN` gives one value; numbering the rows lets you keep a whole row.',
        ],
        [
            'question' => 'How many employees have never logged in?',
            'options' => ['8', '10', '11', '22'],
            'correct_option' => 2,
            'explanation' => '11 different employees appear in `logins`, so the other 11 of the 22 never did.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Same email',
            'prompt' => 'Show each `email` (in lower case) that belongs to more than one employee row, ignoring case, and how many `copies` there are.',
            'starter_code' => null,
            'solution' => 'SELECT lower(email) AS email, COUNT(*) AS copies FROM employees GROUP BY lower(email) HAVING COUNT(*) > 1;',
            'hint' => 'Group by `lower(email)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Clean up',
            'prompt' => 'Delete the duplicate employee rows (same email, ignoring case), keeping the row with the lowest `id` of each.',
            'starter_code' => null,
            'solution' => 'DELETE FROM employees WHERE id NOT IN (SELECT MIN(id) FROM employees GROUP BY lower(email));',
            'check_sql' => 'SELECT id, name FROM employees ORDER BY id',
            'hint' => 'Keep `MIN(id)` per `lower(email)` and delete every other id.',
            'order_matters' => false,
        ],
        [
            'title' => 'Three in a row',
            'prompt' => 'Show the `name` of every employee who logged in on at least three consecutive days.',
            'starter_code' => null,
            'solution' => 'WITH days AS (SELECT DISTINCT employee_id, date(logged_in) AS day FROM logins), islands AS (SELECT employee_id, julianday(day) - ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY day) AS island FROM days) SELECT DISTINCT e.name FROM islands AS i JOIN employees AS e ON e.id = i.employee_id GROUP BY i.employee_id, i.island HAVING COUNT(*) >= 3;',
            'hint' => 'Distinct days, islands as in the lesson, then group by employee and island and keep islands of 3 or more days.',
            'order_matters' => false,
        ],
        [
            'title' => 'First login',
            'prompt' => 'For each employee who has logged in, show their `name` and the `id` of their first login (`first_login_id`).',
            'starter_code' => null,
            'solution' => 'SELECT e.name, l.id AS first_login_id FROM (SELECT id, employee_id, ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY logged_in) AS n FROM logins) AS l JOIN employees AS e ON e.id = l.employee_id WHERE l.n = 1;',
            'hint' => 'Number each employee\'s logins by time and keep row 1, then join for the name.',
            'order_matters' => false,
        ],
        [
            'title' => 'Never logged in',
            'prompt' => 'Show the `name` of every employee with no logins.',
            'starter_code' => null,
            'solution' => 'SELECT e.name FROM employees AS e WHERE NOT EXISTS (SELECT 1 FROM logins AS l WHERE l.employee_id = e.id);',
            'hint' => '`NOT EXISTS` a login, or a `LEFT JOIN` with `IS NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Twice in a day',
            'prompt' => 'Show the `name` and `day` (`YYYY-MM-DD`) for each time an employee logged in more than once on the same day, with the number of `logins` that day.',
            'starter_code' => null,
            'solution' => 'SELECT e.name, date(l.logged_in) AS day, COUNT(*) AS logins FROM logins AS l JOIN employees AS e ON e.id = l.employee_id GROUP BY l.employee_id, date(l.logged_in) HAVING COUNT(*) > 1;',
            'hint' => 'Group by employee and `date(logged_in)`, and keep groups with more than one row.',
            'order_matters' => false,
        ],
    ],
];
