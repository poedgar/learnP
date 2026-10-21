<?php

// Lesson 4 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'migrations',
    'title' => 'Migrations',
    'summary' => 'Describing the database in code: migrations with `up()` and `down()`, column types and modifiers, foreign keys, running order and batches, and changing tables with new migrations.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a nullable 'summary' text column.
        $columns = [
            ['id', 'integer primary key autoincrement'],
            ['title', 'varchar(255) not null'],
            ['price', 'decimal(8, 2) not null default 0'],
            ['created_at', 'datetime null'],
            ['updated_at', 'datetime null'],
        ];

        $lines = array_map(fn ($column) => '  ' . $column[0] . ' ' . $column[1], $columns);

        echo "create table books (\n" . implode(",\n", $lines) . "\n)";
        PHP,

    'quiz' => [
        [
            'question' => 'What is a migration?',
            'options' => [
                'A backup of the database',
                'A PHP file describing one change to the database, with `up()` to make it and `down()` to undo it',
                'A way to move data between servers',
                'An Eloquent model',
            ],
            'correct_option' => 1,
            'explanation' => 'Migrations keep every copy of the database in step, from your laptop to production.',
        ],
        [
            'question' => 'What does `$table->string(\'title\')` create?',
            'options' => ['A `text` column', 'A `varchar(255)` column that can\'t be null', 'A nullable column', 'An index'],
            'correct_option' => 1,
            'explanation' => 'Columns are required unless you add `->nullable()`, and `string()` is 255 characters unless you give a length.',
        ],
        [
            'question' => 'What does `$table->timestamps()` add?',
            'options' => [
                'One `timestamp` column',
                'The `created_at` and `updated_at` columns, which Eloquent fills in',
                'A trigger',
                'The current time to every row',
            ],
            'correct_option' => 1,
            'explanation' => 'Eloquent sets `created_at` on insert and `updated_at` on every save.',
        ],
        [
            'question' => 'Which table does `$table->foreignId(\'author_id\')->constrained()` point at?',
            'options' => ['`author`', '`authors`', '`author_ids`', 'It must be given'],
            'correct_option' => 1,
            'explanation' => 'Laravel drops `_id` and makes the rest plural.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $files = [
                    '2026_10_21_100000_create_books_table.php',
                    '2026_10_20_090000_create_authors_table.php',
                    '2026_10_21_093000_create_genres_table.php',
                ];

                sort($files);

                echo implode(PHP_EOL, array_map(fn ($f) => substr($f, 18, -4), $files));
                PHP,
            'options' => [
                'books, authors, genres (one per line, as written)',
                '`create_authors_table`, `create_genres_table`, `create_books_table`',
                '`create_books_table`, `create_genres_table`, `create_authors_table`',
                'genres, books, authors',
            ],
            'correct_option' => 1,
            'explanation' => 'Sorting the names sorts by their timestamps, which is the order migrations run in.',
        ],
        [
            'question' => 'What does `php artisan migrate:rollback` undo?',
            'options' => [
                'Every migration',
                'The most recent batch: the migrations run by the last `php artisan migrate`',
                'Only the newest migration file',
                'Nothing, it\'s a dry run',
            ],
            'correct_option' => 1,
            'explanation' => 'Each run of `migrate` gets a batch number; rollback undoes the latest batch, newest first.',
        ],
        [
            'question' => 'A migration has already run on production, and you need to add a column. What should you do?',
            'options' => [
                'Edit the old migration and run `migrate` again',
                'Write a new migration that adds the column',
                'Add the column by hand on production',
                'Run `migrate:fresh` on production',
            ],
            'correct_option' => 1,
            'explanation' => 'Laravel won\'t rerun a migration it has recorded, and `migrate:fresh` deletes all the data.',
        ],
        [
            'question' => 'An `up()` creates a table and then adds an index to it. What order should `down()` undo them in?',
            'options' => [
                'Drop the table, then drop the index',
                'Drop the index, then drop the table',
                'It doesn\'t matter',
                '`down()` can\'t drop indexes',
            ],
            'correct_option' => 1,
            'explanation' => '`down()` reverses `up()` step by step, newest step first.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'One column',
            'prompt' => 'Write `columnSql(string $name, string $type, bool $nullable = false, mixed $default = null): string`, giving lines like `title varchar(255) not null` or `summary text null`. A default is added at the end: numbers as they are, `true`/`false` as `1`/`0`, and strings in single quotes (with any `\'` doubled).',
            'starter_code' => <<<'PHP'
                <?php

                function columnSql(string $name, string $type, bool $nullable = false, mixed $default = null): string
                {
                    return "$name $type";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function columnSql(string $name, string $type, bool $nullable = false, mixed $default = null): string
                {
                    $sql = "$name $type " . ($nullable ? 'null' : 'not null');

                    if ($default !== null) {
                        $sql .= ' default ' . match (true) {
                            is_bool($default) => $default ? '1' : '0',
                            is_int($default), is_float($default) => (string) $default,
                            default => "'" . str_replace("'", "''", (string) $default) . "'",
                        };
                    }

                    return $sql;
                }
                PHP,
            'hint' => 'Build the start, then add ` default ...` only when `$default !== null`. A `match (true)` handles the three kinds of default.',
            'checks' => [
                ['label' => 'Required and nullable', 'code' => "return expect_same([columnSql('title', 'varchar(255)'), columnSql('summary', 'text', true)], ['title varchar(255) not null', 'summary text null']);"],
                ['label' => 'Number and boolean defaults', 'code' => "return expect_same([columnSql('price', 'decimal(8, 2)', default: 0), columnSql('in_stock', 'boolean', default: true), columnSql('rating', 'float', default: 4.5)], ['price decimal(8, 2) not null default 0', 'in_stock boolean not null default 1', 'rating float not null default 4.5']);"],
                ['label' => 'String defaults are quoted', 'code' => "return expect_same([columnSql('format', 'varchar(255)', default: 'paperback'), columnSql('shop', 'varchar(255)', true, \"Ada's\")], [\"format varchar(255) not null default 'paperback'\", \"shop varchar(255) null default 'Ada''s'\"]);"],
            ],
        ],
        [
            'title' => 'A blueprint',
            'prompt' => 'Finish `Blueprint`. `id()` adds `id integer primary key autoincrement`; `string($name, $length = 255)` adds `name varchar(length) not null`; `text($name)` adds `name text not null`; `boolean($name)` adds `name boolean not null`; `timestamps()` adds `created_at datetime null` and `updated_at datetime null`. `nullable()` makes the last column added `null` instead of `not null`, and every method except `toSql()` returns `$this`. `toSql()` returns `create table books (col, col, ...)` with columns joined by `, `.',
            'starter_code' => <<<'PHP'
                <?php

                class Blueprint
                {
                    private array $columns = [];

                    public function __construct(private string $table) {}

                    public function id(): static
                    {
                        $this->columns[] = 'id integer primary key autoincrement';
                        return $this;
                    }

                    public function toSql(): string
                    {
                        return "create table {$this->table} (" . implode(', ', $this->columns) . ')';
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Blueprint
                {
                    private array $columns = [];

                    public function __construct(private string $table) {}

                    public function id(): static
                    {
                        $this->columns[] = 'id integer primary key autoincrement';
                        return $this;
                    }

                    public function string(string $name, int $length = 255): static
                    {
                        $this->columns[] = "$name varchar($length) not null";
                        return $this;
                    }

                    public function text(string $name): static
                    {
                        $this->columns[] = "$name text not null";
                        return $this;
                    }

                    public function boolean(string $name): static
                    {
                        $this->columns[] = "$name boolean not null";
                        return $this;
                    }

                    public function timestamps(): static
                    {
                        $this->columns[] = 'created_at datetime null';
                        $this->columns[] = 'updated_at datetime null';
                        return $this;
                    }

                    public function nullable(): static
                    {
                        $last = array_key_last($this->columns);
                        $this->columns[$last] = preg_replace('/ not null$/', ' null', $this->columns[$last]);
                        return $this;
                    }

                    public function toSql(): string
                    {
                        return "create table {$this->table} (" . implode(', ', $this->columns) . ')';
                    }
                }
                PHP,
            'hint' => 'Each column method appends a string and returns `$this`. `nullable()` changes the last one: `array_key_last()` finds it, and replacing a trailing ` not null` with ` null` does the rest.',
            'checks' => [
                ['label' => 'A books table', 'code' => "\$sql = (new Blueprint('books'))->id()->string('title')->text('summary')->nullable()->boolean('in_stock')->timestamps()->toSql();\nreturn expect_same(\$sql, 'create table books (id integer primary key autoincrement, title varchar(255) not null, summary text null, in_stock boolean not null, created_at datetime null, updated_at datetime null)');"],
                ['label' => 'String lengths', 'code' => "return expect_same((new Blueprint('books'))->string('isbn', 13)->toSql(), 'create table books (isbn varchar(13) not null)');"],
                ['label' => 'nullable() only touches the last column', 'code' => "return expect_same((new Blueprint('a'))->string('x')->string('y')->nullable()->toSql(), 'create table a (x varchar(255) not null, y varchar(255) null)');"],
            ],
        ],
        [
            'title' => 'Foreign keys',
            'prompt' => 'Write `foreignKeySql(string $column, ?string $onDelete = null): string`, like `foreignId($column)->constrained()`. The table is the column without its `_id`, made plural by adding `s` (or `ies` in place of a final consonant + `y`, so `category_id` → `categories`). Return `foreign key (author_id) references authors(id)`, followed by ` on delete cascade` or ` on delete set null` when `$onDelete` is `cascade` or `null`. A column not ending in `_id` throws `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function foreignKeySql(string $column, ?string $onDelete = null): string
                {
                    return "foreign key ($column)";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function foreignKeySql(string $column, ?string $onDelete = null): string
                {
                    if (! str_ends_with($column, '_id')) {
                        throw new InvalidArgumentException("$column is not a foreign id column.");
                    }

                    $singular = substr($column, 0, -3);
                    $table = preg_match('/[^aeiou]y$/', $singular) === 1 ? substr($singular, 0, -1) . 'ies' : $singular . 's';

                    $sql = "foreign key ($column) references $table(id)";

                    return $sql . match ($onDelete) {
                        'cascade' => ' on delete cascade',
                        'null' => ' on delete set null',
                        default => '',
                    };
                }
                PHP,
            'hint' => '`substr($column, 0, -3)` drops `_id`. A pattern like `/[^aeiou]y$/` spots a consonant followed by `y`.',
            'checks' => [
                ['label' => 'Tables from column names', 'code' => "return expect_same([foreignKeySql('author_id'), foreignKeySql('category_id'), foreignKeySql('day_id')], ['foreign key (author_id) references authors(id)', 'foreign key (category_id) references categories(id)', 'foreign key (day_id) references days(id)']);"],
                ['label' => 'On delete', 'code' => "return expect_same([foreignKeySql('book_id', 'cascade'), foreignKeySql('publisher_id', 'null')], ['foreign key (book_id) references books(id) on delete cascade', 'foreign key (publisher_id) references publishers(id) on delete set null']);"],
                ['label' => 'Only _id columns', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => foreignKeySql('author'));"],
            ],
        ],
        [
            'title' => 'What still has to run?',
            'prompt' => 'Write `pendingMigrations(array $files, array $ran): array`. `$files` are migration file names in any order, like `2026_10_21_100000_create_books_table.php`; `$ran` holds the names (without `.php`) of migrations already run. Return the names, without `.php`, of those not run yet, oldest first.',
            'starter_code' => <<<'PHP'
                <?php

                function pendingMigrations(array $files, array $ran): array
                {
                    return $files;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pendingMigrations(array $files, array $ran): array
                {
                    $names = array_map(fn (string $file) => basename($file, '.php'), $files);
                    sort($names);

                    return array_values(array_diff($names, $ran));
                }
                PHP,
            'hint' => '`basename($file, \'.php\')` removes the extension. Sort the names (the timestamp prefix makes that chronological), then `array_diff()` removes those already run.',
            'checks' => [
                ['label' => 'Pending, oldest first', 'code' => "\$files = ['2026_10_21_100000_create_books_table.php', '2026_10_20_090000_create_authors_table.php', '2026_10_22_080000_add_isbn_to_books_table.php'];\nreturn expect_same(pendingMigrations(\$files, ['2026_10_20_090000_create_authors_table']), ['2026_10_21_100000_create_books_table', '2026_10_22_080000_add_isbn_to_books_table']);"],
                ['label' => 'Nothing to do', 'code' => "return expect_same(pendingMigrations(['2026_01_01_000000_a.php'], ['2026_01_01_000000_a']), []);"],
                ['label' => 'A fresh database', 'code' => "return expect_same(pendingMigrations(['2026_01_02_000000_b.php', '2026_01_01_000000_a.php'], []), ['2026_01_01_000000_a', '2026_01_02_000000_b']);"],
            ],
        ],
        [
            'title' => 'Roll back a batch',
            'prompt' => 'Write `rollbackBatch(array $migrations): array`. Each record is `[\'migration\' => name, \'batch\' => number]`, as in Laravel\'s `migrations` table. Return the names in the highest batch, newest migration first (the reverse of their name order), which is the order `migrate:rollback` undoes them. With no records, return an empty array.',
            'starter_code' => <<<'PHP'
                <?php

                function rollbackBatch(array $migrations): array
                {
                    return array_column($migrations, 'migration');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function rollbackBatch(array $migrations): array
                {
                    if ($migrations === []) {
                        return [];
                    }

                    $latest = max(array_column($migrations, 'batch'));
                    $names = array_column(array_filter($migrations, fn (array $m) => $m['batch'] === $latest), 'migration');
                    rsort($names);

                    return $names;
                }
                PHP,
            'hint' => 'Find the highest batch with `max(array_column(...))`, keep those records, take their names and sort them in reverse with `rsort()`.',
            'checks' => [
                ['label' => 'The latest batch, newest first', 'code' => "\$m = [['migration' => '2026_10_20_a', 'batch' => 1], ['migration' => '2026_10_21_b', 'batch' => 2], ['migration' => '2026_10_22_c', 'batch' => 2]];\nreturn expect_same(rollbackBatch(\$m), ['2026_10_22_c', '2026_10_21_b']);"],
                ['label' => 'A single batch', 'code' => "return expect_same(rollbackBatch([['migration' => '2026_01_01_a', 'batch' => 1], ['migration' => '2026_01_02_b', 'batch' => 1]]), ['2026_01_02_b', '2026_01_01_a']);"],
                ['label' => 'Nothing to roll back', 'code' => 'return expect_same(rollbackBatch([]), []);'],
            ],
        ],
        [
            'title' => 'down() reverses up()',
            'prompt' => 'Write `downFor(array $up): array`, turning the steps of an `up()` into the steps of its `down()`: in reverse order, `[\'create\', table]` becomes `[\'drop\', table]`, `[\'addColumn\', table, column]` becomes `[\'dropColumn\', table, column]`, and `[\'renameColumn\', table, from, to]` becomes `[\'renameColumn\', table, to, from]`. Any other step throws `LogicException`, because it can\'t be undone automatically.',
            'starter_code' => <<<'PHP'
                <?php

                function downFor(array $up): array
                {
                    return $up;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function downFor(array $up): array
                {
                    return array_map(fn (array $step) => match ($step[0]) {
                        'create' => ['drop', $step[1]],
                        'addColumn' => ['dropColumn', $step[1], $step[2]],
                        'renameColumn' => ['renameColumn', $step[1], $step[3], $step[2]],
                        default => throw new LogicException("Cannot undo {$step[0]} automatically."),
                    }, array_reverse($up));
                }
                PHP,
            'hint' => 'Reverse the steps with `array_reverse()`, then map each one with a `match` on its first element. `throw` can be used as an expression in a `match` arm.',
            'checks' => [
                ['label' => 'Steps are undone in reverse', 'code' => "return expect_same(downFor([['create', 'books'], ['addColumn', 'books', 'isbn']]), [['dropColumn', 'books', 'isbn'], ['drop', 'books']]);"],
                ['label' => 'Renames swap back', 'code' => "return expect_same(downFor([['renameColumn', 'books', 'name', 'title']]), [['renameColumn', 'books', 'title', 'name']]);"],
                ['label' => 'Unknown steps throw', 'code' => "return expect_throws(LogicException::class, fn () => downFor([['create', 'a'], ['rawSql', 'update books set price = 0']]));"],
                ['label' => 'Nothing to undo', 'code' => 'return expect_same(downFor([]), []);'],
            ],
        ],
    ],
];
