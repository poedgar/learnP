## Why objects?

So far you may have written PHP as a list of steps: variables, functions and arrays. That
works well for small scripts. As programs grow, the data and the functions that work on it
drift apart, and it gets hard to see which function is allowed to change which array.

**Object-oriented programming** (OOP) keeps them together. You describe a *kind of thing*,
such as a book, a bank account or a shopping cart, once:

- the data each one holds, its **properties**;
- what each one can do, its **methods**.

That description is a **class**. Each individual thing you create from it is an **object**,
also called an **instance** of the class. A class is like a recipe; objects are the dishes
you cook from it. One recipe, as many dishes as you like, and each dish is separate.

## Your first class

A class is declared with the `class` keyword, a name in `PascalCase`, and a body in braces.
Properties are declared with a **visibility** (more on that below), an optional **type**, and
a name:

```php
class Book
{
    public string $title;
    public string $author;
}
```

This only *describes* books. To get an actual book, create an object with `new`, then use
the **object operator** `->` to read and write its properties:

```php
class Book
{
    public string $title;
    public string $author;
}

$book = new Book();
$book->title = 'Dune';
$book->author = 'Frank Herbert';

echo $book->title . ' was written by ' . $book->author;
```

Notice there's no `$` before the property name after `->`: you write `$book->title`, not
`$book->$title`.

Every object has its own copy of the properties. Changing one book doesn't touch another:

```php
class Book
{
    public string $title;
}

$first = new Book();
$first->title = 'Dune';

$second = new Book();
$second->title = 'Circe';

echo $first->title . "\n";   // Dune
echo $second->title . "\n";  // Circe
```

## Methods and `$this`

A **method** is a function that belongs to a class. Inside a method, `$this` refers to the
object the method was called on, so the same method works for every book:

```php
class Book
{
    public string $title;
    public string $author;

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

$book = new Book();
$book->title = 'Beloved';
$book->author = 'Toni Morrison';

echo $book->describe();
```

When you call `$book->describe()`, PHP runs the method with `$this` set to `$book`.

## Constructors

Setting every property by hand after `new` is tedious, and it's easy to forget one. A
**constructor** is a special method named `__construct` that PHP calls automatically when you
create an object. Whatever you pass to `new` is passed on to it:

```php
class Book
{
    public string $title;
    public string $author;

    public function __construct(string $title, string $author)
    {
        $this->title = $title;
        $this->author = $author;
    }

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

$book = new Book('Dune', 'Frank Herbert');
echo $book->describe();
```

Now a `Book` can't exist without a title and an author.

### Constructor property promotion

Declaring a property, accepting a parameter and assigning one to the other is so common
that PHP 8 has a shortcut. Put the visibility in front of a constructor parameter and it
becomes a property, assigned automatically:

```php
class Book
{
    public function __construct(
        public string $title,
        public string $author,
    ) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

echo (new Book('Circe', 'Madeline Miller'))->describe();
```

Both versions of `Book` behave the same. Use whichever reads more clearly.

## Visibility: public and private

Every property and method has a **visibility**:

| Keyword | Can be used from |
|---------|------------------|
| `public` | anywhere |
| `private` | only inside the class's own methods |

So far everything has been `public`, which means any code can change any property to any
value. That's risky for data with rules. A bank balance should only change through deposits
and withdrawals, never by someone typing `$account->balance = 1000000;`.

Make the property `private`, and offer public methods that enforce the rules:

```php
class BankAccount
{
    private float $balance = 0;

    public function deposit(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposits must be positive.');
        }
        $this->balance += $amount;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}

$account = new BankAccount();
$account->deposit(50);
$account->deposit(25);
echo $account->getBalance();
```

Try adding `$account->balance = 1000000;` to that example in the sandbox. PHP stops with an
error, because code outside the class can't touch a private property.

Hiding an object's data behind methods is called **encapsulation**. It is the main reason
classes exist: the class decides what "valid" means, and nothing else can break those rules.

> **A good default.** Make properties `private` unless there's a reason not to, and expose
> only the methods other code actually needs. It's easy to make something public later, but
> hard to take it back once other code depends on it.

## Putting it together

A class bundles data (properties) with behaviour (methods). `new` creates independent
objects from it, the constructor gets each one into a valid state, `$this` lets methods work
on their own object, and `private` protects data that has rules.

Next, check your understanding with the quiz, then build some classes of your own in the
challenges.
