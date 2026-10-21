## Your JSON is a promise

Once a mobile app is in people's pockets, you can't change what your API returns without breaking it.
So the JSON shape deserves as much care as a database schema. Returning a model straight from a
controller works, but it ties the API to your table: rename a column and every client breaks, add a
`password` column and it leaks unless you remember `$hidden`. **API resources** put a deliberate layer
in between.

## API resources

A resource class turns a model into exactly the array clients should see:

```laravel
// php artisan make:resource BookResource
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => ['amount' => $this->price_cents, 'currency' => 'GBP'],
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => new AuthorResource($this->whenLoaded('author')),
            'reviews_count' => $this->whenCounted('reviews'),
            'is_mine' => $this->when($request->user()?->id === $this->user_id, true),
        ];
    }
}

// in a controller
return new BookResource($book);                                  // { "data": { ... } }
return BookResource::collection(Book::with('author')->paginate()); // { "data": [...], "links": ..., "meta": ... }
```

The output is wrapped in a `data` key, which leaves room beside it for `meta` and `links`.

## Habits that keep an API pleasant

- **Consistent keys.** Pick `snake_case` (Laravel's habit) or `camelCase` and use it everywhere.
- **Dates in ISO 8601** with a time zone: `2026-10-21T09:00:00+00:00`. Never `21/10/26`.
- **Money as integers** of the smallest unit (pence, cents) plus a currency, never a float: `0.1 + 0.2`
  isn't `0.3` in floating point.
- **Stable ids**, and `null` rather than a missing key for "no value", so clients can rely on the shape.
- **No secrets.** A resource lists fields one by one, so a new column never leaks by accident.

```php
$book = ['id' => 7, 'title' => 'Dune', 'price' => 9.99, 'published_at' => '2026-10-21 09:00:00'];

$resource = [
    'id' => $book['id'],
    'title' => $book['title'],
    'price' => ['amount' => (int) round($book['price'] * 100), 'currency' => 'GBP'],
    'published_at' => (new DateTimeImmutable($book['published_at'], new DateTimeZone('UTC')))->format(DATE_ATOM),
];

echo json_encode(['data' => $resource], JSON_PRETTY_PRINT);
```

## Only what was loaded

`whenLoaded('author')` includes the author only if the controller eager-loaded it. That means the
resource never triggers a surprise query per book (the N+1 problem from Laravel basics), and the
controller decides how much to send. `whenCounted()` does the same for `withCount()`, and
`when($condition, $value)` leaves a key out entirely when the condition is false.

```php
$withAuthor = ['id' => 7, 'title' => 'Dune', 'author' => ['name' => 'Frank Herbert']];
$withoutAuthor = ['id' => 8, 'title' => 'Emma'];

foreach ([$withAuthor, $withoutAuthor] as $book) {
    $data = ['id' => $book['id'], 'title' => $book['title']];
    if (array_key_exists('author', $book)) {
        $data['author'] = $book['author'];
    }
    echo json_encode($data) . PHP_EOL;
}
```

## Letting clients choose

Clients often want less (a list page needs titles, not descriptions) or more (the author too, in one
request). Two conventions, from the JSON:API style, are common:

- `?include=author,reviews.user` asks for related resources. Check each against an **allow-list**, or
  clients can make you load anything.
- `?fields=id,title` asks for only some fields: **sparse fieldsets**.

```laravel
$includes = array_intersect(explode(',', $request->query('include', '')), ['author', 'reviews']);

return BookResource::collection(Book::with($includes)->paginate());
```

The package `spatie/laravel-query-builder` does this (and filtering and sorting, from lesson 5) with
allow-lists built in.
