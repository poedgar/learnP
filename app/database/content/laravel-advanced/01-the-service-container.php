<?php

// Lesson 1 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'the-service-container',
    'title' => 'The service container',
    'summary' => 'How Laravel builds objects: bindings and singletons, interfaces bound to classes, autowiring with reflection, and calling functions with their dependencies injected.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try making Basket a shared instance.
        class Container
        {
            private array $factories = [];

            public function bind(string $id, Closure $factory): void
            {
                $this->factories[$id] = $factory;
            }

            public function make(string $id): mixed
            {
                return ($this->factories[$id])($this);
            }
        }

        class Basket
        {
            public array $items = [];
        }

        $app = new Container();
        $app->bind(Basket::class, fn () => new Basket());

        $app->make(Basket::class)->items[] = 'Dune';
        echo count($app->make(Basket::class)->items) . ' item(s) in the basket';
        PHP,

    'quiz' => [
        [
            'question' => 'What does a service container do?',
            'options' => [
                'It stores your app\'s files',
                'It knows how to build objects and their dependencies, and hands them out on request',
                'It runs code in a separate process',
                'It caches database queries',
            ],
            'correct_option' => 1,
            'explanation' => 'You teach the container how to build things once, with bindings; afterwards anything can ask it for them.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $factories = [];
                $factories['greeting'] = fn () => 'Hello';
                $factories['message'] = fn () => $factories['greeting']() . ', reader';

                $factories['greeting'] = fn () => 'Welcome';

                echo $factories['message']();
                PHP,
            'options' => ['`Hello, reader`', '`Welcome, reader`', '`, reader`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'An arrow function captures `$factories` by value when it is created, while `greeting` still held the first closure. This is why a container passes itself into each factory instead.',
        ],
        [
            'question' => 'What is the difference between `bind()` and `singleton()` in Laravel?',
            'options' => [
                '`bind()` is for interfaces, `singleton()` for classes',
                '`bind()` runs the factory on every `make()`; `singleton()` runs it once and reuses the result',
                '`singleton()` can only be used once per app',
                'There is none',
            ],
            'correct_option' => 1,
            'explanation' => 'A singleton is built the first time it\'s asked for, then the same object is returned every time.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Counter
                {
                    public int $count = 0;
                }

                $shared = null;
                $make = function () use (&$shared) {
                    return $shared ??= new Counter();
                };

                $make()->count++;
                $make()->count++;
                $fresh = new Counter();
                $fresh->count++;

                echo $make()->count;
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`0`'],
            'correct_option' => 1,
            'explanation' => '`$make()` always returns the one shared counter, which was incremented twice. `$fresh` is a different object.',
        ],
        [
            'question' => 'Why bind an interface, such as `PaymentGateway`, rather than ask for a concrete class everywhere?',
            'options' => [
                'Interfaces are faster',
                'The class that fulfils it can be swapped in one place, for example a fake in tests',
                'PHP can\'t type-hint concrete classes',
                'Laravel requires it',
            ],
            'correct_option' => 1,
            'explanation' => 'Code that depends on the interface never changes; only the binding does.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Clock {}

                class Shop
                {
                    public function __construct(public Clock $clock, public string $name = 'Corner Books') {}
                }

                $parameters = (new ReflectionClass(Shop::class))->getConstructor()->getParameters();

                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();
                    echo $parameter->getName() . ':' . ($type->isBuiltin() ? 'builtin' : $type->getName()) . ' ';
                }
                PHP,
            'options' => [
                '`clock:Clock name:builtin `',
                '`clock:builtin name:builtin `',
                '`Clock string `',
                '`clock:Clock name:string `',
            ],
            'correct_option' => 0,
            'explanation' => '`Clock` is a class, so its type isn\'t built in and the name is `Clock`. `string` is a built-in type. Autowiring builds the first and has to use the default for the second.',
        ],
        [
            'question' => 'A constructor needs `int $limit` with no default value, and nothing is bound for it. What does Laravel\'s container do when autowiring the class?',
            'options' => [
                'Passes `0`',
                'Passes `null`',
                'Throws a `BindingResolutionException`',
                'Skips the parameter',
            ],
            'correct_option' => 2,
            'explanation' => 'There\'s no way to guess a plain integer, so resolution fails with an exception that names the parameter.',
        ],
        [
            'question' => 'When Laravel calls a route closure `function (Request $request, string $id)`, where do the two arguments come from?',
            'options' => [
                'Both from the URL',
                '`$request` from the container, `$id` from the URL',
                'Both from the container',
                '`$request` from the URL, `$id` from the container',
            ],
            'correct_option' => 1,
            'explanation' => 'Class-typed parameters are resolved from the container; route parameters are passed in by name.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Bind and make',
            'prompt' => 'Finish `Container`. `bind(string $id, Closure $factory)` stores a factory, and `make(string $id)` calls it, passing the container in, and returns the result. Asking for an id that was never bound throws a `RuntimeException` with the message `Nothing is bound to Mailer.` (using the id asked for).',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    public function bind(string $id, Closure $factory): void
                    {
                    }

                    public function make(string $id): mixed
                    {
                        return null;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        if (! array_key_exists($id, $this->bindings)) {
                            throw new RuntimeException("Nothing is bound to $id.");
                        }

                        return ($this->bindings[$id])($this);
                    }
                }
                PHP,
            'hint' => 'Keep the factories in a private array keyed by id. In `make()`, check `array_key_exists()` first, then call the factory with `$this`.',
            'checks' => [
                ['label' => 'make() returns what the factory builds', 'code' => "\$c = new Container();\n\$c->bind('shop', fn () => 'Corner Books');\nreturn expect_same(\$c->make('shop'), 'Corner Books');"],
                ['label' => 'Factories receive the container', 'code' => "\$c = new Container();\n\$c->bind('name', fn () => 'Dune');\n\$c->bind('title', fn (Container \$app) => strtoupper(\$app->make('name')));\nreturn expect_same(\$c->make('title'), 'DUNE');"],
                ['label' => 'Each make() runs the factory again', 'code' => "\$c = new Container();\n\$c->bind('basket', fn () => new ArrayObject());\nreturn \$c->make('basket') !== \$c->make('basket') ?: 'Two make() calls returned the same object.';"],
                ['label' => 'Unknown ids throw a helpful exception', 'code' => "try {\n    (new Container())->make('Mailer');\n} catch (RuntimeException \$e) {\n    return expect_same(\$e->getMessage(), 'Nothing is bound to Mailer.');\n}\nreturn 'No RuntimeException was thrown.';"],
            ],
        ],
        [
            'title' => 'Build it once',
            'prompt' => 'Add `singleton(string $id, Closure $factory)`. A singleton\'s factory runs the first time it is made; every later `make()` returns that same object. Plain `bind()` bindings must still build a new object each time.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function singleton(string $id, Closure $factory): void
                    {
                        $this->bind($id, $factory);
                    }

                    public function make(string $id): mixed
                    {
                        return ($this->bindings[$id])($this);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];
                    private array $shared = [];
                    private array $instances = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                        unset($this->shared[$id], $this->instances[$id]);
                    }

                    public function singleton(string $id, Closure $factory): void
                    {
                        $this->bind($id, $factory);
                        $this->shared[$id] = true;
                    }

                    public function make(string $id): mixed
                    {
                        if (array_key_exists($id, $this->instances)) {
                            return $this->instances[$id];
                        }

                        $object = ($this->bindings[$id])($this);

                        if (isset($this->shared[$id])) {
                            $this->instances[$id] = $object;
                        }

                        return $object;
                    }
                }
                PHP,
            'hint' => 'Remember which ids are shared (for example in a `$shared` array), and keep each shared object in an `$instances` array the first time it is built. Check `$instances` at the start of `make()`.',
            'checks' => [
                ['label' => 'A singleton is the same object every time', 'code' => "\$c = new Container();\n\$c->singleton('db', fn () => new ArrayObject());\nreturn \$c->make('db') === \$c->make('db') ?: 'Two make() calls returned different objects.';"],
                ['label' => 'Its factory runs only once', 'code' => "\$runs = 0;\n\$c = new Container();\n\$c->singleton('db', function () use (&\$runs) { \$runs++; return new ArrayObject(); });\n\$c->make('db');\n\$c->make('db');\n\$c->make('db');\nreturn expect_same(\$runs, 1);"],
                ['label' => 'It is built only when first needed', 'code' => "\$runs = 0;\n\$c = new Container();\n\$c->singleton('db', function () use (&\$runs) { \$runs++; return new ArrayObject(); });\nreturn expect_same(\$runs, 0);"],
                ['label' => 'bind() still builds a new object each time', 'code' => "\$c = new Container();\n\$c->bind('basket', fn () => new ArrayObject());\nreturn \$c->make('basket') !== \$c->make('basket') ?: 'bind() should not share its object.';"],
            ],
        ],
        [
            'title' => 'Swap in a fake',
            'prompt' => 'Add `instance(string $id, mixed $object)`, which stores an object you already have so `make()` returns exactly it, and `has(string $id): bool`, which says whether the id has a binding or an instance. Registering an instance replaces any binding with that id, which is how tests swap a real service for a fake.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        return ($this->bindings[$id])($this);
                    }
                }

                interface Mailer
                {
                    public function send(string $to): string;
                }

                class SmtpMailer implements Mailer
                {
                    public function send(string $to): string
                    {
                        return "Sent to $to over SMTP";
                    }
                }

                class FakeMailer implements Mailer
                {
                    public array $sent = [];

                    public function send(string $to): string
                    {
                        $this->sent[] = $to;
                        return 'Pretended to send';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];
                    private array $instances = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                        unset($this->instances[$id]);
                    }

                    public function instance(string $id, mixed $object): void
                    {
                        $this->instances[$id] = $object;
                        unset($this->bindings[$id]);
                    }

                    public function has(string $id): bool
                    {
                        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->bindings);
                    }

                    public function make(string $id): mixed
                    {
                        if (array_key_exists($id, $this->instances)) {
                            return $this->instances[$id];
                        }

                        return ($this->bindings[$id])($this);
                    }
                }

                interface Mailer
                {
                    public function send(string $to): string;
                }

                class SmtpMailer implements Mailer
                {
                    public function send(string $to): string
                    {
                        return "Sent to $to over SMTP";
                    }
                }

                class FakeMailer implements Mailer
                {
                    public array $sent = [];

                    public function send(string $to): string
                    {
                        $this->sent[] = $to;
                        return 'Pretended to send';
                    }
                }
                PHP,
            'hint' => 'Keep instances in their own array. `make()` checks it first; `has()` checks both arrays with `array_key_exists()`.',
            'checks' => [
                ['label' => 'make() returns the very instance given', 'code' => "\$c = new Container();\n\$fake = new FakeMailer();\n\$c->instance(Mailer::class, \$fake);\nreturn \$c->make(Mailer::class) === \$fake ?: 'make() did not return the instance.';"],
                ['label' => 'An instance replaces a binding', 'code' => "\$c = new Container();\n\$c->bind(Mailer::class, fn () => new SmtpMailer());\n\$fake = new FakeMailer();\n\$c->instance(Mailer::class, \$fake);\n\$c->make(Mailer::class)->send('ada@example.com');\nreturn expect_same(\$fake->sent, ['ada@example.com']);"],
                ['label' => 'has() knows bindings and instances', 'code' => "\$c = new Container();\n\$c->bind('a', fn () => 1);\n\$c->instance('b', 2);\nreturn expect_same([\$c->has('a'), \$c->has('b'), \$c->has('c')], [true, true, false]);"],
                ['label' => 'An instance can be any value, even null', 'code' => "\$c = new Container();\n\$c->instance('nothing', null);\nreturn expect_same([\$c->has('nothing'), \$c->make('nothing')], [true, null]);"],
            ],
        ],
        [
            'title' => 'Bind an interface to a class',
            'prompt' => 'Let `bind()` take either a factory closure or a **class name**: `bind(Mailer::class, SmtpMailer::class)` means "build a `SmtpMailer` when a `Mailer` is asked for". Binding a class name that doesn\'t exist throws an `InvalidArgumentException` straight away, from `bind()`. The classes in this challenge have no constructor arguments.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        return ($this->bindings[$id])($this);
                    }
                }

                interface Mailer {}

                class SmtpMailer implements Mailer {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure|string $concrete): void
                    {
                        if (is_string($concrete)) {
                            if (! class_exists($concrete)) {
                                throw new InvalidArgumentException("There is no class $concrete.");
                            }
                            $class = $concrete;
                            $concrete = fn () => new $class();
                        }

                        $this->bindings[$id] = $concrete;
                    }

                    public function make(string $id): mixed
                    {
                        return ($this->bindings[$id])($this);
                    }
                }

                interface Mailer {}

                class SmtpMailer implements Mailer {}
                PHP,
            'hint' => 'Widen the parameter type to `Closure|string`. When it\'s a string, check `class_exists()`, then turn it into a closure that does `new $class()`.',
            'checks' => [
                ['label' => 'A class name builds that class', 'code' => "\$c = new Container();\n\$c->bind(Mailer::class, SmtpMailer::class);\nreturn \$c->make(Mailer::class) instanceof SmtpMailer ?: 'make(Mailer::class) did not build a SmtpMailer.';"],
                ['label' => 'Each make() builds a new one', 'code' => "\$c = new Container();\n\$c->bind(Mailer::class, SmtpMailer::class);\nreturn \$c->make(Mailer::class) !== \$c->make(Mailer::class) ?: 'Both calls returned the same object.';"],
                ['label' => 'Closures still work', 'code' => "\$c = new Container();\n\$c->bind('n', fn () => 42);\nreturn expect_same(\$c->make('n'), 42);"],
                ['label' => 'A missing class is caught when binding', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => (new Container())->bind(Mailer::class, 'PostcardMailer'));"],
            ],
        ],
        [
            'title' => 'Autowiring',
            'prompt' => 'Make `make()` build classes that were never bound. Read the constructor with reflection: build each class-typed parameter with `make()` (so bindings and autowiring work all the way down), and use the default value for other parameters. A parameter that is neither throws a `RuntimeException` with the message `Cannot resolve $limit in Shelf.` (the parameter\'s name and the class being built).',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        if (isset($this->bindings[$id])) {
                            return ($this->bindings[$id])($this);
                        }

                        return new $id();
                    }
                }

                class Logger {}

                class Catalogue
                {
                    public function __construct(public Logger $logger) {}
                }

                class Search
                {
                    public function __construct(public Catalogue $catalogue, public int $perPage = 20) {}
                }

                class Shelf
                {
                    public function __construct(public int $limit) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        if (isset($this->bindings[$id])) {
                            return ($this->bindings[$id])($this);
                        }

                        $constructor = (new ReflectionClass($id))->getConstructor();
                        if ($constructor === null) {
                            return new $id();
                        }

                        $arguments = array_map(function (ReflectionParameter $parameter) use ($id) {
                            $type = $parameter->getType();
                            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                                return $this->make($type->getName());
                            }
                            if ($parameter->isDefaultValueAvailable()) {
                                return $parameter->getDefaultValue();
                            }
                            throw new RuntimeException("Cannot resolve \${$parameter->getName()} in $id.");
                        }, $constructor->getParameters());

                        return new $id(...$arguments);
                    }
                }

                class Logger {}

                class Catalogue
                {
                    public function __construct(public Logger $logger) {}
                }

                class Search
                {
                    public function __construct(public Catalogue $catalogue, public int $perPage = 20) {}
                }

                class Shelf
                {
                    public function __construct(public int $limit) {}
                }
                PHP,
            'hint' => '`(new ReflectionClass($id))->getConstructor()` is null for classes without one. For each parameter, a `ReflectionNamedType` that isn\'t `isBuiltin()` names a class to `make()`; otherwise use `getDefaultValue()` if `isDefaultValueAvailable()`.',
            'checks' => [
                ['label' => 'Dependencies are built all the way down', 'code' => "\$search = (new Container())->make(Search::class);\nreturn \$search->catalogue->logger instanceof Logger ?: 'Search was not built with a Catalogue and Logger.';"],
                ['label' => 'Defaults are used for other parameters', 'code' => 'return expect_same((new Container())->make(Search::class)->perPage, 20);'],
                ['label' => 'Bindings are used for dependencies', 'code' => "\$c = new Container();\n\$logger = new Logger();\n\$c->bind(Logger::class, fn () => \$logger);\nreturn \$c->make(Search::class)->catalogue->logger === \$logger ?: 'The bound Logger was not used.';"],
                ['label' => 'Unresolvable parameters are named', 'code' => "try {\n    (new Container())->make(Shelf::class);\n} catch (RuntimeException \$e) {\n    return expect_same(\$e->getMessage(), 'Cannot resolve \$limit in Shelf.');\n}\nreturn 'No RuntimeException was thrown.';"],
            ],
        ],
        [
            'title' => 'Call with injection',
            'prompt' => 'Add `call(Closure $callback, array $parameters = [])`, like Laravel\'s `app()->call()`. For each of the closure\'s parameters: use `$parameters[name]` if it was given, otherwise `make()` its class if it has a class type, otherwise its default value. Return what the closure returns.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        return isset($this->bindings[$id]) ? ($this->bindings[$id])($this) : new $id();
                    }

                    public function call(Closure $callback, array $parameters = []): mixed
                    {
                        return $callback();
                    }
                }

                class Request
                {
                    public string $path = '/books';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $bindings = [];

                    public function bind(string $id, Closure $factory): void
                    {
                        $this->bindings[$id] = $factory;
                    }

                    public function make(string $id): mixed
                    {
                        return isset($this->bindings[$id]) ? ($this->bindings[$id])($this) : new $id();
                    }

                    public function call(Closure $callback, array $parameters = []): mixed
                    {
                        $arguments = [];
                        foreach ((new ReflectionFunction($callback))->getParameters() as $parameter) {
                            $name = $parameter->getName();
                            $type = $parameter->getType();

                            $arguments[] = match (true) {
                                array_key_exists($name, $parameters) => $parameters[$name],
                                $type instanceof ReflectionNamedType && ! $type->isBuiltin() => $this->make($type->getName()),
                                default => $parameter->getDefaultValue(),
                            };
                        }

                        return $callback(...$arguments);
                    }
                }

                class Request
                {
                    public string $path = '/books';
                }
                PHP,
            'hint' => '`new ReflectionFunction($callback)` lists the closure\'s parameters. Build an array of arguments in order, then call `$callback(...$arguments)`.',
            'checks' => [
                ['label' => 'Class-typed parameters come from the container', 'code' => "return expect_same((new Container())->call(fn (Request \$request) => \$request->path), '/books');"],
                ['label' => 'Named values are passed in', 'code' => "return expect_same((new Container())->call(fn (Request \$r, string \$id) => \$r->path . '/' . \$id, ['id' => '42']), '/books/42');"],
                ['label' => 'Given values win over the container', 'code' => "\$mine = new Request();\n\$mine->path = '/basket';\nreturn expect_same((new Container())->call(fn (Request \$request) => \$request->path, ['request' => \$mine]), '/basket');"],
                ['label' => 'Defaults fill the rest, in any order', 'code' => "return expect_same((new Container())->call(fn (int \$page = 1, ?Request \$r = null, string \$sort = 'title') => \"\$page \$sort \" . \$r?->path, ['sort' => 'price']), '1 price /books');"],
            ],
        ],
    ],
];
