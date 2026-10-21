## Logged in as someone

Most pages behave differently for guests, customers and admins, so tests need to choose who is making
the request. `actingAs()` logs a user in for the request, without a login form:

```laravel
it('lets the owner edit a book', function () {
    $owner = User::factory()->create();
    $book = Book::factory()->for($owner)->create();

    $this->actingAs($owner)->get("/books/{$book->id}/edit")->assertOk();
});

it('forbids other users', function () {
    $book = Book::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/books/{$book->id}/edit")
        ->assertForbidden();
});

it('sends guests to the login page', function () {
    $this->get('/books/1/edit')->assertRedirect('/login');
});
```

For every protected page, test **each kind of visitor**: the one who's allowed, the one who isn't, and a
guest. Authorization bugs are rarely in the happy path. For APIs, `Sanctum::actingAs($user, ['books:write'])`
does the same with token abilities.

## Controlling time

Code that depends on "now" (trials that end, links that expire, a report for "last month") is hard to
test if the clock keeps moving. Laravel lets a test move time:

```laravel
it('ends the trial after 14 days', function () {
    $this->freezeTime();                       // now() stops moving
    $user = User::factory()->create();         // trial starts now

    $this->travel(13)->days();
    expect($user->fresh()->onTrial())->toBeTrue();

    $this->travel(1)->days();
    expect($user->fresh()->onTrial())->toBeFalse();
});

$this->travelTo(now()->setDate(2026, 12, 24));  // a fixed moment
$this->travelBack();                            // back to the real clock
```

This works because Laravel code asks `now()` (Carbon) for the time, and the test can replace what it
returns. The same idea in plain PHP is to pass the clock in:

```php
function isExpired(DateTimeImmutable $issuedAt, int $ttlMinutes, DateTimeImmutable $now): bool
{
    return $now >= $issuedAt->modify("+{$ttlMinutes} minutes");
}

$issued = new DateTimeImmutable('2026-10-21 09:00');

foreach (['09:59', '10:00', '10:01'] as $time) {
    echo $time . ' ' . (isExpired($issued, 60, new DateTimeImmutable("2026-10-21 {$time}")) ? 'expired' : 'valid') . PHP_EOL;
}
```

Test the **boundaries**: one second before, exactly at, and one second after the moment something
changes. That's where off-by-one bugs live.

## Mocks and spies

Sometimes your code depends on a class that's slow or talks to the outside world, and there's no
ready-made fake. Swap it in the container with a mock:

```laravel
it('charges the card when an order is placed', function () {
    $this->mock(PaymentGateway::class, function (MockInterface $mock) {
        $mock->shouldReceive('charge')
            ->once()
            ->with(1749, 'tok_visa')
            ->andReturn(new Charge(id: 'ch_1', status: 'paid'));
    });

    $this->post('/orders', [...])->assertCreated();
});
```

A **mock** has expectations set up front (`once()`, `with(...)`) and fails if they aren't met. A **spy**
records everything and lets you check afterwards, which keeps the arrange, act, assert order:

```laravel
$spy = $this->spy(PaymentGateway::class);

$this->post('/orders', [...]);

$spy->shouldHaveReceived('charge')->once()->with(1749, 'tok_visa');
```

A spy is just an object that writes down every call, which PHP's `__call()` makes easy:

```php
final class Spy
{
    public array $calls = [];

    public function __call(string $method, array $arguments): mixed
    {
        $this->calls[] = [$method, $arguments];

        return null;
    }
}

$gateway = new Spy();
$gateway->charge(1749, 'tok_visa');

print_r($gateway->calls);
```

## Don't wait in tests

Code that sleeps between retries makes tests slow. `Sleep::fake()` makes `Sleep::for(...)` return at
once while recording how long it would have waited, and `Sleep::assertSlept()` checks it. The idea is
the same as the clock: make the waiting something the test controls.

## Don't mock what you own

Mocks are powerful, but a test full of `shouldReceive()` breaks every time you refactor, and it can
pass while the real code is broken (the mock says what *you think* the class does). Prefer, in order:
the real thing, a Laravel fake, then a mock for a boundary you don't control, such as a payment
provider.
