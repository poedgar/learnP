## Everything from outside is a guess

A web app is a program that strangers send data to. Query strings, form fields, cookies, headers,
uploaded file names and JSON bodies all come from the person (or script) making the request, and they
can put anything in them. Most security bugs start the same way: code that **trusted** a value it
should have **checked**.

The rule this course keeps coming back to:

- **Validate input** when it arrives: decide what's acceptable and reject everything else.
- **Escape output** when it leaves: encode values for the place they're going (HTML, SQL, a URL,
  a shell command), so data can never be read as code.

Neither replaces the other. This lesson is about the first.

## Allow, don't deny

There are two ways to check input. A **deny-list** looks for known bad things ("reject anything
containing `<script>`"); an **allow-list** describes exactly what's good ("a sort column is `title`,
`author` or `price`"). Attackers are endlessly creative about getting past deny-lists, while an
allow-list simply has nothing else to let through:

```php
function sortColumnUnsafe(string $input): string
{
    // Deny-list: blocks the obvious attack...
    return str_contains($input, ';') ? 'title' : $input;
}

$attack = 'price DESC, (SELECT password FROM users LIMIT 1)';
echo sortColumnUnsafe($attack) . PHP_EOL;

$allowed = ['title', 'author', 'price'];
echo in_array($attack, $allowed, true) ? $attack : 'title';
```

## Check the shape, not just the type

PHP happily converts strings to numbers, which hides bad input instead of rejecting it. `(int)`
turns `'12abc'` into `12` and `'abc'` into `0`, and `is_numeric()` accepts `'1e3'` and `' 5'`. When
you need a whole number, say exactly what one looks like:

```php
foreach (['42', '42abc', ' 42', '1e3', '-1', '007'] as $input) {
    $digitsOnly = preg_match('/^\d+$/', $input) === 1;
    printf("%-6s (int)=%-4d is_numeric=%-5s digits only=%s\n", "'$input'", (int) $input, var_export(is_numeric($input), true), var_export($digitsOnly, true));
}
```

Then check the **range** too: a page number of `999999999` is a valid integer and still nonsense.

## Loose comparisons are a trap

`==` compares after converting types, and the rules are surprising. Two different strings that both
look like numbers in scientific notation compare equal, because both are zero:

```php
var_dump('0e1234' == '0e9999');   // both are 0 × 10^n
var_dump('0e1234' === '0e9999');
var_dump('1e1' == '10');
var_dump(in_array('1e1', ['10']));
var_dump(in_array('1e1', ['10'], true));
```

This has broken real login and password-reset code, where a hash or token happened to start with
`0e` followed by digits. For anything security-related, compare with `===`, use `in_array(..., true)`,
and for secrets use `hash_equals()`, which also resists timing attacks (lesson 6).

JSON makes this worse: `json_decode()` can hand you `true`, an array or a number where you expected a
string. `true == 'admin'` is true in PHP. Check types before values.

## Validate whole requests

Real input arrives as a bundle: a JSON body or a form with several fields. Check every field you use,
reject fields you don't expect, and pass on only the cleaned values, never the original array:

```php
$body = json_decode('{"book_id": 7, "quantity": 2, "price": 0.01}', true);

$order = [
    'book_id' => $body['book_id'],
    'quantity' => $body['quantity'],
];

// The price comes from the database, never from the request.
print_r($order);
```

A field like `price` or `is_admin` in a request is a classic way in: code that passes the whole array
on to `create()` or `update()` lets the visitor set it. Lesson 8 returns to this.

## Normalise first

Sometimes the same value can be written several ways: `Ada@Example.com` and `ada@example.com `
are the same address. Normalise (trim, change case) **before** validating and before comparing or
storing, so two spellings can't create two accounts or slip past a check that looked at one of them.

## What goes in the error message

When input is rejected, say what was wrong with the input ("quantity must be between 1 and 10"), not
what happened inside your app. Stack traces, SQL and file paths in error pages tell an attacker how
your code is built. Laravel shows details only when `APP_DEBUG` is true, which must never be the case
in production.
