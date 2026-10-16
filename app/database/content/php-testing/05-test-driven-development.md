## Red, green, refactor

**Test-driven development** (TDD) turns the usual order round: you write a failing test first, then just enough code to
make it pass, then tidy up. Each loop is small, often a minute or two:

1. **Red**: write one small test for behaviour that doesn't exist yet, and watch it fail.
2. **Green**: write the simplest code that makes it pass, even if it looks naive.
3. **Refactor**: improve the code (names, duplication, structure) while all the tests stay green.

Watching the test fail first proves it can fail: a test that passes before the code exists is testing nothing.

## A worked example: the string calculator

The classic first kata: `add(string $numbers): int` adds comma-separated numbers. Start with the simplest case, an
empty string:

```php
function add(string $numbers): int
{
    return 0;
}

var_dump(add('') === 0);
```

That's green. The next test, `add('4') === 4`, fails, so the code grows only enough to pass both:

```php
function add(string $numbers): int
{
    return $numbers === '' ? 0 : (int) $numbers;
}

var_dump(add('') === 0, add('4') === 4);
```

Then two numbers, then any number of them. Now a general solution is the simplest thing that passes, because the
tests have **triangulated** it:

```php
function add(string $numbers): int
{
    return $numbers === '' ? 0 : array_sum(array_map('intval', explode(',', $numbers)));
}

var_dump(add('') === 0, add('4') === 4, add('1,2') === 3, add('1,2,3,4') === 10);
```

## Growing the rules

Each new rule starts as a failing test. "Newlines also separate numbers" and "negative numbers are rejected, listing
all of them" each take one red-green-refactor loop:

```php
function add(string $numbers): int
{
    if ($numbers === '') {
        return 0;
    }

    $values = array_map('intval', preg_split('/[,\n]/', $numbers));
    $negatives = array_filter($values, fn (int $n) => $n < 0);

    if ($negatives !== []) {
        throw new InvalidArgumentException('Negatives not allowed: ' . implode(', ', $negatives));
    }

    return array_sum($values);
}

var_dump(add("1\n2,3") === 6);

try {
    add('1,-2,-5');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

## Fake it, then make it

The second step of a loop can be shamelessly simple. Returning a constant to pass the first test feels like
cheating, but it keeps each step tiny and pushes you to write the **next** test that forces real logic. Three common
moves:

- **Fake it**: return exactly what the test expects.
- **Triangulate**: add a second example that the fake can't pass, which forces a generalisation.
- **Obvious implementation**: when the answer is clear, just write it.

## The order of tests matters

Choose the next test so the code moves forward one small step. Start with degenerate cases (empty, zero, one), then
the simplest real case, then variations, then errors. A test that needs a big jump in the code is a sign to pick a
smaller one first.

```php
$plan = [
    'an empty string is 0',
    'one number is itself',
    'two numbers are added',
    'any count of numbers is added',
    'newlines work as separators',
    'negatives are rejected',
];

foreach ($plan as $i => $test) {
    echo ($i + 1) . ". $test\n";
}
```

Keeping a written list like this, and crossing items off, is part of the technique.

## Refactor only on green

Refactoring means changing the structure without changing the behaviour, and the tests are what tell you the
behaviour hasn't changed. So refactor only when everything passes, and run the tests after every small change. If a
test goes red during a refactoring, undo the last step rather than debugging forwards.

## Katas

A **kata** is a small exercise you repeat to practise the rhythm, like scales on a piano. Popular ones are FizzBuzz,
prime factors, Roman numerals, the bowling game, word wrap and the shopping basket. In the challenges below, each
check is one test in the order you might write them, so you can work through them one red-green loop at a time.

## Putting it together

The finished FizzBuzz kata, with the tests that drove it:

```php
function fizzBuzz(int $n): string
{
    return match (true) {
        $n % 15 === 0 => 'FizzBuzz',
        $n % 3 === 0 => 'Fizz',
        $n % 5 === 0 => 'Buzz',
        default => (string) $n,
    };
}

$tests = [
    'ordinary numbers are themselves' => fizzBuzz(1) === '1' && fizzBuzz(2) === '2',
    'multiples of 3 are Fizz' => fizzBuzz(3) === 'Fizz' && fizzBuzz(9) === 'Fizz',
    'multiples of 5 are Buzz' => fizzBuzz(5) === 'Buzz' && fizzBuzz(20) === 'Buzz',
    'multiples of both are FizzBuzz' => fizzBuzz(15) === 'FizzBuzz' && fizzBuzz(45) === 'FizzBuzz',
];

foreach ($tests as $name => $passed) {
    echo ($passed ? '✓' : '✗') . " $name\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
