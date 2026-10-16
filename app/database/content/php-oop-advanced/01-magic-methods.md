## Methods PHP calls for you

You've already met two methods that PHP calls by itself: `__construct()`, when an object is
created, and `__toString()`, when an object is used as text. Every method whose name starts
with two underscores is a **magic method**: you never call it directly, PHP calls it when
something particular happens to the object. This lesson covers the useful ones.

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function __toString(): string
    {
        return "{$this->title} by {$this->author}";
    }
}

$book = new Book('Circe', 'Madeline Miller');
echo "Now reading: $book";
```

## Reading properties that don't exist: `__get`

When code reads a property that doesn't exist, or that it isn't allowed to see, PHP calls
`__get()` with the property's name instead of failing. A settings object can keep its values
in a private array and still let you write `$settings->theme`:

```php
class Settings
{
    private array $values = ['theme' => 'dark', 'language' => 'en'];

    public function __get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }
}

$settings = new Settings();
echo $settings->theme . "\n";
var_dump($settings->fontSize);
```

Real properties are always used first. `__get()` only runs when there's no property it may use.

## Writing them: `__set`, and checking them: `__isset` and `__unset`

`__set()` runs when code assigns to such a property, with the name and the value. `__isset()`
answers `isset()` and `empty()`, and `__unset()` handles `unset()`. Without `__isset()`,
`isset($settings->theme)` would say `false` even though `__get()` returns a value:

```php
class Settings
{
    private array $values = [];

    public function __get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->values[$name]);
    }
}

$settings = new Settings();
$settings->theme = 'light';
var_dump(isset($settings->theme));
unset($settings->theme);
var_dump(isset($settings->theme));
```

## Calling methods that don't exist: `__call`

`__call()` runs when code calls a method that doesn't exist (or isn't visible), with the
method's name and an array of the arguments. It's how some libraries offer methods like
`whereTitle('Dune')` without writing one for every column:

```php
class BookQuery
{
    private array $conditions = [];

    public function __call(string $method, array $arguments): static
    {
        if (str_starts_with($method, 'where')) {
            $column = strtolower(substr($method, 5));
            $this->conditions[] = "$column = '{$arguments[0]}'";
            return $this;
        }

        throw new BadMethodCallException("There is no method $method().");
    }

    public function toSql(): string
    {
        return 'SELECT * FROM books WHERE ' . implode(' AND ', $this->conditions);
    }
}

echo (new BookQuery())->whereGenre('Fantasy')->whereAuthor('Ursula K. Le Guin')->toSql();
```

Throw a `BadMethodCallException` for names you don't handle, or a typo would silently do nothing.
`__callStatic()` does the same for static calls, such as `BookQuery::where…()`.

## Objects you can call: `__invoke`

An object with `__invoke()` can be called like a function. That's handy for small objects that
do one job and need some settings, such as a discount that's passed to `array_map()`:

```php
class Discount
{
    public function __construct(private int $percent) {}

    public function __invoke(float $price): float
    {
        return round($price * (100 - $this->percent) / 100, 2);
    }
}

$tenPercentOff = new Discount(10);
echo $tenPercentOff(20.0) . "\n";
print_r(array_map($tenPercentOff, [9.99, 14.99]));
```

An invokable object counts as `callable`, so it can go anywhere a function can.

## The cost of magic

Magic methods are powerful, but they hide things. An editor can't suggest `$settings->theme`,
a typo in `$settings->thmee` gives `null` instead of an error, and readers have to find the
magic method to know what's going on. Use them when the names really can't be known in advance,
like the columns in `whereGenre()`, and prefer ordinary properties and methods everywhere else.

```php
// Error: __get() doesn't help with methods; this calls a method that doesn't exist
class Settings
{
    public function __get(string $name): mixed
    {
        return 'dark';
    }
}

echo (new Settings())->theme();
```

## Putting it together

A record that stores its fields in an array, refuses unknown ones, and can describe itself:

```php
class Record
{
    private array $fields = [];

    public function __construct(private array $allowed) {}

    public function __get(string $name): mixed
    {
        return $this->fields[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (! in_array($name, $this->allowed, true)) {
            throw new InvalidArgumentException("Unknown field $name.");
        }
        $this->fields[$name] = $value;
    }

    public function __toString(): string
    {
        return json_encode($this->fields);
    }
}

$book = new Record(['title', 'author']);
$book->title = 'Dune';
$book->author = 'Frank Herbert';
echo $book;
```

Next, try the quiz to check your understanding, then solve the challenges.
