## How this course works

SQL interviews reuse a small set of classic problems: the second-highest salary, employees who earn
more than their manager, duplicate emails, three logins in a row. They're popular because each one
tests whether you think about **ties**, **NULLs** and **duplicates**. This course works through them on
a small company, Northwind Labs:

| Table | One row per |
|---|---|
| `departments` | department (Legal has nobody in it yet) |
| `employees` | employee: `email`, `department_id` (`NULL` for the CEO), `manager_id` (`NULL` for the CEO), `salary`, `hired_on` |
| `projects` | project, owned by a department |
| `assignments` | employee on a project, with `hours` |
| `logins` | login, with the `logged_in` timestamp |

In an interview, say your assumptions out loud: "if two people tie, I'll return both", "the CEO has no
department, so I'll leave them out of department averages". That's half of what's being tested.

## The second-highest salary

The famous one. The trap is ties: if two people share the top salary, the "second highest" is the
next *different* salary. `DISTINCT` handles that, and `LIMIT ... OFFSET` skips the first:

```sql
SELECT DISTINCT salary
FROM employees
ORDER BY salary DESC
LIMIT 1 OFFSET 1;
```

If there is no second salary, this returns no rows at all; wrapping it, `SELECT (SELECT ...) AS second`,
returns one row with `NULL` instead, which is what the question usually wants. Another classic answer
is `SELECT MAX(salary) FROM employees WHERE salary < (SELECT MAX(salary) FROM employees)`.

## The Nth highest, with window functions

`DENSE_RANK()` numbers distinct values without gaps, so "the Nth highest" is just `rank = N`:

```sql
SELECT name, salary
FROM (
    SELECT name, salary, DENSE_RANK() OVER (ORDER BY salary DESC) AS rnk
    FROM employees
)
WHERE rnk = 3;
```

Know the three ranking functions cold. For salaries 145, 140, 120, 120, 110:

| salary | `ROW_NUMBER()` | `RANK()` | `DENSE_RANK()` |
|---|---|---|---|
| 145 | 1 | 1 | 1 |
| 140 | 2 | 2 | 2 |
| 120 | 3 | 3 | 3 |
| 120 | 4 | 3 | 3 |
| 110 | 5 | 5 | 4 |

## Top N per group

"The top two earners in each department" adds `PARTITION BY`:

```sql
SELECT department_id, name, salary
FROM (
    SELECT department_id, name, salary,
           DENSE_RANK() OVER (PARTITION BY department_id ORDER BY salary DESC) AS rnk
    FROM employees
    WHERE department_id IS NOT NULL
)
WHERE rnk <= 2
ORDER BY department_id, salary DESC;
```

Finance shows three people for "top two" because two of them share second place. Whether that's
right depends on the question, so ask.

## Compared with the group

"Who earns more than their department's average?" compares each row with an aggregate of its group.
A window average keeps every row while adding the group's value:

```sql
SELECT name, department_id, salary,
       ROUND(AVG(salary) OVER (PARTITION BY department_id)) AS department_average
FROM employees
WHERE department_id IS NOT NULL;
```

A correlated subquery (`WHERE salary > (SELECT AVG(salary) FROM employees AS x WHERE x.department_id = e.department_id)`)
works too. Being able to give both answers is a good sign in an interview.
