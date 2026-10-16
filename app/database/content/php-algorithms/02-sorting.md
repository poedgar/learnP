## Putting things in order

Sorting is one of the most studied problems in computing, because so much else depends on it: binary search needs
sorted data, reports want ranked lists, and duplicates sit next to each other once sorted. In real code you'll call
`sort()` or `usort()`. Writing a few sorts yourself shows how they work and why some are much faster than others.

## Selection sort

**Selection sort** finds the smallest item and swaps it to the front, then the smallest of the rest into second
place, and so on. It's easy to understand, and always O(n²):

```php
function selectionSort(array $items): array
{
    $n = count($items);
    for ($i = 0; $i < $n - 1; $i++) {
        $smallest = $i;
        for ($j = $i + 1; $j < $n; $j++) {
            if ($items[$j] < $items[$smallest]) {
                $smallest = $j;
            }
        }
        [$items[$i], $items[$smallest]] = [$items[$smallest], $items[$i]];
    }
    return $items;
}

echo implode(', ', selectionSort([29, 10, 14, 37, 13]));
```

`[$a, $b] = [$b, $a]` swaps two values in one line.

## Insertion sort

**Insertion sort** builds a sorted section at the front, one item at a time: each new item slides left past larger
items until it's in place, like sorting a hand of cards. It's O(n²) in general, but very fast on lists that are
already nearly sorted:

```php
function insertionSort(array $items): array
{
    for ($i = 1; $i < count($items); $i++) {
        $current = $items[$i];
        $j = $i - 1;
        while ($j >= 0 && $items[$j] > $current) {
            $items[$j + 1] = $items[$j];
            $j--;
        }
        $items[$j + 1] = $current;
    }
    return $items;
}

echo implode(', ', insertionSort([5, 2, 9, 1, 5, 6]));
```

## Merge sort

**Merge sort** splits the list in half, sorts each half (by splitting again, recursively), then **merges** the two
sorted halves by repeatedly taking the smaller front item. Splitting gives log n levels, and each level merges n
items, so it's O(n log n):

```php
function mergeSort(array $items): array
{
    if (count($items) <= 1) {
        return $items;
    }

    $middle = intdiv(count($items), 2);
    $left = mergeSort(array_slice($items, 0, $middle));
    $right = mergeSort(array_slice($items, $middle));

    $merged = [];
    while ($left && $right) {
        $merged[] = $left[0] <= $right[0] ? array_shift($left) : array_shift($right);
    }
    return [...$merged, ...$left, ...$right];
}

echo implode(', ', mergeSort([38, 27, 43, 3, 9, 82, 10]));
```

(`array_shift()` is itself slow on big arrays; a production merge would track positions with indexes instead.)

## Quicksort

**Quicksort** picks one item, the **pivot**, and splits the rest into smaller and larger items, then sorts each side
the same way. It's O(n log n) on average and very fast in practice, but O(n²) in the worst case, such as an
already-sorted list with a poor choice of pivot:

```php
function quickSort(array $items): array
{
    if (count($items) <= 1) {
        return $items;
    }

    $pivot = array_shift($items);
    $smaller = array_filter($items, fn ($x) => $x < $pivot);
    $larger = array_filter($items, fn ($x) => $x >= $pivot);

    return [...quickSort($smaller), $pivot, ...quickSort($larger)];
}

echo implode(', ', quickSort([10, 7, 8, 9, 1, 5]));
```

## Stability

A sort is **stable** if items that compare equal keep their original order. That matters when sorting by one field
after another: sort orders by date, then stably by customer, and each customer's orders stay in date order. Since PHP
8.0, all of PHP's own sorts are stable:

```php
$orders = [
    ['customer' => 'Ben', 'id' => 1],
    ['customer' => 'Amara', 'id' => 2],
    ['customer' => 'Ben', 'id' => 3],
    ['customer' => 'Amara', 'id' => 4],
];

usort($orders, fn ($a, $b) => $a['customer'] <=> $b['customer']);
echo implode(', ', array_map(fn ($o) => "{$o['customer']}#{$o['id']}", $orders));
```

Each customer's orders kept their order: 2 before 4, 1 before 3.

## Comparing the sorts

| Sort | Average | Worst | Stable | Notes |
|------|---------|-------|--------|-------|
| Selection | O(n²) | O(n²) | no | simple, few swaps |
| Insertion | O(n²) | O(n²) | yes | great on nearly sorted data |
| Merge | O(n log n) | O(n log n) | yes | needs extra memory |
| Quick | O(n log n) | O(n²) | no | usually fastest in practice |

No comparison sort can beat O(n log n) in general. PHP's built-in sort is a tuned hybrid, so use it in real code.

## Sorting without comparing: counting sort

When the values are small whole numbers, such as star ratings, you can sort without comparing at all: count how many
of each value there are, then write them out in order. That's O(n + k), where k is the range of values:

```php
function countingSort(array $ratings, int $max): array
{
    $counts = array_fill(0, $max + 1, 0);
    foreach ($ratings as $rating) {
        $counts[$rating]++;
    }

    $sorted = [];
    foreach ($counts as $value => $count) {
        for ($i = 0; $i < $count; $i++) {
            $sorted[] = $value;
        }
    }
    return $sorted;
}

echo implode(' ', countingSort([4, 5, 1, 3, 5, 4, 2, 5], 5));
```

## Putting it together

Ranking books by sales (most first), with ties broken by title, using a stable sort twice: first by the tie-breaker,
then by the main key:

```php
$sales = [
    ['title' => 'Emma', 'sold' => 12],
    ['title' => 'Dune', 'sold' => 40],
    ['title' => 'Circe', 'sold' => 12],
    ['title' => 'Beloved', 'sold' => 3],
];

usort($sales, fn ($a, $b) => $a['title'] <=> $b['title']);
usort($sales, fn ($a, $b) => $b['sold'] <=> $a['sold']);

foreach ($sales as $rank => $book) {
    echo ($rank + 1) . ". {$book['title']} ({$book['sold']})\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
