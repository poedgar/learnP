## What refactoring is

**Refactoring** means changing the structure of code without changing what it does. It's done in small, safe steps,
each one leaving the code working, with tests run after every step. Martin Fowler's *Refactoring* catalogues these
steps, with names like *Extract Function* and *Move Function*, so they can be discussed and practised.

Refactoring isn't rewriting. If the tests don't pass between steps, or the behaviour changes, it isn't refactoring.

## Code smells

A **code smell** is a sign that code may be hard to change. It isn't a bug, just a hint of where to look. The
common ones:

- **Duplicated code**: the same logic in several places, so every change must be made several times.
- **Long function**: too much to hold in your head at once.
- **Long parameter list**: hard to call correctly.
- **Feature envy**: a function more interested in another object's data than its own.
- **Data clumps**: the same few values always passed around together.
- **Primitive obsession**: strings and arrays standing in for real concepts like money or email addresses.
- **Switch statements** on a type, repeated in several places.
- **Comments** used to explain code that could explain itself.

## Removing duplication

When the same logic appears twice, extract it once and call it from both places. The differences become parameters:

```php
function ukLabel(string $name, string $postcode): string
{
    return strtoupper($name) . "\n" . strtoupper($postcode) . "\nUNITED KINGDOM";
}

function frLabel(string $name, string $postcode): string
{
    return strtoupper($name) . "\n" . strtoupper($postcode) . "\nFRANCE";
}

function addressLabel(string $name, string $postcode, string $country): string
{
    return strtoupper($name) . "\n" . strtoupper($postcode) . "\n" . strtoupper($country);
}

var_dump(ukLabel('Amara Okafor', 'sw1a 1aa') === addressLabel('Amara Okafor', 'sw1a 1aa', 'United Kingdom'));
```

## Extract Function

The most common refactoring: take a fragment that does one identifiable thing, move it into a function, and name it
for **what** it does. The original code becomes a call that reads like a sentence:

```php
function welcome(array $user): string
{
    $name = $user['nickname'] !== '' ? $user['nickname'] : explode(' ', $user['fullName'])[0];

    return "Welcome back, $name!";
}

function displayName(array $user): string
{
    return $user['nickname'] !== '' ? $user['nickname'] : explode(' ', $user['fullName'])[0];
}

function welcomeRefactored(array $user): string
{
    return 'Welcome back, ' . displayName($user) . '!';
}

$user = ['nickname' => '', 'fullName' => 'Ben Ito'];
echo welcome($user) . ' / ' . welcomeRefactored($user);
```

## Extract Variable

A long expression is easier to read when its parts have names. Extracting a variable for a sub-expression names the
idea without moving it anywhere:

```php
$width = 4.0;
$height = 2.5;
$coats = 2;

$litres = $width * $height * $coats / 10 + ($width * $height > 8 ? 0.5 : 0);

$wallArea = $width * $height;
$paintNeeded = $wallArea * $coats / 10;
$spareForLargeWalls = $wallArea > 8 ? 0.5 : 0;

echo $litres . ' = ' . ($paintNeeded + $spareForLargeWalls);
```

## Split Loop

A loop that calculates two unrelated things is doing two jobs. Splitting it into two loops, or two array functions,
lets each be extracted and named. Don't worry about running through the list twice; it's rarely what makes code slow:

```php
$orders = [['pence' => 1250, 'items' => 2], ['pence' => 800, 'items' => 1], ['pence' => 3000, 'items' => 5]];

$revenue = 0;
$biggest = 0;
foreach ($orders as $order) {
    $revenue += $order['pence'];
    $biggest = max($biggest, $order['items']);
}

$revenueAgain = array_sum(array_column($orders, 'pence'));
$biggestAgain = max(array_column($orders, 'items'));

echo "$revenue $biggest / $revenueAgain $biggestAgain";
```

## Move Function

**Feature envy** is a function that mostly uses another object's data. Moving it into that object puts behaviour next
to the data it needs, and often lets that data become private:

```php
final class Rectangle
{
    public function __construct(private float $width, private float $height) {}

    public function area(): float
    {
        return $this->width * $this->height;
    }
}

echo (new Rectangle(3, 4))->area();
```

Before the move, `area()` was a free function reading `$rect->width` and `$rect->height` from public properties.

## Replace Temp with Query

A temporary variable that holds a calculated value can often become a small method. Other methods can then use it
too, and the original method gets shorter:

```php
final class Rental
{
    public function __construct(private int $days, private int $dailyPence) {}

    public function charge(): int
    {
        return $this->rentalCost() + $this->lateFee();
    }

    private function rentalCost(): int
    {
        return $this->days * $this->dailyPence;
    }

    private function lateFee(): int
    {
        return $this->days > 7 ? 500 : 0;
    }
}

echo (new Rental(9, 300))->charge();
```

## Small steps, always green

Each refactoring above is a few minutes' work and leaves everything working. Run the tests after each one. If they
fail, undo and take a smaller step. Many tiny safe steps add up to large improvements without ever having broken
code for long.

## Putting it together

A function with a long parameter list, a magic number and duplication, refactored step by step into named parts:

```php
function invoiceLine(string $title, int $qty, int $unitPence, bool $member): string
{
    $total = $qty * $unitPence;
    if ($member) {
        $total = $total - intdiv($total * 10, 100);
    }

    return $title . ': £' . number_format($total / 100, 2);
}

const MEMBER_DISCOUNT_PERCENT = 10;

function lineTotal(int $qty, int $unitPence): int
{
    return $qty * $unitPence;
}

function withMemberDiscount(int $pence): int
{
    return $pence - intdiv($pence * MEMBER_DISCOUNT_PERCENT, 100);
}

function pounds(int $pence): string
{
    return '£' . number_format($pence / 100, 2);
}

echo invoiceLine('Dune', 3, 999, true) . "\n";
echo 'Dune: ' . pounds(withMemberDiscount(lineTotal(3, 999)));
```

Next, try the quiz to check your understanding, then solve the challenges.
