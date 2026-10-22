<?php

// Lesson 1 of the SQL practice: library course. The theory is in the .md file with the same name.
// Each challenge's solution is compared with the learner's result. Quiz answers that quote numbers
// were checked against database/datasets/library.sql.

return [
    'slug' => 'finding-books',
    'title' => 'Finding books',
    'summary' => 'Meet the library data: books and copies, the catalogue with authors, copies per branch, what\'s on loan right now, what\'s available, and searching titles.',
    'starter_code' => "-- The catalogue. Try showing only Fantasy books.\nSELECT b.title, a.name AS author, b.genre, b.published_year\nFROM books AS b\nJOIN authors AS a ON a.id = b.author_id\nORDER BY a.name, b.published_year;",

    'quiz' => [
        [
            'question' => 'What is the difference between a book and a copy here?',
            'options' => [
                'There is none',
                'A book is a title in the catalogue; a copy is one physical item of it, kept at a branch',
                'A copy is a photocopy',
                'A book is borrowed; a copy is reserved',
            ],
            'correct_option' => 1,
            'explanation' => 'Loans are of copies; availability is about whether any copy of a book is free.',
        ],
        [
            'question' => 'How do you recognise a loan that is still out?',
            'options' => ['`due_on IS NULL`', '`returned_on IS NULL`', '`borrowed_on > due_on`', '`returned_on = due_on`'],
            'correct_option' => 1,
            'explanation' => 'The return date is filled in when the copy comes back.',
        ],
        [
            'question' => 'Which branch holds the most copies?',
            'options' => ['Central', 'Riverside', 'Hillcrest', 'They are equal'],
            'correct_option' => 1,
            'explanation' => 'Riverside has 15 copies, Central 14 and Hillcrest 9.',
        ],
        [
            'question' => 'Which two books have no copy available right now?',
            'options' => [
                'Klara and the Sun and Murder on the Orient Express',
                'Sapiens and Guards! Guards!',
                'Emma and SPQR',
                'Pride and Prejudice and Beloved',
            ],
            'correct_option' => 0,
            'explanation' => 'All three copies of each are on loan.',
        ],
        [
            'question' => 'Which author has the most books in the catalogue?',
            'options' => ['Agatha Christie', 'Neil Gaiman', 'Terry Pratchett', 'Jane Austen'],
            'correct_option' => 2,
            'explanation' => 'Three Discworld novels: Guards! Guards!, Small Gods and Night Watch.',
        ],
        [
            'question' => 'Does `title LIKE \'%the%\'` match "And Then There Were None"?',
            'options' => [
                'No, LIKE is case-sensitive',
                'Yes: LIKE ignores case for ASCII letters, and "the" appears inside "Then" and "There"',
                'Only if the word "the" stands alone',
                'It is an error',
            ],
            'correct_option' => 1,
            'explanation' => '`LIKE` matches text anywhere, even inside other words.',
        ],
        [
            'question' => 'How many books are there in the Fantasy genre?',
            'options' => ['3', '4', '5', '6'],
            'correct_option' => 3,
            'explanation' => 'Six: three Pratchetts, two Gaimans and A Wizard of Earthsea.',
        ],
        [
            'question' => 'To find copies that are available, which condition is right?',
            'options' => [
                '`WHERE l.returned_on IS NOT NULL` after joining loans',
                '`NOT EXISTS` an open loan (returned_on IS NULL) for that copy',
                '`WHERE l.due_on > \'2026-10-21\'`',
                '`COUNT(l.id) = 0`',
            ],
            'correct_option' => 1,
            'explanation' => 'A copy has many past loans; it\'s available when none of them is still open.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Modern books',
            'prompt' => 'Show the `title`, `author` (the author\'s name) and `published_year` of books published in 2000 or later.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, a.name AS author, b.published_year FROM books AS b JOIN authors AS a ON a.id = b.author_id WHERE b.published_year >= 2000;',
            'hint' => 'Join books to authors and filter on the year.',
            'order_matters' => false,
        ],
        [
            'title' => 'Branch shelves',
            'prompt' => 'For each branch, show its `name`, the number of `copies` it holds and the number of different `titles`.',
            'starter_code' => null,
            'solution' => 'SELECT br.name, COUNT(*) AS copies, COUNT(DISTINCT c.book_id) AS titles FROM copies AS c JOIN branches AS br ON br.id = c.branch_id GROUP BY br.id;',
            'hint' => '`COUNT(*)` for copies, `COUNT(DISTINCT book_id)` for titles.',
            'order_matters' => false,
        ],
        [
            'title' => 'Available now',
            'prompt' => 'For every book, show its `title` and how many copies are `available` (not on loan right now), including books with 0 available.',
            'starter_code' => null,
            'solution' => 'SELECT b.title, COUNT(c.id) AS available FROM books AS b LEFT JOIN copies AS c ON c.book_id = b.id AND NOT EXISTS (SELECT 1 FROM loans AS l WHERE l.copy_id = c.id AND l.returned_on IS NULL) GROUP BY b.id;',
            'hint' => 'LEFT JOIN books to the copies that have no open loan (put the `NOT EXISTS` in the `ON`), and count the copies.',
            'order_matters' => false,
        ],
        [
            'title' => 'Where is Sapiens',
            'prompt' => 'Show the `name` of each branch that has an available copy of *Sapiens* right now, and how many (`available`).',
            'starter_code' => null,
            'solution' => "SELECT br.name, COUNT(*) AS available FROM copies AS c JOIN books AS b ON b.id = c.book_id JOIN branches AS br ON br.id = c.branch_id WHERE b.title = 'Sapiens' AND NOT EXISTS (SELECT 1 FROM loans AS l WHERE l.copy_id = c.id AND l.returned_on IS NULL) GROUP BY br.id;",
            'hint' => 'Copies of Sapiens with no open loan, grouped by branch.',
            'order_matters' => false,
        ],
        [
            'title' => 'Search the catalogue',
            'prompt' => 'Show the `title` and author `name` of books whose title contains "the" anywhere, in any case.',
            'starter_code' => null,
            'solution' => "SELECT b.title, a.name FROM books AS b JOIN authors AS a ON a.id = b.author_id WHERE b.title LIKE '%the%';",
            'hint' => '`LIKE \'%the%\'` already ignores case.',
            'order_matters' => false,
        ],
        [
            'title' => 'Prolific authors',
            'prompt' => 'Show the `name` of every author with more than one book in the catalogue, their number of `books` and the year of their `newest` book.',
            'starter_code' => null,
            'solution' => 'SELECT a.name, COUNT(*) AS books, MAX(b.published_year) AS newest FROM authors AS a JOIN books AS b ON b.author_id = a.id GROUP BY a.id HAVING COUNT(*) > 1;',
            'hint' => 'Group books by author, keep groups with more than one, and take the `MAX` year.',
            'order_matters' => false,
        ],
    ],
];
