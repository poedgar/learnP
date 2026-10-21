<?php

// Lesson 5 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'routing-and-model-binding',
    'title' => 'Routing and model binding',
    'summary' => 'How the router matches paths with regular expressions, returns 404 and 405, applies constraints, builds URLs from named routes and turns parameters into models.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a path with a page number, or none.
        $routes = [
            '/books/{id}' => fn ($id) => "Book $id",
            '/authors/{author}/books' => fn ($author) => "Books by $author",
        ];

        $path = '/authors/herbert/books';

        foreach ($routes as $uri => $action) {
            $pattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $uri) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                echo $action(...array_values($parameters));
                break;
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $pattern = '#^/books/(?P<id>[^/]+)$#';

                var_dump(
                    preg_match($pattern, '/books/42'),
                    preg_match($pattern, '/books/42/reviews'),
                    preg_match($pattern, '/books/'),
                );
                PHP,
            'options' => [
                '`int(1)`, `int(1)`, `int(1)`',
                '`int(1)`, `int(0)`, `int(0)`',
                '`int(1)`, `int(0)`, `int(1)`',
                '`int(1)`, `int(1)`, `int(0)`',
            ],
            'correct_option' => 1,
            'explanation' => '`[^/]+` needs at least one character that isn\'t a slash, and `$` means nothing may follow. So only `/books/42` matches.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                preg_match('#^/books/(?P<id>[^/]+)$#', '/books/dune', $matches);

                echo count($matches) . ' ' . $matches['id'] . ' ' . $matches[1];
                PHP,
            'options' => ['`1 dune dune`', '`2 dune dune`', '`3 dune dune`', '`2 dune /books/dune`'],
            'correct_option' => 2,
            'explanation' => 'Index 0 is the whole match; the named group appears twice, as `id` and as `1`. That\'s why routers filter out the numeric keys.',
        ],
        [
            'question' => 'A `POST` request is sent to `/books/42`, but only `Route::get(\'/books/{id}\', ...)` exists. What status does Laravel respond with?',
            'options' => ['200', '404 Not Found', '405 Method Not Allowed', '500 Server Error'],
            'correct_option' => 2,
            'explanation' => 'The path matches a route, just not with that method, so the answer is 405 rather than 404.',
        ],
        [
            'question' => 'Which route does `/books/dune` match?',
            'code' => <<<'PHP'
                $routes = [
                    'numbers' => '#^/books/(?P<id>[0-9]+)$#',
                    'slugs' => '#^/books/(?P<slug>[a-z-]+)$#',
                ];

                foreach ($routes as $name => $pattern) {
                    if (preg_match($pattern, '/books/dune')) {
                        echo $name;
                        break;
                    }
                }
                PHP,
            'options' => ['`numbers`', '`slugs`', 'Both', 'Neither'],
            'correct_option' => 1,
            'explanation' => 'The constraint `[0-9]+` stops the first route matching letters, so the router moves on to the next one, as `->where()` does in Laravel.',
        ],
        [
            'question' => 'What does `route(\'books.show\', [\'book\' => 42, \'tab\' => \'reviews\'])` return for a route `/books/{book}` named `books.show`?',
            'options' => [
                '`/books/42`',
                '`/books/42/reviews`',
                '`/books/42?tab=reviews`',
                'An error, because `tab` isn\'t a parameter',
            ],
            'correct_option' => 2,
            'explanation' => 'Parameters that aren\'t in the path are added as a query string.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Author {}

                $action = function (Author $author, string $tab = 'books') {};

                foreach ((new ReflectionFunction($action))->getParameters() as $parameter) {
                    echo $parameter->getName() . '=' . $parameter->getType()->getName() . ' ';
                }
                PHP,
            'options' => [
                '`author=Author tab=string `',
                '`Author string `',
                '`author tab `',
                '`author=object tab=string `',
            ],
            'correct_option' => 0,
            'explanation' => 'Reflection gives each parameter\'s name and type. Route model binding uses both: the name to find the route value, the type to find the model.',
        ],
        [
            'question' => 'In Laravel, what happens when `/books/999` hits `fn (Book $book) => ...` and no book has id 999?',
            'options' => [
                'The action gets `null`',
                'The action gets the string `\'999\'`',
                'A `ModelNotFoundException` is thrown, which becomes a 404',
                'Laravel creates a new empty `Book`',
            ],
            'correct_option' => 2,
            'explanation' => 'Implicit binding fails loudly, so your action can always rely on getting a real model.',
        ],
        [
            'question' => 'What does `{book:slug}` in a route URI do?',
            'options' => [
                'It requires the parameter to look like a slug',
                'It binds the `Book` model by its `slug` column instead of its id',
                'It renames the parameter to `slug`',
                'It creates a slug from the book\'s title',
            ],
            'correct_option' => 1,
            'explanation' => 'The part after the colon is the column the model is looked up by.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Compile a route',
            'prompt' => 'Write `compileRoute(string $uri): string`, returning a regular expression with `#` delimiters that matches whole paths. `{name}` becomes a named group matching one or more characters other than `/`. `{name?}` is optional: the group and the slash before it may both be left out. Other characters match only themselves.',
            'starter_code' => <<<'PHP'
                <?php

                function compileRoute(string $uri): string
                {
                    return '#^' . $uri . '$#';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function compileRoute(string $uri): string
                {
                    $pattern = preg_quote($uri, '#');
                    $pattern = preg_replace('#/\\\\\{(\w+)\\\\\?\\\\\}#', '(?:/(?P<$1>[^/]+))?', $pattern);
                    $pattern = preg_replace('#\\\\\{(\w+)\\\\\}#', '(?P<$1>[^/]+)', $pattern);

                    return '#^' . $pattern . '$#';
                }
                PHP,
            'hint' => 'Handle optional parameters first, replacing `/{name?}` with `(?:/(?P<name>[^/]+))?`, then the required ones. `preg_quote()` the URI first so a `.` in it means a dot; the braces then appear escaped as `\{` and `\}`.',
            'checks' => [
                ['label' => 'Parameters are captured by name', 'code' => "preg_match(compileRoute('/books/{id}/reviews/{review}'), '/books/42/reviews/7', \$m);\nreturn expect_same([\$m['id'] ?? null, \$m['review'] ?? null], ['42', '7']);"],
                ['label' => 'Whole paths only', 'code' => "\$p = compileRoute('/books/{id}');\nreturn expect_same([preg_match(\$p, '/books/42'), preg_match(\$p, '/books/42/edit'), preg_match(\$p, '/books/'), preg_match(\$p, '/old/books/42')], [1, 0, 0, 0]);"],
                ['label' => 'Optional parameters may be left out', 'code' => "\$p = compileRoute('/authors/{author}/books/{page?}');\npreg_match(\$p, '/authors/herbert/books/2', \$m);\nreturn expect_same([preg_match(\$p, '/authors/herbert/books'), \$m['page'] ?? null], [1, '2']);"],
                ['label' => 'Other characters match only themselves', 'code' => "\$p = compileRoute('/feed.{format}');\nreturn expect_same([preg_match(\$p, '/feed.xml'), preg_match(\$p, '/feedXxml')], [1, 0]);"],
            ],
        ],
        [
            'title' => 'Dispatch with 404 and 405',
            'prompt' => 'Finish `Router::dispatch(string $method, string $path)`. Find the first route whose method and path match, and call its action with the route parameters in order. If no route\'s path matches, throw `HttpException` with status 404. If a path matches but none with this method, throw it with status 405.',
            'starter_code' => <<<'PHP'
                <?php

                class HttpException extends RuntimeException
                {
                    public function __construct(public int $status)
                    {
                        parent::__construct("HTTP $status");
                    }
                }

                class Router
                {
                    private array $routes = [];

                    public function get(string $uri, Closure $action): void
                    {
                        $this->routes[] = ['GET', $uri, $action];
                    }

                    public function post(string $uri, Closure $action): void
                    {
                        $this->routes[] = ['POST', $uri, $action];
                    }

                    public function dispatch(string $method, string $path): mixed
                    {
                        throw new HttpException(404);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class HttpException extends RuntimeException
                {
                    public function __construct(public int $status)
                    {
                        parent::__construct("HTTP $status");
                    }
                }

                class Router
                {
                    private array $routes = [];

                    public function get(string $uri, Closure $action): void
                    {
                        $this->routes[] = ['GET', $uri, $action];
                    }

                    public function post(string $uri, Closure $action): void
                    {
                        $this->routes[] = ['POST', $uri, $action];
                    }

                    public function dispatch(string $method, string $path): mixed
                    {
                        $pathMatched = false;

                        foreach ($this->routes as [$routeMethod, $uri, $action]) {
                            $pattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $uri) . '$#';
                            if (preg_match($pattern, $path, $matches) !== 1) {
                                continue;
                            }

                            $pathMatched = true;
                            if ($routeMethod === $method) {
                                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                                return $action(...array_values($parameters));
                            }
                        }

                        throw new HttpException($pathMatched ? 405 : 404);
                    }
                }
                PHP,
            'hint' => 'Compile each URI as in the lesson. Remember whether any path matched while you look for one with the right method; that decides between 405 and 404. Keep only the string keys of `$matches`.',
            'checks' => [
                ['label' => 'Matching routes run with their parameters', 'code' => "\$r = new Router();\n\$r->get('/authors/{author}/books/{book}', fn (\$a, \$b) => \"\$b by \$a\");\nreturn expect_same(\$r->dispatch('GET', '/authors/herbert/books/dune'), 'dune by herbert');"],
                ['label' => 'The method matters', 'code' => "\$r = new Router();\n\$r->get('/books', fn () => 'list');\n\$r->post('/books', fn () => 'create');\nreturn expect_same([\$r->dispatch('GET', '/books'), \$r->dispatch('POST', '/books')], ['list', 'create']);"],
                ['label' => 'Unknown paths are 404', 'code' => "\$r = new Router();\n\$r->get('/books', fn () => 'list');\ntry { \$r->dispatch('GET', '/films'); } catch (HttpException \$e) { return expect_same(\$e->status, 404); }\nreturn 'No HttpException was thrown.';"],
                ['label' => 'Wrong methods are 405', 'code' => "\$r = new Router();\n\$r->get('/books/{id}', fn (\$id) => \$id);\ntry { \$r->dispatch('POST', '/books/1'); } catch (HttpException \$e) { return expect_same(\$e->status, 405); }\nreturn 'No HttpException was thrown.';"],
            ],
        ],
        [
            'title' => 'Constraints',
            'prompt' => 'Add constraints to `Route`. `where(string $parameter, string $pattern)` returns the route, so calls can be chained, and makes that parameter match only `$pattern` (a regular expression without delimiters, such as `[0-9]+`). `matches(string $path)` returns the parameters as an array keyed by name, or `null` if the path doesn\'t match. Parameters without a constraint still match `[^/]+`.',
            'starter_code' => <<<'PHP'
                <?php

                class Route
                {
                    public function __construct(public string $uri) {}

                    public function where(string $parameter, string $pattern): static
                    {
                        return $this;
                    }

                    public function matches(string $path): ?array
                    {
                        $pattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->uri) . '$#';
                        if (preg_match($pattern, $path, $matches) !== 1) {
                            return null;
                        }

                        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Route
                {
                    private array $wheres = [];

                    public function __construct(public string $uri) {}

                    public function where(string $parameter, string $pattern): static
                    {
                        $this->wheres[$parameter] = $pattern;
                        return $this;
                    }

                    public function matches(string $path): ?array
                    {
                        $pattern = '#^' . preg_replace_callback(
                            '/\{(\w+)\}/',
                            fn (array $m) => '(?P<' . $m[1] . '>' . ($this->wheres[$m[1]] ?? '[^/]+') . ')',
                            $this->uri,
                        ) . '$#';

                        if (preg_match($pattern, $path, $matches) !== 1) {
                            return null;
                        }

                        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    }
                }
                PHP,
            'hint' => 'Store the constraints in an array keyed by parameter name. Use `preg_replace_callback()` so each `{name}` can become `(?P<name>...)` with its own pattern, or `[^/]+` if it has none.',
            'checks' => [
                ['label' => 'A constraint is applied', 'code' => "\$route = (new Route('/books/{id}'))->where('id', '[0-9]+');\nreturn expect_same([\$route->matches('/books/42'), \$route->matches('/books/dune')], [['id' => '42'], null]);"],
                ['label' => 'where() can be chained', 'code' => "\$route = (new Route('/{year}/{slug}'))->where('year', '[0-9]{4}')->where('slug', '[a-z-]+');\nreturn expect_same([\$route->matches('/2026/dune-messiah'), \$route->matches('/26/dune'), \$route->matches('/2026/Dune')], [['year' => '2026', 'slug' => 'dune-messiah'], null, null]);"],
                ['label' => 'Other parameters still match anything but /', 'code' => "\$route = (new Route('/authors/{author}/books/{id}'))->where('id', '[0-9]+');\nreturn expect_same(\$route->matches('/authors/frank-herbert/books/7'), ['author' => 'frank-herbert', 'id' => '7']);"],
            ],
        ],
        [
            'title' => 'URLs from names',
            'prompt' => 'Finish `UrlGenerator::route(string $name, array $parameters = [])`. It finds the route with that name, fills in each `{parameter}` (URL-encoded with `rawurlencode()`) and adds any leftover parameters as a query string with `http_build_query()`. A missing route name throws `InvalidArgumentException`, and so does a missing path parameter.',
            'starter_code' => <<<'PHP'
                <?php

                class UrlGenerator
                {
                    public function __construct(private array $routes) {}

                    public function route(string $name, array $parameters = []): string
                    {
                        return $this->routes[$name];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class UrlGenerator
                {
                    public function __construct(private array $routes) {}

                    public function route(string $name, array $parameters = []): string
                    {
                        if (! isset($this->routes[$name])) {
                            throw new InvalidArgumentException("Route [$name] not defined.");
                        }

                        $path = preg_replace_callback('/\{(\w+)\}/', function (array $match) use ($name, &$parameters) {
                            if (! array_key_exists($match[1], $parameters)) {
                                throw new InvalidArgumentException("Missing parameter {$match[1]} for route $name.");
                            }
                            $value = $parameters[$match[1]];
                            unset($parameters[$match[1]]);

                            return rawurlencode((string) $value);
                        }, $this->routes[$name]);

                        return $parameters === [] ? $path : $path . '?' . http_build_query($parameters);
                    }
                }
                PHP,
            'hint' => 'Use `preg_replace_callback()`, taking each value out of `$parameters` (by reference) as you use it. Whatever is left afterwards goes through `http_build_query()`.',
            'checks' => [
                ['label' => 'Parameters fill the path', 'code' => "\$urls = new UrlGenerator(['books.show' => '/authors/{author}/books/{book}']);\nreturn expect_same(\$urls->route('books.show', ['author' => 'herbert', 'book' => 42]), '/authors/herbert/books/42');"],
                ['label' => 'Leftovers become the query string', 'code' => "\$urls = new UrlGenerator(['books.show' => '/books/{book}']);\nreturn expect_same(\$urls->route('books.show', ['book' => 42, 'tab' => 'reviews', 'page' => 2]), '/books/42?tab=reviews&page=2');"],
                ['label' => 'Values are URL-encoded', 'code' => "\$urls = new UrlGenerator(['search' => '/search/{term}']);\nreturn expect_same(\$urls->route('search', ['term' => 'sci fi/space']), '/search/sci%20fi%2Fspace');"],
                ['label' => 'Unknown names and missing parameters throw', 'code' => "\$urls = new UrlGenerator(['books.show' => '/books/{book}', 'home' => '/']);\nif (\$urls->route('home') !== '/') return 'A route without parameters should give its path.';\n\$a = expect_throws(InvalidArgumentException::class, fn () => \$urls->route('films.show'));\nreturn \$a === true ? expect_throws(InvalidArgumentException::class, fn () => \$urls->route('books.show')) : \$a;"],
            ],
        ],
        [
            'title' => 'Bind the model',
            'prompt' => 'Write `callWithBindings(Closure $action, array $parameters)`, which calls the action with arguments by parameter name. If a parameter\'s type is a subclass of `Model`, pass `Type::find($value)` instead of the raw value, and if that returns `null`, throw `ModelNotFoundException`. Other parameters get the raw value.',
            'starter_code' => <<<'PHP'
                <?php

                class ModelNotFoundException extends RuntimeException {}

                abstract class Model
                {
                    protected const ROWS = [];

                    final public function __construct(public array $attributes) {}

                    public static function find(int|string $id): ?static
                    {
                        $row = static::ROWS[$id] ?? null;

                        return $row === null ? null : new static(['id' => (int) $id, ...$row]);
                    }
                }

                class Book extends Model
                {
                    protected const ROWS = [1 => ['title' => 'Dune'], 2 => ['title' => 'Piranesi']];
                }

                class Author extends Model
                {
                    protected const ROWS = [7 => ['name' => 'Frank Herbert']];
                }

                function callWithBindings(Closure $action, array $parameters): mixed
                {
                    return $action(...$parameters);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class ModelNotFoundException extends RuntimeException {}

                abstract class Model
                {
                    protected const ROWS = [];

                    final public function __construct(public array $attributes) {}

                    public static function find(int|string $id): ?static
                    {
                        $row = static::ROWS[$id] ?? null;

                        return $row === null ? null : new static(['id' => (int) $id, ...$row]);
                    }
                }

                class Book extends Model
                {
                    protected const ROWS = [1 => ['title' => 'Dune'], 2 => ['title' => 'Piranesi']];
                }

                class Author extends Model
                {
                    protected const ROWS = [7 => ['name' => 'Frank Herbert']];
                }

                function callWithBindings(Closure $action, array $parameters): mixed
                {
                    $arguments = [];
                    foreach ((new ReflectionFunction($action))->getParameters() as $parameter) {
                        $value = $parameters[$parameter->getName()];
                        $type = $parameter->getType();

                        if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), Model::class)) {
                            $value = $type->getName()::find($value)
                                ?? throw new ModelNotFoundException("No {$type->getName()} $value.");
                        }

                        $arguments[] = $value;
                    }

                    return $action(...$arguments);
                }
                PHP,
            'hint' => 'Loop over `(new ReflectionFunction($action))->getParameters()`. `is_subclass_of($type->getName(), Model::class)` tells you whether to look the value up; `$class::find($value)` works with a class name in a variable.',
            'checks' => [
                ['label' => 'Models are looked up', 'code' => "return expect_same(callWithBindings(fn (Book \$book) => \$book->attributes['title'], ['book' => '2']), 'Piranesi');"],
                ['label' => 'Arguments go by name, not position', 'code' => "return expect_same(callWithBindings(fn (Author \$author, Book \$book) => \$book->attributes['title'] . ' by ' . \$author->attributes['name'], ['book' => '1', 'author' => '7']), 'Dune by Frank Herbert');"],
                ['label' => 'Other parameters get the raw value', 'code' => "return expect_same(callWithBindings(fn (Book \$book, string \$tab) => \"{\$book->attributes['id']}/\$tab\", ['book' => '1', 'tab' => 'reviews']), '1/reviews');"],
                ['label' => 'Missing models throw', 'code' => "return expect_throws(ModelNotFoundException::class, fn () => callWithBindings(fn (Book \$book) => 'found', ['book' => '999']));"],
            ],
        ],
        [
            'title' => 'Bind by slug',
            'prompt' => 'Support `{book:slug}`. `parseParameters(string $uri, string $path)` returns, for each parameter in the URI, its value and the field to look it up by: `[\'book\' => [\'value\' => \'dune\', \'field\' => \'slug\']]`. A parameter without `:field` uses the model\'s key, `id`. Return `null` if the path doesn\'t match. Then `Book::resolveRouteBinding($value, $field)` returns the book whose `$field` equals `$value`, or `null`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public const ROWS = [
                        ['id' => 1, 'slug' => 'dune', 'title' => 'Dune'],
                        ['id' => 2, 'slug' => 'piranesi', 'title' => 'Piranesi'],
                    ];

                    public function __construct(public array $attributes) {}

                    public static function resolveRouteBinding(string $value, string $field): ?static
                    {
                        return null;
                    }
                }

                function parseParameters(string $uri, string $path): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public const ROWS = [
                        ['id' => 1, 'slug' => 'dune', 'title' => 'Dune'],
                        ['id' => 2, 'slug' => 'piranesi', 'title' => 'Piranesi'],
                    ];

                    public function __construct(public array $attributes) {}

                    public static function resolveRouteBinding(string $value, string $field): ?static
                    {
                        foreach (self::ROWS as $row) {
                            if ((string) ($row[$field] ?? '') === $value) {
                                return new static($row);
                            }
                        }

                        return null;
                    }
                }

                function parseParameters(string $uri, string $path): ?array
                {
                    $fields = [];
                    $pattern = preg_replace_callback('/\{(\w+)(?::(\w+))?\}/', function (array $m) use (&$fields) {
                        $fields[$m[1]] = $m[2] ?? 'id';

                        return '(?P<' . $m[1] . '>[^/]+)';
                    }, $uri);

                    if (preg_match('#^' . $pattern . '$#', $path, $matches) !== 1) {
                        return null;
                    }

                    $parameters = [];
                    foreach ($fields as $name => $field) {
                        $parameters[$name] = ['value' => $matches[$name], 'field' => $field];
                    }

                    return $parameters;
                }
                PHP,
            'hint' => 'Match `\{(\w+)(?::(\w+))?\}` with `preg_replace_callback()`, recording each parameter\'s field (or `\'id\'`) as you turn it into a named group. Compare values as strings in `resolveRouteBinding()`, since ids are integers.',
            'checks' => [
                ['label' => 'Fields come from the URI', 'code' => "return expect_same(parseParameters('/authors/{author}/books/{book:slug}', '/authors/7/books/dune'), ['author' => ['value' => '7', 'field' => 'id'], 'book' => ['value' => 'dune', 'field' => 'slug']]);"],
                ['label' => 'Non-matching paths give null', 'code' => "return expect_same(parseParameters('/books/{book:slug}', '/films/dune'), null);"],
                ['label' => 'Books are found by any field', 'code' => "return expect_same([Book::resolveRouteBinding('piranesi', 'slug')?->attributes['title'], Book::resolveRouteBinding('1', 'id')?->attributes['title']], ['Piranesi', 'Dune']);"],
                ['label' => 'Unknown values give null', 'code' => "return expect_same([Book::resolveRouteBinding('foundation', 'slug'), Book::resolveRouteBinding('9', 'id')], [null, null]);"],
            ],
        ],
    ],
];
