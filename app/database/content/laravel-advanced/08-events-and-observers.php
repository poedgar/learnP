<?php

// Lesson 8 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'events-and-observers',
    'title' => 'Events and observers',
    'summary' => 'How Laravel\'s event dispatcher works: listeners and object events, stopping early and `until()`, wildcards, listener classes, model events that can cancel a save, and observers.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a listener that returns false.
        $listeners = [];

        function listen(array &$listeners, string $event, Closure $listener): void
        {
            $listeners[$event][] = $listener;
        }

        function dispatch(array $listeners, string $event, mixed ...$payload): void
        {
            foreach ($listeners[$event] ?? [] as $listener) {
                $listener(...$payload);
            }
        }

        listen($listeners, 'order.placed', fn ($id) => print("Receipt sent for $id" . PHP_EOL));
        listen($listeners, 'order.placed', fn ($id) => print("Stock updated for $id" . PHP_EOL));

        dispatch($listeners, 'order.placed', 'A-17');
        PHP,

    'quiz' => [
        [
            'question' => 'What problem do events solve?',
            'options' => [
                'They make code run faster',
                'The code where something happens doesn\'t need to know about every feature that reacts to it',
                'They replace the database',
                'They let PHP run code in parallel',
            ],
            'correct_option' => 1,
            'explanation' => 'Checkout announces `OrderPlaced`; receipts, stock and analytics each listen without checkout changing.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $listeners = [
                    'order.placed' => [
                        fn ($id) => "receipt:$id",
                        fn ($id) => false,
                        fn ($id) => "stock:$id",
                    ],
                ];

                $responses = [];
                foreach ($listeners['order.placed'] as $listener) {
                    $response = $listener('A1');
                    if ($response === false) {
                        break;
                    }
                    $responses[] = $response;
                }

                echo implode(',', $responses);
                PHP,
            'options' => ['`receipt:A1,stock:A1`', '`receipt:A1`', '`receipt:A1,,stock:A1`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => 'Returning `false` stops the event; the third listener never runs.',
        ],
        [
            'question' => 'You dispatch `new OrderPlaced($order)`. What do its listeners receive?',
            'options' => [
                'The string `\'OrderPlaced\'`',
                'The `OrderPlaced` object',
                'Only `$order`',
                'Nothing; they read a global',
            ],
            'correct_option' => 1,
            'explanation' => 'The class name is the event name, and the object itself is passed to each listener.',
        ],
        [
            'question' => 'What does `Event::until(\'can.ship\', [$order])` return?',
            'options' => [
                'An array of every listener\'s answer',
                'The first answer from a listener that isn\'t `null`',
                'Always `true`',
                'The number of listeners',
            ],
            'correct_option' => 1,
            'explanation' => '`until()` stops at the first real answer, which suits questions rather than announcements.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $patterns = ['order.*', 'order.placed', '*.shipped', 'user.*'];

                $matching = array_filter($patterns, function (string $pattern) {
                    $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                    return preg_match($regex, 'order.shipped') === 1;
                });

                echo implode(' ', $matching);
                PHP,
            'options' => ['`order.*`', '`order.* *.shipped`', '`order.* order.placed *.shipped`', '`*.shipped`'],
            'correct_option' => 1,
            'explanation' => 'Both wildcard patterns match `order.shipped`; `order.placed` and `user.*` don\'t.',
        ],
        [
            'question' => 'A listener class implements `ShouldQueue`. What changes?',
            'options' => [
                'It runs before the other listeners',
                'It is pushed onto a queue and run later by a worker, instead of during the request',
                'It runs only once per day',
                'It can\'t receive the event',
            ],
            'correct_option' => 1,
            'explanation' => 'Slow work like sending email moves out of the request, so the user isn\'t kept waiting.',
        ],
        [
            'question' => 'A `creating` listener on `Book` returns `false`. What happens to `$book->save()`?',
            'options' => [
                'The book is saved anyway',
                'Nothing is written to the database and `save()` returns `false`',
                'An exception is thrown',
                'The book is saved, then deleted',
            ],
            'correct_option' => 1,
            'explanation' => 'The "-ing" events run before the query; returning `false` cancels it.',
        ],
        [
            'question' => 'What does `Book::observe(BookObserver::class)` do?',
            'options' => [
                'Logs every query on the books table',
                'Registers each method of `BookObserver` named after a model event as a listener for that event on `Book`',
                'Makes `Book` read-only',
                'Copies `BookObserver`\'s methods into `Book`',
            ],
            'correct_option' => 1,
            'explanation' => 'An observer\'s `creating()`, `updated()`, `deleted()` and so on become that model\'s event listeners.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Listen and dispatch',
            'prompt' => 'Finish `Dispatcher`. `listen(string $event, Closure $listener)` adds a listener. `dispatch(string|object $event, array $payload = [])` calls each listener for the event, in the order added, and returns their answers as a list. For a string event, the payload is spread as arguments; for an object event, the event name is the object\'s class and the only argument is the object.',
            'starter_code' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    public function listen(string $event, Closure $listener): void
                    {
                    }

                    public function dispatch(string|object $event, array $payload = []): array
                    {
                        return [];
                    }
                }

                class OrderPlaced
                {
                    public function __construct(public string $orderId) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(string|object $event, array $payload = []): array
                    {
                        if (is_object($event)) {
                            [$name, $payload] = [$event::class, [$event]];
                        } else {
                            $name = $event;
                        }

                        $responses = [];
                        foreach ($this->listeners[$name] ?? [] as $listener) {
                            $responses[] = $listener(...$payload);
                        }

                        return $responses;
                    }
                }

                class OrderPlaced
                {
                    public function __construct(public string $orderId) {}
                }
                PHP,
            'hint' => 'Keep listeners in `$this->listeners[$event][]`. For an object, switch the name to `$event::class` and the payload to `[$event]` before looping.',
            'checks' => [
                ['label' => 'Listeners run in order, with the payload', 'code' => "\$d = new Dispatcher();\n\$d->listen('order.placed', fn (\$id, \$total) => \"receipt \$id \$total\");\n\$d->listen('order.placed', fn (\$id) => \"stock \$id\");\nreturn expect_same(\$d->dispatch('order.placed', ['A-17', 25]), ['receipt A-17 25', 'stock A-17']);"],
                ['label' => 'Object events use the class name', 'code' => "\$d = new Dispatcher();\n\$d->listen(OrderPlaced::class, fn (OrderPlaced \$e) => \"receipt {\$e->orderId}\");\nreturn expect_same(\$d->dispatch(new OrderPlaced('B-2')), ['receipt B-2']);"],
                ['label' => 'Other events are unaffected', 'code' => "\$d = new Dispatcher();\n\$d->listen('a', fn () => 'a');\nreturn expect_same(\$d->dispatch('b'), []);"],
            ],
        ],
        [
            'title' => 'Stop, or ask until',
            'prompt' => 'Change `dispatch()` so that a listener returning `false` stops the event: later listeners don\'t run, and `dispatch()` returns the answers collected before it. Then add `until(string $event, array $payload = [])`, which calls listeners in order and returns the first answer that isn\'t `null`, or `null` if there is none.',
            'starter_code' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(string $event, array $payload = []): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event] ?? [] as $listener) {
                            $responses[] = $listener(...$payload);
                        }

                        return $responses;
                    }

                    public function until(string $event, array $payload = []): mixed
                    {
                        return null;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(string $event, array $payload = []): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event] ?? [] as $listener) {
                            $response = $listener(...$payload);
                            if ($response === false) {
                                break;
                            }
                            $responses[] = $response;
                        }

                        return $responses;
                    }

                    public function until(string $event, array $payload = []): mixed
                    {
                        foreach ($this->listeners[$event] ?? [] as $listener) {
                            $response = $listener(...$payload);
                            if ($response !== null) {
                                return $response;
                            }
                        }

                        return null;
                    }
                }
                PHP,
            'hint' => 'Compare each answer with `=== false` (an empty string or `0` must not stop the event) and `break`. `until()` returns from inside the loop as soon as an answer `!== null`.',
            'checks' => [
                ['label' => 'false stops later listeners', 'code' => "\$ran = [];\n\$d = new Dispatcher();\n\$d->listen('e', function () use (&\$ran) { \$ran[] = 1; return 'one'; });\n\$d->listen('e', function () use (&\$ran) { \$ran[] = 2; return false; });\n\$d->listen('e', function () use (&\$ran) { \$ran[] = 3; return 'three'; });\n\$responses = \$d->dispatch('e');\nreturn expect_same([\$responses, \$ran], [['one'], [1, 2]]);"],
                ['label' => 'Other falsy answers don\'t stop it', 'code' => "\$d = new Dispatcher();\n\$d->listen('e', fn () => 0);\n\$d->listen('e', fn () => '');\n\$d->listen('e', fn () => null);\n\$d->listen('e', fn () => 'last');\nreturn expect_same(\$d->dispatch('e'), [0, '', null, 'last']);"],
                ['label' => 'until() returns the first real answer', 'code' => "\$ran = 0;\n\$d = new Dispatcher();\n\$d->listen('can.ship', function () use (&\$ran) { \$ran++; return null; });\n\$d->listen('can.ship', function () use (&\$ran) { \$ran++; return 'no: out of stock'; });\n\$d->listen('can.ship', function () use (&\$ran) { \$ran++; return 'yes'; });\nreturn expect_same([\$d->until('can.ship'), \$ran], ['no: out of stock', 2]);"],
                ['label' => 'until() with no answer is null', 'code' => "\$d = new Dispatcher();\n\$d->listen('q', fn () => null);\nreturn expect_same([\$d->until('q'), \$d->until('nothing')], [null, null]);"],
            ],
        ],
        [
            'title' => 'Wildcard listeners',
            'prompt' => 'Let `listen()` accept patterns with `*`, matching any characters. When an event is dispatched, its exact listeners run first, then the matching wildcard listeners in the order added. A wildcard listener is called with two arguments, the event name and the payload array, while exact listeners still get the payload spread out.',
            'starter_code' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(string $event, array $payload = []): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event] ?? [] as $listener) {
                            $responses[] = $listener(...$payload);
                        }

                        return $responses;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];
                    private array $wildcards = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        if (str_contains($event, '*')) {
                            $this->wildcards[] = [$event, $listener];
                        } else {
                            $this->listeners[$event][] = $listener;
                        }
                    }

                    public function dispatch(string $event, array $payload = []): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event] ?? [] as $listener) {
                            $responses[] = $listener(...$payload);
                        }

                        foreach ($this->wildcards as [$pattern, $listener]) {
                            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
                            if (preg_match($regex, $event) === 1) {
                                $responses[] = $listener($event, $payload);
                            }
                        }

                        return $responses;
                    }
                }
                PHP,
            'hint' => 'Keep wildcard listeners in their own list as `[$pattern, $listener]` pairs. Turn a pattern into a regular expression with `preg_quote()` and replace the escaped `\*` with `.*`.',
            'checks' => [
                ['label' => 'Wildcards hear matching events', 'code' => "\$d = new Dispatcher();\n\$d->listen('order.*', fn (\$name, \$data) => \"\$name:\" . implode(',', \$data));\nreturn expect_same([\$d->dispatch('order.shipped', ['A1']), \$d->dispatch('user.created', ['u'])], [['order.shipped:A1'], []]);"],
                ['label' => 'Exact listeners run first', 'code' => "\$d = new Dispatcher();\n\$d->listen('*', fn (\$name) => 'any');\n\$d->listen('order.placed', fn (\$id) => \"exact \$id\");\nreturn expect_same(\$d->dispatch('order.placed', ['A1']), ['exact A1', 'any']);"],
                ['label' => '* can be anywhere', 'code' => "\$d = new Dispatcher();\n\$d->listen('*.shipped', fn (\$name) => \$name);\nreturn expect_same([\$d->dispatch('order.shipped'), \$d->dispatch('order.shipped.late')], [['order.shipped'], []]);"],
                ['label' => 'Dots are not wildcards', 'code' => "\$d = new Dispatcher();\n\$d->listen('order.*', fn (\$name) => \$name);\nreturn expect_same(\$d->dispatch('orderXshipped'), []);"],
            ],
        ],
        [
            'title' => 'Listener classes',
            'prompt' => 'Let `listen()` take a listener as a closure, a class name (call its `handle()` method), or a string `Class@method` (call that method). Class listeners are created with `new` only when the event fires, a fresh object each time. Events are objects, passed to the listener.',
            'starter_code' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(object $event): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event::class] ?? [] as $listener) {
                            $responses[] = $listener($event);
                        }

                        return $responses;
                    }
                }

                class OrderPlaced
                {
                    public function __construct(public string $orderId) {}
                }

                class SendReceipt
                {
                    public static int $created = 0;

                    public function __construct()
                    {
                        self::$created++;
                    }

                    public function handle(OrderPlaced $event): string
                    {
                        return "receipt for {$event->orderId}";
                    }

                    public function sendCopy(OrderPlaced $event): string
                    {
                        return "copy for {$event->orderId}";
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Dispatcher
                {
                    private array $listeners = [];

                    public function listen(string $event, Closure|string $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function dispatch(object $event): array
                    {
                        $responses = [];
                        foreach ($this->listeners[$event::class] ?? [] as $listener) {
                            $responses[] = $this->resolve($listener)($event);
                        }

                        return $responses;
                    }

                    private function resolve(Closure|string $listener): callable
                    {
                        if ($listener instanceof Closure) {
                            return $listener;
                        }

                        [$class, $method] = array_pad(explode('@', $listener, 2), 2, 'handle');

                        return [new $class(), $method];
                    }
                }

                class OrderPlaced
                {
                    public function __construct(public string $orderId) {}
                }

                class SendReceipt
                {
                    public static int $created = 0;

                    public function __construct()
                    {
                        self::$created++;
                    }

                    public function handle(OrderPlaced $event): string
                    {
                        return "receipt for {$event->orderId}";
                    }

                    public function sendCopy(OrderPlaced $event): string
                    {
                        return "copy for {$event->orderId}";
                    }
                }
                PHP,
            'hint' => 'Widen the type to `Closure|string` and store listeners as given. At dispatch time, split a string at `@` with `array_pad(..., 2, \'handle\')` and call `[new $class(), $method]`.',
            'checks' => [
                ['label' => 'A class name calls handle()', 'code' => "\$d = new Dispatcher();\n\$d->listen(OrderPlaced::class, SendReceipt::class);\nreturn expect_same(\$d->dispatch(new OrderPlaced('A1')), ['receipt for A1']);"],
                ['label' => 'Class@method calls that method', 'code' => "\$d = new Dispatcher();\n\$d->listen(OrderPlaced::class, 'SendReceipt@sendCopy');\nreturn expect_same(\$d->dispatch(new OrderPlaced('A2')), ['copy for A2']);"],
                ['label' => 'Created only when the event fires, fresh each time', 'code' => "SendReceipt::\$created = 0;\n\$d = new Dispatcher();\n\$d->listen(OrderPlaced::class, SendReceipt::class);\nif (SendReceipt::\$created !== 0) return 'The listener was created when it was registered.';\n\$d->dispatch(new OrderPlaced('A'));\n\$d->dispatch(new OrderPlaced('B'));\nreturn expect_same(SendReceipt::\$created, 2);"],
                ['label' => 'Closures still work alongside', 'code' => "\$d = new Dispatcher();\n\$d->listen(OrderPlaced::class, fn (\$e) => 'closure');\n\$d->listen(OrderPlaced::class, SendReceipt::class);\nreturn expect_same(\$d->dispatch(new OrderPlaced('A3')), ['closure', 'receipt for A3']);"],
            ],
        ],
        [
            'title' => 'Model events',
            'prompt' => 'Finish `Model::save()`. A model whose `$exists` is false is being created, otherwise updated. Fire `saving`, then `creating` or `updating`; if any of their listeners returns `false`, stop and return `false` without saving. Otherwise "save" (set `$exists` to true and add the model to `static::$saved`), then fire `created` or `updated`, then `saved`, and return `true`. Listeners are registered per class with `static::on($event, $listener)` and receive the model.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public static array $saved = [];
                    protected static array $listeners = [];
                    public bool $exists = false;

                    public function __construct(public array $attributes = []) {}

                    public static function on(string $event, Closure $listener): void
                    {
                        static::$listeners[static::class][$event][] = $listener;
                    }

                    protected function fire(string $event): bool
                    {
                        foreach (static::$listeners[static::class][$event] ?? [] as $listener) {
                            if ($listener($this) === false) {
                                return false;
                            }
                        }

                        return true;
                    }

                    public function save(): bool
                    {
                        $this->exists = true;
                        static::$saved[] = $this;

                        return true;
                    }
                }

                class Book extends Model {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public static array $saved = [];
                    protected static array $listeners = [];
                    public bool $exists = false;

                    public function __construct(public array $attributes = []) {}

                    public static function on(string $event, Closure $listener): void
                    {
                        static::$listeners[static::class][$event][] = $listener;
                    }

                    protected function fire(string $event): bool
                    {
                        foreach (static::$listeners[static::class][$event] ?? [] as $listener) {
                            if ($listener($this) === false) {
                                return false;
                            }
                        }

                        return true;
                    }

                    public function save(): bool
                    {
                        $creating = ! $this->exists;

                        if (! $this->fire('saving') || ! $this->fire($creating ? 'creating' : 'updating')) {
                            return false;
                        }

                        $this->exists = true;
                        static::$saved[] = $this;

                        $this->fire($creating ? 'created' : 'updated');
                        $this->fire('saved');

                        return true;
                    }
                }

                class Book extends Model {}
                PHP,
            'hint' => 'Work out `$creating = ! $this->exists` first, before saving changes it. `fire()` already returns `false` when a listener does, so the "-ing" events can be checked with `||`.',
            'checks' => [
                ['label' => 'Events fire in order when creating, then updating', 'code' => "eval('class Novel extends Model {}');\n\$log = new ArrayObject();\nforeach (['saving', 'creating', 'created', 'updating', 'updated', 'saved'] as \$e) { Novel::on(\$e, function () use (\$log, \$e) { \$log[] = \$e; }); }\n\$novel = new Novel();\n\$novel->save();\n\$novel->save();\nreturn expect_same(\$log->getArrayCopy(), ['saving', 'creating', 'created', 'saved', 'saving', 'updating', 'updated', 'saved']);"],
                ['label' => 'false from creating cancels the save', 'code' => "eval('class Poem extends Model {}');\nPoem::on('creating', fn (\$m) => \$m->attributes['title'] !== '');\nModel::\$saved = [];\n\$poem = new Poem(['title' => '']);\nreturn expect_same([\$poem->save(), \$poem->exists, count(Model::\$saved)], [false, false, 0]);"],
                ['label' => 'false from saving stops before creating', 'code' => "eval('class Essay extends Model {}');\n\$ran = false;\nEssay::on('saving', fn () => false);\nEssay::on('creating', function () use (&\$ran) { \$ran = true; });\nreturn expect_same([(new Essay())->save(), \$ran], [false, false]);"],
                ['label' => 'Listeners belong to their own class', 'code' => "eval('class Comic extends Model {}');\neval('class Zine extends Model {}');\nComic::on('saving', fn () => false);\nreturn expect_same([(new Zine())->save(), (new Comic())->save()], [true, false]);"],
            ],
        ],
        [
            'title' => 'Observers',
            'prompt' => 'Add `Model::observe(object $observer)`. For every model event name in `Model::EVENTS` that the observer has a method for, register a listener that calls that method with the model, so the method\'s return value counts (an observer\'s `creating()` returning `false` still cancels). Then write `BookObserver` with a `creating()` method that sets the book\'s `slug` attribute from its title: lower case, with every run of characters other than letters and digits turned into one `-`, and no `-` at either end.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public const EVENTS = ['saving', 'creating', 'created', 'updating', 'updated', 'saved'];
                    protected static array $listeners = [];
                    public bool $exists = false;

                    public function __construct(public array $attributes = []) {}

                    public static function on(string $event, Closure $listener): void
                    {
                        static::$listeners[static::class][$event][] = $listener;
                    }

                    public static function observe(object $observer): void
                    {
                    }

                    protected function fire(string $event): bool
                    {
                        foreach (static::$listeners[static::class][$event] ?? [] as $listener) {
                            if ($listener($this) === false) {
                                return false;
                            }
                        }

                        return true;
                    }

                    public function save(): bool
                    {
                        $creating = ! $this->exists;
                        if (! $this->fire('saving') || ! $this->fire($creating ? 'creating' : 'updating')) {
                            return false;
                        }
                        $this->exists = true;
                        $this->fire($creating ? 'created' : 'updated');
                        $this->fire('saved');

                        return true;
                    }
                }

                class Book extends Model {}

                class BookObserver
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public const EVENTS = ['saving', 'creating', 'created', 'updating', 'updated', 'saved'];
                    protected static array $listeners = [];
                    public bool $exists = false;

                    public function __construct(public array $attributes = []) {}

                    public static function on(string $event, Closure $listener): void
                    {
                        static::$listeners[static::class][$event][] = $listener;
                    }

                    public static function observe(object $observer): void
                    {
                        foreach (self::EVENTS as $event) {
                            if (method_exists($observer, $event)) {
                                static::on($event, fn (Model $model) => $observer->$event($model));
                            }
                        }
                    }

                    protected function fire(string $event): bool
                    {
                        foreach (static::$listeners[static::class][$event] ?? [] as $listener) {
                            if ($listener($this) === false) {
                                return false;
                            }
                        }

                        return true;
                    }

                    public function save(): bool
                    {
                        $creating = ! $this->exists;
                        if (! $this->fire('saving') || ! $this->fire($creating ? 'creating' : 'updating')) {
                            return false;
                        }
                        $this->exists = true;
                        $this->fire($creating ? 'created' : 'updated');
                        $this->fire('saved');

                        return true;
                    }
                }

                class Book extends Model {}

                class BookObserver
                {
                    public function creating(Book $book): void
                    {
                        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($book->attributes['title']));
                        $book->attributes['slug'] = trim($slug, '-');
                    }
                }
                PHP,
            'hint' => 'Loop over `self::EVENTS`, and for each name with `method_exists($observer, $event)` register `fn (Model $model) => $observer->$event($model)`. For the slug, `preg_replace(\'/[^a-z0-9]+/\', \'-\', ...)` on the lower-cased title, then `trim()` the dashes.',
            'checks' => [
                ['label' => 'The observer sets the slug on create', 'code' => "if (! method_exists('BookObserver', 'creating')) return 'BookObserver has no creating() method.';\nBook::observe(new BookObserver());\n\$book = new Book(['title' => 'Children of Dune: Book Three!']);\n\$book->save();\nreturn expect_same(\$book->attributes['slug'] ?? null, 'children-of-dune-book-three');"],
                ['label' => 'Each observer method hears its own event', 'code' => "eval('class Leaflet extends Model {}');\n\$observer = new class { public array \$log = []; public function created(\$m) { \$this->log[] = 'created'; } public function updated(\$m) { \$this->log[] = 'updated'; } public function helper() { \$this->log[] = 'helper'; } };\nLeaflet::observe(\$observer);\n\$leaflet = new Leaflet();\n\$leaflet->save();\n\$leaflet->save();\nreturn expect_same(\$observer->log, ['created', 'updated']);"],
                ['label' => 'An observer returning false cancels', 'code' => "eval('class Pamphlet extends Model {}');\nPamphlet::observe(new class { public function saving(\$m) { return false; } });\nreturn expect_same((new Pamphlet())->save(), false);"],
            ],
        ],
    ],
];
