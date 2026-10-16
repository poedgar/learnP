<?php

// Lesson 2 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'builder-and-singleton',
    'title' => 'Builder and Singleton',
    'summary' => 'Assemble complex objects step by step with a builder that checks them before they exist, and learn why Singleton is best replaced by passing one object in.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class SelectBuilder
        {
            private array $wheres = [];

            public function __construct(private string $table) {}

            public function where(string $condition): static
            {
                $this->wheres[] = $condition;
                return $this;
            }

            public function toSql(): string
            {
                $sql = "SELECT * FROM {$this->table}";
                return $this->wheres ? $sql . ' WHERE ' . implode(' AND ', $this->wheres) : $sql;
            }
        }

        echo (new SelectBuilder('books'))->where('price < 10')->toSql();
        PHP,

    'quiz' => [
        [
            'question' => 'What problem does the Builder pattern solve?',
            'options' => [
                'Making sure only one object exists',
                'Creating objects that need many, often optional, parts, step by step and with clear names',
                'Copying objects',
                'Choosing between several classes',
            ],
            'correct_option' => 1,
            'explanation' => 'Each step has a name, optional parts can simply be skipped, and `build()` produces the result.',
        ],
        [
            'question' => 'Why do a builder\'s methods usually `return $this`?',
            'options' => [
                'So the steps can be chained one after another',
                'Because PHP requires it',
                'So the builder can\'t be changed',
                'To make the builder a singleton',
            ],
            'correct_option' => 0,
            'explanation' => '`$builder->add(…)->giftWrapped()->build()` works because each step returns the same builder.',
        ],
        [
            'question' => 'Where is the best place to check that the finished object is valid?',
            'options' => ['In every setter', 'In `build()`, once all the parts are known', 'In the caller', 'Nowhere'],
            'correct_option' => 1,
            'explanation' => 'Only `build()` sees the whole thing, and an invalid object then never comes into existence.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Counter
                {
                    private static ?Counter $instance = null;
                    public int $count = 0;

                    private function __construct() {}

                    public static function instance(): self
                    {
                        return self::$instance ??= new self();
                    }
                }

                Counter::instance()->count++;
                Counter::instance()->count++;
                echo Counter::instance()->count;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error: the constructor is private'],
            'correct_option' => 2,
            'explanation' => 'Every call returns the same single object, so both increments change it.',
        ],
        [
            'question' => 'How does a singleton stop people creating a second instance?',
            'options' => [
                'With a `final` class',
                'With a private constructor, so only its own static method can call `new`',
                'With a readonly property',
                'It can\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'Outside code can\'t call `new Config()`; it has to go through `Config::instance()`.',
        ],
        [
            'question' => 'Why is a singleton hard to test?',
            'options' => [
                'Because it\'s slow',
                'Because every test shares the one hidden instance, and you can\'t hand in a different one',
                'Because PHPUnit refuses singletons',
                'Because singletons can\'t have methods',
            ],
            'correct_option' => 1,
            'explanation' => 'State leaks between tests, and code reaching for `Config::instance()` can\'t be given a fake.',
        ],
        [
            'question' => 'What is the usual alternative to a singleton?',
            'options' => [
                'A global variable',
                'Creating one object at the start and passing it in to whatever needs it',
                'A static class with only static methods',
                'Creating a new object every time',
            ],
            'correct_option' => 1,
            'explanation' => 'You still have just one, but every dependency is visible in constructors and replaceable in tests.',
        ],
        [
            'question' => 'What does `self::$instance ??= new self();` do?',
            'options' => [
                'Creates a new instance every time',
                'Creates the instance the first time, and reuses it after that',
                'Deletes the instance',
                'Compares two instances',
            ],
            'correct_option' => 1,
            'explanation' => '`??=` only assigns when the property is still `null`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A pizza builder',
            'prompt' => "Write `PizzaBuilder` with chainable `size(string \$size)`, `topping(string \$topping)` (may be called many times) and `extraCheese()`, and `build(): Pizza`. Without a size, the size is `'medium'`. `Pizza` (given) is readonly.",
            'starter_code' => <<<'PHP'
                <?php

                class Pizza
                {
                    public function __construct(
                        public readonly string $size,
                        public readonly array $toppings,
                        public readonly bool $extraCheese,
                    ) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Pizza
                {
                    public function __construct(
                        public readonly string $size,
                        public readonly array $toppings,
                        public readonly bool $extraCheese,
                    ) {}
                }

                class PizzaBuilder
                {
                    private string $size = 'medium';
                    private array $toppings = [];
                    private bool $extraCheese = false;

                    public function size(string $size): static
                    {
                        $this->size = $size;
                        return $this;
                    }

                    public function topping(string $topping): static
                    {
                        $this->toppings[] = $topping;
                        return $this;
                    }

                    public function extraCheese(): static
                    {
                        $this->extraCheese = true;
                        return $this;
                    }

                    public function build(): Pizza
                    {
                        return new Pizza($this->size, $this->toppings, $this->extraCheese);
                    }
                }
                PHP,
            'hint' => 'Keep each part in a private property with a default, make every step `return $this`, and create the `Pizza` in `build()`.',
            'checks' => [
                ['label' => 'A fully built pizza', 'code' => <<<'PHP'
                    $pizza = (new PizzaBuilder())->size('large')->topping('olives')->topping('basil')->extraCheese()->build();
                    return expect_same([$pizza->size, $pizza->toppings, $pizza->extraCheese], ['large', ['olives', 'basil'], true]);
                    PHP],
                ['label' => 'Defaults', 'code' => <<<'PHP'
                    $pizza = (new PizzaBuilder())->build();
                    return expect_same([$pizza->size, $pizza->toppings, $pizza->extraCheese], ['medium', [], false]);
                    PHP],
            ],
        ],
        [
            'title' => 'No empty orders',
            'prompt' => 'Finish `OrderBuilder::build()` so that it throws a `LogicException` when no item was added, or when the customer name is empty, and otherwise returns the `Order`.',
            'starter_code' => <<<'PHP'
                <?php

                class Order
                {
                    public function __construct(public readonly string $customer, public readonly array $items) {}
                }

                class OrderBuilder
                {
                    private array $items = [];

                    public function __construct(private string $customer) {}

                    public function add(string $title, int $quantity = 1): static
                    {
                        $this->items[$title] = ($this->items[$title] ?? 0) + $quantity;
                        return $this;
                    }

                    public function build(): Order
                    {
                        return new Order($this->customer, $this->items);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Order
                {
                    public function __construct(public readonly string $customer, public readonly array $items) {}
                }

                class OrderBuilder
                {
                    private array $items = [];

                    public function __construct(private string $customer) {}

                    public function add(string $title, int $quantity = 1): static
                    {
                        $this->items[$title] = ($this->items[$title] ?? 0) + $quantity;
                        return $this;
                    }

                    public function build(): Order
                    {
                        if (trim($this->customer) === '') {
                            throw new LogicException('An order needs a customer.');
                        }
                        if ($this->items === []) {
                            throw new LogicException('An order needs at least one item.');
                        }

                        return new Order($this->customer, $this->items);
                    }
                }
                PHP,
            'hint' => 'Two checks at the top of `build()`, each throwing a `LogicException`.',
            'checks' => [
                ['label' => 'A good order is built', 'code' => <<<'PHP'
                    $order = (new OrderBuilder('Amara'))->add('Dune')->add('Dune', 2)->add('Emma')->build();
                    return expect_same($order->items, ['Dune' => 3, 'Emma' => 1]);
                    PHP],
                ['label' => 'No items throws', 'code' => "return expect_throws(LogicException::class, fn () => (new OrderBuilder('Amara'))->build());"],
                ['label' => 'No customer throws', 'code' => "return expect_throws(LogicException::class, fn () => (new OrderBuilder('  '))->add('Dune')->build());"],
            ],
        ],
        [
            'title' => 'An email builder',
            'prompt' => 'Write `EmailBuilder` with chainable `to(string)`, `cc(string)` (may be called many times), `subject(string)` and `body(string)`, and `build(): string` producing the lines `To: …`, then `Cc: …` (the addresses joined by `, `, and only if there are any), then `Subject: …`, an empty line and the body, joined by `\\n`.',
            'starter_code' => <<<'PHP'
                <?php

                class EmailBuilder
                {
                    public function build(): string
                    {
                        return '';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class EmailBuilder
                {
                    private string $to = '';
                    private array $cc = [];
                    private string $subject = '';
                    private string $body = '';

                    public function to(string $address): static { $this->to = $address; return $this; }
                    public function cc(string $address): static { $this->cc[] = $address; return $this; }
                    public function subject(string $subject): static { $this->subject = $subject; return $this; }
                    public function body(string $body): static { $this->body = $body; return $this; }

                    public function build(): string
                    {
                        $lines = ["To: {$this->to}"];
                        if ($this->cc) {
                            $lines[] = 'Cc: ' . implode(', ', $this->cc);
                        }
                        $lines[] = "Subject: {$this->subject}";
                        $lines[] = '';
                        $lines[] = $this->body;

                        return implode("\n", $lines);
                    }
                }
                PHP,
            'hint' => 'Collect the lines in an array in `build()`, adding the `Cc:` line only when the list isn\'t empty, then `implode("\n", …)`.',
            'checks' => [
                ['label' => 'With copies', 'code' => <<<'PHP'
                    $email = (new EmailBuilder())->to('amara@example.com')->cc('ben@example.com')->cc('shop@example.com')->subject('Shipped')->body('On its way.')->build();
                    return expect_same($email, "To: amara@example.com\nCc: ben@example.com, shop@example.com\nSubject: Shipped\n\nOn its way.");
                    PHP],
                ['label' => 'Without copies there is no Cc line', 'code' => <<<'PHP'
                    $email = (new EmailBuilder())->to('a@example.com')->subject('Hi')->body('Hello')->build();
                    return expect_same($email, "To: a@example.com\nSubject: Hi\n\nHello");
                    PHP],
            ],
        ],
        [
            'title' => 'One logger, by hand',
            'prompt' => 'Make `Logger` a singleton: a private constructor, and a static `instance(): Logger` that always returns the same object. `log(string)` adds a line and `lines()` returns them.',
            'starter_code' => <<<'PHP'
                <?php

                class Logger
                {
                    private array $lines = [];

                    public function log(string $line): void
                    {
                        $this->lines[] = $line;
                    }

                    public function lines(): array
                    {
                        return $this->lines;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Logger
                {
                    private static ?Logger $instance = null;
                    private array $lines = [];

                    private function __construct() {}

                    public static function instance(): self
                    {
                        return self::$instance ??= new self();
                    }

                    public function log(string $line): void
                    {
                        $this->lines[] = $line;
                    }

                    public function lines(): array
                    {
                        return $this->lines;
                    }
                }
                PHP,
            'hint' => 'A `private static ?Logger $instance = null;`, a `private function __construct() {}`, and `return self::$instance ??= new self();`.',
            'checks' => [
                ['label' => 'instance() always gives the same logger', 'code' => <<<'PHP'
                    Logger::instance()->log('one');
                    Logger::instance()->log('two');
                    return (Logger::instance() === Logger::instance() && Logger::instance()->lines() === ['one', 'two']) ?: 'instance() should return one shared Logger.';
                    PHP],
                ['label' => 'The constructor is private', 'code' => "return (new ReflectionMethod('Logger', '__construct'))->isPrivate() ?: 'Make the constructor private.';"],
            ],
        ],
        [
            'title' => 'From singleton to injection',
            'prompt' => "`Checkout` reaches for `Clock::instance()` inside `receipt()`, so tests can't control the time. Remove the singleton: make `Clock`'s constructor public, taking the date as a string, and have `Checkout` receive a `Clock` in its constructor and use that.",
            'starter_code' => <<<'PHP'
                <?php

                class Clock
                {
                    private static ?Clock $instance = null;

                    private function __construct(private string $today) {}

                    public static function instance(): self
                    {
                        return self::$instance ??= new self('2026-04-08');
                    }

                    public function today(): string
                    {
                        return $this->today;
                    }
                }

                class Checkout
                {
                    public function receipt(float $total): string
                    {
                        return 'Paid £' . number_format($total, 2) . ' on ' . Clock::instance()->today();
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Clock
                {
                    public function __construct(private string $today) {}

                    public function today(): string
                    {
                        return $this->today;
                    }
                }

                class Checkout
                {
                    public function __construct(private Clock $clock) {}

                    public function receipt(float $total): string
                    {
                        return 'Paid £' . number_format($total, 2) . ' on ' . $this->clock->today();
                    }
                }
                PHP,
            'hint' => 'Delete the static parts of `Clock` and make its constructor public. Give `Checkout` a constructor `public function __construct(private Clock $clock) {}` and use `$this->clock`.',
            'checks' => [
                ['label' => 'A Checkout uses the Clock it\'s given', 'code' => <<<'PHP'
                    $checkout = new Checkout(new Clock('2026-12-24'));
                    return expect_same($checkout->receipt(19.5), 'Paid £19.50 on 2026-12-24');
                    PHP],
                ['label' => 'There is no singleton left', 'code' => "return (! method_exists('Clock', 'instance') && (new ReflectionMethod('Clock', '__construct'))->isPublic()) ?: 'Remove instance() and make the constructor public.';"],
            ],
        ],
        [
            'title' => 'A URL builder',
            'prompt' => "Write `UrlBuilder` taking a base like `'https://shop.example'` in its constructor, with chainable `path(string \$segment)` (each adds `/segment`, with spaces encoded by `rawurlencode()`) and `query(string \$key, string \$value)`, and `build(): string`. Query parameters are added after `?` with `http_build_query()`, only when there are any.",
            'starter_code' => <<<'PHP'
                <?php

                class UrlBuilder
                {
                    public function __construct(private string $base) {}

                    public function build(): string
                    {
                        return $this->base;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class UrlBuilder
                {
                    private array $segments = [];
                    private array $query = [];

                    public function __construct(private string $base) {}

                    public function path(string $segment): static
                    {
                        $this->segments[] = rawurlencode($segment);
                        return $this;
                    }

                    public function query(string $key, string $value): static
                    {
                        $this->query[$key] = $value;
                        return $this;
                    }

                    public function build(): string
                    {
                        $url = rtrim($this->base, '/');
                        foreach ($this->segments as $segment) {
                            $url .= '/' . $segment;
                        }

                        return $this->query ? $url . '?' . http_build_query($this->query) : $url;
                    }
                }
                PHP,
            'hint' => 'Collect encoded segments and query pairs in arrays; `build()` joins the segments with `/` and adds `\'?\' . http_build_query($this->query)` when there are parameters.',
            'checks' => [
                ['label' => 'Paths and a query', 'code' => "return expect_same((new UrlBuilder('https://shop.example'))->path('books')->path('the hobbit')->query('format', 'paperback')->query('page', '2')->build(), 'https://shop.example/books/the%20hobbit?format=paperback&page=2');"],
                ['label' => 'No query, no question mark', 'code' => "return expect_same((new UrlBuilder('https://shop.example'))->path('about')->build(), 'https://shop.example/about');"],
                ['label' => 'Just the base', 'code' => "return expect_same((new UrlBuilder('https://shop.example'))->build(), 'https://shop.example');"],
            ],
        ],
    ],
];
