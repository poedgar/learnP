## When behaviour depends on a situation

An order behaves differently depending on where it is in its life: a new order can be paid or cancelled, a
shipped one can't be cancelled, a delivered one can be returned. And a request such as a refund may need
approval from different people depending on its size. This lesson's two patterns handle both kinds of
situation without walls of `if`s.

## The problem: `if` on status everywhere

When every method checks the status, the rules for one status are scattered across all the methods, and adding a
status means editing each of them:

```php
class Order
{
    public string $status = 'new';

    public function pay(): string
    {
        if ($this->status !== 'new') {
            return "Can't pay a {$this->status} order";
        }
        $this->status = 'paid';
        return 'Paid';
    }

    public function ship(): string
    {
        if ($this->status !== 'paid') {
            return "Can't ship a {$this->status} order";
        }
        $this->status = 'shipped';
        return 'Shipped';
    }
}

$order = new Order();
echo $order->ship() . "\n" . $order->pay() . "\n" . $order->ship();
```

## The State pattern

Each status becomes a **state** class implementing the same interface, one method per action. The order keeps its
current state object and hands every action to it. A state answers the actions allowed in that situation, and
moves the order to the next state:

```php
interface OrderState
{
    public function pay(Order $order): string;
    public function ship(Order $order): string;
    public function name(): string;
}

class Order
{
    public OrderState $state;

    public function __construct() { $this->state = new NewOrder(); }

    public function pay(): string { return $this->state->pay($this); }
    public function ship(): string { return $this->state->ship($this); }
}

class NewOrder implements OrderState
{
    public function pay(Order $order): string { $order->state = new PaidOrder(); return 'Paid'; }
    public function ship(Order $order): string { return "Can't ship an unpaid order"; }
    public function name(): string { return 'new'; }
}

class PaidOrder implements OrderState
{
    public function pay(Order $order): string { return 'Already paid'; }
    public function ship(Order $order): string { $order->state = new ShippedOrder(); return 'Shipped'; }
    public function name(): string { return 'paid'; }
}

class ShippedOrder implements OrderState
{
    public function pay(Order $order): string { return 'Already paid'; }
    public function ship(Order $order): string { return 'Already shipped'; }
    public function name(): string { return 'shipped'; }
}

$order = new Order();
echo $order->ship() . "\n" . $order->pay() . "\n" . $order->ship() . "\n" . $order->state->name();
```

All the rules for "paid" are in `PaidOrder`. A new status is a new class.

## A base class for the defaults

Most states refuse most actions. A base class with refusing defaults keeps each state down to the actions it
actually allows:

```php
abstract class LoanState
{
    public function borrow(Loan $loan): string { return "Can't borrow: {$this->name()}"; }
    public function giveBack(Loan $loan): string { return "Can't return: {$this->name()}"; }
    abstract public function name(): string;
}

class Loan
{
    public LoanState $state;
    public function __construct() { $this->state = new OnShelf(); }
    public function borrow(): string { return $this->state->borrow($this); }
    public function giveBack(): string { return $this->state->giveBack($this); }
}

class OnShelf extends LoanState
{
    public function borrow(Loan $loan): string { $loan->state = new OnLoan(); return 'Borrowed'; }
    public function name(): string { return 'on the shelf'; }
}

class OnLoan extends LoanState
{
    public function giveBack(Loan $loan): string { $loan->state = new OnShelf(); return 'Returned'; }
    public function name(): string { return 'on loan'; }
}

$loan = new Loan();
echo implode("\n", [$loan->giveBack(), $loan->borrow(), $loan->borrow(), $loan->giveBack()]);
```

## Enums for simple state machines

When states have no real behaviour, only a list of allowed next states, a PHP enum (from the OOP in PHP course) is
lighter than a class per state:

```php
enum Status: string
{
    case New = 'new';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function canMoveTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::New => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped, self::Cancelled => [],
        }, true);
    }
}

var_dump(Status::New->canMoveTo(Status::Paid));
var_dump(Status::Shipped->canMoveTo(Status::Cancelled));
```

Reach for full State classes when each state has real, different behaviour.

## The Chain of Responsibility pattern

A refund under £20 is approved by a bookseller, under £200 by the manager, and anything bigger by the owner. A
**chain of responsibility** passes a request along a line of **handlers**: each either deals with it or passes it
to the next one:

```php
abstract class Approver
{
    private ?Approver $next = null;

    public function then(Approver $next): Approver
    {
        $this->next = $next;
        return $next;
    }

    public function handle(float $amount): string
    {
        if ($this->canApprove($amount)) {
            return static::class . " approves £$amount";
        }
        return $this->next?->handle($amount) ?? "Nobody can approve £$amount";
    }

    abstract protected function canApprove(float $amount): bool;
}

class Bookseller extends Approver { protected function canApprove(float $amount): bool { return $amount < 20; } }
class Manager extends Approver { protected function canApprove(float $amount): bool { return $amount < 200; } }
class Owner extends Approver { protected function canApprove(float $amount): bool { return $amount < 1000; } }

$chain = new Bookseller();
$chain->then(new Manager())->then(new Owner());

foreach ([15.0, 120.0, 650.0, 5000.0] as $amount) {
    echo $chain->handle($amount) . "\n";
}
```

The person asking only talks to the first handler, and the chain can be rearranged without changing them.

## Chains that all take part: middleware

A popular variation lets **every** handler do something and then call the next one, like layers of an onion.
That's how web frameworks run **middleware** around a request: logging, authentication, then the actual page:

```php
$middleware = [
    fn (string $request, Closure $next) => '[log] ' . $next($request),
    fn (string $request, Closure $next) => str_contains($request, 'token') ? $next($request) : 'Denied',
];

$handler = fn (string $request) => "Page for $request";

foreach (array_reverse($middleware) as $layer) {
    $handler = fn (string $request) => $layer($request, $handler);
}

echo $handler('/books?token=abc') . "\n";
echo $handler('/books');
```

Each layer decides whether, and how, to call the next. A layer that doesn't call `$next` stops the chain.

## Putting it together

A support ticket routed by a chain, whose handlers are easy to reorder:

```php
interface Handler
{
    public function handle(array $ticket): ?string;
}

class FaqBot implements Handler
{
    public function handle(array $ticket): ?string
    {
        return str_contains($ticket['text'], 'opening hours') ? 'Bot: we open 9 till 6' : null;
    }
}

class Bookseller implements Handler
{
    public function handle(array $ticket): ?string
    {
        return $ticket['topic'] === 'books' ? 'Bookseller: happy to help' : null;
    }
}

class Manager implements Handler
{
    public function handle(array $ticket): ?string { return 'Manager: leave it with me'; }
}

function route(array $ticket, Handler ...$chain): string
{
    foreach ($chain as $handler) {
        $answer = $handler->handle($ticket);
        if ($answer !== null) {
            return $answer;
        }
    }
    return 'Unanswered';
}

$chain = [new FaqBot(), new Bookseller(), new Manager()];
echo route(['topic' => 'shop', 'text' => 'What are your opening hours?'], ...$chain) . "\n";
echo route(['topic' => 'books', 'text' => 'Is Dune a series?'], ...$chain) . "\n";
echo route(['topic' => 'refund', 'text' => 'My parcel is late'], ...$chain);
```

Next, try the quiz to check your understanding, then solve the challenges.
