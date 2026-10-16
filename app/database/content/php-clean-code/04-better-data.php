<?php

// Lesson 4 of the Clean code and refactoring course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Refactoring challenges start from working code: the checks make sure the behaviour is unchanged and
// that the structure asked for is there, reading the learner's code with Reflection and learnp_code().

return [
    'slug' => 'better-data',
    'title' => 'Better data',
    'summary' => 'Give data a proper shape: named constants instead of magic numbers, enums for fixed choices, value objects instead of bare strings, parameter objects for data clumps, and encapsulated collections.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        // What do 3 and 0.15 mean here? Give them names with constants.
        function lateFee(int $daysLate): float
        {
            if ($daysLate <= 3) {
                return 0.0;
            }

            return ($daysLate - 3) * 0.15;
        }

        echo lateFee(2) . ' ' . lateFee(10);
        PHP,

    'quiz' => [
        [
            'question' => 'What is a magic number?',
            'options' => [
                'A random number',
                'A literal whose meaning isn\'t obvious from the code, such as `86400` or `3000`',
                'A number stored in a constant',
                'A floating-point number',
            ],
            'correct_option' => 1,
            'explanation' => 'Naming it, as `SECONDS_PER_DAY`, explains it and keeps it in one place.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                enum Size: string
                {
                    case Small = 's';
                    case Large = 'l';
                }

                echo Size::from('l')->name . ' ' . (Size::tryFrom('m') === null ? 'none' : 'found');
                PHP,
            'options' => ['`Large none`', '`l none`', '`Large found`', 'An error for m'],
            'correct_option' => 0,
            'explanation' => '`from()` finds the case for a value; `tryFrom()` returns `null` instead of throwing for an unknown one.',
        ],
        [
            'question' => 'What is primitive obsession?',
            'options' => [
                'Using too many classes',
                'Using strings, ints or arrays for concepts that have rules, such as money, emails or postcodes',
                'Preferring old PHP functions',
                'Using integers instead of floats',
            ],
            'correct_option' => 1,
            'explanation' => 'Every function then has to check the value again, or trust that someone did.',
        ],
        [
            'question' => 'Which is true of a value object?',
            'options' => [
                'It has an id and changes over time',
                'It checks its value once when created, is immutable, and equals any other with the same value',
                'It must extend a base class',
                'It can only hold one string',
            ],
            'correct_option' => 1,
            'explanation' => 'A `Temperature` of 21°C is the same as any other 21°C.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                final class Address
                {
                    public function __construct(public readonly string $city) {}

                    public function withCity(string $city): self
                    {
                        return new self($city);
                    }
                }

                $home = new Address('Leeds');
                $home->withCity('York');
                echo $home->city;
                PHP,
            'options' => ['`Leeds`', '`York`', 'An error', 'Nothing'],
            'correct_option' => 0,
            'explanation' => '`withCity()` returns a new object, which is thrown away here. `$home` is unchanged.',
        ],
        [
            'question' => 'You keep passing `$lat` and `$lng` together to many functions. What is this, and what is the fix?',
            'options' => [
                'A data clump; group them into one object such as `Coordinates`',
                'Duplication; delete one of them',
                'Feature envy; move the functions',
                'Nothing to fix',
            ],
            'correct_option' => 0,
            'explanation' => 'Values that always travel together belong in one object.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                final class Shelf
                {
                    private array $titles = ['Dune'];

                    public function titles(): array
                    {
                        return $this->titles;
                    }
                }

                $shelf = new Shelf();
                $titles = $shelf->titles();
                $titles[] = 'Emma';
                echo count($titles) . ' ' . count($shelf->titles());
                PHP,
            'options' => ['`2 1`', '`2 2`', '`1 1`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Arrays are copied on assignment, so changing the returned copy doesn\'t touch the shelf.',
        ],
        [
            'question' => 'Why make a collection property private and add methods like `add()`?',
            'options' => [
                'Private properties are faster',
                'So every change goes through methods that can enforce rules, such as no duplicates or a maximum size',
                'PHP doesn\'t allow public arrays',
                'To hide bugs',
            ],
            'correct_option' => 1,
            'explanation' => 'A public array can be changed by any code, bypassing every rule.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Name the magic numbers',
            'prompt' => 'Define constants `VAT_RATE` (0.2), `FREE_DELIVERY_FROM_PENCE` (5000) and `LOAN_DAYS` (21), and use them so none of the three functions contains `1.2`, `5000` or `21`. The results must not change.',
            'starter_code' => <<<'PHP'
                <?php

                function priceWithVat(int $pence): int
                {
                    return (int) round($pence * 1.2);
                }

                function hasFreeDelivery(int $pence): bool
                {
                    return $pence >= 5000;
                }

                function dueDate(DateTimeImmutable $borrowed): DateTimeImmutable
                {
                    return $borrowed->modify('+21 days');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                const VAT_RATE = 0.2;
                const FREE_DELIVERY_FROM_PENCE = 5000;
                const LOAN_DAYS = 21;

                function priceWithVat(int $pence): int
                {
                    return (int) round($pence * (1 + VAT_RATE));
                }

                function hasFreeDelivery(int $pence): bool
                {
                    return $pence >= FREE_DELIVERY_FROM_PENCE;
                }

                function dueDate(DateTimeImmutable $borrowed): DateTimeImmutable
                {
                    return $borrowed->modify('+' . LOAN_DAYS . ' days');
                }
                PHP,
            'hint' => '`1.2` is `1 + VAT_RATE`. Build the date modifier with concatenation: `\'+\' . LOAN_DAYS . \' days\'`.',
            'checks' => [
                ['label' => 'The constants', 'code' => <<<'PHP'
                    foreach (['VAT_RATE' => 0.2, 'FREE_DELIVERY_FROM_PENCE' => 5000, 'LOAN_DAYS' => 21] as $name => $value) {
                        if (! defined($name) || constant($name) !== $value) {
                            return "Define $name as " . var_export($value, true) . '.';
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'No magic numbers in the functions', 'code' => <<<'PHP'
                    $lines = explode("\n", learnp_code());
                    foreach (['priceWithVat', 'hasFreeDelivery', 'dueDate'] as $name) {
                        $function = new ReflectionFunction($name);
                        $source = implode("\n", array_slice($lines, $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
                        if (preg_match('/\b(1\.2|5000|21)\b/', $source, $match) === 1) {
                            return "$name() still contains {$match[1]}.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'Same results', 'code' => "return expect_same([priceWithVat(999), priceWithVat(1000), hasFreeDelivery(4999), hasFreeDelivery(5000), dueDate(new DateTimeImmutable('2026-03-01'))->format('Y-m-d')], [1199, 1200, false, true, '2026-03-22']);"],
            ],
        ],
        [
            'title' => 'An enum for tiers',
            'prompt' => "Replace the tier strings with `enum Tier: string` (cases `Gold = 'gold'`, `Silver = 'silver'`, `Bronze = 'bronze'`) and a method `discountPercent(): int` (20, 10 and 5). Change `discountFor()` to take a `Tier`.",
            'starter_code' => <<<'PHP'
                <?php

                function discountFor(string $tier, int $pence): int
                {
                    if ($tier === 'gold') {
                        $percent = 20;
                    } elseif ($tier === 'silver') {
                        $percent = 10;
                    } elseif ($tier === 'bronze') {
                        $percent = 5;
                    } else {
                        throw new InvalidArgumentException("Unknown tier: $tier");
                    }

                    return intdiv($pence * $percent, 100);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum Tier: string
                {
                    case Gold = 'gold';
                    case Silver = 'silver';
                    case Bronze = 'bronze';

                    public function discountPercent(): int
                    {
                        return match ($this) {
                            self::Gold => 20,
                            self::Silver => 10,
                            self::Bronze => 5,
                        };
                    }
                }

                function discountFor(Tier $tier, int $pence): int
                {
                    return intdiv($pence * $tier->discountPercent(), 100);
                }
                PHP,
            'hint' => 'Inside the enum, `match ($this)` with an arm for each case gives the percentage. No unknown tier can reach `discountFor()` any more.',
            'checks' => [
                ['label' => 'The Tier enum', 'code' => <<<'PHP'
                    if (! enum_exists('Tier')) {
                        return 'There is no Tier enum yet.';
                    }
                    return expect_same(array_map(fn ($case) => [$case->name, $case->value, $case->discountPercent()], Tier::cases()), [['Gold', 'gold', 20], ['Silver', 'silver', 10], ['Bronze', 'bronze', 5]]);
                    PHP],
                ['label' => 'discountFor() takes a Tier', 'code' => "return expect_same((string) (new ReflectionFunction('discountFor'))->getParameters()[0]->getType(), 'Tier');"],
                ['label' => 'Same discounts', 'code' => "return expect_same([discountFor(Tier::Gold, 5000), discountFor(Tier::from('silver'), 999), discountFor(Tier::Bronze, 2000)], [1000, 99, 100]);"],
            ],
        ],
        [
            'title' => 'An Email value object',
            'prompt' => 'Write `final class Email`. Its constructor trims and lower-cases the address and throws `InvalidArgumentException` unless it matches `/^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$/`. Add `value(): string`, `domain(): string` (the part after `@`) and `equals(Email $other): bool`.',
            'starter_code' => <<<'PHP'
                <?php

                final class Email
                {
                    public function __construct(private string $address) {}

                    public function value(): string
                    {
                        return $this->address;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Email
                {
                    private string $address;

                    public function __construct(string $address)
                    {
                        $address = strtolower(trim($address));
                        if (preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $address) !== 1) {
                            throw new InvalidArgumentException("Not an email address: $address");
                        }
                        $this->address = $address;
                    }

                    public function value(): string
                    {
                        return $this->address;
                    }

                    public function domain(): string
                    {
                        return substr($this->address, strpos($this->address, '@') + 1);
                    }

                    public function equals(Email $other): bool
                    {
                        return $this->address === $other->address;
                    }
                }
                PHP,
            'hint' => 'Normalise first, then validate, then store. `strpos()` finds the `@`; `substr()` takes what follows.',
            'checks' => [
                ['label' => 'Normalised', 'code' => "return expect_same((new Email('  Amara@Example.COM '))->value(), 'amara@example.com');"],
                ['label' => 'Invalid addresses are rejected', 'code' => <<<'PHP'
                    foreach (['amara', 'amara@', '@example.com', 'a b@example.com', 'amara@example'] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => new Email($bad));
                        if ($result !== true) {
                            return "'$bad': $result";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'domain() and equals()', 'code' => "\$a = new Email('ben@books.example.org');\nreturn expect_same([\$a->domain(), \$a->equals(new Email('BEN@books.example.org')), \$a->equals(new Email('cy@books.example.org'))], ['books.example.org', true, false]);"],
            ],
        ],
        [
            'title' => 'A parameter object',
            'prompt' => '`bookingSummary()` takes five parameters, four of which describe the stay. Introduce `final class Stay` with a constructor `(DateTimeImmutable $checkIn, DateTimeImmutable $checkOut, int $adults, int $children = 0)` that throws `InvalidArgumentException` unless check-out is after check-in, and methods `nights(): int` and `guests(): int`. Then change `bookingSummary()` to `(string $guest, Stay $stay)`.',
            'starter_code' => <<<'PHP'
                <?php

                function bookingSummary(string $guest, string $checkIn, string $checkOut, int $adults, int $children): string
                {
                    $nights = (new DateTimeImmutable($checkIn))->diff(new DateTimeImmutable($checkOut))->days;

                    return "$guest: $nights nights, " . ($adults + $children) . ' guests';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Stay
                {
                    public function __construct(
                        private DateTimeImmutable $checkIn,
                        private DateTimeImmutable $checkOut,
                        private int $adults,
                        private int $children = 0,
                    ) {
                        if ($checkOut <= $checkIn) {
                            throw new InvalidArgumentException('Check-out must be after check-in');
                        }
                    }

                    public function nights(): int
                    {
                        return $this->checkIn->diff($this->checkOut)->days;
                    }

                    public function guests(): int
                    {
                        return $this->adults + $this->children;
                    }
                }

                function bookingSummary(string $guest, Stay $stay): string
                {
                    return "$guest: {$stay->nights()} nights, {$stay->guests()} guests";
                }
                PHP,
            'hint' => 'Promote the four constructor parameters to private properties and check the dates in the constructor body. `diff()->days` gives the nights.',
            'checks' => [
                ['label' => 'Stay', 'code' => "\$stay = new Stay(new DateTimeImmutable('2026-07-10'), new DateTimeImmutable('2026-07-14'), 2, 1);\nreturn expect_same([\$stay->nights(), \$stay->guests(), (new Stay(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-02'), 1))->guests()], [4, 3, 1]);"],
                ['label' => 'Impossible stays are rejected', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => new Stay(new DateTimeImmutable('2026-07-10'), new DateTimeImmutable('2026-07-10'), 2));"],
                ['label' => 'bookingSummary() takes a Stay', 'code' => <<<'PHP'
                    $parameters = (new ReflectionFunction('bookingSummary'))->getParameters();
                    if (count($parameters) !== 2 || (string) $parameters[1]->getType() !== 'Stay') {
                        return 'bookingSummary() should take (string $guest, Stay $stay).';
                    }
                    return expect_same(bookingSummary('Amara', new Stay(new DateTimeImmutable('2026-07-10'), new DateTimeImmutable('2026-07-14'), 2, 1)), 'Amara: 4 nights, 3 guests');
                    PHP],
            ],
        ],
        [
            'title' => 'A Money value object',
            'prompt' => "The starter passes amounts and currencies around as separate values. Write `final class Money` with readonly `int \$pence` and `string \$currency`, `add(Money \$other): Money` (a new object; throws `DomainException` for different currencies) and `format(): string`, e.g. `'GBP 12.50'`.",
            'starter_code' => <<<'PHP'
                <?php

                function addMoney(int $pence, string $currency, int $otherPence, string $otherCurrency): array
                {
                    if ($currency !== $otherCurrency) {
                        throw new DomainException('Currencies differ');
                    }

                    return [$pence + $otherPence, $currency];
                }

                function formatMoney(int $pence, string $currency): string
                {
                    return $currency . ' ' . number_format($pence / 100, 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Money
                {
                    public function __construct(public readonly int $pence, public readonly string $currency) {}

                    public function add(Money $other): Money
                    {
                        if ($other->currency !== $this->currency) {
                            throw new DomainException("Cannot add {$other->currency} to {$this->currency}");
                        }

                        return new Money($this->pence + $other->pence, $this->currency);
                    }

                    public function format(): string
                    {
                        return $this->currency . ' ' . number_format($this->pence / 100, 2);
                    }
                }
                PHP,
            'hint' => 'Use promoted `public readonly` properties. `add()` checks the currencies and returns `new Money(...)` rather than changing `$this`.',
            'checks' => [
                ['label' => 'Readonly properties', 'code' => <<<'PHP'
                    foreach (['pence', 'currency'] as $name) {
                        if (! property_exists('Money', $name) || ! (new ReflectionProperty('Money', $name))->isReadOnly()) {
                            return "Money needs a readonly \$$name property.";
                        }
                    }
                    return true;
                    PHP],
                ['label' => 'add() returns a new Money', 'code' => "\$a = new Money(1250, 'GBP');\n\$sum = \$a->add(new Money(799, 'GBP'));\nreturn expect_same([\$sum->pence, \$sum->currency, \$a->pence, \$sum !== \$a], [2049, 'GBP', 1250, true]);"],
                ['label' => 'Different currencies are rejected', 'code' => "return expect_throws(DomainException::class, fn () => (new Money(100, 'GBP'))->add(new Money(100, 'EUR')));"],
                ['label' => 'format()', 'code' => "return expect_same([(new Money(1250, 'GBP'))->format(), (new Money(123456, 'EUR'))->format()], ['GBP 12.50', 'EUR 1,234.56']);"],
            ],
        ],
        [
            'title' => 'Encapsulate the collection',
            'prompt' => 'Make `Playlist::$songs` private. Add `add(string $song): void` (throws `DomainException` for a song already there), `remove(string $song): void`, `songs(): array` (a list, in the order added) and make `Playlist` implement `Countable`.',
            'starter_code' => <<<'PHP'
                <?php

                final class Playlist
                {
                    public array $songs = [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Playlist implements Countable
                {
                    private array $songs = [];

                    public function add(string $song): void
                    {
                        if (in_array($song, $this->songs, true)) {
                            throw new DomainException("$song is already on the playlist");
                        }
                        $this->songs[] = $song;
                    }

                    public function remove(string $song): void
                    {
                        $this->songs = array_values(array_filter($this->songs, fn (string $s) => $s !== $song));
                    }

                    public function songs(): array
                    {
                        return $this->songs;
                    }

                    public function count(): int
                    {
                        return count($this->songs);
                    }
                }
                PHP,
            'hint' => 'Check for duplicates with `in_array(..., true)`. After removing with `array_filter()`, use `array_values()` so `songs()` stays a list.',
            'checks' => [
                ['label' => 'The songs are private', 'code' => "return (new ReflectionProperty('Playlist', 'songs'))->isPrivate() ? true : '\$songs should be private.';"],
                ['label' => 'Adding and removing', 'code' => "\$list = new Playlist();\nforeach (['Wonderwall', 'Yellow', 'Creep'] as \$song) {\n    \$list->add(\$song);\n}\n\$list->remove('Yellow');\nreturn expect_same([\$list->songs(), count(\$list)], [['Wonderwall', 'Creep'], 2]);"],
                ['label' => 'No duplicates', 'code' => "\$list = new Playlist();\n\$list->add('Yellow');\nreturn expect_throws(DomainException::class, fn () => \$list->add('Yellow'));"],
                ['label' => 'Changing the returned list changes nothing', 'code' => "\$list = new Playlist();\n\$list->add('Yellow');\n\$songs = \$list->songs();\n\$songs[] = 'Sneaked in';\nreturn expect_same(\$list->songs(), ['Yellow']);"],
            ],
        ],
    ],
];
