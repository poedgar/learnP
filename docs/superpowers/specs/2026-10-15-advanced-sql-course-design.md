# Advanced SQL course: design

Date: 2026-10-15
Status: built in conversation

## Goal

A third course, **Advanced SQL** (`advanced-sql`, position 3), for learners who finished the SQL
course. It covers the topics the SQL course left out; CASE, text and date functions, recursive CTEs
and window functions are reused rather than retaught.

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 1 | Stacking and pairing | `stacking-and-pairing` | UNION / UNION ALL / INTERSECT / EXCEPT, self-joins, CROSS, RIGHT and FULL OUTER JOIN |
| 2 | The truth about NULL | `the-truth-about-null` | three-valued logic, NOT, IS / IS DISTINCT FROM, NULL in aggregates, sorting, joins and constraints |
| 3 | Trees and hierarchies | `trees-and-hierarchies` | self-joins per level, recursive walks down and up, paths, subtree totals, loop guards |
| 4 | Everyday query patterns | `everyday-query-patterns` | pivot (SUM(CASE), FILTER), unpivot, duplicates, latest row per group, gaps, islands |
| 5 | Upsert and RETURNING | `upsert-and-returning` | OR IGNORE, OR REPLACE (and its trap), ON CONFLICT DO NOTHING / DO UPDATE / WHERE, INSERT … SELECT WHERE true, RETURNING, UPDATE … FROM |
| 6 | Working with JSON | `working-with-json` | -> and ->>, paths, arrays, json_each, json_object / json_array, json_group_array, json_set |
| 7 | Designing a schema | `designing-a-schema` | normal forms in plain words, splitting a table, junction tables, composite keys, ON DELETE, denormalising |
| 8 | Indexes and query plans | `indexes-and-query-plans` | EXPLAIN QUERY PLAN, single/multi-column, covering, unique and expression indexes, when indexes can't help |
| 9 | Views, triggers and generated columns | `views-triggers-and-generated-columns` | views, triggers with OLD/NEW/WHEN, RAISE, VIRTUAL and STORED generated columns |
| 10 | Full-text search | `full-text-search` | FTS5: MATCH, phrases, prefixes, operators, column filters, rank, highlight/snippet, porter, triggers |

Same shape as the SQL course: Markdown theory with runnable ` ```sql ` blocks (`-- Error:` blocks must
fail), 8 quiz questions and 6 challenges per lesson, in `app/database/content/advanced-sql/`, seeded by
`AdvancedSqlCourseSeeder`. Challenges that change data or create objects use `check_sql`; table and
index checks read `pragma_table_info`, `pragma_foreign_key_list`, `pragma_index_list` and
`pragma_index_info`, so learners can name things as they like.

## Dataset: bookshop_plus

`database/datasets/bookshop_plus.sql`: the SQL course's `books`, `customers`, `orders` and
`order_items`, unchanged (enforced by `BookshopPlusDatasetTest`), plus:

- `staff` (manager tree; Lucy earns more than her manager; one unknown city) and `categories` with
  `book_categories` (a tree and a many-to-many relationship; an empty Non-fiction branch);
- `wishlist` (set operations), `reviews` (NULL ratings, two double-submitted reviews, text to search),
  `visits` (streaks), `events` (JSON payloads), `supplier_prices` (a raw feed with duplicates and price
  history, no id), `sales_targets` (one column per month, to unpivot), `stock_levels` (upserts) and
  `paper_orders` (a deliberately badly designed table).

## Sandbox changes

- The authorizer allows `PRAGMA data_version`, which FTS5 runs internally whenever it writes; without it,
  learners could create a full-text table but not fill it. Learners still can't type it themselves.
- `PRAGMA index_info` and `index_xinfo` join the read-only PRAGMAs, for the indexes lesson and its checks.

## Tests

- `SqlLessonContentTest` covers both SQL courses (lesson order and datasets, 8 + 6 per lesson, quiz
  validity, every theory example, no quiz giving away a challenge, follow-up checks).
- `ChallengeCheckApiTest::test_every_seeded_solution_passes_its_own_challenge` turns off the sandbox's
  per-minute rate limit: there are now more SQL challenges than the limit allows.
- `CourseApiTest` lists the new course and its navigation; `SandboxApiTest` covers FTS5 and the new PRAGMAs.

## Out of scope

The practice courses (analytics, e-commerce, library/school, interview questions), PostgreSQL-only
features, and any web or mobile changes: both apps list courses from the API.
