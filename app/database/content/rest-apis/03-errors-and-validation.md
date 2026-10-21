## Errors are part of the API

Clients spend a surprising amount of code on what goes wrong: showing the right message next to the
right field, retrying when it's worth it, asking the user to log in again. They can only do that if
errors are **predictable**: the right status code, and a body with the same shape every time.

## Laravel's validation errors

When validation fails on a request that wants JSON, Laravel answers `422` with this shape, and many
front-end libraries understand it out of the box:

```json
{
    "message": "The title field is required. (and 1 more error)",
    "errors": {
        "title": ["The title field is required."],
        "price": ["The price field must be at least 0."]
    }
}
```

`message` is the first error plus a count of the rest; `errors` has a list of messages per field.
Validation for APIs is written exactly as for forms, usually in a form request:

```laravel
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'exists:books,id'],
            'items.*.quantity' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
```

Errors in nested arrays are named by their path: `items.2.quantity` is the quantity of the third item,
so the client knows exactly which line to highlight.

## Does the client want JSON?

The same exception can become an HTML error page or a JSON error. Laravel decides with
`$request->expectsJson()`, which looks for `Accept: application/json`. That's why API clients should
always send that header, and why `getJson()`/`postJson()` in tests do. You can also make every
`/api/*` route answer in JSON, whatever the header says:

```laravel
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
})
```

## Exceptions become status codes

Laravel turns common exceptions into responses for you:

| Exception | Response |
|---|---|
| `ValidationException` | 422 with `errors` |
| `ModelNotFoundException` (route model binding, `findOrFail`) | 404 |
| `AuthenticationException` | 401 |
| `AuthorizationException` (a policy said no) | 403 |
| `ThrottleRequestsException` | 429 |
| anything else | 500 |

You can map your own: `$exceptions->render(fn (OutOfStock $e) => response()->json([...], 409))`.

## Never leak internals

With `APP_DEBUG=true`, a 500 includes the exception, file and stack trace. That's lovely on your
laptop and dangerous in production, where it can reveal paths, queries and secrets. In production the
body is just `{"message": "Server Error"}`; the details go to the log, where you can read them.

```php
function errorBody(Throwable $e, bool $debug): array
{
    $body = ['message' => $debug ? $e->getMessage() : 'Server Error'];
    if ($debug) {
        $body['exception'] = $e::class;
    }

    return $body;
}

$e = new PDOException('SQLSTATE[HY000]: could not connect to db.internal:5432 as admin');

echo json_encode(errorBody($e, debug: false)) . PHP_EOL;
echo json_encode(errorBody($e, debug: true)) . PHP_EOL;
```

## Problem details

Some APIs use the standard **problem details** format (RFC 9457), with the content type
`application/problem+json`. It gives every error the same fields, and `type` is a URL that documents
that kind of error:

```json
{
    "type": "https://api.cornerbooks.test/problems/out-of-stock",
    "title": "Conflict",
    "status": 409,
    "detail": "Only 2 copies of Dune are left.",
    "instance": "/api/orders/981"
}
```

Whichever format you choose, use it for **every** error, and document it. A stable error code (like
`out-of-stock`) is far easier for a program to act on than an English sentence that might be reworded.

## Which errors to retry

A client can retry a request that failed because of the server or the network: `429`, `502`, `503` and
`504`, or no response at all. It shouldn't retry a `4xx` like `422` or `403`, which will fail again
until the request changes. And without a response, it should only retry idempotent methods, since a
`POST` might have worked before the connection dropped.
