## Testing a whole request

The PHP testing course tested single functions and classes. A Laravel app also needs tests that go
through the whole stack: route, middleware, controller, database, view. These are **feature tests**,
and they're where most of a Laravel app's tests live. They don't start a web server: Laravel builds a
request in memory, passes it through the app exactly as a real one would, and hands you the response
to check.

```laravel
// tests/Feature/BookPageTest.php (make it with: php artisan make:test BookPageTest)
class BookPageTest extends TestCase
{
    public function test_the_book_list_shows_titles(): void
    {
        $response = $this->get('/books');

        $response->assertOk();
        $response->assertSee('Dune');
    }
}
```

Run them with `php artisan test`. Unit tests (in `tests/Unit`) are for code that doesn't need the
framework; feature tests (in `tests/Feature`) boot the app first.

## Pest

Most new Laravel projects use **Pest**, which runs on top of PHPUnit with a shorter syntax: a test is
a function call with a description, and `$this` is still the test case:

```laravel
// tests/Feature/BookPageTest.php
it('shows the titles on the book list', function () {
    $this->get('/books')
        ->assertOk()
        ->assertSee('Dune');
});

test('guests cannot open the admin area', function () {
    $this->get('/admin')->assertRedirect('/login');
});
```

Both styles run with `php artisan test`, and the assertions are the same. This course uses Pest in its
examples; lesson 4 looks at Pest's own features.

## Making requests

There's a method per HTTP verb, plus JSON versions that send a JSON body and ask for a JSON response:

```laravel
$this->get('/books');
$this->post('/books', ['title' => 'Dune', 'price' => 9.99]);
$this->put('/books/1', [...]);
$this->delete('/books/1');

$this->getJson('/api/books');
$this->postJson('/api/books', ['title' => 'Dune']);

$this->withHeaders(['Accept-Language' => 'de'])->get('/');
```

## Asserting on the response

The response object has dozens of assertions, and each returns the response, so they chain:

```laravel
$response->assertStatus(201);        // or assertOk() 200, assertCreated() 201,
                                     // assertNoContent() 204, assertNotFound() 404,
                                     // assertForbidden() 403, assertUnprocessable() 422
$response->assertSee('Dune');        // the text appears in the body (HTML-escaped first)
$response->assertDontSee('Secret');
$response->assertRedirect('/books'); // a 3xx with that Location
$response->assertJson(['title' => 'Dune']);        // these keys and values are in the JSON
$response->assertJsonPath('data.0.title', 'Dune'); // one value, found by a dot path
$response->assertJsonCount(3, 'data');
$response->assertSessionHasErrors(['title']);      // validation failed for title
$response->assertHeader('Content-Type', 'application/json');
```

None of this is magic. An assertion compares something and throws if it's wrong, with a message saying
what it expected. Here's the idea in plain PHP, with a response as an array:

```php
function assertStatus(array $response, int $expected): void
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException("Expected response status code [{$expected}] but received {$response['status']}.");
    }
}

$response = ['status' => 404, 'headers' => [], 'body' => 'Not Found'];

try {
    assertStatus($response, 200);
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
```

## assertSee escapes

`assertSee('Tom & Jerry')` looks for `Tom &amp; Jerry`, because that's how Blade's `{{ }}` writes the
text into HTML. So it passes when the page *shows* the text, whatever the HTML looks like. Pass `false`
as the second argument to search the raw HTML instead: `assertSee('<h1>', false)`.

```php
$html = '<h1>Tom &amp; Jerry</h1>';

var_dump(str_contains($html, htmlspecialchars('Tom & Jerry'))); // what assertSee checks
var_dump(str_contains($html, 'Tom & Jerry'));                   // a naive search would fail
```

## JSON paths

`assertJsonPath('data.0.title', 'Dune')` walks the decoded JSON one key at a time: `data`, then item
`0`, then `title`. It's the same dot notation as `data_get()` and `config('app.name')`:

```php
$json = json_decode('{"data": [{"title": "Dune", "author": {"name": "Herbert"}}]}', true);

$value = $json;
foreach (explode('.', 'data.0.author.name') as $key) {
    $value = $value[$key];
}

echo $value;
```

## What a good feature test looks like

Feature tests follow the same arrange, act, assert shape as any test:

```laravel
it('creates a book', function () {
    $admin = User::factory()->admin()->create();           // arrange (lesson 2)

    $response = $this->actingAs($admin)                    // act
        ->post('/books', ['title' => 'Dune', 'price' => 9.99]);

    $response->assertRedirect('/books');                   // assert
    $this->assertDatabaseHas('books', ['title' => 'Dune']);
});
```

Test the behaviour a user or client sees (status, page content, JSON, what was saved), not how the
controller is written inside. Then you can refactor the controller freely and the tests still tell
you whether it works.
