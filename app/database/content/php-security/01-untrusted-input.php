<?php

// Lesson 1 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'untrusted-input',
    'title' => 'Untrusted input',
    'summary' => 'Treating everything from outside as a guess: allow-lists, checking the shape of numbers, loose comparisons and JSON types, validating whole requests and normalising first.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try other values in $inputs.
        $inputs = ['3', '3abc', '0', '-2', '1e1', '999999'];

        foreach ($inputs as $input) {
            $page = (int) $input;
            echo "'$input' becomes page $page" . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Which of these is input your PHP code should not trust?',
            'options' => [
                'Only form fields',
                'Form fields, query strings, cookies, headers, uploaded file names and JSON bodies',
                'Only data from logged-out visitors',
                'Nothing, if the site uses HTTPS',
            ],
            'correct_option' => 1,
            'explanation' => 'Everything in a request is chosen by whoever sends it, including cookies and headers. HTTPS only stops others reading it on the way.',
        ],
        [
            'question' => 'Why is an allow-list safer than a deny-list?',
            'options' => [
                'It runs faster',
                'It names exactly what is acceptable, so anything unexpected is rejected, while a deny-list only stops the attacks you thought of',
                'Deny-lists are not allowed in PHP',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Attackers look for what a deny-list forgot. An allow-list has nothing to forget.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                var_dump((int) '12abc', (int) 'abc', (int) ' 7');
                PHP,
            'options' => [
                '`int(12)`, `int(0)`, `int(7)`',
                'An error for each',
                '`int(0)`, `int(0)`, `int(0)`',
                '`int(12)`, `NULL`, `int(7)`',
            ],
            'correct_option' => 0,
            'explanation' => 'Casting never complains; it quietly turns bad input into a number. That\'s why you check the shape with a pattern first.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $stored = '0e462097431906509019562988736854';
                $given = '0e830400451993494058024219903391';

                var_dump($given == $stored, $given === $stored);
                PHP,
            'options' => [
                '`bool(false)`, `bool(false)`',
                '`bool(true)`, `bool(false)`',
                '`bool(true)`, `bool(true)`',
                '`bool(false)`, `bool(true)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Both strings look like 0 × 10^n, so `==` compares them as the number 0. `===` compares the strings themselves.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $input = json_decode('{"role": true}', true);

                var_dump(
                    in_array($input['role'], ['admin', 'editor']),
                    in_array($input['role'], ['admin', 'editor'], true),
                );
                PHP,
            'options' => [
                '`bool(false)`, `bool(false)`',
                '`bool(true)`, `bool(false)`',
                '`bool(true)`, `bool(true)`',
                'An error: `true` is not a string',
            ],
            'correct_option' => 1,
            'explanation' => 'Loosely, `true == \'admin\'` is true. JSON can send booleans where you expected strings, so compare strictly.',
        ],
        [
            'question' => 'A request to update an order contains `book_id`, `quantity` and `price`. What should your code do with `price`?',
            'options' => [
                'Use it, since the visitor sent it',
                'Ignore it and take the price from your own database',
                'Use it only if it\'s a positive number',
                'Round it to two decimal places',
            ],
            'correct_option' => 1,
            'explanation' => 'Anything the visitor controls can be changed to `0.01`. Values that matter come from your side.',
        ],
        [
            'question' => 'Why normalise input (trim, lower-case) before validating and storing it?',
            'options' => [
                'It saves space in the database',
                'So different spellings of the same value can\'t slip past a check or create duplicates',
                'Because validation only works on lower case',
                'It hides the input from attackers',
            ],
            'correct_option' => 1,
            'explanation' => '`Ada@Example.com` and `ada@example.com ` should be one account, and checked as one value.',
        ],
        [
            'question' => 'What should a production error page show when something fails inside the app?',
            'options' => [
                'The full stack trace, to help users report it',
                'A general message, with the details only in your logs',
                'The SQL query that failed',
                'The server\'s file paths',
            ],
            'correct_option' => 1,
            'explanation' => 'Details help attackers map your code. In Laravel, keep `APP_DEBUG=false` in production.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Allow-list the sort',
            'prompt' => 'Write `sortColumn(mixed $input): string`. It returns `$input` if it is exactly one of `title`, `author` or `price`, and `title` for anything else, including `null`, other types and different capitalisation.',
            'starter_code' => <<<'PHP'
                <?php

                function sortColumn(mixed $input): string
                {
                    return (string) $input;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sortColumn(mixed $input): string
                {
                    return in_array($input, ['title', 'author', 'price'], true) ? $input : 'title';
                }
                PHP,
            'hint' => '`in_array($input, [...], true)` compares strictly, so only those exact strings get through.',
            'checks' => [
                ['label' => 'Allowed columns pass', 'code' => "return expect_same([sortColumn('title'), sortColumn('author'), sortColumn('price')], ['title', 'author', 'price']);"],
                ['label' => 'Attacks fall back to title', 'code' => "return expect_same(sortColumn('price DESC, (SELECT password FROM users)'), 'title');"],
                ['label' => 'Other types fall back too', 'code' => "return expect_same([sortColumn(null), sortColumn(true), sortColumn(['price']), sortColumn(0)], ['title', 'title', 'title', 'title']);"],
                ['label' => 'Exact spelling only', 'code' => "return expect_same([sortColumn('Price'), sortColumn(' price')], ['title', 'title']);"],
            ],
        ],
        [
            'title' => 'A page number',
            'prompt' => 'Write `pageNumber(mixed $input, int $lastPage): int`. Accept an integer, or a string of digits only, between 1 and `$lastPage`; return it as an `int`. Anything else, such as `\'2abc\'`, `\' 2\'`, `\'1e1\'`, `\'-1\'`, `0`, `2.5` or a number past the last page, gives page 1.',
            'starter_code' => <<<'PHP'
                <?php

                function pageNumber(mixed $input, int $lastPage): int
                {
                    return (int) $input;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pageNumber(mixed $input, int $lastPage): int
                {
                    if (is_string($input) && preg_match('/^\d+$/', $input) === 1) {
                        $input = (int) $input;
                    }

                    return is_int($input) && $input >= 1 && $input <= $lastPage ? $input : 1;
                }
                PHP,
            'hint' => 'Turn a string into an int only if it matches `/^\d+$/`. Then accept only an `int` within the range.',
            'checks' => [
                ['label' => 'Good pages pass', 'code' => "return expect_same([pageNumber('3', 10), pageNumber(7, 10), pageNumber('10', 10)], [3, 7, 10]);"],
                ['label' => 'Messy strings give page 1', 'code' => "return expect_same(array_map(fn (\$i) => pageNumber(\$i, 10), ['2abc', ' 2', '1e1', '-1', '', '0x1']), [1, 1, 1, 1, 1, 1]);"],
                ['label' => 'Out of range gives page 1', 'code' => "return expect_same([pageNumber('0', 10), pageNumber(11, 10), pageNumber('999999', 10)], [1, 1, 1]);"],
                ['label' => 'Other types give page 1', 'code' => "return expect_same([pageNumber(2.5, 10), pageNumber(true, 10), pageNumber(null, 10), pageNumber(['2'], 10)], [1, 1, 1, 1]);"],
            ],
        ],
        [
            'title' => 'No magic hashes',
            'prompt' => '`tokenMatches()` compares a reset token from the URL with the stored one using `==`, so two different tokens that look like `0e` numbers match. Fix it so only an identical string matches, using `hash_equals()`. Anything that isn\'t a string never matches.',
            'starter_code' => <<<'PHP'
                <?php

                function tokenMatches(mixed $given, string $stored): bool
                {
                    return $given == $stored;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function tokenMatches(mixed $given, string $stored): bool
                {
                    return is_string($given) && hash_equals($stored, $given);
                }
                PHP,
            'hint' => '`hash_equals($known, $user)` needs two strings, so check `is_string($given)` first.',
            'checks' => [
                ['label' => 'The right token matches', 'code' => "return expect_same(tokenMatches('a3f9c2', 'a3f9c2'), true);"],
                ['label' => '0e tokens don\'t match each other', 'code' => "return expect_same(tokenMatches('0e830400451993494058024219903391', '0e462097431906509019562988736854'), false);"],
                ['label' => 'Other types never match', 'code' => "return expect_same([tokenMatches(true, 'a3f9c2'), tokenMatches(0, '0e1'), tokenMatches(null, '')], [false, false, false]);"],
                ['label' => 'hash_equals() is used', 'code' => "return str_contains(learnp_code(), 'hash_equals') ?: 'Use hash_equals() to compare secrets.';"],
            ],
        ],
        [
            'title' => 'Roles from JSON',
            'prompt' => 'Write `requestedRole(string $json): string`. The JSON body has a `role` that may be `reader` or `editor`. Return it if it is exactly one of those **strings**; return `reader` if it\'s missing, another value, another type (such as `true` or `["editor"]`), or the JSON is invalid. Never return `admin`.',
            'starter_code' => <<<'PHP'
                <?php

                function requestedRole(string $json): string
                {
                    $data = json_decode($json, true);

                    return in_array($data['role'], ['reader', 'editor']) ? $data['role'] : 'reader';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function requestedRole(string $json): string
                {
                    $data = json_decode($json, true);
                    $role = is_array($data) ? ($data['role'] ?? null) : null;

                    return in_array($role, ['reader', 'editor'], true) ? $role : 'reader';
                }
                PHP,
            'hint' => '`json_decode()` returns `null` for invalid JSON, and the decoded value might not even be an array. Read `role` safely, then compare strictly.',
            'checks' => [
                ['label' => 'Allowed roles', 'code' => "return expect_same([requestedRole('{\"role\":\"editor\"}'), requestedRole('{\"role\":\"reader\"}')], ['editor', 'reader']);"],
                ['label' => 'true is not a role', 'code' => "return expect_same(requestedRole('{\"role\":true}'), 'reader');"],
                ['label' => 'Other values and types', 'code' => "return expect_same([requestedRole('{\"role\":\"admin\"}'), requestedRole('{\"role\":[\"editor\"]}'), requestedRole('{\"role\":0}')], ['reader', 'reader', 'reader']);"],
                ['label' => 'Missing role or bad JSON', 'code' => "return expect_same([requestedRole('{}'), requestedRole('not json'), requestedRole('\"editor\"'), requestedRole('[]')], ['reader', 'reader', 'reader', 'reader']);"],
            ],
        ],
        [
            'title' => 'Validate an order',
            'prompt' => 'Write `parseOrder(string $json): array`. The body must be a JSON object with `book_id`, an integer of at least 1, and `quantity`, an integer from 1 to 10. Return just those two fields. If the JSON is invalid or a field is missing or wrong, throw `InvalidArgumentException` with the message `book_id must be a positive whole number.` or `quantity must be between 1 and 10.` (checking `book_id` first), or `The body must be a JSON object.` Extra fields like `price` are dropped.',
            'starter_code' => <<<'PHP'
                <?php

                function parseOrder(string $json): array
                {
                    return json_decode($json, true);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseOrder(string $json): array
                {
                    $data = json_decode($json, true);
                    if (! is_array($data) || array_is_list($data) && $data !== []) {
                        throw new InvalidArgumentException('The body must be a JSON object.');
                    }

                    $bookId = $data['book_id'] ?? null;
                    if (! is_int($bookId) || $bookId < 1) {
                        throw new InvalidArgumentException('book_id must be a positive whole number.');
                    }

                    $quantity = $data['quantity'] ?? null;
                    if (! is_int($quantity) || $quantity < 1 || $quantity > 10) {
                        throw new InvalidArgumentException('quantity must be between 1 and 10.');
                    }

                    return ['book_id' => $bookId, 'quantity' => $quantity];
                }
                PHP,
            'hint' => 'Check `is_array()` on the decoded value, then `is_int()` and the range of each field. Build the result from the checked variables, not from `$data`.',
            'checks' => [
                ['label' => 'A good order, extra fields dropped', 'code' => "return expect_same(parseOrder('{\"book_id\": 7, \"quantity\": 2, \"price\": 0.01}'), ['book_id' => 7, 'quantity' => 2]);"],
                ['label' => 'Bad book ids', 'code' => <<<'PHP'
                    foreach (['{"book_id": "7", "quantity": 1}', '{"book_id": 0, "quantity": 1}', '{"quantity": 1}', '{"book_id": 1.5, "quantity": 1}'] as $body) {
                        try { parseOrder($body); return "No exception for $body"; }
                        catch (InvalidArgumentException $e) { if ($e->getMessage() !== 'book_id must be a positive whole number.') return "Wrong message for $body: " . $e->getMessage(); }
                    }
                    return true;
                    PHP],
                ['label' => 'Bad quantities', 'code' => <<<'PHP'
                    foreach (['{"book_id": 7, "quantity": 0}', '{"book_id": 7, "quantity": 11}', '{"book_id": 7, "quantity": "2"}', '{"book_id": 7}'] as $body) {
                        try { parseOrder($body); return "No exception for $body"; }
                        catch (InvalidArgumentException $e) { if ($e->getMessage() !== 'quantity must be between 1 and 10.') return "Wrong message for $body: " . $e->getMessage(); }
                    }
                    return true;
                    PHP],
                ['label' => 'Not an object', 'code' => <<<'PHP'
                    foreach (['nonsense', '"order"', '42', '[7, 2]'] as $body) {
                        try { parseOrder($body); return "No exception for $body"; }
                        catch (InvalidArgumentException $e) { if ($e->getMessage() !== 'The body must be a JSON object.') return "Wrong message for $body: " . $e->getMessage(); }
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Usernames',
            'prompt' => 'Write `normaliseUsername(string $input): ?string`. Trim spaces and lower-case it first; then accept it only if it is 3 to 20 characters of `a-z`, `0-9` and `_`. Return the normalised name, or `null` if it isn\'t acceptable. Then write `isTaken(string $input, array $existing): bool`, which normalises before comparing, so `\' Ada \'` is taken when `ada` exists.',
            'starter_code' => <<<'PHP'
                <?php

                function normaliseUsername(string $input): ?string
                {
                    return $input;
                }

                function isTaken(string $input, array $existing): bool
                {
                    return in_array($input, $existing, true);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function normaliseUsername(string $input): ?string
                {
                    $name = strtolower(trim($input));

                    return preg_match('/^[a-z0-9_]{3,20}$/', $name) === 1 ? $name : null;
                }

                function isTaken(string $input, array $existing): bool
                {
                    $name = normaliseUsername($input);

                    return $name !== null && in_array($name, $existing, true);
                }
                PHP,
            'hint' => 'Normalise with `strtolower(trim(...))`, then check with `preg_match(\'/^[a-z0-9_]{3,20}$/\', ...)`. Use `$` and `^` so the whole name must match.',
            'checks' => [
                ['label' => 'Names are normalised', 'code' => "return expect_same([normaliseUsername('  Ada_Lovelace '), normaliseUsername('reader42')], ['ada_lovelace', 'reader42']);"],
                ['label' => 'Bad names are rejected', 'code' => "return expect_same(array_map('normaliseUsername', ['ab', str_repeat('a', 21), 'ada lovelace', 'ada-l', 'adá', \"a\\nda\", '']), [null, null, null, null, null, null, null]);"],
                ['label' => 'Lengths 3 and 20 are fine', 'code' => "return expect_same([normaliseUsername('abc'), normaliseUsername(str_repeat('z', 20))], ['abc', str_repeat('z', 20)]);"],
                ['label' => 'isTaken() compares normalised names', 'code' => "\$existing = ['ada', 'bob_1'];\nreturn expect_same([isTaken(' Ada ', \$existing), isTaken('BOB_1', \$existing), isTaken('carol', \$existing), isTaken('a d a', \$existing)], [true, true, false, false]);"],
            ],
        ],
    ],
];
