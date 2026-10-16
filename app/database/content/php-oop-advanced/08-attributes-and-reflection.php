<?php

// Lesson 8 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'attributes-and-reflection',
    'title' => 'Attributes and reflection',
    'summary' => 'Inspect classes, properties and constructors while code runs with reflection, attach notes to code with your own attributes, and read them to build validators and routers.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        #[Attribute]
        class Route
        {
            public function __construct(public string $path) {}
        }

        class BookController
        {
            #[Route('/books')]
            public function list(): string { return 'All books'; }

            #[Route('/books/new')]
            public function create(): string { return 'New book form'; }
        }

        foreach ((new ReflectionClass(BookController::class))->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class) as $attribute) {
                echo $attribute->newInstance()->path . ' → ' . $method->getName() . "()\n";
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What is reflection?',
            'options' => [
                'Copying an object',
                'Code inspecting classes, methods and properties while the program runs',
                'A way to run code in reverse',
                'An interface for comparing objects',
            ],
            'correct_option' => 1,
            'explanation' => 'Reflection answers questions such as "which methods does this class have?" and "what does its constructor need?" at run time.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public string $title = 'Dune';
                    protected float $price = 9.99;
                    private int $stock = 3;
                }

                echo count((new ReflectionClass(Book::class))->getProperties());
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`0`'],
            'correct_option' => 2,
            'explanation' => '`getProperties()` lists every property, whatever its visibility. Pass a filter such as `ReflectionProperty::IS_PUBLIC` to limit it.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Safe
                {
                    private string $code = '1234';
                }

                echo (new ReflectionProperty(Safe::class, 'code'))->getValue(new Safe());
                PHP,
            'options' => ['`1234`', 'An error: the property is private', '`null`', '`code`'],
            'correct_option' => 0,
            'explanation' => 'Reflection can read private values. That\'s exactly why it should be kept for tools, not everyday code.',
        ],
        [
            'question' => 'What does writing `#[Route(\'/books\')]` above a method do on its own?',
            'options' => [
                'Registers the route with PHP\'s built-in router',
                'Calls `new Route(\'/books\')` when the class loads',
                'Nothing, until some code reads the attribute with reflection',
                'Makes the method public',
            ],
            'correct_option' => 2,
            'explanation' => 'An attribute is just a note. A framework (or your own code) has to look for it with `getAttributes()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                #[Attribute]
                class Tag
                {
                    public function __construct(public string $name) {}
                }

                #[Tag('classic')]
                class Book {}

                $attribute = (new ReflectionClass(Book::class))->getAttributes()[0];
                echo $attribute->getName() . ' ' . $attribute->getArguments()[0] . ' ' . $attribute->newInstance()->name;
                PHP,
            'options' => ['`Tag classic classic`', '`Book classic classic`', '`Tag Tag classic`', 'An error'],
            'correct_option' => 0,
            'explanation' => '`getName()` is the attribute\'s class, `getArguments()` the values written in brackets, and `newInstance()` creates a `Tag` from them.',
        ],
        [
            'question' => 'What must the class used as an attribute have, for `newInstance()` to work?',
            'options' => [
                'A method called `handle()`',
                'The `#[Attribute]` attribute on the class itself',
                'No constructor',
                'A name ending in `Attribute`',
            ],
            'correct_option' => 1,
            'explanation' => 'Without `#[Attribute]` on its class, `newInstance()` stops with an error saying it isn\'t an attribute class.',
        ],
        [
            'question' => 'An attribute is declared with `#[Attribute(Attribute::TARGET_METHOD)]`, but someone writes it above a class. When does PHP complain?',
            'options' => [
                'When the file is loaded',
                'When the class is first used',
                'When `newInstance()` is called on that attribute',
                'Never',
            ],
            'correct_option' => 2,
            'explanation' => 'Attributes are only checked when they\'re turned into objects, so a misplaced one goes unnoticed until something reads it.',
        ],
        [
            'question' => 'Which is a good reason **not** to use reflection in everyday application code?',
            'options' => [
                'It only works on classes in the same file',
                'It ignores `private`, is slower, and hides what\'s happening from readers and editors',
                'It can\'t read method names',
                'It\'s deprecated',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a great tool for frameworks and tools. In ordinary code, an interface or a plain method call is clearer.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Describe a class',
            'prompt' => "Write a function `describeClass(string \$class): array` returning an array with:\n\n- `'methods'`: the names of the class's **public** methods, sorted A to Z;\n- `'properties'`: the names of its **public** properties, sorted A to Z.\n\nFor `Book` below, that's `['methods' => ['__construct', 'describe'], 'properties' => ['title']]`.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, private float $price) {}

                    public function describe(): string { return $this->title; }

                    private function secret(): void {}
                }

                function describeClass(string $class): array
                {
                    return ['methods' => [], 'properties' => []];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, private float $price) {}

                    public function describe(): string { return $this->title; }

                    private function secret(): void {}
                }

                function describeClass(string $class): array
                {
                    $reflection = new ReflectionClass($class);

                    $methods = array_map(fn (ReflectionMethod $method) => $method->getName(), $reflection->getMethods(ReflectionMethod::IS_PUBLIC));
                    $properties = array_map(fn (ReflectionProperty $property) => $property->getName(), $reflection->getProperties(ReflectionProperty::IS_PUBLIC));
                    sort($methods);
                    sort($properties);

                    return ['methods' => $methods, 'properties' => $properties];
                }
                PHP,
            'hint' => '`getMethods(ReflectionMethod::IS_PUBLIC)` and `getProperties(ReflectionProperty::IS_PUBLIC)` filter by visibility. Map each to `getName()`, then `sort()`.',
            'checks' => [
                ['label' => 'It describes Book', 'code' => "return expect_same(describeClass('Book'), ['methods' => ['__construct', 'describe'], 'properties' => ['title']]);"],
                ['label' => 'It works for any class, sorted', 'code' => <<<'PHP'
                    $class = get_class(new class {
                        public int $zeta = 1;
                        public int $alpha = 2;
                        protected int $hidden = 3;
                        public function walk(): void {}
                        public function bark(): void {}
                    });
                    return expect_same(describeClass($class), ['methods' => ['bark', 'walk'], 'properties' => ['alpha', 'zeta']]);
                    PHP],
            ],
        ],
        [
            'title' => 'What the constructor needs',
            'prompt' => "Write a function `constructorNeeds(string \$class): array` that returns the constructor's parameters as name => type (as a string): for `Book` below, `['title' => 'string', 'price' => 'float']`. A class without a constructor gives `[]`.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price = 9.99) {}
                }

                function constructorNeeds(string $class): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price = 9.99) {}
                }

                function constructorNeeds(string $class): array
                {
                    $constructor = (new ReflectionClass($class))->getConstructor();
                    if ($constructor === null) {
                        return [];
                    }

                    $needs = [];
                    foreach ($constructor->getParameters() as $parameter) {
                        $needs[$parameter->getName()] = (string) $parameter->getType();
                    }

                    return $needs;
                }
                PHP,
            'hint' => '`getConstructor()` returns `null` when there\'s none. Otherwise loop over `getParameters()`, using `getName()` and `(string) $parameter->getType()`.',
            'checks' => [
                ['label' => 'It lists Book\'s constructor parameters', 'code' => "return expect_same(constructorNeeds('Book'), ['title' => 'string', 'price' => 'float']);"],
                ['label' => 'A class without a constructor gives []', 'code' => 'return expect_same(constructorNeeds(get_class(new class {})), []);'],
                ['label' => 'Class types are named too', 'code' => <<<'PHP'
                    $class = get_class(new class (new ArrayObject()) {
                        public function __construct(public ArrayObject $items) {}
                    });
                    return expect_same(constructorNeeds($class), ['items' => 'ArrayObject']);
                    PHP],
            ],
        ],
        [
            'title' => 'Build it from an array',
            'prompt' => 'Write a function `hydrate(string $class, array $data): object` that creates an object of `$class`, passing each constructor parameter the value in `$data` with the same name. A parameter missing from `$data` uses its default value, or, if it has none, makes `hydrate()` throw an `InvalidArgumentException`. Extra keys in `$data` are ignored.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price = 9.99) {}
                }

                function hydrate(string $class, array $data): object
                {
                    return new $class(...$data);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price = 9.99) {}
                }

                function hydrate(string $class, array $data): object
                {
                    $reflection = new ReflectionClass($class);
                    $arguments = [];

                    foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
                        $name = $parameter->getName();
                        if (array_key_exists($name, $data)) {
                            $arguments[] = $data[$name];
                        } elseif ($parameter->isDefaultValueAvailable()) {
                            $arguments[] = $parameter->getDefaultValue();
                        } else {
                            throw new InvalidArgumentException("Missing a value for $name.");
                        }
                    }

                    return $reflection->newInstanceArgs($arguments);
                }
                PHP,
            'hint' => 'Loop over the constructor\'s parameters, building a list of arguments: the value from `$data`, else `getDefaultValue()` when `isDefaultValueAvailable()`, else throw. Then `newInstanceArgs($arguments)`.',
            'checks' => [
                ['label' => 'Values are matched by name', 'code' => <<<'PHP'
                    $book = hydrate('Book', ['price' => 19.99, 'title' => 'Dune']);
                    return expect_same([$book->title, $book->price], ['Dune', 19.99]);
                    PHP],
                ['label' => 'Missing values use the default, and extra keys are ignored', 'code' => <<<'PHP'
                    $book = hydrate('Book', ['title' => 'Emma', 'colour' => 'blue']);
                    return expect_same([$book->title, $book->price], ['Emma', 9.99]);
                    PHP],
                ['label' => 'A missing required value throws an InvalidArgumentException', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => hydrate('Book', ['price' => 5.0]));"],
            ],
        ],
        [
            'title' => 'Labels for a form',
            'prompt' => "Write an attribute class `Label`, usable only on properties, holding a public string `\$text`. Put `#[Label('Book title')]` on `Book`'s `\$title` and `#[Label('Price in pounds')]` on its `\$price`. Then write a function `labels(string \$class): array` giving each property's name => its label's text, using the property name itself when it has no `Label`.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public float $price,
                        public int $stock = 0,
                    ) {}
                }

                function labels(string $class): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                #[Attribute(Attribute::TARGET_PROPERTY)]
                class Label
                {
                    public function __construct(public string $text) {}
                }

                class Book
                {
                    public function __construct(
                        #[Label('Book title')] public string $title,
                        #[Label('Price in pounds')] public float $price,
                        public int $stock = 0,
                    ) {}
                }

                function labels(string $class): array
                {
                    $labels = [];
                    foreach ((new ReflectionClass($class))->getProperties() as $property) {
                        $attributes = $property->getAttributes(Label::class);
                        $labels[$property->getName()] = $attributes ? $attributes[0]->newInstance()->text : $property->getName();
                    }

                    return $labels;
                }
                PHP,
            'hint' => 'Mark `Label` with `#[Attribute(Attribute::TARGET_PROPERTY)]`. On a promoted property, the attribute goes before `public`. In `labels()`, `getAttributes(Label::class)` is empty when there\'s no label.',
            'checks' => [
                ['label' => 'Label is an attribute for properties only', 'code' => <<<'PHP'
                    if (! class_exists('Label', false)) return 'There is no class named Label.';
                    $attributes = (new ReflectionClass('Label'))->getAttributes(Attribute::class);
                    if (! $attributes) return 'Mark Label with #[Attribute].';
                    return expect_same($attributes[0]->newInstance()->flags, Attribute::TARGET_PROPERTY);
                    PHP],
                ['label' => 'labels(\'Book\') gives each property\'s label', 'code' => "return expect_same(labels('Book'), ['title' => 'Book title', 'price' => 'Price in pounds', 'stock' => 'stock']);"],
            ],
        ],
        [
            'title' => 'Required fields',
            'prompt' => "Write an attribute `Required` (for properties). Write a function `missing(object \$object): array` returning the names of the object's properties marked `#[Required]` whose value is `null` or an empty string, in the order they're declared. Mark `Customer`'s `\$name` and `\$email` as required.",
            'starter_code' => <<<'PHP'
                <?php

                class Customer
                {
                    public function __construct(
                        public ?string $name,
                        public ?string $email,
                        public ?string $city = null,
                    ) {}
                }

                function missing(object $object): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                #[Attribute(Attribute::TARGET_PROPERTY)]
                class Required {}

                class Customer
                {
                    public function __construct(
                        #[Required] public ?string $name,
                        #[Required] public ?string $email,
                        public ?string $city = null,
                    ) {}
                }

                function missing(object $object): array
                {
                    $missing = [];
                    foreach ((new ReflectionObject($object))->getProperties() as $property) {
                        if ($property->getAttributes(Required::class)) {
                            $value = $property->getValue($object);
                            if ($value === null || $value === '') {
                                $missing[] = $property->getName();
                            }
                        }
                    }

                    return $missing;
                }
                PHP,
            'hint' => 'An attribute can be an empty class: `#[Attribute(Attribute::TARGET_PROPERTY)] class Required {}`. In `missing()`, check `getAttributes(Required::class)`, then the value with `getValue($object)`.',
            'checks' => [
                ['label' => 'Required is an attribute', 'code' => "return (class_exists('Required', false) && (new ReflectionClass('Required'))->getAttributes(Attribute::class)) ?: 'Write a class Required marked with #[Attribute].';"],
                ['label' => 'Empty required fields are listed in order', 'code' => "return expect_same(missing(new Customer(null, '')), ['name', 'email']);"],
                ['label' => 'Filled required fields and optional fields aren\'t', 'code' => "return expect_same(missing(new Customer('Amara', null, null)), ['email']);"],
                ['label' => 'A complete customer misses nothing', 'code' => "return expect_same(missing(new Customer('Amara', 'amara@example.com')), []);"],
            ],
        ],
        [
            'title' => 'Routes from attributes',
            'prompt' => "Write an attribute `Get`, usable on methods, holding a public string `\$path`. Mark `BookController`'s methods with it: `list()` with `/books` and `show()` with `/books/{id}`. Then write a function `routes(string \$controller): array` returning path => method name for every method of the class that has a `#[Get]` attribute.",
            'starter_code' => <<<'PHP'
                <?php

                class BookController
                {
                    public function list(): string { return 'All books'; }

                    public function show(): string { return 'One book'; }

                    public function helper(): void {}
                }

                function routes(string $controller): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                #[Attribute(Attribute::TARGET_METHOD)]
                class Get
                {
                    public function __construct(public string $path) {}
                }

                class BookController
                {
                    #[Get('/books')]
                    public function list(): string { return 'All books'; }

                    #[Get('/books/{id}')]
                    public function show(): string { return 'One book'; }

                    public function helper(): void {}
                }

                function routes(string $controller): array
                {
                    $routes = [];
                    foreach ((new ReflectionClass($controller))->getMethods() as $method) {
                        foreach ($method->getAttributes(Get::class) as $attribute) {
                            $routes[$attribute->newInstance()->path] = $method->getName();
                        }
                    }

                    return $routes;
                }
                PHP,
            'hint' => 'Mark `Get` with `#[Attribute(Attribute::TARGET_METHOD)]`. Loop over `getMethods()`, and for each `getAttributes(Get::class)` add `$route->path => $method->getName()`.',
            'checks' => [
                ['label' => 'Get is an attribute for methods', 'code' => <<<'PHP'
                    if (! class_exists('Get', false)) return 'There is no class named Get.';
                    $attributes = (new ReflectionClass('Get'))->getAttributes(Attribute::class);
                    if (! $attributes) return 'Mark Get with #[Attribute].';
                    return expect_same($attributes[0]->newInstance()->flags, Attribute::TARGET_METHOD);
                    PHP],
                ['label' => 'routes() maps each path to its method', 'code' => "return expect_same(routes('BookController'), ['/books' => 'list', '/books/{id}' => 'show']);"],
                ['label' => 'It works for any controller', 'code' => <<<'PHP'
                    $class = get_class(new class {
                        #[Get('/about')]
                        public function about(): string { return 'About us'; }
                    });
                    return expect_same(routes($class), ['/about' => 'about']);
                    PHP],
            ],
        ],
    ],
];
