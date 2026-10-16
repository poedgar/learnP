## Constructors with too many arguments

Some objects need many values, most of them optional. A constructor with eight parameters is hard to call
correctly: which `true` was "gift wrap" and which was "express"?

```php
class Order
{
    public function __construct(
        public string $customer,
        public array $items = [],
        public bool $giftWrap = false,
        public bool $express = false,
        public ?string $note = null,
        public ?string $voucher = null,
    ) {}
}

$order = new Order('Amara', ['Dune'], true, false, null, 'SPRING10');
echo $order->voucher;
```

Named arguments help a lot. When building also involves steps and checks, the **Builder** pattern helps
more.

## The Builder pattern

A **builder** is a separate object that collects the parts step by step, through clearly named methods,
and creates the finished object at the end with `build()`. The methods return the builder, so the steps
chain:

```php
class Order
{
    public function __construct(
        public readonly string $customer,
        public readonly array $items,
        public readonly bool $giftWrap,
        public readonly ?string $voucher,
    ) {}
}

class OrderBuilder
{
    private array $items = [];
    private bool $giftWrap = false;
    private ?string $voucher = null;

    public function __construct(private string $customer) {}

    public function add(string $title): static
    {
        $this->items[] = $title;
        return $this;
    }

    public function giftWrapped(): static
    {
        $this->giftWrap = true;
        return $this;
    }

    public function withVoucher(string $code): static
    {
        $this->voucher = $code;
        return $this;
    }

    public function build(): Order
    {
        return new Order($this->customer, $this->items, $this->giftWrap, $this->voucher);
    }
}

$order = (new OrderBuilder('Amara'))->add('Dune')->add('Emma')->giftWrapped()->build();
echo count($order->items) . ' items, gift wrapped: ' . var_export($order->giftWrap, true);
```

The finished `Order` can be readonly, because all the changing happens in the builder.

## Checking before building

`build()` is the natural place to check the whole object is valid, since only then are all the parts
known. A half-finished order never escapes:

```php
class Order
{
    public function __construct(public readonly string $customer, public readonly array $items) {}
}

class OrderBuilder
{
    private array $items = [];

    public function __construct(private string $customer) {}

    public function add(string $title): static
    {
        $this->items[] = $title;
        return $this;
    }

    public function build(): Order
    {
        if ($this->items === []) {
            throw new LogicException('An order needs at least one item.');
        }
        return new Order($this->customer, $this->items);
    }
}

try {
    (new OrderBuilder('Ben'))->build();
} catch (LogicException $e) {
    echo $e->getMessage();
}
```

## Builders for text

Builders aren't only for objects. Anything assembled in steps fits, such as a query or a message. Laravel's
query builder is the best-known example:

```php
class SelectBuilder
{
    private array $wheres = [];
    private ?string $order = null;

    public function __construct(private string $table) {}

    public function where(string $condition): static
    {
        $this->wheres[] = $condition;
        return $this;
    }

    public function orderBy(string $column): static
    {
        $this->order = $column;
        return $this;
    }

    public function toSql(): string
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if ($this->order) {
            $sql .= " ORDER BY {$this->order}";
        }
        return $sql;
    }
}

echo (new SelectBuilder('books'))->where('price < 10')->where("genre = 'Horror'")->orderBy('title')->toSql();
```

## The Singleton pattern

A **singleton** is a class that allows only one object of itself, reached through a static method. Its
constructor is private, and the one instance is kept in a static property:

```php
class Config
{
    private static ?Config $instance = null;
    private array $values = ['currency' => 'GBP'];

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}

var_dump(Config::instance() === Config::instance());
echo Config::instance()->get('currency');
```

## Why Singleton is usually a bad idea

Singleton is the most criticised pattern in the catalogue, because it's really a global variable in
disguise:

- **Hidden dependencies.** A class that calls `Config::instance()` inside a method depends on it, but nothing
  in its constructor says so.
- **Hard to test.** You can't hand in a different config for one test; every test shares the one instance,
  and changes leak between them.
- **Shared state.** Anything can change the one object, from anywhere.

The alternative is simple: create **one** object at the start of the program and **pass it in** to whatever
needs it. You still have only one, but the dependency is visible and replaceable:

```php
class Config
{
    public function __construct(private array $values) {}

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}

class PriceFormatter
{
    public function __construct(private Config $config) {}

    public function format(float $amount): string
    {
        $symbol = $this->config->get('currency') === 'GBP' ? '£' : '€';
        return $symbol . number_format($amount, 2);
    }
}

$config = new Config(['currency' => 'GBP']);
echo (new PriceFormatter($config))->format(9.5) . "\n";
echo (new PriceFormatter(new Config(['currency' => 'EUR'])))->format(9.5);
```

Dependency injection containers (from the Advanced OOP course) usually manage this "one shared object" for
you.

## Putting it together

A builder for a shipping label that checks its parts and produces readonly results:

```php
class Label
{
    public function __construct(public readonly string $name, public readonly array $lines, public readonly string $postcode) {}

    public function __toString(): string
    {
        return implode("\n", [$this->name, ...$this->lines, $this->postcode]);
    }
}

class LabelBuilder
{
    private string $name = '';
    private array $lines = [];
    private string $postcode = '';

    public function to(string $name): static { $this->name = $name; return $this; }
    public function line(string $line): static { $this->lines[] = $line; return $this; }
    public function postcode(string $postcode): static { $this->postcode = strtoupper($postcode); return $this; }

    public function build(): Label
    {
        if ($this->name === '' || $this->postcode === '') {
            throw new LogicException('A label needs a name and a postcode.');
        }
        return new Label($this->name, $this->lines, $this->postcode);
    }
}

echo (new LabelBuilder())->to('Amara Okafor')->line('12 Park Row')->line('Leeds')->postcode('ls1 5ab')->build();
```

Next, try the quiz to check your understanding, then solve the challenges.
