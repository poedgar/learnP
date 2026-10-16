## Class constants

Some values belong to a class and never change, like the VAT rate on books. Declare them as
**class constants** with `const`. Read one with the class name and `::`, or with `self::`
inside the class:

```php
class Book
{
    public const VAT_RATE = 0.0;
    public const MAX_TITLE_LENGTH = 120;

    public function __construct(public string $title, public float $price) {}

    public function priceWithVat(): float
    {
        return round($this->price * (1 + self::VAT_RATE), 2);
    }
}

echo Book::MAX_TITLE_LENGTH . "\n";
echo (new Book('Dune', 19.99))->priceWithVat();
```

Constant names are usually written in `UPPER_SNAKE_CASE`. Trying to change one is an error
before the script even runs:

```php
// Error: a constant can't be assigned a new value.
class Book
{
    public const VAT_RATE = 0.0;
}

Book::VAT_RATE = 0.2;
```

## Static properties and methods

Normal properties belong to each **object**: every book has its own title. A **static**
property belongs to the **class** itself, so there is only one, shared by everyone. Static
methods are called on the class too, without an object:

```php
class Book
{
    private static int $created = 0;

    public function __construct(public string $title)
    {
        self::$created++;
    }

    public static function created(): int
    {
        return self::$created;
    }
}

new Book('Dune');
new Book('Circe');
new Book('Beloved');

echo Book::created() . ' books created';
```

Inside a static method there is no `$this`, because it isn't called on an object.

## Watch out for shared state

Because a static property is shared, every part of a program that touches it affects every
other part. Two separate shopping baskets that kept their items in a static property would
end up with each other's books:

```php
class Basket
{
    private static array $items = [];

    public function add(string $title): void
    {
        self::$items[] = $title;
    }

    public function count(): int
    {
        return count(self::$items);
    }
}

$mine = new Basket();
$yours = new Basket();

$mine->add('Dune');
$yours->add('Circe');

echo 'My basket has ' . $mine->count() . " items\n";
```

It prints 2, though only one book went into "my" basket. Keep per-object data in normal
properties. Use static ones only for things that truly belong to the class as a whole, like a
counter or a cache, and even then, sparingly.

## `self::` and `static::`

Inside a class, `self::` means "the class this code is written in". `static::` means "the
class the method was actually called on", which can be a child. The difference shows when a
child overrides something:

```php
class Book
{
    public static function kind(): string
    {
        return 'book';
    }

    public static function withSelf(): string
    {
        return self::kind();
    }

    public static function withStatic(): string
    {
        return static::kind();
    }
}

class EBook extends Book
{
    public static function kind(): string
    {
        return 'e-book';
    }
}

echo EBook::withSelf() . "\n";
echo EBook::withStatic() . "\n";
```

`self::kind()` is always `Book`'s version; `static::kind()` picked `EBook`'s.

## Named constructors

A class has one `__construct()`, but objects often arrive in different shapes: a row from a
database, an array from a form. A **named constructor** is a static method that builds an
object from one such shape, with a name that says what it expects:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public static function fromArray(array $data): static
    {
        return new static($data['title'], $data['author']);
    }

    public static function fromCsvLine(string $line): static
    {
        [$title, $author] = explode(',', $line);

        return new static(trim($title), trim($author));
    }
}

$a = Book::fromArray(['title' => 'Dune', 'author' => 'Frank Herbert']);
$b = Book::fromCsvLine('Circe, Madeline Miller');

echo $a->title . ' / ' . $b->author;
```

`new static(…)` creates an object of whichever class the method was called on, so
`EBook::fromArray(…)` would build an `EBook`.

## Enums

Some values can only be one of a fixed set: a genre is Fantasy, Horror, Romance or one of a
few others. A string would let typos like `'Fantsy'` slip in. An **enum** lists the allowed
values as **cases**:

```php
enum Genre
{
    case Fantasy;
    case Horror;
    case Romance;
    case ScienceFiction;
}

class Book
{
    public function __construct(public string $title, public Genre $genre) {}
}

$book = new Book('Dune', Genre::ScienceFiction);

var_dump($book->genre === Genre::ScienceFiction);
echo $book->genre->name . "\n";
echo count(Genre::cases()) . ' genres';
```

A parameter typed `Genre` accepts only those four cases. Each case is a single object, so
`===` compares them, `->name` gives the case's name, and `Genre::cases()` lists them all.

## Backed enums

To store an enum in a database or read one from a form, each case needs a plain value. A
**backed enum** gives every case a string (or int) value:

```php
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
    case EBook = 'ebook';
}

echo Format::Paperback->value . "\n";

$format = Format::from('ebook');
echo $format->name . "\n";

var_dump(Format::tryFrom('scroll'));
```

`->value` gives the stored value. `from()` turns a value back into a case, and `tryFrom()`
does the same but gives `null` for a value that isn't one. `from()` with an unknown value is
an error:

```php
// Error: 'scroll' isn't a valid Format.
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
}

$format = Format::from('scroll');
```

## `match`, and methods on enums

`match` compares a value with each arm, using `===`, and gives the result of the first arm
that matches. One arm can list several values, and `default` catches anything else:

```php
function deliveryDays(string $speed): int
{
    return match ($speed) {
        'standard' => 3,
        'express', 'priority' => 2,
        'next-day' => 1,
        default => 5,
    };
}

echo deliveryDays('express') . "\n";
echo deliveryDays('pigeon') . "\n";
```

Without a `default`, a value that matches no arm throws an `UnhandledMatchError`.

`match` and enums go well together. Enums can have methods; inside one, `$this` is the case,
and a `match` with an arm for every case picks a result for each:

```php
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
    case EBook = 'ebook';

    public function label(): string
    {
        return match ($this) {
            self::Hardback => 'Hardback edition',
            self::Paperback => 'Paperback edition',
            self::EBook => 'E-book (instant download)',
        };
    }

    public function isPhysical(): bool
    {
        return $this !== self::EBook;
    }
}

foreach (Format::cases() as $format) {
    echo $format->label() . ($format->isPhysical() ? ', ships by post' : '') . "\n";
}
```

## Enums that implement interfaces

An enum can implement an interface, just like a class. Code that asks for the interface then
accepts enum cases and objects alike. Enums can also hold constants:

```php
interface HasColour
{
    public function colour(): string;
}

enum Status: string implements HasColour
{
    case Draft = 'draft';
    case Published = 'published';

    const DEFAULT = self::Draft;

    public function colour(): string
    {
        return match ($this) {
            self::Draft => 'grey',
            self::Published => 'green',
        };
    }
}

function badge(HasColour $item): string
{
    return "[{$item->colour()}]";
}

echo badge(Status::Published) . ' ' . badge(Status::DEFAULT) . "\n";
var_dump(Status::Draft instanceof HasColour);
```

Next, try the quiz to check your understanding, then solve the challenges.
