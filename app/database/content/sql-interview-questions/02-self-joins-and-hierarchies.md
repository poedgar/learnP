## A table that points at itself

`employees.manager_id` holds the `id` of another row in the **same** table. Questions about managers
need the table twice: once for the employee, once for the manager, with different aliases. This is a
**self-join**, and a favourite in interviews because it tests whether you can keep two roles of the
same table straight.

## Employees and their managers

```sql
SELECT e.name AS employee, m.name AS manager
FROM employees AS e
LEFT JOIN employees AS m ON m.id = e.manager_id
ORDER BY manager, employee;
```

The `LEFT JOIN` keeps Grace Okafor, the CEO, whose `manager_id` is `NULL`; an inner join would quietly
drop her. Read the `ON` carefully: the manager row (`m`) is the one whose `id` equals the employee's
`manager_id`.

## Earning more than the boss

The classic: compare each employee's salary with their manager's, on the same joined row:

```sql
SELECT e.name, e.salary, m.name AS manager, m.salary AS manager_salary
FROM employees AS e
JOIN employees AS m ON m.id = e.manager_id
WHERE e.salary > m.salary;
```

## Counting reports

Turn it around: group by the manager to count their **direct reports**:

```sql
SELECT m.name, COUNT(*) AS reports
FROM employees AS e
JOIN employees AS m ON m.id = e.manager_id
GROUP BY m.id
ORDER BY reports DESC;
```

## Pairs from one table

"Which employees were hired on the same day?" pairs rows of the same table. As with products bought
together, `a.id < b.id` gives each pair once and never pairs someone with themselves:

```sql
SELECT a.name, b.name, a.hired_on
FROM employees AS a
JOIN employees AS b ON b.hired_on = a.hired_on AND a.id < b.id;
```

## All the way up

A self-join goes up one level. For the whole chain (employee, their manager, that manager's manager, up
to the CEO) you need a **recursive CTE**, from the Advanced SQL course: start with one row, then keep
joining to the next manager until there's none:

```sql
WITH RECURSIVE chain (id, name, manager_id, level) AS (
    SELECT id, name, manager_id, 0 FROM employees WHERE id = 7
    UNION ALL
    SELECT m.id, m.name, m.manager_id, c.level + 1
    FROM employees AS m
    JOIN chain AS c ON m.id = c.manager_id
)
SELECT name, level FROM chain;
```

Going down instead (everyone under a manager, at any depth) is the same with the join reversed:
`JOIN chain AS c ON e.manager_id = c.id`.
