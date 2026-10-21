## What an API is for

A web page is for people; an **API** is for programs. The mobile app, a partner's shop or your own
JavaScript front end ask your Laravel app for data and send changes back, as JSON over HTTP. Most such
APIs follow the **REST** style, which is less a standard than a set of habits that make APIs
predictable: if you've used one REST API, the next one feels familiar.

## Resources are nouns

A REST API is organised around **resources**, the things your app knows about, each at its own URL.
Collections are plural, and a single item adds its id:

| URL | Means |
|---|---|
| `/api/books` | the collection of books |
| `/api/books/7` | book number 7 |
| `/api/authors/3/books` | the books of author 3 |

The URL names *what*; the HTTP **method** says *what to do* with it. So it's `POST /api/books`, not
`POST /api/createBook`.

| Method | On `/api/books` | On `/api/books/7` |
|---|---|---|
| `GET` | list books | show book 7 |
| `POST` | create a book | (not used) |
| `PUT` | (not used) | replace book 7 |
| `PATCH` | (not used) | change some fields of book 7 |
| `DELETE` | (not used) | delete book 7 |

## Safe and idempotent

Two properties of methods matter to everyone who calls your API:

- **Safe** methods (`GET`, `HEAD`, `OPTIONS`) don't change anything. Browsers, caches and crawlers may
  call them freely, so never delete things in a `GET`.
- **Idempotent** methods give the same end result however many times they're repeated: `PUT` (set the
  book to *this*) and `DELETE` (make it gone) are, and so are the safe ones. `POST` isn't: sending
  "create a book" twice makes two books. `PATCH` usually isn't guaranteed to be.

That's why a client may retry a `PUT` after a network timeout, but has to be careful retrying a `POST`.

```php
$methods = [
    'GET' => ['safe' => true, 'idempotent' => true],
    'POST' => ['safe' => false, 'idempotent' => false],
    'PUT' => ['safe' => false, 'idempotent' => true],
    'DELETE' => ['safe' => false, 'idempotent' => true],
];

foreach ($methods as $method => $p) {
    echo str_pad($method, 7) . ($p['idempotent'] ? 'can be retried' : 'careful retrying') . PHP_EOL;
}
```

## Status codes tell the story

The status code is the first thing a client checks, so pick the one that says what happened:

| Code | When |
|---|---|
| `200 OK` | it worked; here's the data |
| `201 Created` | a new resource was made; its URL is in the `Location` header |
| `204 No Content` | it worked, nothing to send back (a typical `DELETE`) |
| `400 Bad Request` | the request itself is broken (invalid JSON, say) |
| `401 Unauthorized` | we don't know who you are: log in or send a token |
| `403 Forbidden` | we know who you are, and you may not do this |
| `404 Not Found` | no such resource |
| `405 Method Not Allowed` | the URL exists but not with this method; the `Allow` header lists those that work |
| `409 Conflict` | it clashes with the current state (a duplicate ISBN) |
| `422 Unprocessable Content` | the data failed validation |
| `429 Too Many Requests` | slow down (lesson 4) |
| `500 Internal Server Error` | our fault, not yours |

The 4xx codes mean "the client should change something"; the 5xx codes mean "the server failed".

## A REST API in Laravel

API routes go in `routes/api.php` (add it with `php artisan install:api`). They're prefixed with
`/api` and use the `api` middleware group: no sessions or CSRF, just stateless requests.
`Route::apiResource()` registers the five routes above in one line:

```laravel
// routes/api.php
Route::apiResource('books', BookController::class);
// GET /api/books → index, POST /api/books → store, GET /api/books/{book} → show,
// PUT|PATCH /api/books/{book} → update, DELETE /api/books/{book} → destroy
```

Return an array, a model or a collection from a controller and Laravel turns it into JSON. Use
`response()->json()` to choose the status and headers:

```laravel
class BookController extends Controller
{
    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());

        return response()->json($book, 201, ['Location' => route('books.show', $book)]);
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();   // 204
    }
}
```

Route model binding (`Book $book`) returns the 404 for a missing book automatically, and Laravel
answers a method the route doesn't have with 405.
