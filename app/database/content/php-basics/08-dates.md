## Dates are objects

Dates look simple until you add a month to 31 January. PHP's `DateTimeImmutable` does the hard parts
for you. It's an **object**: a value with its own functions, called **methods**, which you call with
`->`:

```php
$date = new DateTimeImmutable('2026-04-08');

echo $date->format('Y-m-d') . "\n";
echo $date->format('d/m/Y') . "\n";
echo $date->format('l jS F Y');
```

`new DateTimeImmutable(…)` creates one from text such as `'2026-04-08'` or `'2026-04-08 14:30'`.
Without an argument it means "now", which changes every time you run the code, so the examples here use
fixed dates.

## Format codes

`format()` takes a pattern in which letters stand for parts of the date. The ones you'll use most:

| Code | Gives | Example |
|------|-------|---------|
| `Y` / `y` | year, 4 or 2 digits | `2026` / `26` |
| `m` / `n` | month, with or without a leading zero | `04` / `4` |
| `d` / `j` | day of the month, with or without a leading zero | `08` / `8` |
| `F` / `M` | month name, full or short | `April` / `Apr` |
| `l` / `D` | day name, full or short | `Wednesday` / `Wed` |
| `S` | English ending for the day | `th` |
| `H:i` | hours and minutes, 24-hour | `14:30` |

```php
$date = new DateTimeImmutable('2026-04-08 14:30');
echo $date->format('D j M, H:i');
```

## Reading dates in other formats

`DateTimeImmutable::createFromFormat()` reads text in a format you describe, such as British dates.
Start the pattern with `!`, or any part you don't mention (like the time) is filled in from the current
moment. It returns `false` when the text doesn't fit:

```php
$date = DateTimeImmutable::createFromFormat('!d/m/Y', '08/04/2026');
echo $date->format('Y-m-d') . "\n";

var_dump(DateTimeImmutable::createFromFormat('!d/m/Y', 'next Tuesday'));
```

Watch out: `'31/02/2026'` doesn't fail; it rolls over to 3 March. If that matters, format the result
back and compare it with the input.

## Moving dates

`modify()` takes plain-English changes such as `'+14 days'`, `'-1 week'` or `'next monday'`. Because the
object is **immutable**, it never changes; each method returns a **new** date:

```php
$borrowed = new DateTimeImmutable('2026-04-08');
$due = $borrowed->modify('+14 days');

echo $borrowed->format('Y-m-d') . ' → ' . $due->format('Y-m-d') . "\n";
echo $borrowed->modify('next monday')->format('l j F') . "\n";
echo $borrowed->modify('last day of this month')->format('Y-m-d');
```

There's also a mutable `DateTime` class whose methods change the object itself. That causes bugs when
the same date is shared, so prefer `DateTimeImmutable`.

## Months are tricky

Adding a month keeps the day number and lets it overflow, so 31 January plus one month is "31 February",
which PHP turns into 3 March. When you mean "the end of next month", say so:

```php
$date = new DateTimeImmutable('2026-01-31');

echo $date->modify('+1 month')->format('Y-m-d') . "\n";
echo $date->modify('last day of next month')->format('Y-m-d');
```

## The time between two dates

`diff()` returns a `DateInterval` describing the gap. Its `days` property is the total number of days,
and `format()` shows it with `%`-codes, such as `%a` for total days or `%m` and `%d` for months and days:

```php
$ordered = new DateTimeImmutable('2026-03-14');
$shipped = new DateTimeImmutable('2026-04-02');

$gap = $ordered->diff($shipped);
echo $gap->days . " days\n";
echo $gap->format('%m month(s) and %d day(s)');
```

`$gap->invert` is 1 when the second date is earlier than the first.

## Comparing dates

The comparison operators work on dates, so you can ask which comes first, or sort them:

```php
$due = new DateTimeImmutable('2026-04-22');
$today = new DateTimeImmutable('2026-04-25');

var_dump($today > $due);
var_dump($due == new DateTimeImmutable('2026-04-22'));
```

## Time zones

A date belongs to a time zone. Give one with `new DateTimeZone(…)`, and convert with `setTimezone()`,
which returns the same moment as seen from somewhere else:

```php
$london = new DateTimeImmutable('2026-04-08 09:00', new DateTimeZone('Europe/London'));
$tokyo = $london->setTimezone(new DateTimeZone('Asia/Tokyo'));

echo $london->format('H:i T') . ' is ' . $tokyo->format('H:i T');
```

Store and compare times in UTC, and convert to a local zone only when showing them to someone.

## Putting it together

A loans list: each due date, and whether it's overdue on a given day:

```php
$today = new DateTimeImmutable('2026-04-25');
$loans = ['Dune' => '2026-04-01', 'Emma' => '2026-04-20', 'Circe' => '2026-04-15'];

foreach ($loans as $title => $borrowed) {
    $due = (new DateTimeImmutable($borrowed))->modify('+14 days');
    $status = $today > $due ? 'overdue by ' . $due->diff($today)->days . ' days' : 'on loan';
    echo "$title: due {$due->format('j M')}, $status\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
