<?php

// Lesson 3 of the Advanced SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop_plus.sql.

return [
    'slug' => 'trees-and-hierarchies',
    'title' => 'Trees and hierarchies',
    'summary' => 'Query data that points at itself: managers and teams, categories and subcategories, with self-joins and recursive WITH queries.',
    'starter_code' => "-- Everyone in Priya's team, at any depth. Try starting from someone else.\nWITH RECURSIVE team (id, name, depth) AS (\n    SELECT id, name, 0 FROM staff WHERE name = 'Priya Shah'\n    UNION ALL\n    SELECT s.id, s.name, t.depth + 1\n    FROM staff AS s\n    JOIN team AS t ON s.manager_id = t.id\n)\nSELECT name, depth FROM team;",

    'quiz' => [
        [
            'question' => 'How do the `staff` and `categories` tables store their trees?',
            'options' => [
                'Each row holds the `id` of the row above it',
                'Each row lists the ids of all the rows below it',
                'A separate table stores every path',
                'The rows are stored in tree order',
            ],
            'correct_option' => 0,
            'explanation' => '`manager_id` and `parent_id` point at the row one level up. This is the simplest way to store a tree.',
        ],
        [
            'question' => 'How many self-joins does it take to show each member of staff with their manager **and** their manager\'s manager?',
            'options' => ['None', 'One', 'Two', 'As many as there are staff'],
            'correct_option' => 2,
            'explanation' => 'Each level up is one more join: `staff` to its manager, then the manager to theirs.',
        ],
        [
            'question' => 'In a recursive `WITH`, what does the first `SELECT` (before `UNION ALL`) do?',
            'options' => [
                'It runs once for every row found',
                'It gives the starting rows',
                'It decides when to stop',
                'It sorts the result',
            ],
            'correct_option' => 1,
            'explanation' => 'The first `SELECT` runs once and gives the starting point. The second runs repeatedly on the rows found by the previous round.',
        ],
        [
            'question' => 'When does a recursive `WITH` stop?',
            'options' => [
                'After 10 rounds',
                'When it reaches a row with `NULL` in it',
                'When a round finds no new rows',
                'Only when you add a `LIMIT`',
            ],
            'correct_option' => 2,
            'explanation' => 'Each round works on the rows from the previous round. Once a round adds nothing, there is nothing left to work on.',
        ],
        [
            'question' => 'Walking **up** the tree from Sam Doyle (Sam → Ravi → Priya → Margaret), how many rows does the chain contain, counting Sam?',
            'options' => ['1', '3', '4', '10'],
            'correct_option' => 2,
            'explanation' => 'Sam, Ravi, Priya and Margaret. Margaret\'s `manager_id` is `NULL`, which matches nobody, so the walk stops there.',
        ],
        [
            'question' => 'Which join, inside the recursive part, walks **down** the tree from the people found so far (`t`) to the people who report to them (`s`)?',
            'options' => [
                '`JOIN team AS t ON s.manager_id = t.id`',
                '`JOIN team AS t ON s.id = t.manager_id`',
                '`JOIN team AS t ON s.id = t.id`',
                '`JOIN team AS t ON s.manager_id = t.manager_id`',
            ],
            'correct_option' => 0,
            'explanation' => 'People who report to `t` have `t`\'s id as their `manager_id`. `s.id = t.manager_id` walks up instead.',
        ],
        [
            'question' => "Why does `ORDER BY path` (paths like `'Fiction > Fantasy'`) list each category directly under its parent?",
            'options' => [
                'Because SQLite sorts trees specially',
                'Because each child\'s path starts with its parent\'s path',
                'Because the categories were inserted in that order',
                'It doesn\'t; you need `ORDER BY depth`',
            ],
            'correct_option' => 1,
            'explanation' => '`\'Fiction > Fantasy\'` sorts right after `\'Fiction\'` and before `\'Fiction > Horror\'`, just like folders in a file browser.',
        ],
        [
            'question' => 'What does a condition like `WHERE c.steps < 10` in the recursive part protect against?',
            'options' => [
                'Rows with `NULL` in them',
                'Trees that are too wide',
                'A loop in the data, which would make the recursion run forever',
                'Duplicate rows in the result',
            ],
            'correct_option' => 2,
            'explanation' => 'If the data contains a loop, the walk never finds "no new rows". A step limit makes sure it stops.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Reporting lines',
            'prompt' => 'For every member of staff, show their `name`, their `role`, and the role of their manager in a column named `manager_role`. The owner has no manager, but must still appear.',
            'starter_code' => null,
            'solution' => 'SELECT s.name, s.role, m.role AS manager_role FROM staff AS s LEFT JOIN staff AS m ON m.id = s.manager_id;',
            'hint' => 'Join `staff AS s` to `staff AS m` on `m.id = s.manager_id`. A `LEFT JOIN` keeps the owner.',
            'order_matters' => false,
        ],
        [
            'title' => 'Biggest teams first',
            'prompt' => 'Show the `name` of every member of staff who manages at least one person, and their number of direct `reports`. Put the most reports first, and sort ties by name.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, COUNT(*) AS reports FROM staff AS m JOIN staff AS s ON s.manager_id = m.id GROUP BY m.id ORDER BY reports DESC, m.name;',
            'hint' => 'An inner `JOIN` from managers to the people who report to them keeps only real managers. Group by the manager and count.',
            'order_matters' => true,
        ],
        [
            'title' => 'Above Nina',
            'prompt' => 'List everyone above Nina Kowalski in the chain of command, nearest first: show their `name` and `steps` above her (her manager is 1 step up). Don\'t include Nina.',
            'starter_code' => null,
            'solution' => "WITH RECURSIVE chain (id, name, manager_id, steps) AS (SELECT m.id, m.name, m.manager_id, 1 FROM staff AS m JOIN staff AS n ON n.manager_id = m.id WHERE n.name = 'Nina Kowalski' UNION ALL SELECT s.id, s.name, s.manager_id, c.steps + 1 FROM staff AS s JOIN chain AS c ON s.id = c.manager_id) SELECT name, steps FROM chain ORDER BY steps;",
            'hint' => 'Walk up as in the lesson, but start from Nina\'s manager at step 1, or start from Nina at step 0 and filter her out at the end.',
            'order_matters' => true,
        ],
        [
            'title' => 'Payroll by branch',
            'prompt' => 'Margaret has two direct reports, each running a branch of the business. For each of them, show their `name`, the `headcount` of their whole branch (counting themselves and everyone below them at any depth) and its total `payroll` (the sum of salaries).',
            'starter_code' => null,
            'solution' => 'WITH RECURSIVE branch (root_id, id, salary) AS (SELECT id, id, salary FROM staff WHERE manager_id = 1 UNION ALL SELECT b.root_id, s.id, s.salary FROM staff AS s JOIN branch AS b ON s.manager_id = b.id) SELECT m.name, COUNT(*) AS headcount, SUM(b.salary) AS payroll FROM branch AS b JOIN staff AS m ON m.id = b.root_id GROUP BY m.id;',
            'hint' => 'Start the recursion from everyone whose `manager_id` is 1, and carry a `root_id` along, as in "Totals for a whole subtree". Then group by `root_id`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Shelf locations',
            'prompt' => 'For every book in `book_categories`, show the book\'s `title` and the full `path` of the category, written like `Fiction > Fantasy > Epic Fantasy`. A book in two categories appears twice.',
            'starter_code' => null,
            'solution' => "WITH RECURSIVE tree (id, path) AS (SELECT id, name FROM categories WHERE parent_id IS NULL UNION ALL SELECT c.id, t.path || ' > ' || c.name FROM categories AS c JOIN tree AS t ON c.parent_id = t.id) SELECT b.title, t.path FROM book_categories AS bc JOIN books AS b ON b.id = bc.book_id JOIN tree AS t ON t.id = bc.category_id;",
            'hint' => "Build every category's path with a recursive `WITH`, starting from the roots and adding `' > ' || name` each level. Then join it to `book_categories` and `books`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Empty branches',
            'prompt' => 'Find the categories that contain no books at all, neither directly nor in any of their subcategories. Show their `name`.',
            'starter_code' => null,
            'solution' => 'WITH RECURSIVE below (root_id, id) AS (SELECT id, id FROM categories UNION ALL SELECT b.root_id, c.id FROM categories AS c JOIN below AS b ON c.parent_id = b.id) SELECT cat.name FROM categories AS cat JOIN below ON below.root_id = cat.id LEFT JOIN book_categories AS bc ON bc.category_id = below.id GROUP BY cat.id HAVING COUNT(bc.book_id) = 0;',
            'hint' => 'Count each subtree\'s books as in the lesson, then keep the categories whose count is 0 with `HAVING`.',
            'order_matters' => false,
        ],
    ],
];
