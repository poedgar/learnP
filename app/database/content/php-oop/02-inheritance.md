## Kinds of things: `extends`

An e-book is a book: it has a title, an author and a price, and it can describe itself. It
also has something ordinary books don't, a file size. Rather than copying `Book` and adding
to it, say that `EBook` **extends** `Book`:

```php
class Book
{
    public function __construct(
        public string $title,
        public string $author,
        public float $price,
    ) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class EBook extends Book
{
    public int $fileSizeMb = 0;
}

$ebook = new EBook('Dune', 'Frank Herbert', 6.99);
$ebook->fileSizeMb = 3;

echo $ebook->describe() . "\n";
echo $ebook->fileSizeMb . " MB\n";
```

`EBook` is the **child class** (or subclass), and `Book` is its **parent** (or superclass).
The child **inherits** the parent's properties and methods, including its constructor, so
`new EBook(…)` takes the same arguments as `new Book(…)`. Then it adds its own.

## Overriding a method

A child can replace an inherited method by declaring one with the same name. This is called
**overriding**:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class EBook extends Book
{
    public function describe(): string
    {
        return $this->title . ' by ' . $this->author . ' (e-book)';
    }
}

echo (new Book('Dune', 'Frank Herbert'))->describe() . "\n";
echo (new EBook('Dune', 'Frank Herbert'))->describe() . "\n";
```

Each object uses the version from its own class. The overriding method must be
**compatible** with the one it replaces: it accepts the same arguments and returns the same
kind of value. Otherwise PHP refuses the class:

```php
// Error: describe() in EBook must be compatible with describe() in Book.
class Book
{
    public function describe(): string
    {
        return 'a book';
    }
}

class EBook extends Book
{
    public function describe(int $width): string
    {
        return 'an e-book';
    }
}
```

## Calling the parent's version: `parent::`

Often the child wants to do what the parent does, *plus a little more*. Copying the parent's
code works until the parent changes. Instead, call the parent's version with `parent::`:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class SignedBook extends Book
{
    public function describe(): string
    {
        return parent::describe() . ', signed by the author';
    }
}

echo (new SignedBook('Circe', 'Madeline Miller'))->describe();
```

The same goes for constructors. If a child declares its own constructor, the parent's one
**doesn't run** unless the child calls it. An audiobook needs a narrator as well:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}
}

class AudioBook extends Book
{
    public function __construct(string $title, string $author, public string $narrator)
    {
        parent::__construct($title, $author);
    }
}

$audiobook = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
echo $audiobook->title . ', read by ' . $audiobook->narrator;
```

## Protected: for the family only

You've met `public` (anyone can use it) and `private` (only the class itself). Inheritance
adds a third: `protected` means **the class and its children**, but nobody outside.

```php
class Book
{
    public function __construct(public string $title, protected float $price) {}

    public function getPrice(): float
    {
        return $this->price;
    }
}

class SecondHandBook extends Book
{
    public function markDown(): void
    {
        $this->price = $this->price / 2;
    }
}

$book = new SecondHandBook('Dracula', 7.99);
$book->markDown();
echo $book->getPrice();
```

`SecondHandBook` can change `$price` because it's protected. Code outside the classes can't:

```php
// Error: $price is protected, so code outside the class can't read it.
class Book
{
    public function __construct(protected float $price) {}
}

$book = new Book(7.99);
echo $book->price;
```

Had `$price` been `private`, not even `SecondHandBook` could see it. A good rule: start with
`private`, and loosen it to `protected` only when a child class really needs it.

## Final: no further changes

Mark a method `final` and no child may override it. Mark a class `final` and no class may
extend it:

```php
// Error: Isbn is final, so it can't be extended.
final class Isbn
{
    public function __construct(public string $value) {}
}

class FancyIsbn extends Isbn
{
}
```

Use `final` for things that must behave exactly one way, such as how an ID is worked out,
or for classes that weren't designed to be extended.

## Checking the kind: `instanceof`

An `EBook` *is a* `Book`, so it can go anywhere a `Book` is expected, including a parameter
typed `Book`. `instanceof` asks whether an object is of a class or one of its children:

```php
class Book
{
    public function __construct(public string $title) {}
}

class EBook extends Book
{
}

function shelfLabel(Book $book): string
{
    return strtoupper($book->title);
}

$ebook = new EBook('Neuromancer');

echo shelfLabel($ebook) . "\n";
var_dump($ebook instanceof EBook);
var_dump($ebook instanceof Book);
var_dump(new Book('Dune') instanceof EBook);
```

## When not to inherit

Inheritance says "an X **is a** Y". Use it only when that sentence is true. An e-book is a
book, so `EBook extends Book` is right. A customer is *not* a book, even though both have a
name, so `Customer extends Book` would be wrong, however much code it saved.

A class can only extend **one** parent, and every child is tied to every detail of its
parent. When you just want to reuse some behaviour, there are better tools, which the
rest of this course covers: interfaces, traits, and objects that hold other objects.

Next, try the quiz to check your understanding, then solve the challenges.
