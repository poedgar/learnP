## Who uses the library

A library is judged by how much it's used. Member queries find the keen readers, the people who
signed up and never came back, and what each person likes to read, which is useful for
recommendations and for deciding what to buy.

## Loans per member

A `LEFT JOIN` keeps members with no loans, and `COUNT(l.id)` gives them 0:

```sql
SELECT m.name, COUNT(l.id) AS loans
FROM members AS m
LEFT JOIN loans AS l ON l.member_id = m.id
GROUP BY m.id
ORDER BY loans DESC;
```

## What someone likes

Each member's loans by genre, ranked within the member. `RANK()` keeps ties: someone who borrowed
three crime and three fantasy books has two favourites. Here, for one member:

```sql
SELECT b.genre,
       COUNT(*) AS loans,
       RANK() OVER (ORDER BY COUNT(*) DESC) AS place
FROM loans AS l
JOIN copies AS c ON c.id = l.copy_id
JOIN books AS b ON b.id = c.book_id
WHERE l.member_id = 5
GROUP BY b.genre;
```

To do it for every member at once, group by member *and* genre, add `PARTITION BY l.member_id` to the
window, and keep `place = 1` in an outer query.

## Home and away

Each member has a home branch, but they can borrow copies held at any branch. Comparing the copy's
branch with the member's home branch shows how much people travel, and whether a branch's stock suits
its own members:

```sql
SELECT br.name AS home_branch,
       COUNT(*) AS loans,
       COUNT(*) FILTER (WHERE c.branch_id = m.branch_id) AS from_home
FROM loans AS l
JOIN members AS m ON m.id = l.member_id
JOIN copies AS c ON c.id = l.copy_id
JOIN branches AS br ON br.id = m.branch_id
GROUP BY m.branch_id;
```

## Gone quiet

A member who used to borrow and hasn't for months might need a reminder that the library exists.
"Not in the last 90 days" is a comparison with `date('2026-10-21', '-90 days')`:

```sql
SELECT date('2026-10-21', '-90 days') AS cutoff;
```

Use the latest loan per member (`MAX(borrowed_on)` with `HAVING`), or `NOT EXISTS` a recent loan;
both work. Members who have never borrowed are a different group, worth handling separately.
