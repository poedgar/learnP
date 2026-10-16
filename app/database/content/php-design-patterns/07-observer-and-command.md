## Two ways to decouple

Both patterns in this lesson separate **who triggers** something from **what happens**:

- **Observer** lets many objects react when something happens, without the thing that happened knowing who they
  are.
- **Command** turns a request into an object, so it can be stored, queued, logged and undone.

## The problem: one class calling everyone

When an order is placed, an email goes out, stock is reduced and the sales report is updated. If `Order` calls
each of those itself, it has to know about all of them, and every new reaction means editing `Order`:

```php
class Mailer { public function confirm(string $id): void { echo "Email for $id\n"; } }
class Stock { public function reduce(string $id): void { echo "Stock reduced for $id\n"; } }

class OrderService
{
    public function __construct(private Mailer $mailer, private Stock $stock) {}

    public function place(string $id): void
    {
        echo "Placed $id\n";
        $this->mailer->confirm($id);
        $this->stock->reduce($id);
    }
}

(new OrderService(new Mailer(), new Stock()))->place('A-1');
```

## The Observer pattern

The **subject** (the thing being watched) keeps a list of **observers** and tells all of them when something
happens. It only knows they implement a small interface. Observers can be added and removed at run time:

```php
interface OrderObserver
{
    public function orderPlaced(string $id): void;
}

class OrderService
{
    private array $observers = [];

    public function attach(OrderObserver $observer): void
    {
        $this->observers[] = $observer;
    }

    public function place(string $id): void
    {
        echo "Placed $id\n";
        foreach ($this->observers as $observer) {
            $observer->orderPlaced($id);
        }
    }
}

class EmailOnOrder implements OrderObserver
{
    public function orderPlaced(string $id): void { echo "Email for $id\n"; }
}

class StockOnOrder implements OrderObserver
{
    public function orderPlaced(string $id): void { echo "Stock reduced for $id\n"; }
}

$orders = new OrderService();
$orders->attach(new EmailOnOrder());
$orders->attach(new StockOnOrder());
$orders->place('A-1');
```

Adding a loyalty-points reaction is a new observer class. `OrderService` never changes.

## Events and listeners

A common variation is an **event dispatcher**: one shared object where listeners register for named events, and
anything can dispatch an event with some data. Laravel and Symfony both work this way:

```php
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
$events->listen('order.placed', fn ($data) => print("Email {$data['customer']}\n"));
$events->listen('order.placed', fn ($data) => print("Points for {$data['customer']}\n"));
$events->listen('order.shipped', fn ($data) => print("Tracking for {$data['id']}\n"));

$events->dispatch('order.placed', ['id' => 'A-1', 'customer' => 'Amara']);
$events->dispatch('book.reviewed');
```

An event nobody listens to is simply ignored.

## Things to watch with observers

- **Order.** Observers usually run in the order they were attached. Don't rely on that for anything important.
- **Failures.** If one observer throws, the rest may not run. Decide whether that's right, or catch errors per
  observer.
- **Hidden flow.** "What happens when an order is placed?" is no longer visible in one place. Keep observers small
  and well named.

## The Command pattern

A **command** is an object that represents one action, with everything needed to carry it out, behind a small
interface such as `execute()`. Because it's an object, it can be passed around, put in a queue, or logged before
it runs:

```php
interface Command
{
    public function execute(): string;
}

class SendEmail implements Command
{
    public function __construct(private string $to, private string $subject) {}
    public function execute(): string { return "Sent '{$this->subject}' to {$this->to}"; }
}

class RestockBook implements Command
{
    public function __construct(private string $title, private int $copies) {}
    public function execute(): string { return "Ordered {$this->copies} × {$this->title}"; }
}

$queue = [
    new SendEmail('amara@example.com', 'Your order'),
    new RestockBook('Dune', 10),
];

foreach ($queue as $command) {
    echo $command->execute() . "\n";
}
```

Job queues work like this: commands are stored, and a worker runs them later.

## Undo

A command that knows how to reverse itself makes undo straightforward. Keep a history of executed commands, and
undo by popping the last one:

```php
interface UndoableCommand
{
    public function execute(): void;
    public function undo(): void;
}

class Basket
{
    public array $items = [];
}

class AddItem implements UndoableCommand
{
    public function __construct(private Basket $basket, private string $title) {}
    public function execute(): void { $this->basket->items[] = $this->title; }
    public function undo(): void { array_pop($this->basket->items); }
}

class History
{
    private array $done = [];

    public function run(UndoableCommand $command): void
    {
        $command->execute();
        $this->done[] = $command;
    }

    public function undo(): void
    {
        array_pop($this->done)?->undo();
    }
}

$basket = new Basket();
$history = new History();
$history->run(new AddItem($basket, 'Dune'));
$history->run(new AddItem($basket, 'Emma'));
$history->undo();

echo implode(', ', $basket->items);
```

`array_pop()` gives `null` when the history is empty, and `?->` makes that safe.

## Putting it together

Commands run through an invoker that tells observers about each one, combining both patterns:

```php
interface Command { public function execute(): string; }
interface CommandObserver { public function ran(Command $command, string $result): void; }

class ChangePrice implements Command
{
    public function __construct(private string $title, private float $price) {}
    public function execute(): string { return "{$this->title} now £{$this->price}"; }
}

class AuditTrail implements CommandObserver
{
    public array $entries = [];
    public function ran(Command $command, string $result): void { $this->entries[] = $command::class . ": $result"; }
}

class Invoker
{
    private array $observers = [];

    public function attach(CommandObserver $observer): void { $this->observers[] = $observer; }

    public function run(Command $command): string
    {
        $result = $command->execute();
        foreach ($this->observers as $observer) {
            $observer->ran($command, $result);
        }
        return $result;
    }
}

$audit = new AuditTrail();
$invoker = new Invoker();
$invoker->attach($audit);
$invoker->run(new ChangePrice('Dune', 9.99));
$invoker->run(new ChangePrice('Emma', 5.0));

print_r($audit->entries);
```

Next, try the quiz to check your understanding, then solve the challenges.
