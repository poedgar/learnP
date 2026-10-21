<?php

// Lesson 5 of the Caching and Redis course. The theory is in the .md file with the same name.

return [
    'slug' => 'memoization-and-eviction',
    'title' => 'Memoization, eviction and measuring',
    'summary' => 'The smallest caches: memoization and Laravel\'s once(), long-lived workers, fetching many keys at once, LRU and LFU eviction, hit ratios and what a cache is worth, and a checklist for caching well.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Read a key and watch the eviction order change.
        $capacity = 3;
        $cache = [];

        foreach (['a', 'b', 'c', 'a', 'd'] as $key) {
            if (array_key_exists($key, $cache)) {
                $value = $cache[$key];
                unset($cache[$key]);
                $cache[$key] = $value;
                continue;
            }
            if (count($cache) >= $capacity) {
                array_shift($cache);
            }
            $cache[$key] = strtoupper($key);
        }

        echo implode(',', array_keys($cache));
        PHP,

    'quiz' => [
        [
            'question' => 'What is memoization?',
            'options' => [
                'Writing notes in code',
                'Keeping a function\'s result in memory so the same call doesn\'t compute it again',
                'Saving to Redis',
                'Compressing data',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a cache with no network, living as long as the request or object.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $calls = 0;
                $results = [];

                $find = function (int $id) use (&$calls, &$results) {
                    return $results[$id] ??= (function () use (&$calls) { $calls++; return null; })();
                };

                $find(7);
                $find(7);
                $find(7);

                echo $calls;
                PHP,
            'options' => ['`1`', '`3`', '`0`', '`2`'],
            'correct_option' => 1,
            'explanation' => '`??=` treats a stored `null` as missing, so "not found" is looked up again every time.',
        ],
        [
            'question' => 'Why is memoizing in a static property risky with Laravel Octane?',
            'options' => [
                'Static properties are slow',
                'A worker serves many requests, so a value memoized for one user can leak into the next user\'s request',
                'Octane forbids static properties',
                'It uses too much disk',
            ],
            'correct_option' => 1,
            'explanation' => '`once()` is reset between Octane requests; your own static caches aren\'t.',
        ],
        [
            'question' => 'You show 20 books, each cached under its own key. What is the efficient way to read them?',
            'options' => [
                '20 separate `Cache::get()` calls',
                'One `Cache::many()` for all keys, then one query for the misses and `Cache::putMany()`',
                'Cache the whole page forever',
                'Skip the cache',
            ],
            'correct_option' => 1,
            'explanation' => 'One round trip to the cache and at most one to the database.',
        ],
        [
            'question' => 'An LRU cache is full. Which key is evicted?',
            'options' => ['The newest key', 'The key not read for the longest time', 'The biggest key', 'A random key'],
            'correct_option' => 1,
            'explanation' => 'Least recently used.',
        ],
        [
            'question' => 'When is LFU better than LRU?',
            'options' => [
                'Never',
                'When some keys stay popular for a long time and a burst of one-off reads shouldn\'t push them out',
                'When all keys are read equally',
                'When there is no memory limit',
            ],
            'correct_option' => 1,
            'explanation' => 'LFU counts how often a key is used, not just how recently.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $hits = 950;
                $misses = 50;

                echo round($hits / ($hits + $misses) * 100) . '% ' . round(0.95 * 1 + 0.05 * 100, 2) . 'ms';
                PHP,
            'options' => ['`95% 5.95ms`', '`95% 50.5ms`', '`5% 5.95ms`', '`95% 100ms`'],
            'correct_option' => 0,
            'explanation' => 'A 95% hit ratio, and an average of 5.95 ms when hits take 1 ms and misses 100 ms.',
        ],
        [
            'question' => 'What should come first when you think something needs caching?',
            'options' => ['Installing Redis', 'Measuring whether it is actually slow and read often', 'Picking a TTL', 'Adding tags'],
            'correct_option' => 1,
            'explanation' => 'Caching what is already fast only adds complexity.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Remember null too',
            'prompt' => 'Write `memo(callable $fn): Closure`, returning a function that calls `$fn` with its arguments the first time it sees them, and returns the stored result after that, **including** when the result was `null` or `false`. Different arguments are different calls.',
            'starter_code' => <<<'PHP'
                <?php

                function memo(callable $fn): Closure
                {
                    $results = [];

                    return function (...$args) use ($fn, &$results) {
                        return $results[serialize($args)] ??= $fn(...$args);
                    };
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function memo(callable $fn): Closure
                {
                    $results = [];

                    return function (...$args) use ($fn, &$results) {
                        $key = serialize($args);
                        if (! array_key_exists($key, $results)) {
                            $results[$key] = $fn(...$args);
                        }

                        return $results[$key];
                    };
                }
                PHP,
            'hint' => '`??=` treats `null` as missing; check with `array_key_exists()` instead.',
            'checks' => [
                ['label' => 'Caches per arguments', 'code' => <<<'PHP'
                    $calls = 0;
                    $add = memo(function (int $a, int $b) use (&$calls) { $calls++; return $a + $b; });
                    return expect_same([$add(1, 2), $add(1, 2), $add(2, 1), $calls], [3, 3, 3, 2]);
                    PHP],
                ['label' => 'null and false are remembered', 'code' => <<<'PHP'
                    $calls = 0;
                    $find = memo(function (int $id) use (&$calls) { $calls++; return $id === 0 ? false : null; });
                    $find(7); $find(7); $find(0); $find(0);
                    return expect_same($calls, 2);
                    PHP],
            ],
        ],
        [
            'title' => 'Once per object',
            'prompt' => 'Write a `Once` class with `call(object $owner, string $name, callable $fn): mixed`, which runs `$fn` the first time for that owner and name and returns the stored result afterwards. Results belong to their owner: another object gets its own. Use a `WeakMap`, so results disappear when the owner object does.',
            'starter_code' => <<<'PHP'
                <?php

                class Once
                {
                    private array $results = [];

                    public function call(object $owner, string $name, callable $fn): mixed
                    {
                        return $this->results[$name] ??= $fn();
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Once
                {
                    private WeakMap $results;

                    public function __construct()
                    {
                        $this->results = new WeakMap();
                    }

                    public function call(object $owner, string $name, callable $fn): mixed
                    {
                        $stored = $this->results[$owner] ?? [];
                        if (! array_key_exists($name, $stored)) {
                            $stored[$name] = $fn();
                            $this->results[$owner] = $stored;
                        }

                        return $stored[$name];
                    }
                }
                PHP,
            'hint' => 'A `WeakMap` uses objects as keys. Store an array of name => result for each owner (read it out, change it, write it back).',
            'checks' => [
                ['label' => 'Once per owner and name', 'code' => <<<'PHP'
                    $once = new Once();
                    $calls = 0;
                    $ada = new stdClass();
                    $bob = new stdClass();
                    $load = function () use (&$calls) { $calls++; return "result {$calls}"; };
                    $results = [$once->call($ada, 'permissions', $load), $once->call($ada, 'permissions', $load), $once->call($bob, 'permissions', $load), $once->call($ada, 'roles', $load)];
                    return expect_same([$results, $calls], [['result 1', 'result 1', 'result 2', 'result 3'], 3]);
                    PHP],
                ['label' => 'Forgotten with the owner', 'code' => <<<'PHP'
                    $once = new Once();
                    $owner = new stdClass();
                    $once->call($owner, 'x', fn () => str_repeat('x', 1000));
                    $map = null;
                    foreach ((new ReflectionObject($once))->getProperties() as $property) {
                        $value = $property->getValue($once);
                        if ($value instanceof WeakMap) $map = $value;
                    }
                    if ($map === null) return 'Keep the results in a WeakMap property.';
                    $before = count($map);
                    unset($owner);
                    return expect_same([$before, count($map)], [1, 0]);
                    PHP],
            ],
        ],
        [
            'title' => 'LRU',
            'prompt' => 'Write an `LruCache` class built from a capacity. `get(string $key): mixed` returns the value (or `null`) and marks it most recently used. `put(string $key, mixed $value)` stores or replaces the value, marking it most recently used; if that takes the cache over capacity, evict the least recently used key. `keys(): array` lists keys from least to most recently used.',
            'starter_code' => <<<'PHP'
                <?php

                class LruCache
                {
                    private array $items = [];

                    public function __construct(private int $capacity) {}

                    public function get(string $key): mixed
                    {
                        return $this->items[$key] ?? null;
                    }

                    public function put(string $key, mixed $value): void
                    {
                        $this->items[$key] = $value;
                        if (count($this->items) > $this->capacity) {
                            array_pop($this->items);
                        }
                    }

                    public function keys(): array
                    {
                        return array_keys($this->items);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LruCache
                {
                    private array $items = [];

                    public function __construct(private int $capacity) {}

                    public function get(string $key): mixed
                    {
                        if (! array_key_exists($key, $this->items)) {
                            return null;
                        }

                        $value = $this->items[$key];
                        unset($this->items[$key]);
                        $this->items[$key] = $value;

                        return $value;
                    }

                    public function put(string $key, mixed $value): void
                    {
                        unset($this->items[$key]);
                        $this->items[$key] = $value;

                        if (count($this->items) > $this->capacity) {
                            unset($this->items[array_key_first($this->items)]);
                        }
                    }

                    public function keys(): array
                    {
                        return array_map('strval', array_keys($this->items));
                    }
                }
                PHP,
            'hint' => 'PHP arrays keep insertion order: `unset()` and re-add a key to move it to the end, and evict `array_key_first()`.',
            'checks' => [
                ['label' => 'Evicts the least recently used', 'code' => <<<'PHP'
                    $cache = new LruCache(3);
                    $cache->put('a', 1); $cache->put('b', 2); $cache->put('c', 3);
                    $cache->get('a');
                    $cache->put('d', 4);
                    return expect_same([$cache->keys(), $cache->get('b'), $cache->get('a')], [['c', 'a', 'd'], null, 1]);
                    PHP],
                ['label' => 'Replacing moves a key too', 'code' => <<<'PHP'
                    $cache = new LruCache(2);
                    $cache->put('a', 1); $cache->put('b', 2);
                    $cache->put('a', 10);
                    $cache->put('c', 3);
                    return expect_same([$cache->keys(), $cache->get('a')], [['a', 'c'], 10]);
                    PHP],
                ['label' => 'Stores null and false', 'code' => <<<'PHP'
                    $cache = new LruCache(2);
                    $cache->put('a', false); $cache->put('b', 2);
                    $cache->get('a');
                    $cache->put('c', 3);
                    return expect_same($cache->keys(), ['a', 'c']);
                    PHP],
            ],
        ],
        [
            'title' => 'LFU',
            'prompt' => 'Write an `LfuCache` class built from a capacity. Every `get()` of a stored key and every `put()` counts as a use. When `put()` adds a new key to a full cache, first evict the key with the fewest uses; among equals, the one used least recently. `get(string $key): mixed` returns the value or `null`; `keys(): array` lists the stored keys in any order.',
            'starter_code' => <<<'PHP'
                <?php

                class LfuCache
                {
                    private array $items = [];

                    public function __construct(private int $capacity) {}

                    public function get(string $key): mixed
                    {
                        return $this->items[$key] ?? null;
                    }

                    public function put(string $key, mixed $value): void
                    {
                        if (count($this->items) >= $this->capacity) {
                            array_shift($this->items);
                        }
                        $this->items[$key] = $value;
                    }

                    public function keys(): array
                    {
                        return array_keys($this->items);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LfuCache
                {
                    private array $items = [];
                    private array $uses = [];
                    private array $lastUsed = [];
                    private int $clock = 0;

                    public function __construct(private int $capacity) {}

                    public function get(string $key): mixed
                    {
                        if (! array_key_exists($key, $this->items)) {
                            return null;
                        }

                        $this->touch($key);

                        return $this->items[$key];
                    }

                    public function put(string $key, mixed $value): void
                    {
                        if (! array_key_exists($key, $this->items) && count($this->items) >= $this->capacity) {
                            $victim = null;
                            foreach (array_keys($this->items) as $candidate) {
                                if ($victim === null
                                    || $this->uses[$candidate] < $this->uses[$victim]
                                    || ($this->uses[$candidate] === $this->uses[$victim] && $this->lastUsed[$candidate] < $this->lastUsed[$victim])) {
                                    $victim = $candidate;
                                }
                            }
                            unset($this->items[$victim], $this->uses[$victim], $this->lastUsed[$victim]);
                        }

                        $this->items[$key] = $value;
                        $this->touch($key);
                    }

                    public function keys(): array
                    {
                        return array_map('strval', array_keys($this->items));
                    }

                    private function touch(string $key): void
                    {
                        $this->uses[$key] = ($this->uses[$key] ?? 0) + 1;
                        $this->lastUsed[$key] = ++$this->clock;
                    }
                }
                PHP,
            'hint' => 'Track a use count and a "last used" tick per key. To evict, find the key with the smallest count, breaking ties by the smaller tick.',
            'checks' => [
                ['label' => 'Keeps the popular keys', 'code' => <<<'PHP'
                    $cache = new LfuCache(2);
                    $cache->put('popular', 1);
                    $cache->get('popular'); $cache->get('popular');
                    $cache->put('once', 2);
                    $cache->put('another', 3);
                    $keys = $cache->keys(); sort($keys);
                    return expect_same($keys, ['another', 'popular']);
                    PHP],
                ['label' => 'Ties go to the least recently used', 'code' => <<<'PHP'
                    $cache = new LfuCache(2);
                    $cache->put('a', 1);
                    $cache->put('b', 2);
                    $cache->put('c', 3);
                    $keys = $cache->keys(); sort($keys);
                    return expect_same([$keys, $cache->get('a')], [['b', 'c'], null]);
                    PHP],
                ['label' => 'Updating a key doesn\'t evict', 'code' => <<<'PHP'
                    $cache = new LfuCache(2);
                    $cache->put('a', 1); $cache->put('b', 2); $cache->put('a', 10);
                    $keys = $cache->keys(); sort($keys);
                    return expect_same([$keys, $cache->get('a')], [['a', 'b'], 10]);
                    PHP],
            ],
        ],
        [
            'title' => 'Many at once',
            'prompt' => 'Write `rememberMany(array &$store, array $keys, callable $loadMissing): array`. `$store` is a simple key => value cache. Return key => value for every key, in the order given. Keys already in the store come from it; all the others are loaded with **one** call to `$loadMissing($missingKeys)`, which returns key => value, and are then stored. Don\'t call the loader if nothing is missing.',
            'starter_code' => <<<'PHP'
                <?php

                function rememberMany(array &$store, array $keys, callable $loadMissing): array
                {
                    $result = [];
                    foreach ($keys as $key) {
                        $result[$key] = $store[$key] ?? $loadMissing([$key])[$key];
                    }

                    return $result;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function rememberMany(array &$store, array $keys, callable $loadMissing): array
                {
                    $missing = array_values(array_filter($keys, fn ($key) => ! array_key_exists($key, $store)));

                    if ($missing !== []) {
                        foreach ($loadMissing($missing) as $key => $value) {
                            $store[$key] = $value;
                        }
                    }

                    $result = [];
                    foreach ($keys as $key) {
                        $result[$key] = $store[$key] ?? null;
                    }

                    return $result;
                }
                PHP,
            'hint' => 'Work out the missing keys first, load them all in one call, store them, then build the result in the requested order.',
            'checks' => [
                ['label' => 'One load for the misses', 'code' => <<<'PHP'
                    $store = ['book:2' => 'Emma'];
                    $loads = [];
                    $result = rememberMany($store, ['book:1', 'book:2', 'book:3'], function (array $keys) use (&$loads) {
                        $loads[] = $keys;
                        return ['book:1' => 'Dune', 'book:3' => 'Ulysses'];
                    });
                    return expect_same([$result, $loads, $store['book:3']], [['book:1' => 'Dune', 'book:2' => 'Emma', 'book:3' => 'Ulysses'], [['book:1', 'book:3']], 'Ulysses']);
                    PHP],
                ['label' => 'No load when everything is cached', 'code' => <<<'PHP'
                    $store = ['a' => 1, 'b' => 2];
                    $loaded = false;
                    $result = rememberMany($store, ['b', 'a'], function () use (&$loaded) { $loaded = true; return []; });
                    return expect_same([$result, $loaded], [['b' => 2, 'a' => 1], false]);
                    PHP],
            ],
        ],
        [
            'title' => 'What is it worth',
            'prompt' => 'Write `cacheReport(int $hits, int $misses, float $hitMs, float $missMs, int $requestsPerMinute): array` returning `hit_ratio` (hits over all lookups, rounded to 3 decimals; 0.0 with no lookups), `average_ms` (the hit ratio weighting the two times, rounded to 2 decimals) and `slow_requests_per_minute` (requests per minute that miss, rounded to the nearest whole number).',
            'starter_code' => <<<'PHP'
                <?php

                function cacheReport(int $hits, int $misses, float $hitMs, float $missMs, int $requestsPerMinute): array
                {
                    return ['hit_ratio' => $hits / $misses, 'average_ms' => ($hitMs + $missMs) / 2, 'slow_requests_per_minute' => $misses];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheReport(int $hits, int $misses, float $hitMs, float $missMs, int $requestsPerMinute): array
                {
                    $lookups = $hits + $misses;
                    $ratio = $lookups === 0 ? 0.0 : $hits / $lookups;

                    return [
                        'hit_ratio' => round($ratio, 3),
                        'average_ms' => round($ratio * $hitMs + (1 - $ratio) * $missMs, 2),
                        'slow_requests_per_minute' => (int) round($requestsPerMinute * (1 - $ratio)),
                    ];
                }
                PHP,
            'hint' => 'Work out the unrounded ratio first and use it for the other two numbers; round only at the end.',
            'checks' => [
                ['label' => 'A good cache', 'code' => "return expect_same(cacheReport(950, 50, 1.0, 100.0, 600), ['hit_ratio' => 0.95, 'average_ms' => 5.95, 'slow_requests_per_minute' => 30]);"],
                ['label' => 'A poor one', 'code' => "return expect_same(cacheReport(1, 2, 2.0, 50.0, 90), ['hit_ratio' => 0.333, 'average_ms' => 34.0, 'slow_requests_per_minute' => 60]);"],
                ['label' => 'No lookups yet', 'code' => "return expect_same(cacheReport(0, 0, 1.0, 80.0, 100), ['hit_ratio' => 0.0, 'average_ms' => 80.0, 'slow_requests_per_minute' => 100]);"],
            ],
        ],
    ],
];
