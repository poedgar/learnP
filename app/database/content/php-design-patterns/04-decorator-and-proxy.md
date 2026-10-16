## Wrapping an object in another

Both patterns in this lesson wrap an object in another object **with the same interface**. Callers can't tell
the difference: they get something that behaves like the original. What the wrapper does differs:

- a **decorator** adds behaviour (formatting, logging, a discount);
- a **proxy** controls access to the real object (caching it, creating it late, checking permission).

## The Decorator pattern

You met decorators briefly in the Advanced OOP course. Here's the idea in full. A price calculator is
decorated with extra rules, each its own small class, combined in whatever order you need:

```php
interface Pricing
{
    public function price(float $base): float;
}

class BasePricing implements Pricing
{
    public function price(float $base): float { return $base; }
}

class WithTax implements Pricing
{
    public function __construct(private Pricing $inner, private float $rate = 0.2) {}

    public function price(float $base): float
    {
        return $this->inner->price($base) * (1 + $this->rate);
    }
}

class WithDiscount implements Pricing
{
    public function __construct(private Pricing $inner, private float $percent) {}

    public function price(float $base): float
    {
        return $this->inner->price($base) * (100 - $this->percent) / 100;
    }
}

$pricing = new WithTax(new WithDiscount(new BasePricing(), 10));
echo round($pricing->price(20.0), 2);
```

Each decorator calls the object it wraps, and adds its own step before or after.

## Why not inheritance?

With inheritance, every combination needs its own class: `TaxedPricing`, `DiscountedPricing`,
`TaxedDiscountedPricing`… Decorators combine at run time, so three small classes give every combination, and a
fourth rule doesn't multiply the count. The order matters, just as it does in real life:

```php
interface Text { public function render(string $s): string; }

class Plain implements Text { public function render(string $s): string { return $s; } }

class Trimmed implements Text
{
    public function __construct(private Text $inner) {}
    public function render(string $s): string { return $this->inner->render(trim($s)); }
}

class Bracketed implements Text
{
    public function __construct(private Text $inner) {}
    public function render(string $s): string { return '[' . $this->inner->render($s) . ']'; }
}

echo (new Bracketed(new Trimmed(new Plain())))->render('  Dune  ') . "\n";
echo (new Trimmed(new Bracketed(new Plain())))->render('  Dune  ');
```

## Decorators that observe

A decorator doesn't have to change the result. A logging decorator records each call and passes it straight
on, without the original class knowing:

```php
interface Repository { public function find(int $id): ?string; }

class BookRepository implements Repository
{
    public function find(int $id): ?string { return [1 => 'Dune', 2 => 'Emma'][$id] ?? null; }
}

class LoggingRepository implements Repository
{
    public array $log = [];

    public function __construct(private Repository $inner) {}

    public function find(int $id): ?string
    {
        $result = $this->inner->find($id);
        $this->log[] = "find($id) → " . ($result ?? 'null');
        return $result;
    }
}

$books = new LoggingRepository(new BookRepository());
$books->find(1);
$books->find(9);
print_r($books->log);
```

## The Proxy pattern: a caching proxy

A **proxy** stands in for a real object. A **caching proxy** remembers answers, so the slow real object is only
asked once per question:

```php
interface ExchangeRates { public function rate(string $currency): float; }

class SlowRatesApi implements ExchangeRates
{
    public int $calls = 0;

    public function rate(string $currency): float
    {
        $this->calls++;
        return ['EUR' => 1.17, 'USD' => 1.27][$currency];
    }
}

class CachedRates implements ExchangeRates
{
    private array $cache = [];

    public function __construct(private ExchangeRates $real) {}

    public function rate(string $currency): float
    {
        return $this->cache[$currency] ??= $this->real->rate($currency);
    }
}

$api = new SlowRatesApi();
$rates = new CachedRates($api);
$rates->rate('EUR');
$rates->rate('EUR');
$rates->rate('USD');
echo "API called {$api->calls} times";
```

## A lazy proxy

A **lazy** (or virtual) proxy doesn't even create the real object until someone actually uses it. That's useful
when creating it is expensive and often not needed:

```php
interface Report { public function render(): string; }

class BigReport implements Report
{
    public function __construct()
    {
        echo "(building the big report...)\n";
    }

    public function render(): string { return 'Sales: 1,204 books'; }
}

class LazyReport implements Report
{
    private ?Report $real = null;

    public function __construct(private Closure $factory) {}

    public function render(): string
    {
        $this->real ??= ($this->factory)();
        return $this->real->render();
    }
}

$report = new LazyReport(fn () => new BigReport());
echo "Report ready to use\n";
echo $report->render() . "\n";
echo $report->render();
```

The big report is built only once, on the first `render()`. PHP 8.4 can even create lazy objects natively with
reflection, but the hand-written version shows the idea.

## A protection proxy

A **protection** proxy checks permission before passing a call on. The real object stays simple, and the rules
live in one place:

```php
interface Refunds { public function refund(string $order): string; }

class RealRefunds implements Refunds
{
    public function refund(string $order): string { return "Refunded $order"; }
}

class ManagersOnly implements Refunds
{
    public function __construct(private Refunds $real, private string $role) {}

    public function refund(string $order): string
    {
        if ($this->role !== 'manager') {
            throw new RuntimeException('Only managers can refund.');
        }
        return $this->real->refund($order);
    }
}

echo (new ManagersOnly(new RealRefunds(), 'manager'))->refund('A-1') . "\n";

try {
    (new ManagersOnly(new RealRefunds(), 'bookseller'))->refund('A-2');
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
```

## Putting it together

A repository with a cache in front of it and logging around that, so the log shows which calls reached the real
repository:

```php
interface Books { public function title(int $id): string; }

class DatabaseBooks implements Books
{
    public function title(int $id): string { return "Book #$id from the database"; }
}

class CachedBooks implements Books
{
    private array $cache = [];
    public function __construct(private Books $inner) {}
    public function title(int $id): string { return $this->cache[$id] ??= $this->inner->title($id); }
}

class LoggedBooks implements Books
{
    public function __construct(private Books $inner, private string $name) {}

    public function title(int $id): string
    {
        echo "[{$this->name}] title($id)\n";
        return $this->inner->title($id);
    }
}

$books = new CachedBooks(new LoggedBooks(new DatabaseBooks(), 'database'));
echo $books->title(7) . "\n";
echo $books->title(7) . "\n";
```

Next, try the quiz to check your understanding, then solve the challenges.
