## What goes wrong in one big table

At a book fair, the shop took orders on paper, and someone typed them into one table:

```sql
SELECT * FROM paper_orders;
```

It looks simple, but it causes trouble:

- **Repeated facts.** Amara's name, email and city are typed out on every order. Paul's city
  is York on one order and Leeds on another. Which is right? A fact stored twice can disagree
  with itself.
- **Repeating columns.** `book_1`, `book_2`, `book_3`: a fourth book has nowhere to go, and
  "who bought Circe?" means checking three columns.
- **Facts that can't exist on their own.** There's no way to record a customer until they
  order something.

**Normalising** a schema means arranging tables so each fact is stored once, in the one place it
belongs.

## One value per cell

The first rule, called **first normal form**: each cell holds one value, and there are no
repeating groups of columns. Lists such as `'Circe, Dune'` in one cell, or `book_1`, `book_2` and
`book_3` columns, become rows in another table instead. You saw how to turn those columns into
rows in lesson 4:

```sql
SELECT order_ref, book_1 AS title FROM paper_orders
UNION ALL SELECT order_ref, book_2 FROM paper_orders WHERE book_2 IS NOT NULL
UNION ALL SELECT order_ref, book_3 FROM paper_orders WHERE book_3 IS NOT NULL
ORDER BY order_ref;
```

## One fact in one place

The next rules, **second** and **third normal form**, boil down to this: every column should
describe the thing the table's key identifies, all of it, and nothing else.

- In `paper_orders` the key is the order, but `customer_name` and `customer_city` describe the
  **customer**. They belong in a customers table, keyed by the customer.
- In `book_categories`, the key is the pair (book, category). A `book_title` column there would
  describe only the book, half of the key, so it belongs in `books`.

The bookshop's own tables already follow these rules. `orders` stores a `customer_id`, not the
customer's name and city, so a customer who moves is updated in one row:

```sql
SELECT o.id, o.ordered_on, c.name, c.city
FROM orders AS o
JOIN customers AS c ON c.id = o.customer_id
WHERE c.id = 1;
```

## Splitting a table

To fix `paper_orders`, create tables with the right shape and fill them from the old one with
`INSERT … SELECT`. First the customers, one row per email address:

```sql
CREATE TABLE fair_customers (
    email TEXT PRIMARY KEY,
    name  TEXT NOT NULL
);
INSERT INTO fair_customers (email, name)
SELECT DISTINCT customer_email, customer_name FROM paper_orders;
SELECT * FROM fair_customers;
```

Paul's two different cities are exactly the kind of problem you have to settle by hand while
splitting. The data can't tell you which is right.

## Many-to-many: junction tables

A book can be in several categories, and a category holds several books. Neither table can hold
a single id pointing at the other, so a third table, a **junction table**, holds one row per
pairing:

```sql
SELECT b.title, c.name AS category
FROM book_categories AS bc
JOIN books AS b ON b.id = bc.book_id
JOIN categories AS c ON c.id = bc.category_id
ORDER BY b.title;
```

The same table answers the question from the other side, such as which books are classics:

```sql
SELECT b.title
FROM categories AS c
JOIN book_categories AS bc ON bc.category_id = c.id
JOIN books AS b ON b.id = bc.book_id
WHERE c.name = 'Classics';
```

## Composite primary keys

`book_categories` has no `id` column. Its primary key is the **pair** `(book_id, category_id)`,
written after the columns as `PRIMARY KEY (book_id, category_id)`. That stops the same book from
being put in the same category twice:

```sql
-- Error: Dune is already in Science Fiction
INSERT INTO book_categories (book_id, category_id) VALUES (6, 4);
```

Putting Dune in a second category is fine, because that pair is new.

## What happens on delete

A foreign key normally refuses to delete a row that others point at. You can choose a different
rule with `ON DELETE`:

- `ON DELETE CASCADE` deletes the pointing rows too.
- `ON DELETE SET NULL` keeps them, but sets their foreign key to `NULL`.
- With no `ON DELETE` (or `RESTRICT`), the delete is refused.

```sql
CREATE TABLE staff_notes (
    id       INTEGER PRIMARY KEY,
    staff_id INTEGER NOT NULL REFERENCES staff (id) ON DELETE CASCADE,
    note     TEXT    NOT NULL
);
INSERT INTO staff_notes (staff_id, note) VALUES (10, 'Speaks Arabic'), (4, 'First aider');
DELETE FROM staff WHERE id = 10;
SELECT * FROM staff_notes;
```

Ahmed's note went with him. Cascades are convenient for things that only make sense with their
parent, like notes or order lines. For important history, such as orders, refusing is safer.

## When to break the rules

Sometimes a copy is the right design. `order_items.unit_price` looks like a duplicate of
`books.price`, but it isn't: it records the price **at the time of the sale**, a different fact
that must not change when the shop changes its prices. Reports sometimes keep pre-calculated
totals, too, to avoid adding up thousands of rows every time. That's called **denormalising**.
Do it on purpose, for a reason, and know which copy is the true one.

## Putting it together

The categories of every book Amara has bought, through two junction-style tables:

```sql
SELECT DISTINCT b.title, c.name AS category
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
JOIN books AS b ON b.id = oi.book_id
JOIN book_categories AS bc ON bc.book_id = b.id
JOIN categories AS c ON c.id = bc.category_id
WHERE o.customer_id = 1
ORDER BY b.title, category;
```

Next, try the quiz to check your understanding, then solve the challenges.
