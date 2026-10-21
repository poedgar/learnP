<?php

// Lesson 7 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'inside-eloquent',
    'title' => 'Inside Eloquent',
    'summary' => 'How Eloquent works: a query builder that compiles SQL with bindings, static calls forwarded to it, local scopes, models as attribute arrays with casts, accessors and mutators, dirty tracking and mass assignment.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding an orderBy() method.
        class Builder
        {
            private array $wheres = [];
            private array $bindings = [];

            public function __construct(private string $table) {}

            public function where(string $column, mixed $value): static
            {
                $this->wheres[] = "$column = ?";
                $this->bindings[] = $value;

                return $this;
            }

            public function toSql(): string
            {
                $where = $this->wheres ? ' where ' . implode(' and ', $this->wheres) : '';

                return "select * from {$this->table}$where";
            }
        }

        echo (new Builder('books'))->where('author', 'Herbert')->where('in_stock', true)->toSql();
        PHP,

    'quiz' => [
        [
            'question' => 'Why does the query builder put `?` in the SQL and keep the values in a separate bindings list?',
            'options' => [
                'It makes the SQL shorter',
                'The database fills the placeholders itself, so values can never be run as SQL (no SQL injection)',
                'Because PHP can\'t join strings and numbers',
                'So the SQL can be cached in a file',
            ],
            'correct_option' => 1,
            'explanation' => 'A value like `\'; drop table books; --` stays a value when it\'s a binding.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $wheres = [];
                $bindings = [];

                foreach ([['author', '=', 'Herbert'], ['price', '<', 10]] as [$column, $operator, $value]) {
                    $wheres[] = "$column $operator ?";
                    $bindings[] = $value;
                }

                echo 'where ' . implode(' and ', $wheres) . ' ' . json_encode($bindings);
                PHP,
            'options' => [
                '`where author = Herbert and price < 10 []`',
                '`where author = ? and price < ? ["Herbert",10]`',
                '`where ? = ? and ? < ? ["author","Herbert","price",10]`',
                '`where author = ? and price < ? []`',
            ],
            'correct_option' => 1,
            'explanation' => 'Column names and operators are part of the SQL; only the values become bindings.',
        ],
        [
            'question' => 'There is no static `where()` method on `Book`. Why does `Book::where(\'author\', \'Herbert\')` work?',
            'options' => [
                'PHP looks for an instance method instead',
                'The model\'s `__callStatic()` creates a query builder for the table and forwards the call to it',
                'Laravel generates a static method when the model is loaded',
                'It doesn\'t; you must write `(new Book)->where()`',
            ],
            'correct_option' => 1,
            'explanation' => 'Forwarding unknown calls to a new builder is what lets every builder method start a query from the model class.',
        ],
        [
            'question' => 'A model has `public function scopeInStock(Builder $query): void`. How do you use it?',
            'options' => [
                '`Book::scopeInStock()`',
                '`Book::inStock()` or `->inStock()` on any book query',
                '`Book::scope(\'inStock\')`',
                '`Book::where(\'inStock\')`',
            ],
            'correct_option' => 1,
            'explanation' => 'The builder\'s `__call()` finds `scopeInStock` on the model and calls it with the query.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Model
                {
                    protected array $attributes = [];
                    protected array $casts = [];

                    public function __construct(array $row)
                    {
                        $this->attributes = $row;
                    }

                    public function __get(string $key): mixed
                    {
                        $value = $this->attributes[$key];

                        return match ($this->casts[$key] ?? null) {
                            'int' => (int) $value,
                            'bool' => (bool) $value,
                            default => $value,
                        };
                    }
                }

                class Book extends Model
                {
                    protected array $casts = ['stock' => 'int', 'featured' => 'bool'];
                }

                $book = new Book(['stock' => '3', 'featured' => '0', 'year' => '1965']);

                var_dump($book->stock, $book->featured, $book->year);
                PHP,
            'options' => [
                '`string(1) "3"`, `string(1) "0"`, `string(4) "1965"`',
                '`int(3)`, `bool(false)`, `string(4) "1965"`',
                '`int(3)`, `bool(true)`, `int(1965)`',
                '`int(3)`, `bool(false)`, `int(1965)`',
            ],
            'correct_option' => 1,
            'explanation' => '`stock` and `featured` have casts. `year` doesn\'t, so it stays the string the database gave.',
        ],
        [
            'question' => 'Which method is an accessor for `$author->full_name` in the classic Laravel style?',
            'options' => [
                '`fullName()`',
                '`getFullNameAttribute()`',
                '`get_full_name()`',
                '`full_name_accessor()`',
            ],
            'correct_option' => 1,
            'explanation' => 'The attribute name is turned into StudlyCase and wrapped in `get...Attribute`. A mutator is `set...Attribute`.',
        ],
        [
            'question' => 'A model is loaded, then `$book->price = 12` is set and `$book->price = 9.99` set back to its original value. What does `$book->isDirty(\'price\')` return in Laravel?',
            'options' => [
                '`true`, because it was changed',
                '`false`, because it equals the original again',
                'It throws an exception',
                '`null`',
            ],
            'correct_option' => 1,
            'explanation' => 'Dirty means "different from the original", not "was ever touched". `save()` would skip the column.',
        ],
        [
            'question' => 'A `User` model has `protected $fillable = [\'name\', \'email\']`. What does `User::create([\'name\' => \'Ada\', \'is_admin\' => true])` do with `is_admin`?',
            'options' => [
                'Saves it',
                'Ignores it, because it isn\'t fillable',
                'Saves it as a string',
                'Deletes the column',
            ],
            'correct_option' => 1,
            'explanation' => 'Mass assignment only fills the listed attributes, so user input can\'t set columns it shouldn\'t.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Compile a select',
            'prompt' => 'Finish `Builder`. `where($column, $operator, $value = null)` also accepts two arguments, meaning `=`. `orderBy($column, $direction = \'asc\')` and `limit(int $count)` add those clauses. `toSql()` returns lower-case SQL such as `select * from books where author = ? and price < ? order by price desc, title asc limit 3`, and `getBindings()` the values in order. Every method except the last two returns `$this`.',
            'starter_code' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];
                    private array $bindings = [];

                    public function __construct(private string $table) {}

                    public function where(string $column, mixed $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function orderBy(string $column, string $direction = 'asc'): static
                    {
                        return $this;
                    }

                    public function limit(int $count): static
                    {
                        return $this;
                    }

                    public function toSql(): string
                    {
                        return "select * from {$this->table}";
                    }

                    public function getBindings(): array
                    {
                        return $this->bindings;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];
                    private array $bindings = [];
                    private array $orders = [];
                    private ?int $limit = null;

                    public function __construct(private string $table) {}

                    public function where(string $column, mixed $operator, mixed $value = null): static
                    {
                        if (func_num_args() === 2) {
                            [$operator, $value] = ['=', $operator];
                        }

                        $this->wheres[] = "$column $operator ?";
                        $this->bindings[] = $value;

                        return $this;
                    }

                    public function orderBy(string $column, string $direction = 'asc'): static
                    {
                        $this->orders[] = $column . ' ' . strtolower($direction);

                        return $this;
                    }

                    public function limit(int $count): static
                    {
                        $this->limit = $count;

                        return $this;
                    }

                    public function toSql(): string
                    {
                        $sql = "select * from {$this->table}";
                        if ($this->wheres) {
                            $sql .= ' where ' . implode(' and ', $this->wheres);
                        }
                        if ($this->orders) {
                            $sql .= ' order by ' . implode(', ', $this->orders);
                        }
                        if ($this->limit !== null) {
                            $sql .= " limit {$this->limit}";
                        }

                        return $sql;
                    }

                    public function getBindings(): array
                    {
                        return $this->bindings;
                    }
                }
                PHP,
            'hint' => '`func_num_args() === 2` tells you the operator was left out (checking `$value === null` would break `where(\'x\', \'=\', null)`). Collect orders in an array and join them with `, `.',
            'checks' => [
                ['label' => 'A full query', 'code' => "\$q = (new Builder('books'))->where('author', '=', 'Herbert')->where('price', '<', 10)->orderBy('price', 'desc')->orderBy('title')->limit(3);\nreturn expect_same(\$q->toSql(), 'select * from books where author = ? and price < ? order by price desc, title asc limit 3');"],
                ['label' => 'Bindings in order', 'code' => "\$q = (new Builder('books'))->where('author', 'Herbert')->where('price', '<', 10);\nreturn expect_same(\$q->getBindings(), ['Herbert', 10]);"],
                ['label' => 'Two arguments mean =', 'code' => "return expect_same((new Builder('books'))->where('author', 'Herbert')->toSql(), 'select * from books where author = ?');"],
                ['label' => 'Nothing added, plain select', 'code' => "return expect_same((new Builder('authors'))->toSql(), 'select * from authors');"],
            ],
        ],
        [
            'title' => 'orWhere and whereIn',
            'prompt' => 'Add `orWhere($column, $operator, $value)`, joined with `or` instead of `and`, and `whereIn($column, array $values)`, which compiles to `column in (?, ?, ?)` with one binding per value. An empty `whereIn` can never match, so it compiles to `0 = 1` (as Laravel does). The first condition never has `and` or `or` before it.',
            'starter_code' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];
                    private array $bindings = [];

                    public function __construct(private string $table) {}

                    public function where(string $column, string $operator, mixed $value): static
                    {
                        $this->wheres[] = "$column $operator ?";
                        $this->bindings[] = $value;

                        return $this;
                    }

                    public function toSql(): string
                    {
                        $where = $this->wheres ? ' where ' . implode(' and ', $this->wheres) : '';

                        return "select * from {$this->table}$where";
                    }

                    public function getBindings(): array
                    {
                        return $this->bindings;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];
                    private array $bindings = [];

                    public function __construct(private string $table) {}

                    private function add(string $boolean, string $sql, array $bindings): static
                    {
                        $this->wheres[] = [$boolean, $sql];
                        array_push($this->bindings, ...$bindings);

                        return $this;
                    }

                    public function where(string $column, string $operator, mixed $value): static
                    {
                        return $this->add('and', "$column $operator ?", [$value]);
                    }

                    public function orWhere(string $column, string $operator, mixed $value): static
                    {
                        return $this->add('or', "$column $operator ?", [$value]);
                    }

                    public function whereIn(string $column, array $values): static
                    {
                        if ($values === []) {
                            return $this->add('and', '0 = 1', []);
                        }

                        $placeholders = implode(', ', array_fill(0, count($values), '?'));

                        return $this->add('and', "$column in ($placeholders)", array_values($values));
                    }

                    public function toSql(): string
                    {
                        $sql = "select * from {$this->table}";
                        foreach ($this->wheres as $index => [$boolean, $condition]) {
                            $sql .= ($index === 0 ? ' where ' : " $boolean ") . $condition;
                        }

                        return $sql;
                    }

                    public function getBindings(): array
                    {
                        return $this->bindings;
                    }
                }
                PHP,
            'hint' => 'Store each condition together with its joining word (`and` or `or`) and use the word only from the second condition on. `array_fill(0, count($values), \'?\')` makes the placeholders.',
            'checks' => [
                ['label' => 'orWhere joins with or', 'code' => "\$q = (new Builder('books'))->where('author', '=', 'Herbert')->orWhere('author', '=', 'Clarke');\nreturn expect_same([\$q->toSql(), \$q->getBindings()], ['select * from books where author = ? or author = ?', ['Herbert', 'Clarke']]);"],
                ['label' => 'whereIn has one placeholder per value', 'code' => "\$q = (new Builder('books'))->where('price', '<', 10)->whereIn('id', [3, 8, 13]);\nreturn expect_same([\$q->toSql(), \$q->getBindings()], ['select * from books where price < ? and id in (?, ?, ?)', [10, 3, 8, 13]]);"],
                ['label' => 'An empty whereIn never matches', 'code' => "\$q = (new Builder('books'))->whereIn('id', []);\nreturn expect_same([\$q->toSql(), \$q->getBindings()], ['select * from books where 0 = 1', []]);"],
                ['label' => 'The first condition has no joining word', 'code' => "return expect_same((new Builder('books'))->orWhere('stock', '>', 0)->toSql(), 'select * from books where stock > ?');"],
            ],
        ],
        [
            'title' => 'Attributes and casts',
            'prompt' => 'Finish `Model`\'s `__get()` and `__set()`. Reading applies the cast in `$casts`: `int`, `float`, `bool`, or `array` (stored as a JSON string, decoded to an array). Writing an `array`-cast attribute stores it as JSON; everything else is stored as given. Attributes that aren\'t set read as `null`, whatever their cast.',
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    protected array $casts = [];

                    public function __construct(protected array $attributes = []) {}

                    public function __get(string $key): mixed
                    {
                        return $this->attributes[$key] ?? null;
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        $this->attributes[$key] = $value;
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                }

                class Book extends Model
                {
                    protected array $casts = ['stock' => 'int', 'price' => 'float', 'featured' => 'bool', 'tags' => 'array'];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    protected array $casts = [];

                    public function __construct(protected array $attributes = []) {}

                    public function __get(string $key): mixed
                    {
                        $value = $this->attributes[$key] ?? null;
                        if ($value === null) {
                            return null;
                        }

                        return match ($this->casts[$key] ?? null) {
                            'int' => (int) $value,
                            'float' => (float) $value,
                            'bool' => (bool) $value,
                            'array' => json_decode($value, true),
                            default => $value,
                        };
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        if (($this->casts[$key] ?? null) === 'array' && $value !== null) {
                            $value = json_encode($value);
                        }

                        $this->attributes[$key] = $value;
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                }

                class Book extends Model
                {
                    protected array $casts = ['stock' => 'int', 'price' => 'float', 'featured' => 'bool', 'tags' => 'array'];
                }
                PHP,
            'hint' => 'A `match` on `$this->casts[$key] ?? null` picks the conversion. Return `null` early for missing attributes so `(int) null` doesn\'t turn them into `0`.',
            'checks' => [
                ['label' => 'Database strings are cast on read', 'code' => "\$book = new Book(['stock' => '3', 'price' => '9.99', 'featured' => '0', 'title' => 'Dune']);\nreturn expect_same([\$book->stock, \$book->price, \$book->featured, \$book->title], [3, 9.99, false, 'Dune']);"],
                ['label' => 'Array casts are JSON in the attributes', 'code' => "\$book = new Book();\n\$book->tags = ['sci-fi', 'classic'];\nreturn expect_same([\$book->getAttributes()['tags'] ?? null, \$book->tags], ['[\"sci-fi\",\"classic\"]', ['sci-fi', 'classic']]);"],
                ['label' => 'Missing attributes are null', 'code' => "\$book = new Book();\nreturn expect_same([\$book->stock, \$book->tags, \$book->subtitle], [null, null, null]);"],
                ['label' => 'Other values are stored as given', 'code' => "\$book = new Book();\n\$book->stock = '7';\nreturn expect_same([\$book->getAttributes()['stock'] ?? null, \$book->stock], ['7', 7]);"],
            ],
        ],
        [
            'title' => 'Accessors and mutators',
            'prompt' => 'Make `__get($key)` call a method named `get{Studly}Attribute()` when the model has one, and `__set($key, $value)` call `set{Studly}Attribute($value)` when it has one, where `{Studly}` is the key in StudlyCase (`full_name` → `FullName`). Otherwise they use the attributes array as before. Then give `Author` a `full_name` accessor (first and last name with a space) and an `email` mutator that stores the address in lower case.',
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    public function __construct(protected array $attributes = []) {}

                    public function __get(string $key): mixed
                    {
                        return $this->attributes[$key] ?? null;
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        $this->attributes[$key] = $value;
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                }

                class Author extends Model
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    public function __construct(protected array $attributes = []) {}

                    protected static function studly(string $key): string
                    {
                        return str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                    }

                    public function __get(string $key): mixed
                    {
                        $accessor = 'get' . static::studly($key) . 'Attribute';
                        if (method_exists($this, $accessor)) {
                            return $this->$accessor();
                        }

                        return $this->attributes[$key] ?? null;
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        $mutator = 'set' . static::studly($key) . 'Attribute';
                        if (method_exists($this, $mutator)) {
                            $this->$mutator($value);
                            return;
                        }

                        $this->attributes[$key] = $value;
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                }

                class Author extends Model
                {
                    public function getFullNameAttribute(): string
                    {
                        return "{$this->first_name} {$this->last_name}";
                    }

                    public function setEmailAttribute(string $value): void
                    {
                        $this->attributes['email'] = strtolower($value);
                    }
                }
                PHP,
            'hint' => 'Build the method name, then check `method_exists($this, $name)`. Inside the mutator, write to `$this->attributes[\'email\']` directly, or `__set()` would call the mutator again forever.',
            'checks' => [
                ['label' => 'full_name is worked out', 'code' => "\$author = new Author(['first_name' => 'Susanna', 'last_name' => 'Clarke']);\nreturn expect_same(\$author->full_name, 'Susanna Clarke');"],
                ['label' => 'email is stored in lower case', 'code' => "\$author = new Author();\n\$author->email = 'Susanna@Example.COM';\nreturn expect_same(\$author->getAttributes(), ['email' => 'susanna@example.com']);"],
                ['label' => 'Accessors work for any model', 'code' => "eval('class Shelf extends Model { public function getShelfCodeAttribute(): string { return \"S-\" . \$this->attributes[\"number\"]; } }');\nreturn expect_same((new Shelf(['number' => 4]))->shelf_code, 'S-4');"],
                ['label' => 'Plain attributes still work', 'code' => "\$author = new Author(['first_name' => 'Frank']);\n\$author->country = 'US';\nreturn expect_same([\$author->first_name, \$author->country, \$author->missing], ['Frank', 'US', null]);"],
            ],
        ],
        [
            'title' => 'What changed?',
            'prompt' => 'Add change tracking to `Model`. The constructor\'s attributes are the original. `isDirty(?string $key = null)` says whether that attribute (or, with no key, any attribute) differs from the original; `getDirty()` returns just the changed attributes with their new values; `syncOriginal()` makes the current attributes the new original, as `save()` does. A value set back to its original is not dirty.',
            'starter_code' => <<<'PHP'
                <?php

                class Model
                {
                    public function __construct(protected array $attributes = []) {}

                    public function __get(string $key): mixed
                    {
                        return $this->attributes[$key] ?? null;
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        $this->attributes[$key] = $value;
                    }

                    public function isDirty(?string $key = null): bool
                    {
                        return false;
                    }

                    public function getDirty(): array
                    {
                        return [];
                    }

                    public function syncOriginal(): void
                    {
                    }
                }

                class Book extends Model {}
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Model
                {
                    protected array $original;

                    public function __construct(protected array $attributes = [])
                    {
                        $this->original = $attributes;
                    }

                    public function __get(string $key): mixed
                    {
                        return $this->attributes[$key] ?? null;
                    }

                    public function __set(string $key, mixed $value): void
                    {
                        $this->attributes[$key] = $value;
                    }

                    public function getDirty(): array
                    {
                        return array_filter(
                            $this->attributes,
                            fn ($value, $key) => ! array_key_exists($key, $this->original) || $this->original[$key] !== $value,
                            ARRAY_FILTER_USE_BOTH,
                        );
                    }

                    public function isDirty(?string $key = null): bool
                    {
                        $dirty = $this->getDirty();

                        return $key === null ? $dirty !== [] : array_key_exists($key, $dirty);
                    }

                    public function syncOriginal(): void
                    {
                        $this->original = $this->attributes;
                    }
                }

                class Book extends Model {}
                PHP,
            'hint' => 'Keep a copy of the attributes in `$original` from the constructor. `getDirty()` keeps the attributes that are new or `!==` their original; `isDirty()` can be built on it.',
            'checks' => [
                ['label' => 'Changes are dirty', 'code' => "\$book = new Book(['title' => 'Dune', 'price' => 9.99]);\n\$book->price = 12.5;\nreturn expect_same([\$book->isDirty(), \$book->isDirty('price'), \$book->isDirty('title'), \$book->getDirty()], [true, true, false, ['price' => 12.5]]);"],
                ['label' => 'A fresh model is clean', 'code' => "\$book = new Book(['title' => 'Dune']);\nreturn expect_same([\$book->isDirty(), \$book->getDirty()], [false, []]);"],
                ['label' => 'Setting back the original is clean', 'code' => "\$book = new Book(['price' => 9.99]);\n\$book->price = 12.5;\n\$book->price = 9.99;\nreturn expect_same(\$book->isDirty('price'), false);"],
                ['label' => 'New attributes are dirty; syncOriginal() cleans', 'code' => "\$book = new Book(['title' => 'Dune']);\n\$book->subtitle = 'Book one';\nif (\$book->getDirty() !== ['subtitle' => 'Book one']) return 'A new attribute should be dirty.';\n\$book->syncOriginal();\nreturn expect_same(\$book->isDirty(), false);"],
            ],
        ],
        [
            'title' => 'Scopes and static calls',
            'prompt' => 'Make `Book::where(...)` and `Book::popular()` work. `Model::__callStatic()` creates `new Builder(new static())` and forwards the call to it. `Builder::__call($method, $arguments)` calls the model\'s `scope{Method}()` (first letter upper-cased) with the builder and the arguments, then returns the builder; if there is no such scope it throws `BadMethodCallException`. Then add `scopePopular(Builder $query, int $minimum = 1000)` to `Book`, adding `where(\'sales\', \'>=\', $minimum)`.',
            'starter_code' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];

                    public function __construct(public Model $model) {}

                    public function where(string $column, string $operator, mixed $value): static
                    {
                        $this->wheres[] = "$column $operator " . var_export($value, true);

                        return $this;
                    }

                    public function toSql(): string
                    {
                        $where = $this->wheres ? ' where ' . implode(' and ', $this->wheres) : '';

                        return 'select * from ' . $this->model->table . $where;
                    }
                }

                abstract class Model
                {
                    public string $table = '';
                }

                class Book extends Model
                {
                    public string $table = 'books';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Builder
                {
                    private array $wheres = [];

                    public function __construct(public Model $model) {}

                    public function where(string $column, string $operator, mixed $value): static
                    {
                        $this->wheres[] = "$column $operator " . var_export($value, true);

                        return $this;
                    }

                    public function toSql(): string
                    {
                        $where = $this->wheres ? ' where ' . implode(' and ', $this->wheres) : '';

                        return 'select * from ' . $this->model->table . $where;
                    }

                    public function __call(string $method, array $arguments): static
                    {
                        $scope = 'scope' . ucfirst($method);
                        if (! method_exists($this->model, $scope)) {
                            throw new BadMethodCallException("Call to undefined method Builder::$method().");
                        }

                        $this->model->$scope($this, ...$arguments);

                        return $this;
                    }
                }

                abstract class Model
                {
                    public string $table = '';

                    public static function __callStatic(string $method, array $arguments): mixed
                    {
                        return (new Builder(new static()))->$method(...$arguments);
                    }
                }

                class Book extends Model
                {
                    public string $table = 'books';

                    public function scopePopular(Builder $query, int $minimum = 1000): void
                    {
                        $query->where('sales', '>=', $minimum);
                    }
                }
                PHP,
            'hint' => 'In `__callStatic()`, `new static()` is the model class that was called. `$this->model->$scope($this, ...$arguments)` runs the scope; return `$this` so the chain carries on.',
            'checks' => [
                ['label' => 'Book::where() starts a query', 'code' => "return expect_same(Book::where('author', '=', 'Herbert')->toSql(), \"select * from books where author = 'Herbert'\");"],
                ['label' => 'Scopes can be called statically', 'code' => "return expect_same(Book::popular()->toSql(), 'select * from books where sales >= 1000');"],
                ['label' => 'Scopes chain and take arguments', 'code' => "return expect_same(Book::where('stock', '>', 0)->popular(5000)->toSql(), 'select * from books where stock > 0 and sales >= 5000');"],
                ['label' => 'Unknown methods throw', 'code' => "return expect_throws(BadMethodCallException::class, fn () => Book::where('a', '=', 1)->cheapest());"],
            ],
        ],
    ],
];
