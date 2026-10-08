## Counting rows with COUNT

So far every query has returned rows from a table. **Aggregate functions** do something
different: they take many rows and boil them down to one value. The simplest counts rows:

```sql
SELECT COUNT(*) AS books
FROM books;
```

The result is a single row with a single number. `WHERE` still works, and it runs *before*
the counting, so only the rows it keeps are counted:

```sql
SELECT COUNT(*) AS fantasy_books
FROM books
WHERE genre = 'Fantasy';
```

## COUNT(column) skips NULLs

`COUNT(*)` counts rows. `COUNT(column)` counts the rows where that column **isn't** `NULL`:

```sql
SELECT COUNT(*) AS customers, COUNT(city) AS with_a_city
FROM customers;
```

That gives 10 and 8: two customers have no city. The same trick counts the orders that have
shipped:

```sql
SELECT COUNT(shipped_on) AS shipped
FROM orders;
```

## SUM, AVG, MIN and MAX

Four more aggregate functions do what their names say:

```sql
SELECT MIN(price), MAX(price), AVG(price), SUM(pages)
FROM books;
```

Averages often come out with long decimals. `ROUND(value, places)` tidies them up:

```sql
SELECT ROUND(AVG(price), 2) AS average_price
FROM books;
```

You can aggregate a calculation, too. Each line in `order_items` is worth
`quantity * unit_price`, so the shop's total takings are:

```sql
SELECT ROUND(SUM(quantity * unit_price), 2) AS revenue
FROM order_items;
```

Like `COUNT(column)`, these functions ignore `NULL`s.

## Counting different values with COUNT(DISTINCT …)

`COUNT(DISTINCT column)` counts how many *different* values a column holds:

```sql
SELECT COUNT(customer_id) AS orders, COUNT(DISTINCT customer_id) AS customers
FROM orders;
```

There are 20 orders, placed by 8 different customers.

## Groups with GROUP BY

One number for the whole table is useful, but usually you want one number *per something*:
books per genre, orders per customer. `GROUP BY` splits the rows into groups that share a
value, and the aggregate runs once for each group:

```sql
SELECT genre, COUNT(*) AS books
FROM books
GROUP BY genre;
```

You get one row per genre. Any aggregate works per group:

```sql
SELECT order_id, SUM(quantity) AS copies
FROM order_items
GROUP BY order_id;
```

## Grouped or aggregated: the one rule

After `GROUP BY`, each result row stands for a whole group. So every column you select must
either be one you grouped by, or be inside an aggregate function. What should this show in
the `title` column, when the Fantasy group holds six titles?

```sql
SELECT genre, title, COUNT(*)
FROM books
GROUP BY genre;
```

SQLite runs it anyway and quietly picks one title from each group, which is almost never
what you meant. Most other databases, such as PostgreSQL, refuse to run it. Stick to the
rule and you'll never be surprised.

## Filtering groups with HAVING

`WHERE` filters rows **before** they are grouped, so it can't see group totals:

```sql
-- Error: WHERE runs before the groups (and their counts) exist.
SELECT genre, COUNT(*)
FROM books
WHERE COUNT(*) > 3
GROUP BY genre;
```

To filter **groups**, use `HAVING`, which comes after `GROUP BY`:

```sql
SELECT genre, COUNT(*) AS books
FROM books
GROUP BY genre
HAVING COUNT(*) > 3;
```

You can use both. `WHERE` picks which rows go into the groups; `HAVING` picks which groups
come out. Genres with at least three books in stock:

```sql
SELECT genre, COUNT(*) AS in_stock
FROM books
WHERE in_stock = 1
GROUP BY genre
HAVING COUNT(*) >= 3;
```

## Sorting by an aggregate, and putting it together

`ORDER BY` can sort by an aggregate, or by its alias. Which customers order most often?

```sql
SELECT customer_id, COUNT(*) AS orders
FROM orders
GROUP BY customer_id
ORDER BY orders DESC;
```

The full order of the clauses is:

```sql
SELECT genre, ROUND(AVG(price), 2) AS average_price
FROM books
WHERE published_year >= 1900
GROUP BY genre
HAVING COUNT(*) >= 2
ORDER BY average_price DESC
LIMIT 3;
```

The database runs them in this order:

1. `FROM` picks the table.
2. `WHERE` keeps the matching rows.
3. `GROUP BY` splits them into groups.
4. `HAVING` keeps the matching groups.
5. `SELECT` calculates the columns, including the aggregates.
6. `ORDER BY` sorts the result.
7. `LIMIT` cuts it down to size.

Next, try the quiz to check your understanding, then solve the challenges.
