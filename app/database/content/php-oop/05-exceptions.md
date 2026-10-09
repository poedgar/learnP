## Errors you can't ignore

When something goes wrong, a method could return `false` or `null` and hope the caller
checks. Callers forget, and the program carries on with nonsense. An **exception** can't be
ignored: `throw` stops the method on the spot, and unless some code catches the exception, it
stops the whole script with a message saying what went wrong.

```php
// Error: nothing catches the exception, so the script stops here.
class Order
{
    private array $lines = [];

    public function add(string $title, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->lines[$title] = $quantity;
    }
}

$order = new Order();
$order->add('Dune', 0);
echo 'This line never runs.';
```

An exception is an object. Create it with `new`, give it a message, and `throw` it.

## Catching with `try` and `catch`

Code that knows what to do about a problem wraps the risky call in `try`, and handles the
exception in `catch`. The exception object arrives as a variable; `getMessage()` gives its
message:

```php
class Order
{
    public function add(string $title, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        echo "Added $quantity × $title\n";
    }
}

$order = new Order();

try {
    $order->add('Dune', 2);
    $order->add('Circe', 0);
    $order->add('Beloved', 1);
} catch (InvalidArgumentException $e) {
    echo 'Could not add the book: ' . $e->getMessage() . "\n";
}

echo 'The shop is still open.';
```

When `add('Circe', 0)` throws, the rest of the `try` block is skipped (so *Beloved* is never
added), the `catch` block runs, and the script carries on after it.

## `finally`: always runs

A `finally` block runs whether the `try` block finished normally or threw. It's the place for
clean-up that must happen either way:

```php
function printReceipt(bool $printerJammed): void
{
    echo "Printer on\n";

    try {
        if ($printerJammed) {
            throw new RuntimeException('Paper jam');
        }

        echo "Receipt printed\n";
    } finally {
        echo "Printer off\n";
    }
}

printReceipt(false);

try {
    printReceipt(true);
} catch (RuntimeException $e) {
    echo 'Problem: ' . $e->getMessage() . "\n";
}
```

The printer is switched off both times. In the second call the exception still travels on to
the caller after `finally` has run.

## Catching more than one kind

A `try` can have several `catch` blocks, one per kind of problem. PHP uses the **first** one
that matches, so put the most specific first. One `catch` can also accept several kinds,
separated by `|`:

```php
function checkout(int $quantity, bool $paymentWorks): string
{
    if ($quantity < 1) {
        throw new InvalidArgumentException('Nothing to buy.');
    }

    if (! $paymentWorks) {
        throw new RuntimeException('The card was declined.');
    }

    return 'Thank you!';
}

foreach ([[1, true], [0, true], [1, false]] as [$quantity, $paymentWorks]) {
    try {
        echo checkout($quantity, $paymentWorks) . "\n";
    } catch (InvalidArgumentException $e) {
        echo 'Check your basket: ' . $e->getMessage() . "\n";
    } catch (RuntimeException | LogicException $e) {
        echo 'Try again later: ' . $e->getMessage() . "\n";
    }
}
```

## PHP's own family of exceptions

Exceptions are classes, so they form a family through inheritance. Catching a parent catches
all its children too. The top of the family looks like this:

- `Throwable`, the interface every exception implements, with two branches:
  - `Exception`, for problems a program can expect and handle. Its children include
    `RuntimeException` (something went wrong while running) and `LogicException` (the code
    was used wrongly), with children such as `InvalidArgumentException`, `DomainException`
    and `OutOfBoundsException`.
  - `Error`, for mistakes PHP itself detects, such as `TypeError`, `ValueError` and
    `DivisionByZeroError`.

`catch (Exception $e)` doesn't catch an `Error`; `catch (Throwable $e)` catches both.

```php
function share(int $books, int $people): int
{
    return intdiv($books, $people);
}

try {
    echo share(10, 0);
} catch (Exception $e) {
    echo "An Exception\n";
} catch (Error $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}

var_dump(new InvalidArgumentException() instanceof LogicException);
```

## Your own exception classes

Make your own exception by extending one of PHP's. The class name then says exactly what went
wrong, and code can catch just that problem. An exception can carry extra information too,
through its own properties and constructor:

```php
class BasketFullException extends RuntimeException
{
    public function __construct(private int $limit)
    {
        parent::__construct("A basket holds at most $limit books.");
    }

    public function limit(): int
    {
        return $this->limit;
    }
}

class Basket
{
    private array $titles = [];

    public function add(string $title): void
    {
        if (count($this->titles) >= 2) {
            throw new BasketFullException(2);
        }

        $this->titles[] = $title;
    }
}

$basket = new Basket();

try {
    $basket->add('Dune');
    $basket->add('Circe');
    $basket->add('Beloved');
} catch (BasketFullException $e) {
    echo $e->getMessage() . ' (limit: ' . $e->limit() . ")\n";
}
```

## Wrapping one exception in another

Low-level problems often mean something bigger to the rest of the program. Catch the low-level
exception and throw one that explains the bigger picture, passing the original as the third
argument, `$previous`. Nothing is lost: `getPrevious()` gives it back.

```php
class ImportFailedException extends RuntimeException
{
}

function importPrice(string $text): float
{
    try {
        if (! is_numeric($text)) {
            throw new UnexpectedValueException("'$text' is not a number.");
        }

        return (float) $text;
    } catch (UnexpectedValueException $e) {
        throw new ImportFailedException('Could not import the price list.', 0, $e);
    }
}

try {
    importPrice('twelve');
} catch (ImportFailedException $e) {
    echo $e->getMessage() . "\n";
    echo 'Because: ' . $e->getPrevious()->getMessage() . "\n";
}
```

## When not to throw

Exceptions are for situations that **shouldn't** happen in normal use: bad input, a broken
connection, a full basket. Don't use them for ordinary answers. Searching for a book that the
shop doesn't stock isn't an error; it's a normal result, so return `null` or an empty array:

```php
class Catalogue
{
    public function __construct(private array $titles) {}

    public function find(string $title): ?string
    {
        return in_array($title, $this->titles, true) ? $title : null;
    }
}

$catalogue = new Catalogue(['Dune', 'Circe']);

var_dump($catalogue->find('Circe'));
var_dump($catalogue->find('Beloved'));
```

The `?string` return type means "a string, or `null`".

Next, try the quiz to check your understanding, then solve the challenges.
