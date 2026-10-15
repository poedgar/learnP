## Saving a query as a view

Some queries get written again and again, such as each order's total. A **view** saves a
query under a name, so you can use it like a table:

```sql
CREATE VIEW order_totals AS
SELECT o.id AS order_id, o.customer_id, o.ordered_on,
       SUM(oi.quantity * oi.unit_price) AS total
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
GROUP BY o.id;

SELECT * FROM order_totals WHERE total > 40;
```

A view stores the **query**, not its results. Every time you select from it, the query runs
again, so a view is always up to date.

## Using views

A view can be filtered, joined, grouped, and used inside other queries and views:

```sql
CREATE VIEW order_totals AS
SELECT o.id AS order_id, o.customer_id, SUM(oi.quantity * oi.unit_price) AS total
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
GROUP BY o.id;

SELECT c.name, ROUND(AVG(t.total), 2) AS average_order
FROM order_totals AS t
JOIN customers AS c ON c.id = t.customer_id
GROUP BY c.id
ORDER BY average_order DESC;
```

Views are good for naming a calculation once, so everyone gets the same answer, and for showing
only some columns. A view of reviews for the website could leave out who wrote them. You can't
`INSERT`, `UPDATE` or `DELETE` through a view in SQLite; change the tables underneath it instead.
`DROP VIEW name` removes a view, and leaves the tables alone.

## Triggers: SQL that runs by itself

A **trigger** is SQL that the database runs automatically when a table changes. It names the
event (`INSERT`, `UPDATE` or `DELETE`), the table, and whether it runs `BEFORE` or `AFTER` the
change. Its statements go between `BEGIN` and `END`:

```sql
CREATE TABLE log (message TEXT);

CREATE TRIGGER customers_added AFTER INSERT ON customers
BEGIN
    INSERT INTO log (message) VALUES ('New customer: ' || NEW.name);
END;

INSERT INTO customers (name, email, joined_on)
VALUES ('Kofi Mensah', 'kofi@example.com', '2026-04-08');
SELECT * FROM log;
```

Inside the trigger, `NEW` is the row being inserted. The trigger runs once for each row.

## Recording history

In an `UPDATE` trigger, `OLD` is the row before the change and `NEW` the row after it. That makes
it easy to keep a history of prices. `UPDATE OF price` limits the trigger to changes of that
column, and `WHEN` adds a condition:

```sql
CREATE TABLE price_history (book_id INTEGER, old_price REAL, new_price REAL);

CREATE TRIGGER books_price_changed AFTER UPDATE OF price ON books
WHEN NEW.price <> OLD.price
BEGIN
    INSERT INTO price_history (book_id, old_price, new_price)
    VALUES (OLD.id, OLD.price, NEW.price);
END;

UPDATE books SET price = price + 1 WHERE genre = 'Horror';
SELECT * FROM price_history;
```

Nobody has to remember to write the history: every way of changing a price records it. In a
`DELETE` trigger only `OLD` exists, and in an `INSERT` trigger only `NEW`.

## Keeping data in step

A trigger can keep one table in step with another. When a book is sold, take the copies off the
shelf:

```sql
CREATE TRIGGER order_items_sold AFTER INSERT ON order_items
BEGIN
    UPDATE stock_levels
    SET quantity = quantity - NEW.quantity
    WHERE book_id = NEW.book_id;
END;

INSERT INTO order_items (order_id, book_id, quantity, unit_price) VALUES (20, 6, 1, 19.99);
SELECT * FROM stock_levels WHERE book_id = 6;
```

Use this kind of trigger carefully. Logic hidden in triggers surprises people who don't know it's
there, and a long chain of triggers is hard to follow.

## Refusing bad changes

A `BEFORE` trigger can stop a change with `RAISE(ABORT, 'message')`. The statement fails with
that message, and nothing it did is kept:

```sql
-- Error: book 10 has only 1 copy, so the trigger refuses the change
CREATE TRIGGER stock_not_negative BEFORE UPDATE OF quantity ON stock_levels
WHEN NEW.quantity < 0
BEGIN
    SELECT RAISE(ABORT, 'Stock cannot go below zero');
END;

UPDATE stock_levels SET quantity = quantity - 3 WHERE book_id = 10;
```

For a rule about a single row, like this one, a `CHECK (quantity >= 0)` is simpler. Triggers are
for rules a `CHECK` can't express, such as rules that look at other tables.

## Generated columns

A **generated column** is calculated from other columns of the same row. You never write to it;
the database keeps it right:

```sql
ALTER TABLE order_items
ADD COLUMN line_total REAL GENERATED ALWAYS AS (quantity * unit_price) VIRTUAL;

SELECT order_id, book_id, quantity, unit_price, line_total
FROM order_items
WHERE order_id = 3;
```

A `VIRTUAL` column is calculated each time it's read. A `STORED` column is calculated when the row
is written and saved with it, which costs space but makes reading faster and lets you index it.
SQLite can only add `VIRTUAL` columns to an existing table; `STORED` ones must be part of
`CREATE TABLE`:

```sql
CREATE TABLE parcels (
    id        INTEGER PRIMARY KEY,
    weight_g  INTEGER NOT NULL,
    weight_kg REAL GENERATED ALWAYS AS (weight_g / 1000.0) STORED
);
INSERT INTO parcels (weight_g) VALUES (850), (2300);
SELECT * FROM parcels;
```

## Putting it together

A view that uses a generated column, showing each book's sales from its order lines:

```sql
ALTER TABLE order_items
ADD COLUMN line_total REAL GENERATED ALWAYS AS (quantity * unit_price) VIRTUAL;

CREATE VIEW book_sales AS
SELECT b.title, SUM(oi.quantity) AS copies, ROUND(SUM(oi.line_total), 2) AS revenue
FROM books AS b
JOIN order_items AS oi ON oi.book_id = b.id
GROUP BY b.id;

SELECT * FROM book_sales ORDER BY revenue DESC LIMIT 5;
```

Next, try the quiz to check your understanding, then solve the challenges.
