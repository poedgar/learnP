## Sharing code without a parent: traits

Books, customers and events all need a web-address-friendly version of their name, a
**slug**: `the-name-of-the-wind`. They aren't kinds of the same thing, so a shared parent
class would be wrong. A **trait** is a bundle of methods (and properties) that any class can
pull in with `use`, as if they had been written inside it:

```php
trait HasSlug
{
    public function slug(): string
    {
        return strtolower(str_replace(' ', '-', $this->name));
    }
}

class Book
{
    use HasSlug;

    public function __construct(public string $name) {}
}

class Event
{
    use HasSlug;

    public function __construct(public string $name, public string $date) {}
}

echo (new Book('The Name of the Wind'))->slug() . "\n";
echo (new Event('Poetry Night', '2026-05-01'))->slug() . "\n";
```

Inside the trait, `$this` is whichever object the method was called on. `HasSlug` simply
assumes the class has a `$name` property.

## Traits with properties

A trait can bring properties as well as methods. Each class that uses it gets its own copy of
them, just as if they'd been declared in the class:

```php
trait CountsViews
{
    private int $views = 0;

    public function view(): void
    {
        $this->views++;
    }

    public function views(): int
    {
        return $this->views;
    }
}

class Book
{
    use CountsViews;
}

class Author
{
    use CountsViews;
}

$book = new Book();
$book->view();
$book->view();

$author = new Author();
$author->view();

echo $book->views() . ' and ' . $author->views();
```

`class_uses($book)` lists the traits a class uses, and `instanceof` doesn't work with traits:
a trait isn't a type, only a way of sharing code.

## Using several traits, and conflicts

A class can use several traits, separated by commas. If two of them have a method with the
same name, PHP can't guess which one you mean and refuses the class:

```php
// Error: both traits have a greet() method, and PHP won't choose between them.
trait Friendly
{
    public function greet(): string
    {
        return 'Hello!';
    }
}

trait Formal
{
    public function greet(): string
    {
        return 'Good afternoon.';
    }
}

class ShopAssistant
{
    use Friendly, Formal;
}
```

Settle it in a block after `use`. `insteadof` picks the winner, and `as` keeps the loser
under another name:

```php
trait Friendly
{
    public function greet(): string
    {
        return 'Hello!';
    }
}

trait Formal
{
    public function greet(): string
    {
        return 'Good afternoon.';
    }
}

class ShopAssistant
{
    use Friendly, Formal {
        Formal::greet insteadof Friendly;
        Friendly::greet as greetRegular;
    }
}

$assistant = new ShopAssistant();

echo $assistant->greet() . "\n";
echo $assistant->greetRegular() . "\n";
```

## Readonly properties

Some values should never change once an object exists: a book's ISBN, an order's number. Mark
a typed property `readonly` and it can be set **once**, from inside the class (usually in the
constructor). After that, any attempt to change it is an error:

```php
// Error: $number is readonly, so it can't be changed after the constructor sets it.
class Order
{
    public function __construct(public readonly int $number) {}
}

$order = new Order(1042);
echo $order->number . "\n";

$order->number = 1043;
```

Reading a readonly property is fine; that's why `$number` can still be public. Readonly
properties must have a type.

## Readonly classes

When **every** property should be readonly, mark the whole class `readonly` instead of
repeating the word:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postcode,
    ) {}
}

$address = new Address('12 Market Street', 'Leeds', 'LS1 6DT');
echo $address->city;
```

## Changing an object that can't change

If an address can't change, how does a customer move house? You don't change the object; you
make a **new** one with the difference, and leave the original alone. By convention such
methods start with `with`, and are called **withers**:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}

    public function withCity(string $city): static
    {
        return new static($this->street, $city);
    }
}

$old = new Address('12 Market Street', 'Leeds');
$new = $old->withCity('York');

echo $old->city . "\n";
echo $new->city . "\n";
```

Objects that never change are easy to trust: you can hand one to any code, knowing nothing can
alter it behind your back.

## Value objects

Small immutable objects like `Address` are called **value objects**. What matters about them
is their value, not which object you hold: two addresses with the same street and city are
the same address. PHP's `==` compares two objects' properties, but it's clearer to give the
class an `equals()` method, where you decide what "the same" means:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}

    public function equals(Address $other): bool
    {
        return strtolower($this->street) === strtolower($other->street)
            && strtolower($this->city) === strtolower($other->city);
    }
}

$a = new Address('12 Market Street', 'Leeds');
$b = new Address('12 market street', 'LEEDS');

var_dump($a === $b);
var_dump($a->equals($b));
```

`===` asks "is this the very same object?", which it isn't. `equals()` asks the question that
matters.

Next, try the quiz to check your understanding, then solve the challenges.
