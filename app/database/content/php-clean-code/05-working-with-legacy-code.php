<?php

// Lesson 5 of the Clean code and refactoring course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// The Gilded Rose checks run the learner's updateQuality() and the original side by side for 30 days,
// comparing every item each day, and limit every function and method to 15 lines.

$gildedRose = <<<'PHP'
    <?php

    final class Item
    {
        public function __construct(public string $name, public int $sellIn, public int $quality) {}
    }

    function updateQuality(array $items): void
    {
        foreach ($items as $item) {
            if ($item->name != 'Aged Brie' and $item->name != 'Backstage passes to a TAFKAL80ETC concert') {
                if ($item->quality > 0) {
                    if ($item->name != 'Sulfuras, Hand of Ragnaros') {
                        $item->quality = $item->quality - 1;
                    }
                }
            } else {
                if ($item->quality < 50) {
                    $item->quality = $item->quality + 1;
                    if ($item->name == 'Backstage passes to a TAFKAL80ETC concert') {
                        if ($item->sellIn < 11) {
                            if ($item->quality < 50) {
                                $item->quality = $item->quality + 1;
                            }
                        }
                        if ($item->sellIn < 6) {
                            if ($item->quality < 50) {
                                $item->quality = $item->quality + 1;
                            }
                        }
                    }
                }
            }

            if ($item->name != 'Sulfuras, Hand of Ragnaros') {
                $item->sellIn = $item->sellIn - 1;
            }

            if ($item->sellIn < 0) {
                if ($item->name != 'Aged Brie') {
                    if ($item->name != 'Backstage passes to a TAFKAL80ETC concert') {
                        if ($item->quality > 0) {
                            if ($item->name != 'Sulfuras, Hand of Ragnaros') {
                                $item->quality = $item->quality - 1;
                            }
                        }
                    } else {
                        $item->quality = $item->quality - $item->quality;
                    }
                } else {
                    if ($item->quality < 50) {
                        $item->quality = $item->quality + 1;
                    }
                }
            }
        }
    }
    PHP;

// The original rules, for checks to compare against.
$legacyRules = <<<'PHP'
    $legacy = function (array $items): void {
        foreach ($items as $item) {
            $special = in_array($item->name, ['Aged Brie', 'Backstage passes to a TAFKAL80ETC concert'], true);
            if (! $special) {
                if ($item->quality > 0 && $item->name !== 'Sulfuras, Hand of Ragnaros') {
                    $item->quality--;
                }
            } elseif ($item->quality < 50) {
                $item->quality++;
                if ($item->name === 'Backstage passes to a TAFKAL80ETC concert') {
                    if ($item->sellIn < 11 && $item->quality < 50) {
                        $item->quality++;
                    }
                    if ($item->sellIn < 6 && $item->quality < 50) {
                        $item->quality++;
                    }
                }
            }
            if ($item->name !== 'Sulfuras, Hand of Ragnaros') {
                $item->sellIn--;
            }
            if ($item->sellIn < 0) {
                if ($item->name === 'Aged Brie') {
                    if ($item->quality < 50) {
                        $item->quality++;
                    }
                } elseif ($item->name === 'Backstage passes to a TAFKAL80ETC concert') {
                    $item->quality = 0;
                } elseif ($item->quality > 0 && $item->name !== 'Sulfuras, Hand of Ragnaros') {
                    $item->quality--;
                }
            }
        }
    };
    $make = fn () => [
        new Item('+5 Dexterity Vest', 10, 20),
        new Item('Aged Brie', 2, 0),
        new Item('Elixir of the Mongoose', 5, 7),
        new Item('Sulfuras, Hand of Ragnaros', 0, 80),
        new Item('Sulfuras, Hand of Ragnaros', -1, 80),
        new Item('Backstage passes to a TAFKAL80ETC concert', 15, 20),
        new Item('Backstage passes to a TAFKAL80ETC concert', 10, 49),
        new Item('Backstage passes to a TAFKAL80ETC concert', 5, 49),
        new Item('Backstage passes to a TAFKAL80ETC concert', 1, 20),
        new Item('Aged Brie', -2, 48),
        new Item('Rusty Sword', 3, 0),
    ];
    $snapshot = fn (array $items) => array_map(fn (Item $item) => "{$item->name}: sellIn {$item->sellIn}, quality {$item->quality}", $items);
    $expected = $make();
    $actual = $make();
    for ($day = 1; $day <= 30; $day++) {
        $legacy($expected);
        updateQuality($actual);
        $want = $snapshot($expected);
        $got = $snapshot($actual);
        if ($got !== $want) {
            $index = array_key_first(array_diff_assoc($got, $want));
            return "Day $day: expected {$want[$index]}, got {$got[$index]}.";
        }
    }
    return true;
    PHP;

