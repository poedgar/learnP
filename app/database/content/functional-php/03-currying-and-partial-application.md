## Fixing some arguments in advance

You often call the same function with some arguments that never change: the same tax rate, the same currency, the same
separator. **Partial application** fixes those arguments once, producing a new function that only needs the rest:

```php
function priceWith(float $rate, float $price): float
{
    return round($price * (1 + $rate), 2);
}

$withVat = fn (float $price) => priceWith(0.2, $price);

echo $withVat(10.0) . ' ' . $withVat(19.99);
```

`$withVat` is `priceWith` with the rate already filled in.

## A general `partial()`

Instead of writing a new closure each time, a higher-order helper can fix any leading arguments of any function:

```php
function partial(callable $f, mixed ...$fixed): Closure
{
    return fn (mixed ...$rest) => $f(...$fixed, ...$rest);
}

function label(string $currency, int $decimals, float $amount): string
{
    return $currency . number_format($amount, $decimals);
}

$pounds = partial(label(...), '£', 2);
$wholeEuros = partial(label(...), '€', 0);

echo $pounds(1234.5) . ' ' . $wholeEuros(1234.5);
```

`...$fixed` collects the arguments given now, and `...$rest` those given later; the call spreads both into `$f`.

## Fixing arguments from the right

PHP's own functions often take the "data" argument first and the settings later, such as `explode($separator,
$text)` or `str_repeat($text, $times)`. Fixing the **last** arguments needs the opposite order, with the fixed values
going after the ones given later:

```php
$triple = fn (string $text) => str_repeat($text, 3);
$tidyNumber = fn (float $n) => number_format($n, decimals: 2);

echo $triple('ab') . ' ' . $tidyNumber(1234567.891);
```

Named arguments, like `decimals: 2`, fix one argument by name. A general `partialRight()` helper, the mirror image of
`partial()`, is one of the challenges.

## Currying

**Currying** turns a function of several arguments into a chain of functions of **one** argument each. A curried
`add(a, b)` is called as `add(a)(b)`:

```php
$add = fn (int $a) => fn (int $b) => $a + $b;

$addFive = $add(5);
echo $addFive(3) . ' ' . $add(10)(20);
```

Every step returns a function until the last argument arrives. Currying is partial application made automatic: any
prefix of the arguments gives you a useful function.

## A `curry()` helper

For a function with a known number of parameters, a helper can do the nesting for you:

```php
function curry3(Closure $f): Closure
{
    return fn (mixed $a) => fn (mixed $b) => fn (mixed $c) => $f($a, $b, $c);
}

$volume = curry3(fn (int $w, int $h, int $d) => $w * $h * $d);

echo $volume(2)(3)(4);
```

A fully general `curry()` works for any number of parameters. It asks Reflection how many are required:

```php
$needed = (new ReflectionFunction(fn (int $w, int $h, int $d) => 0))->getNumberOfRequiredParameters();
echo $needed;
```

It then collects arguments, one or more at a time, until it has that many, and calls the function.

## Why data comes last in functional code

For partial application to be convenient, functions take their **settings first and the data last**. Then fixing the
settings leaves a function waiting for data, which slots straight into `array_map()` or a pipeline:

```php
$multiply = fn (float $factor) => fn (float $n) => $n * $factor;
$roundTo = fn (int $places) => fn (float $n) => round($n, $places);

$prices = [7.99, 19.99, 13.99];
$withTax = array_map($multiply(1.2), $prices);

print_r(array_map($roundTo(2), $withTax));
```

## Partial application with objects

The same idea appears in object-oriented code as configuration in a constructor: a `PriceFormatter('£', 2)` object is
a partially applied formatting function, ready for the amount. Closures and small invokable objects are two ways of
saying the same thing:

```php
final class Formatter
{
    public function __construct(private string $currency, private int $decimals) {}

    public function __invoke(float $amount): string
    {
        return $this->currency . number_format($amount, $this->decimals);
    }
}

$asPounds = new Formatter('£', 2);
echo implode(', ', array_map($asPounds, [3.5, 1200.0]));
```

## Putting it together

Building a family of discount functions from one curried definition, and applying a chosen one to a basket:

```php
$discount = fn (int $percent) => fn (float $minimum) => fn (float $total) =>
    $total >= $minimum ? round($total * (100 - $percent) / 100, 2) : $total;

$summerSale = $discount(20)(0.0);
$bulkOrder = $discount(10)(50.0);

foreach ([30.0, 80.0] as $total) {
    echo "£$total: summer £{$summerSale($total)}, bulk £{$bulkOrder($total)}\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
