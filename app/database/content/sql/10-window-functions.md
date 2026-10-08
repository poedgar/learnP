## A window keeps every row

`GROUP BY` squashes each group into one row. Sometimes you want the group's figure **next to
every row** instead: each book with its genre's average price. A **window function** does
that. Add `OVER (…)` after an aggregate:

```sql
SELECT title, genre, price,
       ROUND(AVG(price) OVER (PARTITION BY genre), 2) AS genre_average
FROM books
ORDER BY genre, price;
```

All 16 books stay in the result. `AVG(price) OVER (PARTITION BY genre)` means "the average
price of the rows in this row's genre".

## PARTITION BY

`PARTITION BY` splits the rows into groups for the window, like `GROUP BY` does, but without
merging them. Leave it out and the window is the whole result:

```sql
SELECT title, price,
       ROUND(AVG(price) OVER (), 2) AS shop_average,
       COUNT(*) OVER (PARTITION BY genre) AS books_in_genre
FROM books;
```

## Numbering rows: ROW_NUMBER, RANK and DENSE_RANK

These functions number rows in the order given inside `OVER`. They only differ when there are
ties:

```sql
WITH scores(points) AS (VALUES (10), (10), (8))
SELECT points,
       ROW_NUMBER() OVER (ORDER BY points DESC) AS row_number,
       RANK() OVER (ORDER BY points DESC) AS rank,
       DENSE_RANK() OVER (ORDER BY points DESC) AS dense_rank
FROM scores;
```

- `ROW_NUMBER` always counts 1, 2, 3, breaking ties in no particular order.
- `RANK` gives ties the same number and then skips: 1, 1, 3.
- `DENSE_RANK` gives ties the same number without skipping: 1, 1, 2.

(`VALUES` makes a small table on the spot, which is handy for trying things out.)

With `PARTITION BY`, the numbering restarts for each group. Each customer's orders, newest
first:

```sql
SELECT customer_id, id, ordered_on,
       ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY ordered_on DESC) AS newest_first
FROM orders;
```

## Top N per group

You can't filter on a window function in `WHERE`, because windows are worked out after
`WHERE` runs:

```sql
-- Error: window functions can't be used in WHERE.
SELECT title
FROM books
WHERE ROW_NUMBER() OVER (ORDER BY price) <= 3;
```

Number the rows in a `WITH` step first, then filter. Each customer's most recent order:

```sql
WITH numbered AS (
    SELECT customer_id, id, ordered_on,
           ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY ordered_on DESC) AS n
    FROM orders
)
SELECT customer_id, id, ordered_on
FROM numbered
WHERE n = 1;
```

## Running totals

Put `ORDER BY` inside `OVER` and an aggregate becomes **running**: it covers the rows up to
the current one. How the customer list grew:

```sql
SELECT name, joined_on,
       COUNT(*) OVER (ORDER BY joined_on) AS customers_so_far
FROM customers;
```

Watch out for rows that share the sort value. They count as one step, so the running figure
jumps for all of them together. Two orders were placed on 7 March and three on 14 March:

```sql
SELECT id, ordered_on,
       COUNT(*) OVER (ORDER BY ordered_on) AS orders_so_far
FROM orders
WHERE ordered_on BETWEEN '2026-03-07' AND '2026-03-14';
```

Both 7 March orders show 2, and all three 14 March orders show 5. Add a tie-breaker, such as
`ORDER BY ordered_on, id`, to make it go up one row at a time.

## Looking at neighbours: LAG and LEAD

`LAG(column)` gives the value from the **previous** row in the window's order; `LEAD(column)`
gives the **next** one. The first row has no previous row, so `LAG` gives it `NULL`. Each
order's total next to the previous order's:

```sql
WITH totals AS (
    SELECT order_id, ROUND(SUM(quantity * unit_price), 2) AS total
    FROM order_items
    GROUP BY order_id
)
SELECT order_id, total,
       LAG(total) OVER (ORDER BY order_id) AS previous_total,
       LEAD(total) OVER (ORDER BY order_id) AS next_total
FROM totals;
```

## Window frames

A **frame** chooses exactly which rows around the current one the function sees.
`ROWS BETWEEN 2 PRECEDING AND CURRENT ROW` means "this row and the two before it": a moving
average of the last three orders.

```sql
WITH totals AS (
    SELECT order_id, SUM(quantity * unit_price) AS total
    FROM order_items
    GROUP BY order_id
)
SELECT order_id, ROUND(total, 2) AS total,
       ROUND(AVG(total) OVER (ORDER BY order_id ROWS BETWEEN 2 PRECEDING AND CURRENT ROW), 2) AS moving_average
FROM totals;
```

The first two rows have fewer than two rows before them, so their averages use what there is.

## Putting it together

Windows can run over grouped results, too. Each genre's revenue, its rank, and its share of
the total:

```sql
SELECT b.genre,
       ROUND(SUM(oi.quantity * oi.unit_price), 2) AS revenue,
       RANK() OVER (ORDER BY SUM(oi.quantity * oi.unit_price) DESC) AS position,
       ROUND(100.0 * SUM(oi.quantity * oi.unit_price) / SUM(SUM(oi.quantity * oi.unit_price)) OVER (), 1) AS percent
FROM order_items AS oi
JOIN books AS b ON b.id = oi.book_id
GROUP BY b.genre
ORDER BY position;
```

`SUM(SUM(…)) OVER ()` reads from the inside out: the inner `SUM` is each genre's revenue, and
the window adds those up across all genres.

That's the end of the SQL course. Try the quiz and the challenges, then go and ask your own
questions of the bookshop.
