## Never return everything

`GET /api/books` is fine with 40 books and a disaster with 400,000. Every collection endpoint should
return one **page** at a time, with enough information for the client to fetch the next.

## Page numbers

The familiar kind is **offset pagination**: `?page=3&per_page=20` skips 40 rows and takes 20. Laravel's
`paginate()` does it, and an API resource collection adds the details:

```laravel
return BookResource::collection(Book::orderBy('id')->paginate($request->integer('per_page', 20)));
```

```json
{
    "data": [ ... ],
    "links": { "first": "...?page=1", "prev": "...?page=2", "next": "...?page=4", "last": "...?page=21" },
    "meta": { "current_page": 3, "last_page": 21, "per_page": 20, "total": 412 }
}
```

Always cap `per_page` (a client asking for a million rows per page is the same disaster), and treat a
missing or silly page number as page 1.

```php
$total = 412;
$perPage = min(100, max(1, 20));
$lastPage = max(1, (int) ceil($total / $perPage));

foreach ([1, 3, 21] as $page) {
    $offset = ($page - 1) * $perPage;
    echo "page {$page}: rows " . ($offset + 1) . '-' . min($total, $offset + $perPage) . " of {$total}, last page {$lastPage}" . PHP_EOL;
}
```

## Cursors

Offsets have two problems on big, busy tables: `OFFSET 400000` makes the database walk past 400,000
rows, and if a book is added while someone is paging, everything shifts and they see a row twice.
**Cursor pagination** instead says "the 20 after the last one I saw": `WHERE id > 980 ORDER BY id LIMIT 20`.
It's fast at any depth and stable while data changes, but it can't jump to page 7 or count the total.

```laravel
return BookResource::collection(Book::orderBy('id')->cursorPaginate(20));
// meta: { "next_cursor": "eyJpZCI6OTgwLCJfcG9pbnRzVG9OZXh0SXRlbXMiOnRydWV9", "prev_cursor": null, ... }
```

The cursor is an opaque string (base64-encoded JSON in Laravel): clients pass it back without looking
inside, so you're free to change what's in it. Infinite scrolling feeds are the classic use.

## Filtering and sorting

Common conventions are `?filter[status]=published` and `?sort=-published_at,title` (a `-` means
descending). As with includes, **allow-list** both: sorting by an unindexed column can be slow, and
filtering on anything could expose data.

```php
$sort = '-published_at,title';
$allowed = ['title', 'published_at', 'price'];

foreach (explode(',', $sort) as $field) {
    $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
    $column = ltrim($field, '-');
    echo in_array($column, $allowed, true) ? "ORDER BY {$column} {$direction}" . PHP_EOL : "not allowed: {$column}" . PHP_EOL;
}
```

## HTTP caching

The fastest request is the one that never reaches your app. HTTP has caching built in:

- `Cache-Control: public, max-age=60` lets browsers and CDNs reuse a response for 60 seconds without
  asking. Use `private` for anything specific to one user, and `no-store` for sensitive data.
- An **ETag** is a fingerprint of the response. The client sends it back as `If-None-Match`; if
  nothing changed, you answer `304 Not Modified` with no body, saving the bandwidth (and, if you can
  check cheaply, the work).

```php
$body = json_encode(['data' => ['id' => 7, 'title' => 'Dune']]);
$etag = '"' . md5($body) . '"';

foreach ([null, $etag, '"stale"'] as $ifNoneMatch) {
    echo ($ifNoneMatch === $etag ? '304 Not Modified' : "200 OK, ETag {$etag}") . PHP_EOL;
}
```

Laravel's `cache.headers` middleware sets these: `->middleware('cache.headers:public;max_age=60;etag')`.

## Versions

Sooner or later you'll want to change something incompatibly: rename a field, change a status. Clients
already in the wild can't change at the same moment, so you run **two versions** side by side for a
while:

- In the URL: `/api/v1/books` and `/api/v2/books`. Easy to see, test and route; the most common choice.
- In a header: `Accept: application/vnd.cornerbooks.v2+json`. Keeps URLs stable but is harder to try in a browser.

Most changes don't need a new version: **adding** a field or an endpoint breaks nobody. Removing or
renaming does. When an old version is going away, say so in advance with a `Deprecation` and a `Sunset`
header carrying the date, and in your docs. Describing the API in an **OpenAPI** file lets you
generate documentation and client libraries from it.
