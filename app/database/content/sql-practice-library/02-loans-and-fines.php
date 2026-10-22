<?php

// Lesson 2 of the SQL practice: library course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/library.sql.

return [
    'slug' => 'loans-and-fines',
    'title' => 'Loans and fines',
    'summary' => 'Working with loan dates: what\'s overdue and by how long, fines for late returns and for books still out, loans per month, and how long people keep books.',
    'starter_code' => "-- Loans still out. Try keeping only those past their due date.\nSELECT id, member_id, borrowed_on, due_on\nFROM loans\nWHERE returned_on IS NULL\nORDER BY due_on;",

    'quiz' => [
        [
            'question' => 'Why do the queries use \'2026-10-21\' instead of `date(\'now\')`?',
            'options' => [
                'date(\'now\') doesn\'t exist in SQLite',
                'So the answers stay the same whenever you run them; the data was made with that date as today',
                'Because it is faster',
                'Because loans are in the future',
            ],
            'correct_option' => 1,
            'explanation' => 'With `date(\'now\')`, every loan would eventually be overdue.',
        ],
        [
            'question' => 'How many loans are overdue today?',
            'options' => ['3', '5', '11', '12'],
            'correct_option' => 1,
            'explanation' => '11 loans are out, and 5 of them are past their due date.',
        ],
        [
            'question' => 'Which overdue loan is the most overdue, and by how many days?',
            'options' => ['Klara and the Sun, 22 days', 'Guards! Guards!, 34 days', 'Sapiens, 16 days', 'Murder on the Orient Express, 9 days'],
            'correct_option' => 1,
            'explanation' => 'Bea Reid\'s Guards! Guards! was due on 17 September.',
        ],
        [
            'question' => 'Why doesn\'t `WHERE returned_on > due_on` include books still out?',
            'options' => [
                'Because they aren\'t late',
                'returned_on is NULL for them, and a comparison with NULL is never true',
                'Because of the date format',
                'It does include them',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `COALESCE(returned_on, \'2026-10-21\')` to count them up to today.',
        ],
        [
            'question' => 'How much was charged in fines for books returned late, in total?',
            'options' => ['12.00', '24.75', '99.00', '3.00'],
            'correct_option' => 1,
            'explanation' => '12 late returns, 99 days late in all, at 0.25 a day.',
        ],
        [
            'question' => 'Which member paid the most in fines for late returns?',
            'options' => ['Jon Ahmed', 'Bea Reid', 'Alex Moss', 'Mia Vale'],
            'correct_option' => 2,
            'explanation' => 'Alex Moss: 5.00. Jon Ahmed (4.00) also has two books overdue now.',
        ],
        [
            'question' => 'In which month were the most books borrowed?',
            'options' => ['February', 'May', 'July', 'October'],
            'correct_option' => 1,
            'explanation' => '14 loans started in May 2026.',
        ],
        [
            'question' => 'Why leave open loans out of the average loan length?',
            'options' => [
                'They are overdue',
                'Their length isn\'t known yet; counting them only up to today would pull the average down',
                'AVG can\'t handle dates',
                'They belong to another branch',
            ],
            'correct_option' => 1,
            'explanation' => 'Unfinished cases make durations look shorter than they are, a common reporting trap.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Overdue list',
            'prompt' => 'For each overdue loan (still out, due before 2026-10-21), show the member\'s `name`, the book\'s `title` and `days_overdue` as a whole number.',
            'starter_code' => null,
            'solution' => "SELECT m.name, b.title, CAST(julianday('2026-10-21') - julianday(l.due_on) AS INTEGER) AS days_overdue FROM loans AS l JOIN members AS m ON m.id = l.member_id JOIN copies AS c ON c.id = l.copy_id JOIN books AS b ON b.id = c.book_id WHERE l.returned_on IS NULL AND l.due_on < '2026-10-21';",
            'hint' => 'Join loans to members, and through copies to books; keep open loans past their due date.',
            'order_matters' => false,
        ],
        [
            'title' => 'Fines paid',
            'prompt' => 'For each member who returned a book late, show their `name` and total `fines` at 0.25 a day late.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, SUM(julianday(l.returned_on) - julianday(l.due_on)) * 0.25 AS fines FROM loans AS l JOIN members AS m ON m.id = l.member_id WHERE l.returned_on > l.due_on GROUP BY m.id;',
            'hint' => 'Sum the days late per member and multiply by 0.25.',
            'order_matters' => false,
        ],
        [
            'title' => 'Fines still growing',
            'prompt' => 'For each member with an overdue loan right now, show their `name` and `owed`: the fines on all their overdue loans counted up to 2026-10-21, at 0.25 a day.',
            'starter_code' => null,
            'solution' => "SELECT m.name, SUM(julianday('2026-10-21') - julianday(l.due_on)) * 0.25 AS owed FROM loans AS l JOIN members AS m ON m.id = l.member_id WHERE l.returned_on IS NULL AND l.due_on < '2026-10-21' GROUP BY m.id;",
            'hint' => 'Like the overdue list, but summed per member and multiplied by 0.25.',
            'order_matters' => false,
        ],
        [
            'title' => 'Busy months',
            'prompt' => 'Show each `month` (`YYYY-MM`) in which at least 8 loans started, with the number of `loans`.',
            'starter_code' => null,
            'solution' => 'SELECT substr(borrowed_on, 1, 7) AS month, COUNT(*) AS loans FROM loans GROUP BY month HAVING COUNT(*) >= 8;',
            'hint' => 'Group by month and filter the groups with `HAVING`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Days by genre',
            'prompt' => 'For each genre, show `genre` and `average_days`: the average length of returned loans of its books, rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT b.genre, ROUND(AVG(julianday(l.returned_on) - julianday(l.borrowed_on)), 1) AS average_days FROM loans AS l JOIN copies AS c ON c.id = l.copy_id JOIN books AS b ON b.id = c.book_id WHERE l.returned_on IS NOT NULL GROUP BY b.genre;',
            'hint' => 'Join loans through copies to books, keep returned loans and group by genre.',
            'order_matters' => false,
        ],
        [
            'title' => 'On time',
            'prompt' => 'In one row, show how many loans have been `returned`, how many of those were returned `on_time` (on or before the due date) and the `on_time_pct`, rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT COUNT(*) AS returned, COUNT(*) FILTER (WHERE returned_on <= due_on) AS on_time, ROUND(100.0 * COUNT(*) FILTER (WHERE returned_on <= due_on) / COUNT(*), 1) AS on_time_pct FROM loans WHERE returned_on IS NOT NULL;',
            'hint' => 'Keep returned loans, then count all of them and those with `returned_on <= due_on`.',
            'order_matters' => false,
        ],
    ],
];
