## Rows that point at rows

Some data forms a tree. Every member of staff has a manager, who is also a member of staff,
and every category can sit inside a parent category. Both tables store the tree the same way:
each row holds the `id` of the row above it.

```sql
SELECT id, name, parent_id
FROM categories;
```

The rows with no parent, `Fiction`, `Classics` and `Non-fiction`, are the **roots**. Rows that
nothing points at, such as `Epic Fantasy`, are the **leaves**.

## One level at a time with self-joins

Each extra level up the tree is one more self-join. Every member of staff with their manager
and their manager's manager:

```sql
SELECT s.name, m.name AS manager, mm.name AS managers_manager
FROM staff AS s
LEFT JOIN staff AS m ON m.id = s.manager_id
LEFT JOIN staff AS mm ON mm.id = m.manager_id;
```

`LEFT JOIN` keeps the people near the top, who have fewer levels above them. This works when
you know how many levels you need, but trees can be any depth.

## Counting direct reports

Join the other way, from a manager down to the people who report to them, and group:

```sql
SELECT m.name, COUNT(s.id) AS direct_reports
FROM staff AS m
LEFT JOIN staff AS s ON s.manager_id = m.id
GROUP BY m.id
ORDER BY direct_reports DESC, m.name;
```

The leaves are the people nobody reports to:

```sql
SELECT name
FROM staff AS s
WHERE NOT EXISTS (SELECT 1 FROM staff AS r WHERE r.manager_id = s.id);
```

## Walking down the tree with WITH RECURSIVE

A recursive `WITH` (from lesson 9 of the SQL course) can follow the tree to any depth. Start
with one person, then repeatedly add everyone who reports to someone already found. Priya's
whole team, with how far each person is below her:

```sql
WITH RECURSIVE team (id, name, depth) AS (
    SELECT id, name, 0 FROM staff WHERE name = 'Priya Shah'
    UNION ALL
    SELECT s.id, s.name, t.depth + 1
    FROM staff AS s
    JOIN team AS t ON s.manager_id = t.id
)
SELECT name, depth FROM team;
```

The first `SELECT` is the starting point. The second runs again and again, each time on the
rows found by the previous round, until a round finds nobody new.

## Walking up: the chain of command

To go up instead, swap the join: find the row whose `id` is the current row's `manager_id`.
Everyone above Sam Doyle:

```sql
WITH RECURSIVE chain (id, name, manager_id, steps) AS (
    SELECT id, name, manager_id, 0 FROM staff WHERE name = 'Sam Doyle'
    UNION ALL
    SELECT s.id, s.name, s.manager_id, c.steps + 1
    FROM staff AS s
    JOIN chain AS c ON s.id = c.manager_id
)
SELECT name, steps FROM chain;
```

Going up is simpler than going down, because each row has exactly one parent. The recursion
stops at Margaret, whose `manager_id` is `NULL` and so matches nobody.

## Paths and indentation

Carry a growing piece of text through the recursion to build each category's full path, and
use the depth to indent it:

```sql
WITH RECURSIVE tree (id, name, depth, path) AS (
    SELECT id, name, 0, name FROM categories WHERE parent_id IS NULL
    UNION ALL
    SELECT c.id, c.name, t.depth + 1, t.path || ' > ' || c.name
    FROM categories AS c
    JOIN tree AS t ON c.parent_id = t.id
)
SELECT SUBSTR('            ', 1, depth * 4) || name AS category, path
FROM tree
ORDER BY path;
```

Sorting by the path puts every category directly under its parent, like folders in a file
browser.

## Totals for a whole subtree

How many books are in `Fantasy`, counting its subcategories too? Pair every category with
itself and with everything below it, remembering where each walk started in `root_id`:

```sql
WITH RECURSIVE below (root_id, id) AS (
    SELECT id, id FROM categories
    UNION ALL
    SELECT b.root_id, c.id
    FROM categories AS c
    JOIN below AS b ON c.parent_id = b.id
)
SELECT cat.name, COUNT(DISTINCT bc.book_id) AS books
FROM categories AS cat
JOIN below ON below.root_id = cat.id
LEFT JOIN book_categories AS bc ON bc.category_id = below.id
GROUP BY cat.id;
```

`Fiction` counts all 16 books, because every genre sits inside it. `COUNT(DISTINCT …)` matters
when a book appears in more than one category of the same subtree.

## Guarding against loops

If someone set the owner's manager to a packer, the tree would contain a loop, and walking up
would never end. A step limit is a cheap safety net:

```sql
UPDATE staff SET manager_id = 8 WHERE id = 1;

WITH RECURSIVE chain (id, name, manager_id, steps) AS (
    SELECT id, name, manager_id, 0 FROM staff WHERE name = 'Sam Doyle'
    UNION ALL
    SELECT s.id, s.name, s.manager_id, c.steps + 1
    FROM staff AS s
    JOIN chain AS c ON s.id = c.manager_id
    WHERE c.steps < 10
)
SELECT name, steps FROM chain;
```

The chain goes round the loop until it reaches 10 steps. Without the limit, the sandbox would
stop the query after a few seconds.

## Putting it together

Every leaf category with its full path and its number of books:

```sql
WITH RECURSIVE tree (id, path) AS (
    SELECT id, name FROM categories WHERE parent_id IS NULL
    UNION ALL
    SELECT c.id, t.path || ' > ' || c.name
    FROM categories AS c
    JOIN tree AS t ON c.parent_id = t.id
)
SELECT t.path, COUNT(bc.book_id) AS books
FROM tree AS t
LEFT JOIN book_categories AS bc ON bc.category_id = t.id
WHERE NOT EXISTS (SELECT 1 FROM categories AS child WHERE child.parent_id = t.id)
GROUP BY t.id
ORDER BY t.path;
```

Next, try the quiz to check your understanding, then solve the challenges.
