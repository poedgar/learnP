## Breaking out of the query

**SQL injection** is XSS's older sibling: user input is glued into an SQL string, so the input can
change what the query *does*. A login check built like this:

```php
$email = "nobody@example.com' OR '1'='1";
$password = 'whatever';

$sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";

echo $sql;
```

...now asks for every user where `email = 'nobody@example.com'`, **or** where `'1'='1'`, which is all
of them. The same trick can read other tables with `UNION SELECT`, change data, or delete it. It has
been behind some of the largest data breaches ever.

## The fix: placeholders and bindings

Never put values into the SQL text. Write a **placeholder** (`?` or a name like `:email`) and send the
value separately. The database receives the query's *structure* first and the values afterwards, so a
value can never be read as SQL, whatever characters it contains. With PHP's PDO:

```text
$statement = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$statement->execute([$email]);
$user = $statement->fetch();
```

This is exactly what Laravel's query builder and Eloquent do: `User::where('email', $email)` produces
`select * from users where email = ?` with `$email` as a **binding**. In plain PHP, a function that
builds a query should return both parts:

```php
function booksBy(string $author): array
{
    return [
        'sql' => 'SELECT title FROM books WHERE author = ?',
        'bindings' => [$author],
    ];
}

print_r(booksBy("O'Brien' OR 1=1 --"));
```

The apostrophe in `O'Brien` doesn't need escaping and the attack is just an odd author name that
matches nothing.

**Escaping functions** (`addslashes()`, `mysqli_real_escape_string()`) were the old way. They're easy
to forget, depend on the connection's character set, and don't help with numbers or identifiers. Use
bindings.

## What can't be a placeholder

Placeholders stand for **values** only. Table names, column names, `ASC`/`DESC` and SQL keywords can't
be bound, yet "sort by this column" often comes from the URL. Those parts need an **allow-list**: map
what the user asked for onto a fixed set of strings you wrote:

```php
$columns = ['title' => 'title', 'newest' => 'published_at', 'price' => 'price'];

$requested = $_GET['sort'] ?? 'newest; DROP TABLE books';
$column = $columns[$requested] ?? 'title';

echo "SELECT * FROM books ORDER BY $column";
```

The same goes for `LIMIT` and `OFFSET`, which some drivers won't bind: cast to `int` and clamp to a
sensible range, so only digits can ever reach the SQL.

## Lists and LIKE

Two everyday cases need care:

- **`IN (...)` lists**: one placeholder per value, `IN (?, ?, ?)`, generated from the count. An empty list
  can't be written as `IN ()`, which is a syntax error; use a condition that is always false.
- **`LIKE` searches**: the value is bound, so it's safe, but `%` and `_` inside it are still wildcards.
  A search for `100%` would match anything starting with `100`. Escape them with a chosen escape
  character and tell the database which one: `... LIKE ? ESCAPE '\'`.

## Logging queries safely

To log or debug a query, you might want to see it with its values filled in, as Laravel's
`toRawSql()` does. That string is for **reading only**: quote strings (doubling any `'` inside),
write numbers as they are and `null` as `NULL`, and never send the result back to the database.

## Second-order injection

Injection doesn't need to happen on the request that carries the payload. A username stored safely
today can be glued into a different query by a nightly report tomorrow. Data from your own database is
still user data if a user put it there, so the rule never changes: **every value goes in as a
binding**.
