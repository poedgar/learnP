<?php

// Lesson 7 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'forms-and-validation',
    'title' => 'Forms and validation',
    'summary' => 'Handling forms the Laravel way: `@csrf`, `old()` and `@error`, `$request->validate()` and common rules, redirecting back with errors and old input, and `@method` for PUT and DELETE.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try an empty title or a negative price.
        $input = ['title' => 'Dune', 'price' => '-5'];

        $errors = [];
        if (trim($input['title'] ?? '') === '') {
            $errors['title'][] = 'The title field is required.';
        }
        if (! is_numeric($input['price'] ?? null) || $input['price'] < 0) {
            $errors['price'][] = 'The price field must be at least 0.';
        }

        print_r($errors ?: 'All good!');
        PHP,

    'quiz' => [
        [
            'question' => 'What does `old(\'title\')` give you in a form?',
            'options' => [
                'The title saved in the database',
                'What the visitor typed into the title field before a failed submit',
                'The previous version of the template',
                'The default title',
            ],
            'correct_option' => 1,
            'explanation' => 'Old input is flashed into the session on a failed submit, so the form can be filled in again.',
        ],
        [
            'question' => 'What happens when `$request->validate([...])` fails in a normal browser form?',
            'options' => [
                'It returns `false`',
                'It stops the action and redirects back to the form with the errors and old input',
                'It shows a 500 error',
                'It saves the valid fields',
            ],
            'correct_option' => 1,
            'explanation' => 'Code after `validate()` only runs when everything passed. JSON clients get a 422 response instead.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $values = ['Dune', '', '  ', null, '0', 0];

                $missing = array_filter($values, fn ($v) => $v === null || (is_string($v) && trim($v) === ''));

                echo count($missing);
                PHP,
            'options' => ['`2`', '`3`', '`4`', '`5`'],
            'correct_option' => 1,
            'explanation' => '`\'\'`, `\'  \'` and `null` are missing. `\'0\'` and `0` are real answers.',
        ],
        [
            'question' => 'What does the rule `confirmed` on a `password` field check?',
            'options' => [
                'That the password is strong',
                'That a `password_confirmation` field was sent with the same value',
                'That the user confirmed their email',
                'That the password was entered twice in the same field',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s for "type your password again" fields.',
        ],
        [
            'question' => 'For a text field, what does `max:255` limit?',
            'options' => ['The value as a number', 'The number of characters', 'The number of words', 'The file size'],
            'correct_option' => 1,
            'explanation' => '`min` and `max` measure length for text, the value for numbers and the count for arrays.',
        ],
        [
            'question' => 'Which input does Laravel never flash back to the form after a failed submit?',
            'options' => ['`email`', '`title`', '`password` and `password_confirmation`', 'Hidden fields'],
            'correct_option' => 2,
            'explanation' => 'Passwords aren\'t kept in the session or shown again, so the visitor types them again.',
        ],
        [
            'question' => 'How does a form send a `DELETE` request?',
            'options' => [
                '`<form method="DELETE">`',
                'It posts with a hidden `_method` field set to `DELETE`, written by `@method(\'DELETE\')`',
                'It can\'t; only JavaScript can',
                'With a `GET` and `?delete=1`',
            ],
            'correct_option' => 1,
            'explanation' => 'HTML forms only support GET and POST, so Laravel reads the real method from `_method`.',
        ],
        [
            'question' => 'What does `@error(\'title\') ... @enderror` show?',
            'options' => [
                'Every error on the page',
                'Its content, with `$message` set to the title field\'s first error, only if that field has errors',
                'A 404 page',
                'The old title',
            ],
            'correct_option' => 1,
            'explanation' => 'It keeps each message next to its own field.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'The real method',
            'prompt' => 'Write `effectiveMethod(string $method, array $post): string`. For a `POST` (any case) whose data has a `_method` of `PUT`, `PATCH` or `DELETE` (any case), return that method in upper case. Otherwise return the request\'s own method in upper case. A `GET` can never be turned into anything else.',
            'starter_code' => <<<'PHP'
                <?php

                function effectiveMethod(string $method, array $post): string
                {
                    return $post['_method'] ?? $method;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function effectiveMethod(string $method, array $post): string
                {
                    $method = strtoupper($method);
                    $spoofed = strtoupper((string) ($post['_method'] ?? ''));

                    if ($method === 'POST' && in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                        return $spoofed;
                    }

                    return $method;
                }
                PHP,
            'hint' => 'Upper-case both. Only replace the method when the request is a `POST` and `_method` is one of the three allowed values.',
            'checks' => [
                ['label' => 'Spoofed methods', 'code' => "return expect_same([effectiveMethod('POST', ['_method' => 'PUT']), effectiveMethod('post', ['_method' => 'delete']), effectiveMethod('POST', ['_method' => 'Patch'])], ['PUT', 'DELETE', 'PATCH']);"],
                ['label' => 'Plain requests', 'code' => "return expect_same([effectiveMethod('POST', []), effectiveMethod('get', [])], ['POST', 'GET']);"],
                ['label' => 'Only POST can be spoofed, and only to these', 'code' => "return expect_same([effectiveMethod('GET', ['_method' => 'DELETE']), effectiveMethod('POST', ['_method' => 'CONNECT']), effectiveMethod('POST', ['_method' => ['PUT']])], ['GET', 'POST', 'POST']);"],
            ],
        ],
        [
            'title' => 'Validate a book',
            'prompt' => 'Write `validateBook(array $input): array`, returning errors keyed by field (each a list of messages), or `[]` if everything passes. `title` is required (present and not just spaces) and at most 255 characters: `The title field is required.` / `The title field must not be greater than 255 characters.` `price` is required and must be numeric and at least 0: `The price field is required.` / `The price field must be a number.` / `The price field must be at least 0.` (only the first that applies).',
            'starter_code' => <<<'PHP'
                <?php

                function validateBook(array $input): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validateBook(array $input): array
                {
                    $errors = [];

                    $title = $input['title'] ?? null;
                    if (! is_string($title) || trim($title) === '') {
                        $errors['title'][] = 'The title field is required.';
                    } elseif (strlen($title) > 255) {
                        $errors['title'][] = 'The title field must not be greater than 255 characters.';
                    }

                    $price = $input['price'] ?? null;
                    if ($price === null || (is_string($price) && trim($price) === '')) {
                        $errors['price'][] = 'The price field is required.';
                    } elseif (! is_numeric($price)) {
                        $errors['price'][] = 'The price field must be a number.';
                    } elseif ($price < 0) {
                        $errors['price'][] = 'The price field must be at least 0.';
                    }

                    return $errors;
                }
                PHP,
            'hint' => 'Check each field with `if`/`elseif` so only the first problem is reported. `is_numeric()` accepts strings like `\'9.99\'`, which is what forms send.',
            'checks' => [
                ['label' => 'Good input passes', 'code' => "return expect_same([validateBook(['title' => 'Dune', 'price' => '9.99']), validateBook(['title' => 'Free book', 'price' => '0'])], [[], []]);"],
                ['label' => 'Missing fields', 'code' => "return expect_same(validateBook(['title' => '   ']), ['title' => ['The title field is required.'], 'price' => ['The price field is required.']]);"],
                ['label' => 'Bad prices', 'code' => "return expect_same([validateBook(['title' => 'Dune', 'price' => 'cheap']), validateBook(['title' => 'Dune', 'price' => '-1'])], [['price' => ['The price field must be a number.']], ['price' => ['The price field must be at least 0.']]]);"],
                ['label' => 'Long titles', 'code' => "return expect_same(validateBook(['title' => str_repeat('a', 256), 'price' => 1]), ['title' => ['The title field must not be greater than 255 characters.']]);"],
            ],
        ],
        [
            'title' => 'Passwords that match',
            'prompt' => 'Write `validateRegistration(array $input): array` with these rules, giving at most one message per field: `email` is required (`The email field is required.`) and must look like `something@something.something` with no spaces (`The email field must be a valid email address.`); `password` is required (`The password field is required.`), at least 8 characters (`The password field must be at least 8 characters.`) and must equal `password_confirmation` (`The password field confirmation does not match.`).',
            'starter_code' => <<<'PHP'
                <?php

                function validateRegistration(array $input): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validateRegistration(array $input): array
                {
                    $errors = [];

                    $email = trim((string) ($input['email'] ?? ''));
                    if ($email === '') {
                        $errors['email'][] = 'The email field is required.';
                    } elseif (preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email) !== 1) {
                        $errors['email'][] = 'The email field must be a valid email address.';
                    }

                    $password = (string) ($input['password'] ?? '');
                    if ($password === '') {
                        $errors['password'][] = 'The password field is required.';
                    } elseif (strlen($password) < 8) {
                        $errors['password'][] = 'The password field must be at least 8 characters.';
                    } elseif ($password !== ($input['password_confirmation'] ?? null)) {
                        $errors['password'][] = 'The password field confirmation does not match.';
                    }

                    return $errors;
                }
                PHP,
            'hint' => 'A pattern like `/^[^@\s]+@[^@\s]+\.[^@\s]+$/` is a simple email check. Compare the password and its confirmation with `!==`.',
            'checks' => [
                ['label' => 'Good registrations pass', 'code' => "return expect_same(validateRegistration(['email' => 'ada@example.com', 'password' => 'correct horse', 'password_confirmation' => 'correct horse']), []);"],
                ['label' => 'Missing fields', 'code' => "return expect_same(validateRegistration([]), ['email' => ['The email field is required.'], 'password' => ['The password field is required.']]);"],
                ['label' => 'Bad emails', 'code' => "foreach (['ada', 'ada@example', 'ada @example.com', '@example.com'] as \$email) {\n    \$e = validateRegistration(['email' => \$email, 'password' => 'longenough', 'password_confirmation' => 'longenough']);\n    if ((\$e['email'][0] ?? null) !== 'The email field must be a valid email address.') return \"'\$email' should be rejected.\";\n}\nreturn true;"],
                ['label' => 'Password rules', 'code' => "return expect_same([validateRegistration(['email' => 'a@b.co', 'password' => 'short', 'password_confirmation' => 'short']), validateRegistration(['email' => 'a@b.co', 'password' => 'longenough', 'password_confirmation' => 'different'])], [['password' => ['The password field must be at least 8 characters.']], ['password' => ['The password field confirmation does not match.']]]);"],
            ],
        ],
        [
            'title' => 'Old input',
            'prompt' => 'Write `flashOldInput(array &$session, array $input)`, storing the input in `$session[\'_old_input\']` without `password` and `password_confirmation`. Then write `old(array $session, string $key, mixed $default = null): mixed`, returning the old value for a key, or the default.',
            'starter_code' => <<<'PHP'
                <?php

                function flashOldInput(array &$session, array $input): void
                {
                    $session['_old_input'] = $input;
                }

                function old(array $session, string $key, mixed $default = null): mixed
                {
                    return $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function flashOldInput(array &$session, array $input): void
                {
                    unset($input['password'], $input['password_confirmation']);
                    $session['_old_input'] = $input;
                }

                function old(array $session, string $key, mixed $default = null): mixed
                {
                    return $session['_old_input'][$key] ?? $default;
                }
                PHP,
            'hint' => '`unset()` can remove several keys at once. `??` gives the default when the key is missing.',
            'checks' => [
                ['label' => 'Input is kept, passwords are not', 'code' => "\$session = [];\nflashOldInput(\$session, ['email' => 'ada@example.com', 'name' => 'Ada', 'password' => 'secret123', 'password_confirmation' => 'secret123']);\nreturn expect_same(\$session, ['_old_input' => ['email' => 'ada@example.com', 'name' => 'Ada']]);"],
                ['label' => 'old() reads it back', 'code' => "\$session = ['_old_input' => ['title' => 'Dune']];\nreturn expect_same([old(\$session, 'title'), old(\$session, 'price', '0.00'), old([], 'title', '')], ['Dune', '0.00', '']);"],
            ],
        ],
        [
            'title' => '@error',
            'prompt' => 'Write `errorFor(array $errors, string $field): string`, doing what `@error` does in the form: if the field has errors, return `<p class="error">first message</p>` with the message escaped; otherwise return an empty string. Then write `fieldClass(array $errors, string $field): string`, returning `input` or, when the field has errors, `input input-error`.',
            'starter_code' => <<<'PHP'
                <?php

                function errorFor(array $errors, string $field): string
                {
                    return '';
                }

                function fieldClass(array $errors, string $field): string
                {
                    return 'input';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function errorFor(array $errors, string $field): string
                {
                    $message = $errors[$field][0] ?? null;
                    if ($message === null) {
                        return '';
                    }

                    return '<p class="error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
                }

                function fieldClass(array $errors, string $field): string
                {
                    return empty($errors[$field]) ? 'input' : 'input input-error';
                }
                PHP,
            'hint' => '`$errors[$field][0] ?? null` gives the first message or `null`. Escape it with `htmlspecialchars()`.',
            'checks' => [
                ['label' => 'The first message, escaped', 'code' => "\$errors = ['title' => ['The title field is required.', 'Another'], 'price' => ['Use < 1000 & > 0']];\nreturn expect_same([errorFor(\$errors, 'title'), errorFor(\$errors, 'price')], ['<p class=\"error\">The title field is required.</p>', '<p class=\"error\">Use &lt; 1000 &amp; &gt; 0</p>']);"],
                ['label' => 'No errors, nothing shown', 'code' => "return expect_same([errorFor(['title' => ['x']], 'price'), errorFor([], 'title')], ['', '']);"],
                ['label' => 'Classes for the input', 'code' => "return expect_same([fieldClass(['title' => ['x']], 'title'), fieldClass(['title' => ['x']], 'price'), fieldClass(['title' => []], 'title')], ['input input-error', 'input', 'input']);"],
            ],
        ],
        [
            'title' => 'Store or go back',
            'prompt' => 'Write `storeBook(array $input, array &$session, string $previousUrl): array`, the whole `store` action. Validate with the given `validateBook()`. On failure, flash `$session[\'_errors\']` and `$session[\'_old_input\']` (the input) and return `[\'status\' => 302, \'location\' => $previousUrl]`. On success, add the book (`title` and `price` only) to `$session[\'books\']`, flash `$session[\'status\'] = \'Book saved.\'` and return a 302 to `/books`.',
            'starter_code' => <<<'PHP'
                <?php

                function validateBook(array $input): array
                {
                    $errors = [];
                    if (trim((string) ($input['title'] ?? '')) === '') {
                        $errors['title'][] = 'The title field is required.';
                    }
                    if (! is_numeric($input['price'] ?? null) || $input['price'] < 0) {
                        $errors['price'][] = 'The price field must be at least 0.';
                    }

                    return $errors;
                }

                function storeBook(array $input, array &$session, string $previousUrl): array
                {
                    $session['books'][] = $input;

                    return ['status' => 302, 'location' => '/books'];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validateBook(array $input): array
                {
                    $errors = [];
                    if (trim((string) ($input['title'] ?? '')) === '') {
                        $errors['title'][] = 'The title field is required.';
                    }
                    if (! is_numeric($input['price'] ?? null) || $input['price'] < 0) {
                        $errors['price'][] = 'The price field must be at least 0.';
                    }

                    return $errors;
                }

                function storeBook(array $input, array &$session, string $previousUrl): array
                {
                    $errors = validateBook($input);
                    if ($errors !== []) {
                        $session['_errors'] = $errors;
                        $session['_old_input'] = $input;

                        return ['status' => 302, 'location' => $previousUrl];
                    }

                    $session['books'][] = ['title' => $input['title'], 'price' => $input['price']];
                    $session['status'] = 'Book saved.';

                    return ['status' => 302, 'location' => '/books'];
                }
                PHP,
            'hint' => 'Validate first and return early on errors. Only build the book from `title` and `price`, so extra fields are dropped.',
            'checks' => [
                ['label' => 'Bad input goes back with errors', 'code' => "\$session = [];\n\$response = storeBook(['title' => '', 'price' => '9.99'], \$session, '/books/create');\nreturn expect_same([\$response, \$session], [['status' => 302, 'location' => '/books/create'], ['_errors' => ['title' => ['The title field is required.']], '_old_input' => ['title' => '', 'price' => '9.99']]]);"],
                ['label' => 'Good input is saved', 'code' => "\$session = ['books' => []];\n\$response = storeBook(['title' => 'Dune', 'price' => '9.99', 'featured' => '1'], \$session, '/books/create');\nreturn expect_same([\$response, \$session], [['status' => 302, 'location' => '/books'], ['books' => [['title' => 'Dune', 'price' => '9.99']], 'status' => 'Book saved.']]);"],
            ],
        ],
    ],
];
