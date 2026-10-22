<?php

// Lesson 4 of the SQL interview questions course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/company.sql.

return [
    'slug' => 'tricky-aggregates',
    'title' => 'Tricky aggregates',
    'summary' => 'GROUP BY questions with a twist: groups with no rows, "for every" questions (relational division), the median, running totals, shares of a total and sideways results.',
    'starter_code' => "-- Headcount per department, including empty ones. Try adding the average salary.\nSELECT d.name, COUNT(e.id) AS employees\nFROM departments AS d\nLEFT JOIN employees AS e ON e.department_id = d.id\nGROUP BY d.id;",

    'quiz' => [
        [
            'question' => 'Which department disappears from a headcount that uses an inner JOIN?',
            'options' => ['Engineering', 'Finance', 'Legal', 'None'],
            'correct_option' => 2,
            'explanation' => 'Legal has no employees, so no joined rows. Start from departments and LEFT JOIN.',
        ],
        [
            'question' => 'What is relational division?',
            'options' => [
                'Dividing one column by another',
                'Finding rows related to every item of a set, like employees on every project of their department',
                'Splitting a table in two',
                'Integer division in SQL',
            ],
            'correct_option' => 1,
            'explanation' => 'Usually solved by comparing counts, or with a double NOT EXISTS.',
        ],
        [
            'question' => 'Which employees work on all three Engineering projects?',
            'options' => [
                'Everyone in Engineering',
                'Alan Brooks, Ada Ferreira and Ken Adeyemi',
                'Only Ada Ferreira',
                'Priya Nair and Tom Walsh',
            ],
            'correct_option' => 1,
            'explanation' => 'Each of them has an assignment on Checkout rewrite, Mobile app and Data platform.',
        ],
        [
            'question' => 'Ada Ferreira also helps on the Spring campaign, a Marketing project. Why doesn\'t that affect the "every project of their department" count?',
            'options' => [
                'Because she isn\'t in Marketing',
                'The count only includes projects of her own department, so other assignments are left out',
                'Because of the LEFT JOIN',
                'It does affect it',
            ],
            'correct_option' => 1,
            'explanation' => 'Counting all her assignments would give 4 against 3 Engineering projects, and she\'d fail the equality.',
        ],
        [
            'question' => 'With 22 employees, which positions does the median formula average?',
            'options' => ['11 only', '11 and 12', '12 only', '10 and 11'],
            'correct_option' => 1,
            'explanation' => '(22 + 1) / 2 = 11 and (22 + 2) / 2 = 12 with integer division.',
        ],
        [
            'question' => 'What is the median salary?',
            'options' => ['85000', '90000', '92500', '95000'],
            'correct_option' => 2,
            'explanation' => 'The 11th and 12th salaries are 90000 and 95000; their average is 92500.',
        ],
        [
            'question' => 'What headcount had Northwind reached by the end of 2019?',
            'options' => ['6', '8', '10', '13'],
            'correct_option' => 2,
            'explanation' => '1 + 3 + 2 + 2 + 2 hires from 2015 to 2019, assuming nobody left.',
        ],
        [
            'question' => 'What is `COUNT(*) FILTER (WHERE salary >= 100000)` the same as?',
            'options' => [
                '`COUNT(salary >= 100000)`',
                '`SUM(CASE WHEN salary >= 100000 THEN 1 ELSE 0 END)`',
                '`MAX(salary >= 100000)`',
                '`COUNT(*) WHERE salary >= 100000`',
            ],
            'correct_option' => 1,
            'explanation' => '`COUNT(salary >= 100000)` counts every non-NULL row, true or false.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Every project',
            'prompt' => 'Show the `name` of every employee who is assigned to every project of their own department. Leave out employees whose department has no projects.',
            'starter_code' => null,
            'solution' => 'SELECT e.name FROM employees AS e WHERE EXISTS (SELECT 1 FROM projects AS p WHERE p.department_id = e.department_id) AND NOT EXISTS (SELECT 1 FROM projects AS p WHERE p.department_id = e.department_id AND NOT EXISTS (SELECT 1 FROM assignments AS a WHERE a.employee_id = e.id AND a.project_id = p.id));',
            'hint' => 'There is no project of their department that they are not assigned to: a `NOT EXISTS` inside a `NOT EXISTS`. Or compare counts.',
            'order_matters' => false,
        ],
        [
            'title' => 'Average with empty groups',
            'prompt' => 'For every department, including those without employees, show its `name`, `employees` and `average_salary` (rounded to a whole number; `NULL` for an empty department).',
            'starter_code' => null,
            'solution' => 'SELECT d.name, COUNT(e.id) AS employees, ROUND(AVG(e.salary)) AS average_salary FROM departments AS d LEFT JOIN employees AS e ON e.department_id = d.id GROUP BY d.id;',
            'hint' => 'Departments LEFT JOIN employees; `AVG` of no values is `NULL`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Median salary',
            'prompt' => 'Return one row with `median_salary`: the median of all salaries.',
            'starter_code' => null,
            'solution' => 'WITH ordered AS (SELECT salary, ROW_NUMBER() OVER (ORDER BY salary) AS position, COUNT(*) OVER () AS total FROM employees) SELECT AVG(salary) AS median_salary FROM ordered WHERE position IN ((total + 1) / 2, (total + 2) / 2);',
            'hint' => 'Number the salaries in order, then average the middle position or two.',
            'order_matters' => false,
        ],
        [
            'title' => 'Payroll share',
            'prompt' => 'For each department with employees, show its `name`, its `payroll` (total salaries) and `pct`: its share of the whole company\'s payroll (everyone, the CEO included), rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => 'SELECT d.name, SUM(e.salary) AS payroll, ROUND(100.0 * SUM(e.salary) / (SELECT SUM(salary) FROM employees), 1) AS pct FROM departments AS d JOIN employees AS e ON e.department_id = d.id GROUP BY d.id;',
            'hint' => 'Divide each department\'s total by a scalar subquery for the company total; a window `SUM(...) OVER ()` would leave out the CEO, who has no department.',
            'order_matters' => false,
        ],
        [
            'title' => 'Hours by department',
            'prompt' => 'For each department that owns projects, show its `name`, the `total_hours` logged on its projects, and the `outside_hours`: hours logged on them by people from other departments.',
            'starter_code' => null,
            'solution' => 'SELECT d.name, SUM(a.hours) AS total_hours, COALESCE(SUM(a.hours) FILTER (WHERE e.department_id IS NOT p.department_id), 0) AS outside_hours FROM projects AS p JOIN departments AS d ON d.id = p.department_id JOIN assignments AS a ON a.project_id = p.id JOIN employees AS e ON e.id = a.employee_id GROUP BY d.id;',
            'hint' => 'Join projects, assignments and employees; a filtered `SUM` for hours where the employee\'s department differs. `COALESCE` turns "none" into 0.',
            'order_matters' => false,
        ],
        [
            'title' => 'Old hands and new',
            'prompt' => 'For each department with employees, show its `name`, how many were hired `before_2020` and how many `since_2020`.',
            'starter_code' => null,
            'solution' => "SELECT d.name, COUNT(*) FILTER (WHERE e.hired_on < '2020-01-01') AS before_2020, COUNT(*) FILTER (WHERE e.hired_on >= '2020-01-01') AS since_2020 FROM departments AS d JOIN employees AS e ON e.department_id = d.id GROUP BY d.id;",
            'hint' => 'One filtered `COUNT` per column.',
            'order_matters' => false,
        ],
    ],
];
