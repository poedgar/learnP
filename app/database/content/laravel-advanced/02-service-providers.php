<?php

// Lesson 2 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'service-providers',
    'title' => 'Service providers',
    'summary' => 'How Laravel starts: providers registering bindings and then booting, hooks around booting, deferred providers, package configuration and environment checks.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try using 'clock' in register() instead of boot().
        class App
        {
            public array $bindings = [];

            public function start(array $providers): void
            {
                foreach ($providers as $provider) {
                    $provider->register($this);
                }
                foreach ($providers as $provider) {
                    $provider->boot($this);
                }
            }
        }

        class GreetingProvider
        {
            public function register(App $app): void
            {
                $app->bindings['greeting'] = fn () => 'Welcome to Corner Books';
            }

            public function boot(App $app): void
            {
                echo $app->bindings['greeting']() . ', it is ' . $app->bindings['clock']() . PHP_EOL;
            }
        }

        class ClockProvider
        {
            public function register(App $app): void
            {
                $app->bindings['clock'] = fn () => '9 o\'clock';
            }

            public function boot(App $app): void {}
        }

        (new App())->start([new GreetingProvider(), new ClockProvider()]);
        PHP,

    'quiz' => [
        [
            'question' => 'What belongs in a service provider\'s `register()` method?',
            'options' => [
                'Only bindings into the container',
                'Event listeners and routes',
                'Code that uses services from other providers',
                'Database queries',
            ],
            'correct_option' => 0,
            'explanation' => 'During `register()` other providers may not have registered yet, so it should only bind things. Using services belongs in `boot()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $calls = [];
                foreach (['A', 'B'] as $name) {
                    $calls[] = "$name registers";
                }
                foreach (['A', 'B'] as $name) {
                    $calls[] = "$name boots";
                }

                echo implode(', ', $calls);
                PHP,
            'options' => [
                '`A registers, A boots, B registers, B boots`',
                '`A registers, B registers, A boots, B boots`',
                '`A boots, B boots, A registers, B registers`',
                '`A registers, B registers`',
            ],
            'correct_option' => 1,
            'explanation' => 'Every provider registers first, then every provider boots, which is the order Laravel uses.',
        ],
        [
            'question' => 'Why can `boot()` safely use a service registered by a provider listed after it?',
            'options' => [
                'Laravel sorts providers by their dependencies',
                'All `register()` methods have run before any `boot()` method',
                'Providers are booted in reverse order',
                'It can\'t; that throws an error',
            ],
            'correct_option' => 1,
            'explanation' => 'The two-phase start means every binding exists by the time booting starts.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $booted = false;
                $callbacks = [];
                $output = [];

                $whenBooted = function (Closure $callback) use (&$booted, &$callbacks) {
                    if ($booted) {
                        $callback();
                    } else {
                        $callbacks[] = $callback;
                    }
                };

                $whenBooted(function () use (&$output) { $output[] = 'first'; });
                $booted = true;
                foreach ($callbacks as $callback) {
                    $callback();
                }
                $whenBooted(function () use (&$output) { $output[] = 'late'; });

                echo implode(' ', $output);
                PHP,
            'options' => ['`first`', '`late first`', '`first late`', 'Nothing'],
            'correct_option' => 2,
            'explanation' => 'The first callback waits until booting finishes. The one added afterwards runs immediately, just like Laravel\'s `booted()`.',
        ],
        [
            'question' => 'What is a deferred provider?',
            'options' => [
                'A provider that boots after all the others',
                'A provider that is only loaded when one of the services it provides is first needed',
                'A provider that runs in a queue',
                'A provider that is disabled in production',
            ],
            'correct_option' => 1,
            'explanation' => 'Laravel remembers what a deferred provider `provides()` and loads it on demand, which keeps startup fast.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $defaults = ['per_page' => 15, 'sort' => 'title', 'show_covers' => true];
                $app = ['sort' => 'price', 'show_covers' => false];

                $config = array_merge($defaults, $app);

                var_dump($config['per_page'], $config['sort'], $config['show_covers']);
                PHP,
            'options' => [
                '`int(15)`, `string(5) "price"`, `bool(false)`',
                '`int(15)`, `string(5) "title"`, `bool(true)`',
                '`NULL`, `string(5) "price"`, `bool(false)`',
                '`int(15)`, `string(5) "price"`, `bool(true)`',
            ],
            'correct_option' => 0,
            'explanation' => 'Later arrays win in `array_merge()`, so the app\'s values override the defaults, and defaults fill in what the app left out.',
        ],
        [
            'question' => 'The app runs in `local`. What does `$app->environment(\'production\', \'local\')` return in Laravel?',
            'options' => ['`\'local\'`', '`true`', '`false`', '`[\'local\']`'],
            'correct_option' => 1,
            'explanation' => 'With arguments, `environment()` answers whether the current environment matches any of them. Without arguments it returns the name.',
        ],
        [
            'question' => 'In what order does a Laravel request start?',
            'options' => [
                'Route, middleware, providers, configuration',
                'Configuration, providers register, providers boot, then middleware and the route',
                'Providers boot, providers register, configuration, route',
                'Middleware, configuration, route, providers',
            ],
            'correct_option' => 1,
            'explanation' => 'The app loads its configuration, runs both provider phases, then the HTTP kernel sends the request through middleware to a route.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Register, then boot',
            'prompt' => 'Finish `Application`. `addProvider(ServiceProvider $provider)` stores a provider, and `start()` calls `register()` on every provider, in the order they were added, and then `boot()` on every provider, in the same order. `start()` only does this once; calling it again does nothing.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];

                    public function addProvider(ServiceProvider $provider): void
                    {
                    }

                    public function start(): void
                    {
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];
                    private array $providers = [];
                    private bool $started = false;

                    public function addProvider(ServiceProvider $provider): void
                    {
                        $this->providers[] = $provider;
                    }

                    public function start(): void
                    {
                        if ($this->started) {
                            return;
                        }
                        $this->started = true;

                        foreach ($this->providers as $provider) {
                            $provider->register($this);
                        }
                        foreach ($this->providers as $provider) {
                            $provider->boot($this);
                        }
                    }
                }
                PHP,
            'hint' => 'Keep the providers in an array. In `start()`, loop over them twice: once calling `register()`, then again calling `boot()`. A `$started` flag stops a second run.',
            'checks' => [
                ['label' => 'Every provider registers before any boots', 'code' => <<<'PHP'
                    $log = new ArrayObject();
                    $make = fn (string $name) => new class($name, $log) extends ServiceProvider {
                        public function __construct(private string $name, private ArrayObject $log) {}
                        public function register(Application $app): void { $this->log[] = "{$this->name} register"; }
                        public function boot(Application $app): void { $this->log[] = "{$this->name} boot"; }
                    };
                    $app = new Application();
                    $app->addProvider($make('A'));
                    $app->addProvider($make('B'));
                    $app->start();
                    return expect_same($log->getArrayCopy(), ['A register', 'B register', 'A boot', 'B boot']);
                    PHP],
                ['label' => 'boot() can use what a later provider registered', 'code' => <<<'PHP'
                    $app = new Application();
                    $first = new class extends ServiceProvider {
                        public string $seen = '';
                        public function boot(Application $app): void { $this->seen = $app->bindings['clock'](); }
                    };
                    $app->addProvider($first);
                    $app->addProvider(new class extends ServiceProvider {
                        public function register(Application $app): void { $app->bindings['clock'] = fn () => 'tick'; }
                    });
                    $app->start();
                    return expect_same($first->seen, 'tick');
                    PHP],
                ['label' => 'start() runs only once', 'code' => <<<'PHP'
                    $provider = new class extends ServiceProvider {
                        public int $boots = 0;
                        public function boot(Application $app): void { $this->boots++; }
                    };
                    $app = new Application();
                    $app->addProvider($provider);
                    $app->start();
                    $app->start();
                    return expect_same($provider->boots, 1);
                    PHP],
            ],
        ],
        [
            'title' => 'Latecomers',
            'prompt' => 'Providers can be added after the app has started, as Laravel\'s `$app->register()` allows. Change `addProvider()` so that, once the app has started, a new provider is registered and booted straight away. Before `start()`, it still just waits.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];
                    private array $providers = [];
                    private bool $started = false;

                    public function addProvider(ServiceProvider $provider): void
                    {
                        $this->providers[] = $provider;
                    }

                    public function start(): void
                    {
                        if ($this->started) {
                            return;
                        }
                        $this->started = true;

                        foreach ($this->providers as $provider) {
                            $provider->register($this);
                        }
                        foreach ($this->providers as $provider) {
                            $provider->boot($this);
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];
                    private array $providers = [];
                    private bool $started = false;

                    public function addProvider(ServiceProvider $provider): void
                    {
                        $this->providers[] = $provider;

                        if ($this->started) {
                            $provider->register($this);
                            $provider->boot($this);
                        }
                    }

                    public function start(): void
                    {
                        if ($this->started) {
                            return;
                        }
                        $this->started = true;

                        foreach ($this->providers as $provider) {
                            $provider->register($this);
                        }
                        foreach ($this->providers as $provider) {
                            $provider->boot($this);
                        }
                    }
                }
                PHP,
            'hint' => 'In `addProvider()`, after storing the provider, check `$this->started` and call both methods if it\'s true.',
            'checks' => [
                ['label' => 'A late provider registers and boots at once', 'code' => <<<'PHP'
                    $app = new Application();
                    $app->start();
                    $provider = new class extends ServiceProvider {
                        public array $log = [];
                        public function register(Application $app): void { $this->log[] = 'register'; }
                        public function boot(Application $app): void { $this->log[] = 'boot'; }
                    };
                    $app->addProvider($provider);
                    return expect_same($provider->log, ['register', 'boot']);
                    PHP],
                ['label' => 'An early provider waits for start()', 'code' => <<<'PHP'
                    $app = new Application();
                    $provider = new class extends ServiceProvider {
                        public array $log = [];
                        public function register(Application $app): void { $this->log[] = 'register'; }
                        public function boot(Application $app): void { $this->log[] = 'boot'; }
                    };
                    $app->addProvider($provider);
                    if ($provider->log !== []) return 'The provider ran before start().';
                    $app->start();
                    return expect_same($provider->log, ['register', 'boot']);
                    PHP],
                ['label' => 'A late provider is not run twice', 'code' => <<<'PHP'
                    $app = new Application();
                    $app->start();
                    $provider = new class extends ServiceProvider {
                        public int $boots = 0;
                        public function boot(Application $app): void { $this->boots++; }
                    };
                    $app->addProvider($provider);
                    $app->start();
                    return expect_same($provider->boots, 1);
                    PHP],
            ],
        ],
        [
            'title' => 'Booting hooks',
            'prompt' => 'Add `booting(Closure $callback)` and `booted(Closure $callback)`. In `start()`, `booting` callbacks run after every provider has registered but before the first `boot()`, and `booted` callbacks run after the last `boot()`, each in the order they were added. A `booted` callback added after the app has started runs immediately. Each callback receives the application.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];
                    private array $providers = [];
                    private bool $started = false;

                    public function addProvider(ServiceProvider $provider): void
                    {
                        $this->providers[] = $provider;
                    }

                    public function booting(Closure $callback): void
                    {
                    }

                    public function booted(Closure $callback): void
                    {
                    }

                    public function start(): void
                    {
                        if ($this->started) {
                            return;
                        }
                        $this->started = true;

                        foreach ($this->providers as $provider) {
                            $provider->register($this);
                        }
                        foreach ($this->providers as $provider) {
                            $provider->boot($this);
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class ServiceProvider
                {
                    public function register(Application $app): void {}

                    public function boot(Application $app): void {}
                }

                class Application
                {
                    public array $bindings = [];
                    private array $providers = [];
                    private array $bootingCallbacks = [];
                    private array $bootedCallbacks = [];
                    private bool $started = false;

                    public function addProvider(ServiceProvider $provider): void
                    {
                        $this->providers[] = $provider;
                    }

                    public function booting(Closure $callback): void
                    {
                        $this->bootingCallbacks[] = $callback;
                    }

                    public function booted(Closure $callback): void
                    {
                        if ($this->started) {
                            $callback($this);
                            return;
                        }
                        $this->bootedCallbacks[] = $callback;
                    }

                    public function start(): void
                    {
                        if ($this->started) {
                            return;
                        }

                        foreach ($this->providers as $provider) {
                            $provider->register($this);
                        }
                        foreach ($this->bootingCallbacks as $callback) {
                            $callback($this);
                        }
                        foreach ($this->providers as $provider) {
                            $provider->boot($this);
                        }
                        $this->started = true;
                        foreach ($this->bootedCallbacks as $callback) {
                            $callback($this);
                        }
                    }
                }
                PHP,
            'hint' => 'Keep two arrays of callbacks. In `start()`, run the `booting` ones between the two provider loops and the `booted` ones after. In `booted()`, call the callback right away when the app has already started.',
            'checks' => [
                ['label' => 'Hooks run at the right moments', 'code' => <<<'PHP'
                    $log = new ArrayObject();
                    $app = new Application();
                    $app->addProvider(new class($log) extends ServiceProvider {
                        public function __construct(private ArrayObject $log) {}
                        public function register(Application $app): void { $this->log[] = 'register'; }
                        public function boot(Application $app): void { $this->log[] = 'boot'; }
                    });
                    $app->booted(function () use ($log) { $log[] = 'booted'; });
                    $app->booting(function () use ($log) { $log[] = 'booting'; });
                    $app->start();
                    return expect_same($log->getArrayCopy(), ['register', 'booting', 'boot', 'booted']);
                    PHP],
                ['label' => 'Callbacks run in the order added', 'code' => <<<'PHP'
                    $log = new ArrayObject();
                    $app = new Application();
                    $app->booted(function () use ($log) { $log[] = 1; });
                    $app->booted(function () use ($log) { $log[] = 2; });
                    $app->start();
                    return expect_same($log->getArrayCopy(), [1, 2]);
                    PHP],
                ['label' => 'A late booted() runs immediately', 'code' => <<<'PHP'
                    $ran = false;
                    $app = new Application();
                    $app->start();
                    $app->booted(function () use (&$ran) { $ran = true; });
                    return $ran ?: 'The callback did not run.';
                    PHP],
                ['label' => 'Callbacks receive the application', 'code' => <<<'PHP'
                    $app = new Application();
                    $seen = null;
                    $app->booting(function ($given) use (&$seen) { $seen = $given; });
                    $app->start();
                    return $seen === $app ?: 'The callback was not given the application.';
                    PHP],
            ],
        ],
        [
            'title' => 'Load it when needed',
            'prompt' => 'Support deferred providers. `addDeferred(DeferredProvider $provider)` records each id the provider `provides()` without registering anything. `make(string $id)` returns the binding; if there is none yet but a deferred provider provides the id, that provider is registered first (once only, even if it provides several ids), then the binding is used. An id nobody provides throws a `RuntimeException`.',
            'starter_code' => <<<'PHP'
                <?php

                interface DeferredProvider
                {
                    public function provides(): array;

                    public function register(Application $app): void;
                }

                class Application
                {
                    public array $bindings = [];

                    public function addDeferred(DeferredProvider $provider): void
                    {
                    }

                    public function make(string $id): mixed
                    {
                        return ($this->bindings[$id])();
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DeferredProvider
                {
                    public function provides(): array;

                    public function register(Application $app): void;
                }

                class Application
                {
                    public array $bindings = [];
                    private array $deferred = [];

                    public function addDeferred(DeferredProvider $provider): void
                    {
                        foreach ($provider->provides() as $id) {
                            $this->deferred[$id] = $provider;
                        }
                    }

                    public function make(string $id): mixed
                    {
                        if (! isset($this->bindings[$id]) && isset($this->deferred[$id])) {
                            $provider = $this->deferred[$id];
                            $this->deferred = array_filter($this->deferred, fn ($waiting) => $waiting !== $provider);
                            $provider->register($this);
                        }

                        if (! isset($this->bindings[$id])) {
                            throw new RuntimeException("Nothing provides $id.");
                        }

                        return ($this->bindings[$id])();
                    }
                }
                PHP,
            'hint' => 'Map each provided id to its provider. When `make()` finds no binding but a waiting provider, register it and remove every id of that provider from the map, so it never registers twice.',
            'checks' => [
                ['label' => 'Nothing is registered up front', 'code' => <<<'PHP'
                    $provider = new class implements DeferredProvider {
                        public int $registered = 0;
                        public function provides(): array { return ['pdf']; }
                        public function register(Application $app): void { $this->registered++; $app->bindings['pdf'] = fn () => 'PDF'; }
                    };
                    (new Application())->addDeferred($provider);
                    return expect_same($provider->registered, 0);
                    PHP],
                ['label' => 'The provider loads on first use', 'code' => <<<'PHP'
                    $app = new Application();
                    $app->addDeferred(new class implements DeferredProvider {
                        public function provides(): array { return ['pdf']; }
                        public function register(Application $app): void { $app->bindings['pdf'] = fn () => 'PDF renderer'; }
                    });
                    return expect_same($app->make('pdf'), 'PDF renderer');
                    PHP],
                ['label' => 'It registers only once for all its ids', 'code' => <<<'PHP'
                    $provider = new class implements DeferredProvider {
                        public int $registered = 0;
                        public function provides(): array { return ['pdf', 'csv']; }
                        public function register(Application $app): void {
                            $this->registered++;
                            $app->bindings['pdf'] = fn () => 'PDF';
                            $app->bindings['csv'] = fn () => 'CSV';
                        }
                    };
                    $app = new Application();
                    $app->addDeferred($provider);
                    $app->make('pdf');
                    $app->make('csv');
                    $app->make('pdf');
                    return expect_same($provider->registered, 1);
                    PHP],
                ['label' => 'Unknown ids throw', 'code' => "return expect_throws(RuntimeException::class, fn () => (new Application())->make('fax'));"],
            ],
        ],
        [
            'title' => 'Package defaults',
            'prompt' => 'Write `mergeConfigFrom(array $defaults, string $key)` on `Application`, like the provider helper of the same name. It sets `$config[$key]` to the package\'s defaults with the app\'s own values for that key on top. Keys the app sets win; keys it leaves out come from the defaults. The app may have no settings for that key at all.',
            'starter_code' => <<<'PHP'
                <?php

                class Application
                {
                    public function __construct(public array $config = []) {}

                    public function mergeConfigFrom(array $defaults, string $key): void
                    {
                        $this->config[$key] = $defaults;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Application
                {
                    public function __construct(public array $config = []) {}

                    public function mergeConfigFrom(array $defaults, string $key): void
                    {
                        $this->config[$key] = array_merge($defaults, $this->config[$key] ?? []);
                    }
                }
                PHP,
            'hint' => '`array_merge()` lets later arrays win. Put the defaults first and the app\'s values (or `[]` if there are none) second.',
            'checks' => [
                ['label' => 'The app\'s values win', 'code' => "\$app = new Application(['mail' => ['from' => 'shop@example.com']]);\n\$app->mergeConfigFrom(['driver' => 'smtp', 'from' => 'noreply@example.com'], 'mail');\nreturn expect_same(\$app->config['mail'], ['driver' => 'smtp', 'from' => 'shop@example.com']);"],
                ['label' => 'Defaults fill in what the app left out', 'code' => "\$app = new Application(['mail' => ['from' => 'a@example.com']]);\n\$app->mergeConfigFrom(['port' => 587], 'mail');\nreturn expect_same(\$app->config['mail']['port'] ?? null, 587);"],
                ['label' => 'Works when the app has nothing for the key', 'code' => "\$app = new Application();\n\$app->mergeConfigFrom(['port' => 587], 'mail');\nreturn expect_same(\$app->config, ['mail' => ['port' => 587]]);"],
                ['label' => 'Other keys are untouched', 'code' => "\$app = new Application(['app' => ['name' => 'Corner Books']]);\n\$app->mergeConfigFrom(['port' => 587], 'mail');\nreturn expect_same(\$app->config['app'], ['name' => 'Corner Books']);"],
            ],
        ],
        [
            'title' => 'Which environment?',
            'prompt' => 'Write `environment(string ...$patterns): string|bool` on `Application`, like Laravel\'s. With no arguments it returns the environment\'s name. With arguments it returns `true` if the name matches any of them, where `*` in a pattern matches any characters (so `local*` matches `local` and `local-docker`), and `false` otherwise. Other characters, like `.`, match only themselves.',
            'starter_code' => <<<'PHP'
                <?php

                class Application
                {
                    public function __construct(private string $env) {}

                    public function environment(string ...$patterns): string|bool
                    {
                        return $this->env;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Application
                {
                    public function __construct(private string $env) {}

                    public function environment(string ...$patterns): string|bool
                    {
                        if ($patterns === []) {
                            return $this->env;
                        }

                        foreach ($patterns as $pattern) {
                            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                            if (preg_match($regex, $this->env) === 1) {
                                return true;
                            }
                        }

                        return false;
                    }
                }
                PHP,
            'hint' => 'Turn each pattern into a regular expression: `preg_quote()` it, then replace the escaped `\*` with `.*`, and anchor it with `^` and `$`.',
            'checks' => [
                ['label' => 'No arguments returns the name', 'code' => "return expect_same((new Application('staging'))->environment(), 'staging');"],
                ['label' => 'Exact names match', 'code' => "\$app = new Application('local');\nreturn expect_same([\$app->environment('production', 'local'), \$app->environment('production')], [true, false]);"],
                ['label' => '* matches any characters', 'code' => "return expect_same([(new Application('local-docker'))->environment('local*'), (new Application('local'))->environment('local*'), (new Application('production'))->environment('local*')], [true, true, false]);"],
                ['label' => 'Patterns match the whole name, and . means a dot', 'code' => "return expect_same([(new Application('testing'))->environment('test'), (new Application('v1x0'))->environment('v1.0')], [false, false]);"],
            ],
        ],
    ],
];
