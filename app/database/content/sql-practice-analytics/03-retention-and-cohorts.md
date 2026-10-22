## Do people come back?

Signups are easy to buy with ads; what makes an app grow is people **coming back**. Retention
measures that: of the people who started in a given period, how many are still around later? This
lesson answers it with the `sessions` table.

## Cohorts

A **cohort** is a group of users who started at the same time, usually the same signup month. Comparing
cohorts shows whether the product is getting better at keeping people: if the April cohort keeps more
of its users than the January one did, something worked.

```sql
SELECT substr(signed_up_on, 1, 7) AS cohort, COUNT(*) AS users
FROM users
GROUP BY cohort
ORDER BY cohort;
```

## Months since signup

To line cohorts up, measure activity in "months since signup" instead of calendar months: month 0 is
the signup month, month 1 the one after, and so on. Turn each date into a month number
(`year * 12 + month`) and subtract:

```sql
SELECT u.name,
       u.signed_up_on,
       s.started_at,
       (CAST(strftime('%Y', s.started_at) AS INTEGER) * 12 + CAST(strftime('%m', s.started_at) AS INTEGER))
     - (CAST(strftime('%Y', u.signed_up_on) AS INTEGER) * 12 + CAST(strftime('%m', u.signed_up_on) AS INTEGER)) AS month_number
FROM users AS u
JOIN sessions AS s ON s.user_id = u.id
WHERE u.id = 2
ORDER BY s.started_at;
```

## A retention table

With the month number in a CTE, a retention table is one `COUNT(DISTINCT ...) FILTER` per month:

```sql
WITH activity AS (
    SELECT u.id,
           substr(u.signed_up_on, 1, 7) AS cohort,
           (CAST(strftime('%Y', s.started_at) AS INTEGER) * 12 + CAST(strftime('%m', s.started_at) AS INTEGER))
         - (CAST(strftime('%Y', u.signed_up_on) AS INTEGER) * 12 + CAST(strftime('%m', u.signed_up_on) AS INTEGER)) AS month_number
    FROM users AS u
    JOIN sessions AS s ON s.user_id = u.id
)
SELECT cohort,
       COUNT(DISTINCT id) FILTER (WHERE month_number = 0) AS m0,
       COUNT(DISTINCT id) FILTER (WHERE month_number = 1) AS m1,
       COUNT(DISTINCT id) FILTER (WHERE month_number = 2) AS m2,
       COUNT(DISTINCT id) FILTER (WHERE month_number = 3) AS m3
FROM activity
GROUP BY cohort
ORDER BY cohort;
```

Read it along a row: the March cohort had 6 users active in month 0 and 5 in month 1. The later
cohorts have zeros on the right simply because those months haven't happened yet (the data ends in
June). As with trials, don't mistake "not yet" for "lost".

## Last seen

A simpler, very practical measure: when did each user last show up? Someone not seen for a while is
**at risk**, and maybe worth a friendly email.

```sql
SELECT u.name, MAX(s.started_at) AS last_seen
FROM users AS u
JOIN sessions AS s ON s.user_id = u.id
GROUP BY u.id
HAVING MAX(s.started_at) < '2026-05-01'
ORDER BY last_seen;
```

## Churn

For a subscription business, **churn** is paying customers who leave. In Pagewise that's a `cancel`
event after a `subscribe`. The churn rate for a period is the people who cancelled divided by the people
who were subscribed at the start of it. Small numbers make rates jumpy, so always show the counts
next to any percentage.

```sql
SELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'subscribe') AS ever_subscribed,
       COUNT(DISTINCT user_id) FILTER (WHERE event = 'cancel') AS cancelled
FROM events;
```
