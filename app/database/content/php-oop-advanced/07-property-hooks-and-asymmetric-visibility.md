## Getters and setters, the old way

To check a value before storing it, classes used to make the property private and add two
methods. Every price needs `getPrice()` and `setPrice()`, and code using the class has to call
them instead of simply reading and writing `$book->price`:

```php
class Book
{
    private float $price = 0.0;

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new InvalidArgumentException('A price can\'t be negative.');
        }
        $this->price = $price;
    }
}

$book = new Book();
$book->setPrice(9.99);
echo $book->getPrice();
```

PHP 8.4 added **property hooks**: code attached to the property itself, which runs when it's read
or written. Code using the class just reads and writes the property.

## A `set` hook

A `set` hook runs whenever the property is assigned, with the new value in `$value`. Inside the hook,
`$this->price` is the stored value itself, so assigning to it stores the value:

```php
class Book
{
    public float $price = 0.0 {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('A price can\'t be negative.');
            }
            $this->price = $value;
        }
    }
}

$book = new Book();
$book->price = 9.99;
echo $book->price . "\n";

try {
    $book->price = -1;
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

If a hook is a single expression, `set => …` stores whatever the expression gives:

```php
class Book
{
    public string $title {
        set => trim($value);
    }
}

$book = new Book();
$book->title = '   Dune  ';
echo "[{$book->title}]";
```

## A `get` hook

A `get` hook runs whenever the property is read, and its result is what the reader sees. It can
change how the stored value comes out:

```php
class Author
{
    public string $name {
        get => ucwords($this->name);
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

echo (new Author('ursula k. le guin'))->name;
```

## Virtual properties

A property whose hooks never mention its own stored value doesn't store anything at all. It's a
**virtual** property, worked out from other properties every time it's read. With only a `get`
hook, it can't be written:

```php
class Author
{
    public string $fullName {
        get => "{$this->firstName} {$this->lastName}";
    }

    public function __construct(public string $firstName, public string $lastName) {}
}

$author = new Author('Toni', 'Morrison');
echo $author->fullName . "\n";

$author->lastName = 'Morrison (Nobel 1993)';
echo $author->fullName;
```

```php
// Error: a virtual property with only a get hook can't be written
class Author
{
    public string $fullName {
        get => 'Toni Morrison';
    }
}

$author = new Author();
$author->fullName = 'Someone else';
```

## Asymmetric visibility: read anywhere, change inside

Often a property should be readable by everyone but changeable only by the class, like a bank
balance that only `deposit()` may change. Before PHP 8.4 that meant a private property and a
getter. Now a property can have a different visibility for writing, written after the normal one:

```php
class Account
{
    public private(set) float $balance = 0.0;

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
    }
}

$account = new Account();
$account->deposit(25.0);
echo $account->balance . "\n";

try {
    $account->balance = 1000000.0;
} catch (Error $e) {
    echo $e->getMessage();
}
```

`private(set)` lets only this class write it; `protected(set)` lets child classes write it too.

## Asymmetric visibility or readonly?

They look alike, but they're different promises. A `readonly` property can be set **once** and never
changes again, even inside the class. A `public private(set)` property can change as often as the
class likes, but only the class can change it:

```php
class Order
{
    public function __construct(
        public readonly string $reference,
        public private(set) string $status = 'new',
    ) {}

    public function ship(): void
    {
        $this->status = 'shipped';
    }
}

$order = new Order('A-1');
$order->ship();
echo "{$order->reference}: {$order->status}";
```

Use `readonly` for identity and values that never change, and asymmetric visibility for state the
object manages itself.

## Hooks in interfaces

An interface can now require a **property**, saying whether it must be readable (`get`), writable
(`set`) or both. A class can meet it with an ordinary property or a hooked one:

```php
interface Priced
{
    public float $price { get; }
}

class Book implements Priced
{
    public function __construct(public float $price) {}
}

class Bundle implements Priced
{
    public float $price {
        get => array_sum(array_map(fn (Priced $item) => $item->price, $this->items)) * 0.9;
    }

    public function __construct(private array $items) {}
}

$bundle = new Bundle([new Book(10.0), new Book(20.0)]);
echo $bundle->price;
```

## Putting it together

A product that tidies its name, refuses bad stock levels, works out a label, and lets only itself
change its sales count:

```php
class Product
{
    public string $name {
        set => ucfirst(trim($value));
    }

    public int $stock = 0 {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Stock can\'t go below zero.');
            }
            $this->stock = $value;
        }
    }

    public string $label {
        get => $this->stock > 0 ? "{$this->name} ({$this->stock} left)" : "{$this->name} (sold out)";
    }

    public private(set) int $sold = 0;

    public function sell(): void
    {
        $this->stock--;
        $this->sold++;
    }
}

$dune = new Product();
$dune->name = '  dune ';
$dune->stock = 2;
$dune->sell();
echo $dune->label . ', sold ' . $dune->sold;
```

Next, try the quiz to check your understanding, then solve the challenges.
