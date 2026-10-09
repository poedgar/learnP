<?php

// Lesson 3 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'interfaces-and-abstract-classes',
    'title' => 'Interfaces and abstract classes',
    'summary' => 'Describe what classes can do with interfaces, write code that works with any class that keeps the promise, and share code in a family with abstract classes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        interface Describable
        {
            public function describe(): string;
        }

        class Book implements Describable
        {
            public function __construct(public string $title) {}

            public function describe(): string
            {
                return 'Book: ' . $this->title;
            }
        }

        function receiptLine(Describable $item): string
        {
            return '- ' . $item->describe();
        }

        echo receiptLine(new Book('Dune'));
        PHP,

    'quiz' => [
        [
            'question' => 'What does an interface contain?',
            'options' => [
                'The public methods a class promises to have, without their code',
                'Complete methods that classes inherit',
                'The properties every object must store',
                'A list of the classes that use it',
            ],
            'correct_option' => 0,
            'explanation' => 'An interface lists method signatures only. Each class that implements it writes the code.',
        ],
        [
            'question' => 'How many interfaces can one class implement?',
            'options' => ['None', 'One', 'Two', 'As many as you like'],
            'correct_option' => 3,
            'explanation' => 'A class has at most one parent, but can implement any number of interfaces, separated by commas.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                interface Describable
                {
                    public function describe(): string;
                }

                class Mug implements Describable
                {
                }

                echo 'Ready';
                PHP,
            'options' => [
                'It prints `Ready`',
                'It prints `Ready`, and fails later when `describe()` is called',
                'An error before anything is printed: `Mug` doesn\'t provide `describe()`',
                'It prints nothing',
            ],
            'correct_option' => 2,
            'explanation' => 'PHP checks the promise when the class is declared, so the script stops before `echo` runs.',
        ],
        [
            'question' => 'A function is declared as `function receiptLine(Describable $item)`. What can you pass to it?',
            'options' => [
                'Only objects of a class called `Describable`',
                'Any object whose class implements `Describable`',
                'Any object at all',
                'Only strings',
            ],
            'correct_option' => 1,
            'explanation' => 'An interface type accepts every object that keeps the promise, whatever its class.',
        ],
        [
            'question' => 'Can you create an object from an abstract class with `new`?',
            'options' => [
                'Yes, like any other class',
                'No; only from a non-abstract class that extends it',
                'Only if it has no abstract methods left',
                'Only from inside the class itself',
            ],
            'correct_option' => 1,
            'explanation' => 'Abstract classes exist to be extended. `new` on one is an error, even if it happens to have no abstract methods.',
        ],
        [
            'question' => 'A class extends an abstract class but doesn\'t implement one of its abstract methods. What then?',
            'options' => [
                'The method silently returns `null`',
                'PHP uses the parent\'s empty version',
                'The class must be declared abstract too, or PHP refuses it',
                'The method is skipped when called',
            ],
            'correct_option' => 2,
            'explanation' => 'Every abstract method needs a body somewhere down the family before objects can be created.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Order implements Countable
                {
                    private array $lines = ['Dune', 'Circe', 'Beloved'];

                    public function count(): int
                    {
                        return count($this->lines);
                    }
                }

                echo count(new Order());
                PHP,
            'options' => ['`0`', '`1`', '`3`', 'An error: `count()` only works on arrays'],
            'correct_option' => 2,
            'explanation' => 'Because `Order` implements `Countable`, PHP\'s `count()` calls its `count()` method.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public string $title) {}

                    public function __toString(): string
                    {
                        return '"' . $this->title . '"';
                    }
                }

                echo 'Now reading ' . new Book('Circe');
                PHP,
            'options' => [
                '`Now reading Book`',
                '`Now reading "Circe"`',
                '`Now reading Circe`',
                'An error: an object can\'t be joined with text',
            ],
            'correct_option' => 1,
            'explanation' => 'When an object is used as text, PHP calls its `__toString()` method.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Keep the promise',
            'prompt' => 'Make `Mug` implement the `Describable` interface. A mug\'s `describe()` returns `Mug with ` followed by its design, like `Mug with the bookshop logo`.',
            'starter_code' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Mug
                {
                    public function __construct(public string $design) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Mug implements Describable
                {
                    public function __construct(public string $design) {}

                    public function describe(): string
                    {
                        return 'Mug with ' . $this->design;
                    }
                }
                PHP,
            'hint' => 'Add `implements Describable` after `class Mug`, then write `public function describe(): string`.',
            'checks' => [
                ['label' => 'Mug implements Describable', 'code' => "return in_array('Describable', class_implements('Mug')) ?: 'Mug should implement Describable.';"],
                ['label' => "describe() gives 'Mug with the bookshop logo'", 'code' => "return expect_same((new Mug('the bookshop logo'))->describe(), 'Mug with the bookshop logo');"],
                ['label' => 'Each mug describes its own design', 'code' => "return expect_same((new Mug('a cat'))->describe(), 'Mug with a cat');"],
            ],
        ],
        [
            'title' => 'Anything describable',
            'prompt' => 'Write a function `label(Describable $item): string` that returns `* ` followed by the item\'s description, like `* Book: Dune`. It must accept **any** `Describable` object, not just books.',
            'starter_code' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Book implements Describable
                {
                    public function __construct(public string $title) {}

                    public function describe(): string
                    {
                        return 'Book: ' . $this->title;
                    }
                }

                // Write label() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Book implements Describable
                {
                    public function __construct(public string $title) {}

                    public function describe(): string
                    {
                        return 'Book: ' . $this->title;
                    }
                }

                function label(Describable $item): string
                {
                    return '* ' . $item->describe();
                }
                PHP,
            'hint' => 'Type the parameter with the interface: `function label(Describable $item): string`.',
            'checks' => [
                ['label' => 'A function label() exists', 'code' => "return function_exists('label') ?: 'There is no function called label().';"],
                ['label' => 'Its parameter is typed Describable', 'code' => "return expect_same((string) (new ReflectionFunction('label'))->getParameters()[0]->getType(), 'Describable');"],
                ['label' => "label() gives '* Book: Dune' for Dune", 'code' => "return expect_same(label(new Book('Dune')), '* Book: Dune');"],
                ['label' => 'It works for a Describable that isn\'t a book', 'code' => <<<'PHP'
                    $card = new class implements Describable {
                        public function describe(): string
                        {
                            return 'Gift card';
                        }
                    };
                    return expect_same(label($card), '* Gift card');
                    PHP],
            ],
        ],
        [
            'title' => 'Membership levels',
            'prompt' => "Write an **abstract** class `Membership` with:\n\n- an abstract method `discountPercent(): int`;\n- `price(float \$listPrice): float`, which takes the discount off the list price and rounds to 2 places.\n\nThen write `StandardMembership` (0% off) and `GoldMembership` (10% off). With a list price of 20.0, Standard gives 20.0 and Gold gives 18.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Membership
                {
                    public function price(float $listPrice): float
                    {
                        return $listPrice;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Membership
                {
                    abstract public function discountPercent(): int;

                    public function price(float $listPrice): float
                    {
                        return round($listPrice * (100 - $this->discountPercent()) / 100, 2);
                    }
                }

                class StandardMembership extends Membership
                {
                    public function discountPercent(): int
                    {
                        return 0;
                    }
                }

                class GoldMembership extends Membership
                {
                    public function discountPercent(): int
                    {
                        return 10;
                    }
                }
                PHP,
            'hint' => 'Declare `abstract class Membership` with `abstract public function discountPercent(): int;`, and call `$this->discountPercent()` inside `price()`.',
            'checks' => [
                ['label' => 'Membership is abstract', 'code' => "return (new ReflectionClass('Membership'))->isAbstract() ?: 'Membership should be abstract.';"],
                ['label' => 'discountPercent() is abstract in Membership', 'code' => <<<'PHP'
                    if (! method_exists('Membership', 'discountPercent')) return 'Membership has no discountPercent() method.';
                    return (new ReflectionMethod('Membership', 'discountPercent'))->isAbstract() ?: 'discountPercent() should be abstract in Membership.';
                    PHP],
                ['label' => 'StandardMembership gives 20.0 for 20.0', 'code' => 'return expect_same((new StandardMembership())->price(20.0), 20.0);'],
                ['label' => 'GoldMembership gives 18.0 for 20.0', 'code' => 'return expect_same((new GoldMembership())->price(20.0), 18.0);'],
                ['label' => 'GoldMembership rounds to 2 places', 'code' => 'return expect_same((new GoldMembership())->price(13.99), 12.59);'],
            ],
        ],
        [
            'title' => 'Count the shelf',
            'prompt' => 'Make `Shelf` implement `Countable`, so that `count($shelf)` gives the number of books added to it with `add()`.',
            'starter_code' => <<<'PHP'
                <?php

                class Shelf
                {
                    private array $books = [];

                    public function add(string $title): void
                    {
                        $this->books[] = $title;
                    }
                }

                $shelf = new Shelf();
                $shelf->add('Dune');
                echo count($shelf);
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Shelf implements Countable
                {
                    private array $books = [];

                    public function add(string $title): void
                    {
                        $this->books[] = $title;
                    }

                    public function count(): int
                    {
                        return count($this->books);
                    }
                }
                PHP,
            'hint' => 'Add `implements Countable`, and a `public function count(): int` that returns `count($this->books)`.',
            'checks' => [
                ['label' => 'Shelf implements Countable', 'code' => "return (new Shelf()) instanceof Countable ?: 'Shelf should implement Countable.';"],
                ['label' => 'An empty shelf counts 0', 'code' => 'return expect_same(count(new Shelf()), 0);'],
                ['label' => 'A shelf with three books counts 3', 'code' => <<<'PHP'
                    $shelf = new Shelf();
                    $shelf->add('Dune');
                    $shelf->add('Circe');
                    $shelf->add('Beloved');
                    return expect_same(count($shelf), 3);
                    PHP],
            ],
        ],
        [
            'title' => 'A book as text',
            'prompt' => 'Give `Book` a `__toString()` method, so that a book used as text reads like `Dune (Frank Herbert, 1965)`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public int $year,
                    ) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public int $year,
                    ) {}

                    public function __toString(): string
                    {
                        return $this->title . ' (' . $this->author . ', ' . $this->year . ')';
                    }
                }
                PHP,
            'hint' => 'Add `public function __toString(): string` and join the title, author and year with `.`.',
            'checks' => [
                ['label' => 'Book is Stringable', 'code' => "return (new Book('Dune', 'Frank Herbert', 1965)) instanceof Stringable ?: 'Book needs a __toString() method.';"],
                ['label' => "A book reads as 'Dune (Frank Herbert, 1965)'", 'code' => "return expect_same((string) new Book('Dune', 'Frank Herbert', 1965), 'Dune (Frank Herbert, 1965)');"],
                ['label' => 'It works for any book', 'code' => "return expect_same('Read ' . new Book('Circe', 'Madeline Miller', 2018), 'Read Circe (Madeline Miller, 2018)');"],
            ],
        ],
        [
            'title' => 'Ways to pay',
            'prompt' => "Design payment methods:\n\n- An interface `PaymentMethod` with `pay(float \$amount): string`.\n- `CardPayment`, created with the card's last four digits, whose `pay(12.5)` returns `Paid 12.50 by card ending 4242`.\n- `GiftCardPayment`, created with a balance. `pay()` takes the amount off the balance and returns `Paid 12.50 by gift card, 7.50 left` (for a balance of 20.0). If the balance is too small, it throws an `InvalidArgumentException` and leaves the balance alone.\n- A function `checkout(PaymentMethod \$method, float \$amount): string` that pays and returns the message.\n\nShow amounts with 2 decimal places, as `number_format(\$amount, 2)` does.",
            'starter_code' => <<<'PHP'
                <?php

                // Declare PaymentMethod, CardPayment, GiftCardPayment and checkout() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface PaymentMethod
                {
                    public function pay(float $amount): string;
                }

                class CardPayment implements PaymentMethod
                {
                    public function __construct(private string $lastFour) {}

                    public function pay(float $amount): string
                    {
                        return 'Paid ' . number_format($amount, 2) . ' by card ending ' . $this->lastFour;
                    }
                }

                class GiftCardPayment implements PaymentMethod
                {
                    public function __construct(private float $balance) {}

                    public function pay(float $amount): string
                    {
                        if ($amount > $this->balance) {
                            throw new InvalidArgumentException('Not enough left on the gift card.');
                        }

                        $this->balance -= $amount;

                        return 'Paid ' . number_format($amount, 2) . ' by gift card, ' . number_format($this->balance, 2) . ' left';
                    }
                }

                function checkout(PaymentMethod $method, float $amount): string
                {
                    return $method->pay($amount);
                }
                PHP,
            'hint' => 'Both payment classes implement `PaymentMethod`. `checkout()` is typed with the interface, so it works with either.',
            'checks' => [
                ['label' => 'PaymentMethod is an interface with pay()', 'code' => <<<'PHP'
                    if (! interface_exists('PaymentMethod')) return 'There is no interface called PaymentMethod.';
                    return method_exists('PaymentMethod', 'pay') ?: 'PaymentMethod should declare pay().';
                    PHP],
                ['label' => "A card pays 'Paid 12.50 by card ending 4242'", 'code' => <<<'PHP'
                    if (! in_array('PaymentMethod', class_implements('CardPayment'))) return 'CardPayment should implement PaymentMethod.';
                    return expect_same((new CardPayment('4242'))->pay(12.5), 'Paid 12.50 by card ending 4242');
                    PHP],
                ['label' => 'A gift card pays and keeps track of its balance', 'code' => <<<'PHP'
                    if (! in_array('PaymentMethod', class_implements('GiftCardPayment'))) return 'GiftCardPayment should implement PaymentMethod.';
                    $card = new GiftCardPayment(20.0);
                    $first = $card->pay(12.5);
                    if ($first !== 'Paid 12.50 by gift card, 7.50 left') return expect_same($first, 'Paid 12.50 by gift card, 7.50 left');
                    return expect_same($card->pay(7.5), 'Paid 7.50 by gift card, 0.00 left');
                    PHP],
                ['label' => 'A gift card refuses to pay more than its balance', 'code' => <<<'PHP'
                    $card = new GiftCardPayment(5.0);
                    $refused = expect_throws(InvalidArgumentException::class, fn () => $card->pay(12.5));
                    if ($refused !== true) return $refused;
                    return expect_same($card->pay(5.0), 'Paid 5.00 by gift card, 0.00 left');
                    PHP],
                ['label' => 'checkout() works with any payment method', 'code' => <<<'PHP'
                    if (! function_exists('checkout')) return 'There is no function called checkout().';
                    $type = (string) (new ReflectionFunction('checkout'))->getParameters()[0]->getType();
                    if ($type !== 'PaymentMethod') return 'checkout()\'s first parameter should be typed PaymentMethod.';
                    return expect_same(checkout(new CardPayment('1234'), 3), 'Paid 3.00 by card ending 1234');
                    PHP],
            ],
        ],
    ],
];
