## Data shapes code

Much messy code isn't caused by the logic but by the data it works with: bare numbers whose meaning you have to guess,
strings standing in for fixed choices, the same three values always passed together, arrays anyone can change. Giving
data a proper shape often makes the logic around it simple.

## Magic numbers

A **magic number** is a literal whose meaning isn't obvious: `if ($pence > 3000)`, `$days * 86400`. Name it with a
constant. The code then says what it means, and when the value changes there's one place to change it:

```php
const SECONDS_PER_DAY = 86400;
const MAX_RENEWALS = 3;

function secondsFor(int $days): int
{
    return $days * SECONDS_PER_DAY;
}

function canRenew(int $renewals): bool
{
    return $renewals < MAX_RENEWALS;
}

echo secondsFor(2) . ' ';
var_dump(canRenew(3));
```

Not every number is magic: `0`, `1` and `100` in a percentage calculation usually explain themselves.

## Enums for fixed choices

Strings like `'pending'`, `'paid'` and `'refunded'` invite typos and accept any value at all. An **enum** lists the
allowed choices, PHP checks them, and behaviour that depends on the choice can live on the enum itself:

```php
enum Status: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}

$status = Status::from('paid');
var_dump($status->isFinal());
echo Status::tryFrom('payed') === null ? 'not a status' : 'a status';
```

## Primitive obsession and value objects

**Primitive obsession** is using strings, ints and arrays for concepts that have rules: a postcode, an ISBN, an
amount of money. Every function that takes one must check it again, or trust that someone else did.

A **value object** wraps the value, checks it once in the constructor, and offers the operations that make sense. It's
**immutable**, and two value objects with the same value are equal:

```php
final class Temperature
{
    public function __construct(public readonly float $celsius)
    {
        if ($celsius < -273.15) {
            throw new InvalidArgumentException('Below absolute zero');
        }
    }

    public function fahrenheit(): float
    {
        return $this->celsius * 9 / 5 + 32;
    }

    public function isWarmerThan(Temperature $other): bool
    {
        return $this->celsius > $other->celsius;
    }
}

$today = new Temperature(21.0);
echo $today->fahrenheit() . ' ';
var_dump($today->isWarmerThan(new Temperature(18.5)));
```

Once a function takes a `Temperature`, it can't receive an impossible one.

## Data clumps

When the same few values always travel together, such as latitude and longitude, or a start and end date, they're a
**data clump**. Missing one is a bug, and every function repeats the parameters. Make them one object:

```php
final class Coordinates
{
    public function __construct(public readonly float $latitude, public readonly float $longitude) {}

    public function __toString(): string
    {
        return sprintf('%.4f, %.4f', $this->latitude, $this->longitude);
    }
}

function describe(string $place, Coordinates $at): string
{
    return "$place is at $at";
}

echo describe('The British Library', new Coordinates(51.5299, -0.1277));
```

## Parameter objects

A long parameter list is often a data clump in disguise. **Introduce Parameter Object** groups related parameters
into a class, which can then carry the rules about them, such as "the end must be after the start":

```php
final class DateRange
{
    public function __construct(public readonly DateTimeImmutable $from, public readonly DateTimeImmutable $to)
    {
        if ($to < $from) {
            throw new InvalidArgumentException('The range ends before it starts');
        }
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->from && $date <= $this->to;
    }
}

$festival = new DateRange(new DateTimeImmutable('2026-08-07'), new DateTimeImmutable('2026-08-31'));
var_dump($festival->contains(new DateTimeImmutable('2026-08-15')));
```

## Encapsulate collections

A public array property lets any code add, remove or replace items, bypassing any rules. Keep the collection
private, offer methods for the changes that are allowed, and hand out a **copy** when someone wants to read it.
Arrays in PHP are copied when assigned, so returning one is already safe:

```php
final class Shelf
{
    private array $titles = [];

    public function add(string $title): void
    {
        if (count($this->titles) >= 3) {
            throw new OverflowException('The shelf is full');
        }
        $this->titles[] = $title;
    }

    public function titles(): array
    {
        return $this->titles;
    }
}

$shelf = new Shelf();
$shelf->add('Dune');
$copy = $shelf->titles();
$copy[] = 'Sneaked in';

echo count($shelf->titles());
```

## Immutable changes with withers

Value objects don't change; they make a changed copy. A method named `with…()` returns a new object, leaving the
original as it was:

```php
final class Address
{
    public function __construct(public readonly string $street, public readonly string $city) {}

    public function withCity(string $city): self
    {
        return new self($this->street, $city);
    }
}

$home = new Address('1 Main Street', 'Leeds');
$moved = $home->withCity('York');

echo $home->city . ' → ' . $moved->city;
```

## Putting it together

A loan with an enum for its state, a constant for the period and a value object for the dates:

```php
const LOAN_PERIOD_DAYS = 14;

enum LoanState
{
    case OnLoan;
    case Returned;
}

final class Loan
{
    public function __construct(
        public readonly string $title,
        public readonly DateTimeImmutable $borrowed,
        public readonly LoanState $state = LoanState::OnLoan,
    ) {}

    public function due(): DateTimeImmutable
    {
        return $this->borrowed->modify('+' . LOAN_PERIOD_DAYS . ' days');
    }

    public function returned(): self
    {
        return new self($this->title, $this->borrowed, LoanState::Returned);
    }
}

$loan = new Loan('Dune', new DateTimeImmutable('2026-03-01'));
echo $loan->due()->format('j M') . ', ' . $loan->returned()->state->name;
```

Next, try the quiz to check your understanding, then solve the challenges.
