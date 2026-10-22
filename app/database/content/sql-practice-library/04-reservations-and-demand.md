## When every copy is out

If all copies of a book are on loan, a member can **reserve** it: they join a waiting list, and the
next copy that comes back is held for the first person in line. A reservation is waiting while its
`fulfilled_on` is `NULL`. Reservations, together with loans, show the library what people want and
whether it owns enough copies.

## The waiting list

Who is waiting for what, in order? `ROW_NUMBER()`, partitioned by book and ordered by when people
reserved, gives each person their place in the queue:

```sql
SELECT r.book_id,
       r.member_id,
       r.reserved_on,
       ROW_NUMBER() OVER (PARTITION BY r.book_id ORDER BY r.reserved_on) AS position
FROM reservations AS r
WHERE r.fulfilled_on IS NULL
ORDER BY r.book_id, position;
```

Here `ROW_NUMBER()` is right, not `RANK()`: two people can't share a place in a queue (if they reserved
on the same day, add the reservation `id` to the `ORDER BY` to break the tie).

## How long people wait

For fulfilled reservations, the wait is `fulfilled_on - reserved_on`:

```sql
SELECT r.id, r.reserved_on, r.fulfilled_on,
       julianday(r.fulfilled_on) - julianday(r.reserved_on) AS days_waited
FROM reservations AS r
WHERE r.fulfilled_on IS NOT NULL;
```

## When will a copy come back?

For a book everyone is waiting for, the earliest due date among its open loans is the best guess for
when the first person in the queue will get it (if the borrower is on time!):

```sql
SELECT c.book_id, MIN(l.due_on) AS next_due
FROM loans AS l
JOIN copies AS c ON c.id = l.copy_id
WHERE l.returned_on IS NULL
GROUP BY c.book_id;
```

## Demand per copy

Raw loan counts favour books the library has many copies of. Dividing by the number of copies shows
which titles are really in demand, and so where buying another copy would help most:

```sql
SELECT c.book_id,
       COUNT(DISTINCT c.id) AS copies,
       COUNT(l.id) AS loans
FROM copies AS c
LEFT JOIN loans AS l ON l.copy_id = c.id
GROUP BY c.book_id
ORDER BY loans DESC;
```

Note `COUNT(DISTINCT c.id)`: joining loans repeats each copy once per loan, so a plain `COUNT(c.id)`
would count loans, not copies. Divide with `1.0 *` in front to avoid integer division.

## Copies on the shelf for nothing

The other side of demand: copies that have never been borrowed take up shelf space. They might be
better moved to another branch, or replaced with something people want.
