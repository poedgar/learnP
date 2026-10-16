## Strings are text

A **string** is any piece of text. You've seen single quotes (text as written) and double
quotes (variables and `\n` replaced). Inside double quotes, put curly braces around a variable when
the text right after it would otherwise run into its name:

```php
$copies = 3;
$item = 'book';

echo "You ordered $copies {$item}s.\n";
echo 'Single quotes: $copies {$item}s.';
```

To put a quote mark inside a string of the same kind, escape it with a backslash: `'It\'s here'`.

## Measuring and changing case

PHP has dozens of string functions. These come up all the time:

```php
$title = 'the name of the wind';

echo strlen($title) . "\n";
echo strtoupper($title) . "\n";
echo ucfirst($title) . "\n";
echo ucwords($title) . "\n";
echo trim('   padded   ') . "|";
```

`strlen()` counts bytes, which is the same as characters for plain English text. Accented letters and
emoji take more than one byte each.

## Finding and cutting

`str_contains()`, `str_starts_with()` and `str_ends_with()` answer yes-or-no questions. `strpos()`
gives the position where some text first appears (counting from 0), and `substr()` cuts out a piece:

```php
$isbn = '978-0-441-01359-3';

var_dump(str_starts_with($isbn, '978'));
var_dump(str_contains($isbn, '441'));
echo strpos($isbn, '-') . "\n";
echo substr($isbn, 0, 3) . "\n";
echo substr($isbn, -1);
```

A negative position counts from the end, so `substr($isbn, -1)` is the last character. `strpos()`
returns `false` when the text isn't there, so check its result with `=== false`, never with `!`.

## Replacing, splitting and joining

`str_replace()` swaps every occurrence of some text. `explode()` splits a string into an array of
pieces, and `implode()` joins an array back into one string:

```php
$isbn = '978-0-441-01359-3';
echo str_replace('-', '', $isbn) . "\n";

$tags = explode(',', 'fantasy,classic,dragons');
echo count($tags) . ' tags, the first is ' . $tags[0] . "\n";

echo implode(' | ', $tags);
```

You'll meet arrays properly in lesson 5; for now, think of one as a numbered list.

## Formatting with `sprintf`

`sprintf()` builds a string from a pattern with **placeholders**, filling them in order. `%s` takes a
string, `%d` a whole number, and `%.2f` a number with exactly two decimal places. `printf()` does the
same and prints the result:

```php
$title = 'Dune';
$copies = 3;
$price = 19.9;

echo sprintf('%d × %s at £%.2f', $copies, $title, $price) . "\n";
printf('Total: £%.2f', $copies * $price);
```

Widths line things up in columns: `%-12s` pads a string to 12 characters on the right, and `%6.2f`
pads a number to 6 characters on the left.

```php
printf("%-12s%8.2f\n", 'Dune', 19.9);
printf("%-12s%8.2f\n", 'Emma', 7.99);
printf("%-12s%8.2f\n", 'Circe', 13.99);
```

`number_format(1234.5, 2)` gives `1,234.50`, with a thousands separator.

## Long text: heredoc and nowdoc

For text over several lines, a **heredoc** starts with `<<<` and a name of your choosing, and ends
with that name on a line of its own. It works like double quotes. A **nowdoc**, with the name in single
quotes, works like single quotes:

```php
$name = 'Amara';
$order = 'A-1001';

$email = <<<TEXT
    Hi $name,

    Your order $order has shipped.
    TEXT;

echo $email . "\n\n";

echo <<<'TEXT'
    In a nowdoc, $name stays as it is.
    TEXT;
```

The closing name's indentation is removed from every line, so the text can be indented to match your
code.

## Comparing strings

`===` compares strings exactly, including case. `strcasecmp()` ignores case, and `strcmp()` and the
spaceship operator `<=>` tell you which comes first alphabetically:

```php
var_dump('Dune' === 'dune');
var_dump(strcasecmp('Dune', 'dune') === 0);
echo ('Dune' <=> 'Emma') . "\n";
echo ('apple' <=> 'Apple');
```

Uppercase letters sort before lowercase ones, so `'Apple'` comes before `'apple'`.

## Putting it together

A shelf label built from raw, messy data:

```php
$rawTitle = '  the left hand of darkness ';
$author = 'Ursula K. Le Guin';
$price = 15.5;

$title = ucwords(trim($rawTitle));
$code = strtoupper(substr(str_replace(' ', '', $author), 0, 3));

echo sprintf('[%s] %s, £%.2f', $code, $title, $price);
```

Next, try the quiz to check your understanding, then solve the challenges.
