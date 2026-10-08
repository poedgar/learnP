# SQL lessons 5–7: design

Date: 2026-10-08
Status: approved in conversation, awaiting review of this document

## Goal

Finish the beginner SQL course with three lessons after *Combining tables*:

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 5 | Queries inside queries | `queries-inside-queries` | subqueries: scalar, IN, EXISTS, correlated, in SELECT and FROM |
| 6 | Changing data | `changing-data` | INSERT, UPDATE, DELETE, transactions |
| 7 | Designing tables | `designing-tables` | CREATE TABLE, keys, NOT NULL, DEFAULT, UNIQUE, CHECK, FOREIGN KEY, ALTER/DROP |

Same audience, voice and shape as lessons 1–4. Lessons 5–7 use the `bookshop` dataset unchanged; every
sandbox run starts from a fresh copy, so learners can change and drop data freely.

Lessons 6 and 7 need challenges that check what the learner's SQL *did* to the database, not what it
selected. That needs one backend change: a follow-up check query (below). No web or mobile changes.

## Follow-up check queries

- New nullable text column `check_sql` on `challenges` (migration; fillable; hidden like `solution`;
  never in `ChallengeResource`).
- `SqlSandbox::check()` takes an optional `?string $checkSql`. When set, it runs
  `learner SQL` + `"\n;\n"` + `checkSql`, and `solution` + `"\n;\n"` + `checkSql`, then compares the last
  result set of each exactly as today (same messages, same `order_matters` rule). The statement splitter
  already drops empty statements, so a trailing `;` in the learner's SQL is harmless.
- `ChallengeCheckController` passes `$challenge->check_sql`.
- When `check_sql` is null, behaviour is identical to today (all existing challenges).
- If the learner's SQL errors, they get today's "Your SQL has an error" message; the run shows which
  statement failed.
- The response's `run` is the learner's SQL plus the check, so "Your result" in the web and mobile apps
  shows the check query's output next to "Expected result".
- Lesson 7 checks constraints by behaviour: a check query may `INSERT OR IGNORE` a row the constraint must
  reject, then `SELECT` from the table. A table without the constraint keeps the bad row, so the result
  differs. Column shape is checked with the allowed `PRAGMA table_info(...)` / `foreign_key_list(...)`.

## Shape of each lesson

As lessons 2–4: Markdown theory (about 8 `## ` sections, runnable ` ```sql ` blocks; blocks starting
`-- Error:` must fail) plus a PHP definition file with title, summary, starter code, 8 quiz questions
(4 options) and 6 challenges, in `app/database/content/sql/`, loaded by `SqlCourseSeeder`'s
`BOOKSHOP_LESSONS`. Challenge definitions gain an optional `check_sql` key.

Each lesson only uses what it or earlier lessons teach. No challenge's solution appears verbatim in its
lesson's theory or quiz. `order_matters` challenges name a tie-breaker wherever the data has ties.

## Lesson outlines

### Lesson 5: Queries inside queries

Sections: a subquery that returns one value (books above the average price); a subquery that returns a list
(IN); `NOT IN (subquery)` and NULLs (callback to lesson 2), prefer `NOT EXISTS`; EXISTS and NOT EXISTS;
correlated subqueries (above their genre's average); a subquery in SELECT, compared with a JOIN; subqueries
in FROM; choosing between a join and a subquery.

Challenges: books cheaper than the average; customers who ordered in April 2026; books never ordered with
NOT EXISTS; the most expensive book in each genre; customers whose average order value is above a threshold
(subquery in FROM); one combining techniques.

### Lesson 6: Changing data

Sections: the sandbox resets after every run; INSERT one row and several rows; INSERT … SELECT; UPDATE … SET
… WHERE and the missing-WHERE accident; UPDATE with calculations and subqueries; DELETE, and a foreign key
refusing to delete a customer who has orders; "run the SELECT first"; transactions (BEGIN, COMMIT,
ROLLBACK).

Every challenge has `check_sql`. Challenges: add a new book; add a customer with no city; mark order 17 as
shipped; take 10% off every horror book; delete the books that are out of stock and have never sold; one
combining several changes.

### Lesson 7: Designing tables

Sections: CREATE TABLE, columns and SQLite's types; PRIMARY KEY and `INTEGER PRIMARY KEY`; NOT NULL and
DEFAULT; UNIQUE; CHECK; FOREIGN KEY … REFERENCES (enforced in this sandbox); ALTER TABLE and DROP TABLE;
putting it together: a `reviews` table.

Every challenge has `check_sql`, checking columns and that the constraints reject bad rows. Challenges:
an `authors` table; a newsletter sign-up table with a unique email; a `reviews` table whose rating must be
1–5; reviews linked to books; adding a column to `customers`; a complete table design.

## Correctness checks

- Every query, quoted number and claim is run against the real `bookshop` data in SQLite 3.53 (the
  sandbox's version) while writing the plan.
- Existing tests extend automatically: lesson order and counts, quiz validity, theory examples, no quiz
  giving away a challenge, every solution passing its own challenge (now including `check_sql`).
- New `SqlSandbox`/API tests: with `check_sql`, the right change passes, a wrong change fails, a SELECT
  that changes nothing fails, and a challenge without `check_sql` behaves as before.
- New content test: for every challenge with `check_sql`, running the check on the untouched dataset gives
  a different result from the solution's. So any answer that changes nothing fails.
- `CourseApiTest` `lessons_count` 4 → 7 and navigation ends at `designing-tables`.

## Out of scope

CASE and built-in text/date functions, CTEs (WITH), window functions, indexes and performance, views,
triggers. Changes to lessons 1–4 or to the web/mobile apps.
