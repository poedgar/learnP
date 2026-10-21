<?php

// Lesson 1 of the REST APIs course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'resources-and-verbs',
    'title' => 'Resources, verbs and status codes',
    'summary' => 'What a REST API is: resources as URLs, HTTP methods, safe and idempotent requests, choosing status codes, and apiResource routes in Laravel.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add the PATCH and DELETE routes.
        $routes = [
            ['GET', '/api/books', 'index'],
            ['POST', '/api/books', 'store'],
            ['GET', '/api/books/{id}', 'show'],
        ];

        foreach ($routes as [$method, $path, $action]) {
            echo str_pad($method, 7) . str_pad($path, 18) . $action . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Which is the most RESTful way to create a book?',
            'options' => ['`GET /api/createBook?title=Dune`', '`POST /api/books`', '`POST /api/books/create`', '`PUT /api/newBook`'],
            'correct_option' => 1,
            'explanation' => 'The URL names the collection; the method says what to do with it.',
        ],
        [
            'question' => 'Which method changes only some fields of a resource?',
            'options' => ['`PUT`', '`PATCH`', '`POST`', '`GET`'],
            'correct_option' => 1,
            'explanation' => '`PUT` replaces the whole resource with what you send.',
        ],
        [
            'question' => 'Why may a client safely retry a `PUT` after a timeout, but not a `POST`?',
            'options' => [
                '`PUT` is faster',
                '`PUT` is idempotent: repeating it gives the same result, while repeating a `POST` can create a second resource',
                '`POST` is not allowed twice',
                'They are both safe to retry',
            ],
            'correct_option' => 1,
            'explanation' => 'Setting a book to the same values twice leaves one book with those values.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $safe = ['GET', 'HEAD', 'OPTIONS'];
                $idempotent = [...$safe, 'PUT', 'DELETE'];

                foreach (['GET', 'DELETE', 'POST'] as $method) {
                    echo $method . ':' . (in_array($method, $safe) ? 'S' : '') . (in_array($method, $idempotent) ? 'I' : '') . ' ';
                }
                PHP,
            'options' => ['`GET:SI DELETE:I POST: `', '`GET:S DELETE:SI POST:I `', '`GET:SI DELETE:SI POST: `', '`GET:I DELETE:I POST:I `'],
            'correct_option' => 0,
            'explanation' => 'Every safe method is idempotent, DELETE is idempotent but not safe, and POST is neither.',
        ],
        [
            'question' => 'A `POST /api/books` created book 42. What should the response be?',
            'options' => [
                '`200` with the book',
                '`201` with the book and `Location: /api/books/42`',
                '`204` with no body',
                '`302` to the new book',
            ],
            'correct_option' => 1,
            'explanation' => '201 says something new exists, and `Location` says where.',
        ],
        [
            'question' => 'A request has a valid token, but the user may not delete this book. Which status?',
            'options' => ['`401`', '`403`', '`404`', '`422`'],
            'correct_option' => 1,
            'explanation' => '401 means "we don\'t know who you are"; 403 means "we know, and no".',
        ],
        [
            'question' => 'What does `PATCH /api/authors` (on the collection) usually get back?',
            'options' => ['`404 Not Found`', '`405 Method Not Allowed`, with an `Allow` header', '`200 OK`', '`500 Internal Server Error`'],
            'correct_option' => 1,
            'explanation' => 'The URL exists, but not with that method. `Allow: GET, HEAD, POST` tells the client what does work.',
        ],
        [
            'question' => 'What does `Route::apiResource(\'books\', BookController::class)` register?',
            'options' => [
                'Seven routes including create and edit forms',
                'Five routes: index, store, show, update and destroy',
                'One route to the controller',
                'Only GET routes',
            ],
            'correct_option' => 1,
            'explanation' => 'APIs don\'t need the HTML form pages, so `create` and `edit` are left out.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Which action',
            'prompt' => 'Write `apiAction(string $method, string $path): ?string` for the routes `Route::apiResource(\'books\', ...)` makes. Return `index`, `store`, `show`, `update` (for PUT or PATCH) or `destroy` for `/api/books` and `/api/books/ID` (ID is one or more digits), or `null` for anything else. Methods are case-insensitive.',
            'starter_code' => <<<'PHP'
                <?php

                function apiAction(string $method, string $path): ?string
                {
                    if ($path === '/api/books') {
                        return $method === 'GET' ? 'index' : 'store';
                    }

                    return 'show';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function apiAction(string $method, string $path): ?string
                {
                    $method = strtoupper($method);

                    if ($path === '/api/books') {
                        return match ($method) {
                            'GET' => 'index',
                            'POST' => 'store',
                            default => null,
                        };
                    }

                    if (preg_match('#^/api/books/\d+$#', $path) === 1) {
                        return match ($method) {
                            'GET' => 'show',
                            'PUT', 'PATCH' => 'update',
                            'DELETE' => 'destroy',
                            default => null,
                        };
                    }

                    return null;
                }
                PHP,
            'hint' => 'Two cases, the collection and one item (`#^/api/books/\d+$#`), each with a `match` on the upper-cased method.',
            'checks' => [
                ['label' => 'The collection', 'code' => "return expect_same([apiAction('GET', '/api/books'), apiAction('post', '/api/books'), apiAction('DELETE', '/api/books')], ['index', 'store', null]);"],
                ['label' => 'One book', 'code' => "return expect_same([apiAction('GET', '/api/books/7'), apiAction('PUT', '/api/books/7'), apiAction('patch', '/api/books/12'), apiAction('DELETE', '/api/books/7'), apiAction('POST', '/api/books/7')], ['show', 'update', 'update', 'destroy', null]);"],
                ['label' => 'Other paths', 'code' => "return expect_same([apiAction('GET', '/api/books/dune'), apiAction('GET', '/api/books/7/x'), apiAction('GET', '/api/authors')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Safe to retry',
            'prompt' => 'Write `methodProperties(string $method): array` returning `[\'safe\' => bool, \'idempotent\' => bool]` for GET, HEAD, OPTIONS, POST, PUT, PATCH and DELETE (any case). Treat PATCH as not idempotent. For any other method, throw an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function methodProperties(string $method): array
                {
                    return ['safe' => $method === 'GET', 'idempotent' => $method !== 'POST'];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function methodProperties(string $method): array
                {
                    $method = strtoupper($method);
                    $safe = ['GET', 'HEAD', 'OPTIONS'];
                    $idempotent = [...$safe, 'PUT', 'DELETE'];

                    if (! in_array($method, [...$idempotent, 'POST', 'PATCH'], true)) {
                        throw new InvalidArgumentException("Unknown method {$method}.");
                    }

                    return [
                        'safe' => in_array($method, $safe, true),
                        'idempotent' => in_array($method, $idempotent, true),
                    ];
                }
                PHP,
            'hint' => 'Two lists: the safe methods, and the idempotent ones (the safe ones plus PUT and DELETE).',
            'checks' => [
                ['label' => 'Each method', 'code' => <<<'PHP'
                    $result = [];
                    foreach (['GET', 'head', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'delete'] as $m) {
                        $p = methodProperties($m);
                        $result[] = ($p['safe'] ? 'S' : '-') . ($p['idempotent'] ? 'I' : '-');
                    }
                    return expect_same($result, ['SI', 'SI', 'SI', '--', '-I', '--', '-I']);
                    PHP],
                ['label' => 'Unknown methods', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => methodProperties('BREW'));"],
            ],
        ],
        [
            'title' => 'Not found, or not allowed',
            'prompt' => 'Routes are `[method, pattern]` pairs where `{id}` stands for digits, like `[\'GET\', \'/api/books/{id}\']`. Write `routeStatus(array $routes, string $method, string $path): array`. If a route matches both method and path, return `[\'status\' => 200]`. If the path matches but not with this method, return `[\'status\' => 405, \'allow\' => \'GET, PUT\']` (the allowed methods in the order the routes list them, joined by a comma and a space). If no path matches, return `[\'status\' => 404]`.',
            'starter_code' => <<<'PHP'
                <?php

                function routeStatus(array $routes, string $method, string $path): array
                {
                    foreach ($routes as [$routeMethod, $pattern]) {
                        if ($routeMethod === $method && $pattern === $path) {
                            return ['status' => 200];
                        }
                    }

                    return ['status' => 404];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function routeStatus(array $routes, string $method, string $path): array
                {
                    $allowed = [];
                    foreach ($routes as [$routeMethod, $pattern]) {
                        $regex = '#^' . str_replace('\{id\}', '\d+', preg_quote($pattern, '#')) . '$#';
                        if (preg_match($regex, $path) !== 1) {
                            continue;
                        }
                        if ($routeMethod === $method) {
                            return ['status' => 200];
                        }
                        $allowed[] = $routeMethod;
                    }

                    return $allowed === [] ? ['status' => 404] : ['status' => 405, 'allow' => implode(', ', $allowed)];
                }
                PHP,
            'hint' => 'Turn each pattern into a regex (`preg_quote()`, then `\{id\}` becomes `\d+`). Collect the methods of routes whose path matches.',
            'checks' => [
                ['label' => 'A match', 'code' => <<<'PHP'
                    $routes = [['GET', '/api/books'], ['POST', '/api/books'], ['GET', '/api/books/{id}'], ['PUT', '/api/books/{id}'], ['DELETE', '/api/books/{id}']];
                    return expect_same([routeStatus($routes, 'GET', '/api/books/7'), routeStatus($routes, 'POST', '/api/books')], [['status' => 200], ['status' => 200]]);
                    PHP],
                ['label' => '405 with the allowed methods', 'code' => <<<'PHP'
                    $routes = [['GET', '/api/books'], ['POST', '/api/books'], ['GET', '/api/books/{id}'], ['PUT', '/api/books/{id}'], ['DELETE', '/api/books/{id}']];
                    return expect_same([routeStatus($routes, 'PATCH', '/api/books'), routeStatus($routes, 'POST', '/api/books/7')], [['status' => 405, 'allow' => 'GET, POST'], ['status' => 405, 'allow' => 'GET, PUT, DELETE']]);
                    PHP],
                ['label' => '404', 'code' => <<<'PHP'
                    $routes = [['GET', '/api/books'], ['GET', '/api/books/{id}']];
                    return expect_same([routeStatus($routes, 'GET', '/api/authors'), routeStatus($routes, 'GET', '/api/books/dune'), routeStatus($routes, 'GET', '/api/books.json')], [['status' => 404], ['status' => 404], ['status' => 404]]);
                    PHP],
            ],
        ],
        [
            'title' => 'Read the URL',
            'prompt' => 'Write `parseResourcePath(string $path): ?array` for paths like `/authors/3/books/7`. Segments alternate between a resource name (lowercase letters and hyphens) and an optional id (digits). Return a list of `[\'resource\' => ..., \'id\' => int|null]`, the last id being optional. Return `null` if the path doesn\'t fit, for example `/authors/books` or `/3/books`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseResourcePath(string $path): ?array
                {
                    $parts = explode('/', trim($path, '/'));

                    return [['resource' => $parts[0], 'id' => (int) ($parts[1] ?? 0)]];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseResourcePath(string $path): ?array
                {
                    $segments = explode('/', trim($path, '/'));
                    if ($segments === ['']) {
                        return null;
                    }

                    $result = [];
                    foreach (array_chunk($segments, 2) as $pair) {
                        [$name, $id] = $pair + [1 => null];
                        if (preg_match('/^[a-z]+(-[a-z]+)*$/', $name) !== 1) {
                            return null;
                        }
                        if ($id !== null && preg_match('/^\d+$/', $id) !== 1) {
                            return null;
                        }
                        $result[] = ['resource' => $name, 'id' => $id === null ? null : (int) $id];
                    }

                    return $result;
                }
                PHP,
            'hint' => '`array_chunk($segments, 2)` gives name/id pairs; only the last pair may lack its id.',
            'checks' => [
                ['label' => 'Nested resources', 'code' => "return expect_same([parseResourcePath('/authors/3/books/7'), parseResourcePath('/authors/3/books')], [[['resource' => 'authors', 'id' => 3], ['resource' => 'books', 'id' => 7]], [['resource' => 'authors', 'id' => 3], ['resource' => 'books', 'id' => null]]]);"],
                ['label' => 'One level', 'code' => "return expect_same([parseResourcePath('/order-items'), parseResourcePath('/books/12/')], [[['resource' => 'order-items', 'id' => null]], [['resource' => 'books', 'id' => 12]]]);"],
                ['label' => 'Paths that don\'t fit', 'code' => "return expect_same([parseResourcePath('/authors/books'), parseResourcePath('/3/books'), parseResourcePath('/Books/1'), parseResourcePath('/'), parseResourcePath('/books/1x')], [null, null, null, null, null]);"],
            ],
        ],
        [
            'title' => 'Store a book',
            'prompt' => 'Write `storeBook(array $books, array $input): array`, returning a response `[\'status\' => ..., \'headers\' => [...], \'body\' => [...]]`. If `title` is missing or blank, answer `422` with body `[\'errors\' => [\'title\' => [\'The title field is required.\']]]`. If a book in `$books` already has the same `isbn`, answer `409` with body `[\'message\' => \'A book with this ISBN already exists.\']`. Otherwise answer `201` with header `Location: /api/books/ID` and the new book as the body, with `id` one more than the highest existing id (1 if there are none) followed by the input. Headers are `[]` when there are none.',
            'starter_code' => <<<'PHP'
                <?php

                function storeBook(array $books, array $input): array
                {
                    $book = ['id' => count($books) + 1] + $input;

                    return ['status' => 200, 'headers' => [], 'body' => $book];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function storeBook(array $books, array $input): array
                {
                    if (trim((string) ($input['title'] ?? '')) === '') {
                        return ['status' => 422, 'headers' => [], 'body' => ['errors' => ['title' => ['The title field is required.']]]];
                    }

                    foreach ($books as $book) {
                        if (isset($input['isbn']) && ($book['isbn'] ?? null) === $input['isbn']) {
                            return ['status' => 409, 'headers' => [], 'body' => ['message' => 'A book with this ISBN already exists.']];
                        }
                    }

                    $id = $books === [] ? 1 : max(array_column($books, 'id')) + 1;
                    $book = ['id' => $id] + $input;

                    return ['status' => 201, 'headers' => ['Location' => "/api/books/{$id}"], 'body' => $book];
                }
                PHP,
            'hint' => 'Validation first, then the conflict, then create. `max(array_column($books, \'id\'))` finds the highest id.',
            'checks' => [
                ['label' => '201 with Location', 'code' => <<<'PHP'
                    $books = [['id' => 3, 'title' => 'Dune', 'isbn' => '111'], ['id' => 8, 'title' => 'Emma', 'isbn' => '222']];
                    return expect_same(storeBook($books, ['title' => 'Ulysses', 'isbn' => '333']), ['status' => 201, 'headers' => ['Location' => '/api/books/9'], 'body' => ['id' => 9, 'title' => 'Ulysses', 'isbn' => '333']]);
                    PHP],
                ['label' => 'The first book', 'code' => "return expect_same(storeBook([], ['title' => 'Dune'])['headers'], ['Location' => '/api/books/1']);"],
                ['label' => '422 and 409', 'code' => <<<'PHP'
                    $books = [['id' => 1, 'title' => 'Dune', 'isbn' => '111']];
                    $invalid = storeBook($books, ['title' => '  ', 'isbn' => '111']);
                    $duplicate = storeBook($books, ['title' => 'Dune again', 'isbn' => '111']);
                    return expect_same([$invalid['status'], $invalid['body'], $duplicate['status'], $duplicate['body']], [422, ['errors' => ['title' => ['The title field is required.']]], 409, ['message' => 'A book with this ISBN already exists.']]);
                    PHP],
            ],
        ],
        [
            'title' => 'PUT twice',
            'prompt' => 'Write `putBook(array $books, int $id, array $input): array` for `PUT /api/books/{id}`, where `$books` maps id => book. PUT **replaces** the whole book with `[\'id\' => $id] + $input` (fields not sent are gone), creating it if it doesn\'t exist. Return `[\'books\' => the new books array, \'status\' => 201 if it was created, 200 if replaced]`. Sending the same PUT twice must leave the same books.',
            'starter_code' => <<<'PHP'
                <?php

                function putBook(array $books, int $id, array $input): array
                {
                    $books[$id] = array_merge($books[$id] ?? [], $input);

                    return ['books' => $books, 'status' => 200];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function putBook(array $books, int $id, array $input): array
                {
                    $existed = array_key_exists($id, $books);
                    $books[$id] = ['id' => $id] + $input;

                    return ['books' => $books, 'status' => $existed ? 200 : 201];
                }
                PHP,
            'hint' => 'Don\'t merge with the old book: a PUT replaces it. Note whether it existed before writing.',
            'checks' => [
                ['label' => 'Replaces the whole book', 'code' => <<<'PHP'
                    $books = [7 => ['id' => 7, 'title' => 'Dnue', 'price' => 999, 'subtitle' => 'Old']];
                    return expect_same(putBook($books, 7, ['title' => 'Dune', 'price' => 999]), ['books' => [7 => ['id' => 7, 'title' => 'Dune', 'price' => 999]], 'status' => 200]);
                    PHP],
                ['label' => 'Creates a missing book', 'code' => "return expect_same(putBook([], 9, ['title' => 'Emma']), ['books' => [9 => ['id' => 9, 'title' => 'Emma']], 'status' => 201]);"],
                ['label' => 'Idempotent', 'code' => <<<'PHP'
                    $once = putBook([1 => ['id' => 1, 'title' => 'A']], 2, ['title' => 'B']);
                    $twice = putBook($once['books'], 2, ['title' => 'B']);
                    return expect_same([$twice['books'], $twice['status']], [$once['books'], 200]);
                    PHP],
            ],
        ],
    ],
];
