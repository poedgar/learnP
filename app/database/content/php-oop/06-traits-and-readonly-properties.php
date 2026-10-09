<?php

// Lesson 6 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'traits-and-readonly-properties',
    'title' => 'Traits and readonly properties',
    'summary' => 'Share code between unrelated classes with traits, and build objects that never change with readonly properties, withers and value objects.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        trait HasSlug
        {
            public function slug(): string
            {
                return strtolower(str_replace(' ', '-', $this->name));
            }
        }

        readonly class Author
        {
            use HasSlug;

            public function __construct(public string $name) {}
        }

        echo (new Author('Ursula K. Le Guin'))->slug();
        PHP,

    'quiz' => [
        [
            'question' => 'What is a trait?',
            'options' => [
                'A parent class that can\'t be created with `new`',
                'A bundle of methods and properties that classes copy in with `use`',
                'An interface with method bodies',
                'A kind of exception',
            ],
            'correct_option' => 1,
            'explanation' => 'A trait\'s methods and properties become part of each class that uses it, without creating a parent or a type.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                trait Counts
                {
                    private int $count = 0;

                    public function tick(): int
                    {
                        return ++$this->count;
                    }
                }

                class Book
                {
                    use Counts;
                }

                $a = new Book();
                $b = new Book();
                $a->tick();
                $a->tick();

                echo $a->tick() . ' ' . $b->tick();
                PHP,
            'options' => ['`3 4`', '`3 1`', '`1 1`', '`2 1`'],
            'correct_option' => 1,
            'explanation' => 'A trait\'s property becomes a normal property of each object, so `$a` and `$b` count separately.',
        ],
        [
            'question' => 'Two traits used by one class both have a method `describe()`. What happens?',
            'options' => [
                'The trait listed last wins',
                'The trait listed first wins',
                'PHP refuses the class unless you choose one with `insteadof`',
                'Both methods run, one after the other',
            ],
            'correct_option' => 2,
            'explanation' => 'PHP won\'t guess. `A::describe insteadof B;` picks one, and `B::describe as other;` can keep the other under a new name.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                class Isbn
                {
                    public function __construct(public readonly string $value) {}
                }

                $isbn = new Isbn('9780441013593');
                $isbn->value = '0000000000000';
                echo $isbn->value;
                PHP,
            'options' => [
                'It prints `0000000000000`',
                'It prints `9780441013593`',
                'An error: a readonly property can\'t be changed once it\'s set',
                'An error: readonly properties can\'t be public',
            ],
            'correct_option' => 2,
            'explanation' => 'A readonly property is set once, by the class. It can be read from anywhere, but never changed.',
        ],
        [
            'question' => 'What does `readonly class Money { … }` mean?',
            'options' => [
                'Money objects can\'t be created',
                'Every property of `Money` is readonly',
                'Money\'s methods can\'t change anything outside the class',
                'Money can\'t be extended',
            ],
            'correct_option' => 1,
            'explanation' => 'A readonly class makes all its properties readonly, so you don\'t have to mark each one.',
        ],
        [
            'question' => 'A readonly `Address` has `withCity(string $city): static`. What should it do?',
            'options' => [
                'Change `$this->city` and return `$this`',
                'Return a new `Address` with the new city, leaving the original unchanged',
                'Throw an exception, because readonly objects can\'t change',
                'Return the new city as a string',
            ],
            'correct_option' => 1,
            'explanation' => 'A wither builds and returns a new object with the difference. The original can\'t change, and doesn\'t need to.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                readonly class Price
                {
                    public function __construct(public float $amount) {}
                }

                $a = new Price(9.99);
                $b = new Price(9.99);

                var_dump($a === $b);
                var_dump($a == $b);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
            ],
            'correct_option' => 2,
            'explanation' => '`===` asks whether they\'re the same object, which they aren\'t. `==` compares their properties, which are equal.',
        ],
        [
            'question' => 'Does `$book instanceof HasSlug` work, when `Book` uses the trait `HasSlug`?',
            'options' => [
                'Yes, it gives `true`',
                'Yes, but only for readonly classes',
                'No: a trait isn\'t a type. Use an interface when you need one',
                'No: traits can\'t be used with objects',
            ],
            'correct_option' => 2,
            'explanation' => 'Traits share code; interfaces describe types. A class often does both: implements an interface, and uses a trait to provide it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Shout the title',
            'prompt' => 'Write a trait `Shouts` with a method `shout(): string` that returns the object\'s `$title` in capitals followed by `!`, like `DUNE!`. Make `Book` use it.',
            'starter_code' => <<<'PHP'
                <?php

                // Write the Shouts trait here.

                class Book
                {
                    public function __construct(public string $title) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait Shouts
                {
                    public function shout(): string
                    {
                        return strtoupper($this->title) . '!';
                    }
                }

                class Book
                {
                    use Shouts;

                    public function __construct(public string $title) {}
                }
                PHP,
            'hint' => 'Declare `trait Shouts { … }` with the method, then write `use Shouts;` inside `Book`.',
            'checks' => [
                ['label' => 'A trait named Shouts exists', 'code' => "return trait_exists('Shouts', false) ?: 'There is no trait named Shouts.';"],
                ['label' => 'Book uses Shouts', 'code' => "return in_array('Shouts', class_uses('Book')) ?: 'Book should use the Shouts trait.';"],
                ['label' => "shout() gives 'DUNE!' for Dune", 'code' => "return expect_same((new Book('Dune'))->shout(), 'DUNE!');"],
                ['label' => 'It works for any title', 'code' => "return expect_same((new Book('The Hobbit'))->shout(), 'THE HOBBIT!');"],
            ],
        ],
        [
            'title' => 'Discounts for strangers',
            'prompt' => "Books and gift cards have nothing in common except a `\$price`. Write a trait `HasDiscount` with:\n\n- a private `int` property `\$discountPercent`, starting at 0;\n- `setDiscount(int \$percent): void`;\n- `discountedPrice(): float`, the price with the discount taken off, rounded to 2 places.\n\nMake both `Book` and `GiftCard` use it. A book costing 20.0 with a 25% discount costs 15.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}
                }

                class GiftCard
                {
                    public function __construct(public float $price) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait HasDiscount
                {
                    private int $discountPercent = 0;

                    public function setDiscount(int $percent): void
                    {
                        $this->discountPercent = $percent;
                    }

                    public function discountedPrice(): float
                    {
                        return round($this->price * (100 - $this->discountPercent) / 100, 2);
                    }
                }

                class Book
                {
                    use HasDiscount;

                    public function __construct(public string $title, public float $price) {}
                }

                class GiftCard
                {
                    use HasDiscount;

                    public function __construct(public float $price) {}
                }
                PHP,
            'hint' => 'The trait can read `$this->price`, because every class that uses it has one. Add `use HasDiscount;` to both classes.',
            'checks' => [
                ['label' => 'Book and GiftCard both use HasDiscount', 'code' => <<<'PHP'
                    if (! trait_exists('HasDiscount', false)) return 'There is no trait named HasDiscount.';
                    if (! in_array('HasDiscount', class_uses('Book'))) return 'Book should use HasDiscount.';
                    return in_array('HasDiscount', class_uses('GiftCard')) ?: 'GiftCard should use HasDiscount.';
                    PHP],
                ['label' => 'Without a discount, the price is unchanged', 'code' => 'return expect_same((new GiftCard(25.0))->discountedPrice(), 25.0);'],
                ['label' => 'A book costing 20.0 with 25% off costs 15.0', 'code' => <<<'PHP'
                    $book = new Book('Dune', 20.0);
                    $book->setDiscount(25);
                    return expect_same($book->discountedPrice(), 15.0);
                    PHP],
                ['label' => 'Each object keeps its own discount', 'code' => <<<'PHP'
                    $card = new GiftCard(10.0);
                    $other = new GiftCard(10.0);
                    $card->setDiscount(10);
                    return expect_same($card->discountedPrice() . ' / ' . $other->discountedPrice(), '9 / 10');
                    PHP],
            ],
        ],
        [
            'title' => 'An ISBN that never changes',
            'prompt' => "Write a class `Isbn` with a public **readonly** string `\$value`. Its constructor takes an ISBN, removes any `-` characters, and stores the result. If what's left isn't exactly 13 digits, throw an `InvalidArgumentException`.\n\n`new Isbn('978-0-441-01359-3')` stores `9780441013593`.",
            'starter_code' => <<<'PHP'
                <?php

                class Isbn
                {
                    public string $value;

                    public function __construct(string $isbn)
                    {
                        $this->value = $isbn;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Isbn
                {
                    public readonly string $value;

                    public function __construct(string $isbn)
                    {
                        $digits = str_replace('-', '', $isbn);

                        if (! preg_match('/^\d{13}$/', $digits)) {
                            throw new InvalidArgumentException("'$isbn' is not a 13-digit ISBN.");
                        }

                        $this->value = $digits;
                    }
                }
                PHP,
            'hint' => 'Declare `public readonly string $value;`. In the constructor, `str_replace(\'-\', \'\', $isbn)`, check `strlen() === 13` and `ctype_digit()` (or a regular expression), then set `$this->value`.',
            'checks' => [
                ['label' => '$value is readonly', 'code' => <<<'PHP'
                    if (! property_exists('Isbn', 'value')) return 'Isbn has no $value property.';
                    return (new ReflectionProperty('Isbn', 'value'))->isReadOnly() ?: '$value should be readonly.';
                    PHP],
                ['label' => 'Dashes are removed', 'code' => "return expect_same((new Isbn('978-0-441-01359-3'))->value, '9780441013593');"],
                ['label' => '$value can\'t be changed afterwards', 'code' => <<<'PHP'
                    $isbn = new Isbn('9780441013593');
                    return expect_throws(Error::class, function () use ($isbn) {
                        $isbn->value = '0000000000000';
                    });
                    PHP],
                ['label' => 'Too short, too long or not digits throws an InvalidArgumentException', 'code' => <<<'PHP'
                    foreach (['12345', '97804410135931', '978044101359X'] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => new Isbn($bad));
                        if ($result !== true) return "For '$bad': " . $result;
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Money that doesn\'t change',
            'prompt' => 'Make `Money` a **readonly class** with public `float $amount` and `string $currency`, and add `withAmount(float $amount): static`, which returns a **new** `Money` with that amount and the same currency. The original must be unchanged.',
            'starter_code' => <<<'PHP'
                <?php

                class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function withAmount(float $amount): static
                    {
                        $this->amount = $amount;

                        return $this;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function withAmount(float $amount): static
                    {
                        return new static($amount, $this->currency);
                    }
                }
                PHP,
            'hint' => 'Put `readonly` before `class`. In `withAmount()`, return `new static($amount, $this->currency)` (or `new self(…)`).',
            'checks' => [
                ['label' => 'Money\'s properties are readonly', 'code' => <<<'PHP'
                    $amount = new ReflectionProperty('Money', 'amount');
                    $currency = new ReflectionProperty('Money', 'currency');
                    return ($amount->isReadOnly() && $currency->isReadOnly()) ?: 'Make Money a readonly class (or both properties readonly).';
                    PHP],
                ['label' => 'withAmount() returns a new Money with the new amount', 'code' => <<<'PHP'
                    $price = new Money(19.99, 'GBP');
                    $sale = $price->withAmount(14.99);
                    if ($sale === $price) return 'withAmount() should return a new object, not the same one.';
                    return expect_same($sale->amount . ' ' . $sale->currency, '14.99 GBP');
                    PHP],
                ['label' => 'The original is unchanged', 'code' => <<<'PHP'
                    $price = new Money(19.99, 'GBP');
                    $price->withAmount(14.99);
                    return expect_same($price->amount, 19.99);
                    PHP],
            ],
        ],
        [
            'title' => 'The same amount of money',
            'prompt' => 'Give the readonly `Money` class a method `equals(Money $other): bool`. Two amounts of money are equal when both the amount and the currency match: 10.00 GBP equals 10.00 GBP, but not 10.00 EUR or 10.50 GBP.',
            'starter_code' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    // Add equals() here.
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function equals(Money $other): bool
                    {
                        return $this->amount === $other->amount && $this->currency === $other->currency;
                    }
                }
                PHP,
            'hint' => 'Compare both properties with `===` and join the two comparisons with `&&`.',
            'checks' => [
                ['label' => 'Equal amount and currency are equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.0, 'GBP')) === true ?: '10.00 GBP should equal 10.00 GBP.';"],
                ['label' => 'A different currency is not equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.0, 'EUR')) === false ?: '10.00 GBP should not equal 10.00 EUR.';"],
                ['label' => 'A different amount is not equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.5, 'GBP')) === false ?: '10.00 GBP should not equal 10.50 GBP.';"],
            ],
        ],
        [
            'title' => 'Settle the conflict',
            'prompt' => "`Book` uses two traits, `CatalogueEntry` and `ShelfLabel`, and both have a `describe()` method, so PHP refuses the class. Make `Book`'s `describe()` use `ShelfLabel`'s version, and keep `CatalogueEntry`'s version available as `catalogueEntry()`.",
            'starter_code' => <<<'PHP'
                <?php

                trait CatalogueEntry
                {
                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                trait ShelfLabel
                {
                    public function describe(): string
                    {
                        return strtoupper($this->title);
                    }
                }

                class Book
                {
                    use CatalogueEntry, ShelfLabel;

                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait CatalogueEntry
                {
                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                trait ShelfLabel
                {
                    public function describe(): string
                    {
                        return strtoupper($this->title);
                    }
                }

                class Book
                {
                    use CatalogueEntry, ShelfLabel {
                        ShelfLabel::describe insteadof CatalogueEntry;
                        CatalogueEntry::describe as catalogueEntry;
                    }

                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'hint' => 'Add a block after the `use` line: `ShelfLabel::describe insteadof CatalogueEntry;` and `CatalogueEntry::describe as catalogueEntry;`.',
            'checks' => [
                ['label' => "describe() gives ShelfLabel's 'DUNE'", 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->describe(), 'DUNE');"],
                ['label' => "catalogueEntry() gives 'Dune by Frank Herbert'", 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->catalogueEntry(), 'Dune by Frank Herbert');"],
                ['label' => 'Book still uses both traits', 'code' => "return expect_same(implode(', ', array_keys(class_uses('Book'))), 'CatalogueEntry, ShelfLabel');"],
            ],
        ],
    ],
];
