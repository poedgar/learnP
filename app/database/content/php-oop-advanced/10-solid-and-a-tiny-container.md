## Five principles, one goal

Code that works today has to keep working while it changes: new features, new rules, new
people. **SOLID** is a set of five design principles, collected by Robert C. Martin, that make
classes easier to change without breaking things. None of them is a law; each describes a smell
to notice and a direction to move in.

## S: one reason to change

The **single responsibility principle**: a class should have one job, so it has only one reason
to change. This invoice calculates totals, formats them and decides on the currency symbol. A
change to the layout and a change to the tax rules both mean editing it:

```php
class InvoicePrinter
{
    public function print(array $prices): string
    {
        $total = array_sum($prices) * 1.2;
        return 'Total: £' . number_format($total, 2);
    }
}

echo (new InvoicePrinter())->print([10.0, 5.0]);
```

Splitting the job gives two small classes, each changed for one reason:

```php
class TaxCalculator
{
    public function totalWithTax(array $prices): float
    {
        return array_sum($prices) * 1.2;
    }
}

class MoneyFormatter
{
    public function format(float $amount): string
    {
        return '£' . number_format($amount, 2);
    }
}

$total = (new TaxCalculator())->totalWithTax([10.0, 5.0]);
echo 'Total: ' . (new MoneyFormatter())->format($total);
```

## O: open for extension, closed for modification

The **open/closed principle**: you should be able to add new behaviour by adding new code, not by
editing code that already works. A `match` on discount types has to be edited for every new kind
of discount. An interface lets each discount be its own class:

```php
interface Discount
{
    public function apply(float $price): float;
}

class HalfPrice implements Discount
{
    public function apply(float $price): float
    {
        return $price / 2;
    }
}

class StudentDiscount implements Discount
{
    public function apply(float $price): float
    {
        return $price * 0.9;
    }
}

function priceWith(float $price, Discount $discount): float
{
    return round($discount->apply($price), 2);
}

echo priceWith(20.0, new HalfPrice()) . ' ' . priceWith(20.0, new StudentDiscount());
```

A new discount next month is a new class; `priceWith()` never changes.

## L: children keep their parent's promises

The **Liskov substitution principle**: anywhere a parent class is expected, a child must work just as
well. A child that throws where the parent wouldn't, or quietly ignores a call, breaks code written for
the parent:

```php
class Catalogue
{
    protected array $titles = [];

    public function add(string $title): void
    {
        $this->titles[] = $title;
    }

    public function count(): int
    {
        return count($this->titles);
    }
}

class ArchivedCatalogue extends Catalogue
{
    public function add(string $title): void
    {
        // Archived catalogues are frozen, so this silently does nothing.
    }
}

function addNewArrival(Catalogue $catalogue): int
{
    $catalogue->add('Piranesi');
    return $catalogue->count();
}

echo addNewArrival(new Catalogue()) . ' ' . addNewArrival(new ArchivedCatalogue());
```

The second result surprises the code that relied on `add()`. An archived catalogue isn't really a kind
of catalogue you can add to; it should share an interface for reading, not inherit `add()`.

## I: small interfaces

The **interface segregation principle**: don't force a class to implement methods it doesn't need.
If every item in the shop had to implement one big `Item` interface with `sell()` and `lend()`, a gift
card, which can't be lent, would need an empty or throwing `lend()`. Several small interfaces let each
class promise only what it does:

```php
interface Sellable
{
    public function sell(): string;
}

interface Lendable
{
    public function lend(string $member): string;
}

class GiftCard implements Sellable
{
    public function sell(): string { return 'Gift card sold'; }
}

class LibraryCopy implements Sellable, Lendable
{
    public function sell(): string { return 'Copy sold'; }
    public function lend(string $member): string { return "Lent to $member"; }
}

foreach ([new GiftCard(), new LibraryCopy()] as $item) {
    echo $item->sell() . ($item instanceof Lendable ? ', and can be lent' : '') . "\n";
}
```

Code that lends things asks for a `Lendable`, and never sees a gift card.

## D: depend on promises, not details

The **dependency inversion principle**: high-level code (the checkout) shouldn't depend on low-level
details (a particular payment provider). Both should depend on an interface. You did this in the OOP in
PHP course, passing dependencies into the constructor:

```php
interface PaymentGateway
{
    public function charge(float $amount): string;
}

class FakeGateway implements PaymentGateway
{
    public function charge(float $amount): string
    {
        return "Pretended to charge £$amount";
    }
}

class Checkout
{
    public function __construct(private PaymentGateway $gateway) {}

    public function pay(float $amount): string
    {
        return $this->gateway->charge($amount);
    }
}

echo (new Checkout(new FakeGateway()))->pay(12.5);
```

## Composition over inheritance

Inheritance fixes behaviour when the class is written, and every combination needs its own child
class. **Composition** builds behaviour from small objects at run time. A **decorator** implements an
interface by wrapping another object with the same interface and adding something:

```php
interface Notifier
{
    public function send(string $message): string;
}

class EmailNotifier implements Notifier
{
    public function send(string $message): string
    {
        return "Email: $message";
    }
}

class UrgentNotifier implements Notifier
{
    public function __construct(private Notifier $inner) {}

    public function send(string $message): string
    {
        return $this->inner->send("URGENT! $message");
    }
}

echo (new UrgentNotifier(new EmailNotifier()))->send('The server is down');
```

Wrap it twice for a double effect, or around a text-message notifier: no new classes needed.

## A tiny container

When every class receives its dependencies, someone has to create them all, in the right order.
A **dependency injection container** is an object that knows how to make things. You register a
**factory** (a closure) for each name, and ask for objects by name:

```php
class Container
{
    private array $factories = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        return ($this->factories[$id])($this);
    }
}

interface Clock { public function now(): string; }

class FixedClock implements Clock
{
    public function now(): string { return '2026-04-08 12:00'; }
}

class Greeter
{
    public function __construct(private Clock $clock) {}

    public function greet(): string { return "Hello! It's {$this->clock->now()}."; }
}

$container = new Container();
$container->set(Clock::class, fn () => new FixedClock());
$container->set(Greeter::class, fn (Container $c) => new Greeter($c->get(Clock::class)));

echo $container->get(Greeter::class)->greet();
```

Each factory receives the container, so it can ask for what it needs. Frameworks such as Laravel and
Symfony are built around a container like this, and use reflection (from lesson 8) to fill in
constructors automatically.

## Putting it together

A checkout that depends only on interfaces, a discount chosen at run time, and a decorated notifier,
all assembled by the container:

```php
interface Discount { public function apply(float $price): float; }
interface Notifier { public function send(string $message): string; }

class TenPercentOff implements Discount
{
    public function apply(float $price): float { return $price * 0.9; }
}

class ScreenNotifier implements Notifier
{
    public function send(string $message): string { return "[screen] $message"; }
}

class Checkout
{
    public function __construct(private Discount $discount, private Notifier $notifier) {}

    public function complete(array $prices): string
    {
        $total = round($this->discount->apply(array_sum($prices)), 2);
        return $this->notifier->send("Paid £$total");
    }
}

$factories = [
    Discount::class => fn () => new TenPercentOff(),
    Notifier::class => fn () => new ScreenNotifier(),
];
$checkout = new Checkout($factories[Discount::class](), $factories[Notifier::class]());

echo $checkout->complete([10.0, 20.0]);
```

That's the end of the course. You've seen the tools behind every modern PHP framework. Try the quiz,
then the last challenges.
