<?php

// Lesson 10 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'validation-and-form-requests',
    'title' => 'Validation and form requests',
    'summary' => 'How Laravel validates input: rule strings parsed into rules, messages with placeholders, empty values, `nullable` and `validated()`, rule objects, wildcard rules for nested data, and form requests.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try an empty title, or a long one.
        $data = ['title' => 'Dune', 'format' => 'audiobook'];
        $rules = ['title' => 'required|max:10', 'format' => 'required|in:paperback,hardback'];

        foreach ($rules as $field => $definition) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $definition) as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, '');
                $failed = match ($name) {
                    'required' => $value === null || $value === '',
                    'max' => strlen((string) $value) > (int) $argument,
                    'in' => ! in_array($value, explode(',', $argument), true),
                };
                if ($failed) {
                    echo "$field fails $name" . PHP_EOL;
                }
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $rule = 'regex:/^[a-z]+:[0-9]+$/';

                [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

                echo $name . ' | ' . $parameters;
                PHP,
            'options' => [
                '`regex | /^[a-z]+`',
                '`regex | /^[a-z]+:[0-9]+$/`',
                '`regex:/^[a-z]+ | [0-9]+$/`',
                '`regex | `',
            ],
            'correct_option' => 1,
            'explanation' => 'The limit of 2 splits only at the first colon, so the parameter keeps its own colon. A `|` inside it would still break the rule string, which is why such rules are written in an array.',
        ],
        [
            'question' => 'A field has the rules `string|max:255` (no `required`) and the visitor leaves it out. What happens?',
            'options' => [
                'It fails `string`',
                'It fails `max`',
                'It passes: rules other than `required` skip missing values',
                'The request is rejected with a 500',
            ],
            'correct_option' => 2,
            'explanation' => 'Only "implicit" rules like `required` complain about a missing value. Add `required` if the field must be there.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function size(mixed $value): int|float
                {
                    return match (true) {
                        is_int($value), is_float($value) => $value,
                        is_array($value) => count($value),
                        default => strlen((string) $value),
                    };
                }

                echo size(42) . ' ' . size('42') . ' ' . size([4, 2]) . ' ' . size('Piranesi');
                PHP,
            'options' => ['`42 42 2 8`', '`42 2 2 8`', '`2 2 2 8`', '`42 2 4 8`'],
            'correct_option' => 1,
            'explanation' => 'Numbers are measured by value, strings by length and arrays by count. That\'s how `min` and `max` decide, so `\'42\'` (a string) has size 2.',
        ],
        [
            'question' => 'What does `nullable` do in `\'subtitle\' => \'nullable|string|max:100\'`?',
            'options' => [
                'Makes the field required',
                'Lets the value be `null`, skipping the other rules when it is',
                'Turns empty strings into `null` in the database',
                'Removes the field from the validated data',
            ],
            'correct_option' => 1,
            'explanation' => 'Without `nullable`, a `null` subtitle would fail `string`.',
        ],
        [
            'question' => 'The request contains `title`, `price` and `is_featured`, but only `title` and `price` have rules. What does `$request->validated()` return?',
            'options' => [
                'All three fields',
                'Only `title` and `price`',
                'Only `is_featured`',
                '`true`',
            ],
            'correct_option' => 1,
            'explanation' => 'Only validated fields come back, which keeps unexpected input out of `create()` and `update()`.',
        ],
        [
            'question' => 'What does `bail` do in `\'isbn\' => \'bail|required|digits:13|unique:books\'`?',
            'options' => [
                'Skips the field if it\'s empty',
                'Stops checking the field after its first failing rule',
                'Throws an exception straight away',
                'Retries the check',
            ],
            'correct_option' => 1,
            'explanation' => 'If the ISBN isn\'t 13 digits, there\'s no point querying the database for `unique`, and one message is enough.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $data = ['lines' => [['qty' => 2], ['qty' => 0], ['qty' => 5]]];

                $errors = [];
                foreach ($data['lines'] as $index => $line) {
                    if ($line['qty'] < 1) {
                        $errors["lines.$index.qty"] = 'must be at least 1';
                    }
                }

                echo json_encode($errors);
                PHP,
            'options' => [
                '`{"lines.*.qty":"must be at least 1"}`',
                '`{"lines.1.qty":"must be at least 1"}`',
                '`{"lines.2.qty":"must be at least 1"}`',
                '`[]`',
            ],
            'correct_option' => 1,
            'explanation' => 'Wildcard rules report errors at the real position, here index 1, so the form can mark the right line.',
        ],
        [
            'question' => 'In what order does Laravel handle a form request when it is resolved?',
            'options' => [
                'Rules, then `authorize()`, then `prepareForValidation()`',
                '`authorize()`, then `prepareForValidation()`, then the rules',
                '`prepareForValidation()`, then the rules, then `authorize()`',
                'Only the rules; `authorize()` is called by the controller',
            ],
            'correct_option' => 1,
            'explanation' => 'An unauthorized request is turned away (403) before any of its input is even looked at.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Parse the rules',
            'prompt' => 'Write `parseRules(string|array $rules): array`, returning a list of `[name, parameters]` pairs. A string is split at `|`; each rule is split at its **first** `:` into a name and parameters, which are split at `,`. A rule without parameters has `[]`. An array is a list of rule strings, each parsed the same way but never split at `|`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseRules(string|array $rules): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseRules(string|array $rules): array
                {
                    $list = is_string($rules) ? explode('|', $rules) : $rules;

                    return array_map(function (string $rule): array {
                        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

                        return [$name, $parameters === '' ? [] : explode(',', $parameters)];
                    }, $list);
                }
                PHP,
            'hint' => 'Turn a string into a list with `explode(\'|\', ...)` first. Then each rule is `array_pad(explode(\':\', $rule, 2), 2, \'\')`, with the parameters exploded at commas unless they\'re empty.',
            'checks' => [
                ['label' => 'A rule string', 'code' => "return expect_same(parseRules('required|string|max:255'), [['required', []], ['string', []], ['max', ['255']]]);"],
                ['label' => 'Several parameters', 'code' => "return expect_same(parseRules('in:paperback,hardback,ebook'), [['in', ['paperback', 'hardback', 'ebook']]]);"],
                ['label' => 'Only the first colon splits', 'code' => "return expect_same(parseRules('date_format:H:i'), [['date_format', ['H:i']]]);"],
                ['label' => 'Arrays aren\'t split at |', 'code' => "return expect_same(parseRules(['required', 'regex:/^(a|b)$/']), [['required', []], ['regex', ['/^(a|b)$/']]]);"],
            ],
        ],
        [
            'title' => 'Check the fields',
            'prompt' => 'Finish `Validator::errors()`, returning messages keyed by field, in rule order, for the rules `required`, `string`, `integer`, `email`, `min`, `max` and `in` (see the messages in `MESSAGES`). A value that is missing, `null` or `\'\'` fails only `required`; every other rule skips it. `min`/`max` compare numbers by value and strings by length, using the messages ending in `characters.` for strings. `:attribute` is the field name with `_` replaced by spaces. Fields without errors are left out.',
            'starter_code' => <<<'PHP'
                <?php

                function parseRules(string $rules): array
                {
                    return array_map(function (string $rule): array {
                        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

                        return [$name, $parameters === '' ? [] : explode(',', $parameters)];
                    }, explode('|', $rules));
                }

                class Validator
                {
                    public const MESSAGES = [
                        'required' => 'The :attribute field is required.',
                        'string' => 'The :attribute field must be a string.',
                        'integer' => 'The :attribute field must be an integer.',
                        'email' => 'The :attribute field must be a valid email address.',
                        'min.numeric' => 'The :attribute field must be at least :min.',
                        'min.string' => 'The :attribute field must be at least :min characters.',
                        'max.numeric' => 'The :attribute field must not be greater than :max.',
                        'max.string' => 'The :attribute field must not be greater than :max characters.',
                        'in' => 'The selected :attribute is invalid.',
                    ];

                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseRules(string $rules): array
                {
                    return array_map(function (string $rule): array {
                        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

                        return [$name, $parameters === '' ? [] : explode(',', $parameters)];
                    }, explode('|', $rules));
                }

                class Validator
                {
                    public const MESSAGES = [
                        'required' => 'The :attribute field is required.',
                        'string' => 'The :attribute field must be a string.',
                        'integer' => 'The :attribute field must be an integer.',
                        'email' => 'The :attribute field must be a valid email address.',
                        'min.numeric' => 'The :attribute field must be at least :min.',
                        'min.string' => 'The :attribute field must be at least :min characters.',
                        'max.numeric' => 'The :attribute field must not be greater than :max.',
                        'max.string' => 'The :attribute field must not be greater than :max characters.',
                        'in' => 'The selected :attribute is invalid.',
                    ];

                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $definition) {
                            $value = $this->data[$field] ?? null;
                            $empty = $value === null || $value === '';

                            foreach (parseRules($definition) as [$rule, $parameters]) {
                                if ($empty && $rule !== 'required') {
                                    continue;
                                }

                                $message = $this->check($rule, $parameters, $value, $empty);
                                if ($message !== null) {
                                    $errors[$field][] = str_replace(
                                        [':attribute', ':min', ':max'],
                                        [str_replace('_', ' ', $field), $parameters[0] ?? '', $parameters[0] ?? ''],
                                        $message,
                                    );
                                }
                            }
                        }

                        return $errors;
                    }

                    private function check(string $rule, array $parameters, mixed $value, bool $empty): ?string
                    {
                        $numeric = is_int($value) || is_float($value);
                        $size = $numeric ? $value : strlen((string) $value);
                        $kind = $numeric ? 'numeric' : 'string';

                        $passes = match ($rule) {
                            'required' => ! $empty,
                            'string' => is_string($value),
                            'integer' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
                            'email' => is_string($value) && preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $value) === 1,
                            'min' => $size >= (float) $parameters[0],
                            'max' => $size <= (float) $parameters[0],
                            'in' => in_array((string) $value, $parameters, true),
                        };

                        if ($passes) {
                            return null;
                        }

                        return in_array($rule, ['min', 'max'], true) ? self::MESSAGES["$rule.$kind"] : self::MESSAGES[$rule];
                    }
                }
                PHP,
            'hint' => 'Loop over fields, then over `parseRules()`. Skip every rule but `required` for empty values. A helper returning the message template (or `null` when the rule passes) keeps the loop short; fill in `:attribute`, `:min` and `:max` with `str_replace()`.',
            'checks' => [
                ['label' => 'Missing required fields', 'code' => "\$v = new Validator(['title' => ''], ['title' => 'required|string', 'first_name' => 'required']);\nreturn expect_same(\$v->errors(), ['title' => ['The title field is required.'], 'first_name' => ['The first name field is required.']]);"],
                ['label' => 'Empty optional fields are skipped', 'code' => "\$v = new Validator(['subtitle' => null], ['subtitle' => 'string|max:5', 'isbn' => 'integer']);\nreturn expect_same(\$v->errors(), []);"],
                ['label' => 'min and max measure numbers and strings', 'code' => "\$v = new Validator(['title' => 'Dune: The Graphic Novel', 'stock' => 0, 'code' => 'ab'], ['title' => 'string|max:10', 'stock' => 'integer|min:1', 'code' => 'min:3']);\nreturn expect_same(\$v->errors(), ['title' => ['The title field must not be greater than 10 characters.'], 'stock' => ['The stock field must be at least 1.'], 'code' => ['The code field must be at least 3 characters.']]);"],
                ['label' => 'email, in and integer', 'code' => "\$v = new Validator(['email' => 'ada@', 'format' => 'scroll', 'pages' => '12', 'year' => 'soon'], ['email' => 'email', 'format' => 'in:paperback,hardback', 'pages' => 'integer', 'year' => 'integer']);\nreturn expect_same(\$v->errors(), ['email' => ['The email field must be a valid email address.'], 'format' => ['The selected format is invalid.'], 'year' => ['The year field must be an integer.']]);"],
                ['label' => 'Every failing rule is reported, in order', 'code' => "\$v = new Validator(['title' => 42], ['title' => 'required|string|max:1']);\nreturn expect_same(\$v->errors(), ['title' => ['The title field must be a string.', 'The title field must not be greater than 1.']]);"],
            ],
        ],
        [
            'title' => 'nullable, bail and validated()',
            'prompt' => 'Add three things to `Validator`. `nullable` lets a `null` value skip every other rule (unlike `\'\'`, which `required` still rejects). `bail` stops checking a field after its first error. `validated()` returns only the fields that have rules **and** are present in the data, or throws a `ValidationException` (whose `errors` property holds the errors) if anything failed.',
            'starter_code' => <<<'PHP'
                <?php

                class ValidationException extends RuntimeException
                {
                    public function __construct(public array $errors)
                    {
                        parent::__construct('The given data was invalid.');
                    }
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $definition) {
                            $value = $this->data[$field] ?? null;
                            foreach (explode('|', $definition) as $rule) {
                                $message = $this->check($rule, $value);
                                if ($message !== null) {
                                    $errors[$field][] = $message;
                                }
                            }
                        }

                        return $errors;
                    }

                    public function validated(): array
                    {
                        return $this->data;
                    }

                    private function check(string $rule, mixed $value): ?string
                    {
                        return match ($rule) {
                            'required' => $value === null || $value === '' ? 'required' : null,
                            'string' => is_string($value) ? null : 'string',
                            'integer' => is_int($value) ? null : 'integer',
                            default => null,
                        };
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class ValidationException extends RuntimeException
                {
                    public function __construct(public array $errors)
                    {
                        parent::__construct('The given data was invalid.');
                    }
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $definition) {
                            $value = $this->data[$field] ?? null;
                            $rules = explode('|', $definition);

                            if ($value === null && in_array('nullable', $rules, true)) {
                                continue;
                            }

                            foreach ($rules as $rule) {
                                $message = $this->check($rule, $value);
                                if ($message !== null) {
                                    $errors[$field][] = $message;
                                    if (in_array('bail', $rules, true)) {
                                        break;
                                    }
                                }
                            }
                        }

                        return $errors;
                    }

                    public function validated(): array
                    {
                        $errors = $this->errors();
                        if ($errors !== []) {
                            throw new ValidationException($errors);
                        }

                        return array_intersect_key($this->data, $this->rules);
                    }

                    private function check(string $rule, mixed $value): ?string
                    {
                        return match ($rule) {
                            'required' => $value === null || $value === '' ? 'required' : null,
                            'string' => is_string($value) ? null : 'string',
                            'integer' => is_int($value) ? null : 'integer',
                            default => null,
                        };
                    }
                }
                PHP,
            'hint' => 'Check for `nullable` with a `null` value before the rule loop and `continue`. `bail` is a `break` after the first error. `array_intersect_key($this->data, $this->rules)` keeps fields that have rules and are present.',
            'checks' => [
                ['label' => 'nullable lets null through', 'code' => "\$v = new Validator(['subtitle' => null, 'series' => ''], ['subtitle' => 'nullable|string', 'series' => 'nullable|required']);\nreturn expect_same(\$v->errors(), ['series' => ['required']]);"],
                ['label' => 'bail stops at the first error', 'code' => "\$v = new Validator(['isbn' => null, 'pages' => null], ['isbn' => 'bail|required|string|integer', 'pages' => 'required|string|integer']);\nreturn expect_same(\$v->errors(), ['isbn' => ['required'], 'pages' => ['required', 'string', 'integer']]);"],
                ['label' => 'validated() keeps only fields with rules', 'code' => "\$v = new Validator(['title' => 'Dune', 'is_featured' => true, 'pages' => 412], ['title' => 'required|string', 'pages' => 'integer', 'subtitle' => 'nullable|string']);\nreturn expect_same(\$v->validated(), ['title' => 'Dune', 'pages' => 412]);"],
                ['label' => 'validated() throws when invalid', 'code' => "try {\n    (new Validator(['title' => ''], ['title' => 'required']))->validated();\n} catch (ValidationException \$e) {\n    return expect_same(\$e->errors, ['title' => ['required']]);\n}\nreturn 'No ValidationException was thrown.';"],
            ],
        ],
        [
            'title' => 'Rule objects',
            'prompt' => 'Let a field\'s rules be an array that mixes rule names (`required`, `string`) with objects implementing `ValidationRule`. For an object, call `validate($field, $value, $fail)`, where `$fail` is a closure taking a message; each message passed to it is added to the field\'s errors with `:attribute` replaced by the field name. Then write `Isbn`, which fails with `The :attribute must be a valid ISBN-13.` unless the value is 13 digits starting with 978 or 979. Rule objects, like other rules, skip empty values.',
            'starter_code' => <<<'PHP'
                <?php

                interface ValidationRule
                {
                    public function validate(string $attribute, mixed $value, Closure $fail): void;
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $rules) {
                            $value = $this->data[$field] ?? null;
                            foreach ($rules as $rule) {
                                if ($rule === 'required' && ($value === null || $value === '')) {
                                    $errors[$field][] = "The $field field is required.";
                                }
                            }
                        }

                        return $errors;
                    }
                }

                class Isbn
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ValidationRule
                {
                    public function validate(string $attribute, mixed $value, Closure $fail): void;
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $rules) {
                            $value = $this->data[$field] ?? null;
                            $empty = $value === null || $value === '';

                            foreach ($rules as $rule) {
                                if ($rule === 'required' && $empty) {
                                    $errors[$field][] = "The $field field is required.";
                                }

                                if ($rule instanceof ValidationRule && ! $empty) {
                                    $rule->validate($field, $value, function (string $message) use (&$errors, $field) {
                                        $errors[$field][] = str_replace(':attribute', $field, $message);
                                    });
                                }
                            }
                        }

                        return $errors;
                    }
                }

                class Isbn implements ValidationRule
                {
                    public function validate(string $attribute, mixed $value, Closure $fail): void
                    {
                        if (preg_match('/^97[89]\d{10}$/', (string) $value) !== 1) {
                            $fail('The :attribute must be a valid ISBN-13.');
                        }
                    }
                }
                PHP,
            'hint' => 'Check `$rule instanceof ValidationRule`. The `$fail` closure needs `use (&$errors, $field)` so it can add to the errors array. `Isbn` implements the interface and uses `preg_match()`.',
            'checks' => [
                ['label' => 'Isbn rejects bad numbers', 'code' => "\$v = new Validator(['isbn' => '12345'], ['isbn' => ['required', new Isbn()]]);\nreturn expect_same(\$v->errors(), ['isbn' => ['The isbn must be a valid ISBN-13.']]);"],
                ['label' => 'Isbn accepts good ones', 'code' => "\$v = new Validator(['isbn' => '9780441172719', 'other' => '9791234567890'], ['isbn' => [new Isbn()], 'other' => [new Isbn()]]);\nreturn expect_same(\$v->errors(), []);"],
                ['label' => 'Any rule object can fail, more than once', 'code' => "\$rule = new class implements ValidationRule { public function validate(string \$attribute, mixed \$value, Closure \$fail): void { \$fail(':attribute is too short.'); \$fail(':attribute is boring.'); } };\nreturn expect_same((new Validator(['title' => 'Hi'], ['title' => [\$rule]]))->errors(), ['title' => ['title is too short.', 'title is boring.']]);"],
                ['label' => 'Rule objects skip empty values', 'code' => "return expect_same((new Validator(['isbn' => ''], ['isbn' => [new Isbn()]]))->errors(), []);"],
            ],
        ],
        [
            'title' => 'Wildcard rules',
            'prompt' => 'Write `expandRules(array $rules, array $data): array`. A key containing `*` (like `lines.*.quantity`) is replaced by one key per element actually present in the data at that point (`lines.0.quantity`, `lines.1.quantity`...), with the same rules; keys may contain several `*`. Keys without `*` are kept as they are. If the data has no array at a `*`, that rule produces no keys. Then write `dataGet(array $data, string $key)`, reading a dotted key from nested arrays, or `null` if any part is missing.',
            'starter_code' => <<<'PHP'
                <?php

                function expandRules(array $rules, array $data): array
                {
                    return $rules;
                }

                function dataGet(array $data, string $key): mixed
                {
                    return $data[$key] ?? null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function dataGet(array $data, string $key): mixed
                {
                    $value = $data;
                    foreach (explode('.', $key) as $segment) {
                        if (! is_array($value) || ! array_key_exists($segment, $value)) {
                            return null;
                        }
                        $value = $value[$segment];
                    }

                    return $value;
                }

                function expandRules(array $rules, array $data): array
                {
                    $expanded = [];
                    foreach ($rules as $key => $rule) {
                        foreach (expandKey($key, $data) as $concrete) {
                            $expanded[$concrete] = $rule;
                        }
                    }

                    return $expanded;
                }

                function expandKey(string $key, array $data): array
                {
                    $star = strpos($key, '*');
                    if ($star === false) {
                        return [$key];
                    }

                    $before = rtrim(substr($key, 0, $star), '.');
                    $after = substr($key, $star + 1);
                    $items = $before === '' ? $data : dataGet($data, $before);
                    if (! is_array($items)) {
                        return [];
                    }

                    $keys = [];
                    foreach (array_keys($items) as $index) {
                        $prefix = $before === '' ? (string) $index : "$before.$index";
                        array_push($keys, ...expandKey($prefix . $after, $data));
                    }

                    return $keys;
                }
                PHP,
            'hint' => 'Write `dataGet()` first: walk the segments of the key. To expand, find the first `*`, read the array before it with `dataGet()`, and for each of its keys substitute the index and expand the rest recursively (that handles several stars).',
            'checks' => [
                ['label' => 'dataGet() reads nested keys', 'code' => "\$data = ['lines' => [['quantity' => 2], ['quantity' => 0]]];\nreturn expect_same([dataGet(\$data, 'lines.1.quantity'), dataGet(\$data, 'lines.5.quantity'), dataGet(\$data, 'lines.0')], [0, null, ['quantity' => 2]]);"],
                ['label' => 'One star expands per element', 'code' => "\$data = ['lines' => [['quantity' => 2], ['quantity' => 0], ['quantity' => 5]]];\nreturn expect_same(expandRules(['lines' => 'array', 'lines.*.quantity' => 'min:1'], \$data), ['lines' => 'array', 'lines.0.quantity' => 'min:1', 'lines.1.quantity' => 'min:1', 'lines.2.quantity' => 'min:1']);"],
                ['label' => 'Several stars', 'code' => "\$data = ['shelves' => [['books' => ['a', 'b']], ['books' => ['c']]]];\nreturn expect_same(array_keys(expandRules(['shelves.*.books.*' => 'string'], \$data)), ['shelves.0.books.0', 'shelves.0.books.1', 'shelves.1.books.0']);"],
                ['label' => 'Nothing to expand, no keys', 'code' => "return expect_same(expandRules(['tags.*' => 'string', 'title' => 'required'], ['tags' => 'none']), ['title' => 'required']);"],
            ],
        ],
        [
            'title' => 'A form request',
            'prompt' => 'Finish `FormRequest::validateResolved()`: if `authorize()` returns false, throw `AuthorizationException`; otherwise call `prepareForValidation()`, then validate `$this->input` against `rules()` with `Validator`, throwing `ValidationException` with the errors if any fail, and storing the result of `validated()` in `$this->validated`. Then write `StoreBookRequest`: only users with the role `editor` are authorized; before validation the title is trimmed and the slug, if missing, is made from the title (lower case, spaces as `-`); rules are `title` `required|max:50` and `slug` `required`.',
            'starter_code' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException {}

                class ValidationException extends RuntimeException
                {
                    public function __construct(public array $errors)
                    {
                        parent::__construct('The given data was invalid.');
                    }
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $definition) {
                            $value = $this->data[$field] ?? null;
                            foreach (explode('|', $definition) as $rule) {
                                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, '');
                                if ($name === 'required' && ($value === null || $value === '')) {
                                    $errors[$field][] = "The $field field is required.";
                                }
                                if ($name === 'max' && $value !== null && strlen($value) > (int) $parameter) {
                                    $errors[$field][] = "The $field field must not be greater than $parameter characters.";
                                }
                            }
                        }

                        return $errors;
                    }

                    public function validated(): array
                    {
                        return array_intersect_key($this->data, $this->rules);
                    }
                }

                abstract class FormRequest
                {
                    public array $validated = [];

                    public function __construct(public array $input, public array $user = []) {}

                    public function authorize(): bool
                    {
                        return true;
                    }

                    protected function prepareForValidation(): void {}

                    abstract public function rules(): array;

                    public function validateResolved(): void
                    {
                        $this->validated = $this->input;
                    }
                }

                class StoreBookRequest extends FormRequest
                {
                    public function rules(): array
                    {
                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AuthorizationException extends RuntimeException {}

                class ValidationException extends RuntimeException
                {
                    public function __construct(public array $errors)
                    {
                        parent::__construct('The given data was invalid.');
                    }
                }

                class Validator
                {
                    public function __construct(private array $data, private array $rules) {}

                    public function errors(): array
                    {
                        $errors = [];
                        foreach ($this->rules as $field => $definition) {
                            $value = $this->data[$field] ?? null;
                            foreach (explode('|', $definition) as $rule) {
                                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, '');
                                if ($name === 'required' && ($value === null || $value === '')) {
                                    $errors[$field][] = "The $field field is required.";
                                }
                                if ($name === 'max' && $value !== null && strlen($value) > (int) $parameter) {
                                    $errors[$field][] = "The $field field must not be greater than $parameter characters.";
                                }
                            }
                        }

                        return $errors;
                    }

                    public function validated(): array
                    {
                        return array_intersect_key($this->data, $this->rules);
                    }
                }

                abstract class FormRequest
                {
                    public array $validated = [];

                    public function __construct(public array $input, public array $user = []) {}

                    public function authorize(): bool
                    {
                        return true;
                    }

                    protected function prepareForValidation(): void {}

                    abstract public function rules(): array;

                    public function validateResolved(): void
                    {
                        if (! $this->authorize()) {
                            throw new AuthorizationException('This action is unauthorized.');
                        }

                        $this->prepareForValidation();

                        $validator = new Validator($this->input, $this->rules());
                        $errors = $validator->errors();
                        if ($errors !== []) {
                            throw new ValidationException($errors);
                        }

                        $this->validated = $validator->validated();
                    }
                }

                class StoreBookRequest extends FormRequest
                {
                    public function authorize(): bool
                    {
                        return ($this->user['role'] ?? null) === 'editor';
                    }

                    protected function prepareForValidation(): void
                    {
                        $title = trim((string) ($this->input['title'] ?? ''));
                        $this->input['title'] = $title;
                        $this->input['slug'] ??= str_replace(' ', '-', strtolower($title));
                    }

                    public function rules(): array
                    {
                        return ['title' => 'required|max:50', 'slug' => 'required'];
                    }
                }
                PHP,
            'hint' => 'In `validateResolved()`, the order is authorize, prepare, validate. In `StoreBookRequest::prepareForValidation()`, change `$this->input` directly; `??=` sets the slug only when it\'s missing.',
            'checks' => [
                ['label' => 'Valid input from an editor', 'code' => "\$request = new StoreBookRequest(['title' => '  Children of Dune ', 'extra' => 'x'], ['role' => 'editor']);\n\$request->validateResolved();\nreturn expect_same(\$request->validated, ['title' => 'Children of Dune', 'slug' => 'children-of-dune']);"],
                ['label' => 'A given slug is kept', 'code' => "\$request = new StoreBookRequest(['title' => 'Dune', 'slug' => 'dune-1965'], ['role' => 'editor']);\n\$request->validateResolved();\nreturn expect_same(\$request->validated['slug'] ?? null, 'dune-1965');"],
                ['label' => 'Others are not authorized, before validation', 'code' => "\$request = new StoreBookRequest(['title' => ''], ['role' => 'reader']);\nreturn expect_throws(AuthorizationException::class, fn () => \$request->validateResolved());"],
                ['label' => 'Invalid input throws with the errors', 'code' => "\$request = new StoreBookRequest(['title' => '   '], ['role' => 'editor']);\ntry { \$request->validateResolved(); } catch (ValidationException \$e) { return expect_same(\$e->errors, ['title' => ['The title field is required.'], 'slug' => ['The slug field is required.']]); }\nreturn 'No ValidationException was thrown.';"],
                ['label' => 'Long titles fail', 'code' => "\$request = new StoreBookRequest(['title' => str_repeat('a', 51), 'slug' => 'a'], ['role' => 'editor']);\nreturn expect_throws(ValidationException::class, fn () => \$request->validateResolved());"],
            ],
        ],
    ],
];
