## Two halves

Eloquent, Laravel's ORM, looks like one thing but is two working together:

- The **query builder** turns method calls into SQL with placeholders, plus a list of values
  (bindings) to fill them, which is what protects you from SQL injection.
- The **model** is an object wrapped around one row: an array of attributes, with magic methods,
  casts, accessors and change tracking on top.

```laravel
$books = Book::where('author', 'Herbert')->where('price', '<', 10)->orderBy('title')->get();

$book = Book::find(1);
$book->title = 'Dune (special edition)';
$book->isDirty('title'); // true
$book->save();           // update books set title = ? where id = ?
```

There's no database in this sandbox, so this lesson builds the SQL and the model behaviour, which
is where nearly all of Eloquent's own code is.

## Building SQL from method calls

A builder collects the parts of a query as you call methods (each returning `$this`, so they chain)
and only turns them into SQL at the end. Values never go into the SQL string; they go into the
bindings, and the database fills the `?` placeholders safely:

```php
class Builder
{
    private array $wheres = [];
    private array $bindings = [];

    public function __construct(private string $table) {}

    public function where(string $column, string $operator, mixed $value): static
    {
        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this;
    }

    public function toSql(): string
    {
        $sql = "select * from {$this->table}";

        return $this->wheres === [] ? $sql : $sql . ' where ' . implode(' and ', $this->wheres);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}

$query = (new Builder('books'))->where('author', '=', 'Herbert')->where('price', '<', 10);

echo $query->toSql() . PHP_EOL;
echo json_encode($query->getBindings());
```

In Laravel, `->toSql()` and `->getBindings()` exist on every query, and `->dd()` prints both.
Laravel also lets you leave out the operator: `where('author', 'Herbert')` means `=`.

## `Book::where()` is not static

`Book::where(...)` reads like a static method, but models have no static `where()`. The model's
`__callStatic()` creates a new builder for the model's table and forwards the call to it, much
like a facade. Instance calls you don't define, like `$book->where(...)`, are forwarded the same
way by `__call()`.

**Local scopes** use the same forwarding. A model method named `scopePopular($query)` can be called
on any query as `->popular()`: the builder's `__call()` sees there is no `popular()` method, finds
`scopePopular` on the model and calls it with itself:

```laravel
class Book extends Model
{
    public function scopePopular(Builder $query, int $minimumSales = 1000): void
    {
        $query->where('sales', '>=', $minimumSales);
    }
}

Book::popular(5000)->orderBy('title')->get();
```

## A model is an array of attributes

A model doesn't declare a property per column. It keeps the row in an `$attributes` array and uses
`__get()` and `__set()` to make `$book->title` read and write it:

```php
class Model
{
    protected array $attributes = [];

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }
}

class Book extends Model {}

$book = new Book();
$book->title = 'Dune';
$book->price = '9.99';

var_dump($book->title, $book->price);
```

Notice `price` came back as the string `'9.99'`. Databases hand everything to PHP as strings, which
is why models have **casts**: `protected $casts = ['price' => 'float', 'in_stock' => 'boolean',
'tags' => 'array']` converts on the way out of the attributes array, and `array` casts are stored as
JSON on the way in.

## Accessors and mutators

When reading or writing an attribute needs more than a cast, a model can define an **accessor**
(runs on read) or a **mutator** (runs on write). In older Laravel style these are methods named
after the attribute in StudlyCase:

```laravel
class Author extends Model
{
    public function getFullNameAttribute(): string    // $author->full_name
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function setEmailAttribute(string $value): void // $author->email = ...
    {
        $this->attributes['email'] = strtolower($value);
    }
}
```

Newer code returns an `Attribute` object from a method named in camelCase, but both are found the
same way: `__get()` and `__set()` look for a matching method before touching the array. Turning
`full_name` into `FullName` is a small string job:

```php
$key = 'first_name';
$studly = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

echo 'get' . $studly . 'Attribute';
```

## Remembering what changed

When a model is loaded, Eloquent keeps a copy of its attributes as the **original**. Comparing the
current attributes with that copy tells it which columns are **dirty**, so `save()` only updates
what changed (and does nothing at all if nothing did). After saving, the original is updated to
match.

## Mass assignment

`Book::create($request->all())` would let a visitor set any column, including ones like `is_admin`.
Models list the attributes that may be filled in bulk in `$fillable` (or the ones that may not in
`$guarded`); everything else is ignored by `fill()` and `create()`. Setting an attribute directly,
`$book->is_featured = true`, is always allowed, because that's your code, not user input.
