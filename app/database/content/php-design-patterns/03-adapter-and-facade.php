<?php

// Lesson 3 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'adapter-and-facade',
    'title' => 'Adapter and Facade',
    'summary' => 'Make a class fit an interface it wasn\'t written for with an adapter, and hide a complicated set of classes behind a simple facade.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        interface PaymentGateway
        {
            public function charge(float $pounds): bool;
        }

        class OtherCompanyClient
        {
            public function pay(int $pence): string { return $pence > 0 ? 'OK' : 'NO'; }
        }

        class OtherCompanyAdapter implements PaymentGateway
        {
            public function __construct(private OtherCompanyClient $client) {}

            public function charge(float $pounds): bool
            {
                return $this->client->pay((int) round($pounds * 100)) === 'OK';
            }
        }

        var_dump((new OtherCompanyAdapter(new OtherCompanyClient()))->charge(9.99));
        PHP,

    'quiz' => [
        [
            'question' => 'What does an adapter do?',
            'options' => [
                'Makes a class fit an interface it wasn\'t written for, by translating calls',
                'Creates objects of the right class',
                'Makes sure only one object exists',
                'Notifies other objects of changes',
            ],
            'correct_option' => 0,
            'explanation' => 'Like a travel plug: your code keeps using its interface, and the adapter converts each call for the other class.',
        ],
        [
            'question' => 'You need a library class whose methods don\'t match your interface, and you can\'t edit the library. What do you write?',
            'options' => [
                'A child class of your interface',
                'An adapter class that implements your interface and wraps the library object',
                'A singleton',
                'A copy of the library with renamed methods',
            ],
            'correct_option' => 1,
            'explanation' => 'The adapter is your class, so it can implement your interface and call the library inside.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Thermometer { public function celsius(): float; }

                class AmericanSensor { public function readFahrenheit(): float { return 212.0; } }

                class SensorAdapter implements Thermometer
                {
                    public function __construct(private AmericanSensor $sensor) {}
                    public function celsius(): float { return ($this->sensor->readFahrenheit() - 32) * 5 / 9; }
                }

                echo (new SensorAdapter(new AmericanSensor()))->celsius();
                PHP,
            'options' => ['`212`', '`100`', '`32`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The adapter converts the sensor\'s Fahrenheit reading into the Celsius that `Thermometer` promises.',
        ],
        [
            'question' => 'What does a facade do?',
            'options' => [
                'Converts one interface into another existing one',
                'Offers a simple interface over a group of classes, coordinating them behind the scenes',
                'Wraps an object to add behaviour',
                'Creates families of related objects',
            ],
            'correct_option' => 1,
            'explanation' => 'Everyday code calls `placeOrder()`; the facade deals with stock, payment, shipping and email.',
        ],
        [
            'question' => 'After adding a facade, can code still use the classes behind it directly?',
            'options' => [
                'No, the facade hides them completely',
                'Yes; the facade is a convenience, not a wall',
                'Only if they\'re static',
                'Only from inside the facade',
            ],
            'correct_option' => 1,
            'explanation' => 'Special cases can still reach the parts. The facade just makes the common case simple.',
        ],
        [
            'question' => 'What is the main difference between an adapter and a facade?',
            'options' => [
                'There is none',
                'An adapter makes one object fit an existing interface; a facade simplifies a group of objects with a new interface',
                'A facade only works with static methods',
                'An adapter wraps many objects; a facade wraps one',
            ],
            'correct_option' => 1,
            'explanation' => 'Adapters translate; facades simplify.',
        ],
        [
            'question' => 'An adapter converts pounds into pence for a library. Where should that conversion live?',
            'options' => [
                'In every caller',
                'Inside the adapter',
                'In the library',
                'In a global function',
            ],
            'correct_option' => 1,
            'explanation' => 'The point is that callers speak your interface\'s language; all translation stays in one place.',
        ],
        [
            'question' => 'Why is it useful that the checkout depends on `PaymentGateway` rather than on a library class?',
            'options' => [
                'It runs faster',
                'Switching payment company means writing a new adapter; the checkout doesn\'t change',
                'Libraries can\'t be used directly',
                'PHP requires interfaces for payments',
            ],
            'correct_option' => 1,
            'explanation' => 'Your code is protected from the details, and changes, of other people\'s code.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Adapt the old mailer',
            'prompt' => "The shop's code uses the `Mailer` interface. Write `OldMailerAdapter`, implementing `Mailer` by wrapping an `OldMailSystem`. `send()` must pass the arguments in the order the old system wants, and return `true` when the old system returns `1`.",
            'starter_code' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $subject, string $body): bool;
                }

                // Old code: don't change it.
                class OldMailSystem
                {
                    public array $sent = [];

                    public function deliver(string $subject, string $body, string $recipient): int
                    {
                        $this->sent[] = "$recipient|$subject|$body";
                        return str_contains($recipient, '@') ? 1 : 0;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Mailer
                {
                    public function send(string $to, string $subject, string $body): bool;
                }

                class OldMailSystem
                {
                    public array $sent = [];

                    public function deliver(string $subject, string $body, string $recipient): int
                    {
                        $this->sent[] = "$recipient|$subject|$body";
                        return str_contains($recipient, '@') ? 1 : 0;
                    }
                }

                class OldMailerAdapter implements Mailer
                {
                    public function __construct(private OldMailSystem $system) {}

                    public function send(string $to, string $subject, string $body): bool
                    {
                        return $this->system->deliver($subject, $body, $to) === 1;
                    }
                }
                PHP,
            'hint' => 'Take an `OldMailSystem` in the constructor. In `send()`, call `deliver($subject, $body, $to)` and compare the result with `1`.',
            'checks' => [
                ['label' => 'OldMailerAdapter implements Mailer', 'code' => "return (new OldMailerAdapter(new OldMailSystem()) instanceof Mailer) ?: 'OldMailerAdapter should implement Mailer.';"],
                ['label' => 'Arguments arrive in the old order', 'code' => <<<'PHP'
                    $system = new OldMailSystem();
                    (new OldMailerAdapter($system))->send('amara@example.com', 'Shipped', 'On its way');
                    return expect_same($system->sent, ['amara@example.com|Shipped|On its way']);
                    PHP],
                ['label' => 'The result becomes a bool', 'code' => <<<'PHP'
                    $adapter = new OldMailerAdapter(new OldMailSystem());
                    return expect_same([$adapter->send('a@example.com', 's', 'b'), $adapter->send('nobody', 's', 'b')], [true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Readings in Celsius',
            'prompt' => 'The warehouse monitor works with `TemperatureSensor` (Celsius). Write `KelvinSensorAdapter`, wrapping a `KelvinProbe` so that `celsius()` returns its reading converted to Celsius (subtract 273.15), rounded to 1 decimal place.',
            'starter_code' => <<<'PHP'
                <?php

                interface TemperatureSensor
                {
                    public function celsius(): float;
                }

                class KelvinProbe
                {
                    public function __construct(private float $kelvin) {}

                    public function measure(): float
                    {
                        return $this->kelvin;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface TemperatureSensor
                {
                    public function celsius(): float;
                }

                class KelvinProbe
                {
                    public function __construct(private float $kelvin) {}

                    public function measure(): float
                    {
                        return $this->kelvin;
                    }
                }

                class KelvinSensorAdapter implements TemperatureSensor
                {
                    public function __construct(private KelvinProbe $probe) {}

                    public function celsius(): float
                    {
                        return round($this->probe->measure() - 273.15, 1);
                    }
                }
                PHP,
            'hint' => 'Wrap the probe, and in `celsius()` return `round($this->probe->measure() - 273.15, 1)`.',
            'checks' => [
                ['label' => 'It implements TemperatureSensor', 'code' => "return (new KelvinSensorAdapter(new KelvinProbe(0)) instanceof TemperatureSensor) ?: 'Implement TemperatureSensor.';"],
                ['label' => 'Readings are converted', 'code' => 'return expect_same([(new KelvinSensorAdapter(new KelvinProbe(293.15)))->celsius(), (new KelvinSensorAdapter(new KelvinProbe(273.15)))->celsius(), (new KelvinSensorAdapter(new KelvinProbe(255.37)))->celsius()], [20.0, 0.0, -17.8]);'],
            ],
        ],
        [
            'title' => 'Books from an old array',
            'prompt' => "New code asks a `Catalogue` for books as `['title' => …, 'price' => …]` arrays (price as a float). The old system returns rows like `['TITLE' => 'DUNE', 'PRICE_PENCE' => 1999]`. Write `LegacyCatalogueAdapter`, implementing `Catalogue` by wrapping `LegacyStore`, converting each row: titles in normal case (`ucwords(strtolower(…))`) and prices in pounds.",
            'starter_code' => <<<'PHP'
                <?php

                interface Catalogue
                {
                    public function books(): array;
                }

                class LegacyStore
                {
                    public function fetchAll(): array
                    {
                        return [
                            ['TITLE' => 'DUNE', 'PRICE_PENCE' => 1999],
                            ['TITLE' => 'THE HOBBIT', 'PRICE_PENCE' => 1499],
                        ];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Catalogue
                {
                    public function books(): array;
                }

                class LegacyStore
                {
                    public function fetchAll(): array
                    {
                        return [
                            ['TITLE' => 'DUNE', 'PRICE_PENCE' => 1999],
                            ['TITLE' => 'THE HOBBIT', 'PRICE_PENCE' => 1499],
                        ];
                    }
                }

                class LegacyCatalogueAdapter implements Catalogue
                {
                    public function __construct(private LegacyStore $store) {}

                    public function books(): array
                    {
                        return array_map(fn (array $row) => [
                            'title' => ucwords(strtolower($row['TITLE'])),
                            'price' => $row['PRICE_PENCE'] / 100,
                        ], $this->store->fetchAll());
                    }
                }
                PHP,
            'hint' => '`array_map()` over `fetchAll()`, building each new row from the old keys.',
            'checks' => [
                ['label' => 'Rows are converted', 'code' => "return expect_same((new LegacyCatalogueAdapter(new LegacyStore()))->books(), [['title' => 'Dune', 'price' => 19.99], ['title' => 'The Hobbit', 'price' => 14.99]]);"],
                ['label' => 'It implements Catalogue', 'code' => "return (new LegacyCatalogueAdapter(new LegacyStore()) instanceof Catalogue) ?: 'Implement Catalogue.';"],
            ],
        ],
        [
            'title' => 'A returns facade',
            'prompt' => "Returning a book means: check the order with `Orders::find()`, refund with `Refunds::issue()`, put the book back with `Inventory::restock()`, and log it with `AuditLog::write()`. Write `ReturnsFacade` with `returnBook(string \$orderId): string`, which does all four and returns `'Refunded £X.XX'`, or returns `'Unknown order'` (doing nothing else) when the order isn't found.",
            'starter_code' => <<<'PHP'
                <?php

                class Orders
                {
                    public function find(string $id): ?array
                    {
                        return ['A-1' => ['title' => 'Dune', 'paid' => 19.99]][$id] ?? null;
                    }
                }

                class Refunds
                {
                    public array $issued = [];
                    public function issue(float $amount): void { $this->issued[] = $amount; }
                }

                class Inventory
                {
                    public array $restocked = [];
                    public function restock(string $title): void { $this->restocked[] = $title; }
                }

                class AuditLog
                {
                    public array $lines = [];
                    public function write(string $line): void { $this->lines[] = $line; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Orders
                {
                    public function find(string $id): ?array
                    {
                        return ['A-1' => ['title' => 'Dune', 'paid' => 19.99]][$id] ?? null;
                    }
                }

                class Refunds
                {
                    public array $issued = [];
                    public function issue(float $amount): void { $this->issued[] = $amount; }
                }

                class Inventory
                {
                    public array $restocked = [];
                    public function restock(string $title): void { $this->restocked[] = $title; }
                }

                class AuditLog
                {
                    public array $lines = [];
                    public function write(string $line): void { $this->lines[] = $line; }
                }

                class ReturnsFacade
                {
                    public function __construct(
                        private Orders $orders,
                        private Refunds $refunds,
                        private Inventory $inventory,
                        private AuditLog $log,
                    ) {}

                    public function returnBook(string $orderId): string
                    {
                        $order = $this->orders->find($orderId);
                        if ($order === null) {
                            return 'Unknown order';
                        }

                        $this->refunds->issue($order['paid']);
                        $this->inventory->restock($order['title']);
                        $this->log->write("Returned $orderId");

                        return sprintf('Refunded £%.2f', $order['paid']);
                    }
                }
                PHP,
            'hint' => 'Take the four objects in the constructor (in that order). In `returnBook()`, return early when `find()` gives `null`; otherwise call the other three and build the message with `sprintf()`.',
            'checks' => [
                ['label' => 'A return does all four steps', 'code' => <<<'PHP'
                    $refunds = new Refunds();
                    $inventory = new Inventory();
                    $log = new AuditLog();
                    $result = (new ReturnsFacade(new Orders(), $refunds, $inventory, $log))->returnBook('A-1');
                    return expect_same([$result, $refunds->issued, $inventory->restocked, count($log->lines)], ['Refunded £19.99', [19.99], ['Dune'], 1]);
                    PHP],
                ['label' => 'An unknown order changes nothing', 'code' => <<<'PHP'
                    $refunds = new Refunds();
                    $inventory = new Inventory();
                    $log = new AuditLog();
                    $result = (new ReturnsFacade(new Orders(), $refunds, $inventory, $log))->returnBook('Z-9');
                    return expect_same([$result, $refunds->issued, $inventory->restocked, $log->lines], ['Unknown order', [], [], []]);
                    PHP],
            ],
        ],
        [
            'title' => 'One interface, two sources',
            'prompt' => "Prices come from two places with different shapes. Write `JsonPriceSource` and `CsvPriceSource`, both implementing `PriceSource`, so that `priceOf('Dune')` returns the price as a float, or `null` if the title isn't there. The first wraps a JSON string like `'{\"Dune\": 19.99}'`, the second CSV lines like `\"Dune,19.99\\nEmma,7.99\"`.",
            'starter_code' => <<<'PHP'
                <?php

                interface PriceSource
                {
                    public function priceOf(string $title): ?float;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface PriceSource
                {
                    public function priceOf(string $title): ?float;
                }

                class JsonPriceSource implements PriceSource
                {
                    private array $prices;

                    public function __construct(string $json)
                    {
                        $this->prices = json_decode($json, true);
                    }

                    public function priceOf(string $title): ?float
                    {
                        return isset($this->prices[$title]) ? (float) $this->prices[$title] : null;
                    }
                }

                class CsvPriceSource implements PriceSource
                {
                    private array $prices = [];

                    public function __construct(string $csv)
                    {
                        foreach (explode("\n", trim($csv)) as $line) {
                            [$title, $price] = explode(',', $line);
                            $this->prices[$title] = (float) $price;
                        }
                    }

                    public function priceOf(string $title): ?float
                    {
                        return $this->prices[$title] ?? null;
                    }
                }
                PHP,
            'hint' => 'Each class converts its data into a `title => price` array in its constructor (`json_decode($json, true)`, or `explode()` the lines and fields), then `priceOf()` looks the title up.',
            'checks' => [
                ['label' => 'Both implement PriceSource', 'code' => "return (new JsonPriceSource('{}') instanceof PriceSource && new CsvPriceSource('a,1') instanceof PriceSource) ?: 'Both classes should implement PriceSource.';"],
                ['label' => 'Prices from JSON', 'code' => "\$source = new JsonPriceSource('{\"Dune\": 19.99, \"Emma\": 8}');\nreturn expect_same([\$source->priceOf('Dune'), \$source->priceOf('Emma'), \$source->priceOf('Circe')], [19.99, 8.0, null]);"],
                ['label' => 'Prices from CSV', 'code' => "\$source = new CsvPriceSource(\"Dune,19.99\\nEmma,7.99\");\nreturn expect_same([\$source->priceOf('Emma'), \$source->priceOf('Circe')], [7.99, null]);"],
            ],
        ],
        [
            'title' => 'A newsletter facade',
            'prompt' => "Write `NewsletterFacade` with `subscribe(string \$email): string`. It tidies the email with `EmailCleaner`, checks it with `EmailValidator` (returning `'Invalid email'` if it fails), adds it with `SubscriberList::add()` (returning `'Already subscribed'` if that gives `false`), and sends a welcome with `Mailer::send()`, returning `'Subscribed'`.",
            'starter_code' => <<<'PHP'
                <?php

                class EmailCleaner
                {
                    public function clean(string $email): string { return strtolower(trim($email)); }
                }

                class EmailValidator
                {
                    public function isValid(string $email): bool { return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[a-z]+$/', $email); }
                }

                class SubscriberList
                {
                    public array $emails = [];

                    public function add(string $email): bool
                    {
                        if (in_array($email, $this->emails, true)) {
                            return false;
                        }
                        $this->emails[] = $email;
                        return true;
                    }
                }

                class Mailer
                {
                    public array $sent = [];
                    public function send(string $to, string $text): void { $this->sent[] = "$to: $text"; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class EmailCleaner
                {
                    public function clean(string $email): string { return strtolower(trim($email)); }
                }

                class EmailValidator
                {
                    public function isValid(string $email): bool { return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[a-z]+$/', $email); }
                }

                class SubscriberList
                {
                    public array $emails = [];

                    public function add(string $email): bool
                    {
                        if (in_array($email, $this->emails, true)) {
                            return false;
                        }
                        $this->emails[] = $email;
                        return true;
                    }
                }

                class Mailer
                {
                    public array $sent = [];
                    public function send(string $to, string $text): void { $this->sent[] = "$to: $text"; }
                }

                class NewsletterFacade
                {
                    public function __construct(
                        private EmailCleaner $cleaner,
                        private EmailValidator $validator,
                        private SubscriberList $list,
                        private Mailer $mailer,
                    ) {}

                    public function subscribe(string $email): string
                    {
                        $email = $this->cleaner->clean($email);

                        if (! $this->validator->isValid($email)) {
                            return 'Invalid email';
                        }
                        if (! $this->list->add($email)) {
                            return 'Already subscribed';
                        }

                        $this->mailer->send($email, 'Welcome to the bookshop newsletter!');

                        return 'Subscribed';
                    }
                }
                PHP,
            'hint' => 'Take the four parts in the constructor, in the order listed, and call them in turn, returning early for an invalid or duplicate email.',
            'checks' => [
                ['label' => 'A new subscriber is cleaned, added and welcomed', 'code' => <<<'PHP'
                    $list = new SubscriberList();
                    $mailer = new Mailer();
                    $facade = new NewsletterFacade(new EmailCleaner(), new EmailValidator(), $list, $mailer);
                    return expect_same([$facade->subscribe('  Amara@Example.com '), $list->emails, count($mailer->sent)], ['Subscribed', ['amara@example.com'], 1]);
                    PHP],
                ['label' => 'Duplicates and invalid emails', 'code' => <<<'PHP'
                    $mailer = new Mailer();
                    $facade = new NewsletterFacade(new EmailCleaner(), new EmailValidator(), new SubscriberList(), $mailer);
                    $facade->subscribe('ben@example.com');
                    return expect_same([$facade->subscribe('BEN@example.com'), $facade->subscribe('not an email'), count($mailer->sent)], ['Already subscribed', 'Invalid email', 1]);
                    PHP],
            ],
        ],
    ],
];
