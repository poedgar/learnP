<?php

// Lesson 3 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'closures-and-callables',
    'title' => 'Closures and callables',
    'summary' => 'Treat functions as values: closures and use, arrow functions, every shape of callable, first-class callable syntax, functions that build functions, and Closure::bind.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function discountOf(int $percent): Closure
        {
            return fn (float $price): float => round($price * (100 - $percent) / 100, 2);
        }

        $prices = [19.99, 7.99, 13.99];
        print_r(array_map(discountOf(25), $prices));
        print_r(array_map(strval(...), $prices));
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $greeting = 'Hello';
                $greet = function (string $name) use ($greeting): string {
                    return "$greeting, $name";
                };
                $greeting = 'Goodbye';

                echo $greet('Amara');
                PHP,
            'options' => ['`Hello, Amara`', '`Goodbye, Amara`', '`, Amara`', 'An error'],
            'correct_option' => 0,
            'explanation' => '`use ($greeting)` copies the value when the closure is created. Changing the variable later doesn\'t affect it.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $count = 0;
                $add = function () use (&$count): void {
                    $count++;
                };
                $add();
                $add();

                echo $count;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error'],
            'correct_option' => 2,
            'explanation' => 'With `&`, the closure shares the variable rather than copying it, so its changes are seen outside.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $total = 0;
                array_map(fn (int $n) => $total += $n, [1, 2, 3]);

                echo $total;
                PHP,
            'options' => ['`6`', '`3`', '`0`', 'An error: arrow functions can\'t use outside variables'],
            'correct_option' => 2,
            'explanation' => 'An arrow function captures outside variables by value. It adds to its own copy of `$total`; the outside one stays 0.',
        ],
        [
            'question' => 'What is `$length` after `$length = strlen(...);`?',
            'options' => [
                'The length of an empty string, 0',
                'A `Closure` that calls `strlen()`',
                'The string `\'strlen\'`',
                'An error: `strlen()` needs an argument',
            ],
            'correct_option' => 1,
            'explanation' => 'With `...` as the only argument, PHP doesn\'t call the function; it creates a `Closure` for it.',
        ],
        [
            'question' => 'What does the callable `[$book, \'describe\']` do when called?',
            'options' => [
                'Calls `describe($book)`',
                'Calls `$book->describe()`',
                'Calls `Book::describe()` statically',
                'Nothing; arrays aren\'t callable',
            ],
            'correct_option' => 1,
            'explanation' => 'An array of an object and a method name means "call this method on this object".',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function adder(int $amount): Closure
                {
                    return fn (int $n): int => $n + $amount;
                }

                $addFive = adder(5);
                $addTen = adder(10);

                echo $addFive(1) + $addTen(1);
                PHP,
            'options' => ['`17`', '`16`', '`12`', '`22`'],
            'correct_option' => 0,
            'explanation' => 'Each call to `adder()` makes a separate closure with its own `$amount`: 6 + 11 = 17.',
        ],
        [
            'question' => 'What does `$closure->call($book)` do?',
            'options' => [
                'Calls the closure with `$book` as its first argument',
                'Calls the closure as if it were a method of `$book`, with access to its private members',
                'Copies the closure into `$book` permanently',
                'Checks whether `$book` is callable',
            ],
            'correct_option' => 1,
            'explanation' => 'Inside the call, `$this` is `$book`, and the closure has the access of `$book`\'s class.',
        ],
        [
            'question' => 'Why prefer `trim(...)` over the string `\'trim\'` when passing a function?',
            'options' => [
                'The string form doesn\'t work with `array_map()`',
                'PHP checks that the function exists straight away, and editors can follow it',
                'The `...` form is faster to type',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'A typo in `\'trmi\'` is only found when it\'s called. `trmi(...)` fails immediately, and tools understand it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Within budget',
            'prompt' => "Write a function `atMost(float \$limit): Closure` that returns a closure taking a price and returning `true` when the price is no more than `\$limit`.\n\n`array_filter([7.99, 13.99, 10.0], atMost(10.0))` keeps 7.99 and 10.0.",
            'starter_code' => <<<'PHP'
                <?php

                function atMost(float $limit): Closure
                {
                    return fn (float $price): bool => true;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function atMost(float $limit): Closure
                {
                    return fn (float $price): bool => $price <= $limit;
                }
                PHP,
            'hint' => 'The arrow function can use `$limit` directly: `fn (float $price): bool => $price <= $limit`.',
            'checks' => [
                ['label' => 'atMost() returns a Closure', 'code' => 'return (atMost(10.0) instanceof Closure) ?: \'atMost() should return a Closure.\';'],
                ['label' => 'It keeps prices up to and including the limit', 'code' => 'return expect_same(array_values(array_filter([7.99, 13.99, 10.0], atMost(10.0))), [7.99, 10.0]);'],
                ['label' => 'Each closure keeps its own limit', 'code' => <<<'PHP'
                    $cheap = atMost(5.0);
                    $any = atMost(100.0);
                    return expect_same([$cheap(9.99), $any(9.99)], [false, true]);
                    PHP],
            ],
        ],
        [
            'title' => 'A counter that remembers',
            'prompt' => 'Write a function `makeCounter(): Closure`. Each closure it returns counts its own calls: the first call returns 1, the next 2, and so on. Two counters count separately.',
            'starter_code' => <<<'PHP'
                <?php

                function makeCounter(): Closure
                {
                    $count = 0;

                    return function () use ($count): int {
                        $count++;

                        return $count;
                    };
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function makeCounter(): Closure
                {
                    $count = 0;

                    return function () use (&$count): int {
                        return ++$count;
                    };
                }
                PHP,
            'hint' => 'The closure captures a copy of `$count`, which starts at 0 every time. Capture it by reference with `use (&$count)`.',
            'checks' => [
                ['label' => 'Calls count up from 1', 'code' => <<<'PHP'
                    $next = makeCounter();
                    return expect_same([$next(), $next(), $next()], [1, 2, 3]);
                    PHP],
                ['label' => 'Two counters count separately', 'code' => <<<'PHP'
                    $a = makeCounter();
                    $b = makeCounter();
                    $a();
                    $a();
                    return expect_same([$a(), $b()], [3, 1]);
                    PHP],
            ],
        ],
        [
            'title' => 'Compose',
            'prompt' => "Write a function `compose(callable ...\$functions): Closure` that returns a closure applying each function in turn, left to right, to a value: `compose(trim(...), strtoupper(...))(' dune ')` gives `'DUNE'`. With no functions at all, the closure returns its value unchanged.",
            'starter_code' => <<<'PHP'
                <?php

                function compose(callable ...$functions): Closure
                {
                    return fn (mixed $value): mixed => $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function compose(callable ...$functions): Closure
                {
                    return function (mixed $value) use ($functions): mixed {
                        foreach ($functions as $function) {
                            $value = $function($value);
                        }

                        return $value;
                    };
                }
                PHP,
            'hint' => 'Capture `$functions` with `use`, then loop: `$value = $function($value);`. `array_reduce()` works too.',
            'checks' => [
                ['label' => "compose(trim(...), strtoupper(...)) turns ' dune ' into 'DUNE'", 'code' => "return expect_same(compose(trim(...), strtoupper(...))(' dune '), 'DUNE');"],
                ['label' => 'The functions run left to right', 'code' => <<<'PHP'
                    $f = compose(fn ($n) => $n + 1, fn ($n) => $n * 10);
                    return expect_same($f(2), 30);
                    PHP],
                ['label' => 'Any kind of callable works', 'code' => <<<'PHP'
                    $f = compose('strrev', new class {
                        public function __invoke(string $text): string
                        {
                            return ucfirst($text);
                        }
                    });
                    return expect_same($f('enud'), 'Dune');
                    PHP],
                ['label' => 'With no functions, the value comes back unchanged', 'code' => 'return expect_same(compose()(42), 42);'],
            ],
        ],
        [
            'title' => 'Sort by any field',
            'prompt' => "Books are arrays such as `['title' => 'Dune', 'price' => 19.99]`. Write a function `by(string \$field): Closure` that returns a comparison closure for `usort()`, ordering books by that field from smallest to largest.\n\n`usort(\$books, by('price'))` sorts by price, and `usort(\$books, by('title'))` by title.",
            'starter_code' => <<<'PHP'
                <?php

                function by(string $field): Closure
                {
                    return fn (array $a, array $b): int => 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function by(string $field): Closure
                {
                    return fn (array $a, array $b): int => $a[$field] <=> $b[$field];
                }
                PHP,
            'hint' => 'Return `fn (array $a, array $b): int => $a[$field] <=> $b[$field]`.',
            'checks' => [
                ['label' => 'Sorting by price', 'code' => <<<'PHP'
                    $books = [['title' => 'Dune', 'price' => 19.99], ['title' => 'Emma', 'price' => 7.99], ['title' => 'Circe', 'price' => 13.99]];
                    usort($books, by('price'));
                    return expect_same(array_column($books, 'title'), ['Emma', 'Circe', 'Dune']);
                    PHP],
                ['label' => 'Sorting by title', 'code' => <<<'PHP'
                    $books = [['title' => 'Dune', 'price' => 19.99], ['title' => 'Emma', 'price' => 7.99], ['title' => 'Circe', 'price' => 13.99]];
                    usort($books, by('title'));
                    return expect_same(array_column($books, 'title'), ['Circe', 'Dune', 'Emma']);
                    PHP],
            ],
        ],
        [
            'title' => 'Peek inside',
            'prompt' => 'For a debugging tool, write a function `readProperty(object $object, string $name): mixed` that returns the value of any property of an object, even a private or protected one, using a closure bound to the object (`Closure::bind()` or `->call()`).',
            'starter_code' => <<<'PHP'
                <?php

                function readProperty(object $object, string $name): mixed
                {
                    return $object->$name;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function readProperty(object $object, string $name): mixed
                {
                    return (fn () => $this->$name)->call($object);
                }
                PHP,
            'hint' => 'Inside a closure bound to `$object`, `$this->$name` is allowed. `(fn () => $this->$name)->call($object)` does it in one line.',
            'checks' => [
                ['label' => 'It reads a private property', 'code' => <<<'PHP'
                    $book = new class {
                        private string $secret = 'signed';
                    };
                    return expect_same(readProperty($book, 'secret'), 'signed');
                    PHP],
                ['label' => 'It reads protected and public properties too', 'code' => <<<'PHP'
                    $book = new class {
                        protected int $stock = 3;
                        public string $title = 'Dune';
                    };
                    return expect_same([readProperty($book, 'stock'), readProperty($book, 'title')], [3, 'Dune']);
                    PHP],
            ],
        ],
        [
            'title' => 'Remember the answers',
            'prompt' => 'Write a function `memoize(callable $function): Closure`. The closure it returns calls `$function` with the same arguments and returns its result, but remembers results: calling it again with the same arguments returns the remembered result without calling `$function` again.',
            'starter_code' => <<<'PHP'
                <?php

                function memoize(callable $function): Closure
                {
                    return fn (mixed ...$arguments): mixed => $function(...$arguments);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function memoize(callable $function): Closure
                {
                    $results = [];

                    return function (mixed ...$arguments) use ($function, &$results): mixed {
                        $key = serialize($arguments);
                        if (! array_key_exists($key, $results)) {
                            $results[$key] = $function(...$arguments);
                        }

                        return $results[$key];
                    };
                }
                PHP,
            'hint' => 'Keep a `$results` array captured by reference. Use `serialize($arguments)` as the key, and only call `$function` when the key isn\'t there yet.',
            'checks' => [
                ['label' => 'Results are the same as calling the function', 'code' => <<<'PHP'
                    $add = memoize(fn (int $a, int $b) => $a + $b);
                    return expect_same([$add(2, 3), $add(10, 1)], [5, 11]);
                    PHP],
                ['label' => 'The same arguments call the function only once', 'code' => <<<'PHP'
                    $calls = 0;
                    $square = memoize(function (int $n) use (&$calls) {
                        $calls++;
                        return $n * $n;
                    });
                    $square(4);
                    $square(4);
                    $square(5);
                    return expect_same($calls, 2);
                    PHP],
                ['label' => 'A remembered null still counts', 'code' => <<<'PHP'
                    $calls = 0;
                    $nothing = memoize(function () use (&$calls) {
                        $calls++;
                        return null;
                    });
                    $nothing();
                    $nothing();
                    return expect_same($calls, 1);
                    PHP],
            ],
        ],
    ],
];
