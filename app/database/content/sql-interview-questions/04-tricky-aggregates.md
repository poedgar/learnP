## Aggregates with a twist

The last group of classics looks like ordinary `GROUP BY` questions, each with one twist: a group that
has no rows, a "for every" condition, a statistic SQLite doesn't have, or a result that should be
sideways. These are the ones where a quick answer is usually subtly wrong.

## Groups with nothing in them

"Headcount per department" with a plain join leaves out Legal, which has nobody yet. Start from the
side that must appear in full, and `LEFT JOIN` the rest:

```sql
SELECT d.name, COUNT(e.id) AS employees
FROM departments AS d
LEFT JOIN employees AS e ON e.department_id = d.id
GROUP BY d.id;
```

## "For every": relational division

"Which employees work on **every** project of their department?" can't be answered by looking at one
row at a time. Count instead: an employee is on every project if the number of their department's
projects they're on equals the number of projects their department has:

```sql
SELECT e.name,
       COUNT(a.project_id) AS on_projects,
       (SELECT COUNT(*) FROM projects AS p WHERE p.department_id = e.department_id) AS department_projects
FROM employees AS e
LEFT JOIN assignments AS a
       ON a.employee_id = e.id
      AND a.project_id IN (SELECT id FROM projects WHERE department_id = e.department_id)
WHERE e.department_id = 1
GROUP BY e.id;
```

Keep the rows where the two numbers match. Another way to say it, with a double negative: there's
**no** project of their department that they're **not** on (`NOT EXISTS ... NOT EXISTS`).

## The median

SQLite has no `MEDIAN()`. Number the rows in order and average the middle one or two. Here for the
hours people put into projects:

```sql
WITH ordered AS (
    SELECT hours,
           ROW_NUMBER() OVER (ORDER BY hours) AS position,
           COUNT(*) OVER () AS total
    FROM assignments
)
SELECT AVG(hours) AS median_hours
FROM ordered
WHERE position IN ((total + 1) / 2, (total + 2) / 2);
```

## Running totals

"Headcount at the end of each year" is a running total of hires, a window `SUM` over the grouped
counts:

```sql
SELECT strftime('%Y', hired_on) AS year,
       COUNT(*) AS hired,
       SUM(COUNT(*)) OVER (ORDER BY strftime('%Y', hired_on)) AS headcount
FROM employees
GROUP BY year;
```

(It assumes nobody left, which in an interview is worth saying out loud.)

## Sideways results

"One row per department, one column per something" is conditional aggregation: a `COUNT` or `SUM`
per column, each with its own condition:

```sql
SELECT d.name,
       COUNT(*) FILTER (WHERE e.salary >= 100000) AS six_figures,
       COUNT(*) FILTER (WHERE e.salary < 100000) AS below
FROM departments AS d
JOIN employees AS e ON e.department_id = d.id
GROUP BY d.id;
```

`SUM(CASE WHEN ... THEN 1 ELSE 0 END)` does the same in databases without `FILTER`, so know both.
