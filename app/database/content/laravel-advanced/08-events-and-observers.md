## Telling the rest of the app

When an order is placed, many things should follow: a receipt email, a stock update, a note for the
warehouse, a line in the analytics. Putting all of that in the checkout code ties checkout to every
one of those features. **Events** turn it around: checkout announces "an order was placed", and
each feature listens for that announcement on its own.

```laravel
class OrderPlaced
{
    public function __construct(public Order $order) {}
}

// Somewhere in checkout:
OrderPlaced::dispatch($order);   // or event(new OrderPlaced($order))

// In a service provider, or found automatically in app/Listeners:
Event::listen(OrderPlaced::class, SendReceipt::class);
Event::listen(OrderPlaced::class, UpdateStock::class);
```

This is the observer pattern, and Laravel's **event dispatcher** is the object in the middle.

## The dispatcher

A dispatcher keeps a list of listeners per event name. Dispatching calls each listener for that
name, in the order they were added, and collects what they return:

```php
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

$events = new Dispatcher();
$events->listen('order.placed', fn (string $id) => "receipt for $id");
$events->listen('order.placed', fn (string $id) => "stock updated for $id");

print_r($events->dispatch('order.placed', ['A-17']));
```

When the event is an **object**, like `new OrderPlaced($order)`, Laravel uses its class name as the
event name and passes the object itself to each listener. The object carries all the data, with
proper types, which is why class events are the usual choice.

## Stopping early

A listener can stop the event from reaching the listeners after it by returning `false`. And
sometimes you want an answer rather than a broadcast: `Event::until()` dispatches but stops at the
first listener that returns something other than `null`, and returns that value. Laravel uses it for
"may this happen?" questions.

## Wildcards

A listener can subscribe to many events at once with `*`: `Event::listen('order.*', ...)` hears
`order.placed`, `order.shipped` and so on. Because it may be any of them, a wildcard listener is
given the event's name as well as its data:

```laravel
Event::listen('order.*', function (string $eventName, array $data) {
    Log::info("Something happened to an order: $eventName");
});
```

## Listener classes

Listeners are usually classes with a `handle()` method, registered by class name. The dispatcher
creates the object (through the container, so it can have dependencies) only when the event fires:

```laravel
class SendReceipt
{
    public function __construct(private Mailer $mailer) {}

    public function handle(OrderPlaced $event): void
    {
        $this->mailer->to($event->order->email)->send(new Receipt($event->order));
    }
}
```

`Event::listen(OrderPlaced::class, [SendReceipt::class, 'handle'])` or the string
`'App\Listeners\SendReceipt@handle'` name a different method. A listener that implements
`ShouldQueue` isn't run at once; it's pushed onto a queue, which is next lesson's subject.

## Model events

Eloquent models fire events through their lifecycle: `retrieved`, `creating` and `created`,
`updating` and `updated`, `saving` and `saved` (for both), `deleting` and `deleted`. The
"-ing" events run *before* the database is touched, and if a listener returns `false`, the
operation is cancelled:

```php
$listeners = [
    'saving' => [fn (array $book) => $book['price'] >= 0],
    'saved' => [fn (array $book) => print("Saved {$book['title']}" . PHP_EOL)],
];

function save(array $book, array $listeners): bool
{
    foreach ($listeners['saving'] as $listener) {
        if ($listener($book) === false) {
            return false;
        }
    }

    // ...the insert or update would run here...

    foreach ($listeners['saved'] as $listener) {
        $listener($book);
    }

    return true;
}

var_dump(save(['title' => 'Dune', 'price' => 9.99], $listeners));
var_dump(save(['title' => 'Oops', 'price' => -1], $listeners));
```

## Observers

When a model has several such listeners, an **observer** groups them in one class, with a method
per event. `Book::observe(BookObserver::class)` registers every method whose name is a model event:

```laravel
class BookObserver
{
    public function creating(Book $book): void
    {
        $book->slug = Str::slug($book->title);
    }

    public function deleted(Book $book): void
    {
        Cache::forget("book:{$book->id}");
    }
}
```

## Events in tests

`Event::fake()` swaps the dispatcher for a fake that records events without running any listener,
so a test can check that checkout *announced* the order without sending real email:
`Event::assertDispatched(OrderPlaced::class)`. It's the facade swap from lesson 3 again.

Events make features easy to add and remove, but the flow of the program becomes less visible. Use
them where several independent things react to one fact, not to hide a single direct call.
