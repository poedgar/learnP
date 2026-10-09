# OOP in PHP Lessons 5–7 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add lessons 5 (*Exceptions*), 6 (*Traits and readonly properties*) and 7 (*Putting objects together*), completing the *OOP in PHP* course.

**Architecture:** Content only, following lessons 2–4 (plan `docs/superpowers/plans/2026-10-09-php-oop-lessons-2-4.md`): each lesson is a Markdown file plus a PHP definition file in `app/database/content/php-oop/`, listed in `PhpOopCourseSeeder::LATER_LESSONS`. The existing `PhpLessonContentTest` and `PhpChallengeHarnessTest` cover the new lessons automatically once they're listed. No runner, API or app changes.

**Tech Stack:** Laravel 13 / PHP (8.5 locally; the production runner is **PHP 8.4**), PHPUnit feature tests (`php artisan test`, which prints JSON here; pipe through `tail -1`). Checks use `expect_same()` and `expect_throws()` from `app/resources/php/check-harness.php`.

**Spec:** `docs/superpowers/specs/2026-10-09-php-oop-lessons-2-7-design.md`

## Global Constraints

- Slugs and positions: `exceptions` (5), `traits-and-readonly-properties` (6), `putting-objects-together` (7); `dataset` is `null`.
- Each lesson: 6–8 `## ` sections, 8 quiz questions with 4 options each, 6 challenges with checks.
- PHP 8.4 only: no pipe operator `|>`, no `clone($x, [...])`, no `array_first()`/`array_last()`, no `#[\NoDiscard]`, nor anything else new in 8.5.
- Checks test behaviour, never the learner's source text, and never read files.
- Checks that compare several values compare them joined into one string (`implode(', ', …)` or `$a . ' / ' . $b`), never as arrays: the harness describes an array only by its length, which tells a learner nothing.
- No challenge's solution appears in the lesson's starter code, theory or quiz (`test_no_challenge_solution_is_already_shown_in_the_lesson`, `test_no_quiz_question_gives_away_a_challenge_solution`).
- Every starter runs and fails at least one check; every solution passes all its checks.
- Theory ` ```php ` blocks are written without `<?php`; one whose first line starts with `// Error:` must stop with a fatal error, every other must not. Quiz `code` is written without `<?php` and runs on its own.
- Each lesson only uses what it or earlier lessons teach (lessons 1–4: classes, constructors, visibility, inheritance, interfaces, abstract classes, static members, constants, enums and `match`; lesson 1's `InvalidArgumentException`).
- Every solution, starter, check, theory example and quiz snippet below was run through the real harness while writing this plan: all solutions pass, all starters fail at least one check, every example behaves as the text says, every quiz snippet prints its marked answer, and none of the constraints above is broken.

## Review Focus

1. **Reasonable alternative solutions must pass**, and plausible wrong ones fail with a message that helps. Try several per challenge, especially: `catch` without a variable, `ctype_digit` vs a regular expression in *An ISBN that never changes*, `new self` vs `new static`, `UNIQUE`-style alternatives such as a different discount formula.
2. **Nothing newer than PHP 8.4**, including in checks (anonymous classes, `fn () => throw`, readonly classes, `catch` without a variable are all 8.0–8.2).
3. **Exceptions must be taught correctly**: `finally` runs on `return`; `Exception` doesn't catch `Error`; `catch` order; `$previous`.
4. **Readonly rules must be taught correctly**: set once from inside the class; readonly classes; withers build new objects.
5. **The course recap** in lesson 7 must match what lessons 1–7 actually teach.

---

## File Structure

| File | Responsibility |
|------|----------------|
| `app/database/content/php-oop/05-exceptions.{md,php}` | Lesson 5 |
| `app/database/content/php-oop/06-traits-and-readonly-properties.{md,php}` | Lesson 6 |
| `app/database/content/php-oop/07-putting-objects-together.{md,php}` | Lesson 7 |
| `app/database/seeders/PhpOopCourseSeeder.php` | `LATER_LESSONS` gains three entries |
| `app/tests/Feature/PhpLessonContentTest.php` | `LESSONS` gains three entries |
| `app/tests/Feature/PhpChallengeHarnessTest.php` | Expected number of PHP challenges: 23 → 29 → 35 → 41 |
| `app/tests/Feature/CourseApiTest.php` | The PHP course's `lessons_count`: 4 → 5 → 6 → 7 |

All commands run from `/Users/macbook/Desktop/dev/learnP/app`.

---

### Task 1: Lesson 5, *Exceptions*

**Files:**
- Create: `app/database/content/php-oop/05-exceptions.md`, `app/database/content/php-oop/05-exceptions.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpLessonContentTest.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: the lesson definition file format, `PhpOopCourseSeeder::LATER_LESSONS` and `PhpLessonContentTest::LESSONS` (from lessons 2–4).
- Produces: lesson `exceptions` at position 5.

- [ ] **Step 1: Update the tests to expect lesson 5**

In `app/tests/Feature/PhpLessonContentTest.php`, add to the end of `LESSONS`:

```php
        'exceptions',
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, change `$this->assertCount(23, $challenges);` to:

```php
        $this->assertCount(29, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, change `->assertJsonPath('data.1.lessons_count', 4)` to:

```php
            ->assertJsonPath('data.1.lessons_count', 5)
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL on the lesson list, the harness count and `data.1.lessons_count`.

- [ ] **Step 3: Register the lesson**

In `app/database/seeders/PhpOopCourseSeeder.php`, add to the end of `LATER_LESSONS`:

```php
        '05-exceptions',
```

- [ ] **Step 4: Write lesson 5's theory**

Create `app/database/content/php-oop/05-exceptions.md`:

````markdown
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
````

- [ ] **Step 5: Write lesson 5's quiz and challenges**

Create `app/database/content/php-oop/05-exceptions.php`:

```php
<?php

// Lesson 5 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'exceptions',
    'title' => 'Exceptions',
    'summary' => 'Signal problems with exceptions, handle them with try, catch and finally, and make your own exception classes that say exactly what went wrong.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function share(int $books, int $people): int
        {
            if ($people < 1) {
                throw new InvalidArgumentException('Share between at least one person.');
            }

            return intdiv($books, $people);
        }

        try {
            echo share(10, 3) . "\n";
            echo share(10, 0) . "\n";
        } catch (InvalidArgumentException $e) {
            echo 'Oops: ' . $e->getMessage();
        }
        PHP,

    'quiz' => [
        [
            'question' => 'In a method, what happens to the lines after a `throw` that runs?',
            'options' => [
                'They run, then the exception is thrown',
                'They are skipped: the method stops at the `throw`',
                'They run only if nothing catches the exception',
                'They run in a `finally` block',
            ],
            'correct_option' => 1,
            'explanation' => '`throw` leaves the method immediately. Execution continues in the nearest matching `catch`, or the script stops if there is none.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                try {
                    echo 'a';
                    throw new RuntimeException('oops');
                    echo 'b';
                } catch (RuntimeException $e) {
                    echo 'c';
                }

                echo 'd';
                PHP,
            'options' => ['`abcd`', '`acd`', '`ac`', '`ab`'],
            'correct_option' => 1,
            'explanation' => '`a` is printed, the throw skips `b`, the `catch` prints `c`, and the script carries on to print `d`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function load(): string
                {
                    try {
                        return 'loaded ';
                    } finally {
                        echo 'cleaned up, ';
                    }
                }

                echo load();
                PHP,
            'options' => ['`loaded `', '`cleaned up, `', '`cleaned up, loaded `', '`loaded cleaned up, `'],
            'correct_option' => 2,
            'explanation' => '`finally` runs even when the `try` block returns. It runs as the function is leaving, before `echo` prints the returned value.',
        ],
        [
            'question' => 'Does `catch (Exception $e)` catch a `TypeError`?',
            'options' => [
                'Yes, every problem is an `Exception`',
                'No: `TypeError` is an `Error`, a separate branch under `Throwable`',
                'Only if the `TypeError` has a message',
                'Only inside a `finally` block',
            ],
            'correct_option' => 1,
            'explanation' => '`Exception` and `Error` are siblings under `Throwable`. Catch `Throwable` (or `Error`) to catch both kinds.',
        ],
        [
            'question' => 'A `try` has `catch (RuntimeException $e)` first and `catch (OutOfStockException $e)` second, where `OutOfStockException` extends `RuntimeException`. What happens when an `OutOfStockException` is thrown?',
            'options' => [
                'The second `catch` runs, because it\'s more specific',
                'Both `catch` blocks run',
                'The first `catch` runs: PHP uses the first one that matches',
                'Neither runs',
            ],
            'correct_option' => 2,
            'explanation' => 'PHP tries `catch` blocks in order, and a child exception matches its parent\'s `catch`. Put the most specific ones first.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class OutOfStockException extends RuntimeException
                {
                }

                try {
                    throw new OutOfStockException('Dune is out of stock.');
                } catch (RuntimeException $e) {
                    echo get_class($e);
                }
                PHP,
            'options' => ['`RuntimeException`', '`OutOfStockException`', '`Exception`', '`Dune is out of stock.`'],
            'correct_option' => 1,
            'explanation' => 'The `catch` accepts any `RuntimeException`, children included, but the object is still an `OutOfStockException`.',
        ],
        [
            'question' => 'What does `$e->getPrevious()` give?',
            'options' => [
                'The exception thrown before this one, anywhere in the program',
                'The exception that was passed as the third argument when this one was created',
                'The previous line of code',
                'Always `null`',
            ],
            'correct_option' => 1,
            'explanation' => 'When you wrap an exception, pass the original as `$previous`; `getPrevious()` returns it, so the cause isn\'t lost.',
        ],
        [
            'question' => 'A customer searches the catalogue for a title the shop doesn\'t stock. What should `find()` do?',
            'options' => [
                'Throw an exception',
                'Stop the script',
                'Return `null` (or an empty result): not finding something is a normal answer',
                'Return `false` and print an error',
            ],
            'correct_option' => 2,
            'explanation' => 'Exceptions are for things that shouldn\'t happen. An empty search result is an ordinary outcome.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Sensible quantities',
            'prompt' => "Give `OrderLine` a method `setQuantity(int \$quantity): void`. A quantity must be from 1 to 10. For anything else, throw an `InvalidArgumentException` with the message `Quantity must be between 1 and 10.` and keep the old quantity.",
            'starter_code' => <<<'PHP'
                <?php

                class OrderLine
                {
                    private int $quantity = 1;

                    public function __construct(public string $title) {}

                    public function quantity(): int
                    {
                        return $this->quantity;
                    }

                    // Add setQuantity() here.
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class OrderLine
                {
                    private int $quantity = 1;

                    public function __construct(public string $title) {}

                    public function quantity(): int
                    {
                        return $this->quantity;
                    }

                    public function setQuantity(int $quantity): void
                    {
                        if ($quantity < 1 || $quantity > 10) {
                            throw new InvalidArgumentException('Quantity must be between 1 and 10.');
                        }

                        $this->quantity = $quantity;
                    }
                }
                PHP,
            'hint' => 'Check the quantity first and `throw new InvalidArgumentException(…)` if it\'s out of range; only set `$this->quantity` after the check.',
            'checks' => [
                ['label' => 'setQuantity(3) sets the quantity', 'code' => <<<'PHP'
                    $line = new OrderLine('Dune');
                    $line->setQuantity(3);
                    return expect_same($line->quantity(), 3);
                    PHP],
                ['label' => '1 and 10 are allowed', 'code' => <<<'PHP'
                    $line = new OrderLine('Dune');
                    $line->setQuantity(10);
                    $line->setQuantity(1);
                    return expect_same($line->quantity(), 1);
                    PHP],
                ['label' => '0 and 11 throw an InvalidArgumentException', 'code' => <<<'PHP'
                    $line = new OrderLine('Dune');
                    $low = expect_throws(InvalidArgumentException::class, fn () => $line->setQuantity(0));
                    return $low === true ? expect_throws(InvalidArgumentException::class, fn () => $line->setQuantity(11)) : $low;
                    PHP],
                ['label' => 'The message explains the limits', 'code' => <<<'PHP'
                    try {
                        (new OrderLine('Dune'))->setQuantity(0);
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), 'Quantity must be between 1 and 10.');
                    }
                    return 'setQuantity(0) should throw an InvalidArgumentException.';
                    PHP],
                ['label' => 'A refused quantity leaves the old one', 'code' => <<<'PHP'
                    $line = new OrderLine('Dune');
                    $line->setQuantity(4);
                    try {
                        $line->setQuantity(50);
                    } catch (InvalidArgumentException) {
                    }
                    return expect_same($line->quantity(), 4);
                    PHP],
            ],
        ],
        [
            'title' => 'Sorry, not this time',
            'prompt' => "`Shop::buy()` throws a `RuntimeException` when a book is out of stock. Write a function `tryToBuy(Shop \$shop, string \$title): string` that buys the book and returns `Bought Dune`, or, if `buy()` throws a `RuntimeException`, returns `Sorry: ` followed by the exception's message.",
            'starter_code' => <<<'PHP'
                <?php

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new RuntimeException("$title is out of stock.");
                        }

                        $this->stock[$title]--;
                    }
                }

                function tryToBuy(Shop $shop, string $title): string
                {
                    $shop->buy($title);

                    return 'Bought ' . $title;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new RuntimeException("$title is out of stock.");
                        }

                        $this->stock[$title]--;
                    }
                }

                function tryToBuy(Shop $shop, string $title): string
                {
                    try {
                        $shop->buy($title);

                        return 'Bought ' . $title;
                    } catch (RuntimeException $e) {
                        return 'Sorry: ' . $e->getMessage();
                    }
                }
                PHP,
            'hint' => 'Wrap the `buy()` call in `try`, and `return \'Sorry: \' . $e->getMessage()` in `catch (RuntimeException $e)`.',
            'checks' => [
                ['label' => "Buying a book in stock gives 'Bought Dune'", 'code' => "return expect_same(tryToBuy(new Shop(['Dune' => 2]), 'Dune'), 'Bought Dune');"],
                ['label' => "An out-of-stock book gives 'Sorry: Circe is out of stock.'", 'code' => "return expect_same(tryToBuy(new Shop(['Dune' => 2]), 'Circe'), 'Sorry: Circe is out of stock.');"],
                ['label' => 'The last copy can be bought, then not again', 'code' => <<<'PHP'
                    $shop = new Shop(['Dune' => 1]);
                    $first = tryToBuy($shop, 'Dune');
                    return expect_same($first . ' / ' . tryToBuy($shop, 'Dune'), 'Bought Dune / Sorry: Dune is out of stock.');
                    PHP],
            ],
        ],
        [
            'title' => 'Out of stock',
            'prompt' => 'Declare your own exception class `OutOfStockException`, extending `RuntimeException`, and change `Shop::buy()` to throw it (with the same message) when a book is out of stock.',
            'starter_code' => <<<'PHP'
                <?php

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new RuntimeException("$title is out of stock.");
                        }

                        $this->stock[$title]--;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class OutOfStockException extends RuntimeException
                {
                }

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new OutOfStockException("$title is out of stock.");
                        }

                        $this->stock[$title]--;
                    }
                }
                PHP,
            'hint' => '`class OutOfStockException extends RuntimeException {}` is a whole exception class. Then throw it instead of `RuntimeException`.',
            'checks' => [
                ['label' => 'OutOfStockException extends RuntimeException', 'code' => <<<'PHP'
                    if (! class_exists('OutOfStockException', false)) return 'There is no class named OutOfStockException.';
                    return is_subclass_of('OutOfStockException', 'RuntimeException') ?: 'OutOfStockException should extend RuntimeException.';
                    PHP],
                ['label' => 'Buying an out-of-stock book throws OutOfStockException', 'code' => "return expect_throws('OutOfStockException', fn () => (new Shop([]))->buy('Dune'));"],
                ['label' => 'The message is still \'Dune is out of stock.\'', 'code' => <<<'PHP'
                    try {
                        (new Shop([]))->buy('Dune');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Dune is out of stock.');
                    }
                    return 'buy() should throw when the book is out of stock.';
                    PHP],
                ['label' => 'Books in stock can still be bought', 'code' => <<<'PHP'
                    (new Shop(['Dune' => 1]))->buy('Dune');
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Which book was it?',
            'prompt' => "Make `OutOfStockException` carry the book's title. Its constructor takes just the title, sets the message to `<title> is out of stock.`, and a method `title(): string` returns the title. `Shop::buy()` throws `new OutOfStockException(\$title)`.",
            'starter_code' => <<<'PHP'
                <?php

                class OutOfStockException extends RuntimeException
                {
                }

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new OutOfStockException("$title is out of stock.");
                        }

                        $this->stock[$title]--;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class OutOfStockException extends RuntimeException
                {
                    public function __construct(private string $title)
                    {
                        parent::__construct("$title is out of stock.");
                    }

                    public function title(): string
                    {
                        return $this->title;
                    }
                }

                class Shop
                {
                    public function __construct(private array $stock) {}

                    public function buy(string $title): void
                    {
                        if (($this->stock[$title] ?? 0) < 1) {
                            throw new OutOfStockException($title);
                        }

                        $this->stock[$title]--;
                    }
                }
                PHP,
            'hint' => 'Give the exception a constructor `(private string $title)` that calls `parent::__construct("$title is out of stock.")`, plus a `title()` method.',
            'checks' => [
                ['label' => "new OutOfStockException('Dune') has the right message", 'code' => "return expect_same((new OutOfStockException('Dune'))->getMessage(), 'Dune is out of stock.');"],
                ['label' => 'title() gives the title', 'code' => "return expect_same((new OutOfStockException('Circe'))->title(), 'Circe');"],
                ['label' => 'Shop::buy() throws one that knows the title', 'code' => <<<'PHP'
                    try {
                        (new Shop(['Dune' => 1]))->buy('Beloved');
                    } catch (OutOfStockException $e) {
                        return expect_same($e->title(), 'Beloved');
                    }
                    return 'buy() should throw an OutOfStockException for a book that isn\'t in stock.';
                    PHP],
            ],
        ],
        [
            'title' => 'Always close the till',
            'prompt' => "Write a function `sellWithTill(Till \$till, callable \$sale): void`. It opens the till, runs `\$sale()`, and then closes the till, **even if `\$sale()` throws**. If it throws, the exception must still reach whoever called `sellWithTill()`.",
            'starter_code' => <<<'PHP'
                <?php

                class Till
                {
                    private bool $open = false;

                    public function open(): void
                    {
                        $this->open = true;
                    }

                    public function close(): void
                    {
                        $this->open = false;
                    }

                    public function isOpen(): bool
                    {
                        return $this->open;
                    }
                }

                function sellWithTill(Till $till, callable $sale): void
                {
                    $till->open();
                    $sale();
                    $till->close();
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Till
                {
                    private bool $open = false;

                    public function open(): void
                    {
                        $this->open = true;
                    }

                    public function close(): void
                    {
                        $this->open = false;
                    }

                    public function isOpen(): bool
                    {
                        return $this->open;
                    }
                }

                function sellWithTill(Till $till, callable $sale): void
                {
                    $till->open();

                    try {
                        $sale();
                    } finally {
                        $till->close();
                    }
                }
                PHP,
            'hint' => 'Put `$sale()` in a `try` block and `$till->close()` in `finally`. No `catch` is needed: the exception carries on to the caller by itself.',
            'checks' => [
                ['label' => 'The till is open during the sale', 'code' => <<<'PHP'
                    $till = new Till();
                    $wasOpen = false;
                    sellWithTill($till, function () use ($till, &$wasOpen) {
                        $wasOpen = $till->isOpen();
                    });
                    return $wasOpen ?: 'The till should be open while the sale runs.';
                    PHP],
                ['label' => 'The till is closed after a sale', 'code' => <<<'PHP'
                    $till = new Till();
                    sellWithTill($till, fn () => null);
                    return ! $till->isOpen() ?: 'The till should be closed after the sale.';
                    PHP],
                ['label' => 'The till is closed even when the sale throws', 'code' => <<<'PHP'
                    $till = new Till();
                    try {
                        sellWithTill($till, fn () => throw new RuntimeException('Card declined'));
                    } catch (RuntimeException) {
                    }
                    return ! $till->isOpen() ?: 'The till was left open after the sale threw.';
                    PHP],
                ['label' => 'The exception still reaches the caller', 'code' => "return expect_throws(RuntimeException::class, fn () => sellWithTill(new Till(), fn () => throw new RuntimeException('Card declined')));"],
            ],
        ],
        [
            'title' => 'Explain the bigger problem',
            'prompt' => "`PriceList::priceOf()` throws an `OutOfBoundsException` for a title it doesn't know. Declare `CheckoutException`, extending `RuntimeException`, and write `priceForCheckout(PriceList \$prices, string \$title): float`. It returns the price, or throws a `CheckoutException` with the message `Can't check out Dune: it has no price.` (for Dune), passing the original `OutOfBoundsException` as the previous exception.",
            'starter_code' => <<<'PHP'
                <?php

                class PriceList
                {
                    public function __construct(private array $prices) {}

                    public function priceOf(string $title): float
                    {
                        if (! isset($this->prices[$title])) {
                            throw new OutOfBoundsException("No price for $title.");
                        }

                        return $this->prices[$title];
                    }
                }

                // Declare CheckoutException and priceForCheckout() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class PriceList
                {
                    public function __construct(private array $prices) {}

                    public function priceOf(string $title): float
                    {
                        if (! isset($this->prices[$title])) {
                            throw new OutOfBoundsException("No price for $title.");
                        }

                        return $this->prices[$title];
                    }
                }

                class CheckoutException extends RuntimeException
                {
                }

                function priceForCheckout(PriceList $prices, string $title): float
                {
                    try {
                        return $prices->priceOf($title);
                    } catch (OutOfBoundsException $e) {
                        throw new CheckoutException("Can't check out $title: it has no price.", 0, $e);
                    }
                }
                PHP,
            'hint' => 'Catch the `OutOfBoundsException` and `throw new CheckoutException($message, 0, $e)`: the third argument is the previous exception.',
            'checks' => [
                ['label' => 'A known title gives its price', 'code' => "return expect_same(priceForCheckout(new PriceList(['Dune' => 19.99]), 'Dune'), 19.99);"],
                ['label' => 'An unknown title throws a CheckoutException', 'code' => <<<'PHP'
                    if (! class_exists('CheckoutException', false) || ! is_subclass_of('CheckoutException', 'RuntimeException')) return 'Declare CheckoutException, extending RuntimeException.';
                    return expect_throws('CheckoutException', fn () => priceForCheckout(new PriceList([]), 'Dune'));
                    PHP],
                ['label' => 'Its message explains the problem', 'code' => <<<'PHP'
                    try {
                        priceForCheckout(new PriceList([]), 'Circe');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), "Can't check out Circe: it has no price.");
                    }
                    return 'priceForCheckout() should throw for an unknown title.';
                    PHP],
                ['label' => 'The original OutOfBoundsException is kept as the previous one', 'code' => <<<'PHP'
                    try {
                        priceForCheckout(new PriceList([]), 'Dune');
                    } catch (RuntimeException $e) {
                        return $e->getPrevious() instanceof OutOfBoundsException ?: 'Pass the OutOfBoundsException as the third argument, $previous.';
                    }
                    return 'priceForCheckout() should throw for an unknown title.';
                    PHP],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if Pint's `single_quote` fixer complains, run `vendor/bin/pint database tests`, check the diff only changes quote style, and re-run the tests).

- [ ] **Step 7: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/05-exceptions.md app/database/content/php-oop/05-exceptions.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 5: Exceptions"
```

---

### Task 2: Lesson 6, *Traits and readonly properties*

**Files:**
- Create: `app/database/content/php-oop/06-traits-and-readonly-properties.md`, `app/database/content/php-oop/06-traits-and-readonly-properties.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpLessonContentTest.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: the lesson definition file format, `PhpOopCourseSeeder::LATER_LESSONS` and `PhpLessonContentTest::LESSONS` (from lessons 2–4).
- Produces: lesson `traits-and-readonly-properties` at position 6.

- [ ] **Step 1: Update the tests to expect lesson 6**

In `app/tests/Feature/PhpLessonContentTest.php`, add to the end of `LESSONS`:

```php
        'traits-and-readonly-properties',
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, change `$this->assertCount(29, $challenges);` to:

```php
        $this->assertCount(35, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, change `->assertJsonPath('data.1.lessons_count', 5)` to:

```php
            ->assertJsonPath('data.1.lessons_count', 6)
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL on the lesson list, the harness count and `data.1.lessons_count`.

- [ ] **Step 3: Register the lesson**

In `app/database/seeders/PhpOopCourseSeeder.php`, add to the end of `LATER_LESSONS`:

```php
        '06-traits-and-readonly-properties',
```

- [ ] **Step 4: Write lesson 6's theory**

Create `app/database/content/php-oop/06-traits-and-readonly-properties.md`:

````markdown
## Sharing code without a parent: traits

Books, customers and events all need a web-address-friendly version of their name, a
**slug**: `the-name-of-the-wind`. They aren't kinds of the same thing, so a shared parent
class would be wrong. A **trait** is a bundle of methods (and properties) that any class can
pull in with `use`, as if they had been written inside it:

```php
trait HasSlug
{
    public function slug(): string
    {
        return strtolower(str_replace(' ', '-', $this->name));
    }
}

class Book
{
    use HasSlug;

    public function __construct(public string $name) {}
}

class Event
{
    use HasSlug;

    public function __construct(public string $name, public string $date) {}
}

echo (new Book('The Name of the Wind'))->slug() . "\n";
echo (new Event('Poetry Night', '2026-05-01'))->slug() . "\n";
```

Inside the trait, `$this` is whichever object the method was called on. `HasSlug` simply
assumes the class has a `$name` property.

## Traits with properties

A trait can bring properties as well as methods. Each class that uses it gets its own copy of
them, just as if they'd been declared in the class:

```php
trait CountsViews
{
    private int $views = 0;

    public function view(): void
    {
        $this->views++;
    }

    public function views(): int
    {
        return $this->views;
    }
}

class Book
{
    use CountsViews;
}

class Author
{
    use CountsViews;
}

$book = new Book();
$book->view();
$book->view();

$author = new Author();
$author->view();

echo $book->views() . ' and ' . $author->views();
```

`class_uses($book)` lists the traits a class uses, and `instanceof` doesn't work with traits:
a trait isn't a type, only a way of sharing code.

## Using several traits, and conflicts

A class can use several traits, separated by commas. If two of them have a method with the
same name, PHP can't guess which one you mean and refuses the class:

```php
// Error: both traits have a greet() method, and PHP won't choose between them.
trait Friendly
{
    public function greet(): string
    {
        return 'Hello!';
    }
}

trait Formal
{
    public function greet(): string
    {
        return 'Good afternoon.';
    }
}

class ShopAssistant
{
    use Friendly, Formal;
}
```

Settle it in a block after `use`. `insteadof` picks the winner, and `as` keeps the loser
under another name:

```php
trait Friendly
{
    public function greet(): string
    {
        return 'Hello!';
    }
}

trait Formal
{
    public function greet(): string
    {
        return 'Good afternoon.';
    }
}

class ShopAssistant
{
    use Friendly, Formal {
        Formal::greet insteadof Friendly;
        Friendly::greet as greetRegular;
    }
}

$assistant = new ShopAssistant();

echo $assistant->greet() . "\n";
echo $assistant->greetRegular() . "\n";
```

## Readonly properties

Some values should never change once an object exists: a book's ISBN, an order's number. Mark
a typed property `readonly` and it can be set **once**, from inside the class (usually in the
constructor). After that, any attempt to change it is an error:

```php
// Error: $number is readonly, so it can't be changed after the constructor sets it.
class Order
{
    public function __construct(public readonly int $number) {}
}

$order = new Order(1042);
echo $order->number . "\n";

$order->number = 1043;
```

Reading a readonly property is fine; that's why `$number` can still be public. Readonly
properties must have a type.

## Readonly classes

When **every** property should be readonly, mark the whole class `readonly` instead of
repeating the word:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postcode,
    ) {}
}

$address = new Address('12 Market Street', 'Leeds', 'LS1 6DT');
echo $address->city;
```

## Changing an object that can't change

If an address can't change, how does a customer move house? You don't change the object; you
make a **new** one with the difference, and leave the original alone. By convention such
methods start with `with`, and are called **withers**:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}

    public function withCity(string $city): static
    {
        return new static($this->street, $city);
    }
}

