## How people use the app

Retention says *whether* people come back; engagement says *how* they use the app when they do: how
long, how often, on what, and when. The `sessions` table has one row per visit, with `started_at`,
`duration_seconds` and `device`.

## Averages, with care

Average session length by device:

```sql
SELECT device,
       COUNT(*) AS sessions,
       ROUND(AVG(duration_seconds) / 60.0, 1) AS avg_minutes
FROM sessions
GROUP BY device
ORDER BY sessions DESC;
```

Averages are easily pulled about by a few unusual values: one session left open overnight can double
an average. The **median** (the middle value) is often a fairer "typical" number.

## The median

SQLite has no `MEDIAN()`, but window functions get you there: number the rows in order, then pick the
middle one (or the two middle ones, for an even count, and average them):

```sql
WITH ordered AS (
    SELECT duration_seconds,
           ROW_NUMBER() OVER (ORDER BY duration_seconds) AS position,
           COUNT(*) OVER () AS total
    FROM sessions
)
SELECT ROUND(AVG(duration_seconds) / 60.0, 1) AS median_minutes
FROM ordered
WHERE position IN ((total + 1) / 2, (total + 2) / 2);
```

With an odd `total`, both expressions give the same middle position; with an even one, they give the
two middle positions. (Integer division rounds down.)

## Top users

"Who are our most engaged users?" is a ranking. `DENSE_RANK()` gives users with equal counts the same
rank, so a "top 3" includes everyone tied for third:

```sql
WITH counts AS (
    SELECT user_id, COUNT(*) AS sessions,
           DENSE_RANK() OVER (ORDER BY COUNT(*) DESC) AS rank
    FROM sessions
    GROUP BY user_id
)
SELECT u.name, c.sessions, c.rank
FROM counts AS c
JOIN users AS u ON u.id = c.user_id
WHERE c.rank <= 2
ORDER BY c.rank;
```

## When people visit

Time of day matters for when to send notifications, or to schedule maintenance. Pull the hour out
with `strftime('%H', ...)` and put it in buckets with `CASE`:

```sql
SELECT strftime('%H', started_at) AS hour, COUNT(*) AS sessions
FROM sessions
GROUP BY hour
ORDER BY sessions DESC
LIMIT 5;
```

Days work the same way: `date(started_at)` for the calendar day, `strftime('%w', ...)` for the day of
the week (0 is Sunday).

## Streaks

A **streak** is a run of consecutive days with a visit. It's the gaps-and-islands trick from Advanced
SQL: for each user's distinct visit days in order, the day number minus the row number stays the same
along a run, so it labels each island:

```sql
WITH days AS (
    SELECT DISTINCT user_id, date(started_at) AS day
    FROM sessions
),
islands AS (
    SELECT user_id, day,
           julianday(day) - ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY day) AS island
    FROM days
)
SELECT user_id, MIN(day) AS streak_start, COUNT(*) AS days
FROM islands
GROUP BY user_id, island
HAVING COUNT(*) > 1;
```

The `DISTINCT` matters: two visits on the same day would otherwise break the "one row per day" rhythm
the trick depends on.
