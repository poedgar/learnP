<?php

// Lesson 8 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'authentication-and-authorization',
    'title' => 'Authentication and authorization',
    'summary' => 'Accounts in Laravel: starter kits, `Auth::attempt()`, the `auth` and `guest` middleware, intended URLs, logging out properly, and gates, `@can` and roles.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a wrong password.
        $users = [
            'ada@example.com' => ['id' => 1, 'name' => 'Ada', 'password' => password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4])],
        ];
        $session = [];

        $email = 'ada@example.com';
        $password = 'correct horse';

        $user = $users[$email] ?? null;
        if ($user !== null && password_verify($password, $user['password'])) {
            $session['user_id'] = $user['id'];
            echo "Welcome back, {$user['name']}!";
        } else {
            echo 'These credentials do not match our records.';
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does a Laravel starter kit like Breeze give you?',
            'options' => [
                'A database server',
                'Ready-made registration, login, logout, password reset and email verification, with pages and tests',
                'A hosting account',
                'A payment system',
            ],
            'correct_option' => 1,
            'explanation' => 'One command generates the whole authentication flow, which you can then change.',
        ],
        [
            'question' => 'How does `Auth::attempt()` check the password?',
            'options' => [
                'It compares it with the stored password using `===`',
                'It hashes nothing; passwords are stored as they are',
                'With `password_verify()` against the stored hash',
                'It emails the user',
            ],
            'correct_option' => 2,
            'explanation' => 'Passwords are stored as hashes made with `Hash::make()`; `password_verify()` checks a guess against one.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $hash = password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4]);

                var_dump(password_verify('correct horse', $hash), password_verify('Correct horse', $hash), $hash === 'correct horse');
                PHP,
            'options' => [
                '`bool(true)`, `bool(true)`, `bool(false)`',
                '`bool(true)`, `bool(false)`, `bool(false)`',
                '`bool(false)`, `bool(false)`, `bool(true)`',
                '`bool(true)`, `bool(false)`, `bool(true)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Only the exact password verifies, and the hash looks nothing like the password.',
        ],
        [
            'question' => 'A guest visits `/orders`, which has the `auth` middleware. What happens?',
            'options' => [
                'They see an empty page',
                'They are redirected to the login page, and `/orders` is remembered so they return there after logging in',
                'They get a 500 error',
                'They are logged in automatically',
            ],
            'correct_option' => 1,
            'explanation' => '`redirect()->intended()` after login uses the remembered URL.',
        ],
        [
            'question' => 'What is the `guest` middleware for?',
            'options' => [
                'Pages only guests may see, like the login form; logged-in users are redirected away',
                'Allowing guests everywhere',
                'Logging guests\' visits',
                'Creating guest accounts',
            ],
            'correct_option' => 0,
            'explanation' => 'There\'s no point showing a login form to someone already logged in.',
        ],
        [
            'question' => 'Why call `$request->session()->regenerate()` after a successful login?',
            'options' => [
                'To save memory',
                'To give the session a new id, so an id an attacker planted before login is useless',
                'To log the user out of other devices',
                'It is optional decoration',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s the defence against session fixation.',
        ],
        [
            'question' => 'Which steps make up a proper logout in Laravel?',
            'options' => [
                'Only `Auth::logout()`',
                '`Auth::logout()`, invalidate the session, regenerate the CSRF token, then redirect',
                'Delete the user',
                'Clear the browser cache',
            ],
            'correct_option' => 1,
            'explanation' => 'Invalidating throws away the session\'s data and id; a new CSRF token stops old forms from working.',
        ],
        [
            'question' => 'What does `@can(\'manage-stock\') ... @endcan` do in a Blade view?',
            'options' => [
                'Protects the stock routes',
                'Shows its content only if the current user is allowed `manage-stock`',
                'Defines a new gate',
                'Logs the user in',
            ],
            'correct_option' => 1,
            'explanation' => 'It only hides things in the page. The routes behind them must check permission themselves.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Auth::attempt()',
            'prompt' => 'Finish `Auth`, constructed with a list of users (arrays with `id`, `email` and a password `hash`). `attempt(string $email, string $password): bool` finds the user by email, ignoring case and surrounding spaces, and checks the password with `password_verify()`; on success it remembers the user and returns `true`. `check()` says whether someone is logged in, `user()` returns them (or `null`), and `logout()` forgets them.',
            'starter_code' => <<<'PHP'
                <?php

                class Auth
                {
                    public function __construct(private array $users) {}

                    public function attempt(string $email, string $password): bool
                    {
                        return false;
                    }

                    public function check(): bool
                    {
                        return false;
                    }

                    public function user(): ?array
                    {
                        return null;
                    }

                    public function logout(): void
                    {
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Auth
                {
                    private ?array $user = null;

                    public function __construct(private array $users) {}

                    public function attempt(string $email, string $password): bool
                    {
                        $email = strtolower(trim($email));

                        foreach ($this->users as $user) {
                            if (strtolower($user['email']) === $email && password_verify($password, $user['hash'])) {
                                $this->user = $user;

                                return true;
                            }
                        }

                        return false;
                    }

                    public function check(): bool
                    {
                        return $this->user !== null;
                    }

                    public function user(): ?array
                    {
                        return $this->user;
                    }

                    public function logout(): void
                    {
                        $this->user = null;
                    }
                }
                PHP,
            'hint' => 'Keep the logged-in user in a private property. Normalise the email with `strtolower(trim(...))` before comparing.',
            'checks' => [
                ['label' => 'Correct details log in', 'code' => "\$auth = new Auth([['id' => 1, 'email' => 'ada@example.com', 'hash' => password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4])]]);\nreturn expect_same([\$auth->attempt(' ADA@example.com ', 'correct horse'), \$auth->check(), \$auth->user()['id'] ?? null], [true, true, 1]);"],
                ['label' => 'Wrong details don\'t', 'code' => "\$auth = new Auth([['id' => 1, 'email' => 'ada@example.com', 'hash' => password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4])]]);\nreturn expect_same([\$auth->attempt('ada@example.com', 'wrong'), \$auth->attempt('bob@example.com', 'correct horse'), \$auth->check(), \$auth->user()], [false, false, false, null]);"],
                ['label' => 'logout() forgets the user', 'code' => "\$auth = new Auth([['id' => 1, 'email' => 'ada@example.com', 'hash' => password_hash('pw', PASSWORD_DEFAULT, ['cost' => 4])]]);\n\$auth->attempt('ada@example.com', 'pw');\n\$auth->logout();\nreturn expect_same([\$auth->check(), \$auth->user()], [false, null]);"],
                ['label' => 'password_verify() is used', 'code' => "return str_contains(learnp_code(), 'password_verify') ?: 'Check passwords with password_verify().';"],
            ],
        ],
        [
            'title' => 'The auth middleware',
            'prompt' => 'Write `requireAuth(array &$session, string $path): ?array`. If `$session[\'user_id\']` is set, return `null` (let the request through). Otherwise remember the path in `$session[\'url.intended\']` and return `[\'status\' => 302, \'location\' => \'/login\']`.',
            'starter_code' => <<<'PHP'
                <?php

                function requireAuth(array &$session, string $path): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function requireAuth(array &$session, string $path): ?array
                {
                    if (isset($session['user_id'])) {
                        return null;
                    }

                    $session['url.intended'] = $path;

                    return ['status' => 302, 'location' => '/login'];
                }
                PHP,
            'hint' => 'Return early when the user is logged in. Otherwise write the intended path into the session before returning the redirect.',
            'checks' => [
                ['label' => 'Logged-in users pass', 'code' => "\$session = ['user_id' => 7];\nreturn expect_same([requireAuth(\$session, '/orders'), \$session], [null, ['user_id' => 7]]);"],
                ['label' => 'Guests go to the login page', 'code' => "\$session = [];\nreturn expect_same([requireAuth(\$session, '/orders/12'), \$session], [['status' => 302, 'location' => '/login'], ['url.intended' => '/orders/12']]);"],
            ],
        ],
        [
            'title' => 'Back to where they were',
            'prompt' => 'Write `intended(array &$session, string $default = \'/dashboard\'): string`, like `redirect()->intended()`. It takes `url.intended` out of the session (so it\'s used only once) and returns it if it\'s a local path: starting with one `/`, not `//`. Otherwise it returns the default.',
            'starter_code' => <<<'PHP'
                <?php

                function intended(array &$session, string $default = '/dashboard'): string
                {
                    return $session['url.intended'] ?? $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function intended(array &$session, string $default = '/dashboard'): string
                {
                    $url = $session['url.intended'] ?? null;
                    unset($session['url.intended']);

                    if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                        return $url;
                    }

                    return $default;
                }
                PHP,
            'hint' => 'Read the value, `unset()` it, then check `str_starts_with($url, \'/\')` and not `\'//\'`.',
            'checks' => [
                ['label' => 'The remembered page, once', 'code' => "\$session = ['url.intended' => '/orders/12', 'user_id' => 1];\nreturn expect_same([intended(\$session), intended(\$session), \$session], ['/orders/12', '/dashboard', ['user_id' => 1]]);"],
                ['label' => 'The default without one', 'code' => "\$session = [];\nreturn expect_same(intended(\$session, '/home'), '/home');"],
                ['label' => 'Other sites are refused', 'code' => "\$a = ['url.intended' => '//evil.example'];\n\$b = ['url.intended' => 'https://evil.example'];\nreturn expect_same([intended(\$a), intended(\$b)], ['/dashboard', '/dashboard']);"],
            ],
        ],
        [
            'title' => 'The guest middleware',
            'prompt' => 'Write `redirectIfAuthenticated(array $session, string $home = \'/dashboard\'): ?array`, the middleware for the login and register pages. A logged-in user (with `user_id` in the session) gets `[\'status\' => 302, \'location\' => $home]`; guests get `null` and see the page.',
            'starter_code' => <<<'PHP'
                <?php

                function redirectIfAuthenticated(array $session, string $home = '/dashboard'): ?array
                {
                    return ['status' => 302, 'location' => $home];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function redirectIfAuthenticated(array $session, string $home = '/dashboard'): ?array
                {
                    return isset($session['user_id']) ? ['status' => 302, 'location' => $home] : null;
                }
                PHP,
            'hint' => 'It\'s the opposite of the `auth` middleware: redirect when `user_id` is set.',
            'checks' => [
                ['label' => 'Logged-in users are sent home', 'code' => "return expect_same([redirectIfAuthenticated(['user_id' => 1]), redirectIfAuthenticated(['user_id' => 1], '/account')], [['status' => 302, 'location' => '/dashboard'], ['status' => 302, 'location' => '/account']]);"],
                ['label' => 'Guests see the page', 'code' => 'return expect_same(redirectIfAuthenticated([]), null);'],
            ],
        ],
        [
            'title' => 'Role middleware',
            'prompt' => 'Write `checkRole(?array $user, string $middleware): ?array` for a middleware written like `role:admin,editor`. Take the roles after the colon. A guest (`null`) gets `[\'status\' => 302, \'location\' => \'/login\']`; a user whose `role` isn\'t one of them gets `[\'status\' => 403]`; anyone else gets `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function checkRole(?array $user, string $middleware): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function checkRole(?array $user, string $middleware): ?array
                {
                    if ($user === null) {
                        return ['status' => 302, 'location' => '/login'];
                    }

                    [, $list] = array_pad(explode(':', $middleware, 2), 2, '');
                    $roles = $list === '' ? [] : explode(',', $list);

                    return in_array($user['role'] ?? null, $roles, true) ? null : ['status' => 403];
                }
                PHP,
            'hint' => 'Split once at `:`, then the roles at `,`. Compare with `in_array(..., true)`.',
            'checks' => [
                ['label' => 'Allowed roles pass', 'code' => "return expect_same([checkRole(['role' => 'admin'], 'role:admin,editor'), checkRole(['role' => 'editor'], 'role:admin,editor')], [null, null]);"],
                ['label' => 'Other roles get 403', 'code' => "return expect_same([checkRole(['role' => 'reader'], 'role:admin,editor'), checkRole(['role' => 'admin'], 'role:'), checkRole([], 'role:admin')], [['status' => 403], ['status' => 403], ['status' => 403]]);"],
                ['label' => 'Guests go to the login page', 'code' => "return expect_same(checkRole(null, 'role:admin'), ['status' => 302, 'location' => '/login']);"],
            ],
        ],
        [
            'title' => 'Log out properly',
            'prompt' => 'Write `logout(array $session, Closure $newId, Closure $newToken): array`, returning the session after logging out the way Laravel does: all old data is thrown away (invalidate), it gets a new `_id` from `$newId()`, and a new CSRF `_token` from `$newToken()`. Return exactly `[\'_id\' => ..., \'_token\' => ...]`.',
            'starter_code' => <<<'PHP'
                <?php

                function logout(array $session, Closure $newId, Closure $newToken): array
                {
                    unset($session['user_id']);

                    return $session;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function logout(array $session, Closure $newId, Closure $newToken): array
                {
                    return ['_id' => $newId(), '_token' => $newToken()];
                }
                PHP,
            'hint' => 'Invalidating means starting from an empty session, so you don\'t need anything from the old one.',
            'checks' => [
                ['label' => 'A fresh session', 'code' => "\$old = ['_id' => 'old-id', '_token' => 'old-token', 'user_id' => 7, 'basket' => ['Dune']];\nreturn expect_same(logout(\$old, fn () => 'new-id', fn () => 'new-token'), ['_id' => 'new-id', '_token' => 'new-token']);"],
                ['label' => 'Ids and tokens really change', 'code' => "\$session = logout(['_id' => 'a', '_token' => 'b'], fn () => bin2hex(random_bytes(20)), fn () => bin2hex(random_bytes(20)));\nreturn \$session['_id'] !== 'a' && \$session['_token'] !== 'b' ?: 'The id and token must be new.';"],
            ],
        ],
    ],
];
