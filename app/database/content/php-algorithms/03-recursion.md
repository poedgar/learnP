## Solving a problem with a smaller version of itself

A **recursive** function solves a problem by calling itself on a smaller part of it, until the part is so small the
answer is obvious. Every recursive function needs two things:

- a **base case**: a problem small enough to answer directly;
- a **recursive case**: a step that makes the problem smaller and calls the function again.

```php
function countdown(int $n): void
{
    if ($n === 0) {
        echo "Lift off!\n";
        return;
    }
    echo "$n\n";
    countdown($n - 1);
}

countdown(3);
```

## Factorial

The factorial of n, written n!, is 1 × 2 × … × n. It has a natural recursive definition: 0! is 1, and n! is n × (n − 1)!:

```php
function factorial(int $n): int
{
    if ($n <= 1) {
        return 1;
    }
    return $n * factorial($n - 1);
}

echo factorial(5) . ' ' . factorial(10);
```

Each call waits for the smaller one to finish. `factorial(5)` becomes 5 × factorial(4), which becomes 5 × 4 ×
factorial(3), and so on down to 1.

## The call stack

Every call that hasn't finished yet sits on the **call stack**, with its own variables. You can watch the stack grow
and shrink: each call prints on the way in, and again on the way out once the smaller call has finished:

```php
function nest(int $n, int $depth = 0): void
{
    $indent = str_repeat('  ', $depth);
    echo "{$indent}enter $n\n";
    if ($n > 0) {
        nest($n - 1, $depth + 1);
    }
    echo "{$indent}leave $n\n";
}

nest(2);
```

Recursion that goes too deep, or forgets its base case, keeps adding calls until the stack is full. PHP 8.3 and later
stop it with a "Maximum call stack size reached" error; older versions used memory until the script was killed. For
very deep problems, a loop is safer.

## Recursion on structures

Recursion shines on data that contains smaller versions of itself: nested arrays, trees, folders. Summing every number
in a nested list:

```php
function deepSum(array $items): int
{
    $total = 0;
    foreach ($items as $item) {
        $total += is_array($item) ? deepSum($item) : $item;
    }
    return $total;
}

echo deepSum([1, [2, 3], [[4], 5], []]);
```

The base case is hidden in the loop: a number is added directly, and only arrays cause another call.

## Fibonacci, and repeated work

In the Fibonacci sequence, each number is the sum of the two before it: 0, 1, 1, 2, 3, 5, 8… The obvious recursive
version is correct but terribly slow, because it calculates the same values over and over:

```php
$calls = 0;

function fib(int $n): int
{
    global $calls;
    $calls++;
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}

echo fib(20) . " took $calls calls";
```

That's O(2ⁿ): each extra n roughly doubles the work. `fib(40)` would take over a billion calls.

## Memoization

**Memoization** remembers each answer the first time it's worked out, so it's never calculated twice. With a cache,
Fibonacci becomes O(n):

```php
function fastFib(int $n, array &$memo = []): int
{
    if ($n < 2) {
        return $n;
    }
    return $memo[$n] ??= fastFib($n - 1, $memo) + fastFib($n - 2, $memo);
}

echo fastFib(50) . "\n" . fastFib(90);
```

This idea, solving each smaller problem once and reusing it, is the heart of dynamic programming (lesson 9).

## Generating combinations

Recursion is natural for "all the ways to…" problems. Every arrangement (**permutation**) of a list: pick each item in
turn as the first, and arrange the rest recursively:

```php
function permutations(array $items): array
{
    if (count($items) <= 1) {
        return [$items];
    }

    $result = [];
    foreach ($items as $i => $item) {
        $rest = $items;
        unset($rest[$i]);
        foreach (permutations(array_values($rest)) as $permutation) {
            $result[] = [$item, ...$permutation];
        }
    }
    return $result;
}

foreach (permutations(['A', 'B', 'C']) as $p) {
    echo implode('', $p) . ' ';
}
```

n items have n! permutations, so this grows very fast: fine for 5 items, hopeless for 15.

## Recursion or a loop?

Anything recursive can be written with a loop (often with your own stack, as you'll see in lesson 4), and vice versa.
Choose whichever is clearer: recursion for trees and nested data, loops for simple repetition like summing a flat list.

```php
function factorialLoop(int $n): int
{
    $result = 1;
    for ($i = 2; $i <= $n; $i++) {
        $result *= $i;
    }
    return $result;
}

echo factorialLoop(10);
```

## Putting it together

Finding every way to make a total from coin values, which naturally splits into "use this coin" or "don't":

```php
function waysToMake(int $amount, array $coins): int
{
    if ($amount === 0) {
        return 1;
    }
    if ($amount < 0 || $coins === []) {
        return 0;
    }

    $first = $coins[0];
    return waysToMake($amount - $first, $coins) + waysToMake($amount, array_slice($coins, 1));
}

echo waysToMake(10, [5, 2, 1]);
```

Next, try the quiz to check your understanding, then solve the challenges.
