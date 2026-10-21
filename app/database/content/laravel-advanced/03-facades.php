<?php

// Lesson 3 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'facades',
    'title' => 'Facades',
    'summary' => 'How `Cache::get()` reaches an object in the container: `__callStatic()`, accessors, cached roots, swapping in fakes for tests, aliases and real-time facades.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a Clock facade.
        abstract class Facade
        {
            public static array $container = [];

            abstract protected static function getFacadeAccessor(): string;

            public static function __callStatic(string $method, array $arguments): mixed
            {
                return static::$container[static::getFacadeAccessor()]->$method(...$arguments);
            }
        }

        class Greeter
        {
            public function hello(string $name): string
            {
                return "Hello, $name!";
            }
        }

        class Greet extends Facade
        {
            protected static function getFacadeAccessor(): string
            {
                return 'greeter';
            }
        }

        Facade::$container['greeter'] = new Greeter();

        echo Greet::hello('Ada');
        PHP,

    'quiz' => [
        [
            'question' => 'What is a Laravel facade?',
            'options' => [
                'A class full of static methods that do the work themselves',
                'A class that forwards static calls to an object in the service container',
                'A design pattern for building HTML',
                'A cached copy of a configuration file',
            ],
            'correct_option' => 1,
            'explanation' => '`Cache::get()` ends up as a normal method call on the cache object the container holds.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Shout
                {
                    public static function __callStatic(string $method, array $arguments): string
                    {
                        return strtoupper($method) . ':' . implode(',', $arguments);
                    }

                    public static function real(): string
                    {
                        return 'real';
                    }
                }

                echo Shout::hello('a', 'b') . ' ' . Shout::real();
                PHP,
            'options' => ['`HELLO:a,b REAL`', '`HELLO:a,b real`', '`hello:a,b real`', 'An error: hello() is undefined'],
            'correct_option' => 1,
            'explanation' => '`__callStatic()` only runs for static methods that don\'t exist. `real()` exists, so it is called normally.',
        ],
        [
            'question' => 'What does a facade\'s `getFacadeAccessor()` return?',
            'options' => [
                'The object to call',
                'The id the real object has in the container',
                'The name of the method to call',
                'The facade\'s own class name',
            ],
            'correct_option' => 1,
            'explanation' => 'The facade asks the container for that id, for example `\'cache\'`, and calls the method on what it gets back.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                abstract class Base
                {
                    public static function describe(): string
                    {
                        return 'I forward to ' . static::accessor();
                    }

                    abstract protected static function accessor(): string;
                }

                class Mail extends Base
                {
                    protected static function accessor(): string
                    {
                        return 'mailer';
                    }
                }

                class Queue extends Base
                {
                    protected static function accessor(): string
                    {
                        return 'queue';
                    }
                }

                echo Queue::describe();
                PHP,
            'options' => ['`I forward to mailer`', '`I forward to queue`', '`I forward to `', 'An error: Base is abstract'],
            'correct_option' => 1,
            'explanation' => '`static::` refers to the class that was called, `Queue`, so its accessor is used. That\'s how one `Facade` base serves every facade.',
        ],
        [
            'question' => 'Why does Laravel clear the facades\' resolved instances between tests?',
            'options' => [
                'To save memory',
                'So an object, or a fake, resolved in one test doesn\'t leak into the next',
                'Because facades can only be used once',
                'To reload the configuration files',
            ],
            'correct_option' => 1,
            'explanation' => 'Facades remember the object they resolved. Each test gets a fresh application, so the facades must forget the old one.',
        ],
        [
            'question' => 'What does `Mail::fake()` do in a Laravel test?',
            'options' => [
                'Sends mail to a test inbox',
                'Swaps the real mailer for a fake that records messages so the test can assert on them',
                'Disables the `Mail` facade',
                'Prints the mail to the screen',
            ],
            'correct_option' => 1,
            'explanation' => 'Nothing is sent. Afterwards, `Mail::assertSent()` checks what the code tried to send.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Inventory
                {
                    public static function count(): int
                    {
                        return 7;
                    }
                }

                class_alias(Inventory::class, 'Stock');

                $stock = new Stock();
                echo Stock::count() . ' ' . get_class($stock);
                PHP,
            'options' => ['`7 Stock`', '`7 Inventory`', 'An error: class Stock is undefined', '`7 stock`'],
            'correct_option' => 1,
            'explanation' => 'An alias is another name for the same class, so the object\'s class is still `Inventory`.',
        ],
        [
            'question' => 'You write `use Facades\App\Services\PriceCalculator;` and call `PriceCalculator::total($basket)`. What happens?',
            'options' => [
                'An error, because no such facade class exists',
                'Laravel generates a facade class on the fly that forwards to a `PriceCalculator` from the container',
                '`total()` must be a static method',
                'Laravel copies the class into the Facades folder',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s a real-time facade: an autoloader spots the `Facades\` prefix and creates the facade class when it\'s first used.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Forward the call',
            'prompt' => 'Finish `Facade::__callStatic()`. It looks up `static::getFacadeAccessor()` with `static::$app->make()`, calls the method on the object it gets back with the same arguments, and returns the result. Then write a `Clock` facade whose accessor is `clock`.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return null;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        $root = static::$app->make(static::getFacadeAccessor());

                        return $root->$method(...$arguments);
                    }
                }

                class Clock extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'clock';
                    }
                }
                PHP,
            'hint' => 'Get the object with `static::$app->make(static::getFacadeAccessor())`, then call `$object->$method(...$arguments)`. `Clock` extends `Facade` and returns `\'clock\'`.',
            'checks' => [
                ['label' => 'Clock forwards to the clock in the container', 'code' => <<<'PHP'
                    if (! class_exists('Clock', false)) return 'There is no Clock facade.';
                    Facade::$app = new Container();
                    Facade::$app->instance('clock', new class { public function now(): string { return '09:00'; } });
                    return expect_same(Clock::now(), '09:00');
                    PHP],
                ['label' => 'Arguments are passed on', 'code' => <<<'PHP'
                    if (! class_exists('Clock', false)) return 'There is no Clock facade.';
                    Facade::$app = new Container();
                    Facade::$app->instance('clock', new class { public function add(int $a, int $b): int { return $a + $b; } });
                    return expect_same(Clock::add(2, 3), 5);
                    PHP],
                ['label' => 'Each facade uses its own accessor', 'code' => <<<'PHP'
                    if (! class_exists('Clock', false)) return 'There is no Clock facade.';
                    eval('class Shelf extends Facade { protected static function getFacadeAccessor(): string { return "shelf"; } }');
                    Facade::$app = new Container();
                    Facade::$app->instance('clock', new class { public function name(): string { return 'clock'; } });
                    Facade::$app->instance('shelf', new class { public function name(): string { return 'shelf'; } });
                    return expect_same([Clock::name(), Shelf::name()], ['clock', 'shelf']);
                    PHP],
            ],
        ],
        [
            'title' => 'Resolve once',
            'prompt' => 'Make facades remember what they resolved. The first call for an accessor fetches the object from `static::$app`; later calls, from any facade with the same accessor, reuse it without asking the container again. `Facade::clearResolvedInstances()` forgets them all.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    public int $lookups = 0;
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        $this->lookups++;
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function clearResolvedInstances(): void
                    {
                    }

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return static::$app->make(static::getFacadeAccessor())->$method(...$arguments);
                    }
                }

                class Cache extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'cache';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    public int $lookups = 0;
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        $this->lookups++;
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;
                    protected static array $resolved = [];

                    abstract protected static function getFacadeAccessor(): string;

                    public static function clearResolvedInstances(): void
                    {
                        static::$resolved = [];
                    }

                    protected static function getFacadeRoot(): object
                    {
                        $accessor = static::getFacadeAccessor();

                        return static::$resolved[$accessor] ??= static::$app->make($accessor);
                    }

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return static::getFacadeRoot()->$method(...$arguments);
                    }
                }

                class Cache extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'cache';
                    }
                }
                PHP,
            'hint' => 'Add a `protected static array $resolved = []` keyed by accessor. `$resolved[$accessor] ??= static::$app->make($accessor)` fetches it only the first time.',
            'checks' => [
                ['label' => 'The container is asked only once', 'code' => <<<'PHP'
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    Facade::$app->instance('cache', new class { public function get(): string { return 'Dune'; } });
                    Cache::get();
                    Cache::get();
                    Cache::get();
                    return expect_same(Facade::$app->lookups, 1);
                    PHP],
                ['label' => 'Calls still reach the object', 'code' => <<<'PHP'
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    Facade::$app->instance('cache', new class { public function get(): string { return 'Dune'; } });
                    return expect_same(Cache::get(), 'Dune');
                    PHP],
                ['label' => 'clearResolvedInstances() forgets them', 'code' => <<<'PHP'
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    Facade::$app->instance('cache', new class { public function get(): string { return 'old'; } });
                    Cache::get();
                    Facade::$app->instance('cache', new class { public function get(): string { return 'new'; } });
                    Facade::clearResolvedInstances();
                    return expect_same(Cache::get(), 'new');
                    PHP],
            ],
        ],
        [
            'title' => 'Swap in a fake',
            'prompt' => 'Add `Facade::swap(object $instance)`. It puts the instance behind the calling facade: the facade uses it from then on, even if it had already resolved the real object, and the container\'s entry for the accessor is replaced too, so code that asks the container directly gets the same object. Then add `Mail::fake()`, which swaps in a new `MailFake` and returns it.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;
                    protected static array $resolved = [];

                    abstract protected static function getFacadeAccessor(): string;

                    public static function clearResolvedInstances(): void
                    {
                        static::$resolved = [];
                    }

                    public static function swap(object $instance): void
                    {
                    }

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        $accessor = static::getFacadeAccessor();
                        $root = static::$resolved[$accessor] ??= static::$app->make($accessor);

                        return $root->$method(...$arguments);
                    }
                }

                class MailFake
                {
                    public array $sent = [];

                    public function send(string $to): void
                    {
                        $this->sent[] = $to;
                    }
                }

                class Mail extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'mailer';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;
                    protected static array $resolved = [];

                    abstract protected static function getFacadeAccessor(): string;

                    public static function clearResolvedInstances(): void
                    {
                        static::$resolved = [];
                    }

                    public static function swap(object $instance): void
                    {
                        $accessor = static::getFacadeAccessor();
                        static::$resolved[$accessor] = $instance;
                        static::$app->instance($accessor, $instance);
                    }

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        $accessor = static::getFacadeAccessor();
                        $root = static::$resolved[$accessor] ??= static::$app->make($accessor);

                        return $root->$method(...$arguments);
                    }
                }

                class MailFake
                {
                    public array $sent = [];

                    public function send(string $to): void
                    {
                        $this->sent[] = $to;
                    }
                }

                class Mail extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'mailer';
                    }

                    public static function fake(): MailFake
                    {
                        $fake = new MailFake();
                        static::swap($fake);

                        return $fake;
                    }
                }
                PHP,
            'hint' => 'In `swap()`, set `static::$resolved[$accessor]` and call `static::$app->instance($accessor, $instance)`. `fake()` is a static method on `Mail` that builds a `MailFake`, swaps it in and returns it.',
            'checks' => [
                ['label' => 'Calls reach the swapped instance', 'code' => <<<'PHP'
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    Facade::$app->instance('mailer', new class { public array $sent = []; public function send(string $to): void { $this->sent[] = $to; } });
                    Mail::send('first@example.com');
                    $fake = new MailFake();
                    Mail::swap($fake);
                    Mail::send('ada@example.com');
                    return expect_same($fake->sent, ['ada@example.com']);
                    PHP],
                ['label' => 'The container gets the swap too', 'code' => <<<'PHP'
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    $fake = new MailFake();
                    Mail::swap($fake);
                    return Facade::$app->make('mailer') === $fake ?: 'The container still has the old mailer.';
                    PHP],
                ['label' => 'Mail::fake() records what is sent', 'code' => <<<'PHP'
                    if (! method_exists('Mail', 'fake')) return 'Mail has no fake() method.';
                    Facade::clearResolvedInstances();
                    Facade::$app = new Container();
                    Facade::$app->instance('mailer', new class { public function send(string $to): void { throw new Exception('Real mail was sent!'); } });
                    $fake = Mail::fake();
                    Mail::send('ada@example.com');
                    return expect_same($fake->sent, ['ada@example.com']);
                    PHP],
                ['label' => 'Each fake() starts fresh', 'code' => <<<'PHP'
                    if (! method_exists('Mail', 'fake')) return 'Mail has no fake() method.';
                    Facade::$app = new Container();
                    $first = Mail::fake();
                    Mail::send('a@example.com');
                    $second = Mail::fake();
                    return expect_same($second->sent, []);
                    PHP],
            ],
        ],
        [
            'title' => 'Lazy aliases',
            'prompt' => 'Write `AliasLoader`, like the one Laravel uses for names such as `Str`. It\'s constructed with a map of alias names to real class names. `register()` adds an autoloader (with `spl_autoload_register()`) that creates an alias with `class_alias()` only when PHP first meets that alias, so nothing is loaded up front. Names that aren\'t in the map are left alone for other autoloaders.',
            'starter_code' => <<<'PHP'
                <?php

                class AliasLoader
                {
                    public function __construct(private array $aliases) {}

                    public function register(): void
                    {
                    }
                }

                class SupportStr
                {
                    public static function upper(string $text): string
                    {
                        return strtoupper($text);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class AliasLoader
                {
                    public function __construct(private array $aliases) {}

                    public function register(): void
                    {
                        spl_autoload_register(function (string $name): void {
                            if (isset($this->aliases[$name])) {
                                class_alias($this->aliases[$name], $name);
                            }
                        });
                    }
                }

                class SupportStr
                {
                    public static function upper(string $text): string
                    {
                        return strtoupper($text);
                    }
                }
                PHP,
            'hint' => 'Pass `spl_autoload_register()` a closure that takes the class name PHP is looking for. If that name is a key in `$this->aliases`, call `class_alias($this->aliases[$name], $name)`.',
            'checks' => [
                ['label' => 'An alias works like the real class', 'code' => "(new AliasLoader(['StrOne' => SupportStr::class]))->register();\nreturn expect_same(StrOne::upper('dune'), 'DUNE');"],
                ['label' => 'Objects of an alias are the real class', 'code' => "(new AliasLoader(['StrTwo' => SupportStr::class]))->register();\nreturn expect_same(get_class(new StrTwo()), 'SupportStr');"],
                ['label' => 'Nothing is aliased before it is used', 'code' => "(new AliasLoader(['StrThree' => SupportStr::class]))->register();\nif (class_exists('StrThree', false)) return 'The alias was created up front.';\nreturn class_exists('StrThree') ?: 'The alias was not created when first used.';"],
                ['label' => 'Other names are left alone', 'code' => "(new AliasLoader(['StrFour' => SupportStr::class]))->register();\nreturn class_exists('NotAnAliasAtAll') === false ?: 'An unknown name became a class.';"],
            ],
        ],
        [
            'title' => 'Real-time facades',
            'prompt' => 'Write `RealTimeFacades::register()`. It adds an autoloader that, for any class name starting with `Facades\`, creates a facade class with that name on the fly (with `eval()`): it extends `Facade`, and its accessor is the rest of the name. So `Facades\PriceCalculator::total(...)` forwards to a `PriceCalculator` from the container, and `Facades\Shop\Pricing` forwards to `Shop\Pricing`. Other names are ignored.',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    public function make(string $id): object
                    {
                        return new $id();
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return static::$app->make(static::getFacadeAccessor())->$method(...$arguments);
                    }
                }

                class RealTimeFacades
                {
                    public static function register(): void
                    {
                    }
                }

                class PriceCalculator
                {
                    public function total(array $prices): float
                    {
                        return array_sum($prices);
                    }
                }

                Facade::$app = new Container();
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    public function make(string $id): object
                    {
                        return new $id();
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return static::$app->make(static::getFacadeAccessor())->$method(...$arguments);
                    }
                }

                class RealTimeFacades
                {
                    public static function register(): void
                    {
                        spl_autoload_register(function (string $name): void {
                            if (! str_starts_with($name, 'Facades\\')) {
                                return;
                            }

                            $target = substr($name, strlen('Facades\\'));
                            $position = strrpos($name, '\\');
                            $namespace = substr($name, 0, $position);
                            $class = substr($name, $position + 1);

                            eval("namespace $namespace; class $class extends \\Facade {
                                protected static function getFacadeAccessor(): string { return " . var_export($target, true) . "; }
                            }");
                        });
                    }
                }

                class PriceCalculator
                {
                    public function total(array $prices): float
                    {
                        return array_sum($prices);
                    }
                }

                Facade::$app = new Container();
                PHP,
            'hint' => 'The part after `Facades\` is the accessor. Split the full name at its last backslash into a namespace and a short class name, then `eval("namespace $namespace; class $class extends \\Facade { ... }")`, returning the accessor from `getFacadeAccessor()`.',
            'checks' => [
                ['label' => 'Facades\PriceCalculator forwards to PriceCalculator', 'code' => "RealTimeFacades::register();\nreturn expect_same(\\Facades\\PriceCalculator::total([10.0, 2.5]), 12.5);"],
                ['label' => 'The generated class is a Facade', 'code' => "RealTimeFacades::register();\nreturn is_subclass_of('Facades\\PriceCalculator', 'Facade') ?: 'Facades\\PriceCalculator does not extend Facade.';"],
                ['label' => 'Namespaced classes work too', 'code' => "RealTimeFacades::register();\neval('namespace Shop; class Pricing { public function vat(float \$p): float { return round(\$p * 1.2, 2); } }');\nreturn expect_same(\\Facades\\Shop\\Pricing::vat(10.0), 12.0);"],
                ['label' => 'Other names are ignored', 'code' => "RealTimeFacades::register();\nreturn class_exists('Shop\\Missing') === false ?: 'A class was invented for Shop\\Missing.';"],
            ],
        ],
        [
            'title' => 'Helpful errors',
            'prompt' => 'Make `__callStatic()` fail clearly. If the container has nothing for the accessor, throw a `RuntimeException` with Laravel\'s message `A facade root has not been set.` If the object has no such method, throw a `BadMethodCallException` with the message `Method CacheStore::fly does not exist.` (the object\'s class and the method name).',
            'starter_code' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function has(string $id): bool
                    {
                        return isset($this->instances[$id]);
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return static::$app->make(static::getFacadeAccessor())->$method(...$arguments);
                    }
                }

                class CacheStore
                {
                    public function get(string $key): ?string
                    {
                        return $key === 'bestseller' ? 'Dune' : null;
                    }
                }

                class Cache extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'cache';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Container
                {
                    private array $instances = [];

                    public function instance(string $id, object $object): void
                    {
                        $this->instances[$id] = $object;
                    }

                    public function has(string $id): bool
                    {
                        return isset($this->instances[$id]);
                    }

                    public function make(string $id): object
                    {
                        return $this->instances[$id];
                    }
                }

                abstract class Facade
                {
                    public static Container $app;

                    abstract protected static function getFacadeAccessor(): string;

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        $accessor = static::getFacadeAccessor();
                        if (! static::$app->has($accessor)) {
                            throw new RuntimeException('A facade root has not been set.');
                        }

                        $root = static::$app->make($accessor);
                        if (! method_exists($root, $method)) {
                            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', $root::class, $method));
                        }

                        return $root->$method(...$arguments);
                    }
                }

                class CacheStore
                {
                    public function get(string $key): ?string
                    {
                        return $key === 'bestseller' ? 'Dune' : null;
                    }
                }

                class Cache extends Facade
                {
                    protected static function getFacadeAccessor(): string
                    {
                        return 'cache';
                    }
                }
                PHP,
            'hint' => 'Check `static::$app->has($accessor)` before `make()`, then `method_exists($root, $method)` before calling. `$root::class` gives the object\'s class name.',
            'checks' => [
                ['label' => 'Working calls still work', 'code' => "Facade::\$app = new Container();\nFacade::\$app->instance('cache', new CacheStore());\nreturn expect_same(Cache::get('bestseller'), 'Dune');"],
                ['label' => 'No root gives Laravel\'s message', 'code' => "Facade::\$app = new Container();\ntry {\n    Cache::get('bestseller');\n} catch (RuntimeException \$e) {\n    return expect_same(\$e->getMessage(), 'A facade root has not been set.');\n}\nreturn 'No RuntimeException was thrown.';"],
                ['label' => 'A missing method is named', 'code' => "Facade::\$app = new Container();\nFacade::\$app->instance('cache', new CacheStore());\ntry {\n    Cache::fly();\n} catch (BadMethodCallException \$e) {\n    return expect_same(\$e->getMessage(), 'Method CacheStore::fly does not exist.');\n} catch (Error \$e) {\n    return 'A plain PHP error was thrown: ' . \$e->getMessage();\n}\nreturn 'No BadMethodCallException was thrown.';"],
            ],
        ],
    ],
];
