## A class per table

**Eloquent** is Laravel's way of working with the database through PHP objects. Each table gets a
**model** class, and each row becomes an object of it:

```laravel
// php artisan make:model Book

class Book extends Model
{
    protected $fillable = ['title', 'author', 'price'];
}

$books = Book::all();                                   // every row
$book = Book::find(7);                                  // by id, or null
$cheap = Book::where('price', '<', 10)->orderBy('title')->get();
$dune = Book::where('title', 'Dune')->first();

$book = Book::create(['title' => 'Piranesi', 'author' => 'Susanna Clarke', 'price' => 9.99]);
$book->update(['price' => 7.99]);
$book->delete();
```

You almost never write SQL for everyday work: Eloquent builds it from these calls.

## Conventions

Eloquent guesses as much as it can from names, so a model is often just a class with a list of
fillable columns:

- The **table** is the class name in `snake_case`, made plural: `Book` → `books`, `BookReview` →
  `book_reviews`, `Category` → `categories`. Set `protected $table` if yours is different.
- The **primary key** is `id`, an auto-incrementing integer.
- **Timestamps**: `created_at` and `updated_at` are filled in for you when a row is created or saved.

Turning a class name into a table name is a little string work:

```php
$class = 'BookReview';

$snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
$table = $snake . 's';

echo $table;
```

## Reading

`all()`, `find()`, `where()`, `first()`, `orderBy()`, `count()`... A query starts with a static call on
the model and ends with `get()` (a collection of models), `first()` (one model or `null`) or an
aggregate like `count()`. `findOrFail()` and `firstOrFail()` throw a `ModelNotFoundException` instead
of returning `null`, which Laravel turns into a 404 page.

## Writing

`create()` inserts a row and returns the model, with its new `id` and timestamps. Because it takes an
array, usually straight from a form, Eloquent only fills the columns listed in `$fillable` (lesson 8 of
the security course explains why). Changing attributes one by one and calling `save()` also works:

```laravel
$book = Book::find(7);
$book->price = 7.99;
$book->save();          // update books set price = ?, updated_at = ? where id = ?
```

`firstOrCreate(['isbn' => $isbn], ['title' => $title])` looks a row up and creates it only if it isn't
there: the first array is what to search for, the second the extra values for a new row.

## Soft deletes

Deleting rows is final, and often you'd like a way back. With the `SoftDeletes` trait (and a
`deleted_at` column from `$table->softDeletes()` in the migration), `delete()` only sets `deleted_at`.
Soft-deleted rows disappear from every normal query, `withTrashed()` includes them again, and
`restore()` brings one back:

```php
$rows = [
    ['id' => 1, 'title' => 'Dune', 'deleted_at' => null],
    ['id' => 2, 'title' => 'Piranesi', 'deleted_at' => '2026-10-21 09:30:00'],
];

$visible = array_filter($rows, fn (array $row) => $row['deleted_at'] === null);

echo implode(', ', array_column($visible, 'title'));
```

## Seeing the SQL

When a page is slow or a result looks wrong, look at the queries Eloquent ran:
`Book::where('price', '<', 10)->toSql()` shows the SQL for one query, and Laravel Debugbar or Telescope
show every query a page made.
