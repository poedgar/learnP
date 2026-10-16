## Functions that take or return functions

A **higher-order function** takes a function as an argument, returns one, or both. `array_map()`, `array_filter()`
and `usort()` are higher-order: you hand them a function that says what to do with each item. Writing your own lets
you name and reuse patterns of work:

```php
function twice(Closure $f): Closure
{
    return fn (mixed $x) => $f($f($x));
}

$addTen = fn (int $n) => $n + 10;
$addTwenty = twice($addTen);

echo $addTwenty(5);
```

## Map, filter and reduce

Most list processing fits three shapes:

- **map**: change every item (same number of items out);
- **filter**: keep some items (same items, fewer of them);
- **reduce**: combine all the items into one value (a total, a count, a new array).

```php
$books = [
    ['title' => 'Dune', 'price' => 19.99],
    ['title' => 'Emma', 'price' => 7.99],
    ['title' => 'Circe', 'price' => 13.99],
];

$titles = array_map(fn ($b) => $b['title'], $books);
$cheap = array_filter($books, fn ($b) => $b['price'] < 15);
$total = array_reduce($books, fn ($sum, $b) => $sum + $b['price'], 0);

echo implode(', ', $titles) . ' | ' . count($cheap) . ' cheap | total ' . $total;
```

## How `array_reduce` works

`array_reduce($items, $f, $initial)` keeps a running value, the **carry** (or accumulator). It starts as `$initial`,
and for each item, `$f($carry, $item)` returns the new carry. The last carry is the result:

```php
$steps = [];
$total = array_reduce([3, 5, 2], function (int $carry, int $n) use (&$steps): int {
    $steps[] = "$carry + $n";
    return $carry + $n;
}, 0);

echo implode(' → ', $steps) . " = $total";
```

Always pass the initial value. Without it, the carry starts as `null`, which gives surprising results for an empty
list.

## Reducing to any shape

The carry can be anything: a number, a string, an array, an object. That makes `array_reduce()` the most general of
the three; map and filter can both be written with it:

```php
$prices = [7.99, 19.99, 13.99];

$doubled = array_reduce($prices, fn (array $carry, float $p) => [...$carry, $p * 2], []);
$cheap = array_reduce($prices, fn (array $carry, float $p) => $p < 15 ? [...$carry, $p] : $carry, []);
$stats = array_reduce($prices, fn (array $s, float $p) => ['min' => min($s['min'], $p), 'max' => max($s['max'], $p)], ['min' => INF, 'max' => -INF]);

print_r($doubled);
print_r($cheap);
print_r($stats);
```

Use map and filter when they fit, because they say more; reach for reduce when building something new.

## Grouping and counting with reduce

Building an associative array is a natural reduce: the carry is the array so far:

```php
$sales = ['Dune', 'Emma', 'Dune', 'Circe', 'Dune'];

$counts = array_reduce(
    $sales,
    fn (array $counts, string $title) => [...$counts, $title => ($counts[$title] ?? 0) + 1],
    [],
);

print_r($counts);
```

The spread with a string key replaces that key, so each step returns a new array with one count updated.

## Functions that return functions

Returning a closure lets you make specialised functions from general ones. A filter "factory" builds predicates:

```php
function priceBelow(float $limit): Closure
{
    return fn (array $book) => $book['price'] < $limit;
}

function byAuthor(string $author): Closure
{
    return fn (array $book) => $book['author'] === $author;
}

$books = [
    ['title' => 'A Wizard of Earthsea', 'author' => 'Ursula K. Le Guin', 'price' => 11.25],
    ['title' => 'Dune', 'author' => 'Frank Herbert', 'price' => 19.99],
    ['title' => 'The Lathe of Heaven', 'author' => 'Ursula K. Le Guin', 'price' => 9.99],
];

echo count(array_filter($books, priceBelow(12))) . ' under £12, ';
echo count(array_filter($books, byAuthor('Ursula K. Le Guin'))) . ' by Le Guin';
```

## Combining predicates

Predicates combine like conditions. Small higher-order helpers for "and", "or" and "not" let you build complex filters
from simple named pieces:

```php
function both(Closure $a, Closure $b): Closure
{
    return fn (mixed $x) => $a($x) && $b($x);
}

function not(Closure $f): Closure
{
    return fn (mixed $x) => ! $f($x);
}

$isEven = fn (int $n) => $n % 2 === 0;
$isBig = fn (int $n) => $n > 10;

$numbers = [4, 11, 12, 7, 20, 3];
echo implode(', ', array_filter($numbers, both($isEven, not($isBig))));
```

## Keys in map and filter

`array_filter()` keeps keys, and passes only values unless you ask: `ARRAY_FILTER_USE_KEY` passes keys and
`ARRAY_FILTER_USE_BOTH` passes both. `array_map()` with an associative array keeps the keys too (with one array):

```php
$stock = ['Dune' => 3, 'Emma' => 0, 'Circe' => 5];

print_r(array_filter($stock, fn (int $n, string $title) => $n > 0 && $title !== 'Circe', ARRAY_FILTER_USE_BOTH));
print_r(array_map(fn (int $n) => $n * 2, $stock));
```

## Putting it together

A report built entirely from higher-order functions: filter the in-stock books, map them to lines, and reduce to a
total:

```php
$books = [
    ['title' => 'Dune', 'price' => 19.99, 'stock' => 3],
    ['title' => 'Emma', 'price' => 7.99, 'stock' => 0],
    ['title' => 'Circe', 'price' => 13.99, 'stock' => 5],
];

$inStock = array_filter($books, fn ($b) => $b['stock'] > 0);
$lines = array_map(fn ($b) => sprintf('%-6s £%.2f × %d', $b['title'], $b['price'], $b['stock']), $inStock);
$value = array_reduce($inStock, fn ($sum, $b) => $sum + $b['price'] * $b['stock'], 0.0);

echo implode("\n", $lines) . "\n" . sprintf('Stock value: £%.2f', $value);
```

Next, try the quiz to check your understanding, then solve the challenges.
