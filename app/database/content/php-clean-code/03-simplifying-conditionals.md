## Why conditionals get messy

Conditionals are where code makes decisions, and where it most often becomes hard to follow: deep nesting, long
`elseif` chains, the same `switch` repeated in several places. A handful of refactorings untangle almost all of them.

## Guard clauses

Nested `if`/`else` makes the reader track every branch at once. A **guard clause** deals with a special case at the
top and returns straight away, so the main path of the function sits at the left margin with no nesting:

```php
function deliveryDays(array $order): int
{
    if ($order['country'] !== 'UK') {
        return 7;
    }
    if ($order['express']) {
        return 1;
    }

    return 3;
}

echo deliveryDays(['country' => 'FR', 'express' => true]) . ' ';
echo deliveryDays(['country' => 'UK', 'express' => true]) . ' ';
echo deliveryDays(['country' => 'UK', 'express' => false]);
```

Once each case returns, there's nothing left for an `else` to do.

## Return the condition

An `if` that returns `true` in one branch and `false` in the other is just the condition, said at length:

```php
function isLongLoanVerbose(int $days): bool
{
    if ($days > 21) {
        return true;
    } else {
        return false;
    }
}

function isLongLoan(int $days): bool
{
    return $days > 21;
}

var_dump(isLongLoanVerbose(30) === isLongLoan(30), isLongLoanVerbose(7) === isLongLoan(7));
```

## Decompose a conditional

When the condition and each branch are complicated, extract each into a function named for what it means. The `if`
then reads as plain English:

```php
function isSummer(DateTimeImmutable $date): bool
{
    $month = (int) $date->format('n');

    return $month >= 6 && $month <= 8;
}

function summerCharge(int $nights): int
{
    return $nights * 12000;
}

function regularCharge(int $nights): int
{
    return $nights * 9000 + 2500;
}

$date = new DateTimeImmutable('2026-07-14');
echo isSummer($date) ? summerCharge(3) : regularCharge(3);
```

## Consolidate conditions with the same result

Several checks that all lead to the same result are one rule. Combine them into one condition, and give it a name:

```php
function isTooSmallToShip(array $parcel): bool
{
    return $parcel['grams'] < 10 || $parcel['lengthCm'] < 5 || $parcel['widthCm'] < 3;
}

function shippingLabel(array $parcel): string
{
    if (isTooSmallToShip($parcel)) {
        return 'Send as a letter';
    }

    return 'Parcel: ' . $parcel['grams'] . 'g';
}

echo shippingLabel(['grams' => 8, 'lengthCm' => 20, 'widthCm' => 15]) . "\n";
echo shippingLabel(['grams' => 900, 'lengthCm' => 30, 'widthCm' => 20]);
```

## Lookup tables instead of chains

A chain of `elseif`s that maps one value to another is really **data**. Put the data in an array, or a `match`, and
the logic shrinks to one lookup. Adding a case becomes adding a line:

```php
const SHELF_FOR_GENRE = [
    'fantasy' => 'F',
    'history' => 'H',
    'poetry' => 'P',
];

function shelf(string $genre): string
{
    return SHELF_FOR_GENRE[$genre] ?? 'General';
}

echo shelf('poetry') . ' ' . shelf('cookery');
```

## Replace a type switch with polymorphism

When the same `switch` on a "type" field appears in several functions, each new type means editing all of them.
Instead, make each type a class implementing a shared interface. Each class answers for itself, and adding a type
means adding a class:

```php
interface Animal
{
    public function sound(): string;
}

final class Dog implements Animal
{
    public function sound(): string
    {
        return 'Woof';
    }
}

final class Cat implements Animal
{
    public function sound(): string
    {
        return 'Miaow';
    }
}

foreach ([new Dog(), new Cat()] as $animal) {
    echo $animal->sound() . "\n";
}
```

This doesn't mean every `match` should become classes. One `match` in one place is fine; the smell is the **same**
decision repeated.

## The Null Object

Code full of `if ($thing === null)` checks repeats the same decision everywhere a missing value might appear. A
**Null Object** is a real object that represents "nothing" with sensible behaviour, so callers don't need the checks:

```php
interface Discount
{
    public function apply(int $pence): int;
}

final class PercentOff implements Discount
{
    public function __construct(private int $percent) {}

    public function apply(int $pence): int
    {
        return $pence - intdiv($pence * $this->percent, 100);
    }
}

final class NoDiscount implements Discount
{
    public function apply(int $pence): int
    {
        return $pence;
    }
}

function discountFor(string $code): Discount
{
    return $code === 'SPRING10' ? new PercentOff(10) : new NoDiscount();
}

echo discountFor('SPRING10')->apply(2000) . ' ' . discountFor('')->apply(2000);
```

## Prefer positive conditions

`if (! $isNotValid)` makes the reader undo two negatives. Name conditions positively, and put the normal case first
when both branches matter:

```php
$items = ['Dune'];

$isEmpty = $items === [];
$hasItems = $items !== [];

var_dump(! $isEmpty, $hasItems);
```

## Putting it together

A ticket price with nested conditionals, rewritten with guard clauses and a lookup table:

```php
function ticketVerbose(int $age, bool $member): int
{
    if ($member) {
        $price = 500;
    } else {
        if ($age < 16) {
            $price = 600;
        } elseif ($age >= 65) {
            $price = 700;
        } else {
            $price = 1200;
        }
    }

    return $price;
}

function ticket(int $age, bool $member): int
{
    if ($member) {
        return 500;
    }
    if ($age < 16) {
        return 600;
    }

    return $age >= 65 ? 700 : 1200;
}

foreach ([[10, false], [40, false], [70, false], [40, true]] as [$age, $member]) {
    echo ticketVerbose($age, $member) . ' = ' . ticket($age, $member) . "\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
