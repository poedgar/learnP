## The bookshop grows

The bookstore from the last lesson now sells online, so its database has three new tables
next to `books`:

- `customers`: who buys from the shop, with their `name`, `email`, `city` and the date they
  `joined_on`.
- `orders`: one row per order, with the `customer_id` of the customer who placed it, the date
  it was `ordered_on` and the date it was `shipped_on`.
- `order_items`: the books in each order, with the `quantity` and the `unit_price` paid.

Open the **Sandbox** tab and press **Show tables** to see every column. In this lesson you'll
query these tables one at a time; the lesson after next shows how to combine them.

```sql
SELECT name, city, joined_on
FROM customers;
```

## Matching patterns with LIKE

`=` only finds exact matches. To find text that *looks like* something, use `LIKE` with a
**pattern**. Two characters have a special meaning in a pattern:

| Symbol | Matches |
|--------|---------|
| `%` | any number of characters, including none |
| `_` | exactly one character |

```sql
SELECT title
FROM books
WHERE title LIKE 'The%';
```

`'The%'` means "starts with *The*". Move the `%` around to change the question:

| Pattern | Finds titles that… |
|---------|--------------------|
| `'The%'` | start with *The* |
| `'%Ring'` | end with *Ring* |
| `'%of%'` | contain *of* anywhere |
| `'_une'` | have exactly four characters and end in *une* |

```sql
SELECT title
FROM books
WHERE title LIKE '_une';
```

## Letter case and quotes

In SQLite, `LIKE` ignores the difference between upper and lower case for plain English
letters, so `'%the%'` finds *The Hobbit* as well as *The Name of the Wind*:

```sql
SELECT title
FROM books
WHERE title LIKE '%the%';
```

That returns 6 titles. `=` does care about case: `WHERE title = 'dune'` finds nothing,
because the title is stored as *Dune*. Other databases differ here; PostgreSQL's `LIKE`, for
example, is case-sensitive.

Text goes in single quotes, so what if the text itself contains one? Write it twice. One
customer is called *Siobhan O'Brien*:

```sql
SELECT name, email
FROM customers
WHERE name = 'Siobhan O''Brien';
```

## Checking against a list with IN

When a column can match any of several values, a chain of `OR`s gets long. `IN` takes a
list instead:

```sql
SELECT title, genre
FROM books
WHERE genre IN ('Horror', 'Romance');
```

That means exactly the same as `genre = 'Horror' OR genre = 'Romance'`. `NOT IN` keeps the
rows that match none of the values:

```sql
SELECT title, genre
FROM books
WHERE genre NOT IN ('Fantasy', 'Science Fiction');
```

## Ranges with BETWEEN

`BETWEEN low AND high` keeps values in a range. **Both ends are included**, so this is the
same as `price >= 10 AND price <= 15`:

```sql
SELECT title, price
FROM books
WHERE price BETWEEN 10 AND 15;
```

It works on dates too. The dates in this database are text in the form `YYYY-MM-DD`, which
sorts in date order, so you can compare them directly. These are the orders placed in
February 2026:

```sql
SELECT id, ordered_on
FROM orders
WHERE ordered_on BETWEEN '2026-02-01' AND '2026-02-28';
```

## Missing values: NULL

Two customers didn't give a city, and some orders haven't shipped yet. Those cells hold
`NULL`, which means "no value" or "unknown". It is not zero, and it is not empty text.

```sql
SELECT name, city
FROM customers;
```

Because `NULL` is unknown, comparing anything with it gives *unknown* too, never *true*. So
this finds **no rows**, even though two cities are missing:

```sql
SELECT name
FROM customers
WHERE city = NULL;
```

To test for a missing value, use `IS NULL`, or `IS NOT NULL` for the opposite:

```sql
SELECT name
FROM customers
WHERE city IS NULL;
```

```sql
SELECT id, ordered_on, shipped_on
FROM orders
WHERE shipped_on IS NOT NULL;
```

The same goes for `<>` and `NOT IN`: a comparison with `NULL` is never true, so a `NULL`
inside a `NOT IN` list makes the whole condition unknown for every row.

## Showing a fallback with COALESCE

`COALESCE` returns the first of its arguments that isn't `NULL`. Use it to show something
friendlier than an empty cell:

```sql
SELECT id, ordered_on, COALESCE(shipped_on, 'not shipped yet') AS shipped
FROM orders;
```

It only changes what the query shows. The data in the table stays `NULL`.

## Putting it together

These conditions combine with `AND`, `OR` and parentheses like any others. Customers in
London or Leeds whose name contains an *a* and who joined between December 2025 and March
2026:

```sql
SELECT name, city, joined_on
FROM customers
WHERE city IN ('London', 'Leeds')
  AND name LIKE '%a%'
  AND joined_on BETWEEN '2025-12-01' AND '2026-03-31'
ORDER BY joined_on;
```

Next, try the quiz to check your understanding, then solve the challenges.
