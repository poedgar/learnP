<?php

// Lesson 4 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'the-middleware-pipeline',
    'title' => 'The middleware pipeline',
    'summary' => 'How a request travels through middleware: `handle()` and `$next`, the onion built with `array_reduce()`, the Pipeline class, short-circuiting, parameters, groups and terminable middleware.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try swapping the order of the two layers.
        $layers = [
            fn ($request, $next) => 'outer(' . $next($request) . ')',
            fn ($request, $next) => 'inner(' . $next($request) . ')',
        ];
        $route = fn ($request) => "route:$request";

        $pipeline = array_reduce(
            array_reverse($layers),
            fn ($next, $layer) => fn ($request) => $layer($request, $next),
            $route,
        );

        echo $pipeline('/books');
        PHP,

    'quiz' => [
        [
            'question' => 'What is `$next` inside a middleware\'s `handle()` method?',
            'options' => [
                'The next request in the queue',
                'A closure that runs the rest of the pipeline, including the route, and returns the response',
                'The name of the next middleware',
                'The route\'s URL',
            ],
            'correct_option' => 1,
            'explanation' => 'Calling `$next($request)` passes the request inwards; its return value is the response coming back out.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $route = function (string $request) {
                    echo 'route ';
                    return 'OK';
                };

                $middleware = function (string $request, Closure $next) {
                    echo 'in ';
                    $response = $next($request);
                    echo 'out ';
                    return $response;
                };

                echo $middleware('/books', $route);
                PHP,
            'options' => ['`in out route OK`', '`in route out OK`', '`route in out OK`', '`in route OK out`'],
            'correct_option' => 1,
            'explanation' => 'Code before `$next()` runs first, then the route, then the code after `$next()`. The response is echoed last.',
        ],
        [
            'question' => 'A middleware returns a response without calling `$next`. What happens?',
            'options' => [
                'Laravel calls the route anyway',
                'The request stops there: the middleware and route further in never run',
                'An exception is thrown',
                'The next middleware is skipped, but the route runs',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s how authentication middleware turns away guests: it simply never passes the request on.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $layers = [
                    fn ($text, $next) => $next($text . 'A'),
                    fn ($text, $next) => $next($text . 'B'),
                    fn ($text, $next) => $next($text . 'C'),
                ];

                $pipeline = array_reduce(
                    array_reverse($layers),
                    fn ($next, $layer) => fn ($text) => $layer($text, $next),
                    fn ($text) => $text,
                );

                echo $pipeline('>');
                PHP,
            'options' => ['`>CBA`', '`>ABC`', '`ABC>`', '`>A`'],
            'correct_option' => 1,
            'explanation' => 'The first layer in the list is the outermost, so it runs first. Reversing before `array_reduce()` is what keeps that order.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $layers = [
                    fn ($text, $next) => $next($text) . 'A',
                    fn ($text, $next) => $next($text) . 'B',
                ];

                $pipeline = array_reduce(
                    array_reverse($layers),
                    fn ($next, $layer) => fn ($text) => $layer($text, $next),
                    fn ($text) => $text,
                );

                echo $pipeline('>');
                PHP,
            'options' => ['`>AB`', '`>BA`', '`AB>`', '`>B`'],
            'correct_option' => 1,
            'explanation' => 'On the way out the order reverses: the inner layer (B) adds to the response before the outer layer (A) does.',
        ],
        [
            'question' => 'A route has `->middleware(\'role:editor,admin\')`. How does `CheckRole::handle()` receive `editor` and `admin`?',
            'options' => [
                'As one string, `\'editor,admin\'`',
                'As extra arguments after `$next`',
                'Through `$request->roles`',
                'From the config file',
            ],
            'correct_option' => 1,
            'explanation' => 'Laravel splits the part after the colon at the commas and passes each value as an argument after `$next`.',
        ],
        [
            'question' => 'When does Laravel call a middleware\'s `terminate()` method?',
            'options' => [
                'Before the request reaches the route',
                'When the middleware throws an exception',
                'After the response has been sent to the browser',
                'Only when the app shuts down for maintenance',
            ],
            'correct_option' => 2,
            'explanation' => 'The user already has their page, so it\'s a good place for slow work that shouldn\'t hold the response up.',
        ],
        [
            'question' => 'What is the `web` middleware group?',
            'options' => [
                'Middleware for websites hosted on the web',
                'A name for a list of middleware (cookies, session, CSRF...) applied to every route in `routes/web.php`',
                'All middleware that output HTML',
                'A folder of middleware classes',
            ],
            'correct_option' => 1,
            'explanation' => 'Groups let many middleware be applied with one name. API routes get the `api` group instead.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Build the onion',
            'prompt' => 'Write `pipeline(array $pipes, Closure $destination): Closure`. Each pipe is a closure taking `($passable, Closure $next)`. The returned closure sends its argument through the pipes in order, the first pipe outermost, and finally to `$destination`, returning the result. With no pipes it just calls the destination.',
            'starter_code' => <<<'PHP'
                <?php

                function pipeline(array $pipes, Closure $destination): Closure
                {
                    return $destination;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pipeline(array $pipes, Closure $destination): Closure
                {
                    $next = $destination;
                    foreach (array_reverse($pipes) as $pipe) {
                        $next = fn ($passable) => $pipe($passable, $next);
                    }

                    return $next;
                }
                PHP,
            'hint' => 'Start with `$next = $destination` and wrap one pipe around it at a time, going through the pipes backwards so the first one ends up outermost.',
            'checks' => [
                ['label' => 'Pipes run in order, then the destination', 'code' => "\$run = pipeline([fn (\$t, \$n) => \$n(\$t . 'a'), fn (\$t, \$n) => \$n(\$t . 'b')], fn (\$t) => \$t . '!');\nreturn expect_same(\$run('>'), '>ab!');"],
                ['label' => 'Responses pass back out in reverse', 'code' => "\$run = pipeline([fn (\$t, \$n) => '1(' . \$n(\$t) . ')', fn (\$t, \$n) => '2(' . \$n(\$t) . ')'], fn (\$t) => \$t);\nreturn expect_same(\$run('x'), '1(2(x))');"],
                ['label' => 'A pipe can stop the request', 'code' => "\$reached = false;\n\$run = pipeline([fn (\$t, \$n) => 'stopped'], function (\$t) use (&\$reached) { \$reached = true; return \$t; });\nreturn expect_same([\$run('x'), \$reached], ['stopped', false]);"],
                ['label' => 'No pipes calls the destination', 'code' => 'return expect_same(pipeline([], fn ($t) => $t * 2)(21), 42);'],
            ],
        ],
        [
            'title' => 'send, through, then',
            'prompt' => 'Write a `Pipeline` class like Laravel\'s: `send($passable)` and `through(array $pipes)` return `$this`, and `then(Closure $destination)` runs everything and returns the result. A pipe may be a closure, or an object with a `handle($passable, Closure $next)` method.',
            'starter_code' => <<<'PHP'
                <?php

                class Pipeline
                {
                    public function send(mixed $passable): static
                    {
                        return $this;
                    }

                    public function through(array $pipes): static
                    {
                        return $this;
                    }

                    public function then(Closure $destination): mixed
                    {
                        return null;
                    }
                }

                class Uppercase
                {
                    public function handle(string $text, Closure $next): string
                    {
                        return $next(strtoupper($text));
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Pipeline
                {
                    private mixed $passable = null;
                    private array $pipes = [];

                    public function send(mixed $passable): static
                    {
                        $this->passable = $passable;
                        return $this;
                    }

                    public function through(array $pipes): static
                    {
                        $this->pipes = $pipes;
                        return $this;
                    }

                    public function then(Closure $destination): mixed
                    {
                        $onion = array_reduce(
                            array_reverse($this->pipes),
                            fn (Closure $next, $pipe) => fn ($passable) => $pipe instanceof Closure
                                ? $pipe($passable, $next)
                                : $pipe->handle($passable, $next),
                            $destination,
                        );

                        return $onion($this->passable);
                    }
                }

                class Uppercase
                {
                    public function handle(string $text, Closure $next): string
                    {
                        return $next(strtoupper($text));
                    }
                }
                PHP,
            'hint' => 'Store the passable and pipes in properties. In `then()`, build the onion with `array_reduce()` over the reversed pipes, calling either `$pipe($passable, $next)` or `$pipe->handle($passable, $next)`.',
            'checks' => [
                ['label' => 'Closures and objects both work', 'code' => "\$result = (new Pipeline())->send('dune')->through([new Uppercase(), fn (\$t, \$n) => \$n(\$t . '!')])->then(fn (\$t) => \"[\$t]\");\nreturn expect_same(\$result, '[DUNE!]');"],
                ['label' => 'send() and through() can be chained', 'code' => "\$p = new Pipeline();\nreturn \$p->send('x') === \$p && \$p->through([]) === \$p ?: 'send() and through() should return the pipeline.';"],
                ['label' => 'With no pipes the destination gets the passable', 'code' => 'return expect_same((new Pipeline())->send(20)->through([])->then(fn ($n) => $n + 1), 21);'],
                ['label' => 'Order is kept', 'code' => "\$result = (new Pipeline())->send(1)->through([fn (\$n, \$next) => \$next(\$n * 10), fn (\$n, \$next) => \$next(\$n + 1)])->then(fn (\$n) => \$n);\nreturn expect_same(\$result, 11);"],
            ],
        ],
        [
            'title' => 'Guests stop here',
            'prompt' => 'Write two middleware classes. `Authenticate::handle()` returns a `Response` with status 401 and body `Unauthenticated.` when the request has no user, and passes it on otherwise. `AddSecurityHeaders::handle()` passes the request on and then adds the header `X-Frame-Options: DENY` to whatever response comes back, including a 401.',
            'starter_code' => <<<'PHP'
                <?php

                class Request
                {
                    public function __construct(public string $path, public ?string $user = null) {}
                }

                class Response
                {
                    public array $headers = [];

                    public function __construct(public int $status, public string $body) {}
                }

                class Authenticate
                {
                    public function handle(Request $request, Closure $next): Response
                    {
                        return $next($request);
                    }
                }

                class AddSecurityHeaders
                {
                    public function handle(Request $request, Closure $next): Response
                    {
                        return $next($request);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Request
                {
                    public function __construct(public string $path, public ?string $user = null) {}
                }

                class Response
                {
                    public array $headers = [];

                    public function __construct(public int $status, public string $body) {}
                }

                class Authenticate
                {
                    public function handle(Request $request, Closure $next): Response
                    {
                        if ($request->user === null) {
                            return new Response(401, 'Unauthenticated.');
                        }

                        return $next($request);
                    }
                }

                class AddSecurityHeaders
                {
                    public function handle(Request $request, Closure $next): Response
                    {
                        $response = $next($request);
                        $response->headers['X-Frame-Options'] = 'DENY';

                        return $response;
                    }
                }
                PHP,
            'hint' => '`Authenticate` returns early before calling `$next`. `AddSecurityHeaders` calls `$next` first and changes the response it gets back.',
            'checks' => [
                ['label' => 'Guests get a 401 and the route never runs', 'code' => "\$ran = false;\n\$response = (new Authenticate())->handle(new Request('/basket'), function () use (&\$ran) { \$ran = true; return new Response(200, 'Basket'); });\nreturn expect_same([\$response->status, \$response->body, \$ran], [401, 'Unauthenticated.', false]);"],
                ['label' => 'Signed-in users get through', 'code' => "\$response = (new Authenticate())->handle(new Request('/basket', 'ada'), fn (\$r) => new Response(200, \"Basket for {\$r->user}\"));\nreturn expect_same([\$response->status, \$response->body], [200, 'Basket for ada']);"],
                ['label' => 'The header is added to the response', 'code' => "\$response = (new AddSecurityHeaders())->handle(new Request('/'), fn () => new Response(200, 'Home'));\nreturn expect_same(\$response->headers, ['X-Frame-Options' => 'DENY']);"],
                ['label' => 'Even a 401 gets the header', 'code' => "\$response = (new AddSecurityHeaders())->handle(new Request('/basket'), fn (\$r) => (new Authenticate())->handle(\$r, fn () => new Response(200, 'Basket')));\nreturn expect_same([\$response->status, \$response->headers], [401, ['X-Frame-Options' => 'DENY']]);"],
            ],
        ],
        [
            'title' => 'Middleware with parameters',
            'prompt' => 'Write `runMiddleware(array $names, array $aliases, mixed $request, Closure $route)`. Each name is an alias with optional parameters, such as `role:editor,admin`. Look the alias up in `$aliases` to get a class name, create it with `new`, and call `handle($request, $next, ...$parameters)`. Middleware run in the order listed, and the route is the centre.',
            'starter_code' => <<<'PHP'
                <?php

                class CheckRole
                {
                    public function handle(array $request, Closure $next, string ...$roles): string
                    {
                        return in_array($request['role'], $roles, true) ? $next($request) : 'Forbidden';
                    }
                }

                class Tag
                {
                    public function handle(array $request, Closure $next, string $label = 'plain'): string
                    {
                        return "<$label>" . $next($request);
                    }
                }

                function runMiddleware(array $names, array $aliases, mixed $request, Closure $route): mixed
                {
                    return $route($request);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class CheckRole
                {
                    public function handle(array $request, Closure $next, string ...$roles): string
                    {
                        return in_array($request['role'], $roles, true) ? $next($request) : 'Forbidden';
                    }
                }

                class Tag
                {
                    public function handle(array $request, Closure $next, string $label = 'plain'): string
                    {
                        return "<$label>" . $next($request);
                    }
                }

                function runMiddleware(array $names, array $aliases, mixed $request, Closure $route): mixed
                {
                    $next = $route;
                    foreach (array_reverse($names) as $name) {
                        [$alias, $list] = array_pad(explode(':', $name, 2), 2, '');
                        $parameters = $list === '' ? [] : explode(',', $list);
                        $middleware = new $aliases[$alias]();
                        $next = fn ($request) => $middleware->handle($request, $next, ...$parameters);
                    }

                    return $next($request);
                }
                PHP,
            'hint' => 'Split each name once at `:` (`explode(\':\', $name, 2)`), then split the rest at commas. Build the onion from the last name to the first, spreading the parameters after `$next`.',
            'checks' => [
                ['label' => 'Parameters reach handle()', 'code' => "\$aliases = ['role' => 'CheckRole'];\n\$route = fn () => 'Edit page';\nreturn expect_same([runMiddleware(['role:editor,admin'], \$aliases, ['role' => 'admin'], \$route), runMiddleware(['role:editor,admin'], \$aliases, ['role' => 'reader'], \$route)], ['Edit page', 'Forbidden']);"],
                ['label' => 'Names without parameters work', 'code' => "return expect_same(runMiddleware(['tag'], ['tag' => 'Tag'], [], fn () => 'x'), '<plain>x');"],
                ['label' => 'Middleware run in the order listed', 'code' => "return expect_same(runMiddleware(['tag:a', 'tag:b'], ['tag' => 'Tag'], [], fn () => 'x'), '<a><b>x');"],
                ['label' => 'A refused request stops early', 'code' => "return expect_same(runMiddleware(['tag:outer', 'role:admin', 'tag:inner'], ['tag' => 'Tag', 'role' => 'CheckRole'], ['role' => 'reader'], fn () => 'x'), '<outer>Forbidden');"],
            ],
        ],
        [
            'title' => 'After the response',
            'prompt' => 'Finish `Kernel`. `handle($request)` sends the request through `$this->middleware` (objects with `handle()`) to the router and returns the response. `terminate($request, $response)` then calls `terminate($request, $response)` on every middleware that has such a method, in the order listed. Middleware without one are skipped.',
            'starter_code' => <<<'PHP'
                <?php

                class Kernel
                {
                    public function __construct(private array $middleware, private Closure $router) {}

                    public function handle(string $request): string
                    {
                        return ($this->router)($request);
                    }

                    public function terminate(string $request, string $response): void
                    {
                    }
                }

                class LogRequests
                {
                    public array $logged = [];

                    public function handle(string $request, Closure $next): string
                    {
                        return $next($request);
                    }

                    public function terminate(string $request, string $response): void
                    {
                        $this->logged[] = "$request -> $response";
                    }
                }

                class Shout
                {
                    public function handle(string $request, Closure $next): string
                    {
                        return strtoupper($next($request));
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Kernel
                {
                    public function __construct(private array $middleware, private Closure $router) {}

                    public function handle(string $request): string
                    {
                        $next = $this->router;
                        foreach (array_reverse($this->middleware) as $middleware) {
                            $next = fn ($request) => $middleware->handle($request, $next);
                        }

                        return $next($request);
                    }

                    public function terminate(string $request, string $response): void
                    {
                        foreach ($this->middleware as $middleware) {
                            if (method_exists($middleware, 'terminate')) {
                                $middleware->terminate($request, $response);
                            }
                        }
                    }
                }

                class LogRequests
                {
                    public array $logged = [];

                    public function handle(string $request, Closure $next): string
                    {
                        return $next($request);
                    }

                    public function terminate(string $request, string $response): void
                    {
                        $this->logged[] = "$request -> $response";
                    }
                }

                class Shout
                {
                    public function handle(string $request, Closure $next): string
                    {
                        return strtoupper($next($request));
                    }
                }
                PHP,
            'hint' => '`handle()` builds the usual onion around `$this->router`. `terminate()` loops over the middleware and uses `method_exists($middleware, \'terminate\')`.',
            'checks' => [
                ['label' => 'Requests go through the middleware', 'code' => "\$kernel = new Kernel([new Shout()], fn (\$r) => \"page \$r\");\nreturn expect_same(\$kernel->handle('/books'), 'PAGE /BOOKS');"],
                ['label' => 'terminate() reaches terminable middleware', 'code' => "\$log = new LogRequests();\n\$kernel = new Kernel([new Shout(), \$log], fn (\$r) => 'ok');\n\$response = \$kernel->handle('/books');\n\$kernel->terminate('/books', \$response);\nreturn expect_same(\$log->logged, ['/books -> OK']);"],
                ['label' => 'Nothing is terminated during handle()', 'code' => "\$log = new LogRequests();\n(new Kernel([\$log], fn (\$r) => 'ok'))->handle('/');\nreturn expect_same(\$log->logged, []);"],
                ['label' => 'Each terminable middleware runs once, in order', 'code' => "\$order = new ArrayObject();\n\$make = fn (string \$name) => new class(\$name, \$order) { public function __construct(private string \$name, private ArrayObject \$order) {} public function handle(\$r, \$n) { return \$n(\$r); } public function terminate(\$r, \$s): void { \$this->order[] = \$this->name; } };\n\$kernel = new Kernel([\$make('first'), new Shout(), \$make('second')], fn (\$r) => 'ok');\n\$kernel->terminate('/', \$kernel->handle('/'));\nreturn expect_same(\$order->getArrayCopy(), ['first', 'second']);"],
            ],
        ],
        [
            'title' => 'Middleware groups',
            'prompt' => 'Write `expandMiddleware(array $names, array $groups): array`. Any name that is a key of `$groups` is replaced by that group\'s middleware, in place; groups may contain other groups. Duplicates are removed, keeping the first occurrence, and the result is a plain list.',
            'starter_code' => <<<'PHP'
                <?php

                function expandMiddleware(array $names, array $groups): array
                {
                    return $names;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function expandMiddleware(array $names, array $groups): array
                {
                    $expanded = [];
                    foreach ($names as $name) {
                        $items = isset($groups[$name]) ? expandMiddleware($groups[$name], $groups) : [$name];
                        foreach ($items as $item) {
                            if (! in_array($item, $expanded, true)) {
                                $expanded[] = $item;
                            }
                        }
                    }

                    return $expanded;
                }
                PHP,
            'hint' => 'Loop over the names. For a group, call `expandMiddleware()` on its contents (that handles nested groups); otherwise use the name itself. Only add items that aren\'t already in the result.',
            'checks' => [
                ['label' => 'Groups are expanded in place', 'code' => "return expect_same(expandMiddleware(['log', 'web', 'auth'], ['web' => ['cookies', 'session', 'csrf']]), ['log', 'cookies', 'session', 'csrf', 'auth']);"],
                ['label' => 'Groups can contain groups', 'code' => "return expect_same(expandMiddleware(['admin'], ['web' => ['cookies', 'session'], 'admin' => ['web', 'auth', 'role:admin']]), ['cookies', 'session', 'auth', 'role:admin']);"],
                ['label' => 'Duplicates keep their first place', 'code' => "return expect_same(expandMiddleware(['session', 'web', 'auth', 'session'], ['web' => ['cookies', 'session']]), ['session', 'cookies', 'auth']);"],
                ['label' => 'No groups, no change', 'code' => "return expect_same(expandMiddleware(['a', 'b'], []), ['a', 'b']);"],
            ],
        ],
    ],
];
