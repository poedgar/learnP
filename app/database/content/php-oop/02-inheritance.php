<?php

// Lesson 2 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'inheritance',
    'title' => 'Inheritance',
    'summary' => 'Build specialised classes on top of general ones with extends, override and extend their behaviour with parent::, and share data with subclasses using protected.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class Book
        {
            public function __construct(public string $title, public string $author) {}

            public function describe(): string
            {
                return $this->title . ' by ' . $this->author;
            }
        }

        class AudioBook extends Book
        {
            public function __construct(string $title, string $author, public string $narrator)
            {
                parent::__construct($title, $author);
            }
        }

        $book = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
        echo $book->describe() . ', narrated by ' . $book->narrator;
        PHP,

    'quiz' => [
        [
            'question' => 'What does `class EBook extends Book` mean?',
            'options' => [
                '`EBook` is a copy of `Book` that no longer changes when `Book` does',
                '`EBook` is a kind of `Book`: it inherits `Book`\'s properties and methods and can add its own',
                '`Book` inherits everything from `EBook`',
                'Every `Book` object is also an `EBook`',
            ],
            'correct_option' => 1,
            'explanation' => 'The child class inherits from the parent, and can add or override. Every `EBook` is a `Book`, but not every `Book` is an `EBook`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function kind(): string
                    {
                        return 'book';
                    }
                }

                class EBook extends Book
                {
                    public function kind(): string
                    {
                        return 'e-book';
                    }
                }

                $item = new EBook();
                echo $item->kind();
                PHP,
            'options' => ['`book`', '`e-book`', '`booke-book`', 'An error: `kind()` is declared twice'],
            'correct_option' => 1,
            'explanation' => '`EBook` overrides `kind()`, and an object always uses the version from its own class.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function kind(): string
                    {
                        return 'book';
                    }
                }

                class EBook extends Book
                {
                    public function kind(): string
                    {
                        return 'electronic ' . parent::kind();
                    }
                }

                echo (new EBook())->kind();
                PHP,
            'options' => ['`book`', '`electronic`', '`electronic book`', 'Nothing: the method calls itself forever'],
            'correct_option' => 2,
            'explanation' => '`parent::kind()` runs `Book`\'s version, which returns `book`; the override adds `electronic ` in front.',
        ],
        [
            'question' => 'Where can a `protected` property be used?',
            'options' => [
                'Anywhere',
                'Only in the class that declares it',
                'In the class that declares it and in its child classes',
                'Only in child classes',
            ],
            'correct_option' => 2,
            'explanation' => '`protected` sits between `public` and `private`: the family of classes can use it, code outside can\'t.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public string $title) {}
                }

                class EBook extends Book
                {
                    public function __construct(public int $sizeMb) {}
                }

                $ebook = new EBook(3);
                echo $ebook->title;
                PHP,
            'options' => [
                'It prints an empty line',
                'It prints `3`',
                'It prints `Book`',
                'An error: `$title` was never set, because `EBook`\'s constructor doesn\'t call `Book`\'s',
            ],
            'correct_option' => 3,
            'explanation' => 'A child constructor replaces the parent\'s. Call `parent::__construct(…)` to run it too; here nothing ever sets `$title`.',
        ],
        [
            'question' => 'What does `final class Isbn` prevent?',
            'options' => [
                'Any class from extending `Isbn`',
                'Creating more than one `Isbn` object',
                'Changing an `Isbn`\'s properties',
                'Calling `Isbn`\'s methods from outside',
            ],
            'correct_option' => 0,
            'explanation' => 'A `final` class can\'t have children. (`final` on a method stops that one method from being overridden.)',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                }

                class EBook extends Book
                {
                }

                var_dump(new EBook() instanceof Book);
                var_dump(new Book() instanceof EBook);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(false)`',
            ],
            'correct_option' => 2,
            'explanation' => 'An e-book is a book, so the first is true. A plain book isn\'t an e-book, so the second is false.',
        ],
        [
            'question' => 'How many classes can a PHP class extend directly?',
            'options' => ['None', 'One', 'Two', 'As many as you like'],
            'correct_option' => 1,
            'explanation' => 'PHP classes have exactly one parent at most. Interfaces and traits, later in this course, cover the cases where one isn\'t enough.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An e-book is a book',
            'prompt' => 'Declare a class `EBook` that extends `Book`, with a public `int` property `$fileSizeMb` that starts at `0`. An `EBook` should be created just like a `Book`: `new EBook(\'Dune\', \'Frank Herbert\')`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                // Declare EBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    public int $fileSizeMb = 0;
                }
                PHP,
            'hint' => 'Write `class EBook extends Book`, and declare the property inside it: `public int $fileSizeMb = 0;`. The constructor is inherited.',
            'checks' => [
                ['label' => 'A class named EBook exists', 'code' => "return class_exists('EBook', false) ?: 'There is no class named EBook.';"],
                ['label' => 'EBook extends Book', 'code' => "return is_subclass_of('EBook', 'Book') ?: 'EBook should extend Book.';"],
                ['label' => 'An EBook is created like a Book and can describe itself', 'code' => "return expect_same((new EBook('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert');"],
                ['label' => 'EBook has a public int $fileSizeMb that starts at 0', 'code' => <<<'PHP'
                    if (! property_exists('EBook', 'fileSizeMb')) return 'EBook has no $fileSizeMb property.';
                    $p = new ReflectionProperty('EBook', 'fileSizeMb');
                    if (! $p->isPublic()) return '$fileSizeMb should be public.';
                    if ((string) $p->getType() !== 'int') return '$fileSizeMb should be an int.';
                    return expect_same((new EBook('Dune', 'Frank Herbert'))->fileSizeMb, 0);
                    PHP],
            ],
        ],
        [
            'title' => 'Say it\'s an e-book',
            'prompt' => 'Override `describe()` in `EBook` so it adds ` (e-book)` to what `Book` returns: `Dune by Frank Herbert (e-book)`. Plain books should still describe themselves as before.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    // Override describe() here.
                }

                echo (new EBook('Dune', 'Frank Herbert'))->describe();
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    public function describe(): string
                    {
                        return parent::describe() . ' (e-book)';
                    }
                }
                PHP,
            'hint' => 'Declare `public function describe(): string` in `EBook`, and return `parent::describe() . \' (e-book)\'`.',
            'checks' => [
                ['label' => 'EBook has its own describe()', 'code' => "return (new ReflectionMethod('EBook', 'describe'))->getDeclaringClass()->getName() === 'EBook' ?: 'EBook should declare its own describe().';"],
                ['label' => "An e-book describes itself as 'Dune by Frank Herbert (e-book)'", 'code' => "return expect_same((new EBook('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert (e-book)');"],
                ['label' => 'It works for any e-book', 'code' => "return expect_same((new EBook('Circe', 'Madeline Miller'))->describe(), 'Circe by Madeline Miller (e-book)');"],
                ['label' => 'Plain books are unchanged', 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert');"],
            ],
        ],
        [
            'title' => 'Read by a narrator',
            'prompt' => 'Declare `AudioBook`, which extends `Book` and adds a public string `$narrator`. Its constructor takes the title, the author and the narrator, in that order, and must still set the title and author through `Book`\'s constructor. Override `describe()` to give `Dune by Frank Herbert, read by Scott Brick`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class AudioBook extends Book
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class AudioBook extends Book
                {
                    public function __construct(string $title, string $author, public string $narrator)
                    {
                        parent::__construct($title, $author);
                    }

                    public function describe(): string
                    {
                        return parent::describe() . ', read by ' . $this->narrator;
                    }
                }
                PHP,
            'hint' => 'Give `AudioBook` a constructor `(string $title, string $author, public string $narrator)` that calls `parent::__construct($title, $author)`.',
            'checks' => [
                ['label' => 'AudioBook extends Book', 'code' => "return is_subclass_of('AudioBook', 'Book') ?: 'AudioBook should extend Book.';"],
                ['label' => 'The constructor sets the title, author and narrator', 'code' => <<<'PHP'
                    $book = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
                    return expect_same($book->title . ' / ' . $book->author . ' / ' . $book->narrator, 'Dune / Frank Herbert / Scott Brick');
                    PHP],
                ['label' => "describe() gives 'Dune by Frank Herbert, read by Scott Brick'", 'code' => "return expect_same((new AudioBook('Dune', 'Frank Herbert', 'Scott Brick'))->describe(), 'Dune by Frank Herbert, read by Scott Brick');"],
                ['label' => 'Each audiobook keeps its own narrator', 'code' => <<<'PHP'
                    $a = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
                    $b = new AudioBook('Circe', 'Madeline Miller', 'Perdita Weeks');
                    $second = expect_same($b->describe(), 'Circe by Madeline Miller, read by Perdita Weeks');
                    return $second === true ? expect_same($a->describe(), 'Dune by Frank Herbert, read by Scott Brick') : $second;
                    PHP],
            ],
        ],
        [
            'title' => 'Only the family can change the price',
            'prompt' => "Change `Book` so its `\$price` is **protected** instead of public; `getPrice()` still reads it. Then declare `SaleBook`, which extends `Book` and has `applyDiscount(float \$percent): void`, lowering the price by that percentage.\n\nFor example, a `SaleBook` costing 20.0 costs 15.0 after `applyDiscount(25)`.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}

                    public function getPrice(): float
                    {
                        return $this->price;
                    }
                }

                // Declare SaleBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, protected float $price) {}

                    public function getPrice(): float
                    {
                        return $this->price;
                    }
                }

                class SaleBook extends Book
                {
                    public function applyDiscount(float $percent): void
                    {
                        $this->price = $this->price * (100 - $percent) / 100;
                    }
                }
                PHP,
            'hint' => 'Change `public float $price` to `protected float $price` in the constructor. Inside `SaleBook`, `$this->price` is still available.',
            'checks' => [
                ['label' => 'Book\'s $price is protected', 'code' => "return (new ReflectionProperty('Book', 'price'))->isProtected() ?: '\$price should be protected.';"],
                ['label' => 'getPrice() still works', 'code' => "return expect_same((new Book('Dune', 19.99))->getPrice(), 19.99);"],
                ['label' => 'SaleBook extends Book', 'code' => "return is_subclass_of('SaleBook', 'Book') ?: 'SaleBook should extend Book.';"],
                ['label' => 'applyDiscount(25) takes 20.0 down to 15.0', 'code' => <<<'PHP'
                    $book = new SaleBook('Dune', 20.0);
                    $book->applyDiscount(25);
                    return expect_same(round($book->getPrice(), 2), 15.0);
                    PHP],
                ['label' => 'Code outside the classes can\'t read the price directly', 'code' => "return expect_throws(Error::class, fn () => (new SaleBook('Dune', 20.0))->price);"],
            ],
        ],
        [
            'title' => 'Lock the shelf code',
            'prompt' => 'Every book has a shelf code worked out by `shelfCode()`: the first three letters of the author\'s surname in capitals, then a dash and the year, like `HER-1965`. Make `shelfCode()` **final**, so no subclass can change how it works.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $author, public int $year) {}

                    public function shelfCode(): string
                    {
                        $surname = substr($this->author, strrpos($this->author, ' ') + 1);

                        return strtoupper(substr($surname, 0, 3)) . '-' . $this->year;
                    }
                }

                echo (new Book('Frank Herbert', 1965))->shelfCode();
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $author, public int $year) {}

                    final public function shelfCode(): string
                    {
                        $surname = substr($this->author, strrpos($this->author, ' ') + 1);

                        return strtoupper(substr($surname, 0, 3)) . '-' . $this->year;
                    }
                }
                PHP,
            'hint' => 'Put `final` in front of the method: `final public function shelfCode(): string`.',
            'checks' => [
                ['label' => 'shelfCode() is final', 'code' => "return (new ReflectionMethod('Book', 'shelfCode'))->isFinal() ?: 'shelfCode() should be final.';"],
                ['label' => "shelfCode() gives 'HER-1965' for Frank Herbert, 1965", 'code' => "return expect_same((new Book('Frank Herbert', 1965))->shelfCode(), 'HER-1965');"],
                ['label' => 'Book itself can still be extended', 'code' => "return ! (new ReflectionClass('Book'))->isFinal() ?: 'Only the method should be final, not the whole class.';"],
            ],
        ],
        [
            'title' => 'A small family of products',
            'prompt' => "The shop sells more than books. Build this family:\n\n- `Product`: constructor `(string \$name, float \$price)`; a public `\$name`, a **protected** `\$price`, and `priceWithVat(): float`, which adds 20% VAT, rounded to 2 places.\n- `Book` extends `Product`. Books pay no VAT, so its `priceWithVat()` returns the price unchanged.\n- `EBook` extends `Book`, so it pays no VAT either.\n\nA `Product` costing 10.0 gives 12.0; a `Book` or an `EBook` costing 10.0 gives 10.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public string $name, protected float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }

                // Declare Book and EBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public string $name, protected float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }

                class Book extends Product
                {
                    public function priceWithVat(): float
                    {
                        return $this->price;
                    }
                }

                class EBook extends Book
                {
                }
                PHP,
            'hint' => '`Book` overrides `priceWithVat()` to return `$this->price`. `EBook` only needs `extends Book`: it inherits that version.',
            'checks' => [
                ['label' => 'A Product costing 10.0 costs 12.0 with VAT', 'code' => "return expect_same((new Product('Mug', 10.0))->priceWithVat(), 12.0);"],
                ['label' => 'Product\'s $price is protected', 'code' => "return (new ReflectionProperty('Product', 'price'))->isProtected() ?: '\$price should be protected.';"],
                ['label' => 'Book extends Product and pays no VAT', 'code' => <<<'PHP'
                    if (! is_subclass_of('Book', 'Product')) return 'Book should extend Product.';
                    return expect_same((new Book('Dune', 10.0))->priceWithVat(), 10.0);
                    PHP],
                ['label' => 'EBook extends Book and pays no VAT', 'code' => <<<'PHP'
                    if (! is_subclass_of('EBook', 'Book')) return 'EBook should extend Book.';
                    return expect_same((new EBook('Dune', 6.99))->priceWithVat(), 6.99);
                    PHP],
            ],
        ],
    ],
];
