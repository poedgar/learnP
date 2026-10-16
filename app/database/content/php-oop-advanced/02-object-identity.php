<?php

// Lesson 2 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'object-identity',
    'title' => 'Object identity',
    'summary' => 'Why two variables can share one object, how clone and __clone make shallow and deep copies, comparing and sorting objects, and remembering things about them with WeakMap.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try removing __clone().
        class Address
        {
            public function __construct(public string $city) {}
        }

        class Order
        {
            public function __construct(public Address $address) {}

            public function __clone(): void
            {
                $this->address = clone $this->address;
            }
        }

        $first = new Order(new Address('Leeds'));
        $second = clone $first;
        $second->address->city = 'York';

        echo $first->address->city . ' / ' . $second->address->city;
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Counter
                {
                    public int $value = 0;
                }

                function bump(Counter $counter): void
                {
                    $counter->value++;
                }

                $counter = new Counter();
                bump($counter);
                bump($counter);
                echo $counter->value;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error'],
            'correct_option' => 2,
            'explanation' => 'The function receives a handle to the same object, so both calls change it.',
        ],
        [
            'question' => 'What does `clone` copy?',
            'options' => [
                'Nothing; it creates a second handle to the same object',
                'The object and its property values; objects inside it are shared, not copied',
                'The object and every object inside it, all the way down',
                'Only the public properties',
            ],
            'correct_option' => 1,
            'explanation' => '`clone` is shallow. Properties holding objects are copied as handles unless `__clone()` clones them too.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Tag
                {
                    public function __construct(public string $name) {}
                }

                class Book
                {
                    public function __construct(public string $title, public Tag $tag) {}
                }

                $a = new Book('Dune', new Tag('sci-fi'));
                $b = clone $a;
                $b->title = 'Emma';
                $b->tag->name = 'classic';

                echo $a->title . ' ' . $a->tag->name;
                PHP,
            'options' => ['`Dune sci-fi`', '`Emma classic`', '`Dune classic`', '`Emma sci-fi`'],
            'correct_option' => 2,
            'explanation' => 'The string `title` was copied, but both books share the one `Tag` object, so renaming it shows up in `$a` too.',
        ],
        [
            'question' => 'Inside `__clone()`, what is `$this`?',
            'options' => ['The original object', 'The new copy', 'The class itself', 'There is no `$this` in `__clone()`'],
            'correct_option' => 1,
            'explanation' => 'PHP copies the object first, then calls `__clone()` on the copy, so the copy can replace its shared inner objects.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Point
                {
                    public function __construct(public int $x, public int $y) {}
                }

                $a = new Point(1, 2);
                $b = new Point(1, 2);

                var_dump($a == $b, $a === $b);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
            ],
            'correct_option' => 1,
            'explanation' => '`==` compares class and properties, which match. `===` asks whether they\'re the same object, and they\'re two objects.',
        ],
        [
            'question' => 'What does `3 <=> 7` give?',
            'options' => ['`-1`', '`0`', '`1`', '`4`'],
            'correct_option' => 0,
            'explanation' => 'The spaceship operator gives -1 when the left side is smaller, 0 when they\'re equal and 1 when it\'s larger, which is exactly what `usort()` wants.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book {}

                $notes = new WeakMap();
                $a = new Book();
                $b = new Book();
                $notes[$a] = 'signed';
                $notes[$b] = 'first edition';

                unset($a);
                echo count($notes);
                PHP,
            'options' => ['`2`', '`1`', '`0`', 'An error: objects can\'t be keys'],
            'correct_option' => 1,
            'explanation' => 'Once nothing else refers to `$a`\'s book, its entry vanishes from the `WeakMap`. `$b` is still around, so one entry remains.',
        ],
        [
            'question' => 'Two `Book` objects have the same ISBN but different prices. Which is the clearest way to ask whether they\'re the same book?',
            'options' => [
                '`$a == $b`',
                '`$a === $b`',
                'A method such as `$a->sameBookAs($b)` that compares the ISBNs',
                '`spl_object_id($a) === spl_object_id($b)`',
            ],
            'correct_option' => 2,
            'explanation' => '`==` would compare the prices too, and `===` and `spl_object_id()` ask whether they\'re one object. A method says exactly what "the same" means.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A copy that\'s really a copy',
            'prompt' => 'A `Basket` holds a `Customer` object. Cloning a basket currently shares the customer, so renaming the customer on the copy renames it on the original too. Add a `__clone()` method so each clone gets its own customer.',
            'starter_code' => <<<'PHP'
                <?php

                class Customer
                {
                    public function __construct(public string $name) {}
                }

                class Basket
                {
                    public array $items = [];

                    public function __construct(public Customer $customer) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Customer
                {
                    public function __construct(public string $name) {}
                }

                class Basket
                {
                    public array $items = [];

                    public function __construct(public Customer $customer) {}

                    public function __clone(): void
                    {
                        $this->customer = clone $this->customer;
                    }
                }
                PHP,
            'hint' => 'In `__clone()`, `$this` is the copy: replace `$this->customer` with `clone $this->customer`.',
            'checks' => [
                ['label' => 'Renaming the copy\'s customer leaves the original alone', 'code' => <<<'PHP'
                    $original = new Basket(new Customer('Amara'));
                    $copy = clone $original;
                    $copy->customer->name = 'Ben';
                    return expect_same($original->customer->name, 'Amara');
                    PHP],
                ['label' => 'The copy keeps the customer\'s details', 'code' => <<<'PHP'
                    $copy = clone new Basket(new Customer('Amara'));
                    return expect_same($copy->customer->name, 'Amara');
                    PHP],
                ['label' => 'Items are copied too', 'code' => <<<'PHP'
                    $original = new Basket(new Customer('Amara'));
                    $original->items[] = 'Dune';
                    $copy = clone $original;
                    $copy->items[] = 'Emma';
                    return expect_same([count($original->items), count($copy->items)], [1, 2]);
                    PHP],
            ],
        ],
        [
            'title' => 'A fresh draft',
            'prompt' => '`Invoice` has a `number`, a `Customer` and a list of lines. Add `__clone()` so a clone is a fresh draft: its `number` becomes `null`, it gets its own `Customer`, and its lines are kept.',
            'starter_code' => <<<'PHP'
                <?php

                class Customer
                {
                    public function __construct(public string $name) {}
                }

                class Invoice
                {
                    public array $lines = [];

                    public function __construct(public ?int $number, public Customer $customer) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Customer
                {
                    public function __construct(public string $name) {}
                }

                class Invoice
                {
                    public array $lines = [];

                    public function __construct(public ?int $number, public Customer $customer) {}

                    public function __clone(): void
                    {
                        $this->number = null;
                        $this->customer = clone $this->customer;
                    }
                }
                PHP,
            'hint' => 'Two lines in `__clone()`: set `$this->number = null;` and clone `$this->customer`.',
            'checks' => [
                ['label' => 'The clone has no number; the original keeps its own', 'code' => <<<'PHP'
                    $invoice = new Invoice(1001, new Customer('Amara'));
                    $draft = clone $invoice;
                    return expect_same([$invoice->number, $draft->number], [1001, null]);
                    PHP],
                ['label' => 'The clone has its own customer', 'code' => <<<'PHP'
                    $invoice = new Invoice(1001, new Customer('Amara'));
                    $draft = clone $invoice;
                    $draft->customer->name = 'Ben';
                    return expect_same($invoice->customer->name, 'Amara');
                    PHP],
                ['label' => 'The lines are kept', 'code' => <<<'PHP'
                    $invoice = new Invoice(1001, new Customer('Amara'));
                    $invoice->lines[] = 'Dune x1';
                    return expect_same((clone $invoice)->lines, ['Dune x1']);
                    PHP],
            ],
        ],
        [
            'title' => 'The same book',
            'prompt' => 'Give `Book` a method `sameBookAs(Book $other): bool` that is `true` when the two books have the same ISBN, whatever their titles and prices, ignoring any `-` characters in the ISBNs.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $isbn, public string $title, public float $price) {}

                    public function sameBookAs(Book $other): bool
                    {
                        return $this == $other;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $isbn, public string $title, public float $price) {}

                    public function sameBookAs(Book $other): bool
                    {
                        return str_replace('-', '', $this->isbn) === str_replace('-', '', $other->isbn);
                    }
                }
                PHP,
            'hint' => 'Compare `str_replace(\'-\', \'\', $this->isbn)` with the same for `$other`.',
            'checks' => [
                ['label' => 'Same ISBN, different price: the same book', 'code' => <<<'PHP'
                    $a = new Book('9780441013593', 'Dune', 19.99);
                    $b = new Book('9780441013593', 'Dune', 9.99);
                    return expect_same($a->sameBookAs($b), true);
                    PHP],
                ['label' => 'Dashes in the ISBN don\'t matter', 'code' => <<<'PHP'
                    $a = new Book('978-0-441-01359-3', 'Dune', 19.99);
                    $b = new Book('9780441013593', 'Dune (paperback)', 9.99);
                    return expect_same($a->sameBookAs($b), true);
                    PHP],
                ['label' => 'A different ISBN is a different book', 'code' => <<<'PHP'
                    $a = new Book('9780441013593', 'Dune', 19.99);
                    $b = new Book('9780141439518', 'Dune', 19.99);
                    return expect_same($a->sameBookAs($b), false);
                    PHP],
            ],
        ],
        [
            'title' => 'Cheapest first, then by title',
            'prompt' => 'Give `Book` a method `compareTo(Book $other): int` that orders books by price, cheapest first, and books with the same price by title from A to Z. It returns a negative number, 0 or a positive number, so `usort($books, fn ($a, $b) => $a->compareTo($b))` sorts them.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}

                    public function compareTo(Book $other): int
                    {
                        return 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}

                    public function compareTo(Book $other): int
                    {
                        return [$this->price, $this->title] <=> [$other->price, $other->title];
                    }
                }
                PHP,
            'hint' => 'Compare prices with `<=>`; when that gives 0, compare titles. Comparing two arrays, `[$this->price, $this->title] <=> [$other->price, $other->title]`, does both at once.',
            'checks' => [
                ['label' => 'A cheaper book comes first', 'code' => <<<'PHP'
                    $cheap = new Book('Emma', 7.99);
                    $dear = new Book('Dune', 19.99);
                    return ($cheap->compareTo($dear) < 0 && $dear->compareTo($cheap) > 0) ?: 'compareTo() should be negative for the cheaper book and positive the other way round.';
                    PHP],
                ['label' => 'Equal books compare as 0', 'code' => 'return expect_same((new Book(\'Dune\', 9.99))->compareTo(new Book(\'Dune\', 9.99)), 0);'],
                ['label' => 'usort() puts books in price order, then title order', 'code' => <<<'PHP'
                    $books = [new Book('Dune', 9.99), new Book('Circe', 13.99), new Book('Beloved', 9.99), new Book('Emma', 7.99)];
                    usort($books, fn ($a, $b) => $a->compareTo($b));
                    return expect_same(array_map(fn ($book) => $book->title, $books), ['Emma', 'Beloved', 'Dune', 'Circe']);
                    PHP],
            ],
        ],
        [
            'title' => 'Calculate once',
            'prompt' => "`PriceCalculator::total(Cart \$cart)` is slow. Make it remember each cart's total in a `WeakMap`, so asking again for the same cart doesn't recalculate it. Count the real calculations in the public `\$calculations` property, which starts at 0. Different carts each get their own total.",
            'starter_code' => <<<'PHP'
                <?php

                class Cart
                {
                    public function __construct(public array $prices) {}
                }

                class PriceCalculator
                {
                    public int $calculations = 0;

                    public function total(Cart $cart): float
                    {
                        $this->calculations++;

                        return array_sum($cart->prices);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Cart
                {
                    public function __construct(public array $prices) {}
                }

                class PriceCalculator
                {
                    public int $calculations = 0;

                    private WeakMap $totals;

                    public function __construct()
                    {
                        $this->totals = new WeakMap();
                    }

                    public function total(Cart $cart): float
                    {
                        if (! isset($this->totals[$cart])) {
                            $this->calculations++;
                            $this->totals[$cart] = array_sum($cart->prices);
                        }

                        return $this->totals[$cart];
                    }
                }
                PHP,
            'hint' => 'Create a `WeakMap` in the constructor. In `total()`, calculate and store `$this->totals[$cart]` only when `isset($this->totals[$cart])` is false.',
            'checks' => [
                ['label' => 'The total is right', 'code' => 'return expect_same((new PriceCalculator())->total(new Cart([9.99, 10.01])), 20.0);'],
                ['label' => 'Asking twice for the same cart calculates once', 'code' => <<<'PHP'
                    $calculator = new PriceCalculator();
                    $cart = new Cart([5.0, 5.0]);
                    $calculator->total($cart);
                    $calculator->total($cart);
                    return expect_same($calculator->calculations, 1);
                    PHP],
                ['label' => 'Each cart gets its own total', 'code' => <<<'PHP'
                    $calculator = new PriceCalculator();
                    $a = new Cart([5.0]);
                    $b = new Cart([7.0]);
                    return expect_same([$calculator->total($a), $calculator->total($b), $calculator->calculations], [5.0, 7.0, 2]);
                    PHP],
                ['label' => 'Totals are kept in a WeakMap', 'code' => <<<'PHP'
                    $calculator = new PriceCalculator();
                    foreach ((new ReflectionClass($calculator))->getProperties() as $property) {
                        if ($property->isInitialized($calculator) && $property->getValue($calculator) instanceof WeakMap) return true;
                    }
                    return 'Keep the totals in a WeakMap property, created in the constructor.';
                    PHP],
            ],
        ],
        [
            'title' => 'Never the same object twice',
            'prompt' => 'Write a function `uniqueObjects(array $objects): array` that returns the objects with repeats of the **same object** removed, keeping the first of each, in order. Objects that are merely equal (`==`) but separate are all kept.',
            'starter_code' => <<<'PHP'
                <?php

                function uniqueObjects(array $objects): array
                {
                    return array_values(array_unique($objects, SORT_REGULAR));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function uniqueObjects(array $objects): array
                {
                    $unique = [];
                    foreach ($objects as $object) {
                        if (! in_array($object, $unique, true)) {
                            $unique[] = $object;
                        }
                    }

                    return $unique;
                }
                PHP,
            'hint' => '`in_array($object, $unique, true)` uses `===`, which for objects means "the same object".',
            'checks' => [
                ['label' => 'Repeats of the same object are removed', 'code' => <<<'PHP'
                    $a = new stdClass();
                    $b = new stdClass();
                    $b->name = 'b';
                    $result = uniqueObjects([$a, $b, $a, $b, $a]);
                    return ($result === [$a, $b]) ?: 'Expected [$a, $b], got ' . count($result) . ' objects.';
                    PHP],
                ['label' => 'Equal but separate objects are all kept', 'code' => <<<'PHP'
                    $a = new stdClass();
                    $b = new stdClass();
                    return expect_same(count(uniqueObjects([$a, $b])), 2);
                    PHP],
                ['label' => 'An empty list gives an empty list', 'code' => 'return expect_same(uniqueObjects([]), []);'],
            ],
        ],
    ],
];
