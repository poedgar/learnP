## Every loan has a date

A loan starts on `borrowed_on`, is due back 21 days later on `due_on`, and ends on `returned_on`.
Dates answer most of the library's daily questions: what's overdue, who owes fines, how long people
keep books. Remember that today is **2026-10-21**; queries use that date rather than
`date('now')`, so the answers don't change from day to day.

## Overdue

A loan is overdue when it's still out and its due date has passed:

```sql
SELECT l.id, l.member_id, l.due_on,
       CAST(julianday('2026-10-21') - julianday(l.due_on) AS INTEGER) AS days_overdue
FROM loans AS l
WHERE l.returned_on IS NULL
  AND l.due_on < '2026-10-21'
ORDER BY days_overdue DESC;
```

Comparing the date strings works because they're all `YYYY-MM-DD`.

## Fines

Late returns cost 0.25 a day. For a loan that's been returned late, the days late are
`returned_on - due_on`:

```sql
SELECT l.id,
       julianday(l.returned_on) - julianday(l.due_on) AS days_late,
       (julianday(l.returned_on) - julianday(l.due_on)) * 0.25 AS fine
FROM loans AS l
WHERE l.returned_on > l.due_on
ORDER BY fine DESC;
```

Loans returned on time or early never appear (`returned_on > due_on` is false), and nor do open loans
(comparing with `NULL` isn't true). Fines on books that are **still out** keep growing: for those, use
today's date instead of the return date. `COALESCE(l.returned_on, '2026-10-21')` gives "the return
date, or today if it's not back yet", which lets one query handle both.

## Loans over time

How busy is the library month by month?

```sql
SELECT substr(borrowed_on, 1, 7) AS month, COUNT(*) AS loans
FROM loans
GROUP BY month
ORDER BY month;
```

## How long people keep books

For returned loans, the length in days is `returned_on - borrowed_on`. Averaged by genre, it says
something about the books: long novels stay out longer than crime stories:

```sql
SELECT ROUND(AVG(julianday(returned_on) - julianday(borrowed_on)), 1) AS average_days
FROM loans
WHERE returned_on IS NOT NULL;
```

Leave out loans still open: their length isn't known yet, and counting them up to today would make the
average look shorter than it really is.
