<?php

// Lesson 3 of the Clean code and refactoring course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Refactoring challenges start from working code: the checks make sure the behaviour is unchanged and
// that the structure asked for is there, reading the learner's code with Reflection and learnp_code().

return [
    'slug' => 'simplifying-conditionals',
    'title' => 'Simplifying conditionals',
    'summary' => 'Untangle decisions: guard clauses instead of nesting, returning conditions directly, decomposing and consolidating conditions, lookup tables, polymorphism instead of type switches, and the Null Object.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // Rewrite this with guard clauses so there's no else and no nesting.
        function shipping(int $pence, bool $member): int
        {
            if ($member) {
                return 0;
            } else {
                if ($pence >= 5000) {
                    return 0;
                } else {
                    return 399;
                }
            }
        }

        echo shipping(1000, false) . ' ' . shipping(6000, false) . ' ' . shipping(1000, true);
        PHP,

    'quiz' => [
        [
            'question' => 'What is a guard clause?',
            'options' => [
                'A security check',
                'A check at the top of a function that handles a special case and returns straight away',
                'A `try`/`catch` block',
                'A comment warning about a condition',
            ],
            'correct_option' => 1,
            'explanation' => 'Guard clauses leave the main path unindented, with no `else` needed.',
        ],
        [
            'question' => 'How can this be simplified? `if ($stock > 0) { return true; } return false;`',
            'options' => [
                '`return $stock > 0;`',
                '`return $stock;`',
                '`return true;`',
                'It can\'t',
            ],
            'correct_option' => 0,
            'explanation' => 'The condition already is the boolean you want to return.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                const SHELF_FOR_GENRE = ['fantasy' => 'F', 'history' => 'H'];

                function shelf(string $genre): string
                {
                    return SHELF_FOR_GENRE[$genre] ?? 'General';
                }

                echo shelf('history') . shelf('fantasy') . ' ' . shelf('travel');
                PHP,
            'options' => ['`HF General`', '`H F General`', '`HF travel`', 'An error for travel'],
            'correct_option' => 0,
            'explanation' => 'Missing keys fall back to `\'General\'` thanks to `??`.',
        ],
        [
            'question' => 'When is replacing a `switch` with polymorphism most worthwhile?',
            'options' => [
                'Always, for every `switch`',
                'When the same decision on a type is repeated in several places, so each new type would mean editing all of them',
                'Never',
                'Only when the `switch` has exactly two cases',
            ],
            'correct_option' => 1,
            'explanation' => 'One `match` in one place is fine. Repetition is the smell.',
        ],
        [
            'question' => 'What is a Null Object?',
            'options' => [
                'A variable set to `null`',
                'A real object representing "nothing" with harmless behaviour, so callers don\'t need `null` checks',
                'An object with no methods',
                'An object that throws on every call',
            ],
            'correct_option' => 1,
            'explanation' => '`NoDiscount` returns the price unchanged, so code can always call `apply()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function band(int $score): string
                {
                    if ($score < 0) {
                        return 'invalid';
                    }
                    if ($score < 50) {
                        return 'low';
                    }

                    return $score < 80 ? 'mid' : 'high';
                }

                echo band(-5) . ' ' . band(50) . ' ' . band(80);
                PHP,
            'options' => ['`invalid mid high`', '`invalid low mid`', '`low mid high`', '`invalid mid mid`'],
            'correct_option' => 0,
            'explanation' => '50 isn\'t below 50, so it\'s mid; 80 isn\'t below 80, so it\'s high.',
        ],
        [
            'question' => 'Three separate `if`s at the top of a function all `return 0`. What refactoring fits?',
            'options' => [
                'Consolidate them into one condition, ideally extracted into a well-named function',
                'Add an `else` to each',
                'Replace them with a `switch`',
                'Wrap them in `try`/`catch`',
            ],
            'correct_option' => 0,
            'explanation' => 'Checks with the same result are one rule; give that rule a name.',
        ],
        [
            'question' => 'Which condition is easiest to read?',
            'options' => [
                '`if (! $isNotEmpty)`',
                '`if ($hasItems)`',
                '`if (! ! $hasItems)`',
                '`if ($isEmpty === false)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Positive names avoid the reader having to undo negatives.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Guard clauses',
            'prompt' => 'Rewrite `payAmount()` with guard clauses: no `else` and no `$result` variable, just an early `return` for each special case. The amounts must not change.',
            'starter_code' => <<<'PHP'
                <?php

                function payAmount(array $employee): int
                {
                    if ($employee['isSeparated']) {
                        $result = 0;
                    } else {
                        if ($employee['isRetired']) {
                            $result = $employee['pension'];
                        } else {
                            if ($employee['onLeave']) {
                                $result = intdiv($employee['salary'], 2);
                            } else {
                                $result = $employee['salary'];
                            }
                        }
                    }

                    return $result;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function payAmount(array $employee): int
                {
                    if ($employee['isSeparated']) {
                        return 0;
                    }
                    if ($employee['isRetired']) {
                        return $employee['pension'];
                    }
                    if ($employee['onLeave']) {
                        return intdiv($employee['salary'], 2);
                    }

                    return $employee['salary'];
                }
                PHP,
            'hint' => 'Turn each `$result = …` into `return …`, then the `else` blocks become unnecessary and can be unwrapped.',
            'checks' => [
                ['label' => 'No else and no $result', 'code' => <<<'PHP'
                    $code = learnp_code();
                    if (preg_match('/\belse\b/', $code) === 1) {
                        return 'There is still an else.';
                    }
                    return str_contains($code, '$result') ? 'There is still a $result variable.' : true;
                    PHP],
                ['label' => 'Same amounts', 'code' => <<<'PHP'
                    $employee = fn (bool $separated, bool $retired, bool $leave) => ['isSeparated' => $separated, 'isRetired' => $retired, 'onLeave' => $leave, 'salary' => 3000, 'pension' => 1200];
                    return expect_same(
                        [payAmount($employee(true, true, true)), payAmount($employee(false, true, true)), payAmount($employee(false, false, true)), payAmount($employee(false, false, false))],
                        [0, 1200, 1500, 3000],
                    );
                    PHP],
            ],
        ],
        [
            'title' => 'Return the condition',
            'prompt' => 'Each of these functions spells out `return true` and `return false`. Make each one a single `return` of its condition, with no `if`.',
            'starter_code' => <<<'PHP'
                <?php

                function isWeekend(DateTimeImmutable $date): bool
                {
                    if ((int) $date->format('N') >= 6) {
                        return true;
                    } else {
                        return false;
                    }
                }

                function hasStock(array $product): bool
                {
                    if ($product['stock'] > 0) {
                        return true;
                    }

                    return false;
                }

                function isBlank(string $text): bool
                {
                    if (trim($text) === '') {
                        return true;
                    } else {
                        return false;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isWeekend(DateTimeImmutable $date): bool
                {
                    return (int) $date->format('N') >= 6;
                }

                function hasStock(array $product): bool
                {
                    return $product['stock'] > 0;
                }

                function isBlank(string $text): bool
                {
                    return trim($text) === '';
                }
                PHP,
            'hint' => 'Whatever is inside each `if (…)` is what to return.',
            'checks' => [
                ['label' => 'No if, no return true or false', 'code' => <<<'PHP'
                    $code = learnp_code();
                    if (preg_match('/\bif\s*\(/', $code) === 1) {
                        return 'There is still an if.';
                    }
                    return preg_match('/return\s+(true|false)\s*;/', $code) === 1 ? 'There is still a return true or return false.' : true;
                    PHP],
                ['label' => 'Same answers', 'code' => <<<'PHP'
                    return expect_same(
                        [isWeekend(new DateTimeImmutable('2026-10-17')), isWeekend(new DateTimeImmutable('2026-10-16')), hasStock(['stock' => 3]), hasStock(['stock' => 0]), isBlank('  '), isBlank(' a ')],
                        [true, false, true, false, true, false],
                    );
                    PHP],
            ],
        ],
        [
            'title' => 'A lookup table',
            'prompt' => 'Replace the `elseif` chain in `vatRate()` with a constant `VAT_RATES`, an array from country code to rate, and a single lookup. `vatRate()` must have no `if`, `elseif` or `switch`, and an unknown country still throws `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function vatRate(string $country): float
                {
                    if ($country === 'UK') {
                        return 0.2;
                    } elseif ($country === 'FR') {
                        return 0.2;
                    } elseif ($country === 'DE') {
                        return 0.19;
                    } elseif ($country === 'IE') {
                        return 0.23;
                    } elseif ($country === 'LU') {
                        return 0.17;
                    } else {
                        throw new InvalidArgumentException("No VAT rate for $country");
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                const VAT_RATES = [
                    'UK' => 0.2,
                    'FR' => 0.2,
                    'DE' => 0.19,
                    'IE' => 0.23,
                    'LU' => 0.17,
                ];

                function vatRate(string $country): float
                {
                    return VAT_RATES[$country] ?? throw new InvalidArgumentException("No VAT rate for $country");
                }
                PHP,
            'hint' => 'Declare `const VAT_RATES = [\'UK\' => 0.2, …];` and return `VAT_RATES[$country] ?? throw new InvalidArgumentException(...)`.',
            'checks' => [
                ['label' => 'The VAT_RATES table', 'code' => "return defined('VAT_RATES') ? expect_same(VAT_RATES, ['UK' => 0.2, 'FR' => 0.2, 'DE' => 0.19, 'IE' => 0.23, 'LU' => 0.17]) : 'There is no VAT_RATES constant yet.';"],
                ['label' => 'No if, elseif or switch', 'code' => "return preg_match('/\\b(if|elseif|switch)\\s*\\(/', learnp_code()) === 1 ? 'vatRate() still has an if, elseif or switch.' : true;"],
                ['label' => 'Same rates', 'code' => "\$rates = array_map(vatRate(...), ['UK', 'DE', 'IE', 'LU']);\n\$thrown = expect_throws(InvalidArgumentException::class, fn () => vatRate('US'));\nreturn \$thrown === true ? expect_same(\$rates, [0.2, 0.19, 0.23, 0.17]) : \$thrown;"],
            ],
        ],
        [
            'title' => 'Shapes that know their area',
            'prompt' => "Replace `area()`'s `switch` with polymorphism: an interface `Shape` with `area(): float`, and classes `Circle(float \$radius)`, `Rectangle(float \$width, float \$height)` and `Triangle(float \$base, float \$height)` implementing it. Add `totalArea(array \$shapes): float`, delete `area()`, and use no `switch` or `match` anywhere.",
            'starter_code' => <<<'PHP'
                <?php

                function area(array $shape): float
                {
                    switch ($shape['type']) {
                        case 'circle':
                            return M_PI * $shape['radius'] ** 2;
                        case 'rectangle':
                            return $shape['width'] * $shape['height'];
                        case 'triangle':
                            return $shape['base'] * $shape['height'] / 2;
                    }

                    throw new InvalidArgumentException('Unknown shape');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Shape
                {
                    public function area(): float;
                }

                final class Circle implements Shape
                {
                    public function __construct(private float $radius) {}

                    public function area(): float
                    {
                        return M_PI * $this->radius ** 2;
                    }
                }

                final class Rectangle implements Shape
                {
                    public function __construct(private float $width, private float $height) {}

                    public function area(): float
                    {
                        return $this->width * $this->height;
                    }
                }

                final class Triangle implements Shape
                {
                    public function __construct(private float $base, private float $height) {}

                    public function area(): float
                    {
                        return $this->base * $this->height / 2;
                    }
                }

                function totalArea(array $shapes): float
                {
                    return array_sum(array_map(fn (Shape $shape) => $shape->area(), $shapes));
                }
                PHP,
            'hint' => 'Each `case` becomes the `area()` method of one class, using its own properties. `totalArea()` sums `$shape->area()` over the list.',
            'checks' => [
                ['label' => 'Three shapes implement Shape', 'code' => <<<'PHP'
                    if (! interface_exists('Shape')) {
                        return 'There is no Shape interface yet.';
                    }
                    foreach (['Circle', 'Rectangle', 'Triangle'] as $class) {
                        if (! class_exists($class) || ! is_subclass_of($class, 'Shape')) {
                            return "$class should be a class implementing Shape.";
                        }
                    }
                    return function_exists('area') ? 'area() still exists.' : true;
                    PHP],
                ['label' => 'No switch or match', 'code' => "return preg_match('/\\b(switch|match)\\s*\\(/', learnp_code()) === 1 ? 'There is still a switch or match.' : true;"],
                ['label' => 'Same areas', 'code' => 'return expect_same([round((new Circle(2))->area(), 4), (new Rectangle(3, 4))->area(), (new Triangle(6, 5))->area(), round(totalArea([new Circle(1), new Rectangle(2, 2), new Triangle(2, 3)]), 4)], [12.5664, 12.0, 15.0, 10.1416]);'],
            ],
        ],
        [
            'title' => 'A Null Object for guests',
            'prompt' => "Add `UnknownCustomer implements Customer`, with the name `'guest'` and a 0% discount. Make `findCustomer()` return `Customer` (never `null`), returning an `UnknownCustomer` when the id is missing, and remove every `null` check from `greeting()` and `priceFor()`, which now take a `Customer`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Customer
                {
                    public function name(): string;

                    public function discountPercent(): int;
                }

                final class RegisteredCustomer implements Customer
                {
                    public function __construct(private string $name, private int $discountPercent) {}

                    public function name(): string
                    {
                        return $this->name;
                    }

                    public function discountPercent(): int
                    {
                        return $this->discountPercent;
                    }
                }

                function findCustomer(array $customers, int $id): ?Customer
                {
                    return $customers[$id] ?? null;
                }

                function greeting(?Customer $customer): string
                {
                    return $customer === null ? 'Hello, guest!' : "Hello, {$customer->name()}!";
                }

                function priceFor(?Customer $customer, int $pence): int
                {
                    $discount = $customer === null ? 0 : $customer->discountPercent();

                    return $pence - intdiv($pence * $discount, 100);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Customer
                {
                    public function name(): string;

                    public function discountPercent(): int;
                }

                final class RegisteredCustomer implements Customer
                {
                    public function __construct(private string $name, private int $discountPercent) {}

                    public function name(): string
                    {
                        return $this->name;
                    }

                    public function discountPercent(): int
                    {
                        return $this->discountPercent;
                    }
                }

                final class UnknownCustomer implements Customer
                {
                    public function name(): string
                    {
                        return 'guest';
                    }

                    public function discountPercent(): int
                    {
                        return 0;
                    }
                }

                function findCustomer(array $customers, int $id): Customer
                {
                    return $customers[$id] ?? new UnknownCustomer();
                }

                function greeting(Customer $customer): string
                {
                    return "Hello, {$customer->name()}!";
                }

                function priceFor(Customer $customer, int $pence): int
                {
                    return $pence - intdiv($pence * $customer->discountPercent(), 100);
                }
                PHP,
            'hint' => 'The `null` branches show what a guest should answer: `\'guest\'` and `0`. Put those answers in `UnknownCustomer`, and return one with `?? new UnknownCustomer()`.',
            'checks' => [
                ['label' => 'findCustomer() never returns null', 'code' => <<<'PHP'
                    $type = (new ReflectionFunction('findCustomer'))->getReturnType();
                    if ($type === null || $type->allowsNull()) {
                        return 'findCustomer() should be declared to return Customer, not ?Customer.';
                    }
                    $found = findCustomer([], 42);
                    return $found instanceof UnknownCustomer ? true : 'A missing id should give an UnknownCustomer.';
                    PHP],
                ['label' => 'No null checks left', 'code' => "return preg_match('/null|\\?Customer/', learnp_code()) === 1 ? 'There is still a null check or a ?Customer type.' : true;"],
                ['label' => 'Same results for guests and members', 'code' => <<<'PHP'
                    $customers = [7 => new RegisteredCustomer('Amara', 10)];
                    $member = findCustomer($customers, 7);
                    $guest = findCustomer($customers, 8);
                    return expect_same([greeting($member), greeting($guest), priceFor($member, 2000), priceFor($guest, 2000)], ['Hello, Amara!', 'Hello, guest!', 1800, 2000]);
                    PHP],
            ],
        ],
        [
            'title' => 'Consolidate the conditions',
            'prompt' => 'The three checks at the top of `disabilityAmount()` all mean "not eligible". Extract them into `isNotEligibleForDisability(array $employee): bool` and use it in **one** guard clause, so `return 0` appears only once in your code.',
            'starter_code' => <<<'PHP'
                <?php

                function disabilityAmount(array $employee): int
                {
                    if ($employee['seniority'] < 2) {
                        return 0;
                    }
                    if ($employee['monthsDisabled'] > 12) {
                        return 0;
                    }
                    if ($employee['isPartTime']) {
                        return 0;
                    }

                    return intdiv($employee['salary'], 10);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isNotEligibleForDisability(array $employee): bool
                {
                    return $employee['seniority'] < 2
                        || $employee['monthsDisabled'] > 12
                        || $employee['isPartTime'];
                }

                function disabilityAmount(array $employee): int
                {
                    if (isNotEligibleForDisability($employee)) {
                        return 0;
                    }

                    return intdiv($employee['salary'], 10);
                }
                PHP,
            'hint' => 'Join the three conditions with `||` in the new function\'s `return`.',
            'checks' => [
                ['label' => 'isNotEligibleForDisability()', 'code' => <<<'PHP'
                    $employee = fn (int $seniority, int $months, bool $partTime) => ['seniority' => $seniority, 'monthsDisabled' => $months, 'isPartTime' => $partTime, 'salary' => 30000];
                    return expect_same(
                        [isNotEligibleForDisability($employee(1, 3, false)), isNotEligibleForDisability($employee(5, 13, false)), isNotEligibleForDisability($employee(5, 3, true)), isNotEligibleForDisability($employee(2, 12, false))],
                        [true, true, true, false],
                    );
                    PHP],
                ['label' => 'One guard clause', 'code' => "\$count = preg_match_all('/return\\s+0\\s*;/', learnp_code());\nreturn \$count === 1 ? true : \"return 0 appears \$count times; it should appear once.\";"],
                ['label' => 'Same amounts', 'code' => "return expect_same([disabilityAmount(['seniority' => 5, 'monthsDisabled' => 3, 'isPartTime' => false, 'salary' => 30000]), disabilityAmount(['seniority' => 1, 'monthsDisabled' => 3, 'isPartTime' => false, 'salary' => 30000])], [3000, 0]);"],
            ],
        ],
    ],
];
