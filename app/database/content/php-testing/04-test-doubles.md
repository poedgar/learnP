## Why replace a dependency?

Some things make code hard to test: the current time, random numbers, sending email, calling a payment provider.
They're slow, unpredictable, or have effects you don't want in a test. A **test double** stands in for such a
dependency during a test, the way a stunt double stands in for an actor.

The trick that makes it possible is to depend on an **interface**, and receive the real thing through the
constructor. Then a test can pass in something else:

```php
interface Weather
{
    public function temperature(string $city): float;
}

final class OutfitAdvisor
{
    public function __construct(private Weather $weather) {}

    public function advice(string $city): string
    {
        return $this->weather->temperature($city) < 10 ? 'Take a coat' : 'No coat needed';
    }
}

final class AlwaysCold implements Weather
{
    public function temperature(string $city): float
    {
        return 2.0;
    }
}

echo (new OutfitAdvisor(new AlwaysCold()))->advice('Oslo');
```

## Kinds of double

The names come from Gerard Meszaros's *xUnit Test Patterns*, and help say what a double is for:

- **Dummy**: passed in but never used, just to fill a parameter.
- **Stub**: returns canned answers, like `AlwaysCold` above.
- **Fake**: a working but simpler version, such as an in-memory database instead of a real one.
- **Spy**: records how it was called, so the test can check afterwards.
- **Mock**: is told in advance which calls to expect, and fails if they don't happen.

## Anonymous classes as quick doubles

For a one-off stub you don't need a named class. An **anonymous class** implements the interface inline:

```php
interface Weather
{
    public function temperature(string $city): float;
}

$warm = new class implements Weather {
    public function temperature(string $city): float
    {
        return 24.0;
    }
};

echo $warm->temperature('Lisbon') . "°C\n";
var_dump($warm instanceof Weather);
```

## Controlling time

Code that calls `new DateTimeImmutable()` or `date()` directly gives a different answer every time it runs, so it
can't be tested reliably. Ask for a **clock** instead, and a test can pass one that says whatever time it needs:

```php
interface Clock
{
    public function now(): DateTimeImmutable;
}

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}

final class OpeningHours
{
    public function __construct(private Clock $clock) {}

    public function isOpen(): bool
    {
        $hour = (int) $this->clock->now()->format('G');

        return $hour >= 9 && $hour < 17;
    }
}

$atTen = new class implements Clock {
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-03-02 10:00');
    }
};

var_dump((new OpeningHours($atTen))->isOpen());
```

In production you pass a `SystemClock`; in tests, a clock you control.

## Spies record what happened

When the effect you care about is a call, such as an email being sent, a **spy** keeps a list of the calls so the
test can look at them afterwards:

```php
interface Notifier
{
    public function notify(string $user, string $text): void;
}

final class NotifierSpy implements Notifier
{
    public array $calls = [];

    public function notify(string $user, string $text): void
    {
        $this->calls[] = [$user, $text];
    }
}

function remindOverdue(array $loans, Notifier $notifier): void
{
    foreach ($loans as $user => $daysLate) {
        if ($daysLate > 0) {
            $notifier->notify($user, "Your book is $daysLate days late");
        }
    }
}

$spy = new NotifierSpy();
remindOverdue(['amara' => 3, 'ben' => 0, 'cy' => 10], $spy);

print_r($spy->calls);
```

## Fakes that really work

A **fake** has working behaviour but takes a shortcut, such as keeping data in an array instead of a database. It
lets you test code that saves and loads, quickly and without setup:

```php
interface Counter
{
    public function increment(string $key): int;
}

final class InMemoryCounter implements Counter
{
    private array $counts = [];

    public function increment(string $key): int
    {
        return $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }
}

$counter = new InMemoryCounter();
$counter->increment('dune');
$counter->increment('emma');

echo $counter->increment('dune');
```

## Mocks check expectations

A **mock** is set up with the calls it should receive, and a final `verify()` fails the test if reality differs.
Spies check after the fact; mocks state the expectation up front. Frameworks generate mocks, but the idea is
simple:

```php
final class ExpectOneCall
{
    private int $calls = 0;

    public function __construct(private string $expectedArgument) {}

    public function __invoke(string $argument): void
    {
        if ($argument !== $this->expectedArgument) {
            throw new Exception("Unexpected argument: $argument");
        }
        $this->calls++;
    }

    public function verify(): void
    {
        if ($this->calls !== 1) {
            throw new Exception("Expected 1 call, got {$this->calls}");
        }
    }
}

$mock = new ExpectOneCall('dune');
$mock('dune');
$mock->verify();
echo 'verified';
```

## Don't overdo it

Doubles make tests depend on **how** code works, not just what it does. Mocking every collaborator produces tests
that break whenever you refactor, even when the behaviour is unchanged. Use real objects when they're quick and
predictable, such as value objects and pure functions, and save doubles for the edges: time, randomness, I/O and
other systems.

## Putting it together

A checkout that charges a card and sends a receipt, tested with a stub gateway and a spy mailer:

```php
interface Gateway
{
    public function charge(int $pence): bool;
}

interface Mailer
{
    public function send(string $to, string $subject): void;
}

final class Checkout
{
    public function __construct(private Gateway $gateway, private Mailer $mailer) {}

    public function pay(string $email, int $pence): string
    {
        if (! $this->gateway->charge($pence)) {
            return 'declined';
        }

        $this->mailer->send($email, 'Your receipt');

        return 'paid';
    }
}

$declines = new class implements Gateway {
    public function charge(int $pence): bool
    {
        return false;
    }
};

$mailer = new class implements Mailer {
    public array $sent = [];

    public function send(string $to, string $subject): void
    {
        $this->sent[] = "$to: $subject";
    }
};

echo (new Checkout($declines, $mailer))->pay('amara@example.com', 1999) . ', emails sent: ' . count($mailer->sent);
```

Next, try the quiz to check your understanding, then solve the challenges.
