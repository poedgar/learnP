## What a test is

A **test** is code that runs your code and checks that it did the right thing. You don't need a framework for that.
The simplest test compares the result with what you expected and says so:

```php
function vat(float $price): float
{
    return round($price * 0.2, 2);
}

$actual = vat(10.0);
$expected = 2.0;

echo $actual === $expected ? 'PASS' : "FAIL: expected $expected, got $actual";
```

Tests like this are worth writing because they run in a second, every time, and catch the day a change breaks
something that used to work.

## Assertions stop at the first problem

An **assertion** is a function that checks one thing and throws an exception if it's wrong. Throwing means the
test stops at the first failure, and whatever runs the tests can catch it and report it:

```php
class AssertionFailed extends Exception {}

function assertTrue(bool $condition, string $message = 'Expected true, got false'): void
{
    if (! $condition) {
        throw new AssertionFailed($message);
    }
}

try {
    assertTrue(str_starts_with('Dune', 'D'));
    assertTrue(str_ends_with('Dune', 'x'), 'Dune should end with x');
    echo 'all passed';
} catch (AssertionFailed $e) {
    echo 'FAIL: ' . $e->getMessage();
}
```

Using your own exception class means a failed assertion can be told apart from a bug that throws something else.

## Good failure messages

A test that fails with "false is not true" sends you hunting. A good message shows both values, in a form where
`'3'` and `3` look different. `var_export()` does that:

```php
foreach ([3, '3', 3.0, true, null, 'Dune'] as $value) {
    echo var_export($value, true) . "\n";
}
```

## Use `===`, not `==`

Loose comparison converts types, so `==` lets wrong answers pass. In a test you almost always want `===`: same
type and same value:

```php
var_dump('1.0' == '1');
var_dump('1.0' === '1');
var_dump(100 == '1e2');
var_dump(null == false);
```

A function that should return `0` but returns `false` or `null` passes a `==` check and fails a `===` one, which is
what you want.

## Floats need a tolerance

Floating-point numbers can't store most decimals exactly, so arithmetic leaves tiny errors. Comparing floats with
`===` fails on results that are correct for every practical purpose:

```php
$total = 0.1 + 0.2;

var_dump($total === 0.3);
echo var_export($total, true) . "\n";
var_dump(abs($total - 0.3) < 0.000001);
```

The fix is to accept any result within a small **delta** of the expected value. Money is better kept in whole pence,
which avoids the problem altogether.

## Comparing arrays

For arrays, `===` means the same keys and values, of the same types, **in the same order**. `==` ignores the order of
keys and converts types:

```php
$a = ['title' => 'Dune', 'year' => 1965];
$b = ['year' => 1965, 'title' => 'Dune'];

var_dump($a == $b);
var_dump($a === $b);
var_dump([1, 2] === [2, 1]);
```

When the order of a list doesn't matter, sort both lists first, then compare them with `===`.

## Expecting an exception

Sometimes the right behaviour is to throw. A test for that runs the code, and fails if it **doesn't** throw:

```php
function age(int $years): int
{
    if ($years < 0) {
        throw new InvalidArgumentException('Age cannot be negative');
    }

    return $years;
}

try {
    age(-1);
    echo 'FAIL: nothing was thrown';
} catch (InvalidArgumentException $e) {
    echo 'PASS: ' . $e->getMessage();
}
```

## What about `assert()`?

PHP has a built-in `assert()`, but whether it runs depends on the `zend.assertions` setting, which is normally
switched off in production, and it's meant for checking assumptions inside your code rather than for tests:

```
assert($price >= 0, 'Price must not be negative');
```

Test code uses its own assertion functions, which always run.

## Putting it together

A handful of assertions makes a quick test of a function, with a clear message at the first problem:

```php
class AssertionFailed extends Exception {}

function check(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function initials(string $name): string
{
    return implode('', array_map(fn ($part) => strtoupper($part[0]), explode(' ', $name)));
}

try {
    check('UKL', initials('ursula k le guin'));
    check('FH', initials('Frank Herbert'));
    check('A', initials('Aristotle'));
    echo 'All tests passed';
} catch (AssertionFailed $e) {
    echo 'FAIL: ' . $e->getMessage();
}
```

Next, try the quiz to check your understanding, then solve the challenges.
