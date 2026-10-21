<?php

// Lesson 3 of the Caching and Redis course. The theory is in the .md file with the same name.
// The challenges build small, in-memory versions of Redis commands.

return [
    'slug' => 'redis-data-structures',
    'title' => 'Redis data structures',
    'summary' => 'Redis beyond caching: atomic commands, strings with expiry and counters, lists as queues, sets, sorted sets for leaderboards, hashes, finding keys safely, and memory limits.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add sales and watch the order change.
        $sales = [];
        $zincrby = function (string $member, int $by) use (&$sales) {
            $sales[$member] = ($sales[$member] ?? 0) + $by;
        };

        $zincrby('dune', 3);
        $zincrby('emma', 5);
        $zincrby('dune', 4);

        arsort($sales);
        print_r($sales);
        PHP,

    'quiz' => [
        [
            'question' => 'Why can two servers run `INCR downloads:dune` at the same moment without losing a count?',
            'options' => [
                'Redis locks the key for a second',
                'Redis runs one command at a time, so each command is atomic',
                'They can\'t; one is lost',
                'Redis retries them',
            ],
            'correct_option' => 1,
            'explanation' => 'Read, add and write happen as one step on the server.',
        ],
        [
            'question' => 'What does `TTL` return for a key that exists but has no expiry?',
            'options' => ['`0`', '`-1`', '`-2`', '`null`'],
            'correct_option' => 1,
            'explanation' => '`-2` means the key doesn\'t exist at all.',
        ],
        [
            'question' => 'What does `SET lock:import worker-1 NX EX 30` do?',
            'options' => [
                'Always sets the key',
                'Sets the key only if it doesn\'t exist yet, expiring in 30 seconds',
                'Deletes the key after 30 uses',
                'Sets 30 keys',
            ],
            'correct_option' => 1,
            'explanation' => '`NX` means "not exists": only the first caller succeeds, which is a simple lock.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $queue = [];
                array_unshift($queue, 'first');   // LPUSH
                array_unshift($queue, 'second');  // LPUSH
                array_unshift($queue, 'third');   // LPUSH

                echo array_pop($queue);           // RPOP
                PHP,
            'options' => ['`first`', '`second`', '`third`', 'Nothing'],
            'correct_option' => 0,
            'explanation' => 'Pushing on the left and popping on the right makes a first-in, first-out queue.',
        ],
        [
            'question' => 'Which Redis structure fits "customers who bought both Dune and Emma"?',
            'options' => ['Two lists and a loop', 'Two sets and `SINTER`', 'A sorted set', 'A string per customer'],
            'correct_option' => 1,
            'explanation' => 'The intersection happens on the Redis server in one command.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $bought = [
                    'dune' => ['ada', 'bob', 'carol'],
                    'emma' => ['bob', 'dave', 'carol'],
                ];

                $both = array_values(array_intersect($bought['dune'], $bought['emma']));
                sort($both);

                echo implode(',', $both);
                PHP,
            'options' => ['`bob`', '`bob,carol`', '`ada,bob,carol,dave`', '`carol`'],
            'correct_option' => 1,
            'explanation' => 'That\'s `SINTER bought:dune bought:emma`.',
        ],
        [
            'question' => 'Which structure keeps a leaderboard in order as scores change?',
            'options' => ['A list', 'A set', 'A sorted set', 'A hash'],
            'correct_option' => 2,
            'explanation' => '`ZINCRBY` updates a score, and `ZREVRANGE 0 9` reads the top 10 straight away.',
        ],
        [
            'question' => 'Why should you never run `KEYS *` on a production Redis?',
            'options' => [
                'It deletes the keys',
                'It scans every key in one go, blocking Redis for everyone until it finishes',
                'It is not a real command',
                'It needs a password',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `SCAN` with `MATCH` to walk keys a batch at a time.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'SET, GET and TTL',
            'prompt' => 'Write a `MiniRedis` class. `set(string $key, string $value, int $now, ?int $ex = null, bool $nx = false): bool` stores a value, expiring `$ex` seconds after `$now` if given (setting a key replaces any old expiry); with `$nx` it only sets a key that doesn\'t exist (or has expired), returning whether it set it. `get(string $key, int $now): ?string`. `ttl(string $key, int $now): int` returns the seconds left, `-1` with no expiry, or `-2` if there\'s no such key.',
            'starter_code' => <<<'PHP'
                <?php

                class MiniRedis
                {
                    private array $data = [];

                    public function set(string $key, string $value, int $now, ?int $ex = null, bool $nx = false): bool
                    {
                        $this->data[$key] = $value;

                        return true;
                    }

                    public function get(string $key, int $now): ?string
                    {
                        return $this->data[$key] ?? null;
                    }

                    public function ttl(string $key, int $now): int
                    {
                        return -1;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class MiniRedis
                {
                    private array $data = [];
                    private array $expires = [];

                    public function set(string $key, string $value, int $now, ?int $ex = null, bool $nx = false): bool
                    {
                        if ($nx && $this->get($key, $now) !== null) {
                            return false;
                        }

                        $this->data[$key] = $value;
                        unset($this->expires[$key]);
                        if ($ex !== null) {
                            $this->expires[$key] = $now + $ex;
                        }

                        return true;
                    }

                    public function get(string $key, int $now): ?string
                    {
                        if (isset($this->expires[$key]) && $now >= $this->expires[$key]) {
                            unset($this->data[$key], $this->expires[$key]);
                        }

                        return $this->data[$key] ?? null;
                    }

                    public function ttl(string $key, int $now): int
                    {
                        if ($this->get($key, $now) === null) {
                            return -2;
                        }

                        return isset($this->expires[$key]) ? $this->expires[$key] - $now : -1;
                    }
                }
                PHP,
            'hint' => 'Keep expiry times in a second array. Let `get()` delete an expired key, and use it from `set()` and `ttl()`.',
            'checks' => [
                ['label' => 'Expiry', 'code' => <<<'PHP'
                    $r = new MiniRedis();
                    $r->set('session:abc', 'ada', 1000, 60);
                    $r->set('title', 'Dune', 1000);
                    return expect_same([$r->get('session:abc', 1059), $r->ttl('session:abc', 1050), $r->get('session:abc', 1060), $r->ttl('session:abc', 1060), $r->ttl('title', 5000), $r->ttl('nope', 0)], ['ada', 10, null, -2, -1, -2]);
                    PHP],
                ['label' => 'NX', 'code' => <<<'PHP'
                    $r = new MiniRedis();
                    $results = [$r->set('lock', 'w1', 0, 30, true), $r->set('lock', 'w2', 10, 30, true), $r->get('lock', 10), $r->set('lock', 'w2', 30, 30, true), $r->get('lock', 31)];
                    return expect_same($results, [true, false, 'w1', true, 'w2']);
                    PHP],
                ['label' => 'A plain SET clears the expiry', 'code' => <<<'PHP'
                    $r = new MiniRedis();
                    $r->set('k', 'a', 0, 10);
                    $r->set('k', 'b', 5);
                    return expect_same([$r->ttl('k', 100), $r->get('k', 100)], [-1, 'b']);
                    PHP],
            ],
        ],
        [
            'title' => 'INCR and EXPIRE',
            'prompt' => 'Write a `Counters` class. `incr(string $key, int $now, int $by = 1): int` adds to the integer stored at the key (a missing or expired key counts as 0) and returns it; if the stored value isn\'t a whole number, throw an `UnexpectedValueException` with `ERR value is not an integer or out of range`. `set(string $key, string $value)` stores a plain value. `expire(string $key, int $seconds, int $now): bool` sets an expiry on an existing key (false if there\'s no such key).',
            'starter_code' => <<<'PHP'
                <?php

                class Counters
                {
                    private array $values = [];

                    public function set(string $key, string $value): void
                    {
                        $this->values[$key] = $value;
                    }

                    public function incr(string $key, int $now, int $by = 1): int
                    {
                        $this->values[$key] = (int) ($this->values[$key] ?? 0) + $by;

                        return $this->values[$key];
                    }

                    public function expire(string $key, int $seconds, int $now): bool
                    {
                        return true;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Counters
                {
                    private array $values = [];
                    private array $expires = [];

                    public function set(string $key, string $value): void
                    {
                        $this->values[$key] = $value;
                        unset($this->expires[$key]);
                    }

                    public function incr(string $key, int $now, int $by = 1): int
                    {
                        $this->forgetIfExpired($key, $now);
                        $current = (string) ($this->values[$key] ?? '0');

                        if (preg_match('/^-?\d+$/', $current) !== 1) {
                            throw new UnexpectedValueException('ERR value is not an integer or out of range');
                        }

                        $this->values[$key] = (string) ((int) $current + $by);

                        return (int) $this->values[$key];
                    }

                    public function expire(string $key, int $seconds, int $now): bool
                    {
                        $this->forgetIfExpired($key, $now);
                        if (! array_key_exists($key, $this->values)) {
                            return false;
                        }

                        $this->expires[$key] = $now + $seconds;

                        return true;
                    }

                    private function forgetIfExpired(string $key, int $now): void
                    {
                        if (isset($this->expires[$key]) && $now >= $this->expires[$key]) {
                            unset($this->values[$key], $this->expires[$key]);
                        }
                    }
                }
                PHP,
            'hint' => 'Redis stores numbers as strings: check the stored string is digits (with an optional minus) before adding.',
            'checks' => [
                ['label' => 'Counts', 'code' => <<<'PHP'
                    $r = new Counters();
                    $r->set('stock', '10');
                    return expect_same([$r->incr('views', 0), $r->incr('views', 0), $r->incr('views', 0, 5), $r->incr('stock', 0, -3)], [1, 2, 7, 7]);
                    PHP],
                ['label' => 'A per-minute rate limit', 'code' => <<<'PHP'
                    $r = new Counters();
                    $allowed = [];
                    foreach ([0, 10, 20, 30, 61] as $now) {
                        $count = $r->incr('rate:203.0.113.9', $now);
                        if ($count === 1) $r->expire('rate:203.0.113.9', 60, $now);
                        $allowed[] = $count <= 3;
                    }
                    return expect_same($allowed, [true, true, true, false, true]);
                    PHP],
                ['label' => 'Errors', 'code' => <<<'PHP'
                    $r = new Counters();
                    $r->set('title', 'Dune');
                    if ($r->expire('missing', 10, 0) !== false) return 'expire() on a missing key should return false.';
                    try {
                        $r->incr('title', 0);
                    } catch (UnexpectedValueException $e) {
                        return expect_same($e->getMessage(), 'ERR value is not an integer or out of range');
                    }
                    return 'Incrementing text should throw.';
                    PHP],
            ],
        ],
        [
            'title' => 'Lists',
            'prompt' => 'Write a `Lists` class with `lpush(string $key, string ...$values): int` (each value goes on the left in turn; returns the new length), `rpush(...)` (on the right), `rpop(string $key): ?string`, and `lrange(string $key, int $start, int $stop): array`, where both ends are inclusive and negative indexes count from the end (`-1` is the last item), as in Redis.',
            'starter_code' => <<<'PHP'
                <?php

                class Lists
                {
                    private array $lists = [];

                    public function lpush(string $key, string ...$values): int
                    {
                        $this->lists[$key] = [...$values, ...($this->lists[$key] ?? [])];

                        return count($this->lists[$key]);
                    }

                    public function lrange(string $key, int $start, int $stop): array
                    {
                        return array_slice($this->lists[$key] ?? [], $start, $stop);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Lists
                {
                    private array $lists = [];

                    public function lpush(string $key, string ...$values): int
                    {
                        $this->lists[$key] ??= [];
                        foreach ($values as $value) {
                            array_unshift($this->lists[$key], $value);
                        }

                        return count($this->lists[$key] ?? []);
                    }

                    public function rpush(string $key, string ...$values): int
                    {
                        foreach ($values as $value) {
                            $this->lists[$key][] = $value;
                        }

                        return count($this->lists[$key] ?? []);
                    }

                    public function rpop(string $key): ?string
                    {
                        if (empty($this->lists[$key])) {
                            return null;
                        }

                        return array_pop($this->lists[$key]);
                    }

                    public function lrange(string $key, int $start, int $stop): array
                    {
                        $list = $this->lists[$key] ?? [];
                        $n = count($list);
                        if ($start < 0) {
                            $start = max(0, $n + $start);
                        }
                        if ($stop < 0) {
                            $stop = $n + $stop;
                        }
                        $stop = min($stop, $n - 1);

                        return $start > $stop ? [] : array_slice($list, $start, $stop - $start + 1);
                    }
                }
                PHP,
            'hint' => '`LPUSH a b c` leaves `c, b, a`: push each value onto the front in turn. For `lrange()`, turn negative indexes into positive ones, clamp `$stop`, then slice.',
            'checks' => [
                ['label' => 'Pushing and popping', 'code' => <<<'PHP'
                    $r = new Lists();
                    $lengths = [$r->lpush('jobs', 'a', 'b'), $r->lpush('jobs', 'c'), $r->rpush('jobs', 'z')];
                    return expect_same([$lengths, $r->lrange('jobs', 0, -1), $r->rpop('jobs'), $r->rpop('jobs'), $r->rpop('empty')], [[2, 3, 4], ['c', 'b', 'a', 'z'], 'z', 'a', null]);
                    PHP],
                ['label' => 'Ranges', 'code' => <<<'PHP'
                    $r = new Lists();
                    $r->rpush('l', 'a', 'b', 'c', 'd', 'e');
                    return expect_same([$r->lrange('l', 0, 1), $r->lrange('l', -2, -1), $r->lrange('l', 1, 100), $r->lrange('l', -100, 0), $r->lrange('l', 3, 1), $r->lrange('none', 0, -1)], [['a', 'b'], ['d', 'e'], ['b', 'c', 'd', 'e'], ['a'], [], []]);
                    PHP],
            ],
        ],
        [
            'title' => 'Sets',
            'prompt' => 'Write a `Sets` class with `sadd(string $key, string ...$members): int` (returns how many members were new), `sismember(string $key, string $member): bool`, `smembers(string $key): array` and `sinter(string ...$keys): array` (members in every set). Return member lists sorted alphabetically.',
            'starter_code' => <<<'PHP'
                <?php

                class Sets
                {
                    private array $sets = [];

                    public function sadd(string $key, string ...$members): int
                    {
                        foreach ($members as $member) {
                            $this->sets[$key][] = $member;
                        }

                        return count($members);
                    }

                    public function smembers(string $key): array
                    {
                        return $this->sets[$key] ?? [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Sets
                {
                    private array $sets = [];

                    public function sadd(string $key, string ...$members): int
                    {
                        $added = 0;
                        foreach ($members as $member) {
                            if (! isset($this->sets[$key][$member])) {
                                $this->sets[$key][$member] = true;
                                $added++;
                            }
                        }

                        return $added;
                    }

                    public function sismember(string $key, string $member): bool
                    {
                        return isset($this->sets[$key][$member]);
                    }

                    public function smembers(string $key): array
                    {
                        $members = array_map('strval', array_keys($this->sets[$key] ?? []));
                        sort($members, SORT_STRING);

                        return $members;
                    }

                    public function sinter(string ...$keys): array
                    {
                        $result = null;
                        foreach ($keys as $key) {
                            $members = $this->sets[$key] ?? [];
                            $result = $result === null ? $members : array_intersect_key($result, $members);
                        }

                        $members = array_map('strval', array_keys($result ?? []));
                        sort($members, SORT_STRING);

                        return $members;
                    }
                }
                PHP,
            'hint' => 'Store members as array keys (`$set[$member] = true`): that makes them unique and lookups instant. `array_intersect_key()` intersects. Numeric keys come back as integers, and `sort()` compares numbers as numbers, so use `strval` and `SORT_STRING`.',
            'checks' => [
                ['label' => 'Unique members', 'code' => <<<'PHP'
                    $r = new Sets();
                    $added = [$r->sadd('bought:dune', 'carol', 'ada', 'bob'), $r->sadd('bought:dune', 'ada', 'dave')];
                    return expect_same([$added, $r->smembers('bought:dune'), $r->sismember('bought:dune', 'ada'), $r->sismember('bought:dune', 'eve'), $r->smembers('nope')], [[3, 1], ['ada', 'bob', 'carol', 'dave'], true, false, []]);
                    PHP],
                ['label' => 'Intersections', 'code' => <<<'PHP'
                    $r = new Sets();
                    $r->sadd('bought:dune', 'ada', 'bob', 'carol');
                    $r->sadd('bought:emma', 'carol', 'bob', 'dave');
                    $r->sadd('bought:ulysses', 'carol');
                    return expect_same([$r->sinter('bought:dune', 'bought:emma'), $r->sinter('bought:dune', 'bought:emma', 'bought:ulysses'), $r->sinter('bought:dune', 'nope')], [['bob', 'carol'], ['carol'], []]);
                    PHP],
                ['label' => 'Numeric members stay strings', 'code' => "\$r = new Sets(); \$r->sadd('ids', '10', '7'); return expect_same(\$r->smembers('ids'), ['10', '7']);"],
            ],
        ],
        [
            'title' => 'A leaderboard',
            'prompt' => 'Write a `Leaderboard` class with `zincrby(string $key, int $by, string $member): int` (returns the new score), `zrevrange(string $key, int $start, int $stop): array` (member => score, highest first, `$start` and `$stop` inclusive and non-negative), and `zrevrank(string $key, string $member): ?int` (0 for the top, `null` if absent). Members with the same score come in reverse alphabetical order, as in Redis.',
            'starter_code' => <<<'PHP'
                <?php

                class Leaderboard
                {
                    private array $scores = [];

                    public function zincrby(string $key, int $by, string $member): int
                    {
                        $this->scores[$key][$member] = $by;

                        return $by;
                    }

                    public function zrevrange(string $key, int $start, int $stop): array
                    {
                        return $this->scores[$key] ?? [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Leaderboard
                {
                    private array $scores = [];

                    public function zincrby(string $key, int $by, string $member): int
                    {
                        $this->scores[$key][$member] = ($this->scores[$key][$member] ?? 0) + $by;

                        return $this->scores[$key][$member];
                    }

                    public function zrevrange(string $key, int $start, int $stop): array
                    {
                        $ordered = array_slice($this->ordered($key), $start, max(0, $stop - $start + 1));

                        return array_column($ordered, 1, 0);
                    }

                    public function zrevrank(string $key, string $member): ?int
                    {
                        foreach ($this->ordered($key) as $rank => [$name]) {
                            if ($name === $member) {
                                return $rank;
                            }
                        }

                        return null;
                    }

                    private function ordered(string $key): array
                    {
                        $pairs = [];
                        foreach ($this->scores[$key] ?? [] as $member => $score) {
                            $pairs[] = [(string) $member, $score];
                        }
                        usort($pairs, fn ($a, $b) => [$b[1], $b[0]] <=> [$a[1], $a[0]]);

                        return $pairs;
                    }
                }
                PHP,
            'hint' => 'Sort `[member, score]` pairs with `usort()`, comparing `[$b[1], $b[0]]` with `[$a[1], $a[0]]`: score descending, then name descending.',
            'checks' => [
                ['label' => 'Highest first', 'code' => <<<'PHP'
                    $board = new Leaderboard();
                    $board->zincrby('sales', 3, 'dune');
                    $board->zincrby('sales', 5, 'emma');
                    $new = $board->zincrby('sales', 4, 'dune');
                    $board->zincrby('sales', 1, 'ulysses');
                    return expect_same([$new, $board->zrevrange('sales', 0, 1), $board->zrevrange('sales', 0, 10)], [7, ['dune' => 7, 'emma' => 5], ['dune' => 7, 'emma' => 5, 'ulysses' => 1]]);
                    PHP],
                ['label' => 'Ranks and ties', 'code' => <<<'PHP'
                    $board = new Leaderboard();
                    foreach (['ada' => 5, 'bob' => 5, 'carol' => 9] as $member => $score) $board->zincrby('points', $score, $member);
                    return expect_same([array_keys($board->zrevrange('points', 0, -1 + 3)), $board->zrevrank('points', 'carol'), $board->zrevrank('points', 'ada'), $board->zrevrank('points', 'zed')], [['carol', 'bob', 'ada'], 0, 2, null]);
                    PHP],
            ],
        ],
        [
            'title' => 'Match keys',
            'prompt' => 'Write `globMatch(string $pattern, string $key): bool` for Redis key patterns: `*` matches any characters (including none), `?` exactly one, `[abc]` one of the listed characters, `[a-z]` one in a range, and a backslash makes the next character literal. Everything else matches itself, and the whole key must match.',
            'starter_code' => <<<'PHP'
                <?php

                function globMatch(string $pattern, string $key): bool
                {
                    return str_starts_with($key, rtrim($pattern, '*'));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function globMatch(string $pattern, string $key): bool
                {
                    $regex = '';
                    $length = strlen($pattern);

                    for ($i = 0; $i < $length; $i++) {
                        $char = $pattern[$i];
                        if ($char === '\\' && $i + 1 < $length) {
                            $regex .= preg_quote($pattern[++$i], '#');
                        } elseif ($char === '*') {
                            $regex .= '.*';
                        } elseif ($char === '?') {
                            $regex .= '.';
                        } elseif ($char === '[' && ($close = strpos($pattern, ']', $i + 1)) !== false) {
                            $class = substr($pattern, $i + 1, $close - $i - 1);
                            $regex .= '[' . str_replace(['\\', ']', '^'], ['\\\\', '\]', '\^'], $class) . ']';
                            $i = $close;
                        } else {
                            $regex .= preg_quote($char, '#');
                        }
                    }

                    return preg_match('#^' . $regex . '$#s', $key) === 1;
                }
                PHP,
            'hint' => 'Build a regular expression one pattern character at a time: `*` becomes `.*`, `?` becomes `.`, a `[...]` group is copied as a character class, and everything else goes through `preg_quote()`.',
            'checks' => [
                ['label' => 'Stars and question marks', 'code' => "return expect_same([globMatch('book:*', 'book:7'), globMatch('book:*', 'book:'), globMatch('book:*:title', 'book:7:title'), globMatch('book:?', 'book:7'), globMatch('book:?', 'book:12'), globMatch('book:*', 'author:1')], [true, true, true, true, false, false]);"],
                ['label' => 'Character groups', 'code' => "return expect_same([globMatch('h[ae]llo', 'hello'), globMatch('h[ae]llo', 'hallo'), globMatch('h[ae]llo', 'hillo'), globMatch('user:[0-9]', 'user:5'), globMatch('user:[0-9]', 'user:x')], [true, true, false, true, false]);"],
                ['label' => 'Literal characters', 'code' => "return expect_same([globMatch('price\\\\*', 'price*'), globMatch('price\\\\*', 'prices'), globMatch('a.b', 'a.b'), globMatch('a.b', 'axb'), globMatch('(x)+', '(x)+')], [true, false, true, false, true]);"],
            ],
        ],
    ],
];
