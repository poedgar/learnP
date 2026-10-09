<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class PhpOopCourseSeeder extends Seeder
{
    use SyncsLessonContent;

    /**
     * Lessons 2 onwards, in order. Each is a Markdown file (the theory) and a
     * PHP file (title, summary, starter code, quiz, and challenges with their
     * checks) in database/content/php-oop, sharing this basename.
     */
    private const LATER_LESSONS = [
        '02-inheritance',
        '03-interfaces-and-abstract-classes',
        '04-static-members-constants-and-enums',
        '05-exceptions',
    ];

    public function run(): void
    {
        $course = Course::updateOrCreate(['slug' => 'php-oop'], [
            'title' => 'OOP in PHP',
            'language' => 'php',
            'description' => 'Organise your code around objects. Learn classes, constructors, encapsulation and the ideas behind every modern PHP framework.',
            'position' => 2,
        ]);

        $lesson = $course->lessons()->updateOrCreate(['slug' => 'classes-and-objects'], [
            'title' => 'Classes and objects',
            'summary' => 'Describe things with classes, create objects with new, give them behaviour with methods, and protect their data with private properties.',
            'content' => file_get_contents(database_path('content/php-oop/01-classes-and-objects.md')),
            'dataset' => null,
            'starter_code' => <<<'PHP'
                <?php

                // Change this code, then press Run (Ctrl+Enter).
                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                    ) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                $book = new Book('Dune', 'Frank Herbert');
                echo $book->describe();
                PHP,
            'position' => 1,
        ]);

        $this->syncByPosition($lesson->quizQuestions(), $this->positioned($this->quizQuestions()));
        $this->syncByPosition($lesson->challenges(), $this->positioned($this->challenges()));

        foreach (self::LATER_LESSONS as $index => $name) {
            $this->seedLesson($course, $name, $index + 2);
        }
    }

    private function seedLesson(Course $course, string $name, int $position): void
    {
        $definition = require database_path("content/php-oop/{$name}.php");

        $lesson = $course->lessons()->updateOrCreate(['slug' => $definition['slug']], [
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'content' => file_get_contents(database_path("content/php-oop/{$name}.md")),
            'dataset' => null,
            'starter_code' => $definition['starter_code'],
            'position' => $position,
        ]);

        $this->syncByPosition($lesson->quizQuestions(), $this->positioned($definition['quiz']));
        $this->syncByPosition($lesson->challenges(), $this->positioned($definition['challenges']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quizQuestions(): array
    {
        return [
            [
                'question' => 'What is the relationship between a class and an object?',
                'options' => [
                    'They are two names for the same thing',
                    'A class is a blueprint; an object is one thing built from it',
                    'An object is a blueprint; a class is one thing built from it',
                    'A class is a group of related functions with no data',
                ],
                'correct_option' => 1,
                'explanation' => 'A class describes the properties and methods a kind of thing has. Each object created from it with `new` is a separate instance with its own property values.',
            ],
            [
                'question' => 'Which line creates a `Book` object?',
                'options' => ['`$book = Book();`', '`$book = new Book();`', '`$book = create Book;`', '`$book = Book::new();`'],
                'correct_option' => 1,
                'explanation' => '`new` followed by the class name creates an instance and runs its constructor, if it has one.',
            ],
            [
                'question' => 'Inside a method, how do you read the current object\'s `title` property?',
                'options' => ['`$title`', '`$this->$title`', '`$this->title`', '`self.title`'],
                'correct_option' => 2,
                'explanation' => '`$this` is the object the method was called on, and `->title` reads its property. There is no `$` in front of the property name.',
            ],
            [
                'question' => 'What does this code print?',
                'code' => <<<'PHP'
                    class Counter
                    {
                        public int $count = 0;

                        public function increment(): void
                        {
                            $this->count++;
                        }
                    }

                    $a = new Counter();
                    $b = new Counter();
                    $a->increment();
                    $a->increment();
                    $b->increment();

                    echo $a->count;
                    PHP,
                'options' => ['`1`', '`2`', '`3`', '`0`'],
                'correct_option' => 1,
                'explanation' => '`$a` and `$b` are separate objects with their own `$count`. `$a` was incremented twice, so it prints 2.',
            ],
            [
                'question' => 'When does PHP call a class\'s `__construct` method?',
                'options' => [
                    'Only when you call `$object->__construct()` yourself',
                    'Automatically, each time an object is created with `new`',
                    'Once, the first time the class is used',
                    'When the script ends',
                ],
                'correct_option' => 1,
                'explanation' => 'The constructor runs automatically for every new object. Arguments passed to `new Book(...)` are passed to it.',
            ],
            [
                'question' => 'What happens when this code runs?',
                'code' => <<<'PHP'
                    class Wallet
                    {
                        private int $coins = 5;
                    }

                    $wallet = new Wallet();
                    echo $wallet->coins;
                    PHP,
                'options' => [
                    'It prints `5`',
                    'It prints nothing',
                    'PHP stops with an error: the property is private',
                    'It prints `null`',
                ],
                'correct_option' => 2,
                'explanation' => 'Private properties can only be used inside the class\'s own methods. Reading one from outside throws an `Error`: "Cannot access private property".',
            ],
            [
                'question' => 'What does `public function __construct(private string $name) {}` do?',
                'options' => [
                    'Declares a private `$name` property and assigns the argument to it',
                    'Nothing, because the constructor body is empty',
                    'Declares a local variable that disappears after the constructor runs',
                    'Makes the whole class private',
                ],
                'correct_option' => 0,
                'explanation' => 'This is constructor property promotion. A visibility keyword on a constructor parameter declares the property and assigns it, even with an empty body.',
            ],
            [
                'question' => 'What does this code print?',
                'code' => <<<'PHP'
                    class Counter
                    {
                        public int $count = 0;

                        public function increment(): void
                        {
                            $this->count++;
                        }
                    }

                    $a = new Counter();
                    $b = $a;
                    $b->increment();

                    echo $a->count;
                    PHP,
                'options' => ['`0`', '`1`', '`2`', 'An error'],
                'correct_option' => 1,
                'explanation' => 'Assigning an object doesn\'t copy it. `$a` and `$b` refer to the same object, so incrementing through `$b` is visible through `$a`. Use `clone` when you need a separate copy.',
            ],
        ];
    }

    /**
     * Each check is PHP code run after the learner's code; it passes by
     * returning true. See resources/php/check-harness.php.
     *
     * @return list<array<string, mixed>>
     */
    private function challenges(): array
    {
        return [
            [
                'title' => 'Your first class',
                'prompt' => 'Declare a class `Book` with two public string properties: `title` and `author`. Then create a book, set both properties, and echo its title.',
                'starter_code' => "<?php\n\nclass Book\n{\n    // Declare the properties here.\n}\n",
                'solution' => <<<'PHP'
                    <?php
                    class Book
                    {
                        public string $title;
                        public string $author;
                    }

                    $book = new Book();
                    $book->title = 'Dune';
                    $book->author = 'Frank Herbert';
                    echo $book->title;
                    PHP,
                'hint' => 'Properties are declared inside the class body, like `public string $title;`.',
                'checks' => [
                    ['label' => 'A class named Book exists', 'code' => "return class_exists('Book', false) ?: 'There is no class named Book.';"],
                    ['label' => 'Book has a public string property $title', 'code' => <<<'PHP'
                        if (! property_exists('Book', 'title')) return 'Book has no $title property.';
                        $p = new ReflectionProperty('Book', 'title');
                        if (! $p->isPublic()) return '$title should be public.';
                        return expect_same((string) $p->getType(), 'string');
                        PHP],
                    ['label' => 'Book has a public string property $author', 'code' => <<<'PHP'
                        if (! property_exists('Book', 'author')) return 'Book has no $author property.';
                        $p = new ReflectionProperty('Book', 'author');
                        if (! $p->isPublic()) return '$author should be public.';
                        return expect_same((string) $p->getType(), 'string');
                        PHP],
                    ['label' => 'Your code echoes a title', 'code' => "return trim(ob_get_contents()) !== '' ?: 'Your code did not print anything.';"],
                ],
            ],
            [
                'title' => 'Teach it to describe itself',
                'prompt' => 'Add a method `describe()` to `Book` that returns the title and author as one string, like `Dune by Frank Herbert`.',
                'starter_code' => "<?php\n\nclass Book\n{\n    public string \$title;\n    public string \$author;\n\n    // Add describe() here.\n}\n\n\$book = new Book();\n\$book->title = 'Dune';\n\$book->author = 'Frank Herbert';\necho \$book->describe();\n",
                'solution' => <<<'PHP'
                    <?php
                    class Book
                    {
                        public string $title;
                        public string $author;

                        public function describe(): string
                        {
                            return $this->title . ' by ' . $this->author;
                        }
                    }
                    PHP,
                'hint' => 'Inside the method, read the properties with $this->title and $this->author, and join them with the . operator.',
                'checks' => [
                    ['label' => 'Book has a describe() method', 'code' => "return method_exists('Book', 'describe') ?: 'Book has no describe() method.';"],
                    ['label' => "describe() returns 'Dune by Frank Herbert' for Dune", 'code' => <<<'PHP'
                        $book = new Book();
                        $book->title = 'Dune';
                        $book->author = 'Frank Herbert';
                        return expect_same($book->describe(), 'Dune by Frank Herbert');
                        PHP],
                    ['label' => 'describe() uses the book it is called on', 'code' => <<<'PHP'
                        $book = new Book();
                        $book->title = 'Circe';
                        $book->author = 'Madeline Miller';
                        return expect_same($book->describe(), 'Circe by Madeline Miller');
                        PHP],
                ],
            ],
            [
                'title' => 'Build it with a constructor',
                'prompt' => 'Give `Book` a constructor so a book can be created in one line: `new Book(\'Dune\', \'Frank Herbert\')`. Keep the public `title` and `author` properties and the `describe()` method.',
                'starter_code' => "<?php\n\nclass Book\n{\n    // Add a constructor that sets the title and author.\n\n    public function describe(): string\n    {\n        return \$this->title . ' by ' . \$this->author;\n    }\n}\n\necho (new Book('Dune', 'Frank Herbert'))->describe();\n",
                'solution' => <<<'PHP'
                    <?php
                    class Book
                    {
                        public function __construct(
                            public string $title,
                            public string $author,
                        ) {}

                        public function describe(): string
                        {
                            return $this->title . ' by ' . $this->author;
                        }
                    }
                    PHP,
                'hint' => 'Constructor property promotion does this in one step: public function __construct(public string $title, public string $author) {}',
                'checks' => [
                    ['label' => 'new Book($title, $author) sets both properties', 'code' => <<<'PHP'
                        $book = new Book('Dune', 'Frank Herbert');
                        return expect_same([$book->title, $book->author], ['Dune', 'Frank Herbert']);
                        PHP],
                    ['label' => 'describe() still works', 'code' => "return expect_same((new Book('Beloved', 'Toni Morrison'))->describe(), 'Beloved by Toni Morrison');"],
                    ['label' => 'Each book keeps its own values', 'code' => <<<'PHP'
                        $a = new Book('Dune', 'Frank Herbert');
                        $b = new Book('Circe', 'Madeline Miller');
                        return expect_same($a->describe(), 'Dune by Frank Herbert');
                        PHP],
                ],
            ],
            [
                'title' => 'Guard the balance',
                'prompt' => "Write a class `BankAccount` that starts with a balance of 0.\n\n- The balance must be a **private** property.\n- `deposit(float \$amount): void` adds money. If the amount is zero or negative, throw an `InvalidArgumentException` and leave the balance unchanged.\n- `getBalance(): float` returns the current balance.",
                'starter_code' => "<?php\n\nclass BankAccount\n{\n}\n\n\$account = new BankAccount();\n\$account->deposit(50);\necho \$account->getBalance();\n",
                'solution' => <<<'PHP'
                    <?php
                    class BankAccount
                    {
                        private float $balance = 0;

                        public function deposit(float $amount): void
                        {
                            if ($amount <= 0) {
                                throw new InvalidArgumentException('Deposits must be positive.');
                            }
                            $this->balance += $amount;
                        }

                        public function getBalance(): float
                        {
                            return $this->balance;
                        }
                    }
                    PHP,
                'hint' => 'Start with private float $balance = 0; and check the amount at the top of deposit() before changing anything.',
                'checks' => [
                    ['label' => 'A new account has a balance of 0', 'code' => 'return expect_same((float) (new BankAccount())->getBalance(), 0.0);'],
                    ['label' => 'Deposits add up', 'code' => <<<'PHP'
                        $account = new BankAccount();
                        $account->deposit(50);
                        $account->deposit(25.5);
                        return expect_same($account->getBalance(), 75.5);
                        PHP],
                    ['label' => 'The balance is stored in a private property', 'code' => <<<'PHP'
                        $properties = (new ReflectionClass('BankAccount'))->getProperties();
                        if ($properties === []) return 'BankAccount has no properties.';
                        foreach ($properties as $property) {
                            if (! $property->isPrivate()) return '$' . $property->getName() . ' should be private.';
                        }
                        return true;
                        PHP],
                    ['label' => 'Depositing zero or less throws InvalidArgumentException', 'code' => <<<'PHP'
                        $result = expect_throws(InvalidArgumentException::class, fn () => (new BankAccount())->deposit(-5));
                        return $result === true
                            ? expect_throws(InvalidArgumentException::class, fn () => (new BankAccount())->deposit(0))
                            : $result;
                        PHP],
                    ['label' => 'A rejected deposit leaves the balance unchanged', 'code' => <<<'PHP'
                        $account = new BankAccount();
                        $account->deposit(20);
                        try { $account->deposit(-5); } catch (Throwable) {}
                        return expect_same($account->getBalance(), 20.0);
                        PHP],
                ],
            ],
            [
                'title' => 'Shopping cart',
                'prompt' => "Write a class `Cart` for an online shop.\n\n- `addItem(string \$name, float \$price): void` adds an item.\n- `count(): int` returns how many items are in the cart.\n- `total(): float` returns the sum of all prices.\n\nKeep the items in a private array property.",
                'starter_code' => "<?php\n\nclass Cart\n{\n    private array \$items = [];\n}\n\n\$cart = new Cart();\n\$cart->addItem('Dune', 19.99);\n\$cart->addItem('Circe', 13.99);\necho \$cart->count() . ' items, total ' . \$cart->total();\n",
                'solution' => <<<'PHP'
                    <?php
                    class Cart
                    {
                        private array $items = [];

                        public function addItem(string $name, float $price): void
                        {
                            $this->items[] = ['name' => $name, 'price' => $price];
                        }

                        public function count(): int
                        {
                            return count($this->items);
                        }

                        public function total(): float
                        {
                            return array_sum(array_column($this->items, 'price'));
                        }
                    }
                    PHP,
                'hint' => 'Append to the array with $this->items[] = ...; count() and array_sum() do most of the work.',
                'checks' => [
                    ['label' => 'An empty cart has 0 items and a total of 0', 'code' => <<<'PHP'
                        $cart = new Cart();
                        return expect_same([$cart->count(), (float) $cart->total()], [0, 0.0]);
                        PHP],
                    ['label' => 'addItem() increases count()', 'code' => <<<'PHP'
                        $cart = new Cart();
                        $cart->addItem('Dune', 19.99);
                        $cart->addItem('Circe', 13.99);
                        $cart->addItem('Circe', 13.99);
                        return expect_same($cart->count(), 3);
                        PHP],
                    ['label' => 'total() adds up the prices', 'code' => <<<'PHP'
                        $cart = new Cart();
                        $cart->addItem('Dune', 19.99);
                        $cart->addItem('Circe', 13.99);
                        return expect_same(round($cart->total(), 2), 33.98);
                        PHP],
                    ['label' => 'Carts are independent', 'code' => <<<'PHP'
                        $a = new Cart();
                        $b = new Cart();
                        $a->addItem('Dune', 19.99);
                        return expect_same($b->count(), 0);
                        PHP],
                    ['label' => 'Items are kept in private properties', 'code' => <<<'PHP'
                        foreach ((new ReflectionClass('Cart'))->getProperties() as $property) {
                            if (! $property->isPrivate()) return '$' . $property->getName() . ' should be private.';
                        }
                        return true;
                        PHP],
                ],
            ],
        ];
    }
}
