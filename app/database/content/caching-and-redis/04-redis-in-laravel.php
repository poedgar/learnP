<?php

// Lesson 4 of the Caching and Redis course. The theory is in the .md file with the same name.

return [
    'slug' => 'redis-in-laravel',
    'title' => 'Redis in Laravel',
    'summary' => 'Using Redis from Laravel: cache, session and queue drivers, the Redis facade, pipelines and transactions with WATCH, atomic locks with owners and expiry, reliable queues, publish and subscribe, and sessions.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Make worker-1 release its lock.
        $locks = [];

        $acquire = function (string $name, string $owner) use (&$locks): bool {
            if (isset($locks[$name])) {
                return false;
            }
            $locks[$name] = $owner;

            return true;
        };

        foreach (['worker-1', 'worker-2'] as $worker) {
            echo $worker . ': ' . ($acquire('import:catalogue', $worker) ? 'running the import' : 'someone else is on it') . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Why does Laravel\'s Redis config use separate connections (database numbers) for the cache and for everything else?',
            'options' => [
                'It is faster',
                'So clearing the cache doesn\'t delete queued jobs or sessions',
                'Redis allows only one key per database',
                'Because the cache needs a password',
            ],
            'correct_option' => 1,
            'explanation' => '`php artisan cache:clear` flushes the cache\'s database only.',
        ],
        [
            'question' => 'What does a Redis pipeline save?',
            'options' => [
                'Memory',
                'Round trips: many commands are sent together instead of waiting for each reply',
                'Disk space',
                'Keys',
            ],
            'correct_option' => 1,
            'explanation' => 'When Redis is on another machine, each round trip costs network time.',
        ],
        [
            'question' => 'Why does a lock need an expiry?',
            'options' => [
                'To save memory',
                'So a worker that crashes while holding it doesn\'t block everyone forever',
                'Redis requires one',
                'To make it faster',
            ],
            'correct_option' => 1,
            'explanation' => 'Pick an expiry comfortably longer than the work normally takes.',
        ],
        [
            'question' => 'Why does `release()` check the lock\'s owner token before deleting it?',
            'options' => [
                'For logging',
                'A slow worker whose lock already expired (and was taken by another worker) must not release the other worker\'s lock',
                'Tokens are faster than names',
                'It doesn\'t; any worker can release',
            ],
            'correct_option' => 1,
            'explanation' => 'Releasing someone else\'s lock would let a third worker run at the same time.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $owners = ['import' => 'token-b'];

                $release = function (string $name, string $token) use (&$owners): bool {
                    if (($owners[$name] ?? null) !== $token) {
                        return false;
                    }
                    unset($owners[$name]);

                    return true;
                };

                var_dump($release('import', 'token-a'), $release('import', 'token-b'));
                PHP,
            'options' => ['`bool(true) bool(true)`', '`bool(false) bool(true)`', '`bool(true) bool(false)`', '`bool(false) bool(false)`'],
            'correct_option' => 1,
            'explanation' => 'Only the current owner\'s token releases the lock.',
        ],
        [
            'question' => 'A queue worker pops a job and then crashes. What happens to the job with Laravel\'s Redis queue?',
            'options' => [
                'It is lost',
                'It sits in the reserved set until `retry_after` passes, then becomes available to another worker',
                'It runs twice at once',
                'Redis restarts the worker',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s also why jobs should be safe to run more than once.',
        ],
        [
            'question' => 'A subscriber is restarting when a message is published. What does it get when it comes back?',
            'options' => ['The message', 'Nothing: published messages aren\'t stored', 'An error', 'The last 10 messages'],
            'correct_option' => 1,
            'explanation' => 'Pub/sub is fire-and-forget. Use a queue (or Redis streams) when every message must be handled.',
        ],
        [
            'question' => 'Why is the session id regenerated at login?',
            'options' => [
                'To log the user out elsewhere',
                'To stop session fixation: an attacker who planted a session id before login can\'t use it after',
                'Redis needs new keys',
                'To make sessions shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'The data moves to a new key; the old id becomes worthless.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Locks with owners',
            'prompt' => 'Write a `LockStore` class. `acquire(string $name, string $owner, int $seconds, int $now): bool` takes the lock if nobody holds it (or the holder\'s lock has expired). `release(string $name, string $owner, int $now): bool` frees it only if `$owner` holds it and it hasn\'t expired. `owner(string $name, int $now): ?string` says who holds it now.',
            'starter_code' => <<<'PHP'
                <?php

                class LockStore
                {
                    private array $locks = [];

                    public function acquire(string $name, string $owner, int $seconds, int $now): bool
                    {
                        if (isset($this->locks[$name])) {
                            return false;
                        }
                        $this->locks[$name] = $owner;

                        return true;
                    }

                    public function release(string $name, string $owner, int $now): bool
                    {
                        unset($this->locks[$name]);

                        return true;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LockStore
                {
                    private array $locks = [];

                    public function acquire(string $name, string $owner, int $seconds, int $now): bool
                    {
                        if ($this->owner($name, $now) !== null) {
                            return false;
                        }

                        $this->locks[$name] = ['owner' => $owner, 'expires_at' => $now + $seconds];

                        return true;
                    }

                    public function release(string $name, string $owner, int $now): bool
                    {
                        if ($this->owner($name, $now) !== $owner) {
                            return false;
                        }

                        unset($this->locks[$name]);

                        return true;
                    }

                    public function owner(string $name, int $now): ?string
                    {
                        $lock = $this->locks[$name] ?? null;
                        if ($lock === null || $now >= $lock['expires_at']) {
                            return null;
                        }

                        return $lock['owner'];
                    }
                }
                PHP,
            'hint' => 'Store the owner and the expiry. `owner()` answers "who holds it now?", and the other two methods can rely on it.',
            'checks' => [
                ['label' => 'One holder at a time', 'code' => <<<'PHP'
                    $locks = new LockStore();
                    return expect_same([$locks->acquire('import', 'w1', 60, 0), $locks->acquire('import', 'w2', 60, 10), $locks->owner('import', 10), $locks->release('import', 'w1', 20), $locks->acquire('import', 'w2', 60, 21)], [true, false, 'w1', true, true]);
                    PHP],
                ['label' => 'Expired locks can be taken', 'code' => <<<'PHP'
                    $locks = new LockStore();
                    $locks->acquire('import', 'w1', 60, 0);
                    return expect_same([$locks->owner('import', 60), $locks->acquire('import', 'w2', 60, 60), $locks->owner('import', 61)], [null, true, 'w2']);
                    PHP],
                ['label' => 'Nobody releases someone else\'s lock', 'code' => <<<'PHP'
                    $locks = new LockStore();
                    $locks->acquire('import', 'w1', 60, 0);
                    $locks->acquire('import', 'w2', 60, 70);
                    return expect_same([$locks->release('import', 'w1', 80), $locks->owner('import', 80), $locks->release('import', 'w2', 200)], [false, 'w2', false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Wait for the lock',
            'prompt' => 'Write `block(callable $attempt, int $timeoutMs, int $intervalMs, callable $sleep): bool`. Call `$attempt()` (which returns whether it got the lock); if it fails, call `$sleep($intervalMs)` and try again. Return `true` as soon as an attempt succeeds. Once the total time slept would exceed `$timeoutMs`, stop and throw a `RuntimeException` with `Unable to acquire the lock.`',
            'starter_code' => <<<'PHP'
                <?php

                function block(callable $attempt, int $timeoutMs, int $intervalMs, callable $sleep): bool
                {
                    if (! $attempt()) {
                        $sleep($intervalMs);
                    }

                    return true;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function block(callable $attempt, int $timeoutMs, int $intervalMs, callable $sleep): bool
                {
                    $slept = 0;
                    while (! $attempt()) {
                        if ($slept + $intervalMs > $timeoutMs) {
                            throw new RuntimeException('Unable to acquire the lock.');
                        }
                        $sleep($intervalMs);
                        $slept += $intervalMs;
                    }

                    return true;
                }
                PHP,
            'hint' => 'Keep a running total of the time slept, and check it before each sleep.',
            'checks' => [
                ['label' => 'Gets it on the third try', 'code' => <<<'PHP'
                    $tries = 0;
                    $slept = [];
                    $result = block(function () use (&$tries) { return ++$tries === 3; }, 1000, 250, function ($ms) use (&$slept) { $slept[] = $ms; });
                    return expect_same([$result, $tries, $slept], [true, 3, [250, 250]]);
                    PHP],
                ['label' => 'Gives up', 'code' => <<<'PHP'
                    $tries = 0;
                    $slept = 0;
                    try {
                        block(function () use (&$tries) { $tries++; return false; }, 1000, 250, function ($ms) use (&$slept) { $slept += $ms; });
                    } catch (RuntimeException $e) {
                        return expect_same([$e->getMessage(), $tries, $slept], ['Unable to acquire the lock.', 5, 1000]);
                    }
                    return 'It should give up after the timeout.';
                    PHP],
            ],
        ],
        [
            'title' => 'WATCH and EXEC',
            'prompt' => 'Write a `RedisClient` class sharing a store object (`stdClass` with `public array $data` and `public array $versions`, both key => value). `set(string $key, string $value)` writes at once and bumps the key\'s version. `watch(string $key)` remembers the key\'s current version. `multi()` starts queueing: while queued, `set()` returns `QUEUED` instead of writing. `exec(): ?array` runs the queued sets and returns their results (`OK` each), unless a watched key\'s version has changed since `watch()`, in which case nothing is written and it returns `null`. Either way, queueing and watches end. Outside a transaction `set()` returns `OK`.',
            'starter_code' => <<<'PHP'
                <?php

                class RedisClient
                {
                    private array $queued = [];

                    public function __construct(private stdClass $store) {}

                    public function set(string $key, string $value): string
                    {
                        $this->store->data[$key] = $value;

                        return 'OK';
                    }

                    public function watch(string $key): void {}

                    public function multi(): void {}

                    public function exec(): ?array
                    {
                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class RedisClient
                {
                    private ?array $queued = null;
                    private array $watched = [];

                    public function __construct(private stdClass $store) {}

                    public function set(string $key, string $value): string
                    {
                        if ($this->queued !== null) {
                            $this->queued[] = [$key, $value];

                            return 'QUEUED';
                        }

                        $this->store->data[$key] = $value;
                        $this->store->versions[$key] = ($this->store->versions[$key] ?? 0) + 1;

                        return 'OK';
                    }

                    public function watch(string $key): void
                    {
                        $this->watched[$key] = $this->store->versions[$key] ?? 0;
                    }

                    public function multi(): void
                    {
                        $this->queued = [];
                    }

                    public function exec(): ?array
                    {
                        $queued = $this->queued ?? [];
                        $watched = $this->watched;
                        $this->queued = null;
                        $this->watched = [];

                        foreach ($watched as $key => $version) {
                            if (($this->store->versions[$key] ?? 0) !== $version) {
                                return null;
                            }
                        }

                        $results = [];
                        foreach ($queued as [$key, $value]) {
                            $results[] = $this->set($key, $value);
                        }

                        return $results;
                    }
                }
                PHP,
            'hint' => 'Use `null` for "not in a transaction" and an array for the queue. In `exec()`, clear the queue and watches first, then check the versions, then run each queued `set()`.',
            'checks' => [
                ['label' => 'A transaction runs together', 'code' => <<<'PHP'
                    $store = new stdClass(); $store->data = []; $store->versions = [];
                    $a = new RedisClient($store);
                    $a->multi();
                    $queued = [$a->set('stock:dune', '4'), $a->set('order:981', 'paid')];
                    $before = $store->data;
                    return expect_same([$queued, $before, $a->exec(), $store->data, $a->set('x', '1')], [['QUEUED', 'QUEUED'], [], ['OK', 'OK'], ['stock:dune' => '4', 'order:981' => 'paid'], 'OK']);
                    PHP],
                ['label' => 'WATCH cancels on a change', 'code' => <<<'PHP'
                    $store = new stdClass(); $store->data = ['stock:dune' => '5']; $store->versions = ['stock:dune' => 1];
                    $a = new RedisClient($store);
                    $b = new RedisClient($store);
                    $a->watch('stock:dune');
                    $b->set('stock:dune', '4');
                    $a->multi();
                    $a->set('stock:dune', '4');
                    $a->set('order:981', 'paid');
                    $result = $a->exec();
                    return expect_same([$result, $store->data], [null, ['stock:dune' => '4']]);
                    PHP],
                ['label' => 'Unchanged watches go through', 'code' => <<<'PHP'
                    $store = new stdClass(); $store->data = ['stock:dune' => '5']; $store->versions = ['stock:dune' => 1];
                    $a = new RedisClient($store);
                    $a->watch('stock:dune');
                    $a->multi();
                    $a->set('stock:dune', '4');
                    return expect_same([$a->exec(), $store->data['stock:dune'], $store->versions['stock:dune']], [['OK'], '4', 2]);
                    PHP],
            ],
        ],
        [
            'title' => 'A reliable queue',
            'prompt' => 'Write a `ReliableQueue` class. `push(string $job)` adds a job with an id (1, 2, ...) and `attempts` 0. `reserve(int $now, int $retryAfter): ?array` takes the oldest waiting job, increments its attempts, marks it reserved until `$now + $retryAfter`, and returns `[\'id\' => ..., \'job\' => ..., \'attempts\' => ...]` (or `null`). `ack(int $id)` deletes a finished job. Before reserving, any reserved job whose deadline has passed (at or before `$now`) goes back to the **front** of the waiting jobs.',
            'starter_code' => <<<'PHP'
                <?php

                class ReliableQueue
                {
                    private array $waiting = [];

                    public function push(string $job): void
                    {
                        $this->waiting[] = $job;
                    }

                    public function reserve(int $now, int $retryAfter): ?array
                    {
                        $job = array_shift($this->waiting);

                        return $job === null ? null : ['id' => 1, 'job' => $job, 'attempts' => 1];
                    }

                    public function ack(int $id): void {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class ReliableQueue
                {
                    private array $waiting = [];
                    private array $reserved = [];
                    private int $nextId = 1;

                    public function push(string $job): void
                    {
                        $this->waiting[] = ['id' => $this->nextId++, 'job' => $job, 'attempts' => 0];
                    }

                    public function reserve(int $now, int $retryAfter): ?array
                    {
                        $expired = [];
                        foreach ($this->reserved as $id => $entry) {
                            if ($entry['until'] <= $now) {
                                $expired[] = $entry['job'];
                                unset($this->reserved[$id]);
                            }
                        }
                        $this->waiting = [...$expired, ...$this->waiting];

                        $job = array_shift($this->waiting);
                        if ($job === null) {
                            return null;
                        }

                        $job['attempts']++;
                        $this->reserved[$job['id']] = ['job' => $job, 'until' => $now + $retryAfter];

                        return $job;
                    }

                    public function ack(int $id): void
                    {
                        unset($this->reserved[$id]);
                    }
                }
                PHP,
            'hint' => 'Keep reserved jobs by id with their deadline. At the start of `reserve()`, move expired ones to the front of the waiting list.',
            'checks' => [
                ['label' => 'Jobs in order', 'code' => <<<'PHP'
                    $queue = new ReliableQueue();
                    $queue->push('send-invoice');
                    $queue->push('resize-cover');
                    $first = $queue->reserve(0, 60);
                    $queue->ack($first['id']);
                    return expect_same([$first, $queue->reserve(0, 60), $queue->reserve(0, 60)], [['id' => 1, 'job' => 'send-invoice', 'attempts' => 1], ['id' => 2, 'job' => 'resize-cover', 'attempts' => 1], null]);
                    PHP],
                ['label' => 'A crashed worker\'s job comes back', 'code' => <<<'PHP'
                    $queue = new ReliableQueue();
                    $queue->push('charge-card');
                    $queue->push('send-invoice');
                    $queue->reserve(0, 60);
                    $mid = $queue->reserve(30, 60);
                    $again = $queue->reserve(60, 60);
                    return expect_same([$mid['job'], $again], ['send-invoice', ['id' => 1, 'job' => 'charge-card', 'attempts' => 2]]);
                    PHP],
                ['label' => 'Acknowledged jobs never return', 'code' => <<<'PHP'
                    $queue = new ReliableQueue();
                    $queue->push('charge-card');
                    $job = $queue->reserve(0, 60);
                    $queue->ack($job['id']);
                    return expect_same($queue->reserve(1000, 60), null);
                    PHP],
            ],
        ],
        [
            'title' => 'Publish and subscribe',
            'prompt' => 'Write a `PubSub` class. `subscribe(string $pattern, callable $listener)` listens on channels matching the pattern, where `*` matches any characters (so `orders.*` matches `orders.shipped`). `publish(string $channel, string $message): int` calls each matching listener with `($channel, $message)`, in the order they subscribed, and returns how many received it.',
            'starter_code' => <<<'PHP'
                <?php

                class PubSub
                {
                    private array $listeners = [];

                    public function subscribe(string $pattern, callable $listener): void
                    {
                        $this->listeners[$pattern] = $listener;
                    }

                    public function publish(string $channel, string $message): int
                    {
                        if (isset($this->listeners[$channel])) {
                            ($this->listeners[$channel])($channel, $message);

                            return 1;
                        }

                        return 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class PubSub
                {
                    private array $listeners = [];

                    public function subscribe(string $pattern, callable $listener): void
                    {
                        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
                        $this->listeners[] = [$regex, $listener];
                    }

                    public function publish(string $channel, string $message): int
                    {
                        $received = 0;
                        foreach ($this->listeners as [$regex, $listener]) {
                            if (preg_match($regex, $channel) === 1) {
                                $listener($channel, $message);
                                $received++;
                            }
                        }

                        return $received;
                    }
                }
                PHP,
            'hint' => 'Keep a list of `[regex, listener]` pairs (several listeners can share a pattern), and turn `*` into `.*`.',
            'checks' => [
                ['label' => 'Patterns and order', 'code' => <<<'PHP'
                    $bus = new PubSub();
                    $log = [];
                    $bus->subscribe('orders.*', function ($channel, $message) use (&$log) { $log[] = "all:{$channel}:{$message}"; });
                    $bus->subscribe('orders.shipped', function ($channel, $message) use (&$log) { $log[] = "shipped:{$message}"; });
                    $bus->subscribe('orders.*', function ($channel, $message) use (&$log) { $log[] = "second:{$message}"; });
                    $counts = [$bus->publish('orders.shipped', '981'), $bus->publish('orders.paid', '982'), $bus->publish('books.new', '7')];
                    return expect_same([$counts, $log], [[3, 2, 0], ['all:orders.shipped:981', 'shipped:981', 'second:981', 'all:orders.paid:982', 'second:982']]);
                    PHP],
                ['label' => 'Dots are literal', 'code' => <<<'PHP'
                    $bus = new PubSub();
                    $bus->subscribe('orders.shipped', fn () => null);
                    return expect_same($bus->publish('ordersXshipped', 'x'), 0);
                    PHP],
            ],
        ],
        [
            'title' => 'Sessions',
            'prompt' => 'Write a `SessionStore` class built from a lifetime in seconds. `write(string $id, array $data, int $now)` stores the data and (re)starts its lifetime. `read(string $id, int $now): array` returns the data, or `[]` if missing or expired. `regenerate(string $oldId, string $newId, int $now)` moves the data to the new id with a fresh lifetime and removes the old one (a missing old session moves nothing).',
            'starter_code' => <<<'PHP'
                <?php

                class SessionStore
                {
                    private array $sessions = [];

                    public function __construct(private int $lifetime) {}

                    public function write(string $id, array $data, int $now): void
                    {
                        $this->sessions[$id] = $data;
                    }

                    public function read(string $id, int $now): array
                    {
                        return $this->sessions[$id] ?? [];
                    }

                    public function regenerate(string $oldId, string $newId, int $now): void
                    {
                        $this->sessions[$newId] = $this->sessions[$oldId];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class SessionStore
                {
                    private array $sessions = [];

                    public function __construct(private int $lifetime) {}

                    public function write(string $id, array $data, int $now): void
                    {
                        $this->sessions[$id] = ['data' => $data, 'expires_at' => $now + $this->lifetime];
                    }

                    public function read(string $id, int $now): array
                    {
                        $session = $this->sessions[$id] ?? null;
                        if ($session === null || $now >= $session['expires_at']) {
                            unset($this->sessions[$id]);

                            return [];
                        }

                        return $session['data'];
                    }

                    public function regenerate(string $oldId, string $newId, int $now): void
                    {
                        $data = $this->read($oldId, $now);
                        unset($this->sessions[$oldId]);

                        if ($data !== []) {
                            $this->write($newId, $data, $now);
                        }
                    }
                }
                PHP,
            'hint' => 'Store the data with an `expires_at`. `regenerate()` can be built from `read()`, `unset()` and `write()`.',
            'checks' => [
                ['label' => 'Expires after inactivity', 'code' => <<<'PHP'
                    $sessions = new SessionStore(7200);
                    $sessions->write('abc', ['cart' => ['dune']], 0);
                    $sessions->write('abc', ['cart' => ['dune', 'emma']], 7000);
                    return expect_same([$sessions->read('abc', 14199), $sessions->read('abc', 14200), $sessions->read('nope', 0)], [['cart' => ['dune', 'emma']], [], []]);
                    PHP],
                ['label' => 'Regenerating at login', 'code' => <<<'PHP'
                    $sessions = new SessionStore(7200);
                    $sessions->write('guest-id', ['cart' => ['dune']], 0);
                    $sessions->regenerate('guest-id', 'fresh-id', 100);
                    $sessions->regenerate('missing', 'other', 100);
                    return expect_same([$sessions->read('guest-id', 100), $sessions->read('fresh-id', 7299), $sessions->read('other', 100)], [[], ['cart' => ['dune']], []]);
                    PHP],
            ],
        ],
    ],
];
