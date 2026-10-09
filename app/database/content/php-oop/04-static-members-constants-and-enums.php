<?php

// Lesson 4 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'static-members-constants-and-enums',
    'title' => 'Static members, constants and enums',
    'summary' => 'Give classes their own constants and static members, build objects with named constructors, and model fixed sets of values with enums.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        enum Format: string
        {
            case Hardback = 'hardback';
            case Paperback = 'paperback';
            case EBook = 'ebook';
        }

        $format = Format::from('ebook');
        echo $format->name;
        PHP,

    'quiz' => [
        [
            'question' => 'How do you read the constant `VAT_RATE` of class `Book` from outside the class?',
            'options' => ['`Book->VAT_RATE`', '`Book::VAT_RATE`', '`$Book::VAT_RATE`', '`Book.VAT_RATE`'],
            'correct_option' => 1,
            'explanation' => 'Class constants and static members are read with the class name and `::`. Inside the class, `self::VAT_RATE` works too.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Visitor
                {
                    public static int $count = 0;

                    public function __construct()
                    {
                        self::$count++;
                    }
                }

                $a = new Visitor();
                $b = new Visitor();

                echo Visitor::$count;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error: `$count` belongs to each object'],
            'correct_option' => 2,
            'explanation' => 'There is only one static `$count`, shared by the whole class, so both constructors add to the same number.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public static function kind(): string
                    {
                        return 'book';
                    }

                    public static function describe(): string
                    {
                        return self::kind() . ' / ' . static::kind();
                    }
                }

                class EBook extends Book
                {
                    public static function kind(): string
                    {
                        return 'e-book';
                    }
                }

                echo EBook::describe();
                PHP,
            'options' => ['`book / book`', '`e-book / e-book`', '`book / e-book`', '`e-book / book`'],
            'correct_option' => 2,
            'explanation' => '`self::` means the class the code is written in (`Book`); `static::` means the class it was called on (`EBook`).',
        ],
        [
            'question' => 'Why is a static method like `Book::fromArray($data)` called a *named constructor*?',
            'options' => [
                'Because it replaces `__construct()`',
                'Because it\'s a static method that creates and returns a new object, with a name that says what it\'s built from',
                'Because it can only be called once',
                'Because PHP calls it automatically on `new`',
            ],
            'correct_option' => 1,
            'explanation' => 'Named constructors are ordinary static methods that build objects, often from a particular shape of data.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                enum Genre
                {
                    case Fantasy;
                    case Horror;
                }

                $a = Genre::Fantasy;
                $b = Genre::Fantasy;

                var_dump($a === $b);
                echo $a->name;
                PHP,
            'options' => [
                '`bool(true)` then `Fantasy`',
                '`bool(false)` then `Fantasy`',
                '`bool(true)` then `0`',
                'An error: enum cases can\'t be compared',
            ],
            'correct_option' => 0,
            'explanation' => 'Each enum case exists exactly once, so two variables holding `Genre::Fantasy` hold the same object. `->name` is the case\'s name.',
        ],
        [
            'question' => 'For a backed enum `Format`, what does `Format::tryFrom(\'scroll\')` return when no case has the value `scroll`?',
            'options' => ['The first case', '`null`', 'An empty string', 'It throws an error'],
            'correct_option' => 1,
            'explanation' => '`tryFrom()` gives `null` for an unknown value. `from()` is the one that throws a `ValueError`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                enum Format: string
                {
                    case Hardback = 'hardback';
                    case Paperback = 'paperback';
                }

                echo Format::Paperback->value;
                PHP,
            'options' => ['`Paperback`', '`paperback`', '`1`', '`Format::Paperback`'],
            'correct_option' => 1,
            'explanation' => '`->value` is the value the case is backed by; `->name` would give `Paperback`.',
        ],
        [
            'question' => 'A `match ($this)` inside an enum has an arm for every case except one. What happens when that case\'s method is called?',
            'options' => [
                'It returns `null`',
                'It returns the first arm\'s result',
                'PHP refuses the enum before anything runs',
                'An `UnhandledMatchError` is thrown',
            ],
            'correct_option' => 3,
            'explanation' => '`match` has no silent fallback: a value with no matching arm (and no `default`) throws an `UnhandledMatchError`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'One VAT rate',
            'prompt' => 'Give `Product` a public class constant `VAT_RATE` of `0.2`, and make `priceWithVat()` use it instead of the number written into the calculation.',
            'starter_code' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Product
                {
                    public const VAT_RATE = 0.2;

                    public function __construct(public float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * (1 + self::VAT_RATE), 2);
                    }
                }
                PHP,
            'hint' => 'Declare `public const VAT_RATE = 0.2;` and write `(1 + self::VAT_RATE)` in the calculation.',
            'checks' => [
                ['label' => 'Product::VAT_RATE is 0.2', 'code' => <<<'PHP'
                    if (! defined('Product::VAT_RATE')) return 'Product has no VAT_RATE constant.';
                    return expect_same(Product::VAT_RATE, 0.2);
                    PHP],
                ['label' => 'priceWithVat() gives 12.0 for 10.0', 'code' => 'return expect_same((new Product(10.0))->priceWithVat(), 12.0);'],
            ],
        ],
        [
            'title' => 'Counting books',
            'prompt' => 'Make `Book` count how many books have been created: a **private static** `int` property that the constructor adds 1 to, and a **static** method `created(): int` that returns it.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    private static int $created = 0;

                    public function __construct(public string $title)
                    {
                        self::$created++;
                    }

                    public static function created(): int
                    {
                        return self::$created;
                    }
                }
                PHP,
            'hint' => 'Declare `private static int $created = 0;`, add `self::$created++;` to the constructor, and return `self::$created` from a `public static function created(): int`.',
            'checks' => [
                ['label' => 'created() is a static method', 'code' => <<<'PHP'
                    if (! method_exists('Book', 'created')) return 'Book has no created() method.';
                    return (new ReflectionMethod('Book', 'created'))->isStatic() ?: 'created() should be static.';
                    PHP],
                ['label' => 'Creating three books adds 3', 'code' => <<<'PHP'
                    $before = Book::created();
                    new Book('Dune');
                    new Book('Circe');
                    new Book('Beloved');
                    return expect_same(Book::created() - $before, 3);
                    PHP],
                ['label' => 'The count is kept in a private static property', 'code' => <<<'PHP'
                    foreach ((new ReflectionClass('Book'))->getProperties(ReflectionProperty::IS_STATIC) as $p) {
                        if ($p->isPrivate()) return true;
                    }
                    return 'Keep the count in a private static property.';
                    PHP],
            ],
        ],
        [
            'title' => 'Build from an array',
            'prompt' => 'Add a named constructor `fromArray(array $data)` to `Book`: a static method that returns a new `Book` built from an array with `title` and `author` keys, like `[\'title\' => \'Dune\', \'author\' => \'Frank Herbert\']`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public static function fromArray(array $data): static
                    {
                        return new static($data['title'], $data['author']);
                    }
                }
                PHP,
            'hint' => '`public static function fromArray(array $data): static` returning `new static($data[\'title\'], $data[\'author\'])`. (`self` works too.)',
            'checks' => [
                ['label' => 'fromArray() is a static method', 'code' => <<<'PHP'
                    if (! method_exists('Book', 'fromArray')) return 'Book has no fromArray() method.';
                    return (new ReflectionMethod('Book', 'fromArray'))->isStatic() ?: 'fromArray() should be static.';
                    PHP],
                ['label' => 'It builds a Book with the title and author', 'code' => <<<'PHP'
                    $book = Book::fromArray(['title' => 'Dune', 'author' => 'Frank Herbert']);
                    if (! $book instanceof Book) return 'fromArray() should return a Book.';
                    return expect_same([$book->title, $book->author], ['Dune', 'Frank Herbert']);
                    PHP],
                ['label' => 'The order of the keys doesn\'t matter', 'code' => <<<'PHP'
                    $book = Book::fromArray(['author' => 'Toni Morrison', 'title' => 'Beloved']);
                    return expect_same([$book->title, $book->author], ['Beloved', 'Toni Morrison']);
                    PHP],
            ],
        ],
        [
            'title' => 'Genres',
            'prompt' => 'Declare an enum `Genre` with the cases `Fantasy`, `Horror`, `Romance` and `ScienceFiction`, in that order. Then change `Book` so its `$genre` is a `Genre` instead of a string.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $genre) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum Genre
                {
                    case Fantasy;
                    case Horror;
                    case Romance;
                    case ScienceFiction;
                }

                class Book
                {
                    public function __construct(public string $title, public Genre $genre) {}
                }
                PHP,
            'hint' => 'Declare `enum Genre { case Fantasy; … }`, and change the constructor\'s `string $genre` to `Genre $genre`.',
            'checks' => [
                ['label' => 'An enum named Genre exists', 'code' => "return enum_exists('Genre', false) ?: 'There is no enum named Genre.';"],
                ['label' => 'Genre has Fantasy, Horror, Romance and ScienceFiction', 'code' => "return expect_same(array_map(fn (\$case) => \$case->name, Genre::cases()), ['Fantasy', 'Horror', 'Romance', 'ScienceFiction']);"],
                ['label' => 'A book\'s genre is a Genre', 'code' => <<<'PHP'
                    $type = (string) (new ReflectionProperty('Book', 'genre'))->getType();
                    if ($type !== 'Genre') return '$genre should be typed Genre.';
                    return expect_same((new Book('Dune', Genre::ScienceFiction))->genre, Genre::ScienceFiction);
                    PHP],
            ],
        ],
        [
            'title' => 'Order status',
            'prompt' => 'Declare a **string-backed** enum `OrderStatus` with these cases and values: `Pending` (`pending`), `Shipped` (`shipped`) and `Delivered` (`delivered`). Give it a method `label(): string` that returns `Waiting to ship`, `On its way` and `Delivered` respectively.',
            'starter_code' => <<<'PHP'
                <?php

                enum OrderStatus
                {
                    case Pending;
                    case Shipped;
                    case Delivered;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum OrderStatus: string
                {
                    case Pending = 'pending';
                    case Shipped = 'shipped';
                    case Delivered = 'delivered';

                    public function label(): string
                    {
                        return match ($this) {
                            self::Pending => 'Waiting to ship',
                            self::Shipped => 'On its way',
                            self::Delivered => 'Delivered',
                        };
                    }
                }
                PHP,
            'hint' => 'Write `enum OrderStatus: string` and give each case a value with `=`. In `label()`, use `match ($this)` with an arm for each case.',
            'checks' => [
                ['label' => 'OrderStatus is backed by strings', 'code' => <<<'PHP'
                    if (! enum_exists('OrderStatus', false)) return 'There is no enum named OrderStatus.';
                    return expect_same((string) (new ReflectionEnum('OrderStatus'))->getBackingType(), 'string');
                    PHP],
                ['label' => 'The values are pending, shipped and delivered', 'code' => "return expect_same(array_map(fn (\$case) => \$case->value, OrderStatus::cases()), ['pending', 'shipped', 'delivered']);"],
                ['label' => "OrderStatus::from('shipped') is Shipped", 'code' => "return expect_same(OrderStatus::from('shipped'), OrderStatus::Shipped);"],
                ['label' => 'label() gives the right text for each case', 'code' => "return expect_same(array_map(fn (\$case) => \$case->label(), OrderStatus::cases()), ['Waiting to ship', 'On its way', 'Delivered']);"],
            ],
        ],
        [
            'title' => 'Shipping costs',
            'prompt' => "Write a function `shippingCost(DeliverySpeed \$speed, float \$orderTotal): float` for the `DeliverySpeed` enum below:\n\n- `Standard` costs 2.99, but is **free** (0.0) when the order total is 50 or more;\n- `Express` costs 4.99;\n- `NextDay` costs 9.99.",
            'starter_code' => <<<'PHP'
                <?php

                enum DeliverySpeed
                {
                    case Standard;
                    case Express;
                    case NextDay;
                }

                // Write shippingCost() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum DeliverySpeed
                {
                    case Standard;
                    case Express;
                    case NextDay;
                }

                function shippingCost(DeliverySpeed $speed, float $orderTotal): float
                {
                    return match ($speed) {
                        DeliverySpeed::Standard => $orderTotal >= 50 ? 0.0 : 2.99,
                        DeliverySpeed::Express => 4.99,
                        DeliverySpeed::NextDay => 9.99,
                    };
                }
                PHP,
            'hint' => 'Use `match ($speed)` with one arm per case. The `Standard` arm can use `$orderTotal >= 50 ? 0.0 : 2.99`.',
            'checks' => [
                ['label' => 'A function shippingCost() exists', 'code' => "return function_exists('shippingCost') ?: 'There is no function called shippingCost().';"],
                ['label' => 'Standard costs 2.99 under 50', 'code' => 'return expect_same(shippingCost(DeliverySpeed::Standard, 49.99), 2.99);'],
                ['label' => 'Standard is free from 50', 'code' => 'return expect_same(shippingCost(DeliverySpeed::Standard, 50.0), 0.0);'],
                ['label' => 'Express and NextDay cost 4.99 and 9.99, whatever the total', 'code' => 'return expect_same([shippingCost(DeliverySpeed::Express, 80.0), shippingCost(DeliverySpeed::NextDay, 10.0)], [4.99, 9.99]);'],
            ],
        ],
    ],
];
