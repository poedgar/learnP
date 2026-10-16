<?php

// Lesson 9 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'visitor-and-choosing-patterns',
    'title' => 'Visitor, and choosing a pattern',
    'summary' => 'Add new operations to a stable set of classes with visitors, compare them with a plain match, and learn to pick the right pattern for a problem, or none at all.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try writing a second visitor.
        interface Visitor { public function visitBook(Book $b): string; public function visitCard(Card $c): string; }
        interface Product { public function accept(Visitor $v): string; }

        class Book implements Product
        {
            public function __construct(public string $title) {}
            public function accept(Visitor $v): string { return $v->visitBook($this); }
        }

        class Card implements Product
        {
            public function __construct(public float $value) {}
            public function accept(Visitor $v): string { return $v->visitCard($this); }
        }

        class Label implements Visitor
        {
            public function visitBook(Book $b): string { return "Book: {$b->title}"; }
            public function visitCard(Card $c): string { return "Gift card £{$c->value}"; }
        }

        foreach ([new Book('Dune'), new Card(25)] as $product) {
            echo $product->accept(new Label()) . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'When does the Visitor pattern fit well?',
            'options' => [
                'When new classes are added often but operations rarely change',
                'When the set of classes is stable but new operations on them keep arriving',
                'When there is only one class',
                'When objects must be created lazily',
            ],
            'correct_option' => 1,
            'explanation' => 'Each new operation is one new visitor class; the product classes don\'t change.',
        ],
        [
            'question' => 'What does a product\'s `accept(Visitor $visitor)` method do?',
            'options' => [
                'Copies the visitor',
                'Calls the visitor method for its own class, passing itself',
                'Checks permissions',
                'Adds the visitor to a list of observers',
            ],
            'correct_option' => 1,
            'explanation' => 'A `Book` calls `$visitor->visitBook($this)`, so the visitor gets exactly the type it needs.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Visitor { public function visitDog(Dog $d): string; public function visitCat(Cat $c): string; }

                class Dog { public function accept(Visitor $v): string { return $v->visitDog($this); } }
                class Cat { public function accept(Visitor $v): string { return $v->visitCat($this); } }

                class Sound implements Visitor
                {
                    public function visitDog(Dog $d): string { return 'woof'; }
                    public function visitCat(Cat $c): string { return 'miaow'; }
                }

                echo implode(' ', array_map(fn ($pet) => $pet->accept(new Sound()), [new Cat(), new Dog(), new Cat()]));
                PHP,
            'options' => ['`miaow woof miaow`', '`woof miaow woof`', '`woof woof woof`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Each pet calls the visitor method for its own class.',
        ],
        [
            'question' => 'What is the cost of the Visitor pattern when you add a new product class?',
            'options' => [
                'Nothing',
                'Every existing visitor needs a new method for it',
                'All products must be rewritten',
                'The visitors stop working at run time',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s the mirror image of its strength: operations are cheap to add, classes are expensive.',
        ],
        [
            'question' => 'Which pattern fits "many parts of the system must react when an order is placed"?',
            'options' => ['Builder', 'Observer', 'Adapter', 'Singleton'],
            'correct_option' => 1,
            'explanation' => 'The order announces the event; any number of observers react to it.',
        ],
        [
            'question' => 'Which pattern fits "the payment library\'s class doesn\'t match our PaymentGateway interface"?',
            'options' => ['Adapter', 'Composite', 'State', 'Visitor'],
            'correct_option' => 0,
            'explanation' => 'An adapter makes an existing class fit the interface your code expects.',
        ],
        [
            'question' => 'Which pattern fits "users can undo their last few changes"?',
            'options' => ['Command', 'Facade', 'Factory', 'Proxy'],
            'correct_option' => 0,
            'explanation' => 'Commands that know how to reverse themselves, kept in a history, give undo.',
        ],
        [
            'question' => 'You have one shipping method today. What\'s the sensible design?',
            'options' => [
                'A Strategy interface, a factory and a registry, ready for the future',
                'A simple function, adding a Strategy when a second method actually arrives',
                'A singleton',
                'A Visitor',
            ],
            'correct_option' => 1,
            'explanation' => 'A pattern for a problem you don\'t have yet is just indirection. Add it when it earns its place.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Export visitor',
            'prompt' => 'Write `CsvExport`, a visitor implementing `ProductVisitor`, that returns one CSV line per product: `book,Dune,19.99` for a book and `card,,25.00` for a gift card (prices with two decimals).',
            'starter_code' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                    public function visitGiftCard(GiftCard $card): mixed;
                }

                interface Product
                {
                    public function accept(ProductVisitor $visitor): mixed;
                }

                class Book implements Product
                {
                    public function __construct(public string $title, public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class GiftCard implements Product
                {
                    public function __construct(public float $value) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                    public function visitGiftCard(GiftCard $card): mixed;
                }

                interface Product
                {
                    public function accept(ProductVisitor $visitor): mixed;
                }

                class Book implements Product
                {
                    public function __construct(public string $title, public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class GiftCard implements Product
                {
                    public function __construct(public float $value) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
                }

                class CsvExport implements ProductVisitor
                {
                    public function visitBook(Book $book): string
                    {
                        return sprintf('book,%s,%.2f', $book->title, $book->price);
                    }

                    public function visitGiftCard(GiftCard $card): string
                    {
                        return sprintf('card,,%.2f', $card->value);
                    }
                }
                PHP,
            'hint' => 'One method per product class, each returning its line with `sprintf()`.',
            'checks' => [
                ['label' => 'Lines for each product', 'code' => "\$export = new CsvExport();\nreturn expect_same([(new Book('Dune', 19.99))->accept(\$export), (new GiftCard(25))->accept(\$export)], ['book,Dune,19.99', 'card,,25.00']);"],
                ['label' => 'CsvExport is a ProductVisitor', 'code' => "return (new CsvExport() instanceof ProductVisitor) ?: 'Implement ProductVisitor.';"],
            ],
        ],
        [
            'title' => 'Add a new product type',
            'prompt' => "Add an `Ebook` product (title and price) that accepts visitors, add `visitEbook(Ebook \$ebook)` to the `ProductVisitor` interface, and update `PriceVisitor` so an e-book's price is its price with 20% VAT added, rounded to 2 places.",
            'starter_code' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                }

                interface Product
                {
                    public function accept(ProductVisitor $visitor): mixed;
                }

                class Book implements Product
                {
                    public function __construct(public string $title, public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class PriceVisitor implements ProductVisitor
                {
                    public function visitBook(Book $book): float { return $book->price; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                    public function visitEbook(Ebook $ebook): mixed;
                }

                interface Product
                {
                    public function accept(ProductVisitor $visitor): mixed;
                }

                class Book implements Product
                {
                    public function __construct(public string $title, public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class Ebook implements Product
                {
                    public function __construct(public string $title, public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitEbook($this); }
                }

                class PriceVisitor implements ProductVisitor
                {
                    public function visitBook(Book $book): float { return $book->price; }
                    public function visitEbook(Ebook $ebook): float { return round($ebook->price * 1.2, 2); }
                }
                PHP,
            'hint' => 'Three changes: the new class with its `accept()`, the new method on the interface, and the new method in `PriceVisitor`.',
            'checks' => [
                ['label' => 'Books keep their price', 'code' => "return expect_same((new Book('Dune', 19.99))->accept(new PriceVisitor()), 19.99);"],
                ['label' => 'E-books include VAT', 'code' => "return expect_same((new Ebook('Emma', 3.99))->accept(new PriceVisitor()), 4.79);"],
                ['label' => 'The interface has visitEbook()', 'code' => "return (new ReflectionClass('ProductVisitor'))->hasMethod('visitEbook') ?: 'Add visitEbook() to ProductVisitor.';"],
            ],
        ],
        [
            'title' => 'A shopping-basket summary',
            'prompt' => "Write `SummaryVisitor`, implementing `ProductVisitor`, that **remembers** what it visits: after visiting, `summary()` returns text like `'2 books, 1 card, £44.99'`: the number of books, of gift cards (`book`/`books`, `card`/`cards`), and the total value with two decimals.",
            'starter_code' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                    public function visitGiftCard(GiftCard $card): mixed;
                }

                class Book
                {
                    public function __construct(public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class GiftCard
                {
                    public function __construct(public float $value) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ProductVisitor
                {
                    public function visitBook(Book $book): mixed;
                    public function visitGiftCard(GiftCard $card): mixed;
                }

                class Book
                {
                    public function __construct(public float $price) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
                }

                class GiftCard
                {
                    public function __construct(public float $value) {}
                    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
                }

                class SummaryVisitor implements ProductVisitor
                {
                    private int $books = 0;
                    private int $cards = 0;
                    private float $total = 0.0;

                    public function visitBook(Book $book): mixed
                    {
                        $this->books++;
                        $this->total += $book->price;
                        return null;
                    }

                    public function visitGiftCard(GiftCard $card): mixed
                    {
                        $this->cards++;
                        $this->total += $card->value;
                        return null;
                    }

                    public function summary(): string
                    {
                        return sprintf(
                            '%d %s, %d %s, £%.2f',
                            $this->books, $this->books === 1 ? 'book' : 'books',
                            $this->cards, $this->cards === 1 ? 'card' : 'cards',
                            $this->total,
                        );
                    }
                }
                PHP,
            'hint' => 'Keep counters and a running total in private properties, updated by each `visit…()` method, and build the text in `summary()`.',
            'checks' => [
                ['label' => 'A mixed basket', 'code' => "\$v = new SummaryVisitor();\nforeach ([new Book(19.99), new GiftCard(15.0), new Book(10.0)] as \$p) { \$p->accept(\$v); }\nreturn expect_same(\$v->summary(), '2 books, 1 card, £44.99');"],
                ['label' => 'Singular and plural', 'code' => "\$v = new SummaryVisitor();\n(new Book(5.0))->accept(\$v);\nreturn expect_same(\$v->summary(), '1 book, 0 cards, £5.00');"],
            ],
        ],
        [
            'title' => 'The same job with match',
            'prompt' => 'Without visitors, write a function `shippingFor(object $item): float` using `match (true)` with `instanceof`: a `Book` costs 2.95, an `Ebook` 0.0, and a `Poster` 4.50. Any other object throws an `UnhandledMatchError` (which `match` does for you).',
            'starter_code' => <<<'PHP'
                <?php

                final class Book {}
                final class Ebook {}
                final class Poster {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Book {}
                final class Ebook {}
                final class Poster {}

                function shippingFor(object $item): float
                {
                    return match (true) {
                        $item instanceof Book => 2.95,
                        $item instanceof Ebook => 0.0,
                        $item instanceof Poster => 4.50,
                    };
                }
                PHP,
            'hint' => 'One arm per class, `$item instanceof Book => 2.95`, and no `default`.',
            'checks' => [
                ['label' => 'Each kind of item', 'code' => 'return expect_same([shippingFor(new Book()), shippingFor(new Ebook()), shippingFor(new Poster())], [2.95, 0.0, 4.5]);'],
                ['label' => 'Anything else is an UnhandledMatchError', 'code' => 'return expect_throws(UnhandledMatchError::class, fn () => shippingFor(new stdClass()));'],
            ],
        ],
        [
            'title' => 'Name the pattern',
            'prompt' => "Set `\$answers` to an array mapping each problem number to the name of the pattern that fits it best (as written: `'Factory'`, `'Builder'`, `'Adapter'`, `'Facade'`, `'Decorator'`, `'Proxy'`, `'Composite'`, `'Strategy'`, `'Observer'`, `'Command'`, `'State'`, `'Visitor'`):\n\n1. Send a confirmation email, update stock and award points whenever an order is placed.\n2. Let staff undo their last price changes.\n3. A cart can hold books and bundles, and bundles can hold bundles; all have a price.\n4. Only load the 50 MB sales history if someone actually opens the report.\n5. An order behaves differently when new, paid, shipped or cancelled.\n6. Use a shipping company's library, whose methods don't match your `Courier` interface.",
            'starter_code' => <<<'PHP'
                <?php

                $answers = [
                    1 => '',
                    2 => '',
                    3 => '',
                    4 => '',
                    5 => '',
                    6 => '',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $answers = [
                    1 => 'Observer',
                    2 => 'Command',
                    3 => 'Composite',
                    4 => 'Proxy',
                    5 => 'State',
                    6 => 'Adapter',
                ];
                PHP,
            'hint' => 'Look for the key phrase in each: "whenever … happens", "undo", "holds … which can hold", "only load if", "behaves differently when", "doesn\'t match your interface".',
            'checks' => [
                ['label' => 'Problems 1 to 3', 'code' => "\$a = \$GLOBALS['answers'] ?? [];\nreturn expect_same([\$a[1] ?? null, \$a[2] ?? null, \$a[3] ?? null], ['Observer', 'Command', 'Composite']);"],
                ['label' => 'Problems 4 to 6', 'code' => "\$a = \$GLOBALS['answers'] ?? [];\nreturn expect_same([\$a[4] ?? null, \$a[5] ?? null, \$a[6] ?? null], ['Proxy', 'State', 'Adapter']);"],
            ],
        ],
        [
            'title' => 'Simplify an over-engineered design',
            'prompt' => '`DiscountStrategyFactoryManager` wraps a single, never-changing rule (10% off orders of £50 or more) in three layers of classes. Replace all of it with one function `discountedTotal(float $total): float` returning the total with the discount applied when it qualifies, rounded to 2 places. Remove the classes.',
            'starter_code' => <<<'PHP'
                <?php

                interface DiscountStrategy { public function apply(float $total): float; }

                class TenPercentOverFifty implements DiscountStrategy
                {
                    public function apply(float $total): float { return $total >= 50 ? round($total * 0.9, 2) : $total; }
                }

                class DiscountStrategyFactory
                {
                    public function make(): DiscountStrategy { return new TenPercentOverFifty(); }
                }

                class DiscountStrategyFactoryManager
                {
                    public function __construct(private DiscountStrategyFactory $factory) {}
                    public function total(float $total): float { return $this->factory->make()->apply($total); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function discountedTotal(float $total): float
                {
                    return $total >= 50 ? round($total * 0.9, 2) : $total;
                }
                PHP,
            'hint' => 'The whole design does one thing; one line in a function does it too.',
            'checks' => [
                ['label' => 'Totals of £50 or more get 10% off', 'code' => 'return expect_same([discountedTotal(50.0), discountedTotal(80.0)], [45.0, 72.0]);'],
                ['label' => 'Smaller totals don\'t', 'code' => 'return expect_same(discountedTotal(49.99), 49.99);'],
                ['label' => 'The classes are gone', 'code' => "return (! class_exists('DiscountStrategyFactoryManager', false) && ! class_exists('DiscountStrategyFactory', false) && ! interface_exists('DiscountStrategy', false)) ?: 'Remove the old classes and interface.';"],
            ],
        ],
    ],
];
