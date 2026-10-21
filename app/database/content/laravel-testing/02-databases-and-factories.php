<?php

// Lesson 2 of the Laravel testing course. The theory is in the .md file with the same name.

return [
    'slug' => 'databases-and-factories',
    'title' => 'Databases and factories',
    'summary' => 'A fresh database per test with RefreshDatabase, model factories with overrides, states, sequences and relationships, and assertions such as assertDatabaseHas.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add a state, or make more books.
        $definition = ['title' => 'A book', 'price' => 9.99, 'published' => true];

        $make = fn (array $overrides = []) => array_merge($definition, $overrides);

        print_r($make());
        print_r($make(['published' => false]));
        PHP,

    'quiz' => [
        [
            'question' => 'What does the `RefreshDatabase` trait do for each test?',
            'options' => [
                'Runs every migration again before each test',
                'Migrates once, then runs each test in a transaction that is rolled back afterwards',
                'Deletes the database file',
                'Copies your development data into the test',
            ],
            'correct_option' => 1,
            'explanation' => 'Every test starts with empty tables, and it\'s fast.',
        ],
        [
            'question' => 'What is the difference between `Book::factory()->make()` and `->create()`?',
            'options' => [
                'None',
                '`make()` builds a model without saving it; `create()` also saves it',
                '`make()` saves it; `create()` doesn\'t',
                '`create()` makes three',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `make()` when the test doesn\'t need the database.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $definition = ['title' => 'A book', 'price' => 9.99, 'published' => true];

                $book = array_merge($definition, ['price' => 0]);

                echo $book['title'] . ', ' . $book['price'] . ', ' . var_export($book['published'], true);
                PHP,
            'options' => ['`A book, 9.99, true`', '`A book, 0, true`', '`A book, 0, false`', '`, 0, `'],
            'correct_option' => 1,
            'explanation' => 'Overrides win; everything else keeps its default.',
        ],
        [
            'question' => 'Why pass only the attributes a test is about, like `create([\'price\' => 0])`?',
            'options' => [
                'It runs faster',
                'The reader sees what matters, and the test doesn\'t break when unrelated columns change',
                'Factories ignore other attributes',
                'It is required by Pest',
            ],
            'correct_option' => 1,
            'explanation' => 'The factory fills in the rest with believable values.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $sequence = ['red', 'green', 'blue'];

                $colours = [];
                foreach (range(0, 4) as $i) {
                    $colours[] = $sequence[$i % count($sequence)];
                }

                echo implode(' ', $colours);
                PHP,
            'options' => ['`red green blue`', '`red green blue red green`', '`red red red red red`', '`blue green red blue green`'],
            'correct_option' => 1,
            'explanation' => 'A sequence goes round again when it runs out, like `sequence()` on a factory.',
        ],
        [
            'question' => 'What does `$factory->unpublished()` do to `$factory` itself?',
            'options' => [
                'Changes it, so every later model is unpublished',
                'Nothing: it returns a new factory with the state added',
                'Saves an unpublished book',
                'Throws if the state doesn\'t exist',
            ],
            'correct_option' => 1,
            'explanation' => 'Factories are immutable, so states can be combined freely.',
        ],
        [
            'question' => 'When does `assertDatabaseHas(\'books\', [\'title\' => \'Dune\'])` pass?',
            'options' => [
                'When every row has the title Dune',
                'When at least one row has the title Dune, whatever its other columns',
                'When the only column is title',
                'When the table has exactly one row',
            ],
            'correct_option' => 1,
            'explanation' => 'It looks for one matching row; `assertDatabaseCount` counts rows.',
        ],
        [
            'question' => 'How do you make an author with three books?',
            'options' => [
                '`Author::factory()->count(3)->create()`',
                '`Author::factory()->has(Book::factory()->count(3))->create()`',
                '`Book::factory()->author(3)->create()`',
                '`Author::factory()->create([\'books\' => 3])`',
            ],
            'correct_option' => 1,
            'explanation' => '`has()` makes related models through the relationship; `for()` goes the other way.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Defaults and overrides',
            'prompt' => 'Write `makeModel(array $definition, array $overrides = []): array`. Merge the overrides over the definition, then replace every value that is a `Closure` with the result of calling it. An override can be a closure too.',
            'starter_code' => <<<'PHP'
                <?php

                function makeModel(array $definition, array $overrides = []): array
                {
                    return array_merge($definition, $overrides);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function makeModel(array $definition, array $overrides = []): array
                {
                    $attributes = array_merge($definition, $overrides);

                    foreach ($attributes as $key => $value) {
                        if ($value instanceof Closure) {
                            $attributes[$key] = $value();
                        }
                    }

                    return $attributes;
                }
                PHP,
            'hint' => 'After `array_merge()`, loop and call each `Closure`.',
            'checks' => [
                ['label' => 'Overrides win', 'code' => "return expect_same(makeModel(['title' => 'A book', 'price' => 9.99], ['price' => 0]), ['title' => 'A book', 'price' => 0]);"],
                ['label' => 'Closures are called', 'code' => "return expect_same(makeModel(['title' => fn () => 'Dune', 'slug' => 'dune'], ['price' => fn () => 5]), ['title' => 'Dune', 'slug' => 'dune', 'price' => 5]);"],
                ['label' => 'An overridden closure is never called', 'code' => <<<'PHP'
                    $called = false;
                    $book = makeModel(['title' => function () use (&$called) { $called = true; return 'x'; }], ['title' => 'Dune']);
                    return ($book === ['title' => 'Dune'] && ! $called) || 'The override should replace the closure before anything is called.';
                    PHP],
            ],
        ],
        [
            'title' => 'Many at once',
            'prompt' => 'Write `makeMany(callable $definition, int $count): array`. It calls `$definition` once per model with the model\'s number, starting at 1, and returns the list of results. For `$count` below 1, return an empty array.',
            'starter_code' => <<<'PHP'
                <?php

                function makeMany(callable $definition, int $count): array
                {
                    return array_fill(0, $count, $definition(1));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function makeMany(callable $definition, int $count): array
                {
                    $models = [];
                    for ($n = 1; $n <= $count; $n++) {
                        $models[] = $definition($n);
                    }

                    return $models;
                }
                PHP,
            'hint' => 'A `for` loop from 1 to `$count`, calling the definition each time.',
            'checks' => [
                ['label' => 'Numbers each model', 'code' => "return expect_same(makeMany(fn (int \$n) => ['title' => \"Book \$n\"], 3), [['title' => 'Book 1'], ['title' => 'Book 2'], ['title' => 'Book 3']]);"],
                ['label' => 'Zero or fewer', 'code' => "return expect_same([makeMany(fn (\$n) => \$n, 0), makeMany(fn (\$n) => \$n, -2)], [[], []]);"],
            ],
        ],
        [
            'title' => 'States',
            'prompt' => 'Write a `Factory` class built from a definition array. `state(array|Closure $state)` returns a **new** factory with the state added (the original is unchanged). `make(array $overrides = [])` applies the states in order, each merged over the attributes so far (a closure state is called with the attributes so far and returns the changes), then the overrides.',
            'starter_code' => <<<'PHP'
                <?php

                class Factory
                {
                    private array $states = [];

                    public function __construct(private array $definition) {}

                    public function state(array|Closure $state): static
                    {
                        $this->states[] = $state;

                        return $this;
                    }

                    public function make(array $overrides = []): array
                    {
                        return array_merge($this->definition, ...$this->states, ...[$overrides]);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Factory
                {
                    private array $states = [];

                    public function __construct(private array $definition) {}

                    public function state(array|Closure $state): static
                    {
                        $copy = clone $this;
                        $copy->states[] = $state;

                        return $copy;
                    }

                    public function make(array $overrides = []): array
                    {
                        $attributes = $this->definition;
                        foreach ($this->states as $state) {
                            $changes = $state instanceof Closure ? $state($attributes) : $state;
                            $attributes = array_merge($attributes, $changes);
                        }

                        return array_merge($attributes, $overrides);
                    }
                }
                PHP,
            'hint' => '`clone $this`, add the state to the copy and return it. In `make()`, fold the states over the definition one at a time.',
            'checks' => [
                ['label' => 'States apply in order', 'code' => <<<'PHP'
                    $factory = (new Factory(['title' => 'A book', 'price' => 10, 'published' => true]))
                        ->state(['published' => false])
                        ->state(fn (array $a) => ['price' => $a['price'] / 2]);
                    return expect_same($factory->make(['title' => 'Dune']), ['title' => 'Dune', 'price' => 5, 'published' => false]);
                    PHP],
                ['label' => 'state() leaves the original alone', 'code' => <<<'PHP'
                    $base = new Factory(['published' => true]);
                    $draft = $base->state(['published' => false]);
                    if ($draft === $base) return 'state() should return a new factory.';
                    return expect_same([$base->make(), $draft->make()], [['published' => true], ['published' => false]]);
                    PHP],
            ],
        ],
        [
            'title' => 'Round and round',
            'prompt' => 'Write `withSequence(array $models, array $sequence): array`. Each model gets the next item of `$sequence` merged over it, going back to the first item when the sequence runs out. An empty sequence leaves the models as they are.',
            'starter_code' => <<<'PHP'
                <?php

                function withSequence(array $models, array $sequence): array
                {
                    foreach ($models as $i => $model) {
                        $models[$i] = array_merge($model, $sequence[$i]);
                    }

                    return $models;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function withSequence(array $models, array $sequence): array
                {
                    if ($sequence === []) {
                        return $models;
                    }

                    $sequence = array_values($sequence);
                    $result = [];
                    foreach (array_values($models) as $i => $model) {
                        $result[] = array_merge($model, $sequence[$i % count($sequence)]);
                    }

                    return $result;
                }
                PHP,
            'hint' => 'Use `$i % count($sequence)` to go round, and return early for an empty sequence.',
            'checks' => [
                ['label' => 'Goes round', 'code' => <<<'PHP'
                    $models = array_fill(0, 5, ['title' => 'A book']);
                    $result = withSequence($models, [['published' => true], ['published' => false]]);
                    return expect_same(array_column($result, 'published'), [true, false, true, false, true]);
                    PHP],
                ['label' => 'Keeps other attributes', 'code' => "return expect_same(withSequence([['title' => 'Dune', 'size' => 'S']], [['size' => 'L']]), [['title' => 'Dune', 'size' => 'L']]);"],
                ['label' => 'An empty sequence', 'code' => "return expect_same(withSequence([['title' => 'Dune']], []), [['title' => 'Dune']]);"],
            ],
        ],
        [
            'title' => 'Somewhere in the table',
            'prompt' => 'Write `assertDatabaseHas(array $rows, array $where): void`. It passes if at least one row has every key in `$where` with an equal value (`==`, as databases give back strings), and throws a `RuntimeException` otherwise, with the message `No row matches {JSON}`, where JSON is `json_encode($where)`.',
            'starter_code' => <<<'PHP'
                <?php

                function assertDatabaseHas(array $rows, array $where): void
                {
                    if (! in_array($where, $rows)) {
                        throw new RuntimeException('No row matches');
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assertDatabaseHas(array $rows, array $where): void
                {
                    foreach ($rows as $row) {
                        $matches = true;
                        foreach ($where as $key => $value) {
                            if (! array_key_exists($key, $row) || $row[$key] != $value) {
                                $matches = false;
                                break;
                            }
                        }
                        if ($matches) {
                            return;
                        }
                    }

                    throw new RuntimeException('No row matches ' . json_encode($where));
                }
                PHP,
            'hint' => 'For each row, check every key in `$where` exists and is equal. Return as soon as a row matches.',
            'checks' => [
                ['label' => 'Ignores other columns', 'code' => <<<'PHP'
                    $rows = [['id' => 1, 'title' => 'Dune', 'price' => '9.99'], ['id' => 2, 'title' => 'Emma', 'price' => '7.50']];
                    assertDatabaseHas($rows, ['title' => 'Emma']);
                    assertDatabaseHas($rows, ['title' => 'Dune', 'price' => 9.99]);
                    return true;
                    PHP],
                ['label' => 'Every key must match in the same row', 'code' => <<<'PHP'
                    $rows = [['id' => 1, 'title' => 'Dune', 'price' => '9.99'], ['id' => 2, 'title' => 'Emma', 'price' => '7.50']];
                    try {
                        assertDatabaseHas($rows, ['title' => 'Dune', 'price' => 7.5]);
                    } catch (RuntimeException $e) {
                        return expect_same($e->getMessage(), 'No row matches {"title":"Dune","price":7.5}');
                    }
                    return 'Nothing was thrown, but no single row has both values.';
                    PHP],
                ['label' => 'Missing columns never match', 'code' => "return expect_throws(RuntimeException::class, fn () => assertDatabaseHas([['id' => 1]], ['title' => null]));"],
            ],
        ],
        [
            'title' => 'Roll it back',
            'prompt' => 'Write a `TestDatabase` class: `insert(string $table, array $row): int` adds the row with the next `id` for that table (1, 2, ...) and returns the id; `count(string $table): int`; `beginTransaction()` remembers the current state; `rollBack()` returns to it, including the next ids. Calling `rollBack()` without a transaction throws a `LogicException`.',
            'starter_code' => <<<'PHP'
                <?php

                class TestDatabase
                {
                    private array $tables = [];

                    public function insert(string $table, array $row): int
                    {
                        $this->tables[$table][] = $row;

                        return count($this->tables[$table]);
                    }

                    public function count(string $table): int
                    {
                        return count($this->tables[$table] ?? []);
                    }

                    public function beginTransaction(): void {}

                    public function rollBack(): void {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TestDatabase
                {
                    private array $tables = [];
                    private array $nextIds = [];
                    private ?array $snapshot = null;

                    public function insert(string $table, array $row): int
                    {
                        $id = $this->nextIds[$table] ?? 1;
                        $this->nextIds[$table] = $id + 1;
                        $this->tables[$table][] = ['id' => $id] + $row;

                        return $id;
                    }

                    public function count(string $table): int
                    {
                        return count($this->tables[$table] ?? []);
                    }

                    public function beginTransaction(): void
                    {
                        $this->snapshot = [$this->tables, $this->nextIds];
                    }

                    public function rollBack(): void
                    {
                        if ($this->snapshot === null) {
                            throw new LogicException('There is no active transaction.');
                        }

                        [$this->tables, $this->nextIds] = $this->snapshot;
                        $this->snapshot = null;
                    }
                }
                PHP,
            'hint' => 'Keep the next id per table separately from the rows (rows could be deleted). Arrays are copied when assigned, so a snapshot is just `[$this->tables, $this->nextIds]`.',
            'checks' => [
                ['label' => 'Ids per table', 'code' => <<<'PHP'
                    $db = new TestDatabase();
                    return expect_same([$db->insert('books', ['title' => 'Dune']), $db->insert('books', ['title' => 'Emma']), $db->insert('authors', ['name' => 'Herbert']), $db->count('books')], [1, 2, 1, 2]);
                    PHP],
                ['label' => 'Rolling back undoes the test', 'code' => <<<'PHP'
                    $db = new TestDatabase();
                    $db->insert('books', ['title' => 'Dune']);
                    $db->beginTransaction();
                    $db->insert('books', ['title' => 'Emma']);
                    $db->insert('books', ['title' => 'Ulysses']);
                    $db->rollBack();
                    return expect_same([$db->count('books'), $db->insert('books', ['title' => 'Next'])], [1, 2]);
                    PHP],
                ['label' => 'No transaction, no rollback', 'code' => "return expect_throws(LogicException::class, fn () => (new TestDatabase())->rollBack());"],
            ],
        ],
    ],
];
