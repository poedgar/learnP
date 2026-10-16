## Tests as named functions

Once you have assertions, a **test** is just a function that calls the code and asserts. Giving each one a name
that says what it checks, and keeping them in an array, lets you run them all together:

```php
class AssertionFailed extends Exception {}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new AssertionFailed($message);
    }
}

$tests = [
    'trims spaces' => fn () => check(trim('  a  ') === 'a', 'not trimmed'),
    'upper-cases words' => fn () => check(ucwords('le guin') === 'Le Guin', 'wrong case'),
];

foreach ($tests as $name => $test) {
    $test();
    echo "ran: $name\n";
}
```

## One failure shouldn't stop the rest

That loop stops at the first exception. A **test runner** wraps each test in `try`/`catch`, so one failure is
recorded and the other tests still run:

```php
class AssertionFailed extends Exception {}

$tests = [
    'adds' => function () {
        if (1 + 1 !== 2) {
            throw new AssertionFailed('1 + 1 should be 2');
        }
    },
    'is wrong on purpose' => function () {
        throw new AssertionFailed('this test always fails');
    },
    'subtracts' => function () {
        if (5 - 3 !== 2) {
            throw new AssertionFailed('5 - 3 should be 2');
        }
    },
];

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "✓ $name\n";
    } catch (Throwable $e) {
        echo "✗ $name: {$e->getMessage()}\n";
    }
}
```

Catching `Throwable` rather than only `AssertionFailed` matters: a test that hits a `TypeError` or a bug in the code
under test should be reported as a failure, not crash the whole run.

## Failures and errors

Test tools often tell two kinds of problem apart: a **failure** is an assertion that didn't hold (the code gave the
wrong answer), and an **error** is anything else thrown (the code, or the test, broke):

```php
class AssertionFailed extends Exception {}

function outcome(Closure $test): string
{
    try {
        $test();
        return 'pass';
    } catch (AssertionFailed $e) {
        return 'failure: ' . $e->getMessage();
    } catch (Throwable $e) {
        return 'error: ' . $e::class;
    }
}

echo outcome(fn () => null) . "\n";
echo outcome(fn () => throw new AssertionFailed('wrong total')) . "\n";
echo outcome(fn () => intdiv(1, 0)) . "\n";
```

## A fresh start for every test

Tests must not depend on each other. If one test changes shared state, the next may pass or fail depending on the
order. So each test gets **fresh** objects, usually built by a `setUp()` step before every test:

```php
final class Basket
{
    public array $items = [];
}

$basket = new Basket();

$tests = [
    'starts empty' => fn (Basket $b) => count($b->items) === 0,
    'can add an item' => function (Basket $b) {
        $b->items[] = 'Dune';
        return count($b->items) === 1;
    },
    'still starts empty' => fn (Basket $b) => count($b->items) === 0,
];

foreach ($tests as $name => $test) {
    echo $name . ' with a shared basket: ' . ($test($basket) ? 'pass' : 'FAIL') . "\n";
}

foreach ($tests as $name => $test) {
    echo $name . ' with a fresh basket: ' . ($test(new Basket()) ? 'pass' : 'FAIL') . "\n";
}
```

## Finding tests with reflection

PHPUnit's convention is a class whose public methods starting with `test` are tests. Reflection finds them, so you
never forget to register one:

```php
final class StringTests
{
    public function testTrims(): void {}

    public function testPads(): void {}

    public function helper(): void {}
}

$methods = (new ReflectionClass(StringTests::class))->getMethods(ReflectionMethod::IS_PUBLIC);
$names = array_map(fn (ReflectionMethod $m) => $m->getName(), $methods);

print_r(array_values(array_filter($names, fn (string $n) => str_starts_with($n, 'test'))));
```

`getMethods()` lists them in the order they're written. A runner then makes a **new** object for each test method,
calls `setUp()` on it if there is one, and calls the method.

## Many cases, one test

When the same check applies to many inputs, write the test once and feed it a list of **cases**. PHPUnit calls this a
data provider. Each case is reported separately, so you can see exactly which input failed:

```php
$isLeap = fn (int $year) => $year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0);

$cases = [
    'ordinary leap year' => [2024, true],
    'ordinary year' => [2023, false],
    'century' => [1900, false],
    'every 400 years' => [2000, true],
];

foreach ($cases as $label => [$year, $expected]) {
    echo $label . ': ' . ($isLeap($year) === $expected ? 'pass' : 'FAIL') . "\n";
}
```

## Testing what's printed

To test code that `echo`es, catch its output in a buffer: `ob_start()` begins collecting, and `ob_get_clean()`
returns what was collected and stops:

```php
function greet(string $name): void
{
    echo "Hello, $name!";
}

ob_start();
greet('Amara');
$printed = ob_get_clean();

var_dump($printed === 'Hello, Amara!');
```

If the code throws between the two calls, the buffer is left open, so a careful runner closes it in a `finally`
block.

## Putting it together

A compact runner and its report:

```php
class AssertionFailed extends Exception {}

function same(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

$tests = [
    'slug lowercases' => fn () => same('dune', strtolower('Dune')),
    'slug joins words' => fn () => same('the-hobbit', str_replace(' ', '-', 'the hobbit')),
    'slug trims' => fn () => same('emma', trim(' emma')),
];

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo '.';
    } catch (Throwable $e) {
        $failed++;
        echo 'F';
    }
}

echo "\n" . (count($tests) - $failed) . ' passed, ' . $failed . ' failed';
```

Next, try the quiz to check your understanding, then solve the challenges.
