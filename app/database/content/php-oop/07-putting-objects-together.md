## Has-a, not is-a

Inheritance models "is a": an e-book *is a* book. Most relationships in a program are a
different kind: an order *has* lines, a shelf *has* books, a checkout *has* a payment method.
Model those by giving one object a property that holds another. This is **composition**, and
it's how most well-built programs fit together.

A good habit: when you're tempted to extend a class only to reuse its code, ask whether your
object could **have** one of those instead.

## Objects holding objects

A shelf holds books. It keeps them in an array property, and answers questions by asking each
book in turn:

```php
class Book
{
    public function __construct(public string $title, public int $pages) {}
}

class Shelf
{
    /** @var Book[] */
    private array $books = [];

    public function add(Book $book): void
    {
        $this->books[] = $book;
    }

    public function totalPages(): int
    {
        $total = 0;
        foreach ($this->books as $book) {
            $total += $book->pages;
        }

        return $total;
    }
}

$shelf = new Shelf();
$shelf->add(new Book('Dune', 612));
$shelf->add(new Book('Circe', 393));

echo $shelf->totalPages() . ' pages';
```

`add(Book $book)` only accepts books, so the shelf can trust everything it holds. The
`/** @var Book[] */` comment tells readers (and editors) what the array contains.

## Passing in what an object needs

A shipping quote needs a courier to ask for prices. The quote could create its own courier
with `new` inside its methods, but then it's stuck with that one courier forever. Instead,
**pass the courier in** through the constructor. This is called **dependency injection**:

```php
class Courier
{
    public function priceFor(float $weightKg): float
    {
        return 3.0 + $weightKg * 1.5;
    }
}

class ShippingQuote
{
    public function __construct(private Courier $courier) {}

    public function forParcel(float $weightKg): string
    {
        return 'Shipping: ' . number_format($this->courier->priceFor($weightKg), 2);
    }
}

$quote = new ShippingQuote(new Courier());
echo $quote->forParcel(2.0);
```

The quote says what it needs, a `Courier`, and whoever creates the quote decides which one.

## Depending on a promise, not a class

Better still, type the dependency with an **interface**. Then any courier that keeps the
promise will do, including ones written years later:

```php
interface Courier
{
    public function priceFor(float $weightKg): float;
}

class RoyalPost implements Courier
{
    public function priceFor(float $weightKg): float
    {
        return 3.0 + $weightKg * 1.5;
    }
}

class SpeedyParcels implements Courier
{
    public function priceFor(float $weightKg): float
    {
        return 7.5;
    }
}

class ShippingQuote
{
    public function __construct(private Courier $courier) {}

    public function forParcel(float $weightKg): float
    {
        return $this->courier->priceFor($weightKg);
    }
}

echo (new ShippingQuote(new RoyalPost()))->forParcel(2.0) . "\n";
echo (new ShippingQuote(new SpeedyParcels()))->forParcel(2.0) . "\n";
```

`ShippingQuote` never changes, however many couriers you add.

## Swapping in a fake

Real dependencies send emails, charge cards and talk to other servers: things you don't want
to happen while you're trying out your code. Because the class depends on an interface, you
can hand it a **fake** that just remembers what it was asked to do:

```php
interface Mailer
{
    public function send(string $to, string $message): void;
}

class FakeMailer implements Mailer
{
    public array $sent = [];

    public function send(string $to, string $message): void
    {
        $this->sent[] = "$to: $message";
    }
}

class Newsletter
{
    public function __construct(private Mailer $mailer) {}

    public function announce(array $emails, string $title): void
    {
        foreach ($emails as $email) {
            $this->mailer->send($email, "New in the shop: $title");
        }
    }
}

$mailer = new FakeMailer();
(new Newsletter($mailer))->announce(['amara@example.com', 'ben.h@example.com'], 'Piranesi');

echo implode("\n", $mailer->sent);
```

Nothing was emailed, and you can see exactly what would have been. Automated tests are built
on this idea.

## A small project: a till

Here's everything together: value objects, an interface, injected collaborators and objects
holding objects. A till rings up items and asks a pricing rule for the total:

```php
readonly class Item
{
    public function __construct(public string $name, public float $price) {}
}

interface PricingRule
{
    /** @param Item[] $items */
    public function total(array $items): float;
}

class FullPrice implements PricingRule
{
    public function total(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item->price;
        }

        return round($sum, 2);
    }
}

class ThreeForTwo implements PricingRule
{
    public function total(array $items): float
    {
        $prices = array_map(fn (Item $item) => $item->price, $items);
        sort($prices);
        $free = intdiv(count($prices), 3);

        return round(array_sum(array_slice($prices, $free)), 2);
    }
}

class Till
{
    /** @var Item[] */
    private array $items = [];

    public function __construct(private PricingRule $rule) {}

    public function ring(Item $item): void
    {
        $this->items[] = $item;
    }

    public function total(): float
    {
        return $this->rule->total($this->items);
    }
}

$items = [new Item('Dune', 19.99), new Item('Circe', 13.99), new Item('Beloved', 16.00)];

foreach ([new FullPrice(), new ThreeForTwo()] as $rule) {
    $till = new Till($rule);
    foreach ($items as $item) {
        $till->ring($item);
    }
    echo get_class($rule) . ': ' . $till->total() . "\n";
}
```

The cheapest of every three books is free under `ThreeForTwo`, and the till didn't change at
all to support it.

## Looking back

Over this course you've built up a toolkit:

- **Classes and objects** bundle data with the code that works on it, and visibility keeps
  that data safe.
- **Inheritance** models "is a" and lets a child reuse and adjust its parent.
- **Interfaces** describe what something can do, so code can work with any implementation.
  **Abstract classes** share code within a family.
- **Static members, constants and enums** belong to the class, not to each object; enums make
  fixed sets of values safe.
- **Exceptions** report problems that can't be ignored.
- **Traits** share code between unrelated classes; **readonly** properties and **value
  objects** make data that can't change behind your back.
- **Composition** and **dependency injection** fit objects together, so each part can be
  swapped, faked and tested on its own.

Next, try the quiz, then solve the last challenges of the course.
