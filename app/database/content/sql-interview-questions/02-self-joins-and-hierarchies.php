<?php

// Lesson 2 of the SQL interview questions course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/company.sql.

return [
    'slug' => 'self-joins-and-hierarchies',
    'title' => 'Self-joins and hierarchies',
    'summary' => 'Questions about a table that points at itself: employees and managers, earning more than the boss, counting reports, pairs from one table, and whole chains with recursive CTEs.',
    'starter_code' => "-- Every employee with their manager. Try showing only Sales.\nSELECT e.name AS employee, m.name AS manager\nFROM employees AS e\nLEFT JOIN employees AS m ON m.id = e.manager_id\nORDER BY manager, employee;",

    'quiz' => [
        [
            'question' => 'Why does the employees-and-managers query use a LEFT JOIN?',
            'options' => [
                'It is faster',
                'So the CEO, who has no manager (manager_id is NULL), isn\'t dropped',
                'Because managers are in another table',
                'To avoid duplicates',
            ],
            'correct_option' => 1,
            'explanation' => 'An inner join only keeps employees whose manager row exists.',
        ],
        [
            'question' => 'Who earns more than their manager?',
            'options' => [
                'Nobody',
                'Ken Adeyemi and Lena Vogel',
                'Ada Ferreira and Linus Berg',
                'Grace Okafor',
            ],
            'correct_option' => 1,
            'explanation' => 'Ken (145000) out-earns Alan Brooks (140000); Lena (105000) out-earns Sara Lindgren (100000).',
        ],
        [
            'question' => 'Who has the most direct reports?',
            'options' => ['Grace Okafor', 'Alan Brooks', 'Sara Lindgren', 'Marcus Reid'],
            'correct_option' => 2,
            'explanation' => 'Six people report to Sara Lindgren, including the two Omar Farouk records.',
        ],
        [
            'question' => 'In a self-join pairing employees hired on the same day, why `a.id < b.id`?',
            'options' => [
                'To sort the result',
                'Each pair appears once, and nobody is paired with themselves',
                'Because ids are dates',
                'It is required for self-joins',
            ],
            'correct_option' => 1,
            'explanation' => 'With `<>` every pair would appear twice.',
        ],
        [
            'question' => 'Which pairs of employees share a hire date?',
            'options' => [
                'None',
                'Tom Walsh and Yuki Tanaka, and the two Omar Farouk records',
                'Ada Ferreira and Linus Berg',
                'Sam Osei and Nora Quinn',
            ],
            'correct_option' => 1,
            'explanation' => 'Both were hired on 2021-07-05; the duplicate Omar records share 2024-01-15.',
        ],
        [
            'question' => 'What does each step of the recursive chain CTE do?',
            'options' => [
                'Finds all employees',
                'Joins the rows found so far to their managers, adding one level, until a row has no manager',
                'Sorts by level',
                'Counts reports',
            ],
            'correct_option' => 1,
            'explanation' => 'It stops when the join finds nothing new: the CEO\'s manager_id is NULL.',
        ],
        [
            'question' => 'How many levels above Yuki Tanaka is the CEO?',
            'options' => ['1', '2', '3', '4'],
            'correct_option' => 2,
            'explanation' => 'Yuki → Ada Ferreira → Alan Brooks → Grace Okafor: three steps up.',
        ],
        [
            'question' => 'To find everyone under Alan Brooks at any depth, how does the recursive step change?',
            'options' => [
                'It can\'t be done',
                'Join employees whose manager_id is in the chain: `ON e.manager_id = c.id`',
                'Use GROUP BY',
                'Use a LEFT JOIN instead',
            ],
            'correct_option' => 1,
            'explanation' => 'Going down follows the link the other way.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'No manager',
            'prompt' => 'Show the `name` of every employee without a manager.',
            'starter_code' => null,
            'solution' => 'SELECT name FROM employees WHERE manager_id IS NULL;',
            'hint' => '`IS NULL`, not `= NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Paid more than the boss',
            'prompt' => 'Show the `name` of each employee who earns more than their manager, and `difference`: how much more.',
            'starter_code' => null,
            'solution' => 'SELECT e.name, e.salary - m.salary AS difference FROM employees AS e JOIN employees AS m ON m.id = e.manager_id WHERE e.salary > m.salary;',
            'hint' => 'Join employees to their manager row and compare salaries.',
            'order_matters' => false,
        ],
        [
            'title' => 'Big teams',
            'prompt' => 'Show the `name` of every manager with at least 3 direct reports, and the number of `reports`.',
            'starter_code' => null,
            'solution' => 'SELECT m.name, COUNT(*) AS reports FROM employees AS e JOIN employees AS m ON m.id = e.manager_id GROUP BY m.id HAVING COUNT(*) >= 3;',
            'hint' => 'Group by the manager and filter with `HAVING`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Different departments',
            'prompt' => 'Show the `employee` name and `manager` name for employees whose manager is in a different department (leave out anyone with no department, including the CEO as a manager).',
            'starter_code' => null,
            'solution' => 'SELECT e.name AS employee, m.name AS manager FROM employees AS e JOIN employees AS m ON m.id = e.manager_id WHERE e.department_id IS NOT NULL AND m.department_id IS NOT NULL AND e.department_id <> m.department_id;',
            'hint' => 'Self-join and compare `department_id`s, ruling out NULLs.',
            'order_matters' => false,
        ],
        [
            'title' => 'Up the chain',
            'prompt' => 'For Priya Nair (id 5), show every person above her in the chain of managers: their `name` and `level` (1 for her manager, 2 for the next, and so on).',
            'starter_code' => null,
            'solution' => 'WITH RECURSIVE chain (id, name, manager_id, level) AS (SELECT id, name, manager_id, 0 FROM employees WHERE id = 5 UNION ALL SELECT m.id, m.name, m.manager_id, c.level + 1 FROM employees AS m JOIN chain AS c ON m.id = c.manager_id) SELECT name, level FROM chain WHERE level > 0;',
            'hint' => 'The lesson\'s recursive chain, starting from Priya, without her own row (level 0).',
            'order_matters' => false,
        ],
        [
            'title' => 'Everyone under Alan',
            'prompt' => 'Show the `name` of everyone who reports to Alan Brooks (id 2), directly or indirectly, and their `depth` (1 for direct reports).',
            'starter_code' => null,
            'solution' => 'WITH RECURSIVE team (id, name, depth) AS (SELECT id, name, 1 FROM employees WHERE manager_id = 2 UNION ALL SELECT e.id, e.name, t.depth + 1 FROM employees AS e JOIN team AS t ON e.manager_id = t.id) SELECT name, depth FROM team;',
            'hint' => 'Start from Alan\'s direct reports, then repeatedly add employees whose manager is already in the result.',
            'order_matters' => false,
        ],
    ],
];
