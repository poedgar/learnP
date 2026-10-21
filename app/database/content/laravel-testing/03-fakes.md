## Don't send the email

Placing an order sends a confirmation email, queues a job to charge the card, fires an event and calls a
shipping company's API. A test of "place an order" shouldn't do any of those for real: it would be
slow, need the network, and email real people. But it should check they **would** happen.

Laravel's answer is **fakes**. Each facade can swap its real service for a fake that records what it's
asked to do, and gives you assertions about it (the PHP testing course built fakes by hand; these are
ready-made):

```laravel
it('emails the customer when an order is placed', function () {
    Mail::fake();

    $order = Order::factory()->create();
    $this->post("/orders/{$order->id}/place");

    Mail::assertSent(OrderPlaced::class);
});
```

Call `fake()` **before** the code under test runs; the facade then points at the fake (that's the swap
from the facades lesson in Advanced Laravel).

## Asserting with a closure

"An email was sent" is rarely enough. Pass a closure to check *which* one: it gets each recorded mail,
and the assertion passes if any returns true:

```laravel
Mail::assertSent(OrderPlaced::class, function (OrderPlaced $mail) use ($order) {
    return $mail->order->is($order) && $mail->hasTo($order->customer->email);
});

Mail::assertSent(OrderPlaced::class, 1);   // exactly once
Mail::assertNotSent(RefundIssued::class);
Mail::assertNothingSent();
```

Inside, a fake is a list and a filter:

```php
$sent = [];
$send = function (string $mailable, array $data) use (&$sent) {
    $sent[] = ['mailable' => $mailable, 'data' => $data];
};

$send('OrderPlaced', ['to' => 'ada@example.com', 'order' => 7]);
$send('Newsletter', ['to' => 'ada@example.com']);

$matching = array_filter($sent, fn ($mail) => $mail['mailable'] === 'OrderPlaced' && $mail['data']['order'] === 7);

echo count($matching) === 1 ? 'OrderPlaced was sent once' : 'not sent';
```

## The other fakes

```laravel
Queue::fake();
// ... act ...
Queue::assertPushed(ChargeCard::class);
Queue::assertPushedOn('payments', ChargeCard::class);
Queue::assertNotPushed(SendInvoice::class);

Event::fake([OrderShipped::class]);   // only this event is faked; others run as normal
Event::assertDispatched(OrderShipped::class, fn ($e) => $e->order->is($order));

Notification::fake();
Notification::assertSentTo($user, InvoicePaid::class);

Storage::fake('avatars');             // a throwaway disk
$this->post('/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')]);
Storage::disk('avatars')->assertExists('me.jpg');
```

A faked queue doesn't run the job. That's right for testing "placing an order queues a charge". Test
the job itself separately, by calling its `handle()` method in its own test.

`Event::fake()` with no list fakes **every** event, including Eloquent's own model events, which can
silence observers your test relies on. Name the events you mean.

## Faking HTTP calls

Code that calls other services uses the `Http` client, and `Http::fake()` returns canned responses
instead of going out to the network. Patterns can use `*`:

```laravel
Http::preventStrayRequests();   // any request without a fake throws

Http::fake([
    'api.shipping.test/rates*' => Http::response(['price' => 4.99], 200),
    'api.shipping.test/*' => Http::response(['error' => 'Down'], 503),
]);

$rate = app(ShippingRates::class)->for($order);

expect($rate)->toBe(4.99);
Http::assertSent(fn (Request $request) => $request['postcode'] === 'SW1A 1AA');
```

The first pattern that matches wins, so put the specific ones first. `preventStrayRequests()` is worth
turning on for every test: a forgotten fake fails loudly instead of quietly calling a real API.

A `*` pattern becomes a regular expression: quote everything, then let `*` match anything:

```php
$pattern = 'api.shipping.test/rates*';
$regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';

foreach (['api.shipping.test/rates?to=SW1', 'api.shipping.test/labels'] as $url) {
    echo $url . ': ' . (preg_match($regex, $url) ? 'faked' : 'no match') . PHP_EOL;
}
```

## Fake at the edge

Fake what leaves your app (mail, queues, other people's APIs, the file system), and let everything
inside it run for real: your controllers, models and database. That way tests check that the parts
work *together*, and only the parts you can't control are pretended.
