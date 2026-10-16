## Functions as values

A function doesn't have to have a name. An **anonymous function**, or **closure**, can be
stored in a variable, passed to another function, and called later. You've used them with
`usort()` and `array_map()`:

```php
$shout = function (string $text): string {
    return strtoupper($text) . '!';
};

echo $shout('dune') . "\n";
print_r(array_map($shout, ['emma', 'circe']));
```

Every closure is an object of the class `Closure`, so it can also be stored in a property or
returned from a method.

## Capturing variables with `use`

A closure can't see the variables around it unless you list them after `use`. They're copied
**when the closure is created**, so later changes don't reach it:

```php
$discount = 10;

$apply = function (float $price) use ($discount): float {
    return $price * (100 - $discount) / 100;
};

$discount = 50;
echo $apply(20.0);
```

That prints 18, not 10: the closure kept the 10 it captured. Write `use (&$discount)` to share
the variable instead of copying it, so changes go both ways.

## Arrow functions

`fn` is a shorter closure for a single expression. It returns the expression's value, and
captures every outside variable it uses automatically, by value:

```php
$minimum = 10.0;
$prices = [7.99, 13.99, 19.99, 9.50];

$affordable = array_filter($prices, fn (float $price) => $price >= $minimum);

print_r(array_values($affordable));
```

Because the capture is a copy, an arrow function can't change outside variables. Use a full
`function () use (&$x)` for that.

## The many shapes of `callable`

Anything PHP can call is a **callable**: a closure, the name of a function as a string, an
object with `__invoke()`, or an array naming an object (or class) and a method:

```php
class Book
{
    public function __construct(public string $title) {}

    public function describe(): string
    {
        return "Book: {$this->title}";
    }

    public static function fromTitle(string $title): self
    {
        return new self($title);
    }
}

$book = new Book('Dune');

echo call_user_func('strtoupper', 'dune') . "\n";
echo call_user_func([$book, 'describe']) . "\n";
echo call_user_func([Book::class, 'fromTitle'], 'Emma')->title;
```

The `callable` type accepts all of them. Strings and arrays are easy to get wrong, though: a typo
in `'strtouper'` is only noticed when the call happens.

## First-class callables: `name(...)`

Writing a function or method call with `...` as its only argument doesn't call it. It turns it
into a `Closure`, checked by PHP straight away:

```php
class Book
{
    public function __construct(public string $title) {}

    public function describe(): string
    {
        return "Book: {$this->title}";
    }
}

$length = strlen(...);
$describe = (new Book('Dune'))->describe(...);

echo $length('Circe') . "\n";
echo $describe() . "\n";
var_dump($length instanceof Closure);
```

`$object->method(...)` and `ClassName::method(...)` work too. This is the modern way to pass an
existing function or method somewhere: `array_map(trim(...), $lines)`.

## Functions that make functions

A function can build and return a closure, with settings baked in. Each call makes a new,
independent closure:

```php
function discountOf(int $percent): Closure
{
    return fn (float $price): float => round($price * (100 - $percent) / 100, 2);
}

$tenOff = discountOf(10);
$halfPrice = discountOf(50);

echo $tenOff(19.99) . ' and ' . $halfPrice(19.99);
```

## `$this` in closures, and `Closure::bind`

A closure created inside a method can use `$this`, and even private members, because it belongs
to that object. `Closure::bind($closure, $object, Class::class)` makes a copy of a closure that
belongs to another object, with access to that class's private members. `$closure->call($object)`
binds and calls in one step:

```php
class Book
{
    private string $secret = 'signed by the author';
}

$peek = function (): string {
    return $this->secret;
};

echo $peek->call(new Book());
```

That's how test tools and frameworks reach inside objects, and also why it should be rare in your
own code: it ignores the class's `private` rules. A `static function () { … }` never has a
`$this` and can't be bound.

## Putting it together

A pipeline built from small callables of different shapes, joined into one closure:

```php
function pipeline(callable ...$steps): Closure
{
    return function (mixed $value) use ($steps): mixed {
        foreach ($steps as $step) {
            $value = $step($value);
        }
        return $value;
    };
}

$slug = pipeline(
    trim(...),
    strtolower(...),
    fn (string $text) => preg_replace('/[^a-z0-9]+/', '-', $text),
);

echo $slug('  The Name of the Wind ');
```

Next, try the quiz to check your understanding, then solve the challenges.
