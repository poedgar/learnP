## Why algorithms matter

An **algorithm** is a precise recipe for solving a problem. For ten books, almost any recipe is fast enough. For ten
million, the recipe decides whether an answer takes a millisecond or a week. This course covers the classic
algorithms and data structures, all in plain PHP, and how to reason about their speed.

## Counting steps

To compare algorithms without a stopwatch, count roughly how many steps they take as the input grows. Finding the
largest price means looking at every price once:

```php
function largest(array $prices): float
{
    $steps = 0;
    $best = $prices[0];
    foreach ($prices as $price) {
        $steps++;
        if ($price > $best) {
            $best = $price;
        }
    }
    echo "$steps steps for " . count($prices) . " prices\n";
    return $best;
}

largest([7.99, 19.99, 13.99]);
largest(range(1, 1000));
```

Twice the prices, twice the steps. We say it grows **linearly**.

## Big-O notation

**Big-O** describes how the number of steps grows with the size of the input, `n`, ignoring constant factors. The
common ones, from fastest to slowest:

| Big-O | Name | Example |
|-------|------|---------|
| O(1) | constant | reading `$array[$key]` |
| O(log n) | logarithmic | binary search |
| O(n) | linear | looking at every item once |
| O(n log n) | linearithmic | good sorting algorithms |
| O(n²) | quadratic | comparing every item with every other |
| O(2ⁿ) | exponential | trying every combination |

For a million items, O(log n) is about 20 steps, O(n) a million, and O(n²) a trillion.

## Quadratic loops

A loop inside a loop over the same data is usually O(n²). Checking for duplicate titles by comparing every pair:

```php
function hasDuplicate(array $titles): bool
{
    $comparisons = 0;
    for ($i = 0; $i < count($titles); $i++) {
        for ($j = $i + 1; $j < count($titles); $j++) {
            $comparisons++;
            if ($titles[$i] === $titles[$j]) {
                echo "$comparisons comparisons\n";
                return true;
            }
        }
    }
    echo "$comparisons comparisons\n";
    return false;
}

hasDuplicate(['Dune', 'Emma', 'Circe', 'Beloved']);
hasDuplicate(array_map(fn ($n) => "Book $n", range(1, 100)));
```

100 titles take 4,950 comparisons; 1,000 would take nearly half a million. In lesson 6 you'll see how a hash map
does this in O(n).

## Linear search

To find an item in an unsorted list, there's nothing for it but to check each one in turn. That's a **linear
search**, O(n). It returns the index, or -1 when the item isn't there:

```php
function linearSearch(array $items, mixed $target): int
{
    foreach ($items as $index => $item) {
        if ($item === $target) {
            return $index;
        }
    }
    return -1;
}

$titles = ['Emma', 'Dune', 'Circe', 'Beloved'];
echo linearSearch($titles, 'Circe') . ' ' . linearSearch($titles, 'Dracula');
```

PHP's `array_search()` and `in_array()` are linear searches too.

## Binary search

If the list is **sorted**, you can do far better. Look at the middle item: if the target is smaller, it can only be
in the left half; if larger, only in the right half. Each step halves what's left, so it takes O(log n) steps:

```php
function binarySearch(array $sorted, int $target): int
{
    $low = 0;
    $high = count($sorted) - 1;

    while ($low <= $high) {
        $middle = intdiv($low + $high, 2);
        if ($sorted[$middle] === $target) {
            return $middle;
        }
        if ($sorted[$middle] < $target) {
            $low = $middle + 1;
        } else {
            $high = $middle - 1;
        }
    }

    return -1;
}

$years = [1813, 1818, 1897, 1937, 1949, 1965, 1969, 1987];
echo binarySearch($years, 1965) . ' ' . binarySearch($years, 2000);
```

A million sorted items need at most 20 steps.

## Counting the halving

To feel the difference, count the steps each search takes on the same large sorted list:

```php
function stepsLinear(array $items, int $target): int
{
    foreach ($items as $i => $item) {
        if ($item === $target) {
            return $i + 1;
        }
    }
    return count($items);
}

function stepsBinary(array $sorted, int $target): int
{
    [$low, $high, $steps] = [0, count($sorted) - 1, 0];
    while ($low <= $high) {
        $steps++;
        $middle = intdiv($low + $high, 2);
        if ($sorted[$middle] === $target) {
            return $steps;
        }
        $sorted[$middle] < $target ? $low = $middle + 1 : $high = $middle - 1;
    }
    return $steps;
}

$numbers = range(1, 100000);
echo stepsLinear($numbers, 99999) . ' steps linear, ' . stepsBinary($numbers, 99999) . ' steps binary';
```

## Space, too

Big-O also describes **memory**. Building a new array of every item is O(n) extra space; keeping a couple of
variables is O(1). Often you can trade one for the other: lesson 6 spends memory on a lookup table to save time.

```php
function totalPages(array $books): int
{
    $total = 0;
    foreach ($books as $pages) {
        $total += $pages;
    }
    return $total;
}

echo totalPages([412, 474, 393]);
```

That uses O(n) time but only O(1) extra space: one running total, however many books there are.

## Putting it together

Finding where a new book belongs in a sorted list, with a binary search that returns the **insertion point** rather
than -1:

```php
function insertionPoint(array $sorted, int $value): int
{
    $low = 0;
    $high = count($sorted);
    while ($low < $high) {
        $middle = intdiv($low + $high, 2);
        if ($sorted[$middle] < $value) {
            $low = $middle + 1;
        } else {
            $high = $middle;
        }
    }
    return $low;
}

$years = [1813, 1897, 1949, 1987];
$position = insertionPoint($years, 1965);
array_splice($years, $position, 0, [1965]);
echo implode(', ', $years);
```

Next, try the quiz to check your understanding, then solve the challenges.
