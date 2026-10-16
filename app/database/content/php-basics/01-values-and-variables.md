## Your first PHP

PHP is a programming language that runs on a server and builds web pages, APIs and scripts.
A PHP file starts with `<?php`; this sandbox adds that line for you in the examples. `echo`
prints something, and every statement ends with a semicolon:

```php
echo 'Hello from the bookshop!';
```

Press **Try it in the sandbox** under any example to run it, change it and run it again.

## Variables

A **variable** is a named box for a value. Its name starts with `$`, and `=` puts a value in it.
You can use the variable anywhere you'd use the value, and change it later:

```php
$title = 'Dune';
$copies = 3;

echo $title;
echo "\n";
echo $copies;

$copies = 2;
echo "\n" . $copies;
```

`"\n"` is a new line, and `.` joins pieces of text together. Variable names are case-sensitive:
`$title` and `$Title` are different boxes.

## The basic types

Every value has a **type**. These five cover most of what you'll store:

| Type | Example | Used for |
|------|---------|----------|
| `string` | `'Dune'` | text |
| `int` | `42` | whole numbers |
| `float` | `19.99` | numbers with a fractional part |
| `bool` | `true`, `false` | yes or no |
| `null` | `null` | "no value" |

`var_dump()` shows a value together with its type, which makes it the best tool for seeing what's
really in a variable:

```php
var_dump('Dune');
var_dump(42);
var_dump(19.99);
var_dump(true);
var_dump(null);
```

## Arithmetic

Numbers work as you'd expect, with `+`, `-`, `*` and `/`. `**` raises to a power, and brackets set
the order:

```php
$price = 12.50;
$copies = 3;

echo $price * $copies . "\n";
echo 2 + 3 * 4 . "\n";
echo (2 + 3) * 4 . "\n";
echo 2 ** 10;
```

## Division, remainders and rounding

`/` always gives the exact answer, even for whole numbers, so `7 / 2` is `3.5`. For a whole-number
answer use `intdiv()`, and `%` gives what's left over:

```php
echo 7 / 2 . "\n";
echo intdiv(7, 2) . "\n";
echo 7 % 2 . "\n";
echo round(19.987, 2) . "\n";
echo floor(4.8) . ' ' . ceil(4.2);
```

`%` is handy for telling odd from even: `$n % 2` is 0 for even numbers.

## Changing a variable in place

Some operators change a variable using its own value. `+=` adds to it, `.=` adds text to the end,
and `++` adds one:

```php
$total = 10;
$total += 5;
$total *= 2;

$message = 'Dune';
$message .= ' is in stock';

$visitors = 0;
$visitors++;
$visitors++;

echo "$total / $message / $visitors";
```

## Text inside double quotes

Notice `"$total / $message"` above: inside **double** quotes, PHP replaces variables with their values.
Inside **single** quotes, text is used exactly as written:

```php
$title = 'Dune';

echo "Now reading: $title\n";
echo 'Now reading: $title';
```

Use single quotes for plain text and double quotes when you want variables or `\n` inside. The next
lesson covers strings in more depth.

## Putting it together

The total for a small order, with tax:

```php
$title = 'The Hobbit';
$price = 14.99;
$copies = 2;
$taxRate = 0.2;

$subtotal = $price * $copies;
$tax = round($subtotal * $taxRate, 2);
$total = $subtotal + $tax;

echo "$copies × $title\n";
echo "Subtotal: $subtotal\n";
echo "Tax: $tax\n";
echo "Total: $total";
```

Next, try the quiz to check your understanding, then solve the challenges.
