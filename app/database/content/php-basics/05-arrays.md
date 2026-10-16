## Lists of values

An **array** holds many values in one variable. Write the values between square brackets, and read
one back by its position, its **index**, counting from 0:

```php
$titles = ['Dune', 'Emma', 'Circe'];

echo $titles[0] . "\n";
echo $titles[2] . "\n";
echo count($titles) . ' books';
```

`count()` tells you how many items there are, so the last one is at `count($titles) - 1`.

## Adding, changing and removing

Empty brackets add a value at the end. Assigning to an index changes that item. `unset()` removes one,
and `array_pop()` and `array_shift()` take an item off the end or the start and return it:

```php
$titles = ['Dune', 'Emma'];

$titles[] = 'Circe';
$titles[1] = 'Emma (paperback)';
$last = array_pop($titles);
$first = array_shift($titles);

echo "Took $first and $last, left with: " . implode(', ', $titles);
```

`array_unshift()` and `array_push()` add to the start and the end, for completeness.

## Arrays with named keys

The keys don't have to be numbers. An **associative array** uses strings as keys, which makes it a good
fit for describing one thing, or for looking something up by name:

```php
$book = [
    'title' => 'Dune',
    'author' => 'Frank Herbert',
    'price' => 19.99,
];

echo $book['title'] . ' by ' . $book['author'] . "\n";

$book['price'] = 14.99;
$book['inStock'] = true;

print_r($book);
```

`print_r()` shows a whole array in a readable form. Keys are unique: assigning to an existing key
replaces its value.

## Looking things up safely

Reading a key that doesn't exist gives a warning and `null`. Check first with `isset()` or
`array_key_exists()`, or supply a fallback with `??`:

```php
$prices = ['Dune' => 19.99, 'Emma' => 7.99];

var_dump(isset($prices['Dune']));
var_dump(array_key_exists('Circe', $prices));
echo $prices['Circe'] ?? 'not stocked';
```

`isset()` is also false for a key whose value is `null`; `array_key_exists()` is true for it. Usually
the difference doesn't matter.

## Arrays inside arrays

An array can hold other arrays. A list of books, each one an associative array, is one of the most
common shapes of data you'll handle:

```php
$books = [
    ['title' => 'Dune', 'price' => 19.99],
    ['title' => 'Emma', 'price' => 7.99],
    ['title' => 'Circe', 'price' => 13.99],
];

echo $books[1]['title'] . "\n";

foreach ($books as $book) {
    echo "{$book['title']}: {$book['price']}\n";
}
```

Inside double quotes, wrap an array lookup in `{ }`, as above.

## Looping and changing

`foreach` hands you a **copy** of each item, so changing `$book` inside the loop doesn't change the
array. Loop with the key and assign through it instead:

```php
$prices = ['Dune' => 19.99, 'Emma' => 7.99];

foreach ($prices as $title => $price) {
    $prices[$title] = round($price * 0.9, 2);
}

print_r($prices);
```

## Arrays are copied, not shared

Assigning an array to another variable, or passing it to a function, makes a copy. Changing the copy
leaves the original alone:

```php
$original = ['Dune', 'Emma'];
$copy = $original;
$copy[] = 'Circe';

echo count($original) . ' and ' . count($copy);
```

(Objects work differently, as you'll see in the OOP in PHP course.)

## Spreading and destructuring

`...` spreads an array's items into another array. Square brackets on the **left** of `=` unpack an
array into variables, which is called **destructuring**:

```php
$fantasy = ['The Hobbit', 'Circe'];
$horror = ['Dracula'];
$all = [...$fantasy, ...$horror, 'Dune'];
echo implode(', ', $all) . "\n";

[$first, $second] = $all;
echo "$first, then $second\n";

['title' => $title, 'price' => $price] = ['title' => 'Emma', 'price' => 7.99];
echo "$title costs $price";
```

## Putting it together

Counting how many books each author has, building an associative array as we go:

```php
$books = [
    ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien'],
    ['title' => 'A Wizard of Earthsea', 'author' => 'Ursula K. Le Guin'],
    ['title' => 'The Fellowship of the Ring', 'author' => 'J.R.R. Tolkien'],
];

$counts = [];
foreach ($books as $book) {
    $author = $book['author'];
    $counts[$author] = ($counts[$author] ?? 0) + 1;
}

foreach ($counts as $author => $count) {
    echo "$author: $count\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
