<?php

// Lesson 10 of the Advanced SQL course. The theory is in the .md file with the same name.
// The dataset has no full-text tables: learners create their own in the same run as the
// search. The last challenge's check_sql adds a review after the learner's SQL, to see that
// their trigger keeps the index in step.

return [
    'slug' => 'full-text-search',
    'title' => 'Full-text search',
    'summary' => 'Search text by words with SQLite\'s FTS5: phrases, prefixes, AND/OR/NOT, ranking the best matches, highlighting them, and keeping the index up to date.',
    'starter_code' => "-- Build a full-text index of the reviews, then search it. Try 'creep*' or '\"desert planet\"'.\nCREATE VIRTUAL TABLE review_search USING fts5(body);\nINSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;\n\nSELECT rowid, body FROM review_search WHERE review_search MATCH 'slow';",

    'quiz' => [
        [
            'question' => "Which of these is **not** a problem with searching text using `LIKE '%slow%'`?",
            'options' => [
                'It has to read every row',
                'It matches inside other words',
                'It can\'t rank the best matches first',
                'It can\'t find text in the middle of a column',
            ],
            'correct_option' => 3,
            'explanation' => '`LIKE` with `%` on both sides finds text anywhere, which is exactly why it matches inside words and can\'t use an index.',
        ],
        [
            'question' => 'How do you create a full-text index over the `body` of reviews?',
            'options' => [
                '`CREATE INDEX review_search ON reviews (body);`',
                '`CREATE VIRTUAL TABLE review_search USING fts5(body);`',
                '`CREATE FULLTEXT INDEX ON reviews (body);`',
                '`ALTER TABLE reviews ADD FULLTEXT (body);`',
            ],
            'correct_option' => 1,
            'explanation' => 'An FTS5 index is a virtual table. You then fill it, for example with `INSERT … SELECT` from `reviews`.',
        ],
        [
            'question' => "Does `MATCH 'slow'` find a review containing \"Slow start\"?",
            'options' => [
                'Yes, words match regardless of letter case',
                'No, the case must match',
                'Only with `MATCH \'Slow\'`',
                'Only with the porter tokenizer',
            ],
            'correct_option' => 0,
            'explanation' => 'FTS5\'s default tokenizer ignores letter case.',
        ],
        [
            'question' => "What does `MATCH 'creep*'` find?",
            'options' => [
                'Only the word "creep"',
                'Words starting with "creep", like creepy and creeping',
                'Any text containing the letters "creep"',
                'Nothing; `*` isn\'t allowed',
            ],
            'correct_option' => 1,
            'explanation' => 'A `*` after a word makes it a prefix search.',
        ],
        [
            'question' => "What is the difference between `MATCH 'desert planet'` and `MATCH '\"desert planet\"'`?",
            'options' => [
                'There is none',
                'The first needs both words anywhere; the second needs them together, in that order',
                'The first needs either word; the second needs both',
                'The second is a syntax error',
            ],
            'correct_option' => 1,
            'explanation' => 'Separate words are joined by an implicit `AND`. Double quotes make a phrase.',
        ],
        [
            'question' => 'What does `ORDER BY rank` do in an FTS5 query?',
            'options' => [
                'Sorts by the rowid',
                'Puts the best matches first, because a lower rank is a better match',
                'Puts the worst matches first',
                'Sorts alphabetically',
            ],
            'correct_option' => 1,
            'explanation' => 'FTS5 scores each match so that better matches get lower, more negative, ranks.',
        ],
        [
            'question' => "In an index created with `fts5(title, author, genre)`, what does `MATCH 'author:tolkien'` search?",
            'options' => [
                'Every column for "author" and "tolkien"',
                'Only the `author` column, for "tolkien"',
                'The `title` column only',
                'It\'s a syntax error',
            ],
            'correct_option' => 1,
            'explanation' => 'A column name and a colon in front of a word limit that word to one column.',
        ],
        [
            'question' => 'You add a new review to `reviews`. When does it appear in `review_search`?',
            'options' => [
                'Immediately, automatically',
                'Only if a trigger (or your own code) inserts it into the full-text table too',
                'The next time the database restarts',
                'Never; full-text tables can\'t change',
            ],
            'correct_option' => 1,
            'explanation' => 'The full-text table is separate from `reviews`. Triggers on `reviews` are the usual way to keep it in step.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Rings and wind',
            'prompt' => 'Build a full-text index of the books\' titles, and use it to find the `title` of every book whose title contains the word "ring" or the word "wind".',
            'starter_code' => null,
            'solution' => "CREATE VIRTUAL TABLE title_search USING fts5(title); INSERT INTO title_search (rowid, title) SELECT id, title FROM books; SELECT title FROM title_search WHERE title_search MATCH 'ring OR wind';",
            'hint' => "Create the table with `fts5(title)`, fill it from `books`, then search with `MATCH 'ring OR wind'`.",
            'order_matters' => false,
        ],
        [
            'title' => 'All things magic',
            'prompt' => 'Find every review containing a word that starts with "magic" (so "magical" counts too). Show the review\'s `id` and the book\'s `title`.',
            'starter_code' => null,
            'solution' => "CREATE VIRTUAL TABLE review_search USING fts5(body); INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews; SELECT r.id, b.title FROM review_search AS s JOIN reviews AS r ON r.id = s.rowid JOIN books AS b ON b.id = r.book_id WHERE review_search MATCH 'magic*';",
            'hint' => "Index the review bodies with each review's id as the `rowid`, search with `MATCH 'magic*'`, then join to `reviews` and `books` on that rowid.",
            'order_matters' => false,
        ],
        [
            'title' => 'In one sitting',
            'prompt' => 'Find the reviews that contain the exact phrase "one sitting". Show their `id` and `body`.',
            'starter_code' => null,
            'solution' => "CREATE VIRTUAL TABLE review_search USING fts5(body); INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews; SELECT rowid AS id, body FROM review_search WHERE review_search MATCH '\"one sitting\"';",
            'hint' => "Put the phrase in double quotes inside the single-quoted search text: `MATCH '\"one sitting\"'`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Best matches first',
            'prompt' => 'Search the reviews for "long" or "slow". Show each match\'s `id` and `body`, best match first, and sort equally good matches by id.',
            'starter_code' => null,
            'solution' => "CREATE VIRTUAL TABLE review_search USING fts5(body); INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews; SELECT rowid AS id, body FROM review_search WHERE review_search MATCH 'long OR slow' ORDER BY rank, rowid;",
            'hint' => "`MATCH 'long OR slow'`, then `ORDER BY rank, rowid`. Some reviews tie on rank, so the second sort key matters.",
            'order_matters' => true,
        ],
        [
            'title' => 'Highlight the creeps',
            'prompt' => 'Find the reviews with a word starting with "creep", and show each one\'s `id` and its body with the matching word wrapped in square brackets, like `[Creepy]`, in a column named `highlighted`.',
            'starter_code' => null,
            'solution' => "CREATE VIRTUAL TABLE review_search USING fts5(body); INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews; SELECT rowid AS id, highlight(review_search, 0, '[', ']') AS highlighted FROM review_search WHERE review_search MATCH 'creep*';",
            'hint' => "`highlight(review_search, 0, '[', ']')`: the table, the column's position (0 for the first), then the opening and closing markers.",
            'order_matters' => false,
        ],
        [
            'title' => 'A search that stays current',
            'prompt' => 'Create a full-text table named `review_search` over the reviews\' `body`, using each review\'s `id` as the `rowid`, fill it with the existing reviews, and add a trigger so that every review inserted from now on is added to it too.',
            'starter_code' => null,
            'solution' => 'CREATE VIRTUAL TABLE review_search USING fts5(body); INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews; CREATE TRIGGER reviews_into_search AFTER INSERT ON reviews BEGIN INSERT INTO review_search (rowid, body) VALUES (NEW.id, NEW.body); END;',
            'check_sql' => "INSERT INTO reviews (book_id, customer_id, rating, body, reviewed_on) VALUES (13, 10, 5, 'Cyberpunk at its sharpest.', '2026-04-09');\nSELECT rowid, body FROM review_search WHERE review_search MATCH 'cyberpunk OR dragon' ORDER BY rowid",
            'hint' => 'Three statements: `CREATE VIRTUAL TABLE`, `INSERT … SELECT` from `reviews`, and an `AFTER INSERT ON reviews` trigger inserting `NEW.id, NEW.body`.',
            'order_matters' => true,
        ],
    ],
];
