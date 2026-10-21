## Tests that read like sentences

Pest is a testing framework built on PHPUnit, and the default in new Laravel apps. Everything from the
last three lessons works in it unchanged. What it adds is a shorter way to write tests and a fluent
**expectation** API:

```laravel
test('the total adds up the lines', function () {
    $cart = new Cart();
    $cart->add('Dune', 9.99);
    $cart->add('Emma', 7.50);

    expect($cart->total())->toBe(17.49)
        ->and($cart->count())->toBe(2);
});
```

`expect($value)` wraps a value; each `to...` method checks it and returns the expectation again, so you
chain checks. `->and($other)` switches to a new value.

## Common expectations

```laravel
expect($total)->toBe(17.49);             // ===
expect($user->toArray())->toEqual([...]); // ==
expect($ok)->toBeTrue();
expect($title)->toBeString()->toContain('Dune')->toStartWith('D');
expect($books)->toHaveCount(3)->toContain('Dune');
expect($user)->toBeInstanceOf(User::class);
expect($price)->toBeGreaterThan(0)->toBeLessThanOrEqual(100);
expect(fn () => $cart->add('Dune', -1))->toThrow(InvalidArgumentException::class);

expect($status)->not->toBe('failed');    // `not` flips the next expectation
expect($prices)->each->toBeGreaterThan(0); // check every item
```

An expectation is an object holding a value, with methods that throw when the value is wrong:

```php
final class Expectation
{
    public function __construct(public mixed $value) {}

    public function toBe(mixed $expected): static
    {
        if ($this->value !== $expected) {
            throw new RuntimeException('Expected ' . var_export($this->value, true) . ' to be ' . var_export($expected, true));
        }

        return $this;
    }
}

function expect(mixed $value): Expectation
{
    return new Expectation($value);
}

expect(2 + 2)->toBe(4);
echo 'passed' . PHP_EOL;

try {
    expect('4')->toBe(4);
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
```

## Datasets

Run one test with many inputs using `with()`. Each item becomes its own test in the report:

```laravel
it('rejects invalid ISBNs', function (string $isbn) {
    expect(Isbn::isValid($isbn))->toBeFalse();
})->with([
    'too short' => ['123'],
    'letters' => ['ABCDEFGHIJ'],
    'bad check digit' => ['0306406153'],
]);
```

The report shows `it rejects invalid ISBNs with data set "too short"`, so you know which one failed.
Datasets can be shared between files (`dataset('isbns', [...])` in `tests/Datasets`), and several
`with()` calls combine into every combination.

## Hooks and groups

```laravel
describe('the cart', function () {
    beforeEach(function () {
        $this->cart = new Cart();   // a fresh cart before every test in this block
    });

    it('starts empty', function () {
        expect($this->cart->count())->toBe(0);
    });

    it('adds items', function () {
        $this->cart->add('Dune', 9.99);
        expect($this->cart->count())->toBe(1);
    });
});
```

`beforeEach` runs before **every** test, so tests never share state. That's the most important rule
of all: a test must pass on its own, in any order. There's also `afterEach`, and
`beforeAll`/`afterAll` for the rare one-off setup.

## Skipping, todos and only

```laravel
it('exports to PDF')->todo();                         // a reminder, shown in the report
it('talks to the real API', fn () => ...)->skip('Needs credentials');
it('is the one I am fixing', fn () => ...)->only();   // run just this (don't commit it!)
```

## Architecture tests

Pest can test rules about the code itself, which keeps a codebase tidy as it grows:

```laravel
arch('models extend Eloquent')
    ->expect('App\Models')
    ->toExtend(Illuminate\Database\Eloquent\Model::class);

arch('no debugging left behind')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('controllers are not used by models')
    ->expect('App\Models')
    ->not->toUse('App\Http\Controllers');
```

Run tests in parallel with `php artisan test --parallel`, and see which lines your tests reach with
`--coverage` (it needs Xdebug or PCOV).
