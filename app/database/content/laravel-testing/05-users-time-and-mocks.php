<?php

// Lesson 5 of the Laravel testing course. The theory is in the .md file with the same name.

return [
    'slug' => 'users-time-and-mocks',
    'title' => 'Users, time and mocks',
    'summary' => 'Testing as different users with actingAs, freezing and travelling through time, testing boundaries, mocks and spies in the container, faking sleep, and when not to mock.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try other times around the boundary.
        function isExpired(DateTimeImmutable $issuedAt, int $ttlMinutes, DateTimeImmutable $now): bool
        {
            return $now >= $issuedAt->modify("+{$ttlMinutes} minutes");
        }

        $issued = new DateTimeImmutable('2026-10-21 09:00:00');
        $now = new DateTimeImmutable('2026-10-21 09:59:59');

        echo isExpired($issued, 60, $now) ? 'expired' : 'valid';
        PHP,

    'quiz' => [
        [
            'question' => 'What does `$this->actingAs($user)` do in a feature test?',
            'options' => [
                'Creates the user',
                'Logs the user in for the request, without going through a login form',
                'Makes the user an admin',
                'Fakes the user\'s email',
            ],
            'correct_option' => 1,
            'explanation' => 'Use it to test the same page as different users.',
        ],
        [
            'question' => 'Which visitors should a test suite check for a page only a book\'s owner may edit?',
            'options' => [
                'Only the owner',
                'The owner (allowed), another user (403) and a guest (redirect to login)',
                'Only a guest',
                'Only an admin',
            ],
            'correct_option' => 1,
            'explanation' => 'Authorization bugs are usually in the paths that should be refused.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $start = new DateTimeImmutable('2026-10-01 00:00:00');
                $end = $start->modify('+14 days');

                foreach (['2026-10-14 23:59:59', '2026-10-15 00:00:00'] as $time) {
                    echo (new DateTimeImmutable($time) < $end ? 'trial' : 'ended') . ' ';
                }
                PHP,
            'options' => ['`trial trial `', '`trial ended `', '`ended ended `', '`ended trial `'],
            'correct_option' => 1,
            'explanation' => 'Fourteen days after midnight on 1 October is midnight on 15 October: that\'s the boundary to test on both sides.',
        ],
        [
            'question' => 'Why does `$this->travel(5)->days()` affect your app\'s code?',
            'options' => [
                'It changes the server\'s clock',
                'Laravel code gets the time from `now()`, and the test changes what `now()` returns',
                'It waits five days',
                'It only affects the database',
            ],
            'correct_option' => 1,
            'explanation' => 'Code calling PHP\'s `time()` directly wouldn\'t notice, which is a reason to use `now()`.',
        ],
        [
            'question' => 'What is the difference between a mock and a spy?',
            'options' => [
                'There is none',
                'A mock is told what to expect before the code runs; a spy records calls so you check them afterwards',
                'A spy can\'t return values',
                'Mocks are for facades, spies for models',
            ],
            'correct_option' => 1,
            'explanation' => 'Spies keep the arrange, act, assert order.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                final class Recorder
                {
                    public array $calls = [];

                    public function __call(string $method, array $arguments): mixed
                    {
                        $this->calls[] = $method . '(' . implode(', ', $arguments) . ')';

                        return null;
                    }
                }

                $gateway = new Recorder();
                $gateway->charge(1749, 'tok_visa');
                $gateway->refund('ch_1');

                echo implode(' ', $gateway->calls);
                PHP,
            'options' => ['`charge(1749, tok_visa) refund(ch_1)`', '`charge refund`', '`refund(ch_1)`', 'An error: the methods don\'t exist'],
            'correct_option' => 0,
            'explanation' => '`__call()` catches calls to methods that don\'t exist, which is how spies record them.',
        ],
        [
            'question' => 'How do you replace a class with a mock so that code which resolves it from the container gets the mock?',
            'options' => ['`new MockInterface()`', '`$this->mock(PaymentGateway::class, fn ($mock) => ...)`', '`PaymentGateway::fake()`', '`app()->forget(PaymentGateway::class)`'],
            'correct_option' => 1,
            'explanation' => '`$this->mock()` binds the mock in the container for the rest of the test.',
        ],
        [
            'question' => 'Which should you usually reach for first in a Laravel feature test?',
            'options' => [
                'A mock for every class the controller uses',
                'The real code, then a Laravel fake, and a mock only for boundaries you don\'t control',
                'A spy for every model',
                'Skipping the test',
            ],
            'correct_option' => 1,
            'explanation' => 'Heavy mocking ties tests to the implementation and can pass while the real code is broken.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A test clock',
            'prompt' => 'Write a `TestClock` class built from a start time (`DateTimeImmutable`). `now()` returns the current time; `travel(int $seconds)` moves it forward (or back, for negative numbers) and returns `$this`; `travelTo(DateTimeImmutable $moment)` jumps to a moment and returns `$this`. Time never moves on its own.',
            'starter_code' => <<<'PHP'
                <?php

                class TestClock
                {
                    public function __construct(private DateTimeImmutable $now) {}

                    public function now(): DateTimeImmutable
                    {
                        return new DateTimeImmutable();
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TestClock
                {
                    public function __construct(private DateTimeImmutable $now) {}

                    public function now(): DateTimeImmutable
                    {
                        return $this->now;
                    }

                    public function travel(int $seconds): static
                    {
                        $this->now = $this->now->modify(($seconds >= 0 ? '+' : '') . "{$seconds} seconds");

                        return $this;
                    }

                    public function travelTo(DateTimeImmutable $moment): static
                    {
                        $this->now = $moment;

                        return $this;
                    }
                }
                PHP,
            'hint' => '`DateTimeImmutable::modify()` returns a new object, so store the result. `"+90 seconds"` and `"-30 seconds"` both work.',
            'checks' => [
                ['label' => 'Frozen, then travelling', 'code' => <<<'PHP'
                    $clock = new TestClock(new DateTimeImmutable('2026-10-21 09:00:00'));
                    $first = $clock->now()->format('H:i:s');
                    $clock->travel(90)->travel(-30);
                    return expect_same([$first, $clock->now()->format('H:i:s')], ['09:00:00', '09:01:00']);
                    PHP],
                ['label' => 'travelTo', 'code' => <<<'PHP'
                    $clock = new TestClock(new DateTimeImmutable('2026-10-21 09:00:00'));
                    $same = $clock->travelTo(new DateTimeImmutable('2026-12-24 18:00:00'));
                    return expect_same([$same === $clock, $clock->now()->format('Y-m-d H:i')], [true, '2026-12-24 18:00']);
                    PHP],
            ],
        ],
        [
            'title' => 'Days left in the trial',
            'prompt' => 'Write `trialDaysLeft(DateTimeImmutable $start, DateTimeImmutable $now, int $days = 14): int`. The trial ends exactly `$days` days after `$start`. Return how many **whole or partial** days are left (rounding up), or 0 once it has ended. One second before the end is 1 day left; at the end it\'s 0.',
            'starter_code' => <<<'PHP'
                <?php

                function trialDaysLeft(DateTimeImmutable $start, DateTimeImmutable $now, int $days = 14): int
                {
                    return $days - $start->diff($now)->days;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function trialDaysLeft(DateTimeImmutable $start, DateTimeImmutable $now, int $days = 14): int
                {
                    $end = $start->modify("+{$days} days");
                    $secondsLeft = $end->getTimestamp() - $now->getTimestamp();

                    return $secondsLeft > 0 ? (int) ceil($secondsLeft / 86400) : 0;
                }
                PHP,
            'hint' => 'Work in seconds: the end timestamp minus now, then `ceil()` divided by 86400.',
            'checks' => [
                ['label' => 'At the start', 'code' => "\$start = new DateTimeImmutable('2026-10-01 12:00:00'); return expect_same([trialDaysLeft(\$start, \$start), trialDaysLeft(\$start, \$start, 7)], [14, 7]);"],
                ['label' => 'Around the end', 'code' => <<<'PHP'
                    $start = new DateTimeImmutable('2026-10-01 12:00:00');
                    $at = fn (string $time) => trialDaysLeft($start, new DateTimeImmutable($time));
                    return expect_same([$at('2026-10-14 12:00:00'), $at('2026-10-15 11:59:59'), $at('2026-10-15 12:00:00'), $at('2026-11-30 00:00:00')], [1, 1, 0, 0]);
                    PHP],
                ['label' => 'Part of a day counts as a day', 'code' => "\$start = new DateTimeImmutable('2026-10-01 12:00:00'); return expect_same(trialDaysLeft(\$start, new DateTimeImmutable('2026-10-02 13:00:00')), 13);"],
            ],
        ],
        [
            'title' => 'Who may edit',
            'prompt' => 'Write `editBookStatus(?array $user, array $book): int`, the status code the edit page should answer with. A guest (`null`) gets `302` (to the login page). A user whose `id` equals the book\'s `owner_id`, or whose `role` is `admin`, gets `200`. Anyone else gets `403`.',
            'starter_code' => <<<'PHP'
                <?php

                function editBookStatus(?array $user, array $book): int
                {
                    return $user['id'] === $book['owner_id'] ? 200 : 403;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function editBookStatus(?array $user, array $book): int
                {
                    if ($user === null) {
                        return 302;
                    }

                    $owns = $user['id'] === $book['owner_id'];
                    $isAdmin = ($user['role'] ?? null) === 'admin';

                    return $owns || $isAdmin ? 200 : 403;
                }
                PHP,
            'hint' => 'Handle the guest first. Not every user has a `role` key.',
            'checks' => [
                ['label' => 'Each kind of visitor', 'code' => <<<'PHP'
                    $book = ['id' => 7, 'owner_id' => 1];
                    return expect_same([
                        editBookStatus(['id' => 1], $book),
                        editBookStatus(['id' => 2], $book),
                        editBookStatus(['id' => 3, 'role' => 'admin'], $book),
                        editBookStatus(['id' => 4, 'role' => 'editor'], $book),
                        editBookStatus(null, $book),
                    ], [200, 403, 200, 403, 302]);
                    PHP],
            ],
        ],
        [
            'title' => 'A spy',
            'prompt' => 'Write a `Spy` class that records every method called on it (any name) with its arguments, and returns `null`. `shouldHaveReceived(string $method, ?array $arguments = null, ?int $times = null)` throws a `RuntimeException` unless the method was called (with exactly those arguments, if given), and, if `$times` is given, exactly that many times.',
            'starter_code' => <<<'PHP'
                <?php

                class Spy
                {
                    private array $calls = [];

                    public function __call(string $method, array $arguments): mixed
                    {
                        $this->calls[] = $method;

                        return null;
                    }

                    public function shouldHaveReceived(string $method, ?array $arguments = null, ?int $times = null): void
                    {
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Spy
                {
                    private array $calls = [];

                    public function __call(string $method, array $arguments): mixed
                    {
                        $this->calls[] = [$method, $arguments];

                        return null;
                    }

                    public function shouldHaveReceived(string $method, ?array $arguments = null, ?int $times = null): void
                    {
                        $matching = array_filter(
                            $this->calls,
                            fn ($call) => $call[0] === $method && ($arguments === null || $call[1] === $arguments),
                        );
                        $count = count($matching);

                        if ($count === 0) {
                            throw new RuntimeException("Method {$method} was not called as expected.");
                        }
                        if ($times !== null && $count !== $times) {
                            throw new RuntimeException("Method {$method} was called {$count} times, expected {$times}.");
                        }
                    }
                }
                PHP,
            'hint' => 'Record `[$method, $arguments]`, then count the calls that match the name (and the arguments, when given).',
            'checks' => [
                ['label' => 'Records any call', 'code' => <<<'PHP'
                    $spy = new Spy();
                    $result = $spy->charge(1749, 'tok_visa');
                    $spy->charge(500, 'tok_visa');
                    $spy->refund('ch_1');
                    $spy->shouldHaveReceived('charge');
                    $spy->shouldHaveReceived('charge', null, 2);
                    $spy->shouldHaveReceived('charge', [1749, 'tok_visa'], 1);
                    $spy->shouldHaveReceived('refund', ['ch_1']);
                    return $result === null || 'Calls should return null.';
                    PHP],
                ['label' => 'Fails when calls don\'t match', 'code' => <<<'PHP'
                    $spy = new Spy();
                    $spy->charge(1749, 'tok_visa');
                    foreach ([fn () => $spy->shouldHaveReceived('refund'), fn () => $spy->shouldHaveReceived('charge', [1749]), fn () => $spy->shouldHaveReceived('charge', null, 2)] as $failing) {
                        $result = expect_throws(RuntimeException::class, $failing);
                        if ($result !== true) return $result;
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'A stub with answers',
            'prompt' => 'Write a `Stub` class built from an array of method name => return value. Calling a listed method returns its value, or, if the value is a `Closure`, the result of calling it with the method\'s arguments. Calling any other method throws a `BadMethodCallException` with the message `Unexpected call to NAME().`',
            'starter_code' => <<<'PHP'
                <?php

                class Stub
                {
                    public function __construct(private array $returns) {}

                    public function __call(string $method, array $arguments): mixed
                    {
                        return $this->returns[$method] ?? null;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Stub
                {
                    public function __construct(private array $returns) {}

                    public function __call(string $method, array $arguments): mixed
                    {
                        if (! array_key_exists($method, $this->returns)) {
                            throw new BadMethodCallException("Unexpected call to {$method}().");
                        }

                        $value = $this->returns[$method];

                        return $value instanceof Closure ? $value(...$arguments) : $value;
                    }
                }
                PHP,
            'hint' => 'Use `array_key_exists()` (a listed method may return `null`) and spread the arguments into the closure.',
            'checks' => [
                ['label' => 'Returns the answers', 'code' => <<<'PHP'
                    $rates = new Stub(['rateFor' => fn (string $postcode) => str_starts_with($postcode, 'SW') ? 4.99 : 6.99, 'currency' => 'GBP', 'discount' => null]);
                    return expect_same([$rates->rateFor('SW1A 1AA'), $rates->rateFor('M1 1AE'), $rates->currency(), $rates->discount()], [4.99, 6.99, 'GBP', null]);
                    PHP],
                ['label' => 'Unexpected calls throw', 'code' => <<<'PHP'
                    try {
                        (new Stub([]))->charge(100);
                    } catch (BadMethodCallException $e) {
                        return expect_same($e->getMessage(), 'Unexpected call to charge().');
                    }
                    return 'An unlisted method should throw.';
                    PHP],
            ],
        ],
        [
            'title' => 'Retry without waiting',
            'prompt' => 'Write `retry(int $times, callable $callback, int $sleepMs, callable $sleep): mixed`. Call `$callback` (passing the attempt number, from 1) until it returns without throwing, and return its result. Between failed attempts, call `$sleep($sleepMs)` instead of really waiting. After `$times` failed attempts, rethrow the last exception (no sleep after the last attempt).',
            'starter_code' => <<<'PHP'
                <?php

                function retry(int $times, callable $callback, int $sleepMs, callable $sleep): mixed
                {
                    for ($attempt = 1; $attempt <= $times; $attempt++) {
                        try {
                            return $callback($attempt);
                        } catch (Throwable $e) {
                            usleep($sleepMs * 1000);
                        }
                    }

                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function retry(int $times, callable $callback, int $sleepMs, callable $sleep): mixed
                {
                    for ($attempt = 1; ; $attempt++) {
                        try {
                            return $callback($attempt);
                        } catch (Throwable $e) {
                            if ($attempt >= $times) {
                                throw $e;
                            }
                            $sleep($sleepMs);
                        }
                    }
                }
                PHP,
            'hint' => 'In the `catch`, rethrow if this was the last attempt; otherwise call `$sleep($sleepMs)` and go round again.',
            'checks' => [
                ['label' => 'Succeeds after a failure', 'code' => <<<'PHP'
                    $slept = [];
                    $result = retry(3, function (int $attempt) {
                        if ($attempt < 3) throw new RuntimeException("Attempt {$attempt} failed");
                        return "ok on {$attempt}";
                    }, 100, function (int $ms) use (&$slept) { $slept[] = $ms; });
                    return expect_same([$result, $slept], ['ok on 3', [100, 100]]);
                    PHP],
                ['label' => 'Gives up and rethrows', 'code' => <<<'PHP'
                    $slept = [];
                    try {
                        retry(2, fn (int $attempt) => throw new RuntimeException("Attempt {$attempt} failed"), 50, function (int $ms) use (&$slept) { $slept[] = $ms; });
                    } catch (RuntimeException $e) {
                        return expect_same([$e->getMessage(), $slept], ['Attempt 2 failed', [50]]);
                    }
                    return 'The last exception should be rethrown.';
                    PHP],
                ['label' => 'No sleep after a first success', 'code' => <<<'PHP'
                    $slept = 0;
                    $result = retry(5, fn () => 'first time', 100, function () use (&$slept) { $slept++; });
                    return expect_same([$result, $slept], ['first time', 0]);
                    PHP],
            ],
        ],
    ],
];
