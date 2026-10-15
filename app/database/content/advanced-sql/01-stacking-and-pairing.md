## The bookshop grows again

This course uses the bookshop from the SQL course, with the same `books`, `customers`,
`orders` and `order_items`. The shop has grown since then. There are now tables for its
`staff`, book `categories`, customers' `wishlist`, `reviews`, website `visits`, app `events`
and more. The schema panel above the editor lists them all. Have a look at the staff:

```sql
SELECT id, name, role, manager_id, city
FROM staff;
```

Each staff member's `manager_id` is the `id` of another staff member. Margaret, the owner, has
no manager.

## Stacking results with UNION

A join puts tables side by side. `UNION` stacks the results of two queries **on top of each
other**. Every city where the shop has a customer or a member of staff:

```sql
SELECT city FROM customers
UNION
SELECT city FROM staff;
```

`UNION` removes duplicate rows, so London appears once even though many people live there.

## UNION ALL keeps duplicates

`UNION ALL` keeps every row from both queries, duplicates included:

```sql
SELECT city FROM customers
UNION ALL
SELECT city FROM staff;
```

That gives 20 rows: 10 customers and 10 staff. Use `UNION ALL` when duplicates matter, or
when you know there are none. It's also faster, because the database doesn't have to look
for duplicates.

A literal value can label where each row came from:

```sql
SELECT name, 'customer' AS kind FROM customers
UNION ALL
SELECT name, 'staff' FROM staff
ORDER BY name;
```

## The rules for stacking

- Both queries must return the **same number of columns**. The columns are matched by
  position, not by name.
- The result takes its column names from the **first** query.
- `ORDER BY` and `LIMIT` go at the very end, and apply to the whole stacked result.

```sql
-- Error: the first query has two columns and the second has one
SELECT name, city FROM customers
UNION
SELECT name FROM staff;
```

## Rows in both, or only one: INTERSECT and EXCEPT

`INTERSECT` keeps the rows that both queries return. Cities with a customer **and** a member
of staff:

```sql
SELECT city FROM customers
INTERSECT
SELECT city FROM staff;
```

`EXCEPT` keeps the rows of the first query that the second doesn't return. Cities with
customers but no staff:

```sql
SELECT city FROM customers
EXCEPT
SELECT city FROM staff;
```

Unlike `UNION`, the order matters: swap the two queries, and you get the cities with staff but
no customers. Notice that the `INTERSECT` result includes an empty city. When stacking and
comparing rows, two `NULL`s count as the same. The next lesson looks at `NULL` more closely.

## Joining a table to itself

To show each member of staff next to their manager, join `staff` to itself. Aliases tell the
two copies apart: `s` is the member of staff and `m` is their manager.

```sql
SELECT s.name, s.role, m.name AS manager
FROM staff AS s
LEFT JOIN staff AS m ON m.id = s.manager_id;
```

`LEFT JOIN` keeps Margaret, who has no manager. A self-join can also pair rows. Books by the
same author, with each pair listed once:

```sql
SELECT a.author, a.title, b.title AS other_title
FROM books AS a
JOIN books AS b ON b.author = a.author AND a.id < b.id;
```

`a.id < b.id` stops a book from pairing with itself, and keeps only one of the pairs (A, B)
and (B, A).

## Every combination: CROSS JOIN

`CROSS JOIN` pairs every row of one table with every row of the other. It has no `ON`.
Every staff member with every role in the shop, for a training plan:

```sql
SELECT s.name, r.role
FROM staff AS s
CROSS JOIN (SELECT DISTINCT role FROM staff) AS r
ORDER BY s.name, r.role;
```

With 10 staff and 8 different roles that's 80 rows. Cross joins grow fast: 16 books
crossed with 10 customers is already 160 rows.

## RIGHT and FULL OUTER JOIN

`RIGHT JOIN` is a `LEFT JOIN` the other way round: it keeps every row of the **second** table.
Customers with nothing on their wishlist:

```sql
SELECT c.name
FROM wishlist AS w
RIGHT JOIN customers AS c ON c.id = w.customer_id
WHERE w.customer_id IS NULL;
```

Most people swap the tables and write a `LEFT JOIN` instead. `FULL OUTER JOIN` keeps
unmatched rows from **both** sides, with `NULL`s where there's no match:

```sql
SELECT c.city AS customer_city, s.city AS staff_city
FROM (SELECT DISTINCT city FROM customers) AS c
FULL OUTER JOIN (SELECT DISTINCT city FROM staff) AS s ON s.city = c.city;
```

## Putting it together

A timeline of everything customer 1 did: orders and reviews stacked together, newest first:

```sql
SELECT ordered_on AS happened_on, 'order' AS what, id AS ref
FROM orders
WHERE customer_id = 1
UNION ALL
SELECT reviewed_on, 'review', book_id
FROM reviews
WHERE customer_id = 1
ORDER BY happened_on DESC;
```

Next, try the quiz to check your understanding, then solve the challenges.
