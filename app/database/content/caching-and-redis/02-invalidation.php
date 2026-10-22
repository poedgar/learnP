<?php

// Lesson 2 of the Caching and Redis course. The theory is in the .md file with the same name.

return [
    'slug' => 'invalidation',
    'title' => 'Keeping caches fresh',
    'summary' => 'Cache invalidation: TTLs as a backstop, forgetting keys when data changes, versioned keys and tags for groups, and stopping stampedes with locks, stale-while-revalidate, jitter and early expiration.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Bump the version and look again.
        $cache = ['books:v1:bestsellers' => ['Dune', 'Emma']];
        $version = 1;

        $get = fn (string $name) => $cache["books:v{$version}:{$name}"] ?? 'miss';

        echo json_encode($get('bestsellers')) . PHP_EOL;
        $version++;
        echo json_encode($get('bestsellers'));
        PHP,

    'quiz' => [
        [
            'question' => 'Why give a cached value a TTL even when you also forget it when the data changes?',
            'options' => [
                'Laravel requires it',
                'As a backstop: if some change path forgets to clear the key, the data still corrects itself within the TTL',
                'It makes the cache faster',
                'It saves disk space',
            ],
            'correct_option' => 1,
            'explanation' => 'Forgetting to invalidate one key is a very common bug.',
        ],
        [
            'question' => 'A book moves from author 3 to author 5. Which author pages must be cleared?',
            'options' => ['Only author 5\'s', 'Only author 3\'s', 'Both author 3\'s and author 5\'s', 'Neither'],
            'correct_option' => 2,
            'explanation' => 'The old author\'s list still contains the book, so use `getOriginal(\'author_id\')` too.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $cache = ['books:v1:list' => 'old list'];
                $version = 1;

                $version++;
                $cache["books:v{$version}:list"] = 'new list';

                echo count($cache) . ' ' . $cache["books:v{$version}:list"];
                PHP,
            'options' => ['`1 new list`', '`2 new list`', '`2 old list`', '`1 old list`'],
            'correct_option' => 1,
            'explanation' => 'The old key is still there, but nothing reads it any more; its TTL will clear it.',
        ],
        [
            'question' => 'Which cache stores support `Cache::tags()`?',
            'options' => ['All of them', 'Redis and Memcached, but not file or database', 'Only file', 'Only array'],
            'correct_option' => 1,
            'explanation' => 'Tags need a store that can track groups of keys efficiently.',
        ],
        [
            'question' => 'What is a cache stampede?',
            'options' => [
                'Too many keys in the cache',
                'A popular key expires and many requests miss at once, all running the slow work together',
                'Two servers with different caches',
                'A cache that never expires',
            ],
            'correct_option' => 1,
            'explanation' => 'It can overload the database exactly when traffic is highest.',
        ],
        [
            'question' => 'With `Cache::flexible(\'bestsellers\', [300, 600], ...)`, what happens to a request 450 seconds after the value was cached?',
            'options' => [
                'It waits for the value to be rebuilt',
                'It gets the cached value, and a refresh runs after the response is sent',
                'It gets null',
                'It throws',
            ],
            'correct_option' => 1,
            'explanation' => 'Between the two numbers the value is stale but usable; after 600 it\'s rebuilt before answering.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $ttl = 1000;
                $jitter = 0.2;

                $low = (int) round($ttl * (1 - $jitter));
                $high = (int) round($ttl * (1 + $jitter));

                echo "{$low}-{$high}";
                PHP,
            'options' => ['`800-1200`', '`900-1100`', '`1000-1200`', '`980-1020`'],
            'correct_option' => 0,
            'explanation' => 'TTLs spread over that range, so keys cached together don\'t expire together.',
        ],
        [
            'question' => 'Which should never be served from a cache?',
            'options' => ['The list of countries', 'A blog post', 'The stock level checked at the moment of payment', 'Yesterday\'s sales report'],
            'correct_option' => 2,
            'explanation' => 'At checkout, a few seconds stale can mean selling something you don\'t have.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Which keys to forget',
            'prompt' => 'Write `keysToForget(array $original, array $book): array`. `$original` is the book before saving and `$book` after. Always forget `book:ID` and `author:AUTHOR_ID:books` for the current author. If `author_id` changed, also forget the old author\'s key. If `sales` changed, also forget `books:bestsellers`. Return the keys in that order, without duplicates.',
            'starter_code' => <<<'PHP'
                <?php

                function keysToForget(array $original, array $book): array
                {
                    return ["book:{$book['id']}"];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function keysToForget(array $original, array $book): array
                {
                    $keys = ["book:{$book['id']}", "author:{$book['author_id']}:books"];

                    if (($original['author_id'] ?? null) !== $book['author_id']) {
                        $keys[] = "author:{$original['author_id']}:books";
                    }
                    if (($original['sales'] ?? null) !== ($book['sales'] ?? null)) {
                        $keys[] = 'books:bestsellers';
                    }

                    return array_values(array_unique($keys));
                }
                PHP,
            'hint' => 'Start with the two keys that always go, then compare the old and new `author_id` and `sales`.',
            'checks' => [
                ['label' => 'A title fix', 'code' => "return expect_same(keysToForget(['id' => 7, 'author_id' => 3, 'title' => 'Dnue', 'sales' => 10], ['id' => 7, 'author_id' => 3, 'title' => 'Dune', 'sales' => 10]), ['book:7', 'author:3:books']);"],
                ['label' => 'A new author', 'code' => "return expect_same(keysToForget(['id' => 7, 'author_id' => 3, 'sales' => 10], ['id' => 7, 'author_id' => 5, 'sales' => 10]), ['book:7', 'author:5:books', 'author:3:books']);"],
                ['label' => 'More sales', 'code' => "return expect_same(keysToForget(['id' => 7, 'author_id' => 3, 'sales' => 10], ['id' => 7, 'author_id' => 3, 'sales' => 11]), ['book:7', 'author:3:books', 'books:bestsellers']);"],
            ],
        ],
        [
            'title' => 'Versioned groups',
            'prompt' => 'Write a `VersionedCache` class. `put(string $group, string $name, mixed $value)` and `get(string $group, string $name): mixed` (null on a miss) store values under keys like `books:v1:bestsellers`, using the group\'s current version (starting at 1). `flush(string $group)` bumps the group\'s version, so its old values are no longer found. `keys(): array` lists every stored key (old ones too).',
            'starter_code' => <<<'PHP'
                <?php

                class VersionedCache
                {
                    private array $store = [];

                    public function put(string $group, string $name, mixed $value): void
                    {
                        $this->store["{$group}:{$name}"] = $value;
                    }

                    public function get(string $group, string $name): mixed
                    {
                        return $this->store["{$group}:{$name}"] ?? null;
                    }

                    public function flush(string $group): void
                    {
                        $this->store = [];
                    }

                    public function keys(): array
                    {
                        return array_keys($this->store);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class VersionedCache
                {
                    private array $store = [];
                    private array $versions = [];

                    public function put(string $group, string $name, mixed $value): void
                    {
                        $this->store[$this->key($group, $name)] = $value;
                    }

                    public function get(string $group, string $name): mixed
                    {
                        return $this->store[$this->key($group, $name)] ?? null;
                    }

                    public function flush(string $group): void
                    {
                        $this->versions[$group] = ($this->versions[$group] ?? 1) + 1;
                    }

                    public function keys(): array
                    {
                        return array_keys($this->store);
                    }

                    private function key(string $group, string $name): string
                    {
                        $version = $this->versions[$group] ?? 1;

                        return "{$group}:v{$version}:{$name}";
                    }
                }
                PHP,
            'hint' => 'A private `key()` method that builds the key from the current version. `flush()` only changes the version; it deletes nothing.',
            'checks' => [
                ['label' => 'Flushing a group', 'code' => <<<'PHP'
                    $cache = new VersionedCache();
                    $cache->put('books', 'bestsellers', ['Dune']);
                    $cache->put('authors', 'list', ['Herbert']);
                    $cache->flush('books');
                    $cache->put('books', 'bestsellers', ['Emma']);
                    return expect_same([$cache->get('books', 'bestsellers'), $cache->get('authors', 'list'), $cache->get('books', 'missing')], [['Emma'], ['Herbert'], null]);
                    PHP],
                ['label' => 'Old keys are left to expire', 'code' => <<<'PHP'
                    $cache = new VersionedCache();
                    $cache->put('books', 'bestsellers', ['Dune']);
                    $cache->flush('books');
                    $cache->flush('books');
                    $cache->put('books', 'bestsellers', ['Emma']);
                    return expect_same($cache->keys(), ['books:v1:bestsellers', 'books:v3:bestsellers']);
                    PHP],
            ],
        ],
        [
            'title' => 'Tags',
            'prompt' => 'Write a `TaggedCache` class. `put(array $tags, string $key, mixed $value)` stores a value with some tags; `get(string $key): mixed` returns it or `null`; `flushTag(string $tag): int` removes every key that has that tag and returns how many were removed. Storing a key again replaces its value and its tags.',
            'starter_code' => <<<'PHP'
                <?php

                class TaggedCache
                {
                    private array $values = [];

                    public function put(array $tags, string $key, mixed $value): void
                    {
                        $this->values[$key] = $value;
                    }

                    public function get(string $key): mixed
                    {
                        return $this->values[$key] ?? null;
                    }

                    public function flushTag(string $tag): int
                    {
                        return 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TaggedCache
                {
                    private array $values = [];
                    private array $tags = [];

                    public function put(array $tags, string $key, mixed $value): void
                    {
                        $this->values[$key] = $value;
                        $this->tags[$key] = $tags;
                    }

                    public function get(string $key): mixed
                    {
                        return $this->values[$key] ?? null;
                    }

                    public function flushTag(string $tag): int
                    {
                        $removed = 0;
                        foreach ($this->tags as $key => $tags) {
                            if (in_array($tag, $tags, true)) {
                                unset($this->values[$key], $this->tags[$key]);
                                $removed++;
                            }
                        }

                        return $removed;
                    }
                }
                PHP,
            'hint' => 'Keep each key\'s tags alongside its value, and loop over them in `flushTag()`.',
            'checks' => [
                ['label' => 'Flushes by tag', 'code' => <<<'PHP'
                    $cache = new TaggedCache();
                    $cache->put(['books', 'author:3'], 'book:7', 'Dune');
                    $cache->put(['books', 'author:5'], 'book:8', 'Emma');
                    $cache->put(['authors'], 'author:3', 'Herbert');
                    $removed = $cache->flushTag('author:3');
                    return expect_same([$removed, $cache->get('book:7'), $cache->get('book:8'), $cache->get('author:3')], [1, null, 'Emma', 'Herbert']);
                    PHP],
                ['label' => 'Re-storing replaces the tags', 'code' => <<<'PHP'
                    $cache = new TaggedCache();
                    $cache->put(['books'], 'book:7', 'Dune');
                    $cache->put(['featured'], 'book:7', 'Dune (featured)');
                    return expect_same([$cache->flushTag('books'), $cache->get('book:7'), $cache->flushTag('featured'), $cache->get('book:7')], [0, 'Dune (featured)', 1, null]);
                    PHP],
            ],
        ],
        [
            'title' => 'Stale while revalidate',
            'prompt' => 'Write `flexible(array &$store, array &$refresh, string $key, array $times, int $now, callable $callback): mixed`, where `$times` is `[fresh, stale]` in seconds and items are `[\'value\' => ..., \'created_at\' => int]`. Younger than `fresh`: return the value. From `fresh` up to (not including) `stale`: return the value and add the key to `$refresh` (once). Missing or at least `stale` old: call the callback, store it with `created_at` `$now`, and return it.',
            'starter_code' => <<<'PHP'
                <?php

                function flexible(array &$store, array &$refresh, string $key, array $times, int $now, callable $callback): mixed
                {
                    $value = $callback();
                    $store[$key] = ['value' => $value, 'created_at' => $now];

                    return $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function flexible(array &$store, array &$refresh, string $key, array $times, int $now, callable $callback): mixed
                {
                    [$fresh, $stale] = $times;
                    $item = $store[$key] ?? null;

                    if ($item !== null) {
                        $age = $now - $item['created_at'];
                        if ($age < $fresh) {
                            return $item['value'];
                        }
                        if ($age < $stale) {
                            if (! in_array($key, $refresh, true)) {
                                $refresh[] = $key;
                            }

                            return $item['value'];
                        }
                    }

                    $value = $callback();
                    $store[$key] = ['value' => $value, 'created_at' => $now];

                    return $value;
                }
                PHP,
            'hint' => 'Work out the age once, then three cases: fresh, stale (queue a refresh), too old or missing (rebuild now).',
            'checks' => [
                ['label' => 'Fresh, stale and expired', 'code' => <<<'PHP'
                    $store = [];
                    $refresh = [];
                    $calls = 0;
                    $build = function () use (&$calls) { $calls++; return "list #{$calls}"; };
                    $results = [];
                    foreach ([0, 299, 300, 450, 600] as $now) {
                        $results[] = flexible($store, $refresh, 'bestsellers', [300, 600], $now, $build);
                    }
                    return expect_same([$results, $calls, $refresh], [['list #1', 'list #1', 'list #1', 'list #1', 'list #2'], 2, ['bestsellers']]);
                    PHP],
            ],
        ],
        [
            'title' => 'A little jitter',
            'prompt' => 'Write `jitteredTtl(int $ttl, float $fraction, float $random): int`, where `$random` is a number from 0 up to 1 (as `mt_rand() / mt_getrandmax()` would give). Map it evenly onto the range from `$ttl * (1 - $fraction)` to `$ttl * (1 + $fraction)`, round to the nearest second, and never return less than 1.',
            'starter_code' => <<<'PHP'
                <?php

                function jitteredTtl(int $ttl, float $fraction, float $random): int
                {
                    return $ttl + (int) ($random * $fraction);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function jitteredTtl(int $ttl, float $fraction, float $random): int
                {
                    $low = $ttl * (1 - $fraction);
                    $high = $ttl * (1 + $fraction);

                    return max(1, (int) round($low + ($high - $low) * $random));
                }
                PHP,
            'hint' => 'Work out the low and high ends, then `low + (high - low) * random`.',
            'checks' => [
                ['label' => 'Spreads evenly', 'code' => 'return expect_same([jitteredTtl(600, 0.1, 0.0), jitteredTtl(600, 0.1, 0.5), jitteredTtl(600, 0.1, 1.0), jitteredTtl(600, 0.1, 0.25)], [540, 600, 660, 570]);'],
                ['label' => 'Never below 1', 'code' => 'return expect_same([jitteredTtl(1, 1.0, 0.0), jitteredTtl(10, 0.0, 0.9)], [1, 10]);'],
            ],
        ],
        [
            'title' => 'Refresh a little early',
            'prompt' => 'Probabilistic early expiration refreshes a value before it expires, with rising probability. Write `shouldRefreshEarly(float $now, float $expiresAt, float $computeSeconds, float $beta, float $random): bool`, where `$random` is in (0, 1]. Return true when `$now - $computeSeconds * $beta * log($random) >= $expiresAt`. (`log()` of a number below 1 is negative, so this looks a little into the future.)',
            'starter_code' => <<<'PHP'
                <?php

                function shouldRefreshEarly(float $now, float $expiresAt, float $computeSeconds, float $beta, float $random): bool
                {
                    return $now >= $expiresAt;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shouldRefreshEarly(float $now, float $expiresAt, float $computeSeconds, float $beta, float $random): bool
                {
                    return $now - $computeSeconds * $beta * log($random) >= $expiresAt;
                }
                PHP,
            'hint' => 'It\'s one comparison; `log()` is the natural logarithm.',
            'checks' => [
                ['label' => 'Close to expiry, slow values refresh early', 'code' => 'return expect_same([shouldRefreshEarly(995, 1000, 2.0, 1.0, 0.05), shouldRefreshEarly(995, 1000, 0.1, 1.0, 0.05), shouldRefreshEarly(900, 1000, 2.0, 1.0, 0.05)], [true, false, false]);'],
                ['label' => 'Expired always refreshes', 'code' => 'return expect_same([shouldRefreshEarly(1000, 1000, 1.0, 1.0, 1.0), shouldRefreshEarly(1001, 1000, 0.0, 1.0, 0.5)], [true, true]);'],
            ],
        ],
    ],
];
