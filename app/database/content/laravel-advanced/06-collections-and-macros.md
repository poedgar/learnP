## Arrays with methods

Laravel wraps arrays in `Illuminate\Support\Collection`, which gives them dozens of chainable
methods. Every Eloquent query that returns many models returns a collection, and `collect()` turns
any array into one:

```laravel
$titles = Book::all()
    ->filter(fn (Book $book) => $book->in_stock)
    ->sortBy('price')
    ->pluck('title')
    ->take(5);
```

Reading top to bottom says what happens, which is much harder with nested `array_filter()` and
`array_map()` calls. This lesson rebuilds the parts that make collections work.

## A collection is a wrapper

At heart a collection is a class holding an array. Each method does its work with a normal array
function and wraps the result in a **new** collection, leaving the original untouched, which is
what makes chaining safe:

```php
class Collection
{
    public function __construct(private array $items = []) {}

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    public function sum(): int|float
    {
        return array_sum($this->items);
    }

    public function all(): array
    {
        return $this->items;
    }
}

$prices = new Collection([10, 20, 30]);
$withTax = $prices->map(fn (int $price) => $price * 1.2);

echo $withTax->sum() . ' (was ' . $prices->sum() . ')';
```

Laravel's `filter()` keeps the original keys, just like `array_filter()`. That matters: after
filtering `[0 => 'a', 1 => 'b', 2 => 'c']` down to `b` and `c`, their keys are `1` and `2`. Call
`->values()` to renumber them from 0 when you need a list.

## Working with records

Most collections hold records: arrays or objects with named fields. A handful of methods do the
everyday jobs:

- `pluck('title')` takes one field from every record; `pluck('title', 'id')` keys the result by
  another field.
- `keyBy('id')` keys the records themselves by a field.
- `groupBy('author')` makes one group per value of the field.
- `sortBy('price')` sorts by a field, keeping each record's key.

Each is a short loop. Grouping, for example:

```php
$books = [
    ['title' => 'Dune', 'author' => 'Herbert'],
    ['title' => 'Piranesi', 'author' => 'Clarke'],
    ['title' => 'Children of Dune', 'author' => 'Herbert'],
];

$groups = [];
foreach ($books as $book) {
    $groups[$book['author']][] = $book['title'];
}

foreach ($groups as $author => $titles) {
    echo $author . ': ' . implode(', ', $titles) . PHP_EOL;
}
```

## Macros: adding your own methods

You can't edit Laravel's `Collection` class, but you can teach it new methods at runtime with
**macros**. The class uses the `Macroable` trait, which stores closures by name and runs them from
`__call()` when an unknown method is called. Before calling the closure, it **binds** it to the
object, so `$this` inside the macro is the collection:

```laravel
Collection::macro('toUpper', function () {
    return $this->map(fn (string $value) => strtoupper($value));
});

collect(['dune', 'piranesi'])->toUpper(); // ['DUNE', 'PIRANESI']
```

Binding is done with `Closure::bind($closure, $newThis, $scope)`, which returns a copy of the
closure where `$this` is `$newThis` and private members of `$scope` are visible:

```php
class Basket
{
    private array $items = ['Dune', 'Piranesi'];
}

$count = function (): int {
    return count($this->items);
};

$bound = Closure::bind($count, new Basket(), Basket::class);

echo $bound();
```

Many Laravel classes are macroable: `Collection`, `Str`, `Request`, `Response`, `Builder` and more.
Macros are usually registered in a service provider's `boot()` method.

## Higher-order messages

For the very common "call this on every item" case, collections have a shortcut. Accessing a
method name as a *property* returns a proxy, and whatever you do to the proxy is done to each item:

```laravel
$books->map->title;            // same as ->map(fn ($book) => $book->title)
$books->each->markAsSold();    // same as ->each(fn ($book) => $book->markAsSold())
$books->filter->isAvailable(); // same as ->filter(fn ($book) => $book->isAvailable())
```

The proxy is a tiny class with `__get()` and `__call()`. You'll write one.

## Lazy collections

A normal collection holds every item in memory. `LazyCollection` wraps a **generator** instead, so
items are produced one at a time, only when asked for. That lets you work through a huge file or
table with almost no memory, and `take()` can stop an endless source:

```php
function naturals(): Generator
{
    for ($n = 1; ; $n++) {
        yield $n;
    }
}

function lazyMap(iterable $items, Closure $callback): Generator
{
    foreach ($items as $key => $item) {
        yield $key => $callback($item);
    }
}

$output = [];
foreach (lazyMap(naturals(), fn ($n) => $n * $n) as $square) {
    if ($square > 50) {
        break;
    }
    $output[] = $square;
}

echo implode(' ', $output);
```

In Laravel, `Book::cursor()` and `Book::lazy()` return lazy collections over database rows, and
`LazyCollection::make(fn () => yield ...)` wraps any generator.
