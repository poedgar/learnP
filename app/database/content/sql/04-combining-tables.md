## Why data is split across tables

An order belongs to a customer. The shop could copy the customer's name and email into every
order, but then a customer who changes their email would have to be updated in many places,
and one missed row would make the data disagree with itself. Instead, each customer is
stored **once**, and an order just points at them:

```sql
SELECT id, customer_id, ordered_on
FROM orders
LIMIT 5;
```

- `customers.id` is the customers table's **primary key**: a value that identifies exactly
  one row.
- `orders.customer_id` is a **foreign key**: it holds the primary key of the customer who
  placed the order.

In the same way, `order_items.order_id` points at `orders.id`, and `order_items.book_id`
points at `books.id`. To answer questions like "who ordered what?", you follow these links
with a **join**.

## Joining two tables with JOIN … ON

`JOIN` puts two tables side by side. `ON` says which rows belong together:

```sql
SELECT orders.id, orders.ordered_on, customers.name
FROM orders
JOIN customers ON customers.id = orders.customer_id;
```

For each order, the database finds the customer whose `id` equals the order's
`customer_id`, and joins the two rows into one. Columns are written `table.column`, so it's
clear which table each one comes from.

`JOIN` is short for `INNER JOIN`: it keeps only the pairs that match. A customer with no
orders has nothing to pair with, so they don't appear.

## Table aliases, and ambiguous columns

Both tables have an `id` column, so a bare `id` doesn't say which one you mean:

```sql
-- Error: ambiguous column name, because orders and customers both have an id.
SELECT id, name
FROM orders
JOIN customers ON customers.id = orders.customer_id;
```

Writing the table name in front of every column fixes it, but gets long. Give each table a
short **alias** with `AS` instead:

```sql
SELECT o.id, o.ordered_on, c.name
FROM orders AS o
JOIN customers AS c ON c.id = o.customer_id;
```

Inside this query, `o` means `orders` and `c` means `customers`.

Don't forget the `ON`. Without it, SQLite pairs **every** customer with **every** order:
10 customers × 20 orders gives 200 rows of nonsense.

## Joining more tables

Each extra `JOIN` follows one more link. Every order with the books in it:

```sql
SELECT o.id AS order_id, b.title, oi.quantity
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
JOIN books AS b ON b.id = oi.book_id
ORDER BY o.id;
```

Joined rows can be filtered and sorted like any others. Everything Amara Okafor has bought,
which takes all four tables:

```sql
SELECT c.name, o.ordered_on, b.title
FROM customers AS c
JOIN orders AS o ON o.customer_id = c.id
JOIN order_items AS oi ON oi.order_id = o.id
JOIN books AS b ON b.id = oi.book_id
WHERE c.name = 'Amara Okafor'
ORDER BY o.ordered_on;
```

## LEFT JOIN keeps rows with no match

An inner join drops customers who have never ordered. A `LEFT JOIN` keeps **every** row
from the table on its left; where there is no match on the right, the right-hand columns
are `NULL`:

```sql
SELECT c.name, o.id AS order_id
FROM customers AS c
LEFT JOIN orders AS o ON o.customer_id = c.id
ORDER BY c.name;
```

That returns 22 rows: the 20 orders, plus one row each for Isla Murray and Jonas Weber,
whose `order_id` is `NULL`. The order of the tables matters: the table you want to keep
whole goes on the left.

## Finding rows with no match

Those `NULL`s are useful. Keep only them, and you have the rows with no match at all, here
the customers who have never ordered:

```sql
SELECT c.name
FROM customers AS c
LEFT JOIN orders AS o ON o.customer_id = c.id
WHERE o.id IS NULL;
```

Test a column that can't be `NULL` in a real match, such as the right-hand table's `id`.
Then `NULL` can only mean "no match".

## Joins and GROUP BY

Joins and grouping work together: join first, then group the joined rows. How many books
has each customer bought?

```sql
SELECT c.name, SUM(oi.quantity) AS books_bought
FROM customers AS c
JOIN orders AS o ON o.customer_id = c.id
JOIN order_items AS oi ON oi.order_id = o.id
GROUP BY c.id, c.name
ORDER BY books_bought DESC;
```

Group by the customer's `id` as well as their `name`, so two customers who happen to share
a name stay separate.

To count matches *including zero*, combine a `LEFT JOIN` with `COUNT(column)`. `COUNT(*)`
would count the `NULL` row a customer with no orders gets, and report 1.

## Putting it together

Money is worked out from `order_items.unit_price`, the price the customer actually paid.
`books.price` is today's price, and two order lines were sold in a sale. Revenue per genre,
best first:

```sql
SELECT b.genre, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS revenue
FROM order_items AS oi
JOIN books AS b ON b.id = oi.book_id
GROUP BY b.genre
ORDER BY revenue DESC;
```

Next, try the quiz to check your understanding, then solve the challenges.
