<?php

// Lesson 6 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'strategy-and-template-method',
    'title' => 'Strategy and Template Method',
    'summary' => 'Two ways to vary part of an algorithm: hand in interchangeable strategy objects (or closures), or let child classes fill in the steps of a template method.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try writing a new strategy.
        interface ShippingStrategy { public function cost(float $kg): float; }

        class StandardShipping implements ShippingStrategy
        {
            public function cost(float $kg): float { return 2.95; }
        }

        class ExpressShipping implements ShippingStrategy
        {
            public function cost(float $kg): float { return 4.95 + $kg * 0.5; }
        }

        foreach ([new StandardShipping(), new ExpressShipping()] as $strategy) {
            echo get_class($strategy) . ': £' . $strategy->cost(3.0) . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does the Strategy pattern do?',
            'options' => [
                'Puts each interchangeable way of doing a job in its own class behind a shared interface',
                'Makes a parent class define the order of steps',
                'Creates families of objects',
                'Remembers results',
            ],
            'correct_option' => 0,
            'explanation' => 'The code that needs the job done receives a strategy object and calls it, whichever one it is.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Greeting { public function greet(string $name): string; }

                class Formal implements Greeting { public function greet(string $name): string { return "Good day, $name."; } }
                class Casual implements Greeting { public function greet(string $name): string { return "Hi $name!"; } }

                class Shop
                {
                    public function __construct(private Greeting $greeting) {}
                    public function welcome(string $name): string { return $this->greeting->greet($name); }
                }

                echo (new Shop(new Casual()))->welcome('Amara');
                PHP,
            'options' => ['`Good day, Amara.`', '`Hi Amara!`', '`Amara`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`Shop` uses whichever strategy it was given; here, the casual one.',
        ],
        [
            'question' => 'How is a new shipping method added when the checkout uses the Strategy pattern?',
            'options' => [
                'By editing the checkout\'s `match`',
                'By writing a new class that implements the strategy interface',
                'By extending the checkout',
                'By adding a static method',
            ],
            'correct_option' => 1,
            'explanation' => 'The checkout is closed for modification but open for extension.',
        ],
        [
            'question' => 'When is a closure a good replacement for a strategy class?',
            'options' => [
                'Never',
                'When the strategy is a single small operation that needs no name or settings of its own',
                'When it has several methods',
                'Only in PHP 8.4',
            ],
            'correct_option' => 1,
            'explanation' => '`usort()`\'s comparison function is exactly this: a one-method strategy written as a closure.',
        ],
        [
            'question' => 'In the Template Method pattern, what does the template method do?',
            'options' => [
                'It\'s an abstract method each child writes in full',
                'It\'s a method in the parent that fixes the steps and their order, calling abstract steps the children fill in',
                'It creates objects',
                'It copies a template file',
            ],
            'correct_option' => 1,
            'explanation' => 'The outline is written once; only the varying steps are left to the children.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                abstract class Drink
                {
                    public function prepare(): string
                    {
                        return 'boil, ' . $this->brew() . ', pour' . $this->extras();
                    }

                    abstract protected function brew(): string;

                    protected function extras(): string { return ''; }
                }

                class Tea extends Drink
                {
                    protected function brew(): string { return 'steep'; }
                    protected function extras(): string { return ', add milk'; }
                }

                echo (new Tea())->prepare();
                PHP,
            'options' => ['`boil, steep, pour`', '`boil, steep, pour, add milk`', '`steep, add milk`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The parent fixes the order. `Tea` fills in `brew()` and overrides the optional `extras()` hook.',
        ],
        [
            'question' => 'What is a hook, in the Template Method pattern?',
            'options' => [
                'A step with a default implementation that children may override if they want',
                'An abstract step every child must write',
                'A static method',
                'An event listener',
            ],
            'correct_option' => 0,
            'explanation' => 'Abstract steps must be written; hooks have a sensible default.',
        ],
        [
            'question' => 'Which is a reason to prefer Strategy over Template Method?',
            'options' => [
                'Strategy needs fewer classes in every case',
                'The varying part can be chosen and swapped at run time, and combined freely, without inheritance',
                'Template Method doesn\'t work in PHP',
                'Strategy fixes the order of steps more strictly',
            ],
            'correct_option' => 1,
            'explanation' => 'Composition is more flexible. Template Method shines when the outline must never change.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Pick a tax rule',
            'prompt' => 'Write the interface `TaxRule` with `tax(float $price): float`, and three strategies: `StandardRate` (20%), `ReducedRate` (5%) and `ZeroRate` (0%), each rounded to 2 places. Then write `Till` taking a `TaxRule` in its constructor, with `total(float $price): float` returning the price plus its tax.',
            'starter_code' => <<<'PHP'
                <?php

                // Write TaxRule, the three rates and Till here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface TaxRule
                {
                    public function tax(float $price): float;
                }

                class StandardRate implements TaxRule
                {
                    public function tax(float $price): float { return round($price * 0.2, 2); }
                }

                class ReducedRate implements TaxRule
                {
                    public function tax(float $price): float { return round($price * 0.05, 2); }
                }

                class ZeroRate implements TaxRule
                {
                    public function tax(float $price): float { return 0.0; }
                }

                class Till
                {
                    public function __construct(private TaxRule $rule) {}

                    public function total(float $price): float
                    {
                        return round($price + $this->rule->tax($price), 2);
                    }
                }
                PHP,
            'hint' => 'One interface, three small classes, and a `Till` that calls `$this->rule->tax($price)`.',
            'checks' => [
                ['label' => 'The three rates', 'code' => 'return expect_same([(new StandardRate())->tax(10.0), (new ReducedRate())->tax(10.0), (new ZeroRate())->tax(10.0)], [2.0, 0.5, 0.0]);'],
                ['label' => 'Till uses its rule', 'code' => 'return expect_same([(new Till(new StandardRate()))->total(20.0), (new Till(new ZeroRate()))->total(12.5)], [24.0, 12.5]);'],
                ['label' => 'They implement TaxRule', 'code' => "return (interface_exists('TaxRule', false) && new StandardRate() instanceof TaxRule && new ReducedRate() instanceof TaxRule && new ZeroRate() instanceof TaxRule) ?: 'All three rates should implement TaxRule.';"],
            ],
        ],
        [
            'title' => 'Strategies from a name',
            'prompt' => "Customers choose `'standard'`, `'express'` or `'collect'`. Write `ShippingSelector` with `for(string \$name): ShippingStrategy`, returning the matching strategy and throwing an `InvalidArgumentException` for unknown names. Then write `quote(string \$name, float \$kg): string`, returning text like `'express: £5.95'`.",
            'starter_code' => <<<'PHP'
                <?php

                interface ShippingStrategy { public function cost(float $kg): float; }

                class StandardShipping implements ShippingStrategy { public function cost(float $kg): float { return 2.95; } }
                class ExpressShipping implements ShippingStrategy { public function cost(float $kg): float { return 4.95 + $kg * 0.5; } }
                class CollectInStore implements ShippingStrategy { public function cost(float $kg): float { return 0.0; } }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ShippingStrategy { public function cost(float $kg): float; }

                class StandardShipping implements ShippingStrategy { public function cost(float $kg): float { return 2.95; } }
                class ExpressShipping implements ShippingStrategy { public function cost(float $kg): float { return 4.95 + $kg * 0.5; } }
                class CollectInStore implements ShippingStrategy { public function cost(float $kg): float { return 0.0; } }

                class ShippingSelector
                {
                    public function for(string $name): ShippingStrategy
                    {
                        return match ($name) {
                            'standard' => new StandardShipping(),
                            'express' => new ExpressShipping(),
                            'collect' => new CollectInStore(),
                            default => throw new InvalidArgumentException("Unknown shipping method: $name"),
                        };
                    }

                    public function quote(string $name, float $kg): string
                    {
                        return sprintf('%s: £%.2f', $name, $this->for($name)->cost($kg));
                    }
                }
                PHP,
            'hint' => 'A `match` in `for()`, and `sprintf(\'%s: £%.2f\', …)` in `quote()`.',
            'checks' => [
                ['label' => 'Names give the right strategies', 'code' => "\$s = new ShippingSelector();\nreturn (\$s->for('express') instanceof ExpressShipping && \$s->for('collect') instanceof CollectInStore) ?: 'for() should return the matching strategy.';"],
                ['label' => 'Quotes', 'code' => "\$s = new ShippingSelector();\nreturn expect_same([\$s->quote('express', 2.0), \$s->quote('standard', 9.0), \$s->quote('collect', 1.0)], ['express: £5.95', 'standard: £2.95', 'collect: £0.00']);"],
                ['label' => 'Unknown names throw', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => (new ShippingSelector())->for('drone'));"],
            ],
        ],
        [
            'title' => 'Sort with any strategy',
            'prompt' => 'Write `BookSorter` with `sort(array $books, Closure $compare): array`, returning the books sorted with the given comparison (the original array is untouched). Then set `$byPriceThenTitle` to a closure that orders books by price and then title, and `$longestFirst` to one that orders by pages, most first.',
            'starter_code' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Emma', 'price' => 7.99, 'pages' => 474],
                    ['title' => 'Dune', 'price' => 9.99, 'pages' => 412],
                    ['title' => 'Circe', 'price' => 7.99, 'pages' => 393],
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                class BookSorter
                {
                    public function sort(array $books, Closure $compare): array
                    {
                        usort($books, $compare);

                        return $books;
                    }
                }

                $books = [
                    ['title' => 'Emma', 'price' => 7.99, 'pages' => 474],
                    ['title' => 'Dune', 'price' => 9.99, 'pages' => 412],
                    ['title' => 'Circe', 'price' => 7.99, 'pages' => 393],
                ];

                $byPriceThenTitle = fn (array $a, array $b) => [$a['price'], $a['title']] <=> [$b['price'], $b['title']];
                $longestFirst = fn (array $a, array $b) => $b['pages'] <=> $a['pages'];
                PHP,
            'hint' => 'Arrays are copied when passed in, so `usort($books, $compare)` inside `sort()` leaves the caller\'s array alone. The closures compare with `<=>`.',
            'checks' => [
                ['label' => 'By price, then title', 'code' => "return expect_same(array_column((new BookSorter())->sort(\$GLOBALS['books'], \$GLOBALS['byPriceThenTitle']), 'title'), ['Circe', 'Emma', 'Dune']);"],
                ['label' => 'Longest first', 'code' => "return expect_same(array_column((new BookSorter())->sort(\$GLOBALS['books'], \$GLOBALS['longestFirst']), 'title'), ['Emma', 'Dune', 'Circe']);"],
                ['label' => 'The original order is kept', 'code' => "(new BookSorter())->sort(\$GLOBALS['books'], \$GLOBALS['longestFirst']);\nreturn expect_same(array_column(\$GLOBALS['books'], 'title'), ['Emma', 'Dune', 'Circe']);"],
            ],
        ],
        [
            'title' => 'A data import template',
            'prompt' => "Write an abstract class `Importer` whose template method `import(string \$raw): array` splits the text into lines, skips empty ones, turns each line into an array with the abstract `parseLine(string \$line): array`, and then passes the list to `filter(array \$rows): array`, a hook that keeps everything by default. Then write `CsvImporter` (fields separated by `,`) and `TabImporter` (fields separated by tabs, and its `filter()` drops rows whose first field is `'#'`).",
            'starter_code' => <<<'PHP'
                <?php

                // Write Importer, CsvImporter and TabImporter here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Importer
                {
                    public function import(string $raw): array
                    {
                        $rows = [];
                        foreach (explode("\n", $raw) as $line) {
                            if (trim($line) !== '') {
                                $rows[] = $this->parseLine($line);
                            }
                        }

                        return $this->filter($rows);
                    }

                    abstract protected function parseLine(string $line): array;

                    protected function filter(array $rows): array
                    {
                        return $rows;
                    }
                }

                class CsvImporter extends Importer
                {
                    protected function parseLine(string $line): array
                    {
                        return explode(',', $line);
                    }
                }

                class TabImporter extends Importer
                {
                    protected function parseLine(string $line): array
                    {
                        return explode("\t", $line);
                    }

                    protected function filter(array $rows): array
                    {
                        return array_values(array_filter($rows, fn (array $row) => $row[0] !== '#'));
                    }
                }
                PHP,
            'hint' => '`import()` lives in the abstract parent and calls `$this->parseLine()` and `$this->filter()`. Only `TabImporter` overrides `filter()`.',
            'checks' => [
                ['label' => 'Importer is abstract, with an abstract parseLine()', 'code' => "return ((new ReflectionClass('Importer'))->isAbstract() && (new ReflectionMethod('Importer', 'parseLine'))->isAbstract()) ?: 'Make Importer and its parseLine() abstract.';"],
                ['label' => 'CSV lines', 'code' => "return expect_same((new CsvImporter())->import(\"Dune,19.99\\n\\nEmma,7.99\\n\"), [['Dune', '19.99'], ['Emma', '7.99']]);"],
                ['label' => 'Tab lines, with comments filtered out', 'code' => "return expect_same((new TabImporter())->import(\"#\\tcomment\\nCirce\\t13.99\"), [['Circe', '13.99']]);"],
                ['label' => 'Only import() is public', 'code' => "return ((new ReflectionMethod('Importer', 'parseLine'))->isProtected() && (new ReflectionMethod('Importer', 'filter'))->isProtected()) ?: 'Make parseLine() and filter() protected steps.';"],
            ],
        ],
        [
            'title' => 'Discounts in order',
            'prompt' => 'Write `PriceCalculator`, whose constructor takes any number of `Discount` strategies (use a variadic parameter), with `final(float $price): float` applying them in order and rounding to 2 places at the end. Write `PercentOff(int)` and `AmountOff(float)` (never below 0) too.',
            'starter_code' => <<<'PHP'
                <?php

                interface Discount
                {
                    public function apply(float $price): float;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Discount
                {
                    public function apply(float $price): float;
                }

                class PercentOff implements Discount
                {
                    public function __construct(private int $percent) {}
                    public function apply(float $price): float { return $price * (100 - $this->percent) / 100; }
                }

                class AmountOff implements Discount
                {
                    public function __construct(private float $amount) {}
                    public function apply(float $price): float { return max(0.0, $price - $this->amount); }
                }

                class PriceCalculator
                {
                    private array $discounts;

                    public function __construct(Discount ...$discounts)
                    {
                        $this->discounts = $discounts;
                    }

                    public function final(float $price): float
                    {
                        foreach ($this->discounts as $discount) {
                            $price = $discount->apply($price);
                        }

                        return round($price, 2);
                    }
                }
                PHP,
            'hint' => '`public function __construct(Discount ...$discounts)` collects them into an array. Apply each in turn, then round.',
            'checks' => [
                ['label' => 'Discounts apply in order', 'code' => 'return expect_same([(new PriceCalculator(new PercentOff(10), new AmountOff(5.0)))->final(20.0), (new PriceCalculator(new AmountOff(5.0), new PercentOff(10)))->final(20.0)], [13.0, 13.5]);'],
                ['label' => 'No discounts, and never below 0', 'code' => 'return expect_same([(new PriceCalculator())->final(9.999), (new PriceCalculator(new AmountOff(50.0)))->final(20.0)], [10.0, 0.0]);'],
            ],
        ],
        [
            'title' => 'Greetings for every hour',
            'prompt' => "Replace the `match` in `greeting()` with strategies: write the interface `GreetingStyle` with `greet(string \$name): string`, and `MorningStyle` (`'Good morning, NAME'`), `EveningStyle` (`'Good evening, NAME'`) and `NightStyle` (`'Shh, NAME, the shop is closed'`). Then write `styleFor(int \$hour): GreetingStyle`: morning before 12, evening from 12 to 21, night from 22.",
            'starter_code' => <<<'PHP'
                <?php

                function greeting(int $hour, string $name): string
                {
                    return match (true) {
                        $hour < 12 => "Good morning, $name",
                        $hour < 22 => "Good evening, $name",
                        default => "Shh, $name, the shop is closed",
                    };
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface GreetingStyle
                {
                    public function greet(string $name): string;
                }

                class MorningStyle implements GreetingStyle
                {
                    public function greet(string $name): string { return "Good morning, $name"; }
                }

                class EveningStyle implements GreetingStyle
                {
                    public function greet(string $name): string { return "Good evening, $name"; }
                }

                class NightStyle implements GreetingStyle
                {
                    public function greet(string $name): string { return "Shh, $name, the shop is closed"; }
                }

                function styleFor(int $hour): GreetingStyle
                {
                    return match (true) {
                        $hour < 12 => new MorningStyle(),
                        $hour < 22 => new EveningStyle(),
                        default => new NightStyle(),
                    };
                }
                PHP,
            'hint' => 'Each style is a tiny class. `styleFor()` keeps the `match`, but returns a strategy instead of text.',
            'checks' => [
                ['label' => 'Each style greets', 'code' => "return expect_same([(new MorningStyle())->greet('Amara'), (new NightStyle())->greet('Ben')], ['Good morning, Amara', 'Shh, Ben, the shop is closed']);"],
                ['label' => 'The right style for each hour', 'code' => "return expect_same(array_map(fn (\$h) => styleFor(\$h)->greet('Cy'), [9, 12, 21, 22]), ['Good morning, Cy', 'Good evening, Cy', 'Good evening, Cy', 'Shh, Cy, the shop is closed']);"],
                ['label' => 'styleFor() returns a GreetingStyle', 'code' => "return (string) (new ReflectionFunction('styleFor'))->getReturnType() === 'GreetingStyle' ?: 'Declare styleFor(): GreetingStyle.';"],
            ],
        ],
    ],
];
