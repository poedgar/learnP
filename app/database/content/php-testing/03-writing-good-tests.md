## Arrange, act, assert

A clear test has three parts, in order: **arrange** the inputs and objects, **act** by calling the one thing being
tested, and **assert** what should be true afterwards. Keeping them apart makes a test easy to read at a glance:

```php
final class Basket
{
    private array $prices = [];

    public function add(float $price): void
    {
        $this->prices[] = $price;
    }

    public function total(): float
    {
        return array_sum($this->prices);
    }
}

// Arrange
$basket = new Basket();
$basket->add(7.99);
$basket->add(12.01);

// Act
$total = $basket->total();

// Assert
echo $total === 20.0 ? 'pass' : "FAIL: got $total";
```

## One behaviour per test, named for it

A test named `testBasket` that checks ten things tells you little when it fails. Name each test for the behaviour it
pins down, as a sentence: "an empty basket totals zero", "a discount code takes off ten percent". When it fails, the
name says what broke.

```php
$tests = [
    'an empty basket totals zero' => fn () => array_sum([]) === 0,
    'adding two prices sums them' => fn () => array_sum([1.5, 2.5]) === 4.0,
];

foreach ($tests as $name => $test) {
    echo ($test() ? 'pass' : 'FAIL') . ": $name\n";
}
```

## Equivalence classes

You can't test every input, so group inputs that the code should treat the same way into **equivalence classes**,
and test one from each. For a function that grades a score from 0 to 100, the classes might be: fail, pass,
distinction, and invalid:

```php
function grade(int $score): string
{
    if ($score < 0 || $score > 100) {
        throw new InvalidArgumentException("Score out of range: $score");
    }

    return match (true) {
        $score >= 70 => 'distinction',
        $score >= 40 => 'pass',
        default => 'fail',
    };
}

foreach ([25, 55, 85] as $score) {
    echo "$score: " . grade($score) . "\n";
}
```

## Boundary values

Bugs gather at the edges: `>` written instead of `>=`, a loop that runs one time too many. So test the values **on**
and **either side of** each boundary. For `grade()`, those are 39, 40, 69 and 70, plus 0, 100, -1 and 101:

```php
function grade(int $score): string
{
    return $score >= 70 ? 'distinction' : ($score >= 40 ? 'pass' : 'fail');
}

function buggyGrade(int $score): string
{
    return $score > 70 ? 'distinction' : ($score > 40 ? 'pass' : 'fail');
}

foreach ([39, 40, 69, 70] as $score) {
    echo "$score: " . grade($score) . ' / buggy: ' . buggyGrade($score) . "\n";
}
```

Testing only 25, 55 and 85 would never notice the buggy version.

## Do your tests catch bugs?

A test suite is only as good as the bugs it would catch. **Mutation testing** makes small deliberate changes to the
code, called *mutants*, such as flipping `>=` to `>` or `+` to `-`, and checks that at least one test fails for each.
A mutant that survives points to a missing test.

```php
$isAdult = fn (int $age) => $age >= 18;

$mutants = [
    '>= became >' => fn (int $age) => $age > 18,
    '18 became 17' => fn (int $age) => $age >= 17,
];

$weakTests = fn (Closure $f) => $f(30) === true && $f(5) === false;
$strongTests = fn (Closure $f) => $f(18) === true && $f(17) === false;

foreach ($mutants as $change => $mutant) {
    echo "$change: weak tests " . ($weakTests($mutant) ? 'missed it' : 'caught it')
        . ', strong tests ' . ($strongTests($mutant) ? 'missed it' : 'caught it') . "\n";
}
```

Tools like Infection do this automatically for PHP. In this lesson's challenges, the checks play that role: your
tests must pass the correct code **and** fail for each mutant.

## Testing the unhappy paths

Invalid input is behaviour too. Test that it's rejected the right way: the right exception class, and nothing
returned:

```php
function parsePrice(string $text): int
{
    if (preg_match('/^\d+\.\d{2}$/', $text) !== 1) {
        throw new InvalidArgumentException("Not a price: $text");
    }

    return (int) str_replace('.', '', $text);
}

foreach (['12.50', '12.5', 'abc'] as $input) {
    try {
        echo "$input → " . parsePrice($input) . " pence\n";
    } catch (InvalidArgumentException $e) {
        echo "$input → rejected\n";
    }
}
```

## Characterisation tests for code you didn't write

Before changing old code that has no tests, first record what it **actually does** today, right or wrong. These
**characterisation tests** turn the current behaviour into expected values, so any change you make by accident shows
up straight away:

```php
function legacyShipping(float $weight): float
{
    if ($weight <= 0) {
        return 0;
    }
    if ($weight < 2) {
        return 3.5;
    }

    return 3.5 + ceil($weight - 2) * 1.25;
}

$recorded = [];
foreach ([0.0, 1.0, 2.0, 2.1, 5.0] as $weight) {
    $recorded[(string) $weight] = legacyShipping($weight);
}

var_dump($recorded);
```

Notice that `legacyShipping(0)` returns the integer `0`, not a float. A characterisation test records that too.

## Putting it together

A small suite for `slugify()` with one case per behaviour, including the edges:

```php
function slug(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    return trim($slug, '-');
}

$cases = [
    'lowercases' => ['Dune', 'dune'],
    'joins words with dashes' => ['the left hand', 'the-left-hand'],
    'drops punctuation' => ["Emma: a novel!", 'emma-a-novel'],
    'trims dashes at the ends' => ['  --Circe--  ', 'circe'],
    'empty stays empty' => ['', ''],
];

foreach ($cases as $name => [$input, $expected]) {
    $actual = slug($input);
    echo ($actual === $expected ? 'pass' : "FAIL (got '$actual')") . ": $name\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
