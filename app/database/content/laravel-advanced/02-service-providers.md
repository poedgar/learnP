## Where bindings live

The container needs to be taught how to build things before anything asks for them. In Laravel
that teaching happens in **service providers**: small classes, one per area of the app (mail,
payments, a package), each registering its bindings and setting things up. `bootstrap/providers.php`
lists your own; packages add theirs automatically.

```laravel
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, fn ($app) => new StripeGateway(
            config('services.stripe.secret'),
        ));
    }

    public function boot(): void
    {
        Event::listen(OrderPlaced::class, ChargeCustomer::class);
    }
}
```

## Two phases: register, then boot

A provider has two methods, and Laravel calls them in two separate passes:

1. **register()** on every provider, in order. This only puts bindings into the container. It must
   not *use* other services, because the provider that registers them may not have run yet.
2. **boot()** on every provider, in order, once all of them are registered. Now every binding
   exists, so `boot()` may use anything: register event listeners, routes, view composers.

Here a mail provider's `boot()` uses a logger that a provider *later in the list* registers. It works
only because every `register()` has run before the first `boot()`:

```php
abstract class ServiceProvider
{
    public function __construct(protected App $app) {}

    public function register(): void {}

    public function boot(): void {}
}

class App
{
    public array $bindings = [];
    public array $log = [];

    /** @param list<class-string<ServiceProvider>> $providers */
    public function start(array $providers): void
    {
        $instances = array_map(fn ($class) => new $class($this), $providers);

        foreach ($instances as $provider) {
            $provider->register();
        }
        foreach ($instances as $provider) {
            $provider->boot();
        }
    }
}

class MailProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindings['mailer'] = fn () => 'smtp mailer';
    }

    public function boot(): void
    {
        $logger = $this->app->bindings['logger']();
        $this->app->log[] = "$logger is watching the mailer";
    }
}

class LogProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindings['logger'] = fn () => 'file logger';
    }
}

$app = new App();
$app->start([MailProvider::class, LogProvider::class]);

echo implode(PHP_EOL, $app->log);
```

Move the logger lookup into `MailProvider::register()` and it breaks, because `LogProvider` hasn't
registered anything yet. That's the whole reason for the two phases.

## Hooks around booting

The application also lets you run code just before and just after the providers boot, with
`booting()` and `booted()`. A `booted()` callback added after the app has already booted runs
straight away, so it is safe to call at any time:

```laravel
$app->booted(function () {
    // Every provider has booted; routes and listeners are all in place.
});
```

## Deferred providers

Most requests use only a few services. A provider whose `register()` only binds things can be
**deferred**: Laravel remembers which ids it `provides()` and doesn't load the provider at all
until one of those ids is first asked for. That keeps startup fast:

```php
class PdfProvider
{
    public static int $loaded = 0;

    public function provides(): array
    {
        return ['pdf'];
    }

    public function register(array &$bindings): void
    {
        self::$loaded++;
        $bindings['pdf'] = fn () => 'PDF renderer';
    }
}

$bindings = [];
$deferred = [];
foreach ((new PdfProvider())->provides() as $id) {
    $deferred[$id] = PdfProvider::class;
}

echo 'Loaded before use: ' . PdfProvider::$loaded . PHP_EOL;

$id = 'pdf';
if (! isset($bindings[$id]) && isset($deferred[$id])) {
    (new $deferred[$id]())->register($bindings);
}
echo $bindings[$id]() . ', loaded: ' . PdfProvider::$loaded;
```

In Laravel a provider opts in by implementing the `DeferrableProvider` interface. The list of
deferred ids is cached, so Laravel doesn't even have to create the provider to know what it offers.

## Configuration from packages

A package ships default settings, and the app can override any of them. A provider merges the two
in `register()`, with the app's values winning:

```php
$packageDefaults = ['driver' => 'smtp', 'port' => 587, 'from' => 'noreply@example.com'];
$appConfig = ['from' => 'shop@example.com'];

$config = array_merge($packageDefaults, $appConfig);

echo $config['driver'] . ' ' . $config['port'] . ' ' . $config['from'];
```

In Laravel that's one line in a provider's `register()`:
`$this->mergeConfigFrom(__DIR__.'/../config/mail.php', 'mail');`.

## Checking the environment

Some providers belong only to some environments: a debug toolbar locally, an error tracker in
production. Laravel's `environment()` answers both questions: with no arguments it returns the
name; with names, or patterns such as `local*`, it says whether the current one matches.

```laravel
if ($this->app->environment('local', 'testing')) {
    $this->app->register(DebugbarServiceProvider::class);
}
```

## How a request starts

Putting it together, every Laravel request (and every Artisan command) starts the same way:

1. Create the application, which is the container.
2. Load the environment and configuration.
3. Register every provider, then boot every provider (deferred ones wait until needed).
4. Hand the request to the HTTP kernel, which sends it through middleware to a route.

The next lessons follow the request through those last steps.
