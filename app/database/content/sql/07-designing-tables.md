## Creating a table

Every table you've used so far was created with `CREATE TABLE`. You name the table, then
list its columns, each with a **type**:

```sql
CREATE TABLE publishers (
    id      INTEGER PRIMARY KEY,
    name    TEXT NOT NULL,
    founded INTEGER
);

INSERT INTO publishers (name, founded) VALUES ('Bloomsbury', 1986);
SELECT * FROM publishers;
```

SQLite's main types are `INTEGER` (whole numbers), `REAL` (decimals), `TEXT` and `BLOB` (raw
bytes). It has no separate date type, which is why the bookshop stores dates as text in the
form `YYYY-MM-DD`.

SQLite is relaxed about types: a column's type is a preference, not a rule. It will happily
store text in an `INTEGER` column:

```sql
CREATE TABLE notes (pages INTEGER);
INSERT INTO notes VALUES ('lots');
SELECT pages, typeof(pages) FROM notes;
```

Most other databases would refuse. In SQLite you can ask for that strictness by adding
`STRICT` after the closing parenthesis of `CREATE TABLE`.

## Primary keys

A **primary key** identifies each row, so no two rows may share one. A column declared
`INTEGER PRIMARY KEY` is special in SQLite: leave it out when you insert, and the database
numbers the rows for you, as you saw with `customers` in the last lesson.

```sql
-- Error: two shelves can't share the same code.
CREATE TABLE shelves (code TEXT PRIMARY KEY, room TEXT);
INSERT INTO shelves VALUES ('A1', 'Front');
INSERT INTO shelves VALUES ('A1', 'Back');
```

A primary key can span several columns. `order_items` uses the pair
`(order_id, book_id)`: an order can't list the same book twice, but different orders can
contain the same book. `PRAGMA table_info` shows a table's columns, and the `pk` column
numbers the primary key's parts:

```sql
PRAGMA table_info(order_items);
```

## NOT NULL and DEFAULT

`NOT NULL` makes a column required. `DEFAULT` gives the value to use when an `INSERT` leaves
the column out:

```sql
CREATE TABLE events (
    title     TEXT NOT NULL,
    starts_on TEXT NOT NULL,
    room      TEXT NOT NULL DEFAULT 'Main shop'
);

INSERT INTO events (title, starts_on) VALUES ('Poetry night', '2026-05-01');
SELECT * FROM events;
```

Leaving out a required column with no default is an error:

```sql
-- Error: starts_on is required.
CREATE TABLE events (title TEXT NOT NULL, starts_on TEXT NOT NULL);
INSERT INTO events (title) VALUES ('Book swap');
```

A default is only used when the column is left out. Writing `NULL` yourself stores `NULL`, or
breaks `NOT NULL`.

## UNIQUE

`UNIQUE` stops two rows having the same value in a column:

```sql
-- Error: the gift card code GIFT-10 is already taken.
CREATE TABLE gift_cards (code TEXT NOT NULL UNIQUE, amount REAL NOT NULL);
INSERT INTO gift_cards VALUES ('GIFT-10', 10);
INSERT INTO gift_cards VALUES ('GIFT-10', 25);
```

One surprise: `NULL` doesn't count as a value here, so a `UNIQUE` column can hold any number
of `NULL`s. Add `NOT NULL` if every row must have a value. `UNIQUE (a, b)` written after the
columns makes a *combination* of columns unique.

## CHECK

`CHECK` adds your own rule. A row that makes the condition false is refused:

```sql
-- Error: a stock count can't be negative.
CREATE TABLE stock_counts (
    book_id INTEGER NOT NULL,
    copies  INTEGER NOT NULL CHECK (copies >= 0)
);
INSERT INTO stock_counts VALUES (1, -2);
```

Any condition you could write in a `WHERE` works, such as `CHECK (price > 0)` or
`CHECK (genre IN ('Fantasy', 'Horror'))`.

One catch: a condition about `NULL` is neither true nor false, so a `NULL` slips past a
`CHECK`. If the column must have a value, add `NOT NULL` as well.

## Foreign keys

A **foreign key** says that a column holds another table's primary key, with
`REFERENCES table (column)`. The database then refuses values that point at nothing:

```sql
CREATE TABLE loans (
    id          INTEGER PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    book_id     INTEGER NOT NULL REFERENCES books (id)
);

INSERT INTO loans (customer_id, book_id) VALUES (1, 6);
SELECT * FROM loans;
```

```sql
-- Error: there is no book 99.
CREATE TABLE loans (
    id          INTEGER PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    book_id     INTEGER NOT NULL REFERENCES books (id)
);
INSERT INTO loans (customer_id, book_id) VALUES (1, 99);
```

It also works the other way: as you saw in the last lesson, you can't delete a customer while
their orders still point at them.

SQLite only enforces foreign keys when they are switched on with the setting
`PRAGMA foreign_keys = ON`. This sandbox switches it on for you; in your own SQLite
databases, run that setting each time you connect.

## Changing and removing tables

`ALTER TABLE` changes a table that already exists. The most common change adds a column; the
existing rows get `NULL` (or the column's default):

```sql
ALTER TABLE books ADD COLUMN isbn TEXT;
SELECT title, isbn FROM books LIMIT 3;
```

`ALTER TABLE … RENAME COLUMN old TO new` renames a column, and `ALTER TABLE … RENAME TO new`
renames the table.

`DROP TABLE` removes a table and everything in it. Unlike `DELETE FROM`, which empties a table
but keeps it, there is nothing left afterwards:

```sql
DROP TABLE order_items;
SELECT name FROM pragma_table_list WHERE schema = 'main' AND name NOT LIKE 'sqlite_%';
```

## Putting it together

A table for book reviews: each review belongs to a book and a customer, has between 1 and 5
stars, gets today's date unless told otherwise, and each customer can review a book only once.

```sql
CREATE TABLE book_reviews (
    id          INTEGER PRIMARY KEY,
    book_id     INTEGER NOT NULL REFERENCES books (id),
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    stars       INTEGER NOT NULL CHECK (stars BETWEEN 1 AND 5),
    written_on  TEXT    NOT NULL DEFAULT '2026-04-08',
    body        TEXT,
    UNIQUE (customer_id, book_id)
);

INSERT INTO book_reviews (book_id, customer_id, stars, body)
VALUES (6, 2, 5, 'Worth every page.');

SELECT b.title, c.name, r.stars, r.written_on, r.body
FROM book_reviews AS r
JOIN books AS b ON b.id = r.book_id
JOIN customers AS c ON c.id = r.customer_id;
```

Every rule a table enforces is one mistake your data can never contain.

Next, try the quiz to check your understanding, then solve the challenges. Each one is checked
by trying rows against your table, so make every rule the challenge asks for.
