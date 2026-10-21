## Remember the answer

Some work is slow and gives the same answer many times: the bestseller list needs a heavy query, the
exchange rate needs a call to another company's API, the menu is built from a dozen tables. A **cache**
keeps the answer for a while, so the next request reads it in a millisecond instead of working it out
again.

The usual pattern is **cache-aside**: look in the cache; on a **hit**, use what's there; on a **miss**,
do the work, store the result, and use it.

```php
$cache = [];
$work = 0;

$bestsellers = function () use (&$cache, &$work): array {
    if (! array_key_exists('bestsellers', $cache)) {   // a miss
        $work++;                                        // the slow query
        $cache['bestsellers'] = ['Dune', 'Emma', 'Ulysses'];
    }

    return $cache['bestsellers'];                        // a hit from now on
};

$bestsellers();
$bestsellers();
$bestsellers();

echo "Asked 3 times, worked {$work} time(s).";
```

## Laravel's Cache

Laravel's `Cache` facade gives every cache store the same methods:

```laravel
Cache::put('bestsellers', $books, now()->addMinutes(10));   // store for 10 minutes
Cache::get('bestsellers');                                  // the value, or null
Cache::get('bestsellers', []);                              // or a default
Cache::has('bestsellers');
Cache::forget('bestsellers');

// The everyday one: cache-aside in a single call
$books = Cache::remember('bestsellers', now()->addMinutes(10), function () {
    return Book::orderByDesc('sales')->take(10)->get();
});

Cache::rememberForever('countries', fn () => Country::all());
Cache::add('lock:import', true, 60);    // only stores if the key is missing; returns whether it did
Cache::increment('downloads:dune');      // atomic counters
Cache::pull('one-time-code');            // get, then forget
```

The closure only runs on a miss. On a hit, the slow query never happens.

## Time to live

Every cached value should have a **TTL** (time to live), after which it expires and the next request
works it out again. The TTL is the most important decision: it's how stale you're willing to let the
data be. A list of countries can live for a day; stock levels for a few seconds, if at all.

```php
$store = [];
$put = function (string $key, mixed $value, int $ttl, int $now) use (&$store) {
    $store[$key] = ['value' => $value, 'expires_at' => $now + $ttl];
};
$get = function (string $key, int $now) use (&$store): mixed {
    $item = $store[$key] ?? null;

    return $item !== null && $now < $item['expires_at'] ? $item['value'] : null;
};

$put('rate:GBP-EUR', 1.17, ttl: 60, now: 1000);

foreach ([1000, 1059, 1060] as $now) {
    echo "t={$now}: " . var_export($get('rate:GBP-EUR', $now), true) . PHP_EOL;
}
```

## Stores

`config/cache.php` chooses where values live, with `CACHE_STORE` in `.env`:

| Store | Good for |
|---|---|
| `array` | tests: lives for one request only |
| `file` | a single small server |
| `database` | the default in new apps; no extra software |
| `redis` | fast, shared by every server, and the base for locks, queues and more (lessons 3 and 4) |
| `memcached` | fast, shared, simple key-value |

If your app runs on more than one server, the cache must be **shared** (database, Redis or Memcached),
or each server keeps its own copy and they disagree. Use `Cache::store('redis')->get(...)` to pick a
store explicitly.

## Naming keys

Keys are strings, and a good scheme saves confusion. Put the most general part first, separate parts
with colons, and include **everything the value depends on**:

```laravel
"book:{$book->id}"                       // one book
"books:bestsellers:{$locale}"            // differs per language
"user:{$user->id}:recommendations"
"search:" . md5(json_encode($filters))  // long inputs: hash them
```

Forgetting something in the key is a classic bug: if the bestseller list depends on the language but
the key doesn't, German visitors get the English list cached by whoever came first. And never put one
user's private data under a key another user could read.

## What not to cache

Caching adds complexity: stale data, keys to manage, another thing that can be down. Don't cache what
is already fast (a lookup by primary key is often under a millisecond), and measure before and after.
Cache what's slow, read often and changed rarely.