$noLongFunctions = <<<'PHP'
    $tooLong = [];
    $isLong = fn (ReflectionFunctionAbstract $f) => $f->getEndLine() - $f->getStartLine() + 1 > 15;
    foreach (get_defined_functions()['user'] as $name) {
        $function = new ReflectionFunction($name);
        if (str_ends_with((string) $function->getFileName(), 'main.php') && $isLong($function)) {
            $tooLong[] = $function->getName() . '()';
        }
    }
    foreach (get_declared_classes() as $class) {
        $reflection = new ReflectionClass($class);
        if (! str_ends_with((string) $reflection->getFileName(), 'main.php')) {
            continue;
        }
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $class && $isLong($method)) {
                $tooLong[] = "$class::{$method->getName()}()";
            }
        }
    }
    return $tooLong === [] ? true : 'Longer than 15 lines: ' . implode(', ', $tooLong);
    PHP;

$gildedRoseSolution = <<<'PHP'
    <?php

    final class Item
    {
        public function __construct(public string $name, public int $sellIn, public int $quality) {}
    }

    const AGED_BRIE = 'Aged Brie';
    const BACKSTAGE_PASSES = 'Backstage passes to a TAFKAL80ETC concert';
    const SULFURAS = 'Sulfuras, Hand of Ragnaros';
    const MAX_QUALITY = 50;

    function updateQuality(array $items): void
    {
        foreach ($items as $item) {
            updateItem($item);
        }
    }

    function updateItem(Item $item): void
    {
        if ($item->name === SULFURAS) {
            return;
        }

        $item->sellIn--;
        $item->quality = max(0, min(MAX_QUALITY, $item->quality + qualityChange($item)));
    }

    function qualityChange(Item $item): int
    {
        $expired = $item->sellIn < 0;

        return match ($item->name) {
            AGED_BRIE => $expired ? 2 : 1,
            BACKSTAGE_PASSES => passesChange($item),
            default => $expired ? -2 : -1,
        };
    }

    function passesChange(Item $item): int
    {
        if ($item->sellIn < 0) {
            return -$item->quality;
        }
        if ($item->sellIn < 5) {
            return 3;
        }

        return $item->sellIn < 10 ? 2 : 1;
    }
    PHP;

