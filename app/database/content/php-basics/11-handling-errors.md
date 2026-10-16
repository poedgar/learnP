## Things go wrong

Files go missing, people type letters where numbers belong, and dates don't exist. Good code expects
that, notices when it happens, and says something useful. PHP reports problems in three ways:
**special return values**, **warnings**, and **exceptions**. This lesson covers all three.

## Functions that return `false` or `null`

Many functions signal "it didn't work" through their return value: `strpos()` returns `false` when the
text isn't found, `createFromFormat()` returns `false` for a bad date, and `??` lookups give `null`. Always
check those results before using them:

```php
$position = strpos('Dune', 'z');

if ($position === false) {
    echo "Not there\n";
} else {
    echo "Found at $position\n";
}

$date = DateTimeImmutable::createFromFormat('!d/m/Y', '45/13/2026');
echo $date === false ? 'Not a valid date' : $date->format('Y-m-d');
```

Use `=== false`, because `0` (found at the start) is falsy too.

## Warnings

A **warning** is PHP saying "this looks wrong, but I'll carry on". Reading an array key that doesn't exist
is a classic: you get a warning and `null`, and the script continues with a wrong value. In this sandbox,
warnings appear in the output. Checking first, with `??` or `isset()`, avoids them:

```php
$prices = ['Dune' => 19.99];

$price = $prices['Dune'] ?? 0.0;
echo "Dune costs $price\n";

$missing = $prices['Circe'] ?? null;
var_dump($missing);
```

Treat warnings as bugs to fix, not noise to ignore.

## Exceptions: stopping with a reason

An **exception** is an object describing a problem. `throw` stops the current function straight away and
hands the exception back up through every caller, until something deals with it. If nothing does, the
script stops with an error:

```php
// Error: nobody catches the exception, so the script stops
function quantity(int $copies): int
{
    if ($copies < 1) {
        throw new InvalidArgumentException('Order at least one copy.');
    }
    return $copies;
}

echo quantity(0);
```

PHP comes with exception classes for common cases: `InvalidArgumentException` for bad arguments,
`RuntimeException` for things that went wrong while running, `LogicException` for mistakes in the code
itself, and several more.

## Catching with `try` and `catch`

Code that might throw goes in a `try` block. If it throws, PHP jumps to the matching `catch` block, with the
exception in a variable. `getMessage()` gives its message:

```php
function quantity(int $copies): int
{
    if ($copies < 1) {
        throw new InvalidArgumentException('Order at least one copy.');
    }
    return $copies;
}

foreach ([2, 0, 5] as $copies) {
    try {
        echo 'Ordering ' . quantity($copies) . "\n";
    } catch (InvalidArgumentException $e) {
        echo 'Problem: ' . $e->getMessage() . "\n";
    }
}

echo 'Still running!';
```

The loop carries on after the problem, because the exception was handled.

## Different problems, different handling

A `try` can have several `catch` blocks, tried in order, and one `catch` can name several classes with `|`.
`finally` runs whatever happens, which is useful for tidying up:

```php
function parseQuantity(string $input): int
{
    if (! is_numeric($input)) {
        throw new InvalidArgumentException("'$input' isn't a number.");
    }
    if ((int) $input > 10) {
        throw new RangeException('At most 10 copies per order.');
    }
    return (int) $input;
}

foreach (['3', 'lots', '50'] as $input) {
    try {
        echo 'OK: ' . parseQuantity($input) . "\n";
    } catch (InvalidArgumentException $e) {
        echo 'Typing mistake: ' . $e->getMessage() . "\n";
    } catch (RangeException $e) {
        echo 'Too many: ' . $e->getMessage() . "\n";
    } finally {
        echo "(checked '$input')\n";
    }
}
```

## Errors from PHP itself

Some problems are PHP's own `Error`s rather than exceptions: calling a function that doesn't exist, the
wrong type for a typed parameter (`TypeError`), or dividing by zero (`DivisionByZeroError`). They can be caught
too, though usually they mean the code needs fixing:

```php
try {
    echo intdiv(10, 0);
} catch (DivisionByZeroError $e) {
    echo 'Oops: ' . $e->getMessage();
}
```

Both `Exception` and `Error` share the parent type `Throwable`, so `catch (Throwable $e)` catches anything.
Use it rarely, at the very top of a program, so you don't hide bugs.

## Check early, fail clearly

The best place to deal with bad input is as soon as it arrives. A function that checks its arguments
first, and throws with a clear message, saves everyone from puzzling over a wrong result later:

```php
function shippingCost(float $weightKg, string $country): float
{
    if ($weightKg <= 0) {
        throw new InvalidArgumentException('The weight must be above zero.');
    }
    if (! in_array($country, ['UK', 'IE'], true)) {
        throw new InvalidArgumentException("We don't ship to $country yet.");
    }

    return $country === 'UK' ? 2.95 + $weightKg : 5.50 + $weightKg * 1.5;
}

echo shippingCost(1.2, 'UK');
```

## Putting it together

Processing typed-in order lines, reporting each problem and totting up the good ones:

```php
function lineTotal(string $line): float
{
    $parts = explode('x', str_replace(' ', '', $line));
    if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
        throw new InvalidArgumentException("Can't read '$line'. Use the form '2 x 9.99'.");
    }
    return (int) $parts[0] * (float) $parts[1];
}

$total = 0.0;
foreach (['2 x 9.99', 'three x 5', '1 x 14.50'] as $line) {
    try {
        $total += lineTotal($line);
    } catch (InvalidArgumentException $e) {
        echo $e->getMessage() . "\n";
    }
}

printf('Total of the readable lines: £%.2f', $total);
```

That's the end of PHP basics. Next, try the quiz, then the challenges, and then the OOP in PHP course.
