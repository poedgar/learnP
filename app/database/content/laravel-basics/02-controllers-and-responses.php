<?php

// Lesson 2 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'controllers-and-responses',
    'title' => 'Controllers and responses',
    'summary' => 'Organising actions in controllers: calling controller methods, resource controllers and their seven routes, invokable controllers, status codes, redirects with flash messages, `abort()` and JSON or HTML.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a destroy() action.
        class BookController
        {
            private array $books = [7 => 'Dune', 8 => 'Piranesi'];

            public function index(): string
            {
                return 'All books: ' . implode(', ', $this->books);
            }

            public function show(int $id): string
            {
                return $this->books[$id] ?? '404 Not Found';
            }
        }

        $controller = new BookController();
        echo $controller->index() . PHP_EOL;
        echo $controller->show(7) . PHP_EOL;
        echo $controller->show(99);
        PHP,

    'quiz' => [
        [
            'question' => 'Where do controllers live in a Laravel app?',
            'options' => ['`routes/controllers`', '`app/Http/Controllers`', '`resources/controllers`', '`public/controllers`'],
            'correct_option' => 1,
            'explanation' => '`php artisan make:controller BookController` creates `app/Http/Controllers/BookController.php`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class ReviewController
                {
                    public function show(string $id): string
                    {
                        return "Review $id";
                    }
                }

                [$class, $method] = [ReviewController::class, 'show'];

                echo (new $class())->$method('42');
                PHP,
            'options' => ['`Review 42`', '`ReviewController show`', '`Review $id`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'A class name and a method name in variables are enough to call it; that\'s what Laravel does with `[BookController::class, \'show\']`.',
        ],
        [
            'question' => 'With `Route::resource(\'books\', BookController::class)`, which action handles `POST /books`?',
            'options' => ['`create`', '`store`', '`update`', '`index`'],
            'correct_option' => 1,
            'explanation' => '`create` shows the empty form (GET), and `store` saves what it sends (POST).',
        ],
        [
            'question' => 'Which action and name does `DELETE /books/7` get in a resource controller?',
            'options' => ['`delete`, `books.delete`', '`destroy`, `books.destroy`', '`remove`, `books.remove`', '`update`, `books.update`'],
            'correct_option' => 1,
            'explanation' => 'Laravel calls it `destroy`.',
        ],
        [
            'question' => 'Why does an app redirect after saving a form instead of showing a page straight away?',
            'options' => [
                'Redirects are faster',
                'So refreshing the next page doesn\'t send the form again',
                'Browsers require it',
                'To clear the session',
            ],
            'correct_option' => 1,
            'explanation' => 'This is called Post/Redirect/Get. The page after the redirect is a plain GET that is safe to refresh.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $session = ['_flash' => ['status' => 'Book saved.']];

                $first = $session['_flash']['status'] ?? 'nothing';
                unset($session['_flash']['status']);
                $second = $session['_flash']['status'] ?? 'nothing';

                echo "$first / $second";
                PHP,
            'options' => ['`Book saved. / Book saved.`', '`Book saved. / nothing`', '`nothing / nothing`', '`nothing / Book saved.`'],
            'correct_option' => 1,
            'explanation' => 'A flash message is shown once and then removed.',
        ],
        [
            'question' => 'Which status code means "the input you sent was invalid"?',
            'options' => ['`200`', '`302`', '`404`', '`422`'],
            'correct_option' => 3,
            'explanation' => '422 Unprocessable Content is what Laravel returns to JSON clients when validation fails.',
        ],
        [
            'question' => 'What does `$request->wantsJson()` look at?',
            'options' => [
                'The URL\'s extension',
                'The `Accept` header, which says whether the client wants `application/json`',
                'Whether the user is logged in',
                'The route\'s name',
            ],
            'correct_option' => 1,
            'explanation' => 'JavaScript clients send `Accept: application/json`; browsers ask for HTML.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Seven resource routes',
            'prompt' => 'Write `resourceRoutes(string $name): array`, returning the seven routes `Route::resource()` registers, in the order of the table in the lesson, each as `[method, path, action, route name]`. The parameter is the resource name without its last `s` (`books` → `{book}`). For update, use `PUT`.',
            'starter_code' => <<<'PHP'
                <?php

                function resourceRoutes(string $name): array
                {
                    return [
                        ['GET', "/$name", 'index', "$name.index"],
                    ];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function resourceRoutes(string $name): array
                {
                    $parameter = '{' . rtrim($name, 's') . '}';

                    return [
                        ['GET', "/$name", 'index', "$name.index"],
                        ['GET', "/$name/create", 'create', "$name.create"],
                        ['POST', "/$name", 'store', "$name.store"],
                        ['GET', "/$name/$parameter", 'show', "$name.show"],
                        ['GET', "/$name/$parameter/edit", 'edit', "$name.edit"],
                        ['PUT', "/$name/$parameter", 'update', "$name.update"],
                        ['DELETE', "/$name/$parameter", 'destroy', "$name.destroy"],
                    ];
                }
                PHP,
            'hint' => 'Work out the parameter once, like `{book}`, then list the seven routes from the table.',
            'checks' => [
                ['label' => 'Seven routes for books', 'code' => "return expect_same(resourceRoutes('books'), [['GET', '/books', 'index', 'books.index'], ['GET', '/books/create', 'create', 'books.create'], ['POST', '/books', 'store', 'books.store'], ['GET', '/books/{book}', 'show', 'books.show'], ['GET', '/books/{book}/edit', 'edit', 'books.edit'], ['PUT', '/books/{book}', 'update', 'books.update'], ['DELETE', '/books/{book}', 'destroy', 'books.destroy']]);"],
                ['label' => 'Other resources', 'code' => "return expect_same(resourceRoutes('reviews')[3], ['GET', '/reviews/{review}', 'show', 'reviews.show']);"],
            ],
        ],
        [
            'title' => 'Call the action',
            'prompt' => 'Write `callAction(array|string $action, array $parameters = []): mixed`. An array like `[BookController::class, \'show\']` creates the controller and calls that method; a string is an invokable controller\'s class name, so create it and call it like a function. The parameters are passed in order.',
            'starter_code' => <<<'PHP'
                <?php

                class BookController
                {
                    public function show(string $id): string
                    {
                        return "Book $id";
                    }
                }

                class ShowBestsellers
                {
                    public function __invoke(int $limit = 3): string
                    {
                        return "Top $limit bestsellers";
                    }
                }

                function callAction(array|string $action, array $parameters = []): mixed
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class BookController
                {
                    public function show(string $id): string
                    {
                        return "Book $id";
                    }
                }

                class ShowBestsellers
                {
                    public function __invoke(int $limit = 3): string
                    {
                        return "Top $limit bestsellers";
                    }
                }

                function callAction(array|string $action, array $parameters = []): mixed
                {
                    if (is_string($action)) {
                        return (new $action())(...$parameters);
                    }

                    [$class, $method] = $action;

                    return (new $class())->$method(...$parameters);
                }
                PHP,
            'hint' => 'An object with `__invoke()` can be called like a function: `(new $class())(...$parameters)`.',
            'checks' => [
                ['label' => 'Controller methods', 'code' => "return expect_same(callAction([BookController::class, 'show'], ['7']), 'Book 7');"],
                ['label' => 'Invokable controllers', 'code' => "return expect_same([callAction(ShowBestsellers::class), callAction(ShowBestsellers::class, [10])], ['Top 3 bestsellers', 'Top 10 bestsellers']);"],
            ],
        ],
        [
            'title' => 'abort()',
            'prompt' => 'Write `HttpException`, an exception with a public `int $status`, and `abort(int $status)`, which throws it with the message for that status: 403 `Forbidden`, 404 `Not Found`, 419 `Page Expired`, 500 `Server Error`, anything else `Error`. Then write `abortIf(bool $condition, int $status)`, which aborts only when the condition is true.',
            'starter_code' => <<<'PHP'
                <?php

                function abort(int $status): never
                {
                    throw new Exception('Error');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class HttpException extends RuntimeException
                {
                    public function __construct(public int $status, string $message)
                    {
                        parent::__construct($message);
                    }
                }

                function abort(int $status): never
                {
                    $message = match ($status) {
                        403 => 'Forbidden',
                        404 => 'Not Found',
                        419 => 'Page Expired',
                        500 => 'Server Error',
                        default => 'Error',
                    };

                    throw new HttpException($status, $message);
                }

                function abortIf(bool $condition, int $status): void
                {
                    if ($condition) {
                        abort($status);
                    }
                }
                PHP,
            'hint' => 'Give `HttpException` a constructor that takes the status and a message. A `match` picks the message.',
            'checks' => [
                ['label' => 'abort() throws with a status and message', 'code' => "if (! class_exists('HttpException', false)) return 'There is no HttpException class.';\ntry { abort(404); } catch (HttpException \$e) { return expect_same([\$e->status, \$e->getMessage()], [404, 'Not Found']); }\nreturn 'Nothing was thrown.';"],
                ['label' => 'Other messages', 'code' => "if (! class_exists('HttpException', false)) return 'There is no HttpException class.';\n\$messages = [];\nforeach ([403, 419, 500, 418] as \$status) { try { abort(\$status); } catch (HttpException \$e) { \$messages[] = \$e->getMessage(); } }\nreturn expect_same(\$messages, ['Forbidden', 'Page Expired', 'Server Error', 'Error']);"],
                ['label' => 'abortIf() only aborts when true', 'code' => "if (! function_exists('abortIf')) return 'There is no abortIf() function.';\nabortIf(false, 404);\nreturn expect_throws(HttpException::class, fn () => abortIf(true, 403));"],
            ],
        ],
        [
            'title' => 'Redirect with a message',
            'prompt' => 'Write `redirectWith(string $to, array &$session, string $key, string $message): array`, which flashes the message into `$session[\'_flash\'][$key]` and returns `[\'status\' => 302, \'headers\' => [\'Location\' => $to]]`. Then write `pullFlash(array &$session, string $key): ?string`, which returns a flashed message once, removing it, and `null` after that.',
            'starter_code' => <<<'PHP'
                <?php

                function redirectWith(string $to, array &$session, string $key, string $message): array
                {
                    return ['status' => 200, 'headers' => []];
                }

                function pullFlash(array &$session, string $key): ?string
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function redirectWith(string $to, array &$session, string $key, string $message): array
                {
                    $session['_flash'][$key] = $message;

                    return ['status' => 302, 'headers' => ['Location' => $to]];
                }

                function pullFlash(array &$session, string $key): ?string
                {
                    $message = $session['_flash'][$key] ?? null;
                    unset($session['_flash'][$key]);

                    return $message;
                }
                PHP,
            'hint' => 'Both functions take the session by reference (`&$session`) so they can change it.',
            'checks' => [
                ['label' => 'A 302 to the new page', 'code' => "\$session = [];\nreturn expect_same(redirectWith('/books/7', \$session, 'status', 'Book saved.'), ['status' => 302, 'headers' => ['Location' => '/books/7']]);"],
                ['label' => 'The message is flashed', 'code' => "\$session = [];\nredirectWith('/books/7', \$session, 'status', 'Book saved.');\nreturn expect_same(\$session, ['_flash' => ['status' => 'Book saved.']]);"],
                ['label' => 'Shown once only', 'code' => "\$session = ['_flash' => ['status' => 'Book saved.']];\nreturn expect_same([pullFlash(\$session, 'status'), pullFlash(\$session, 'status'), pullFlash(\$session, 'other')], ['Book saved.', null, null]);"],
            ],
        ],
        [
            'title' => 'A controller with responses',
            'prompt' => 'Finish `BookController`, constructed with books keyed by id. `index()` returns a JSON-style response `[\'status\' => 200, \'body\' => [...titles sorted A to Z]]`. `show(int $id)` returns `[\'status\' => 200, \'body\' => the book]`, or a 404 response `[\'status\' => 404, \'body\' => [\'message\' => \'Not Found\']]`. `store(array $input)` adds a book with the next id (one more than the highest, or 1) and returns status 201 with the new book, including its `id`.',
            'starter_code' => <<<'PHP'
                <?php

                class BookController
                {
                    public function __construct(public array $books = []) {}

                    public function index(): array
                    {
                        return ['status' => 200, 'body' => []];
                    }

                    public function show(int $id): array
                    {
                        return ['status' => 200, 'body' => $this->books[$id]];
                    }

                    public function store(array $input): array
                    {
                        return ['status' => 200, 'body' => $input];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class BookController
                {
                    public function __construct(public array $books = []) {}

                    public function index(): array
                    {
                        $titles = array_column($this->books, 'title');
                        sort($titles);

                        return ['status' => 200, 'body' => $titles];
                    }

                    public function show(int $id): array
                    {
                        if (! isset($this->books[$id])) {
                            return ['status' => 404, 'body' => ['message' => 'Not Found']];
                        }

                        return ['status' => 200, 'body' => $this->books[$id]];
                    }

                    public function store(array $input): array
                    {
                        $id = $this->books === [] ? 1 : max(array_keys($this->books)) + 1;
                        $book = ['id' => $id, ...$input];
                        $this->books[$id] = $book;

                        return ['status' => 201, 'body' => $book];
                    }
                }
                PHP,
            'hint' => '`array_column($this->books, \'title\')` and `sort()` give the titles. `max(array_keys($this->books)) + 1` is the next id.',
            'checks' => [
                ['label' => 'index() sorts titles', 'code' => "\$c = new BookController([7 => ['id' => 7, 'title' => 'Piranesi'], 8 => ['id' => 8, 'title' => 'Dune']]);\nreturn expect_same(\$c->index(), ['status' => 200, 'body' => ['Dune', 'Piranesi']]);"],
                ['label' => 'show() finds or 404s', 'code' => "\$c = new BookController([7 => ['id' => 7, 'title' => 'Piranesi']]);\nreturn expect_same([\$c->show(7), \$c->show(99)], [['status' => 200, 'body' => ['id' => 7, 'title' => 'Piranesi']], ['status' => 404, 'body' => ['message' => 'Not Found']]]);"],
                ['label' => 'store() creates with the next id', 'code' => "\$c = new BookController([7 => ['id' => 7, 'title' => 'Piranesi']]);\n\$response = \$c->store(['title' => 'Foundation']);\nreturn expect_same([\$response, \$c->show(8)['body']], [['status' => 201, 'body' => ['id' => 8, 'title' => 'Foundation']], ['id' => 8, 'title' => 'Foundation']]);"],
                ['label' => 'The first book gets id 1', 'code' => "return expect_same((new BookController())->store(['title' => 'Dune'])['body'], ['id' => 1, 'title' => 'Dune']);"],
            ],
        ],
        [
            'title' => 'HTML or JSON',
            'prompt' => 'Write `wantsJson(string $accept): bool`, true if the `Accept` header contains `application/json` or `+json` (any case). Then write `respond(array $titles, string $accept): array`: JSON clients get `[\'type\' => \'application/json\', \'body\' => json_encode($titles)]`, and others `[\'type\' => \'text/html\', \'body\' => \'<ul><li>Dune</li>...</ul>\']` with each title escaped with `htmlspecialchars()`.',
            'starter_code' => <<<'PHP'
                <?php

                function wantsJson(string $accept): bool
                {
                    return false;
                }

                function respond(array $titles, string $accept): array
                {
                    return ['type' => 'text/html', 'body' => implode(', ', $titles)];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function wantsJson(string $accept): bool
                {
                    $accept = strtolower($accept);

                    return str_contains($accept, 'application/json') || str_contains($accept, '+json');
                }

                function respond(array $titles, string $accept): array
                {
                    if (wantsJson($accept)) {
                        return ['type' => 'application/json', 'body' => json_encode($titles)];
                    }

                    $items = array_map(fn (string $title) => '<li>' . htmlspecialchars($title) . '</li>', $titles);

                    return ['type' => 'text/html', 'body' => '<ul>' . implode('', $items) . '</ul>'];
                }
                PHP,
            'hint' => 'Lower-case the header and use `str_contains()`. For HTML, map each title to an escaped `<li>` and join them.',
            'checks' => [
                ['label' => 'Spotting JSON clients', 'code' => "return expect_same([wantsJson('application/json'), wantsJson('Application/JSON, text/plain'), wantsJson('application/vnd.api+json'), wantsJson('text/html,application/xhtml+xml')], [true, true, true, false]);"],
                ['label' => 'JSON for JSON clients', 'code' => "return expect_same(respond(['Dune', 'Piranesi'], 'application/json'), ['type' => 'application/json', 'body' => '[\"Dune\",\"Piranesi\"]']);"],
                ['label' => 'Escaped HTML for browsers', 'code' => "return expect_same(respond(['Dune', 'Tom & Jerry <3'], 'text/html'), ['type' => 'text/html', 'body' => '<ul><li>Dune</li><li>Tom &amp; Jerry &lt;3</li></ul>']);"],
            ],
        ],
    ],
];
