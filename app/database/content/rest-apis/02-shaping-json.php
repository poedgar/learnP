<?php

// Lesson 2 of the REST APIs course. The theory is in the .md file with the same name.

return [
    'slug' => 'shaping-json',
    'title' => 'Shaping JSON with API resources',
    'summary' => 'API resources between your models and your clients: the data wrapper, consistent keys, ISO dates and integer money, whenLoaded and when, and letting clients choose includes and fields safely.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add the author, or format the date.
        $book = ['id' => 7, 'title' => 'Dune', 'price' => 9.99, 'password_hint' => 'secret'];

        $resource = [
            'id' => $book['id'],
            'title' => $book['title'],
            'price' => ['amount' => (int) round($book['price'] * 100), 'currency' => 'GBP'],
        ];

        echo json_encode(['data' => $resource], JSON_PRETTY_PRINT);
        PHP,

    'quiz' => [
        [
            'question' => 'Why return an API resource instead of the model itself?',
            'options' => [
                'Models can\'t be turned into JSON',
                'The resource decides the exact shape, so database changes don\'t break clients and new columns don\'t leak',
                'Resources are faster',
                'Laravel requires it for APIs',
            ],
            'correct_option' => 1,
            'explanation' => 'The API is a promise to clients; the resource keeps it separate from the table.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                var_dump(0.1 + 0.2 === 0.3);
                var_dump(10 + 20 === 30);
                PHP,
            'options' => ['`bool(true) bool(true)`', '`bool(false) bool(true)`', '`bool(true) bool(false)`', '`bool(false) bool(false)`'],
            'correct_option' => 1,
            'explanation' => 'Floats can\'t store 0.1 exactly; whole numbers of pence or cents are exact.',
        ],
        [
            'question' => 'Which date format is best in a JSON API?',
            'options' => ['`21/10/2026`', '`2026-10-21T09:00:00+00:00`', '`1792573200`', '`Wednesday 21st October`'],
            'correct_option' => 1,
            'explanation' => 'ISO 8601 with a time zone is unambiguous, sortable and every language can parse it.',
        ],
        [
            'question' => 'What does `$this->whenLoaded(\'author\')` do in a resource?',
            'options' => [
                'Loads the author',
                'Includes the author only if it was eager-loaded, and otherwise leaves the key out',
                'Waits for the author to load',
                'Always returns null',
            ],
            'correct_option' => 1,
            'explanation' => 'The resource never runs a query per book; the controller decides what to load.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $book = ['id' => 7, 'author' => null];

                echo isset($book['author']) ? 'isset ' : 'not isset ';
                echo array_key_exists('author', $book) ? 'loaded' : 'not loaded';
                PHP,
            'options' => ['`isset loaded`', '`not isset loaded`', '`not isset not loaded`', '`isset not loaded`'],
            'correct_option' => 1,
            'explanation' => 'A book with no author has the relation loaded as `null`, which `isset()` can\'t tell from missing.',
        ],
        [
            'question' => 'What does `BookResource::collection($books)` return as JSON for a paginated query?',
            'options' => [
                'A bare array of books',
                'An object with the books under `data`, plus `links` and `meta` for the pages',
                'Only the first book',
                'The SQL query',
            ],
            'correct_option' => 1,
            'explanation' => 'The `data` wrapper leaves room for paging information beside it.',
        ],
        [
            'question' => 'Why must `?include=` be checked against an allow-list?',
            'options' => [
                'It mustn\'t; any relation is fine',
                'Otherwise clients could load any relation, including private ones or huge ones that slow the server down',
                'Because includes are case-sensitive',
                'To make the URL shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'Clients choose from what you offer, not from everything the model has.',
        ],
        [
            'question' => 'What is a sparse fieldset?',
            'options' => [
                'A form with few fields',
                'The client asking for only some fields, like `?fields=id,title`',
                'A table with null columns',
                'A resource without relations',
            ],
            'correct_option' => 1,
            'explanation' => 'A list screen doesn\'t need every book\'s description.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A user resource',
            'prompt' => 'Write `userResource(array $user): array`. Return `id`, `name`, `email` and `joined_at` (the `created_at` value, a UTC time like `2026-10-21 09:00:00`, as `2026-10-21T09:00:00+00:00`) in that order. Nothing else, so `password` and `remember_token` never appear. A missing `email` becomes `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function userResource(array $user): array
                {
                    unset($user['password']);

                    return $user;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function userResource(array $user): array
                {
                    $joined = new DateTimeImmutable($user['created_at'], new DateTimeZone('UTC'));

                    return [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'] ?? null,
                        'joined_at' => $joined->format(DATE_ATOM),
                    ];
                }
                PHP,
            'hint' => 'Build a new array field by field. `DATE_ATOM` is the ISO 8601 format.',
            'checks' => [
                ['label' => 'The shape', 'code' => <<<'PHP'
                    $user = ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com', 'password' => '$2y$12$abc', 'remember_token' => 'xyz', 'created_at' => '2026-10-21 09:00:00'];
                    return expect_same(userResource($user), ['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com', 'joined_at' => '2026-10-21T09:00:00+00:00']);
                    PHP],
                ['label' => 'A missing email is null', 'code' => "return expect_same(userResource(['id' => 2, 'name' => 'Bob', 'created_at' => '2026-01-02 03:04:05'])['email'], null);"],
            ],
        ],
        [
            'title' => 'Prices in pence',
            'prompt' => 'Write `money(float|int|string $price, string $currency = \'GBP\'): array`, which turns a price such as `9.99` or `"12.5"` into `[\'amount\' => 999, \'currency\' => \'GBP\']`, rounding to the nearest penny. A negative price throws an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function money(float|int|string $price, string $currency = 'GBP'): array
                {
                    return ['amount' => (int) ($price * 100), 'currency' => $currency];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function money(float|int|string $price, string $currency = 'GBP'): array
                {
                    if ((float) $price < 0) {
                        throw new InvalidArgumentException('A price cannot be negative.');
                    }

                    return ['amount' => (int) round((float) $price * 100), 'currency' => $currency];
                }
                PHP,
            'hint' => '`(int) (19.99 * 100)` is 1998, because the float is a hair under 1999. Round before casting.',
            'checks' => [
                ['label' => 'Exact pence', 'code' => "return expect_same([money(9.99), money('12.5'), money(19.99)['amount'], money(0.29)['amount'], money(3, 'EUR')], [['amount' => 999, 'currency' => 'GBP'], ['amount' => 1250, 'currency' => 'GBP'], 1999, 29, ['amount' => 300, 'currency' => 'EUR']]);"],
                ['label' => 'No negative prices', 'code' => 'return expect_throws(InvalidArgumentException::class, fn () => money(-1));'],
            ],
        ],
        [
            'title' => 'Only what was loaded',
            'prompt' => 'Write `bookResource(array $book): array` returning `id` and `title`, then `author` (as `[\'name\' => ...]`, or `null` for a book without an author) **only if** the `author` key is present, then `reviews_count` only if that key is present.',
            'starter_code' => <<<'PHP'
                <?php

                function bookResource(array $book): array
                {
                    return [
                        'id' => $book['id'],
                        'title' => $book['title'],
                        'author' => isset($book['author']) ? ['name' => $book['author']['name']] : null,
                        'reviews_count' => $book['reviews_count'] ?? 0,
                    ];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bookResource(array $book): array
                {
                    $data = ['id' => $book['id'], 'title' => $book['title']];

                    if (array_key_exists('author', $book)) {
                        $data['author'] = $book['author'] === null ? null : ['name' => $book['author']['name']];
                    }
                    if (array_key_exists('reviews_count', $book)) {
                        $data['reviews_count'] = $book['reviews_count'];
                    }

                    return $data;
                }
                PHP,
            'hint' => '`array_key_exists()` tells "loaded, but null" apart from "not loaded".',
            'checks' => [
                ['label' => 'Not loaded', 'code' => "return expect_same(bookResource(['id' => 1, 'title' => 'Dune']), ['id' => 1, 'title' => 'Dune']);"],
                ['label' => 'Loaded', 'code' => "return expect_same(bookResource(['id' => 1, 'title' => 'Dune', 'reviews_count' => 0, 'author' => ['name' => 'Frank Herbert', 'email' => 'frank@example.com']]), ['id' => 1, 'title' => 'Dune', 'author' => ['name' => 'Frank Herbert'], 'reviews_count' => 0]);"],
                ['label' => 'Loaded but empty', 'code' => "return expect_same(bookResource(['id' => 2, 'title' => 'Beowulf', 'author' => null]), ['id' => 2, 'title' => 'Beowulf', 'author' => null]);"],
            ],
        ],
        [
            'title' => 'Sparse fieldsets',
            'prompt' => 'Write `onlyFields(array $resource, ?string $fields): array` for `?fields=title,price`. Keep only the requested keys that exist, in the resource\'s order, but always keep `id`. Ignore spaces around names and unknown names. `null` or an empty string means all fields.',
            'starter_code' => <<<'PHP'
                <?php

                function onlyFields(array $resource, ?string $fields): array
                {
                    return array_intersect_key($resource, array_flip(explode(',', $fields)));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function onlyFields(array $resource, ?string $fields): array
                {
                    if ($fields === null || trim($fields) === '') {
                        return $resource;
                    }

                    $wanted = array_map('trim', explode(',', $fields));
                    $wanted[] = 'id';

                    return array_intersect_key($resource, array_flip($wanted));
                }
                PHP,
            'hint' => '`array_intersect_key()` keeps the first array\'s order. Add `id` to the wanted list.',
            'checks' => [
                ['label' => 'Requested fields plus id', 'code' => "return expect_same(onlyFields(['id' => 7, 'title' => 'Dune', 'price' => 999, 'description' => 'Sand'], 'price, title,nope'), ['id' => 7, 'title' => 'Dune', 'price' => 999]);"],
                ['label' => 'Everything by default', 'code' => "\$book = ['id' => 7, 'title' => 'Dune']; return expect_same([onlyFields(\$book, null), onlyFields(\$book, ''), onlyFields(\$book, 'id')], [\$book, \$book, ['id' => 7]]);"],
            ],
        ],
        [
            'title' => 'Allowed includes',
            'prompt' => 'Write `parseIncludes(?string $include, array $allowed): array` for `?include=author,reviews.user`. Return the unique includes in the order given, adding the parents of nested ones before them (`reviews.user` needs `reviews`). Throw an `InvalidArgumentException` naming the first include that isn\'t in `$allowed` (`Include [x] is not allowed.`). Empty or `null` gives `[]`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseIncludes(?string $include, array $allowed): array
                {
                    return explode(',', (string) $include);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseIncludes(?string $include, array $allowed): array
                {
                    $result = [];
                    foreach (explode(',', (string) $include) as $name) {
                        $name = trim($name);
                        if ($name === '') {
                            continue;
                        }
                        if (! in_array($name, $allowed, true)) {
                            throw new InvalidArgumentException("Include [{$name}] is not allowed.");
                        }

                        $parts = explode('.', $name);
                        for ($i = 1; $i <= count($parts); $i++) {
                            $path = implode('.', array_slice($parts, 0, $i));
                            if (! in_array($path, $result, true)) {
                                $result[] = $path;
                            }
                        }
                    }

                    return $result;
                }
                PHP,
            'hint' => 'For `reviews.user`, add `reviews` and then `reviews.user`, skipping any already there.',
            'checks' => [
                ['label' => 'Parents come first', 'code' => "return expect_same(parseIncludes('author,reviews.user,author', ['author', 'reviews', 'reviews.user']), ['author', 'reviews', 'reviews.user']);"],
                ['label' => 'Nothing requested', 'code' => "return expect_same([parseIncludes(null, ['author']), parseIncludes('', ['author'])], [[], []]);"],
                ['label' => 'Not allowed', 'code' => <<<'PHP'
                    try {
                        parseIncludes('author,password_resets', ['author']);
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), 'Include [password_resets] is not allowed.');
                    }
                    return 'An include outside the allow-list should throw.';
                    PHP],
            ],
        ],
        [
            'title' => 'snake_case everywhere',
            'prompt' => 'Write `snakeKeys(array $data): array`, which converts every string key from camelCase to snake_case (`publishedAt` to `published_at`, `isbn13` stays), all the way down through nested arrays. Integer keys stay as they are.',
            'starter_code' => <<<'PHP'
                <?php

                function snakeKeys(array $data): array
                {
                    $result = [];
                    foreach ($data as $key => $value) {
                        $result[strtolower($key)] = $value;
                    }

                    return $result;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function snakeKeys(array $data): array
                {
                    $result = [];
                    foreach ($data as $key => $value) {
                        if (is_string($key)) {
                            $key = strtolower(preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $key));
                        }
                        $result[$key] = is_array($value) ? snakeKeys($value) : $value;
                    }

                    return $result;
                }
                PHP,
            'hint' => 'Put an underscore before each capital letter that follows a lowercase letter or digit, lower-case the key, and recurse into arrays.',
            'checks' => [
                ['label' => 'Converts keys', 'code' => "return expect_same(snakeKeys(['id' => 1, 'publishedAt' => 'x', 'isbn13' => '9', 'authorFullName' => 'y']), ['id' => 1, 'published_at' => 'x', 'isbn13' => '9', 'author_full_name' => 'y']);"],
                ['label' => 'All the way down', 'code' => "return expect_same(snakeKeys(['data' => [['bookId' => 1, 'reviewCount' => 2], ['bookId' => 2, 'tags' => ['sciFi', 'classic']]]]), ['data' => [['book_id' => 1, 'review_count' => 2], ['book_id' => 2, 'tags' => ['sciFi', 'classic']]]]);"],
            ],
        ],
    ],
];
