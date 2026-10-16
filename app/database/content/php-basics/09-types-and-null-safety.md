## Saying what you expect

Type declarations on parameters and return values tell PHP, and anyone reading, what a function
expects and promises. When the wrong kind of value arrives, PHP stops with a clear `TypeError` instead
of carrying on with nonsense. PHP 8 added several ways to describe types precisely.

## Values that may be missing: `?type`

A `?` before a type also allows `null`, which suits values that may be absent, such as an optional
discount. Pair it with a `null` default to make the argument optional:

```php
function finalPrice(float $price, ?int $discountPercent = null): float
{
    if ($discountPercent === null) {
        return $price;
    }
    return round($price * (100 - $discountPercent) / 100, 2);
}

echo finalPrice(20.0) . "\n";
echo finalPrice(20.0, 25);
```

## One of several types: union types

`int|float` accepts either. A **union type** lists the types a value may have, separated by `|`. Many of
PHP's own functions return one, such as `strpos()`, which returns `int|false`:

```php
function describeAmount(int|float $amount): string
{
    return is_int($amount) ? "$amount whole units" : "$amount (with a fraction)";
}

echo describeAmount(3) . "\n";
echo describeAmount(2.5) . "\n";

$position = strpos('Dune', 'z');
var_dump($position);
```

`?string` is short for `string|null`.

## Anything at all: `mixed`

`mixed` accepts any value, `null` included. It's honest for functions that really handle everything,
like `var_dump()`, but it also gives up all the checking types provide, so use something more precise
whenever you can:

```php
function show(mixed $value): string
{
    return get_debug_type($value) . ': ' . var_export($value, true);
}

echo show(42) . "\n";
echo show(['Dune']) . "\n";
echo show(null);
```

## Functions that never return: `never`

`void` means "returns, but with no value". `never` means the function **never returns at all**: it
always throws an exception or ends the script. That tells readers (and tools) that code after the call
won't run:

```php
function fail(string $message): never
{
    throw new RuntimeException($message);
}

function stockOf(array $stock, string $title): int
{
    return $stock[$title] ?? fail("$title isn't stocked.");
}

echo stockOf(['Dune' => 3], 'Dune') . "\n";

try {
    stockOf(['Dune' => 3], 'Circe');
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
```

## Fallbacks: `??` and `??=`

`$a ?? $b` gives `$a` unless it's missing or `null`, in which case it gives `$b`, without any warning.
`$a ??= $b` assigns `$b` only when `$a` is missing or `null`, which is perfect for defaults:

```php
$settings = ['theme' => 'dark', 'perPage' => null];

echo $settings['language'] ?? 'en';
echo "\n";

$settings['theme'] ??= 'light';
$settings['perPage'] ??= 20;
$settings['language'] ??= 'en';

print_r($settings);
```

Don't confuse it with `?:`, which falls back for **any** falsy value: `0 ?: 5` is 5, but `0 ?? 5` is 0.

## Methods on maybe-missing objects: `?->`

Calling a method on `null` is an error. The **nullsafe** operator `?->` calls the method only when the
object exists, and otherwise gives `null` straight away, which pairs neatly with `??`:

```php
function publishedYear(?DateTimeImmutable $published): string
{
    return $published?->format('Y') ?? 'unknown';
}

echo publishedYear(new DateTimeImmutable('1965-08-01')) . "\n";
echo publishedYear(null);
```

```php
// Error: calling a method on null
$published = null;
echo $published->format('Y');
```

## Several types at once: intersection types

Occasionally a value must be **all** of several types at once, for example something you can both count
and loop over. `A&B` is an **intersection type**. PHP's built-in `ArrayObject` is both `Countable` and
`Traversable`:

```php
function summary(Countable&Traversable $items): string
{
    $names = [];
    foreach ($items as $item) {
        $names[] = $item;
    }
    return count($items) . ' items: ' . implode(', ', $names);
}

echo summary(new ArrayObject(['Dune', 'Emma']));
```

You'll understand `Countable` and `Traversable` better after the OOP in PHP course; for now, just note
the `&`.

## Putting it together

A lookup helper that is precise about what may be missing:

```php
function priceLabel(array $prices, string $title, ?float $discount = null): string
{
    $price = $prices[$title] ?? null;

    if ($price === null) {
        return "$title: not stocked";
    }

    $final = $discount === null ? $price : round($price * (1 - $discount), 2);
    return sprintf('%s: £%.2f', $title, $final);
}

$prices = ['Dune' => 19.99, 'Emma' => 7.99];
echo priceLabel($prices, 'Dune') . "\n";
echo priceLabel($prices, 'Emma', 0.5) . "\n";
echo priceLabel($prices, 'Circe');
```

Next, try the quiz to check your understanding, then solve the challenges.
