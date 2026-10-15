## NULL means "unknown"

`NULL` isn't zero and it isn't empty text. It means "there is no value here", usually because
the value is unknown. Two customers didn't give a city, four reviews have text but no star
rating, and four orders haven't shipped yet:

```sql
SELECT id, book_id, rating, body
FROM reviews
WHERE rating IS NULL;
```

Because `NULL` is unknown, almost anything you do with it is unknown too. That one idea
explains every surprise in this lesson.

## Three answers: true, false and unknown

Is an unknown city equal to `'London'`? You can't say, so the answer is neither true nor
false but **unknown**, which SQL also writes as `NULL`. Even `NULL = NULL` is unknown: two
unknown values might or might not be the same.

```sql
SELECT NULL = NULL, NULL <> 1, NULL AND 0, NULL OR 1, NOT NULL;
```

`NULL AND 0` is false, because false AND anything is false. `NULL OR 1` is true, because true
OR anything is true. The rest stay unknown. **`WHERE` keeps only rows where the condition is
true**, and drops both false and unknown.

## NOT doesn't rescue you

Nine orders shipped after 1 March:

```sql
SELECT COUNT(*) FROM orders WHERE shipped_on > '2026-03-01';
```

You might expect the opposite condition to return the other 11 of the 20 orders:

```sql
SELECT COUNT(*) FROM orders WHERE NOT (shipped_on > '2026-03-01');
```

It returns 7. For the four unshipped orders the comparison is unknown, `NOT` unknown is still
unknown, and `WHERE` drops them both times. Whenever a column can be `NULL`, ask yourself
which group its `NULL` rows should land in, and say so with `IS NULL`.

## Comparing safely: IS and IS NOT

`IS` compares like `=`, but treats two `NULL`s as equal and always answers true or false.
`IS NOT` is its opposite. Staff who aren't based in Leeds, including Ahmed, whose city is
unknown:

```sql
SELECT name, city
FROM staff
WHERE city IS NOT 'Leeds';
```

With `city <> 'Leeds'`, Ahmed would vanish. The standard spelling is `IS DISTINCT FROM` and
`IS NOT DISTINCT FROM`; SQLite understands both.

```sql
SELECT NULL IS NULL, 1 IS 1, NULL IS 1, NULL IS NOT DISTINCT FROM NULL;
```

## NULL in calculations

Arithmetic, `||` and most functions give `NULL` as soon as one input is `NULL`:

```sql
SELECT 'Dune' || NULL, NULL + 1, LENGTH(NULL), ROUND(NULL, 2);
```

`COALESCE` (or `IFNULL`, its two-argument form) swaps `NULL` for a fallback. `COALESCE` takes
any number of values and returns the first that isn't `NULL`:

```sql
SELECT name, COALESCE(city, 'unknown') AS city
FROM staff;
```

## NULL in aggregates

Aggregates skip `NULL`s. `COUNT(*)` counts rows, but `COUNT(rating)` counts only ratings that
exist, and `AVG` averages only those:

```sql
SELECT COUNT(*) AS reviews,
       COUNT(rating) AS ratings,
       AVG(rating) AS average,
       AVG(COALESCE(rating, 0)) AS average_counting_missing_as_zero
FROM reviews;
```

The two averages differ: 4.16 when unrated reviews are left out, 3.43 when they count as zero.
Neither is wrong, but they answer different questions. `SUM`, `MIN` and `MAX` of nothing but
`NULL`s give `NULL`, not 0.

## NULL in groups, sorting and joins

`GROUP BY` puts all the `NULL`s into **one** group, and `DISTINCT` keeps one `NULL`. Sorting
puts `NULL` first in ascending order and last in descending order. Use `NULLS FIRST` or
`NULLS LAST` to choose:

```sql
SELECT name, city
FROM staff
ORDER BY city DESC NULLS FIRST;
```

Joins compare with `=`, so a `NULL` **never** matches anything, not even another `NULL`. Joining
customers to staff on their city leaves out every customer with no city:

```sql
SELECT c.name AS customer, s.name AS staff_member
FROM customers AS c
JOIN staff AS s ON s.city = c.city
ORDER BY c.name;
```

The same goes for a simple `CASE`. `CASE city WHEN NULL THEN …` compares with `=` and never
matches. Write `CASE WHEN city IS NULL THEN …` instead.

## NULL and constraints

A `CHECK` fails only when its condition is false, so `NULL` passes. That's why a review can
have no rating even though `rating` must be between 1 and 5:

```sql
INSERT INTO reviews (book_id, customer_id, rating, body, reviewed_on)
VALUES (12, 6, NULL, 'Quietly devastating.', '2026-04-09');
SELECT id, rating, body FROM reviews WHERE book_id = 12;
```

```sql
-- Error: 6 isn't between 1 and 5, so the CHECK fails
INSERT INTO reviews (book_id, customer_id, rating, body, reviewed_on)
VALUES (12, 6, 6, 'Better than perfect.', '2026-04-09');
```

`UNIQUE` lets many rows hold `NULL`, because no two unknown values are known to be equal. Add
`NOT NULL` when a value must always be there.

## Putting it together

Average rating per genre, with the number of reviews and of ratings, and genres without any
ratings at the end:

```sql
SELECT b.genre,
       COUNT(r.id) AS reviews,
       COUNT(r.rating) AS ratings,
       ROUND(AVG(r.rating), 2) AS average
FROM books AS b
LEFT JOIN reviews AS r ON r.book_id = b.id
GROUP BY b.genre
ORDER BY average DESC NULLS LAST;
```

Next, try the quiz to check your understanding, then solve the challenges.
