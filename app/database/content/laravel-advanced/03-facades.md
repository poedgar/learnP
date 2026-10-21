## Static calls that aren't static

Laravel code is full of calls like `Cache::get('key')`, `Mail::to($user)->send(...)` and
`Route::get(...)`. They look like static methods, but `Cache` has no static `get()` method. `Cache`
is a **facade**: a small class that forwards each static call to an ordinary object living in the
service container.

```laravel
use Illuminate\Support\Facades\Cache;

Cache::put('bestseller', 'Dune', now()->addHour());

// ...is the same as...
app('cache')->put('bestseller', 'Dune', now()->addHour());
```

So you get a short, readable syntax, while the real work is done by an object that can be
configured, swapped and faked like any other.

## How the forwarding works

PHP calls a class's `__callStatic()` magic method whenever a static method that doesn't exist is
called. A facade uses it to look up the real object and call the method on it. Each facade only says
*which* object, by returning its container id from `getFacadeAccessor()`:

```php
abstract class Facade
{
    public static array $container = [];

    abstract protected static function getFacadeAccessor(): string;

    public static function __callStatic(string $method, array $arguments): mixed
    {
        $instance = static::$container[static::getFacadeAccessor()];

        return $instance->$method(...$arguments);
    }
}

class CacheStore
{
    private array $items = [];

    public function put(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->items[$key] ?? null;
    }
}

class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
}

Facade::$container['cache'] = new CacheStore();

Cache::put('bestseller', 'Dune');
echo Cache::get('bestseller');
```

`static::getFacadeAccessor()` uses late static binding: inside `Facade`, `static` is whichever
facade was called, so `Cache` gets `'cache'` and a `Mail` facade would get `'mail'`.

## Resolved once, then remembered

Looking the object up in the container on every call would be wasteful, so Laravel's facades keep
each resolved object in a static array, keyed by accessor. `Facade::clearResolvedInstances()`
empties it; Laravel's test case does that between tests so nothing leaks from one test to the next.

## Facades in tests

Because a facade only forwards, a test can put a different object behind it. `Cache::swap($fake)`
replaces the object, both in the facade's memory and in the container. Several facades come with a
ready-made fake that records what happened, so you can assert on it:

```laravel
Mail::fake();

$this->post('/orders', [...]);

Mail::assertSent(OrderShipped::class);
```

Here is the idea in plain PHP: a fake notifier that records messages instead of sending them.

```php
interface Notifier
{
    public function send(string $to, string $message): void;
}

class NotifierFake implements Notifier
{
    public array $sent = [];

    public function send(string $to, string $message): void
    {
        $this->sent[] = "$to: $message";
    }

    public function assertSentTo(string $to): string
    {
        foreach ($this->sent as $line) {
            if (str_starts_with($line, "$to: ")) {
                return "OK, something was sent to $to";
            }
        }

        return "FAIL: nothing was sent to $to";
    }
}

function placeOrder(Notifier $notifier): void
{
    $notifier->send('ada@example.com', 'Your order is on its way');
}

$fake = new NotifierFake();
placeOrder($fake);

echo $fake->assertSentTo('ada@example.com') . PHP_EOL;
echo $fake->assertSentTo('bob@example.com');
```

## Aliases

In Blade views and older code you see `Cache::get()` without any `use` line. That works because
Laravel registers **aliases**: short global names for facade classes, created lazily with PHP's
`class_alias()` the first time they're used.

```php
class SupportStr
{
    public static function upper(string $text): string
    {
        return strtoupper($text);
    }
}

class_alias(SupportStr::class, 'Str');

echo Str::upper('piranesi') . ' ' . (Str::class);
```

`Str::class` is just the alias string; the class behind it is `SupportStr`.

## Real-time facades

Any class can be used as a facade without writing a facade class for it: put `Facades\` in front of
its name. Laravel's autoloader notices the prefix, generates a tiny facade class on the fly whose
accessor is the original class name, and the container builds the real object.

```laravel
use Facades\App\Services\PriceCalculator;

PriceCalculator::total($basket); // (new PriceCalculator(...))->total($basket), via the container
```

Generating a class at runtime is done with an **autoloader**: a function PHP calls when it meets a
class it doesn't know yet. You'll write one in the challenges.

## Facade or injection?

Facades are convenient, but they hide a class's dependencies: nothing in a constructor says that it
sends mail. A good rule of thumb:

- In small glue code (routes, controllers, views), facades keep things short and readable.
- In classes with real logic, prefer **constructor injection** of the underlying class or interface,
  so the dependencies are visible and the class works without Laravel.

Either way, they reach the same object in the container.
