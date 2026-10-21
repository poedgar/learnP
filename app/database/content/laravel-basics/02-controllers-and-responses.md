## From closures to controllers

Closures in `routes/web.php` are fine for a page or two, but a real app has dozens of actions. Laravel
groups related actions into **controller** classes in `app/Http/Controllers`, and the route file just
points at them:

```laravel
// php artisan make:controller BookController

class BookController extends Controller
{
    public function index()
    {
        return view('books.index', ['books' => Book::all()]);
    }

    public function show(Book $book)
    {
        return view('books.show', ['book' => $book]);
    }
}

Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
```

A controller is an ordinary PHP class. Laravel creates it and calls the method the route names,
passing the route's parameters, so it's easy to picture in plain PHP:

```php
class BookController
{
    private array $books = [7 => 'Dune', 8 => 'Piranesi'];

    public function index(): string
    {
        return implode(', ', $this->books);
    }

    public function show(int $id): string
    {
        return $this->books[$id] ?? 'Not found';
    }
}

$action = [BookController::class, 'show'];
[$class, $method] = $action;

echo (new $class())->$method(8);
```

## Resource controllers

Most things in an app (books, orders, reviews) need the same seven actions, so Laravel has a
convention for them. One line registers all seven routes, with standard names:

```laravel
Route::resource('books', BookController::class);
```

| Method | Path | Action | Name |
|---|---|---|---|
| GET | `/books` | `index` | `books.index` |
| GET | `/books/create` | `create` | `books.create` |
| POST | `/books` | `store` | `books.store` |
| GET | `/books/{book}` | `show` | `books.show` |
| GET | `/books/{book}/edit` | `edit` | `books.edit` |
| PUT/PATCH | `/books/{book}` | `update` | `books.update` |
| DELETE | `/books/{book}` | `destroy` | `books.destroy` |

`create` and `edit` show forms; `store`, `update` and `destroy` handle them. A controller with a single
`__invoke()` method is an **invokable** controller, registered with just its class name:
`Route::get('/bestsellers', ShowBestsellers::class)`.

## Responses

Whatever an action returns becomes the response. Laravel has helpers for each kind:

```laravel
return view('books.index', ['books' => $books]);            // HTML from a Blade template
return response()->json($book, 201);                         // JSON with a status code
return redirect()->route('books.show', $book)                // 302 to another page...
    ->with('status', 'Book saved.');                         // ...with a one-time message
abort(404);                                                  // stop with an error page
```

Every response has a **status code** that tells the browser what happened: `200` OK, `201` Created,
`204` No Content, `302` Found (a redirect), `403` Forbidden, `404` Not Found, `422` the input was
invalid, `500` something broke on the server.

## Flash messages

After a form is saved, the app **redirects** (so refreshing the page doesn't send the form again) and
shows "Book saved." on the next page. That message is **flashed** into the session: it's kept for exactly
one more request and then forgotten.

```php
$session = ['_flash' => ['status' => 'Book saved.']];

function pull(array &$session, string $key): ?string
{
    $value = $session['_flash'][$key] ?? null;
    unset($session['_flash'][$key]);

    return $value;
}

var_dump(pull($session, 'status'), pull($session, 'status'));
```

## HTML or JSON?

The same controller can serve a browser and a JavaScript client. `$request->wantsJson()` looks at the
request's `Accept` header: if it asks for `application/json`, return data; otherwise return a page.
Laravel's error handling does the same, which is why an API client gets errors as JSON.
