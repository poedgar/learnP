## More than a cache

**Redis** is an in-memory data store: everything lives in RAM, so reads and writes take well under a
millisecond. As a cache it's excellent, but its real strength is that values aren't just strings. It
has lists, sets, sorted sets and hashes, with commands that work on them **atomically** on the
server. Counters, queues, leaderboards and rate limiters become one command each.

You talk to Redis with commands. Try them with `redis-cli` (or Docker:
`docker run -it --rm redis:7 redis-cli`, after starting a Redis container):

```bash
SET book:7:title "Dune"
GET book:7:title
SET session:abc "..." EX 3600      # expires in an hour
TTL session:abc                    # seconds left: -1 means no expiry, -2 means no such key
DEL book:7:title
```

Redis runs commands **one at a time**, so each command is atomic: two clients running `INCR` at the
same moment can never both read 5 and write 6. That's what makes the structures below safe to share
between all your servers.

## Strings and counters

A string holds text, a number or serialised data. `INCR` and `INCRBY` treat it as an integer:

```bash
INCR downloads:dune               # 1, then 2, 3, ...
INCRBY downloads:dune 10
SET lock:import worker-1 NX EX 30  # set only if it doesn't exist: a simple lock
```

A **rate limiter** is `INCR` plus `EXPIRE`: count requests in a key for the current minute, and let
the key vanish when the minute is over.

## Lists: queues

A list is an ordered sequence. Push on one end, pop from the other, and you have a queue (Laravel's
Redis queue driver works like this):

```bash
LPUSH jobs "send-invoice:981"
LPUSH jobs "resize-cover:7"
RPOP jobs                          # "send-invoice:981": first in, first out
LRANGE recent:views:ada 0 9        # the first 10 items
LTRIM recent:views:ada 0 99        # keep only the newest 100
```

Indexes can be negative, counting from the end: `-1` is the last item, so `LRANGE key 0 -1` is
everything.

```php
$list = ['c', 'b', 'a'];   // after LPUSH a, LPUSH b, LPUSH c

$range = function (array $list, int $start, int $stop): array {
    $n = count($list);
    $start = $start < 0 ? max(0, $n + $start) : $start;
    $stop = $stop < 0 ? $n + $stop : min($stop, $n - 1);

    return $start > $stop ? [] : array_slice($list, $start, $stop - $start + 1);
};

echo implode(',', $range($list, 0, -1)) . ' | ' . implode(',', $range($list, -2, -1)) . ' | ' . implode(',', $range($list, 0, 0));
```

## Sets: unique members

A set holds each member once, and can intersect and combine with other sets on the server:

```bash
SADD bought:dune ada bob carol
SADD bought:emma bob dave
SISMEMBER bought:dune ada          # 1
SINTER bought:dune bought:emma     # bob: bought both
SUNION bought:dune bought:emma     # everyone who bought either
SCARD bought:dune                  # 3
```

Tags, "who's online", "people who bought this also bought" and unique visitors per day all fit sets.

## Sorted sets: leaderboards

A sorted set gives each member a **score** and keeps them in order. It's the structure behind
leaderboards, "most viewed this week" and delayed jobs (score = when to run):

```bash
ZINCRBY sales:2026-10 3 dune       # dune's score goes up by 3
ZINCRBY sales:2026-10 5 emma
ZREVRANGE sales:2026-10 0 9 WITHSCORES   # top 10, highest first
ZRANK sales:2026-10 dune
```

Finding the top 10 of millions of members is fast, because the order is maintained as scores change.
Members with equal scores are ordered by name (in reverse for `ZREVRANGE`).

## Hashes: small objects

A hash is a map of fields to values under one key, good for an object you update a field at a time:

```bash
HSET cart:ada dune 1 emma 2
HINCRBY cart:ada dune 1
HGETALL cart:ada
```

## Finding keys

`KEYS book:*` lists matching keys, but it scans **everything** and blocks Redis while it does, so
never run it in production. `SCAN 0 MATCH book:* COUNT 100` walks the keys a batch at a time instead.
Patterns use `*` (anything), `?` (one character) and `[abc]` (one of these characters).

## Memory is the limit

Everything is in RAM, so set `maxmemory` and a `maxmemory-policy` (such as `allkeys-lru`, which
evicts the least recently used keys when memory is full; lesson 5 builds one). Redis can also save to
disk, but treat a cache as something you can lose and rebuild.
