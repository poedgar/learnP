## Behavioural patterns

**Behavioural** patterns are about how objects share out work and responsibility. The first two answer the same
question in opposite ways: "this algorithm has a part that varies; where should the varying part live?"

- **Strategy** puts the varying part in a separate object you hand in (composition).
- **Template Method** puts it in a method that child classes fill in (inheritance).

## The problem: one class, many ways

Shipping costs depend on the method chosen. A growing `match` mixes every rule into one function, and every new
method means editing it:

```php
function shippingCost(string $method, float $kg): float
{
    return match ($method) {
        'standard' => 2.95,
        'express' => 4.95 + $kg * 0.5,
        'collect' => 0.0,
    };
}

echo shippingCost('express', 2.0);
```

## The Strategy pattern

Each way of doing the job becomes its own class behind a shared interface: a **strategy**. The code that needs the
job done receives a strategy and calls it, without knowing which one it has:

```php
interface ShippingStrategy
{
    public function cost(float $kg): float;
}

class StandardShipping implements ShippingStrategy
{
    public function cost(float $kg): float { return 2.95; }
}

class ExpressShipping implements ShippingStrategy
{
    public function cost(float $kg): float { return 4.95 + $kg * 0.5; }
}

class CollectInStore implements ShippingStrategy
{
    public function cost(float $kg): float { return 0.0; }
}

class Checkout
{
    public function __construct(private ShippingStrategy $shipping) {}

    public function total(float $goods, float $kg): float
    {
        return round($goods + $this->shipping->cost($kg), 2);
    }
}

echo (new Checkout(new ExpressShipping()))->total(20.0, 2.0) . "\n";
echo (new Checkout(new CollectInStore()))->total(20.0, 2.0);
```

A new shipping method is a new class. `Checkout` never changes. That's the open/closed principle from the
Advanced OOP course in action.

## Choosing a strategy at run time

The choice can come from data, often through a small lookup or factory. Strategies can also be swapped while the
program runs:

```php
interface SortOrder
{
    public function compare(array $a, array $b): int;
}

class ByPrice implements SortOrder
{
    public function compare(array $a, array $b): int { return $a['price'] <=> $b['price']; }
}

class ByTitle implements SortOrder
{
    public function compare(array $a, array $b): int { return $a['title'] <=> $b['title']; }
}

$books = [['title' => 'Emma', 'price' => 7.99], ['title' => 'Circe', 'price' => 13.99], ['title' => 'Dune', 'price' => 9.99]];
$orders = ['price' => new ByPrice(), 'title' => new ByTitle()];

foreach (['price', 'title'] as $choice) {
    usort($books, [$orders[$choice], 'compare']);
    echo "By $choice: " . implode(', ', array_column($books, 'title')) . "\n";
}
```

## Closures as lightweight strategies

In PHP, a strategy with a single method is often just a closure. `usort()`'s comparison function is a strategy.
Use classes when a strategy needs a name, settings, or several methods; use closures for small one-offs:

```php
function applyDiscount(float $price, Closure $rule): float
{
    return round($rule($price), 2);
}

$halfPrice = fn (float $p) => $p / 2;
$fiveOff = fn (float $p) => max(0, $p - 5);

echo applyDiscount(19.99, $halfPrice) . ' ' . applyDiscount(19.99, $fiveOff);
```

## The Template Method pattern

Sometimes several processes share the same **steps in the same order**, and only some steps differ. A parent class
writes the whole process once, in a **template method**, and leaves the differing steps as abstract methods for
child classes to fill in:

```php
abstract class ReportGenerator
{
    // The template method: the fixed outline of every report.
    public function generate(array $rows): string
    {
        return $this->header() . "\n" . implode("\n", array_map([$this, 'row'], $rows)) . "\n" . $this->footer(count($rows));
    }

    abstract protected function header(): string;
    abstract protected function row(array $row): string;

    protected function footer(int $count): string
    {
        return "$count rows";
    }
}

class CsvReport extends ReportGenerator
{
    protected function header(): string { return 'title,price'; }
    protected function row(array $row): string { return "{$row[0]},{$row[1]}"; }
}

class MarkdownReport extends ReportGenerator
{
    protected function header(): string { return "| title | price |\n|---|---|"; }
    protected function row(array $row): string { return "| {$row[0]} | {$row[1]} |"; }
    protected function footer(int $count): string { return "_{$count} books_"; }
}

$rows = [['Dune', 19.99], ['Emma', 7.99]];
echo (new CsvReport())->generate($rows) . "\n\n" . (new MarkdownReport())->generate($rows);
```

`footer()` isn't abstract: it has a sensible default, which children may override. Such optional steps are called
**hooks**.

## Strategy or Template Method?

- **Strategy** is flexible: the varying part can be chosen and changed at run time, and combined freely. It needs
  an object to be passed in.
- **Template Method** is simpler when the variations are few and fixed, and the outline must never change: the
  parent controls the order of steps, and children can't skip them.

Many developers reach for Strategy first, following "composition over inheritance", and use Template Method for
frameworks where the outline really is fixed.

## Putting it together

A checkout that takes its pricing rules as a list of strategies, applied in order:

```php
interface PriceRule
{
    public function apply(float $total): float;
}

class PercentOff implements PriceRule
{
    public function __construct(private int $percent) {}
    public function apply(float $total): float { return $total * (100 - $this->percent) / 100; }
}

class FreeOverThreshold implements PriceRule
{
    public function __construct(private float $threshold, private float $shipping) {}
    public function apply(float $total): float { return $total >= $this->threshold ? $total : $total + $this->shipping; }
}

class Checkout
{
    public function __construct(private array $rules) {}

    public function total(float $goods): float
    {
        foreach ($this->rules as $rule) {
            $goods = $rule->apply($goods);
        }
        return round($goods, 2);
    }
}

$checkout = new Checkout([new PercentOff(10), new FreeOverThreshold(25.0, 2.95)]);
echo $checkout->total(20.0) . ' ' . $checkout->total(40.0);
```

Next, try the quiz to check your understanding, then solve the challenges.
