## Tables that point at each other

Data is connected: an author writes many books, a book has many reviews, a book can have many tags
and a tag many books. In the database those links are **foreign keys** (lesson 4). In Eloquent they are
**relationships**, methods on the models:

```laravel
class Author extends Model
{
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);           // books.author_id
    }
}

class Book extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);       // books.author_id
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);      // pivot table book_tag
    }
}

$author->books;             // a collection of Book models
$book->author->name;        // one Author
$book->tags()->attach($tagId);
```

## Naming conventions

Eloquent works out the columns from the class names:

- The **foreign key** is the related model's name in snake case plus `_id`: a book belongs to an
  `Author`, so `books.author_id`; a `BookReview` is referred to by `book_review_id`.
- A **many-to-many** link needs a **pivot table** with both ids. Its name is the two model names in
  snake case, singular, in **alphabetical order**, joined by `_`: `Book` and `Tag` → `book_tag`.

```php
$models = ['Tag', 'Book'];
sort($models);

echo strtolower(implode('_', $models));
```

## Loading related data: the N+1 problem

`$author->books` runs a query the first time you read it. That's convenient, and in a loop it's a
trap:

```laravel
$authors = Author::all();               // 1 query
foreach ($authors as $author) {
    echo $author->books->count();       // 1 more query per author!
}
```

With 50 authors, that page runs 51 queries: **N+1**. The fix is **eager loading**: ask for the books up
front with `with()`, and Eloquent fetches them for all authors in a **single** extra query
(`select * from books where author_id in (1, 2, 3, ...)`), then sorts them into each author:

```laravel
$authors = Author::with('books')->get();   // 2 queries, however many authors
```

Sorting the books into their authors is a grouping job:

```php
$authors = [['id' => 1, 'name' => 'Herbert'], ['id' => 2, 'name' => 'Clarke']];
$books = [
    ['title' => 'Dune', 'author_id' => 1],
    ['title' => 'Piranesi', 'author_id' => 2],
    ['title' => 'Children of Dune', 'author_id' => 1],
];

$byAuthor = [];
foreach ($books as $book) {
    $byAuthor[$book['author_id']][] = $book['title'];
}

foreach ($authors as $author) {
    echo $author['name'] . ': ' . implode(', ', $byAuthor[$author['id']] ?? []) . PHP_EOL;
}
```

You can make Laravel complain about forgotten eager loading during development with
`Model::preventLazyLoading()` in a service provider.

## Counting without loading

Often you only need the number: "Herbert (12 books)". `withCount('books')` adds a `books_count`
attribute using a sub-query, without loading any books at all:

```laravel
$authors = Author::withCount('books')->get();
$authors->first()->books_count; // 12
```

## Querying through relationships

Relationships are queries too. `$author->books()` (with parentheses) is a query builder already limited
to that author's books, so you can keep adding conditions: `$author->books()->where('price', '<', 10)->get()`.
And `Author::has('books')` or `whereHas('books', fn ($q) => $q->where('price', '<', 10))` filter
authors by their books.
