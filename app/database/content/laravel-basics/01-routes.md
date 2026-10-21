## Where every request starts

A Laravel app answers web requests. Each request has a **method** (`GET` to read, `POST` to send a
form, `PUT`/`PATCH` to update, `DELETE` to remove) and a **path** (`/books/7`). **Routes** connect the
two to your code. They live in `routes/web.php`:

```laravel
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Welcome to Corner Books';
});

Route::get('/books', function () {
    return ['Dune', 'Piranesi'];   // arrays are sent as JSON
});

Route::post('/newsletter', function () {
    return 'Thanks for subscribing!';
});
```

Code in a `laravel` block like the one above is real Laravel. The sandbox runs plain PHP without the
framework, so in this course each lesson shows the Laravel way, and then you practise the idea behind it
in plain PHP, which is a good way to understand what Laravel does for you.

## A route table

At heart, routing is a lookup: the method and path go in, and the matching code comes out. If nothing
matches, the visitor gets a **404 Not Found**:

```php
$routes = [
    'GET /' => fn () => 'Welcome to Corner Books',
    'GET /books' => fn () => 'All books',
    'POST /newsletter' => fn () => 'Thanks for subscribing!',
];

foreach (['GET /books', 'POST /newsletter', 'GET /films'] as $request) {
    echo $request . ' → ' . (isset($routes[$request]) ? $routes[$request]() : '404 Not Found') . PHP_EOL;
}
```

The method matters: `GET /newsletter` and `POST /newsletter` are different routes.

## Route parameters

Most paths contain values: `/books/7`, `/authors/frank-herbert`. Curly braces in a route mark a
**parameter**, and Laravel passes its value to your function. A `?` makes it optional, in which case
the function needs a default:

```laravel
Route::get('/books/{id}', function (string $id) {
    return "Book number $id";
});

Route::get('/shop/{page?}', function (string $page = '1') {
    return "Shop, page $page";
});
```

Matching `/books/{id}` against `/books/7` can be done segment by segment: split both at `/`, and
wherever the route has `{name}`, take the path's segment as that parameter:

```php
$route = explode('/', '/books/{id}/reviews');
$path = explode('/', '/books/7/reviews');

$parameters = [];
foreach ($route as $i => $segment) {
    if (str_starts_with($segment, '{')) {
        $parameters[trim($segment, '{}')] = $path[$i];
    }
}

print_r($parameters);
```

## The request

Values can also come from the **query string** (`/search?q=dune&page=2`) or a submitted form. Ask for
the `Request` object and Laravel hands it to you:

```laravel
use Illuminate\Http\Request;

Route::get('/search', function (Request $request) {
    $term = $request->query('q', '');      // from the query string, '' if missing
    $page = $request->integer('page', 1);  // converted to an int

    return "Searching for $term, page $page";
});
```

## Named routes and groups

Giving a route a **name** lets you build its URL in one place, so changing the path later doesn't break
every link to it. **Groups** share settings, like a path prefix, between routes:

```laravel
Route::get('/books/{id}', ShowBook::class)->name('books.show');

route('books.show', ['id' => 7]); // "/books/7"

Route::prefix('admin')->group(function () {
    Route::get('/books', AdminBooks::class); // "/admin/books"
});
```

`php artisan route:list` prints every route the app has, which is the quickest way to see what your
`routes/web.php` really produced.
