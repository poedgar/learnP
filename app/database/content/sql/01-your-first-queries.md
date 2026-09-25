## What is a database?

A **database** is an organised collection of data. Most databases you will meet are
**relational**: they store data in **tables**, much like spreadsheets.

- A **table** holds one kind of thing, such as books, customers or orders.
- Each **row** is one item: one book.
- Each **column** is one fact about every item: its title, its price.

Throughout this lesson you'll work with a bookstore database that has a single table
called `books`. Open the **Sandbox** tab at any time to see its columns.

| id | title | author | genre | published_year | price |
|---:|-------|--------|-------|---------------:|------:|
| 1 | Pride and Prejudice | Jane Austen | Romance | 1813 | 9.99 |
| 2 | Frankenstein | Mary Shelley | Horror | 1818 | 8.50 |
| 3 | Nineteen Eighty-Four | George Orwell | Science Fiction | 1949 | 12.99 |

**SQL** (Structured Query Language) is how you talk to a relational database. You write a
**query** that describes the data you want, and the database works out how to fetch it.

## Your first query: SELECT and FROM

Every query that reads data starts with `SELECT`. You say *which columns* you want, then
`FROM` *which table*:

```sql
SELECT title, author
FROM books;
```

The result is itself a table, with one column for each name you listed, in the order you
listed them. Separate column names with commas, and don't put a comma after the last one.

To get every column, use the `*` wildcard:

```sql
SELECT *
FROM books;
```

`SELECT *` is handy for exploring, but in real code you should name the columns you need.
Your query stays readable, and it won't break when someone adds a column later.

> **Style note.** SQL keywords are not case-sensitive: `select` works as well as `SELECT`.
> Writing keywords in capitals is a convention that makes queries easier to read. The
> semicolon marks the end of a statement; you need it when you run more than one statement
> at a time.

## Calculating and renaming columns

A column in your result doesn't have to exist in the table. You can calculate it:

```sql
SELECT title, price, price * 0.9
FROM books;
```

That last column gets an awkward name. Give it a better one with `AS`, which creates an
**alias**:

```sql
SELECT title, price * 0.9 AS sale_price
FROM books;
```

Aliases only rename the column in the result. The table itself doesn't change.

## Removing duplicates with DISTINCT

Several books share a genre. To list each genre only once, add `DISTINCT` straight after
`SELECT`:

```sql
SELECT DISTINCT genre
FROM books;
```

## Filtering rows with WHERE

`SELECT` chooses columns. `WHERE` chooses **rows**: only rows where the condition is true
make it into the result.

```sql
SELECT title, price
FROM books
WHERE price < 10;
```

You can compare with these operators:

| Operator | Meaning |
|----------|---------|
| `=` | equal to |
| `<>` or `!=` | not equal to |
| `<`, `>` | less than, greater than |
| `<=`, `>=` | less than or equal, greater than or equal |

Text values go inside **single quotes**. Numbers don't need quotes.

```sql
SELECT title, published_year
FROM books
WHERE genre = 'Fantasy';
```

### Combining conditions

Use `AND` when every condition must be true, and `OR` when at least one must be:

```sql
SELECT title, genre, price
FROM books
WHERE genre = 'Horror' OR genre = 'Romance';
```

```sql
SELECT title, price
FROM books
WHERE genre = 'Fantasy' AND in_stock = 1;
```

When you mix `AND` and `OR`, `AND` is evaluated first, just like multiplication before
addition. Use parentheses to make your meaning explicit:

```sql
SELECT title, genre, price
FROM books
WHERE (genre = 'Horror' OR genre = 'Fantasy') AND price < 13;
```

## Sorting with ORDER BY

Rows come back in no guaranteed order unless you ask for one. `ORDER BY` sorts the result;
add `DESC` for largest first (`ASC`, smallest first, is the default):

```sql
SELECT title, price
FROM books
ORDER BY price DESC;
```

You can sort by several columns. The second one breaks ties in the first:

```sql
SELECT genre, title
FROM books
ORDER BY genre, title;
```

## Limiting results with LIMIT

`LIMIT` keeps only the first *n* rows. Combined with `ORDER BY`, it answers "top *n*"
questions:

```sql
SELECT title, pages
FROM books
ORDER BY pages DESC
LIMIT 3;
```

## Putting it together

The clauses always appear in this order. You can leave out the ones you don't need, but you
can't rearrange them:

```sql
SELECT DISTINCT author
FROM books
WHERE published_year >= 1900
ORDER BY author
LIMIT 5;
```

1. `FROM` picks the table.
2. `WHERE` keeps the matching rows.
3. `SELECT` picks and calculates the columns, and `DISTINCT` removes duplicates.
4. `ORDER BY` sorts the result.
5. `LIMIT` cuts it down to size.

Notice that the database *runs* the clauses in a different order from the one you *write*
them in. Keep that in mind; it explains a lot of SQL's behaviour later on.

Next, try the quiz to check your understanding, then solve the challenges.
