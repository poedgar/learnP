## Thinking in functions

**Functional programming** is a style that builds programs out of small functions that take values and return new
values, without changing anything along the way. PHP isn't a purely functional language, but its closures, arrow
functions and array functions make the style very practical. This course goes beyond the closures lesson of the
Advanced OOP course: what makes a function "pure", folding with `array_reduce`, currying, and building pipelines.

## Pure functions

A function is **pure** when:

1. its result depends **only** on its arguments (the same input always gives the same output), and
2. it has **no side effects**: it doesn't print, change a global, write to a file or a database, or change its
   arguments.

```php
function withTax(float $price, float $rate): float
{
    return round($price * (1 + $rate), 2);
}

echo withTax(10.0, 0.2) . ' ' . withTax(10.0, 0.2);
```

`withTax()` is pure: give it 10.0 and 0.2, and it will return 12.0 today, tomorrow and in every test.

## Impure functions

These look similar, but each one is impure, for a different reason:

```php
$taxRate = 0.2;
$log = [];

function withGlobalTax(float $price): float
{
    global $taxRate;
    return $price * (1 + $taxRate);
}

function withTaxAndLog(float $price): float
{
    global $log;
    $log[] = "taxed $price";
    return $price * 1.2;
}

function randomDiscount(float $price): float
{
    return $price * (random_int(80, 100) / 100);
}

echo withGlobalTax(10.0) . "\n";
$taxRate = 0.05;
echo withGlobalTax(10.0) . "\n";
echo withTaxAndLog(10.0) . ' and the log has ' . count($log) . ' entry';
```

The first depends on a global that can change, the second changes the log, and the third returns something different
each time.

## Why purity is worth it

Pure functions are:

- **easy to test**: no setup, no mocks, just inputs and expected outputs;
- **easy to understand**: everything that matters is in the signature;
- **safe to reuse and combine**: calling one can't break something elsewhere;
- **safe to cache**: since the same input gives the same output, answers can be remembered (memoization).

Real programs must print, save and send email, of course. The functional approach is to keep those side effects at
the edges, and make the core logic pure.

## Don't change your arguments

Arrays are copied when passed in, so changing an array parameter only changes the function's copy. But objects are
passed as handles, so changing an object argument changes the caller's object, a classic hidden side effect:

```php
class Basket
{
    public array $items = [];
}

function addItemImpure(Basket $basket, string $title): Basket
{
    $basket->items[] = $title;
    return $basket;
}

function addItemPure(array $items, string $title): array
{
    return [...$items, $title];
}

$basket = new Basket();
$copy = addItemImpure($basket, 'Dune');
echo count($basket->items) . " (the caller's basket changed)\n";

$items = ['Dune'];
$more = addItemPure($items, 'Emma');
echo count($items) . ' and ' . count($more);
```

## Immutable data

**Immutable** values never change after they're created; "changing" one means making a new value with the difference.
Readonly classes and `with…()` methods (from the OOP courses) make immutable objects, and plain arrays behave this way
when you build new ones instead of editing in place:

```php
final readonly class Price
{
    public function __construct(public float $amount, public string $currency) {}

    public function withAmount(float $amount): self
    {
        return new self($amount, $this->currency);
    }

    public function discounted(int $percent): self
    {
        return $this->withAmount(round($this->amount * (100 - $percent) / 100, 2));
    }
}

$price = new Price(20.0, 'GBP');
$sale = $price->discounted(25);

echo "{$price->amount} → {$sale->amount}";
```

## Expressions over statements

Functional code prefers **expressions** that produce values to **statements** that change variables step by step.
`match`, the ternary operator and array functions are expressions; loops that build up a variable are statements:

```php
$prices = [7.99, 19.99, 13.99];

$total = 0;
foreach ($prices as $price) {
    if ($price < 15) {
        $total += $price;
    }
}
echo "$total\n";

echo array_sum(array_filter($prices, fn (float $p) => $p < 15));
```

Both are correct. The second says **what** it computes rather than **how**, and has no variable that changes.

## Side effects at the edges

A common structure: a pure core that works out **what** should happen, and a thin impure shell that **does** it. The
core can be tested without printing or sending anything:

```php
function reminders(array $loans, string $today): array
{
    return array_values(array_map(
        fn (array $loan) => "Dear {$loan['name']}, '{$loan['title']}' was due on {$loan['due']}.",
        array_filter($loans, fn (array $loan) => $loan['due'] < $today),
    ));
}

$loans = [
    ['name' => 'Amara', 'title' => 'Dune', 'due' => '2026-04-01'],
    ['name' => 'Ben', 'title' => 'Emma', 'due' => '2026-05-01'],
];

foreach (reminders($loans, '2026-04-20') as $message) {
    echo $message . "\n";
}
```

`reminders()` is pure; only the `foreach … echo` at the bottom has a side effect.

## Putting it together

A pure function that applies a list of price rules, each itself a pure function, with nothing changed along the way:

```php
$rules = [
    fn (float $p) => $p * 0.9,
    fn (float $p) => $p > 20 ? $p - 2 : $p,
    fn (float $p) => round($p, 2),
];

function applyAll(float $price, array $rules): float
{
    return array_reduce($rules, fn (float $carry, Closure $rule) => $rule($carry), $price);
}

echo applyAll(30.0, $rules) . ' ' . applyAll(10.0, $rules);
```

`array_reduce()` is the subject of the next lesson. Next, try the quiz, then the challenges.
