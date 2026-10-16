## Making decisions with `if`

`if` runs a block of code only when a condition is true. `else` runs when it isn't, and `elseif`
tries another condition in between. The first true branch wins, and the rest are skipped:

```php
$stock = 2;

if ($stock === 0) {
    echo 'Sold out';
} elseif ($stock < 5) {
    echo "Only $stock left!";
} else {
    echo 'In stock';
}
```

The braces `{ }` mark each block. Always write them, even around a single line.

## Choosing a value: the ternary operator

When an `if` only picks between two values, the **ternary** operator `condition ? yes : no` says it in
one line:

```php
$copies = 1;
echo "$copies " . ($copies === 1 ? 'copy' : 'copies');
```

Keep ternaries short. Nesting one inside another is hard to read; use `if` or `match` instead.

## `match`: one value, many cases

`match` compares a value against several cases with `===` and returns the result of the first that
matches. Several values can share a case, and `default` catches everything else:

```php
$genre = 'Horror';

$shelf = match ($genre) {
    'Fantasy', 'Science Fiction' => 'Speculative',
    'Horror' => 'Dark Corner',
    'Romance' => 'Front Table',
    default => 'General',
};

echo $shelf;
```

If no case matches and there's no `default`, `match` stops with an error, so you notice the case you
forgot. The older `switch` statement does a similar job, but compares loosely and needs a `break` after
every case; prefer `match`.

## `match (true)` for ranges

To choose by conditions rather than exact values, match against `true`. Each case is a condition,
tried in order:

```php
$price = 13.99;

$band = match (true) {
    $price < 10 => 'Budget',
    $price < 15 => 'Standard',
    default => 'Premium',
};

echo $band;
```

## Repeating with `while`

A `while` loop repeats its block as long as its condition is true, checking before each round. Make
sure something inside changes the condition, or it will never stop:

```php
$copies = 3;

while ($copies > 0) {
    echo "Selling one, $copies left before the sale\n";
    $copies--;
}

echo 'Sold out!';
```

## Counting with `for`

A `for` loop puts the three parts of counting in one line: where to start, when to keep going, and
what to do after each round:

```php
for ($i = 1; $i <= 5; $i++) {
    echo "Shelf $i\n";
}

for ($n = 10; $n > 0; $n -= 3) {
    echo "$n ";
}
```

## Walking through lists with `foreach`

`foreach` visits every item of an array in turn, with no counting needed. With `$key => $value`, you
get each item's key too:

```php
$titles = ['Dune', 'Emma', 'Circe'];

foreach ($titles as $title) {
    echo "- $title\n";
}

$prices = ['Dune' => 19.99, 'Emma' => 7.99];

foreach ($prices as $title => $price) {
    echo "$title costs $price\n";
}
```

You'll use `foreach` constantly once you meet arrays properly in the next lesson.

## `break` and `continue`

Inside any loop, `break` stops the loop completely, and `continue` skips the rest of this round and
goes on to the next:

```php
$prices = [7.99, 25.00, 13.99, 99.99, 4.50];

foreach ($prices as $price) {
    if ($price > 50) {
        echo "Found something too expensive, stopping.\n";
        break;
    }
    if ($price > 20) {
        continue;
    }
    echo "Affordable: $price\n";
}
```

## Putting it together

A delivery plan for a list of orders, using a loop, `match (true)` and `continue`:

```php
$orders = ['A-1' => 2, 'A-2' => 0, 'A-3' => 7, 'A-4' => 15];

foreach ($orders as $reference => $items) {
    if ($items === 0) {
        echo "$reference: nothing to send\n";
        continue;
    }

    $method = match (true) {
        $items <= 3 => 'letter post',
        $items <= 10 => 'parcel',
        default => 'courier',
    };

    echo "$reference: $items items by $method\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