return [
    'slug' => 'working-with-legacy-code',
    'title' => 'Working with legacy code',
    'summary' => 'Change code that has no tests: seams with default dependencies, sprouting and wrapping new behaviour, removing globals, and refactoring the Gilded Rose kata safely before adding a feature.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // Pin down what this legacy function does before you change it.
        function legacyDiscount(int $pence, string $code): int
        {
            if ($code == 'HALF') {
                return intdiv($pence, 2);
            } else {
                if ($code == 'TENOFF' and $pence > 1000) {
                    return $pence - 1000;
                }
            }

            return $pence;
        }

        foreach ([[3000, 'HALF'], [3000, 'TENOFF'], [500, 'TENOFF'], [3000, '']] as [$pence, $code]) {
            echo "$pence with '$code' → " . legacyDiscount($pence, $code) . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'How does Michael Feathers define legacy code?',
            'options' => [
                'Code more than five years old',
                'Code without tests',
                'Code in an old version of PHP',
                'Code written by someone else',
            ],
            'correct_option' => 1,
            'explanation' => 'Without tests, changes are unsafe, whatever the code\'s age.',
        ],
        [
            'question' => 'What is a seam?',
            'options' => [
                'A place where you can change what code does without editing it there, such as a constructor parameter',
                'A merge conflict',
                'A comment marking where to refactor',
                'A gap in the test coverage',
            ],
            'correct_option' => 0,
            'explanation' => 'A test can pass a different object through the seam.',
        ],
        [
            'question' => 'Why give a new constructor parameter a default, as in `__construct(?Clock $clock = null)`?',
            'options' => [
                'Defaults are required in PHP',
                'Existing callers that use `new ShopSign()` keep working, while tests can pass their own clock',
                'It makes the class faster',
                'To hide the dependency',
            ],
            'correct_option' => 1,
            'explanation' => 'Then the seam can be added without changing every caller at once.',
        ],
        [
            'question' => 'What does "sprout" mean?',
            'options' => [
                'Copy a function and change the copy',
                'Write the new behaviour as a new, tested function or class, and call it from one line of the old code',
                'Delete old code',
                'Split a class in two',
            ],
            'correct_option' => 1,
            'explanation' => 'The new code is clean and tested even though its surroundings aren\'t.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                final class Door
                {
                    public array $events = [];

                    public function open(): void
                    {
                        $this->events[] = 'logged';
                        $this->unlock();
                    }

                    private function unlock(): void
                    {
                        $this->events[] = 'unlocked';
                    }
                }

                $door = new Door();
                $door->open();
                $door->open();
                echo count($door->events) . ' ' . end($door->events);
                PHP,
            'options' => ['`4 unlocked`', '`2 unlocked`', '`4 logged`', 'An error, because unlock() is private'],
            'correct_option' => 0,
            'explanation' => 'Each `open()` adds two events, and the last one is always `unlocked`. Private methods can be called from inside the class.',
        ],
        [
            'question' => 'Why are functions that use `global $config` hard to test?',
            'options' => [
                'Globals are slow',
                'The dependency is hidden from the signature, and a test can only change it by changing global state for everything',
                'PHP doesn\'t allow testing globals',
                'They aren\'t hard to test',
            ],
            'correct_option' => 1,
            'explanation' => 'Passing the configuration in makes the dependency visible and easy to vary.',
        ],
        [
            'question' => 'What is scratch refactoring?',
            'options' => [
                'Refactoring with no plan',
                'Refactoring freely just to understand the code, then throwing that work away and starting again carefully',
                'Deleting all the code and starting from scratch',
                'Refactoring on paper',
            ],
            'correct_option' => 1,
            'explanation' => 'The understanding is what you keep from the first pass.',
        ],
        [
            'question' => 'What is the strangler fig approach?',
            'options' => [
                'Rewriting the whole system at once',
                'Gradually routing features to new code until the old system is no longer used',
                'Deleting unused code',
                'Wrapping every class in a decorator',
            ],
            'correct_option' => 1,
            'explanation' => 'The new system grows around the old one, one feature at a time.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Sprout a function',
            'prompt' => "New requirement: `postEntries()` must skip entries whose `id` is already in the ledger, or appears earlier in the same batch. Don't add it inside the old loop: sprout `newEntries(array \$entries, array \$ledger): array`, which returns only the new entries (first occurrence of each id, in order), and call it once at the start of `postEntries()`.",
            'starter_code' => <<<'PHP'
                <?php

                function postEntries(array $entries, array $ledger): array
                {
                    // ... imagine several hundred lines of untested code around here ...
                    foreach ($entries as $entry) {
                        $ledger[] = ['id' => $entry['id'], 'pence' => $entry['pence'], 'posted' => true];
                    }

                    return $ledger;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function newEntries(array $entries, array $ledger): array
                {
                    $seen = array_flip(array_column($ledger, 'id'));
                    $new = [];

                    foreach ($entries as $entry) {
                        if (! isset($seen[$entry['id']])) {
                            $new[] = $entry;
                            $seen[$entry['id']] = true;
                        }
                    }

                    return $new;
                }

                function postEntries(array $entries, array $ledger): array
                {
                    $entries = newEntries($entries, $ledger);
                    // ... imagine several hundred lines of untested code around here ...
                    foreach ($entries as $entry) {
                        $ledger[] = ['id' => $entry['id'], 'pence' => $entry['pence'], 'posted' => true];
                    }

                    return $ledger;
                }
                PHP,
            'hint' => 'In `newEntries()`, keep a set of ids already seen, starting with the ledger\'s (`array_flip(array_column($ledger, \'id\'))`). Then the old function only needs one new line: `$entries = newEntries($entries, $ledger);`.',
            'checks' => [
                ['label' => 'newEntries()', 'code' => <<<'PHP'
                    $ledger = [['id' => 1, 'pence' => 500, 'posted' => true]];
                    $entries = [['id' => 1, 'pence' => 500], ['id' => 2, 'pence' => 300], ['id' => 3, 'pence' => 200], ['id' => 2, 'pence' => 300]];
                    return expect_same(newEntries($entries, $ledger), [['id' => 2, 'pence' => 300], ['id' => 3, 'pence' => 200]]);
                    PHP],
                ['label' => 'postEntries() calls it', 'code' => <<<'PHP'
                    $function = new ReflectionFunction('postEntries');
                    $source = implode("\n", array_slice(explode("\n", learnp_code()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
                    return str_contains($source, 'newEntries(') ? true : 'postEntries() should call newEntries().';
                    PHP],
                ['label' => 'Duplicates are skipped', 'code' => <<<'PHP'
                    $ledger = postEntries([['id' => 1, 'pence' => 500]], []);
                    $ledger = postEntries([['id' => 1, 'pence' => 500], ['id' => 2, 'pence' => 300], ['id' => 2, 'pence' => 300]], $ledger);
                    return expect_same(array_column($ledger, 'id'), [1, 2]);
                    PHP],
            ],
        ],
        [
            'title' => 'Add a seam',
            'prompt' => "`ReportMailer` creates its own `SmtpMailer`, so it can't be tested without sending email. Add a constructor `__construct(?Mailer \$mailer = null)` that uses the given mailer, or a new `SmtpMailer` when none is passed, so existing `new ReportMailer()` calls still work. `sendDaily()` must no longer create a mailer itself.",
            'starter_code' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $body): void;
                }

                final class SmtpMailer implements Mailer
                {
                    public function send(string $to, string $body): void
                    {
                        echo "SMTP to $to: $body\n";
                    }
                }

                final class ReportMailer
                {
                    public function sendDaily(array $sales): void
                    {
                        $mailer = new SmtpMailer();
                        $mailer->send('boss@example.com', 'Sales today: ' . array_sum($sales));
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $body): void;
                }

                final class SmtpMailer implements Mailer
                {
                    public function send(string $to, string $body): void
                    {
                        echo "SMTP to $to: $body\n";
                    }
                }

                final class ReportMailer
                {
                    private Mailer $mailer;

                    public function __construct(?Mailer $mailer = null)
                    {
                        $this->mailer = $mailer ?? new SmtpMailer();
                    }

                    public function sendDaily(array $sales): void
                    {
                        $this->mailer->send('boss@example.com', 'Sales today: ' . array_sum($sales));
                    }
                }
                PHP,
            'hint' => 'Store `$mailer ?? new SmtpMailer()` in a private property in the constructor, and use `$this->mailer` in `sendDaily()`.',
            'checks' => [
                ['label' => 'A test double can be passed in', 'code' => <<<'PHP'
                    $spy = eval('return new class implements Mailer {
                        public array $sent = [];

                        public function send(string $to, string $body): void
                        {
                            $this->sent[] = "$to: $body";
                        }
                    };');
                    (new ReportMailer($spy))->sendDaily([1200, 800]);
                    return expect_same($spy->sent, ['boss@example.com: Sales today: 2000']);
                    PHP],
                ['label' => 'Existing callers still use SMTP', 'code' => <<<'PHP'
                    ob_start();
                    try {
                        (new ReportMailer())->sendDaily([5, 10]);
                    } finally {
                        $printed = ob_get_clean();
                    }
                    return expect_same($printed, "SMTP to boss@example.com: Sales today: 15\n");
                    PHP],
                ['label' => 'sendDaily() no longer creates a mailer', 'code' => <<<'PHP'
                    $method = new ReflectionMethod('ReportMailer', 'sendDaily');
                    $source = implode("\n", array_slice(explode("\n", learnp_code()), $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
                    return str_contains($source, 'new SmtpMailer') ? 'sendDaily() still creates its own SmtpMailer.' : true;
                    PHP],
            ],
        ],
        [
            'title' => 'Wrap a method',
            'prompt' => "Every payment must now also be logged in a public array `\$log` as `'Paid NAME £X.XX'`. Use Wrap Method: rename the existing `pay()` to a private `dispatchPayment()`, add a private `logPayment()`, and write a new public `pay()` that calls both.",
            'starter_code' => <<<'PHP'
                <?php

                final class Payroll
                {
                    public array $paid = [];

                    public function pay(string $employee, int $pence): void
                    {
                        $this->paid[] = [$employee, $pence];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Payroll
                {
                    public array $paid = [];

                    public array $log = [];

                    public function pay(string $employee, int $pence): void
                    {
                        $this->logPayment($employee, $pence);
                        $this->dispatchPayment($employee, $pence);
                    }

                    private function logPayment(string $employee, int $pence): void
                    {
                        $this->log[] = "Paid $employee £" . number_format($pence / 100, 2);
                    }

                    private function dispatchPayment(string $employee, int $pence): void
                    {
                        $this->paid[] = [$employee, $pence];
                    }
                }
                PHP,
            'hint' => 'The old body moves, unchanged, into `dispatchPayment()`. The new `pay()` has just two lines.',
            'checks' => [
                ['label' => 'Private dispatchPayment() and logPayment()', 'code' => <<<'PHP'
                    foreach (['dispatchPayment', 'logPayment'] as $name) {
                        if (! method_exists('Payroll', $name) || ! (new ReflectionMethod('Payroll', $name))->isPrivate()) {
                            return "Payroll needs a private $name() method.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'Paying still pays, and logs', 'code' => "\$payroll = new Payroll();\n\$payroll->pay('Amara', 250000);\n\$payroll->pay('Ben', 199950);\nreturn expect_same([\$payroll->paid, \$payroll->log], [[['Amara', 250000], ['Ben', 199950]], ['Paid Amara £2,500.00', 'Paid Ben £1,999.50']]);"],
            ],
        ],
        [
            'title' => 'Replace the global',
            'prompt' => 'The functions read a global `$config`. Write `final class Config` with readonly `string $currency` and `float $vatRate`, and change the functions to `grossPrice(Config $config, int $netPence): int` and `label(Config $config, int $pence): string`, with no `global` or `$GLOBALS` anywhere.',
            'starter_code' => <<<'PHP'
                <?php

                $config = ['currency' => 'GBP', 'vatRate' => 0.2];

                function grossPrice(int $netPence): int
                {
                    global $config;

                    return (int) round($netPence * (1 + $config['vatRate']));
                }

                function label(int $pence): string
                {
                    global $config;

                    return $config['currency'] . ' ' . number_format($pence / 100, 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Config
                {
                    public function __construct(public readonly string $currency, public readonly float $vatRate) {}
                }

                function grossPrice(Config $config, int $netPence): int
                {
                    return (int) round($netPence * (1 + $config->vatRate));
                }

                function label(Config $config, int $pence): string
                {
                    return $config->currency . ' ' . number_format($pence / 100, 2);
                }
                PHP,
            'hint' => 'Add the `Config $config` parameter first, read `$config->vatRate` instead of `$config[\'vatRate\']`, and delete the `global` lines and the global array.',
            'checks' => [
                ['label' => 'No globals', 'code' => "return preg_match('/\\bglobal\\s|\\\$GLOBALS/', learnp_code()) === 1 ? 'The code still uses global or \$GLOBALS.' : true;"],
                ['label' => 'The functions take a Config', 'code' => <<<'PHP'
                    foreach (['grossPrice', 'label'] as $name) {
                        $parameters = (new ReflectionFunction($name))->getParameters();
                        if (count($parameters) !== 2 || (string) $parameters[0]->getType() !== 'Config') {
                            return "$name() should take a Config first, then the pence.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'Same results, and the config can vary', 'code' => "\$uk = new Config('GBP', 0.2);\n\$de = new Config('EUR', 0.19);\nreturn expect_same([grossPrice(\$uk, 1000), label(\$uk, 1200), grossPrice(\$de, 1000), label(\$de, 1190)], [1200, 'GBP 12.00', 1190, 'EUR 11.90']);"],
            ],
        ],
        [
            'title' => 'The Gilded Rose: refactor',
            'prompt' => "Refactor the Gilded Rose's `updateQuality(array \$items): void` without changing what it does (the rules are in the lesson). Keep the `Item` class as it is. No function or method may be longer than **15 lines**. The checks compare your version with the original, day by day, for 30 days.",
            'starter_code' => $gildedRose,
            'solution' => $gildedRoseSolution,
            'hint' => 'Handle Sulfuras first with a guard clause. Then, for everything else: take a day off `sellIn`, work out the change in quality for each kind of item in its own small function, and clamp the result between 0 and 50.',
            'checks' => [
                ['label' => 'Same behaviour for 30 days', 'code' => $legacyRules],
                ['label' => 'No function longer than 15 lines', 'code' => $noLongFunctions],
            ],
        ],
        [
            'title' => 'The Gilded Rose: conjured items',
            'prompt' => 'Now add the feature: items whose name starts with `Conjured` lose quality **twice as fast** as normal items (2 a day, 4 after the sell-by date, never below 0). Everything else must behave as before, and no function or method may be longer than 15 lines. Start by refactoring, as in the previous challenge.',
            'starter_code' => $gildedRose,
            'solution' => str_replace(
                <<<'PHP'
                        return match ($item->name) {
                            AGED_BRIE => $expired ? 2 : 1,
                            BACKSTAGE_PASSES => passesChange($item),
                            default => $expired ? -2 : -1,
                        };
                    PHP,
                <<<'PHP'
                        $rate = str_starts_with($item->name, 'Conjured') ? 2 : 1;

                        return match ($item->name) {
                            AGED_BRIE => $expired ? 2 : 1,
                            BACKSTAGE_PASSES => passesChange($item),
                            default => $expired ? -2 * $rate : -$rate,
                        };
                    PHP,
                $gildedRoseSolution,
            ),
            'hint' => 'Once each kind of item has its own small piece of code, conjured items are one more case: the normal change multiplied by 2.',
            'checks' => [
                ['label' => 'Conjured items degrade twice as fast', 'code' => <<<'PHP'
                    $items = [new Item('Conjured Mana Cake', 3, 6), new Item('Conjured Bread', 1, 10)];
                    $days = [];
                    for ($day = 0; $day < 4; $day++) {
                        updateQuality($items);
                        $days[] = array_map(fn (Item $item) => [$item->sellIn, $item->quality], $items);
                    }
                    return expect_same($days, [[[2, 4], [0, 8]], [[1, 2], [-1, 4]], [[0, 0], [-2, 0]], [[-1, 0], [-3, 0]]]);
                    PHP],
                ['label' => 'Everything else is unchanged', 'code' => $legacyRules],
                ['label' => 'No function longer than 15 lines', 'code' => $noLongFunctions],
            ],
        ],
    ],
];
