<?php

// Lesson 5 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'sessions-cookies-and-csrf',
    'title' => 'Sessions, cookies and CSRF',
    'summary' => 'Protecting the session cookie: cookie flags and SameSite, session fixation and timeouts, cross-site request forgery and CSRF tokens, Origin checks, and remember-me tokens.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try submitting a different token.
        $session = ['csrf' => bin2hex(random_bytes(16))];

        $requests = [
            ['GET', null],
            ['POST', $session['csrf']],
            ['POST', 'forged-by-another-site'],
            ['POST', null],
        ];

        foreach ($requests as [$method, $token]) {
            $safe = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
            $ok = $safe || (is_string($token) && hash_equals($session['csrf'], $token));
            echo "$method with " . ($token ?? 'no token') . ': ' . ($ok ? 'allowed' : 'refused (419)') . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does the `HttpOnly` cookie flag do?',
            'options' => [
                'Sends the cookie only over HTTP, not HTTPS',
                'Hides the cookie from JavaScript, so an XSS bug can\'t read it',
                'Makes the cookie expire when the tab closes',
                'Encrypts the cookie',
            ],
            'correct_option' => 1,
            'explanation' => 'The browser still sends it with requests, but `document.cookie` can\'t see it.',
        ],
        [
            'question' => 'What is session fixation?',
            'options' => [
                'A session that never expires',
                'An attacker gets the victim to use a session id the attacker knows, which stays valid after the victim logs in',
                'Storing too much in the session',
                'Two tabs sharing a session',
            ],
            'correct_option' => 1,
            'explanation' => 'Giving the session a new id at login makes the known id worthless.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $sessions = ['abc123' => ['user_id' => null]];
                $id = 'abc123';

                // Logging in, the vulnerable way:
                $sessions[$id]['user_id'] = 42;

                $attackerKnows = 'abc123';
                echo $sessions[$attackerKnows]['user_id'] ?? 'nothing';
                PHP,
            'options' => ['`nothing`', '`42`', '`abc123`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The id didn\'t change at login, so the attacker\'s copy of it now belongs to user 42.',
        ],
        [
            'question' => 'Why can a page on another site make a logged-in user\'s browser change their email on your site?',
            'options' => [
                'It can read your cookies',
                'The browser attaches your site\'s cookies to requests to your site, even when another site triggers them',
                'It guesses the password',
                'It can\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s CSRF. The attacker can\'t see the response, but the action still happens.',
        ],
        [
            'question' => 'Which requests should a CSRF check require a token for?',
            'options' => [
                'All requests, including GET',
                'POST, PUT, PATCH and DELETE, the ones that change things',
                'Only requests from logged-in users',
                'Only API requests',
            ],
            'correct_option' => 1,
            'explanation' => 'GET must never change state, so it can safely skip the check; links and images trigger GETs from anywhere.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $expected = 'a3f9c2e1';

                var_dump(
                    hash_equals($expected, 'a3f9c2e1'),
                    hash_equals($expected, 'A3F9C2E1'),
                    hash_equals($expected, 'a3f9c2'),
                );
                PHP,
            'options' => [
                '`bool(true)`, `bool(true)`, `bool(false)`',
                '`bool(true)`, `bool(false)`, `bool(false)`',
                '`bool(true)`, `bool(false)`, `bool(true)`',
                '`bool(false)`, `bool(false)`, `bool(false)`',
            ],
            'correct_option' => 1,
            'explanation' => '`hash_equals()` needs an exact match, case and length included, and takes the same time however much matches.',
        ],
        [
            'question' => 'What does `SameSite=Lax` do for a session cookie?',
            'options' => [
                'Sends it with every request',
                'Sends it when the user follows a link to your site, but not with cross-site form posts or background requests',
                'Never sends it',
                'Sends it only over HTTP',
            ],
            'correct_option' => 1,
            'explanation' => 'Lax blocks the classic CSRF form post while keeping incoming links logged in.',
        ],
        [
            'question' => 'In a selector/validator remember-me token, what is stored in the database?',
            'options' => [
                'Both parts, as they are',
                'The selector as it is, and only a hash of the validator',
                'Only the user\'s password',
                'Nothing; it\'s all in the cookie',
            ],
            'correct_option' => 1,
            'explanation' => 'The selector finds the row; the validator is a secret, so only its hash is kept and compared with `hash_equals()`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A Set-Cookie header',
            'prompt' => 'Write `setCookieHeader(string $name, string $value, array $options = []): string`, returning a header like `Set-Cookie: session=abc; Path=/; Max-Age=7200; Secure; HttpOnly; SameSite=Lax`. Defaults: `path` `/`, `secure` true, `httponly` true, `samesite` `Lax`, no `max_age`; options override them. Leave out `Max-Age` unless given and `Secure`/`HttpOnly` when false. The value is URL-encoded with `rawurlencode()`. `SameSite=None` without `secure` throws `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function setCookieHeader(string $name, string $value, array $options = []): string
                {
                    return "Set-Cookie: $name=$value";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function setCookieHeader(string $name, string $value, array $options = []): string
                {
                    $options += ['path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax', 'max_age' => null];

                    if ($options['samesite'] === 'None' && ! $options['secure']) {
                        throw new InvalidArgumentException('SameSite=None cookies must be Secure.');
                    }

                    $parts = [$name . '=' . rawurlencode($value), 'Path=' . $options['path']];
                    if ($options['max_age'] !== null) {
                        $parts[] = 'Max-Age=' . $options['max_age'];
                    }
                    if ($options['secure']) {
                        $parts[] = 'Secure';
                    }
                    if ($options['httponly']) {
                        $parts[] = 'HttpOnly';
                    }
                    $parts[] = 'SameSite=' . $options['samesite'];

                    return 'Set-Cookie: ' . implode('; ', $parts);
                }
                PHP,
            'hint' => '`$options += [...defaults]` fills in what wasn\'t given. Build a list of parts and join them with `; `.',
            'checks' => [
                ['label' => 'Safe defaults', 'code' => "return expect_same(setCookieHeader('session', 'abc'), 'Set-Cookie: session=abc; Path=/; Secure; HttpOnly; SameSite=Lax');"],
                ['label' => 'Options and Max-Age', 'code' => "return expect_same(setCookieHeader('theme', 'dark', ['httponly' => false, 'max_age' => 31536000, 'samesite' => 'Strict']), 'Set-Cookie: theme=dark; Path=/; Max-Age=31536000; Secure; SameSite=Strict');"],
                ['label' => 'Values are encoded', 'code' => "return expect_same(setCookieHeader('q', 'a b;c=d'), 'Set-Cookie: q=a%20b%3Bc%3Dd; Path=/; Secure; HttpOnly; SameSite=Lax');"],
                ['label' => 'SameSite=None needs Secure', 'code' => "if (setCookieHeader('x', '1', ['samesite' => 'None']) !== 'Set-Cookie: x=1; Path=/; Secure; HttpOnly; SameSite=None') return 'SameSite=None with Secure should be allowed.';\nreturn expect_throws(InvalidArgumentException::class, fn () => setCookieHeader('x', '1', ['samesite' => 'None', 'secure' => false]));"],
            ],
        ],
        [
            'title' => 'New id at login',
            'prompt' => 'Finish `Session`. `regenerate()` gives it a new random id (40 hex characters from `random_bytes(20)`), keeping its data. `login(int $userId)` regenerates and then stores `user_id`. `logout()` empties all the data and regenerates.',
            'starter_code' => <<<'PHP'
                <?php

                class Session
                {
                    public string $id;
                    public array $data = [];

                    public function __construct()
                    {
                        $this->id = bin2hex(random_bytes(20));
                    }

                    public function regenerate(): void
                    {
                    }

                    public function login(int $userId): void
                    {
                        $this->data['user_id'] = $userId;
                    }

                    public function logout(): void
                    {
                        unset($this->data['user_id']);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Session
                {
                    public string $id;
                    public array $data = [];

                    public function __construct()
                    {
                        $this->id = bin2hex(random_bytes(20));
                    }

                    public function regenerate(): void
                    {
                        $this->id = bin2hex(random_bytes(20));
                    }

                    public function login(int $userId): void
                    {
                        $this->regenerate();
                        $this->data['user_id'] = $userId;
                    }

                    public function logout(): void
                    {
                        $this->data = [];
                        $this->regenerate();
                    }
                }
                PHP,
            'hint' => '`regenerate()` sets `$this->id` to a fresh `bin2hex(random_bytes(20))`. Call it from `login()` and `logout()`.',
            'checks' => [
                ['label' => 'Login changes the id and keeps data', 'code' => "\$s = new Session();\n\$s->data['basket'] = ['Dune'];\n\$before = \$s->id;\n\$s->login(42);\nreturn \$s->id !== \$before && preg_match('/^[a-f0-9]{40}\$/', \$s->id) === 1 && \$s->data === ['basket' => ['Dune'], 'user_id' => 42] ?: 'login() must give a new 40-character id and keep the data.';"],
                ['label' => 'Logout empties and changes the id', 'code' => "\$s = new Session();\n\$s->login(42);\n\$s->data['csrf'] = 'x';\n\$before = \$s->id;\n\$s->logout();\nreturn \$s->data === [] && \$s->id !== \$before ?: 'logout() must clear everything and give a new id.';"],
                ['label' => 'Ids are random', 'code' => "\$s = new Session();\n\$ids = [];\nfor (\$i = 0; \$i < 5; \$i++) { \$s->regenerate(); \$ids[] = \$s->id; }\nreturn count(array_unique(\$ids)) === 5 ?: 'regenerate() gave a repeated id.';"],
            ],
        ],
        [
            'title' => 'CSRF tokens',
            'prompt' => 'Write `csrfToken(array &$session): string`, which creates a token (64 hex characters from `random_bytes(32)`) in `$session[\'_token\']` the first time and returns the same one after that. Then write `csrfPasses(array $session, string $method, mixed $submitted): bool`: `GET`, `HEAD` and `OPTIONS` (any case) always pass; other methods pass only if the session has a token and the submitted value is a string equal to it, compared with `hash_equals()`.',
            'starter_code' => <<<'PHP'
                <?php

                function csrfToken(array &$session): string
                {
                    return 'token';
                }

                function csrfPasses(array $session, string $method, mixed $submitted): bool
                {
                    return $method === 'GET' || $submitted == ($session['_token'] ?? null);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function csrfToken(array &$session): string
                {
                    return $session['_token'] ??= bin2hex(random_bytes(32));
                }

                function csrfPasses(array $session, string $method, mixed $submitted): bool
                {
                    if (in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
                        return true;
                    }

                    $token = $session['_token'] ?? null;

                    return is_string($token) && is_string($submitted) && hash_equals($token, $submitted);
                }
                PHP,
            'hint' => '`$session[\'_token\'] ??= ...` creates it only once. For unsafe methods, both values must be strings before `hash_equals()`.',
            'checks' => [
                ['label' => 'One token per session', 'code' => "\$session = [];\n\$a = csrfToken(\$session);\n\$b = csrfToken(\$session);\nreturn \$a === \$b && preg_match('/^[a-f0-9]{64}\$/', \$a) === 1 && (\$session['_token'] ?? null) === \$a ?: 'The token should be created once, stored, and be 64 hex characters.';"],
                ['label' => 'Different sessions, different tokens', 'code' => "\$x = []; \$y = [];\nreturn csrfToken(\$x) !== csrfToken(\$y) ?: 'Two sessions got the same token.';"],
                ['label' => 'Safe methods pass', 'code' => "return expect_same([csrfPasses([], 'GET', null), csrfPasses([], 'head', null), csrfPasses([], 'OPTIONS', null)], [true, true, true]);"],
                ['label' => 'Unsafe methods need the right token', 'code' => "\$s = ['_token' => str_repeat('a', 64)];\nreturn expect_same([csrfPasses(\$s, 'POST', str_repeat('a', 64)), csrfPasses(\$s, 'delete', 'guess'), csrfPasses(\$s, 'PUT', null), csrfPasses(\$s, 'PATCH', true), csrfPasses([], 'POST', '')], [true, false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'Check the Origin',
            'prompt' => 'Write `sameOrigin(?string $origin, ?string $referer, string $appUrl): bool`. Use the `Origin` header if there is one, otherwise the `Referer`; with neither, return false. It matches if its scheme, host (ignoring case) and port are exactly those of `$appUrl`, with the default port (80 for http, 443 for https) when none is written. Compare the parsed parts, never prefixes of strings, so `https://shop.example.evil.net` doesn\'t match `https://shop.example`.',
            'starter_code' => <<<'PHP'
                <?php

                function sameOrigin(?string $origin, ?string $referer, string $appUrl): bool
                {
                    return str_starts_with($origin ?? $referer ?? '', $appUrl);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sameOrigin(?string $origin, ?string $referer, string $appUrl): bool
                {
                    $source = $origin ?? $referer;
                    if ($source === null || $source === '') {
                        return false;
                    }

                    $parts = function (string $url): ?array {
                        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                        if ($scheme === '' || $host === '') {
                            return null;
                        }
                        $port = parse_url($url, PHP_URL_PORT) ?? ($scheme === 'https' ? 443 : 80);

                        return [$scheme, $host, $port];
                    };

                    $actual = $parts($source);

                    return $actual !== null && $actual === $parts($appUrl);
                }
                PHP,
            'hint' => '`parse_url($url, PHP_URL_SCHEME)`, `PHP_URL_HOST` and `PHP_URL_PORT` pull out each part. Build `[scheme, host, port]` for both URLs and compare the arrays.',
            'checks' => [
                ['label' => 'Our own origin', 'code' => "return expect_same([sameOrigin('https://shop.example', null, 'https://shop.example'), sameOrigin('https://SHOP.example:443', null, 'https://shop.example')], [true, true]);"],
                ['label' => 'Look-alike hosts are refused', 'code' => "return expect_same([sameOrigin('https://shop.example.evil.net', null, 'https://shop.example'), sameOrigin('https://evil.net/shop.example', null, 'https://shop.example'), sameOrigin('https://shop.example@evil.net', null, 'https://shop.example')], [false, false, false]);"],
                ['label' => 'Scheme and port matter', 'code' => "return expect_same([sameOrigin('http://shop.example', null, 'https://shop.example'), sameOrigin('https://shop.example:8443', null, 'https://shop.example')], [false, false]);"],
                ['label' => 'Referer as a fallback; neither fails', 'code' => "return expect_same([sameOrigin(null, 'https://shop.example/basket?x=1', 'https://shop.example'), sameOrigin(null, null, 'https://shop.example'), sameOrigin('null', null, 'https://shop.example')], [true, false, false]);"],
            ],
        ],
        [
            'title' => 'Session timeouts',
            'prompt' => 'Write `sessionExpired(array $session, int $now, int $idle = 1800, int $absolute = 28800): bool`. A session has `created_at` and `last_activity` timestamps. It has expired if `$idle` seconds or more passed since the last activity, or `$absolute` seconds or more since it was created. A session missing either timestamp counts as expired.',
            'starter_code' => <<<'PHP'
                <?php

                function sessionExpired(array $session, int $now, int $idle = 1800, int $absolute = 28800): bool
                {
                    return false;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sessionExpired(array $session, int $now, int $idle = 1800, int $absolute = 28800): bool
                {
                    if (! isset($session['created_at'], $session['last_activity'])) {
                        return true;
                    }

                    return $now - $session['last_activity'] >= $idle || $now - $session['created_at'] >= $absolute;
                }
                PHP,
            'hint' => '`isset()` with two arguments checks both keys. Then compare each difference with its limit using `>=`.',
            'checks' => [
                ['label' => 'Active sessions live on', 'code' => "return expect_same(sessionExpired(['created_at' => 0, 'last_activity' => 10000], 11000), false);"],
                ['label' => 'Idle timeout', 'code' => "return expect_same([sessionExpired(['created_at' => 0, 'last_activity' => 100], 1899), sessionExpired(['created_at' => 0, 'last_activity' => 100], 1900)], [false, true]);"],
                ['label' => 'Absolute timeout, however active', 'code' => "return expect_same([sessionExpired(['created_at' => 0, 'last_activity' => 28790], 28799), sessionExpired(['created_at' => 0, 'last_activity' => 28790], 28800)], [false, true]);"],
                ['label' => 'Custom limits and missing data', 'code' => "return expect_same([sessionExpired(['created_at' => 0, 'last_activity' => 0], 60, idle: 60), sessionExpired(['last_activity' => 5], 6)], [true, true]);"],
            ],
        ],
        [
            'title' => 'Remember me',
            'prompt' => 'Write `createRememberToken(int $userId): array` returning `[\'cookie\' => \'selector:validator\', \'record\' => [\'selector\' => ..., \'hash\' => ..., \'user_id\' => ...]]`, with a selector of 24 and a validator of 64 random hex characters, and the record holding the SHA-256 hash of the validator. Then write `userFromRememberCookie(string $cookie, array $records): ?int`: find the record with the cookie\'s selector, compare hashes with `hash_equals()`, and return its `user_id`, or `null` for anything malformed or wrong.',
            'starter_code' => <<<'PHP'
                <?php

                function createRememberToken(int $userId): array
                {
                    return ['cookie' => (string) $userId, 'record' => ['selector' => '', 'hash' => '', 'user_id' => $userId]];
                }

                function userFromRememberCookie(string $cookie, array $records): ?int
                {
                    return (int) $cookie;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function createRememberToken(int $userId): array
                {
                    $selector = bin2hex(random_bytes(12));
                    $validator = bin2hex(random_bytes(32));

                    return [
                        'cookie' => "$selector:$validator",
                        'record' => ['selector' => $selector, 'hash' => hash('sha256', $validator), 'user_id' => $userId],
                    ];
                }

                function userFromRememberCookie(string $cookie, array $records): ?int
                {
                    if (preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m) !== 1) {
                        return null;
                    }

                    foreach ($records as $record) {
                        if ($record['selector'] === $m[1]) {
                            return hash_equals($record['hash'], hash('sha256', $m[2])) ? $record['user_id'] : null;
                        }
                    }

                    return null;
                }
                PHP,
            'hint' => '`bin2hex(random_bytes(12))` gives 24 hex characters. Check the cookie\'s shape with a pattern that captures both parts, find the record by selector, then compare the validator\'s hash.',
            'checks' => [
                ['label' => 'The cookie and record fit together', 'code' => "\$t = createRememberToken(42);\n[\$selector, \$validator] = explode(':', \$t['cookie']) + [1 => ''];\nreturn strlen(\$selector) === 24 && strlen(\$validator) === 64 && \$t['record'] === ['selector' => \$selector, 'hash' => hash('sha256', \$validator), 'user_id' => 42] ?: 'The cookie and record don\\'t match the description.';"],
                ['label' => 'The right cookie logs in', 'code' => "\$a = createRememberToken(7);\n\$b = createRememberToken(9);\nreturn expect_same([userFromRememberCookie(\$a['cookie'], [\$a['record'], \$b['record']]), userFromRememberCookie(\$b['cookie'], [\$a['record'], \$b['record']])], [7, 9]);"],
                ['label' => 'Wrong validators and unknown selectors fail', 'code' => "\$a = createRememberToken(7);\n[\$selector] = explode(':', \$a['cookie']);\nreturn expect_same([userFromRememberCookie(\$selector . ':' . str_repeat('0', 64), [\$a['record']]), userFromRememberCookie(str_repeat('b', 24) . ':' . str_repeat('0', 64), [\$a['record']])], [null, null]);"],
                ['label' => 'Malformed cookies fail', 'code' => "\$a = createRememberToken(7);\nreturn expect_same([userFromRememberCookie('7', [\$a['record']]), userFromRememberCookie('', [\$a['record']]), userFromRememberCookie(\$a['cookie'] . 'x', [\$a['record']])], [null, null, null]);"],
            ],
        ],
    ],
];
