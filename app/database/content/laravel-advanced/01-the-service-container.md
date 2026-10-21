## The heart of Laravel

Almost everything in a Laravel app is built by one object: the **service container**. When a
controller asks for a `Mailer` in its constructor, Laravel doesn't call `new Mailer()` itself; it
asks the container, which knows how to build a mailer, and the mailer's own dependencies, and
theirs. In a real app it looks like this:

```laravel
// Teach the container how to build something...
app()->bind(PaymentGateway::class, fn ($app) => new StripeGateway(config('services.stripe.key')));

// ...and ask for it anywhere: by name, or just by type-hinting it.
$gateway = app()->make(PaymentGateway::class);

class CheckoutController
{
    public function __construct(private PaymentGateway $gateway) {}
}
```

Code in a `laravel` block like the one above is real Laravel. It can't run in this sandbox, which runs
a single PHP file without the framework, so in this course you rebuild each piece of Laravel in plain
PHP instead. The real thing is bigger, but it works the same way.

## Bind and make

At its simplest a container is an array of **factories**: functions that build something, stored
under a name. `bind()` stores a factory; `make()` looks it up and calls it. Each factory receives
the container, so it can ask for the things it needs in turn:

```php
class Container
{
    private array $factories = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function make(string $id): mixed
    {
        return ($this->factories[$id])($this);
    }
}

class Mailer
{
    public function __construct(public string $from) {}
}

$app = new Container();
$app->bind('from', fn () => 'shop@example.com');
$app->bind(Mailer::class, fn (Container $c) => new Mailer($c->make('from')));

echo $app->make(Mailer::class)->from;
```

Names are usually class or interface names (`Mailer::class` is just the string `'Mailer'`), but
any string works.

## A new object every time, or one shared object

A plain binding calls its factory on every `make()`, so each call gets a new object. That suits
things with state of their own, like a shopping basket. Things that are expensive to build or must
be shared, like a database connection, are registered with `singleton()` instead: the factory runs
once, and every later `make()` returns that same object.

```php
class Connection
{
    public static int $opened = 0;

    public function __construct()
    {
        self::$opened++;
    }
}

$factories = ['db' => fn () => new Connection()];
$shared = [];

function makeShared(string $id, array $factories, array &$shared): object
{
    return $shared[$id] ??= $factories[$id]();
}

$a = makeShared('db', $factories, $shared);
$b = makeShared('db', $factories, $shared);

var_dump($a === $b, Connection::$opened);
```

Laravel also has `instance()`, for an object you've already built: it is stored and handed out as
it is. Tests use it all the time, to swap a real service for a fake.

## Depend on interfaces

The container shines when code asks for an **interface** and the container decides which class
fulfils it. `Checkout` below only knows about `PaymentGateway`; which gateway it gets is decided in
one place, the binding:

```php
interface PaymentGateway
{
    public function charge(int $pence): string;
}

class FakeGateway implements PaymentGateway
{
    public function charge(int $pence): string
    {
        return "Pretended to charge $pence";
    }
}

class Checkout
{
    public function __construct(private PaymentGateway $gateway) {}

    public function pay(int $pence): string
    {
        return $this->gateway->charge($pence);
    }
}

$bindings = [PaymentGateway::class => fn () => new FakeGateway()];
$checkout = new Checkout($bindings[PaymentGateway::class]());

echo $checkout->pay(1250);
```

In Laravel, `$app->bind(PaymentGateway::class, StripeGateway::class)` takes a class name as the
second argument too; the container builds that class when the interface is asked for.

## Autowiring with reflection

Most classes are never bound at all. Ask Laravel's container for a class it has no binding for, and
it reads the class's constructor with **reflection**, builds each type-hinted dependency (with the
same process, all the way down) and passes them in. This is called **autowiring**:

```php
class Logger {}

class Inventory
{
    public function __construct(public Logger $logger, public int $lowStock = 3) {}
}

function build(string $class): object
{
    $constructor = (new ReflectionClass($class))->getConstructor();
    if ($constructor === null) {
        return new $class();
    }

    $arguments = [];
    foreach ($constructor->getParameters() as $parameter) {
        $type = $parameter->getType();
        $arguments[] = $type instanceof ReflectionNamedType && ! $type->isBuiltin()
            ? build($type->getName())
            : $parameter->getDefaultValue();
    }

    return new $class(...$arguments);
}

$inventory = build(Inventory::class);
echo get_class($inventory->logger) . ', low stock at ' . $inventory->lowStock;
```

A parameter with a built-in type (`int`, `string`) and no default can't be guessed. Laravel throws a
`BindingResolutionException` saying which parameter it couldn't resolve.

## Calling functions with injection

The container can also **call** a function, filling in its parameters. Laravel does this for
controller methods, route closures, `handle()` methods on jobs and more: class-typed parameters
come from the container, the rest from values you pass by name.

```laravel
Route::get('/books/{id}', function (Request $request, string $id) {
    // $request comes from the container, $id from the URL.
});

app()->call([$report, 'generate'], ['year' => 2026]);
```

## What a container gives you

- One place that decides how things are built, instead of `new` scattered through the code.
- Easy swapping: bind a fake in tests, a different gateway in production.
- Shared services without global variables.
- Less wiring code, thanks to autowiring.

The cost is a little magic: objects appear without a visible `new`. Laravel keeps that magic
predictable by always following the same rules, the ones you'll build in the challenges.
