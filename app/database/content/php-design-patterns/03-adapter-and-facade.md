## Structural patterns

**Structural** patterns are about fitting objects together. This lesson covers two of the most practical:
the **Adapter**, which makes a class fit an interface it wasn't written for, and the **Facade**, which puts a
simple front on a complicated set of classes.

## The problem: a class with the wrong shape

The shop's checkout works with any `PaymentGateway`. Now you want to use a payment company's library, but
its class has its own method names, its own argument order, and amounts in pence:

```php
interface PaymentGateway
{
    public function charge(float $pounds, string $customerEmail): bool;
}

// From the payment company's library: you can't change it.
class StripeLikeClient
{
    public function createCharge(array $request): array
    {
        return ['status' => $request['amount_pence'] > 0 ? 'succeeded' : 'failed', 'email' => $request['email']];
    }
}

echo 'The checkout wants charge(), the library offers createCharge().';
```

You can't change the library, and you don't want the checkout to know about it either.

## The Adapter pattern

An **adapter** implements the interface your code expects, and inside, translates each call into the form the
other class wants, like a travel plug adapter:

```php
interface PaymentGateway
{
    public function charge(float $pounds, string $customerEmail): bool;
}

class StripeLikeClient
{
    public function createCharge(array $request): array
    {
        return ['status' => $request['amount_pence'] > 0 ? 'succeeded' : 'failed'];
    }
}

class StripeLikeAdapter implements PaymentGateway
{
    public function __construct(private StripeLikeClient $client) {}

    public function charge(float $pounds, string $customerEmail): bool
    {
        $result = $this->client->createCharge([
            'amount_pence' => (int) round($pounds * 100),
            'email' => $customerEmail,
        ]);

        return $result['status'] === 'succeeded';
    }
}

function checkout(PaymentGateway $gateway, float $total): string
{
    return $gateway->charge($total, 'amara@example.com') ? 'Paid' : 'Declined';
}

echo checkout(new StripeLikeAdapter(new StripeLikeClient()), 19.99);
```

The checkout never sees the library. Switching payment companies means writing another adapter, nothing more.

## Adapters for old code and data

Adapters also wrap your own older code, or data in the wrong shape. Here an old function returning a
CSV line is adapted to an interface that new code wants:

```php
interface BookSource
{
    /** @return list<array{title: string, price: float}> */
    public function books(): array;
}

function legacyBookCsv(): string
{
    return "Dune;19.99\nEmma;7.99";
}

class LegacyCsvBookSource implements BookSource
{
    public function books(): array
    {
        $books = [];
        foreach (explode("\n", legacyBookCsv()) as $line) {
            [$title, $price] = explode(';', $line);
            $books[] = ['title' => $title, 'price' => (float) $price];
        }
        return $books;
    }
}

print_r((new LegacyCsvBookSource())->books());
```

## The problem: too many moving parts

Placing an order touches stock, payment, shipping and email. Code that does it calls all four, in the right
order, with the right arguments, every time:

```php
class Stock { public function reserve(string $title): bool { return $title !== 'Beloved'; } }
class Payments { public function take(float $amount): string { return 'PAY-' . (int) ($amount * 100); } }
class Shipping { public function book(string $title): string { return "SHIP-$title"; } }
class Mailer { public function send(string $text): void { echo "Mail: $text\n"; } }

$stock = new Stock();
$payments = new Payments();
$shipping = new Shipping();
$mailer = new Mailer();

if ($stock->reserve('Dune')) {
    $payment = $payments->take(19.99);
    $parcel = $shipping->book('Dune');
    $mailer->send("Order paid ($payment), parcel $parcel");
}
```

## The Facade pattern

A **facade** is one class with a few simple methods that does all that coordination behind the scenes. The
parts still exist, and can be used directly when needed, but everyday code talks to the facade:

```php
class Stock { public function reserve(string $title): bool { return $title !== 'Beloved'; } }
class Payments { public function take(float $amount): string { return 'PAY-' . (int) ($amount * 100); } }
class Shipping { public function book(string $title): string { return "SHIP-$title"; } }
class Mailer { public function send(string $text): void { echo "Mail: $text\n"; } }

class OrderFacade
{
    public function __construct(
        private Stock $stock,
        private Payments $payments,
        private Shipping $shipping,
        private Mailer $mailer,
    ) {}

    public function placeOrder(string $title, float $price): bool
    {
        if (! $this->stock->reserve($title)) {
            return false;
        }
        $payment = $this->payments->take($price);
        $parcel = $this->shipping->book($title);
        $this->mailer->send("Order paid ($payment), parcel $parcel");
        return true;
    }
}

$orders = new OrderFacade(new Stock(), new Payments(), new Shipping(), new Mailer());
var_dump($orders->placeOrder('Dune', 19.99));
var_dump($orders->placeOrder('Beloved', 16.00));
```

## Adapter or Facade?

They both wrap other classes, but for different reasons:

- An **adapter** changes an interface into **another, existing** one, so one class fits where another is
  expected. It usually wraps one object.
- A **facade** **simplifies**: it offers a new, smaller interface over a group of objects.

(Laravel calls its static helpers such as `Cache::get()` "facades". They're related in spirit, a simple front
door, but they work differently from the classic pattern.)

## Putting it together

Two shipping companies with different libraries, each adapted to one interface, behind a facade that picks the
cheapest:

```php
interface Courier { public function quote(float $kg): float; }

class FastParcelApi { public function priceInPence(int $grams): int { return 300 + intdiv($grams, 10); } }
class CheapPostApi { public function getRate(array $parcel): array { return ['gbp' => 2.5 + $parcel['kg'] * 1.5]; } }

class FastParcelAdapter implements Courier
{
    public function __construct(private FastParcelApi $api) {}
    public function quote(float $kg): float { return $this->api->priceInPence((int) ($kg * 1000)) / 100; }
}

class CheapPostAdapter implements Courier
{
    public function __construct(private CheapPostApi $api) {}
    public function quote(float $kg): float { return $this->api->getRate(['kg' => $kg])['gbp']; }
}

class ShippingFacade
{
    public function __construct(private array $couriers) {}

    public function cheapest(float $kg): string
    {
        $quotes = array_map(fn (Courier $c) => $c->quote($kg), $this->couriers);
        asort($quotes);
        $name = array_key_first($quotes);
        return sprintf('%s: £%.2f', $name, $quotes[$name]);
    }
}

$shipping = new ShippingFacade([
    'FastParcel' => new FastParcelAdapter(new FastParcelApi()),
    'CheapPost' => new CheapPostAdapter(new CheapPostApi()),
]);

echo $shipping->cheapest(0.5) . "\n" . $shipping->cheapest(3.0);
```

Next, try the quiz to check your understanding, then solve the challenges.
