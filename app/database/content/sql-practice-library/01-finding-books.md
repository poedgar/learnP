## Meet the library

This course is practice on a town library with three branches: Central, Riverside and Hillcrest. A
**book** is a title in the catalogue; the library owns one or more **copies** of it, each kept at a
branch. Members borrow copies for 21 days, and late returns are fined 0.25 a day. Take 2026-10-21 as
today.

| Table | One row per |
|---|---|
| `branches` | branch: `name`, `address` |
| `authors` | author: `name`, year `born` |
| `books` | title: `author_id`, `genre`, `published_year` |
| `copies` | physical copy: `book_id`, `branch_id`, `acquired_on` |
| `members` | member: `name`, home `branch_id`, `joined_on` |
| `loans` | loan of a copy: `member_id`, `borrowed_on`, `due_on`, `returned_on` (`NULL` while it's still out) |
| `reservations` | request to borrow a book when one is free: `reserved_on`, `fulfilled_on` (`NULL` while waiting) |

The difference between a book and a copy is the heart of this data: "is *Dune* available?" means "is
any **copy** of it not on loan?".

## The catalogue

Books with their authors are a simple join:

```sql
SELECT b.title, a.name AS author, b.genre, b.published_year
FROM books AS b
JOIN authors AS a ON a.id = b.author_id
ORDER BY a.name, b.published_year;
```

## Copies and branches

How many copies of each book are there, and where?

```sql
SELECT b.title, br.name AS branch, COUNT(*) AS copies
FROM copies AS c
JOIN books AS b ON b.id = c.book_id
JOIN branches AS br ON br.id = c.branch_id
GROUP BY b.id, br.id
ORDER BY b.title, branch;
```

## On loan right now

A copy is out if it has a loan with no `returned_on`. Those are the loans still open:

```sql
SELECT b.title, br.name AS branch, l.borrowed_on, l.due_on
FROM loans AS l
JOIN copies AS c ON c.id = l.copy_id
JOIN books AS b ON b.id = c.book_id
JOIN branches AS br ON br.id = c.branch_id
WHERE l.returned_on IS NULL
ORDER BY l.due_on;
```

A copy is **available** when no such open loan exists for it: a `NOT EXISTS` on the copy, or a
`LEFT JOIN` to open loans and a check for `NULL`. Counting available copies per book takes one more
step: count the copies, and subtract (or filter out) the ones on loan.

## Searching

`LIKE` with `%` finds titles containing a word. In SQLite, `LIKE` ignores case for plain ASCII letters,
so `'%the%'` also matches "The" and "Then":

```sql
SELECT title
FROM books
WHERE title LIKE '%sun%' OR title LIKE '%ocean%';
```

Searching for a short word inside text matches it inside longer words too ("the" is in "There").
Proper word searching needs full-text search (covered in Advanced SQL).
