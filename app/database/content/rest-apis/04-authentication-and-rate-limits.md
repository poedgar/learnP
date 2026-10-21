## Who is calling?

A browser sends a session cookie with every request, but a mobile app or another server usually
doesn't have one. API requests prove who they are with a **token** instead: a long random string,
sent in a header with every request:

```text
GET /api/orders HTTP/1.1
Accept: application/json
Authorization: Bearer 7|Kb2xYwq9TzR4mN8vLp3sHd6fJg1cXe5aUo0iQy7r
```

APIs are **stateless**: each request carries everything needed to identify it, so any server behind a
load balancer can answer it. Always use HTTPS: a token sent over plain HTTP can be read by anyone on
the network, and it's as good as a password.

## Sanctum tokens

Laravel Sanctum issues tokens to your users (and, for a single-page app on your own domain, can use
the session cookie instead; that's what this site does on the web):

```laravel
// Issue a token, e.g. when the mobile app logs in
$token = $user->createToken('iPhone', ['orders:read', 'orders:write']);
return ['token' => $token->plainTextToken];   // "7|Kb2xYwq9..." shown once, never again

// Protect routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});

// Check an ability
if (! $request->user()->tokenCan('orders:write')) {
    abort(403);
}

// Log out this device, or every device
$request->user()->currentAccessToken()->delete();
$request->user()->tokens()->delete();
```

The database keeps only a **SHA-256 hash** of each token, like a password, so a leaked database
doesn't leak working tokens. The `7|` prefix is the row id, which lets Sanctum find the row straight
away and then compare hashes:

```php
$plain = 'Kb2xYwq9TzR4mN8vLp3sHd6fJg1cXe5aUo0iQy7r';
$stored = hash('sha256', $plain);                     // what the database keeps

$presented = 'Kb2xYwq9TzR4mN8vLp3sHd6fJg1cXe5aUo0iQy7r';
echo hash_equals($stored, hash('sha256', $presented)) ? 'valid' : 'invalid';
```

`hash_equals()` compares in constant time, so an attacker can't learn how many characters matched from
how long the comparison took. Give tokens only the **abilities** they need, and an expiry
(`createToken($name, $abilities, now()->addDays(30))`).

For letting *other* companies' apps act on behalf of your users ("Sign in with Corner Books"), you'd
use OAuth2, which Laravel Passport implements. That's a much bigger topic; most first-party apps only
need Sanctum.

## Rate limiting

An API without limits can be overwhelmed by one buggy script, or used to guess passwords. **Rate
limiting** caps how many requests a client may make in a period, and answers `429 Too Many Requests`
beyond it, with a `Retry-After` header saying how many seconds to wait:

```laravel
// AppServiceProvider::boot()
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->input('email') . $request->ip()));

// routes/api.php
Route::middleware('throttle:api')->group(...);
```

Responses also carry `X-RateLimit-Limit` and `X-RateLimit-Remaining`, so well-behaved clients can slow
down before they hit the wall. Limit per user when you know who's calling, and per IP address when
you don't.

The simplest algorithm is a **fixed window**: count requests per key, and reset the count when the
window ends:

```php
$limit = 3;
$windowSeconds = 60;
$hits = [];   // key => [count, resets at]

foreach ([0, 10, 20, 30, 61] as $now) {
    [$count, $resetsAt] = $hits['ip:203.0.113.9'] ?? [0, $now + $windowSeconds];
    if ($now >= $resetsAt) {
        [$count, $resetsAt] = [0, $now + $windowSeconds];
    }
    $count++;
    $hits['ip:203.0.113.9'] = [$count, $resetsAt];

    echo "t={$now}s " . ($count <= $limit ? 'allowed' : '429, retry after ' . ($resetsAt - $now) . 's') . PHP_EOL;
}
```

A fixed window allows a burst at the boundary (the full limit at the end of one window and again at
the start of the next). A **token bucket** smooths that out: each client has a bucket that refills at
a steady rate, and each request takes a token.

## Webhooks: proving who's calling you

Sometimes the API calls *you*: a payment provider posts "payment succeeded" to your webhook URL.
Anyone could post to that URL, so providers **sign** each request with a secret you share. You
recompute the signature (an HMAC of the body) and compare:

```php
$secret = 'whsec_test_secret';
$payload = '{"type":"payment.succeeded","order":981}';
$timestamp = 1792573200;

$signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
$header = "t={$timestamp},v1={$signature}";

echo $header;
```

Including the timestamp in what's signed, and rejecting old timestamps, stops an attacker replaying a
request they captured.
