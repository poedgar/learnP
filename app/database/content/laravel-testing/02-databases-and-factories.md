## A fresh database for every test

Feature tests touch the database, and a test that leaves rows behind can break the next one. Add the
`RefreshDatabase` trait and Laravel migrates a test database once, then wraps **each test in a
transaction that's rolled back** when the test ends. Every test starts with empty tables, and it's
fast, because nothing is migrated twice.

```laravel
// tests/Pest.php: use it for every feature test
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');
```

Point tests at their own database in `phpunit.xml` (often SQLite in memory:
`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) so they never touch your development data.

The rollback idea is simple: remember the state before, put it back after.

```php
$books = [['id' => 1, 'title' => 'Dune']];

$snapshot = $books;                          // begin the transaction
$books[] = ['id' => 2, 'title' => 'Emma'];   // the test inserts a row
echo count($books) . PHP_EOL;

$books = $snapshot;                          // roll back
echo count($books) . PHP_EOL;
```

## Factories

Tests need data, and typing every column is tedious and brittle. A **factory** describes how to make
a believable model, with fake values for anything the test doesn't care about:

```laravel
// database/factories/BookFactory.php
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'price' => fake()->randomFloat(2, 1, 50),
            'published' => true,
            'author_id' => Author::factory(),
        ];
    }
}
```

Then in a test:

```laravel
$book = Book::factory()->create();                         // saved to the database
$book = Book::factory()->make();                           // built, not saved
$books = Book::factory()->count(3)->create();              // a collection of three
$book = Book::factory()->create(['title' => 'Dune']);      // override what matters
```

Only state what the test is about. `create(['price' => 0])` in a test about free books tells the
reader exactly what matters; everything else is filled in.

At heart, a factory merges defaults and overrides, calling the values that are closures:

```php
$definition = [
    'title' => fn () => 'Book ' . rand(1, 1000),
    'price' => 9.99,
    'published' => true,
];

$make = function (array $overrides = []) use ($definition): array {
    $attributes = array_merge($definition, $overrides);

    return array_map(fn ($value) => $value instanceof Closure ? $value() : $value, $attributes);
};

print_r($make(['title' => 'Dune', 'price' => 0]));
```

## States

A **state** is a named set of changes, so tests read like sentences:

```laravel
// in BookFactory
public function unpublished(): static
{
    return $this->state(fn (array $attributes) => ['published' => false]);
}

public function free(): static
{
    return $this->state(['price' => 0]);
}

// in a test
$draft = Book::factory()->unpublished()->free()->create();
```

Each state call returns a **new** factory, so `$factory->unpublished()` doesn't change `$factory`.

## Sequences and relationships

`sequence()` gives each model in turn the next set of values, going round again when it runs out:

```laravel
Book::factory()->count(4)->sequence(['published' => true], ['published' => false])->create();
// published, unpublished, published, unpublished
```

```php
$sequence = [['published' => true], ['published' => false]];

foreach (range(0, 3) as $i) {
    echo var_export($sequence[$i % count($sequence)]['published'], true) . ' ';
}
```

Related models come from their own factories: `'author_id' => Author::factory()` makes an author for
each book, `Book::factory()->for($author)` uses one you already have, and
`Author::factory()->has(Book::factory()->count(3))->create()` makes an author with three books.

## Asserting on the database

```laravel
$this->assertDatabaseHas('books', ['title' => 'Dune', 'published' => true]);
$this->assertDatabaseMissing('books', ['title' => 'Draft']);
$this->assertDatabaseCount('books', 3);
$this->assertModelExists($book);
$this->assertSoftDeleted($book);
```

`assertDatabaseHas` passes if **some** row has **at least** those values; other columns don't matter:

```php
$rows = [
    ['id' => 1, 'title' => 'Dune', 'price' => 9.99],
    ['id' => 2, 'title' => 'Emma', 'price' => 7.5],
];
$where = ['title' => 'Emma'];

$found = array_filter($rows, fn ($row) => array_intersect_key($row, $where) == $where);

echo count($found) ? 'found' : 'missing';
```

Check the database for what an action *saved*, and the response for what it *showed*. A good feature
test for "create a book" usually does both.
