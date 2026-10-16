<?php

// Lesson 4 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'decorator-and-proxy',
    'title' => 'Decorator and Proxy',
    'summary' => 'Wrap objects in others with the same interface: decorators that add behaviour in any combination, and proxies that cache, delay or guard access to the real object.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try swapping the decorators round.
        interface Pricing { public function price(float $base): float; }

        class BasePricing implements Pricing
        {
            public function price(float $base): float { return $base; }
        }

        class WithTax implements Pricing
        {
            public function __construct(private Pricing $inner) {}
            public function price(float $base): float { return $this->inner->price($base) * 1.2; }
        }

        class MinusFive implements Pricing
        {
            public function __construct(private Pricing $inner) {}
            public function price(float $base): float { return $this->inner->price($base) - 5; }
        }

        echo (new WithTax(new MinusFive(new BasePricing())))->price(20.0);
        PHP,

    'quiz' => [
        [
            'question' => 'What do a decorator and a proxy have in common?',
            'options' => [
                'Both create objects',
                'Both wrap an object and offer the same interface as it',
                'Both are singletons',
                'Both change the interface of the object they wrap',
            ],
            'correct_option' => 1,
            'explanation' => 'Callers can\'t tell the wrapper from the original. A decorator adds behaviour; a proxy controls access.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Number { public function value(): int; }

                class Five implements Number { public function value(): int { return 5; } }

                class Doubled implements Number
                {
                    public function __construct(private Number $inner) {}
                    public function value(): int { return $this->inner->value() * 2; }
                }

                class PlusOne implements Number
                {
                    public function __construct(private Number $inner) {}
                    public function value(): int { return $this->inner->value() + 1; }
                }

                echo (new PlusOne(new Doubled(new Five())))->value() . ' ' . (new Doubled(new PlusOne(new Five())))->value();
                PHP,
            'options' => ['`11 12`', '`12 11`', '`11 11`', '`12 12`'],
            'correct_option' => 0,
            'explanation' => 'The innermost wrapper acts first: (5 × 2) + 1 = 11, but (5 + 1) × 2 = 12. Order matters.',
        ],
        [
            'question' => 'Why prefer decorators to inheritance for combinations of features?',
            'options' => [
                'Inheritance is slower',
                'With inheritance, every combination needs its own class; decorators combine at run time',
                'Decorators don\'t need interfaces',
                'PHP limits how many child classes you can write',
            ],
            'correct_option' => 1,
            'explanation' => 'Three decorators give every combination of three features; with inheritance you\'d need a class for each.',
        ],
        [
            'question' => 'What does a caching proxy do?',
            'options' => [
                'Creates the real object only when needed',
                'Remembers results, so the real object is asked only once per question',
                'Checks permissions',
                'Converts one interface into another',
            ],
            'correct_option' => 1,
            'explanation' => 'Repeated questions are answered from the cache, which helps when the real object is slow.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Heavy
                {
                    public function __construct() { echo 'built '; }
                    public function hello(): string { return 'hello '; }
                }

                class LazyHeavy
                {
                    private ?Heavy $real = null;
                    public function hello(): string { return ($this->real ??= new Heavy())->hello(); }
                }

                $lazy = new LazyHeavy();
                echo 'start ';
                echo $lazy->hello();
                echo $lazy->hello();
                PHP,
            'options' => ['`built start hello hello `', '`start built hello hello `', '`start built hello built hello `', '`start hello hello `'],
            'correct_option' => 1,
            'explanation' => 'Nothing is built until the first `hello()`, and `??=` keeps the same object for the second call.',
        ],
        [
            'question' => 'Which kind of proxy checks whether the caller is allowed to do something?',
            'options' => ['A caching proxy', 'A lazy proxy', 'A protection proxy', 'A remote proxy'],
            'correct_option' => 2,
            'explanation' => 'A protection proxy checks permission first, so the real object stays free of access rules.',
        ],
        [
            'question' => 'A logging decorator records every call and passes it on unchanged. What does the decorated class need to know about it?',
            'options' => [
                'It must call the logger itself',
                'Nothing at all',
                'It must extend the logging class',
                'It must be marked with an attribute',
            ],
            'correct_option' => 1,
            'explanation' => 'The decorator wraps it from outside. The original class is unchanged.',
        ],
        [
            'question' => 'What makes something a decorator rather than a facade?',
            'options' => [
                'A decorator has the same interface as the one object it wraps; a facade offers a new, simpler interface over several',
                'A decorator wraps several objects',
                'A facade must be static',
                'There is no difference',
            ],
            'correct_option' => 0,
            'explanation' => 'Decorators can be stacked precisely because they keep the interface.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Add-ons for a coffee',
            'prompt' => "`Coffee` has `cost(): float` and `description(): string`. Write two decorators implementing `Coffee`: `WithMilk` (adds 0.30 and `', milk'`) and `WithSyrup` (adds 0.50 and `', syrup'`). They can be stacked in any order and repeated.",
            'starter_code' => <<<'PHP'
                <?php

                interface Coffee
                {
                    public function cost(): float;
                    public function description(): string;
                }

                class Espresso implements Coffee
                {
                    public function cost(): float { return 2.00; }
                    public function description(): string { return 'Espresso'; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Coffee
                {
                    public function cost(): float;
                    public function description(): string;
                }

                class Espresso implements Coffee
                {
                    public function cost(): float { return 2.00; }
                    public function description(): string { return 'Espresso'; }
                }

                class WithMilk implements Coffee
                {
                    public function __construct(private Coffee $inner) {}
                    public function cost(): float { return round($this->inner->cost() + 0.30, 2); }
                    public function description(): string { return $this->inner->description() . ', milk'; }
                }

                class WithSyrup implements Coffee
                {
                    public function __construct(private Coffee $inner) {}
                    public function cost(): float { return round($this->inner->cost() + 0.50, 2); }
                    public function description(): string { return $this->inner->description() . ', syrup'; }
                }
                PHP,
            'hint' => 'Each decorator takes a `Coffee` in its constructor, and adds to the wrapped coffee\'s cost and description. Rounding to 2 places keeps the cost tidy.',
            'checks' => [
                ['label' => 'Milk on an espresso', 'code' => "\$coffee = new WithMilk(new Espresso());\nreturn expect_same([\$coffee->cost(), \$coffee->description()], [2.3, 'Espresso, milk']);"],
                ['label' => 'Stacked and repeated', 'code' => "\$coffee = new WithSyrup(new WithMilk(new WithMilk(new Espresso())));\nreturn expect_same([\$coffee->cost(), \$coffee->description()], [3.1, 'Espresso, milk, milk, syrup']);"],
                ['label' => 'Both are Coffee', 'code' => "return (new WithMilk(new Espresso()) instanceof Coffee && new WithSyrup(new Espresso()) instanceof Coffee) ?: 'Both decorators should implement Coffee.';"],
            ],
        ],
        [
            'title' => 'Count the calls',
            'prompt' => 'Write a decorator `CountingMailer` implementing `Mailer` that passes every `send()` on to the mailer it wraps, returning its result, and counts the calls in a public `$count` property.',
            'starter_code' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $text): bool;
                }

                class FakeMailer implements Mailer
                {
                    public array $sent = [];

                    public function send(string $to, string $text): bool
                    {
                        $this->sent[] = $to;
                        return true;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $text): bool;
                }

                class FakeMailer implements Mailer
                {
                    public array $sent = [];

                    public function send(string $to, string $text): bool
                    {
                        $this->sent[] = $to;
                        return true;
                    }
                }

                class CountingMailer implements Mailer
                {
                    public int $count = 0;

                    public function __construct(private Mailer $inner) {}

                    public function send(string $to, string $text): bool
                    {
                        $this->count++;

                        return $this->inner->send($to, $text);
                    }
                }
                PHP,
            'hint' => 'Increase `$this->count`, then `return $this->inner->send($to, $text);`.',
            'checks' => [
                ['label' => 'Calls are passed on', 'code' => "\$inner = new FakeMailer();\n\$mailer = new CountingMailer(\$inner);\n\$result = \$mailer->send('a@example.com', 'hi');\nreturn expect_same([\$result, \$inner->sent], [true, ['a@example.com']]);"],
                ['label' => 'Calls are counted', 'code' => "\$mailer = new CountingMailer(new FakeMailer());\n\$mailer->send('a@example.com', 'x');\n\$mailer->send('b@example.com', 'y');\nreturn expect_same(\$mailer->count, 2);"],
            ],
        ],
        [
            'title' => 'Cache the slow lookups',
            'prompt' => '`SlowStock::copies()` is slow. Write a caching proxy `CachedStock` implementing `Stock`, so each title is looked up in the real stock at most once. Different titles are cached separately, and a cached `0` counts as cached.',
            'starter_code' => <<<'PHP'
                <?php

                interface Stock
                {
                    public function copies(string $title): int;
                }

                class SlowStock implements Stock
                {
                    public array $lookups = [];

                    public function copies(string $title): int
                    {
                        $this->lookups[] = $title;
                        return ['Dune' => 3, 'Emma' => 0][$title] ?? 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Stock
                {
                    public function copies(string $title): int;
                }

                class SlowStock implements Stock
                {
                    public array $lookups = [];

                    public function copies(string $title): int
                    {
                        $this->lookups[] = $title;
                        return ['Dune' => 3, 'Emma' => 0][$title] ?? 0;
                    }
                }

                class CachedStock implements Stock
                {
                    private array $cache = [];

                    public function __construct(private Stock $real) {}

                    public function copies(string $title): int
                    {
                        if (! array_key_exists($title, $this->cache)) {
                            $this->cache[$title] = $this->real->copies($title);
                        }

                        return $this->cache[$title];
                    }
                }
                PHP,
            'hint' => 'Keep a cache array. Use `array_key_exists()` (or `??=`, which works here because the values are ints, never `null`).',
            'checks' => [
                ['label' => 'Answers are the same as the real stock', 'code' => "\$stock = new CachedStock(new SlowStock());\nreturn expect_same([\$stock->copies('Dune'), \$stock->copies('Emma')], [3, 0]);"],
                ['label' => 'Each title is looked up once', 'code' => <<<'PHP'
                    $real = new SlowStock();
                    $stock = new CachedStock($real);
                    foreach (['Dune', 'Emma', 'Dune', 'Emma', 'Dune'] as $title) {
                        $stock->copies($title);
                    }
                    return expect_same($real->lookups, ['Dune', 'Emma']);
                    PHP],
            ],
        ],
        [
            'title' => 'Only when needed',
            'prompt' => 'Write a lazy proxy `LazyCatalogue` implementing `Catalogue`. Its constructor takes a `Closure` that creates the real catalogue. The real one must be created on the first call to `titles()`, not before, and only once.',
            'starter_code' => <<<'PHP'
                <?php

                interface Catalogue
                {
                    public function titles(): array;
                }

                class FullCatalogue implements Catalogue
                {
                    public static int $built = 0;

                    public function __construct()
                    {
                        self::$built++;
                    }

                    public function titles(): array
                    {
                        return ['Dune', 'Emma', 'Circe'];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Catalogue
                {
                    public function titles(): array;
                }

                class FullCatalogue implements Catalogue
                {
                    public static int $built = 0;

                    public function __construct()
                    {
                        self::$built++;
                    }

                    public function titles(): array
                    {
                        return ['Dune', 'Emma', 'Circe'];
                    }
                }

                class LazyCatalogue implements Catalogue
                {
                    private ?Catalogue $real = null;

                    public function __construct(private Closure $factory) {}

                    public function titles(): array
                    {
                        $this->real ??= ($this->factory)();

                        return $this->real->titles();
                    }
                }
                PHP,
            'hint' => 'Store the closure, and a `?Catalogue $real = null`. In `titles()`, `$this->real ??= ($this->factory)();`.',
            'checks' => [
                ['label' => 'Nothing is built until titles() is called', 'code' => "FullCatalogue::\$built = 0;\nnew LazyCatalogue(fn () => new FullCatalogue());\nreturn expect_same(FullCatalogue::\$built, 0);"],
                ['label' => 'It\'s built once, and works', 'code' => "FullCatalogue::\$built = 0;\n\$catalogue = new LazyCatalogue(fn () => new FullCatalogue());\n\$a = \$catalogue->titles();\n\$catalogue->titles();\nreturn expect_same([\$a, FullCatalogue::\$built], [['Dune', 'Emma', 'Circe'], 1]);"],
            ],
        ],
        [
            'title' => 'Managers only',
            'prompt' => "Write a protection proxy `ProtectedPrices` implementing `PriceEditor`. Its constructor takes the real editor and the user's role. `setPrice()` is passed on only for the role `'manager'`; for anyone else it throws a `RuntimeException` and the real editor isn't called.",
            'starter_code' => <<<'PHP'
                <?php

                interface PriceEditor
                {
                    public function setPrice(string $title, float $price): void;
                }

                class RealPriceEditor implements PriceEditor
                {
                    public array $prices = [];

                    public function setPrice(string $title, float $price): void
                    {
                        $this->prices[$title] = $price;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface PriceEditor
                {
                    public function setPrice(string $title, float $price): void;
                }

                class RealPriceEditor implements PriceEditor
                {
                    public array $prices = [];

                    public function setPrice(string $title, float $price): void
                    {
                        $this->prices[$title] = $price;
                    }
                }

                class ProtectedPrices implements PriceEditor
                {
                    public function __construct(private PriceEditor $real, private string $role) {}

                    public function setPrice(string $title, float $price): void
                    {
                        if ($this->role !== 'manager') {
                            throw new RuntimeException('Only managers can change prices.');
                        }

                        $this->real->setPrice($title, $price);
                    }
                }
                PHP,
            'hint' => 'Check the role first, and throw before calling the real editor.',
            'checks' => [
                ['label' => 'Managers can change prices', 'code' => "\$real = new RealPriceEditor();\n(new ProtectedPrices(\$real, 'manager'))->setPrice('Dune', 9.99);\nreturn expect_same(\$real->prices, ['Dune' => 9.99]);"],
                ['label' => 'Others are refused and nothing changes', 'code' => <<<'PHP'
                    $real = new RealPriceEditor();
                    $result = expect_throws(RuntimeException::class, fn () => (new ProtectedPrices($real, 'bookseller'))->setPrice('Dune', 0.01));
                    if ($result !== true) return $result;
                    return expect_same($real->prices, []);
                    PHP],
            ],
        ],
        [
            'title' => 'Shout and trim',
            'prompt' => "Write two decorators for `Formatter`: `UpperCase`, which upper-cases the text **after** the inner formatter has done its work, and `Trimmed`, which trims the text **before** passing it in. Then set `\$formatter` to a formatter that, wrapping `Quoted`, turns `'  dune '` into `'\"DUNE\"'`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Formatter
                {
                    public function format(string $text): string;
                }

                class Quoted implements Formatter
                {
                    public function format(string $text): string
                    {
                        return '"' . $text . '"';
                    }
                }

                $formatter = new Quoted();
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Formatter
                {
                    public function format(string $text): string;
                }

                class Quoted implements Formatter
                {
                    public function format(string $text): string
                    {
                        return '"' . $text . '"';
                    }
                }

                class UpperCase implements Formatter
                {
                    public function __construct(private Formatter $inner) {}

                    public function format(string $text): string
                    {
                        return strtoupper($this->inner->format($text));
                    }
                }

                class Trimmed implements Formatter
                {
                    public function __construct(private Formatter $inner) {}

                    public function format(string $text): string
                    {
                        return $this->inner->format(trim($text));
                    }
                }

                $formatter = new UpperCase(new Trimmed(new Quoted()));
                PHP,
            'hint' => '`UpperCase` changes the inner result: `strtoupper($this->inner->format($text))`. `Trimmed` changes the input: `$this->inner->format(trim($text))`.',
            'checks' => [
                ['label' => 'UpperCase works after the inner formatter', 'code' => "return expect_same((new UpperCase(new Quoted()))->format('dune'), '\"DUNE\"');"],
                ['label' => 'Trimmed works before the inner formatter', 'code' => "return expect_same((new Trimmed(new Quoted()))->format('  dune '), '\"dune\"');"],
                ['label' => '$formatter turns \'  dune \' into \'"DUNE"\'', 'code' => "return expect_same(\$GLOBALS['formatter']->format('  dune '), '\"DUNE\"');"],
            ],
        ],
    ],
];
