## Finding rows without an index

To find customer 3's orders, the database has no choice but to read **every** row of `orders`
and check each one. With 20 orders that's instant. With 20 million it isn't. An **index** is
like the index at the back of a book: a sorted list of values, each pointing to the rows that
hold it, so the database can jump straight to the right place.

This lesson's examples use small tables, so you won't feel the difference in speed. You'll see
it in the **query plan** instead: how the database intends to run a query.

## Reading a query plan

Put `EXPLAIN QUERY PLAN` in front of a query to see the plan instead of the result:

```sql
EXPLAIN QUERY PLAN
SELECT * FROM orders WHERE customer_id = 3;
```

The `detail` column says `SCAN orders`: read the whole table, row by row. Compare a lookup by
primary key:

```sql
EXPLAIN QUERY PLAN
SELECT * FROM books WHERE id = 3;
```

`SEARCH books USING INTEGER PRIMARY KEY` means it jumps straight to the row. **SCAN** reads
everything; **SEARCH** goes directly to the matching rows.

## Creating an index

`CREATE INDEX name ON table (column)` builds an index. Give it a name that says what it covers:

```sql
CREATE INDEX orders_customer ON orders (customer_id);

EXPLAIN QUERY PLAN
SELECT * FROM orders WHERE customer_id = 3;
```

Now the plan says `SEARCH orders USING INDEX orders_customer (customer_id=?)`. The query itself
didn't change, and neither does its result. The database decides by itself when an index helps.
`DROP INDEX orders_customer;` removes it again.

## Indexes on several columns

An index can cover several columns, sorted by the first, then by the second within it, like a
phone book sorted by surname and then first name. It helps any query that filters on the
**leading** columns:

```sql
CREATE INDEX books_author_year ON books (author, published_year);

EXPLAIN QUERY PLAN
SELECT title FROM books
WHERE author = 'J.R.R. Tolkien' AND published_year > 1940;
```

That's a `SEARCH` on both columns. But a phone book sorted by surname is no help for finding
everyone called Anna. Filtering on `published_year` alone can't use the index:

```sql
CREATE INDEX books_author_year ON books (author, published_year);

EXPLAIN QUERY PLAN
SELECT title FROM books WHERE published_year > 1940;
```

So the order of columns matters: put the column you always filter on first.

## Covering indexes

An index stores its own columns. If a query needs no other columns, the database never has to
look at the table at all:

```sql
CREATE INDEX books_author_year ON books (author, published_year);

EXPLAIN QUERY PLAN
SELECT author, published_year FROM books WHERE author = 'J.R.R. Tolkien';
```

`USING COVERING INDEX` means the index alone answered the query. That's one reason to avoid
`SELECT *` in queries that run often.

## Indexes and sorting

An index is already sorted, so it can also save a sort. Without a suitable index, the plan shows
an extra step, `USE TEMP B-TREE FOR ORDER BY`:

```sql
EXPLAIN QUERY PLAN
SELECT * FROM reviews WHERE book_id = 4 ORDER BY reviewed_on DESC;
```

An index on `(book_id, reviewed_on)` would let the database find book 4's reviews and read them
in date order, with no separate sort.

## When an index can't help

An index holds the column's values as they are. A condition that changes the column first can't
use it:

```sql
CREATE INDEX books_title ON books (title);

EXPLAIN QUERY PLAN
SELECT * FROM books WHERE lower(title) = 'dune';
```

That's a `SCAN`, because the index knows `Dune`, not `dune`. The fix is an index on the
expression itself:

```sql
CREATE INDEX books_lower_title ON books (lower(title));

EXPLAIN QUERY PLAN
SELECT * FROM books WHERE lower(title) = 'dune';
```

`LIKE` with a wildcard at the start, such as `LIKE '%Tolkien'`, can't use an index either: a
sorted list can't tell you which entries **end** with something.

## The indexes you already have

Every `PRIMARY KEY` (other than a plain `INTEGER PRIMARY KEY`, which is the row's own id) and
every `UNIQUE` rule creates an index automatically, to check for duplicates quickly. They're
listed in `sqlite_master`, the table that describes the database:

```sql
SELECT name, tbl_name FROM sqlite_master WHERE type = 'index';
```

`PRAGMA index_list(table)` lists one table's indexes, and `PRAGMA index_info(index)` shows an
index's columns. A `UNIQUE` index enforces uniqueness, just like a `UNIQUE` rule:

```sql
CREATE UNIQUE INDEX customers_email ON customers (email);
PRAGMA index_list(customers);
```

## The cost of indexes

Indexes aren't free. Every `INSERT`, `UPDATE` and `DELETE` has to update every index on the
table, and each index takes up space. Don't index every column. Index the columns your frequent
queries filter, join and sort on, check with `EXPLAIN QUERY PLAN` that they're used, and drop
the ones that aren't. On very small tables a scan is just as fast, and the database may ignore
an index anyway.

## Putting it together

Joins benefit from an index on the joining column. Without one, finding each book's order lines
scans `order_items`:

```sql
CREATE INDEX order_items_book ON order_items (book_id);

EXPLAIN QUERY PLAN
SELECT b.title, SUM(oi.quantity) AS copies
FROM books AS b
JOIN order_items AS oi ON oi.book_id = b.id
WHERE b.genre = 'Fantasy'
GROUP BY b.id;
```

Try it without the `CREATE INDEX` line and compare the plans. Next, try the quiz to check your
understanding, then solve the challenges.
