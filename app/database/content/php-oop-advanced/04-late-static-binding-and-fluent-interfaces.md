## `self` means "the class I'm written in"

In the OOP in PHP course you saw that `self::` and `static::` differ. Here's why it matters. A
parent class with a factory method that uses `new self` always creates a parent, even when the
method is called on a child:

```php
class Model
{
    public static function make(): self
    {
        return new self();
    }
}

class Book extends Model {}

echo get_class(Book::make());
```

`self` is fixed when the code is written: it's always the class the method was written in.

## `static` means "the class it was called on"

`static` is worked out when the code **runs**: it's the class the method was actually called on.
That's called **late static binding**. With `new static`, the same factory creates the right
kind of object for every child:

```php
class Model
{
    public function __construct(public array $attributes = []) {}

    public static function make(array $attributes): static
    {
        return new static($attributes);
    }
}

class Book extends Model {}
class Author extends Model {}

echo get_class(Book::make(['title' => 'Dune'])) . "\n";
echo get_class(Author::make(['name' => 'Frank Herbert']));
```

The return type `static` promises exactly that: "an object of whichever class this was called on".

## Children filling in the details

`static::` also reaches constants and static methods that a child overrides. The parent writes
the general code once, and each child supplies its own details:

```php
class Model
{
    const TABLE = 'models';

    public static function selectAll(): string
    {
        return 'SELECT * FROM ' . static::TABLE;
    }
}

class Book extends Model
{
    const TABLE = 'books';
}

echo Book::selectAll() . "\n";
echo Model::selectAll();
```

With `self::TABLE`, `Book::selectAll()` would read from `models`. `static::class` gives the name
of the class the call was made on, which is useful as a key or in messages.

## Static properties are shared down the family

A static property belongs to the class that declares it, and children share it unless they
declare their own. So a counter in the parent counts **every** kind of model together:

```php
class Model
{
    public static int $created = 0;

    public function __construct()
    {
        static::$created++;
    }
}

class Book extends Model {}
class Author extends Model {}

new Book();
new Book();
new Author();

echo Book::$created . ' ' . Author::$created;
```

Both print 3: there's only one `$created`. To keep separate numbers per class, store them in an
array keyed by `static::class`.

## Fluent interfaces

A method that returns `$this` can be followed straight away by another call on the same object.
Chains like this are called a **fluent interface**, and suit objects that are configured step by
step:

```php
class Message
{
    private string $to = '';
    private string $subject = '';

    public function to(string $to): static
    {
        $this->to = $to;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function summary(): string
    {
        return "To {$this->to}: {$this->subject}";
    }
}

echo (new Message())->to('amara@example.com')->subject('Your order has shipped')->summary();
```

The return type is `static`, not `self`, so a child class's own methods can still follow in the
chain.

## Fluent and unchangeable

Returning `$this` changes the object, so anyone else holding it sees the change. For values that
should never change, each step can return a changed **copy** instead, made with `clone`:

```php
class Search
{
    private array $terms = [];

    public function with(string $term): static
    {
        $copy = clone $this;
        $copy->terms[] = $term;
        return $copy;
    }

    public function describe(): string
    {
        return implode(' + ', $this->terms) ?: '(nothing)';
    }
}

$base = (new Search())->with('fantasy');
$narrow = $base->with('dragons');

echo $base->describe() . "\n";
echo $narrow->describe();
```

`clone $this` copies whichever class the object really is, so this works for children too.

## Putting it together

A base query whose fluent methods return `static`, so a child adds its own steps without breaking
the chain:

```php
class Query
{
    protected array $conditions = [];

    public function __construct(protected string $table) {}

    public static function on(string $table): static
    {
        return new static($table);
    }

    public function where(string $condition): static
    {
        $copy = clone $this;
        $copy->conditions[] = $condition;
        return $copy;
    }

    public function toSql(): string
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->conditions ? $sql . ' WHERE ' . implode(' AND ', $this->conditions) : $sql;
    }
}

class BookQuery extends Query
{
    public function cheap(): static
    {
        return $this->where('price < 10');
    }
}

echo BookQuery::on('books')->where("genre = 'Horror'")->cheap()->toSql();
```

Next, try the quiz to check your understanding, then solve the challenges.
