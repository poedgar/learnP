<?php

// Lesson 7 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'putting-objects-together',
    'title' => 'Putting objects together',
    'summary' => 'Build programs from small objects that hold and use each other: composition, dependency injection, depending on interfaces, and fakes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        interface Courier
        {
            public function priceFor(float $weightKg): float;
        }

        class RoyalPost implements Courier
        {
            public function priceFor(float $weightKg): float
            {
                return 3.0 + $weightKg * 1.5;
            }
        }

        class ShippingQuote
        {
            public function __construct(private Courier $courier) {}

            public function forParcel(float $weightKg): float
            {
                return $this->courier->priceFor($weightKg);
            }
        }

        echo (new ShippingQuote(new RoyalPost()))->forParcel(1.2);
        PHP,

    'quiz' => [
        [
            'question' => 'Which relationship should be modelled with composition rather than inheritance?',
            'options' => [
                'An e-book and a book',
                'An order and its order lines',
                'A gold membership and a membership',
                'An `InvalidArgumentException` and a `LogicException`',
            ],
            'correct_option' => 1,
            'explanation' => 'An order *has* lines; it isn\'t a kind of line. The other three are all "is a" relationships.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public int $pages) {}
                }

                class Shelf
                {
                    private array $books = [];

                    public function add(Book $book): void
                    {
                        $this->books[] = $book;
                    }

                    public function count(): int
                    {
                        return count($this->books);
                    }
                }

                $shelf = new Shelf();
                $shelf->add(new Book(612));
                $shelf->add(new Book(393));

                echo $shelf->count();
                PHP,
            'options' => ['`0`', '`1`', '`2`', '`1005`'],
            'correct_option' => 2,
            'explanation' => 'The shelf holds two `Book` objects in its array, so `count()` gives 2.',
        ],
        [
            'question' => 'What is dependency injection?',
            'options' => [
                'Creating every object an object needs with `new` inside its methods',
                'Passing an object the things it needs, usually through its constructor, instead of it creating them itself',
                'Inheriting from the classes you depend on',
                'Making dependencies static',
            ],
            'correct_option' => 1,
            'explanation' => 'The object declares what it needs; whoever creates it decides which implementation to supply.',
        ],
        [
            'question' => 'Why type a constructor parameter with an interface, like `__construct(private Courier $courier)`, rather than a class?',
            'options' => [
                'It makes the code run faster',
                'Interfaces can\'t be passed by mistake',
                'Any class that keeps the promise can be passed in, including fakes and ones written later',
                'PHP requires constructor parameters to be interfaces',
            ],
            'correct_option' => 2,
            'explanation' => 'Depending on the promise rather than one class keeps the code open to new implementations without changing it.',
        ],
        [
            'question' => 'What is a fake, in the sense used in this lesson?',
            'options' => [
                'A class that throws an exception for every method',
                'A simple implementation of an interface that records what it\'s asked to do, used instead of the real thing',
                'A copy of a class with a different name',
                'An abstract class',
            ],
            'correct_option' => 1,
            'explanation' => 'A fake keeps the same promise as the real dependency but does something harmless and observable, such as remembering messages instead of emailing them.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Greeter
                {
                    public function greet(string $name): string;
                }

                class Friendly implements Greeter
                {
                    public function greet(string $name): string
                    {
                        return "Hi, $name!";
                    }
                }

                class Formal implements Greeter
                {
                    public function greet(string $name): string
                    {
                        return "Good morning, $name.";
                    }
                }

                class Shop
                {
                    public function __construct(private Greeter $greeter) {}

                    public function welcome(string $name): string
                    {
                        return $this->greeter->greet($name);
                    }
                }

                echo (new Shop(new Formal()))->welcome('Amara');
                PHP,
            'options' => ['`Hi, Amara!`', '`Good morning, Amara.`', 'Both greetings', 'An error: `Shop` needs a `Friendly` greeter'],
            'correct_option' => 1,
            'explanation' => 'The shop uses whichever `Greeter` it was given, here a `Formal` one.',
        ],
        [
            'question' => 'A class creates its mailer inside a method with `new SmtpMailer()`. What\'s the main problem?',
            'options' => [
                'It can\'t send emails',
                'You can\'t swap in a different mailer, such as a fake, without changing the class',
                'PHP doesn\'t allow `new` inside methods',
                'The mailer is created too late',
            ],
            'correct_option' => 1,
            'explanation' => 'Hard-wiring `new` ties the class to one implementation. Injecting the mailer lets the caller choose.',
        ],
        [
            'question' => 'Which tool fits "share these three methods between `Book`, `Customer` and `Event`, which have nothing else in common"?',
            'options' => ['A common parent class', 'A trait', 'An enum', 'A static method on `Book`'],
            'correct_option' => 1,
            'explanation' => 'A trait shares code without claiming the classes are the same kind of thing. A parent class would wrongly say a customer *is a* book-like thing.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Lines make an order',
            'prompt' => "Write two classes:\n\n- `OrderLine`: constructor `(string \$title, float \$unitPrice, int \$quantity)`, and `total(): float`, the unit price times the quantity.\n- `Order`: `addLine(OrderLine \$line): void`, `lineCount(): int`, and `total(): float`, the sum of its lines' totals rounded to 2 places.\n\nAn order with 2 × 8.50 and 1 × 19.99 has 2 lines and totals 36.99.",
            'starter_code' => <<<'PHP'
                <?php

                // Write OrderLine and Order here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class OrderLine
                {
                    public function __construct(
                        public string $title,
                        public float $unitPrice,
                        public int $quantity,
                    ) {}

                    public function total(): float
                    {
                        return $this->unitPrice * $this->quantity;
                    }
                }

                class Order
                {
                    /** @var OrderLine[] */
                    private array $lines = [];

                    public function addLine(OrderLine $line): void
                    {
                        $this->lines[] = $line;
                    }

                    public function lineCount(): int
                    {
                        return count($this->lines);
                    }

                    public function total(): float
                    {
                        $sum = 0.0;
                        foreach ($this->lines as $line) {
                            $sum += $line->total();
                        }

                        return round($sum, 2);
                    }
                }
                PHP,
            'hint' => '`Order` keeps an array of `OrderLine` objects. Its `total()` loops over them and adds up each line\'s `total()`.',
            'checks' => [
                ['label' => 'An OrderLine of 2 × 8.50 totals 17.0', 'code' => "return expect_same((new OrderLine('Frankenstein', 8.5, 2))->total(), 17.0);"],
                ['label' => 'addLine() only accepts OrderLine objects', 'code' => "return expect_same((string) (new ReflectionMethod('Order', 'addLine'))->getParameters()[0]->getType(), 'OrderLine');"],
                ['label' => 'An order with 2 × 8.50 and 1 × 19.99 has 2 lines and totals 36.99', 'code' => <<<'PHP'
                    $order = new Order();
                    $order->addLine(new OrderLine('Frankenstein', 8.5, 2));
                    $order->addLine(new OrderLine('Dune', 19.99, 1));
                    return expect_same($order->lineCount() . ' lines, ' . $order->total(), '2 lines, 36.99');
                    PHP],
                ['label' => 'An empty order totals 0', 'code' => 'return expect_same((new Order())->total(), 0.0);'],
            ],
        ],
        [
            'title' => 'Bring your own tax',
            'prompt' => '`Invoice` works out its own tax with a number written into it. Change it so a `TaxCalculator` is **injected** through the constructor: `new Invoice(new TaxCalculator(), 50.0)`. `total()` returns the net amount plus `$this->tax->taxOn($net)`.',
            'starter_code' => <<<'PHP'
                <?php

                class TaxCalculator
                {
                    public function taxOn(float $net): float
                    {
                        return round($net * 0.2, 2);
                    }
                }

                class Invoice
                {
                    public function __construct(private float $net) {}

                    public function total(): float
                    {
                        return $this->net + round($this->net * 0.2, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TaxCalculator
                {
                    public function taxOn(float $net): float
                    {
                        return round($net * 0.2, 2);
                    }
                }

                class Invoice
                {
                    public function __construct(private TaxCalculator $tax, private float $net) {}

                    public function total(): float
                    {
                        return $this->net + $this->tax->taxOn($this->net);
                    }
                }
                PHP,
            'hint' => 'Add `private TaxCalculator $tax` as the constructor\'s first parameter, and call `$this->tax->taxOn($this->net)`.',
            'checks' => [
                ['label' => 'The constructor takes a TaxCalculator first', 'code' => "return expect_same((string) (new ReflectionMethod('Invoice', '__construct'))->getParameters()[0]->getType(), 'TaxCalculator');"],
                ['label' => 'An invoice for 50.0 totals 60.0', 'code' => 'return expect_same((new Invoice(new TaxCalculator(), 50.0))->total(), 60.0);'],
                ['label' => 'It uses the calculator it was given', 'code' => <<<'PHP'
                    $noTax = new class extends TaxCalculator {
                        public function taxOn(float $net): float
                        {
                            return 0.0;
                        }
                    };
                    return expect_same((new Invoice($noTax, 50.0))->total(), 50.0);
                    PHP],
            ],
        ],
        [
            'title' => 'Tell someone',
            'prompt' => 'Write a class `OrderService` that depends on the `Notifier` interface below, injected through its constructor. Its method `placeOrder(string $title): string` notifies `New order: Dune` (for Dune) and returns `Order placed: Dune`.',
            'starter_code' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write OrderService here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class OrderService
                {
                    public function __construct(private Notifier $notifier) {}

                    public function placeOrder(string $title): string
                    {
                        $this->notifier->notify("New order: $title");

                        return "Order placed: $title";
                    }
                }
                PHP,
            'hint' => 'Type the constructor parameter with the interface: `private Notifier $notifier`. Call `$this->notifier->notify(…)` in `placeOrder()`.',
            'checks' => [
                ['label' => 'The constructor takes a Notifier', 'code' => <<<'PHP'
                    if (! class_exists('OrderService', false)) return 'There is no class named OrderService.';
                    return expect_same((string) (new ReflectionMethod('OrderService', '__construct'))->getParameters()[0]->getType(), 'Notifier');
                    PHP],
                ['label' => "placeOrder('Dune') returns 'Order placed: Dune'", 'code' => <<<'PHP'
                    $quiet = new class implements Notifier {
                        public function notify(string $message): void {}
                    };
                    return expect_same((new OrderService($quiet))->placeOrder('Dune'), 'Order placed: Dune');
                    PHP],
                ['label' => "It notifies 'New order: Dune'", 'code' => <<<'PHP'
                    $spy = new class implements Notifier {
                        public array $messages = [];

                        public function notify(string $message): void
                        {
                            $this->messages[] = $message;
                        }
                    };
                    (new OrderService($spy))->placeOrder('Dune');
                    return expect_same(implode(' | ', $spy->messages), 'New order: Dune');
                    PHP],
            ],
        ],
        [
            'title' => 'A fake that remembers',
            'prompt' => 'Write `FakeNotifier`, which implements `Notifier` but sends nothing: it remembers each message, and `messages(): array` returns them in the order they arrived.',
            'starter_code' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write FakeNotifier here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class FakeNotifier implements Notifier
                {
                    private array $messages = [];

                    public function notify(string $message): void
                    {
                        $this->messages[] = $message;
                    }

                    public function messages(): array
                    {
                        return $this->messages;
                    }
                }
                PHP,
            'hint' => 'Keep an array property; `notify()` appends to it, `messages()` returns it.',
            'checks' => [
                ['label' => 'FakeNotifier implements Notifier', 'code' => <<<'PHP'
                    if (! class_exists('FakeNotifier', false)) return 'There is no class named FakeNotifier.';
                    return in_array('Notifier', class_implements('FakeNotifier')) ?: 'FakeNotifier should implement Notifier.';
                    PHP],
                ['label' => 'A new fake has no messages', 'code' => 'return expect_same(count((new FakeNotifier())->messages()), 0);'],
                ['label' => 'It remembers messages in order', 'code' => <<<'PHP'
                    $fake = new FakeNotifier();
                    $fake->notify('New order: Dune');
                    $fake->notify('New order: Circe');
                    return expect_same(implode(' | ', $fake->messages()), 'New order: Dune | New order: Circe');
                    PHP],
                ['label' => 'Each fake keeps its own messages', 'code' => <<<'PHP'
                    $a = new FakeNotifier();
                    $b = new FakeNotifier();
                    $a->notify('hello');
                    return expect_same(count($b->messages()), 0);
                    PHP],
            ],
        ],
        [
            'title' => 'Swap the discount',
            'prompt' => "Write:\n\n- an interface `DiscountPolicy` with `apply(float \$total): float`;\n- `NoDiscount`, which returns the total unchanged;\n- `TenPercentOverFifty`, which takes 10% off totals of 50 or more (rounded to 2 places) and leaves smaller totals alone;\n- `Basket`, created with a `DiscountPolicy`, with `add(float \$price): void` and `total(): float`, the sum of the prices with the policy applied.",
            'starter_code' => <<<'PHP'
                <?php

                class Basket
                {
                    private array $prices = [];

                    public function add(float $price): void
                    {
                        $this->prices[] = $price;
                    }

                    public function total(): float
                    {
                        return array_sum($this->prices);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                class NoDiscount implements DiscountPolicy
                {
                    public function apply(float $total): float
                    {
                        return $total;
                    }
                }

                class TenPercentOverFifty implements DiscountPolicy
                {
                    public function apply(float $total): float
                    {
                        return $total >= 50 ? round($total * 0.9, 2) : $total;
                    }
                }

                class Basket
                {
                    private array $prices = [];

                    public function __construct(private DiscountPolicy $policy) {}

                    public function add(float $price): void
                    {
                        $this->prices[] = $price;
                    }

                    public function total(): float
                    {
                        return $this->policy->apply(array_sum($this->prices));
                    }
                }
                PHP,
            'hint' => 'Both policies implement `DiscountPolicy`. `Basket` takes one in its constructor and calls `$this->policy->apply(…)` in `total()`.',
            'checks' => [
                ['label' => 'DiscountPolicy is an interface, and both policies implement it', 'code' => <<<'PHP'
                    if (! interface_exists('DiscountPolicy')) return 'There is no interface called DiscountPolicy.';
                    foreach (['NoDiscount', 'TenPercentOverFifty'] as $class) {
                        if (! class_exists($class) || ! in_array('DiscountPolicy', class_implements($class))) return "$class should implement DiscountPolicy.";
                    }
                    return true;
                    PHP],
                ['label' => 'With NoDiscount, 30 + 25 is 55', 'code' => <<<'PHP'
                    $basket = new Basket(new NoDiscount());
                    $basket->add(30.0);
                    $basket->add(25.0);
                    return expect_same($basket->total(), 55.0);
                    PHP],
                ['label' => 'With TenPercentOverFifty, 30 + 25 is 49.5', 'code' => <<<'PHP'
                    $basket = new Basket(new TenPercentOverFifty());
                    $basket->add(30.0);
                    $basket->add(25.0);
                    return expect_same($basket->total(), 49.5);
                    PHP],
                ['label' => 'TenPercentOverFifty leaves totals under 50 alone, and includes exactly 50', 'code' => <<<'PHP'
                    $policy = new TenPercentOverFifty();
                    return expect_same($policy->apply(49.99) . ' / ' . $policy->apply(50.0), '49.99 / 45');
                    PHP],
            ],
        ],
        [
            'title' => 'The checkout',
            'prompt' => 'Bring it together. `Checkout` is created with a `DiscountPolicy` and a `Notifier` (both given below). Its method `complete(array $prices): float` adds up the prices, applies the policy, notifies `Charged 45.00` (the amount with 2 decimal places, as `number_format()` gives it), and returns the amount charged.',
            'starter_code' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write Checkout here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class Checkout
                {
                    public function __construct(
                        private DiscountPolicy $discount,
                        private Notifier $notifier,
                    ) {}

                    public function complete(array $prices): float
                    {
                        $amount = $this->discount->apply(array_sum($prices));
                        $this->notifier->notify('Charged ' . number_format($amount, 2));

                        return $amount;
                    }
                }
                PHP,
            'hint' => 'Inject both through the constructor. In `complete()`: `array_sum()`, then `apply()`, then `notify()`, then return the amount.',
            'checks' => [
                ['label' => 'The constructor takes a DiscountPolicy and a Notifier', 'code' => <<<'PHP'
                    if (! class_exists('Checkout', false)) return 'There is no class named Checkout.';
                    $types = array_map(fn ($p) => (string) $p->getType(), (new ReflectionMethod('Checkout', '__construct'))->getParameters());
                    return expect_same(implode(', ', $types), 'DiscountPolicy, Notifier');
                    PHP],
                ['label' => 'It charges the discounted total and notifies it', 'code' => <<<'PHP'
                    $halfPrice = new class implements DiscountPolicy {
                        public function apply(float $total): float
                        {
                            return $total / 2;
                        }
                    };
                    $spy = new class implements Notifier {
                        public array $messages = [];

                        public function notify(string $message): void
                        {
                            $this->messages[] = $message;
                        }
                    };
                    $charged = (new Checkout($halfPrice, $spy))->complete([60.0, 30.0]);
                    return expect_same($charged . ' / ' . implode(' | ', $spy->messages), '45 / Charged 45.00');
                    PHP],
                ['label' => 'It works with any policy', 'code' => <<<'PHP'
                    $none = new class implements DiscountPolicy {
                        public function apply(float $total): float
                        {
                            return $total;
                        }
                    };
                    $quiet = new class implements Notifier {
                        public function notify(string $message): void {}
                    };
                    return expect_same((new Checkout($none, $quiet))->complete([19.99, 13.99]), 33.98);
                    PHP],
            ],
        ],
    ],
];
