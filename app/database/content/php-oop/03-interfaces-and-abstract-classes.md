## Interfaces: a promise

Inheritance says what a class **is**. An **interface** says what a class **can do**. It lists
public methods, with their parameters and return types but without their code. A class that
`implements` the interface promises to provide every one of them:

```php
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

class Book implements Discountable
{
    public function __construct(public string $title, public float $price) {}

    public function discountedPrice(float $percent): float
    {
        return round($this->price * (100 - $percent) / 100, 2);
    }
}

echo (new Book('Dune', 19.99))->discountedPrice(10);
```

If the class breaks its promise, PHP refuses it before any of it runs:

```php
// Error: Book promises to be Discountable but has no discountedPrice().
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

class Book implements Discountable
{
    public function __construct(public float $price) {}
}
```

## Several promises at once

A class has only one parent, but it can implement **as many interfaces as you like**,
separated by commas:

```php
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

interface Describable
{
    public function describe(): string;
}

class Book implements Discountable, Describable
{
    public function __construct(public string $title, public float $price) {}

    public function discountedPrice(float $percent): float
    {
        return round($this->price * (100 - $percent) / 100, 2);
    }

    public function describe(): string
    {
        return $this->title . ', ' . $this->price;
    }
}

$book = new Book('Circe', 13.99);
echo $book->describe() . "\n";
echo $book->discountedPrice(50);
```

## Code that accepts any implementation

The real power is in type hints. A parameter typed with an interface accepts **any** object
whose class implements it, even classes that have nothing else in common:

```php
interface Describable
{
    public function describe(): string;
}

class Book implements Describable
{
    public function __construct(public string $title) {}

    public function describe(): string
    {
        return 'Book: ' . $this->title;
    }
}

class GiftCard implements Describable
{
    public function __construct(public float $amount) {}

    public function describe(): string
    {
        return 'Gift card worth ' . $this->amount;
    }
}

function receiptLine(Describable $item): string
{
    return '- ' . $item->describe();
}

echo receiptLine(new Book('Dune')) . "\n";
echo receiptLine(new GiftCard(25)) . "\n";
```

`receiptLine()` doesn't know or care what the item really is. It only relies on the promise.

## Polymorphism

Put different objects that keep the same promise in one list, and the same call does the
right thing for each. This is called **polymorphism**, "many shapes":

```php
interface Shippable
{
    public function weightKg(): float;
}

class Book implements Shippable
{
    public function __construct(public int $pages) {}

    public function weightKg(): float
    {
        return $this->pages / 1000;
    }
}

class Mug implements Shippable
{
    public function weightKg(): float
    {
        return 0.35;
    }
}

$parcel = [new Book(612), new Book(304), new Mug()];

$total = 0;
foreach ($parcel as $item) {
    $total += $item->weightKg();
}

echo 'Parcel weighs ' . $total . ' kg';
```

Adding a new kind of shippable thing later needs no change to the loop.

## Abstract classes

Sometimes the members of a family share most of their code and differ in one or two details.
An **abstract class** is a parent that can't be created on its own and can leave some methods
**abstract**, with no body, for each child to fill in:

```php
abstract class Product
{
    public function __construct(public string $name, protected float $price) {}

    abstract public function vatRate(): float;

    public function priceWithVat(): float
    {
        return round($this->price * (1 + $this->vatRate()), 2);
    }
}

class Book extends Product
{
    public function vatRate(): float
    {
        return 0.0;
    }
}

class Mug extends Product
{
    public function vatRate(): float
    {
        return 0.2;
    }
}

echo (new Book('Dune', 10))->priceWithVat() . "\n";
echo (new Mug('Logo mug', 10))->priceWithVat() . "\n";
```

`priceWithVat()` is written once, in `Product`, and calls `vatRate()`, which each child
provides. A plain `Product` makes no sense on its own, and PHP won't create one:

```php
// Error: Product is abstract, so it can't be created with new.
abstract class Product
{
    abstract public function vatRate(): float;
}

$product = new Product();
```

A child that doesn't fill in every abstract method must be abstract itself.

## Interface or abstract class?

- Use an **interface** to describe something a class *can do*, especially when unrelated
  classes should be able to do it (books, mugs and gift cards can all be `Describable`).
- Use an **abstract class** when closely related classes share real code and data, and only
  some details differ.

The two work well together: an abstract class can implement an interface, leaving some of its
methods for the children.

## Built-in interfaces: Countable and Stringable

PHP comes with interfaces of its own. Implement `Countable`, with its `count(): int` method,
and PHP's own `count()` function works on your objects:

```php
class Order implements Countable
{
    private array $lines = [];

    public function add(string $title): void
    {
        $this->lines[] = $title;
    }

    public function count(): int
    {
        return count($this->lines);
    }
}

$order = new Order();
$order->add('Dune');
$order->add('Circe');

echo count($order);
```

Give a class a `__toString(): string` method and its objects can be used as text, in `echo`
or joined with `.`. PHP then counts the class as implementing `Stringable` automatically:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function __toString(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

$book = new Book('Dune', 'Frank Herbert');

echo 'You ordered ' . $book . "\n";
var_dump($book instanceof Stringable);
```

Next, try the quiz to check your understanding, then solve the challenges.
