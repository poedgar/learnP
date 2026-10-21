<?php

// Lesson 3 of the Laravel testing course. The theory is in the .md file with the same name.

return [
    'slug' => 'fakes',
    'title' => 'Fakes for mail, queues, events and HTTP',
    'summary' => 'Swapping services for fakes that record: Mail, Queue, Event, Notification, Storage and Http fakes, assertions with closures, URL patterns and stray requests, and faking only at the edges.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Record another mail and filter for it.
        $sent = [];
        $send = function (string $mailable, array $data) use (&$sent) {
            $sent[] = compact('mailable', 'data');
        };

        $send('OrderPlaced', ['to' => 'ada@example.com']);

        $orderMails = array_filter($sent, fn ($mail) => $mail['mailable'] === 'OrderPlaced');
        echo count($orderMails) . ' OrderPlaced mail(s)';
        PHP,

    'quiz' => [
        [
            'question' => 'Why fake the mailer in a test of placing an order?',
            'options' => [
                'Real mail is not allowed in PHP',
                'So nothing is really sent, but the test can still check that the right mail would be',
                'To make the order cheaper',
                'Because Mail only works in production',
            ],
            'correct_option' => 1,
            'explanation' => 'Fakes record what they\'re asked to do and give you assertions about it.',
        ],
        [
            'question' => 'When must you call `Mail::fake()`?',
            'options' => [
                'After the action, before asserting',
                'Before the code that sends mail runs',
                'In the mailable class',
                'Only once, in AppServiceProvider',
            ],
            'correct_option' => 1,
            'explanation' => 'Mail sent before the swap went to the real mailer and wasn\'t recorded.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $pushed = [
                    ['job' => 'ChargeCard', 'queue' => 'payments'],
                    ['job' => 'SendInvoice', 'queue' => 'default'],
                    ['job' => 'ChargeCard', 'queue' => 'default'],
                ];

                $onPayments = array_filter($pushed, fn ($p) => $p['job'] === 'ChargeCard' && $p['queue'] === 'payments');

                echo count($onPayments);
                PHP,
            'options' => ['`0`', '`1`', '`2`', '`3`'],
            'correct_option' => 1,
            'explanation' => 'That\'s `Queue::assertPushedOn(\'payments\', ChargeCard::class)`: the job and the queue must both match.',
        ],
        [
            'question' => 'After `Queue::fake()`, what happens to a dispatched job?',
            'options' => [
                'It runs immediately',
                'It is recorded but never run',
                'It runs at the end of the test',
                'It throws',
            ],
            'correct_option' => 1,
            'explanation' => 'Test the job\'s `handle()` in its own test.',
        ],
        [
            'question' => 'What is the risk of `Event::fake()` with no list of events?',
            'options' => [
                'None',
                'It fakes every event, including Eloquent model events, so observers your code relies on stop running',
                'It sends events to production',
                'It makes the test slower',
            ],
            'correct_option' => 1,
            'explanation' => 'Pass the events you mean: `Event::fake([OrderShipped::class])`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $pattern = 'api.test/users/*';
                $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';

                echo preg_match($regex, 'api.test/users/7') . preg_match($regex, 'api.test/orders/7') . preg_match($regex, 'apiXtest/users/7');
                PHP,
            'options' => ['`111`', '`100`', '`101`', '`110`'],
            'correct_option' => 1,
            'explanation' => '`preg_quote()` makes the dot a literal dot, so only `*` matches anything.',
        ],
        [
            'question' => 'What does `Http::preventStrayRequests()` do?',
            'options' => [
                'Blocks requests to your own app',
                'Makes any HTTP request without a matching fake throw, instead of going to the network',
                'Retries failed requests',
                'Hides requests from logs',
            ],
            'correct_option' => 1,
            'explanation' => 'A forgotten fake then fails loudly.',
        ],
        [
            'question' => 'Which of these should a feature test normally run for real, not fake?',
            'options' => ['Sending email', 'A payment provider\'s API', 'Your own models and database', 'Uploading to cloud storage'],
            'correct_option' => 2,
            'explanation' => 'Fake at the edges of your app; let your own code run so the test checks it works together.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A mail fake',
            'prompt' => 'Write a `MailFake` class. `send(string $mailable, array $data)` records a mail. `sent(string $mailable, ?callable $filter = null): array` returns the recorded data arrays for that mailable, keeping only those the filter (given the data) returns true for. `assertSent(string $mailable, ?callable $filter = null)` throws a `RuntimeException` with the message `The expected [MAILABLE] mailable was not sent.` if nothing matches.',
            'starter_code' => <<<'PHP'
                <?php

                class MailFake
                {
                    private array $mails = [];

                    public function send(string $mailable, array $data): void
                    {
                        $this->mails[] = $mailable;
                    }

                    public function assertSent(string $mailable, ?callable $filter = null): void
                    {
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class MailFake
                {
                    private array $mails = [];

                    public function send(string $mailable, array $data): void
                    {
                        $this->mails[] = [$mailable, $data];
                    }

                    public function sent(string $mailable, ?callable $filter = null): array
                    {
                        $found = [];
                        foreach ($this->mails as [$name, $data]) {
                            if ($name === $mailable && ($filter === null || $filter($data))) {
                                $found[] = $data;
                            }
                        }

                        return $found;
                    }

                    public function assertSent(string $mailable, ?callable $filter = null): void
                    {
                        if ($this->sent($mailable, $filter) === []) {
                            throw new RuntimeException("The expected [{$mailable}] mailable was not sent.");
                        }
                    }
                }
                PHP,
            'hint' => 'Record `[$mailable, $data]` pairs. `assertSent()` can use `sent()`.',
            'checks' => [
                ['label' => 'Finds sent mail', 'code' => <<<'PHP'
                    $mail = new MailFake();
                    $mail->send('OrderPlaced', ['to' => 'ada@example.com', 'order' => 7]);
                    $mail->send('OrderPlaced', ['to' => 'bob@example.com', 'order' => 8]);
                    $mail->send('Newsletter', ['to' => 'ada@example.com']);
                    return expect_same([count($mail->sent('OrderPlaced')), $mail->sent('OrderPlaced', fn ($d) => $d['order'] === 8), $mail->sent('Refund')], [2, [['to' => 'bob@example.com', 'order' => 8]], []]);
                    PHP],
                ['label' => 'assertSent passes and fails', 'code' => <<<'PHP'
                    $mail = new MailFake();
                    $mail->send('OrderPlaced', ['order' => 7]);
                    $mail->assertSent('OrderPlaced');
                    $mail->assertSent('OrderPlaced', fn ($d) => $d['order'] === 7);
                    try {
                        $mail->assertSent('OrderPlaced', fn ($d) => $d['order'] === 9);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'The expected [OrderPlaced] mailable was not sent.');
                    }
                    return 'assertSent() passed although no mail matched the filter.';
                    PHP],
            ],
        ],
        [
            'title' => 'How many times',
            'prompt' => 'Write `assertSentTimes(array $sent, string $mailable, int $times): void` and `assertNothingSent(array $sent): void`. `$sent` is a list of `[\'mailable\' => ..., \'data\' => [...]]` records. The first throws a `RuntimeException` with `The expected [MAILABLE] mailable was sent N times instead of T times.` when the count differs; the second throws `Mail was sent unexpectedly.` if the list isn\'t empty.',
            'starter_code' => <<<'PHP'
                <?php

                function assertSentTimes(array $sent, string $mailable, int $times): void
                {
                    if (count($sent) !== $times) {
                        throw new RuntimeException('Wrong count');
                    }
                }

                function assertNothingSent(array $sent): void
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertSentTimes(array $sent, string $mailable, int $times): void
                {
                    $count = count(array_filter($sent, fn ($mail) => $mail['mailable'] === $mailable));

                    if ($count !== $times) {
                        throw new RuntimeException("The expected [{$mailable}] mailable was sent {$count} times instead of {$times} times.");
                    }
                }

                function assertNothingSent(array $sent): void
                {
                    if ($sent !== []) {
                        throw new RuntimeException('Mail was sent unexpectedly.');
                    }
                }
                PHP,
            'hint' => 'Count only the records for that mailable.',
            'checks' => [
                ['label' => 'Counts one mailable', 'code' => <<<'PHP'
                    $sent = [['mailable' => 'OrderPlaced', 'data' => []], ['mailable' => 'Newsletter', 'data' => []], ['mailable' => 'OrderPlaced', 'data' => []]];
                    assertSentTimes($sent, 'OrderPlaced', 2);
                    assertSentTimes($sent, 'Refund', 0);
                    try {
                        assertSentTimes($sent, 'Newsletter', 2);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'The expected [Newsletter] mailable was sent 1 times instead of 2 times.');
                    }
                    return 'Nothing was thrown for the wrong count.';
                    PHP],
                ['label' => 'Nothing sent', 'code' => <<<'PHP'
                    assertNothingSent([]);
                    return expect_throws(RuntimeException::class, fn () => assertNothingSent([['mailable' => 'Newsletter', 'data' => []]]));
                    PHP],
            ],
        ],
        [
            'title' => 'URL patterns',
            'prompt' => 'Write `urlMatches(string $pattern, string $url): bool`, where `*` in the pattern matches any characters (including none) and everything else must match exactly, over the whole URL.',
            'starter_code' => <<<'PHP'
                <?php

                function urlMatches(string $pattern, string $url): bool
                {
                    return str_starts_with($url, rtrim($pattern, '*'));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function urlMatches(string $pattern, string $url): bool
                {
                    $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#s';

                    return preg_match($regex, $url) === 1;
                }
                PHP,
            'hint' => 'Quote the pattern with `preg_quote()`, turn each quoted `\*` into `.*`, and anchor it with `^` and `$`.',
            'checks' => [
                ['label' => 'Stars match anything', 'code' => "return expect_same([urlMatches('api.test/rates*', 'api.test/rates?to=SW1'), urlMatches('api.test/*/rates', 'api.test/v2/rates'), urlMatches('*', 'anything'), urlMatches('api.test/rates*', 'api.test/rates')], [true, true, true, true]);"],
                ['label' => 'Everything else is literal', 'code' => "return expect_same([urlMatches('api.test/rates', 'apiXtest/rates'), urlMatches('api.test/rates', 'api.test/rates/1'), urlMatches('api.test/*/rates', 'api.test/v2/labels'), urlMatches('(x)?', 'x')], [false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'Canned responses',
            'prompt' => 'Write an `HttpFake` class built from an array of URL pattern => response (patterns use `*` as in the last challenge). `get(string $url): array` records the URL and returns the response of the **first** matching pattern; if none matches, it throws a `RuntimeException` with `Attempted request to [URL] without a matching fake.` `recorded(): array` returns the URLs requested so far.',
            'starter_code' => <<<'PHP'
                <?php

                class HttpFake
                {
                    public function __construct(private array $fakes) {}

                    public function get(string $url): array
                    {
                        return $this->fakes[$url];
                    }

                    public function recorded(): array
                    {
                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class HttpFake
                {
                    private array $recorded = [];

                    public function __construct(private array $fakes) {}

                    public function get(string $url): array
                    {
                        $this->recorded[] = $url;

                        foreach ($this->fakes as $pattern => $response) {
                            $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#s';
                            if (preg_match($regex, $url) === 1) {
                                return $response;
                            }
                        }

                        throw new RuntimeException("Attempted request to [{$url}] without a matching fake.");
                    }

                    public function recorded(): array
                    {
                        return $this->recorded;
                    }
                }
                PHP,
            'hint' => 'Loop over the fakes in order and return the first match. Throw after the loop.',
            'checks' => [
                ['label' => 'The first matching pattern wins', 'code' => <<<'PHP'
                    $http = new HttpFake([
                        'ship.test/rates*' => ['status' => 200, 'json' => ['price' => 4.99]],
                        'ship.test/*' => ['status' => 503, 'json' => ['error' => 'Down']],
                    ]);
                    return expect_same([$http->get('ship.test/rates?to=SW1')['json'], $http->get('ship.test/labels')['status']], [['price' => 4.99], 503]);
                    PHP],
                ['label' => 'Stray requests throw', 'code' => <<<'PHP'
                    $http = new HttpFake(['ship.test/*' => ['status' => 200]]);
                    try {
                        $http->get('pay.test/charge');
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'Attempted request to [pay.test/charge] without a matching fake.');
                    }
                    return 'A request without a fake should throw.';
                    PHP],
                ['label' => 'Requests are recorded', 'code' => <<<'PHP'
                    $http = new HttpFake(['*' => ['status' => 200]]);
                    $http->get('a.test/1');
                    $http->get('b.test/2');
                    return expect_same($http->recorded(), ['a.test/1', 'b.test/2']);
                    PHP],
            ],
        ],
        [
            'title' => 'Only some events',
            'prompt' => 'Write an `EventFake` class built from `(array $listeners, array $fake = [])`, where `$listeners` maps an event class to a list of callables. `dispatch(object $event)` records the event if it is faked, and otherwise calls its listeners with it. An empty `$fake` list fakes every event. `dispatched(string $class): int` returns how many recorded events are of that class.',
            'starter_code' => <<<'PHP'
                <?php

                class EventFake
                {
                    private array $recorded = [];

                    public function __construct(private array $listeners, private array $fake = []) {}

                    public function dispatch(object $event): void
                    {
                        $this->recorded[] = $event;
                    }

                    public function dispatched(string $class): int
                    {
                        return count($this->recorded);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class EventFake
                {
                    private array $recorded = [];

                    public function __construct(private array $listeners, private array $fake = []) {}

                    public function dispatch(object $event): void
                    {
                        if ($this->fake === [] || in_array($event::class, $this->fake, true)) {
                            $this->recorded[] = $event;

                            return;
                        }

                        foreach ($this->listeners[$event::class] ?? [] as $listener) {
                            $listener($event);
                        }
                    }

                    public function dispatched(string $class): int
                    {
                        return count(array_filter($this->recorded, fn ($event) => $event instanceof $class));
                    }
                }
                PHP,
            'hint' => 'Use `$event::class` to look the event up both in `$fake` and in `$listeners`.',
            'checks' => [
                ['label' => 'Faked events are recorded, not handled', 'code' => <<<'PHP'
                    $handled = [];
                    $events = new EventFake(['OrderShippedEvent' => [function ($e) use (&$handled) { $handled[] = 'shipped'; }]], ['OrderShippedEvent']);
                    if (! class_exists('OrderShippedEvent')) { eval('class OrderShippedEvent {} class BookSavedEvent {}'); }
                    $events->dispatch(new OrderShippedEvent());
                    $events->dispatch(new OrderShippedEvent());
                    return expect_same([$events->dispatched('OrderShippedEvent'), $handled], [2, []]);
                    PHP],
                ['label' => 'Other events still reach their listeners', 'code' => <<<'PHP'
                    if (! class_exists('OrderShippedEvent')) { eval('class OrderShippedEvent {} class BookSavedEvent {}'); }
                    $handled = [];
                    $events = new EventFake(['BookSavedEvent' => [function ($e) use (&$handled) { $handled[] = 'observer ran'; }]], ['OrderShippedEvent']);
                    $events->dispatch(new BookSavedEvent());
                    return expect_same([$handled, $events->dispatched('BookSavedEvent')], [['observer ran'], 0]);
                    PHP],
                ['label' => 'No list fakes everything', 'code' => <<<'PHP'
                    if (! class_exists('OrderShippedEvent')) { eval('class OrderShippedEvent {} class BookSavedEvent {}'); }
                    $handled = [];
                    $events = new EventFake(['BookSavedEvent' => [function ($e) use (&$handled) { $handled[] = 'ran'; }]]);
                    $events->dispatch(new BookSavedEvent());
                    return expect_same([$handled, $events->dispatched('BookSavedEvent')], [[], 1]);
                    PHP],
            ],
        ],
        [
            'title' => 'A queue fake',
            'prompt' => 'Write a `QueueFake` class. `push(string $job, string $queue = \'default\')` records a job. `assertPushed(string $job, ?int $times = null)` throws a `RuntimeException` if the job was never pushed, or, when `$times` is given, if it was pushed a different number of times. `assertPushedOn(string $queue, string $job)` throws a `RuntimeException` unless that job was pushed on that queue.',
            'starter_code' => <<<'PHP'
                <?php

                class QueueFake
                {
                    private array $jobs = [];

                    public function push(string $job, string $queue = 'default'): void
                    {
                        $this->jobs[] = $job;
                    }

                    public function assertPushed(string $job, ?int $times = null): void
                    {
                        if (! in_array($job, $this->jobs, true)) {
                            throw new RuntimeException("[{$job}] was not pushed.");
                        }
                    }

                    public function assertPushedOn(string $queue, string $job): void
                    {
                        $this->assertPushed($job);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class QueueFake
                {
                    private array $jobs = [];

                    public function push(string $job, string $queue = 'default'): void
                    {
                        $this->jobs[] = ['job' => $job, 'queue' => $queue];
                    }

                    public function assertPushed(string $job, ?int $times = null): void
                    {
                        $count = count(array_filter($this->jobs, fn ($pushed) => $pushed['job'] === $job));

                        if ($count === 0) {
                            throw new RuntimeException("The expected [{$job}] job was not pushed.");
                        }
                        if ($times !== null && $count !== $times) {
                            throw new RuntimeException("The expected [{$job}] job was pushed {$count} times instead of {$times} times.");
                        }
                    }

                    public function assertPushedOn(string $queue, string $job): void
                    {
                        foreach ($this->jobs as $pushed) {
                            if ($pushed['job'] === $job && $pushed['queue'] === $queue) {
                                return;
                            }
                        }

                        throw new RuntimeException("The expected [{$job}] job was not pushed on the [{$queue}] queue.");
                    }
                }
                PHP,
            'hint' => 'Record the job and its queue together. `assertPushed()` counts; `assertPushedOn()` looks for a pair.',
            'checks' => [
                ['label' => 'assertPushed', 'code' => <<<'PHP'
                    $queue = new QueueFake();
                    $queue->push('ChargeCard', 'payments');
                    $queue->push('ChargeCard');
                    $queue->assertPushed('ChargeCard');
                    $queue->assertPushed('ChargeCard', 2);
                    $never = expect_throws(RuntimeException::class, fn () => $queue->assertPushed('SendInvoice'));
                    if ($never !== true) return $never;
                    return expect_throws(RuntimeException::class, fn () => $queue->assertPushed('ChargeCard', 1));
                    PHP],
                ['label' => 'assertPushedOn', 'code' => <<<'PHP'
                    $queue = new QueueFake();
                    $queue->push('ChargeCard', 'payments');
                    $queue->push('SendInvoice');
                    $queue->assertPushedOn('payments', 'ChargeCard');
                    $queue->assertPushedOn('default', 'SendInvoice');
                    return expect_throws(RuntimeException::class, fn () => $queue->assertPushedOn('payments', 'SendInvoice'));
                    PHP],
            ],
        ],
    ],
];
