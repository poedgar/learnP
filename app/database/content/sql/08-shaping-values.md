## Choosing a value with CASE

`CASE` picks a value depending on conditions, like an "if" for a single column. Each book
gets a length label:

```sql
SELECT title, pages,
       CASE
           WHEN pages < 300 THEN 'short'
           WHEN pages < 450 THEN 'medium'
           ELSE 'long'
       END AS length
FROM books;
```

The conditions are tried **in order**, and the first one that's true wins: a 250-page book
is "short" even though it's also under 450. `ELSE` covers everything else. Leave out `ELSE`,
and rows that match no condition get `NULL`.

## Counting with CASE

Put a `CASE` inside an aggregate to count or add up only some rows, several different ways
in one query. In-stock and out-of-stock books per genre, side by side:

```sql
SELECT genre,
       SUM(CASE WHEN in_stock = 1 THEN 1 ELSE 0 END) AS in_stock,
       SUM(CASE WHEN in_stock = 0 THEN 1 ELSE 0 END) AS out_of_stock
FROM books
GROUP BY genre;
```

Each `CASE` turns a row into 1 or 0, and `SUM` adds those up.

## Working with text

SQLite has functions for changing and taking apart text:

| Function | Gives |
|----------|-------|
| `UPPER(t)`, `LOWER(t)` | the text in capitals or small letters |
| `LENGTH(t)` | the number of characters |
| `SUBSTR(t, start, length)` | part of the text; the first character is position 1 |
| `REPLACE(t, find, with)` | the text with every `find` replaced |
| `TRIM(t)` | the text without spaces at either end |
| `INSTR(t, find)` | the position where `find` first appears, or 0 |

`||` joins pieces of text together:

```sql
SELECT UPPER(title) AS shouting,
       REPLACE(title, 'The ', '') AS without_the,
       title || ' by ' || author AS full_title
FROM books;
```

Functions can be nested. A customer's first name is everything before the first space:

```sql
SELECT name, SUBSTR(name, 1, INSTR(name, ' ') - 1) AS first_name
FROM customers;
```

## Working with numbers

You've met `ROUND`. `ABS` drops a minus sign. And watch out for **integer division**: when
both numbers are whole, SQLite divides and throws away the remainder.

```sql
SELECT 7 / 2, 7 / 2.0, 7 % 2, ABS(-3), ROUND(2 / 3.0, 2);
```

`7 / 2` is 3, but `7 / 2.0` is 3.5. `%` gives the remainder. If you need decimals from two
whole-number columns, multiply one of them by `1.0` first.

## Dates

The bookshop stores dates as text in the form `YYYY-MM-DD`. SQLite's date functions read and
write that form:

```sql
SELECT date('2026-03-14', '+7 days') AS a_week_later,
       date('2026-03-31', '+1 day') AS next_day,
       date('2026-03-14', 'start of month') AS month_start,
       strftime('%Y', '2026-03-14') AS year,
       strftime('%m', '2026-03-14') AS month;
```

`strftime` formats a date: `%Y` is the year, `%m` the month and `%d` the day. It makes
grouping by month easy. In which year did each customer join?

```sql
SELECT name, strftime('%Y', joined_on) AS joined
FROM customers;
```

## Days between dates

`julianday` turns a date into a day number, so subtracting two of them gives the days in
between. How many days before 8 April 2026 did each customer join?

```sql
SELECT name, joined_on, julianday('2026-04-08') - julianday(joined_on) AS days
FROM customers;
```

The result is a decimal number such as `157.0`. `NULL` in either date gives `NULL`.

## Converting with CAST, and NULLIF

`CAST(value AS type)` converts a value. Turning a decimal into an `INTEGER` drops the
fraction:

```sql
SELECT CAST('12' AS INTEGER) + 1, CAST(9.99 AS INTEGER), CAST(price AS TEXT)
FROM books
LIMIT 1;
```

`NULLIF(a, b)` gives `NULL` when `a` equals `b`, and `a` otherwise. Its classic use is
avoiding division by zero. In most databases dividing by 0 is an error; SQLite returns `NULL`,
but `NULLIF` makes your intention clear either way. The average price of each genre's
out-of-stock books, where some genres have none:

```sql
SELECT genre,
       ROUND(SUM(CASE WHEN in_stock = 0 THEN price ELSE 0 END)
             / NULLIF(SUM(CASE WHEN in_stock = 0 THEN 1 ELSE 0 END), 0), 2) AS average_out_of_stock
FROM books
GROUP BY genre;
```

## Putting it together

A month-by-month summary of orders and how many have shipped:

```sql
SELECT strftime('%Y-%m', ordered_on) AS month,
       COUNT(*) AS orders,
       SUM(CASE WHEN shipped_on IS NULL THEN 1 ELSE 0 END) AS waiting
FROM orders
GROUP BY month
ORDER BY month;
```

Next, try the quiz to check your understanding, then solve the challenges.
