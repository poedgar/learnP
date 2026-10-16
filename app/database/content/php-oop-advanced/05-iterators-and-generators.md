## Looping over an object

`foreach` works on arrays, but it can also loop over an object, if the object says how. By
default it walks the object's **public** properties, which is rarely what you want:

```php
class Shelf
{
    public string $name = 'Fantasy';
    private array $books = ['The Hobbit', 'Circe'];
}

foreach (new Shelf() as $property => $value) {
    echo "$property: $value\n";
}
```

The private `$books` isn't visited at all. To make `foreach` go through the books, the class
implements one of PHP's built-in interfaces.

## The easy way: `IteratorAggregate`

`IteratorAggregate` has a single method, `getIterator()`, which returns something `foreach` can
walk. For a class holding an array, an `ArrayIterator` around that array is all it takes:

```php
class Shelf implements IteratorAggregate
{
    public function __construct(private array $books) {}

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->books);
    }
}

foreach (new Shelf(['The Hobbit', 'Circe']) as $position => $title) {
    echo "$position: $title\n";
}
```

The shelf keeps its array private, yet can be looped over like one. Anything `foreach` accepts is
called **iterable**, and `iterable` is also a type you can use in parameters.

## Doing it by hand: `Iterator`

`Iterator` gives full control with five methods: `rewind()` starts again, `valid()` says whether
there's a current item, `current()` and `key()` return it and its key, and `next()` moves on. A
countdown that never stores its numbers:

```php
class Countdown implements Iterator
{
    private int $number;

    public function __construct(private int $from) {}

    public function rewind(): void { $this->number = $this->from; }
    public function valid(): bool { return $this->number > 0; }
    public function current(): int { return $this->number; }
    public function key(): int { return $this->from - $this->number; }
    public function next(): void { $this->number--; }
}

foreach (new Countdown(3) as $step => $number) {
    echo "$step: $number\n";
}
```

It works, but five methods is a lot for a loop. Generators make this much shorter.

## Generators: `yield`

A function containing `yield` is a **generator**. Calling it doesn't run it; it returns a
`Generator` object. Each time `foreach` asks for the next value, the function runs until the next
`yield`, hands over that value, and pauses there:

```php
function countdown(int $from): Generator
{
    for ($number = $from; $number > 0; $number--) {
        yield $number;
    }
}

foreach (countdown(3) as $number) {
    echo "$number\n";
}
echo "Lift off!";
```

The same countdown in four lines. A `Generator` is an `Iterator`, so `getIterator()` can simply be a
generator method too.

## Keys, and values on demand

`yield $key => $value` chooses the keys. And because a generator only works out each value when
it's asked for, it can describe sequences far too big to store, or even endless ones, as long as
the loop stops:

```php
function pages(): Generator
{
    $page = 1;
    while (true) {
        yield "page-$page" => "Results for page $page";
        $page++;
    }
}

foreach (pages() as $key => $results) {
    echo "$key: $results\n";
    if ($key === 'page-3') {
        break;
    }
}
```

`iterator_to_array($generator)` collects a (finite) generator into an array. Pass `false` as the
second argument to number the values from 0 instead of keeping their keys.

## Lazy pipelines

Generators can take other iterables and pass on some or all of their values, changed or not. Each
step only does its work when the end of the chain asks, so nothing is built up in between:

```php
function onlyCheap(iterable $books): Generator
{
    foreach ($books as $title => $price) {
        if ($price < 10) {
            yield $title => $price;
        }
    }
}

function withTax(iterable $books): Generator
{
    foreach ($books as $title => $price) {
        yield $title => round($price * 1.2, 2);
    }
}

$books = ['Emma' => 7.99, 'Dune' => 19.99, 'Dracula' => 7.50];

foreach (withTax(onlyCheap($books)) as $title => $price) {
    echo "$title: $price\n";
}
```

That's how large files or database results can be processed one row at a time.

## Handing over to another generator: `yield from`

`yield from` passes on every value of another iterable, as if they were yielded one by one. It's
handy for joining sequences, or for walking things inside things:

```php
function allTitles(): Generator
{
    yield 'Dune';
    yield from ['Emma', 'Circe'];
    yield 'Beloved';
}

echo implode(', ', iterator_to_array(allTitles(), false));
```

Without `false`, the keys from the array (0 and 1) would overwrite earlier ones.

## Putting it together

A library whose `getIterator()` is a generator walking every shelf in turn:

```php
class Library implements IteratorAggregate
{
    public function __construct(private array $shelves) {}

    public function getIterator(): Generator
    {
        foreach ($this->shelves as $shelf => $titles) {
            foreach ($titles as $title) {
                yield $title => $shelf;
            }
        }
    }
}

$library = new Library([
    'Fantasy' => ['The Hobbit', 'Circe'],
    'Horror' => ['Dracula'],
]);

foreach ($library as $title => $shelf) {
    echo "$title is on the $shelf shelf\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
