## New operations on a fixed set of classes

The shop's catalogue has a handful of product classes that rarely change: books, e-books and gift cards. But new
**operations** on them keep arriving: export to CSV, calculate tax, check stock, build a search index. Adding a
method to every class for every operation spreads each feature across many files. The **Visitor** pattern
collects each operation into one class instead.

## The Visitor pattern

Each product class gets a single method, `accept(Visitor $visitor)`, which calls the visitor's method for **its own
type**. A visitor is an object with one method per product class, and holds one operation:

```php
interface ProductVisitor
{
    public function visitBook(Book $book): mixed;
    public function visitEbook(Ebook $ebook): mixed;
    public function visitGiftCard(GiftCard $card): mixed;
}

interface Product
{
    public function accept(ProductVisitor $visitor): mixed;
}

class Book implements Product
{
    public function __construct(public string $title, public float $price, public float $weightKg) {}
    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
}

class Ebook implements Product
{
    public function __construct(public string $title, public float $price) {}
    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitEbook($this); }
}

class GiftCard implements Product
{
    public function __construct(public float $value) {}
    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
}

class ShippingWeight implements ProductVisitor
{
    public function visitBook(Book $book): float { return $book->weightKg; }
    public function visitEbook(Ebook $ebook): float { return 0.0; }
    public function visitGiftCard(GiftCard $card): float { return 0.02; }
}

$basket = [new Book('Dune', 19.99, 0.6), new Ebook('Emma', 3.99), new GiftCard(25.0)];
$weigh = new ShippingWeight();

echo array_sum(array_map(fn (Product $p) => $p->accept($weigh), $basket)) . ' kg';
```

The `accept()` call is a little dance called **double dispatch**: the product picks the right visitor method for its
class, so the visitor always knows exactly what it's looking at.

## Adding an operation is one class

A tax calculation is a second visitor. No product class changes at all:

```php
interface ProductVisitor
{
    public function visitBook(Book $book): mixed;
    public function visitGiftCard(GiftCard $card): mixed;
}

interface Product { public function accept(ProductVisitor $visitor): mixed; }

class Book implements Product
{
    public function __construct(public string $title, public float $price) {}
    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitBook($this); }
}

class GiftCard implements Product
{
    public function __construct(public float $value) {}
    public function accept(ProductVisitor $visitor): mixed { return $visitor->visitGiftCard($this); }
}

class TaxDue implements ProductVisitor
{
    public function visitBook(Book $book): float { return 0.0; }
    public function visitGiftCard(GiftCard $card): float { return 0.0; }
}

class Describe implements ProductVisitor
{
    public function visitBook(Book $book): string { return "Book: {$book->title}"; }
    public function visitGiftCard(GiftCard $card): string { return "Gift card worth £{$card->value}"; }
}

foreach ([new Book('Dune', 19.99), new GiftCard(25.0)] as $product) {
    echo $product->accept(new Describe()) . "\n";
}
```

The trade-off: a **new product class** now means adding a method to every visitor. Visitor fits when the classes are
stable and the operations keep growing, not the other way round.

## A lighter alternative: `match` on the class

In modern PHP, when you control all the classes, a `match` on the object's type does a similar job with less
ceremony. The cost is that nothing reminds you to handle a new class, except the error when it isn't matched:

```php
final class Book { public function __construct(public float $price) {} }
final class GiftCard { public function __construct(public float $value) {} }

function priceOf(object $product): float
{
    return match (true) {
        $product instanceof Book => $product->price,
        $product instanceof GiftCard => $product->value,
    };
}

echo priceOf(new Book(19.99)) + priceOf(new GiftCard(25.0));
```

## Choosing a pattern

With so many patterns, the hard part is knowing which one fits. Start from the **problem** you have, not from the
catalogue:

| The problem | Try |
|-------------|-----|
| Which class to create depends on data | Factory |
| Building an object takes many optional steps | Builder |
| A class has the wrong interface for your code | Adapter |
| A group of classes is complicated to use | Facade |
| Add features to objects, in any combination | Decorator |
| Cache, delay or guard access to an object | Proxy |
| Treat single items and groups the same | Composite |
| Swap one way of doing something for another | Strategy |
| Same steps, some steps differ by kind | Template Method |
| Many things must react when something happens | Observer |
| Queue, log or undo actions | Command |
| Behaviour depends on the current status | State |
| Pass a request along until someone handles it | Chain of Responsibility |
| New operations on stable classes | Visitor |

## Patterns that don't fit

Signs a pattern is the wrong tool:

- **It solves a problem you don't have yet.** A factory for a class that has one implementation is just indirection.
  Add the pattern when the second case arrives.
- **The code got harder to follow.** Patterns should make change easier; if a reader needs a diagram to find where
  anything happens, simplify.
- **You named the class after the pattern but not the job.** `PaymentStrategyFactoryManager` hints that the design is
  serving the pattern, not the problem.

```php
// The simplest thing that works, with no pattern at all.
function shippingCost(float $kg): float
{
    return $kg <= 1 ? 2.95 : 2.95 + ($kg - 1) * 1.5;
}

echo shippingCost(0.5) . ' ' . shippingCost(3.0);
```

When a second shipping company arrives, *then* a Strategy earns its place.

## Putting it together

A small basket where several patterns cooperate: a factory creates products, a visitor totals them, and a decorator
adds logging around the visitor:

```php
interface Visitor { public function visitBook(Book $b): float; public function visitCard(Card $c): float; }
interface Product { public function accept(Visitor $v): float; }

class Book implements Product
{
    public function __construct(public float $price) {}
    public function accept(Visitor $v): float { return $v->visitBook($this); }
}

class Card implements Product
{
    public function __construct(public float $value) {}
    public function accept(Visitor $v): float { return $v->visitCard($this); }
}

class ProductFactory
{
    public static function fromRow(array $row): Product
    {
        return match ($row['type']) {
            'book' => new Book($row['amount']),
            'card' => new Card($row['amount']),
        };
    }
}

class PriceVisitor implements Visitor
{
    public function visitBook(Book $b): float { return $b->price; }
    public function visitCard(Card $c): float { return $c->value; }
}

class LoggingVisitor implements Visitor
{
    public function __construct(private Visitor $inner) {}
    public function visitBook(Book $b): float { echo "book\n"; return $this->inner->visitBook($b); }
    public function visitCard(Card $c): float { echo "card\n"; return $this->inner->visitCard($c); }
}

$rows = [['type' => 'book', 'amount' => 19.99], ['type' => 'card', 'amount' => 10.0]];
$visitor = new LoggingVisitor(new PriceVisitor());
$total = array_sum(array_map(fn ($row) => ProductFactory::fromRow($row)->accept($visitor), $rows));

echo "Total: $total";
```

That's the end of the course. Try the quiz, then the last challenges.
