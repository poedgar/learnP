<?php

// Lesson 7 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Property hooks and asymmetric visibility need PHP 8.4, which the sandbox runs.

return [
    'slug' => 'property-hooks-and-asymmetric-visibility',
    'title' => 'Property hooks and asymmetric visibility',
    'summary' => 'PHP 8.4\'s replacements for getters and setters: get and set hooks, virtual properties, properties in interfaces, and properties everyone can read but only the class can change.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try setting a negative price.
        class Book
        {
            public float $price = 0.0 {
                set {
                    if ($value < 0) {
                        throw new InvalidArgumentException('A price can\'t be negative.');
                    }
                    $this->price = $value;
                }
            }

            public string $label {
                get => '£' . number_format($this->price, 2);
            }
        }

        $book = new Book();
        $book->price = 9.5;
        echo $book->label;
        PHP,

    'quiz' => [
        [
            'question' => 'Inside a `set` hook, what is `$value`?',
            'options' => [
                'The value currently stored',
                'The value being assigned',
                'The property\'s default value',
                'The object itself',
            ],
            'correct_option' => 1,
            'explanation' => '`$value` is what the code assigned. The hook decides what (if anything) to store in `$this->property`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Tag
                {
                    public string $name {
                        set => strtolower(str_replace(' ', '-', $value));
                    }
                }

                $tag = new Tag();
                $tag->name = 'Science Fiction';
                echo $tag->name;
                PHP,
            'options' => ['`Science Fiction`', '`science-fiction`', '`science fiction`', '`Science-Fiction`'],
            'correct_option' => 1,
            'explanation' => 'With the short form `set => …`, whatever the expression gives is stored.',
        ],
        [
            'question' => 'When is a hooked property **virtual**?',
            'options' => [
                'When it has a `get` hook',
                'When its hooks never use the property\'s own stored value',
                'When it\'s declared in an interface',
                'When it\'s private',
            ],
            'correct_option' => 1,
            'explanation' => 'If no hook reads or writes `$this->property` itself, PHP doesn\'t give it any storage: it\'s worked out each time.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                class Circle
                {
                    public function __construct(public float $radius) {}

                    public float $area {
                        get => round(M_PI * $this->radius ** 2, 2);
                    }
                }

                $circle = new Circle(1.0);
                echo $circle->area . "\n";
                $circle->area = 10.0;
                PHP,
            'options' => [
                'It prints `3.14`, then the area becomes 10',
                'It prints `3.14`, then stops with an error: the property is read-only',
                'It prints `10`',
                'It doesn\'t run: virtual properties need a `set` hook',
            ],
            'correct_option' => 1,
            'explanation' => 'A virtual property with only a `get` hook can be read but not written.',
        ],
        [
            'question' => 'What does `public private(set) int $count` mean?',
            'options' => [
                'Anyone can read it and write it',
                'Anyone can read it; only the class itself can write it',
                'Only the class can read it; anyone can write it',
                'It can be set once and never changed',
            ],
            'correct_option' => 1,
            'explanation' => 'The first visibility is for reading, the one in brackets for writing.',
        ],
        [
            'question' => 'A property is `public protected(set)`. Where can it be written?',
            'options' => [
                'Anywhere',
                'Only in the class that declares it',
                'In the class and its child classes',
                'Nowhere after the constructor',
            ],
            'correct_option' => 2,
            'explanation' => '`protected(set)` follows the same rules as `protected`: the class and its children.',
        ],
        [
            'question' => 'How does `public private(set) string $status` differ from `public readonly string $status`?',
            'options' => [
                'They\'re the same',
                'The `private(set)` one can be changed many times by the class; the readonly one only set once',
                'The readonly one can be changed from outside',
                'The `private(set)` one can\'t be read from outside',
            ],
            'correct_option' => 1,
            'explanation' => '`readonly` means "never changes after it\'s set". Asymmetric visibility only limits **who** may change it.',
        ],
        [
            'question' => 'What does `public float $price { get; }` inside an interface require?',
            'options' => [
                'A method named `getPrice()`',
                'A public property `$price` that can be read, however the class provides it',
                'A property with a `get` hook, and no other kind',
                'A static property',
            ],
            'correct_option' => 1,
            'explanation' => 'An ordinary public property satisfies it, and so does a hooked or virtual one.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'No negative prices',
            'prompt' => 'Give `Book`\'s `$price` property a `set` hook that throws an `InvalidArgumentException` for a negative price and stores any other price. Code using the class still writes `$book->price = 9.99`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public float $price = 0.0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public float $price = 0.0 {
                        set {
                            if ($value < 0) {
                                throw new InvalidArgumentException("A price can't be negative.");
                            }

                            $this->price = $value;
                        }
                    }
                }
                PHP,
            'hint' => 'After the default value, add `{ set { … } }`. Throw when `$value < 0`, otherwise store it with `$this->price = $value;`.',
            'checks' => [
                ['label' => '$price has a set hook', 'code' => "return (new ReflectionProperty('Book', 'price'))->hasHook(PropertyHookType::Set) ?: 'Give \$price a set hook.';"],
                ['label' => 'Ordinary prices are stored', 'code' => <<<'PHP'
                    $book = new Book();
                    $book->price = 9.99;
                    $book->price = 0.0;
                    return expect_same($book->price, 0.0);
                    PHP],
                ['label' => 'A negative price throws an InvalidArgumentException', 'code' => <<<'PHP'
                    return expect_throws(InvalidArgumentException::class, function () {
                        $book = new Book();
                        $book->price = -0.01;
                    });
                    PHP],
                ['label' => 'A refused price leaves the old one in place', 'code' => <<<'PHP'
                    $book = new Book();
                    $book->price = 5.0;
                    try {
                        $book->price = -1.0;
                    } catch (InvalidArgumentException) {
                    }
                    return expect_same($book->price, 5.0);
                    PHP],
            ],
        ],
        [
            'title' => 'Tidy email addresses',
            'prompt' => 'Customers type their email in all sorts of ways. Give `Customer`\'s `$email` a `set` hook that stores it trimmed of spaces and in lower case: `\'  Amara@Example.COM \'` is stored as `\'amara@example.com\'`.',
            'starter_code' => <<<'PHP'
                <?php

                class Customer
                {
                    public string $email = '';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Customer
                {
                    public string $email = '' {
                        set => strtolower(trim($value));
                    }
                }
                PHP,
            'hint' => 'The short form is enough: `public string $email = \'\' { set => strtolower(trim($value)); }`.',
            'checks' => [
                ['label' => 'The email is stored tidied', 'code' => <<<'PHP'
                    $customer = new Customer();
                    $customer->email = '  Amara@Example.COM ';
                    return expect_same($customer->email, 'amara@example.com');
                    PHP],
                ['label' => '$email uses a set hook', 'code' => "return (new ReflectionProperty('Customer', 'email'))->hasHook(PropertyHookType::Set) ?: 'Use a set hook on \$email.';"],
            ],
        ],
        [
            'title' => 'A name worked out',
            'prompt' => "Give `Author` a **virtual** property `\$citation` with a `get` hook giving the last name, a comma and the first name: `Le Guin, Ursula`. It must follow changes to the names, and can't be written.",
            'starter_code' => <<<'PHP'
                <?php

                class Author
                {
                    public string $citation = '';

                    public function __construct(public string $firstName, public string $lastName)
                    {
                        $this->citation = "$lastName, $firstName";
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Author
                {
                    public string $citation {
                        get => "{$this->lastName}, {$this->firstName}";
                    }

                    public function __construct(public string $firstName, public string $lastName) {}
                }
                PHP,
            'hint' => 'Declare `public string $citation { get => … }` without a default, and build it from `$this->lastName` and `$this->firstName`. Remove the line in the constructor.',
            'checks' => [
                ['label' => "\$citation gives 'Le Guin, Ursula'", 'code' => "return expect_same((new Author('Ursula', 'Le Guin'))->citation, 'Le Guin, Ursula');"],
                ['label' => 'It follows changes to the names', 'code' => <<<'PHP'
                    $author = new Author('Toni', 'Morrison');
                    $author->firstName = 'Chloe';
                    return expect_same($author->citation, 'Morrison, Chloe');
                    PHP],
                ['label' => '$citation is virtual and can\'t be written', 'code' => <<<'PHP'
                    if (! (new ReflectionProperty('Author', 'citation'))->isVirtual()) return '$citation should be virtual: a get hook that never stores a value.';
                    return expect_throws(Error::class, function () {
                        $author = new Author('Toni', 'Morrison');
                        $author->citation = 'Someone';
                    });
                    PHP],
            ],
        ],
        [
            'title' => 'Points only the shop can award',
            'prompt' => "Make `LoyaltyCard`'s `\$points` readable from anywhere but writable only by the class itself, using asymmetric visibility. `earn(int \$points)` adds points; writing `\$card->points = 1000` from outside must fail.",
            'starter_code' => <<<'PHP'
                <?php

                class LoyaltyCard
                {
                    public int $points = 0;

                    public function earn(int $points): void
                    {
                        $this->points += $points;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LoyaltyCard
                {
                    public private(set) int $points = 0;

                    public function earn(int $points): void
                    {
                        $this->points += $points;
                    }
                }
                PHP,
            'hint' => 'Write `public private(set) int $points = 0;`.',
            'checks' => [
                ['label' => 'earn() adds points, and anyone can read them', 'code' => <<<'PHP'
                    $card = new LoyaltyCard();
                    $card->earn(10);
                    $card->earn(5);
                    return expect_same($card->points, 15);
                    PHP],
                ['label' => 'Writing from outside fails', 'code' => <<<'PHP'
                    return expect_throws(Error::class, function () {
                        $card = new LoyaltyCard();
                        $card->points = 1000;
                    });
                    PHP],
                ['label' => '$points is public private(set)', 'code' => <<<'PHP'
                    $property = new ReflectionProperty('LoyaltyCard', 'points');
                    return ($property->isPublic() && $property->isPrivateSet()) ?: '$points should be public to read and private(set) to write.';
                    PHP],
            ],
        ],
        [
            'title' => 'Every status, remembered',
            'prompt' => "Give `Order`'s `\$status` a `set` hook that stores the new status **and** adds it to the end of the private `\$history` array. `history()` returns that array, so after the statuses `paid` and `shipped`, it gives `['paid', 'shipped']`.",
            'starter_code' => <<<'PHP'
                <?php

                class Order
                {
                    private array $history = [];

                    public string $status = 'new';

                    public function history(): array
                    {
                        return $this->history;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Order
                {
                    private array $history = [];

                    public string $status = 'new' {
                        set {
                            $this->history[] = $value;
                            $this->status = $value;
                        }
                    }

                    public function history(): array
                    {
                        return $this->history;
                    }
                }
                PHP,
            'hint' => 'In the `set` hook, do two things: `$this->history[] = $value;` and `$this->status = $value;`.',
            'checks' => [
                ['label' => 'The status changes', 'code' => <<<'PHP'
                    $order = new Order();
                    $order->status = 'paid';
                    return expect_same($order->status, 'paid');
                    PHP],
                ['label' => 'Every status set is remembered, in order', 'code' => <<<'PHP'
                    $order = new Order();
                    $order->status = 'paid';
                    $order->status = 'shipped';
                    return expect_same($order->history(), ['paid', 'shipped']);
                    PHP],
            ],
        ],
        [
            'title' => 'Anything with a price',
            'prompt' => "Write an interface `Priced` that requires a readable `float \$price` property. Make `Book` implement it with its promoted `\$price`, and make `Bundle` implement it with a **virtual** `\$price` that is the sum of its items' prices minus 10%, rounded to 2 places. A bundle of books costing 10.0 and 20.0 costs 27.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}
                }

                class Bundle
                {
                    public function __construct(private array $items) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Priced
                {
                    public float $price { get; }
                }

                class Book implements Priced
                {
                    public function __construct(public string $title, public float $price) {}
                }

                class Bundle implements Priced
                {
                    public float $price {
                        get => round(array_sum(array_map(fn (Priced $item) => $item->price, $this->items)) * 0.9, 2);
                    }

                    public function __construct(private array $items) {}
                }
                PHP,
            'hint' => 'The interface declares `public float $price { get; }`. In `Bundle`, a `get` hook adds up `$item->price` for each item and multiplies by 0.9.',
            'checks' => [
                ['label' => 'Priced is an interface requiring a $price property', 'code' => <<<'PHP'
                    if (! interface_exists('Priced', false)) return 'There is no interface named Priced.';
                    return (new ReflectionClass('Priced'))->hasProperty('price') ?: 'Priced should declare a $price property.';
                    PHP],
                ['label' => 'Book and Bundle both implement Priced', 'code' => <<<'PHP'
                    return (new Book('Dune', 9.99) instanceof Priced && new Bundle([]) instanceof Priced) ?: 'Both classes should implement Priced.';
                    PHP],
                ['label' => 'A bundle costs its items minus 10%', 'code' => "return expect_same((new Bundle([new Book('A', 10.0), new Book('B', 20.0)]))->price, 27.0);"],
            ],
        ],
    ],
];
