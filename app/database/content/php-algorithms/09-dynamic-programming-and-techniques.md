## Patterns for solving problems

The last lesson collects three techniques that crack a huge range of problems efficiently: **dynamic programming**,
**two pointers** and **sliding windows**. Each turns a brute-force search into something fast by not repeating work.

## Dynamic programming

**Dynamic programming** (DP) solves a problem by solving its smaller sub-problems once each, storing the answers in a
table, and building up to the full answer. It applies when the same sub-problems come up again and again, as they did
in Fibonacci in lesson 3. Memoized recursion is "top-down" DP; filling a table with a loop is "bottom-up":

```php
function fibTable(int $n): int
{
    $table = [0, 1];
    for ($i = 2; $i <= $n; $i++) {
        $table[$i] = $table[$i - 1] + $table[$i - 2];
    }
    return $table[$n];
}

echo fibTable(50);
```

## A classic: making change

What's the fewest coins that make a given amount? Trying every combination explodes. DP builds a table where entry
`a` holds the fewest coins for amount `a`, worked out from smaller amounts already in the table:

```php
function fewestCoins(array $coins, int $amount): int
{
    $best = array_fill(0, $amount + 1, PHP_INT_MAX);
    $best[0] = 0;

    for ($a = 1; $a <= $amount; $a++) {
        foreach ($coins as $coin) {
            if ($coin <= $a && $best[$a - $coin] !== PHP_INT_MAX) {
                $best[$a] = min($best[$a], $best[$a - $coin] + 1);
            }
        }
    }

    return $best[$amount] === PHP_INT_MAX ? -1 : $best[$amount];
}

echo fewestCoins([1, 2, 5], 11) . ' ' . fewestCoins([2], 3);
```

That's O(amount × coins), instead of exponential.

## Comparing two strings

DP works on two inputs as well, with a 2D table. The **longest common subsequence** of two strings (letters in the same
order, not necessarily next to each other) is the basis of "diff" tools:

```php
function lcsLength(string $a, string $b): int
{
    $rows = strlen($a);
    $cols = strlen($b);
    $table = array_fill(0, $rows + 1, array_fill(0, $cols + 1, 0));

    for ($i = 1; $i <= $rows; $i++) {
        for ($j = 1; $j <= $cols; $j++) {
            $table[$i][$j] = $a[$i - 1] === $b[$j - 1]
                ? $table[$i - 1][$j - 1] + 1
                : max($table[$i - 1][$j], $table[$i][$j - 1]);
        }
    }
    return $table[$rows][$cols];
}

echo lcsLength('BOOKSHOP', 'BOOKSHELF');
```

## Two pointers

The **two pointers** technique walks through a sorted array with two indexes at once, often from both ends towards the
middle. Finding a pair with a given sum in a **sorted** list needs no map at all:

```php
function pairWithSum(array $sorted, int $target): ?array
{
    $left = 0;
    $right = count($sorted) - 1;

    while ($left < $right) {
        $sum = $sorted[$left] + $sorted[$right];
        if ($sum === $target) {
            return [$sorted[$left], $sorted[$right]];
        }
        $sum < $target ? $left++ : $right--;
    }
    return null;
}

print_r(pairWithSum([1, 3, 4, 6, 8, 11], 10));
```

Each step moves one pointer inwards, so it's O(n) with O(1) extra memory.

## Two pointers in the same direction

Two pointers can also move the same way: one reads, the other writes. Removing duplicates from a sorted array in place
keeps a "write" pointer for the next unique slot:

```php
function uniqueSorted(array $sorted): array
{
    if ($sorted === []) {
        return [];
    }
    $write = 1;
    for ($read = 1; $read < count($sorted); $read++) {
        if ($sorted[$read] !== $sorted[$write - 1]) {
            $sorted[$write++] = $sorted[$read];
        }
    }
    return array_slice($sorted, 0, $write);
}

echo implode(', ', uniqueSorted([1, 1, 2, 3, 3, 3, 4]));
```

## Sliding windows

A **sliding window** looks at a run of consecutive items, and moves along by adding one item at the front and dropping
one at the back, instead of re-adding the whole run each time. The best 3-day sales total:

```php
function bestRun(array $sales, int $days): int
{
    $window = array_sum(array_slice($sales, 0, $days));
    $best = $window;

    for ($i = $days; $i < count($sales); $i++) {
        $window += $sales[$i] - $sales[$i - $days];
        $best = max($best, $window);
    }
    return $best;
}

echo bestRun([4, 2, 12, 3, 8, 1, 9, 7], 3);
```

Recomputing each window would be O(n × k); sliding it is O(n).

## Windows that grow and shrink

When the window's size isn't fixed, grow it from the right and shrink it from the left as needed. The shortest run of
days whose sales reach a target:

```php
function shortestRunReaching(array $sales, int $target): int
{
    $best = PHP_INT_MAX;
    $sum = 0;
    $left = 0;

    foreach ($sales as $right => $value) {
        $sum += $value;
        while ($sum >= $target) {
            $best = min($best, $right - $left + 1);
            $sum -= $sales[$left++];
        }
    }
    return $best === PHP_INT_MAX ? 0 : $best;
}

echo shortestRunReaching([2, 3, 1, 2, 4, 3], 7);
```

## Putting it together

The **knapsack** problem: a box holds a limited weight, and each book has a weight and a value. Which books give the most
value? DP over "the best value for each capacity, using the first i books":

```php
function knapsack(array $books, int $capacity): int
{
    $best = array_fill(0, $capacity + 1, 0);
    foreach ($books as [$weight, $value]) {
        for ($c = $capacity; $c >= $weight; $c--) {
            $best[$c] = max($best[$c], $best[$c - $weight] + $value);
        }
    }
    return $best[$capacity];
}

$books = [[3, 25], [2, 20], [4, 40], [1, 8]];
echo knapsack($books, 6);
```

Going through capacities from high to low means each book is used at most once. That's the end of the course!
