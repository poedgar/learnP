## Naming a piece of work

A **function** packages some code under a name, so you can run it whenever you need it, with
different inputs. Its inputs are **parameters**, and `return` hands back the result:

```php
function priceWithTax(float $price): float
{
    return round($price * 1.2, 2);
}

echo priceWithTax(10.0) . "\n";
echo priceWithTax(7.99);
```

`float $price` says the parameter should be a float, and `: float` after the brackets says what the
function returns. These **type declarations** are optional, but they catch mistakes and document the
function, so use them.

## Default values

A parameter can have a default, used when the caller leaves it out. Parameters with defaults go after
the ones without:

```php
function priceWithTax(float $price, float $rate = 0.2): float
{
    return round($price * (1 + $rate), 2);
}

echo priceWithTax(10.0) . "\n";
echo priceWithTax(10.0, 0.05);
```

## Named arguments

Arguments are normally matched by position. You can also pass them **by name**, in any order, which
makes calls with several options much clearer, and lets you skip defaults you don't want to change:

```php
function label(string $title, string $author = 'Unknown', bool $shout = false): string
{
    $text = "$title by $author";
    return $shout ? strtoupper($text) : $text;
}

echo label('Dune', 'Frank Herbert') . "\n";
echo label('Beowulf', shout: true) . "\n";
echo label(author: 'Jane Austen', title: 'Emma');
```

## Any number of arguments

A parameter written with `...` collects every remaining argument into an array. That's called a
**variadic** parameter. The same `...` in a call spreads an array out into separate arguments:

```php
function total(float ...$prices): float
{
    return array_sum($prices);
}

echo total(1.5, 2.5) . "\n";
echo total(10, 20, 30) . "\n";

$basket = [7.99, 13.99];
echo total(...$basket);
```

## Functions have their own variables

Variables inside a function are separate from those outside. A function sees only its parameters and
what it creates itself; the outside variables are untouched by it. That's called **scope**:

```php
$discount = 50;

function sale(float $price): float
{
    $discount = 10;
    return $price * (100 - $discount) / 100;
}

echo sale(20.0) . "\n";
echo $discount;
```

Pass what a function needs as parameters, and get results back with `return`. (The keyword `global`
can reach outside variables, but it makes code hard to follow; avoid it.)

## Returning several things, and returning nothing

A function returns one value, but that value can be an array, which callers can destructure. A function
that only does something, such as printing, declares `: void` and returns nothing:

```php
function priceRange(array $prices): array
{
    return [min($prices), max($prices)];
}

function announce(string $message): void
{
    echo ">> $message\n";
}

[$lowest, $highest] = priceRange([7.99, 19.99, 13.99]);
announce("Prices from $lowest to $highest");
```

## Passing by reference

Arguments are normally copied in, so a function can't change the caller's variable. An `&` before the
parameter passes the variable itself instead. Use it sparingly; returning a new value is usually
clearer:

```php
function addItem(array &$basket, string $item): void
{
    $basket[] = $item;
}

$basket = [];
addItem($basket, 'Dune');
addItem($basket, 'Emma');

echo implode(', ', $basket);
```

PHP's own `sort()` works this way, which is why it changes your array.

## Functions that call themselves

A function may call itself, which is called **recursion**. It needs a case where it stops, or it would
go on for ever. Counting the items in nested lists is a natural fit:

```php
function countItems(array $items): int
{
    $count = 0;
    foreach ($items as $item) {
        $count += is_array($item) ? countItems($item) : 1;
    }
    return $count;
}

echo countItems(['Dune', ['Emma', 'Circe'], [['Beloved']]]);
```

## Putting it together

A small receipt helper with typed parameters, a default, a variadic and named arguments:

```php
function receipt(string $customer, float $taxRate = 0.2, float ...$prices): string
{
    $subtotal = array_sum($prices);
    $total = round($subtotal * (1 + $taxRate), 2);

    return sprintf('%s: %d items, £%.2f', $customer, count($prices), $total);
}

echo receipt('Amara', 0.2, 9.99, 14.99) . "\n";
echo receipt(customer: 'Ben', taxRate: 0.0);
```

Next, try the quiz to check your understanding, then solve the challenges.
