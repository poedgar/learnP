## Why name the steps?

Subqueries in `FROM` let you build an answer in stages, but they read inside-out: the first
step is buried in the middle. `WITH` lets you write the steps **in order, with names**:

```sql
WITH sales AS (
    SELECT book_id, SUM(quantity) AS copies
    FROM order_items
    GROUP BY book_id
)
SELECT b.title, s.copies
FROM sales AS s
JOIN books AS b ON b.id = s.book_id
ORDER BY s.copies DESC, b.title;
```

`sales` is a **common table expression** (CTE): a named query that the rest of the statement
can use like a table. It exists only while this statement runs; nothing is saved.

## Several steps

Separate steps with commas. Each step can use the ones before it. Each order's total, then
the average order:

```sql
WITH order_totals AS (
    SELECT order_id, SUM(quantity * unit_price) AS total
    FROM order_items
    GROUP BY order_id
),
summary AS (
    SELECT COUNT(*) AS orders, ROUND(AVG(total), 2) AS average_order, ROUND(MAX(total), 2) AS biggest_order
    FROM order_totals
)
SELECT * FROM summary;
```

## Using a step twice

A named step can be used as often as you like. Books that sold more copies than the average
book: `sales` gives each book's copies, and also the average of them.

```sql
WITH sales AS (
    SELECT book_id, SUM(quantity) AS copies
    FROM order_items
    GROUP BY book_id
)
SELECT b.title, s.copies
FROM sales AS s
JOIN books AS b ON b.id = s.book_id
WHERE s.copies > (SELECT AVG(copies) FROM sales)
ORDER BY s.copies DESC, b.title;
```

Without `WITH`, you'd write the same subquery twice, as in lesson 5's *Above-average
spenders*.

## Recursion: a step that uses itself

`WITH RECURSIVE` lets a step refer to **itself**, so it can build rows one at a time. Counting
from 1 to 10:

```sql
WITH RECURSIVE counter(n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM counter WHERE n < 10
)
SELECT n FROM counter;
```

It has two parts joined by `UNION ALL`:

1. The **starting row**: `SELECT 1`.
2. The **recursive part**: `SELECT n + 1 FROM counter WHERE n < 10`. It runs on the rows made
   last time, over and over, until it produces no new rows.

The `WHERE n < 10` is what stops it.

## A calendar

Counting works for dates too. Every day in March 2026:

```sql
WITH RECURSIVE days(day) AS (
    SELECT '2026-03-01'
    UNION ALL
    SELECT date(day, '+1 day') FROM days WHERE day < '2026-03-31'
)
SELECT day FROM days;
```

## Finding the gaps

A calendar is useful because it has **every** day, including the ones missing from your
data. Join orders onto it with a `LEFT JOIN`, and keep the days with no match: the days in
March with no orders.

```sql
WITH RECURSIVE days(day) AS (
    SELECT '2026-03-01'
    UNION ALL
    SELECT date(day, '+1 day') FROM days WHERE day < '2026-03-31'
)
SELECT d.day
FROM days AS d
LEFT JOIN orders AS o ON o.ordered_on = d.day
WHERE o.id IS NULL;
```

That's 26 of March's 31 days. The `orders` table alone could never tell you this, because
the days with no orders aren't in it.

## Stopping

A recursive step without a stopping condition never ends. The sandbox stops any query after
3 seconds:

```sql
-- Error: nothing stops this counter, so the sandbox stops it after 3 seconds.
WITH RECURSIVE counter(n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM counter
)
SELECT COUNT(*) FROM counter;
```

Always check that the recursive part's `WHERE` eventually becomes false.

## Putting it together

Steps make long reports readable. Revenue per genre, with each genre's share of the total:

```sql
WITH genre_revenue AS (
    SELECT b.genre, SUM(oi.quantity * oi.unit_price) AS revenue
    FROM order_items AS oi
    JOIN books AS b ON b.id = oi.book_id
    GROUP BY b.genre
),
shop AS (
    SELECT SUM(revenue) AS total FROM genre_revenue
)
SELECT g.genre, ROUND(g.revenue, 2) AS revenue, ROUND(100.0 * g.revenue / shop.total, 1) AS percent
FROM genre_revenue AS g
CROSS JOIN shop
ORDER BY g.revenue DESC;
```

`CROSS JOIN` pairs every row on the left with every row on the right. `shop` has one row, so
it simply adds the total to each genre.

Next, try the quiz to check your understanding, then solve the challenges.
