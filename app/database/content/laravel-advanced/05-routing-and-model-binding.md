## From URL to code

Once a request is through the global middleware, the **router** decides what runs: it compares the
request's method and path with every route you defined and calls the matching action.

```laravel
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/authors/{author}/books/{page?}', AuthorBooks::class)->whereNumber('page');

class BookController
{
    public function show(Book $book) // the Book is looked up for you
    {
        return view('books.show', ['book' => $book]);
    }
}
```

Three ideas are at work: **matching** a path against a pattern, **generating** URLs from route
names, and **route model binding**, which turns `{book}` into a `Book` model.

## Matching with regular expressions

The router turns each route's URI into a regular expression once, then tests paths against it. A
parameter like `{id}` becomes a **named group** that matches anything up to the next slash, so the
value can be read back by name:

```php
$uri = '/books/{id}/reviews/{review}';

$pattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $uri) . '$#';

echo $pattern . PHP_EOL;

if (preg_match($pattern, '/books/42/reviews/7', $matches)) {
    echo "book {$matches['id']}, review {$matches['review']}" . PHP_EOL;
}
var_dump(preg_match($pattern, '/books/42'));
```

`$matches` holds each named group under its name (and also under a number, which the router
ignores). The `^` and `$` anchors make the whole path match, not just part of it.

Laravel adds a few refinements:

- **Optional** parameters, `{page?}`, may be left out along with the slash before them.
- **Constraints** such as `->where('id', '[0-9]+')` replace `[^/]+` with a stricter pattern, so
  `/books/dune` doesn't match a route meant for numbers.
- A path that matches some route but with the wrong method (a `POST` to a `GET` route) gets
  **405 Method Not Allowed**, not 404.

## Named routes

Hard-coding URLs means hunting them down when a path changes. Giving a route a **name** lets you
build its URL from the name and parameters, so the path is written in one place only:

```laravel
route('books.show', ['book' => 42]);              // /books/42
route('books.show', ['book' => 42, 'tab' => 'reviews']); // /books/42?tab=reviews
```

Parameters that aren't part of the path become the query string, as in the second line. Building
the path itself is a string replacement:

```php
$uri = '/authors/{author}/books/{book}';
$parameters = ['author' => 'herbert', 'book' => 'dune'];

$url = preg_replace_callback(
    '/\{(\w+)\}/',
    fn (array $match) => rawurlencode((string) $parameters[$match[1]]),
    $uri,
);

echo $url;
```

## Route model binding

Most routes start by loading a model from a parameter and returning 404 if it isn't there. Laravel
does that for you. When an action's parameter is type-hinted with a model class and named like a
route parameter, the router uses reflection to see the type, looks the record up, and passes in the
model instead of the raw string:

```php
class Book
{
    private const ROWS = [1 => 'Dune', 2 => 'Piranesi'];

    public function __construct(public int $id, public string $title) {}

    public static function find(int $id): ?static
    {
        return isset(self::ROWS[$id]) ? new static($id, self::ROWS[$id]) : null;
    }
}

$action = fn (Book $book) => "Showing {$book->title}";
$routeParameters = ['book' => '2'];

$arguments = [];
foreach ((new ReflectionFunction($action))->getParameters() as $parameter) {
    $value = $routeParameters[$parameter->getName()];
    $class = $parameter->getType()->getName();
    $arguments[] = $class === Book::class ? Book::find((int) $value) : $value;
}

echo $action(...$arguments);
```

If the model isn't found, Laravel throws `ModelNotFoundException`, which becomes a 404 page.

## Binding by another column

IDs make ugly URLs. `{book:slug}` tells Laravel to look the model up by its `slug` column instead,
and a model can change its default key for every route by overriding `getRouteKeyName()`:

```laravel
Route::get('/books/{book:slug}', ShowBook::class); // /books/dune

class Book extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

Behind both is one model method, `resolveRouteBinding($value, $field)`, which you can override for
complete control, for example to include soft-deleted records.

## Grouping routes

Routes that share a prefix, middleware or name prefix are declared together:

```laravel
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/books', AdminBooks::class)->name('books'); // /admin/books, named admin.books
});
```

Groups only change how routes are registered; matching works exactly as above.
