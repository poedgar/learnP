## Code is read far more than it's written

You write a line once, but you and others read it many times: to fix a bug, to add a feature, to understand what
went wrong. **Clean code** is code that's easy to read and safe to change. Most of it comes down to two things: good
names and small, focused functions.

Compare two versions of the same function:

```php
function f(array $l): float
{
    $t = 0;
    foreach ($l as $i) {
        if ($i[2]) {
            $t += $i[1];
        }
    }

    return $t;
}

function totalOfPaidInvoices(array $invoices): float
{
    $total = 0;
    foreach ($invoices as [$number, $amount, $isPaid]) {
        if ($isPaid) {
            $total += $amount;
        }
    }

    return $total;
}

$invoices = [['A1', 120.0, true], ['A2', 80.0, false], ['A3', 40.0, true]];
echo f($invoices) . ' ' . totalOfPaidInvoices($invoices);
```

They do the same thing. Only one tells you what.

## Names that reveal intent

A good name answers why something exists and what it does, so you don't need a comment:

- Variables and properties are **nouns**: `$invoices`, `$dueDate`, `$retryCount`.
- Functions and methods are **verbs** or verb phrases: `sendReminder()`, `calculateTax()`.
- Booleans read as **questions**: `$isPaid`, `$hasStock`, `canWithdraw()`.
- Avoid abbreviations and encodings like `$custNm` or `$strName`; PHP has types for that.
- The wider a name's scope, the more descriptive it should be. `$i` is fine for a three-line loop, not for a
  property.

```php
$d = 21;
$loanPeriodInDays = 21;

$elapsed = 30;
$daysSinceLastLogin = 30;

echo "$d $loanPeriodInDays $elapsed $daysSinceLastLogin";
```

## Functions should do one thing

A function that does one thing is easy to name, test and reuse. If you can only describe it with "and" ("validates
the order **and** saves it **and** emails the customer"), it's doing several things and should be split.

A useful test: can you extract another function from it with a name that isn't just a restatement of the code? If
so, it does more than one thing.

## One level of abstraction

The steps inside a function should be at the same level of detail. Mixing a high-level step ("send the receipt")
with low-level detail (string padding) makes the reader switch gears line by line:

```php
function formatLine(string $title, int $pence): string
{
    return str_pad($title, 20) . str_pad(number_format($pence / 100, 2), 8, ' ', STR_PAD_LEFT);
}

function receipt(array $items): string
{
    $lines = array_map(fn (array $item) => formatLine($item['title'], $item['pence']), $items);
    $lines[] = formatLine('Total', array_sum(array_column($items, 'pence')));

    return implode("\n", $lines);
}

echo receipt([['title' => 'Dune', 'pence' => 999], ['title' => 'Emma', 'pence' => 799]]);
```

`receipt()` reads as a summary; `formatLine()` holds the detail.

## Few parameters, and no flag arguments

Every parameter is something the reader must understand and the caller must get right. Zero to two is easy; three is
fine with good names; more is a sign that some of them belong together in an object.

A **flag argument**, a boolean that changes what the function does, means the function does two things. The call
site `price($order, true)` hides which one. Two functions with clear names are better:

```php
function price(int $pence, bool $member): int
{
    return $member ? intdiv($pence * 90, 100) : $pence;
}

function memberPrice(int $pence): int
{
    return intdiv($pence * 90, 100);
}

echo price(1000, true) . ' ' . memberPrice(1000);
```

## Commands and queries

**Command-query separation**: a function should either **do** something (a command, changing state, returning
nothing) or **answer** something (a query, returning a value, changing nothing), not both. Queries you can call any
number of times without surprises:

```php
final class Stock
{
    private int $count = 3;

    public function isAvailable(): bool
    {
        return $this->count > 0;
    }

    public function takeOne(): void
    {
        if (! $this->isAvailable()) {
            throw new DomainException('Out of stock');
        }
        $this->count--;
    }
}

$stock = new Stock();
$stock->takeOne();
var_dump($stock->isAvailable());
```

## Comments: why, not what

Good code explains **what** it does through names. Comments are for what code can't say: **why** a choice was made, a
warning, a link to a rule. Comments that repeat the code add noise, and comments that have drifted out of date
mislead. Commented-out code is the worst of both: delete it, because version control remembers it.

```php
$rate = 0.2;

// UK standard VAT rate since January 2011.
$vatRate = 0.2;

echo $rate === $vatRate ? 'same value, clearer name' : '';
```

## Putting it together

The same calculation, before and after: names that say what each value is, and a function per idea:

```php
function calc(array $o): float
{
    $s = 0;
    foreach ($o as $x) {
        $s += $x['p'] * $x['q'];
    }

    return $s > 50 ? $s : $s + 4.99;
}

const FREE_DELIVERY_FROM = 50;
const DELIVERY_CHARGE = 4.99;

function subtotal(array $lines): float
{
    return array_sum(array_map(fn (array $line) => $line['p'] * $line['q'], $lines));
}

function deliveryCharge(float $subtotal): float
{
    return $subtotal > FREE_DELIVERY_FROM ? 0 : DELIVERY_CHARGE;
}

$lines = [['p' => 7.99, 'q' => 2], ['p' => 12.5, 'q' => 1]];
echo calc($lines) . ' = ' . (subtotal($lines) + deliveryCharge(subtotal($lines)));
```

Next, try the quiz to check your understanding, then solve the challenges.
