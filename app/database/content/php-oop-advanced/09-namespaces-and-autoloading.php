<?php

// Lesson 9 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the global namespace; a check
// returns true or a message.

return [
    'slug' => 'namespaces-and-autoloading',
    'title' => 'Namespaces and autoloading',
    'summary' => 'Keep names from clashing with namespaces, import them with use and aliases, avoid the missing-backslash trap, and see how autoloaders and PSR-4 find each class\'s file.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        namespace Shop\Catalogue {
            class Book
            {
                public function __construct(public string $title) {}
            }
        }

        namespace App {
            use Shop\Catalogue\Book;

            $book = new Book('Dune');
            echo get_class($book) . "\n";
            echo Book::class;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What problem do namespaces solve?',
            'options' => [
                'They make code run faster',
                'They let different code use the same class names without clashing',
                'They make classes private',
                'They replace interfaces',
            ],
            'correct_option' => 1,
            'explanation' => '`Shop\Book` and `Pdf\Book` are different names, so both can exist in one program.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                namespace Shop\Orders;

                class Invoice {}

                echo Invoice::class;
                PHP,
            'options' => ['`Invoice`', '`Shop\Orders\Invoice`', '`\Shop\Orders\Invoice`', '`Orders\Invoice`'],
            'correct_option' => 1,
            'explanation' => '`::class` gives the fully qualified name, without a leading backslash.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                namespace Shop;

                echo strtoupper('dune') . ' ' . strlen('dune');
                PHP,
            'options' => [
                '`DUNE 4`',
                'An error: `Shop\strtoupper()` doesn\'t exist',
                '`dune 4`',
                'An error: functions can\'t be used in a namespace',
            ],
            'correct_option' => 0,
            'explanation' => 'When `Shop\strtoupper()` doesn\'t exist, PHP falls back to the global function. Classes don\'t get that fallback.',
        ],
        [
            'question' => 'Inside `namespace Shop;`, what does `new Exception(\'Out of stock\')` do, if there\'s no `use` for it?',
            'options' => [
                'Creates PHP\'s built-in `Exception`',
                'Stops with an error: class `Shop\Exception` not found',
                'Creates a `Shop\Exception` automatically',
                'Creates an `Error` instead',
            ],
            'correct_option' => 1,
            'explanation' => 'Unqualified class names are looked up in the current namespace. Write `\Exception` or add `use Exception;`.',
        ],
        [
            'question' => 'After `use Shop\Catalogue\Book as CatalogueBook;`, how do you create a `Shop\Catalogue\Book`?',
            'options' => ['`new Book()`', '`new CatalogueBook()`', '`new Catalogue\Book()`', '`new \CatalogueBook()`'],
            'correct_option' => 1,
            'explanation' => '`as` gives the imported class a new short name for this file.',
        ],
        [
            'question' => 'When does PHP call a function registered with `spl_autoload_register()`?',
            'options' => [
                'Once, when the script starts',
                'Every time any class is used',
                'The first time code uses a class PHP doesn\'t know yet',
                'Only when you call it yourself',
            ],
            'correct_option' => 2,
            'explanation' => 'The autoloader gets the missing class\'s full name and has a chance to load its file before PHP gives up.',
        ],
        [
            'question' => 'Under the PSR-4 rule `"App\\\\": "src/"`, which file holds `App\Http\Controllers\BookController`?',
            'options' => [
                '`App/Http/Controllers/BookController.php`',
                '`src/Http/Controllers/BookController.php`',
                '`src/App/Http/Controllers/BookController.php`',
                '`src/BookController.php`',
            ],
            'correct_option' => 1,
            'explanation' => 'The prefix `App\` is replaced by the base directory `src/`, and the rest of the name becomes the path.',
        ],
        [
            'question' => 'What does the leading backslash in `\Shop\Book` mean?',
            'options' => [
                'It escapes the next character',
                'The name starts from the top-level namespace, whatever namespace the code is in',
                'The class is private',
                'It\'s a typo; PHP ignores it',
            ],
            'correct_option' => 1,
            'explanation' => 'Like an absolute path, it doesn\'t depend on the current namespace.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Into the catalogue',
            'prompt' => 'Put the `Book` class into the namespace `Shop\\Catalogue`, so its full name is `Shop\\Catalogue\\Book`. Keep its constructor and `describe()` method.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return "{$this->title} by {$this->author}";
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                namespace Shop\Catalogue;

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return "{$this->title} by {$this->author}";
                    }
                }
                PHP,
            'hint' => 'Add `namespace Shop\Catalogue;` straight after `<?php`.',
            'checks' => [
                ['label' => 'Shop\Catalogue\Book exists', 'code' => "return class_exists('Shop\\\\Catalogue\\\\Book', false) ?: 'There is no class Shop\\\\Catalogue\\\\Book.';"],
                ['label' => 'There is no Book left in the global namespace', 'code' => "return ! class_exists('Book', false) ?: 'Book is still in the global namespace.';"],
                ['label' => 'It still works', 'code' => "return expect_same((new \\Shop\\Catalogue\\Book('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert');"],
            ],
        ],
        [
            'title' => 'Two orders, no clash',
            'prompt' => "The shop and the warehouse both need a class called `Order`. In one file, write `Shop\\Order` and `Warehouse\\Order`, each with a method `source(): string` returning `'shop'` and `'warehouse'` respectively. Use the braced namespace form.",
            'starter_code' => <<<'PHP'
                <?php

                class Order
                {
                    public function source(): string { return 'shop'; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                namespace Shop {
                    class Order
                    {
                        public function source(): string { return 'shop'; }
                    }
                }

                namespace Warehouse {
                    class Order
                    {
                        public function source(): string { return 'warehouse'; }
                    }
                }
                PHP,
            'hint' => 'Wrap each class in its own `namespace Shop { … }` and `namespace Warehouse { … }` block.',
            'checks' => [
                ['label' => 'Shop\Order comes from the shop', 'code' => "if (! class_exists('Shop\\\\Order', false)) return 'There is no class Shop\\\\Order.';\nreturn expect_same((new \\Shop\\Order())->source(), 'shop');"],
                ['label' => 'Warehouse\Order comes from the warehouse', 'code' => "if (! class_exists('Warehouse\\\\Order', false)) return 'There is no class Warehouse\\\\Order.';\nreturn expect_same((new \\Warehouse\\Order())->source(), 'warehouse');"],
            ],
        ],
        [
            'title' => 'The missing backslash',
            'prompt' => "`Shop\\Isbn` should throw PHP's built-in `InvalidArgumentException` for an ISBN that isn't 13 digits, but instead it stops with \"Class not found\". Fix it, keeping the class in the `Shop` namespace.",
            'starter_code' => <<<'PHP'
                <?php

                namespace Shop;

                class Isbn
                {
                    public function __construct(public string $value)
                    {
                        if (! preg_match('/^\d{13}$/', $value)) {
                            throw new InvalidArgumentException("'$value' is not a 13-digit ISBN.");
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                namespace Shop;

                use InvalidArgumentException;

                class Isbn
                {
                    public function __construct(public string $value)
                    {
                        if (! preg_match('/^\d{13}$/', $value)) {
                            throw new InvalidArgumentException("'$value' is not a 13-digit ISBN.");
                        }
                    }
                }
                PHP,
            'hint' => 'Inside `namespace Shop`, `InvalidArgumentException` means `Shop\InvalidArgumentException`. Add `use InvalidArgumentException;`, or write `\InvalidArgumentException`.',
            'checks' => [
                ['label' => 'A bad ISBN throws PHP\'s InvalidArgumentException', 'code' => "return expect_throws(\\InvalidArgumentException::class, fn () => new \\Shop\\Isbn('123'));"],
                ['label' => 'A good ISBN is accepted', 'code' => "return expect_same((new \\Shop\\Isbn('9780441013593'))->value, '9780441013593');"],
            ],
        ],
        [
            'title' => 'Where does the class live?',
            'prompt' => "Write a function `classToPath(string \$class, string \$prefix, string \$baseDir): ?string` that applies a PSR-4 rule: for a class whose name starts with `\$prefix`, return the base directory followed by the rest of the name, with `\\` turned into `/` and `.php` added. For any other class, return `null`.\n\n`classToPath('App\\Models\\Book', 'App\\\\', 'src/')` gives `'src/Models/Book.php'`.",
            'starter_code' => <<<'PHP'
                <?php

                function classToPath(string $class, string $prefix, string $baseDir): ?string
                {
                    return $baseDir . $class . '.php';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function classToPath(string $class, string $prefix, string $baseDir): ?string
                {
                    if (! str_starts_with($class, $prefix)) {
                        return null;
                    }

                    $relative = substr($class, strlen($prefix));

                    return $baseDir . str_replace('\\', '/', $relative) . '.php';
                }
                PHP,
            'hint' => 'Check `str_starts_with($class, $prefix)`, cut the prefix off with `substr()`, then `str_replace(\'\\\\\', \'/\', …)` and add `.php`.',
            'checks' => [
                ['label' => 'App\\Models\\Book is in src/Models/Book.php', 'code' => "return expect_same(classToPath('App\\\\Models\\\\Book', 'App\\\\', 'src/'), 'src/Models/Book.php');"],
                ['label' => 'Deeper names become deeper folders', 'code' => "return expect_same(classToPath('Shop\\\\Http\\\\Controllers\\\\BookController', 'Shop\\\\', 'app/'), 'app/Http/Controllers/BookController.php');"],
                ['label' => 'A class outside the prefix gives null', 'code' => "return expect_same(classToPath('Vendor\\\\Pdf\\\\Book', 'App\\\\', 'src/'), null);"],
            ],
        ],
        [
            'title' => 'Which class is in this file?',
            'prompt' => "Write the opposite function, `pathToClass(string \$path, string \$prefix, string \$baseDir): ?string`: for a `.php` file inside `\$baseDir`, return the class name it should hold; for any other path, return `null`.\n\n`pathToClass('src/Models/Book.php', 'App\\\\', 'src/')` gives `'App\\Models\\Book'`.",
            'starter_code' => <<<'PHP'
                <?php

                function pathToClass(string $path, string $prefix, string $baseDir): ?string
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pathToClass(string $path, string $prefix, string $baseDir): ?string
                {
                    if (! str_starts_with($path, $baseDir) || ! str_ends_with($path, '.php')) {
                        return null;
                    }

                    $relative = substr($path, strlen($baseDir), -strlen('.php'));

                    return $prefix . str_replace('/', '\\', $relative);
                }
                PHP,
            'hint' => 'Check the path starts with `$baseDir` and ends with `.php`. Cut both off with `substr($path, strlen($baseDir), -4)`, turn `/` into `\\\\`, and put the prefix in front.',
            'checks' => [
                ['label' => 'src/Models/Book.php holds App\\Models\\Book', 'code' => "return expect_same(pathToClass('src/Models/Book.php', 'App\\\\', 'src/'), 'App\\\\Models\\\\Book');"],
                ['label' => 'A file directly in the base directory works too', 'code' => "return expect_same(pathToClass('src/Kernel.php', 'App\\\\', 'src/'), 'App\\\\Kernel');"],
                ['label' => 'Files outside the base directory, or not .php, give null', 'code' => "return expect_same([pathToClass('tests/BookTest.php', 'App\\\\', 'src/'), pathToClass('src/notes.txt', 'App\\\\', 'src/')], [null, null]);"],
            ],
        ],
        [
            'title' => 'Split a class name',
            'prompt' => "Write a function `splitClassName(string \$class): array` that splits a fully qualified class name into `['namespace' => …, 'class' => …]`. A leading backslash is ignored, and a class in the global namespace has `''` as its namespace.\n\n`splitClassName('\\App\\Models\\Book')` gives `['namespace' => 'App\\Models', 'class' => 'Book']`.",
            'starter_code' => <<<'PHP'
                <?php

                function splitClassName(string $class): array
                {
                    return ['namespace' => '', 'class' => $class];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function splitClassName(string $class): array
                {
                    $class = ltrim($class, '\\');
                    $position = strrpos($class, '\\');

                    if ($position === false) {
                        return ['namespace' => '', 'class' => $class];
                    }

                    return ['namespace' => substr($class, 0, $position), 'class' => substr($class, $position + 1)];
                }
                PHP,
            'hint' => '`ltrim($class, \'\\\\\')` removes a leading backslash. `strrpos()` finds the last backslash; everything before it is the namespace.',
            'checks' => [
                ['label' => 'It splits App\\Models\\Book', 'code' => "return expect_same(splitClassName('App\\\\Models\\\\Book'), ['namespace' => 'App\\\\Models', 'class' => 'Book']);"],
                ['label' => 'A leading backslash is ignored', 'code' => "return expect_same(splitClassName('\\\\Shop\\\\Book'), ['namespace' => 'Shop', 'class' => 'Book']);"],
                ['label' => 'A global class has an empty namespace', 'code' => "return expect_same(splitClassName('\\\\Exception'), ['namespace' => '', 'class' => 'Exception']);"],
            ],
        ],
    ],
];
