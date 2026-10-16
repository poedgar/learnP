## Let PHP do the looping

Many loops do the same few jobs: change every item, keep some items, find something, sort. PHP has a
function for each, which is shorter to write and easier to read than the loop. This lesson covers the
ones you'll use every day.

## Searching: `in_array`, `array_search`, `array_keys`

`in_array()` says whether a value is in an array, and `array_search()` gives its key (or `false`).
`array_keys()` and `array_values()` return just the keys or just the values:

```php
$stock = ['Dune' => 3, 'Emma' => 0, 'Circe' => 5];

var_dump(in_array(5, $stock, true));
var_dump(array_search(0, $stock, true));
print_r(array_keys($stock));
print_r(array_values($stock));
```

Pass `true` as the last argument of `in_array()` and `array_search()` to compare strictly, for the
reasons you saw in lesson 3.

## Changing every item: `array_map`

`array_map()` calls a function on every item and returns a new array of the results. The function can
be the name of a built-in function, or a short **arrow function** written `fn ($x) => …`:

```php
$titles = ['dune', 'emma', 'circe'];
print_r(array_map('ucfirst', $titles));

$prices = [10.0, 20.0, 5.0];
print_r(array_map(fn ($price) => $price * 1.2, $prices));
```

An arrow function takes parameters in brackets and returns the expression after `=>`. Functions are
covered properly in the next lesson.

## Keeping some items: `array_filter`

`array_filter()` keeps the items for which a function returns `true`. It keeps their original keys, so
wrap it in `array_values()` when you want a clean list numbered from 0:

```php
$prices = [7.99, 19.99, 4.50, 13.99];

$cheap = array_filter($prices, fn ($price) => $price < 10);
print_r($cheap);
print_r(array_values($cheap));
```

Without a function, `array_filter()` removes every falsy value, which is a quick way to drop empty
strings and `null`s.

## Adding things up: `array_sum`, `max`, `min`, `array_column`

`array_sum()` adds up the values, `max()` and `min()` find the extremes, and `array_column()` pulls one
field out of a list of arrays, which pairs nicely with the others:

```php
$books = [
    ['title' => 'Dune', 'price' => 19.99],
    ['title' => 'Emma', 'price' => 7.99],
    ['title' => 'Circe', 'price' => 13.99],
];

$prices = array_column($books, 'price');
echo array_sum($prices) . "\n";
echo max($prices) . ' / ' . min($prices) . "\n";
print_r(array_column($books, 'price', 'title'));
```

With a third argument, `array_column()` uses that field as the keys.

## Sorting

`sort()` and `rsort()` sort values up or down and renumber the keys. `asort()` and `arsort()` keep each
key with its value, and `ksort()` sorts by key. They all change the array **in place**:

```php
$prices = ['Dune' => 19.99, 'Emma' => 7.99, 'Circe' => 13.99];

$byPrice = $prices;
asort($byPrice);
print_r($byPrice);

$byTitle = $prices;
ksort($byTitle);
print_r($byTitle);
```

## Sorting your own way: `usort`

`usort()` sorts with your own comparison function, which gets two items and returns a negative
number, 0 or a positive number. The spaceship operator `<=>` gives exactly that:

```php
$books = [
    ['title' => 'Dune', 'pages' => 412],
    ['title' => 'Emma', 'pages' => 474],
    ['title' => 'Circe', 'pages' => 393],
];

usort($books, fn ($a, $b) => $a['pages'] <=> $b['pages']);
echo implode(', ', array_column($books, 'title')) . "\n";

usort($books, fn ($a, $b) => $b['pages'] <=> $a['pages']);
echo implode(', ', array_column($books, 'title'));
```

Swap `$a` and `$b` to sort the other way.

## Slicing, combining and more

A few more that save loops: `array_slice()` takes part of an array, `array_merge()` joins arrays,
`array_unique()` removes duplicates, `array_reverse()` flips the order, `range()` makes a list of numbers,
and `array_combine()` pairs keys with values:

```php
$titles = ['Dune', 'Emma', 'Circe', 'Beloved', 'Emma'];

print_r(array_slice($titles, 1, 2));
print_r(array_unique($titles));
print_r(range(1, 5));
print_r(array_combine(['a', 'b'], [1, 2]));
```

## Putting it together

The three cheapest in-stock books, as a formatted list, without a single loop:

```php
$books = [
    ['title' => 'Dune', 'price' => 19.99, 'stock' => 3],
    ['title' => 'Emma', 'price' => 7.99, 'stock' => 0],
    ['title' => 'Circe', 'price' => 13.99, 'stock' => 5],
    ['title' => 'Dracula', 'price' => 7.50, 'stock' => 2],
    ['title' => 'Beloved', 'price' => 16.00, 'stock' => 1],
];

$inStock = array_filter($books, fn ($book) => $book['stock'] > 0);
usort($inStock, fn ($a, $b) => $a['price'] <=> $b['price']);
$cheapest = array_slice($inStock, 0, 3);

echo implode("\n", array_map(fn ($book) => "{$book['title']} (£{$book['price']})", $cheapest));
```

Next, try the quiz to check your understanding, then solve the challenges.
