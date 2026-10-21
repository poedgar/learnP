<?php

// Lesson 3 of the REST APIs course. The theory is in the .md file with the same name.

return [
    'slug' => 'errors-and-validation',
    'title' => 'Errors and validation',
    'summary' => 'Predictable errors: Laravel\'s 422 validation shape, nested field paths, when a request wants JSON, exceptions mapped to status codes, hiding internals in production, problem details, and which errors to retry.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add another failing field.
        $errors = [
            'title' => ['The title field is required.'],
        ];

        $body = [
            'message' => reset($errors)[0],
            'errors' => $errors,
        ];

        echo json_encode($body, JSON_PRETTY_PRINT);
        PHP,

    'quiz' => [
        [
            'question' => 'Which status does Laravel use when a JSON request fails validation?',
            'options' => ['`400`', '`422`', '`500`', '`302`'],
            'correct_option' => 1,
            'explanation' => 'With a body holding a `message` and an `errors` list per field.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $errors = ['title' => ['Required.'], 'price' => ['Too low.', 'Not a number.'], 'year' => ['Too old.']];

                $count = count(array_merge(...array_values($errors)));
                $more = $count - 1;

                echo reset($errors)[0] . " (and {$more} more errors)";
                PHP,
            'options' => ['`Required. (and 2 more errors)`', '`Required. (and 3 more errors)`', '`Required. (and 4 more errors)`', '`Required.`'],
            'correct_option' => 1,
            'explanation' => 'Four messages in all: the first one, and three more.',
        ],
        [
            'question' => 'In a 422 response, what does the error key `items.2.quantity` refer to?',
            'options' => [
                'The second item\'s quantity',
                'The quantity of the third item in the `items` array',
                'Two items\' quantities',
                'A field called "items.2.quantity"',
            ],
            'correct_option' => 1,
            'explanation' => 'Array indexes start at 0, and the path tells the client which line to highlight.',
        ],
        [
            'question' => 'Why should API clients send `Accept: application/json`?',
            'options' => [
                'It makes requests faster',
                'So Laravel renders errors (like validation failures) as JSON instead of redirects or HTML pages',
                'It is required for POST requests',
                'To get a 200 response',
            ],
            'correct_option' => 1,
            'explanation' => '`$request->expectsJson()` looks at that header.',
        ],
        [
            'question' => 'A controller calls `Book::findOrFail(999)` and there is no book 999. What does the client get?',
            'options' => ['`500 Server Error`', '`404 Not Found`', '`422`', '`200` with null'],
            'correct_option' => 1,
            'explanation' => '`ModelNotFoundException` becomes a 404.',
        ],
        [
            'question' => 'Why is `APP_DEBUG=true` dangerous in production?',
            'options' => [
                'It slows the app down a lot',
                'Error responses then include exception messages and stack traces, which can reveal paths, queries and secrets',
                'It turns off validation',
                'It disables the log',
            ],
            'correct_option' => 1,
            'explanation' => 'In production a 500 should say only "Server Error"; the details belong in the log.',
        ],
        [
            'question' => 'In a problem details response, what is the `type` field for?',
            'options' => [
                'The content type',
                'A URL identifying (and documenting) the kind of problem',
                'The exception class',
                'The HTTP method',
            ],
            'correct_option' => 1,
            'explanation' => 'A stable identifier is easier for programs to act on than a sentence.',
        ],
        [
            'question' => 'Which failed request is worth retrying automatically?',
            'options' => ['`422` validation error', '`403` forbidden', '`503` service unavailable', '`404` not found'],
            'correct_option' => 2,
            'explanation' => 'A 503 may succeed later; the 4xx errors will fail again until the request changes.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'The 422 body',
            'prompt' => 'Write `validationBody(array $errors): array` returning `[\'message\' => ..., \'errors\' => $errors]` in Laravel\'s style. The message is the first field\'s first message, followed by ` (and 1 more error)` or ` (and N more errors)` when there are other messages (counting every message of every field).',
            'starter_code' => <<<'PHP'
                <?php

                function validationBody(array $errors): array
                {
                    return ['message' => 'The given data was invalid.', 'errors' => $errors];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validationBody(array $errors): array
                {
                    $messages = array_merge(...array_values($errors));
                    $message = $messages[0];
                    $more = count($messages) - 1;

                    if ($more === 1) {
                        $message .= ' (and 1 more error)';
                    } elseif ($more > 1) {
                        $message .= " (and {$more} more errors)";
                    }

                    return ['message' => $message, 'errors' => $errors];
                }
                PHP,
            'hint' => 'Flatten all the messages with `array_merge(...array_values($errors))`, then count the rest.',
            'checks' => [
                ['label' => 'One error', 'code' => "return expect_same(validationBody(['title' => ['The title field is required.']]), ['message' => 'The title field is required.', 'errors' => ['title' => ['The title field is required.']]]);"],
                ['label' => 'More errors', 'code' => <<<'PHP'
                    $two = validationBody(['title' => ['Required.'], 'price' => ['Too low.']])['message'];
                    $four = validationBody(['title' => ['Required.'], 'price' => ['Too low.', 'Not a number.'], 'year' => ['Too old.']])['message'];
                    return expect_same([$two, $four], ['Required. (and 1 more error)', 'Required. (and 3 more errors)']);
                    PHP],
            ],
        ],
        [
            'title' => 'Order lines',
            'prompt' => 'Write `validateOrder(array $input): array` returning errors keyed by path. `items` must be a non-empty array (`items` => `The items field is required.`). For each item at index N: `items.N.book_id` must be a positive integer (`The book must be chosen.`), and `items.N.quantity` an integer from 1 to 10 (`The quantity must be between 1 and 10.`). Each key has a list with one message. Return `[]` when everything is valid.',
            'starter_code' => <<<'PHP'
                <?php

                function validateOrder(array $input): array
                {
                    $errors = [];
                    if (empty($input['items'])) {
                        $errors['items'] = ['The items field is required.'];
                    }

                    return $errors;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validateOrder(array $input): array
                {
                    $items = $input['items'] ?? null;
                    if (! is_array($items) || $items === []) {
                        return ['items' => ['The items field is required.']];
                    }

                    $errors = [];
                    foreach ($items as $i => $item) {
                        $bookId = $item['book_id'] ?? null;
                        if (! is_int($bookId) || $bookId < 1) {
                            $errors["items.{$i}.book_id"] = ['The book must be chosen.'];
                        }
                        $quantity = $item['quantity'] ?? null;
                        if (! is_int($quantity) || $quantity < 1 || $quantity > 10) {
                            $errors["items.{$i}.quantity"] = ['The quantity must be between 1 and 10.'];
                        }
                    }

                    return $errors;
                }
                PHP,
            'hint' => 'Loop with the index (`foreach ($items as $i => $item)`) and build keys like `"items.{$i}.quantity"`.',
            'checks' => [
                ['label' => 'Valid orders', 'code' => "return expect_same(validateOrder(['items' => [['book_id' => 1, 'quantity' => 2], ['book_id' => 7, 'quantity' => 10]]]), []);"],
                ['label' => 'Paths name the bad lines', 'code' => <<<'PHP'
                    return expect_same(validateOrder(['items' => [['book_id' => 1, 'quantity' => 2], ['book_id' => 0, 'quantity' => 11], ['quantity' => '3']]]), [
                        'items.1.book_id' => ['The book must be chosen.'],
                        'items.1.quantity' => ['The quantity must be between 1 and 10.'],
                        'items.2.book_id' => ['The book must be chosen.'],
                        'items.2.quantity' => ['The quantity must be between 1 and 10.'],
                    ]);
                    PHP],
                ['label' => 'No items', 'code' => "return expect_same([validateOrder([]), validateOrder(['items' => []]), validateOrder(['items' => 'Dune'])], array_fill(0, 3, ['items' => ['The items field is required.']]));"],
            ],
        ],
        [
            'title' => 'JSON or HTML',
            'prompt' => 'Write `wantsJson(array $headers, string $path): bool`. It is true when the path starts with `/api/` (or is exactly `/api`), or when the `Accept` header (any capitalisation of the name) contains `application/json` or a `+json` type such as `application/problem+json`.',
            'starter_code' => <<<'PHP'
                <?php

                function wantsJson(array $headers, string $path): bool
                {
                    return ($headers['Accept'] ?? '') === 'application/json';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function wantsJson(array $headers, string $path): bool
                {
                    if ($path === '/api' || str_starts_with($path, '/api/')) {
                        return true;
                    }

                    $accept = array_change_key_case($headers, CASE_LOWER)['accept'] ?? '';

                    return str_contains($accept, 'application/json') || str_contains($accept, '+json');
                }
                PHP,
            'hint' => 'Check the path first. Lower-case the header names with `array_change_key_case()`.',
            'checks' => [
                ['label' => 'API paths', 'code' => "return expect_same([wantsJson([], '/api/books'), wantsJson([], '/api'), wantsJson([], '/apiary'), wantsJson([], '/books')], [true, true, false, false]);"],
                ['label' => 'The Accept header', 'code' => "return expect_same([wantsJson(['accept' => 'application/json'], '/books'), wantsJson(['Accept' => 'text/html, application/json;q=0.9'], '/'), wantsJson(['ACCEPT' => 'application/problem+json'], '/'), wantsJson(['Accept' => 'text/html'], '/')], [true, true, true, false]);"],
            ],
        ],
        [
            'title' => 'Exceptions to responses',
            'prompt' => 'Write `renderException(Throwable $e, bool $debug): array` returning `[\'status\' => ..., \'body\' => [...]]`. `OutOfBoundsException` is a 404 with message `Not found.`; `DomainException` is a 409 whose message is the exception\'s; anything else is a 500 with message `Server Error`. When `$debug` is true, a 500 shows the real message instead and adds `\'exception\' => the class name`. The body is `[\'message\' => ...]` (plus `exception` in that case).',
            'starter_code' => <<<'PHP'
                <?php

                function renderException(Throwable $e, bool $debug): array
                {
                    return ['status' => 500, 'body' => ['message' => $e->getMessage(), 'exception' => $e::class]];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function renderException(Throwable $e, bool $debug): array
                {
                    if ($e instanceof OutOfBoundsException) {
                        return ['status' => 404, 'body' => ['message' => 'Not found.']];
                    }
                    if ($e instanceof DomainException) {
                        return ['status' => 409, 'body' => ['message' => $e->getMessage()]];
                    }
                    if ($debug) {
                        return ['status' => 500, 'body' => ['message' => $e->getMessage(), 'exception' => $e::class]];
                    }

                    return ['status' => 500, 'body' => ['message' => 'Server Error']];
                }
                PHP,
            'hint' => 'Check the specific classes with `instanceof` first; the 500 case comes last.',
            'checks' => [
                ['label' => 'Known exceptions', 'code' => "return expect_same([renderException(new OutOfBoundsException('Book 9'), false), renderException(new DomainException('Only 2 left.'), false)], [['status' => 404, 'body' => ['message' => 'Not found.']], ['status' => 409, 'body' => ['message' => 'Only 2 left.']]]);"],
                ['label' => 'Production hides details', 'code' => "return expect_same(renderException(new RuntimeException('DB password is hunter2'), false), ['status' => 500, 'body' => ['message' => 'Server Error']]);"],
                ['label' => 'Debug shows them', 'code' => "return expect_same(renderException(new RuntimeException('Connection refused'), true), ['status' => 500, 'body' => ['message' => 'Connection refused', 'exception' => 'RuntimeException']]);"],
            ],
        ],
        [
            'title' => 'Problem details',
            'prompt' => 'Write `problem(int $status, string $detail, ?string $type = null): array` returning `type` (`about:blank` when not given), `title`, `status` and `detail`, in that order. The title is the standard phrase: 400 `Bad Request`, 401 `Unauthorized`, 403 `Forbidden`, 404 `Not Found`, 409 `Conflict`, 422 `Unprocessable Content`, 429 `Too Many Requests`, 500 `Internal Server Error`. For any other status, throw an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function problem(int $status, string $detail, ?string $type = null): array
                {
                    return ['type' => $type, 'title' => 'Error', 'status' => $status, 'detail' => $detail];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function problem(int $status, string $detail, ?string $type = null): array
                {
                    $titles = [
                        400 => 'Bad Request',
                        401 => 'Unauthorized',
                        403 => 'Forbidden',
                        404 => 'Not Found',
                        409 => 'Conflict',
                        422 => 'Unprocessable Content',
                        429 => 'Too Many Requests',
                        500 => 'Internal Server Error',
                    ];

                    if (! isset($titles[$status])) {
                        throw new InvalidArgumentException("No title for status {$status}.");
                    }

                    return ['type' => $type ?? 'about:blank', 'title' => $titles[$status], 'status' => $status, 'detail' => $detail];
                }
                PHP,
            'hint' => 'A lookup array from status to title, and `??` for the default type.',
            'checks' => [
                ['label' => 'Builds the problem', 'code' => "return expect_same(problem(409, 'Only 2 copies of Dune are left.', 'https://api.test/problems/out-of-stock'), ['type' => 'https://api.test/problems/out-of-stock', 'title' => 'Conflict', 'status' => 409, 'detail' => 'Only 2 copies of Dune are left.']);"],
                ['label' => 'Defaults to about:blank', 'code' => "return expect_same([problem(404, 'No such book.')['type'], problem(429, 'Slow down.')['title'], problem(422, 'Bad data.')['title']], ['about:blank', 'Too Many Requests', 'Unprocessable Content']);"],
                ['label' => 'Unknown statuses', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => problem(418, 'Teapot'));"],
            ],
        ],
        [
            'title' => 'Worth retrying',
            'prompt' => 'Write `shouldRetry(?int $status, string $method, int $attempt, int $maxAttempts = 3): bool`. `$status` is `null` when no response arrived. Never retry once `$attempt` has reached `$maxAttempts`. Retry statuses 429, 502, 503 and 504 for any method. With no response, retry only idempotent methods (GET, HEAD, OPTIONS, PUT, DELETE; any case). Don\'t retry anything else.',
            'starter_code' => <<<'PHP'
                <?php

                function shouldRetry(?int $status, string $method, int $attempt, int $maxAttempts = 3): bool
                {
                    return $status === null || $status >= 500;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shouldRetry(?int $status, string $method, int $attempt, int $maxAttempts = 3): bool
                {
                    if ($attempt >= $maxAttempts) {
                        return false;
                    }

                    if ($status === null) {
                        return in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'], true);
                    }

                    return in_array($status, [429, 502, 503, 504], true);
                }
                PHP,
            'hint' => 'Check the attempt count first, then the "no response" case, then the list of statuses.',
            'checks' => [
                ['label' => 'Retryable statuses', 'code' => "return expect_same([shouldRetry(503, 'POST', 1), shouldRetry(429, 'GET', 2), shouldRetry(504, 'PATCH', 1), shouldRetry(500, 'GET', 1), shouldRetry(422, 'POST', 1), shouldRetry(404, 'GET', 1)], [true, true, true, false, false, false]);"],
                ['label' => 'No response', 'code' => "return expect_same([shouldRetry(null, 'get', 1), shouldRetry(null, 'PUT', 1), shouldRetry(null, 'POST', 1), shouldRetry(null, 'PATCH', 1)], [true, true, false, false]);"],
                ['label' => 'Gives up eventually', 'code' => "return expect_same([shouldRetry(503, 'GET', 3), shouldRetry(503, 'GET', 4, 5), shouldRetry(null, 'GET', 5, 5)], [false, true, false]);"],
            ],
        ],
    ],
];
