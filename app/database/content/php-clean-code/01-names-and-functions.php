<?php

// Lesson 1 of the Clean code and refactoring course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Refactoring challenges start from working code: the checks make sure the behaviour is unchanged and
// that the structure asked for is there, reading the learner's code with Reflection and learnp_code().

return [
    'slug' => 'names-and-functions',
    'title' => 'Names and functions',
    'summary' => 'Names that reveal intent, small functions that do one thing at one level of abstraction, no flag arguments, commands separate from queries, and comments that explain why.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // What does this function do? Rename it, its parameter and its variables until it's obvious.
        function f(array $a): int
        {
            $n = 0;
            foreach ($a as $x) {
                if ($x >= 18) {
                    $n++;
                }
            }

            return $n;
        }

        echo f([12, 18, 34, 17, 65]);
        PHP,

    'quiz' => [
        [
            'question' => 'Which is the best name for a variable holding how many days a library loan lasts?',
            'options' => ['`$d`', '`$days`', '`$loanPeriodInDays`', '`$intLoan`'],
            'correct_option' => 2,
            'explanation' => 'It says what the number is and its unit. `$intLoan` encodes the type, which PHP already knows.',
        ],
        [
            'question' => 'Which name suits a method that returns whether a user may log in?',
            'options' => ['`login()`', '`canLogIn()`', '`loginCheck()`', '`doLogin()`'],
            'correct_option' => 1,
            'explanation' => 'Booleans read as questions: `if ($user->canLogIn())`.',
        ],
        [
            'question' => 'What is wrong with a call like `render($page, true)`?',
            'options' => [
                'Nothing',
                'The boolean is a flag argument: the reader can\'t tell what `true` means, and the function does two different things',
                'Booleans can\'t be passed to functions',
                'It\'s too short',
            ],
            'correct_option' => 1,
            'explanation' => 'Split it into two functions whose names say what each does, such as `renderForPrint()`.',
        ],
        [
            'question' => 'What does command-query separation say?',
            'options' => [
                'Commands and queries must be in separate files',
                'A function should either change state (a command) or return an answer (a query), not both',
                'Queries must be written in SQL',
                'Every command needs a matching query',
            ],
            'correct_option' => 1,
            'explanation' => 'Then queries can be called any number of times without side effects.',
        ],
        [
            'question' => 'You describe a function as "validates the order and saves it and emails the customer". What does that suggest?',
            'options' => [
                'It\'s well designed',
                'It does several things and should be split into functions that each do one',
                'It needs more comments',
                'It needs a flag argument',
            ],
            'correct_option' => 1,
            'explanation' => 'Each "and" marks a separate responsibility.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function memberPrice(int $pence): int
                {
                    return intdiv($pence * 90, 100);
                }

                function shippingFor(int $pence): int
                {
                    return $pence >= 2000 ? 0 : 299;
                }

                $price = memberPrice(2000);
                echo $price + shippingFor($price);
                PHP,
            'options' => ['`2099`', '`2000`', '`1800`', '`2299`'],
            'correct_option' => 0,
            'explanation' => 'The member price is 1800, which is under 2000, so 299 shipping is added.',
        ],
        [
            'question' => 'What should you do with commented-out code?',
            'options' => [
                'Keep it in case it\'s needed again',
                'Delete it; version control remembers it, and it only confuses readers',
                'Move it to the end of the file',
                'Turn it into a docblock',
            ],
            'correct_option' => 1,
            'explanation' => 'Nobody knows whether commented-out code still matters, so it never gets deleted unless you do it.',
        ],
        [
            'question' => 'Which comment is most worth keeping?',
            'options' => [
                '`// add one to i` above `$i++;`',
                '`// Amazon rejects titles over 200 characters, so trim here.`',
                '`// function starts here`',
                '`// $old = calculate($x);`',
            ],
            'correct_option' => 1,
            'explanation' => 'It explains why, which the code itself can\'t.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Rename for meaning',
            'prompt' => 'Rename `calc()` to `orderTotal()`, its parameters to `$lines` and `$discountRate`, and every one-letter variable to a name that says what it holds. Behaviour must not change.',
            'starter_code' => <<<'PHP'
                <?php

                function calc(array $a, float $b): float
                {
                    $t = 0;
                    foreach ($a as $x) {
                        $t += $x['price'] * $x['qty'];
                    }

                    return round($t * (1 - $b), 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderTotal(array $lines, float $discountRate): float
                {
                    $subtotal = 0;
                    foreach ($lines as $line) {
                        $subtotal += $line['price'] * $line['qty'];
                    }

                    return round($subtotal * (1 - $discountRate), 2);
                }
                PHP,
            'hint' => 'Rename one thing at a time: the function, then each parameter, then `$t` (a subtotal) and `$x` (one line).',
            'checks' => [
                ['label' => 'orderTotal() replaces calc()', 'code' => <<<'PHP'
                    if (! function_exists('orderTotal')) {
                        return 'There is no orderTotal() function yet.';
                    }
                    if (function_exists('calc')) {
                        return 'calc() still exists.';
                    }
                    $names = array_map(fn (ReflectionParameter $p) => $p->getName(), (new ReflectionFunction('orderTotal'))->getParameters());
                    return expect_same($names, ['lines', 'discountRate']);
                    PHP],
                ['label' => 'No one-letter variables', 'code' => <<<'PHP'
                    preg_match_all('/\$[A-Za-z]\b/', learnp_code(), $matches);
                    return $matches[0] === [] ? true : 'Still one-letter: ' . implode(', ', array_unique($matches[0]));
                    PHP],
                ['label' => 'Same results', 'code' => "\$lines = [['price' => 7.99, 'qty' => 2], ['price' => 12.5, 'qty' => 1]];\nreturn expect_same([orderTotal(\$lines, 0.0), orderTotal(\$lines, 0.1), orderTotal([], 0.5)], [28.48, 25.63, 0.0]);"],
            ],
        ],
        [
            'title' => 'Split a flag argument',
            'prompt' => '`displayName()` takes a `$formal` flag and does two different things. Replace it with `formalName(string $first, string $last)` and `casualName(string $first, string $last)`, and delete `displayName()`.',
            'starter_code' => <<<'PHP'
                <?php

                function displayName(string $first, string $last, bool $formal): string
                {
                    if ($formal) {
                        return strtoupper($last) . ', ' . $first;
                    }

                    return $first . ' ' . substr($last, 0, 1) . '.';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function formalName(string $first, string $last): string
                {
                    return strtoupper($last) . ', ' . $first;
                }

                function casualName(string $first, string $last): string
                {
                    return $first . ' ' . substr($last, 0, 1) . '.';
                }
                PHP,
            'hint' => 'Each branch of the `if` becomes the body of one of the new functions.',
            'checks' => [
                ['label' => 'Two functions, no flag', 'code' => <<<'PHP'
                    if (! function_exists('formalName') || ! function_exists('casualName')) {
                        return 'Write both formalName() and casualName().';
                    }
                    if (function_exists('displayName')) {
                        return 'displayName() still exists.';
                    }
                    foreach (['formalName', 'casualName'] as $name) {
                        if ((new ReflectionFunction($name))->getNumberOfParameters() !== 2) {
                            return "$name() should take just the first and last name.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'Same results', 'code' => "return expect_same([formalName('Ursula', 'Le Guin'), casualName('Ursula', 'Le Guin')], ['LE GUIN, Ursula', 'Ursula L.']);"],
            ],
        ],
        [
            'title' => 'Separate the command from the query',
            'prompt' => "`Account::withdraw()` changes the balance **and** answers whether it worked. Add a query `canWithdraw(int \$pence): bool`, and make `withdraw(int \$pence): void` a command that throws `DomainException` (leaving the balance alone) when there isn't enough money.",
            'starter_code' => <<<'PHP'
                <?php

                final class Account
                {
                    public function __construct(private int $balance) {}

                    public function balance(): int
                    {
                        return $this->balance;
                    }

                    public function withdraw(int $pence): bool
                    {
                        if ($pence > $this->balance) {
                            return false;
                        }
                        $this->balance -= $pence;

                        return true;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Account
                {
                    public function __construct(private int $balance) {}

                    public function balance(): int
                    {
                        return $this->balance;
                    }

                    public function canWithdraw(int $pence): bool
                    {
                        return $pence <= $this->balance;
                    }

                    public function withdraw(int $pence): void
                    {
                        if (! $this->canWithdraw($pence)) {
                            throw new DomainException("Cannot withdraw $pence from {$this->balance}");
                        }
                        $this->balance -= $pence;
                    }
                }
                PHP,
            'hint' => 'Move the comparison into `canWithdraw()`. In `withdraw()`, throw when `! $this->canWithdraw($pence)`, and change the return type to `void`.',
            'checks' => [
                ['label' => 'withdraw() returns nothing', 'code' => "return expect_same((string) (new ReflectionMethod('Account', 'withdraw'))->getReturnType(), 'void');"],
                ['label' => 'canWithdraw() answers without changing anything', 'code' => "\$account = new Account(1000);\nreturn expect_same([\$account->canWithdraw(1000), \$account->canWithdraw(1001), \$account->canWithdraw(1001), \$account->balance()], [true, false, false, 1000]);"],
                ['label' => 'withdraw() takes money or throws', 'code' => "\$account = new Account(1000);\n\$account->withdraw(400);\n\$thrown = expect_throws(DomainException::class, fn () => \$account->withdraw(700));\nreturn \$thrown === true ? expect_same(\$account->balance(), 600) : \$thrown;"],
            ],
        ],
        [
            'title' => 'One level of abstraction',
            'prompt' => "Extract `validSales(array \$sales): array` (sales not refunded and worth more than 0 pence), `totalPence(array \$sales): int` and `formatPounds(int \$pence): string` (e.g. `'£12.50'`), so `salesReport()` reads as a summary: at most **6 lines** from `function` to the closing `}`.",
            'starter_code' => <<<'PHP'
                <?php

                function salesReport(array $sales): string
                {
                    $total = 0;
                    $count = 0;
                    foreach ($sales as $sale) {
                        if ($sale['refunded'] || $sale['pence'] <= 0) {
                            continue;
                        }
                        $total += $sale['pence'];
                        $count++;
                    }
                    $pounds = '£' . number_format($total / 100, 2);

                    return "$count sales, $pounds";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validSales(array $sales): array
                {
                    return array_values(array_filter($sales, fn (array $sale) => ! $sale['refunded'] && $sale['pence'] > 0));
                }

                function totalPence(array $sales): int
                {
                    return array_sum(array_column($sales, 'pence'));
                }

                function formatPounds(int $pence): string
                {
                    return '£' . number_format($pence / 100, 2);
                }

                function salesReport(array $sales): string
                {
                    $valid = validSales($sales);

                    return count($valid) . ' sales, ' . formatPounds(totalPence($valid));
                }
                PHP,
            'hint' => 'Each helper is one expression: `array_filter()` for the valid sales, `array_sum(array_column(...))` for the total, `number_format()` for the pounds.',
            'checks' => [
                ['label' => 'The helpers', 'code' => <<<'PHP'
                    $sales = [['pence' => 1250, 'refunded' => false], ['pence' => 900, 'refunded' => true], ['pence' => 0, 'refunded' => false], ['pence' => 300, 'refunded' => false]];
                    return expect_same(
                        [count(validSales($sales)), totalPence([['pence' => 1250, 'refunded' => false], ['pence' => 300, 'refunded' => false]]), formatPounds(123456)],
                        [2, 1550, '£1,234.56'],
                    );
                    PHP],
                ['label' => 'salesReport() is a short summary', 'code' => <<<'PHP'
                    $function = new ReflectionFunction('salesReport');
                    $lines = $function->getEndLine() - $function->getStartLine() + 1;
                    return $lines <= 6 ? true : "salesReport() is $lines lines long; aim for 6 or fewer.";
                    PHP],
                ['label' => 'Same report', 'code' => "\$sales = [['pence' => 1250, 'refunded' => false], ['pence' => 900, 'refunded' => true], ['pence' => 0, 'refunded' => false], ['pence' => 300, 'refunded' => false]];\nreturn expect_same([salesReport(\$sales), salesReport([])], ['2 sales, £15.50', '0 sales, £0.00']);"],
            ],
        ],
        [
            'title' => 'Name the condition',
            'prompt' => "The long condition in `checkoutMessage()` means \"this user may buy alcohol\". Extract it into `canBuyAlcohol(array \$user): bool` and call that from `checkoutMessage()`, so `checkoutMessage()` no longer mentions `'age'`, `'country'` or `'banned'`.",
            'starter_code' => <<<'PHP'
                <?php

                function checkoutMessage(array $user, array $basket): string
                {
                    if (in_array('wine', $basket, true) && ! ($user['age'] >= 18 && $user['country'] === 'UK' && ! $user['banned'])) {
                        return 'Please remove the wine from your basket.';
                    }

                    return 'Ready to pay.';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function canBuyAlcohol(array $user): bool
                {
                    return $user['age'] >= 18 && $user['country'] === 'UK' && ! $user['banned'];
                }

                function checkoutMessage(array $user, array $basket): string
                {
                    if (in_array('wine', $basket, true) && ! canBuyAlcohol($user)) {
                        return 'Please remove the wine from your basket.';
                    }

                    return 'Ready to pay.';
                }
                PHP,
            'hint' => 'Move the bracketed part into the new function\'s `return`, and replace it with `canBuyAlcohol($user)`.',
            'checks' => [
                ['label' => 'canBuyAlcohol()', 'code' => <<<'PHP'
                    $user = fn (int $age, string $country, bool $banned) => ['age' => $age, 'country' => $country, 'banned' => $banned];
                    return expect_same(
                        [canBuyAlcohol($user(18, 'UK', false)), canBuyAlcohol($user(17, 'UK', false)), canBuyAlcohol($user(40, 'FR', false)), canBuyAlcohol($user(40, 'UK', true))],
                        [true, false, false, false],
                    );
                    PHP],
                ['label' => 'checkoutMessage() uses it', 'code' => <<<'PHP'
                    $function = new ReflectionFunction('checkoutMessage');
                    $source = implode("\n", array_slice(explode("\n", learnp_code()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
                    if (! str_contains($source, 'canBuyAlcohol(')) {
                        return 'checkoutMessage() should call canBuyAlcohol().';
                    }
                    return preg_match("/'(age|country|banned)'/", $source) === 1 ? "checkoutMessage() still looks at the user's details itself." : true;
                    PHP],
                ['label' => 'Same messages', 'code' => <<<'PHP'
                    $adult = ['age' => 30, 'country' => 'UK', 'banned' => false];
                    $child = ['age' => 15, 'country' => 'UK', 'banned' => false];
                    return expect_same(
                        [checkoutMessage($adult, ['wine', 'bread']), checkoutMessage($child, ['wine']), checkoutMessage($child, ['bread'])],
                        ['Ready to pay.', 'Please remove the wine from your basket.', 'Ready to pay.'],
                    );
                    PHP],
            ],
        ],
        [
            'title' => 'Delete dead code',
            'prompt' => 'Remove everything that no longer does anything: the unused `oldShippingCost()` function and all commented-out code. Keep `shippingCost()` working exactly as it does now.',
            'starter_code' => <<<'PHP'
                <?php

                function shippingCost(float $weightKg): float
                {
                    // $cost = $weightKg * 1.5;
                    // if ($weightKg > 30) { return 0; }
                    $base = 3.5;
                    $perExtraKg = 1.25;
                    // $perExtraKg = 1.1;

                    return $base + max(0, ceil($weightKg) - 1) * $perExtraKg;
                }

                function oldShippingCost(float $weightKg): float
                {
                    return $weightKg * 1.5;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shippingCost(float $weightKg): float
                {
                    $base = 3.5;
                    $perExtraKg = 1.25;

                    return $base + max(0, ceil($weightKg) - 1) * $perExtraKg;
                }
                PHP,
            'hint' => 'Delete the three comment lines that contain code and the whole `oldShippingCost()` function. Version control would remember them.',
            'checks' => [
                ['label' => 'No unused function', 'code' => "return function_exists('oldShippingCost') ? 'oldShippingCost() is never called; delete it.' : true;"],
                ['label' => 'No commented-out code', 'code' => <<<'PHP'
                    preg_match_all('/^\s*(\/\/|#).*[$;].*$/m', learnp_code(), $matches);
                    return $matches[0] === [] ? true : 'Still commented out: ' . trim($matches[0][0]);
                    PHP],
                ['label' => 'Same costs', 'code' => 'return expect_same([shippingCost(0.5), shippingCost(1.0), shippingCost(2.2), shippingCost(40.0)], [3.5, 3.5, 6.0, 52.25]);'],
            ],
        ],
    ],
];
