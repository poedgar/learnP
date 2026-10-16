## What legacy code is

Michael Feathers, in *Working Effectively with Legacy Code*, defines **legacy code** simply as code without tests.
Its age doesn't matter. Without tests you can't change it safely, so every change is a gamble, and people stop
improving it. The aim is to get it under test, then refactor.

The difficulty is that legacy code is usually hard to test **because** it was written without tests: hard-wired
dependencies, global state, functions hundreds of lines long.

## The legacy code change algorithm

Feathers suggests five steps for every change:

1. Identify the **change points**: where the code must change.
2. Find **test points**: where you can observe the behaviour.
3. **Break dependencies** that stop you testing there.
4. Write **characterisation tests** that pin the current behaviour down.
5. Make the change, and **refactor**.

The refactorings in this lesson are the small, careful moves that make steps 3 and 5 safe.

## Seams

A **seam** is a place where you can change what code does without editing it there. In object-oriented code the
usual seam is a constructor parameter: if a class receives its dependency instead of creating it, a test can pass a
different one.

When existing code calls `new SomeClass()` everywhere and you can't change every caller, give the new parameter a
default. Old callers keep working, and tests get a seam:

```php
interface Clock
{
    public function hour(): int;
}

final class RealClock implements Clock
{
    public function hour(): int
    {
        return (int) date('G');
    }
}

final class ShopSign
{
    private Clock $clock;

    public function __construct(?Clock $clock = null)
    {
        $this->clock = $clock ?? new RealClock();
    }

    public function text(): string
    {
        return $this->clock->hour() >= 9 && $this->clock->hour() < 17 ? 'Open' : 'Closed';
    }
}

$nine = new class implements Clock {
    public function hour(): int
    {
        return 9;
    }
};

echo (new ShopSign($nine))->text();
```

## Sprout: write new code in a new place

When you need to add behaviour to a long, untested function, don't add it inside. **Sprout** a new function or class
for the new behaviour, write it test-first, and call it from one line of the old code. The new code is clean and
tested even though its surroundings aren't:

```php
function legacyInvoice(array $lines): string
{
    // ... imagine 300 lines of untested code here ...
    $lines = withoutFreeItems($lines);
    $out = '';
    foreach ($lines as $line) {
        $out .= $line['title'] . ': ' . $line['pence'] . "\n";
    }

    return $out;
}

function withoutFreeItems(array $lines): array
{
    return array_values(array_filter($lines, fn (array $line) => $line['pence'] > 0));
}

echo legacyInvoice([['title' => 'Dune', 'pence' => 999], ['title' => 'Bookmark', 'pence' => 0]]);
```

## Wrap: add behaviour around old code

When new behaviour must happen **every time** an old method runs, such as logging or an audit, **wrap** it: rename the
old method, and give its old name to a new method that does the new thing and calls the old one. Callers don't
change, and the old code isn't touched:

```php
final class Door
{
    public array $events = [];

    public function open(): void
    {
        $this->events[] = 'checked badge';
        $this->unlock();
    }

    private function unlock(): void
    {
        $this->events[] = 'unlocked';
    }
}

$door = new Door();
$door->open();
echo implode(', ', $door->events);
```

## Getting rid of globals

Functions that read `global $config` or `$GLOBALS` depend on hidden state: you can't see the dependency in the
signature, and tests can't vary it without changing global state for everyone. **Pass it in** as a parameter or
constructor argument instead, so the dependency is visible:

```php
final class Settings
{
    public function __construct(public readonly string $greeting) {}
}

function welcome(Settings $settings, string $name): string
{
    return "{$settings->greeting}, $name!";
}

echo welcome(new Settings('Bonjour'), 'Amara');
```

## Small steps and scratch refactoring

With no tests, even renaming a variable can break something. Two habits help:

- Lean on PHP: change one thing, run the code, and let errors point to what you missed.
- **Scratch refactoring**: refactor freely just to understand the code, then throw that work away and start again
  carefully, with tests. Understanding is the goal of the first pass.

## The strangler fig

For a whole system, rewriting from scratch is risky: the old one keeps changing while you work. The **strangler fig**
approach grows the new system around the old one, routing one feature at a time to new code until the old code is
no longer used, just as a strangler fig grows around a tree:

```php
function legacyPrice(string $sku): int
{
    return 1000;
}

function newPrice(string $sku): int
{
    return str_starts_with($sku, 'BOOK-') ? 899 : 1000;
}

function price(string $sku): int
{
    $migrated = ['BOOK-'];

    foreach ($migrated as $prefix) {
        if (str_starts_with($sku, $prefix)) {
            return newPrice($sku);
        }
    }

    return legacyPrice($sku);
}

echo price('BOOK-123') . ' ' . price('MUG-9');
```

## The Gilded Rose

The **Gilded Rose** is the most famous refactoring kata: a small shop's stock system with one deeply nested function
that updates every item's quality each day, and a request for a new feature. The challenges end with it. Its rules:

- Every item has a `sellIn` (days left to sell it) and a `quality`. At the end of each day both go down by 1.
- Once the sell-by date has passed, quality goes down twice as fast.
- Quality is never negative and never above 50.
- **Aged Brie** goes up in quality as it ages (twice as fast after its date).
- **Sulfuras, Hand of Ragnaros** is legendary: it never changes, and its quality is 80.
- **Backstage passes** go up by 1, by 2 when there are 10 days or fewer, by 3 at 5 days or fewer, and drop to 0
  after the concert.

The trick is to characterise first, refactor in small steps, and only then add the feature.

## Putting it together

A characterisation test in miniature: record what a legacy function does, so a refactored version can be checked
against it:

```php
function legacyGrade(int $score): string
{
    if ($score >= 70) {
        return 'A';
    } else {
        if ($score >= 50) {
            return 'B';
        } else {
            return 'C';
        }
    }
}

function grade(int $score): string
{
    return match (true) {
        $score >= 70 => 'A',
        $score >= 50 => 'B',
        default => 'C',
    };
}

$inputs = range(0, 100);
$recorded = array_map(legacyGrade(...), $inputs);

var_dump(array_map(grade(...), $inputs) === $recorded);
```

Next, try the quiz to check your understanding, then solve the challenges.
