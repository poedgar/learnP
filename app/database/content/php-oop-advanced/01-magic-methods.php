<?php

// Lesson 1 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'magic-methods',
    'title' => 'Magic methods',
    'summary' => 'Methods PHP calls for you: __get, __set, __isset and __unset for properties, __call and __callStatic for methods, __invoke for callable objects, and when not to use them.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class Settings
        {
            private array $values = ['theme' => 'dark'];

            public function __get(string $name): mixed
            {
                return $this->values[$name] ?? null;
            }

            public function __set(string $name, mixed $value): void
            {
                $this->values[$name] = $value;
            }
        }

        $settings = new Settings();
        $settings->language = 'en';
        echo $settings->theme . ' / ' . $settings->language;
        PHP,

    'quiz' => [
        [
            'question' => 'When does PHP call an object\'s `__get()` method?',
            'options' => [
                'Every time any property is read',
                'When code reads a property that doesn\'t exist or isn\'t visible to it',
                'When the object is created',
                'Only when you call `$object->__get()` yourself',
            ],
            'correct_option' => 1,
            'explanation' => 'Real, visible properties are always read directly. `__get()` is the fallback for everything else.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public string $title = 'Dune';

                    public function __get(string $name): string
                    {
                        return 'magic';
                    }
                }

                $book = new Book();
                echo $book->title . ' ' . $book->author;
                PHP,
            'options' => ['`magic magic`', '`Dune magic`', '`Dune `', 'An error: `author` is undefined'],
            'correct_option' => 1,
            'explanation' => '`title` is a real public property, so it\'s read directly. `author` doesn\'t exist, so `__get(\'author\')` answers.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Settings
                {
                    private array $values = ['theme' => 'dark'];

                    public function __get(string $name): mixed
                    {
                        return $this->values[$name] ?? null;
                    }
                }

                var_dump(isset((new Settings())->theme));
                PHP,
            'options' => ['`bool(true)`', '`bool(false)`', '`string(4) "dark"`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`isset()` doesn\'t call `__get()`; it calls `__isset()`, and without one the answer is `false`. Add `__isset()` whenever you add `__get()`.',
        ],
        [
            'question' => 'What does PHP pass to `__call(string $method, array $arguments)` for `$query->whereGenre(\'Fantasy\')`?',
            'options' => [
                "`'genre'` and `'Fantasy'`",
                "`'whereGenre'` and `['Fantasy']`",
                "`'whereGenre'` and `'Fantasy'`",
                'Nothing; `__call()` takes no arguments',
            ],
            'correct_option' => 1,
            'explanation' => 'The method name exactly as it was called, and every argument collected in an array.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Multiplier
                {
                    public function __construct(private int $by) {}

                    public function __invoke(int $number): int
                    {
                        return $number * $this->by;
                    }
                }

                $triple = new Multiplier(3);
                echo $triple(4) + $triple(1);
                PHP,
            'options' => ['`15`', '`12`', '`7`', 'An error: objects can\'t be called'],
            'correct_option' => 0,
            'explanation' => '`$triple(4)` calls `__invoke(4)`, giving 12, and `$triple(1)` gives 3.',
        ],
        [
            'question' => 'A class has an `__invoke()` method. What does `is_callable(new Discount(10))` return?',
            'options' => ['`true`', '`false`', '`null`', 'It depends on the argument types'],
            'correct_option' => 0,
            'explanation' => 'An invokable object is callable, so it can be passed to `array_map()`, `usort()` and anything else that takes a `callable`.',
        ],
        [
            'question' => 'Which magic method handles `Money::gbp(9.99)` when `Money` has no static method `gbp`?',
            'options' => ['`__call()`', '`__callStatic()`', '`__invoke()`', '`__get()`'],
            'correct_option' => 1,
            'explanation' => '`__call()` is for calls on an object, `__callStatic()` for calls on the class.',
        ],
        [
            'question' => 'What is the main danger of a `__get()` that returns `null` for any unknown name?',
            'options' => [
                'It makes the object slower to create',
                'A typo in a property name gives `null` instead of an error, so the bug goes unnoticed',
                'It stops `__toString()` from working',
                'PHP refuses to run classes like that',
            ],
            'correct_option' => 1,
            'explanation' => '`$settings->thmee` quietly gives `null`. Throwing for unknown names, or using real properties, catches the mistake straight away.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A bag of attributes',
            'prompt' => "Write a class `Attributes` that keeps any values in a private array, so that:\n\n- `\$attributes->colour = 'red'` stores a value;\n- `\$attributes->colour` reads it back, and an attribute that was never set reads as `null`;\n- `isset(\$attributes->colour)` is `true` only for attributes that were set.",
            'starter_code' => <<<'PHP'
                <?php

                class Attributes
                {
                    private array $values = [];

                    // Add __get, __set and __isset here.
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Attributes
                {
                    private array $values = [];

                    public function __get(string $name): mixed
                    {
                        return $this->values[$name] ?? null;
                    }

                    public function __set(string $name, mixed $value): void
                    {
                        $this->values[$name] = $value;
                    }

                    public function __isset(string $name): bool
                    {
                        return isset($this->values[$name]);
                    }
                }
                PHP,
            'hint' => '`__set()` stores into `$this->values[$name]`, `__get()` returns `$this->values[$name] ?? null`, and `__isset()` returns `isset($this->values[$name])`.',
            'checks' => [
                ['label' => 'A value can be set and read back', 'code' => <<<'PHP'
                    $attributes = new Attributes();
                    $attributes->colour = 'red';
                    return expect_same($attributes->colour, 'red');
                    PHP],
                ['label' => 'The value is kept privately, not as a new public property', 'code' => <<<'PHP'
                    $attributes = new Attributes();
                    $attributes->colour = 'red';
                    return ! array_key_exists('colour', get_object_vars($attributes)) ?: 'colour became a public property. Store it in the private array with __set().';
                    PHP],
                ['label' => 'An attribute that was never set reads as null', 'code' => <<<'PHP'
                    if (! method_exists('Attributes', '__get')) return 'Attributes needs a __get() method.';
                    return expect_same((new Attributes())->size, null);
                    PHP],
                ['label' => 'isset() is true only for attributes that were set', 'code' => <<<'PHP'
                    $attributes = new Attributes();
                    $attributes->colour = 'red';
                    return expect_same([isset($attributes->colour), isset($attributes->size)], [true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Count the calls',
            'prompt' => "Write a class `CallCounter` that wraps any object. Every method called on the counter is passed on to the wrapped object, and its result returned, while the counter remembers how many times each method was called. `calls(string \$method): int` gives that number (0 for methods never called).\n\n```\n\$counter = new CallCounter(new Book('Dune'));\n\$counter->describe(); // 'Dune'\n\$counter->calls('describe'); // 1\n```",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(private string $title) {}

                    public function describe(): string
                    {
                        return $this->title;
                    }

                    public function rename(string $title): void
                    {
                        $this->title = $title;
                    }
                }

                class CallCounter
                {
                    private array $counts = [];

                    public function __construct(private object $target) {}

                    public function calls(string $method): int
                    {
                        return 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(private string $title) {}

                    public function describe(): string
                    {
                        return $this->title;
                    }

                    public function rename(string $title): void
                    {
                        $this->title = $title;
                    }
                }

                class CallCounter
                {
                    private array $counts = [];

                    public function __construct(private object $target) {}

                    public function __call(string $method, array $arguments): mixed
                    {
                        $this->counts[$method] = ($this->counts[$method] ?? 0) + 1;

                        return $this->target->$method(...$arguments);
                    }

                    public function calls(string $method): int
                    {
                        return $this->counts[$method] ?? 0;
                    }
                }
                PHP,
            'hint' => 'In `__call()`, add one to `$this->counts[$method]`, then return `$this->target->$method(...$arguments)`. `...` passes the array as separate arguments.',
            'checks' => [
                ['label' => 'Calls are passed on and their results returned', 'code' => <<<'PHP'
                    $counter = new CallCounter(new Book('Dune'));
                    return expect_same($counter->describe(), 'Dune');
                    PHP],
                ['label' => 'Arguments are passed on too', 'code' => <<<'PHP'
                    $book = new Book('Dune');
                    $counter = new CallCounter($book);
                    $counter->rename('Dune Messiah');
                    return expect_same($book->describe(), 'Dune Messiah');
                    PHP],
                ['label' => 'Each method is counted separately', 'code' => <<<'PHP'
                    $counter = new CallCounter(new Book('Dune'));
                    $counter->describe();
                    $counter->describe();
                    $counter->rename('Emma');
                    return expect_same([$counter->calls('describe'), $counter->calls('rename'), $counter->calls('other')], [2, 1, 0]);
                    PHP],
            ],
        ],
        [
            'title' => 'Find by anything',
            'prompt' => "`Catalogue` holds a list of books, each an array with `title`, `author` and `genre`. Add `__call()` so that `findByAuthor('Ursula K. Le Guin')`, `findByGenre('Fantasy')` and so on return the **titles** of the matching books, in their original order, for any key the books have. Any other method name must throw a `BadMethodCallException`.",
            'starter_code' => <<<'PHP'
                <?php

                class Catalogue
                {
                    public function __construct(private array $books) {}
                }

                $catalogue = new Catalogue([
                    ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'genre' => 'Fantasy'],
                    ['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'Science Fiction'],
                ]);
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Catalogue
                {
                    public function __construct(private array $books) {}

                    public function __call(string $method, array $arguments): array
                    {
                        if (! str_starts_with($method, 'findBy')) {
                            throw new BadMethodCallException("There is no method $method().");
                        }

                        $key = lcfirst(substr($method, 6));
                        $titles = [];
                        foreach ($this->books as $book) {
                            if (($book[$key] ?? null) === $arguments[0]) {
                                $titles[] = $book['title'];
                            }
                        }

                        return $titles;
                    }
                }

                $catalogue = new Catalogue([
                    ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'genre' => 'Fantasy'],
                    ['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'Science Fiction'],
                ]);
                PHP,
            'hint' => '`substr($method, 6)` turns `findByAuthor` into `Author`, and `lcfirst()` makes it `author`. Then keep the books whose value for that key equals `$arguments[0]`.',
            'checks' => [
                ['label' => 'findByAuthor() returns that author\'s titles in order', 'code' => <<<'PHP'
                    $catalogue = new Catalogue([
                        ['title' => 'A Wizard of Earthsea', 'author' => 'Ursula K. Le Guin', 'genre' => 'Fantasy'],
                        ['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'Science Fiction'],
                        ['title' => 'The Left Hand of Darkness', 'author' => 'Ursula K. Le Guin', 'genre' => 'Science Fiction'],
                    ]);
                    return expect_same($catalogue->findByAuthor('Ursula K. Le Guin'), ['A Wizard of Earthsea', 'The Left Hand of Darkness']);
                    PHP],
                ['label' => 'findByGenre() works too, and no match gives an empty list', 'code' => <<<'PHP'
                    $catalogue = new Catalogue([
                        ['title' => 'Dune', 'author' => 'Frank Herbert', 'genre' => 'Science Fiction'],
                        ['title' => 'Circe', 'author' => 'Madeline Miller', 'genre' => 'Fantasy'],
                    ]);
                    return expect_same([$catalogue->findByGenre('Fantasy'), $catalogue->findByGenre('Horror')], [['Circe'], []]);
                    PHP],
                ['label' => 'Any other method throws a BadMethodCallException', 'code' => <<<'PHP'
                    return expect_throws(BadMethodCallException::class, fn () => (new Catalogue([]))->deleteEverything());
                    PHP],
            ],
        ],
        [
            'title' => 'A price formatter you can call',
            'prompt' => "Write a class `PriceFormatter` whose constructor takes a currency symbol, and which can be **called** with a price to format it: the symbol followed by the price with exactly two decimal places.\n\n`(new PriceFormatter('£'))(9.5)` gives `£9.50`, so `array_map(new PriceFormatter('€'), [3, 12.25])` gives `['€3.00', '€12.25']`.",
            'starter_code' => <<<'PHP'
                <?php

                class PriceFormatter
                {
                    public function __construct(private string $symbol) {}

                    public function format(float $price): string
                    {
                        return $this->symbol . number_format($price, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class PriceFormatter
                {
                    public function __construct(private string $symbol) {}

                    public function __invoke(float $price): string
                    {
                        return $this->symbol . number_format($price, 2, '.', '');
                    }
                }
                PHP,
            'hint' => 'Add `public function __invoke(float $price): string`. `number_format($price, 2, \'.\', \'\')` gives two decimal places.',
            'checks' => [
                ['label' => 'A PriceFormatter can be called', 'code' => "return is_callable(new PriceFormatter('£')) ?: 'Give PriceFormatter an __invoke() method.';"],
                ['label' => "Calling it with 9.5 gives '£9.50'", 'code' => "\$format = new PriceFormatter('£');\nreturn expect_same(\$format(9.5), '£9.50');"],
                ['label' => 'It works with array_map()', 'code' => "return expect_same(array_map(new PriceFormatter('€'), [3, 12.25]), ['€3.00', '€12.25']);"],
            ],
        ],
        [
            'title' => 'Money in any currency',
            'prompt' => "Give `Money` a `__callStatic()` so that a static call named after a three-letter currency code creates money in that currency: `Money::gbp(9.99)` is `new Money(9.99, 'GBP')` and `Money::eur(5)` is `new Money(5.0, 'EUR')`. A name that isn't exactly three letters, such as `Money::dollars(1)`, throws a `BadMethodCallException`.",
            'starter_code' => <<<'PHP'
                <?php

                class Money
                {
                    public function __construct(public float $amount, public string $currency) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public static function __callStatic(string $name, array $arguments): static
                    {
                        if (! preg_match('/^[a-z]{3}$/i', $name)) {
                            throw new BadMethodCallException("Money::$name() is not a currency.");
                        }

                        return new static((float) $arguments[0], strtoupper($name));
                    }
                }
                PHP,
            'hint' => '`public static function __callStatic(string $name, array $arguments)`. Check the name with `preg_match(\'/^[a-z]{3}$/i\', $name)`, then return `new static($arguments[0], strtoupper($name))`.',
            'checks' => [
                ['label' => 'Money::gbp(9.99) is 9.99 GBP', 'code' => <<<'PHP'
                    $money = Money::gbp(9.99);
                    if (! $money instanceof Money) return 'Money::gbp() should return a Money object.';
                    return expect_same([$money->amount, $money->currency], [9.99, 'GBP']);
                    PHP],
                ['label' => 'Any currency code works, and amounts become floats', 'code' => <<<'PHP'
                    $money = Money::eur(5);
                    return expect_same([$money->amount, $money->currency], [5.0, 'EUR']);
                    PHP],
                ['label' => 'Other names throw a BadMethodCallException', 'code' => 'return expect_throws(BadMethodCallException::class, fn () => Money::dollars(1));'],
            ],
        ],
        [
            'title' => 'Settings that catch typos',
            'prompt' => "Fix `Settings` so that typos can't go unnoticed. Its defaults are `theme` (`'dark'`) and `language` (`'en'`).\n\n- Reading or setting any **other** name throws an `OutOfBoundsException`.\n- Known settings can be read and changed.\n- An object of the class used as text gives every setting as `name=value`, joined by `, `: `theme=dark, language=en`.",
            'starter_code' => <<<'PHP'
                <?php

                class Settings
                {
                    private array $values = ['theme' => 'dark', 'language' => 'en'];

                    public function __get(string $name): mixed
                    {
                        return $this->values[$name] ?? null;
                    }

                    public function __set(string $name, mixed $value): void
                    {
                        $this->values[$name] = $value;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Settings
                {
                    private array $values = ['theme' => 'dark', 'language' => 'en'];

                    public function __get(string $name): mixed
                    {
                        $this->guard($name);

                        return $this->values[$name];
                    }

                    public function __set(string $name, mixed $value): void
                    {
                        $this->guard($name);
                        $this->values[$name] = $value;
                    }

                    public function __toString(): string
                    {
                        $pairs = [];
                        foreach ($this->values as $name => $value) {
                            $pairs[] = "$name=$value";
                        }

                        return implode(', ', $pairs);
                    }

                    private function guard(string $name): void
                    {
                        if (! array_key_exists($name, $this->values)) {
                            throw new OutOfBoundsException("There is no setting called $name.");
                        }
                    }
                }
                PHP,
            'hint' => 'Check `array_key_exists($name, $this->values)` in both `__get()` and `__set()`, and throw when it\'s false. Build `__toString()` from the array with a loop and `implode(\', \', …)`.',
            'checks' => [
                ['label' => 'Known settings can be read and changed', 'code' => <<<'PHP'
                    $settings = new Settings();
                    $settings->theme = 'light';
                    return expect_same([$settings->theme, $settings->language], ['light', 'en']);
                    PHP],
                ['label' => 'Reading an unknown setting throws an OutOfBoundsException', 'code' => 'return expect_throws(OutOfBoundsException::class, fn () => (new Settings())->thmee);'],
                ['label' => 'Setting an unknown setting throws an OutOfBoundsException', 'code' => <<<'PHP'
                    return expect_throws(OutOfBoundsException::class, function () {
                        $settings = new Settings();
                        $settings->fontSize = 12;
                    });
                    PHP],
                ['label' => "Used as text, it gives 'theme=dark, language=en'", 'code' => <<<'PHP'
                    $settings = new Settings();
                    if (! $settings instanceof Stringable) return 'Give Settings a __toString() method.';
                    $settings->language = 'fr';
                    return expect_same((string) $settings, 'theme=dark, language=fr');
                    PHP],
            ],
        ],
    ],
];
