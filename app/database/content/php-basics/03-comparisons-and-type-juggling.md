## Asking questions about values

A **comparison** gives `true` or `false`. PHP has the usual ones, `<`, `>`, `<=` and `>=`, and two
kinds of equality: `==` (equal after converting types) and `===` (equal **and** the same type).
Their opposites are `!=` and `!==`:

```php
var_dump(5 > 3);
var_dump(5 == 5.0);
var_dump(5 === 5.0);
var_dump('Dune' !== 'Emma');
```

`5 == 5.0` is true because the values match, but `5 === 5.0` is false: one is an `int`, the other a
`float`.

## Loose comparison converts types

Before comparing with `==`, PHP converts the two values to a common type. That's called **type
juggling**, and it's convenient right up until it surprises you:

```php
var_dump('42' == 42);
var_dump('1' == '01');
var_dump('10' == '1e1');
var_dump(null == false);
var_dump(0 == 'a');
```

When both strings look like numbers, `==` compares them **as numbers**, so `'10' == '1e1'` (ten in
scientific notation) is true. In PHP 8, a number and a string that isn't numeric are compared as
strings, so `0 == 'a'` is false. Older versions said true, which caused real security bugs.

**Use `===` by default.** Reach for `==` only when you really mean "equal after conversion".

## Truthy and falsy

Anywhere PHP needs a yes or no, such as an `if`, it converts the value to `bool`. A handful of values
count as `false`, and **everything else** counts as `true`:

```php
$values = [false, 0, 0.0, '', '0', [], null, 'false', ' ', '0.0', -1];

foreach ($values as $value) {
    echo var_export($value, true) . ' is ' . ($value ? 'truthy' : 'falsy') . "\n";
}
```

The falsy ones are `false`, `0`, `0.0`, `''`, `'0'`, `[]` and `null`. Surprises in the other direction:
`'false'`, `' '` and `'0.0'` are all truthy, because they're non-empty strings other than `'0'`.

## Converting on purpose: casting

Write a type in brackets before a value to **cast** it. Casting never complains; it does its best:

```php
var_dump((int) '42');
var_dump((int) '12.7');
var_dump((int) '42 apples');
var_dump((float) '3.5');
var_dump((string) 99);
var_dump((bool) '0');
```

`(int)` drops any fraction and stops at the first character that isn't part of a number. That's
forgiving, so check input with `is_numeric()` before casting when it matters.

## Checking types

`is_int()`, `is_float()`, `is_string()`, `is_bool()`, `is_array()` and `is_null()` test for one type.
`is_numeric()` accepts numbers and strings that hold a number. `get_debug_type()` names a value's type:

```php
var_dump(is_numeric('42'));
var_dump(is_numeric('4.2e1'));
var_dump(is_numeric('42 apples'));
var_dump(is_int('42'));

echo get_debug_type(42) . ' ' . get_debug_type('42') . ' ' . get_debug_type(null);
```

## Arithmetic with strings

In arithmetic, a numeric string is converted to a number. A string that isn't a number at all stops
the script with a `TypeError`:

```php
echo '10' + 5;
echo "\n";
echo '2.5' * 2;
```

```php
// Error: 'dune' isn't a number, so it can't be used in a sum
echo 'dune' + 1;
```

## Strict types

Function parameters with a type also juggle: a function wanting an `int` happily accepts `'5'` and
converts it. `declare(strict_types=1);` as the very first statement of a file turns that off for calls
made **from that file**, so the wrong type is an error instead of a silent conversion:

```php
declare(strict_types=1);

function double(int $number): int
{
    return $number * 2;
}

echo double(5) . "\n";

try {
    echo double('5');
} catch (TypeError $e) {
    echo 'Refused: ' . $e->getMessage();
}
```

Many projects put `declare(strict_types=1);` at the top of every file, to catch mistakes early.

## Combining conditions

`&&` (and) is true when both sides are, `||` (or) when either is, and `!` (not) flips a value. Both `&&`
and `||` **short-circuit**: they stop as soon as the answer is known, so the right side may never run:

```php
$inStock = true;
$price = 12.0;

var_dump($inStock && $price < 15);
var_dump(! $inStock || $price > 100);

function expensiveCheck(): bool
{
    echo "(expensive check ran) ";
    return true;
}

var_dump(false && expensiveCheck());
var_dump(true || expensiveCheck());
```

## Putting it together

Deciding whether a typed-in quantity is acceptable: a whole number from 1 to 10, checked strictly:

```php
$inputs = ['3', '0', '11', '2.5', 'two', ' 4 '];

foreach ($inputs as $input) {
    $clean = trim($input);
    $ok = is_numeric($clean) && (string) (int) $clean === $clean && (int) $clean >= 1 && (int) $clean <= 10;
    echo var_export($input, true) . ': ' . ($ok ? 'accepted' : 'rejected') . "\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
