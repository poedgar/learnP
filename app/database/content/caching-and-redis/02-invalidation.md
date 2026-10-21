## The hard part

"There are only two hard things in computer science: cache invalidation and naming things." Storing
an answer is easy; knowing when it's **wrong** is not. An admin fixes a typo in a book's title, and the
book page, the search results, the author's page and the bestseller list may all still show the old
one. This lesson covers the ways to keep cached data fresh enough.

## Let it expire

The simplest strategy is to do nothing and let the TTL handle it: the data is at most that old. It's
often good enough, and it's also the **backstop** for every other strategy: even if you forget to clear
a key somewhere, it fixes itself within the TTL. So give everything a TTL, even when you also clear it
by hand.

## Forget on write

When the data changes, delete the cached copies. The next read misses and builds a fresh one. Model
events are a natural place:

```laravel
class BookObserver
{
    public function saved(Book $book): void
    {
        Cache::forget("book:{$book->id}");
        Cache::forget("author:{$book->author_id}:books");

        if ($book->wasChanged('author_id')) {
            Cache::forget("author:{$book->getOriginal('author_id')}:books");   // the old author too
        }
    }
}
```

The difficulty is finding **every** key a change affects. That's easier when keys follow a clear
scheme, and it's why it pays to cache a few big things rather than many small ones.

## Versioned keys

Sometimes you want to clear a whole group at once ("anything about books"), but caches can't search
keys cheaply. The trick: put a **version number** in the keys, and bump it to make all the old keys
unreachable. They're never read again and expire on their own.

```php
$versions = ['books' => 1];
$key = fn (string $name) => "books:v{$versions['books']}:{$name}";

echo $key('bestsellers') . PHP_EOL;
$versions['books']++;          // "flush" every books key at once
echo $key('bestsellers') . PHP_EOL;
```

In Laravel, keep the version itself in the cache (`Cache::increment('books:version')`), so every server
sees the bump.

## Tags

Redis and Memcached stores support **tags**, which do the grouping for you:

```laravel
Cache::tags(['books', "author:{$author->id}"])->put("book:{$book->id}", $book, 600);

Cache::tags(['books'])->flush();   // everything tagged books
```

Tags aren't available on the `file` or `database` stores.

## The stampede

A popular key expires. In the next second, 200 requests miss at once, and all 200 run the slow query
together, which can take the database down just when you most need it. That's a **cache stampede**
(or dogpile). Three defences:

1. **A lock**, so only one request rebuilds and the others wait for it or use the old value:

```laravel
$books = Cache::get('bestsellers') ?? Cache::lock('bestsellers:rebuild', 10)->block(5, function () {
    return Cache::remember('bestsellers', 600, fn () => Book::bestsellers());
});
```

2. **Stale while revalidate**: keep serving the slightly old value while one refresh happens in the
   background. Laravel's `Cache::flexible()` does this: fresh for the first number of seconds, then
   stale-but-usable until the second, refreshed after the response is sent.

```laravel
$books = Cache::flexible('bestsellers', [300, 600], fn () => Book::bestsellers());
// 0-300s: cached value; 300-600s: cached value, refreshed in the background; after 600s: rebuilt
```

3. **Jitter**: if you cache 10,000 products with the same TTL at the same moment, they all expire
   together. Add a little randomness to each TTL so they spread out:

```php
$ttl = 600;
$jitter = 0.1;   // up to 10% either way

foreach ([0.0, 0.5, 0.99] as $random) {
    echo (int) round($ttl * (1 - $jitter + 2 * $jitter * $random)) . ' ';
}
```

A cleverer version, **probabilistic early expiration**, has each request decide to refresh *a little
before* the TTL ends, with a probability that rises as expiry approaches and with how slow the value
is to compute. One request refreshes early, and the rest never see a miss.

## Don't cache what must be exact

Some data must never be stale: a bank balance, stock at checkout, permissions just revoked. Read those
from the source. A cache is for data where "a few seconds old" is fine.
