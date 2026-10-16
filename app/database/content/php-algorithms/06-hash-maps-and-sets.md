## Looking things up instantly

A **hash map** (also called a dictionary or associative array) stores values under keys, and finds any key in
roughly constant time, O(1), however many entries there are. It does this by turning each key into a number, a
**hash**, that says where to store it. Every PHP array is a hash map, which is why `$prices['Dune']` is instant
even in an array of a million books.

```php
$prices = ['Dune' => 19.99, 'Emma' => 7.99, 'Circe' => 13.99];

echo $prices['Emma'] . "\n";
var_dump(isset($prices['Beloved']));
$prices['Beloved'] = 16.00;
echo count($prices);
```

## Trading memory for speed

Many slow algorithms become fast by building a map first. Searching a list for each of n names is O(n²); turning
the list into a map once makes each check O(1), so the whole job is O(n):

```php
$inStock = ['Dune', 'Emma', 'Circe', 'Beloved'];
$wanted = ['Circe', 'Dracula', 'Dune'];

$lookup = array_flip($inStock);

foreach ($wanted as $title) {
    echo $title . (isset($lookup[$title]) ? ' is in stock' : ' is not stocked') . "\n";
}
```

`array_flip()` swaps keys and values, turning a list of titles into a map from title to position.

## Counting with a map

Counting how often each thing appears is a map from thing to count. One pass, O(n):

```php
$sales = ['Dune', 'Emma', 'Dune', 'Circe', 'Dune', 'Emma'];

$counts = [];
foreach ($sales as $title) {
    $counts[$title] = ($counts[$title] ?? 0) + 1;
}

arsort($counts);
print_r($counts);
```

PHP's `array_count_values()` does exactly this for strings and ints.

## Sets

A **set** holds each value at most once, and answers "is this in it?" quickly. PHP has no set type, but an array
whose keys are the members works perfectly: adding a key twice keeps one copy, and `isset()` is O(1):

```php
$tags = [];
foreach (['fantasy', 'classic', 'fantasy', 'dragons', 'classic'] as $tag) {
    $tags[$tag] = true;
}

echo implode(', ', array_keys($tags)) . "\n";
var_dump(isset($tags['dragons']), isset($tags['horror']));
```

Keys must be ints or strings, so this works for those values.

## Set operations

With sets as arrays, intersection, union and difference come from PHP's key functions:

```php
$amara = ['Dune' => true, 'Emma' => true, 'Circe' => true];
$ben = ['Emma' => true, 'Dracula' => true, 'Circe' => true];

echo 'Both read: ' . implode(', ', array_keys(array_intersect_key($amara, $ben))) . "\n";
echo 'Either read: ' . implode(', ', array_keys($amara + $ben)) . "\n";
echo 'Only Amara: ' . implode(', ', array_keys(array_diff_key($amara, $ben)));
```

`+` on arrays keeps the left side's keys and adds any missing ones from the right.

## The two-sum problem

A classic interview question: find two prices in a list that add up to a gift card's value. Comparing every pair is
O(n²). With a map of prices seen so far, each price just checks whether its partner has already appeared, O(n):

```php
function twoSum(array $prices, int $target): ?array
{
    $seen = [];
    foreach ($prices as $i => $price) {
        $partner = $target - $price;
        if (isset($seen[$partner])) {
            return [$seen[$partner], $i];
        }
        $seen[$price] = $i;
    }
    return null;
}

print_r(twoSum([8, 15, 3, 7, 12], 15));
```

## Grouping

A map from a key to a list groups items by that key, in one pass. Grouping books by their first letter:

```php
$titles = ['Dune', 'Emma', 'Dracula', 'Circe', 'Emily of New Moon', 'Beloved'];

$groups = [];
foreach ($titles as $title) {
    $groups[$title[0]][] = $title;
}

ksort($groups);
foreach ($groups as $letter => $list) {
    echo "$letter: " . implode(', ', $list) . "\n";
}
```

## What makes a good key

Hash maps are only fast if keys are cheap to compare and spread out well, which PHP takes care of for strings and
ints. Two things to watch:

- PHP converts number-like string keys to ints: `$a['42']` and `$a[42]` are the same entry, and `'1.5'` stays a string.
- `true` and `false` used as keys become `1` and `0`, and a float with a fraction is cut down to an int (with a
  deprecation warning since PHP 8.1). Convert such values to strings first.

```php
$map = [];
$map['42'] = 'string key';
$map[42] = 'int key';
$map[true] = 'bool key';
$map['1.5'] = 'stays a string';

var_dump($map);
```

## Putting it together

Finding the first title that appears only once in a list of sales, in two passes over the list:

```php
function firstUnique(array $titles): ?string
{
    $counts = array_count_values($titles);
    foreach ($titles as $title) {
        if ($counts[$title] === 1) {
            return $title;
        }
    }
    return null;
}

echo firstUnique(['Dune', 'Emma', 'Dune', 'Circe', 'Emma']) ?? 'none';
```

Next, try the quiz to check your understanding, then solve the challenges.
