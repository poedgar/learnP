## A query inside a query

Which books cost more than the average? You could ask in two steps: run
`SELECT AVG(price) FROM books`, note the answer, then type it into a second query. Or let SQL
do both at once by putting the first query, in parentheses, inside the second:

```sql
SELECT title, price
FROM books
WHERE price > (SELECT AVG(price) FROM books)
ORDER BY price;
```

The inner query is a **subquery**. It runs first and produces one value, 14.62…, and the
outer query compares every price with it. You get 7 books.

A subquery used with `=`, `<` or `>` like this must return exactly **one value**. If it
returns several rows, SQLite quietly uses the first one, which is almost never what you
meant; most other databases stop with an error.

## Subqueries that return a list: IN

A subquery that returns a column of values works with `IN`, just like a list you type
yourself. Read this one from the inside out: the orders placed in April, then the books in
those orders, then their titles.

```sql
SELECT title
FROM books
WHERE id IN (
    SELECT book_id
    FROM order_items
    WHERE order_id IN (SELECT id FROM orders WHERE ordered_on >= '2026-04-01')
);
```

Each book appears once, even when it was in several of those orders, because the outer query
just asks "is this book's `id` in the list?".

## NOT IN and NULL

`NOT IN` with a subquery has the same trap you met with lists in lesson 2: if the subquery
returns a `NULL`, the condition is never true, and you get no rows at all. Daniel Kim has no
city, so this returns nothing:

```sql
SELECT name, city
FROM customers
WHERE city NOT IN (SELECT city FROM customers WHERE name LIKE 'D%');
```

The bug only appears when the data happens to contain a `NULL`, which makes it easy to miss.
When the subquery's column can be `NULL`, use `NOT EXISTS` instead.

## EXISTS and NOT EXISTS

`EXISTS (subquery)` is true when the subquery returns **at least one row**. What the rows
contain doesn't matter, so it's usual to write `SELECT 1`. Customers who have never placed an
order:

```sql
SELECT name
FROM customers AS c
WHERE NOT EXISTS (
    SELECT 1
    FROM orders AS o
    WHERE o.customer_id = c.id
);
```

And books that someone bought more than one copy of in a single order:

```sql
SELECT title
FROM books AS b
WHERE EXISTS (
    SELECT 1
    FROM order_items AS oi
    WHERE oi.book_id = b.id AND oi.quantity > 1
);
```

`EXISTS` only asks "is there a row?", so `NULL`s can't trip it up.

## Correlated subqueries

Look at the last two subqueries again: they use `c.id` and `b.id`, columns of the **outer**
query. A subquery like that is **correlated**: it is worked out again for each row of the
outer query.

That lets you compare each row with its own group. Books that cost more than the average
**for their genre**:

```sql
SELECT title, genre, price
FROM books AS b
WHERE price > (
    SELECT AVG(price)
    FROM books AS g
    WHERE g.genre = b.genre
);
```

The same table appears twice, so the aliases `b` and `g` are what tell the two apart.

## Subqueries in SELECT

A subquery that returns one value can also be a column. Each customer with the number of
orders they've placed:

```sql
SELECT name,
       (SELECT COUNT(*) FROM orders AS o WHERE o.customer_id = c.id) AS orders
FROM customers AS c;
```

Customers with no orders get 0. That's the same answer as the `LEFT JOIN` with
`COUNT(o.id)` from the last lesson, reached a different way.

## Subqueries in FROM

The result of a query is a table, so you can query it. What is an average order worth? You
need each order's total first, and then the average of those totals:

```sql
SELECT ROUND(AVG(total), 2) AS average_order
FROM (
    SELECT order_id, SUM(quantity * unit_price) AS total
    FROM order_items
    GROUP BY order_id
) AS order_totals;
```

An average order is worth 29.66. You can't do it in one step, because aggregates can't be put
inside each other:

```sql
-- Error: aggregate functions can't be nested.
SELECT AVG(SUM(quantity * unit_price))
FROM order_items
GROUP BY order_id;
```

## Join or subquery?

Many questions can be answered either way. As a rule of thumb, use a **join** when you need
columns from both tables in the result, and a **subquery** when the other table only decides
which rows to keep, or supplies a single value.

Who has bought *Dune* (book 6)? With a join, a customer who bought it twice appears twice,
so you need `DISTINCT`:

```sql
SELECT DISTINCT c.name
FROM customers AS c
JOIN orders AS o ON o.customer_id = c.id
JOIN order_items AS oi ON oi.order_id = o.id
WHERE oi.book_id = 6;
```

With a subquery, each customer is checked once, so duplicates can't happen:

```sql
SELECT name
FROM customers
WHERE id IN (
    SELECT o.customer_id
    FROM orders AS o
    JOIN order_items AS oi ON oi.order_id = o.id
    WHERE oi.book_id = 6
);
```

Next, try the quiz to check your understanding, then solve the challenges.
