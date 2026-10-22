<?php

// Lesson 3 of the SQL practice: library course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/library.sql.

return [
    'slug' => 'members',
    'title' => 'Members',
    'summary' => 'Who uses the library: loans per member, members who never borrowed, each member\'s favourite genre with ties, borrowing at other branches, and members who have gone quiet.',
    'starter_code' => "-- Loans per member, including members with none. Try adding the date of their last loan.\nSELECT m.name, COUNT(l.id) AS loans\nFROM members AS m\nLEFT JOIN loans AS l ON l.member_id = m.id\nGROUP BY m.id\nORDER BY loans DESC;",

    'quiz' => [
        [
            'question' => 'Which two members have borrowed the most (10 loans each)?',
            'options' => ['Alex Moss and Jon Ahmed', 'Ella Lowe and Mia Vale', 'Cal Fox and Hal Ng', 'Bea Reid and Lou Kerr'],
            'correct_option' => 0,
            'explanation' => 'Alex Moss and Jon Ahmed both have 10 loans.',
        ],
        [
            'question' => 'How many members have never borrowed anything?',
            'options' => ['0', '1', '3', '5'],
            'correct_option' => 2,
            'explanation' => 'Pip Lund, Rae Hart and Sol Cruz, who joined only in September.',
        ],
        [
            'question' => 'Why use `RANK()` rather than `ROW_NUMBER()` to find a member\'s favourite genre?',
            'options' => [
                'RANK is faster',
                'With a tie, RANK gives both genres 1, while ROW_NUMBER would pick one of them arbitrarily',
                'ROW_NUMBER can\'t be partitioned',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Ella Lowe borrowed three crime and three fantasy books: both are her favourites.',
        ],
        [
            'question' => 'What does `PARTITION BY l.member_id` add to the ranking window?',
            'options' => [
                'It filters to one member',
                'It ranks genres separately for each member, so every member has their own place 1',
                'It groups the result by member',
                'It sorts the output',
            ],
            'correct_option' => 1,
            'explanation' => 'Without it, all members\' genre counts would be ranked against each other.',
        ],
        [
            'question' => 'What is `date(\'2026-10-21\', \'-90 days\')`?',
            'options' => ['`2026-07-21`', '`2026-07-23`', '`2026-07-24`', '`2026-08-21`'],
            'correct_option' => 1,
            'explanation' => '90 days before 21 October is 23 July (October 21 days, September 30, August 31, then 8 more back into July).',
        ],
        [
            'question' => 'Which member borrowed every one of their books from a branch other than their home branch?',
            'options' => ['Alex Moss', 'Jon Ahmed', 'Mia Vale', 'Ola Okoro'],
            'correct_option' => 1,
            'explanation' => 'All 10 of Jon Ahmed\'s loans were of copies held away from Hillcrest.',
        ],
        [
            'question' => 'Members with no loans and members who stopped borrowing: why treat them separately?',
            'options' => [
                'They are the same group',
                'They need different action: one never got started, the other used to come and stopped',
                'SQL can\'t combine them',
                'Members with no loans don\'t exist',
            ],
            'correct_option' => 1,
            'explanation' => 'A welcome and a "we miss you" are different messages.',
        ],
        [
            'question' => 'In `COUNT(*) FILTER (WHERE c.branch_id = m.branch_id)`, what is counted?',
            'options' => [
                'All loans',
                'Only the loans of copies held at the member\'s home branch',
                'The number of branches',
                'Loans with no branch',
            ],
            'correct_option' => 1,
            'explanation' => '`FILTER` limits the aggregate to rows where the condition holds.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Never borrowed',
            'prompt' => 'Show the `name` and `joined_on` date of members who have never borrowed a book.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, m.joined_on FROM members AS m WHERE NOT EXISTS (SELECT 1 FROM loans AS l WHERE l.member_id = m.id);',
            'hint' => '`NOT EXISTS` a loan for the member.',
            'order_matters' => false,
        ],
        [
            'title' => 'First and latest',
            'prompt' => 'For each member who has borrowed, show their `name`, the date of their `first_loan` and their `latest_loan`.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, MIN(l.borrowed_on) AS first_loan, MAX(l.borrowed_on) AS latest_loan FROM members AS m JOIN loans AS l ON l.member_id = m.id GROUP BY m.id;',
            'hint' => 'Group loans by member and take `MIN` and `MAX` of `borrowed_on`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Favourite genres',
            'prompt' => 'For each member who has borrowed, show their `name` and their favourite `genre`: the genre of most of their loans. If several genres tie, show a row for each.',
            'starter_code' => null,
            'solution' => 'SELECT name, genre FROM (SELECT m.name, b.genre, RANK() OVER (PARTITION BY l.member_id ORDER BY COUNT(*) DESC) AS place FROM loans AS l JOIN members AS m ON m.id = l.member_id JOIN copies AS c ON c.id = l.copy_id JOIN books AS b ON b.id = c.book_id GROUP BY l.member_id, b.genre) WHERE place = 1;',
            'hint' => 'Group by member and genre, rank with `RANK() OVER (PARTITION BY member ORDER BY COUNT(*) DESC)` in a subquery, and keep place 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'Away from home',
            'prompt' => 'For each member who has borrowed a copy held outside their home branch, show their `name` and the number of such `away_loans`.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, COUNT(*) AS away_loans FROM loans AS l JOIN members AS m ON m.id = l.member_id JOIN copies AS c ON c.id = l.copy_id WHERE c.branch_id <> m.branch_id GROUP BY m.id;',
            'hint' => 'Compare the copy\'s `branch_id` with the member\'s.',
            'order_matters' => false,
        ],
        [
            'title' => 'Gone quiet',
            'prompt' => 'Show the `name` and `last_loan` date of members who have borrowed before, but not in the 90 days up to 2026-10-21 (no loan on or after `date(\'2026-10-21\', \'-90 days\')`).',
            'starter_code' => null,
            'solution' => "SELECT m.name, MAX(l.borrowed_on) AS last_loan FROM members AS m JOIN loans AS l ON l.member_id = m.id GROUP BY m.id HAVING MAX(l.borrowed_on) < date('2026-10-21', '-90 days');",
            'hint' => 'Group loans by member and keep those whose latest loan is before the cutoff.',
            'order_matters' => false,
        ],
        [
            'title' => 'Keenest at each branch',
            'prompt' => 'For each branch, show the branch `name` (as `branch`), the `name` of the member based there with the most loans (as `member`), and their `loans`. Show every member who ties for first.',
            'starter_code' => null,
            'solution' => 'SELECT branch, member, loans FROM (SELECT br.name AS branch, m.name AS member, COUNT(*) AS loans, RANK() OVER (PARTITION BY m.branch_id ORDER BY COUNT(*) DESC) AS place FROM loans AS l JOIN members AS m ON m.id = l.member_id JOIN branches AS br ON br.id = m.branch_id GROUP BY m.id) WHERE place = 1;',
            'hint' => 'Count loans per member, rank within the member\'s home branch, keep place 1.',
            'order_matters' => false,
        ],
    ],
];
