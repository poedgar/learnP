<?php

// Lesson 9 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'queues-and-jobs',
    'title' => 'Queues and jobs',
    'summary' => 'How queued jobs work: serialized jobs and a worker, retries and `failed()`, delays, models serialized by id, chains that stop on failure, and batches with progress and callbacks.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try queueing a third job.
        class SendReceipt
        {
            public function __construct(public string $orderId) {}

            public function handle(): void
            {
                echo "Receipt sent for {$this->orderId}" . PHP_EOL;
            }
        }

        $queue = [];
        $queue[] = serialize(new SendReceipt('A-17'));
        $queue[] = serialize(new SendReceipt('A-18'));

        echo count($queue) . ' jobs waiting' . PHP_EOL;

        while ($queue !== []) {
            unserialize(array_shift($queue))->handle();
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Why put sending a receipt email on a queue?',
            'options' => [
                'Queued email is cheaper',
                'So the request can return immediately while a worker sends the email in the background',
                'Because email can\'t be sent from a controller',
                'To send it to more people',
            ],
            'correct_option' => 1,
            'explanation' => 'Slow work moves out of the request, so the visitor isn\'t kept waiting.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Job
                {
                    public function __construct(public string $orderId, public int $attempts = 0) {}
                }

                $stored = serialize(new Job('A-17'));
                $copy = unserialize($stored);
                $copy->attempts++;

                $again = unserialize($stored);

                echo get_class($copy) . ' ' . $copy->orderId . ' ' . $copy->attempts . ' ' . $again->attempts;
                PHP,
            'options' => ['`Job A-17 1 1`', '`Job A-17 1 0`', '`stdClass A-17 1 0`', '`Job A-17 0 0`'],
            'correct_option' => 1,
            'explanation' => 'Unserializing gives a new object of the same class each time; changing one copy doesn\'t change the stored string.',
        ],
        [
            'question' => 'Why can\'t a queued job keep a closure in one of its properties?',
            'options' => [
                'Closures are too large',
                'Closures can\'t be serialized, and the job must travel as text to the worker',
                'Jobs can\'t have properties',
                'Closures are only allowed in routes',
            ],
            'correct_option' => 1,
            'explanation' => '`serialize()` throws for a `Closure`. Keep plain data, like ids and strings, in jobs.',
        ],
        [
            'question' => 'What does `SerializesModels` do with an Eloquent model in a job\'s property?',
            'options' => [
                'Serializes all its attributes as they were at dispatch time',
                'Stores only its class and id, and loads a fresh copy when the worker runs the job',
                'Removes it from the job',
                'Saves it to the database first',
            ],
            'correct_option' => 1,
            'explanation' => 'The worker sees the model as it is now, not as it was when the job was dispatched.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Price
                {
                    public function __construct(public int $pence) {}

                    public function __serialize(): array
                    {
                        return ['pounds' => $this->pence / 100];
                    }

                    public function __unserialize(array $data): void
                    {
                        $this->pence = (int) round($data['pounds'] * 100);
                    }
                }

                $text = serialize(new Price(1250));

                echo (str_contains($text, 'pounds') ? 'pounds ' : 'pence ') . unserialize($text)->pence;
                PHP,
            'options' => ['`pence 1250`', '`pounds 12.5`', '`pounds 1250`', '`pence 12.5`'],
            'correct_option' => 2,
            'explanation' => '`__serialize()` chooses what\'s stored (pounds), and `__unserialize()` rebuilds the object from it (pence again).',
        ],
        [
            'question' => 'A job has `public int $tries = 3` and throws every time. What happens?',
            'options' => [
                'It is retried forever',
                'It runs three times in all, then its `failed()` method is called and it\'s recorded as failed',
                'It runs once and is deleted',
                'The worker crashes',
            ],
            'correct_option' => 1,
            'explanation' => 'After the last allowed attempt the job is given up on, and `failed()` gives you a chance to clean up or alert someone.',
        ],
        [
            'question' => 'Why should a job\'s `handle()` method be idempotent?',
            'options' => [
                'Laravel requires it to return nothing',
                'Because a job can run more than once (after a retry or a crash), and running it again shouldn\'t do extra harm',
                'So it runs faster',
                'Because jobs can\'t use the database',
            ],
            'correct_option' => 1,
            'explanation' => 'Charging a card twice is worse than an error. Check whether the work is already done before doing it.',
        ],
        [
            'question' => 'In `Bus::chain([new ProcessPayment($o), new ReserveStock($o), new SendReceipt($o)])`, `ReserveStock` throws. What happens to `SendReceipt`?',
            'options' => [
                'It runs anyway',
                'It never runs, because a chain stops at the first job that fails',
                'It runs first instead',
                'It runs at the same time as `ReserveStock`',
            ],
            'correct_option' => 1,
            'explanation' => 'A chain only dispatches the next job after the current one succeeds.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Push and work',
            'prompt' => 'Finish `Queue` and `Worker`. `Queue::push(object $job)` stores the job **serialized**, and `pop()` takes the oldest one off and unserializes it, or returns `null` when empty. `Worker::work(Queue $queue)` runs jobs (`handle()`) until the queue is empty and returns how many it ran.',
            'starter_code' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];

                    public function push(object $job): void
                    {
                    }

                    public function pop(): ?object
                    {
                        return null;
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): int
                    {
                        return 0;
                    }
                }

                class SendReceipt
                {
                    public static array $sent = [];

                    public function __construct(public string $orderId) {}

                    public function handle(): void
                    {
                        self::$sent[] = $this->orderId;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];

                    public function push(object $job): void
                    {
                        $this->stored[] = serialize($job);
                    }

                    public function pop(): ?object
                    {
                        $payload = array_shift($this->stored);

                        return $payload === null ? null : unserialize($payload);
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): int
                    {
                        $count = 0;
                        while (($job = $queue->pop()) !== null) {
                            $job->handle();
                            $count++;
                        }

                        return $count;
                    }
                }

                class SendReceipt
                {
                    public static array $sent = [];

                    public function __construct(public string $orderId) {}

                    public function handle(): void
                    {
                        self::$sent[] = $this->orderId;
                    }
                }
                PHP,
            'hint' => '`push()` adds `serialize($job)` to `$stored`; `pop()` uses `array_shift()` (oldest first). The worker loops while `pop()` returns a job.',
            'checks' => [
                ['label' => 'Jobs are stored as strings', 'code' => "\$q = new Queue();\n\$q->push(new SendReceipt('A-17'));\nreturn (count(\$q->stored) === 1 && is_string(\$q->stored[0])) ?: 'push() should store one serialized string.';"],
                ['label' => 'Oldest first, then null', 'code' => "\$q = new Queue();\n\$q->push(new SendReceipt('first'));\n\$q->push(new SendReceipt('second'));\nreturn expect_same([\$q->pop()?->orderId, \$q->pop()?->orderId, \$q->pop()], ['first', 'second', null]);"],
                ['label' => 'The worker runs every job', 'code' => "SendReceipt::\$sent = [];\n\$q = new Queue();\nforeach (['A', 'B', 'C'] as \$id) { \$q->push(new SendReceipt(\$id)); }\n\$count = (new Worker())->work(\$q);\nreturn expect_same([\$count, SendReceipt::\$sent, \$q->stored], [3, ['A', 'B', 'C'], []]);"],
                ['label' => 'The job is a copy, not the original', 'code' => "\$q = new Queue();\n\$job = new SendReceipt('A');\n\$q->push(\$job);\n\$job->orderId = 'changed after dispatch';\nreturn expect_same(\$q->pop()?->orderId, 'A');"],
            ],
        ],
        [
            'title' => 'Try, try again',
            'prompt' => 'Make the worker retry. The queue stores each job with its attempt count (starting at 0). `Worker::work()` increments the attempts before running a job. If `handle()` throws and the job\'s `$tries` (1 if it has no such property) allows another attempt, the job is pushed back with its new count; otherwise its `failed(Throwable $e)` method is called, if it has one, and the job goes into `$queue->failed`. Return how many jobs succeeded.',
            'starter_code' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];
                    public array $failed = [];

                    public function push(object $job, int $attempts = 0): void
                    {
                        $this->stored[] = ['job' => serialize($job), 'attempts' => $attempts];
                    }

                    public function pop(): ?array
                    {
                        $entry = array_shift($this->stored);

                        return $entry === null ? null : ['job' => unserialize($entry['job']), 'attempts' => $entry['attempts']];
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): int
                    {
                        $succeeded = 0;
                        while (($entry = $queue->pop()) !== null) {
                            $entry['job']->handle();
                            $succeeded++;
                        }

                        return $succeeded;
                    }
                }

                class ChargeCard
                {
                    public int $tries = 3;
                    public static int $calls = 0;
                    public static ?string $failedWith = null;

                    public function __construct(public int $failTimes) {}

                    public function handle(): void
                    {
                        self::$calls++;
                        if (self::$calls <= $this->failTimes) {
                            throw new RuntimeException('Card provider is down');
                        }
                    }

                    public function failed(Throwable $e): void
                    {
                        self::$failedWith = $e->getMessage();
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];
                    public array $failed = [];

                    public function push(object $job, int $attempts = 0): void
                    {
                        $this->stored[] = ['job' => serialize($job), 'attempts' => $attempts];
                    }

                    public function pop(): ?array
                    {
                        $entry = array_shift($this->stored);

                        return $entry === null ? null : ['job' => unserialize($entry['job']), 'attempts' => $entry['attempts']];
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): int
                    {
                        $succeeded = 0;
                        while (($entry = $queue->pop()) !== null) {
                            $job = $entry['job'];
                            $attempts = $entry['attempts'] + 1;

                            try {
                                $job->handle();
                                $succeeded++;
                            } catch (Throwable $e) {
                                if ($attempts < ($job->tries ?? 1)) {
                                    $queue->push($job, $attempts);
                                } else {
                                    if (method_exists($job, 'failed')) {
                                        $job->failed($e);
                                    }
                                    $queue->failed[] = $job;
                                }
                            }
                        }

                        return $succeeded;
                    }
                }

                class ChargeCard
                {
                    public int $tries = 3;
                    public static int $calls = 0;
                    public static ?string $failedWith = null;

                    public function __construct(public int $failTimes) {}

                    public function handle(): void
                    {
                        self::$calls++;
                        if (self::$calls <= $this->failTimes) {
                            throw new RuntimeException('Card provider is down');
                        }
                    }

                    public function failed(Throwable $e): void
                    {
                        self::$failedWith = $e->getMessage();
                    }
                }
                PHP,
            'hint' => 'Wrap `handle()` in `try`/`catch (Throwable $e)`. `$job->tries ?? 1` reads the property or falls back to 1. Push the job back with the new attempt count while `$attempts < $tries`.',
            'checks' => [
                ['label' => 'A flaky job succeeds on a retry', 'code' => "ChargeCard::\$calls = 0;\nChargeCard::\$failedWith = null;\n\$q = new Queue();\n\$q->push(new ChargeCard(2));\n\$ok = (new Worker())->work(\$q);\nreturn expect_same([\$ok, ChargeCard::\$calls, count(\$q->failed), ChargeCard::\$failedWith], [1, 3, 0, null]);"],
                ['label' => 'After its last try it fails', 'code' => "ChargeCard::\$calls = 0;\nChargeCard::\$failedWith = null;\n\$q = new Queue();\n\$q->push(new ChargeCard(10));\n\$ok = (new Worker())->work(\$q);\nreturn expect_same([\$ok, ChargeCard::\$calls, count(\$q->failed), ChargeCard::\$failedWith], [0, 3, 1, 'Card provider is down']);"],
                ['label' => 'No $tries means one attempt', 'code' => "if (! class_exists('AlwaysFails', false)) eval('class AlwaysFails { public function handle(): void { throw new Exception(\"no\"); } }');\n\$q = new Queue();\n\$q->push(new AlwaysFails());\n(new Worker())->work(\$q);\nreturn expect_same(count(\$q->failed), 1);"],
                ['label' => 'Other jobs keep running', 'code' => "ChargeCard::\$calls = 0;\n\$q = new Queue();\n\$q->push(new ChargeCard(10));\nif (! class_exists('DoesNothing', false)) eval('class DoesNothing { public function handle(): void {} }');\n\$q->push(new DoesNothing());\nreturn expect_same((new Worker())->work(\$q), 1);"],
            ],
        ],
        [
            'title' => 'Not before',
            'prompt' => 'Add delays. `push(object $job, int $delay = 0, int $now = 0)` stores the job with `available_at = $now + $delay`. `pop(int $now)` returns the oldest job whose `available_at` is at or before `$now`, removing it, and leaves jobs that aren\'t ready where they are; it returns `null` if none is ready.',
            'starter_code' => <<<'PHP'
                <?php

                class Queue
                {
                    private array $stored = [];

                    public function push(object $job, int $delay = 0, int $now = 0): void
                    {
                        $this->stored[] = serialize($job);
                    }

                    public function pop(int $now): ?object
                    {
                        $payload = array_shift($this->stored);

                        return $payload === null ? null : unserialize($payload);
                    }

                    public function size(): int
                    {
                        return count($this->stored);
                    }
                }

                class Remind
                {
                    public function __construct(public string $message) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Queue
                {
                    private array $stored = [];

                    public function push(object $job, int $delay = 0, int $now = 0): void
                    {
                        $this->stored[] = ['job' => serialize($job), 'available_at' => $now + $delay];
                    }

                    public function pop(int $now): ?object
                    {
                        foreach ($this->stored as $index => $entry) {
                            if ($entry['available_at'] <= $now) {
                                array_splice($this->stored, $index, 1);

                                return unserialize($entry['job']);
                            }
                        }

                        return null;
                    }

                    public function size(): int
                    {
                        return count($this->stored);
                    }
                }

                class Remind
                {
                    public function __construct(public string $message) {}
                }
                PHP,
            'hint' => 'Store `[\'job\' => ..., \'available_at\' => ...]`. In `pop()`, walk the list in order and take the first ready entry out with `array_splice()`, which also renumbers the rest.',
            'checks' => [
                ['label' => 'Delayed jobs wait', 'code' => "\$q = new Queue();\n\$q->push(new Remind('later'), 60, 1000);\nreturn expect_same([\$q->pop(1059), \$q->pop(1060)?->message], [null, 'later']);"],
                ['label' => 'Ready jobs jump ahead of waiting ones', 'code' => "\$q = new Queue();\n\$q->push(new Remind('in a minute'), 60, 0);\n\$q->push(new Remind('now'), 0, 0);\nreturn expect_same([\$q->pop(10)?->message, \$q->size()], ['now', 1]);"],
                ['label' => 'Among ready jobs, oldest first', 'code' => "\$q = new Queue();\n\$q->push(new Remind('a'), 5, 0);\n\$q->push(new Remind('b'), 0, 0);\n\$q->push(new Remind('c'), 1, 0);\nreturn expect_same([\$q->pop(100)?->message, \$q->pop(100)?->message, \$q->pop(100)?->message, \$q->pop(100)], ['a', 'b', 'c', null]);"],
            ],
        ],
        [
            'title' => 'Serialize models by id',
            'prompt' => 'Write the trait `SerializesModels`. Its `__serialize()` returns the object\'s properties, but replaces each property holding a `Model` with `[\'model\' => ClassName, \'id\' => id]`. Its `__unserialize()` restores the properties, loading each stored model fresh with `ClassName::find($id)`. Use it in `SendReceipt`.',
            'starter_code' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public static array $table = [];

                    final public function __construct(public int $id, public array $attributes) {}

                    public static function find(int $id): ?static
                    {
                        return isset(static::$table[static::class][$id])
                            ? new static($id, static::$table[static::class][$id])
                            : null;
                    }
                }

                class Order extends Model {}

                trait SerializesModels
                {
                }

                class SendReceipt
                {
                    public function __construct(public Order $order, public string $channel = 'email') {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Model
                {
                    public static array $table = [];

                    final public function __construct(public int $id, public array $attributes) {}

                    public static function find(int $id): ?static
                    {
                        return isset(static::$table[static::class][$id])
                            ? new static($id, static::$table[static::class][$id])
                            : null;
                    }
                }

                class Order extends Model {}

                trait SerializesModels
                {
                    public function __serialize(): array
                    {
                        $data = [];
                        foreach (get_object_vars($this) as $name => $value) {
                            $data[$name] = $value instanceof Model
                                ? ['model' => $value::class, 'id' => $value->id]
                                : $value;
                        }

                        return $data;
                    }

                    public function __unserialize(array $data): void
                    {
                        foreach ($data as $name => $value) {
                            if (is_array($value) && isset($value['model'], $value['id']) && is_subclass_of($value['model'], Model::class)) {
                                $value = $value['model']::find($value['id']);
                            }
                            $this->$name = $value;
                        }
                    }
                }

                class SendReceipt
                {
                    use SerializesModels;

                    public function __construct(public Order $order, public string $channel = 'email') {}
                }
                PHP,
            'hint' => '`get_object_vars($this)` lists the properties. In `__unserialize()`, recognise stored models by their `model` and `id` keys and call `$value[\'model\']::find($value[\'id\'])`.',
            'checks' => [
                ['label' => 'Only the class and id are stored', 'code' => "Model::\$table[Order::class][17] = ['total' => 25, 'note' => str_repeat('x', 500)];\n\$text = serialize(new SendReceipt(Order::find(17)));\nreturn (! str_contains(\$text, str_repeat('x', 50)) && str_contains(\$text, 'Order')) ?: 'The whole order was serialized.';"],
                ['label' => 'The model is loaded fresh', 'code' => "Model::\$table[Order::class][17] = ['total' => 25];\n\$text = serialize(new SendReceipt(Order::find(17)));\nModel::\$table[Order::class][17] = ['total' => 30];\n\$job = unserialize(\$text);\nreturn expect_same([\$job->order instanceof Order, \$job->order->attributes['total'] ?? null], [true, 30]);"],
                ['label' => 'Other properties survive', 'code' => "Model::\$table[Order::class][5] = ['total' => 1];\n\$job = unserialize(serialize(new SendReceipt(Order::find(5), 'sms')));\nreturn expect_same([\$job->channel, \$job->order->id], ['sms', 5]);"],
            ],
        ],
        [
            'title' => 'A chain',
            'prompt' => 'Write `Bus::chain(array $jobs, Queue $queue)`. It pushes the first job with the rest of the jobs stored in its public `$chained` property. In `Worker::work()`, after a job\'s `handle()` succeeds, if it has chained jobs, push the next one carrying the remaining chain. If a job throws, the exception is recorded in `$queue->failed` and the rest of its chain is dropped.',
            'starter_code' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];
                    public array $failed = [];

                    public function push(object $job): void
                    {
                        $this->stored[] = serialize($job);
                    }

                    public function pop(): ?object
                    {
                        $payload = array_shift($this->stored);

                        return $payload === null ? null : unserialize($payload);
                    }
                }

                class Bus
                {
                    public static function chain(array $jobs, Queue $queue): void
                    {
                        foreach ($jobs as $job) {
                            $queue->push($job);
                        }
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): void
                    {
                        while (($job = $queue->pop()) !== null) {
                            try {
                                $job->handle();
                            } catch (Throwable $e) {
                                $queue->failed[] = $e->getMessage();
                            }
                        }
                    }
                }

                class Step
                {
                    public static array $log = [];
                    public array $chained = [];

                    public function __construct(public string $name, public bool $fails = false) {}

                    public function handle(): void
                    {
                        if ($this->fails) {
                            throw new RuntimeException("{$this->name} failed");
                        }
                        self::$log[] = $this->name;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Queue
                {
                    public array $stored = [];
                    public array $failed = [];

                    public function push(object $job): void
                    {
                        $this->stored[] = serialize($job);
                    }

                    public function pop(): ?object
                    {
                        $payload = array_shift($this->stored);

                        return $payload === null ? null : unserialize($payload);
                    }
                }

                class Bus
                {
                    public static function chain(array $jobs, Queue $queue): void
                    {
                        $first = array_shift($jobs);
                        if ($first === null) {
                            return;
                        }

                        $first->chained = $jobs;
                        $queue->push($first);
                    }
                }

                class Worker
                {
                    public function work(Queue $queue): void
                    {
                        while (($job = $queue->pop()) !== null) {
                            try {
                                $job->handle();
                            } catch (Throwable $e) {
                                $queue->failed[] = $e->getMessage();
                                continue;
                            }

                            if (! empty($job->chained)) {
                                Bus::chain($job->chained, $queue);
                            }
                        }
                    }
                }

                class Step
                {
                    public static array $log = [];
                    public array $chained = [];

                    public function __construct(public string $name, public bool $fails = false) {}

                    public function handle(): void
                    {
                        if ($this->fails) {
                            throw new RuntimeException("{$this->name} failed");
                        }
                        self::$log[] = $this->name;
                    }
                }
                PHP,
            'hint' => 'Only the first job goes on the queue, carrying the others in `$chained`. After a successful `handle()`, calling `Bus::chain($job->chained, $queue)` pushes the next one with the rest.',
            'checks' => [
                ['label' => 'Jobs run in order', 'code' => "Step::\$log = [];\n\$q = new Queue();\nBus::chain([new Step('pay'), new Step('reserve'), new Step('receipt')], \$q);\n(new Worker())->work(\$q);\nreturn expect_same(Step::\$log, ['pay', 'reserve', 'receipt']);"],
                ['label' => 'Only one job is queued at a time', 'code' => "\$q = new Queue();\nBus::chain([new Step('a'), new Step('b'), new Step('c')], \$q);\nreturn expect_same(count(\$q->stored), 1);"],
                ['label' => 'A failure stops the chain', 'code' => "Step::\$log = [];\n\$q = new Queue();\nBus::chain([new Step('pay'), new Step('reserve', fails: true), new Step('receipt')], \$q);\n(new Worker())->work(\$q);\nreturn expect_same([Step::\$log, \$q->failed, \$q->stored], [['pay'], ['reserve failed'], []]);"],
                ['label' => 'Separate chains don\'t affect each other', 'code' => "Step::\$log = [];\n\$q = new Queue();\nBus::chain([new Step('x1', fails: true), new Step('x2')], \$q);\nBus::chain([new Step('y1'), new Step('y2')], \$q);\n(new Worker())->work(\$q);\nreturn expect_same(Step::\$log, ['y1', 'y2']);"],
            ],
        ],
        [
            'title' => 'A batch',
            'prompt' => 'Finish `Batch`. It\'s created with the number of jobs. `then()`, `catch()` and `finally()` register callbacks and return the batch. `recordSuccess()` and `recordFailure(Throwable $e)` count finished jobs; `progress()` is the percentage of jobs finished either way, rounded down to a whole number. `catch` callbacks run with the batch and the exception on the **first** failure only. When the last job finishes, `then` callbacks run if nothing failed, then `finally` callbacks run either way, each with the batch.',
            'starter_code' => <<<'PHP'
                <?php

                class Batch
                {
                    public int $processed = 0;
                    public int $failed = 0;

                    public function __construct(public int $total) {}

                    public function then(Closure $callback): static
                    {
                        return $this;
                    }

                    public function catch(Closure $callback): static
                    {
                        return $this;
                    }

                    public function finally(Closure $callback): static
                    {
                        return $this;
                    }

                    public function recordSuccess(): void
                    {
                        $this->processed++;
                    }

                    public function recordFailure(Throwable $e): void
                    {
                        $this->failed++;
                    }

                    public function progress(): int
                    {
                        return 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Batch
                {
                    public int $processed = 0;
                    public int $failed = 0;
                    private array $callbacks = ['then' => [], 'catch' => [], 'finally' => []];

                    public function __construct(public int $total) {}

                    public function then(Closure $callback): static
                    {
                        $this->callbacks['then'][] = $callback;
                        return $this;
                    }

                    public function catch(Closure $callback): static
                    {
                        $this->callbacks['catch'][] = $callback;
                        return $this;
                    }

                    public function finally(Closure $callback): static
                    {
                        $this->callbacks['finally'][] = $callback;
                        return $this;
                    }

                    public function recordSuccess(): void
                    {
                        $this->processed++;
                        $this->finishIfDone();
                    }

                    public function recordFailure(Throwable $e): void
                    {
                        $this->failed++;
                        if ($this->failed === 1) {
                            foreach ($this->callbacks['catch'] as $callback) {
                                $callback($this, $e);
                            }
                        }
                        $this->finishIfDone();
                    }

                    public function progress(): int
                    {
                        return $this->total === 0 ? 100 : intdiv(($this->processed + $this->failed) * 100, $this->total);
                    }

                    private function finishIfDone(): void
                    {
                        if ($this->processed + $this->failed !== $this->total) {
                            return;
                        }

                        if ($this->failed === 0) {
                            foreach ($this->callbacks['then'] as $callback) {
                                $callback($this);
                            }
                        }
                        foreach ($this->callbacks['finally'] as $callback) {
                            $callback($this);
                        }
                    }
                }
                PHP,
            'hint' => 'Keep the callbacks in arrays. After every recorded result, check whether `processed + failed` has reached `total`. `intdiv()` rounds the percentage down.',
            'checks' => [
                ['label' => 'Progress counts finished jobs', 'code' => "\$b = new Batch(3);\n\$b->recordSuccess();\n\$first = \$b->progress();\n\$b->recordFailure(new Exception('x'));\nreturn expect_same([\$first, \$b->progress()], [33, 66]);"],
                ['label' => 'then and finally after a clean run', 'code' => "\$log = new ArrayObject();\n\$b = (new Batch(2))->then(function (\$batch) use (\$log) { \$log[] = 'then'; })->catch(function () use (\$log) { \$log[] = 'catch'; })->finally(function (\$batch) use (\$log) { \$log[] = 'finally'; });\n\$b->recordSuccess();\nif (\$log->count() !== 0) return 'Callbacks ran before the batch finished.';\n\$b->recordSuccess();\nreturn expect_same(\$log->getArrayCopy(), ['then', 'finally']);"],
                ['label' => 'catch runs once, then never runs', 'code' => "\$log = new ArrayObject();\n\$b = (new Batch(3))->then(function () use (\$log) { \$log[] = 'then'; })->catch(function (\$batch, \$e) use (\$log) { \$log[] = 'catch:' . \$e->getMessage(); })->finally(function () use (\$log) { \$log[] = 'finally'; });\n\$b->recordFailure(new Exception('one'));\n\$b->recordSuccess();\n\$b->recordFailure(new Exception('two'));\nreturn expect_same(\$log->getArrayCopy(), ['catch:one', 'finally']);"],
                ['label' => 'Callbacks receive the batch', 'code' => "\$seen = null;\n\$b = (new Batch(1))->finally(function (\$batch) use (&\$seen) { \$seen = \$batch; });\n\$b->recordSuccess();\nreturn \$seen === \$b ?: 'finally() was not given the batch.';"],
            ],
        ],
    ],
];
