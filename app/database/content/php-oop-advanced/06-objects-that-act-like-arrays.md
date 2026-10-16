## Why not just use an array?

Arrays are quick to write, but anything can go in them, and nothing stops a typo or a wrong
type. A **collection class** wraps an array so it can enforce rules and offer useful methods,
while still feeling like an array to the code that uses it. PHP has a few built-in interfaces
for that feel: square brackets, `count()`, `foreach` and `json_encode()`.

## Square brackets: `ArrayAccess`

`ArrayAccess` has four methods, one for each thing you can do with brackets:

```php
class Prices implements ArrayAccess
{
    private array $prices = [];

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->prices[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->prices[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->prices[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->prices[$offset]);
    }
}

$prices = new Prices();
$prices['Dune'] = 19.99;
echo $prices['Dune'] . "\n";
var_dump(isset($prices['Emma']));
unset($prices['Dune']);
var_dump(isset($prices['Dune']));
```

Reading calls `offsetGet()`, writing `offsetSet()`, `isset()` and `empty()` call `offsetExists()`,
and `unset()` calls `offsetUnset()`.

## Adding to the end: `$list[]`

Writing `$list[] = $value`, with empty brackets, calls `offsetSet()` with `null` as the offset.
Treat `null` as "add to the end":

```php
class TitleList implements ArrayAccess
{
    private array $titles = [];

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->titles[] = $value;
        } else {
            $this->titles[$offset] = $value;
        }
    }

    public function offsetGet(mixed $offset): mixed { return $this->titles[$offset] ?? null; }
    public function offsetExists(mixed $offset): bool { return isset($this->titles[$offset]); }
    public function offsetUnset(mixed $offset): void { unset($this->titles[$offset]); }
}

$titles = new TitleList();
$titles[] = 'Dune';
$titles[] = 'Emma';
echo $titles[1];
```

## Rules that arrays can't keep

Because every write goes through `offsetSet()`, the collection can refuse bad values. A list
that only accepts prices above zero:

```php
class PriceList implements ArrayAccess
{
    private array $prices = [];

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! is_float($value) || $value <= 0) {
            throw new InvalidArgumentException('A price must be a float above zero.');
        }
        $offset === null ? $this->prices[] = $value : $this->prices[$offset] = $value;
    }

    public function offsetGet(mixed $offset): mixed { return $this->prices[$offset] ?? null; }
    public function offsetExists(mixed $offset): bool { return isset($this->prices[$offset]); }
    public function offsetUnset(mixed $offset): void { unset($this->prices[$offset]); }
}

$prices = new PriceList();
$prices[] = 9.99;

try {
    $prices[] = -5.0;
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

## `count()` and `foreach`

`Countable` (from the OOP in PHP course) makes `count($object)` work, and `IteratorAggregate`
(from the last lesson) makes `foreach` work. Together with `ArrayAccess`, a collection can be used
almost everywhere an array can:

```php
class Shelf implements ArrayAccess, Countable, IteratorAggregate
{
    private array $titles = [];

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset === null ? $this->titles[] = $value : $this->titles[$offset] = $value;
    }

    public function offsetGet(mixed $offset): mixed { return $this->titles[$offset] ?? null; }
    public function offsetExists(mixed $offset): bool { return isset($this->titles[$offset]); }
    public function offsetUnset(mixed $offset): void { unset($this->titles[$offset]); }

    public function count(): int
    {
        return count($this->titles);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->titles);
    }
}

$shelf = new Shelf();
$shelf[] = 'The Hobbit';
$shelf[] = 'Circe';

echo count($shelf) . " books:\n";
foreach ($shelf as $title) {
    echo "- $title\n";
}
```

## What objects still can't do

PHP's array functions, such as `array_keys()`, `sort()` and `in_array()`, only accept real arrays.
Passing a collection object is an error:

```php
// Error: array functions need a real array, not an object that looks like one
class Shelf implements IteratorAggregate
{
    public function getIterator(): Iterator
    {
        return new ArrayIterator(['Dune', 'Emma']);
    }
}

print_r(array_reverse(new Shelf()));
```

Give the collection its own methods for what it needs (`sorted()`, `contains()`), or turn it into an
array first with `iterator_to_array()`.

## Shaping JSON: `JsonSerializable`

`json_encode()` on an ordinary object includes only its public properties. A class that implements
`JsonSerializable` decides exactly what goes out, through `jsonSerialize()`:

```php
class Book implements JsonSerializable
{
    public function __construct(
        private string $title,
        private float $price,
        private float $costToShop,
    ) {}

    public function jsonSerialize(): array
    {
        return ['title' => $this->title, 'price' => $this->price];
    }
}

echo json_encode(new Book('Dune', 19.99, 8.20));
```

The shop's cost stays out of the API. `jsonSerialize()` can return an array, a string, a number, or
other objects that are themselves `JsonSerializable`.

## Putting it together

A typed collection of books that can be counted, looped over, indexed and turned into JSON:

```php
class Book implements JsonSerializable
{
    public function __construct(public string $title, public float $price) {}

    public function jsonSerialize(): array
    {
        return ['title' => $this->title, 'price' => $this->price];
    }
}

class BookList implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    private array $books = [];

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $value instanceof Book) {
            throw new InvalidArgumentException('Only books can go in a BookList.');
        }
        $offset === null ? $this->books[] = $value : $this->books[$offset] = $value;
    }

    public function offsetGet(mixed $offset): mixed { return $this->books[$offset] ?? null; }
    public function offsetExists(mixed $offset): bool { return isset($this->books[$offset]); }
    public function offsetUnset(mixed $offset): void { unset($this->books[$offset]); }
    public function count(): int { return count($this->books); }
    public function getIterator(): Iterator { return new ArrayIterator($this->books); }
    public function jsonSerialize(): array { return array_values($this->books); }
}

$books = new BookList();
$books[] = new Book('Dune', 19.99);
$books[] = new Book('Emma', 7.99);

echo count($books) . ' books: ' . json_encode($books);
```

Next, try the quiz to check your understanding, then solve the challenges.
