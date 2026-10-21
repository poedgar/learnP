<?php

// Lesson 5 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'eloquent-models',
    'title' => 'Eloquent models',
    'summary' => 'Working with rows as objects: model conventions for tables, keys and timestamps, reading with `find()` and `where()`, creating and updating with `$fillable`, `firstOrCreate()` and soft deletes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try finding the books under £9.
        $books = [
            1 => ['id' => 1, 'title' => 'Dune', 'price' => 9.99],
            2 => ['id' => 2, 'title' => 'Piranesi', 'price' => 8.50],
            3 => ['id' => 3, 'title' => 'Foundation', 'price' => 7.25],
        ];

        $find = fn (int $id) => $books[$id] ?? null;
        $where = fn (string $column, mixed $value) => array_values(array_filter($books, fn ($row) => $row[$column] === $value));

        print_r($find(2));
        print_r($where('title', 'Dune'));
        PHP,

    'quiz' => [
        [
            'question' => 'Which table does the model `BookReview` use by default?',
            'options' => ['`BookReview`', '`book_review`', '`book_reviews`', '`bookreviews`'],
            'correct_option' => 2,
            'explanation' => 'Snake case, then plural.',
        ],
        [
            'question' => 'What does `Book::find(99)` return when there is no book 99?',
            'options' => ['An empty `Book`', '`null`', '`false`', 'It throws a 404'],
            'correct_option' => 1,
            'explanation' => '`find()` returns `null`; `findOrFail()` throws an exception that becomes a 404.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                foreach (['Book', 'Author', 'BookReview', 'ISBNRecord'] as $class) {
                    echo strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . ' ';
                }
                PHP,
            'options' => [
                '`book author book_review isbn_record `',
                '`book author book_review i_s_b_n_record `',
                '`book author bookreview isbnrecord `',
                '`_book _author _book_review _i_s_b_n_record `',
            ],
            'correct_option' => 1,
            'explanation' => 'Every capital except the first gets an underscore before it, so acronyms come out letter by letter. Laravel\'s own `Str::snake()` does the same.',
        ],
        [
            'question' => 'What do `get()` and `first()` return at the end of a query?',
            'options' => [
                'Both return an array',
                '`get()` returns a collection of models; `first()` returns one model or `null`',
                '`get()` returns the SQL; `first()` runs it',
                'They are the same',
            ],
            'correct_option' => 1,
            'explanation' => '`get()` for many rows, `first()` for the first matching one.',
        ],
        [
            'question' => 'A model has `$fillable = [\'title\', \'price\']`. What does `Book::create([\'title\' => \'Dune\', \'featured\' => true])` save?',
            'options' => [
                'Both columns',
                'Only `title`; `featured` isn\'t fillable, so it\'s ignored',
                'Nothing; it throws',
                'Only `featured`',
            ],
            'correct_option' => 1,
            'explanation' => '`$fillable` lists the columns that may be filled from an array, which protects columns the form shouldn\'t touch.',
        ],
        [
            'question' => 'What does `Book::firstOrCreate([\'isbn\' => \'9780441172719\'], [\'title\' => \'Dune\'])` do when that ISBN already exists?',
            'options' => [
                'Creates a second copy',
                'Returns the existing book and creates nothing',
                'Updates its title to Dune',
                'Throws an exception',
            ],
            'correct_option' => 1,
            'explanation' => 'The first array is what to look for; the second is only used when a new row has to be created.',
        ],
        [
            'question' => 'With `SoftDeletes`, what does `$book->delete()` do?',
            'options' => [
                'Removes the row for good',
                'Sets `deleted_at`, so the book is hidden from normal queries but can be restored',
                'Moves the row to another table',
                'Sets `price` to 0',
            ],
            'correct_option' => 1,
            'explanation' => '`withTrashed()` includes soft-deleted rows again, and `restore()` clears `deleted_at`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $rows = [
                    ['title' => 'Dune', 'deleted_at' => null],
                    ['title' => 'Piranesi', 'deleted_at' => '2026-10-21'],
                    ['title' => 'Foundation', 'deleted_at' => null],
                ];

                echo count(array_filter($rows, fn ($row) => $row['deleted_at'] === null)) . ' of ' . count($rows);
                PHP,
            'options' => ['`3 of 3`', '`2 of 3`', '`1 of 3`', '`0 of 3`'],
            'correct_option' => 1,
            'explanation' => 'Normal queries skip soft-deleted rows, so only two books are visible.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Table names',
            'prompt' => 'Write `tableName(string $class): string`, following Eloquent\'s convention: put `_` before each capital letter except the first and lower-case everything, then make it plural. Plural rules: a final consonant + `y` becomes `ies`; a final `s`, `x`, `ch` or `sh` gets `es`; anything else gets `s`.',
            'starter_code' => <<<'PHP'
                <?php

                function tableName(string $class): string
                {
                    return strtolower($class) . 's';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function tableName(string $class): string
                {
                    $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

                    return match (true) {
                        preg_match('/[^aeiou]y$/', $snake) === 1 => substr($snake, 0, -1) . 'ies',
                        preg_match('/(s|x|ch|sh)$/', $snake) === 1 => $snake . 'es',
                        default => $snake . 's',
                    };
                }
                PHP,
            'hint' => 'Snake case first, as in the lesson. Then a `match (true)` with a couple of `preg_match()` checks picks the plural ending.',
            'checks' => [
                ['label' => 'Simple names', 'code' => "return expect_same([tableName('Book'), tableName('Author'), tableName('BookReview')], ['books', 'authors', 'book_reviews']);"],
                ['label' => 'Words ending in y', 'code' => "return expect_same([tableName('Category'), tableName('Day'), tableName('LibraryCopy')], ['categories', 'days', 'library_copies']);"],
                ['label' => 'Words ending in s, x, ch and sh', 'code' => "return expect_same([tableName('Address'), tableName('Box'), tableName('Branch'), tableName('Wish')], ['addresses', 'boxes', 'branches', 'wishes']);"],
            ],
        ],
        [
            'title' => 'find, where and first',
            'prompt' => 'Finish `Table`, an in-memory stand-in for a database table holding rows keyed by id. `find(int $id)` returns the row or `null`. `where(string $column, string $operator, mixed $value)` returns the matching rows as a list, for the operators `=`, `!=`, `<`, `<=`, `>` and `>=`. `first(string $column, mixed $value)` returns the first row whose column equals the value, or `null`.',
            'starter_code' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(private array $rows) {}

                    public function find(int $id): ?array
                    {
                        return null;
                    }

                    public function where(string $column, string $operator, mixed $value): array
                    {
                        return [];
                    }

                    public function first(string $column, mixed $value): ?array
                    {
                        return null;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(private array $rows) {}

                    public function find(int $id): ?array
                    {
                        return $this->rows[$id] ?? null;
                    }

                    public function where(string $column, string $operator, mixed $value): array
                    {
                        $matches = fn (mixed $actual): bool => match ($operator) {
                            '=' => $actual == $value,
                            '!=' => $actual != $value,
                            '<' => $actual < $value,
                            '<=' => $actual <= $value,
                            '>' => $actual > $value,
                            '>=' => $actual >= $value,
                        };

                        return array_values(array_filter($this->rows, fn (array $row) => $matches($row[$column] ?? null)));
                    }

                    public function first(string $column, mixed $value): ?array
                    {
                        return $this->where($column, '=', $value)[0] ?? null;
                    }
                }
                PHP,
            'hint' => 'A `match` on the operator decides how to compare. `first()` can reuse `where()` and take element `[0]`.',
            'checks' => [
                ['label' => 'find()', 'code' => "\$t = new Table([7 => ['id' => 7, 'title' => 'Dune']]);\nreturn expect_same([\$t->find(7), \$t->find(8)], [['id' => 7, 'title' => 'Dune'], null]);"],
                ['label' => 'where() with operators', 'code' => "\$t = new Table([1 => ['id' => 1, 'price' => 9.99], 2 => ['id' => 2, 'price' => 8.5], 3 => ['id' => 3, 'price' => 12.0]]);\nreturn expect_same([array_column(\$t->where('price', '<', 10), 'id'), array_column(\$t->where('price', '>=', 9.99), 'id'), array_column(\$t->where('id', '!=', 2), 'id')], [[1, 2], [1, 3], [1, 3]]);"],
                ['label' => 'first()', 'code' => "\$t = new Table([1 => ['id' => 1, 'author' => 'Herbert'], 2 => ['id' => 2, 'author' => 'Clarke'], 3 => ['id' => 3, 'author' => 'Herbert']]);\nreturn expect_same([\$t->first('author', 'Herbert')['id'] ?? null, \$t->first('author', 'Asimov')], [1, null]);"],
            ],
        ],
        [
            'title' => 'create()',
            'prompt' => 'Add `create(array $attributes, string $now): array` to `Table`. It keeps only the keys in `$this->fillable`, gives the row the next id (one more than the highest, or 1 for an empty table), sets `created_at` and `updated_at` to `$now`, stores the row and returns it with `id` first.',
            'starter_code' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(public array $rows = [], private array $fillable = []) {}

                    public function create(array $attributes, string $now): array
                    {
                        $this->rows[] = $attributes;

                        return $attributes;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(public array $rows = [], private array $fillable = []) {}

                    public function create(array $attributes, string $now): array
                    {
                        $id = $this->rows === [] ? 1 : max(array_keys($this->rows)) + 1;
                        $allowed = array_intersect_key($attributes, array_flip($this->fillable));

                        $row = ['id' => $id, ...$allowed, 'created_at' => $now, 'updated_at' => $now];
                        $this->rows[$id] = $row;

                        return $row;
                    }
                }
                PHP,
            'hint' => '`array_intersect_key($attributes, array_flip($this->fillable))` keeps only fillable keys. Build the row with `id` first, then the attributes, then the timestamps.',
            'checks' => [
                ['label' => 'A new row with an id and timestamps', 'code' => "\$t = new Table([], ['title', 'price']);\nreturn expect_same(\$t->create(['title' => 'Dune', 'price' => 9.99], '2026-10-21 09:00:00'), ['id' => 1, 'title' => 'Dune', 'price' => 9.99, 'created_at' => '2026-10-21 09:00:00', 'updated_at' => '2026-10-21 09:00:00']);"],
                ['label' => 'The next id', 'code' => "\$t = new Table([4 => ['id' => 4], 9 => ['id' => 9]], ['title']);\n\$row = \$t->create(['title' => 'Piranesi'], 'now');\nreturn expect_same([\$row['id'], array_keys(\$t->rows)], [10, [4, 9, 10]]);"],
                ['label' => 'Only fillable columns', 'code' => "\$t = new Table([], ['title']);\nreturn expect_same(\$t->create(['title' => 'Dune', 'featured' => true, 'id' => 99], 'now'), ['id' => 1, 'title' => 'Dune', 'created_at' => 'now', 'updated_at' => 'now']);"],
            ],
        ],
        [
            'title' => 'update()',
            'prompt' => 'Add `update(int $id, array $attributes, string $now): bool` to `Table`. If there\'s no such row, return `false`. Otherwise copy only fillable keys onto the row. If any value actually changed, set `updated_at` to `$now`; if nothing changed, leave `updated_at` alone, as Eloquent does. Return `true`.',
            'starter_code' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(public array $rows = [], private array $fillable = []) {}

                    public function update(int $id, array $attributes, string $now): bool
                    {
                        $this->rows[$id] = [...$this->rows[$id], ...$attributes, 'updated_at' => $now];

                        return true;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Table
                {
                    public function __construct(public array $rows = [], private array $fillable = []) {}

                    public function update(int $id, array $attributes, string $now): bool
                    {
                        if (! isset($this->rows[$id])) {
                            return false;
                        }

                        $row = $this->rows[$id];
                        $changed = false;
                        foreach (array_intersect_key($attributes, array_flip($this->fillable)) as $key => $value) {
                            if (! array_key_exists($key, $row) || $row[$key] !== $value) {
                                $row[$key] = $value;
                                $changed = true;
                            }
                        }

                        if ($changed) {
                            $row['updated_at'] = $now;
                        }
                        $this->rows[$id] = $row;

                        return true;
                    }
                }
                PHP,
            'hint' => 'Loop over the fillable attributes, remembering whether any value differs (`!==`) from the row\'s. Only touch `updated_at` if something did.',
            'checks' => [
                ['label' => 'Changes are saved with a new updated_at', 'code' => "\$t = new Table([7 => ['id' => 7, 'price' => 9.99, 'updated_at' => 'old']], ['price']);\nreturn expect_same([\$t->update(7, ['price' => 7.99], 'new'), \$t->rows[7]], [true, ['id' => 7, 'price' => 7.99, 'updated_at' => 'new']]);"],
                ['label' => 'Unknown rows', 'code' => "\$t = new Table([], ['price']);\nreturn expect_same([\$t->update(7, ['price' => 1], 'now'), \$t->rows], [false, []]);"],
                ['label' => 'Only fillable columns change', 'code' => "\$t = new Table([7 => ['id' => 7, 'price' => 9.99, 'updated_at' => 'old']], ['price']);\n\$t->update(7, ['id' => 1, 'featured' => true], 'new');\nreturn expect_same(\$t->rows[7], ['id' => 7, 'price' => 9.99, 'updated_at' => 'old']);"],
                ['label' => 'No change, no new timestamp', 'code' => "\$t = new Table([7 => ['id' => 7, 'price' => 9.99, 'updated_at' => 'old']], ['price']);\n\$t->update(7, ['price' => 9.99], 'new');\nreturn expect_same(\$t->rows[7]['updated_at'], 'old');"],
            ],
        ],
        [
            'title' => 'firstOrCreate()',
            'prompt' => 'Write `firstOrCreate(array &$rows, array $search, array $extra = []): array`. Return the first row (rows are keyed by id) that has every key and value in `$search`. If there is none, create one from `$search` and `$extra` together with the next id (one more than the highest, or 1), as `[\'id\' => ..., ...$search, ...$extra]`, add it to `$rows` and return it.',
            'starter_code' => <<<'PHP'
                <?php

                function firstOrCreate(array &$rows, array $search, array $extra = []): array
                {
                    $row = ['id' => count($rows) + 1, ...$search, ...$extra];
                    $rows[$row['id']] = $row;

                    return $row;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function firstOrCreate(array &$rows, array $search, array $extra = []): array
                {
                    foreach ($rows as $row) {
                        if (array_intersect_assoc($search, $row) === $search) {
                            return $row;
                        }
                    }

                    $id = $rows === [] ? 1 : max(array_keys($rows)) + 1;
                    $row = ['id' => $id, ...$search, ...$extra];
                    $rows[$id] = $row;

                    return $row;
                }
                PHP,
            'hint' => 'A row matches if every search key exists in it with the same value; `array_intersect_assoc($search, $row) === $search` checks exactly that.',
            'checks' => [
                ['label' => 'Existing rows are returned', 'code' => "\$rows = [3 => ['id' => 3, 'isbn' => '978', 'title' => 'Dune']];\n\$row = firstOrCreate(\$rows, ['isbn' => '978'], ['title' => 'Other']);\nreturn expect_same([\$row, count(\$rows)], [['id' => 3, 'isbn' => '978', 'title' => 'Dune'], 1]);"],
                ['label' => 'Missing rows are created', 'code' => "\$rows = [3 => ['id' => 3, 'isbn' => '978']];\n\$row = firstOrCreate(\$rows, ['isbn' => '979'], ['title' => 'Piranesi']);\nreturn expect_same([\$row, array_keys(\$rows)], [['id' => 4, 'isbn' => '979', 'title' => 'Piranesi'], [3, 4]]);"],
                ['label' => 'Every search key must match', 'code' => "\$rows = [1 => ['id' => 1, 'author' => 'Herbert', 'title' => 'Dune']];\n\$row = firstOrCreate(\$rows, ['author' => 'Herbert', 'title' => 'Dune Messiah']);\nreturn expect_same(\$row['id'], 2);"],
                ['label' => 'An empty table', 'code' => "\$rows = [];\nreturn expect_same(firstOrCreate(\$rows, ['name' => 'Sci-fi']), ['id' => 1, 'name' => 'Sci-fi']);"],
            ],
        ],
        [
            'title' => 'Soft deletes',
            'prompt' => 'Finish `SoftDeletingTable`. `delete(int $id, string $now)` sets the row\'s `deleted_at` to `$now` instead of removing it. `all()` and `find()` ignore soft-deleted rows; `withTrashed()` returns every row; `restore(int $id)` sets `deleted_at` back to `null`. Lists are plain lists in id order.',
            'starter_code' => <<<'PHP'
                <?php

                class SoftDeletingTable
                {
                    public function __construct(public array $rows = []) {}

                    public function delete(int $id, string $now): void
                    {
                        unset($this->rows[$id]);
                    }

                    public function all(): array
                    {
                        return array_values($this->rows);
                    }

                    public function find(int $id): ?array
                    {
                        return $this->rows[$id] ?? null;
                    }

                    public function withTrashed(): array
                    {
                        return array_values($this->rows);
                    }

                    public function restore(int $id): void
                    {
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class SoftDeletingTable
                {
                    public function __construct(public array $rows = []) {}

                    public function delete(int $id, string $now): void
                    {
                        if (isset($this->rows[$id])) {
                            $this->rows[$id]['deleted_at'] = $now;
                        }
                    }

                    public function all(): array
                    {
                        return array_values(array_filter($this->rows, fn (array $row) => ($row['deleted_at'] ?? null) === null));
                    }

                    public function find(int $id): ?array
                    {
                        $row = $this->rows[$id] ?? null;

                        return $row !== null && ($row['deleted_at'] ?? null) === null ? $row : null;
                    }

                    public function withTrashed(): array
                    {
                        return array_values($this->rows);
                    }

                    public function restore(int $id): void
                    {
                        if (isset($this->rows[$id])) {
                            $this->rows[$id]['deleted_at'] = null;
                        }
                    }
                }
                PHP,
            'hint' => 'A row is visible when its `deleted_at` is `null` (or missing). `delete()` and `restore()` just change that one value.',
            'checks' => [
                ['label' => 'Deleted rows are hidden, not removed', 'code' => "\$t = new SoftDeletingTable([1 => ['id' => 1, 'deleted_at' => null], 2 => ['id' => 2, 'deleted_at' => null]]);\n\$t->delete(1, '2026-10-21');\nreturn expect_same([array_column(\$t->all(), 'id'), \$t->find(1), \$t->rows[1]['deleted_at'] ?? 'gone'], [[2], null, '2026-10-21']);"],
                ['label' => 'withTrashed() shows everything', 'code' => "\$t = new SoftDeletingTable([1 => ['id' => 1, 'deleted_at' => '2026-10-20'], 2 => ['id' => 2, 'deleted_at' => null]]);\nreturn expect_same(array_column(\$t->withTrashed(), 'id'), [1, 2]);"],
                ['label' => 'restore() brings a row back', 'code' => "\$t = new SoftDeletingTable([1 => ['id' => 1, 'deleted_at' => '2026-10-20']]);\n\$t->restore(1);\nreturn expect_same(\$t->find(1), ['id' => 1, 'deleted_at' => null]);"],
            ],
        ],
    ],
];
