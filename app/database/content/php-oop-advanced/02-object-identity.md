## Variables hold handles, not objects

When you assign an object to a second variable, or pass it to a function, PHP doesn't copy
the object. Both variables hold a **handle** to the same object, so a change made through one
is seen through the other:

```php
class Cart
{
    public array $items = [];
}

$mine = new Cart();
$yours = $mine;
$yours->items[] = 'Dune';

echo count($mine->items);
```

Arrays and strings work the other way: assigning one copies it. That difference catches out
everyone at least once.

## Copying an object: `clone`

`clone` makes a new object with the same property values. Changing the copy leaves the original
alone:

```php
class Cart
{
    public array $items = [];
}

$mine = new Cart();
$mine->items[] = 'Dune';

$yours = clone $mine;
$yours->items[] = 'Emma';

echo count($mine->items) . ' and ' . count($yours->items);
```

The array was copied along with the object, because arrays are copied on assignment.

## Shallow copies

`clone` is **shallow**: properties that hold objects are copied as handles, so the original and
the clone share those inner objects. Here both orders end up sharing one address:

```php
class Address
{
    public function __construct(public string $city) {}
}

class Order
{
    public function __construct(public Address $address) {}
}

$first = new Order(new Address('Leeds'));
$second = clone $first;
$second->address->city = 'York';

echo $first->address->city;
```

## Deep copies with `__clone`

If a class has a `__clone()` method, PHP calls it on the **new** object, straight after copying.
That's the place to clone the inner objects too, making a **deep** copy:

```php
class Address
{
    public function __construct(public string $city) {}
}

class Order
{
    public function __construct(public Address $address) {}

    public function __clone(): void
    {
        $this->address = clone $this->address;
    }
}

$first = new Order(new Address('Leeds'));
$second = clone $first;
$second->address->city = 'York';

echo $first->address->city . ' / ' . $second->address->city;
```

Inside `__clone()`, `$this` is the copy. `__clone()` can also reset things that shouldn't be
copied, such as an id. For an object that can't change at all (a readonly value object), there's
nothing to protect, so sharing it is fine.

## Same object, or equal objects?

`===` on objects asks "are these the **same** object?". `==` asks "are they of the same class,
with equal properties?":

```php
class Isbn
{
    public function __construct(public string $value) {}
}

$a = new Isbn('9780441013593');
$b = new Isbn('9780441013593');
$c = $a;

var_dump($a === $b, $a == $b, $a === $c);
```

`spl_object_id($object)` gives a number that's unique to each object while it exists, which is
handy for telling objects apart when debugging.

## Comparing and sorting objects

`==` compares every property, which is often not what you mean: two books might be "the same
book" when their ISBNs match, whatever their prices. Say what you mean with a method. For
sorting, a `compareTo()` method that returns a negative number, 0 or a positive number fits the
spaceship operator `<=>` and `usort()`:

```php
class Book
{
    public function __construct(public string $title, public float $price) {}

    public function compareTo(Book $other): int
    {
        return $this->price <=> $other->price;
    }
}

$books = [new Book('Dune', 19.99), new Book('Emma', 7.99), new Book('Circe', 13.99)];
usort($books, fn (Book $a, Book $b) => $a->compareTo($b));

echo implode(', ', array_map(fn (Book $book) => $book->title, $books));
```

`$a <=> $b` is -1 when `$a` is smaller, 0 when equal and 1 when larger.

## Remembering things about objects: `WeakMap`

Sometimes you want to store something **about** an object without adding a property to it, such
as a cached price for each cart. A `WeakMap` uses objects as keys. It's **weak** because it
doesn't keep its keys alive: once nothing else refers to an object, its entry disappears, so the
cache can't grow forever.

```php
class Cart {}

$totals = new WeakMap();

$cart = new Cart();
$totals[$cart] = 42.50;
echo count($totals) . "\n";

unset($cart);
echo count($totals);
```

An ordinary array can't use objects as keys at all, and keying it by `spl_object_id()` would keep
stale entries for objects that no longer exist.

## Putting it together

An order that clones its address deeply, compares by its reference rather than every property,
and is remembered in a `WeakMap`:

```php
class Address
{
    public function __construct(public string $city) {}
}

class Order
{
    public function __construct(public string $reference, public Address $address) {}

    public function __clone(): void
    {
        $this->address = clone $this->address;
    }

    public function sameOrderAs(Order $other): bool
    {
        return $this->reference === $other->reference;
    }
}

$order = new Order('A-1', new Address('Leeds'));
$copy = clone $order;
$copy->address->city = 'York';

$seen = new WeakMap();
$seen[$order] = true;

var_dump($order->sameOrderAs($copy), $order === $copy, isset($seen[$copy]));
```

Next, try the quiz to check your understanding, then solve the challenges.
