## What a design pattern is

Programmers keep meeting the same problems: creating the right kind of object, adding behaviour without
editing a class, reacting when something changes. A **design pattern** is a named, well-tried solution
to one of those problems. Patterns aren't libraries you install; they're shapes for your own classes.
Their names are a shared vocabulary: saying "use a decorator here" tells another developer a whole
design in three words.

The classic catalogue, *Design Patterns* by the "Gang of Four" (1994), groups them into three families,
and this course follows it:

- **creational** patterns create objects (Factory, Builder, Singleton);
- **structural** patterns combine objects (Adapter, Facade, Decorator, Proxy, Composite);
- **behavioural** patterns share out work between objects (Strategy, Template Method, Observer, Command,
  State, Chain of Responsibility, Visitor).

Every pattern has a cost: more classes, more indirection. Use one when it solves a problem you actually
have, not because it exists.

## The problem: `new` everywhere

Code that creates objects with `new` is tied to those exact classes. When the choice of class depends on
data, the `new`s and the decisions spread through the code:

```php
interface Notifier
{
    public function send(string $message): string;
}

class EmailNotifier implements Notifier
{
    public function send(string $message): string { return "Email: $message"; }
}

class SmsNotifier implements Notifier
{
    public function send(string $message): string { return "SMS: $message"; }
}

$preference = 'sms';
$notifier = $preference === 'sms' ? new SmsNotifier() : new EmailNotifier();
echo $notifier->send('Your order has shipped');
```

Every place that needs a notifier repeats that decision, and adding a third kind means finding them all.

## Simple factory

A **factory** is an object (or function) whose job is creating other objects. The decision lives in one
place, and the rest of the code asks the factory instead of using `new`:

```php
interface Notifier { public function send(string $message): string; }

class EmailNotifier implements Notifier
{
    public function send(string $message): string { return "Email: $message"; }
}

class SmsNotifier implements Notifier
{
    public function send(string $message): string { return "SMS: $message"; }
}

class NotifierFactory
{
    public function create(string $channel): Notifier
    {
        return match ($channel) {
            'email' => new EmailNotifier(),
            'sms' => new SmsNotifier(),
            default => throw new InvalidArgumentException("Unknown channel: $channel"),
        };
    }
}

$factory = new NotifierFactory();
echo $factory->create('sms')->send('Your order has shipped');
```

The caller depends only on the `Notifier` interface. Adding a channel touches one `match`.

## Static factory methods

A **static factory method** (also called a named constructor) is a factory living on the class itself.
Several of them can create the same class from different starting points, with names that say what they
do, which a single constructor can't:

```php
class Money
{
    private function __construct(public readonly int $pence) {}

    public static function fromPounds(float $pounds): self
    {
        return new self((int) round($pounds * 100));
    }

    public static function fromPence(int $pence): self
    {
        return new self($pence);
    }

    public static function zero(): self
    {
        return new self(0);
    }
}

echo Money::fromPounds(12.5)->pence . ' ' . Money::fromPence(99)->pence . ' ' . Money::zero()->pence;
```

A `private` constructor forces everyone through the named methods, so a `Money` can't be built from an
ambiguous number.

## Factory method: let children decide

The **Factory Method** pattern goes one step further: a parent class uses an object it doesn't create
itself. It calls an abstract method to get one, and each child class decides which concrete class that is:

```php
interface Report { public function render(array $rows): string; }

class CsvReport implements Report
{
    public function render(array $rows): string { return implode("\n", array_map(fn ($r) => implode(',', $r), $rows)); }
}

class TextReport implements Report
{
    public function render(array $rows): string { return implode("\n", array_map(fn ($r) => implode(' | ', $r), $rows)); }
}

abstract class SalesExport
{
    abstract protected function makeReport(): Report;

    public function export(): string
    {
        $rows = [['Dune', '3'], ['Emma', '1']];
        return $this->makeReport()->render($rows);
    }
}

class CsvSalesExport extends SalesExport
{
    protected function makeReport(): Report { return new CsvReport(); }
}

class TextSalesExport extends SalesExport
{
    protected function makeReport(): Report { return new TextReport(); }
}

echo (new CsvSalesExport())->export() . "\n\n" . (new TextSalesExport())->export();
```

`export()` is written once and works with any report.

## Abstract factory: families that belong together

Sometimes several objects must match each other, like a light-themed button and a light-themed label. An
**Abstract Factory** is an interface with one creation method per kind of object; each implementation
creates a whole matching family:

```php
interface Button { public function render(): string; }
interface Label { public function render(): string; }

interface ThemeFactory
{
    public function button(string $text): Button;
    public function label(string $text): Label;
}

class DarkButton implements Button
{
    public function __construct(private string $text) {}
    public function render(): string { return "[■ {$this->text}]"; }
}

class DarkLabel implements Label
{
    public function __construct(private string $text) {}
    public function render(): string { return "■ {$this->text}"; }
}

class DarkTheme implements ThemeFactory
{
    public function button(string $text): Button { return new DarkButton($text); }
    public function label(string $text): Label { return new DarkLabel($text); }
}

function checkoutPanel(ThemeFactory $theme): string
{
    return $theme->label('Total: £19.99')->render() . ' ' . $theme->button('Pay')->render();
}

echo checkoutPanel(new DarkTheme());
```

Switch the theme by passing a different factory; the panel can't accidentally mix families.

## Putting it together

A factory that also remembers which classes it may create, so new kinds can be registered without editing
it:

```php
interface Notifier { public function send(string $message): string; }

class EmailNotifier implements Notifier
{
    public function send(string $message): string { return "Email: $message"; }
}

class PushNotifier implements Notifier
{
    public function send(string $message): string { return "Push: $message"; }
}

class NotifierRegistry
{
    private array $makers = [];

    public function register(string $channel, Closure $maker): void
    {
        $this->makers[$channel] = $maker;
    }

    public function create(string $channel): Notifier
    {
        $maker = $this->makers[$channel] ?? throw new InvalidArgumentException("Unknown channel: $channel");
        return $maker();
    }
}

$registry = new NotifierRegistry();
$registry->register('email', fn () => new EmailNotifier());
$registry->register('push', fn () => new PushNotifier());

echo $registry->create('push')->send('Dune is back in stock');
```

Next, try the quiz to check your understanding, then solve the challenges.
