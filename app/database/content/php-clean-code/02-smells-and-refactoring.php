<?php

// Lesson 2 of the Clean code and refactoring course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Refactoring challenges start from working code: the checks make sure the behaviour is unchanged and
// that the structure asked for is there, reading the learner's code with Reflection and learnp_code().

return [
    'slug' => 'smells-and-refactoring',
    'title' => 'Code smells and refactoring moves',
    'summary' => 'Recognise code smells such as duplication, long functions and feature envy, and fix them with small safe refactorings: Extract Function, Extract Variable, Split Loop, Move Function and Replace Temp with Query.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // Two functions share most of their code. Extract what they share, then check the output is the same.
        function hardbackLabel(string $title, int $pence): string
        {
            return strtoupper($title) . ' (hardback) £' . number_format($pence / 100, 2);
        }

        function paperbackLabel(string $title, int $pence): string
        {
            return strtoupper($title) . ' (paperback) £' . number_format($pence / 100, 2);
        }

        echo hardbackLabel('Dune', 2499) . "\n" . paperbackLabel('Dune', 999);
        PHP,

    'quiz' => [
        [
            'question' => 'What is refactoring?',
            'options' => [
                'Rewriting code from scratch',
                'Changing the structure of code without changing its behaviour, in small safe steps',
                'Adding new features',
                'Fixing bugs',
            ],
            'correct_option' => 1,
            'explanation' => 'If the behaviour changes, it isn\'t refactoring.',
        ],
        [
            'question' => 'What is a code smell?',
            'options' => [
                'A bug',
                'A sign that code may be hard to change, pointing to where a refactoring might help',
                'A syntax error',
                'Code that runs slowly',
            ],
            'correct_option' => 1,
            'explanation' => 'Smells are hints, not rules: duplication, long functions, feature envy and so on.',
        ],
        [
            'question' => 'A function in `ReportBuilder` reads six properties of an `Order` and none of its own. Which smell is this?',
            'options' => ['Feature envy', 'Duplicated code', 'Long parameter list', 'Speculative generality'],
            'correct_option' => 0,
            'explanation' => 'The fix is usually Move Function: put the behaviour in `Order`, next to its data.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $orders = [['pence' => 500, 'items' => 1], ['pence' => 1500, 'items' => 4]];

                $revenue = array_sum(array_column($orders, 'pence'));
                $mostItems = max(array_column($orders, 'items'));

                echo "$revenue $mostItems";
                PHP,
            'options' => ['`2000 4`', '`2000 5`', '`1500 4`', '`500 1`'],
            'correct_option' => 0,
            'explanation' => 'After Split Loop, each value has its own one-line calculation.',
        ],
        [
            'question' => 'Why is going through a list twice after Split Loop usually fine?',
            'options' => [
                'PHP caches loops',
                'Looping is rarely what makes code slow, and the clearer code is easier to optimise if it ever needs it',
                'It isn\'t fine; never split loops',
                'Two loops are always faster than one',
            ],
            'correct_option' => 1,
            'explanation' => 'Measure before optimising; clarity first.',
        ],
        [
            'question' => 'What does Replace Temp with Query do?',
            'options' => [
                'Turns a temporary variable holding a calculation into a small method that other methods can use too',
                'Replaces SQL queries with arrays',
                'Deletes temporary variables',
                'Makes a variable global',
            ],
            'correct_option' => 0,
            'explanation' => '`$basePrice = ...` becomes `$this->basePrice()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                final class Rental
                {
                    public function __construct(private int $days) {}

                    public function charge(): int
                    {
                        return $this->days * 300 + $this->lateFee();
                    }

                    private function lateFee(): int
                    {
                        return $this->days > 7 ? 500 : 0;
                    }
                }

                echo (new Rental(7))->charge() + (new Rental(8))->charge();
                PHP,
            'options' => ['`5000`', '`4500`', '`5500`', '`2100`'],
            'correct_option' => 0,
            'explanation' => '7 days is 2100 with no fee; 8 days is 2400 + 500 = 2900. Together 5000.',
        ],
        [
            'question' => 'Your tests fail in the middle of a refactoring. What should you do?',
            'options' => [
                'Carry on; they\'ll pass at the end',
                'Undo the last step and take a smaller one',
                'Delete the failing tests',
                'Switch to rewriting the code',
            ],
            'correct_option' => 1,
            'explanation' => 'Every step should leave the code working.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Remove the duplication',
            'prompt' => "Replace the three ticket functions with one `ticketPrice(string \$type, int \$basePence): int` for `'adult'`, `'child'` and `'senior'`, keeping the percentages in one place. An unknown type throws `InvalidArgumentException`. Delete the old functions.",
            'starter_code' => <<<'PHP'
                <?php

                function adultTicket(int $basePence): int
                {
                    return intdiv($basePence * 100, 100) + 150;
                }

                function childTicket(int $basePence): int
                {
                    return intdiv($basePence * 50, 100) + 150;
                }

                function seniorTicket(int $basePence): int
                {
                    return intdiv($basePence * 70, 100) + 150;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                const BOOKING_FEE_PENCE = 150;

                function ticketPrice(string $type, int $basePence): int
                {
                    $percent = ['adult' => 100, 'child' => 50, 'senior' => 70][$type]
                        ?? throw new InvalidArgumentException("Unknown ticket type: $type");

                    return intdiv($basePence * $percent, 100) + BOOKING_FEE_PENCE;
                }
                PHP,
            'hint' => 'Only the percentage differs. Look it up in an array keyed by type, with `?? throw new InvalidArgumentException(...)`.',
            'checks' => [
                ['label' => 'One function instead of three', 'code' => <<<'PHP'
                    foreach (['adultTicket', 'childTicket', 'seniorTicket'] as $old) {
                        if (function_exists($old)) {
                            return "$old() still exists.";
                        }
                    }
                    return function_exists('ticketPrice') ? true : 'There is no ticketPrice() yet.';
                    PHP],
                ['label' => 'Same prices', 'code' => "return expect_same([ticketPrice('adult', 1000), ticketPrice('child', 1000), ticketPrice('senior', 1000), ticketPrice('child', 1235)], [1150, 650, 850, 767]);"],
                ['label' => 'Unknown types are rejected', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => ticketPrice('student', 1000));"],
            ],
        ],
        [
            'title' => 'Split the loop',
            'prompt' => '`summary()` works out two unrelated things in one loop. Write `youngestAge(array $people): int` and `totalSalary(array $people): int` **without any loops** (use array functions), and make `summary()` use them. The people list is never empty.',
            'starter_code' => <<<'PHP'
                <?php

                function summary(array $people): string
                {
                    $youngest = PHP_INT_MAX;
                    $totalSalary = 0;
                    foreach ($people as $person) {
                        if ($person['age'] < $youngest) {
                            $youngest = $person['age'];
                        }
                        $totalSalary += $person['salary'];
                    }

                    return "youngest: $youngest, total salary: $totalSalary";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function youngestAge(array $people): int
                {
                    return min(array_column($people, 'age'));
                }

                function totalSalary(array $people): int
                {
                    return array_sum(array_column($people, 'salary'));
                }

                function summary(array $people): string
                {
                    return 'youngest: ' . youngestAge($people) . ', total salary: ' . totalSalary($people);
                }
                PHP,
            'hint' => '`array_column($people, \'age\')` gives a list of ages; `min()` and `array_sum()` finish the job.',
            'checks' => [
                ['label' => 'The two functions', 'code' => "\$people = [['age' => 34, 'salary' => 42000], ['age' => 22, 'salary' => 31000], ['age' => 51, 'salary' => 58000]];\nreturn expect_same([youngestAge(\$people), totalSalary(\$people)], [22, 131000]);"],
                ['label' => 'No loops', 'code' => "return preg_match('/\\b(foreach|for|while)\\s*\\(/', learnp_code()) === 1 ? 'There is still a loop.' : true;"],
                ['label' => 'Same summary', 'code' => "return expect_same(summary([['age' => 34, 'salary' => 42000], ['age' => 22, 'salary' => 31000]]), 'youngest: 22, total salary: 73000');"],
            ],
        ],
        [
            'title' => 'Move the envious function',
            'prompt' => "`shippingCost()` only uses an `Order`'s data. Move it into `Order` as `shippingCost(): int`, delete the free function, and make `\$items` and `\$country` private, since nothing outside needs them any more.",
            'starter_code' => <<<'PHP'
                <?php

                final class Order
                {
                    public function __construct(public readonly array $items, public readonly string $country) {}
                }

                function shippingCost(Order $order): int
                {
                    $grams = array_sum(array_column($order->items, 'grams'));
                    $pencePer100g = $order->country === 'UK' ? 2 : 5;
                    $handling = $order->country === 'UK' ? 0 : 500;

                    return intdiv($grams, 100) * $pencePer100g + $handling;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Order
                {
                    public function __construct(private array $items, private string $country) {}

                    public function shippingCost(): int
                    {
                        $grams = array_sum(array_column($this->items, 'grams'));
                        $pencePer100g = $this->country === 'UK' ? 2 : 5;
                        $handling = $this->country === 'UK' ? 0 : 500;

                        return intdiv($grams, 100) * $pencePer100g + $handling;
                    }
                }
                PHP,
            'hint' => 'Cut the function into the class, drop the `Order $order` parameter, and change each `$order->` to `$this->`.',
            'checks' => [
                ['label' => 'The method lives in Order', 'code' => <<<'PHP'
                    if (function_exists('shippingCost')) {
                        return 'The free shippingCost() function still exists.';
                    }
                    return method_exists('Order', 'shippingCost') ? true : 'Order has no shippingCost() method yet.';
                    PHP],
                ['label' => 'The data is private', 'code' => <<<'PHP'
                    $class = new ReflectionClass('Order');
                    foreach (['items', 'country'] as $name) {
                        if (! $class->hasProperty($name) || ! $class->getProperty($name)->isPrivate()) {
                            return "\$$name should be a private property.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'Same costs', 'code' => "\$items = [['grams' => 450], ['grams' => 1200]];\nreturn expect_same([(new Order(\$items, 'UK'))->shippingCost(), (new Order(\$items, 'FR'))->shippingCost(), (new Order([], 'UK'))->shippingCost()], [32, 580, 0]);"],
            ],
        ],
        [
            'title' => 'Replace temps with queries',
            'prompt' => 'In `Invoice::total()`, turn the temporary variables into **private** methods `basePrice(): int` and `discount(): int`, so that `total()` is a single `return` (at most 4 lines from `public function` to `}`).',
            'starter_code' => <<<'PHP'
                <?php

                final class Invoice
                {
                    public function __construct(private int $quantity, private int $itemPence) {}

                    public function total(): int
                    {
                        $basePrice = $this->quantity * $this->itemPence;
                        $discount = $basePrice > 100000 ? intdiv($basePrice, 20) : 0;

                        return $basePrice - $discount;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Invoice
                {
                    public function __construct(private int $quantity, private int $itemPence) {}

                    public function total(): int
                    {
                        return $this->basePrice() - $this->discount();
                    }

                    private function basePrice(): int
                    {
                        return $this->quantity * $this->itemPence;
                    }

                    private function discount(): int
                    {
                        return $this->basePrice() > 100000 ? intdiv($this->basePrice(), 20) : 0;
                    }
                }
                PHP,
            'hint' => 'Each temp\'s expression becomes a method\'s `return`. Inside `discount()`, use `$this->basePrice()` instead of the variable.',
            'checks' => [
                ['label' => 'Private query methods', 'code' => <<<'PHP'
                    foreach (['basePrice', 'discount'] as $name) {
                        if (! method_exists('Invoice', $name) || ! (new ReflectionMethod('Invoice', $name))->isPrivate()) {
                            return "Invoice needs a private $name() method.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'total() is a single return', 'code' => <<<'PHP'
                    $method = new ReflectionMethod('Invoice', 'total');
                    $lines = $method->getEndLine() - $method->getStartLine() + 1;
                    return $lines <= 4 ? true : "total() is $lines lines long; it should be just a return.";
                    PHP],
                ['label' => 'Same totals', 'code' => 'return expect_same([(new Invoice(10, 999))->total(), (new Invoice(200, 1000))->total(), (new Invoice(100, 1000))->total()], [9990, 190000, 100000]);'],
            ],
        ],
        [
            'title' => 'Decompose the statement',
            'prompt' => '`statement()` is long, with a `switch` buried in its loop. Extract `amountFor(array $performance, array $play): int` (the price in pence) and `volumeCreditsFor(array $performance, array $play): int`, so `statement()` has no `switch` and is at most **18 lines** long. The text it returns must not change.',
            'starter_code' => <<<'PHP'
                <?php

                function statement(array $invoice, array $plays): string
                {
                    $total = 0;
                    $credits = 0;
                    $result = "Statement for {$invoice['customer']}\n";

                    foreach ($invoice['performances'] as $perf) {
                        $play = $plays[$perf['playID']];
                        switch ($play['type']) {
                            case 'tragedy':
                                $amount = 40000;
                                if ($perf['audience'] > 30) {
                                    $amount += 1000 * ($perf['audience'] - 30);
                                }
                                break;
                            case 'comedy':
                                $amount = 30000;
                                if ($perf['audience'] > 20) {
                                    $amount += 10000 + 500 * ($perf['audience'] - 20);
                                }
                                $amount += 300 * $perf['audience'];
                                break;
                            default:
                                throw new DomainException("Unknown type: {$play['type']}");
                        }

                        $credits += max($perf['audience'] - 30, 0);
                        if ($play['type'] === 'comedy') {
                            $credits += intdiv($perf['audience'], 5);
                        }

                        $result .= "  {$play['name']}: £" . number_format($amount / 100, 2) . " ({$perf['audience']} seats)\n";
                        $total += $amount;
                    }

                    $result .= 'Amount owed is £' . number_format($total / 100, 2) . "\n";
                    $result .= "You earned $credits credits\n";

                    return $result;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function amountFor(array $performance, array $play): int
                {
                    $audience = $performance['audience'];

                    return match ($play['type']) {
                        'tragedy' => 40000 + 1000 * max($audience - 30, 0),
                        'comedy' => 30000 + ($audience > 20 ? 10000 + 500 * ($audience - 20) : 0) + 300 * $audience,
                        default => throw new DomainException("Unknown type: {$play['type']}"),
                    };
                }

                function volumeCreditsFor(array $performance, array $play): int
                {
                    $credits = max($performance['audience'] - 30, 0);

                    return $play['type'] === 'comedy' ? $credits + intdiv($performance['audience'], 5) : $credits;
                }

                function statement(array $invoice, array $plays): string
                {
                    $total = 0;
                    $credits = 0;
                    $result = "Statement for {$invoice['customer']}\n";

                    foreach ($invoice['performances'] as $performance) {
                        $play = $plays[$performance['playID']];
                        $amount = amountFor($performance, $play);
                        $credits += volumeCreditsFor($performance, $play);
                        $total += $amount;
                        $result .= "  {$play['name']}: £" . number_format($amount / 100, 2) . " ({$performance['audience']} seats)\n";
                    }

                    $result .= 'Amount owed is £' . number_format($total / 100, 2) . "\n";

                    return $result . "You earned $credits credits\n";
                }
                PHP,
            'hint' => 'Move the `switch` (or a `match`) into `amountFor()`, returning the amount, and the two credit lines into `volumeCreditsFor()`. The loop then calls both.',
            'checks' => [
                ['label' => 'amountFor() and volumeCreditsFor()', 'code' => <<<'PHP'
                    $tragedy = ['name' => 'Hamlet', 'type' => 'tragedy'];
                    $comedy = ['name' => 'As You Like It', 'type' => 'comedy'];
                    return expect_same(
                        [amountFor(['audience' => 55], $tragedy), amountFor(['audience' => 35], $comedy), amountFor(['audience' => 10], $comedy), volumeCreditsFor(['audience' => 55], $tragedy), volumeCreditsFor(['audience' => 35], $comedy)],
                        [65000, 58000, 33000, 25, 12],
                    );
                    PHP],
                ['label' => 'statement() is short and has no switch', 'code' => <<<'PHP'
                    $function = new ReflectionFunction('statement');
                    $source = implode("\n", array_slice(explode("\n", learnp_code()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
                    if (preg_match('/\bswitch\s*\(/', $source) === 1) {
                        return 'statement() still has a switch.';
                    }
                    $lines = $function->getEndLine() - $function->getStartLine() + 1;
                    return $lines <= 18 ? true : "statement() is $lines lines long; aim for 18 or fewer.";
                    PHP],
                ['label' => 'The same statement', 'code' => <<<'PHP'
                    $plays = [
                        'hamlet' => ['name' => 'Hamlet', 'type' => 'tragedy'],
                        'as-like' => ['name' => 'As You Like It', 'type' => 'comedy'],
                        'othello' => ['name' => 'Othello', 'type' => 'tragedy'],
                    ];
                    $invoice = ['customer' => 'BigCo', 'performances' => [
                        ['playID' => 'hamlet', 'audience' => 55],
                        ['playID' => 'as-like', 'audience' => 35],
                        ['playID' => 'othello', 'audience' => 40],
                    ]];
                    return expect_same(statement($invoice, $plays), "Statement for BigCo\n  Hamlet: £650.00 (55 seats)\n  As You Like It: £580.00 (35 seats)\n  Othello: £500.00 (40 seats)\nAmount owed is £1,730.00\nYou earned 47 credits\n");
                    PHP],
                ['label' => 'Unknown play types still throw', 'code' => "return expect_throws(DomainException::class, fn () => amountFor(['audience' => 10], ['name' => 'Cats', 'type' => 'musical']));"],
            ],
        ],
        [
            'title' => 'Explaining variables',
            'prompt' => 'Make `price()` readable by giving each part of its expression a name: introduce `$basePrice`, `$quantityDiscount` and `$shipping`, and end with `return $basePrice - $quantityDiscount + $shipping;`. The results must be exactly the same.',
            'starter_code' => <<<'PHP'
                <?php

                function price(array $order): float
                {
                    return $order['quantity'] * $order['itemPrice']
                        - max(0, $order['quantity'] - 500) * $order['itemPrice'] * 0.05
                        + min($order['quantity'] * $order['itemPrice'] * 0.1, 100);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function price(array $order): float
                {
                    $basePrice = $order['quantity'] * $order['itemPrice'];
                    $quantityDiscount = max(0, $order['quantity'] - 500) * $order['itemPrice'] * 0.05;
                    $shipping = min($basePrice * 0.1, 100);

                    return $basePrice - $quantityDiscount + $shipping;
                }
                PHP,
            'hint' => 'Each line of the original expression is one variable. Once `$basePrice` exists, use it inside `$shipping` too.',
            'checks' => [
                ['label' => 'Named parts', 'code' => <<<'PHP'
                    $code = preg_replace('/\s+/', ' ', learnp_code());
                    foreach (['$basePrice =', '$quantityDiscount =', '$shipping ='] as $part) {
                        if (! str_contains($code, $part)) {
                            return "Introduce $part ...";
                        }
                    }
                    return str_contains($code, 'return $basePrice - $quantityDiscount + $shipping;') ? true : 'End with return $basePrice - $quantityDiscount + $shipping;';
                    PHP],
                ['label' => 'Exactly the same results', 'code' => "\$orders = [['quantity' => 10, 'itemPrice' => 4.5], ['quantity' => 600, 'itemPrice' => 2.0], ['quantity' => 2000, 'itemPrice' => 1.25]];\nreturn expect_same(array_map(price(...), \$orders), [49.5, 1290.0, 2506.25]);"],
            ],
        ],
    ],
];
