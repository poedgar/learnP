<?php

// Lesson 1 of the Caching and Redis course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// The challenges keep the cache in a plain array: key => ['value' => ..., 'expires_at' => int|null].

return [
    'slug' => 'cache-basics',
    'title' => 'Cache basics',
    'summary' => 'Why and when to cache: cache-aside, hits and misses, Laravel\'s Cache facade and remember(), TTLs, cache stores, naming keys, and what not to cache.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a shorter TTL.
        $store = [];
        $now = 1000;
        $queries = 0;

        foreach ([1000, 1030, 1070] as $now) {
            $item = $store['bestsellers'] ?? null;
            if ($item === null || $now >= $item['expires_at']) {
                $queries++;
                $store['bestsellers'] = ['value' => ['Dune', 'Emma'], 'expires_at' => $now + 60];
            }
        }

        echo "{$queries} queries for 3 requests";
        PHP,

    'quiz' => [
        [
            'question' => 'What is the cache-aside pattern?',
            'options' => [
                'Writing to the cache and the database at the same time',
                'Looking in the cache first; on a miss, doing the work and storing the result for next time',
                'Keeping the cache on a separate server',
                'Caching only on the side of the client',
            ],
            'correct_option' => 1,
            'explanation' => '`Cache::remember()` does exactly that in one call.',
        ],
        [
            'question' => 'When does the closure passed to `Cache::remember()` run?',
            'options' => ['On every call', 'Only when the key is missing or expired', 'Only the first time the app starts', 'After the TTL, in the background'],
            'correct_option' => 1,
            'explanation' => 'On a hit, the stored value is returned and the closure never runs.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $expiresAt = 1000 + 60;

                foreach ([1000, 1059, 1060, 1061] as $now) {
                    echo ($now < $expiresAt ? 'hit' : 'miss') . ' ';
                }
                PHP,
            'options' => ['`hit hit hit miss `', '`hit hit miss miss `', '`hit miss miss miss `', '`miss hit hit hit `'],
            'correct_option' => 1,
            'explanation' => 'A 60-second TTL stored at t=1000 is valid up to, but not including, t=1060.',
        ],
        [
            'question' => 'What does choosing a TTL really decide?',
            'options' => [
                'How much memory the cache uses',
                'How stale the data is allowed to be',
                'How fast the query runs',
                'Which store is used',
            ],
            'correct_option' => 1,
            'explanation' => 'Countries can be a day old; stock levels maybe a few seconds.',
        ],
        [
            'question' => 'Your app runs on three servers behind a load balancer. Why is the `file` cache store a poor choice?',
            'options' => [
                'Files are slow to read',
                'Each server keeps its own cache, so they disagree, and forgetting a key on one doesn\'t clear the others',
                'The file store has no TTL',
                'It can\'t store arrays',
            ],
            'correct_option' => 1,
            'explanation' => 'Use a shared store such as Redis, Memcached or the database.',
        ],
        [
            'question' => 'The bestseller list is different for each language, but it is cached under `books:bestsellers`. What goes wrong?',
            'options' => [
                'Nothing',
                'Whichever language is cached first is shown to everyone until it expires',
                'The cache throws an error',
                'Each language misses every time',
            ],
            'correct_option' => 1,
            'explanation' => 'Put everything the value depends on into the key: `books:bestsellers:de`.',
        ],
        [
            'question' => 'What does `Cache::add(\'lock:import\', true, 60)` do if the key already exists?',
            'options' => ['Overwrites it', 'Nothing, and returns false', 'Throws', 'Extends the TTL'],
            'correct_option' => 1,
            'explanation' => '`add()` only stores when the key is missing, which makes it useful for "only once" checks.',
        ],
        [
            'question' => 'Which is the best candidate for caching?',
            'options' => [
                'Looking up a user by primary key',
                'A slow report built from many tables, read hundreds of times an hour, changed nightly',
                'A bank balance shown right after a payment',
                'A one-time password',
            ],
            'correct_option' => 1,
            'explanation' => 'Cache what is slow, read often and changed rarely.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Read from the cache',
            'prompt' => 'Write `cacheGet(array $store, string $key, int $now, mixed $default = null): mixed`. Items look like `[\'value\' => ..., \'expires_at\' => int|null]`, where `null` means never. Return the value if the item exists and hasn\'t expired (`$now` before `expires_at`), otherwise `$default`.',
            'starter_code' => <<<'PHP'
                <?php

                function cacheGet(array $store, string $key, int $now, mixed $default = null): mixed
                {
                    return $store[$key]['value'] ?? $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheGet(array $store, string $key, int $now, mixed $default = null): mixed
                {
                    $item = $store[$key] ?? null;
                    if ($item === null) {
                        return $default;
                    }
                    if ($item['expires_at'] !== null && $now >= $item['expires_at']) {
                        return $default;
                    }

                    return $item['value'];
                }
                PHP,
            'hint' => 'Missing, or `expires_at` not null and `$now >= expires_at`, both give the default.',
            'checks' => [
                ['label' => 'Hits and misses', 'code' => <<<'PHP'
                    $store = ['rate' => ['value' => 1.17, 'expires_at' => 1060], 'countries' => ['value' => ['GB', 'DE'], 'expires_at' => null]];
                    return expect_same([cacheGet($store, 'rate', 1059), cacheGet($store, 'rate', 1060), cacheGet($store, 'rate', 2000, 'none'), cacheGet($store, 'countries', 999999), cacheGet($store, 'nope', 0, [])], [1.17, null, 'none', ['GB', 'DE'], []]);
                    PHP],
                ['label' => 'Falsy values are still hits', 'code' => "return expect_same([cacheGet(['n' => ['value' => 0, 'expires_at' => null]], 'n', 0, 'default'), cacheGet(['f' => ['value' => false, 'expires_at' => 10]], 'f', 5, 'default')], [0, false]);"],
            ],
        ],
        [
            'title' => 'Remember',
            'prompt' => 'Write `cacheRemember(array &$store, string $key, int $ttl, int $now, callable $callback): mixed`. On a hit (present and not expired), return the stored value without calling the callback. On a miss, call it, store the result with `expires_at` `$now + $ttl`, and return it. A `null` result is returned but not stored.',
            'starter_code' => <<<'PHP'
                <?php

                function cacheRemember(array &$store, string $key, int $ttl, int $now, callable $callback): mixed
                {
                    $value = $callback();
                    $store[$key] = ['value' => $value, 'expires_at' => $now + $ttl];

                    return $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheRemember(array &$store, string $key, int $ttl, int $now, callable $callback): mixed
                {
                    $item = $store[$key] ?? null;
                    if ($item !== null && ($item['expires_at'] === null || $now < $item['expires_at'])) {
                        return $item['value'];
                    }

                    $value = $callback();
                    if ($value !== null) {
                        $store[$key] = ['value' => $value, 'expires_at' => $now + $ttl];
                    }

                    return $value;
                }
                PHP,
            'hint' => 'Check for a valid item first and return early. Only store non-null results.',
            'checks' => [
                ['label' => 'Works once per TTL', 'code' => <<<'PHP'
                    $store = [];
                    $calls = 0;
                    $load = function () use (&$calls) { $calls++; return ['Dune', 'Emma']; };
                    $results = [cacheRemember($store, 'best', 60, 1000, $load), cacheRemember($store, 'best', 60, 1030, $load), cacheRemember($store, 'best', 60, 1060, $load)];
                    return expect_same([$calls, $results[1], $store['best']['expires_at']], [2, ['Dune', 'Emma'], 1120]);
                    PHP],
                ['label' => 'null is not stored', 'code' => <<<'PHP'
                    $store = [];
                    $calls = 0;
                    $load = function () use (&$calls) { $calls++; return null; };
                    cacheRemember($store, 'missing-book', 60, 0, $load);
                    cacheRemember($store, 'missing-book', 60, 1, $load);
                    return expect_same([$calls, $store], [2, []]);
                    PHP],
            ],
        ],
        [
            'title' => 'Only if missing',
            'prompt' => 'Write `cacheAdd(array &$store, string $key, mixed $value, int $ttl, int $now): bool`. Store the value (expiring at `$now + $ttl`) only if the key is missing or expired, and return whether it was stored.',
            'starter_code' => <<<'PHP'
                <?php

                function cacheAdd(array &$store, string $key, mixed $value, int $ttl, int $now): bool
                {
                    $store[$key] = ['value' => $value, 'expires_at' => $now + $ttl];

                    return true;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheAdd(array &$store, string $key, mixed $value, int $ttl, int $now): bool
                {
                    $item = $store[$key] ?? null;
                    if ($item !== null && ($item['expires_at'] === null || $now < $item['expires_at'])) {
                        return false;
                    }

                    $store[$key] = ['value' => $value, 'expires_at' => $now + $ttl];

                    return true;
                }
                PHP,
            'hint' => 'If a valid item is there, return `false` without touching it.',
            'checks' => [
                ['label' => 'First one wins', 'code' => <<<'PHP'
                    $store = [];
                    $results = [cacheAdd($store, 'lock:import', 'worker-1', 60, 0), cacheAdd($store, 'lock:import', 'worker-2', 60, 30)];
                    return expect_same([$results, $store['lock:import']['value']], [[true, false], 'worker-1']);
                    PHP],
                ['label' => 'Expired keys can be added again', 'code' => <<<'PHP'
                    $store = ['lock:import' => ['value' => 'worker-1', 'expires_at' => 60]];
                    return expect_same([cacheAdd($store, 'lock:import', 'worker-2', 60, 60), $store['lock:import']], [true, ['value' => 'worker-2', 'expires_at' => 120]]);
                    PHP],
                ['label' => 'Forever keys stay', 'code' => "\$store = ['k' => ['value' => 1, 'expires_at' => null]]; return expect_same(cacheAdd(\$store, 'k', 2, 60, 10**9), false);"],
            ],
        ],
        [
            'title' => 'Counters',
            'prompt' => 'Write `cacheIncrement(array &$store, string $key, int $now, int $by = 1): int`. A missing or expired key starts from 0 with no expiry; an existing one keeps its expiry. Store and return the new value. If the current value isn\'t an integer, throw an `UnexpectedValueException`.',
            'starter_code' => <<<'PHP'
                <?php

                function cacheIncrement(array &$store, string $key, int $now, int $by = 1): int
                {
                    $value = ($store[$key]['value'] ?? 0) + $by;
                    $store[$key] = ['value' => $value, 'expires_at' => null];

                    return $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheIncrement(array &$store, string $key, int $now, int $by = 1): int
                {
                    $item = $store[$key] ?? null;
                    if ($item === null || ($item['expires_at'] !== null && $now >= $item['expires_at'])) {
                        $item = ['value' => 0, 'expires_at' => null];
                    }

                    if (! is_int($item['value'])) {
                        throw new UnexpectedValueException("The value at {$key} is not an integer.");
                    }

                    $item['value'] += $by;
                    $store[$key] = $item;

                    return $item['value'];
                }
                PHP,
            'hint' => 'Replace a missing or expired item with `[\'value\' => 0, \'expires_at\' => null]`, then add.',
            'checks' => [
                ['label' => 'Counts', 'code' => <<<'PHP'
                    $store = [];
                    return expect_same([cacheIncrement($store, 'downloads', 0), cacheIncrement($store, 'downloads', 0), cacheIncrement($store, 'downloads', 0, 10), cacheIncrement($store, 'downloads', 0, -2)], [1, 2, 12, 10]);
                    PHP],
                ['label' => 'Keeps or resets the expiry', 'code' => <<<'PHP'
                    $store = ['hits' => ['value' => 4, 'expires_at' => 60], 'old' => ['value' => 9, 'expires_at' => 10]];
                    cacheIncrement($store, 'hits', 30);
                    cacheIncrement($store, 'old', 30);
                    return expect_same($store, ['hits' => ['value' => 5, 'expires_at' => 60], 'old' => ['value' => 1, 'expires_at' => null]]);
                    PHP],
                ['label' => 'Only integers', 'code' => "\$store = ['name' => ['value' => 'Dune', 'expires_at' => null]]; return expect_throws(UnexpectedValueException::class, fn () => cacheIncrement(\$store, 'name', 0));"],
            ],
        ],
        [
            'title' => 'Good keys',
            'prompt' => 'Write `cacheKey(string $prefix, array $params): string`. Sort the params by name, and build `PREFIX:` followed by `http_build_query()` of them, so the order params arrive in doesn\'t matter. With no params, the key is just the prefix. If the key would be longer than 100 characters, use `PREFIX:` followed by the `md5()` of the query string instead.',
            'starter_code' => <<<'PHP'
                <?php

                function cacheKey(string $prefix, array $params): string
                {
                    return $prefix . ':' . implode(',', $params);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cacheKey(string $prefix, array $params): string
                {
                    if ($params === []) {
                        return $prefix;
                    }

                    ksort($params);
                    $query = http_build_query($params);
                    $key = "{$prefix}:{$query}";

                    return strlen($key) > 100 ? "{$prefix}:" . md5($query) : $key;
                }
                PHP,
            'hint' => '`ksort()` sorts by key in place; build the key, then check `strlen()`.',
            'checks' => [
                ['label' => 'Order doesn\'t matter', 'code' => "return expect_same([cacheKey('books:search', ['q' => 'dune', 'page' => 2]), cacheKey('books:search', ['page' => 2, 'q' => 'dune'])], ['books:search:page=2&q=dune', 'books:search:page=2&q=dune']);"],
                ['label' => 'No params', 'code' => "return expect_same(cacheKey('books:bestsellers', []), 'books:bestsellers');"],
                ['label' => 'Long keys are hashed', 'code' => <<<'PHP'
                    $params = ['q' => str_repeat('science fiction ', 8), 'sort' => 'title'];
                    ksort($params);
                    return expect_same(cacheKey('books:search', $params), 'books:search:' . md5(http_build_query($params)));
                    PHP],
            ],
        ],
        [
            'title' => 'Read it once',
            'prompt' => 'Write `cachePull(array &$store, string $key, int $now, mixed $default = null): mixed` for one-time values such as login codes. Return the value like a normal read (the default if missing or expired), and remove the key from the store either way.',
            'starter_code' => <<<'PHP'
                <?php

                function cachePull(array &$store, string $key, int $now, mixed $default = null): mixed
                {
                    return $store[$key]['value'] ?? $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cachePull(array &$store, string $key, int $now, mixed $default = null): mixed
                {
                    $item = $store[$key] ?? null;
                    unset($store[$key]);

                    if ($item === null || ($item['expires_at'] !== null && $now >= $item['expires_at'])) {
                        return $default;
                    }

                    return $item['value'];
                }
                PHP,
            'hint' => 'Take the item out of the store first, then decide what to return.',
            'checks' => [
                ['label' => 'Only once', 'code' => <<<'PHP'
                    $store = ['login-code:ada' => ['value' => '481516', 'expires_at' => 600]];
                    return expect_same([cachePull($store, 'login-code:ada', 100), cachePull($store, 'login-code:ada', 101, 'gone'), $store], ['481516', 'gone', []]);
                    PHP],
                ['label' => 'Expired codes are removed too', 'code' => <<<'PHP'
                    $store = ['login-code:bob' => ['value' => '2342', 'expires_at' => 600], 'other' => ['value' => 1, 'expires_at' => null]];
                    return expect_same([cachePull($store, 'login-code:bob', 600), array_keys($store)], [null, ['other']]);
                    PHP],
            ],
        ],
    ],
];
