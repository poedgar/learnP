# SQL lessons 2–4: design

Date: 2026-10-08
Status: approved in conversation, awaiting review of this document

## Goal

Add the next three lessons to the SQL course, finishing the "reading data" half of SQL:

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 2 | Finding the rows you want | `finding-the-rows-you-want` | LIKE, IN, BETWEEN, NULL, COALESCE |
| 3 | Summarising data | `summarising-data` | aggregates, GROUP BY, HAVING |
| 4 | Combining tables | `combining-tables` | INNER JOIN, aliases, LEFT JOIN, JOIN + GROUP BY |

They are for the same beginners as lesson 1 (*Your first queries*), in the same voice. The web and mobile
apps already render whatever the API serves, so this is content plus seeding; no app code changes.

Lesson 1 does not change: its content, dataset, quiz answers and challenge IDs stay as they are.

## Shape of each lesson

Each lesson matches lesson 1:

- Markdown theory in `app/database/content/sql/0N-<slug>.md`, about 8 `## ` sections, each with at least
  one runnable `sql` code block (the "Try it in the sandbox" button runs it against the lesson's dataset).
- 8 quiz questions (4 options each, one correct, an explanation that teaches, `code` where it helps).
- 6 challenges, ordered easy to hard, each with a prompt, an optional starter, a hint, a reference
  `solution`, and `order_matters` set to true exactly when the prompt asks for an order.
- A short `starter_code` for the sandbox that runs and shows something relevant to the lesson.
- Each lesson only uses ideas it introduces or that earlier lessons taught. SQLite dialect throughout.

## Dataset: `bookshop`

A new file `app/database/datasets/bookshop.sql`, used by lessons 2–4. Lesson 1 keeps `bookstore`.

| Table | Rows | Columns |
|-------|------|---------|
| `books` | the same 16 as `bookstore` | identical schema and rows |
| `customers` | ~10 | `id`, `name`, `email`, `city` (nullable), `joined_on` (ISO date text) |
| `orders` | ~20 | `id`, `customer_id` → `customers.id`, `ordered_on` (ISO date text), `shipped_on` (nullable; NULL = not shipped) |
| `order_items` | ~40 | `order_id` → `orders.id`, `book_id` → `books.id`, `quantity`, `unit_price` |

Deliberate shape, because the lessons depend on it:

- 2 customers have never ordered (LEFT JOIN / "never ordered" challenge).
- A few books have never been sold ("never sold" challenge).
- A few orders have `shipped_on` NULL; at least 2 customers have `city` NULL.
- One customer's name contains an apostrophe (e.g. `Siobhan O'Brien`), for quoting in LIKE/`=`.
- Orders span at least three months of 2026, with several in March 2026 (BETWEEN on dates).
- At least one genre has more than 3 books and at least one has 3 or fewer (HAVING).
- `unit_price` sometimes differs from the book's current `price` (a sale), so "revenue" must use
  `order_items.unit_price`, which the lesson calls out.
- Foreign keys are declared, so the schema panel and the JOIN lesson can point at them.

## Lesson outlines

### Lesson 2: Finding the rows you want

Sections:
1. Matching patterns with LIKE: `%` and `_`.
2. LIKE ignores letter case in SQLite (for plain ASCII letters).
3. Checking against a list with IN and NOT IN.
4. Ranges with BETWEEN (both ends included), on numbers and ISO dates.
5. What NULL means: an unknown or missing value, not zero or ''.
6. Why `= NULL` never matches; IS NULL / IS NOT NULL.
7. Showing a fallback with COALESCE.
8. Putting it together.

The quiz covers at least: `NOT IN` with a NULL in the list returning no rows; doubling a quote inside a
string (`'O''Brien'`); BETWEEN including its ends; `_` matching exactly one character.

Challenges (final wording in the plan): titles containing "the" anywhere; books by any of a given set of
authors (IN); orders placed in March 2026; customers with no city shown as `Unknown`; orders not yet
shipped; a combined filter.

### Lesson 3: Summarising data

Sections:
1. Counting rows: COUNT(*).
2. COUNT(column) skips NULLs.
3. SUM, AVG, MIN, MAX, and ROUND.
4. COUNT(DISTINCT …).
5. Groups with GROUP BY.
6. Every selected column must be grouped or aggregated.
7. Filtering groups with HAVING (vs WHERE: before vs after grouping).
8. Sorting by an aggregate, putting it together.

Uses `books`, `orders` and `order_items` one table at a time; no joins yet.

Challenges: books per genre; average price per genre rounded to 2 places; genres with more than 3 books;
the total value of each order (`SUM(quantity * unit_price)`); the busiest order date; one combining WHERE,
GROUP BY, HAVING and ORDER BY.

### Lesson 4: Combining tables

Sections:
1. Why data is split across tables: primary and foreign keys.
2. INNER JOIN … ON.
3. Table aliases, and the "ambiguous column name" error.
4. Joining three or four tables.
5. LEFT JOIN keeps rows with no match.
6. Finding missing matches: LEFT JOIN … WHERE … IS NULL.
7. JOIN with GROUP BY.
8. Putting it together.

Challenges: each order with its customer's name; every book a given customer bought; customers who never
ordered; books that have never sold; revenue per customer, highest first; one combining a join, grouping
and HAVING.

## Seeding

`SqlCourseSeeder` seeds all four lessons. Lessons are upserted by slug and their quiz questions and
challenges by position (the existing `SyncsLessonContent`), so lesson 1's IDs, and learners' saved progress
keyed by them, are unchanged by re-seeding. Lesson 1's definition moves as-is; only its surroundings change.

## Correctness checks

- Every quiz answer that depends on data, and every theory example, is run against the real `bookshop`
  data through the SQL sandbox before it is written down. Row counts and values quoted in the text match.
- The existing `test_every_seeded_solution_passes_its_own_challenge` covers all 18 new challenges.
- New test: the SQL course seeds 4 lessons, in order, lessons 2–4 use `bookshop`, and each has 8 quiz
  questions and 6 challenges.
- New test: every `sql` code block in lessons 2–4 runs on `bookshop` without an error (catches typos in
  the theory).
- `CourseApiTest`'s `lessons_count` assertion for the SQL course changes from 1 to 4. Its test that creates
  a `filtering` lesson at position 2 is checked against the new seed data and adjusted if it now collides.

## Out of scope

Subqueries, INSERT/UPDATE/DELETE, CREATE TABLE, window functions, CASE (later lessons). App or API code
changes. Changes to lesson 1.
