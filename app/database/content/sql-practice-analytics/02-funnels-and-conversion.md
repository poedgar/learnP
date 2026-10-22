## The path to paying

Pagewise wants people to go through a series of steps: sign up, open a book, start a 14-day free
trial, and subscribe. That series is a **funnel**: each step loses some people, and the job of the
product team is to find where the biggest losses are and fix them. Every step is a row in `events`.

## Counting each step

The question is "how many different **users** reached each step?", so count distinct users, not
events (someone could view many books):

```sql
SELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'signup') AS signed_up,
       COUNT(DISTINCT user_id) FILTER (WHERE event = 'view_book') AS viewed,
       COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial') AS trialled,
       COUNT(DISTINCT user_id) FILTER (WHERE event = 'subscribe') AS subscribed
FROM events;
```

One row with one column per step reads like the funnel itself. `FILTER` works with `DISTINCT` too.

## Conversion rates

A conversion rate is the share of people at one step who reach the next. Put the step counts in a
CTE, then divide:

```sql
WITH steps AS (
    SELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'view_book') AS viewed,
           COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial') AS trialled
    FROM events
)
SELECT viewed, trialled, ROUND(100.0 * trialled / viewed, 1) AS view_to_trial_pct
FROM steps;
```

Comparing conversion **between groups** (referrers, countries, devices) is where funnels get useful:
a channel that brings many signups but few subscribers might not be worth the money.

## Who got stuck

To act on a funnel you want the people, not just the numbers. "Started a trial but never subscribed"
is a `NOT EXISTS`:

```sql
SELECT u.name
FROM users AS u
WHERE EXISTS (SELECT 1 FROM events AS e WHERE e.user_id = u.id AND e.event = 'start_trial')
  AND NOT EXISTS (SELECT 1 FROM events AS e WHERE e.user_id = u.id AND e.event = 'subscribe');
```

## Time between steps

How long do people take to get from one step to the next? Find each user's first time at each step,
then subtract. `julianday()` turns a date or timestamp into a number of days, so the difference is in
days (with a fraction for the hours):

```sql
WITH firsts AS (
    SELECT user_id,
           MIN(happened_at) FILTER (WHERE event = 'signup') AS signed_up_at,
           MIN(happened_at) FILTER (WHERE event = 'start_trial') AS trial_at
    FROM events
    GROUP BY user_id
)
SELECT ROUND(AVG(julianday(trial_at) - julianday(signed_up_at)), 1) AS avg_days_to_trial
FROM firsts
WHERE trial_at IS NOT NULL;
```

`AVG` skips `NULL`s anyway, but saying `WHERE trial_at IS NOT NULL` makes the intent clear. Use
`CAST(... AS INTEGER)` to cut the fraction off, or compare `date(...)` parts for whole calendar days.

## Too early to tell

A trial lasts 14 days, so someone who started a trial last week **can't** have subscribed yet. Counting
them as "didn't convert" makes the newest groups look worse than they are. Either leave out people who
haven't had the full 14 days, or report them separately. With today as 2026-07-01:

```sql
SELECT COUNT(*) AS trials_still_running
FROM events
WHERE event = 'start_trial'
  AND happened_at >= date('2026-07-01', '-14 days');
```

`date('2026-07-01', '-14 days')` is `2026-06-17`. Comparing a timestamp text with a date text works,
because `'2026-06-20 09:00:00'` sorts after `'2026-06-17'`.
