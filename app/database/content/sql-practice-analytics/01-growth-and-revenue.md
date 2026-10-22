## Meet Pagewise

This course is practice: no new syntax, just realistic questions answered with the SQL you know from
the SQL and Advanced SQL courses. The data belongs to **Pagewise**, a reading app with a free plan and
a paid **Pro** plan at 7.99 a month. It covers January to June 2026 (take 2026-07-01 as today):

| Table | One row per |
|---|---|
| `users` | person who signed up: `signed_up_on`, `country`, `referrer` (how they found us, `NULL` if unknown), current `plan` |
| `events` | thing a user did: `signup`, `view_book`, `start_trial`, `subscribe`, `cancel`, with `happened_at` |
| `sessions` | visit to the app: `started_at`, `duration_seconds`, `device` |
| `payments` | monthly Pro payment: `paid_on`, `amount_cents`, `status` (`paid` or `refunded`) |

Dates are stored as text in `YYYY-MM-DD` (and `YYYY-MM-DD HH:MM:SS`) form, so they sort correctly and
`substr(date, 1, 7)` gives the month. Money is in **cents**, so it adds up exactly.

## Signups per month

The first question any product team asks: how many people are joining?

```sql
SELECT substr(signed_up_on, 1, 7) AS month, COUNT(*) AS signups
FROM users
GROUP BY month
ORDER BY month;
```

`strftime('%Y-%m', signed_up_on)` does the same and also works on full timestamps. A month with no
signups would simply be missing from this result: worth remembering when you chart it.

## Running totals

How many users do we have *in total* by the end of each month? That's a running total: a window
`SUM` over the monthly counts, ordered by month.

```sql
SELECT substr(signed_up_on, 1, 7) AS month,
       COUNT(*) AS signups,
       SUM(COUNT(*)) OVER (ORDER BY substr(signed_up_on, 1, 7)) AS total_users
FROM users
GROUP BY month;
```

The window runs after `GROUP BY`, so it can add up the groups' `COUNT(*)`s.

## Month over month

Growth is about change. `LAG()` reads the previous row's value, so you can compare each month with
the one before:

```sql
WITH monthly AS (
    SELECT substr(signed_up_on, 1, 7) AS month, COUNT(*) AS signups
    FROM users
    GROUP BY month
)
SELECT month,
       signups,
       LAG(signups) OVER (ORDER BY month) AS previous,
       ROUND(100.0 * (signups - LAG(signups) OVER (ORDER BY month)) / LAG(signups) OVER (ORDER BY month), 1) AS growth_pct
FROM monthly;
```

The first month has no previous month, so `LAG()` gives `NULL`, and so does anything calculated from
it. Multiplying by `100.0` (not `100`) keeps the division from being an integer division.

## Revenue

Only `paid` payments are revenue; a refunded payment was given back. Divide cents by `100.0` at the
very end, to show money in whole units:

```sql
SELECT substr(paid_on, 1, 7) AS month,
       COUNT(*) AS payments,
       SUM(amount_cents) / 100.0 AS revenue
FROM payments
WHERE status = 'paid'
GROUP BY month
ORDER BY month;
```

With the window functions from above you can turn this into cumulative revenue, or each month's share
of the half-year total with `SUM(...) OVER ()`, a window over every row.

## Shares of a whole

"What share of our users came from each referrer?" needs each group's count *and* the overall count.
An empty window, `OVER ()`, gives the total across all groups:

```sql
SELECT COALESCE(referrer, 'unknown') AS referrer,
       COUNT(*) AS users,
       ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (), 1) AS pct
FROM users
GROUP BY referrer
ORDER BY users DESC;
```

`COALESCE` gives the `NULL` group a readable name. The percentages should add up to 100, give or take
rounding: a quick sanity check worth doing on any report.
