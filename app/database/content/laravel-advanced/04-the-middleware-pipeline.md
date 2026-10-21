## Layers around your app

Before a request reaches a route, it passes through **middleware**: layers that can look at the
request, change it, stop it, or change the response on the way back out. Starting the session,
checking the CSRF token, making sure the user is logged in and adding security headers are all
middleware.

```laravel
class EnsureUserIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->subscribed()) {
            return redirect('/billing');
        }

        return $next($request);
    }
}
```

Every middleware has the same shape: a `handle()` method that receives the request and `$next`, a
closure that runs *everything after this layer* and returns the response.

## Before, after and instead

What a middleware does depends on where its code sits relative to `$next($request)`:

- Code **before** the call runs on the way in, and can change the request.
- Code **after** the call runs on the way out, and can change the response.
- **Not** calling `$next` at all stops the request: the middleware returns its own response, and
  nothing further in, including the route, ever runs.

```php
$route = fn (string $request) => "page for $request";

$timer = function (string $request, Closure $next) {
    echo "before: $request" . PHP_EOL;
    $response = $next($request);
    echo "after: $response" . PHP_EOL;

    return $response;
};

$guard = function (string $request, Closure $next) {
    return $request === '/admin' ? 'forbidden' : $next($request);
};

echo $timer('/books', fn ($request) => $guard($request, $route)) . PHP_EOL;
echo $timer('/admin', fn ($request) => $guard($request, $route));
```

## The onion

Wrap each layer around the next and you get an **onion**: the request travels in through every
layer to the route at the centre, and the response travels back out through the same layers in
reverse order. The first middleware in the list is the outermost layer.

Laravel builds the onion with `array_reduce()`, starting from the route (the centre) and wrapping
layers around it from the inside out, which is why the list is reversed first:

```php
$middleware = [
    fn ($request, $next) => '[' . $next($request) . ']',
    fn ($request, $next) => '(' . $next($request . '!') . ')',
];
$route = fn ($request) => strtoupper($request);

$onion = array_reduce(
    array_reverse($middleware),
    fn (Closure $next, Closure $layer) => fn ($request) => $layer($request, $next),
    $route,
);

echo $onion('dune');
```

Each step of the reduce takes the onion built so far (`$next`) and returns a new closure that runs
one more layer around it.

## The Pipeline class

Laravel wraps this in `Illuminate\Pipeline\Pipeline`, which reads almost like a sentence. The HTTP
kernel uses it to send each request through the global middleware and then the route's own:

```laravel
$response = (new Pipeline($app))
    ->send($request)
    ->through([TrimStrings::class, StartSession::class, Authenticate::class])
    ->then(fn ($request) => $router->dispatch($request));
```

Pipes can be closures or class names; a class name is built by the container and its `handle()`
method is called. The same class is used for anything that passes through stages, not just HTTP
requests.

## Middleware parameters

A middleware can take extra arguments, written after a colon in the route definition and separated
by commas. They arrive after `$next`:

```laravel
Route::put('/books/{book}', UpdateBook::class)->middleware('role:editor,admin');

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // $roles is ['editor', 'admin']
    }
}
```

Splitting such a string is plain PHP:

```php
$definition = 'role:editor,admin';

[$name, $parameters] = array_pad(explode(':', $definition, 2), 2, '');
$parameters = $parameters === '' ? [] : explode(',', $parameters);

echo $name . ' with ' . implode(' and ', $parameters);
```

## Groups and terminable middleware

Two more ideas complete the picture:

- **Groups** give a list of middleware one name. Every route in `routes/web.php` gets the `web`
  group (cookies, session, CSRF...), and API routes get the `api` group.
- **Terminable** middleware have a `terminate($request, $response)` method that Laravel calls after
  the response has been sent to the browser, which is a good moment for slow work such as writing
  logs, because the user is no longer waiting.

```laravel
class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        Log::info('Handled', ['path' => $request->path(), 'status' => $response->status()]);
    }
}
```

Middleware is simple, but it is where a lot of a framework's behaviour lives. Knowing the onion
makes it easy to predict what runs when.
