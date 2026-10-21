<?php

// Lesson 8 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'authorization-and-mass-assignment',
    'title' => 'Authorization and mass assignment',
    'summary' => 'Making sure users only do what they may: ownership checks against insecure direct object references, deny-by-default gates and policies, mass assignment, field-level rules and open redirects.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try requesting invoice 1041 instead.
        $invoices = [
            1040 => ['user_id' => 7, 'total' => 120],
            1041 => ['user_id' => 42, 'total' => 35],
        ];
        $currentUserId = 42;
        $requested = 1040;

        $invoice = $invoices[$requested] ?? null;

        if ($invoice === null || $invoice['user_id'] !== $currentUserId) {
            echo '404 Not Found';
        } else {
            echo "Invoice $requested: £{$invoice['total']}";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What is the difference between authentication and authorization?',
            'options' => [
                'There is none',
                'Authentication checks who you are; authorization checks what you\'re allowed to do',
                'Authorization happens first',
                'Authentication is for APIs, authorization for websites',
            ],
            'correct_option' => 1,
            'explanation' => 'Being logged in doesn\'t mean you may see every invoice.',
        ],
        [
            'question' => 'A logged-in user changes `/orders/512` to `/orders/511` and sees a stranger\'s order. What is this bug called?',
            'options' => ['Cross-site scripting', 'An insecure direct object reference (broken access control)', 'SQL injection', 'Session fixation'],
            'correct_option' => 1,
            'explanation' => 'The code checked the login, not the ownership of the record.',
        ],
        [
            'question' => 'Why is `$request->user()->invoices()->findOrFail($id)` safer than `Invoice::findOrFail($id)`?',
            'options' => [
                'It is faster',
                'It can only find invoices that belong to the user, so the ownership check can\'t be forgotten',
                'It caches the result',
                'It is the same',
            ],
            'correct_option' => 1,
            'explanation' => 'Querying through the owner scopes the lookup automatically.',
        ],
        [
            'question' => 'What does "deny by default" mean for authorization?',
            'options' => [
                'Everyone is blocked from everything',
                'Anything not explicitly allowed, including abilities nobody defined, is refused',
                'Admins are denied first',
                'Users must ask for permission by email',
            ],
            'correct_option' => 1,
            'explanation' => 'Mistakes then fail closed: a missing rule refuses instead of allowing.',
        ],
        [
            'question' => 'The "Delete" button is hidden from non-admins. Is the delete route safe?',
            'options' => [
                'Yes, they can\'t see the button',
                'No: anyone can send the request directly, so the route itself must check',
                'Yes, if the route uses POST',
                'Only if JavaScript is disabled',
            ],
            'correct_option' => 1,
            'explanation' => 'The interface is a convenience; the server is where permission is enforced.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $user = ['name' => 'Ada', 'role' => 'reader'];
                $input = ['name' => 'Ada L.', 'role' => 'admin'];
                $fillable = ['name', 'email'];

                $safe = array_merge($user, array_intersect_key($input, array_flip($fillable)));

                echo $safe['name'] . ' ' . $safe['role'];
                PHP,
            'options' => ['`Ada L. admin`', '`Ada L. reader`', '`Ada reader`', '`Ada admin`'],
            'correct_option' => 1,
            'explanation' => 'Only the fillable keys are copied from the input, so the role stays as it was.',
        ],
        [
            'question' => 'In a Laravel `Gate::before()` callback, what does returning `null` mean?',
            'options' => [
                'Deny',
                'Allow',
                'No opinion: carry on to the normal ability check',
                'An error',
            ],
            'correct_option' => 2,
            'explanation' => '`true` allows everything and `false` denies everything; `null` lets the normal rule decide.',
        ],
        [
            'question' => 'Which redirect target after login should be refused?',
            'options' => ['`/basket`', '`/books/7?tab=reviews`', '`//evil.example/login`', '`/`'],
            'correct_option' => 2,
            'explanation' => 'A leading `//` is a protocol-relative URL: browsers treat it as another site.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Whose invoice?',
            'prompt' => 'Write `canView(array $user, array $invoice): bool`: true if the invoice\'s `user_id` is the user\'s `id`, or the user\'s `role` is `admin`. Then write `findInvoice(array $user, int $id, array $invoices): ?array`, which returns the invoice with that id (the array is keyed by id) only if the user may view it, and `null` if it doesn\'t exist **or** isn\'t theirs, so the two look the same.',
            'starter_code' => <<<'PHP'
                <?php

                function canView(array $user, array $invoice): bool
                {
                    return true;
                }

                function findInvoice(array $user, int $id, array $invoices): ?array
                {
                    return $invoices[$id] ?? null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function canView(array $user, array $invoice): bool
                {
                    return $invoice['user_id'] === $user['id'] || ($user['role'] ?? null) === 'admin';
                }

                function findInvoice(array $user, int $id, array $invoices): ?array
                {
                    $invoice = $invoices[$id] ?? null;

                    return $invoice !== null && canView($user, $invoice) ? $invoice : null;
                }
                PHP,
            'hint' => 'Compare ids with `===`. In `findInvoice()`, return the invoice only when it exists and `canView()` says yes.',
            'checks' => [
                ['label' => 'Owners see their invoices', 'code' => "\$invoices = [1040 => ['id' => 1040, 'user_id' => 7], 1041 => ['id' => 1041, 'user_id' => 42]];\nreturn expect_same(findInvoice(['id' => 42, 'role' => 'reader'], 1041, \$invoices), ['id' => 1041, 'user_id' => 42]);"],
                ['label' => 'Others get null', 'code' => "\$invoices = [1040 => ['id' => 1040, 'user_id' => 7]];\nreturn expect_same([findInvoice(['id' => 42, 'role' => 'reader'], 1040, \$invoices), findInvoice(['id' => 42, 'role' => 'reader'], 9999, \$invoices)], [null, null]);"],
                ['label' => 'Admins see everything', 'code' => "return expect_same(canView(['id' => 1, 'role' => 'admin'], ['user_id' => 7]), true);"],
                ['label' => 'No loose id comparison', 'code' => "return expect_same(canView(['id' => '42abc', 'role' => 'reader'], ['user_id' => 42]), false);"],
            ],
        ],
        [
            'title' => 'A gate',
            'prompt' => 'Write `Gate`. `define(string $ability, Closure $rule)` stores a rule that receives the user and any extra arguments. `before(Closure $callback)` adds a callback that receives the user and the ability name. `allows(array $user, string $ability, mixed ...$arguments)` runs the `before` callbacks in order: the first one returning `true` or `false` decides. Otherwise the ability\'s rule decides (as a strict `true`), and an undefined ability is denied.',
            'starter_code' => <<<'PHP'
                <?php

                class Gate
                {
                    private array $abilities = [];

                    public function define(string $ability, Closure $rule): void
                    {
                        $this->abilities[$ability] = $rule;
                    }

                    public function before(Closure $callback): void
                    {
                    }

                    public function allows(array $user, string $ability, mixed ...$arguments): bool
                    {
                        return ! isset($this->abilities[$ability]) || ($this->abilities[$ability])($user, ...$arguments);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Gate
                {
                    private array $abilities = [];
                    private array $before = [];

                    public function define(string $ability, Closure $rule): void
                    {
                        $this->abilities[$ability] = $rule;
                    }

                    public function before(Closure $callback): void
                    {
                        $this->before[] = $callback;
                    }

                    public function allows(array $user, string $ability, mixed ...$arguments): bool
                    {
                        foreach ($this->before as $callback) {
                            $result = $callback($user, $ability);
                            if (is_bool($result)) {
                                return $result;
                            }
                        }

                        if (! isset($this->abilities[$ability])) {
                            return false;
                        }

                        return ($this->abilities[$ability])($user, ...$arguments) === true;
                    }
                }
                PHP,
            'hint' => 'A `before` callback decides only if it returns a bool (`is_bool()`); `null` means carry on. An unknown ability returns `false`.',
            'checks' => [
                ['label' => 'Rules get the user and arguments', 'code' => "\$gate = new Gate();\n\$gate->define('edit-book', fn (\$user, \$book) => \$user['id'] === \$book['author_id']);\nreturn expect_same([\$gate->allows(['id' => 3], 'edit-book', ['author_id' => 3]), \$gate->allows(['id' => 4], 'edit-book', ['author_id' => 3])], [true, false]);"],
                ['label' => 'Undefined abilities are denied', 'code' => "return expect_same((new Gate())->allows(['id' => 1], 'delete-everything'), false);"],
                ['label' => 'before() can decide or pass', 'code' => "\$gate = new Gate();\n\$gate->before(fn (\$user, \$ability) => \$user['super'] ?? null ? true : null);\n\$gate->define('edit-book', fn () => false);\nreturn expect_same([\$gate->allows(['super' => true], 'edit-book'), \$gate->allows(['super' => true], 'undefined'), \$gate->allows(['id' => 1], 'edit-book')], [true, true, false]);"],
                ['label' => 'Only a real true allows', 'code' => "\$gate = new Gate();\n\$gate->define('a', fn () => 1);\n\$gate->define('b', fn () => 'yes');\nreturn expect_same([\$gate->allows([], 'a'), \$gate->allows([], 'b')], [false, false]);"],
            ],
        ],
        [
            'title' => 'A book policy',
            'prompt' => 'Write `BookPolicy` with `update(array $user, array $book): bool` (editors may update any book; anyone may update a book whose `author_id` is their `id`) and `delete(array $user, array $book): bool` (admins only). Then write `authorize(object $policy, string $ability, array $user, array $book): void`, which throws `AuthorizationException` with status 403 when the policy method says no, or when the policy has no such method.',
            'starter_code' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException
                {
                    public function __construct(public int $status = 403)
                    {
                        parent::__construct('This action is unauthorized.');
                    }
                }

                class BookPolicy
                {
                }

                function authorize(object $policy, string $ability, array $user, array $book): void
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException
                {
                    public function __construct(public int $status = 403)
                    {
                        parent::__construct('This action is unauthorized.');
                    }
                }

                class BookPolicy
                {
                    public function update(array $user, array $book): bool
                    {
                        return $user['role'] === 'editor' || $book['author_id'] === $user['id'];
                    }

                    public function delete(array $user, array $book): bool
                    {
                        return $user['role'] === 'admin';
                    }
                }

                function authorize(object $policy, string $ability, array $user, array $book): void
                {
                    if (! method_exists($policy, $ability) || $policy->$ability($user, $book) !== true) {
                        throw new AuthorizationException(403);
                    }
                }
                PHP,
            'hint' => 'Each policy method returns a bool. `authorize()` checks `method_exists()` first, then calls `$policy->$ability($user, $book)`.',
            'checks' => [
                ['label' => 'Update rules', 'code' => "\$p = new BookPolicy();\n\$book = ['author_id' => 7];\nreturn expect_same([\$p->update(['id' => 7, 'role' => 'author'], \$book), \$p->update(['id' => 8, 'role' => 'editor'], \$book), \$p->update(['id' => 9, 'role' => 'author'], \$book)], [true, true, false]);"],
                ['label' => 'Only admins delete', 'code' => "\$p = new BookPolicy();\nreturn expect_same([\$p->delete(['id' => 1, 'role' => 'admin'], ['author_id' => 7]), \$p->delete(['id' => 7, 'role' => 'author'], ['author_id' => 7]), \$p->delete(['id' => 2, 'role' => 'editor'], ['author_id' => 7])], [true, false, false]);"],
                ['label' => 'authorize() throws 403 when refused', 'code' => "try { authorize(new BookPolicy(), 'delete', ['id' => 7, 'role' => 'author'], ['author_id' => 7]); } catch (AuthorizationException \$e) { return expect_same(\$e->status, 403); }\nreturn 'No AuthorizationException was thrown.';"],
                ['label' => 'Allowed actions pass; unknown ones throw', 'code' => "authorize(new BookPolicy(), 'update', ['id' => 7, 'role' => 'author'], ['author_id' => 7]);\nreturn expect_throws(AuthorizationException::class, fn () => authorize(new BookPolicy(), 'publish', ['id' => 1, 'role' => 'admin'], ['author_id' => 7]));"],
            ],
        ],
        [
            'title' => 'Only fillable fields',
            'prompt' => 'Write `fill(array $model, array $input, array $fillable): array`, returning the model with only the fillable keys of the input copied onto it. Every other input key is ignored, and keys of the model that aren\'t in the input are kept.',
            'starter_code' => <<<'PHP'
                <?php

                function fill(array $model, array $input, array $fillable): array
                {
                    return array_merge($model, $input);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function fill(array $model, array $input, array $fillable): array
                {
                    foreach ($fillable as $key) {
                        if (array_key_exists($key, $input)) {
                            $model[$key] = $input[$key];
                        }
                    }

                    return $model;
                }
                PHP,
            'hint' => 'Loop over `$fillable` and copy each key that the input has. `array_key_exists()` also copies `null` values.',
            'checks' => [
                ['label' => 'Fillable keys are copied', 'code' => "return expect_same(fill(['id' => 42, 'name' => 'Ada', 'role' => 'reader'], ['name' => 'Ada L.'], ['name', 'email']), ['id' => 42, 'name' => 'Ada L.', 'role' => 'reader']);"],
                ['label' => 'Everything else is ignored', 'code' => "return expect_same(fill(['id' => 42, 'role' => 'reader'], ['role' => 'admin', 'id' => 1, 'is_verified' => true], ['name', 'email']), ['id' => 42, 'role' => 'reader']);"],
                ['label' => 'null values are copied', 'code' => "return expect_same(fill(['bio' => 'x'], ['bio' => null], ['bio']), ['bio' => null]);"],
            ],
        ],
        [
            'title' => 'Who may change what',
            'prompt' => 'Write `updateUser(array $actor, array $target, array $input): array`. Users may update themselves, and admins (`role` `admin`) anyone; otherwise throw `AuthorizationException`. Anyone allowed may change `name` and `email`. `role` may only be changed by an admin, and never on their own account: in those cases the role in the input is ignored. Other input keys are always ignored. Return the updated target. `AuthorizationException` is given.',
            'starter_code' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException {}

                function updateUser(array $actor, array $target, array $input): array
                {
                    return array_merge($target, $input);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException {}

                function updateUser(array $actor, array $target, array $input): array
                {
                    $isAdmin = $actor['role'] === 'admin';
                    $isSelf = $actor['id'] === $target['id'];

                    if (! $isSelf && ! $isAdmin) {
                        throw new AuthorizationException('You may not edit this user.');
                    }

                    $allowed = ['name', 'email'];
                    if ($isAdmin && ! $isSelf) {
                        $allowed[] = 'role';
                    }

                    foreach ($allowed as $key) {
                        if (array_key_exists($key, $input)) {
                            $target[$key] = $input[$key];
                        }
                    }

                    return $target;
                }
                PHP,
            'hint' => 'Work out `$isAdmin` and `$isSelf` first. Build the list of allowed keys from them, then copy only those.',
            'checks' => [
                ['label' => 'Users edit their own profile, not their role', 'code' => "\$ada = ['id' => 42, 'name' => 'Ada', 'email' => 'a@x', 'role' => 'reader'];\nreturn expect_same(updateUser(\$ada, \$ada, ['name' => 'Ada L.', 'role' => 'admin', 'id' => 1]), ['id' => 42, 'name' => 'Ada L.', 'email' => 'a@x', 'role' => 'reader']);"],
                ['label' => 'Others can\'t edit you', 'code' => "return expect_throws(AuthorizationException::class, fn () => updateUser(['id' => 7, 'role' => 'editor'], ['id' => 42, 'name' => 'Ada', 'role' => 'reader'], ['name' => 'Hacked']));"],
                ['label' => 'Admins change other people\'s roles', 'code' => "\$admin = ['id' => 1, 'role' => 'admin'];\nreturn expect_same(updateUser(\$admin, ['id' => 42, 'name' => 'Ada', 'role' => 'reader'], ['role' => 'editor', 'name' => 'Ada L.']), ['id' => 42, 'name' => 'Ada L.', 'role' => 'editor']);"],
                ['label' => 'Admins can\'t change their own role', 'code' => "\$admin = ['id' => 1, 'name' => 'Root', 'role' => 'admin'];\nreturn expect_same(updateUser(\$admin, \$admin, ['role' => 'reader', 'name' => 'Boss']), ['id' => 1, 'name' => 'Boss', 'role' => 'admin']);"],
            ],
        ],
        [
            'title' => 'Safe redirects',
            'prompt' => 'Write `redirectTarget(?string $target, string $default = \'/\'): string`, deciding where to send someone after logging in. Return `$target` only if, after removing whitespace and control characters (bytes 0 to 32), it starts with exactly one `/` that isn\'t followed by another `/` or a `\\`. Otherwise return `$default`. Absolute URLs, even to your own site, are refused.',
            'starter_code' => <<<'PHP'
                <?php

                function redirectTarget(?string $target, string $default = '/'): string
                {
                    return $target ?? $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function redirectTarget(?string $target, string $default = '/'): string
                {
                    if ($target === null) {
                        return $default;
                    }

                    $clean = preg_replace('/[\x00-\x20]+/', '', $target);

                    return preg_match('#^/(?![/\\\\])#', $clean) === 1 ? $target : $default;
                }
                PHP,
            'hint' => 'Strip `[\x00-\x20]` first, as browsers do. Then a pattern like `#^/(?![/\\\\\\\\])#` means "a slash not followed by another slash or a backslash".',
            'checks' => [
                ['label' => 'Local paths are kept', 'code' => "return expect_same(array_map('redirectTarget', ['/basket', '/books/7?tab=reviews', '/']), ['/basket', '/books/7?tab=reviews', '/']);"],
                ['label' => 'Other sites are refused', 'code' => "return expect_same(array_map('redirectTarget', ['https://evil.example/login', '//evil.example', '/\\\\evil.example', 'evil.example']), ['/', '/', '/', '/']);"],
                ['label' => 'Disguises are refused', 'code' => "return expect_same(array_map('redirectTarget', [\" //evil.example\", \"/\\t/evil.example\", 'javascript:alert(1)', \"\\x00//evil.example\"]), ['/', '/', '/', '/']);"],
                ['label' => 'Missing targets use the default', 'code' => "return expect_same([redirectTarget(null, '/account'), redirectTarget('', '/account')], ['/account', '/account']);"],
            ],
        ],
    ],
];
