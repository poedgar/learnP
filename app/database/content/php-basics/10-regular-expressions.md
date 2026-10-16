## Patterns for text

`str_contains()` finds fixed text. To find text that follows a **pattern**, such as "any ISBN" or "a
postcode", PHP uses **regular expressions**. A regular expression is written inside delimiters, usually
`/…/`, and `preg_match()` says whether a string matches it (1 for yes, 0 for no):

```php
var_dump(preg_match('/Dune/', 'I loved Dune'));
var_dump(preg_match('/dune/', 'I loved Dune'));
var_dump(preg_match('/dune/i', 'I loved Dune'));
```

Letters after the closing delimiter are **flags**; `i` ignores letter case.

## Characters and classes

Most characters match themselves. A few have special meanings, and square brackets match **one** of a
set of characters:

| Pattern | Matches |
|---------|---------|
| `.` | any single character |
| `\d` | a digit; `\D` is anything else |
| `\w` | a letter, digit or underscore |
| `\s` | a space, tab or new line |
| `[aeiou]` | one of the listed characters |
| `[a-z]` | one character in the range |
| `[^0-9]` | one character **not** in the set |

```php
var_dump(preg_match('/\d/', 'Room 7'));
var_dump(preg_match('/[aeiou]/', 'rhythm'));
var_dump(preg_match('/b.g/', 'big bag bug'));
```

To match a special character literally, put a backslash before it: `\.` is a real full stop.

## How many: quantifiers

A **quantifier** after something says how many times it may repeat:

| Quantifier | Means |
|------------|-------|
| `*` | 0 or more |
| `+` | 1 or more |
| `?` | 0 or 1 (optional) |
| `{3}` | exactly 3 |
| `{2,4}` | from 2 to 4 |

```php
var_dump(preg_match('/^\d{13}$/', '9780441013593'));
var_dump(preg_match('/^\d{13}$/', '978044101359'));
var_dump(preg_match('/^colou?r$/', 'color'));
```

`^` means "the start of the string" and `$` "the end". Without them, the pattern only has to appear
**somewhere**, which is the most common regular expression bug in validation.

## Capturing parts: groups

Round brackets make a **group**. `preg_match()` puts what each group matched into an array you pass as its
third argument: the whole match at index 0, then each group in order:

```php
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', '2026-04-08', $parts)) {
    echo "Year {$parts[1]}, month {$parts[2]}, day {$parts[3]}";
}
```

`(?<name>…)` gives a group a name, so you can read it as `$parts['name']`:

```php
preg_match('/^(?<title>.+) by (?<author>.+)$/', 'Dune by Frank Herbert', $parts);
echo $parts['author'];
```

Groups also work with quantifiers: `(ab)+` is one or more `ab`s, and `(cat|dog)` is either word.

## Finding every match

`preg_match()` stops at the first match. `preg_match_all()` finds them all, and returns how many it found:

```php
$text = 'Order A-1001 and A-1002 shipped; A-99 is waiting.';
$count = preg_match_all('/A-(\d+)/', $text, $matches);

echo "$count orders\n";
print_r($matches[1]);
```

`$matches[0]` holds every whole match and `$matches[1]` every first group.

## Replacing and splitting

`preg_replace()` replaces every match. In the replacement, `$1`, `$2` and so on stand for the groups.
`preg_split()` splits a string wherever the pattern matches:

```php
echo preg_replace('/\s+/', ' ', "too    many   \n spaces") . "\n";
echo preg_replace('/(\w+), (\w+)/', '$2 $1', 'Austen, Jane') . "\n";
print_r(preg_split('/[,;]\s*/', 'fantasy, horror;romance'));
```

## Greedy and lazy

Quantifiers are **greedy**: they match as much as they can. Adding `?` after one makes it **lazy**,
matching as little as possible:

```php
$html = '<b>Dune</b> and <b>Emma</b>';

preg_match('/<b>(.*)<\/b>/', $html, $greedy);
preg_match('/<b>(.*?)<\/b>/', $html, $lazy);

echo $greedy[1] . "\n";
echo $lazy[1];
```

The `/` inside the pattern needed a backslash, because `/` is the delimiter. You can pick another
delimiter instead, such as `#…#`.

## Putting it together

Tidying and checking typed-in ISBNs: remove spaces and dashes, then check for exactly 13 digits starting
with 978 or 979:

```php
$inputs = ['978-0-441-01359-3', '979 8 7654 3210 1', '123-4', '9780441013593X'];

foreach ($inputs as $input) {
    $digits = preg_replace('/[\s-]/', '', $input);
    $valid = preg_match('/^97[89]\d{10}$/', $digits) === 1;
    echo "$input: " . ($valid ? "valid ($digits)" : 'invalid') . "\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
