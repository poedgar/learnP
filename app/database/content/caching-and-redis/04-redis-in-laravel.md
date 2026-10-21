## Connecting

Laravel talks to Redis through the PHP extension **phpredis** (fastest) or the **predis** package
(pure PHP, nothing to install on the server). The connection lives in `.env`:

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Those last three lines move the cache, sessions and queues onto Redis, which is the usual setup once
an app runs on more than one server. `config/database.php` defines separate Redis connections
(`default` and `cache`, using different database numbers) so that clearing the cache never deletes
queued jobs, and every key gets a prefix (`REDIS_PREFIX`) so two apps can share one Redis.

## The Redis facade

For the data structures from the last lesson, use the `Redis` facade; every Redis command is a method:

```laravel
Redis::zincrby('sales:2026-10', 1, $book->id);
$top = Redis::zrevrange('sales:2026-10', 0, 9, ['withscores' => true]);

Redis::sadd("online:{$today}", $user->id);
$count = Redis::scard("online:{$today}");

// Many commands in one round trip
Redis::pipeline(function ($pipe) use ($ids) {
    foreach ($ids as $id) {
        $pipe->incr("views:book:{$id}");
    }
});
```

A **pipeline** sends many commands at once instead of waiting for each reply, which matters when Redis
is on another machine. A **transaction** (`MULTI` ... `EXEC`, `Redis::transaction()`) also guarantees
the commands run together, with nothing from other clients in between. Add `WATCH` and the transaction
is cancelled if a watched key changed in the meantime: optimistic locking.

## Atomic locks

Some work must only run once at a time across all servers: importing a supplier's catalogue, charging
a card, rebuilding a big cache entry. Laravel's locks are built on Redis's `SET key owner NX EX seconds`:

```laravel
$lock = Cache::lock('import:catalogue', 120);

if ($lock->get()) {
    try {
        Catalogue::import();
    } finally {
        $lock->release();
    }
}

// Or wait up to 10 seconds for it, then run the closure and release automatically
Cache::lock('import:catalogue', 120)->block(10, fn () => Catalogue::import());
```

Two details make a lock safe:

- **An expiry**, so a worker that crashes while holding the lock doesn't block everyone forever.
- **An owner**: each lock records a random token, and `release()` only deletes it if the token still
  matches. Otherwise a slow worker whose lock already expired could release *someone else's* lock.

```php
$locks = [];
$acquire = function (string $name, string $owner) use (&$locks): bool {
    if (isset($locks[$name])) {
        return false;
    }
    $locks[$name] = $owner;

    return true;
};

var_dump($acquire('import', 'worker-1'), $acquire('import', 'worker-2'));
```

## Queues on Redis

With `QUEUE_CONNECTION=redis`, `dispatch(new ChargeCard($order))` pushes the serialised job onto a Redis
list, and `php artisan queue:work` pops jobs off it. A popped job is moved to a **reserved** set with a
deadline (`retry_after`); if the worker dies before finishing it, the job becomes available again when
the deadline passes, so no job is lost. **Horizon** adds a dashboard, auto-scaling workers and metrics
for Redis queues.

## Publish and subscribe

Redis can also pass messages: `PUBLISH` sends a message to a channel, and every client `SUBSCRIBE`d to
it receives it at once. Messages aren't stored, so a subscriber that isn't listening misses them.

```laravel
Redis::publish('orders', json_encode(['id' => 981, 'status' => 'shipped']));

// in a long-running command
Redis::subscribe(['orders'], function (string $message) {
    // ...
});
```

Laravel's broadcasting (live updates in the browser) can use Redis to fan events out to the websocket
server.

## Sessions

With `SESSION_DRIVER=redis`, each session is a key holding the serialised session data, with a TTL of
the session lifetime. Every request that writes the session refreshes the TTL, so a session expires
after a period of inactivity. Logging in **regenerates** the session id (a new key with the same
data) to prevent session fixation, as the PHP security course explained.
