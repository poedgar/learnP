<?php

// Lesson 4 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'late-static-binding-and-fluent-interfaces',
    'title' => 'Late static binding and fluent interfaces',
    'summary' => 'Write parent code that adapts to each child with static, new static and static::, and build chainable APIs that change an object or return changed copies.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try new self instead of new static.
        class Model
        {
            public function __construct(public array $attributes = []) {}

            public static function make(array $attributes): static
            {
                return new static($attributes);
            }
        }

        class Book extends Model {}

        $book = Book::make(['title' => 'Dune']);
        echo get_class($book) . ': ' . $book->attributes['title'];
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Shape
                {
                    public static function create(): static
                    {
                        return new static();
                    }

                    public static function createSelf(): self
                    {
                        return new self();
                    }
                }

                class Circle extends Shape {}

                echo get_class(Circle::create()) . ' ' . get_class(Circle::createSelf());
                PHP,
            'options' => ['`Circle Circle`', '`Shape Shape`', '`Circle Shape`', '`Shape Circle`'],
            'correct_option' => 2,
            'explanation' => '`static` is the class the method was called on, `Circle`. `self` is the class it was written in, `Shape`.',
        ],
        [
            'question' => 'What is "late" about late static binding?',
            'options' => [
                'It runs after every other method',
                'Which class `static` means is decided when the code runs, not when it\'s written',
                'It only works in PHP 8.4 and later',
                'It waits until the object is destroyed',
            ],
            'correct_option' => 1,
            'explanation' => '`self` is fixed in the source code. `static` is worked out at the moment of each call.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Model
                {
                    const TABLE = 'models';

                    public static function tables(): string
                    {
                        return self::TABLE . ' ' . static::TABLE;
                    }
                }

                class Book extends Model
                {
                    const TABLE = 'books';
                }

                echo Book::tables();
                PHP,
            'options' => ['`books books`', '`models models`', '`models books`', '`books models`'],
            'correct_option' => 2,
            'explanation' => '`self::TABLE` is `Model`\'s constant; `static::TABLE` follows the call to `Book`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Model
                {
                    public static int $count = 0;

                    public static function add(): void
                    {
                        static::$count++;
                    }
                }

                class Book extends Model {}
                class Author extends Model {}

                Book::add();
                Author::add();
                Author::add();

                echo Book::$count;
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`0`'],
            'correct_option' => 2,
            'explanation' => 'Neither child declares its own `$count`, so all three calls change the one property declared in `Model`.',
        ],
        [
            'question' => 'A method in `Model` is called as `Book::describe()`. What is `static::class` inside it?',
            'options' => ['`\'Model\'`', '`\'Book\'`', '`\'static\'`', 'An error: `static` can\'t be used in static methods'],
            'correct_option' => 1,
            'explanation' => '`static::class` is the name of the class the call was made on.',
        ],
        [
            'question' => 'What makes `$message->to(\'amara@example.com\')->subject(\'Hi\')` possible?',
            'options' => [
                'The methods are static',
                '`to()` returns an object with a `subject()` method, such as `$this`',
                'PHP passes `$message` along automatically',
                'Both methods use `__call()`',
            ],
            'correct_option' => 1,
            'explanation' => 'Each call works on whatever the previous one returned. Returning `$this` keeps the chain on the same object.',
        ],
        [
            'question' => 'An unchangeable `Search` has a fluent method `with(string $term): static`. What should it do?',
            'options' => [
                'Add the term to `$this` and return `$this`',
                'Clone `$this`, add the term to the copy, and return the copy',
                'Return a brand new, empty `Search`',
                'Throw, because unchangeable objects can\'t have fluent methods',
            ],
            'correct_option' => 1,
            'explanation' => 'The original stays as it was, and each step hands back a new object with the change.',
        ],
        [
            'question' => 'Why do fluent methods usually declare the return type `static` rather than `self`?',
            'options' => [
                '`self` isn\'t allowed as a return type',
                'So a child class\'s own methods can still be called later in the chain',
                '`static` makes the method faster',
                'There is no difference between them',
            ],
            'correct_option' => 1,
            'explanation' => 'With `static`, a chain started on a `BookQuery` keeps returning a `BookQuery`, so `BookQuery`\'s extra methods stay available.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Create the right kind',
            'prompt' => "Give `Model` a static method `create(array \$attributes): static` that returns a new object **of the class it was called on**, holding those attributes. `Book::create(['title' => 'Dune'])` must give a `Book`, and `Author::create(…)` an `Author`.",
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    public function __construct(public array $attributes = []) {}

                    public static function create(array $attributes): self
                    {
                        return new self($attributes);
                    }
                }

                class Book extends Model {}
                class Author extends Model {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    public function __construct(public array $attributes = []) {}

                    public static function create(array $attributes): static
                    {
                        return new static($attributes);
                    }
                }

                class Book extends Model {}
                class Author extends Model {}
                PHP,
            'hint' => 'Change `new self` to `new static`, and the return type to `static`.',
            'checks' => [
                ['label' => 'Book::create() gives a Book', 'code' => "return expect_same(get_class(Book::create(['title' => 'Dune'])), 'Book');"],
                ['label' => 'Author::create() gives an Author', 'code' => "return expect_same(get_class(Author::create(['name' => 'Toni Morrison'])), 'Author');"],
                ['label' => 'The attributes are kept', 'code' => "return expect_same(Book::create(['title' => 'Dune'])->attributes, ['title' => 'Dune']);"],
            ],
        ],
        [
            'title' => 'Each model its own table',
            'prompt' => '`Model::selectAll()` should read from the table named in the `TABLE` constant **of the class it was called on**: `Book::selectAll()` gives `SELECT * FROM books` and `Author::selectAll()` gives `SELECT * FROM authors`. Fix it.',
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    const TABLE = 'models';

                    public static function selectAll(): string
                    {
                        return 'SELECT * FROM ' . self::TABLE;
                    }
                }

                class Book extends Model
                {
                    const TABLE = 'books';
                }

                class Author extends Model
                {
                    const TABLE = 'authors';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    const TABLE = 'models';

                    public static function selectAll(): string
                    {
                        return 'SELECT * FROM ' . static::TABLE;
                    }
                }

                class Book extends Model
                {
                    const TABLE = 'books';
                }

                class Author extends Model
                {
                    const TABLE = 'authors';
                }
                PHP,
            'hint' => '`self::TABLE` is always `Model`\'s. One word changes it to follow the call.',
            'checks' => [
                ['label' => 'Book::selectAll() reads from books', 'code' => "return expect_same(Book::selectAll(), 'SELECT * FROM books');"],
                ['label' => 'Author::selectAll() reads from authors', 'code' => "return expect_same(Author::selectAll(), 'SELECT * FROM authors');"],
                ['label' => 'Model::selectAll() still reads from models', 'code' => "return expect_same(Model::selectAll(), 'SELECT * FROM models');"],
            ],
        ],
        [
            'title' => 'Counting each kind',
            'prompt' => "Every `Model` counts itself when it's created, but all kinds share one counter. Change it so each class keeps its **own** count: after two books and one author, `Book::count()` is 2 and `Author::count()` is 1. Use one private static array in `Model`, keyed by `static::class`.",
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    private static int $created = 0;

                    public function __construct()
                    {
                        self::$created++;
                    }

                    public static function count(): int
                    {
                        return self::$created;
                    }
                }

                class Book extends Model {}
                class Author extends Model {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    private static array $created = [];

                    public function __construct()
                    {
                        self::$created[static::class] = (self::$created[static::class] ?? 0) + 1;
                    }

                    public static function count(): int
                    {
                        return self::$created[static::class] ?? 0;
                    }
                }

                class Book extends Model {}
                class Author extends Model {}
                PHP,
            'hint' => 'Make `$created` an array. In the constructor add one to `self::$created[static::class]`; `count()` returns that entry, or 0.',
            'checks' => [
                ['label' => 'Each class counts its own objects', 'code' => <<<'PHP'
                    new Book();
                    new Book();
                    new Author();
                    return expect_same([Book::count(), Author::count()], [2, 1]);
                    PHP],
                ['label' => 'A class with no objects counts 0', 'code' => <<<'PHP'
                    if (! class_exists('Magazine', false)) {
                        eval('class Magazine extends Model {}');
                    }
                    return expect_same(Magazine::count(), 0);
                    PHP],
            ],
        ],
        [
            'title' => 'A fluent email',
            'prompt' => "Finish `Email` so its setters can be chained: `to()`, `subject()` and `body()` each store their value and return the same object. `build()` then gives:\n\n```\nTo: amara@example.com\nSubject: Your order\n\nIt has shipped.\n```\n\n(with `\\n` between lines and an empty line before the body).",
            'starter_code' => <<<'PHP'
                <?php

                class Email
                {
                    private string $to = '';
                    private string $subject = '';
                    private string $body = '';

                    public function to(string $to): void
                    {
                        $this->to = $to;
                    }

                    public function subject(string $subject): void
                    {
                        $this->subject = $subject;
                    }

                    public function body(string $body): void
                    {
                        $this->body = $body;
                    }

                    public function build(): string
                    {
                        return '';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Email
                {
                    private string $to = '';
                    private string $subject = '';
                    private string $body = '';

                    public function to(string $to): static
                    {
                        $this->to = $to;
                        return $this;
                    }

                    public function subject(string $subject): static
                    {
                        $this->subject = $subject;
                        return $this;
                    }

                    public function body(string $body): static
                    {
                        $this->body = $body;
                        return $this;
                    }

                    public function build(): string
                    {
                        return "To: {$this->to}\nSubject: {$this->subject}\n\n{$this->body}";
                    }
                }
                PHP,
            'hint' => 'Each setter ends with `return $this;` and declares `: static`. `build()` joins the parts with `"\n"`, and `"\n\n"` before the body.',
            'checks' => [
                ['label' => 'The setters return the same object', 'code' => <<<'PHP'
                    $email = new Email();
                    return ($email->to('a@example.com') === $email && $email->subject('x') === $email && $email->body('y') === $email) ?: 'Each setter should return $this.';
                    PHP],
                ['label' => 'build() lays out the email', 'code' => <<<'PHP'
                    $text = (new Email())->to('amara@example.com')->subject('Your order')->body('It has shipped.')->build();
                    return expect_same($text, "To: amara@example.com\nSubject: Your order\n\nIt has shipped.");
                    PHP],
            ],
        ],
        [
            'title' => 'Filters that don\'t interfere',
            'prompt' => '`Filter::with(string $condition)` changes the filter itself, so building two filters from one base mixes them up. Make it **unchangeable**: `with()` returns a new `Filter` with the extra condition, and leaves the original as it was. `toSql()` joins the conditions with ` AND `.',
            'starter_code' => <<<'PHP'
                <?php

                class Filter
                {
                    private array $conditions = [];

                    public function with(string $condition): static
                    {
                        $this->conditions[] = $condition;
                        return $this;
                    }

                    public function toSql(): string
                    {
                        return implode(' AND ', $this->conditions);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Filter
                {
                    private array $conditions = [];

                    public function with(string $condition): static
                    {
                        $copy = clone $this;
                        $copy->conditions[] = $condition;
                        return $copy;
                    }

                    public function toSql(): string
                    {
                        return implode(' AND ', $this->conditions);
                    }
                }
                PHP,
            'hint' => 'Clone `$this`, add the condition to the clone, and return the clone.',
            'checks' => [
                ['label' => 'with() returns a new object', 'code' => <<<'PHP'
                    $filter = new Filter();
                    return ($filter->with('price < 10') !== $filter) ?: 'with() should return a new Filter, not the same one.';
                    PHP],
                ['label' => 'Two filters built from one base stay separate', 'code' => <<<'PHP'
                    $base = (new Filter())->with('in_stock = 1');
                    $cheap = $base->with('price < 10');
                    $horror = $base->with("genre = 'Horror'");
                    return expect_same([$base->toSql(), $cheap->toSql(), $horror->toSql()], ['in_stock = 1', 'in_stock = 1 AND price < 10', "in_stock = 1 AND genre = 'Horror'"]);
                    PHP],
            ],
        ],
        [
            'title' => 'Keep the chain going',
            'prompt' => "`BookQuery` adds `inStock()` to `Query`, but `BookQuery::on('books')->where('price < 10')->inStock()` fails, because `Query` creates and returns plain `Query` objects. Fix `Query` so every step returns an object of the class the chain started with, and the whole chain gives `SELECT * FROM books WHERE price < 10 AND in_stock = 1`.",
            'starter_code' => <<<'PHP'
                <?php

                class Query
                {
                    protected array $conditions = [];

                    public function __construct(protected string $table) {}

                    public static function on(string $table): self
                    {
                        return new self($table);
                    }

                    public function where(string $condition): self
                    {
                        $copy = new self($this->table);
                        $copy->conditions = [...$this->conditions, $condition];
                        return $copy;
                    }

                    public function toSql(): string
                    {
                        return "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
                    }
                }

                class BookQuery extends Query
                {
                    public function inStock(): static
                    {
                        return $this->where('in_stock = 1');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Query
                {
                    protected array $conditions = [];

                    public function __construct(protected string $table) {}

                    public static function on(string $table): static
                    {
                        return new static($table);
                    }

                    public function where(string $condition): static
                    {
                        $copy = clone $this;
                        $copy->conditions[] = $condition;
                        return $copy;
                    }

                    public function toSql(): string
                    {
                        return "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $this->conditions);
                    }
                }

                class BookQuery extends Query
                {
                    public function inStock(): static
                    {
                        return $this->where('in_stock = 1');
                    }
                }
                PHP,
            'hint' => 'Use `new static` in `on()`, and in `where()` either `new static($this->table)` or `clone $this`. Change the return types to `static`.',
            'checks' => [
                ['label' => 'BookQuery::on() gives a BookQuery', 'code' => "return expect_same(get_class(BookQuery::on('books')), 'BookQuery');"],
                ['label' => 'where() keeps the class', 'code' => "return expect_same(get_class(BookQuery::on('books')->where('price < 10')), 'BookQuery');"],
                ['label' => 'The whole chain works', 'code' => "return expect_same(BookQuery::on('books')->where('price < 10')->inStock()->toSql(), 'SELECT * FROM books WHERE price < 10 AND in_stock = 1');"],
                ['label' => 'Steps don\'t change the query they started from', 'code' => <<<'PHP'
                    $base = BookQuery::on('books')->where('price < 10');
                    $base->inStock();
                    return expect_same($base->toSql(), 'SELECT * FROM books WHERE price < 10');
                    PHP],
            ],
        ],
    ],
];
