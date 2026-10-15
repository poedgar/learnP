## Patterns worth knowing

Some problems come up again and again: turning rows into columns for a report, cleaning up
duplicates, keeping only the newest version of something, and finding runs and gaps in a
sequence. Each has a well-known pattern built from tools you already have.

## Pivoting rows into columns

A report often wants one row per genre and one **column** per month. `GROUP BY` gives one row
per genre; a `SUM(CASE …)` per month spreads the numbers across columns. Copies sold per genre
and month:

```sql
SELECT b.genre,
       SUM(CASE WHEN o.ordered_on LIKE '2026-01%' THEN oi.quantity ELSE 0 END) AS jan,
       SUM(CASE WHEN o.ordered_on LIKE '2026-02%' THEN oi.quantity ELSE 0 END) AS feb,
       SUM(CASE WHEN o.ordered_on LIKE '2026-03%' THEN oi.quantity ELSE 0 END) AS mar,
       SUM(CASE WHEN o.ordered_on LIKE '2026-04%' THEN oi.quantity ELSE 0 END) AS apr
FROM order_items AS oi
JOIN orders AS o ON o.id = oi.order_id
JOIN books AS b ON b.id = oi.book_id
GROUP BY b.genre;
```

Each `CASE` passes a row's quantity through only for its own month. You have to know the
columns in advance; SQL can't invent a column for every month it finds.

## FILTER: a shorter way to write it

`FILTER (WHERE …)` after an aggregate makes it look only at some rows. It says the same thing as
the `CASE` more directly. Orders per month, split by whether they have shipped:

```sql
SELECT strftime('%Y-%m', ordered_on) AS month,
       COUNT(*) FILTER (WHERE shipped_on IS NOT NULL) AS shipped,
       COUNT(*) FILTER (WHERE shipped_on IS NULL) AS waiting
FROM orders
GROUP BY month;
```

`COUNT` with a filter gives 0 when no rows match. `SUM` with a filter gives `NULL`, so wrap it in
`COALESCE(…, 0)` if you want a zero.

## Unpivoting columns into rows

`sales_targets` is the opposite shape, with one column per month:

```sql
SELECT * FROM sales_targets;
```

That's handy to read but awkward to query: "the total target for each month" needs four
different sums. Stack one `SELECT` per column with `UNION ALL` to turn the columns back into
rows:

```sql
SELECT genre, 'jan' AS month, jan AS target FROM sales_targets
UNION ALL SELECT genre, 'feb', feb FROM sales_targets
UNION ALL SELECT genre, 'mar', mar FROM sales_targets
UNION ALL SELECT genre, 'apr', apr FROM sales_targets
ORDER BY genre;
```

Five genres times four months gives 20 rows, which you can group, filter and join like any
other table.

## Finding duplicates

The supplier's price feed sometimes sends the same line twice. Group by every column and keep
the groups with more than one row:

```sql
SELECT book_id, price, sent_at, COUNT(*) AS copies
FROM supplier_prices
GROUP BY book_id, price, sent_at
HAVING COUNT(*) > 1;
```

Group by the columns that **should** be unique together. For people that might be just the
email address; for the feed it's the whole line.

## Keeping the latest row

The feed also sends a new price whenever one changes, so most books have several rows. The
current price is the newest one. Number each book's rows from newest to oldest, then keep row 1:

```sql
SELECT book_id, price, sent_at
FROM (
    SELECT book_id, price, sent_at,
           ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY sent_at DESC) AS n
    FROM supplier_prices
)
WHERE n = 1;
```

Use `ROW_NUMBER`, not `RANK`. Two identical lines would both get rank 1, but `ROW_NUMBER` always
picks exactly one.

## Deleting duplicates

To delete the extra copies, keep the first row of each group and delete the rest. The feed has
no `id` column, but every SQLite table has a hidden one called `rowid`:

```sql
DELETE FROM supplier_prices
WHERE rowid NOT IN (
    SELECT MIN(rowid) FROM supplier_prices GROUP BY book_id, price, sent_at
);
SELECT COUNT(*) FROM supplier_prices;
```

Ten rows become eight. Run the `SELECT` part on its own first, to see which rows you're keeping.

## Gaps in a sequence

`LEAD` (lesson 10 of the SQL course) looks at the next row. Wherever the next id is more than
one higher, some ids are missing. After deleting two reviews:

```sql
DELETE FROM reviews WHERE id IN (6, 12);

SELECT id + 1 AS first_missing, next_id - 1 AS last_missing
FROM (SELECT id, LEAD(id) OVER (ORDER BY id) AS next_id FROM reviews)
WHERE next_id > id + 1;
```

## Islands: runs of consecutive days

`visits` records the days each customer used the website. A run of consecutive days is called an
**island**. The trick: number each customer's visits in date order, and subtract that number
from the date. Within a run, the date and the number both go up by one, so the difference stays
the same. It changes only when a day is skipped.

```sql
SELECT customer_id, visited_on,
       ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY visited_on) AS n,
       date(visited_on, '-' || ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY visited_on) || ' days') AS island
FROM visits
WHERE customer_id = 1;
```

Grouping by that difference gives one row per island:

```sql
SELECT customer_id, MIN(visited_on) AS first_day, MAX(visited_on) AS last_day, COUNT(*) AS days
FROM (
    SELECT customer_id, visited_on,
           julianday(visited_on) - ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY visited_on) AS island
    FROM visits
)
GROUP BY customer_id, island
ORDER BY customer_id, first_day;
```

## Putting it together

Targets against copies actually sold, per genre and month: unpivot the targets, then join them to
the sales:

```sql
WITH targets AS (
    SELECT genre, '01' AS month, jan AS target FROM sales_targets
    UNION ALL SELECT genre, '02', feb FROM sales_targets
    UNION ALL SELECT genre, '03', mar FROM sales_targets
    UNION ALL SELECT genre, '04', apr FROM sales_targets
),
sold AS (
    SELECT b.genre, strftime('%m', o.ordered_on) AS month, SUM(oi.quantity) AS copies
    FROM order_items AS oi
    JOIN orders AS o ON o.id = oi.order_id
    JOIN books AS b ON b.id = oi.book_id
    GROUP BY b.genre, month
)
SELECT t.genre, t.month, t.target, COALESCE(s.copies, 0) AS sold
FROM targets AS t
LEFT JOIN sold AS s ON s.genre = t.genre AND s.month = t.month
ORDER BY t.genre, t.month;
```

The shop is a long way off its targets. Next, try the quiz to check your understanding, then
solve the challenges.
