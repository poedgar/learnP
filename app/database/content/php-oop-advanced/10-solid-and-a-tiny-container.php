<?php

// Lesson 10 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'solid-and-a-tiny-container',
    'title' => 'SOLID and a tiny container',
    'summary' => 'The five SOLID design principles, composition over inheritance with decorators, and a small dependency injection container that wires objects together, even automatically.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try wrapping the notifier twice.
        interface Notifier
        {
            public function send(string $message): string;
        }

        class EmailNotifier implements Notifier
        {
            public function send(string $message): string
            {
                return "Email: $message";
            }
        }

        class UrgentNotifier implements Notifier
        {
            public function __construct(private Notifier $inner) {}

            public function send(string $message): string
            {
                return $this->inner->send("URGENT! $message");
            }
        }

        echo (new UrgentNotifier(new EmailNotifier()))->send('Dune is back in stock');
        PHP,

    'quiz' => [
        [
            'question' => 'What does the single responsibility principle ask of a class?',
            'options' => [
                'That it has only one method',
                'That it has one job, and so only one reason to change',
                'That only one object of it exists',
                'That it\'s used in only one place',
            ],
            'correct_option' => 1,
            'explanation' => 'A class that both calculates and formats changes whenever either the rules or the layout change. Splitting it separates those reasons.',
        ],
        [
            'question' => 'Discounts are chosen with a `match` on a type string. Which change follows the open/closed principle?',
            'options' => [
                'Add another arm to the `match` for each new discount',
                'Make a `Discount` interface, with a class for each discount',
                'Copy the function for each discount',
                'Store discounts in a global variable',
            ],
            'correct_option' => 1,
            'explanation' => 'New discounts become new classes. The code that uses a `Discount` is never edited again.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Bird
                {
                    public function fly(): string
                    {
                        return 'flying';
                    }
                }

                class Penguin extends Bird
                {
                    public function fly(): string
                    {
                        throw new LogicException("Penguins can't fly.");
                    }
                }

                function launch(Bird $bird): string
                {
                    try {
                        return $bird->fly();
                    } catch (LogicException $e) {
                        return 'broken: ' . $e->getMessage();
                    }
                }

                echo launch(new Penguin());
                PHP,
            'options' => [
                '`flying`',
                '`broken: Penguins can\'t fly.`',
                'An error: `Penguin` can\'t override `fly()`',
                'Nothing',
            ],
            'correct_option' => 1,
            'explanation' => 'Code written for `Bird` expected `fly()` to work. A child that throws instead breaks the Liskov substitution principle.',
        ],
        [
            'question' => 'What does the interface segregation principle recommend?',
            'options' => [
                'One big interface for every class',
                'Small interfaces, so classes only promise the methods they really have',
                'No interfaces at all',
                'Putting every interface in its own namespace',
            ],
            'correct_option' => 1,
            'explanation' => 'A receipt printer shouldn\'t have to write an empty `fax()` method just to satisfy a huge interface.',
        ],
        [
            'question' => 'What does the dependency inversion principle say?',
            'options' => [
                'Low-level classes should create the high-level ones',
                'High-level code and low-level details should both depend on abstractions, such as interfaces',
                'Dependencies should be created inside the constructor with `new`',
                'Classes should never depend on each other',
            ],
            'correct_option' => 1,
            'explanation' => 'A `Checkout` that needs a `PaymentGateway` interface works with any provider, including a fake one in tests.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Formatter
                {
                    public function format(string $text): string;
                }

                class Plain implements Formatter
                {
                    public function format(string $text): string { return $text; }
                }

                class Bold implements Formatter
                {
                    public function __construct(private Formatter $inner) {}

                    public function format(string $text): string { return '**' . $this->inner->format($text) . '**'; }
                }

                class Shout implements Formatter
                {
                    public function __construct(private Formatter $inner) {}

                    public function format(string $text): string { return $this->inner->format(strtoupper($text)); }
                }

                echo (new Bold(new Shout(new Plain())))->format('dune');
                PHP,
            'options' => ['`**dune**`', '`**DUNE**`', '`DUNE`', '`**Dune**`'],
            'correct_option' => 1,
            'explanation' => '`Bold` asks `Shout` to format, which upper-cases the text before passing it to `Plain`; then `Bold` adds the stars around the result.',
        ],
        [
            'question' => 'What is a dependency injection container for?',
            'options' => [
                'Storing objects in a database',
                'Knowing how to create objects, and the objects they depend on, so you can ask for them by name',
                'Preventing objects from depending on each other',
                'Running code in a sandbox',
            ],
            'correct_option' => 1,
            'explanation' => 'You register a factory for each name once; the container then builds whole chains of objects on request.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $created = 0;
                $factory = function () use (&$created) {
                    $created++;
                    return new stdClass();
                };

                $shared = null;
                $singleton = function () use (&$shared, $factory) {
                    return $shared ??= $factory();
                };

                $a = $singleton();
                $b = $singleton();
                $c = $factory();

                var_dump($a === $b, $created);
                PHP,
            'options' => [
                '`bool(true)` then `int(2)`',
                '`bool(false)` then `int(3)`',
                '`bool(true)` then `int(3)`',
                '`bool(false)` then `int(2)`',
            ],
            'correct_option' => 0,
            'explanation' => 'The singleton wrapper calls the factory once and then keeps returning that object. The direct `$factory()` call makes a second object.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'One job each',
            'prompt' => "`Report` adds up order totals **and** formats the result. Split it into two classes: `ReportCalculator` with `total(array \$orders): float` (the sum of each order's `amount`), and `ReportFormatter` with `format(float \$total): string`, giving `Total: £12.50`. Remove `Report`.",
            'starter_code' => <<<'PHP'
                <?php

                class Report
                {
                    public function render(array $orders): string
                    {
                        $total = 0.0;
                        foreach ($orders as $order) {
                            $total += $order['amount'];
                        }

                        return 'Total: £' . number_format($total, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class ReportCalculator
                {
                    public function total(array $orders): float
                    {
                        return (float) array_sum(array_column($orders, 'amount'));
                    }
                }

                class ReportFormatter
                {
                    public function format(float $total): string
                    {
                        return 'Total: £' . number_format($total, 2);
                    }
                }
                PHP,
            'hint' => 'Move the adding-up into `ReportCalculator::total()` and the text into `ReportFormatter::format()`. `array_sum(array_column($orders, \'amount\'))` adds up the amounts.',
            'checks' => [
                ['label' => 'ReportCalculator adds up the amounts', 'code' => "if (! class_exists('ReportCalculator', false)) return 'There is no class ReportCalculator.';\nreturn expect_same((new ReportCalculator())->total([['amount' => 10.0], ['amount' => 2.5]]), 12.5);"],
                ['label' => "ReportFormatter gives 'Total: £12.50'", 'code' => "if (! class_exists('ReportFormatter', false)) return 'There is no class ReportFormatter.';\nreturn expect_same((new ReportFormatter())->format(12.5), 'Total: £12.50');"],
                ['label' => 'Each class does only its own job', 'code' => <<<'PHP'
                    if (class_exists('Report', false)) return 'Remove the old Report class.';
                    if (method_exists('ReportCalculator', 'format')) return 'ReportCalculator should only calculate.';
                    return ! method_exists('ReportFormatter', 'total') ?: 'ReportFormatter should only format.';
                    PHP],
            ],
        ],
        [
            'title' => 'Room for new discounts',
            'prompt' => 'Replace the `match` in `discounted()` with an interface `Discount` (`apply(float $price): float`) and two classes: `PercentOff`, constructed with a whole percentage, and `AmountOff`, constructed with an amount, which never takes a price below 0. Then write `Checkout::total(array $prices, Discount ...$discounts): float`, applying each discount in turn to the sum of the prices, rounded to 2 places.',
            'starter_code' => <<<'PHP'
                <?php

                function discounted(float $price, string $type, float $value): float
                {
                    return match ($type) {
                        'percent' => $price * (100 - $value) / 100,
                        'amount' => max(0, $price - $value),
                    };
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Discount
                {
                    public function apply(float $price): float;
                }

                class PercentOff implements Discount
                {
                    public function __construct(private int $percent) {}

                    public function apply(float $price): float
                    {
                        return $price * (100 - $this->percent) / 100;
                    }
                }

                class AmountOff implements Discount
                {
                    public function __construct(private float $amount) {}

                    public function apply(float $price): float
                    {
                        return max(0.0, $price - $this->amount);
                    }
                }

                class Checkout
                {
                    public function total(array $prices, Discount ...$discounts): float
                    {
                        $total = array_sum($prices);
                        foreach ($discounts as $discount) {
                            $total = $discount->apply($total);
                        }

                        return round($total, 2);
                    }
                }
                PHP,
            'hint' => 'Each discount class keeps its value from the constructor and implements `apply()`. In `total()`, start from `array_sum($prices)` and pass it through each discount.',
            'checks' => [
                ['label' => 'PercentOff and AmountOff implement Discount', 'code' => <<<'PHP'
                    if (! interface_exists('Discount', false)) return 'There is no interface named Discount.';
                    return (new PercentOff(10) instanceof Discount && new AmountOff(5.0) instanceof Discount) ?: 'Both discount classes should implement Discount.';
                    PHP],
                ['label' => 'Each discount works on its own', 'code' => 'return expect_same([(new PercentOff(25))->apply(20.0), (new AmountOff(5.0))->apply(20.0), (new AmountOff(50.0))->apply(20.0)], [15.0, 15.0, 0.0]);'],
                ['label' => 'Checkout applies discounts in turn, rounded', 'code' => 'return expect_same((new Checkout())->total([10.0, 9.99], new PercentOff(10), new AmountOff(2.0)), 15.99);'],
                ['label' => 'Without discounts, the total is the sum', 'code' => 'return expect_same((new Checkout())->total([1.25, 2.5]), 3.75);'],
            ],
        ],
        [
            'title' => 'Only what you can do',
            'prompt' => 'Split the big `Machine` interface into two small ones: `CanPrint` (`print(string $document): string`) and `CanScan` (`scan(): string`). `ReceiptPrinter` only prints, so it implements only `CanPrint` and has no `scan()` method at all. `OfficeMachine` implements both. Remove `Machine`.',
            'starter_code' => <<<'PHP'
                <?php

                interface Machine
                {
                    public function print(string $document): string;
                    public function scan(): string;
                }

                class ReceiptPrinter implements Machine
                {
                    public function print(string $document): string { return "Printing: $document"; }
                    public function scan(): string { throw new LogicException("Can't scan."); }
                }

                class OfficeMachine implements Machine
                {
                    public function print(string $document): string { return "Printing: $document"; }
                    public function scan(): string { return 'Scanned page'; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface CanPrint
                {
                    public function print(string $document): string;
                }

                interface CanScan
                {
                    public function scan(): string;
                }

                class ReceiptPrinter implements CanPrint
                {
                    public function print(string $document): string { return "Printing: $document"; }
                }

                class OfficeMachine implements CanPrint, CanScan
                {
                    public function print(string $document): string { return "Printing: $document"; }
                    public function scan(): string { return 'Scanned page'; }
                }
                PHP,
            'hint' => 'Two interfaces with one method each. `ReceiptPrinter implements CanPrint` loses its `scan()`; `OfficeMachine implements CanPrint, CanScan`.',
            'checks' => [
                ['label' => 'CanPrint and CanScan exist, and Machine is gone', 'code' => <<<'PHP'
                    if (! interface_exists('CanPrint', false) || ! interface_exists('CanScan', false)) return 'Write the interfaces CanPrint and CanScan.';
                    return ! interface_exists('Machine', false) ?: 'Remove the Machine interface.';
                    PHP],
                ['label' => 'ReceiptPrinter only prints', 'code' => <<<'PHP'
                    $printer = new ReceiptPrinter();
                    if (! $printer instanceof CanPrint || $printer instanceof CanScan) return 'ReceiptPrinter should implement CanPrint only.';
                    if (method_exists($printer, 'scan')) return 'ReceiptPrinter should have no scan() method.';
                    return expect_same($printer->print('Receipt #1'), 'Printing: Receipt #1');
                    PHP],
                ['label' => 'OfficeMachine does both', 'code' => <<<'PHP'
                    $machine = new OfficeMachine();
                    return ($machine instanceof CanPrint && $machine instanceof CanScan && $machine->scan() === 'Scanned page') ?: 'OfficeMachine should implement both and scan.';
                    PHP],
            ],
        ],
        [
            'title' => 'Wrap, don\'t inherit',
            'prompt' => "`Notifier` has one implementation, `EmailNotifier`. Write two decorators that implement `Notifier` by wrapping **any** other notifier:\n\n- `LoudNotifier` upper-cases the message before passing it on;\n- `SignedNotifier` adds ` — The Bookshop` to the end of the message before passing it on.\n\nThey combine: `new LoudNotifier(new SignedNotifier(new EmailNotifier()))` sends `Email: DUNE IS BACK — The Bookshop`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function send(string $message): string;
                }

                class EmailNotifier implements Notifier
                {
                    public function send(string $message): string
                    {
                        return "Email: $message";
                    }
                }

                class LoudEmailNotifier extends EmailNotifier
                {
                    public function send(string $message): string
                    {
                        return parent::send(strtoupper($message));
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function send(string $message): string;
                }

                class EmailNotifier implements Notifier
                {
                    public function send(string $message): string
                    {
                        return "Email: $message";
                    }
                }

                class LoudNotifier implements Notifier
                {
                    public function __construct(private Notifier $inner) {}

                    public function send(string $message): string
                    {
                        return $this->inner->send(strtoupper($message));
                    }
                }

                class SignedNotifier implements Notifier
                {
                    public function __construct(private Notifier $inner) {}

                    public function send(string $message): string
                    {
                        return $this->inner->send($message . ' — The Bookshop');
                    }
                }
                PHP,
            'hint' => 'Each decorator takes a `Notifier` in its constructor and, in `send()`, calls `$this->inner->send(…)` with the changed message.',
            'checks' => [
                ['label' => 'LoudNotifier upper-cases the message', 'code' => "return expect_same((new LoudNotifier(new EmailNotifier()))->send('dune is back'), 'Email: DUNE IS BACK');"],
                ['label' => 'SignedNotifier signs the message', 'code' => "return expect_same((new SignedNotifier(new EmailNotifier()))->send('Dune is back'), 'Email: Dune is back — The Bookshop');"],
                ['label' => 'They combine, in either order', 'code' => <<<'PHP'
                    $a = (new LoudNotifier(new SignedNotifier(new EmailNotifier())))->send('dune is back');
                    $b = (new SignedNotifier(new LoudNotifier(new EmailNotifier())))->send('dune is back');
                    return expect_same([$a, $b], ['Email: DUNE IS BACK — The Bookshop', 'Email: DUNE IS BACK — THE BOOKSHOP']);
                    PHP],
                ['label' => 'They wrap any notifier', 'code' => <<<'PHP'
                    $sms = new class implements Notifier {
                        public function send(string $message): string { return "SMS: $message"; }
                    };
                    return expect_same((new LoudNotifier($sms))->send('hi'), 'SMS: HI');
                    PHP],
            ],
        ],
        [
            'title' => 'A container of your own',
            'prompt' => "Write a class `Container` with:\n\n- `set(string \$id, Closure \$factory): void`, registering a factory; `get()` calls it with the container and returns a **new** object every time;\n- `singleton(string \$id, Closure \$factory): void`, registering a factory whose object is created on the first `get()` and then **reused**;\n- `has(string \$id): bool`;\n- `get(string \$id): mixed`, which throws an `OutOfBoundsException` for an id that was never registered.",
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $factories = [];

                    public function set(string $id, Closure $factory): void
                    {
                        $this->factories[$id] = $factory;
                    }

                    public function get(string $id): mixed
                    {
                        return ($this->factories[$id])($this);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $factories = [];
                    private array $shared = [];
                    private array $instances = [];

                    public function set(string $id, Closure $factory): void
                    {
                        $this->factories[$id] = $factory;
                    }

                    public function singleton(string $id, Closure $factory): void
                    {
                        $this->factories[$id] = $factory;
                        $this->shared[$id] = true;
                    }

                    public function has(string $id): bool
                    {
                        return isset($this->factories[$id]);
                    }

                    public function get(string $id): mixed
                    {
                        if (! $this->has($id)) {
                            throw new OutOfBoundsException("Nothing is registered as $id.");
                        }

                        if (isset($this->shared[$id])) {
                            return $this->instances[$id] ??= ($this->factories[$id])($this);
                        }

                        return ($this->factories[$id])($this);
                    }
                }
                PHP,
            'hint' => 'Remember which ids are singletons, and keep their objects in an array once created: `$this->instances[$id] ??= …`. Check `has()` at the start of `get()`.',
            'checks' => [
                ['label' => 'set() gives a new object on every get()', 'code' => <<<'PHP'
                    $container = new Container();
                    $container->set('cart', fn () => new ArrayObject());
                    return ($container->get('cart') !== $container->get('cart')) ?: 'Each get() of a set() factory should create a new object.';
                    PHP],
                ['label' => 'singleton() reuses one object, created only when first asked for', 'code' => <<<'PHP'
                    $container = new Container();
                    $made = 0;
                    $container->singleton('clock', function () use (&$made) {
                        $made++;
                        return new ArrayObject();
                    });
                    if ($made !== 0) return 'The singleton was created before anyone asked for it.';
                    $same = $container->get('clock') === $container->get('clock');
                    return expect_same([$same, $made], [true, 1]);
                    PHP],
                ['label' => 'Factories receive the container', 'code' => <<<'PHP'
                    $container = new Container();
                    $container->set('name', fn () => 'Dune');
                    $container->set('shout', fn (Container $c) => strtoupper($c->get('name')));
                    return expect_same($container->get('shout'), 'DUNE');
                    PHP],
                ['label' => 'has() and unknown ids', 'code' => <<<'PHP'
                    $container = new Container();
                    $container->set('a', fn () => 1);
                    if (! method_exists($container, 'has') || $container->has('b') || ! $container->has('a')) return 'has() should say whether an id is registered.';
                    return expect_throws(OutOfBoundsException::class, fn () => $container->get('b'));
                    PHP],
            ],
        ],
        [
            'title' => 'Wire it up automatically',
            'prompt' => "Give the container a method `make(string \$class): object` that creates any class **without a registered factory**, by looking at its constructor with reflection: each parameter whose type is a class or interface is filled with `get()` if that type is registered, or `make()` otherwise; a class without a constructor is simply created. A parameter it can't fill (such as a `string`) throws a `LogicException`.\n\n`make(Checkout::class)` creates a `Checkout` with a `Clock` from the registered factory and a new `Logger`.",
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $factories = [];

                    public function set(string $id, Closure $factory): void
                    {
                        $this->factories[$id] = $factory;
                    }

                    public function has(string $id): bool
                    {
                        return isset($this->factories[$id]);
                    }

                    public function get(string $id): mixed
                    {
                        if (! $this->has($id)) {
                            throw new OutOfBoundsException("Nothing is registered as $id.");
                        }

                        return ($this->factories[$id])($this);
                    }

                    public function make(string $class): object
                    {
                        return new $class();
                    }
                }

                interface Clock { public function now(): string; }

                class FixedClock implements Clock
                {
                    public function now(): string { return '12:00'; }
                }

                class Logger
                {
                    public array $lines = [];
                }

                class Checkout
                {
                    public function __construct(public Clock $clock, public Logger $logger) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $factories = [];

                    public function set(string $id, Closure $factory): void
                    {
                        $this->factories[$id] = $factory;
                    }

                    public function has(string $id): bool
                    {
                        return isset($this->factories[$id]);
                    }

                    public function get(string $id): mixed
                    {
                        if (! $this->has($id)) {
                            throw new OutOfBoundsException("Nothing is registered as $id.");
                        }

                        return ($this->factories[$id])($this);
                    }

                    public function make(string $class): object
                    {
                        $constructor = (new ReflectionClass($class))->getConstructor();
                        if ($constructor === null) {
                            return new $class();
                        }

                        $arguments = [];
                        foreach ($constructor->getParameters() as $parameter) {
                            $type = $parameter->getType();
                            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                                throw new LogicException("Can't fill \${$parameter->getName()} of $class.");
                            }

                            $name = $type->getName();
                            $arguments[] = $this->has($name) ? $this->get($name) : $this->make($name);
                        }

                        return new $class(...$arguments);
                    }
                }

                interface Clock { public function now(): string; }

                class FixedClock implements Clock
                {
                    public function now(): string { return '12:00'; }
                }

                class Logger
                {
                    public array $lines = [];
                }

                class Checkout
                {
                    public function __construct(public Clock $clock, public Logger $logger) {}
                }
                PHP,
            'hint' => 'Get the constructor with reflection. For each parameter, `getType()` should be a `ReflectionNamedType` that isn\'t built in; use `get()` for registered names and `make()` for the rest, then `new $class(...$arguments)`.',
            'checks' => [
                ['label' => 'A class without a constructor is simply created', 'code' => "return ((new Container())->make('Logger') instanceof Logger) ?: 'make(Logger::class) should create a Logger.';"],
                ['label' => 'Registered types come from their factories', 'code' => <<<'PHP'
                    $container = new Container();
                    $container->set(Clock::class, fn () => new FixedClock());
                    $checkout = $container->make(Checkout::class);
                    return expect_same([$checkout->clock->now(), get_class($checkout->logger)], ['12:00', 'Logger']);
                    PHP],
                ['label' => 'Dependencies of dependencies are made too', 'code' => <<<'PHP'
                    $class = get_class(new class (new Checkout(new FixedClock(), new Logger())) {
                        public function __construct(public Checkout $checkout) {}
                    });
                    $container = new Container();
                    $container->set(Clock::class, fn () => new FixedClock());
                    return expect_same($container->make($class)->checkout->clock->now(), '12:00');
                    PHP],
                ['label' => 'A parameter it can\'t fill throws a LogicException', 'code' => <<<'PHP'
                    $class = get_class(new class ('x') {
                        public function __construct(public string $name) {}
                    });
                    return expect_throws(LogicException::class, fn () => (new Container())->make($class));
                    PHP],
            ],
        ],
    ],
];
