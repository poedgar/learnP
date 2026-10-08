## Changing data, safely

So far you've only read data. This lesson changes it: adding rows with `INSERT`, changing
them with `UPDATE` and removing them with `DELETE`.

In a real database these changes are permanent, so be careful. Here you can't break
anything: **every run starts from a fresh copy of the bookshop**, so a change lasts only
until the end of that run. That's also why each example below ends with a `SELECT`, to show
you the change before it disappears.

## Adding rows with INSERT

`INSERT INTO` names the table and the columns you're filling in; `VALUES` gives the values,
in the same order:

```sql
INSERT INTO customers (name, email, city, joined_on)
VALUES ('Lena Fischer', 'lena@example.com', 'Bristol', '2026-04-08');

SELECT * FROM customers WHERE name = 'Lena Fischer';
```

The `id` was left out, and the database filled it in with the next number, 11. Any other
column you leave out gets its default, or `NULL` if it has none. (The next lesson shows how
tables say what their defaults are.)

To add several rows at once, separate them with commas:

```sql
INSERT INTO customers (name, email, joined_on)
VALUES ('Omar Said', 'omar@example.com', '2026-04-08'),
       ('Priya Shah', 'priya@example.com', '2026-04-09');

SELECT id, name, city FROM customers WHERE id > 10;
```

## Copying rows with INSERT … SELECT

Instead of `VALUES`, an `INSERT` can take the rows a query returns. To put a free copy of
*The Remains of the Day* (book 12) into every order that hasn't shipped yet:

```sql
INSERT INTO order_items (order_id, book_id, quantity, unit_price)
SELECT id, 12, 1, 0
FROM orders
WHERE shipped_on IS NULL;

SELECT order_id, book_id, quantity, unit_price FROM order_items WHERE book_id = 12;
```

The query's columns fill the listed columns in order: each unshipped order's `id`, then the
fixed values 12, 1 and 0.

## Changing rows with UPDATE

`UPDATE` changes values in rows that already exist. `SET` says what to change and `WHERE`
says which rows:

```sql
UPDATE customers
SET city = 'Leeds'
WHERE id = 4;

SELECT id, name, city FROM customers WHERE id = 4;
```

To change several columns, separate them with commas: `SET city = 'Leeds', email = '…'`.

**Without a `WHERE`, `UPDATE` changes every row.** This sets every book's price to 5:

```sql
UPDATE books
SET price = 5;

SELECT title, price FROM books;
```

## Calculations and subqueries in UPDATE

The new value can be calculated from the old one. Classics published before 1900 go up by 1:

```sql
UPDATE books
SET price = price + 1
WHERE published_year < 1900;

SELECT title, published_year, price FROM books WHERE published_year < 1900;
```

`WHERE` can use a subquery, too. Take every book that has never sold out of stock:

```sql
UPDATE books
SET in_stock = 0
WHERE id NOT IN (SELECT book_id FROM order_items);

SELECT title, in_stock FROM books;
```

(`NOT IN` is safe here: `book_id` can never be `NULL`.)

## Removing rows with DELETE

`DELETE FROM` removes the rows `WHERE` matches. To take *A Wizard of Earthsea* out of order 20:

```sql
DELETE FROM order_items
WHERE order_id = 20 AND book_id = 8;

SELECT * FROM order_items WHERE order_id = 20;
```

Foreign keys protect the links between tables. Amara Okafor has orders that point at her, so
the database refuses to delete her:

```sql
-- Error: customer 1 still has orders, and the foreign key protects them.
DELETE FROM customers
WHERE id = 1;
```

A customer with no orders, such as Isla Murray, can be deleted. And like `UPDATE`, **`DELETE`
without `WHERE` removes every row** of the table.

## Look before you change

The safest habit with `UPDATE` and `DELETE`: write the `SELECT` first, with the `WHERE` you
plan to use, and check it returns exactly the rows you mean.

```sql
SELECT id, title, published_year
FROM books
WHERE published_year < 1850;
```

Two books. Now you can swap the first line for the change, keeping the `WHERE` as it is:

```sql
UPDATE books
SET in_stock = 0
WHERE published_year < 1850;

SELECT title, in_stock FROM books WHERE published_year < 1850;
```

## Transactions

Some changes belong together: cancelling an order means deleting its lines *and* the order.
A **transaction** groups statements so that they all happen, or none do.

- `BEGIN` starts a transaction.
- `COMMIT` keeps every change made since `BEGIN`.
- `ROLLBACK` undoes them all.

```sql
BEGIN;

UPDATE books SET price = 0;
SELECT title, price FROM books LIMIT 3;

ROLLBACK;

SELECT title, price FROM books LIMIT 3;
```

The first `SELECT` sees free books; after `ROLLBACK`, the prices are back. In a real database,
if something goes wrong in the middle of a transaction, rolling back leaves the data as it
was, instead of half changed.

Next, try the quiz to check your understanding, then solve the challenges. They check the
data after your SQL has run, so you don't need to finish with a `SELECT`.
