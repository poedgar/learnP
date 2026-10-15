## When the row might already exist

`stock_levels` holds one row per book: how many copies are on the shelf. When a delivery
arrives, the book might already have a row (add to it) or not (create it). A plain `INSERT`
fails if the row exists, because `book_id` is the primary key:

```sql
-- Error: book 4 already has a row
INSERT INTO stock_levels (book_id, quantity, updated_on)
VALUES (4, 5, '2026-04-09');
```

You could check first and then choose `INSERT` or `UPDATE`, but that takes two statements.
An **upsert** (update or insert) does both in one.

## INSERT OR IGNORE

`INSERT OR IGNORE` skips any row that would break a `UNIQUE` or primary key rule, and inserts
the rest:

```sql
INSERT OR IGNORE INTO stock_levels (book_id, quantity, updated_on)
VALUES (4, 5, '2026-04-09'), (12, 3, '2026-04-09');
SELECT * FROM stock_levels WHERE book_id IN (4, 12);
```

Book 12 gets a row. Book 4's row is left exactly as it was.

## INSERT OR REPLACE, and why it's risky

`INSERT OR REPLACE` sounds like an upsert, but on a conflict it **deletes** the old row and
inserts a new one. Any column you didn't mention gets its default, not its old value:

```sql
CREATE TABLE notes (id INTEGER PRIMARY KEY, title TEXT, body TEXT DEFAULT 'empty');
INSERT INTO notes VALUES (1, 'Shopping', 'Milk, bread');
INSERT OR REPLACE INTO notes (id, title) VALUES (1, 'Groceries');
SELECT * FROM notes;
```

The body is gone. Deleting the row can also trip foreign keys and triggers. Prefer the
`ON CONFLICT` forms below.

## ON CONFLICT DO NOTHING

`ON CONFLICT (column) DO NOTHING` is the standard way to write `INSERT OR IGNORE`. The column in
brackets must have a `UNIQUE` rule or be the primary key:

```sql
INSERT INTO stock_levels (book_id, quantity, updated_on)
VALUES (4, 5, '2026-04-09'), (12, 3, '2026-04-09')
ON CONFLICT (book_id) DO NOTHING;
SELECT * FROM stock_levels WHERE book_id IN (4, 12);
```

## ON CONFLICT DO UPDATE and excluded

`DO UPDATE SET …` changes the existing row instead. Inside it, `excluded` is the row you tried
to insert. A delivery of 5 copies of book 4 and 3 copies of book 12:

```sql
INSERT INTO stock_levels (book_id, quantity, updated_on)
VALUES (4, 5, '2026-04-09'), (12, 3, '2026-04-09')
ON CONFLICT (book_id) DO UPDATE SET
    quantity = quantity + excluded.quantity,
    updated_on = excluded.updated_on;
SELECT * FROM stock_levels WHERE book_id IN (4, 12);
```

Book 4 had 8 copies and now has 13. Book 12 had no row, so it was simply inserted. A plain
column name such as `quantity` means the existing row's value.

## Upserts with a condition

Add `WHERE` after `DO UPDATE SET …` to update only sometimes. A stock count from an old
spreadsheet shouldn't overwrite a newer count:

```sql
INSERT INTO stock_levels (book_id, quantity, updated_on)
VALUES (6, 9, '2026-03-15'), (7, 9, '2026-04-09')
ON CONFLICT (book_id) DO UPDATE SET
    quantity = excluded.quantity,
    updated_on = excluded.updated_on
WHERE excluded.updated_on > stock_levels.updated_on;
SELECT * FROM stock_levels WHERE book_id IN (6, 7);
```

Book 6's count is older than the one on file, so its row doesn't change. Book 7's is newer, so
it does. Here the existing row is written `stock_levels.updated_on`, to tell it apart clearly.

## Upserting from a SELECT

An upsert can take its rows from a query. Every book on a wishlist gets a stock row with 0
copies, unless it already has one:

```sql
INSERT INTO stock_levels (book_id, quantity, updated_on)
SELECT DISTINCT book_id, 0, '2026-04-09' FROM wishlist WHERE true
ON CONFLICT (book_id) DO NOTHING;
SELECT * FROM stock_levels ORDER BY book_id;
```

The `WHERE true` looks pointless, and it is, except that SQLite needs a `WHERE` there. Without it,
SQLite reads `ON` as the start of a join condition and reports a syntax error.

## RETURNING: see what changed

Add `RETURNING` and a list of columns to an `INSERT`, `UPDATE` or `DELETE`, and it returns the
rows it changed, like a `SELECT`. The most common use is getting the id of a new row:

```sql
INSERT INTO customers (name, email, joined_on)
VALUES ('Kofi Mensah', 'kofi@example.com', '2026-04-08')
RETURNING id, name;
```

It's equally useful for seeing exactly what an `UPDATE` did:

```sql
UPDATE books
SET price = ROUND(price * 1.1, 2)
WHERE genre = 'Horror'
RETURNING id, title, price;
```

With `DELETE`, `RETURNING` shows the rows that were removed.

## Putting it together

`UPDATE … FROM` updates rows using another table or query, like a join. Apply each book's latest
supplier price, and return what changed:

```sql
UPDATE books
SET price = latest.price
FROM (
    SELECT book_id, price
    FROM (
        SELECT book_id, price,
               ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY sent_at DESC) AS n
        FROM supplier_prices
    )
    WHERE n = 1
) AS latest
WHERE latest.book_id = books.id
RETURNING books.id, books.title, books.price;
```

Next, try the quiz to check your understanding, then solve the challenges.
