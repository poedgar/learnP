<?php

// Lesson 4 of the Laravel testing course. The theory is in the .md file with the same name.

return [
    'slug' => 'pest',
    'title' => 'Pest',
    'summary' => 'Writing tests with Pest: chained expectations, `not`, `and` and `each`, datasets, `describe` with `beforeEach`, skip and todo, architecture tests, and how expectations work inside.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add a toBeTrue() expectation.
        final class Expectation
        {
            public function __construct(public mixed $value) {}

            public function toBe(mixed $expected): static
            {
                if ($this->value !== $expected) {
                    throw new RuntimeException('Expected ' . var_export($this->value, true) . ' to be ' . var_export($expected, true));
                }

                return $this;
            }
        }

        $expect = fn (mixed $value) => new Expectation($value);

        $expect(strtoupper('dune'))->toBe('DUNE');
        echo 'All expectations passed';
        PHP,

    'quiz' => [
        [
            'question' => 'What does `expect($a)->toBe(1)->and($b)->toBe(2)` check?',
            'options' => [
                'That $a is 1 or $b is 2',
                'That $a is 1, then that $b is 2',
                'That $a and $b are both 1 and 2',
                'That $a + $b is 3',
            ],
            'correct_option' => 1,
            'explanation' => '`and()` switches the expectation to a new value; the chain continues.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $checks = [
                    'toBe' => 4 === 4.0,
                    'toEqual' => 4 == 4.0,
                ];

                foreach ($checks as $name => $passed) {
                    echo $name . ':' . ($passed ? 'pass' : 'fail') . ' ';
                }
                PHP,
            'options' => ['`toBe:pass toEqual:pass `', '`toBe:fail toEqual:pass `', '`toBe:pass toEqual:fail `', '`toBe:fail toEqual:fail `'],
            'correct_option' => 1,
            'explanation' => '`toBe()` is strict (`===`), so an int is not a float. `toEqual()` compares loosely.',
        ],
        [
            'question' => 'What does `expect($status)->not->toBe(\'failed\')` do?',
            'options' => [
                'Skips the expectation',
                'Passes when $status is anything but \'failed\'',
                'Fails always',
                'Checks $status is not set',
            ],
            'correct_option' => 1,
            'explanation' => '`not` flips the next expectation only.',
        ],
        [
            'question' => 'What does `expect($prices)->each->toBeGreaterThan(0)` check?',
            'options' => ['The array has more than 0 items', 'Every item is greater than 0', 'The first item is greater than 0', 'The sum is greater than 0'],
            'correct_option' => 1,
            'explanation' => '`each` applies the next expectation to every item.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $dataset = ['too short' => ['123'], 'letters' => ['ABCDEFGHIJ'], ['0306406153']];

                foreach (array_keys($dataset) as $key) {
                    echo is_string($key) ? "with data set \"{$key}\"" : "with data set #{$key}";
                    echo PHP_EOL;
                }
                PHP,
            'options' => [
                'Three lines: "too short", "letters" and #0',
                'Three lines: #0, #1 and #2',
                'Two lines',
                'One line',
            ],
            'correct_option' => 0,
            'explanation' => 'Named data sets keep their names; the unnamed one gets the next integer key, 0.',
        ],
        [
            'question' => 'Why use `beforeEach` to create the cart, instead of creating it once at the top of the file?',
            'options' => [
                'It runs faster',
                'Every test gets a fresh cart, so tests don\'t share state and pass in any order',
                'Pest requires it',
                'So the cart is saved to the database',
            ],
            'correct_option' => 1,
            'explanation' => 'A test must pass on its own; shared state makes tests depend on each other.',
        ],
        [
            'question' => 'What is the danger of `->only()`?',
            'options' => [
                'None',
                'If it is committed, the suite runs just that test and every other test is silently skipped',
                'It deletes other tests',
                'It only works in CI',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s for focusing while you fix something, then remove it.',
        ],
        [
            'question' => 'What does an architecture test like `arch()->expect(\'App\\Models\')->toExtend(Model::class)` test?',
            'options' => [
                'That models save correctly',
                'A rule about the code itself: every class in App\\Models extends Model',
                'The database schema',
                'How fast the models are',
            ],
            'correct_option' => 1,
            'explanation' => 'Architecture tests keep conventions from slipping as the codebase grows.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'More expectations',
            'prompt' => 'Write a class `Expectation` (holding `public mixed $value`) and a function `expect(mixed $value): Expectation`. Give it `toBe($expected)` (`===`), `toEqual($expected)` (`==`), `toBeTrue()` (exactly `true`) and `toHaveCount(int $count)` (for arrays). Each returns `$this` and throws a `RuntimeException` when it fails.',
            'starter_code' => <<<'PHP'
                <?php

                class Expectation
                {
                    public function __construct(public mixed $value) {}

                    public function toBe(mixed $expected): static
                    {
                        if ($this->value != $expected) {
                            throw new RuntimeException('Not the same');
                        }

                        return $this;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Expectation
                {
                    public function __construct(public mixed $value) {}

                    public function toBe(mixed $expected): static
                    {
                        return $this->check($this->value === $expected, 'to be ' . var_export($expected, true));
                    }

                    public function toEqual(mixed $expected): static
                    {
                        return $this->check($this->value == $expected, 'to equal ' . var_export($expected, true));
                    }

                    public function toBeTrue(): static
                    {
                        return $this->check($this->value === true, 'to be true');
                    }

                    public function toHaveCount(int $count): static
                    {
                        return $this->check(is_array($this->value) && count($this->value) === $count, "to have {$count} items");
                    }

                    private function check(bool $passed, string $what): static
                    {
                        if (! $passed) {
                            throw new RuntimeException('Expected ' . var_export($this->value, true) . " {$what}.");
                        }

                        return $this;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'hint' => 'A private `check(bool $passed, string $message)` that throws or returns `$this` keeps each expectation to one line.',
            'checks' => [
                ['label' => 'Passing expectations chain', 'code' => <<<'PHP'
                    $e = expect([1, 2, 3])->toHaveCount(3)->toEqual([1, 2, 3])->toBe([1, 2, 3]);
                    expect(1 < 2)->toBeTrue();
                    expect(4)->toEqual(4.0);
                    return ($e instanceof Expectation) || 'Each expectation should return the expectation.';
                    PHP],
                ['label' => 'Failing expectations throw', 'code' => <<<'PHP'
                    foreach ([fn () => expect(4)->toBe(4.0), fn () => expect(1)->toBeTrue(), fn () => expect([1])->toHaveCount(2), fn () => expect('3')->toEqual(4), fn () => expect('ab')->toHaveCount(2)] as $failing) {
                        $result = expect_throws(RuntimeException::class, $failing);
                        if ($result !== true) return $result;
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Not',
            'prompt' => 'Write `Expectation` and `expect()` again, with `toBe($expected)` and `toContain($needle)` (an item of an array, or part of a string). Reading `->not` returns an expectation that flips the **next** check only: `expect(1)->not->toBe(2)->toBe(1)` passes.',
            'starter_code' => <<<'PHP'
                <?php

                class Expectation
                {
                    public bool $not = false;

                    public function __construct(public mixed $value) {}

                    public function toBe(mixed $expected): static
                    {
                        if ($this->value !== $expected) {
                            throw new RuntimeException('Not the same');
                        }

                        return $this;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Expectation
                {
                    private bool $negated = false;

                    public function __construct(public mixed $value) {}

                    public function __get(string $name): static
                    {
                        if ($name !== 'not') {
                            throw new LogicException("Unknown property {$name}.");
                        }

                        $copy = clone $this;
                        $copy->negated = ! $this->negated;

                        return $copy;
                    }

                    public function toBe(mixed $expected): static
                    {
                        return $this->check($this->value === $expected, 'to be ' . var_export($expected, true));
                    }

                    public function toContain(mixed $needle): static
                    {
                        $contains = is_array($this->value)
                            ? in_array($needle, $this->value, true)
                            : str_contains((string) $this->value, (string) $needle);

                        return $this->check($contains, 'to contain ' . var_export($needle, true));
                    }

                    private function check(bool $passed, string $what): static
                    {
                        if ($passed === $this->negated) {
                            throw new RuntimeException('Expected ' . var_export($this->value, true) . ($this->negated ? ' not ' : ' ') . "{$what}.");
                        }

                        $next = clone $this;
                        $next->negated = false;

                        return $next;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'hint' => 'Implement `__get(\'not\')` to return a clone with a private `$negated` flag set. After each check, return a copy with the flag cleared. A check fails when `$passed === $negated`.',
            'checks' => [
                ['label' => 'not flips a check', 'code' => <<<'PHP'
                    expect('Dune')->not->toBe('Emma');
                    expect(['Dune', 'Emma'])->not->toContain('Ulysses')->toContain('Dune');
                    expect('Ulysses')->toContain('lys')->not->toContain('xyz');
                    return expect_throws(RuntimeException::class, fn () => expect(1)->not->toBe(1));
                    PHP],
                ['label' => 'Only the next check', 'code' => <<<'PHP'
                    expect(1)->not->toBe(2)->toBe(1);
                    return expect_throws(RuntimeException::class, fn () => expect(1)->not->toBe(2)->toBe(2));
                    PHP],
                ['label' => 'toContain is strict for arrays', 'code' => "return expect_throws(RuntimeException::class, fn () => expect([1, 2])->toContain('1'));"],
            ],
        ],
        [
            'title' => 'Each and and',
            'prompt' => 'Write `Expectation` and `expect()` with `toBe($expected)`, `toBeGreaterThan($number)`, `and(mixed $value)` (a new expectation for another value) and `each(callable $check)`, which calls `$check` with a new `Expectation` for every item of the array and returns `$this`. `each()` on something that isn\'t an array throws an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                class Expectation
                {
                    public function __construct(public mixed $value) {}

                    public function toBe(mixed $expected): static
                    {
                        if ($this->value !== $expected) {
                            throw new RuntimeException('Not the same');
                        }

                        return $this;
                    }

                    public function each(callable $check): static
                    {
                        $check($this);

                        return $this;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Expectation
                {
                    public function __construct(public mixed $value) {}

                    public function toBe(mixed $expected): static
                    {
                        if ($this->value !== $expected) {
                            throw new RuntimeException('Expected ' . var_export($this->value, true) . ' to be ' . var_export($expected, true) . '.');
                        }

                        return $this;
                    }

                    public function toBeGreaterThan(int|float $number): static
                    {
                        if (! (is_int($this->value) || is_float($this->value)) || $this->value <= $number) {
                            throw new RuntimeException('Expected ' . var_export($this->value, true) . " to be greater than {$number}.");
                        }

                        return $this;
                    }

                    public function and(mixed $value): static
                    {
                        return new static($value);
                    }

                    public function each(callable $check): static
                    {
                        if (! is_array($this->value)) {
                            throw new InvalidArgumentException('each() needs an array.');
                        }

                        foreach ($this->value as $item) {
                            $check(new static($item));
                        }

                        return $this;
                    }
                }

                function expect(mixed $value): Expectation
                {
                    return new Expectation($value);
                }
                PHP,
            'hint' => '`and()` is just `new static($value)`. `each()` loops over the array and passes `new static($item)` to the callable.',
            'checks' => [
                ['label' => 'each checks every item', 'code' => <<<'PHP'
                    expect([9.99, 7.5, 1])->each(fn (Expectation $price) => $price->toBeGreaterThan(0));
                    return expect_throws(RuntimeException::class, fn () => expect([9.99, 0, 1])->each(fn ($price) => $price->toBeGreaterThan(0)));
                    PHP],
                ['label' => 'and switches the value', 'code' => <<<'PHP'
                    $e = expect(1)->toBe(1)->and('Dune')->toBe('Dune');
                    if ($e->value !== 'Dune') return 'and() should return an expectation for the new value.';
                    return expect_throws(RuntimeException::class, fn () => expect(1)->and(2)->toBe(1));
                    PHP],
                ['label' => 'each needs an array', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => expect('Dune')->each(fn (\$x) => \$x));"],
            ],
        ],
        [
            'title' => 'It should throw',
            'prompt' => 'Write `expectToThrow(callable $callback, string $class, ?string $message = null): void`. It calls the callback and passes only if it throws an instance of `$class` and, when `$message` is given, with exactly that message. Otherwise it throws a `RuntimeException`: `Expected CLASS to be thrown.` if nothing (or the wrong class) was thrown, or `Expected message [MESSAGE] but got [ACTUAL].`',
            'starter_code' => <<<'PHP'
                <?php

                function expectToThrow(callable $callback, string $class, ?string $message = null): void
                {
                    try {
                        $callback();
                    } catch (Throwable $e) {
                        return;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function expectToThrow(callable $callback, string $class, ?string $message = null): void
                {
                    try {
                        $callback();
                    } catch (Throwable $e) {
                        if (! $e instanceof $class) {
                            throw new RuntimeException("Expected {$class} to be thrown.");
                        }
                        if ($message !== null && $e->getMessage() !== $message) {
                            throw new RuntimeException("Expected message [{$message}] but got [{$e->getMessage()}].");
                        }

                        return;
                    }

                    throw new RuntimeException("Expected {$class} to be thrown.");
                }
                PHP,
            'hint' => 'Return from inside the `catch` when everything matches; throw after the `try` if nothing was thrown at all.',
            'checks' => [
                ['label' => 'Passes for the right exception', 'code' => <<<'PHP'
                    expectToThrow(fn () => throw new InvalidArgumentException('Price must be positive.'), InvalidArgumentException::class);
                    expectToThrow(fn () => throw new InvalidArgumentException('Price must be positive.'), LogicException::class, 'Price must be positive.');
                    return true;
                    PHP],
                ['label' => 'Nothing thrown', 'code' => <<<'PHP'
                    try {
                        expectToThrow(fn () => null, InvalidArgumentException::class);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Expected InvalidArgumentException to be thrown.');
                    }
                    return 'It should fail when nothing is thrown.';
                    PHP],
                ['label' => 'Wrong class or message', 'code' => <<<'PHP'
                    $wrongClass = expect_throws(RuntimeException::class, fn () => expectToThrow(fn () => throw new LogicException('x'), InvalidArgumentException::class));
                    if ($wrongClass !== true) return $wrongClass;
                    try {
                        expectToThrow(fn () => throw new LogicException('Too low.'), LogicException::class, 'Too high.');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Expected message [Too high.] but got [Too low.].');
                    }
                    return 'It should fail when the message differs.';
                    PHP],
            ],
        ],
        [
            'title' => 'Datasets',
            'prompt' => 'Write `runWithDataset(string $description, callable $test, array $dataset): array`. Run the test once per data set, passing the set\'s items as arguments (a set that isn\'t an array is a single argument). Return an array of name => result, where the name is the description plus ` with data set "KEY"` for string keys or ` with data set #KEY` for integer keys, and the result is `passed` or `failed: MESSAGE` if the test threw.',
            'starter_code' => <<<'PHP'
                <?php

                function runWithDataset(string $description, callable $test, array $dataset): array
                {
                    $results = [];
                    foreach ($dataset as $key => $arguments) {
                        $test($arguments);
                        $results[$description] = 'passed';
                    }

                    return $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runWithDataset(string $description, callable $test, array $dataset): array
                {
                    $results = [];
                    foreach ($dataset as $key => $arguments) {
                        $name = $description . (is_string($key) ? " with data set \"{$key}\"" : " with data set #{$key}");
                        try {
                            $test(...(is_array($arguments) ? array_values($arguments) : [$arguments]));
                            $results[$name] = 'passed';
                        } catch (Throwable $e) {
                            $results[$name] = 'failed: ' . $e->getMessage();
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Spread the arguments with `...`, and catch `Throwable` around each run so one failure doesn\'t stop the rest.',
            'checks' => [
                ['label' => 'Names and results', 'code' => <<<'PHP'
                    $results = runWithDataset('it rejects short ISBNs', function (string $isbn) {
                        if (strlen($isbn) >= 10) throw new RuntimeException("{$isbn} is long enough");
                    }, ['three digits' => ['123'], 'ok' => ['0306406152'], ['12']]);
                    return expect_same($results, [
                        'it rejects short ISBNs with data set "three digits"' => 'passed',
                        'it rejects short ISBNs with data set "ok"' => 'failed: 0306406152 is long enough',
                        'it rejects short ISBNs with data set #0' => 'passed',
                    ]);
                    PHP],
                ['label' => 'Several arguments, or one bare value', 'code' => <<<'PHP'
                    $results = runWithDataset('adds', function (int $a, int $b = 0) {
                        if ($a + $b !== 3) throw new RuntimeException('wrong');
                    }, [[1, 2], [2, 2], 3]);
                    return expect_same(array_values($results), ['passed', 'failed: wrong', 'passed']);
                    PHP],
            ],
        ],
        [
            'title' => 'Fresh for every test',
            'prompt' => 'Write `runTests(array $tests, callable $beforeEach): array`. `$tests` maps a name to a callable. Before each test, create a new `stdClass` context, pass it to `$beforeEach`, then pass the same context to the test. Return name => `passed` or `failed`, depending on whether the test threw.',
            'starter_code' => <<<'PHP'
                <?php

                function runTests(array $tests, callable $beforeEach): array
                {
                    $context = new stdClass();
                    $beforeEach($context);

                    $results = [];
                    foreach ($tests as $name => $test) {
                        $test($context);
                        $results[$name] = 'passed';
                    }

                    return $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runTests(array $tests, callable $beforeEach): array
                {
                    $results = [];
                    foreach ($tests as $name => $test) {
                        $context = new stdClass();
                        try {
                            $beforeEach($context);
                            $test($context);
                            $results[$name] = 'passed';
                        } catch (Throwable) {
                            $results[$name] = 'failed';
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Create the context inside the loop, so no test sees another test\'s changes.',
            'checks' => [
                ['label' => 'Each test gets a fresh context', 'code' => <<<'PHP'
                    $results = runTests([
                        'adds one' => function ($c) { $c->items[] = 'Dune'; if (count($c->items) !== 1) throw new RuntimeException(); },
                        'adds another' => function ($c) { $c->items[] = 'Emma'; if (count($c->items) !== 1) throw new RuntimeException(); },
                        'starts empty' => function ($c) { if ($c->items !== []) throw new RuntimeException(); },
                    ], function ($c) { $c->items = []; });
                    return expect_same($results, ['adds one' => 'passed', 'adds another' => 'passed', 'starts empty' => 'passed']);
                    PHP],
                ['label' => 'Failures are reported, and the rest still run', 'code' => <<<'PHP'
                    $results = runTests(['bad' => fn ($c) => throw new LogicException('x'), 'good' => fn ($c) => null], fn ($c) => null);
                    return expect_same($results, ['bad' => 'failed', 'good' => 'passed']);
                    PHP],
            ],
        ],
    ],
];
