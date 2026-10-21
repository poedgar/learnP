<?php

// Lesson 4 of the REST APIs course. The theory is in the .md file with the same name.

return [
    'slug' => 'authentication-and-rate-limits',
    'title' => 'Authentication and rate limits',
    'summary' => 'Bearer tokens and Sanctum: hashed tokens, abilities and expiry, constant-time comparison; rate limiting with fixed windows and token buckets, 429 and Retry-After; and verifying signed webhooks.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a wrong token.
        $tokens = [7 => ['user' => 'ada', 'hash' => hash('sha256', 'Kb2xYwq9TzR4')]];

        $header = 'Bearer 7|Kb2xYwq9TzR4';
        [$id, $secret] = explode('|', substr($header, 7), 2);

        $row = $tokens[(int) $id] ?? null;
        echo $row && hash_equals($row['hash'], hash('sha256', $secret)) ? "Hello, {$row['user']}" : '401 Unauthorized';
        PHP,

    'quiz' => [
        [
            'question' => 'How does a mobile app usually prove who it is to a Laravel API?',
            'options' => [
                'It sends the user\'s password with every request',
                'It sends a token in the `Authorization: Bearer ...` header with every request',
                'It uses the IP address',
                'It logs in once and the server remembers the phone',
            ],
            'correct_option' => 1,
            'explanation' => 'APIs are stateless: each request carries its own proof.',
        ],
        [
            'question' => 'Why does Sanctum store a hash of each token instead of the token itself?',
            'options' => [
                'Hashes are shorter',
                'So a leaked database doesn\'t hand out working tokens',
                'To make tokens expire',
                'Because tokens are passwords',
            ],
            'correct_option' => 1,
            'explanation' => 'The plain token is shown once, at creation; only the hash is kept.',
        ],
        [
            'question' => 'Why compare token hashes with `hash_equals()` rather than `===`?',
            'options' => [
                'It is shorter to type',
                'It takes the same time whatever matches, so attackers can\'t learn anything from timing',
                'It ignores case',
                '`===` doesn\'t work on strings',
            ],
            'correct_option' => 1,
            'explanation' => '`===` can stop at the first differing character, which leaks information through timing.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $abilities = ['orders:read'];

                $can = fn (string $ability) => in_array('*', $abilities, true) || in_array($ability, $abilities, true);

                echo ($can('orders:read') ? 'yes' : 'no') . ' ' . ($can('orders:write') ? 'yes' : 'no');
                PHP,
            'options' => ['`yes yes`', '`yes no`', '`no yes`', '`no no`'],
            'correct_option' => 1,
            'explanation' => 'Give each token only the abilities it needs; `*` would allow everything.',
        ],
        [
            'question' => 'A client has made too many requests. What should the response be?',
            'options' => [
                '`403` with no headers',
                '`429 Too Many Requests` with a `Retry-After` header',
                '`500`',
                '`503` forever',
            ],
            'correct_option' => 1,
            'explanation' => '`Retry-After` tells the client how many seconds to wait.',
        ],
        [
            'question' => 'What should a rate limit be keyed by when the user is logged in?',
            'options' => ['The URL', 'The user\'s id (falling back to the IP address for guests)', 'The time of day', 'The browser'],
            'correct_option' => 1,
            'explanation' => 'Many users can share one IP address (an office, a phone network).',
        ],
        [
            'question' => 'What is the weakness of a fixed-window rate limit of 60 per minute?',
            'options' => [
                'It never resets',
                'A client can make 60 requests at the end of one window and 60 more at the start of the next, 120 in a few seconds',
                'It only works for logged-in users',
                'It counts every user together',
            ],
            'correct_option' => 1,
            'explanation' => 'A token bucket or sliding window smooths out that burst.',
        ],
        [
            'question' => 'Why does a webhook signature include a timestamp, and why reject old ones?',
            'options' => [
                'For logging',
                'So a captured request can\'t be replayed later: the signature only matches that timestamp, and old ones are refused',
                'To sort webhooks',
                'Because HMAC needs a number',
            ],
            'correct_option' => 1,
            'explanation' => 'Without it, an attacker could resend "payment succeeded" as often as they liked.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Read the bearer token',
            'prompt' => 'Write `bearerToken(array $headers): ?string`. Find the `Authorization` header (any capitalisation of the name); if its value starts with `Bearer ` (the scheme in any case, followed by a space), return the rest with surrounding spaces removed. Return `null` if there is no such header, another scheme, or an empty token.',
            'starter_code' => <<<'PHP'
                <?php

                function bearerToken(array $headers): ?string
                {
                    return substr($headers['Authorization'], 7);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bearerToken(array $headers): ?string
                {
                    $value = array_change_key_case($headers, CASE_LOWER)['authorization'] ?? '';

                    if (strtolower(substr($value, 0, 7)) !== 'bearer ') {
                        return null;
                    }

                    $token = trim(substr($value, 7));

                    return $token === '' ? null : $token;
                }
                PHP,
            'hint' => 'Lower-case the header names, compare the first seven characters case-insensitively, and trim the rest.',
            'checks' => [
                ['label' => 'Finds the token', 'code' => "return expect_same([bearerToken(['Authorization' => 'Bearer 7|abc']), bearerToken(['authorization' => 'bearer  7|abc ']), bearerToken(['AUTHORIZATION' => 'BEARER xyz'])], ['7|abc', '7|abc', 'xyz']);"],
                ['label' => 'Nothing usable', 'code' => "return expect_same([bearerToken([]), bearerToken(['Authorization' => 'Basic dXNlcjpwYXNz']), bearerToken(['Authorization' => 'Bearer   ']), bearerToken(['Authorization' => 'Bearer'])], [null, null, null, null]);"],
            ],
        ],
        [
            'title' => 'Find the token',
            'prompt' => 'Tokens are stored as rows `[\'id\' => 7, \'user_id\' => 1, \'token\' => sha256 hash of the secret]`. Write `findToken(string $plain, array $rows): ?array`. A plain token looks like `7|secret`: find the row with that id and check the hash. A token without `|` is just a secret: check it against every row. Compare hashes with `hash_equals()`. Return the matching row or `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function findToken(string $plain, array $rows): ?array
                {
                    foreach ($rows as $row) {
                        if ($row['token'] === $plain) {
                            return $row;
                        }
                    }

                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function findToken(string $plain, array $rows): ?array
                {
                    $id = null;
                    $secret = $plain;
                    if (str_contains($plain, '|')) {
                        [$id, $secret] = explode('|', $plain, 2);
                    }

                    $hash = hash('sha256', $secret);
                    foreach ($rows as $row) {
                        if ($id !== null && (string) $row['id'] !== $id) {
                            continue;
                        }
                        if (hash_equals($row['token'], $hash)) {
                            return $row;
                        }
                    }

                    return null;
                }
                PHP,
            'hint' => 'Split on the first `|` with `explode(\'|\', $plain, 2)`, hash the secret once, then compare with `hash_equals($row[\'token\'], $hash)`.',
            'checks' => [
                ['label' => 'With an id', 'code' => <<<'PHP'
                    $rows = [['id' => 7, 'user_id' => 1, 'token' => hash('sha256', 'aaa')], ['id' => 8, 'user_id' => 2, 'token' => hash('sha256', 'bbb')]];
                    return expect_same([findToken('8|bbb', $rows)['user_id'] ?? null, findToken('7|bbb', $rows), findToken('9|aaa', $rows)], [2, null, null]);
                    PHP],
                ['label' => 'Without an id', 'code' => <<<'PHP'
                    $rows = [['id' => 7, 'user_id' => 1, 'token' => hash('sha256', 'aaa')], ['id' => 8, 'user_id' => 2, 'token' => hash('sha256', 'bbb')]];
                    return expect_same([findToken('aaa', $rows)['id'] ?? null, findToken('ccc', $rows)], [7, null]);
                    PHP],
                ['label' => 'Secrets may contain |', 'code' => "return expect_same(findToken('3|a|b', [['id' => 3, 'user_id' => 9, 'token' => hash('sha256', 'a|b')]])['user_id'] ?? null, 9);"],
                ['label' => 'Uses hash_equals', 'code' => "return str_contains(learnp_code(), 'hash_equals') || 'Compare the hashes with hash_equals().';"],
            ],
        ],
        [
            'title' => 'Can this token do it',
            'prompt' => 'Write `tokenAllows(array $token, string $ability, DateTimeImmutable $now): bool`. The token has `abilities` (a list, where `*` means everything) and `expires_at` (`null` or a `DateTimeImmutable`). An expired token (expiry at or before `$now`) allows nothing.',
            'starter_code' => <<<'PHP'
                <?php

                function tokenAllows(array $token, string $ability, DateTimeImmutable $now): bool
                {
                    return in_array($ability, $token['abilities'], true);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function tokenAllows(array $token, string $ability, DateTimeImmutable $now): bool
                {
                    if ($token['expires_at'] !== null && $token['expires_at'] <= $now) {
                        return false;
                    }

                    return in_array('*', $token['abilities'], true) || in_array($ability, $token['abilities'], true);
                }
                PHP,
            'hint' => 'Check the expiry first (`DateTimeImmutable` objects compare with `<=`), then the abilities.',
            'checks' => [
                ['label' => 'Abilities', 'code' => <<<'PHP'
                    $now = new DateTimeImmutable('2026-10-21 12:00:00');
                    $reader = ['abilities' => ['orders:read'], 'expires_at' => null];
                    $admin = ['abilities' => ['*'], 'expires_at' => null];
                    return expect_same([tokenAllows($reader, 'orders:read', $now), tokenAllows($reader, 'orders:write', $now), tokenAllows($admin, 'orders:write', $now)], [true, false, true]);
                    PHP],
                ['label' => 'Expiry', 'code' => <<<'PHP'
                    $now = new DateTimeImmutable('2026-10-21 12:00:00');
                    $token = fn (string $expires) => ['abilities' => ['*'], 'expires_at' => new DateTimeImmutable($expires)];
                    return expect_same([tokenAllows($token('2026-10-21 12:00:01'), 'x', $now), tokenAllows($token('2026-10-21 12:00:00'), 'x', $now), tokenAllows($token('2026-01-01'), 'x', $now)], [true, false, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'A fixed window',
            'prompt' => 'Write a `FixedWindowLimiter` class built from `(int $maxAttempts, int $decaySeconds)`. `attempt(string $key, int $now): array` counts a request for the key and returns `[\'allowed\' => bool, \'remaining\' => int, \'retry_after\' => ?int]`. A key\'s window starts at its first request and lasts `$decaySeconds`; after it, the count starts again. `remaining` never goes below 0, and `retry_after` (seconds until the window ends) is only set when the request isn\'t allowed.',
            'starter_code' => <<<'PHP'
                <?php

                class FixedWindowLimiter
                {
                    private array $counts = [];

                    public function __construct(private int $maxAttempts, private int $decaySeconds) {}

                    public function attempt(string $key, int $now): array
                    {
                        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;

                        return ['allowed' => true, 'remaining' => $this->maxAttempts - $this->counts[$key], 'retry_after' => null];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class FixedWindowLimiter
                {
                    private array $windows = [];

                    public function __construct(private int $maxAttempts, private int $decaySeconds) {}

                    public function attempt(string $key, int $now): array
                    {
                        $window = $this->windows[$key] ?? null;
                        if ($window === null || $now >= $window['resets_at']) {
                            $window = ['count' => 0, 'resets_at' => $now + $this->decaySeconds];
                        }

                        $window['count']++;
                        $this->windows[$key] = $window;
                        $allowed = $window['count'] <= $this->maxAttempts;

                        return [
                            'allowed' => $allowed,
                            'remaining' => max(0, $this->maxAttempts - $window['count']),
                            'retry_after' => $allowed ? null : $window['resets_at'] - $now,
                        ];
                    }
                }
                PHP,
            'hint' => 'Keep `count` and `resets_at` per key. Start a new window when there is none or `$now >= resets_at`.',
            'checks' => [
                ['label' => 'Counts down, then refuses', 'code' => <<<'PHP'
                    $limiter = new FixedWindowLimiter(2, 60);
                    return expect_same([$limiter->attempt('ip:1', 0), $limiter->attempt('ip:1', 10), $limiter->attempt('ip:1', 15)], [
                        ['allowed' => true, 'remaining' => 1, 'retry_after' => null],
                        ['allowed' => true, 'remaining' => 0, 'retry_after' => null],
                        ['allowed' => false, 'remaining' => 0, 'retry_after' => 45],
                    ]);
                    PHP],
                ['label' => 'A new window', 'code' => <<<'PHP'
                    $limiter = new FixedWindowLimiter(1, 60);
                    $limiter->attempt('ip:1', 100);
                    $limiter->attempt('ip:1', 120);
                    return expect_same([$limiter->attempt('ip:1', 159)['allowed'], $limiter->attempt('ip:1', 160)['allowed'], $limiter->attempt('ip:1', 161)['retry_after']], [false, true, 59]);
                    PHP],
                ['label' => 'Keys are separate', 'code' => <<<'PHP'
                    $limiter = new FixedWindowLimiter(1, 60);
                    $limiter->attempt('user:1', 0);
                    return expect_same([$limiter->attempt('user:2', 0)['allowed'], $limiter->attempt('user:1', 0)['allowed']], [true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'A token bucket',
            'prompt' => 'Write a `TokenBucket` class built from `(int $capacity, float $refillPerSecond)`. Each key starts with a full bucket. `take(string $key, float $now): bool` first adds `refillPerSecond` tokens for every second since the key\'s last call (never above the capacity), then takes one token if there is at least one and returns `true`, or returns `false`.',
            'starter_code' => <<<'PHP'
                <?php

                class TokenBucket
                {
                    private array $tokens = [];

                    public function __construct(private int $capacity, private float $refillPerSecond) {}

                    public function take(string $key, float $now): bool
                    {
                        $this->tokens[$key] = ($this->tokens[$key] ?? $this->capacity) - 1;

                        return $this->tokens[$key] >= 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TokenBucket
                {
                    private array $buckets = [];

                    public function __construct(private int $capacity, private float $refillPerSecond) {}

                    public function take(string $key, float $now): bool
                    {
                        $bucket = $this->buckets[$key] ?? ['tokens' => (float) $this->capacity, 'at' => $now];
                        $elapsed = max(0.0, $now - $bucket['at']);
                        $tokens = min((float) $this->capacity, $bucket['tokens'] + $elapsed * $this->refillPerSecond);

                        $allowed = $tokens >= 1.0;
                        if ($allowed) {
                            $tokens -= 1.0;
                        }

                        $this->buckets[$key] = ['tokens' => $tokens, 'at' => $now];

                        return $allowed;
                    }
                }
                PHP,
            'hint' => 'Store the token count (a float) and the time of the last call per key. Refill, cap with `min()`, then take one if you can.',
            'checks' => [
                ['label' => 'A burst up to the capacity', 'code' => <<<'PHP'
                    $bucket = new TokenBucket(3, 1.0);
                    return expect_same([$bucket->take('a', 0), $bucket->take('a', 0), $bucket->take('a', 0), $bucket->take('a', 0)], [true, true, true, false]);
                    PHP],
                ['label' => 'Refills steadily', 'code' => <<<'PHP'
                    $bucket = new TokenBucket(3, 0.5);
                    foreach (range(1, 3) as $i) $bucket->take('a', 0);
                    return expect_same([$bucket->take('a', 1.0), $bucket->take('a', 2.0), $bucket->take('a', 2.5), $bucket->take('a', 4.0)], [false, true, false, true]);
                    PHP],
                ['label' => 'Never above the capacity', 'code' => <<<'PHP'
                    $bucket = new TokenBucket(2, 10.0);
                    $bucket->take('a', 0);
                    $results = [$bucket->take('a', 100), $bucket->take('a', 100), $bucket->take('a', 100)];
                    return expect_same($results, [true, true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Check the signature',
            'prompt' => 'Write `verifyWebhook(string $payload, string $header, string $secret, int $now, int $tolerance = 300): bool`. The header looks like `t=1792573200,v1=HEX`. It is valid when both parts are present, the timestamp is no more than `$tolerance` seconds away from `$now` (either way), and `v1` equals `hash_hmac(\'sha256\', "TIMESTAMP.PAYLOAD", $secret)`, compared with `hash_equals()`.',
            'starter_code' => <<<'PHP'
                <?php

                function verifyWebhook(string $payload, string $header, string $secret, int $now, int $tolerance = 300): bool
                {
                    return str_contains($header, hash_hmac('sha256', $payload, $secret));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function verifyWebhook(string $payload, string $header, string $secret, int $now, int $tolerance = 300): bool
                {
                    $parts = [];
                    foreach (explode(',', $header) as $pair) {
                        [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
                        $parts[trim($name)] = trim($value);
                    }

                    if (! isset($parts['t'], $parts['v1']) || preg_match('/^\d+$/', $parts['t']) !== 1) {
                        return false;
                    }

                    $timestamp = (int) $parts['t'];
                    if (abs($now - $timestamp) > $tolerance) {
                        return false;
                    }

                    $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

                    return hash_equals($expected, $parts['v1']);
                }
                PHP,
            'hint' => 'Split the header on commas and each part on the first `=`. Check the timestamp before computing the HMAC of `"{$t}.{$payload}"`.',
            'checks' => [
                ['label' => 'A valid signature', 'code' => <<<'PHP'
                    $payload = '{"type":"payment.succeeded","order":981}';
                    $sig = hash_hmac('sha256', "1792573200.{$payload}", 'whsec');
                    return expect_same([verifyWebhook($payload, "t=1792573200,v1={$sig}", 'whsec', 1792573260), verifyWebhook($payload, "v1={$sig},t=1792573200", 'whsec', 1792573200)], [true, true]);
                    PHP],
                ['label' => 'Tampering and wrong secrets', 'code' => <<<'PHP'
                    $payload = '{"order":981}';
                    $sig = hash_hmac('sha256', "1792573200.{$payload}", 'whsec');
                    return expect_same([
                        verifyWebhook('{"order":982}', "t=1792573200,v1={$sig}", 'whsec', 1792573200),
                        verifyWebhook($payload, "t=1792573200,v1={$sig}", 'other', 1792573200),
                        verifyWebhook($payload, "t=1792573201,v1={$sig}", 'whsec', 1792573200),
                        verifyWebhook($payload, "t=1792573200", 'whsec', 1792573200),
                        verifyWebhook($payload, '', 'whsec', 1792573200),
                    ], [false, false, false, false, false]);
                    PHP],
                ['label' => 'Old requests are refused', 'code' => <<<'PHP'
                    $payload = '{}';
                    $sig = hash_hmac('sha256', "1000.{$payload}", 'whsec');
                    return expect_same([verifyWebhook($payload, "t=1000,v1={$sig}", 'whsec', 1300), verifyWebhook($payload, "t=1000,v1={$sig}", 'whsec', 1301), verifyWebhook($payload, "t=1000,v1={$sig}", 'whsec', 700, 300)], [true, false, true]);
                    PHP],
            ],
        ],
    ],
];
