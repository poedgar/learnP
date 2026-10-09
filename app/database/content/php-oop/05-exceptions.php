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
            'prompt' => 'Give `OrderLine` a method `setQuantity(int $quantity): void`. A quantity must be from 1 to 10. For anything else, throw an `InvalidArgumentException` with the message `Quantity must be between 1 and 10.` and keep the old quantity.',
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
            'prompt' => 'Write a function `sellWithTill(Till $till, callable $sale): void`. It opens the till, runs `$sale()`, and then closes the till, **even if `$sale()` throws**. If it throws, the exception must still reach whoever called `sellWithTill()`.',
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
