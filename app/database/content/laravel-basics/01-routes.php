<?php

// Lesson 1 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'routes',
    'title' => 'Routes',
    'summary' => 'How requests find your code: HTTP methods and paths, a route table and 404s, route parameters, the query string, JSON responses, named routes and groups.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a route for GET /about.
        $routes = [
            'GET /' => fn () => 'Welcome to Corner Books',
            'GET /books' => fn () => 'Dune, Piranesi, Foundation',
            'POST /newsletter' => fn () => 'Thanks for subscribing!',
        ];

        $request = 'GET /books';

        echo isset($routes[$request]) ? $routes[$request]() : '404 Not Found';
        PHP,

    'quiz' => [
        [
            'question' => 'Which file holds the routes for your web pages in a Laravel app?',
            'options' => ['`app/routes.php`', '`routes/web.php`', '`config/routes.php`', '`public/index.php`'],
            'correct_option' => 1,
            'explanation' => 'Web routes go in `routes/web.php`; API routes, if you have them, in `routes/api.php`.',
        ],
        [
            'question' => 'Which HTTP method should a form that creates something use?',
            'options' => ['`GET`', '`POST`', '`HEAD`', 'Any method works the same'],
            'correct_option' => 1,
            'explanation' => '`GET` is for reading and must not change anything; sending a form that creates data is a `POST`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $routes = [
                    'GET /newsletter' => fn () => 'Sign up below',
                    'POST /newsletter' => fn () => 'Thanks!',
                ];

                $request = 'POST /newsletter';
                echo isset($routes[$request]) ? $routes[$request]() : '404';
                PHP,
            'options' => ['`Sign up below`', '`Thanks!`', '`404`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The method is part of the key, so the `POST` route is the one that runs.',
        ],
        [
            'question' => 'A route is `Route::get(\'/books/{id}\', ...)`. Which path matches it?',
            'options' => ['`/books`', '`/books/7`', '`/books/7/reviews`', '`/book/7`'],
            'correct_option' => 1,
            'explanation' => '`{id}` stands for exactly one segment. `/books` is missing it and `/books/7/reviews` has an extra one.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $route = explode('/', '/authors/{author}/books/{book}');
                $path = explode('/', '/authors/herbert/books/dune');

                $values = [];
                foreach ($route as $i => $segment) {
                    if (str_starts_with($segment, '{')) {
                        $values[] = trim($segment, '{}') . '=' . $path[$i];
                    }
                }

                echo implode(' ', $values);
                PHP,
            'options' => ['`herbert dune`', '`author=herbert book=dune`', '`{author}=herbert {book}=dune`', '`authors=herbert books=dune`'],
            'correct_option' => 1,
            'explanation' => 'Wherever the route has a `{name}` segment, the path\'s segment in the same place is its value.',
        ],
        [
            'question' => 'What does a Laravel route closure that returns an array send back?',
            'options' => ['An error', 'The array as JSON', 'The word "Array"', 'An HTML table'],
            'correct_option' => 1,
            'explanation' => 'Laravel turns arrays (and Eloquent models) into a JSON response automatically.',
        ],
        [
            'question' => 'What does `$request->query(\'page\', 1)` return for `/books?page=3`?',
            'options' => ['`1`', '`\'3\'`', '`\'page\'`', '`null`'],
            'correct_option' => 1,
            'explanation' => 'Query string values arrive as strings; the second argument is only used when `page` is missing. `$request->integer()` converts it.',
        ],
        [
            'question' => 'Why give a route a name like `books.show`?',
            'options' => [
                'Names make routes faster',
                'So links can be built with `route(\'books.show\', ...)`, and changing the path later doesn\'t break them',
                'Names are required for GET routes',
                'To hide the URL from visitors',
            ],
            'correct_option' => 1,
            'explanation' => 'The path is written once, in the route; everything else refers to the name.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Dispatch a request',
            'prompt' => 'Write `dispatch(string $method, string $path, array $routes): string`. Routes are keyed like `GET /books`. Call the matching route\'s function and return what it returns, or return `404 Not Found` when there is none. The method may arrive in lower case.',
            'starter_code' => <<<'PHP'
                <?php

                function dispatch(string $method, string $path, array $routes): string
                {
                    return $routes["$method $path"]();
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function dispatch(string $method, string $path, array $routes): string
                {
                    $key = strtoupper($method) . ' ' . $path;

                    return isset($routes[$key]) ? $routes[$key]() : '404 Not Found';
                }
                PHP,
            'hint' => 'Build the key with `strtoupper($method)`, then use `isset()` to decide between calling the route and returning the 404 text.',
            'checks' => [
                ['label' => 'Matching routes run', 'code' => "\$routes = ['GET /' => fn () => 'Home', 'GET /books' => fn () => 'Books'];\nreturn expect_same([dispatch('GET', '/', \$routes), dispatch('GET', '/books', \$routes)], ['Home', 'Books']);"],
                ['label' => 'Unknown paths are 404', 'code' => "return expect_same(dispatch('GET', '/films', ['GET /' => fn () => 'Home']), '404 Not Found');"],
                ['label' => 'The method matters', 'code' => "\$routes = ['POST /newsletter' => fn () => 'Thanks!'];\nreturn expect_same([dispatch('GET', '/newsletter', \$routes), dispatch('post', '/newsletter', \$routes)], ['404 Not Found', 'Thanks!']);"],
            ],
        ],
        [
            'title' => 'Match a parameter',
            'prompt' => 'Write `matchRoute(string $route, string $path): ?array`. Split both at `/`. They match if they have the same number of segments, and every segment is equal, except where the route has `{name}`: there, any non-empty segment matches and is returned under `name`. Return the parameters (an empty array if there are none), or `null` if the path doesn\'t match.',
            'starter_code' => <<<'PHP'
                <?php

                function matchRoute(string $route, string $path): ?array
                {
                    return $route === $path ? [] : null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function matchRoute(string $route, string $path): ?array
                {
                    $routeSegments = explode('/', $route);
                    $pathSegments = explode('/', $path);
                    if (count($routeSegments) !== count($pathSegments)) {
                        return null;
                    }

                    $parameters = [];
                    foreach ($routeSegments as $i => $segment) {
                        if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                            if ($pathSegments[$i] === '') {
                                return null;
                            }
                            $parameters[substr($segment, 1, -1)] = $pathSegments[$i];
                        } elseif ($segment !== $pathSegments[$i]) {
                            return null;
                        }
                    }

                    return $parameters;
                }
                PHP,
            'hint' => 'Compare the counts first. In the loop, a `{name}` segment records `$pathSegments[$i]` (if it isn\'t empty); any other segment must be identical.',
            'checks' => [
                ['label' => 'Parameters are captured', 'code' => "return expect_same(matchRoute('/authors/{author}/books/{book}', '/authors/herbert/books/dune'), ['author' => 'herbert', 'book' => 'dune']);"],
                ['label' => 'Routes without parameters', 'code' => "return expect_same([matchRoute('/books', '/books'), matchRoute('/books', '/films')], [[], null]);"],
                ['label' => 'The number of segments must match', 'code' => "return expect_same([matchRoute('/books/{id}', '/books'), matchRoute('/books/{id}', '/books/7/reviews')], [null, null]);"],
                ['label' => 'Parameters can\'t be empty', 'code' => "return expect_same(matchRoute('/books/{id}', '/books/'), null);"],
            ],
        ],
        [
            'title' => 'Read the query string',
            'prompt' => 'Write `query(string $url, string $key, mixed $default = null): mixed`, like `$request->query()`. Take the query string from the URL with `parse_url()`, turn it into an array with `parse_str()`, and return the value for `$key`, or `$default` if it\'s missing.',
            'starter_code' => <<<'PHP'
                <?php

                function query(string $url, string $key, mixed $default = null): mixed
                {
                    return $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function query(string $url, string $key, mixed $default = null): mixed
                {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $values);

                    return $values[$key] ?? $default;
                }
                PHP,
            'hint' => '`parse_url($url, PHP_URL_QUERY)` gives the part after `?` (or `null`). `parse_str($string, $values)` fills `$values` with an array.',
            'checks' => [
                ['label' => 'Values are read', 'code' => "return expect_same([query('/search?q=dune&page=2', 'q'), query('/search?q=dune&page=2', 'page')], ['dune', '2']);"],
                ['label' => 'Defaults for missing keys', 'code' => "return expect_same([query('/search?q=dune', 'page', 1), query('/books', 'sort', 'title')], [1, 'title']);"],
                ['label' => 'Encoded values are decoded', 'code' => "return expect_same(query('/search?q=science%20fiction&author=O%27Brien', 'author'), \"O'Brien\");"],
            ],
        ],
        [
            'title' => 'A JSON response',
            'prompt' => 'Write `jsonResponse(mixed $data, int $status = 200): array`, returning what a route that returns data sends back: `[\'status\' => $status, \'headers\' => [\'Content-Type\' => \'application/json\'], \'body\' => ...]`, where the body is the data as JSON with slashes left unescaped (`JSON_UNESCAPED_SLASHES`).',
            'starter_code' => <<<'PHP'
                <?php

                function jsonResponse(mixed $data, int $status = 200): array
                {
                    return ['status' => $status, 'headers' => [], 'body' => (string) $data];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function jsonResponse(mixed $data, int $status = 200): array
                {
                    return [
                        'status' => $status,
                        'headers' => ['Content-Type' => 'application/json'],
                        'body' => json_encode($data, JSON_UNESCAPED_SLASHES),
                    ];
                }
                PHP,
            'hint' => '`json_encode($data, JSON_UNESCAPED_SLASHES)` makes the body.',
            'checks' => [
                ['label' => 'A list of books', 'code' => "return expect_same(jsonResponse(['Dune', 'Piranesi']), ['status' => 200, 'headers' => ['Content-Type' => 'application/json'], 'body' => '[\"Dune\",\"Piranesi\"]']);"],
                ['label' => 'Other statuses', 'code' => "return expect_same(jsonResponse(['id' => 7], 201)['status'], 201);"],
                ['label' => 'Slashes stay readable', 'code' => "return expect_same(jsonResponse(['url' => '/books/7'])['body'], '{\"url\":\"/books/7\"}');"],
            ],
        ],
        [
            'title' => 'URLs from names',
            'prompt' => 'Write `route(string $name, array $parameters, array $routes): string`, like Laravel\'s helper. `$routes` maps names to paths such as `/books/{id}`. Replace each `{name}` in the path with the matching parameter. Throw `InvalidArgumentException` if the route name doesn\'t exist.',
            'starter_code' => <<<'PHP'
                <?php

                function route(string $name, array $parameters, array $routes): string
                {
                    return $routes[$name];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function route(string $name, array $parameters, array $routes): string
                {
                    if (! isset($routes[$name])) {
                        throw new InvalidArgumentException("Route [$name] not defined.");
                    }

                    $path = $routes[$name];
                    foreach ($parameters as $key => $value) {
                        $path = str_replace('{' . $key . '}', (string) $value, $path);
                    }

                    return $path;
                }
                PHP,
            'hint' => 'Loop over the parameters and `str_replace()` each `{key}` with its value.',
            'checks' => [
                ['label' => 'Parameters are filled in', 'code' => "\$routes = ['books.show' => '/books/{id}', 'authors.books' => '/authors/{author}/books/{book}'];\nreturn expect_same([route('books.show', ['id' => 7], \$routes), route('authors.books', ['author' => 'herbert', 'book' => 'dune'], \$routes)], ['/books/7', '/authors/herbert/books/dune']);"],
                ['label' => 'Routes without parameters', 'code' => "return expect_same(route('home', [], ['home' => '/']), '/');"],
                ['label' => 'Unknown names throw', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => route('films.index', [], ['books.index' => '/books']));"],
            ],
        ],
        [
            'title' => 'A route group',
            'prompt' => 'Write `prefixRoutes(string $prefix, array $routes): array`, like `Route::prefix()->group()`. Routes are keyed like `GET /books`; return them with the prefix added to each path, so `admin` and `GET /books` give `GET /admin/books`. The prefix may have slashes at either end, and `GET /` becomes `GET /admin`. The functions stay the same.',
            'starter_code' => <<<'PHP'
                <?php

                function prefixRoutes(string $prefix, array $routes): array
                {
                    return $routes;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function prefixRoutes(string $prefix, array $routes): array
                {
                    $prefix = '/' . trim($prefix, '/');
                    $prefixed = [];

                    foreach ($routes as $key => $action) {
                        [$method, $path] = explode(' ', $key, 2);
                        $prefixed[$method . ' ' . rtrim($prefix . $path, '/')] = $action;
                    }

                    return $prefixed;
                }
                PHP,
            'hint' => 'Normalise the prefix to `/admin` with `trim()`. Split each key at the first space, join prefix and path, and `rtrim()` a trailing `/` (which turns `/admin/` into `/admin`).',
            'checks' => [
                ['label' => 'Paths are prefixed', 'code' => "\$routes = prefixRoutes('admin', ['GET /books' => fn () => 'list', 'POST /books' => fn () => 'save']);\nreturn expect_same(array_keys(\$routes), ['GET /admin/books', 'POST /admin/books']);"],
                ['label' => 'The functions are kept', 'code' => "\$routes = prefixRoutes('admin', ['GET /books' => fn () => 'list']);\nreturn expect_same((\$routes['GET /admin/books'] ?? fn () => null)(), 'list');"],
                ['label' => 'Slashes and the root route', 'code' => "return expect_same(array_keys(prefixRoutes('/shop/', ['GET /' => fn () => 'x', 'GET /basket' => fn () => 'y'])), ['GET /shop', 'GET /shop/basket']);"],
            ],
        ],
    ],
];
