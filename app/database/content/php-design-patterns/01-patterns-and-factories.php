<?php

// Lesson 1 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'patterns-and-factories',
    'title' => 'Patterns and factories',
    'summary' => 'What design patterns are and how they\'re grouped, then the creational patterns that decide which object to build: simple factories, static factory methods, Factory Method and Abstract Factory.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a 'push' channel.
        interface Notifier { public function send(string $message): string; }

        class EmailNotifier implements Notifier
        {
            public function send(string $message): string { return "Email: $message"; }
        }

        class SmsNotifier implements Notifier
        {
            public function send(string $message): string { return "SMS: $message"; }
        }

        class NotifierFactory
        {
            public function create(string $channel): Notifier
            {
                return match ($channel) {
                    'email' => new EmailNotifier(),
                    'sms' => new SmsNotifier(),
                    default => throw new InvalidArgumentException("Unknown channel: $channel"),
                };
            }
        }

        echo (new NotifierFactory())->create('sms')->send('Your order has shipped');
        PHP,

    'quiz' => [
        [
            'question' => 'What is a design pattern?',
            'options' => [
                'A library you install with Composer',
                'A named, well-tried shape for solving a common design problem',
                'A PHP keyword',
                'A rule that every class must follow',
            ],
            'correct_option' => 1,
            'explanation' => 'Patterns are ideas for structuring your own classes, with names that make designs easy to discuss.',
        ],
        [
            'question' => 'Which family do Factory, Builder and Singleton belong to?',
            'options' => ['Structural', 'Behavioural', 'Creational', 'Functional'],
            'correct_option' => 2,
            'explanation' => 'Creational patterns are about how objects get created.',
        ],
        [
            'question' => 'What is the main benefit of a simple factory?',
            'options' => [
                'Objects are created faster',
                'The decision about which class to create lives in one place, and callers depend only on an interface',
                'It removes the need for interfaces',
                'It makes every object a singleton',
            ],
            'correct_option' => 1,
            'explanation' => 'Adding or changing a kind of object means editing the factory, not every place that uses one.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Temperature
                {
                    private function __construct(public readonly float $celsius) {}

                    public static function fromCelsius(float $c): self { return new self($c); }
                    public static function fromFahrenheit(float $f): self { return new self(round(($f - 32) * 5 / 9, 1)); }
                }

                echo Temperature::fromFahrenheit(212)->celsius;
                PHP,
            'options' => ['`212`', '`100`', '`180`', 'An error: the constructor is private'],
            'correct_option' => 1,
            'explanation' => 'Static factory methods run inside the class, so they may call the private constructor. (212 − 32) × 5 ÷ 9 = 100.',
        ],
        [
            'question' => 'Why give a class a `private` constructor and public static factory methods?',
            'options' => [
                'To make it impossible to create',
                'To force creation through methods whose names say what the argument means',
                'To make it a singleton',
                'Because constructors can\'t take arguments',
            ],
            'correct_option' => 1,
            'explanation' => '`Money::fromPounds(12.5)` can\'t be confused with `Money::fromPence(1250)`, but `new Money(12.5)` could mean either.',
        ],
        [
            'question' => 'In the Factory Method pattern, who decides which concrete class is created?',
            'options' => [
                'The caller, with `new`',
                'A child class, by implementing the parent\'s creation method',
                'A global configuration file',
                'PHP, automatically',
            ],
            'correct_option' => 1,
            'explanation' => 'The parent calls an abstract method such as `makeReport()`; each child returns its own kind of object.',
        ],
        [
            'question' => 'When is an Abstract Factory the right choice?',
            'options' => [
                'When you create a single object of one class',
                'When several related objects must come from the same family, such as one theme\'s button and label',
                'When you need exactly one instance of a class',
                'When objects must be copied',
            ],
            'correct_option' => 1,
            'explanation' => 'Each factory creates a whole matching family, so mixing parts from different families can\'t happen.',
        ],
        [
            'question' => 'What is the cost of using a pattern where it isn\'t needed?',
            'options' => [
                'None; patterns are always an improvement',
                'More classes and indirection that make simple code harder to follow',
                'PHP runs it more slowly',
                'It breaks type checking',
            ],
            'correct_option' => 1,
            'explanation' => 'Use a pattern when it solves a problem you actually have.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A shape factory',
            'prompt' => "Write a class `ShapeFactory` with a method `create(string \$type, float \$size): Shape` that returns a `Square` for `'square'` and a `Circle` for `'circle'` (the size is the side or the radius), and throws an `InvalidArgumentException` for anything else.",
            'starter_code' => <<<'PHP'
                <?php

                interface Shape
                {
                    public function area(): float;
                }

                class Square implements Shape
                {
                    public function __construct(private float $side) {}
                    public function area(): float { return round($this->side ** 2, 2); }
                }

                class Circle implements Shape
                {
                    public function __construct(private float $radius) {}
                    public function area(): float { return round(M_PI * $this->radius ** 2, 2); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Shape
                {
                    public function area(): float;
                }

                class Square implements Shape
                {
                    public function __construct(private float $side) {}
                    public function area(): float { return round($this->side ** 2, 2); }
                }

                class Circle implements Shape
                {
                    public function __construct(private float $radius) {}
                    public function area(): float { return round(M_PI * $this->radius ** 2, 2); }
                }

                class ShapeFactory
                {
                    public function create(string $type, float $size): Shape
                    {
                        return match ($type) {
                            'square' => new Square($size),
                            'circle' => new Circle($size),
                            default => throw new InvalidArgumentException("Unknown shape: $type"),
                        };
                    }
                }
                PHP,
            'hint' => 'A `match` on `$type`, returning `new Square($size)` or `new Circle($size)`, with `default => throw new InvalidArgumentException(…)`.',
            'checks' => [
                ['label' => 'Squares and circles', 'code' => <<<'PHP'
                    $factory = new ShapeFactory();
                    $square = $factory->create('square', 3);
                    $circle = $factory->create('circle', 1);
                    if (! $square instanceof Square || ! $circle instanceof Circle) return 'create() should return a Square and a Circle.';
                    return expect_same([$square->area(), $circle->area()], [9.0, 3.14]);
                    PHP],
                ['label' => 'Unknown shapes throw', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => (new ShapeFactory())->create('hexagon', 2));"],
            ],
        ],
        [
            'title' => 'Named constructors for a duration',
            'prompt' => 'Give `Duration` a **private** constructor taking a number of seconds, and three static factory methods: `fromSeconds(int)`, `fromMinutes(int)` and `fromHours(int)`. Its public readonly `$seconds` holds the total.',
            'starter_code' => <<<'PHP'
                <?php

                class Duration
                {
                    public function __construct(public readonly int $seconds) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Duration
                {
                    private function __construct(public readonly int $seconds) {}

                    public static function fromSeconds(int $seconds): self
                    {
                        return new self($seconds);
                    }

                    public static function fromMinutes(int $minutes): self
                    {
                        return new self($minutes * 60);
                    }

                    public static function fromHours(int $hours): self
                    {
                        return new self($hours * 3600);
                    }
                }
                PHP,
            'hint' => 'Make the constructor `private`, and have each static method return `new self(…)` with the value converted to seconds.',
            'checks' => [
                ['label' => 'The three factory methods', 'code' => 'return expect_same([Duration::fromSeconds(90)->seconds, Duration::fromMinutes(2)->seconds, Duration::fromHours(1)->seconds], [90, 120, 3600]);'],
                ['label' => 'The constructor is private', 'code' => "return (new ReflectionMethod('Duration', '__construct'))->isPrivate() ?: 'Make the constructor private.';"],
            ],
        ],
        [
            'title' => 'Factory method for documents',
            'prompt' => '`Exporter::export(array $lines)` calls `$this->makeFormatter()` to turn lines into text. Make `Exporter` abstract with an abstract `makeFormatter(): Formatter`, and write two children: `MarkdownExporter` (using `MarkdownFormatter`) and `HtmlExporter` (using `HtmlFormatter`).',
            'starter_code' => <<<'PHP'
                <?php

                interface Formatter
                {
                    public function format(array $lines): string;
                }

                class MarkdownFormatter implements Formatter
                {
                    public function format(array $lines): string
                    {
                        return implode("\n", array_map(fn ($line) => "- $line", $lines));
                    }
                }

                class HtmlFormatter implements Formatter
                {
                    public function format(array $lines): string
                    {
                        return '<ul>' . implode('', array_map(fn ($line) => "<li>$line</li>", $lines)) . '</ul>';
                    }
                }

                class Exporter
                {
                    public function export(array $lines): string
                    {
                        return (new MarkdownFormatter())->format($lines);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Formatter
                {
                    public function format(array $lines): string;
                }

                class MarkdownFormatter implements Formatter
                {
                    public function format(array $lines): string
                    {
                        return implode("\n", array_map(fn ($line) => "- $line", $lines));
                    }
                }

                class HtmlFormatter implements Formatter
                {
                    public function format(array $lines): string
                    {
                        return '<ul>' . implode('', array_map(fn ($line) => "<li>$line</li>", $lines)) . '</ul>';
                    }
                }

                abstract class Exporter
                {
                    abstract protected function makeFormatter(): Formatter;

                    public function export(array $lines): string
                    {
                        return $this->makeFormatter()->format($lines);
                    }
                }

                class MarkdownExporter extends Exporter
                {
                    protected function makeFormatter(): Formatter
                    {
                        return new MarkdownFormatter();
                    }
                }

                class HtmlExporter extends Exporter
                {
                    protected function makeFormatter(): Formatter
                    {
                        return new HtmlFormatter();
                    }
                }
                PHP,
            'hint' => 'Declare `abstract protected function makeFormatter(): Formatter;` in `Exporter`, use it in `export()`, and implement it in each child.',
            'checks' => [
                ['label' => 'Exporter is abstract with an abstract makeFormatter()', 'code' => <<<'PHP'
                    if (! (new ReflectionClass('Exporter'))->isAbstract()) return 'Make Exporter abstract.';
                    return (new ReflectionMethod('Exporter', 'makeFormatter'))->isAbstract() ?: 'Declare makeFormatter() as abstract.';
                    PHP],
                ['label' => 'MarkdownExporter makes a Markdown list', 'code' => "return expect_same((new MarkdownExporter())->export(['Dune', 'Emma']), \"- Dune\\n- Emma\");"],
                ['label' => 'HtmlExporter makes an HTML list', 'code' => "return expect_same((new HtmlExporter())->export(['Dune']), '<ul><li>Dune</li></ul>');"],
            ],
        ],
        [
            'title' => 'A matching family',
            'prompt' => "Write an abstract factory interface `ShippingFactory` with `label(string \$address): string` and `price(float \$weightKg): float`, and two implementations: `UkShipping` (label `'UK: '` + address, price 2.95 + 1.00 per kg) and `EuShipping` (label `'EU: '` + address, price 6.50 + 2.00 per kg). Prices are rounded to 2 places.",
            'starter_code' => <<<'PHP'
                <?php

                // Write ShippingFactory, UkShipping and EuShipping here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface ShippingFactory
                {
                    public function label(string $address): string;
                    public function price(float $weightKg): float;
                }

                class UkShipping implements ShippingFactory
                {
                    public function label(string $address): string { return "UK: $address"; }
                    public function price(float $weightKg): float { return round(2.95 + $weightKg * 1.00, 2); }
                }

                class EuShipping implements ShippingFactory
                {
                    public function label(string $address): string { return "EU: $address"; }
                    public function price(float $weightKg): float { return round(6.50 + $weightKg * 2.00, 2); }
                }
                PHP,
            'hint' => 'One interface with the two methods, and two classes that implement it, each with its own label prefix and prices.',
            'checks' => [
                ['label' => 'Both implement ShippingFactory', 'code' => "return (interface_exists('ShippingFactory', false) && new UkShipping() instanceof ShippingFactory && new EuShipping() instanceof ShippingFactory) ?: 'Write the ShippingFactory interface and implement it in both classes.';"],
                ['label' => 'UK labels and prices', 'code' => "\$uk = new UkShipping();\nreturn expect_same([\$uk->label('1 High St'), \$uk->price(1.5)], ['UK: 1 High St', 4.45]);"],
                ['label' => 'EU labels and prices', 'code' => "\$eu = new EuShipping();\nreturn expect_same([\$eu->label('Rue 2'), \$eu->price(1.5)], ['EU: Rue 2', 9.5]);"],
            ],
        ],
        [
            'title' => 'A registry of makers',
            'prompt' => "Write a class `ParserRegistry` with `register(string \$extension, Closure \$maker): void` and `for(string \$filename): Parser`, which picks the parser by the file's extension (case-insensitive) and creates it by calling the maker. An unknown extension throws an `InvalidArgumentException`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Parser
                {
                    public function parse(string $text): array;
                }

                class CsvParser implements Parser
                {
                    public function parse(string $text): array { return explode(',', $text); }
                }

                class LinesParser implements Parser
                {
                    public function parse(string $text): array { return explode("\n", $text); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Parser
                {
                    public function parse(string $text): array;
                }

                class CsvParser implements Parser
                {
                    public function parse(string $text): array { return explode(',', $text); }
                }

                class LinesParser implements Parser
                {
                    public function parse(string $text): array { return explode("\n", $text); }
                }

                class ParserRegistry
                {
                    private array $makers = [];

                    public function register(string $extension, Closure $maker): void
                    {
                        $this->makers[strtolower($extension)] = $maker;
                    }

                    public function for(string $filename): Parser
                    {
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $maker = $this->makers[$extension] ?? throw new InvalidArgumentException("No parser for .$extension files.");

                        return $maker();
                    }
                }
                PHP,
            'hint' => 'Store makers by lower-case extension. `pathinfo($filename, PATHINFO_EXTENSION)` gives the extension; call the stored closure to create the parser.',
            'checks' => [
                ['label' => 'Parsers are picked by extension', 'code' => <<<'PHP'
                    $registry = new ParserRegistry();
                    $registry->register('csv', fn () => new CsvParser());
                    $registry->register('txt', fn () => new LinesParser());
                    return expect_same([$registry->for('stock.csv')->parse('a,b'), $registry->for('notes.txt')->parse("x\ny")], [['a', 'b'], ['x', 'y']]);
                    PHP],
                ['label' => 'Extensions ignore case', 'code' => <<<'PHP'
                    $registry = new ParserRegistry();
                    $registry->register('CSV', fn () => new CsvParser());
                    return ($registry->for('REPORT.Csv') instanceof CsvParser) ?: 'REPORT.Csv should get the CSV parser.';
                    PHP],
                ['label' => 'Each call makes a new parser', 'code' => <<<'PHP'
                    $registry = new ParserRegistry();
                    $registry->register('csv', fn () => new CsvParser());
                    return ($registry->for('a.csv') !== $registry->for('b.csv')) ?: 'Call the maker each time.';
                    PHP],
                ['label' => 'Unknown extensions throw', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => (new ParserRegistry())->for('photo.jpg'));"],
            ],
        ],
        [
            'title' => 'Prices in pence',
            'prompt' => "Give `Price` a private constructor taking pence, and the static factory methods `fromPence(int)`, `fromPounds(float)` (rounded to the nearest penny) and `parse(string)`, which reads text like `'£12.50'` or `'12.5'` and throws an `InvalidArgumentException` for anything else. `format()` returns text like `'£12.50'`.",
            'starter_code' => <<<'PHP'
                <?php

                class Price
                {
                    public function __construct(public readonly int $pence) {}

                    public function format(): string
                    {
                        return '£' . number_format($this->pence / 100, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Price
                {
                    private function __construct(public readonly int $pence) {}

                    public static function fromPence(int $pence): self
                    {
                        return new self($pence);
                    }

                    public static function fromPounds(float $pounds): self
                    {
                        return new self((int) round($pounds * 100));
                    }

                    public static function parse(string $text): self
                    {
                        $number = ltrim(trim($text), '£');
                        if (! is_numeric($number)) {
                            throw new InvalidArgumentException("'$text' is not a price.");
                        }

                        return self::fromPounds((float) $number);
                    }

                    public function format(): string
                    {
                        return '£' . number_format($this->pence / 100, 2);
                    }
                }
                PHP,
            'hint' => '`fromPounds()` multiplies by 100 and rounds. `parse()` trims, removes a leading `£`, checks `is_numeric()`, and hands over to `fromPounds()`.',
            'checks' => [
                ['label' => 'fromPence() and fromPounds()', 'code' => 'return expect_same([Price::fromPence(1250)->pence, Price::fromPounds(12.5)->pence, Price::fromPounds(0.1 + 0.2)->pence], [1250, 1250, 30]);'],
                ['label' => 'parse() reads prices', 'code' => "return expect_same([Price::parse('£12.50')->format(), Price::parse(' 7 ')->format()], ['£12.50', '£7.00']);"],
                ['label' => 'parse() refuses nonsense', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => Price::parse('free'));"],
                ['label' => 'The constructor is private', 'code' => "return (new ReflectionMethod('Price', '__construct'))->isPrivate() ?: 'Make the constructor private.';"],
            ],
        ],
    ],
];
