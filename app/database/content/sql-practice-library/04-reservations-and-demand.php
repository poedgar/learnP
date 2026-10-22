<?php

// Lesson 4 of the SQL practice: library course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/library.sql.

return [
    'slug' => 'reservations-and-demand',
    'title' => 'Reservations and demand',
    'summary' => 'Waiting lists with ROW_NUMBER, how long reservations take, when the next copy is due back, loans per copy to find real demand, idle copies, and each branch\'s share of the lending.',
    'starter_code' => "-- Waiting reservations, in queue order. Try adding the book titles.\nSELECT book_id, member_id, reserved_on,\n       ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY reserved_on) AS position\nFROM reservations\nWHERE fulfilled_on IS NULL;",

    'quiz' => [
        [
            'question' => 'How many reservations are still waiting?',
            'options' => ['2', '4', '6', '10'],
            'correct_option' => 1,
            'explanation' => 'Three for Klara and the Sun and one for Murder on the Orient Express.',
        ],
        [
            'question' => 'Why use `ROW_NUMBER()` rather than `RANK()` for a place in the queue?',
            'options' => [
                'RANK doesn\'t work with PARTITION BY',
                'Two people can\'t share a place in a queue; ROW_NUMBER always gives different numbers',
                'ROW_NUMBER is sorted',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Break ties on the same day with a second sort column such as the id.',
        ],
        [
            'question' => 'Who is first in the queue for Klara and the Sun?',
            'options' => ['Bea Reid', 'Dev Shah', 'Ivy Bell', 'Finn Park'],
            'correct_option' => 1,
            'explanation' => 'Dev Shah reserved it on 2 October, before Ivy Bell (5 October) and Bea Reid (12 October).',
        ],
        [
            'question' => 'On average, how many days did fulfilled reservations wait?',
            'options' => ['1.0', '7.0', '8.7', '19.0'],
            'correct_option' => 2,
            'explanation' => 'Six fulfilled reservations waited 52 days in all: 8.7 on average.',
        ],
        [
            'question' => 'Which book has the most loans per copy?',
            'options' => ['Guards! Guards!', 'Klara and the Sun', 'Murder on the Orient Express', 'A Wizard of Earthsea'],
            'correct_option' => 2,
            'explanation' => '14 loans across 3 copies: about 4.7 each.',
        ],
        [
            'question' => 'After `copies LEFT JOIN loans`, why `COUNT(DISTINCT c.id)` to count copies?',
            'options' => [
                'Because ids can be NULL',
                'Each copy appears once per loan after the join, so a plain count would count loans',
                'DISTINCT is faster',
                'It isn\'t needed',
            ],
            'correct_option' => 1,
            'explanation' => 'Joins multiply rows; count what you mean.',
        ],
        [
            'question' => 'How many copies have never been borrowed?',
            'options' => ['2', '4', '6', '8'],
            'correct_option' => 2,
            'explanation' => 'Six copies, including the only copies of The Dispossessed and The Ocean at the End of the Lane.',
        ],
        [
            'question' => 'Which branch\'s copies were borrowed least?',
            'options' => ['Central', 'Riverside', 'Hillcrest', 'They are equal'],
            'correct_option' => 2,
            'explanation' => 'Hillcrest\'s copies account for 12 of the 75 loans; it also has the fewest copies.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'The queue',
            'prompt' => 'For each waiting reservation, show the book\'s `title`, the member\'s `name`, `reserved_on` and their `position` in that book\'s queue (1 for the earliest).',
            'starter_code' => null,
            'solution' => 'SELECT b.title, m.name, r.reserved_on, ROW_NUMBER() OVER (PARTITION BY r.book_id ORDER BY r.reserved_on, r.id) AS position FROM reservations AS r JOIN books AS b ON b.id = r.book_id JOIN members AS m ON m.id = r.member_id WHERE r.fulfilled_on IS NULL;',
            'hint' => 'Join reservations to books and members, keep the waiting ones, and number them per book by `reserved_on`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Average wait',
            'prompt' => 'In one row, show the number of `fulfilled` reservations and `average_days` waited, rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT COUNT(*) AS fulfilled, ROUND(AVG(julianday(fulfilled_on) - julianday(reserved_on)), 1) AS average_days FROM reservations WHERE fulfilled_on IS NOT NULL;',
            'hint' => 'Keep fulfilled reservations and average the difference of the `julianday()`s.',
            'order_matters' => false,
        ],
        [
            'title' => 'Next one back',
            'prompt' => 'For each book with waiting reservations, show its `title`, how many people are `waiting`, and `next_due`: the earliest due date among its copies on loan.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, (SELECT COUNT(*) FROM reservations AS r WHERE r.book_id = b.id AND r.fulfilled_on IS NULL) AS waiting, (SELECT MIN(l.due_on) FROM loans AS l JOIN copies AS c ON c.id = l.copy_id WHERE c.book_id = b.id AND l.returned_on IS NULL) AS next_due FROM books AS b WHERE EXISTS (SELECT 1 FROM reservations AS r WHERE r.book_id = b.id AND r.fulfilled_on IS NULL);',
            'hint' => 'Two separate aggregates per book (waiting reservations, open loans) are easiest as scalar subqueries; joining both tables at once would multiply the rows.',
            'order_matters' => false,
        ],
        [
            'title' => 'In demand',
            'prompt' => 'Show the `title`, number of `copies` and `loans_per_copy` (rounded to 1 decimal place) of books with at least 2.5 loans per copy.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, COUNT(DISTINCT c.id) AS copies, ROUND(1.0 * COUNT(l.id) / COUNT(DISTINCT c.id), 1) AS loans_per_copy FROM books AS b JOIN copies AS c ON c.book_id = b.id LEFT JOIN loans AS l ON l.copy_id = c.id GROUP BY b.id HAVING 1.0 * COUNT(l.id) / COUNT(DISTINCT c.id) >= 2.5;',
            'hint' => 'Books, their copies and (LEFT JOIN) their loans; divide `COUNT(l.id)` by `COUNT(DISTINCT c.id)` with `1.0 *` and filter with `HAVING`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Idle copies',
            'prompt' => 'Show the copy `id`, the book\'s `title` and the `branch` name of every copy that has never been borrowed.',
            'starter_code' => null,
            'solution' => 'SELECT c.id, b.title, br.name AS branch FROM copies AS c JOIN books AS b ON b.id = c.book_id JOIN branches AS br ON br.id = c.branch_id WHERE NOT EXISTS (SELECT 1 FROM loans AS l WHERE l.copy_id = c.id);',
            'hint' => '`NOT EXISTS` a loan for the copy.',
            'order_matters' => false,
        ],
        [
            'title' => 'Share of lending',
            'prompt' => 'For each branch, show its `name`, the number of `loans` of copies it holds, and `pct`: its share of all loans, rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT br.name, COUNT(*) AS loans, ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 1) AS pct FROM loans AS l JOIN copies AS c ON c.id = l.copy_id JOIN branches AS br ON br.id = c.branch_id GROUP BY br.id;',
            'hint' => 'Count loans per branch, and divide by `SUM(COUNT(*)) OVER ()`, the total across all branches.',
            'order_matters' => false,
        ],
    ],
];
