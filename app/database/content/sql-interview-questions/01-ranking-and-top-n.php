<?php

// Lesson 1 of the SQL interview questions course. The theory is in the .md file with the same name.
// Each challenge's solution is compared with the learner's result. Quiz answers that quote numbers
// were checked against database/datasets/company.sql.

return [
    'slug' => 'ranking-and-top-n',
    'title' => 'Ranking and top N',
    'summary' => 'The classic ranking questions: the second-highest and Nth-highest salary, ROW_NUMBER vs RANK vs DENSE_RANK, top N per group, and comparing each row with its group.',
    'starter_code' => "-- Distinct salaries, highest first. Try returning just the second highest.\nSELECT DISTINCT salary\nFROM employees\nORDER BY salary DESC;",

    'quiz' => [
        [
            'question' => 'Why `SELECT DISTINCT salary ... LIMIT 1 OFFSET 1` and not just `ORDER BY salary DESC LIMIT 1 OFFSET 1`?',
            'options' => [
                'DISTINCT is faster',
                'If two people share the top salary, the second row would be the same salary again, not the second-highest',
                'OFFSET needs DISTINCT',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Ties are the point of the question.',
        ],
        [
            'question' => 'What is the second-highest salary at Northwind Labs?',
            'options' => ['140000', '145000', '120000', '200000'],
            'correct_option' => 1,
            'explanation' => 'Grace Okafor earns 200000, then Ken Adeyemi 145000.',
        ],
        [
            'question' => 'For salaries 145, 140, 120, 120, 110, what does `RANK()` give the 110 salary?',
            'options' => ['4', '5', '3', '6'],
            'correct_option' => 1,
            'explanation' => '`RANK()` leaves a gap after a tie; `DENSE_RANK()` would give 4.',
        ],
        [
            'question' => 'When there is no second-highest salary, how do you return one row with NULL instead of no rows?',
            'options' => [
                'Add `COALESCE`',
                'Wrap the query as a scalar subquery: `SELECT (SELECT ...) AS second_highest`',
                'Use `LIMIT 2`',
                'It always returns NULL',
            ],
            'correct_option' => 1,
            'explanation' => 'A scalar subquery that finds nothing becomes NULL.',
        ],
        [
            'question' => 'Which ranking function suits "the Nth highest distinct salary"?',
            'options' => ['`ROW_NUMBER()`', '`RANK()`', '`DENSE_RANK()`', '`NTILE()`'],
            'correct_option' => 2,
            'explanation' => 'It numbers distinct values 1, 2, 3 ... without gaps.',
        ],
        [
            'question' => 'Which department has the highest average salary (leaving out the CEO)?',
            'options' => ['Sales', 'Finance', 'Engineering', 'Marketing'],
            'correct_option' => 2,
            'explanation' => 'Engineering averages about 117857.',
        ],
        [
            'question' => 'Why does "top two per department" return three Finance employees with DENSE_RANK?',
            'options' => [
                'It is a bug',
                'Sam Osei and Nora Quinn tie for second place, so both get rank 2',
                'Finance has three managers',
                'Because of NULL departments',
            ],
            'correct_option' => 1,
            'explanation' => 'Use ROW_NUMBER if exactly two rows are wanted, and say how you break ties.',
        ],
        [
            'question' => 'What does `AVG(salary) OVER (PARTITION BY department_id)` add to each row?',
            'options' => [
                'The company average',
                'The average salary of that row\'s department, without collapsing the rows',
                'A running average',
                'The department\'s highest salary',
            ],
            'correct_option' => 1,
            'explanation' => 'Window aggregates keep the detail rows, which makes comparisons easy.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Second highest',
            'prompt' => 'Return one row with one column, `second_highest`: the second-highest distinct salary (NULL if there isn\'t one).',
            'starter_code' => null,
            'solution' => 'SELECT (SELECT DISTINCT salary FROM employees ORDER BY salary DESC LIMIT 1 OFFSET 1) AS second_highest;',
            'hint' => 'Wrap the `DISTINCT ... LIMIT 1 OFFSET 1` query in a scalar subquery.',
            'order_matters' => false,
        ],
        [
            'title' => 'Fourth highest',
            'prompt' => 'Show the `name` and `salary` of everyone paid the fourth-highest distinct salary.',
            'starter_code' => null,
            'solution' => 'SELECT name, salary FROM (SELECT name, salary, DENSE_RANK() OVER (ORDER BY salary DESC) AS rnk FROM employees) WHERE rnk = 4;',
            'hint' => '`DENSE_RANK()` over salary, descending, then keep rank 4.',
            'order_matters' => false,
        ],
        [
            'title' => 'Best paid per department',
            'prompt' => 'For each department with employees, show the department `name` (as `department`), and the `name` and `salary` of its best-paid employee(s), including ties.',
            'starter_code' => null,
            'solution' => 'SELECT department, name, salary FROM (SELECT d.name AS department, e.name, e.salary, RANK() OVER (PARTITION BY e.department_id ORDER BY e.salary DESC) AS rnk FROM employees AS e JOIN departments AS d ON d.id = e.department_id) WHERE rnk = 1;',
            'hint' => 'Rank within each department and keep rank 1.',
            'order_matters' => false,
        ],
        [
            'title' => 'Above average',
            'prompt' => 'Show the `name` and `salary` of employees who earn more than the average of their own department.',
            'starter_code' => null,
            'solution' => 'SELECT e.name, e.salary FROM employees AS e WHERE e.department_id IS NOT NULL AND e.salary > (SELECT AVG(x.salary) FROM employees AS x WHERE x.department_id = e.department_id);',
            'hint' => 'A correlated subquery for the department average, or a window average in a subquery.',
            'order_matters' => false,
        ],
        [
            'title' => 'Three ranks',
            'prompt' => 'For the Engineering department (id 1), show each employee\'s `name`, `salary`, and their `row_number`, `rank` and `dense_rank` by salary, highest first. Break ties for the row number by name.',
            'starter_code' => null,
            'solution' => 'SELECT name, salary, ROW_NUMBER() OVER (ORDER BY salary DESC, name) AS row_number, RANK() OVER (ORDER BY salary DESC) AS rank, DENSE_RANK() OVER (ORDER BY salary DESC) AS dense_rank FROM employees WHERE department_id = 1;',
            'hint' => 'Three window functions side by side; only `ROW_NUMBER()` needs the extra `name` in its ordering.',
            'order_matters' => false,
        ],
        [
            'title' => 'Salary bands',
            'prompt' => 'Show each employee\'s `name`, `salary` and `band`: `NTILE(4) OVER (ORDER BY salary DESC, id)`, so band 1 is the top quarter of earners.',
            'starter_code' => null,
            'solution' => 'SELECT name, salary, NTILE(4) OVER (ORDER BY salary DESC, id) AS band FROM employees;',
            'hint' => '`NTILE(n)` splits the ordered rows into n nearly equal groups.',
            'order_matters' => false,
        ],
    ],
];
