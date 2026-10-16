<?php

// Lesson 6 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'objects-that-act-like-arrays',
    'title' => 'Objects that act like arrays',
    'summary' => 'Build collection classes that support square brackets with ArrayAccess, count() and foreach, refuse bad values, and control their JSON with JsonSerializable.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class Prices implements ArrayAccess, Countable
        {
            private array $prices = [];

            public function offsetExists(mixed $offset): bool { return isset($this->prices[$offset]); }
            public function offsetGet(mixed $offset): mixed { return $this->prices[$offset] ?? null; }
            public function offsetSet(mixed $offset, mixed $value): void { $this->prices[$offset] = $value; }
            public function offsetUnset(mixed $offset): void { unset($this->prices[$offset]); }
            public function count(): int { return count($this->prices); }
        }

        $prices = new Prices();
        $prices['Dune'] = 19.99;
        $prices['Emma'] = 7.99;
        echo count($prices) . ' prices, Dune costs ' . $prices['Dune'];
        PHP,

    'quiz' => [
        [
            'question' => 'Which interface makes `$collection[\'Dune\']` work on an object?',
            'options' => ['`Countable`', '`ArrayAccess`', '`IteratorAggregate`', '`Stringable`'],
            'correct_option' => 1,
            'explanation' => '`ArrayAccess` maps square brackets to `offsetGet()`, `offsetSet()`, `offsetExists()` and `offsetUnset()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Log implements ArrayAccess
                {
                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        echo var_export($offset, true) . ' ';
                    }

                    public function offsetGet(mixed $offset): mixed { return null; }
                    public function offsetExists(mixed $offset): bool { return false; }
                    public function offsetUnset(mixed $offset): void {}
                }

                $log = new Log();
                $log['a'] = 1;
                $log[] = 2;
                PHP,
            'options' => ["`'a' 0 `", "`'a' NULL `", "`'a' '' `", '`1 2 `'],
            'correct_option' => 1,
            'explanation' => 'Empty brackets pass `null` as the offset. The class decides what that means, usually "add to the end".',
        ],
        [
            'question' => 'Which method does `isset($prices[\'Dune\'])` call?',
            'options' => ['`offsetGet()`', '`offsetExists()`', '`__isset()`', '`count()`'],
            'correct_option' => 1,
            'explanation' => '`isset()` and `empty()` on brackets call `offsetExists()`. `__isset()` is for properties.',
        ],
        [
            'question' => 'A class implements `ArrayAccess` but not `Countable`. What does `count($object)` do?',
            'options' => [
                'Counts the items it holds',
                'Returns 1',
                'Throws a `TypeError`: the object isn\'t countable',
                'Returns 0',
            ],
            'correct_option' => 2,
            'explanation' => 'Each array-like ability is a separate interface. `count()` needs `Countable`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    private float $cost = 8.20;

                    public function __construct(public string $title) {}
                }

                echo json_encode(new Book('Dune'));
                PHP,
            'options' => ['`{"title":"Dune","cost":8.2}`', '`{"title":"Dune"}`', '`{}`', '`"Dune"`'],
            'correct_option' => 1,
            'explanation' => 'Without `JsonSerializable`, `json_encode()` includes only public properties.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Price implements JsonSerializable
                {
                    public function __construct(public float $amount) {}

                    public function jsonSerialize(): string
                    {
                        return '£' . number_format($this->amount, 2);
                    }
                }

                echo json_encode(['price' => new Price(9.5)]);
                PHP,
            'options' => ['`{"price":{"amount":9.5}}`', '`{"price":"£9.50"}`', '`{"price":"\\u00a39.50"}`', '`{"price":9.5}`'],
            'correct_option' => 2,
            'explanation' => '`jsonSerialize()` returned a string, so that\'s what goes in the JSON. `json_encode()` escapes non-ASCII characters such as `£` as `\\u00a3` unless you pass `JSON_UNESCAPED_UNICODE`.',
        ],
        [
            'question' => 'Can you pass a collection object that implements `ArrayAccess` and `IteratorAggregate` to `array_keys()`?',
            'options' => [
                'Yes, it works like an array',
                'Only if it also implements `Countable`',
                'No: PHP\'s array functions need a real array; use `iterator_to_array()` first',
                'Only for numeric keys',
            ],
            'correct_option' => 2,
            'explanation' => 'Array-like interfaces cover brackets, `count()` and `foreach`, not the array functions.',
        ],
        [
            'question' => 'Why might a collection\'s `offsetSet()` throw an exception?',
            'options' => [
                'Because `offsetSet()` must always throw',
                'To refuse values that would break the collection\'s rules, such as a non-book in a book list',
                'To stop `foreach` from working',
                'Because collections can\'t be changed',
            ],
            'correct_option' => 1,
            'explanation' => 'Every write goes through `offsetSet()`, so it\'s the one place to keep the collection valid.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A list of titles',
            'prompt' => "Make `Shelf` implement `ArrayAccess` over its private `\$titles` array:\n\n- `\$shelf[] = 'Dune'` adds a title to the end;\n- `\$shelf[0]` reads a title, and a position with nothing there reads as `null`;\n- `isset(\$shelf[0])` and `unset(\$shelf[0])` work.",
            'starter_code' => <<<'PHP'
                <?php

                class Shelf
                {
                    private array $titles = [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Shelf implements ArrayAccess
                {
                    private array $titles = [];

                    public function offsetExists(mixed $offset): bool
                    {
                        return isset($this->titles[$offset]);
                    }

                    public function offsetGet(mixed $offset): mixed
                    {
                        return $this->titles[$offset] ?? null;
                    }

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        if ($offset === null) {
                            $this->titles[] = $value;
                        } else {
                            $this->titles[$offset] = $value;
                        }
                    }

                    public function offsetUnset(mixed $offset): void
                    {
                        unset($this->titles[$offset]);
                    }
                }
                PHP,
            'hint' => 'Write the four `offset…` methods. In `offsetSet()`, a `null` offset means "add to the end": `$this->titles[] = $value`.',
            'checks' => [
                ['label' => 'Shelf implements ArrayAccess', 'code' => "return (new Shelf() instanceof ArrayAccess) ?: 'Shelf should implement ArrayAccess.';"],
                ['label' => '$shelf[] adds to the end', 'code' => <<<'PHP'
                    $shelf = new Shelf();
                    $shelf[] = 'Dune';
                    $shelf[] = 'Emma';
                    return expect_same([$shelf[0], $shelf[1]], ['Dune', 'Emma']);
                    PHP],
                ['label' => 'An empty position reads as null', 'code' => 'return expect_same((new Shelf())[5], null);'],
                ['label' => 'isset() and unset() work', 'code' => <<<'PHP'
                    $shelf = new Shelf();
                    $shelf[] = 'Dune';
                    $before = isset($shelf[0]);
                    unset($shelf[0]);
                    return expect_same([$before, isset($shelf[0])], [true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Count the copies',
            'prompt' => '`Stock` records how many copies of each title the shop has. Make `count($stock)` give the total number of **copies**, not the number of different titles: 3 copies of Dune and 2 of Emma count as 5.',
            'starter_code' => <<<'PHP'
                <?php

                class Stock
                {
                    private array $copies = [];

                    public function add(string $title, int $copies): void
                    {
                        $this->copies[$title] = ($this->copies[$title] ?? 0) + $copies;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Stock implements Countable
                {
                    private array $copies = [];

                    public function add(string $title, int $copies): void
                    {
                        $this->copies[$title] = ($this->copies[$title] ?? 0) + $copies;
                    }

                    public function count(): int
                    {
                        return array_sum($this->copies);
                    }
                }
                PHP,
            'hint' => 'Implement `Countable`; `count()` returns `array_sum($this->copies)`.',
            'checks' => [
                ['label' => 'Stock is countable', 'code' => "return (new Stock() instanceof Countable) ?: 'Stock should implement Countable.';"],
                ['label' => 'count() adds up the copies', 'code' => <<<'PHP'
                    $stock = new Stock();
                    $stock->add('Dune', 3);
                    $stock->add('Emma', 2);
                    $stock->add('Dune', 1);
                    return expect_same(count($stock), 6);
                    PHP],
                ['label' => 'An empty stock counts 0', 'code' => 'return expect_same(count(new Stock()), 0);'],
            ],
        ],
        [
            'title' => 'Books only',
            'prompt' => "`BookList` implements `ArrayAccess`, but accepts anything. Make `offsetSet()` throw an `InvalidArgumentException` for any value that isn't a `Book`, so nothing else can get in. Books are still added as before.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title) {}
                }

                class BookList implements ArrayAccess
                {
                    private array $books = [];

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        $offset === null ? $this->books[] = $value : $this->books[$offset] = $value;
                    }

                    public function offsetGet(mixed $offset): mixed { return $this->books[$offset] ?? null; }
                    public function offsetExists(mixed $offset): bool { return isset($this->books[$offset]); }
                    public function offsetUnset(mixed $offset): void { unset($this->books[$offset]); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title) {}
                }

                class BookList implements ArrayAccess
                {
                    private array $books = [];

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        if (! $value instanceof Book) {
                            throw new InvalidArgumentException('Only books can go in a BookList.');
                        }

                        $offset === null ? $this->books[] = $value : $this->books[$offset] = $value;
                    }

                    public function offsetGet(mixed $offset): mixed { return $this->books[$offset] ?? null; }
                    public function offsetExists(mixed $offset): bool { return isset($this->books[$offset]); }
                    public function offsetUnset(mixed $offset): void { unset($this->books[$offset]); }
                }
                PHP,
            'hint' => 'At the start of `offsetSet()`: `if (! $value instanceof Book) { throw new InvalidArgumentException(…); }`.',
            'checks' => [
                ['label' => 'Books can be added', 'code' => <<<'PHP'
                    $list = new BookList();
                    $list[] = new Book('Dune');
                    return expect_same($list[0]->title, 'Dune');
                    PHP],
                ['label' => 'A string is refused', 'code' => <<<'PHP'
                    return expect_throws(InvalidArgumentException::class, function () {
                        $list = new BookList();
                        $list[] = 'Dune';
                    });
                    PHP],
                ['label' => 'Other objects are refused too, at any position', 'code' => <<<'PHP'
                    return expect_throws(InvalidArgumentException::class, function () {
                        $list = new BookList();
                        $list['first'] = new stdClass();
                    });
                    PHP],
            ],
        ],
        [
            'title' => 'JSON for the shop\'s API',
            'prompt' => "Make `Book` implement `JsonSerializable` so `json_encode(\$book)` gives only its `title`, its `author` and its price as text with a pound sign and two decimals, under the key `price`:\n\n`{\"title\":\"Dune\",\"author\":\"Frank Herbert\",\"price\":\"£19.99\"}` (with `JSON_UNESCAPED_UNICODE`).\n\nThe shop's private `\$cost` must never appear.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public float $price,
                        private float $cost,
                    ) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book implements JsonSerializable
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public float $price,
                        private float $cost,
                    ) {}

                    public function jsonSerialize(): array
                    {
                        return [
                            'title' => $this->title,
                            'author' => $this->author,
                            'price' => '£' . number_format($this->price, 2),
                        ];
                    }
                }
                PHP,
            'hint' => 'Return an array from `jsonSerialize()`, with `\'price\' => \'£\' . number_format($this->price, 2)`.',
            'checks' => [
                ['label' => 'Book implements JsonSerializable', 'code' => "return (new ReflectionClass('Book'))->implementsInterface(JsonSerializable::class) ?: 'Book should implement JsonSerializable.';"],
                ['label' => 'The JSON has the title, author and formatted price', 'code' => <<<'PHP'
                    $json = json_encode(new Book('Dune', 'Frank Herbert', 19.99, 8.20), JSON_UNESCAPED_UNICODE);
                    return expect_same($json, '{"title":"Dune","author":"Frank Herbert","price":"£19.99"}');
                    PHP],
                ['label' => 'Prices always have two decimals', 'code' => <<<'PHP'
                    $data = json_decode(json_encode(new Book('Emma', 'Jane Austen', 8.5, 3.0)), true);
                    return expect_same($data['price'] ?? null, '£8.50');
                    PHP],
            ],
        ],
        [
            'title' => 'Headers in any case',
            'prompt' => "HTTP header names ignore letter case: `Content-Type` and `content-type` are the same header. Make `Headers` implement `ArrayAccess` so that setting, reading, `isset()` and `unset()` all ignore case: after `\$headers['Content-Type'] = 'text/html'`, `\$headers['CONTENT-TYPE']` gives `'text/html'`. A header that isn't set reads as `null`.",
            'starter_code' => <<<'PHP'
                <?php

                class Headers implements ArrayAccess
                {
                    private array $headers = [];

                    public function offsetExists(mixed $offset): bool { return isset($this->headers[$offset]); }
                    public function offsetGet(mixed $offset): mixed { return $this->headers[$offset] ?? null; }
                    public function offsetSet(mixed $offset, mixed $value): void { $this->headers[$offset] = $value; }
                    public function offsetUnset(mixed $offset): void { unset($this->headers[$offset]); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Headers implements ArrayAccess
                {
                    private array $headers = [];

                    public function offsetExists(mixed $offset): bool
                    {
                        return isset($this->headers[strtolower($offset)]);
                    }

                    public function offsetGet(mixed $offset): mixed
                    {
                        return $this->headers[strtolower($offset)] ?? null;
                    }

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        $this->headers[strtolower($offset)] = $value;
                    }

                    public function offsetUnset(mixed $offset): void
                    {
                        unset($this->headers[strtolower($offset)]);
                    }
                }
                PHP,
            'hint' => 'Store and look up every name in one case: `strtolower($offset)` in all four methods.',
            'checks' => [
                ['label' => 'A header can be read in any case', 'code' => <<<'PHP'
                    $headers = new Headers();
                    $headers['Content-Type'] = 'text/html';
                    return expect_same([$headers['content-type'], $headers['CONTENT-TYPE']], ['text/html', 'text/html']);
                    PHP],
                ['label' => 'Setting it again in another case replaces it', 'code' => <<<'PHP'
                    $headers = new Headers();
                    $headers['Accept'] = 'text/html';
                    $headers['ACCEPT'] = 'application/json';
                    return expect_same($headers['accept'], 'application/json');
                    PHP],
                ['label' => 'isset() and unset() ignore case too', 'code' => <<<'PHP'
                    $headers = new Headers();
                    $headers['X-Book'] = 'Dune';
                    $before = isset($headers['x-book']);
                    unset($headers['X-BOOK']);
                    return expect_same([$before, isset($headers['X-Book']), $headers['x-book']], [true, false, null]);
                    PHP],
            ],
        ],
        [
            'title' => 'A collection that does it all',
            'prompt' => "Finish `Collection` so it:\n\n- implements `ArrayAccess` (with `\$c[]` adding to the end), `Countable` and `IteratorAggregate`;\n- has `map(callable \$fn): static`, returning a **new** collection of the results, keys kept;\n- has `filter(callable \$fn): static`, returning a **new** collection of the values for which `\$fn` returns true, keys kept.\n\nThe constructor takes an optional array of starting items.",
            'starter_code' => <<<'PHP'
                <?php

                class Collection
                {
                    public function __construct(private array $items = []) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Collection implements ArrayAccess, Countable, IteratorAggregate
                {
                    public function __construct(private array $items = []) {}

                    public function offsetExists(mixed $offset): bool { return isset($this->items[$offset]); }
                    public function offsetGet(mixed $offset): mixed { return $this->items[$offset] ?? null; }
                    public function offsetUnset(mixed $offset): void { unset($this->items[$offset]); }

                    public function offsetSet(mixed $offset, mixed $value): void
                    {
                        if ($offset === null) {
                            $this->items[] = $value;
                        } else {
                            $this->items[$offset] = $value;
                        }
                    }

                    public function count(): int
                    {
                        return count($this->items);
                    }

                    public function getIterator(): Iterator
                    {
                        return new ArrayIterator($this->items);
                    }

                    public function map(callable $fn): static
                    {
                        return new static(array_map($fn, $this->items));
                    }

                    public function filter(callable $fn): static
                    {
                        return new static(array_filter($this->items, $fn));
                    }
                }
                PHP,
            'hint' => 'The array-like parts are the same as in the lesson. `map()` and `filter()` can return `new static(array_map($fn, $this->items))` and `new static(array_filter($this->items, $fn))`.',
            'checks' => [
                ['label' => 'Brackets, count() and foreach all work', 'code' => <<<'PHP'
                    $c = new Collection();
                    $c[] = 'Dune';
                    $c['fav'] = 'Emma';
                    $seen = [];
                    foreach ($c as $key => $value) {
                        $seen[$key] = $value;
                    }
                    return expect_same([count($c), $c['fav'], $seen], [2, 'Emma', [0 => 'Dune', 'fav' => 'Emma']]);
                    PHP],
                ['label' => 'map() returns a new collection of results, keys kept', 'code' => <<<'PHP'
                    $c = new Collection(['a' => 'dune', 'b' => 'emma']);
                    $upper = $c->map(strtoupper(...));
                    if (! $upper instanceof Collection || $upper === $c) return 'map() should return a new Collection.';
                    return expect_same([iterator_to_array($upper), $c['a']], [['a' => 'DUNE', 'b' => 'EMMA'], 'dune']);
                    PHP],
                ['label' => 'filter() returns a new collection of matching values, keys kept', 'code' => <<<'PHP'
                    $c = new Collection([7.99, 19.99, 9.50]);
                    $cheap = $c->filter(fn ($price) => $price < 10);
                    if (! $cheap instanceof Collection || $cheap === $c) return 'filter() should return a new Collection.';
                    return expect_same([iterator_to_array($cheap), count($c)], [[0 => 7.99, 2 => 9.50], 3]);
                    PHP],
            ],
        ],
    ],
];
