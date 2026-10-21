<?php

// Lesson 1 of the Laravel testing course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'http-tests',
    'title' => 'HTTP tests',
    'summary' => 'Feature tests that send requests through the whole app: PHPUnit and Pest syntax, get/post/getJson, response assertions such as assertSee and assertJsonPath, and how assertions work inside.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try another status or path.
        $response = ['status' => 200, 'headers' => ['Content-Type' => 'application/json'], 'body' => '{"data":[{"title":"Dune"}]}'];

        if ($response['status'] !== 200) {
            throw new RuntimeException("Expected response status code [200] but received {$response['status']}.");
        }

        $json = json_decode($response['body'], true);
        echo 'First title: ' . $json['data'][0]['title'];
        PHP,

    'quiz' => [
        [
            'question' => 'What does `$this->get(\'/books\')` do in a Laravel feature test?',
            'options' => [
                'Starts a web server and requests the page over the network',
                'Builds a request in memory and sends it through the app, returning the response to check',
                'Calls the controller method directly, skipping middleware',
                'Reads the Blade file',
            ],
            'correct_option' => 1,
            'explanation' => 'Routing, middleware, the controller and views all run, just without a network.',
        ],
        [
            'question' => 'Where do tests that boot the framework and make requests usually go?',
            'options' => ['`tests/Unit`', '`tests/Feature`', '`app/Tests`', '`routes/tests.php`'],
            'correct_option' => 1,
            'explanation' => '`tests/Unit` is for code that doesn\'t need the framework.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $html = '<p>Fish &amp; Chips</p>';

                echo str_contains($html, htmlspecialchars('Fish & Chips')) ? 'seen' : 'missing';
                echo ' ';
                echo str_contains($html, 'Fish & Chips') ? 'seen' : 'missing';
                PHP,
            'options' => ['`seen seen`', '`seen missing`', '`missing seen`', '`missing missing`'],
            'correct_option' => 1,
            'explanation' => 'That\'s why `assertSee()` escapes the text first: it finds what the page shows.',
        ],
        [
            'question' => 'What does `assertSee(\'<strong>\', false)` do differently?',
            'options' => [
                'It checks the text is not on the page',
                'It searches the raw HTML without escaping, so it can find tags',
                'It ignores case',
                'It only checks the first line',
            ],
            'correct_option' => 1,
            'explanation' => 'The second argument turns escaping off.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $json = ['data' => [['name' => 'Dune'], ['name' => 'Emma']], 'meta' => ['total' => 2]];

                $value = $json;
                foreach (explode('.', 'data.1.name') as $key) {
                    $value = $value[$key];
                }

                echo $value;
                PHP,
            'options' => ['`Dune`', '`Emma`', '`2`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`data`, then item `1` (the second), then `name`: the walk `assertJsonPath()` does.',
        ],
        [
            'question' => 'A browser form posts invalid data. Which assertion checks that the title failed validation?',
            'options' => ['`assertJsonPath(\'errors.title\')`', '`assertSessionHasErrors([\'title\'])`', '`assertNotFound()`', '`assertSee(\'title\')`'],
            'correct_option' => 1,
            'explanation' => 'A failed form validation redirects back with the errors in the session.',
        ],
        [
            'question' => 'What does `postJson(\'/api/books\', [])` get back when validation fails?',
            'options' => [
                'A redirect back with errors in the session',
                'A 422 response with the messages under `errors` in the JSON',
                'A 500 error',
                'A 200 response with `false`',
            ],
            'correct_option' => 1,
            'explanation' => 'JSON requests get JSON errors: `assertUnprocessable()` and `assertJsonValidationErrors([\'title\'])`.',
        ],
        [
            'question' => 'In Pest, what is `it(\'creates a book\', function () { ... })`?',
            'options' => [
                'A helper that creates a book',
                'A test, described as "it creates a book", whose body is the closure',
                'A route definition',
                'A factory',
            ],
            'correct_option' => 1,
            'explanation' => '`it()` and `test()` define tests; inside, `$this` is the usual Laravel test case.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Seen on the page',
            'prompt' => 'Write `assertSee(string $body, string $text, bool $escape = true): void`. It throws a `RuntimeException` with the message `Expected to see [TEXT] in the response.` when the text isn\'t in the body. With `$escape` (the default), look for the text as `htmlspecialchars()` would write it; with `false`, look for it exactly.',
            'starter_code' => <<<'PHP'
                <?php

                function assertSee(string $body, string $text, bool $escape = true): void
                {
                    if (! str_contains($body, $text)) {
                        throw new RuntimeException('Not found');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertSee(string $body, string $text, bool $escape = true): void
                {
                    $needle = $escape ? htmlspecialchars($text) : $text;

                    if (! str_contains($body, $needle)) {
                        throw new RuntimeException("Expected to see [{$text}] in the response.");
                    }
                }
                PHP,
            'hint' => 'Work out the needle first: `htmlspecialchars($text)` or `$text`. The message uses the text as given.',
            'checks' => [
                ['label' => 'Finds escaped text', 'code' => "assertSee('<h1>Tom &amp; Jerry</h1>', 'Tom & Jerry'); assertSee('<p>Dune</p>', 'Dune'); return true;"],
                ['label' => 'Throws when the text is missing', 'code' => <<<'PHP'
                    try {
                        assertSee('<p>Dune</p>', 'Emma');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Expected to see [Emma] in the response.');
                    }
                    return 'Nothing was thrown for missing text.';
                    PHP],
                ['label' => 'Raw mode finds tags', 'code' => <<<'PHP'
                    assertSee('<h1>Dune</h1>', '<h1>', false);
                    return expect_throws(RuntimeException::class, fn () => assertSee('<h1>Dune</h1>', '<h1>'));
                    PHP],
            ],
        ],
        [
            'title' => 'Follow the path',
            'prompt' => 'Write `jsonPath(array $json, string $path): mixed`, which follows a dot path such as `data.0.title` through nested arrays and returns the value. If any step is missing, throw an `OutOfBoundsException` with the message `No value at [PATH].` A key whose value is `null` exists, so return `null` for it.',
            'starter_code' => <<<'PHP'
                <?php

                function jsonPath(array $json, string $path): mixed
                {
                    $value = $json;
                    foreach (explode('.', $path) as $key) {
                        $value = $value[$key];
                    }

                    return $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function jsonPath(array $json, string $path): mixed
                {
                    $value = $json;
                    foreach (explode('.', $path) as $key) {
                        if (! is_array($value) || ! array_key_exists($key, $value)) {
                            throw new OutOfBoundsException("No value at [{$path}].");
                        }
                        $value = $value[$key];
                    }

                    return $value;
                }
                PHP,
            'hint' => 'Before each step, check the current value is an array and `array_key_exists()` the key (`isset()` would say no for `null`).',
            'checks' => [
                ['label' => 'Follows paths', 'code' => <<<'PHP'
                    $json = ['data' => [['title' => 'Dune', 'author' => ['name' => 'Herbert']]], 'meta' => ['total' => 1]];
                    return expect_same([jsonPath($json, 'data.0.title'), jsonPath($json, 'data.0.author.name'), jsonPath($json, 'meta.total')], ['Dune', 'Herbert', 1]);
                    PHP],
                ['label' => 'null is a value', 'code' => "return expect_same(jsonPath(['data' => ['deleted_at' => null]], 'data.deleted_at'), null);"],
                ['label' => 'Missing steps throw', 'code' => <<<'PHP'
                    $json = ['data' => [['title' => 'Dune']]];
                    foreach (['data.1.title', 'data.0.price', 'data.0.title.x'] as $path) {
                        try {
                            jsonPath($json, $path);
                            return "Nothing was thrown for {$path}.";
                        } catch (OutOfBoundsException $e) {
                            if ($e->getMessage() !== "No value at [{$path}].") return 'The message is: ' . $e->getMessage();
                        }
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Sent elsewhere',
            'prompt' => 'A response is an array like `[\'status\' => 302, \'headers\' => [\'Location\' => \'/books\'], \'body\' => \'\']`. Write `assertRedirect(array $response, string $to): void`. It throws a `RuntimeException` unless the status is one of 301, 302, 303, 307 or 308 and the `Location` header equals `$to`. Header names are case-insensitive, so `location` counts too.',
            'starter_code' => <<<'PHP'
                <?php

                function assertRedirect(array $response, string $to): void
                {
                    if ($response['headers']['Location'] !== $to) {
                        throw new RuntimeException("Not redirected to {$to}.");
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertRedirect(array $response, string $to): void
                {
                    if (! in_array($response['status'], [301, 302, 303, 307, 308], true)) {
                        throw new RuntimeException("Response status code [{$response['status']}] is not a redirect status code.");
                    }

                    $headers = array_change_key_case($response['headers'], CASE_LOWER);
                    $location = $headers['location'] ?? null;

                    if ($location !== $to) {
                        throw new RuntimeException("Expected a redirect to [{$to}] but got [" . ($location ?? 'nothing') . '].');
                    }
                }
                PHP,
            'hint' => 'Check the status with `in_array()`, then find the header with `array_change_key_case()`.',
            'checks' => [
                ['label' => 'Passes for redirects', 'code' => <<<'PHP'
                    assertRedirect(['status' => 302, 'headers' => ['Location' => '/books'], 'body' => ''], '/books');
                    assertRedirect(['status' => 303, 'headers' => ['location' => '/login'], 'body' => ''], '/login');
                    return true;
                    PHP],
                ['label' => 'Needs a redirect status', 'code' => "return expect_throws(RuntimeException::class, fn () => assertRedirect(['status' => 200, 'headers' => ['Location' => '/books'], 'body' => ''], '/books'));"],
                ['label' => 'Needs the right Location', 'code' => <<<'PHP'
                    $wrong = expect_throws(RuntimeException::class, fn () => assertRedirect(['status' => 302, 'headers' => ['Location' => '/home'], 'body' => ''], '/books'));
                    if ($wrong !== true) return $wrong;
                    return expect_throws(RuntimeException::class, fn () => assertRedirect(['status' => 302, 'headers' => [], 'body' => ''], '/books'));
                    PHP],
            ],
        ],
        [
            'title' => 'Which fields failed',
            'prompt' => 'After a failed form submit, the session holds the errors: `[\'errors\' => [\'title\' => [\'The title field is required.\']]]`. Write `assertSessionHasErrors(array $session, array $keys): void`, which throws a `RuntimeException` unless every key has at least one error. The message lists the missing keys, in the order given: `Missing errors for: title, price`.',
            'starter_code' => <<<'PHP'
                <?php

                function assertSessionHasErrors(array $session, array $keys): void
                {
                    if (empty($session['errors'])) {
                        throw new RuntimeException('No errors');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertSessionHasErrors(array $session, array $keys): void
                {
                    $errors = $session['errors'] ?? [];
                    $missing = array_values(array_filter($keys, fn ($key) => empty($errors[$key])));

                    if ($missing !== []) {
                        throw new RuntimeException('Missing errors for: ' . implode(', ', $missing));
                    }
                }
                PHP,
            'hint' => 'Filter the keys down to those with no errors (`empty()` covers missing and empty lists), then throw if any are left.',
            'checks' => [
                ['label' => 'Passes when every key has errors', 'code' => "assertSessionHasErrors(['errors' => ['title' => ['Required.'], 'price' => ['Too low.']]], ['title', 'price']); assertSessionHasErrors(['errors' => ['title' => ['Required.']]], []); return true;"],
                ['label' => 'Names the missing keys', 'code' => <<<'PHP'
                    try {
                        assertSessionHasErrors(['errors' => ['year' => ['Bad year.'], 'price' => []]], ['title', 'year', 'price']);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Missing errors for: title, price');
                    }
                    return 'Nothing was thrown.';
                    PHP],
                ['label' => 'No errors at all', 'code' => "return expect_throws(RuntimeException::class, fn () => assertSessionHasErrors([], ['title']));"],
            ],
        ],
        [
            'title' => 'A fluent response',
            'prompt' => 'Write a `TestResponse` class built from `(int $status, array $headers, string $body)`. Give it `assertStatus(int $status)`, `assertOk()` (status 200) and `assertHeader(string $name, string $value)` (case-insensitive name), each returning `$this` so they chain and throwing a `RuntimeException` on failure, plus `json(): array`, which decodes the body.',
            'starter_code' => <<<'PHP'
                <?php

                class TestResponse
                {
                    public function __construct(public int $status, public array $headers, public string $body) {}

                    public function assertOk(): void
                    {
                        if ($this->status !== 200) {
                            throw new RuntimeException('Not OK');
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TestResponse
                {
                    public function __construct(public int $status, public array $headers, public string $body) {}

                    public function assertStatus(int $status): static
                    {
                        if ($this->status !== $status) {
                            throw new RuntimeException("Expected response status code [{$status}] but received {$this->status}.");
                        }

                        return $this;
                    }

                    public function assertOk(): static
                    {
                        return $this->assertStatus(200);
                    }

                    public function assertHeader(string $name, string $value): static
                    {
                        $headers = array_change_key_case($this->headers, CASE_LOWER);
                        $actual = $headers[strtolower($name)] ?? null;

                        if ($actual !== $value) {
                            throw new RuntimeException("Header [{$name}] was [" . ($actual ?? 'missing') . "], expected [{$value}].");
                        }

                        return $this;
                    }

                    public function json(): array
                    {
                        return json_decode($this->body, true);
                    }
                }
                PHP,
            'hint' => 'Each assertion ends with `return $this;`, and `assertOk()` can simply call `assertStatus(200)`.',
            'checks' => [
                ['label' => 'Assertions chain', 'code' => <<<'PHP'
                    $response = new TestResponse(200, ['Content-Type' => 'application/json'], '{"title":"Dune"}');
                    $same = $response->assertOk()->assertStatus(200)->assertHeader('content-type', 'application/json');
                    return $same === $response || 'Each assertion should return the same response.';
                    PHP],
                ['label' => 'Failures throw', 'code' => <<<'PHP'
                    $response = new TestResponse(404, ['Content-Type' => 'text/html'], 'Not Found');
                    foreach ([fn () => $response->assertOk(), fn () => $response->assertStatus(200), fn () => $response->assertHeader('Content-Type', 'application/json'), fn () => $response->assertHeader('X-Missing', 'yes')] as $assertion) {
                        $result = expect_throws(RuntimeException::class, $assertion);
                        if ($result !== true) return $result;
                    }
                    return true;
                    PHP],
                ['label' => 'json() decodes the body', 'code' => "return expect_same((new TestResponse(200, [], '{\"data\":[{\"title\":\"Dune\"}]}'))->json(), ['data' => [['title' => 'Dune']]]);"],
            ],
        ],
        [
            'title' => 'How many items',
            'prompt' => 'Write `assertJsonCount(array $json, int $count, ?string $path = null): void`. Count the items at the dot path (the whole array when `$path` is `null`) and throw a `RuntimeException` with `Expected N items at [PATH] but found M.` (`PATH` is `root` for `null`) when the count differs, or when the value there isn\'t an array (counting it as 0). A missing path counts as 0 too.',
            'starter_code' => <<<'PHP'
                <?php

                function assertJsonCount(array $json, int $count, ?string $path = null): void
                {
                    if (count($json) !== $count) {
                        throw new RuntimeException('Wrong count');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertJsonCount(array $json, int $count, ?string $path = null): void
                {
                    $value = $json;
                    foreach ($path === null ? [] : explode('.', $path) as $key) {
                        $value = is_array($value) && array_key_exists($key, $value) ? $value[$key] : null;
                    }

                    $actual = is_array($value) ? count($value) : 0;
                    if ($actual !== $count) {
                        $where = $path ?? 'root';
                        throw new RuntimeException("Expected {$count} items at [{$where}] but found {$actual}.");
                    }
                }
                PHP,
            'hint' => 'Walk the path as in "Follow the path", but let a missing step become `null` instead of throwing. Then count only if it\'s an array.',
            'checks' => [
                ['label' => 'Counts at a path', 'code' => <<<'PHP'
                    $json = ['data' => [['id' => 1], ['id' => 2], ['id' => 3]], 'meta' => ['total' => 3]];
                    assertJsonCount($json, 3, 'data');
                    assertJsonCount($json, 2);
                    assertJsonCount(['data' => [['tags' => ['a', 'b']]]], 2, 'data.0.tags');
                    return true;
                    PHP],
                ['label' => 'Explains a wrong count', 'code' => <<<'PHP'
                    try {
                        assertJsonCount(['data' => [['id' => 1]]], 3, 'data');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Expected 3 items at [data] but found 1.');
                    }
                    return 'Nothing was thrown for the wrong count.';
                    PHP],
                ['label' => 'Missing paths and non-arrays', 'code' => <<<'PHP'
                    assertJsonCount(['data' => []], 0, 'data.0.tags');
                    $result = expect_throws(RuntimeException::class, fn () => assertJsonCount(['meta' => ['total' => 3]], 3, 'meta.total'));
                    if ($result !== true) return $result;
                    try {
                        assertJsonCount([1, 2], 1);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Expected 1 items at [root] but found 2.');
                    }
                    return 'Nothing was thrown at the root.';
                    PHP],
            ],
        ],
    ],
];
