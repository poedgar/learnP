## The smallest cache

Not every cache needs Redis. Within one request, the same value is often worked out several times:
the current user's permissions are checked by the menu, the page and three policies. **Memoization**
keeps the answer in memory for the rest of the request (or the object's life), with no network at all:

```php
function memoize(callable $fn): Closure
{
    $results = [];

    return function (...$args) use ($fn, &$results) {
        $key = serialize($args);

        return $results[$key] ??= $fn(...$args);
    };
}

$calls = 0;
$slowSquare = function (int $n) use (&$calls): int {
    $calls++;

    return $n * $n;
};

$square = memoize($slowSquare);
echo $square(4) + $square(4) + $square(5) . " after {$calls} calls";
```

Laravel's `once()` helper does this for the code around it: the closure runs once per object (or once
per request in a static method), and later calls return the stored result:

```laravel
class User extends Authenticatable
{
    public function permissions(): array
    {
        return once(fn () => $this->roles()->with('permissions')->get()->pluck('permissions.*.name')->flatten()->all());
    }
}
```

Memoization only works for **pure** functions (same input, same output). And beware long-lived
processes: with Laravel Octane, a worker serves thousands of requests, so anything memoized in a static
property or singleton leaks from one user's request into the next. `once()` is flushed between Octane
requests; your own static arrays aren't.

## Many keys at once

Showing 20 books, each cached separately, means 20 cache lookups, and on a cold cache 20 database
queries: the N+1 problem again. Fetch them together: `Cache::many([...])` reads all the keys in one
round trip (`MGET` on Redis), and the misses can be loaded with **one** query and stored with
`Cache::putMany()`.

## When memory runs out

A cache has a fixed amount of memory, so when it's full, something has to go. The **eviction policy**
decides what:

- **LRU** (least recently used): throw out whatever hasn't been read for longest. A good default:
  recently used things tend to be used again. Redis's `allkeys-lru` works like this.
- **LFU** (least frequently used): throw out whatever is read least often. Better when a few keys are
  popular for a long time and a burst of one-off reads shouldn't push them out (`allkeys-lfu`).
- **TTL-based** (`volatile-ttl`): evict the keys closest to expiring.

An LRU cache can be built from a PHP array, because arrays remember insertion order: move a key to the
end whenever it's used, and evict from the front.

```php
$cache = ['a' => 1, 'b' => 2, 'c' => 3];   // oldest first

$value = $cache['a'];                        // read a: it becomes the most recent
unset($cache['a']);
$cache['a'] = $value;

array_shift($cache);                         // full: evict the least recently used
echo implode(',', array_keys($cache));
```

## Measure it

A cache that's rarely hit is pure overhead. The number to watch is the **hit ratio**: hits divided by
all lookups. Redis reports `keyspace_hits` and `keyspace_misses` in `INFO stats`. With the hit ratio and
the time of a hit and a miss, you can work out what the cache is worth:

```php
$hitRatio = 0.9;
$hitMs = 1.0;
$missMs = 120.0;

$average = $hitRatio * $hitMs + (1 - $hitRatio) * $missMs;
echo "Average {$average} ms instead of {$missMs} ms";
```

Pushing the hit ratio from 90% to 99% cuts the slow requests by ten: it's often worth a longer TTL or
warming the cache (filling popular keys before anyone asks, after a deploy).

## A checklist

1. Measure first: is it actually slow, and read often?
2. Pick the smallest cache that works: memoize within the request before reaching for Redis.
3. Put everything the value depends on in the key.
4. Choose a TTL from how stale the data may be.
5. Decide how it's invalidated, and keep the TTL as the backstop.
6. Watch the hit ratio and memory once it's live.
