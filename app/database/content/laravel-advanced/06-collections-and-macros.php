<?php

// Lesson 6 of the Advanced Laravel course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'collections-and-macros',
    'title' => 'Collections and macros',
    'summary' => 'How Laravel\'s collections work: an immutable wrapper around an array, record helpers such as `pluck()` and `groupBy()`, macros bound with `Closure::bind()`, higher-order messages and lazy collections.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a filter() method.
        class Collection
        {
            public function __construct(private array $items = []) {}

            public function map(callable $callback): static
            {
                return new static(array_map($callback, $this->items));
            }

            public function implode(string $glue): string
            {
                return implode($glue, $this->items);
            }
        }

        $titles = new Collection(['dune', 'piranesi', 'foundation']);

        echo $titles->map(fn ($title) => ucfirst($title))->implode(', ');
        PHP,

    'quiz' => [
        [
            'question' => 'Why does each collection method return a new collection instead of changing the current one?',
            'options' => [
                'PHP objects can\'t change',
                'So chains are safe: the original stays as it was, and a step can\'t surprise code that still uses it',
                'New objects are faster',
                'Because collections are singletons',
            ],
            'correct_option' => 1,
            'explanation' => 'Each step builds on the previous result without touching it, so keeping a collection around and chaining from it twice works as expected.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $items = ['a', 'b', 'c', 'd'];

                $kept = array_filter($items, fn ($letter) => $letter !== 'a' && $letter !== 'c');

                echo implode(',', array_keys($kept)) . ' ' . json_encode($kept);
                PHP,
            'options' => [
                '`0,1 ["b","d"]`',
                '`1,3 {"1":"b","3":"d"}`',
                '`1,3 ["b","d"]`',
                '`0,1 {"0":"b","1":"d"}`',
            ],
            'correct_option' => 1,
            'explanation' => 'Filtering keeps the original keys, so the result is no longer a list and JSON turns it into an object. That\'s why collections have `values()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $books = [
                    ['id' => 3, 'title' => 'Dune'],
                    ['id' => 8, 'title' => 'Piranesi'],
                ];

                echo json_encode(array_column($books, 'title', 'id'));
                PHP,
            'options' => ['`["Dune","Piranesi"]`', '`{"3":"Dune","8":"Piranesi"}`', '`{"Dune":3,"Piranesi":8}`', '`[3,8]`'],
            'correct_option' => 1,
            'explanation' => '`array_column()` with a third argument keys the values by another field, exactly what `pluck(\'title\', \'id\')` does.',
        ],
        [
            'question' => 'How does a macro\'s closure get access to `$this`, the collection it was called on?',
            'options' => [
                'Macros receive the collection as their first argument',
                'The `Macroable` trait binds the closure to the object with `Closure::bind()` before calling it',
                'Closures always have `$this` from where they are called',
                'Through a global variable',
            ],
            'correct_option' => 1,
            'explanation' => 'Binding returns a copy of the closure whose `$this` is the object, so the macro behaves like a real method.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Shelf
                {
                    private string $label = 'Science fiction';
                }

                $read = fn () => $this->label;

                echo Closure::bind($read, new Shelf(), Shelf::class)();
                PHP,
            'options' => ['`Science fiction`', 'An error: cannot access private property', 'An error: `$this` is not set', 'Nothing'],
            'correct_option' => 0,
            'explanation' => 'Binding with the class as scope lets the closure see private members, as if it were written inside the class.',
        ],
        [
            'question' => 'What is `$books->map->title` short for?',
            'options' => [
                '`$books->title`',
                '`$books->map(fn ($book) => $book->title)`',
                '`$books->pluck(\'map\')`',
                'It is an error',
            ],
            'correct_option' => 1,
            'explanation' => 'Reading `map` as a property returns a proxy; reading `title` on the proxy maps every item to its title.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $made = 0;
                $numbers = (function () use (&$made) {
                    foreach ([1, 2, 3, 4, 5] as $n) {
                        $made++;
                        yield $n;
                    }
                })();

                foreach ($numbers as $n) {
                    if ($n === 2) {
                        break;
                    }
                }

                echo $made;
                PHP,
            'options' => ['`5`', '`2`', '`1`', '`0`'],
            'correct_option' => 1,
            'explanation' => 'A generator produces items only as the loop asks for them. The loop stopped at 2, so only two were made. That\'s how lazy collections save memory.',
        ],
        [
            'question' => 'You need to process a million database rows without loading them all into memory. Which Laravel tool fits?',
            'options' => [
                '`Book::all()->each(...)`',
                'A lazy collection, from `Book::cursor()` or `Book::lazy()`',
                'A macro',
                '`collect(Book::all())`',
            ],
            'correct_option' => 1,
            'explanation' => 'A lazy collection pulls rows one at a time (or in small chunks), so memory use stays flat.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'map, filter and values',
            'prompt' => 'Finish `Collection`: `map()` passes each value **and its key** to the callback and keeps the keys; `filter()` keeps the items the callback returns true for (or that are truthy, when there\'s no callback), keeping keys; `values()` renumbers the keys from 0. Each returns a new collection and leaves the original as it was.',
            'starter_code' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function map(callable $callback): static
                    {
                        return $this;
                    }

                    public function filter(?callable $callback = null): static
                    {
                        return $this;
                    }

                    public function values(): static
                    {
                        return $this;
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function map(callable $callback): static
                    {
                        $keys = array_keys($this->items);

                        return new static(array_combine($keys, array_map($callback, $this->items, $keys)));
                    }

                    public function filter(?callable $callback = null): static
                    {
                        return new static($callback === null
                            ? array_filter($this->items)
                            : array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
                    }

                    public function values(): static
                    {
                        return new static(array_values($this->items));
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'hint' => '`array_map()` with two arrays passes a value and a key, but loses string keys; put them back with `array_combine()`. `array_filter()` keeps keys, and `ARRAY_FILTER_USE_BOTH` passes the key too.',
            'checks' => [
                ['label' => 'map() keeps keys and gets them', 'code' => "return expect_same((new Collection(['a' => 1, 'b' => 2]))->map(fn (\$v, \$k) => \"\$k\$v\")->all(), ['a' => 'a1', 'b' => 'b2']);"],
                ['label' => 'filter() keeps keys; values() renumbers', 'code' => "\$c = (new Collection([5, 12, 8, 20]))->filter(fn (\$n) => \$n > 10);\nreturn expect_same([\$c->all(), \$c->values()->all()], [[1 => 12, 3 => 20], [12, 20]]);"],
                ['label' => 'filter() with no callback drops falsy items', 'code' => "return expect_same((new Collection(['Dune', '', null, 'Piranesi', 0]))->filter()->values()->all(), ['Dune', 'Piranesi']);"],
                ['label' => 'The original is untouched', 'code' => "\$original = new Collection([1, 2, 3]);\n\$doubled = \$original->map(fn (\$n) => \$n * 2);\nif (\$doubled === \$original) return 'map() returned the same collection.';\nreturn expect_same(\$original->all(), [1, 2, 3]);"],
            ],
        ],
        [
            'title' => 'pluck and keyBy',
            'prompt' => 'Add `pluck(string $field, ?string $keyBy = null)` and `keyBy(string $field)`. Records may be arrays or objects, so read fields from either. `pluck()` returns the field from every record, as a list or keyed by `$keyBy`; `keyBy()` returns the records themselves keyed by the field. Both return new collections.',
            'starter_code' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function pluck(string $field, ?string $keyBy = null): static
                    {
                        return $this;
                    }

                    public function keyBy(string $field): static
                    {
                        return $this;
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    private static function field(array|object $record, string $field): mixed
                    {
                        return is_array($record) ? $record[$field] : $record->$field;
                    }

                    public function pluck(string $field, ?string $keyBy = null): static
                    {
                        $plucked = [];
                        foreach ($this->items as $record) {
                            $value = self::field($record, $field);
                            if ($keyBy === null) {
                                $plucked[] = $value;
                            } else {
                                $plucked[self::field($record, $keyBy)] = $value;
                            }
                        }

                        return new static($plucked);
                    }

                    public function keyBy(string $field): static
                    {
                        $keyed = [];
                        foreach ($this->items as $record) {
                            $keyed[self::field($record, $field)] = $record;
                        }

                        return new static($keyed);
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'hint' => 'A small private helper that does `is_array($record) ? $record[$field] : $record->$field` lets both methods read either kind of record.',
            'checks' => [
                ['label' => 'pluck() makes a list', 'code' => "\$books = new Collection([['id' => 3, 'title' => 'Dune'], ['id' => 8, 'title' => 'Piranesi']]);\nreturn expect_same(\$books->pluck('title')->all(), ['Dune', 'Piranesi']);"],
                ['label' => 'pluck() can key by another field', 'code' => "\$books = new Collection([['id' => 3, 'title' => 'Dune'], ['id' => 8, 'title' => 'Piranesi']]);\nreturn expect_same(\$books->pluck('title', 'id')->all(), [3 => 'Dune', 8 => 'Piranesi']);"],
                ['label' => 'Objects work too', 'code' => "\$books = new Collection([(object) ['isbn' => 'a1', 'title' => 'Dune']]);\nreturn expect_same([\$books->pluck('title')->all(), array_keys(\$books->keyBy('isbn')->all())], [['Dune'], ['a1']]);"],
                ['label' => 'keyBy() keys whole records', 'code' => "\$books = new Collection([['id' => 3, 'title' => 'Dune'], ['id' => 8, 'title' => 'Piranesi']]);\nreturn expect_same(\$books->keyBy('id')->all(), [3 => ['id' => 3, 'title' => 'Dune'], 8 => ['id' => 8, 'title' => 'Piranesi']]);"],
            ],
        ],
        [
            'title' => 'groupBy and sortBy',
            'prompt' => 'Add `groupBy(string $field)`, returning a collection whose values are collections of the records in each group (as lists, in their original order), and `sortBy(string $field)`, sorting records by a field in ascending order while keeping each record\'s key. Records are arrays.',
            'starter_code' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function groupBy(string $field): static
                    {
                        return $this;
                    }

                    public function sortBy(string $field): static
                    {
                        return $this;
                    }

                    public function map(callable $callback): static
                    {
                        return new static(array_map($callback, $this->items));
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function groupBy(string $field): static
                    {
                        $groups = [];
                        foreach ($this->items as $record) {
                            $groups[$record[$field]][] = $record;
                        }

                        return new static(array_map(fn (array $group) => new static($group), $groups));
                    }

                    public function sortBy(string $field): static
                    {
                        $items = $this->items;
                        uasort($items, fn (array $a, array $b) => $a[$field] <=> $b[$field]);

                        return new static($items);
                    }

                    public function map(callable $callback): static
                    {
                        return new static(array_map($callback, $this->items));
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'hint' => 'Group into a plain array first, then wrap each group with `new static(...)`. `uasort()` sorts while keeping keys; compare fields with `<=>`.',
            'checks' => [
                ['label' => 'Records are grouped in order', 'code' => "\$books = new Collection([['t' => 'Dune', 'a' => 'Herbert'], ['t' => 'Piranesi', 'a' => 'Clarke'], ['t' => 'Children of Dune', 'a' => 'Herbert']]);\nreturn expect_same(\$books->groupBy('a')->map(fn (\$g) => array_column(\$g->all(), 't'))->all(), ['Herbert' => ['Dune', 'Children of Dune'], 'Clarke' => ['Piranesi']]);"],
                ['label' => 'Each group is a collection', 'code' => "\$groups = (new Collection([['a' => 'x']]))->groupBy('a')->all();\nreturn (\$groups['x'] ?? null) instanceof Collection ?: 'Groups should be collections.';"],
                ['label' => 'sortBy() sorts and keeps keys', 'code' => "\$books = new Collection(['d' => ['price' => 9.99], 'p' => ['price' => 4.5], 'f' => ['price' => 12.0]]);\nreturn expect_same(array_keys(\$books->sortBy('price')->all()), ['p', 'd', 'f']);"],
                ['label' => 'sortBy() leaves the original alone', 'code' => "\$books = new Collection([['n' => 2], ['n' => 1]]);\n\$books->sortBy('n');\nreturn expect_same(\$books->all(), [['n' => 2], ['n' => 1]]);"],
            ],
        ],
        [
            'title' => 'Macroable',
            'prompt' => 'Write a `Macroable` trait and use it in `Collection`. `macro(string $name, Closure $macro)` stores a closure for the class; `hasMacro(string $name)` says whether one exists; calling an unknown method on an object runs the macro with `$this` bound to that object, passing the arguments and returning the result. A method that is neither real nor a macro throws `BadMethodCallException` with the message `Method Collection::shout does not exist.`',
            'starter_code' => <<<'PHP'
                <?php

                trait Macroable
                {
                }

                class Collection
                {
                    use Macroable;

                    public function __construct(protected array $items = []) {}

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                trait Macroable
                {
                    protected static array $macros = [];

                    public static function macro(string $name, Closure $macro): void
                    {
                        static::$macros[$name] = $macro;
                    }

                    public static function hasMacro(string $name): bool
                    {
                        return isset(static::$macros[$name]);
                    }

                    public function __call(string $method, array $arguments): mixed
                    {
                        if (! static::hasMacro($method)) {
                            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
                        }

                        $macro = Closure::bind(static::$macros[$method], $this, static::class);

                        return $macro(...$arguments);
                    }
                }

                class Collection
                {
                    use Macroable;

                    public function __construct(protected array $items = []) {}

                    public function all(): array
                    {
                        return $this->items;
                    }
                }
                PHP,
            'hint' => 'Keep the macros in a `protected static array`. In `__call()`, bind the stored closure with `Closure::bind($macro, $this, static::class)` and call it with the arguments.',
            'checks' => [
                ['label' => 'A macro becomes a method', 'code' => "Collection::macro('toUpper', function () { return new Collection(array_map('strtoupper', \$this->items)); });\nreturn expect_same((new Collection(['dune', 'piranesi']))->toUpper()->all(), ['DUNE', 'PIRANESI']);"],
                ['label' => 'Macros get arguments', 'code' => "Collection::macro('prefixWith', function (string \$prefix) { return array_map(fn (\$v) => \$prefix . \$v, \$this->items); });\nreturn expect_same((new Collection(['a', 'b']))->prefixWith('#'), ['#a', '#b']);"],
                ['label' => 'hasMacro() knows', 'code' => "Collection::macro('second', function () { return \$this->items[1] ?? null; });\nreturn expect_same([Collection::hasMacro('second'), Collection::hasMacro('nope')], [true, false]);"],
                ['label' => 'Unknown methods throw', 'code' => "try { (new Collection())->shout(); } catch (BadMethodCallException \$e) { return expect_same(\$e->getMessage(), 'Method Collection::shout does not exist.'); } catch (Error \$e) { return 'A plain PHP error: ' . \$e->getMessage(); }\nreturn 'Nothing was thrown.';"],
            ],
        ],
        [
            'title' => 'Higher-order messages',
            'prompt' => 'Make `$books->map->title` and `$books->filter->isAvailable()` work. Reading the property `map` or `filter` on a `Collection` returns a `HigherOrderProxy`. Reading a property on the proxy maps or filters by that property; calling a method on it maps or filters by that method\'s result (with the arguments passed on). Write the proxy and `Collection::__get()`; other property names throw an `Exception`.',
            'starter_code' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function map(callable $callback): static
                    {
                        return new static(array_map($callback, $this->items));
                    }

                    public function filter(callable $callback): static
                    {
                        return new static(array_values(array_filter($this->items, $callback)));
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }
                }

                class HigherOrderProxy
                {
                }

                class Book
                {
                    public function __construct(public string $title, public int $stock) {}

                    public function isAvailable(): bool
                    {
                        return $this->stock > 0;
                    }

                    public function priceWithTax(float $price, float $rate): float
                    {
                        return round($price * (1 + $rate), 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}

                    public function map(callable $callback): static
                    {
                        return new static(array_map($callback, $this->items));
                    }

                    public function filter(callable $callback): static
                    {
                        return new static(array_values(array_filter($this->items, $callback)));
                    }

                    public function all(): array
                    {
                        return $this->items;
                    }

                    public function __get(string $name): HigherOrderProxy
                    {
                        if (! in_array($name, ['map', 'filter'], true)) {
                            throw new Exception("Property [$name] does not exist on this collection.");
                        }

                        return new HigherOrderProxy($this, $name);
                    }
                }

                class HigherOrderProxy
                {
                    public function __construct(private Collection $collection, private string $method) {}

                    public function __get(string $property): Collection
                    {
                        return $this->collection->{$this->method}(fn ($item) => $item->$property);
                    }

                    public function __call(string $method, array $arguments): Collection
                    {
                        return $this->collection->{$this->method}(fn ($item) => $item->$method(...$arguments));
                    }
                }

                class Book
                {
                    public function __construct(public string $title, public int $stock) {}

                    public function isAvailable(): bool
                    {
                        return $this->stock > 0;
                    }

                    public function priceWithTax(float $price, float $rate): float
                    {
                        return round($price * (1 + $rate), 2);
                    }
                }
                PHP,
            'hint' => 'The proxy remembers the collection and the method name (`map` or `filter`). Its `__get()` and `__call()` call `$this->collection->{$this->method}(...)` with a closure that reads the property or calls the method on each item.',
            'checks' => [
                ['label' => '->map->title', 'code' => "\$books = new Collection([new Book('Dune', 2), new Book('Piranesi', 0)]);\nreturn expect_same(\$books->map->title->all(), ['Dune', 'Piranesi']);"],
                ['label' => '->filter->isAvailable()', 'code' => "\$books = new Collection([new Book('Dune', 2), new Book('Piranesi', 0), new Book('Foundation', 5)]);\nreturn expect_same(\$books->filter->isAvailable()->map->title->all(), ['Dune', 'Foundation']);"],
                ['label' => 'Arguments are passed on', 'code' => "\$books = new Collection([new Book('Dune', 2)]);\nreturn expect_same(\$books->map->priceWithTax(10.0, 0.2)->all(), [12.0]);"],
                ['label' => 'Other properties throw', 'code' => 'return expect_throws(Exception::class, fn () => (new Collection([]))->reduce);'],
            ],
        ],
        [
            'title' => 'A lazy collection',
            'prompt' => 'Write `LazyCollection`, constructed with a closure that returns a generator. `map()`, `filter()` and `take(int $limit)` each return a new `LazyCollection` that does its work only while being iterated; `all()` runs it and returns the values as a list. Nothing may be pulled from the source before it\'s needed, so `take()` works on endless sources.',
            'starter_code' => <<<'PHP'
                <?php

                class LazyCollection
                {
                    public function __construct(private Closure $source) {}

                    public function map(Closure $callback): static
                    {
                        return $this;
                    }

                    public function filter(Closure $callback): static
                    {
                        return $this;
                    }

                    public function take(int $limit): static
                    {
                        return $this;
                    }

                    public function all(): array
                    {
                        return iterator_to_array(($this->source)(), false);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LazyCollection
                {
                    public function __construct(private Closure $source) {}

                    public function map(Closure $callback): static
                    {
                        $source = $this->source;

                        return new static(function () use ($source, $callback) {
                            foreach ($source() as $item) {
                                yield $callback($item);
                            }
                        });
                    }

                    public function filter(Closure $callback): static
                    {
                        $source = $this->source;

                        return new static(function () use ($source, $callback) {
                            foreach ($source() as $item) {
                                if ($callback($item)) {
                                    yield $item;
                                }
                            }
                        });
                    }

                    public function take(int $limit): static
                    {
                        $source = $this->source;

                        return new static(function () use ($source, $limit) {
                            if ($limit <= 0) {
                                return;
                            }
                            $taken = 0;
                            foreach ($source() as $item) {
                                yield $item;
                                if (++$taken >= $limit) {
                                    return;
                                }
                            }
                        });
                    }

                    public function all(): array
                    {
                        return iterator_to_array(($this->source)(), false);
                    }
                }
                PHP,
            'hint' => 'Each method returns `new static(function () use (...) { foreach ($source() as $item) { ... yield ...; } })`. In `take()`, stop with `return` as soon as you\'ve yielded enough, before asking the source for another item.',
            'checks' => [
                ['label' => 'map, filter and take work together', 'code' => "\$naturals = new LazyCollection(fn () => yield from range(1, 1000));\nreturn expect_same(\$naturals->filter(fn (\$n) => \$n % 2 === 0)->map(fn (\$n) => \$n * 10)->take(3)->all(), [20, 40, 60]);"],
                ['label' => 'Nothing runs until iterated', 'code' => "\$pulled = 0;\n\$lazy = new LazyCollection(function () use (&\$pulled) { foreach ([1, 2, 3] as \$n) { \$pulled++; yield \$n; } });\n\$lazy->map(fn (\$n) => \$n)->filter(fn (\$n) => true)->take(2);\nreturn expect_same(\$pulled, 0);"],
                ['label' => 'take() pulls no more than it needs', 'code' => "\$pulled = 0;\n\$lazy = new LazyCollection(function () use (&\$pulled) { foreach (range(1, 100) as \$n) { \$pulled++; yield \$n; } });\n\$lazy->map(fn (\$n) => \$n * 2)->take(3)->all();\nreturn expect_same(\$pulled, 3);"],
                ['label' => 'take(0) is empty', 'code' => 'return expect_same((new LazyCollection(fn () => yield from [1, 2]))->take(0)->all(), []);'],
            ],
        ],
    ],
];
