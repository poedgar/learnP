## Composing functions

**Composition** joins small functions into a bigger one: the output of one becomes the input of the next. Nested calls
read inside out, which gets hard to follow:

```php
$text = '  The Name of the Wind  ';

echo strtoupper(str_replace(' ', '-', trim($text)));
```

A `pipe()` helper takes the steps in the order they happen and returns one function that runs them all:

```php
function pipe(callable ...$steps): Closure
{
    return fn (mixed $value) => array_reduce($steps, fn (mixed $carry, callable $step) => $step($carry), $value);
}

$slug = pipe(
    trim(...),
    strtolower(...),
    fn (string $s) => str_replace(' ', '-', $s),
);

echo $slug('  The Name of the Wind  ');
```

`pipe()` is a reduce over functions: the carry is the value so far, and each step transforms it.

## The pipe operator in PHP 8.5

PHP 8.5 adds the `|>` operator, which passes the value on its left to the callable on its right. It writes the same
pipeline without a helper, in reading order:

```
$slug = '  The Name of the Wind  '
    |> trim(...)
    |> strtolower(...)
    |> (fn (string $s) => str_replace(' ', '-', $s));
```

Each step must take exactly one argument, which is another reason functional code likes one-argument functions made by
partial application. The examples in this course use `pipe()` so they run on PHP 8.4 too.

## Eager arrays use memory

`array_map()` and `array_filter()` are **eager**: each one builds a whole new array before the next starts. For a large
list that means several full copies, and work done on items you may never need:

```php
$calls = 0;
$numbers = range(1, 1000);

$squares = array_map(function (int $n) use (&$calls): int {
    $calls++;
    return $n * $n;
}, $numbers);

$firstThree = array_slice($squares, 0, 3);

echo implode(', ', $firstThree) . " after $calls calls";
```

It squared a thousand numbers to use three of them.

## Lazy sequences with generators

A **generator** produces values one at a time with `yield`, only when asked. Nothing runs until something loops over
it, and a loop can stop early:

```php
function naturals(): Generator
{
    $n = 1;
    while (true) {
        yield $n++;
    }
}

foreach (naturals() as $n) {
    if ($n > 5) {
        break;
    }
    echo "$n ";
}
```

`naturals()` is infinite, but that's fine: only the values the loop takes are ever made.

## Lazy map, filter and take

Generator versions of map and filter take any `iterable` and yield results as they go, so steps can be chained
without building arrays in between. `take()` stops the chain after enough items:

```php
function lazyFilter(callable $keep, iterable $items): Generator
{
    foreach ($items as $key => $item) {
        if ($keep($item)) {
            yield $key => $item;
        }
    }
}

function take(int $count, iterable $items): Generator
{
    if ($count <= 0) {
        return;
    }
    foreach ($items as $key => $item) {
        yield $key => $item;
        if (--$count === 0) {
            return;
        }
    }
}

$evens = lazyFilter(fn (int $n) => $n % 2 === 0, range(1, 1_000_000));

echo implode(', ', iterator_to_array(take(4, $evens), false));
```

Only the first eight numbers of the million were ever examined. `iterator_to_array($gen, false)` collects a generator
into a list; `false` throws its keys away so they don't clash.

## Reading items as a stream

Generators suit data that arrives as a stream: lines of a file, rows from a database, pages of an API. Each item is
handled and then forgotten, so memory stays flat however long the stream is:

```php
function lines(string $text): Generator
{
    foreach (explode("\n", $text) as $number => $line) {
        yield $number + 1 => $line;
    }
}

$log = "INFO start\nERROR disk full\nINFO retry\nERROR timeout";

foreach (lines($log) as $number => $line) {
    if (str_starts_with($line, 'ERROR')) {
        echo "line $number: $line\n";
    }
}
```

## A fluent collection

Wrapping an iterable in an object lets you chain steps with methods. Each method returns a **new** collection, so the
original is never changed, and nothing runs until the end:

```php
final class Lazy
{
    private function __construct(private Closure $source) {}

    public static function of(iterable $items): self
    {
        return new self(fn () => yield from $items);
    }

    public function map(Closure $f): self
    {
        $source = $this->source;

        return new self(function () use ($source, $f) {
            foreach ($source() as $key => $item) {
                yield $key => $f($item);
            }
        });
    }

    public function toArray(): array
    {
        return iterator_to_array(($this->source)(), false);
    }
}

echo implode(' ', Lazy::of(['dune', 'emma'])->map(ucfirst(...))->toArray());
```

The collection stores a function that **makes** a generator rather than the generator itself, so the same collection
can be looped over more than once. A generator can only be run once.

## Putting it together

A pipeline that reads a stream of orders, keeps the paid ones, works out totals and stops after the first two:

```php
function orders(): Generator
{
    yield ['id' => 1, 'paid' => true, 'items' => [7.99, 12.0]];
    yield ['id' => 2, 'paid' => false, 'items' => [19.99]];
    yield ['id' => 3, 'paid' => true, 'items' => [5.0, 5.0, 2.5]];
    yield ['id' => 4, 'paid' => true, 'items' => [30.0]];
}

function paid(iterable $orders): Generator
{
    foreach ($orders as $order) {
        if ($order['paid']) {
            yield $order;
        }
    }
}

function totals(iterable $orders): Generator
{
    foreach ($orders as $order) {
        yield $order['id'] => array_sum($order['items']);
    }
}

foreach (totals(paid(orders())) as $id => $total) {
    printf("Order %d: £%.2f\n", $id, $total);
    if ($id >= 3) {
        break;
    }
}
```

Next, try the quiz to check your understanding, then solve the challenges.
