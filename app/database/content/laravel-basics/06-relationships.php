<?php

// Lesson 6 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'relationships',
    'title' => 'Relationships',
    'summary' => 'Connecting models: `hasMany`, `belongsTo` and `belongsToMany`, foreign key and pivot table names, the N+1 problem and eager loading with `with()`, and `withCount()`.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Count the queries for more authors.
        $queries = 0;
        $authors = [['id' => 1, 'name' => 'Herbert'], ['id' => 2, 'name' => 'Clarke'], ['id' => 3, 'name' => 'Asimov']];
        $books = [['title' => 'Dune', 'author_id' => 1], ['title' => 'Piranesi', 'author_id' => 2]];

        $queries++; // select * from authors
        foreach ($authors as $author) {
            $queries++; // select * from books where author_id = ?
            $theirs = array_filter($books, fn ($book) => $book['author_id'] === $author['id']);
            echo $author['name'] . ': ' . count($theirs) . PHP_EOL;
        }

        echo "$queries queries";
        PHP,

    'quiz' => [
        [
            'question' => 'An author writes many books. Which relationship goes on the `Author` model?',
            'options' => ['`belongsTo(Book::class)`', '`hasMany(Book::class)`', '`belongsToMany(Book::class)`', '`hasOne(Book::class)`'],
            'correct_option' => 1,
            'explanation' => 'The author has many books; each book `belongsTo` its author.',
        ],
        [
            'question' => 'Which column links a book to its author by default?',
            'options' => ['`authors.book_id`', '`books.author_id`', '`books.author`', '`author_book.id`'],
            'correct_option' => 1,
            'explanation' => 'The side that "belongs to" holds the foreign key, named after the other model plus `_id`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $pairs = [['Tag', 'Book'], ['Genre', 'Author'], ['Shelf', 'Book']];

                foreach ($pairs as $models) {
                    sort($models);
                    echo strtolower(implode('_', $models)) . ' ';
                }
                PHP,
            'options' => [
                '`tag_book genre_author shelf_book `',
                '`book_tag author_genre book_shelf `',
                '`books_tags authors_genres books_shelves `',
                '`book_tag genre_author book_shelf `',
            ],
            'correct_option' => 1,
            'explanation' => 'Pivot table names are the two models in alphabetical order, singular, in snake case.',
        ],
        [
            'question' => 'A page lists 50 authors and shows each one\'s books with `$author->books`. How many queries does it run without eager loading?',
            'options' => ['1', '2', '50', '51'],
            'correct_option' => 3,
            'explanation' => 'One for the authors, then one per author for their books: N+1.',
        ],
        [
            'question' => 'How many queries does `Author::with(\'books\')->get()` run for 50 authors?',
            'options' => ['1', '2', '50', '51'],
            'correct_option' => 1,
            'explanation' => 'One for the authors and one for all their books together, using `where author_id in (...)`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $books = [
                    ['title' => 'Dune', 'author_id' => 1],
                    ['title' => 'Piranesi', 'author_id' => 2],
                    ['title' => 'Children of Dune', 'author_id' => 1],
                ];

                $counts = array_count_values(array_column($books, 'author_id'));

                echo json_encode($counts);
                PHP,
            'options' => ['`[2,1]`', '`{"1":2,"2":1}`', '`{"1":1,"2":1}`', '`[1,2,1]`'],
            'correct_option' => 1,
            'explanation' => 'Two books by author 1 and one by author 2, which is what `withCount(\'books\')` works out in SQL.',
        ],
        [
            'question' => 'What is the difference between `$author->books` and `$author->books()`?',
            'options' => [
                'None',
                '`->books` gives the loaded collection of books; `->books()` gives a query you can add conditions to',
                '`->books()` is deprecated',
                '`->books` only works with eager loading',
            ],
            'correct_option' => 1,
            'explanation' => 'With parentheses you get the relationship as a query builder, for example `$author->books()->where(\'price\', \'<\', 10)->get()`.',
        ],
        [
            'question' => 'What does `Model::preventLazyLoading()` do during development?',
            'options' => [
                'Makes every query slower',
                'Throws an error when a relationship is loaded lazily, so you notice missing `with()` calls',
                'Disables relationships',
                'Caches every query',
            ],
            'correct_option' => 1,
            'explanation' => 'It turns hidden N+1 problems into loud errors before they reach production.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Foreign key names',
            'prompt' => 'Write `foreignKeyFor(string $class): string`, giving the foreign key column that refers to a model: the class name in snake case plus `_id`. `Author` → `author_id`, `BookReview` → `book_review_id`.',
            'starter_code' => <<<'PHP'
                <?php

                function foreignKeyFor(string $class): string
                {
                    return $class . '_id';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function foreignKeyFor(string $class): string
                {
                    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . '_id';
                }
                PHP,
            'hint' => 'Put `_` before every capital letter except the first, lower-case it, then add `_id`.',
            'checks' => [
                ['label' => 'Simple names', 'code' => "return expect_same([foreignKeyFor('Author'), foreignKeyFor('Book')], ['author_id', 'book_id']);"],
                ['label' => 'Longer names', 'code' => "return expect_same([foreignKeyFor('BookReview'), foreignKeyFor('LibraryBranchManager')], ['book_review_id', 'library_branch_manager_id']);"],
            ],
        ],
        [
            'title' => 'Pivot table names',
            'prompt' => 'Write `pivotTable(string $first, string $second): string`: both model names in snake case, sorted alphabetically, joined with `_`. `Book` and `Tag` → `book_tag`; `Tag` and `BookSeries` → `book_series_tag`.',
            'starter_code' => <<<'PHP'
                <?php

                function pivotTable(string $first, string $second): string
                {
                    return strtolower($first . '_' . $second);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pivotTable(string $first, string $second): string
                {
                    $names = array_map(fn (string $class) => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)), [$first, $second]);
                    sort($names);

                    return implode('_', $names);
                }
                PHP,
            'hint' => 'Turn both names into snake case first, then `sort()` them, then join.',
            'checks' => [
                ['label' => 'Alphabetical order', 'code' => "return expect_same([pivotTable('Tag', 'Book'), pivotTable('Book', 'Tag'), pivotTable('Genre', 'Author')], ['book_tag', 'book_tag', 'author_genre']);"],
                ['label' => 'Snake case names', 'code' => "return expect_same(pivotTable('Tag', 'BookSeries'), 'book_series_tag');"],
            ],
        ],
        [
            'title' => 'hasMany and belongsTo',
            'prompt' => 'Write `booksOf(array $author, array $books): array`, returning the books whose `author_id` is the author\'s `id` (as a list, in their original order), and `authorOf(array $book, array $authors): ?array`, returning the author with the book\'s `author_id`, or `null`. Books and authors are lists of arrays.',
            'starter_code' => <<<'PHP'
                <?php

                function booksOf(array $author, array $books): array
                {
                    return $books;
                }

                function authorOf(array $book, array $authors): ?array
                {
                    return $authors[0] ?? null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function booksOf(array $author, array $books): array
                {
                    return array_values(array_filter($books, fn (array $book) => $book['author_id'] === $author['id']));
                }

                function authorOf(array $book, array $authors): ?array
                {
                    foreach ($authors as $author) {
                        if ($author['id'] === $book['author_id']) {
                            return $author;
                        }
                    }

                    return null;
                }
                PHP,
            'hint' => '`array_filter()` then `array_values()` for the books; a loop that returns the first matching author.',
            'checks' => [
                ['label' => 'An author\'s books', 'code' => "\$books = [['title' => 'Dune', 'author_id' => 1], ['title' => 'Piranesi', 'author_id' => 2], ['title' => 'Children of Dune', 'author_id' => 1]];\nreturn expect_same([array_column(booksOf(['id' => 1], \$books), 'title'), booksOf(['id' => 3], \$books)], [['Dune', 'Children of Dune'], []]);"],
                ['label' => 'A book\'s author', 'code' => "\$authors = [['id' => 1, 'name' => 'Herbert'], ['id' => 2, 'name' => 'Clarke']];\nreturn expect_same([authorOf(['author_id' => 2], \$authors), authorOf(['author_id' => 9], \$authors)], [['id' => 2, 'name' => 'Clarke'], null]);"],
            ],
        ],
        [
            'title' => 'Eager loading',
            'prompt' => '`Db` counts its queries. Write `authorsWithBooks(Db $db): array`, returning every author with a `books` key holding their books (a list, possibly empty), using **exactly two** queries: `$db->select(\'authors\')` once, and `$db->selectWhereIn(\'books\', \'author_id\', $ids)` once with all the authors\' ids.',
            'starter_code' => <<<'PHP'
                <?php

                class Db
                {
                    public int $queries = 0;

                    public function __construct(private array $tables) {}

                    public function select(string $table): array
                    {
                        $this->queries++;
                        return $this->tables[$table];
                    }

                    public function selectWhere(string $table, string $column, mixed $value): array
                    {
                        $this->queries++;
                        return array_values(array_filter($this->tables[$table], fn ($row) => $row[$column] === $value));
                    }

                    public function selectWhereIn(string $table, string $column, array $values): array
                    {
                        $this->queries++;
                        return array_values(array_filter($this->tables[$table], fn ($row) => in_array($row[$column], $values, true)));
                    }
                }

                function authorsWithBooks(Db $db): array
                {
                    $authors = $db->select('authors');
                    foreach ($authors as $i => $author) {
                        $authors[$i]['books'] = $db->selectWhere('books', 'author_id', $author['id']);
                    }

                    return $authors;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Db
                {
                    public int $queries = 0;

                    public function __construct(private array $tables) {}

                    public function select(string $table): array
                    {
                        $this->queries++;
                        return $this->tables[$table];
                    }

                    public function selectWhere(string $table, string $column, mixed $value): array
                    {
                        $this->queries++;
                        return array_values(array_filter($this->tables[$table], fn ($row) => $row[$column] === $value));
                    }

                    public function selectWhereIn(string $table, string $column, array $values): array
                    {
                        $this->queries++;
                        return array_values(array_filter($this->tables[$table], fn ($row) => in_array($row[$column], $values, true)));
                    }
                }

                function authorsWithBooks(Db $db): array
                {
                    $authors = $db->select('authors');
                    $books = $db->selectWhereIn('books', 'author_id', array_column($authors, 'id'));

                    $byAuthor = [];
                    foreach ($books as $book) {
                        $byAuthor[$book['author_id']][] = $book;
                    }

                    foreach ($authors as $i => $author) {
                        $authors[$i]['books'] = $byAuthor[$author['id']] ?? [];
                    }

                    return $authors;
                }
                PHP,
            'hint' => 'Fetch the authors, then all their books in one `selectWhereIn()` using `array_column($authors, \'id\')`. Group the books by `author_id`, then attach each group.',
            'checks' => [
                ['label' => 'Books are attached to their authors', 'code' => "\$db = new Db(['authors' => [['id' => 1, 'name' => 'Herbert'], ['id' => 2, 'name' => 'Clarke'], ['id' => 3, 'name' => 'Asimov']], 'books' => [['title' => 'Dune', 'author_id' => 1], ['title' => 'Piranesi', 'author_id' => 2], ['title' => 'Children of Dune', 'author_id' => 1]]]);\n\$result = authorsWithBooks(\$db);\nreturn expect_same(array_map(fn (\$a) => [\$a['name'], array_column(\$a['books'], 'title')], \$result), [['Herbert', ['Dune', 'Children of Dune']], ['Clarke', ['Piranesi']], ['Asimov', []]]);"],
                ['label' => 'Exactly two queries', 'code' => "\$authors = array_map(fn (\$i) => ['id' => \$i, 'name' => \"Author \$i\"], range(1, 50));\n\$db = new Db(['authors' => \$authors, 'books' => [['title' => 'X', 'author_id' => 7]]]);\nauthorsWithBooks(\$db);\nreturn expect_same(\$db->queries, 2);"],
            ],
        ],
        [
            'title' => 'withCount',
            'prompt' => 'Write `withCount(array $authors, array $books, string $relation = \'books\'): array`, adding a `{relation}_count` key (like `books_count`) to each author with the number of books whose `author_id` is theirs, including 0. Count all the books in one pass, not once per author.',
            'starter_code' => <<<'PHP'
                <?php

                function withCount(array $authors, array $books, string $relation = 'books'): array
                {
                    return $authors;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function withCount(array $authors, array $books, string $relation = 'books'): array
                {
                    $counts = array_count_values(array_column($books, 'author_id'));

                    return array_map(fn (array $author) => [...$author, "{$relation}_count" => $counts[$author['id']] ?? 0], $authors);
                }
                PHP,
            'hint' => '`array_count_values(array_column($books, \'author_id\'))` counts every author\'s books in one go.',
            'checks' => [
                ['label' => 'Counts are added', 'code' => "\$books = [['author_id' => 1], ['author_id' => 2], ['author_id' => 1]];\nreturn expect_same(withCount([['id' => 1, 'name' => 'Herbert'], ['id' => 2, 'name' => 'Clarke'], ['id' => 3, 'name' => 'Asimov']], \$books), [['id' => 1, 'name' => 'Herbert', 'books_count' => 2], ['id' => 2, 'name' => 'Clarke', 'books_count' => 1], ['id' => 3, 'name' => 'Asimov', 'books_count' => 0]]);"],
                ['label' => 'Other relation names', 'code' => "return expect_same(withCount([['id' => 5]], [['author_id' => 5]], 'reviews'), [['id' => 5, 'reviews_count' => 1]]);"],
            ],
        ],
        [
            'title' => 'Many to many',
            'prompt' => 'Books and tags are linked through a pivot list of `[\'book_id\' => ..., \'tag_id\' => ...]` rows. Write `tagsOf(int $bookId, array $pivot, array $tags): array`, returning the names of the book\'s tags sorted A to Z (`$tags` maps tag ids to names), and `booksTagged(string $name, array $pivot, array $tags): array`, returning the sorted ids of books with that tag.',
            'starter_code' => <<<'PHP'
                <?php

                function tagsOf(int $bookId, array $pivot, array $tags): array
                {
                    return array_values($tags);
                }

                function booksTagged(string $name, array $pivot, array $tags): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function tagsOf(int $bookId, array $pivot, array $tags): array
                {
                    $names = [];
                    foreach ($pivot as $row) {
                        if ($row['book_id'] === $bookId && isset($tags[$row['tag_id']])) {
                            $names[] = $tags[$row['tag_id']];
                        }
                    }
                    sort($names);

                    return $names;
                }

                function booksTagged(string $name, array $pivot, array $tags): array
                {
                    $tagIds = array_keys($tags, $name, true);
                    $bookIds = [];
                    foreach ($pivot as $row) {
                        if (in_array($row['tag_id'], $tagIds, true)) {
                            $bookIds[] = $row['book_id'];
                        }
                    }
                    $bookIds = array_values(array_unique($bookIds));
                    sort($bookIds);

                    return $bookIds;
                }
                PHP,
            'hint' => 'For `tagsOf()`, walk the pivot rows for that book and look each tag id up in `$tags`. For `booksTagged()`, find the tag\'s id first (`array_keys($tags, $name, true)`), then collect the matching book ids.',
            'checks' => [
                ['label' => 'A book\'s tags', 'code' => "\$tags = [1 => 'sci-fi', 2 => 'classic', 3 => 'fantasy'];\n\$pivot = [['book_id' => 7, 'tag_id' => 2], ['book_id' => 7, 'tag_id' => 1], ['book_id' => 8, 'tag_id' => 3]];\nreturn expect_same([tagsOf(7, \$pivot, \$tags), tagsOf(9, \$pivot, \$tags)], [['classic', 'sci-fi'], []]);"],
                ['label' => 'Books with a tag', 'code' => "\$tags = [1 => 'sci-fi', 2 => 'classic', 3 => 'fantasy'];\n\$pivot = [['book_id' => 9, 'tag_id' => 1], ['book_id' => 7, 'tag_id' => 1], ['book_id' => 8, 'tag_id' => 3], ['book_id' => 7, 'tag_id' => 2]];\nreturn expect_same([booksTagged('sci-fi', \$pivot, \$tags), booksTagged('horror', \$pivot, \$tags)], [[7, 9], []]);"],
            ],
        ],
    ],
];
