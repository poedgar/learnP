# SQL lessons 8–10: design

Date: 2026-10-08
Status: approved in conversation, awaiting review of this document

## Goal

An intermediate section after the beginner course (lessons 1–7):

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 8 | Shaping values | `shaping-values` | CASE; text, number and date functions; CAST, NULLIF |
| 9 | Naming steps with WITH | `naming-steps-with-with` | common table expressions, including recursive ones |
| 10 | Window functions | `window-functions` | OVER, PARTITION BY, ROW_NUMBER/RANK/DENSE_RANK, running totals, LAG/LEAD, frames |

Same audience, voice and shape as lessons 1–7, on the unchanged `bookshop` dataset. All read-only: no
`check_sql`, no backend, web or mobile changes. Built on top of lessons 5–7 (branch
`feature/sql-lessons-5-7`).

Checked in the sandbox's SQLite 3.53 before writing this: window functions (including frames over grouped
results), `LAG`, recursive CTEs, `date()`/`strftime()`/`julianday()`, integer division and `NULLIF` all
work; a runaway recursive CTE is stopped after 3 seconds with the sandbox's existing message.

## Shape of each lesson

As lessons 2–7: Markdown theory (about 8 `## ` sections, runnable ` ```sql ` blocks; blocks starting
`-- Error:` must fail) and a PHP definition with title, summary, starter code, 8 quiz questions (4 options)
and 6 challenges, in `app/database/content/sql/`, listed in `SqlCourseSeeder::BOOKSHOP_LESSONS`.

Each lesson only uses what it or earlier lessons teach. No challenge's solution appears in its lesson's
quiz or verbatim in its theory. `order_matters` challenges name a tie-breaker wherever the data has ties
(important for RANK and ROW_NUMBER, where ties are the point).

## Lesson outlines

### Lesson 8: Shaping values

Sections: CASE in SELECT (price bands); CASE inside aggregates (`SUM(CASE WHEN … THEN 1 ELSE 0 END)`);
text functions (`UPPER`, `LENGTH`, `SUBSTR`, `REPLACE`, `TRIM`, `INSTR`, `||`); numbers (`ROUND`, `ABS`,
integer division: `7 / 2` is 3, `7 / 2.0` is 3.5); dates as text (`date()`, `strftime()`, `'+7 days'`);
days between dates (`julianday()`); `CAST` and `NULLIF` for safe division; putting it together.

Challenges: price bands; each order's month; days each order took to ship, or `not shipped`; each
customer's email domain; shipped and unshipped orders counted side by side in one row; a small monthly
report.

### Lesson 9: Naming steps with WITH

Sections: why (subqueries in FROM get hard to read); one named step; several steps, each using the one
before; using a step twice (replacing lesson 5's repeated subquery in *Above-average spenders*);
recursion, counting 1 to 10; a calendar of every day in March; joining orders onto the calendar to find
days with no orders; stopping conditions, and what happens without one.

Challenges: customer totals, then those above the average; monthly revenue; every day in April with its
number of orders, zeros included; the first 10 even numbers; the best-selling genre per month; a report in
several steps.

### Lesson 10: Window functions

Sections: a window keeps every row (each book next to its genre's average), unlike GROUP BY;
`PARTITION BY`; `ROW_NUMBER`, `RANK`, `DENSE_RANK` and ties; top N per group (a window inside WITH);
running totals with `ORDER BY` in the window; `LAG` and `LEAD` (days since a customer's previous order);
window frames, briefly (a 3-order moving average); putting it together.

Challenges: each book's price against its genre's average; each customer's orders numbered, oldest first;
the 2 most expensive books in each genre; running revenue by date; days between a customer's orders; a
ranking report.

## Correctness checks

- Every query, quoted number and claim is run through the real sandbox while writing the plan.
- Existing tests extend automatically: lesson order and counts, quiz validity, theory examples (the
  runaway-recursion example is an `-- Error:` block and must be stopped), no quiz giving away a
  challenge, every solution passing its own challenge.
- `CourseApiTest` `lessons_count` 7 → 10, navigation ends at `window-functions`.

## Out of scope

Indexes and query plans, views, triggers, JSON functions, string aggregation beyond what lessons need,
changes to lessons 1–7, any backend or app changes.
