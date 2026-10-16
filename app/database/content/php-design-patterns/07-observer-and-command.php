<?php

// Lesson 7 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'observer-and-command',
    'title' => 'Observer and Command',
    'summary' => 'Let any number of objects react to events without the source knowing them, and turn actions into command objects that can be queued, logged and undone.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding another listener.
        class Events
        {
            private array $listeners = [];

            public function listen(string $event, Closure $listener): void
            {
                $this->listeners[$event][] = $listener;
            }

            public function dispatch(string $event, array $data = []): void
            {
                foreach ($this->listeners[$event] ?? [] as $listener) {
                    $listener($data);
                }
            }
        }

        $events = new Events();
        $events->listen('order.placed', fn ($d) => print("Email {$d['customer']}\n"));
        $events->dispatch('order.placed', ['customer' => 'Amara']);
        PHP,

    'quiz' => [
        [
            'question' => 'In the Observer pattern, what does the subject know about its observers?',
            'options' => [
                'Their exact classes',
                'Only that they implement a small interface it can call',
                'Nothing; observers find the subject themselves',
                'Their private properties',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s what makes new reactions possible without changing the subject.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Button
                {
                    private array $handlers = [];
                    public function onClick(Closure $handler): void { $this->handlers[] = $handler; }
                    public function click(): void { foreach ($this->handlers as $h) { $h(); } }
                }

                $button = new Button();
                $button->onClick(fn () => print('A'));
                $button->onClick(fn () => print('B'));
                $button->click();
                $button->click();
                PHP,
            'options' => ['`AB`', '`ABAB`', '`AABB`', '`BA`'],
            'correct_option' => 1,
            'explanation' => 'Each click calls every handler, in the order they were added.',
        ],
        [
            'question' => 'What happens when an event is dispatched that no listener has registered for?',
            'options' => [
                'An exception is thrown',
                'Nothing; the event is simply ignored',
                'Every listener runs',
                'The dispatcher waits for a listener',
            ],
            'correct_option' => 1,
            'explanation' => 'With `$this->listeners[$event] ?? []`, an unknown event loops over an empty list.',
        ],
        [
            'question' => 'Which is a real risk with observers?',
            'options' => [
                'They make the subject depend on every observer class',
                'The flow becomes hidden, and one failing observer may stop the others from running',
                'They can\'t be removed',
                'PHP only allows one observer per subject',
            ],
            'correct_option' => 1,
            'explanation' => 'Keep observers small and named clearly, and decide how errors in one should affect the rest.',
        ],
        [
            'question' => 'What is a command, in the Command pattern?',
            'options' => [
                'A shell command',
                'An object representing one action, with everything needed to carry it out',
                'A static method',
                'An event listener',
            ],
            'correct_option' => 1,
            'explanation' => 'Because the action is an object, it can be stored, queued, logged and undone.',
        ],
        [
            'question' => 'Why does turning actions into objects help with job queues?',
            'options' => [
                'Objects run faster',
                'Commands can be stored now and executed later by a worker',
                'Queues can only hold objects',
                'It doesn\'t help',
            ],
            'correct_option' => 1,
            'explanation' => 'The command carries its own data, so whoever runs it later needs nothing else.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Counter { public int $n = 0; }

                class Increment
                {
                    public function __construct(private Counter $c) {}
                    public function execute(): void { $this->c->n++; }
                    public function undo(): void { $this->c->n--; }
                }

                $counter = new Counter();
                $history = [];
                foreach ([1, 2, 3] as $i) {
                    $command = new Increment($counter);
                    $command->execute();
                    $history[] = $command;
                }
                array_pop($history)->undo();
                array_pop($history)->undo();

                echo $counter->n;
                PHP,
            'options' => ['`3`', '`2`', '`1`', '`0`'],
            'correct_option' => 2,
            'explanation' => 'Three increments make 3; undoing the last two brings it back to 1.',
        ],
        [
            'question' => 'What must a command know to support undo?',
            'options' => [
                'Nothing more than for execute()',
                'Enough about what it changed to reverse it',
                'The full history of every command',
                'The time it was run',
            ],
            'correct_option' => 1,
            'explanation' => 'A history of commands, each able to reverse itself, gives undo for free.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Watch the stock',
            'prompt' => "Make `StockLevel` a subject: `attach(StockObserver \$observer)` adds an observer, and `set(int \$copies)` stores the level and calls `stockChanged(string \$title, int \$copies)` on every observer. Then write `LowStockAlert`, an observer that adds `'Low stock: TITLE (N left)'` to its public `\$alerts` when the level is below 3.",
            'starter_code' => <<<'PHP'
                <?php

                interface StockObserver
                {
                    public function stockChanged(string $title, int $copies): void;
                }

                class StockLevel
                {
                    private int $copies = 0;

                    public function __construct(private string $title) {}

                    public function set(int $copies): void
                    {
                        $this->copies = $copies;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface StockObserver
                {
                    public function stockChanged(string $title, int $copies): void;
                }

                class StockLevel
                {
                    private int $copies = 0;
                    private array $observers = [];

                    public function __construct(private string $title) {}

                    public function attach(StockObserver $observer): void
                    {
                        $this->observers[] = $observer;
                    }

                    public function set(int $copies): void
                    {
                        $this->copies = $copies;
                        foreach ($this->observers as $observer) {
                            $observer->stockChanged($this->title, $copies);
                        }
                    }
                }

                class LowStockAlert implements StockObserver
                {
                    public array $alerts = [];

                    public function stockChanged(string $title, int $copies): void
                    {
                        if ($copies < 3) {
                            $this->alerts[] = "Low stock: $title ($copies left)";
                        }
                    }
                }
                PHP,
            'hint' => 'Keep observers in an array; `set()` loops over them. The alert checks `$copies < 3` before recording.',
            'checks' => [
                ['label' => 'Low levels raise alerts', 'code' => "\$alert = new LowStockAlert();\n\$stock = new StockLevel('Dune');\n\$stock->attach(\$alert);\nforeach ([5, 2, 4, 0] as \$n) { \$stock->set(\$n); }\nreturn expect_same(\$alert->alerts, ['Low stock: Dune (2 left)', 'Low stock: Dune (0 left)']);"],
                ['label' => 'Every observer is told', 'code' => <<<'PHP'
                    $seen = [];
                    $spy = new class($seen) implements StockObserver {
                        public function __construct(private array &$seen) {}
                        public function stockChanged(string $title, int $copies): void { $this->seen[] = "$title=$copies"; }
                    };
                    $stock = new StockLevel('Emma');
                    $stock->attach(new LowStockAlert());
                    $stock->attach($spy);
                    $stock->set(7);
                    return expect_same($seen, ['Emma=7']);
                    PHP],
            ],
        ],
        [
            'title' => 'An event dispatcher',
            'prompt' => 'Write `EventBus` with `on(string $event, Closure $listener): void`, `emit(string $event, mixed $payload = null): int` (calls every listener for that event with the payload, in order, and returns how many ran) and `off(string $event): void` (removes all listeners for that event).',
            'starter_code' => <<<'PHP'
                <?php

                class EventBus
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class EventBus
                {
                    private array $listeners = [];

                    public function on(string $event, Closure $listener): void
                    {
                        $this->listeners[$event][] = $listener;
                    }

                    public function emit(string $event, mixed $payload = null): int
                    {
                        $listeners = $this->listeners[$event] ?? [];
                        foreach ($listeners as $listener) {
                            $listener($payload);
                        }

                        return count($listeners);
                    }

                    public function off(string $event): void
                    {
                        unset($this->listeners[$event]);
                    }
                }
                PHP,
            'hint' => 'Store listeners per event: `$this->listeners[$event][] = $listener;`. `emit()` loops over `$this->listeners[$event] ?? []`.',
            'checks' => [
                ['label' => 'Listeners get the payload, in order', 'code' => "\$log = [];\n\$bus = new EventBus();\n\$bus->on('sale', function (\$p) use (&\$log) { \$log[] = \"a:\$p\"; });\n\$bus->on('sale', function (\$p) use (&\$log) { \$log[] = \"b:\$p\"; });\n\$count = \$bus->emit('sale', 'Dune');\nreturn expect_same([\$count, \$log], [2, ['a:Dune', 'b:Dune']]);"],
                ['label' => 'Unknown events run nothing', 'code' => "return expect_same((new EventBus())->emit('nothing'), 0);"],
                ['label' => 'off() removes listeners', 'code' => "\$bus = new EventBus();\n\$bus->on('sale', fn () => null);\n\$bus->off('sale');\nreturn expect_same(\$bus->emit('sale'), 0);"],
            ],
        ],
        [
            'title' => 'One failure shouldn\'t stop the rest',
            'prompt' => "`Notifier::notify()` calls each observer, but if one throws, the rest never run. Change it so every observer always runs: catch each observer's exception, collect its message, and return the list of error messages (empty if none failed).",
            'starter_code' => <<<'PHP'
                <?php

                interface Observer
                {
                    public function update(string $event): void;
                }

                class Notifier
                {
                    private array $observers = [];

                    public function attach(Observer $observer): void
                    {
                        $this->observers[] = $observer;
                    }

                    public function notify(string $event): array
                    {
                        foreach ($this->observers as $observer) {
                            $observer->update($event);
                        }

                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Observer
                {
                    public function update(string $event): void;
                }

                class Notifier
                {
                    private array $observers = [];

                    public function attach(Observer $observer): void
                    {
                        $this->observers[] = $observer;
                    }

                    public function notify(string $event): array
                    {
                        $errors = [];
                        foreach ($this->observers as $observer) {
                            try {
                                $observer->update($event);
                            } catch (Throwable $e) {
                                $errors[] = $e->getMessage();
                            }
                        }

                        return $errors;
                    }
                }
                PHP,
            'hint' => 'Wrap each `update()` call in its own `try`/`catch` inside the loop.',
            'checks' => [
                ['label' => 'Every observer runs, and errors are collected', 'code' => <<<'PHP'
                    $ran = [];
                    $make = function (string $name, bool $fails) use (&$ran) {
                        return new class($name, $fails, $ran) implements Observer {
                            public function __construct(private string $name, private bool $fails, private array &$ran) {}
                            public function update(string $event): void
                            {
                                $this->ran[] = $this->name;
                                if ($this->fails) throw new RuntimeException("{$this->name} failed");
                            }
                        };
                    };
                    $notifier = new Notifier();
                    $notifier->attach($make('a', false));
                    $notifier->attach($make('b', true));
                    $notifier->attach($make('c', false));
                    $errors = $notifier->notify('sale');
                    return expect_same([$ran, $errors], [['a', 'b', 'c'], ['b failed']]);
                    PHP],
                ['label' => 'No failures, no errors', 'code' => "return expect_same((new Notifier())->notify('sale'), []);"],
            ],
        ],
        [
            'title' => 'A queue of commands',
            'prompt' => "Write `CommandQueue` with `push(Command \$command): void` and `runAll(): array`, which executes the queued commands in the order they were pushed, returns their results, and empties the queue. Then write the command `ApplyDiscount`, taking a title and a percentage, whose `execute()` returns `'10% off Dune'`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Command
                {
                    public function execute(): string;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Command
                {
                    public function execute(): string;
                }

                class ApplyDiscount implements Command
                {
                    public function __construct(private string $title, private int $percent) {}

                    public function execute(): string
                    {
                        return "{$this->percent}% off {$this->title}";
                    }
                }

                class CommandQueue
                {
                    private array $commands = [];

                    public function push(Command $command): void
                    {
                        $this->commands[] = $command;
                    }

                    public function runAll(): array
                    {
                        $results = array_map(fn (Command $command) => $command->execute(), $this->commands);
                        $this->commands = [];

                        return $results;
                    }
                }
                PHP,
            'hint' => 'Store commands in an array. `runAll()` maps each to `execute()`, then empties the array.',
            'checks' => [
                ['label' => 'Commands run in order', 'code' => "\$queue = new CommandQueue();\n\$queue->push(new ApplyDiscount('Dune', 10));\n\$queue->push(new ApplyDiscount('Emma', 25));\nreturn expect_same(\$queue->runAll(), ['10% off Dune', '25% off Emma']);"],
                ['label' => 'The queue is emptied', 'code' => "\$queue = new CommandQueue();\n\$queue->push(new ApplyDiscount('Dune', 10));\n\$queue->runAll();\nreturn expect_same(\$queue->runAll(), []);"],
            ],
        ],
        [
            'title' => 'Undo the price changes',
            'prompt' => "Write the undoable command `SetPrice`, taking a `PriceList`, a title and a new price. `execute()` remembers the old price and sets the new one; `undo()` puts the old price back. Then write `Editor` with `run(UndoableCommand)` and `undo()`, which undoes the most recent command (doing nothing when there's none).",
            'starter_code' => <<<'PHP'
                <?php

                interface UndoableCommand
                {
                    public function execute(): void;
                    public function undo(): void;
                }

                class PriceList
                {
                    public array $prices = ['Dune' => 19.99, 'Emma' => 7.99];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface UndoableCommand
                {
                    public function execute(): void;
                    public function undo(): void;
                }

                class PriceList
                {
                    public array $prices = ['Dune' => 19.99, 'Emma' => 7.99];
                }

                class SetPrice implements UndoableCommand
                {
                    private ?float $old = null;

                    public function __construct(private PriceList $list, private string $title, private float $price) {}

                    public function execute(): void
                    {
                        $this->old = $this->list->prices[$this->title];
                        $this->list->prices[$this->title] = $this->price;
                    }

                    public function undo(): void
                    {
                        $this->list->prices[$this->title] = $this->old;
                    }
                }

                class Editor
                {
                    private array $history = [];

                    public function run(UndoableCommand $command): void
                    {
                        $command->execute();
                        $this->history[] = $command;
                    }

                    public function undo(): void
                    {
                        array_pop($this->history)?->undo();
                    }
                }
                PHP,
            'hint' => '`execute()` stores `$this->old` before changing the price. `Editor` keeps a history; `undo()` pops the last command and calls its `undo()`.',
            'checks' => [
                ['label' => 'Changes and one undo', 'code' => "\$list = new PriceList();\n\$editor = new Editor();\n\$editor->run(new SetPrice(\$list, 'Dune', 9.99));\n\$editor->run(new SetPrice(\$list, 'Dune', 4.99));\n\$editor->undo();\nreturn expect_same(\$list->prices['Dune'], 9.99);"],
                ['label' => 'Undo everything, and then some', 'code' => "\$list = new PriceList();\n\$editor = new Editor();\n\$editor->run(new SetPrice(\$list, 'Emma', 5.0));\n\$editor->run(new SetPrice(\$list, 'Dune', 9.99));\n\$editor->undo();\n\$editor->undo();\n\$editor->undo();\nreturn expect_same(\$list->prices, ['Dune' => 19.99, 'Emma' => 7.99]);"],
            ],
        ],
        [
            'title' => 'Log every command',
            'prompt' => "Write `LoggingInvoker` with `run(Command \$command): string`, which executes the command and returns its result, and keeps a public `\$log` of lines like `'SendReminder: Reminder sent to Ben'` (the command's short class name, a colon, and the result). Write the command `SendReminder` (taking a name) too.",
            'starter_code' => <<<'PHP'
                <?php

                interface Command
                {
                    public function execute(): string;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Command
                {
                    public function execute(): string;
                }

                class SendReminder implements Command
                {
                    public function __construct(private string $name) {}

                    public function execute(): string
                    {
                        return "Reminder sent to {$this->name}";
                    }
                }

                class LoggingInvoker
                {
                    public array $log = [];

                    public function run(Command $command): string
                    {
                        $result = $command->execute();
                        $this->log[] = (new ReflectionClass($command))->getShortName() . ": $result";

                        return $result;
                    }
                }
                PHP,
            'hint' => '`(new ReflectionClass($command))->getShortName()` gives the class name without any namespace (`$command::class` is fine here too).',
            'checks' => [
                ['label' => 'Results are returned and logged', 'code' => "\$invoker = new LoggingInvoker();\n\$result = \$invoker->run(new SendReminder('Ben'));\n\$invoker->run(new SendReminder('Cy'));\nreturn expect_same([\$result, \$invoker->log], ['Reminder sent to Ben', ['SendReminder: Reminder sent to Ben', 'SendReminder: Reminder sent to Cy']]);"],
                ['label' => 'Any command can be logged', 'code' => <<<'PHP'
                    $invoker = new LoggingInvoker();
                    $invoker->run(new class implements Command { public function execute(): string { return 'ok'; } });
                    return (count($invoker->log) === 1 && str_ends_with($invoker->log[0], ': ok')) ?: 'Log any command as "Name: result".';
                    PHP],
            ],
        ],
    ],
];
