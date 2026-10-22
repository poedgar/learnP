## Messy data on purpose

Real tables have duplicates, gaps and repeats, and interviewers love asking you to find them. The
common thread: decide **what makes two rows "the same"**, then group by exactly that.

## Duplicate values

"Find duplicate emails" is `GROUP BY` plus `HAVING COUNT(*) > 1`:

```sql
SELECT email, COUNT(*) AS copies
FROM employees
GROUP BY email
HAVING COUNT(*) > 1;
```

That finds nothing here, and that's the trap: Northwind has the same email twice with different
capitals (`omar.farouk@...` and `Omar.Farouk@...`). Emails aren't case-sensitive, so compare
`lower(email)`. Ask what counts as the same before you write the query.

## Removing duplicates

To delete duplicates while keeping one copy, keep the lowest `id` of each group and delete the rest:

```sql
SELECT lower(email) AS email, MIN(id) AS keep, COUNT(*) AS copies
FROM employees
GROUP BY lower(email)
HAVING COUNT(*) > 1;
```

The `DELETE` then removes every row whose id isn't one of the kept ones:
`DELETE FROM employees WHERE id NOT IN (SELECT MIN(id) FROM employees GROUP BY lower(email))`. In an
interview, mention that other tables might point at the rows you delete, so check before deleting.

## First and last

"Each employee's first login" is a `MIN` per group:

```sql
SELECT employee_id, MIN(logged_in) AS first_login, COUNT(*) AS logins
FROM logins
GROUP BY employee_id;
```

If you need *the whole row* of the first login (not just its time), use `ROW_NUMBER()` partitioned by
employee and keep row 1.

## Consecutive days

"Employees who logged in on three or more days in a row" is the gaps-and-islands trick. First reduce
to one row per employee per **day** (some people log in twice a day), then subtract a row number from
the date: consecutive days share the same result.

```sql
WITH days AS (
    SELECT DISTINCT employee_id, date(logged_in) AS day
    FROM logins
)
SELECT employee_id, day,
       julianday(day) - ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY day) AS island
FROM days
WHERE employee_id = 3;
```

Days 1 to 4 October give the same island value; 6 October starts a new one. Group by employee and
island, and keep islands with `COUNT(*) >= 3`.

Another common answer uses `LAG()` and `LEAD()` to look at the previous and next day, which works
well for "exactly three in a row" but gets clumsy for longer runs. The island version scales.

## Who is missing

"Employees who never logged in" is the anti-join again:

```sql
SELECT e.name
FROM employees AS e
LEFT JOIN logins AS l ON l.employee_id = e.id
WHERE l.id IS NULL;
```

`NOT IN (SELECT employee_id FROM logins)` also works here, but be careful with `NOT IN` in general: if
the subquery returns a single `NULL`, `NOT IN` returns no rows at all. `NOT EXISTS` doesn't have that
problem.
