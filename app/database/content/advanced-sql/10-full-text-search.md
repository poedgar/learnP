## Why LIKE isn't enough

To find reviews that call a book slow, `LIKE` works on a few rows:

```sql
SELECT id, body FROM reviews WHERE body LIKE '%slow%';
```

But `LIKE '%…%'` has to read every row (no index can help, as you saw in lesson 8), it matches
inside words, so `'%art%'` finds "start", and it can't tell you which rows match **best**.
**Full-text search** solves all three. SQLite's version is called **FTS5**.

## Creating a full-text index

A full-text index is a special kind of table, created with `CREATE VIRTUAL TABLE … USING fts5`.
List the columns to search, then fill it. Using each review's `id` as the `rowid` lets you join
back to `reviews` later:

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT rowid, body FROM review_search LIMIT 3;
```

Behind the scenes, FTS5 splits the text into words (called **tokens**) and builds an index from
each word to the rows that contain it.

## Searching with MATCH

Search with `MATCH` and the table's name. Words match whole and ignore letter case:

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT rowid, body FROM review_search WHERE review_search MATCH 'slow';
```

Several words find rows that contain all of them, in any order: `MATCH 'desert planet'` is the
same as `MATCH 'desert AND planet'`.

## Phrases, prefixes and operators

The search text has a small language of its own:

| Search | Finds rows containing |
|--------|-----------------------|
| `slow middle` | both words, anywhere |
| `"desert planet"` | the exact phrase |
| `creep*` | a word starting with "creep": creepy, creeping |
| `creepy OR chilling` | either word |
| `long NOT magic` | "long" but not "magic" |
| `NEAR(slow middle, 5)` | both words, at most 5 words apart |

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT rowid, body FROM review_search WHERE review_search MATCH 'creepy OR chilling';
```

## Searching one column

An index over several columns searches all of them, unless you put a column name and a colon in
front of a word:

```sql
CREATE VIRTUAL TABLE book_search USING fts5(title, author, genre);
INSERT INTO book_search (rowid, title, author, genre) SELECT id, title, author, genre FROM books;

SELECT rowid, title, author FROM book_search WHERE book_search MATCH 'author:tolkien';
```

## Ranking the results

Every match has a `rank`: a score for how well it matches, where **lower is better**. Rare words
count for more than common ones, and short texts full of the search words beat long texts that
mention them once. `ORDER BY rank` puts the best first:

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT rowid, body, rank
FROM review_search
WHERE review_search MATCH 'slow OR long'
ORDER BY rank;
```

## Showing the matches

`highlight` returns a column's text with the matching words wrapped in the markers you choose, and
`snippet` returns just a short piece around them. Both take the table, the column's position
(counting from 0), and the markers:

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT highlight(review_search, 0, '[', ']') AS highlighted,
       snippet(review_search, 0, '[', ']', '…', 4) AS short
FROM review_search
WHERE review_search MATCH 'politics';
```

## Word forms

By default `read` doesn't match "reading". The `porter` tokenizer reduces English words to their
stem, so different forms of a word match each other:

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body, tokenize = 'porter');
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

SELECT rowid, body FROM review_search WHERE review_search MATCH 'read';
```

## Keeping the index in step

A full-text table is separate from `reviews`. New reviews don't appear in it by themselves, so
keep it in step with triggers (lesson 9):

```sql
CREATE VIRTUAL TABLE review_search USING fts5(body);
INSERT INTO review_search (rowid, body) SELECT id, body FROM reviews;

CREATE TRIGGER reviews_added AFTER INSERT ON reviews
BEGIN
    INSERT INTO review_search (rowid, body) VALUES (NEW.id, NEW.body);
END;

INSERT INTO reviews (book_id, customer_id, rating, body, reviewed_on)
VALUES (13, 10, 5, 'Cyberpunk at its sharpest.', '2026-04-09');
SELECT rowid, body FROM review_search WHERE review_search MATCH 'cyberpunk';
```

A complete setup also has triggers for `DELETE` and `UPDATE`. FTS5 can also index another table's
text without keeping its own copy, with `content = 'reviews'`; see the SQLite documentation when
you need that.

## Putting it together

Search the catalogue and join back to `books` for the details, best matches first:

```sql
CREATE VIRTUAL TABLE book_search USING fts5(title, author, genre);
INSERT INTO book_search (rowid, title, author, genre) SELECT id, title, author, genre FROM books;

SELECT b.title, b.author, b.price
FROM book_search AS s
JOIN books AS b ON b.id = s.rowid
WHERE book_search MATCH 'fantasy NOT tolkien'
ORDER BY s.rank, b.title;
```

That's the end of the course. You can now read and write SQL well beyond the basics. Try the quiz,
then the last challenges.