$old = new Address('12 Market Street', 'Leeds');
$new = $old->withCity('York');

echo $old->city . "\n";
echo $new->city . "\n";
```

Objects that never change are easy to trust: you can hand one to any code, knowing nothing can
alter it behind your back.

## Value objects

Small immutable objects like `Address` are called **value objects**. What matters about them
is their value, not which object you hold: two addresses with the same street and city are
the same address. PHP's `==` compares two objects' properties, but it's clearer to give the
class an `equals()` method, where you decide what "the same" means:

```php
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}

    public function equals(Address $other): bool
    {
        return strtolower($this->street) === strtolower($other->street)
            && strtolower($this->city) === strtolower($other->city);
    }
}

$a = new Address('12 Market Street', 'Leeds');
$b = new Address('12 market street', 'LEEDS');

var_dump($a === $b);
var_dump($a->equals($b));
```

`===` asks "is this the very same object?", which it isn't. `equals()` asks the question that
matters.

Next, try the quiz to check your understanding, then solve the challenges.
````

- [ ] **Step 5: Write lesson 6's quiz and challenges**

Create `app/database/content/php-oop/06-traits-and-readonly-properties.php`:

```php
<?php

// Lesson 6 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'traits-and-readonly-properties',
    'title' => 'Traits and readonly properties',
    'summary' => 'Share code between unrelated classes with traits, and build objects that never change with readonly properties, withers and value objects.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        trait HasSlug
        {
            public function slug(): string
            {
                return strtolower(str_replace(' ', '-', $this->name));
            }
        }

        readonly class Author
        {
            use HasSlug;

            public function __construct(public string $name) {}
        }

        echo (new Author('Ursula K. Le Guin'))->slug();
        PHP,

    'quiz' => [
        [
            'question' => 'What is a trait?',
            'options' => [
                'A parent class that can\'t be created with `new`',
                'A bundle of methods and properties that classes copy in with `use`',
                'An interface with method bodies',
                'A kind of exception',
            ],
            'correct_option' => 1,
            'explanation' => 'A trait\'s methods and properties become part of each class that uses it, without creating a parent or a type.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                trait Counts
                {
                    private int $count = 0;

                    public function tick(): int
                    {
                        return ++$this->count;
                    }
                }

                class Book
                {
                    use Counts;
                }

                $a = new Book();
                $b = new Book();
                $a->tick();
                $a->tick();

                echo $a->tick() . ' ' . $b->tick();
                PHP,
            'options' => ['`3 4`', '`3 1`', '`1 1`', '`2 1`'],
            'correct_option' => 1,
            'explanation' => 'A trait\'s property becomes a normal property of each object, so `$a` and `$b` count separately.',
        ],
        [
            'question' => 'Two traits used by one class both have a method `describe()`. What happens?',
            'options' => [
                'The trait listed last wins',
                'The trait listed first wins',
                'PHP refuses the class unless you choose one with `insteadof`',
                'Both methods run, one after the other',
            ],
            'correct_option' => 2,
            'explanation' => 'PHP won\'t guess. `A::describe insteadof B;` picks one, and `B::describe as other;` can keep the other under a new name.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                class Isbn
                {
                    public function __construct(public readonly string $value) {}
                }

                $isbn = new Isbn('9780441013593');
                $isbn->value = '0000000000000';
                echo $isbn->value;
                PHP,
            'options' => [
                'It prints `0000000000000`',
                'It prints `9780441013593`',
                'An error: a readonly property can\'t be changed once it\'s set',
                'An error: readonly properties can\'t be public',
            ],
            'correct_option' => 2,
            'explanation' => 'A readonly property is set once, by the class. It can be read from anywhere, but never changed.',
        ],
        [
            'question' => 'What does `readonly class Money { … }` mean?',
            'options' => [
                'Money objects can\'t be created',
                'Every property of `Money` is readonly',
                'Money\'s methods can\'t change anything outside the class',
                'Money can\'t be extended',
            ],
            'correct_option' => 1,
            'explanation' => 'A readonly class makes all its properties readonly, so you don\'t have to mark each one.',
        ],
        [
            'question' => 'A readonly `Address` has `withCity(string $city): static`. What should it do?',
            'options' => [
                'Change `$this->city` and return `$this`',
                'Return a new `Address` with the new city, leaving the original unchanged',
                'Throw an exception, because readonly objects can\'t change',
                'Return the new city as a string',
            ],
            'correct_option' => 1,
            'explanation' => 'A wither builds and returns a new object with the difference. The original can\'t change, and doesn\'t need to.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                readonly class Price
                {
                    public function __construct(public float $amount) {}
                }

                $a = new Price(9.99);
                $b = new Price(9.99);

                var_dump($a === $b);
                var_dump($a == $b);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
            ],
            'correct_option' => 2,
            'explanation' => '`===` asks whether they\'re the same object, which they aren\'t. `==` compares their properties, which are equal.',
        ],
        [
            'question' => 'Does `$book instanceof HasSlug` work, when `Book` uses the trait `HasSlug`?',
            'options' => [
                'Yes, it gives `true`',
                'Yes, but only for readonly classes',
                'No: a trait isn\'t a type. Use an interface when you need one',
                'No: traits can\'t be used with objects',
            ],
            'correct_option' => 2,
            'explanation' => 'Traits share code; interfaces describe types. A class often does both: implements an interface, and uses a trait to provide it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Shout the title',
            'prompt' => 'Write a trait `Shouts` with a method `shout(): string` that returns the object\'s `$title` in capitals followed by `!`, like `DUNE!`. Make `Book` use it.',
            'starter_code' => <<<'PHP'
                <?php

                // Write the Shouts trait here.

                class Book
                {
                    public function __construct(public string $title) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait Shouts
                {
                    public function shout(): string
                    {
                        return strtoupper($this->title) . '!';
                    }
                }

                class Book
                {
                    use Shouts;

                    public function __construct(public string $title) {}
                }
                PHP,
            'hint' => 'Declare `trait Shouts { … }` with the method, then write `use Shouts;` inside `Book`.',
            'checks' => [
                ['label' => 'A trait named Shouts exists', 'code' => "return trait_exists('Shouts', false) ?: 'There is no trait named Shouts.';"],
                ['label' => 'Book uses Shouts', 'code' => "return in_array('Shouts', class_uses('Book')) ?: 'Book should use the Shouts trait.';"],
                ['label' => "shout() gives 'DUNE!' for Dune", 'code' => "return expect_same((new Book('Dune'))->shout(), 'DUNE!');"],
                ['label' => 'It works for any title', 'code' => "return expect_same((new Book('The Hobbit'))->shout(), 'THE HOBBIT!');"],
            ],
        ],
        [
            'title' => 'Discounts for strangers',
            'prompt' => "Books and gift cards have nothing in common except a `\$price`. Write a trait `HasDiscount` with:\n\n- a private `int` property `\$discountPercent`, starting at 0;\n- `setDiscount(int \$percent): void`;\n- `discountedPrice(): float`, the price with the discount taken off, rounded to 2 places.\n\nMake both `Book` and `GiftCard` use it. A book costing 20.0 with a 25% discount costs 15.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}
                }

                class GiftCard
                {
                    public function __construct(public float $price) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait HasDiscount
                {
                    private int $discountPercent = 0;

                    public function setDiscount(int $percent): void
                    {
                        $this->discountPercent = $percent;
                    }

                    public function discountedPrice(): float
                    {
                        return round($this->price * (100 - $this->discountPercent) / 100, 2);
                    }
                }

                class Book
                {
                    use HasDiscount;

                    public function __construct(public string $title, public float $price) {}
                }

                class GiftCard
                {
                    use HasDiscount;

                    public function __construct(public float $price) {}
                }
                PHP,
            'hint' => 'The trait can read `$this->price`, because every class that uses it has one. Add `use HasDiscount;` to both classes.',
            'checks' => [
                ['label' => 'Book and GiftCard both use HasDiscount', 'code' => <<<'PHP'
                    if (! trait_exists('HasDiscount', false)) return 'There is no trait named HasDiscount.';
                    if (! in_array('HasDiscount', class_uses('Book'))) return 'Book should use HasDiscount.';
                    return in_array('HasDiscount', class_uses('GiftCard')) ?: 'GiftCard should use HasDiscount.';
                    PHP],
                ['label' => 'Without a discount, the price is unchanged', 'code' => "return expect_same((new GiftCard(25.0))->discountedPrice(), 25.0);"],
                ['label' => 'A book costing 20.0 with 25% off costs 15.0', 'code' => <<<'PHP'
                    $book = new Book('Dune', 20.0);
                    $book->setDiscount(25);
                    return expect_same($book->discountedPrice(), 15.0);
                    PHP],
                ['label' => 'Each object keeps its own discount', 'code' => <<<'PHP'
                    $card = new GiftCard(10.0);
                    $other = new GiftCard(10.0);
                    $card->setDiscount(10);
                    return expect_same($card->discountedPrice() . ' / ' . $other->discountedPrice(), '9 / 10');
                    PHP],
            ],
        ],
        [
            'title' => 'An ISBN that never changes',
            'prompt' => "Write a class `Isbn` with a public **readonly** string `\$value`. Its constructor takes an ISBN, removes any `-` characters, and stores the result. If what's left isn't exactly 13 digits, throw an `InvalidArgumentException`.\n\n`new Isbn('978-0-441-01359-3')` stores `9780441013593`.",
            'starter_code' => <<<'PHP'
                <?php

                class Isbn
                {
                    public string $value;

                    public function __construct(string $isbn)
                    {
                        $this->value = $isbn;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Isbn
                {
                    public readonly string $value;

                    public function __construct(string $isbn)
                    {
                        $digits = str_replace('-', '', $isbn);

                        if (! preg_match('/^\d{13}$/', $digits)) {
                            throw new InvalidArgumentException("'$isbn' is not a 13-digit ISBN.");
                        }

                        $this->value = $digits;
                    }
                }
                PHP,
            'hint' => 'Declare `public readonly string $value;`. In the constructor, `str_replace(\'-\', \'\', $isbn)`, check `strlen() === 13` and `ctype_digit()` (or a regular expression), then set `$this->value`.',
            'checks' => [
                ['label' => '$value is readonly', 'code' => <<<'PHP'
                    if (! property_exists('Isbn', 'value')) return 'Isbn has no $value property.';
                    return (new ReflectionProperty('Isbn', 'value'))->isReadOnly() ?: '$value should be readonly.';
                    PHP],
                ['label' => 'Dashes are removed', 'code' => "return expect_same((new Isbn('978-0-441-01359-3'))->value, '9780441013593');"],
                ['label' => '$value can\'t be changed afterwards', 'code' => <<<'PHP'
                    $isbn = new Isbn('9780441013593');
                    return expect_throws(Error::class, function () use ($isbn) {
                        $isbn->value = '0000000000000';
                    });
                    PHP],
                ['label' => 'Too short, too long or not digits throws an InvalidArgumentException', 'code' => <<<'PHP'
                    foreach (['12345', '97804410135931', '978044101359X'] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => new Isbn($bad));
                        if ($result !== true) return "For '$bad': " . $result;
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Money that doesn\'t change',
            'prompt' => "Make `Money` a **readonly class** with public `float \$amount` and `string \$currency`, and add `withAmount(float \$amount): static`, which returns a **new** `Money` with that amount and the same currency. The original must be unchanged.",
            'starter_code' => <<<'PHP'
                <?php

                class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function withAmount(float $amount): static
                    {
                        $this->amount = $amount;

                        return $this;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function withAmount(float $amount): static
                    {
                        return new static($amount, $this->currency);
                    }
                }
                PHP,
            'hint' => 'Put `readonly` before `class`. In `withAmount()`, return `new static($amount, $this->currency)` (or `new self(…)`).',
            'checks' => [
                ['label' => 'Money\'s properties are readonly', 'code' => <<<'PHP'
                    $amount = new ReflectionProperty('Money', 'amount');
                    $currency = new ReflectionProperty('Money', 'currency');
                    return ($amount->isReadOnly() && $currency->isReadOnly()) ?: 'Make Money a readonly class (or both properties readonly).';
                    PHP],
                ['label' => 'withAmount() returns a new Money with the new amount', 'code' => <<<'PHP'
                    $price = new Money(19.99, 'GBP');
                    $sale = $price->withAmount(14.99);
                    if ($sale === $price) return 'withAmount() should return a new object, not the same one.';
                    return expect_same($sale->amount . ' ' . $sale->currency, '14.99 GBP');
                    PHP],
                ['label' => 'The original is unchanged', 'code' => <<<'PHP'
                    $price = new Money(19.99, 'GBP');
                    $price->withAmount(14.99);
                    return expect_same($price->amount, 19.99);
                    PHP],
            ],
        ],
        [
            'title' => 'The same amount of money',
            'prompt' => 'Give the readonly `Money` class a method `equals(Money $other): bool`. Two amounts of money are equal when both the amount and the currency match: 10.00 GBP equals 10.00 GBP, but not 10.00 EUR or 10.50 GBP.',
            'starter_code' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    // Add equals() here.
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                readonly class Money
                {
                    public function __construct(public float $amount, public string $currency) {}

                    public function equals(Money $other): bool
                    {
                        return $this->amount === $other->amount && $this->currency === $other->currency;
                    }
                }
                PHP,
            'hint' => 'Compare both properties with `===` and join the two comparisons with `&&`.',
            'checks' => [
                ['label' => 'Equal amount and currency are equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.0, 'GBP')) === true ?: '10.00 GBP should equal 10.00 GBP.';"],
                ['label' => 'A different currency is not equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.0, 'EUR')) === false ?: '10.00 GBP should not equal 10.00 EUR.';"],
                ['label' => 'A different amount is not equal', 'code' => "return (new Money(10.0, 'GBP'))->equals(new Money(10.5, 'GBP')) === false ?: '10.00 GBP should not equal 10.50 GBP.';"],
            ],
        ],
        [
            'title' => 'Settle the conflict',
            'prompt' => "`Book` uses two traits, `CatalogueEntry` and `ShelfLabel`, and both have a `describe()` method, so PHP refuses the class. Make `Book`'s `describe()` use `ShelfLabel`'s version, and keep `CatalogueEntry`'s version available as `catalogueEntry()`.",
            'starter_code' => <<<'PHP'
                <?php

                trait CatalogueEntry
                {
                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                trait ShelfLabel
                {
                    public function describe(): string
                    {
                        return strtoupper($this->title);
                    }
                }

                class Book
                {
                    use CatalogueEntry, ShelfLabel;

                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait CatalogueEntry
                {
                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                trait ShelfLabel
                {
                    public function describe(): string
                    {
                        return strtoupper($this->title);
                    }
                }

                class Book
                {
                    use CatalogueEntry, ShelfLabel {
                        ShelfLabel::describe insteadof CatalogueEntry;
                        CatalogueEntry::describe as catalogueEntry;
                    }

                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'hint' => 'Add a block after the `use` line: `ShelfLabel::describe insteadof CatalogueEntry;` and `CatalogueEntry::describe as catalogueEntry;`.',
            'checks' => [
                ['label' => "describe() gives ShelfLabel's 'DUNE'", 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->describe(), 'DUNE');"],
                ['label' => "catalogueEntry() gives 'Dune by Frank Herbert'", 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->catalogueEntry(), 'Dune by Frank Herbert');"],
                ['label' => 'Book still uses both traits', 'code' => "return expect_same(implode(', ', array_keys(class_uses('Book'))), 'CatalogueEntry, ShelfLabel');"],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if Pint's `single_quote` fixer complains, run `vendor/bin/pint database tests`, check the diff only changes quote style, and re-run the tests).

- [ ] **Step 7: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/06-traits-and-readonly-properties.md app/database/content/php-oop/06-traits-and-readonly-properties.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 6: Traits and readonly properties"
```

---

### Task 3: Lesson 7, *Putting objects together*, and final checks

**Files:**
- Create: `app/database/content/php-oop/07-putting-objects-together.md`, `app/database/content/php-oop/07-putting-objects-together.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpLessonContentTest.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: the lesson definition file format, `PhpOopCourseSeeder::LATER_LESSONS` and `PhpLessonContentTest::LESSONS` (from lessons 2–4).
- Produces: lesson `putting-objects-together` at position 7.

- [ ] **Step 1: Update the tests to expect lesson 7**

In `app/tests/Feature/PhpLessonContentTest.php`, add to the end of `LESSONS`:

```php
        'putting-objects-together',
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, change `$this->assertCount(35, $challenges);` to:

```php
        $this->assertCount(41, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, change `->assertJsonPath('data.1.lessons_count', 6)` to:

```php
            ->assertJsonPath('data.1.lessons_count', 7)
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL on the lesson list, the harness count and `data.1.lessons_count`.

- [ ] **Step 3: Register the lesson**

In `app/database/seeders/PhpOopCourseSeeder.php`, add to the end of `LATER_LESSONS`:

```php
        '07-putting-objects-together',
```

- [ ] **Step 4: Write lesson 7's theory**

Create `app/database/content/php-oop/07-putting-objects-together.md`:

````markdown
## Has-a, not is-a

Inheritance models "is a": an e-book *is a* book. Most relationships in a program are a
different kind: an order *has* lines, a shelf *has* books, a checkout *has* a payment method.
Model those by giving one object a property that holds another. This is **composition**, and
it's how most well-built programs fit together.

A good habit: when you're tempted to extend a class only to reuse its code, ask whether your
object could **have** one of those instead.

## Objects holding objects

A shelf holds books. It keeps them in an array property, and answers questions by asking each
book in turn:

```php
class Book
{
    public function __construct(public string $title, public int $pages) {}
}

class Shelf
{
    /** @var Book[] */
    private array $books = [];

    public function add(Book $book): void
    {
        $this->books[] = $book;
    }

    public function totalPages(): int
    {
        $total = 0;
        foreach ($this->books as $book) {
            $total += $book->pages;
        }

        return $total;
    }
}

$shelf = new Shelf();
$shelf->add(new Book('Dune', 612));
$shelf->add(new Book('Circe', 393));

echo $shelf->totalPages() . ' pages';
```

`add(Book $book)` only accepts books, so the shelf can trust everything it holds. The
`/** @var Book[] */` comment tells readers (and editors) what the array contains.

## Passing in what an object needs

A shipping quote needs a courier to ask for prices. The quote could create its own courier
with `new` inside its methods, but then it's stuck with that one courier forever. Instead,
**pass the courier in** through the constructor. This is called **dependency injection**:

```php
class Courier
{
    public function priceFor(float $weightKg): float
    {
        return 3.0 + $weightKg * 1.5;
    }
}

class ShippingQuote
{
    public function __construct(private Courier $courier) {}

    public function forParcel(float $weightKg): string
    {
        return 'Shipping: ' . number_format($this->courier->priceFor($weightKg), 2);
    }
}

$quote = new ShippingQuote(new Courier());
echo $quote->forParcel(2.0);
```

The quote says what it needs, a `Courier`, and whoever creates the quote decides which one.

## Depending on a promise, not a class

Better still, type the dependency with an **interface**. Then any courier that keeps the
promise will do, including ones written years later:

```php
interface Courier
{
    public function priceFor(float $weightKg): float;
}

class RoyalPost implements Courier
{
    public function priceFor(float $weightKg): float
    {
        return 3.0 + $weightKg * 1.5;
    }
}

class SpeedyParcels implements Courier
{
    public function priceFor(float $weightKg): float
    {
        return 7.5;
    }
}

class ShippingQuote
{
    public function __construct(private Courier $courier) {}

    public function forParcel(float $weightKg): float
    {
        return $this->courier->priceFor($weightKg);
    }
}

echo (new ShippingQuote(new RoyalPost()))->forParcel(2.0) . "\n";
echo (new ShippingQuote(new SpeedyParcels()))->forParcel(2.0) . "\n";
```

`ShippingQuote` never changes, however many couriers you add.

## Swapping in a fake

Real dependencies send emails, charge cards and talk to other servers: things you don't want
to happen while you're trying out your code. Because the class depends on an interface, you
can hand it a **fake** that just remembers what it was asked to do:

```php
interface Mailer
{
    public function send(string $to, string $message): void;
}

class FakeMailer implements Mailer
{
    public array $sent = [];

    public function send(string $to, string $message): void
    {
        $this->sent[] = "$to: $message";
    }
}

class Newsletter
{
    public function __construct(private Mailer $mailer) {}

    public function announce(array $emails, string $title): void
    {
        foreach ($emails as $email) {
            $this->mailer->send($email, "New in the shop: $title");
        }
    }
}

$mailer = new FakeMailer();
(new Newsletter($mailer))->announce(['amara@example.com', 'ben.h@example.com'], 'Piranesi');

echo implode("\n", $mailer->sent);
```

Nothing was emailed, and you can see exactly what would have been. Automated tests are built
on this idea.

## A small project: a till

Here's everything together: value objects, an interface, injected collaborators and objects
holding objects. A till rings up items and asks a pricing rule for the total:

```php
readonly class Item
{
    public function __construct(public string $name, public float $price) {}
}

interface PricingRule
{
    /** @param Item[] $items */
    public function total(array $items): float;
}

class FullPrice implements PricingRule
{
    public function total(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item->price;
        }

        return round($sum, 2);
    }
}

class ThreeForTwo implements PricingRule
{
    public function total(array $items): float
    {
        $prices = array_map(fn (Item $item) => $item->price, $items);
        sort($prices);
        $free = intdiv(count($prices), 3);

        return round(array_sum(array_slice($prices, $free)), 2);
    }
}

class Till
{
    /** @var Item[] */
    private array $items = [];

    public function __construct(private PricingRule $rule) {}

    public function ring(Item $item): void
    {
        $this->items[] = $item;
    }

    public function total(): float
    {
        return $this->rule->total($this->items);
    }
}

$items = [new Item('Dune', 19.99), new Item('Circe', 13.99), new Item('Beloved', 16.00)];

foreach ([new FullPrice(), new ThreeForTwo()] as $rule) {
    $till = new Till($rule);
    foreach ($items as $item) {
        $till->ring($item);
    }
    echo get_class($rule) . ': ' . $till->total() . "\n";
}
```

The cheapest of every three books is free under `ThreeForTwo`, and the till didn't change at
all to support it.

## Looking back

Over this course you've built up a toolkit:

- **Classes and objects** bundle data with the code that works on it, and visibility keeps
  that data safe.
- **Inheritance** models "is a" and lets a child reuse and adjust its parent.
- **Interfaces** describe what something can do, so code can work with any implementation.
  **Abstract classes** share code within a family.
- **Static members, constants and enums** belong to the class, not to each object; enums make
  fixed sets of values safe.
- **Exceptions** report problems that can't be ignored.
- **Traits** share code between unrelated classes; **readonly** properties and **value
  objects** make data that can't change behind your back.
- **Composition** and **dependency injection** fit objects together, so each part can be
  swapped, faked and tested on its own.

Next, try the quiz, then solve the last challenges of the course.
````

- [ ] **Step 5: Write lesson 7's quiz and challenges**

Create `app/database/content/php-oop/07-putting-objects-together.php`:

```php
<?php

// Lesson 7 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'putting-objects-together',
    'title' => 'Putting objects together',
    'summary' => 'Build programs from small objects that hold and use each other: composition, dependency injection, depending on interfaces, and fakes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        interface Courier
        {
            public function priceFor(float $weightKg): float;
        }

        class RoyalPost implements Courier
        {
            public function priceFor(float $weightKg): float
            {
                return 3.0 + $weightKg * 1.5;
            }
        }

        class ShippingQuote
        {
            public function __construct(private Courier $courier) {}

            public function forParcel(float $weightKg): float
            {
                return $this->courier->priceFor($weightKg);
            }
        }

        echo (new ShippingQuote(new RoyalPost()))->forParcel(1.2);
        PHP,

    'quiz' => [
        [
            'question' => 'Which relationship should be modelled with composition rather than inheritance?',
            'options' => [
                'An e-book and a book',
                'An order and its order lines',
                'A gold membership and a membership',
                'An `InvalidArgumentException` and a `LogicException`',
            ],
            'correct_option' => 1,
            'explanation' => 'An order *has* lines; it isn\'t a kind of line. The other three are all "is a" relationships.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public int $pages) {}
                }

                class Shelf
                {
                    private array $books = [];

                    public function add(Book $book): void
                    {
                        $this->books[] = $book;
                    }

                    public function count(): int
                    {
                        return count($this->books);
                    }
                }

                $shelf = new Shelf();
                $shelf->add(new Book(612));
                $shelf->add(new Book(393));

                echo $shelf->count();
                PHP,
            'options' => ['`0`', '`1`', '`2`', '`1005`'],
            'correct_option' => 2,
            'explanation' => 'The shelf holds two `Book` objects in its array, so `count()` gives 2.',
        ],
        [
            'question' => 'What is dependency injection?',
            'options' => [
                'Creating every object an object needs with `new` inside its methods',
                'Passing an object the things it needs, usually through its constructor, instead of it creating them itself',
                'Inheriting from the classes you depend on',
                'Making dependencies static',
            ],
            'correct_option' => 1,
            'explanation' => 'The object declares what it needs; whoever creates it decides which implementation to supply.',
        ],
        [
            'question' => 'Why type a constructor parameter with an interface, like `__construct(private Courier $courier)`, rather than a class?',
            'options' => [
                'It makes the code run faster',
                'Interfaces can\'t be passed by mistake',
                'Any class that keeps the promise can be passed in, including fakes and ones written later',
                'PHP requires constructor parameters to be interfaces',
            ],
            'correct_option' => 2,
            'explanation' => 'Depending on the promise rather than one class keeps the code open to new implementations without changing it.',
        ],
        [
            'question' => 'What is a fake, in the sense used in this lesson?',
            'options' => [
                'A class that throws an exception for every method',
                'A simple implementation of an interface that records what it\'s asked to do, used instead of the real thing',
                'A copy of a class with a different name',
                'An abstract class',
            ],
            'correct_option' => 1,
            'explanation' => 'A fake keeps the same promise as the real dependency but does something harmless and observable, such as remembering messages instead of emailing them.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Greeter
                {
                    public function greet(string $name): string;
                }

                class Friendly implements Greeter
                {
                    public function greet(string $name): string
                    {
                        return "Hi, $name!";
                    }
                }

                class Formal implements Greeter
                {
                    public function greet(string $name): string
                    {
                        return "Good morning, $name.";
                    }
                }

                class Shop
                {
                    public function __construct(private Greeter $greeter) {}

                    public function welcome(string $name): string
                    {
                        return $this->greeter->greet($name);
                    }
                }

                echo (new Shop(new Formal()))->welcome('Amara');
                PHP,
            'options' => ['`Hi, Amara!`', '`Good morning, Amara.`', 'Both greetings', 'An error: `Shop` needs a `Friendly` greeter'],
            'correct_option' => 1,
            'explanation' => 'The shop uses whichever `Greeter` it was given, here a `Formal` one.',
        ],
        [
            'question' => 'A class creates its mailer inside a method with `new SmtpMailer()`. What\'s the main problem?',
            'options' => [
                'It can\'t send emails',
                'You can\'t swap in a different mailer, such as a fake, without changing the class',
                'PHP doesn\'t allow `new` inside methods',
                'The mailer is created too late',
            ],
            'correct_option' => 1,
            'explanation' => 'Hard-wiring `new` ties the class to one implementation. Injecting the mailer lets the caller choose.',
        ],
        [
            'question' => 'Which tool fits "share these three methods between `Book`, `Customer` and `Event`, which have nothing else in common"?',
            'options' => ['A common parent class', 'A trait', 'An enum', 'A static method on `Book`'],
            'correct_option' => 1,
            'explanation' => 'A trait shares code without claiming the classes are the same kind of thing. A parent class would wrongly say a customer *is a* book-like thing.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Lines make an order',
            'prompt' => "Write two classes:\n\n- `OrderLine`: constructor `(string \$title, float \$unitPrice, int \$quantity)`, and `total(): float`, the unit price times the quantity.\n- `Order`: `addLine(OrderLine \$line): void`, `lineCount(): int`, and `total(): float`, the sum of its lines' totals rounded to 2 places.\n\nAn order with 2 × 8.50 and 1 × 19.99 has 2 lines and totals 36.99.",
            'starter_code' => <<<'PHP'
                <?php

                // Write OrderLine and Order here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class OrderLine
                {
                    public function __construct(
                        public string $title,
                        public float $unitPrice,
                        public int $quantity,
                    ) {}

                    public function total(): float
                    {
                        return $this->unitPrice * $this->quantity;
                    }
                }

                class Order
                {
                    /** @var OrderLine[] */
                    private array $lines = [];

                    public function addLine(OrderLine $line): void
                    {
                        $this->lines[] = $line;
                    }

                    public function lineCount(): int
                    {
                        return count($this->lines);
                    }

                    public function total(): float
                    {
                        $sum = 0.0;
                        foreach ($this->lines as $line) {
                            $sum += $line->total();
                        }

                        return round($sum, 2);
                    }
                }
                PHP,
            'hint' => '`Order` keeps an array of `OrderLine` objects. Its `total()` loops over them and adds up each line\'s `total()`.',
            'checks' => [
                ['label' => 'An OrderLine of 2 × 8.50 totals 17.0', 'code' => "return expect_same((new OrderLine('Frankenstein', 8.5, 2))->total(), 17.0);"],
                ['label' => 'addLine() only accepts OrderLine objects', 'code' => "return expect_same((string) (new ReflectionMethod('Order', 'addLine'))->getParameters()[0]->getType(), 'OrderLine');"],
                ['label' => 'An order with 2 × 8.50 and 1 × 19.99 has 2 lines and totals 36.99', 'code' => <<<'PHP'
                    $order = new Order();
                    $order->addLine(new OrderLine('Frankenstein', 8.5, 2));
                    $order->addLine(new OrderLine('Dune', 19.99, 1));
                    return expect_same($order->lineCount() . ' lines, ' . $order->total(), '2 lines, 36.99');
                    PHP],
                ['label' => 'An empty order totals 0', 'code' => "return expect_same((new Order())->total(), 0.0);"],
            ],
        ],
        [
            'title' => 'Bring your own tax',
            'prompt' => "`Invoice` works out its own tax with a number written into it. Change it so a `TaxCalculator` is **injected** through the constructor: `new Invoice(new TaxCalculator(), 50.0)`. `total()` returns the net amount plus `\$this->tax->taxOn(\$net)`.",
            'starter_code' => <<<'PHP'
                <?php

                class TaxCalculator
                {
                    public function taxOn(float $net): float
                    {
                        return round($net * 0.2, 2);
                    }
                }

                class Invoice
                {
                    public function __construct(private float $net) {}

                    public function total(): float
                    {
                        return $this->net + round($this->net * 0.2, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TaxCalculator
                {
                    public function taxOn(float $net): float
                    {
                        return round($net * 0.2, 2);
                    }
                }

                class Invoice
                {
                    public function __construct(private TaxCalculator $tax, private float $net) {}

                    public function total(): float
                    {
                        return $this->net + $this->tax->taxOn($this->net);
                    }
                }
                PHP,
            'hint' => 'Add `private TaxCalculator $tax` as the constructor\'s first parameter, and call `$this->tax->taxOn($this->net)`.',
            'checks' => [
                ['label' => 'The constructor takes a TaxCalculator first', 'code' => "return expect_same((string) (new ReflectionMethod('Invoice', '__construct'))->getParameters()[0]->getType(), 'TaxCalculator');"],
                ['label' => 'An invoice for 50.0 totals 60.0', 'code' => "return expect_same((new Invoice(new TaxCalculator(), 50.0))->total(), 60.0);"],
                ['label' => 'It uses the calculator it was given', 'code' => <<<'PHP'
                    $noTax = new class extends TaxCalculator {
                        public function taxOn(float $net): float
                        {
                            return 0.0;
                        }
                    };
                    return expect_same((new Invoice($noTax, 50.0))->total(), 50.0);
                    PHP],
            ],
        ],
        [
            'title' => 'Tell someone',
            'prompt' => "Write a class `OrderService` that depends on the `Notifier` interface below, injected through its constructor. Its method `placeOrder(string \$title): string` notifies `New order: Dune` (for Dune) and returns `Order placed: Dune`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write OrderService here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class OrderService
                {
                    public function __construct(private Notifier $notifier) {}

                    public function placeOrder(string $title): string
                    {
                        $this->notifier->notify("New order: $title");

                        return "Order placed: $title";
                    }
                }
                PHP,
            'hint' => 'Type the constructor parameter with the interface: `private Notifier $notifier`. Call `$this->notifier->notify(…)` in `placeOrder()`.',
            'checks' => [
                ['label' => 'The constructor takes a Notifier', 'code' => <<<'PHP'
                    if (! class_exists('OrderService', false)) return 'There is no class named OrderService.';
                    return expect_same((string) (new ReflectionMethod('OrderService', '__construct'))->getParameters()[0]->getType(), 'Notifier');
                    PHP],
                ['label' => "placeOrder('Dune') returns 'Order placed: Dune'", 'code' => <<<'PHP'
                    $quiet = new class implements Notifier {
                        public function notify(string $message): void {}
                    };
                    return expect_same((new OrderService($quiet))->placeOrder('Dune'), 'Order placed: Dune');
                    PHP],
                ['label' => "It notifies 'New order: Dune'", 'code' => <<<'PHP'
                    $spy = new class implements Notifier {
                        public array $messages = [];

                        public function notify(string $message): void
                        {
                            $this->messages[] = $message;
                        }
                    };
                    (new OrderService($spy))->placeOrder('Dune');
                    return expect_same(implode(' | ', $spy->messages), 'New order: Dune');
                    PHP],
            ],
        ],
        [
            'title' => 'A fake that remembers',
            'prompt' => "Write `FakeNotifier`, which implements `Notifier` but sends nothing: it remembers each message, and `messages(): array` returns them in the order they arrived.",
            'starter_code' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write FakeNotifier here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class FakeNotifier implements Notifier
                {
                    private array $messages = [];

                    public function notify(string $message): void
                    {
                        $this->messages[] = $message;
                    }

                    public function messages(): array
                    {
                        return $this->messages;
                    }
                }
                PHP,
            'hint' => 'Keep an array property; `notify()` appends to it, `messages()` returns it.',
            'checks' => [
                ['label' => 'FakeNotifier implements Notifier', 'code' => <<<'PHP'
                    if (! class_exists('FakeNotifier', false)) return 'There is no class named FakeNotifier.';
                    return in_array('Notifier', class_implements('FakeNotifier')) ?: 'FakeNotifier should implement Notifier.';
                    PHP],
                ['label' => 'A new fake has no messages', 'code' => "return expect_same(count((new FakeNotifier())->messages()), 0);"],
                ['label' => 'It remembers messages in order', 'code' => <<<'PHP'
                    $fake = new FakeNotifier();
                    $fake->notify('New order: Dune');
                    $fake->notify('New order: Circe');
                    return expect_same(implode(' | ', $fake->messages()), 'New order: Dune | New order: Circe');
                    PHP],
                ['label' => 'Each fake keeps its own messages', 'code' => <<<'PHP'
                    $a = new FakeNotifier();
                    $b = new FakeNotifier();
                    $a->notify('hello');
                    return expect_same(count($b->messages()), 0);
                    PHP],
            ],
        ],
        [
            'title' => 'Swap the discount',
            'prompt' => "Write:\n\n- an interface `DiscountPolicy` with `apply(float \$total): float`;\n- `NoDiscount`, which returns the total unchanged;\n- `TenPercentOverFifty`, which takes 10% off totals of 50 or more (rounded to 2 places) and leaves smaller totals alone;\n- `Basket`, created with a `DiscountPolicy`, with `add(float \$price): void` and `total(): float`, the sum of the prices with the policy applied.",
            'starter_code' => <<<'PHP'
                <?php

                class Basket
                {
                    private array $prices = [];

                    public function add(float $price): void
                    {
                        $this->prices[] = $price;
                    }

                    public function total(): float
                    {
                        return array_sum($this->prices);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                class NoDiscount implements DiscountPolicy
                {
                    public function apply(float $total): float
                    {
                        return $total;
                    }
                }

                class TenPercentOverFifty implements DiscountPolicy
                {
                    public function apply(float $total): float
                    {
                        return $total >= 50 ? round($total * 0.9, 2) : $total;
                    }
                }

                class Basket
                {
                    private array $prices = [];

                    public function __construct(private DiscountPolicy $policy) {}

                    public function add(float $price): void
                    {
                        $this->prices[] = $price;
                    }

                    public function total(): float
                    {
                        return $this->policy->apply(array_sum($this->prices));
                    }
                }
                PHP,
            'hint' => 'Both policies implement `DiscountPolicy`. `Basket` takes one in its constructor and calls `$this->policy->apply(…)` in `total()`.',
            'checks' => [
                ['label' => 'DiscountPolicy is an interface, and both policies implement it', 'code' => <<<'PHP'
                    if (! interface_exists('DiscountPolicy')) return 'There is no interface called DiscountPolicy.';
                    foreach (['NoDiscount', 'TenPercentOverFifty'] as $class) {
                        if (! class_exists($class) || ! in_array('DiscountPolicy', class_implements($class))) return "$class should implement DiscountPolicy.";
                    }
                    return true;
                    PHP],
                ['label' => 'With NoDiscount, 30 + 25 is 55', 'code' => <<<'PHP'
                    $basket = new Basket(new NoDiscount());
                    $basket->add(30.0);
                    $basket->add(25.0);
                    return expect_same($basket->total(), 55.0);
                    PHP],
                ['label' => 'With TenPercentOverFifty, 30 + 25 is 49.5', 'code' => <<<'PHP'
                    $basket = new Basket(new TenPercentOverFifty());
                    $basket->add(30.0);
                    $basket->add(25.0);
                    return expect_same($basket->total(), 49.5);
                    PHP],
                ['label' => 'TenPercentOverFifty leaves totals under 50 alone, and includes exactly 50', 'code' => <<<'PHP'
                    $policy = new TenPercentOverFifty();
                    return expect_same($policy->apply(49.99) . ' / ' . $policy->apply(50.0), '49.99 / 45');
                    PHP],
            ],
        ],
        [
            'title' => 'The checkout',
            'prompt' => "Bring it together. `Checkout` is created with a `DiscountPolicy` and a `Notifier` (both given below). Its method `complete(array \$prices): float` adds up the prices, applies the policy, notifies `Charged 45.00` (the amount with 2 decimal places, as `number_format()` gives it), and returns the amount charged.",
            'starter_code' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                // Write Checkout here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DiscountPolicy
                {
                    public function apply(float $total): float;
                }

                interface Notifier
                {
                    public function notify(string $message): void;
                }

                class Checkout
                {
                    public function __construct(
                        private DiscountPolicy $discount,
                        private Notifier $notifier,
                    ) {}

                    public function complete(array $prices): float
                    {
                        $amount = $this->discount->apply(array_sum($prices));
                        $this->notifier->notify('Charged ' . number_format($amount, 2));

                        return $amount;
                    }
                }
                PHP,
            'hint' => 'Inject both through the constructor. In `complete()`: `array_sum()`, then `apply()`, then `notify()`, then return the amount.',
            'checks' => [
                ['label' => 'The constructor takes a DiscountPolicy and a Notifier', 'code' => <<<'PHP'
                    if (! class_exists('Checkout', false)) return 'There is no class named Checkout.';
                    $types = array_map(fn ($p) => (string) $p->getType(), (new ReflectionMethod('Checkout', '__construct'))->getParameters());
                    return expect_same(implode(', ', $types), 'DiscountPolicy, Notifier');
                    PHP],
                ['label' => 'It charges the discounted total and notifies it', 'code' => <<<'PHP'
                    $halfPrice = new class implements DiscountPolicy {
                        public function apply(float $total): float
                        {
                            return $total / 2;
                        }
                    };
                    $spy = new class implements Notifier {
                        public array $messages = [];

                        public function notify(string $message): void
                        {
                            $this->messages[] = $message;
                        }
                    };
                    $charged = (new Checkout($halfPrice, $spy))->complete([60.0, 30.0]);
                    return expect_same($charged . ' / ' . implode(' | ', $spy->messages), '45 / Charged 45.00');
                    PHP],
                ['label' => 'It works with any policy', 'code' => <<<'PHP'
                    $none = new class implements DiscountPolicy {
                        public function apply(float $total): float
                        {
                            return $total;
                        }
                    };
                    $quiet = new class implements Notifier {
                        public function notify(string $message): void {}
                    };
                    return expect_same((new Checkout($none, $quiet))->complete([19.99, 13.99]), 33.98);
                    PHP],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if Pint's `single_quote` fixer complains, run `vendor/bin/pint database tests`, check the diff only changes quote style, and re-run the tests).

- [ ] **Step 7: Seed and check**

```bash
php artisan db:seed --class=PhpOopCourseSeeder
php artisan tinker --execute="echo App\Models\Course::where('slug','php-oop')->first()->lessons()->orderBy('position')->pluck('slug')->toJson();"
```

Expected: the seeder finishes with no errors; the list has seven slugs ending `"exceptions","traits-and-readonly-properties","putting-objects-together"`.

- [ ] **Step 8: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/07-putting-objects-together.md app/database/content/php-oop/07-putting-objects-together.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 7: Putting objects together"
```
