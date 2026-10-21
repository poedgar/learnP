## Later, somewhere else

Some work is too slow to do while a visitor waits: sending email, resizing cover images, calling a
payment provider, building a report. Laravel moves it onto a **queue**. The request only writes a
small note, "send the receipt for order 17", and returns at once; a separate long-running process,
the **worker** (`php artisan queue:work`), picks notes up and does the work.

```laravel
class SendReceipt implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Order $order) {}

    public function handle(Mailer $mailer): void
    {
        $mailer->to($this->order->email)->send(new Receipt($this->order));
    }
}

SendReceipt::dispatch($order);              // as soon as a worker is free
SendReceipt::dispatch($order)->delay(60);   // not before a minute from now
```

## A job is data

The worker is a different process, possibly on a different server, so the job has to travel as
text. Laravel **serializes** the job object into a string, stores it (in a database table, Redis,
Amazon SQS...) and the worker unserializes it back into an object and calls `handle()`. PHP's
`serialize()` and `unserialize()` do the heavy lifting:

```php
class ResizeCover
{
    public function __construct(public int $bookId, public int $width) {}

    public function handle(): string
    {
        return "Resized cover of book {$this->bookId} to {$this->width}px";
    }
}

$queue = [];
$queue[] = serialize(new ResizeCover(17, 300));

echo $queue[0] . PHP_EOL;

$job = unserialize(array_shift($queue));
echo $job->handle();
```

This is why a job's properties must be things that serialize well: numbers, strings, arrays, other
plain objects. A closure can't be serialized, and a database connection or open file would be
meaningless in another process.

## Models in jobs

Serializing a whole Eloquent model would freeze its data at dispatch time; by the time the job runs,
the order may have changed. The `SerializesModels` trait (part of `Queueable`) stores only the
model's class and id, and the worker loads a fresh copy from the database before `handle()`. PHP
lets a class control its own serialization with `__serialize()` and `__unserialize()`:

```php
class Temperature
{
    public function __construct(public float $celsius) {}

    public function __serialize(): array
    {
        return ['f' => $this->celsius * 9 / 5 + 32];
    }

    public function __unserialize(array $data): void
    {
        $this->celsius = ($data['f'] - 32) * 5 / 9;
    }
}

$text = serialize(new Temperature(20.0));
echo $text . PHP_EOL;
echo unserialize($text)->celsius;
```

## When jobs fail

A job that throws isn't lost. The worker counts **attempts**: if the job may be tried again (its
`$tries` allows it) it goes back on the queue, usually after a **backoff** delay; when it runs out of
tries, the worker calls the job's `failed()` method and records it in the `failed_jobs` table, where
`php artisan queue:retry` can bring it back after you've fixed the bug.

Because a job may run more than once, `handle()` should be **idempotent**: running it twice should
do no more harm than running it once. Check "has this receipt already been sent?" before sending.

## Delays

A delayed job is stored with an "available at" time. The worker skips jobs whose time hasn't come,
which is also how retry backoff works. Order still matters: among the jobs that are ready, the oldest
goes first.

## Chains and batches

Sometimes jobs depend on each other. A **chain** runs jobs one after another, each only if the one
before succeeded; Laravel stores the rest of the chain inside the current job and dispatches the next
one when it finishes:

```laravel
Bus::chain([
    new ProcessPayment($order),
    new ReserveStock($order),
    new SendReceipt($order),
])->dispatch();
```

A **batch** runs many jobs at the same time (across workers) and tracks them as a group, with
callbacks for when they're all done:

```laravel
Bus::batch($books->map(fn ($book) => new ResizeCover($book->id, 300)))
    ->then(fn (Batch $batch) => Log::info('All covers resized'))
    ->catch(fn (Batch $batch, Throwable $e) => Log::error('A cover failed'))
    ->finally(fn (Batch $batch) => Cache::forget('resizing'))
    ->dispatch();
```

`$batch->progress()` gives the percentage finished, which is handy for a progress bar.

## Running the worker

The worker is a loop: pop a job, run it, record the result, repeat; when the queue is empty, sleep a
moment. It keeps the application booted between jobs, so it's fast, but it also means code changes
aren't picked up until the worker restarts, which is why deployments run `php artisan queue:restart`.
