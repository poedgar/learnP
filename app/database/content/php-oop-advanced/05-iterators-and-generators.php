<?php

// Lesson 5 of the Advanced OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'iterators-and-generators',
    'title' => 'Iterators and generators',
    'summary' => 'Make your objects work with foreach using IteratorAggregate and Iterator, and produce values on demand with generators, yield, keys, lazy pipelines and yield from.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function countdown(int $from): Generator
        {
            for ($number = $from; $number > 0; $number--) {
                yield $number;
            }
        }

        foreach (countdown(5) as $number) {
            echo "$number... ";
        }
        echo 'Lift off!';
        PHP,

    'quiz' => [
        [
            'question' => 'Without implementing any interface, what does `foreach` loop over in an object?',
            'options' => [
                'Nothing; it\'s an error',
                'Its public properties',
                'All of its properties, private ones included',
                'Its methods',
            ],
            'correct_option' => 1,
            'explanation' => 'By default `foreach` visits the properties the loop is allowed to see. Implement `IteratorAggregate` or `Iterator` to choose what\'s visited instead.',
        ],
        [
            'question' => 'Which method does `IteratorAggregate` require?',
            'options' => ['`current()`', '`getIterator()`', '`__invoke()`', '`next()`'],
            'correct_option' => 1,
            'explanation' => '`getIterator()` returns something `foreach` can walk, such as an `ArrayIterator` or a generator.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function numbers(): Generator
                {
                    echo 'start ';
                    yield 1;
                    yield 2;
                }

                $numbers = numbers();
                echo 'made ';
                foreach ($numbers as $n) {
                    echo "$n ";
                }
                PHP,
            'options' => ['`start made 1 2 `', '`made start 1 2 `', '`made 1 2 `', '`start 1 2 made `'],
            'correct_option' => 1,
            'explanation' => 'Calling a generator function doesn\'t run any of its code. It starts only when the loop asks for the first value.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function prices(): Generator
                {
                    yield 'Dune' => 19.99;
                    yield 'Emma' => 7.99;
                }

                foreach (prices() as $title => $price) {
                    echo "$title=$price ";
                }
                PHP,
            'options' => ['`0=19.99 1=7.99 `', '`Dune=19.99 Emma=7.99 `', '`19.99=Dune 7.99=Emma `', 'An error: generators can\'t have keys'],
            'correct_option' => 1,
            'explanation' => '`yield $key => $value` chooses the key, just like an array entry.',
        ],
        [
            'question' => 'Why can a generator describe an endless sequence without running out of memory?',
            'options' => [
                'PHP compresses the values',
                'It only works out each value when the loop asks for it, and doesn\'t store them',
                'Generators are limited to 1,000 values',
                'It can\'t; that would run forever',
            ],
            'correct_option' => 1,
            'explanation' => 'A generator pauses at each `yield`. As long as the loop stops (with `break`), nothing more is ever calculated.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function letters(): Generator
                {
                    yield 'a';
                    yield from ['b', 'c'];
                }

                echo implode('', iterator_to_array(letters(), false));
                PHP,
            'options' => ['`abc`', '`a`', '`bc`', '`ab`'],
            'correct_option' => 0,
            'explanation' => '`yield from` passes on every value of the array. With `false`, `iterator_to_array()` numbers them 0, 1, 2 instead of reusing keys.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function letters(): Generator
                {
                    yield 'a';
                    yield from ['b', 'c'];
                }

                echo implode('', iterator_to_array(letters()));
                PHP,
            'options' => ['`abc`', '`bc`', '`ac`', '`a`'],
            'correct_option' => 1,
            'explanation' => '`a` has key 0, then the array\'s values arrive with their own keys 0 and 1. Keeping keys, `b` overwrites `a`.',
        ],
        [
            'question' => 'Which five methods does the `Iterator` interface require?',
            'options' => [
                '`start`, `stop`, `get`, `set`, `count`',
                '`rewind`, `valid`, `current`, `key`, `next`',
                '`getIterator`, `count`, `offsetGet`, `offsetSet`, `offsetExists`',
                '`first`, `last`, `previous`, `next`, `reset`',
            ],
            'correct_option' => 1,
            'explanation' => '`foreach` calls `rewind()` once, then `valid()`, `current()`, `key()` and `next()` for each step.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Loop over the basket',
            'prompt' => 'Make `Basket` work with `foreach` by implementing `IteratorAggregate`: looping over it visits each item, with the item\'s name as the key and its price as the value.',
            'starter_code' => <<<'PHP'
                <?php

                class Basket
                {
                    private array $items = [];

                    public function add(string $name, float $price): void
                    {
                        $this->items[$name] = $price;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Basket implements IteratorAggregate
                {
                    private array $items = [];

                    public function add(string $name, float $price): void
                    {
                        $this->items[$name] = $price;
                    }

                    public function getIterator(): Iterator
                    {
                        return new ArrayIterator($this->items);
                    }
                }
                PHP,
            'hint' => 'Add `implements IteratorAggregate` and a `getIterator(): Iterator` that returns `new ArrayIterator($this->items)` (or `yield from $this->items`).',
            'checks' => [
                ['label' => 'Basket implements IteratorAggregate', 'code' => "return (new Basket() instanceof IteratorAggregate) ?: 'Basket should implement IteratorAggregate.';"],
                ['label' => 'foreach visits each item, name => price', 'code' => <<<'PHP'
                    $basket = new Basket();
                    $basket->add('Dune', 19.99);
                    $basket->add('Emma', 7.99);
                    $seen = [];
                    foreach ($basket as $name => $price) {
                        $seen[$name] = $price;
                    }
                    return expect_same($seen, ['Dune' => 19.99, 'Emma' => 7.99]);
                    PHP],
                ['label' => 'An empty basket loops zero times', 'code' => 'return expect_same(iterator_to_array(new Basket()), []);'],
            ],
        ],
        [
            'title' => 'Even numbers',
            'prompt' => "Write a generator function `evens(int \$from, int \$to): Generator` that yields every even number from `\$from` to `\$to`, including both ends when they're even. `evens(3, 10)` yields 4, 6, 8 and 10.",
            'starter_code' => <<<'PHP'
                <?php

                function evens(int $from, int $to): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function evens(int $from, int $to): Generator
                {
                    for ($n = $from; $n <= $to; $n++) {
                        if ($n % 2 === 0) {
                            yield $n;
                        }
                    }
                }
                PHP,
            'hint' => 'Loop from `$from` to `$to`, and `yield $n` when `$n % 2 === 0`.',
            'checks' => [
                ['label' => 'evens() is a generator', 'code' => "return (evens(1, 2) instanceof Generator) ?: 'evens() should be a generator function (use yield).';"],
                ['label' => 'evens(3, 10) yields 4, 6, 8, 10', 'code' => 'return expect_same(iterator_to_array(evens(3, 10), false), [4, 6, 8, 10]);'],
                ['label' => 'Both ends are included when even', 'code' => 'return expect_same(iterator_to_array(evens(2, 6), false), [2, 4, 6]);'],
            ],
        ],
        [
            'title' => 'The first few',
            'prompt' => 'Write a generator function `take(iterable $items, int $count): Generator` that yields the first `$count` values of any iterable, keeping their keys, and then stops, even if the iterable is endless.',
            'starter_code' => <<<'PHP'
                <?php

                function take(iterable $items, int $count): Generator
                {
                    foreach ($items as $key => $value) {
                        yield $key => $value;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function take(iterable $items, int $count): Generator
                {
                    if ($count <= 0) {
                        return;
                    }

                    foreach ($items as $key => $value) {
                        yield $key => $value;

                        if (--$count === 0) {
                            return;
                        }
                    }
                }
                PHP,
            'hint' => 'Count down as you yield, and `return` from the generator once you\'ve yielded enough. Check for a count of 0 before the loop.',
            'checks' => [
                ['label' => 'take() keeps the first values and their keys', 'code' => "return expect_same(iterator_to_array(take(['a' => 1, 'b' => 2, 'c' => 3], 2)), ['a' => 1, 'b' => 2]);"],
                ['label' => 'It stops on an endless sequence', 'code' => <<<'PHP'
                    $forever = (function () {
                        $n = 0;
                        while (true) {
                            yield $n++;
                        }
                    })();
                    $seen = [];
                    foreach (take($forever, 3) as $value) {
                        $seen[] = $value;
                        if (count($seen) > 10) return 'take($forever, 3) kept going past 3 values.';
                    }
                    return expect_same($seen, [0, 1, 2]);
                    PHP],
                ['label' => 'Asking for more than there are gives them all', 'code' => 'return expect_same(iterator_to_array(take([5, 6], 10)), [5, 6]);'],
                ['label' => 'take(…, 0) gives nothing', 'code' => 'return expect_same(iterator_to_array(take([5, 6], 0)), []);'],
            ],
        ],
        [
            'title' => 'Map lazily',
            'prompt' => 'Write a generator function `mapLazy(callable $function, iterable $items): Generator` that yields `$function($value)` for each value, keeping its key, and only calls `$function` for a value when that value is asked for.',
            'starter_code' => <<<'PHP'
                <?php

                function mapLazy(callable $function, iterable $items): Generator
                {
                    $results = [];
                    foreach ($items as $key => $value) {
                        $results[$key] = $function($value);
                    }

                    yield from $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function mapLazy(callable $function, iterable $items): Generator
                {
                    foreach ($items as $key => $value) {
                        yield $key => $function($value);
                    }
                }
                PHP,
            'hint' => 'Yield inside the loop, `yield $key => $function($value);`, rather than building an array first.',
            'checks' => [
                ['label' => 'Values are mapped and keys kept', 'code' => "return expect_same(iterator_to_array(mapLazy(strtoupper(...), ['x' => 'dune', 'y' => 'emma'])), ['x' => 'DUNE', 'y' => 'EMMA']);"],
                ['label' => 'Only the values asked for are mapped', 'code' => <<<'PHP'
                    $calls = 0;
                    $mapped = mapLazy(function ($n) use (&$calls) {
                        $calls++;
                        return $n * 2;
                    }, [1, 2, 3, 4, 5]);
                    foreach ($mapped as $value) {
                        if ($value === 4) break;
                    }
                    return expect_same($calls, 2);
                    PHP],
            ],
        ],
        [
            'title' => 'Every book in the shop',
            'prompt' => '`Shop` holds several `Shelf` objects, and each `Shelf` already works with `foreach`. Make `Shop` work with `foreach` too, visiting every title on every shelf in order. Use a generator for `getIterator()`, with `yield from`. (Number the titles from 0 if you collect them with `iterator_to_array($shop, false)`.)',
            'starter_code' => <<<'PHP'
                <?php

                class Shelf implements IteratorAggregate
                {
                    public function __construct(private array $titles) {}

                    public function getIterator(): Iterator
                    {
                        return new ArrayIterator($this->titles);
                    }
                }

                class Shop implements IteratorAggregate
                {
                    public function __construct(private array $shelves) {}

                    public function getIterator(): Iterator
                    {
                        return new ArrayIterator($this->shelves);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Shelf implements IteratorAggregate
                {
                    public function __construct(private array $titles) {}

                    public function getIterator(): Iterator
                    {
                        return new ArrayIterator($this->titles);
                    }
                }

                class Shop implements IteratorAggregate
                {
                    public function __construct(private array $shelves) {}

                    public function getIterator(): Generator
                    {
                        foreach ($this->shelves as $shelf) {
                            yield from $shelf;
                        }
                    }
                }
                PHP,
            'hint' => 'In `getIterator()`, loop over the shelves and `yield from $shelf;` for each one.',
            'checks' => [
                ['label' => 'Every title on every shelf, in order', 'code' => <<<'PHP'
                    $shop = new Shop([new Shelf(['The Hobbit', 'Circe']), new Shelf(['Dracula']), new Shelf([]), new Shelf(['Dune'])]);
                    return expect_same(iterator_to_array($shop, false), ['The Hobbit', 'Circe', 'Dracula', 'Dune']);
                    PHP],
                ['label' => 'getIterator() is a generator', 'code' => "return ((new Shop([]))->getIterator() instanceof Generator) ?: 'Write getIterator() as a generator, with yield.';"],
            ],
        ],
        [
            'title' => 'Range by hand',
            'prompt' => "Finish the `Range` iterator by hand, implementing `Iterator`'s five methods. `new Range(\$from, \$to, \$step)` gives the numbers from `\$from` up to `\$to` (included if reached), going up by `\$step`, with keys 0, 1, 2…: `new Range(0, 10, 5)` gives 0, 5, 10. Looping again starts again from `\$from`.",
            'starter_code' => <<<'PHP'
                <?php

                class Range implements Iterator
                {
                    private int $current = 0;
                    private int $position = 0;

                    public function __construct(private int $from, private int $to, private int $step = 1) {}

                    public function rewind(): void {}
                    public function valid(): bool { return false; }
                    public function current(): int { return 0; }
                    public function key(): int { return 0; }
                    public function next(): void {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Range implements Iterator
                {
                    private int $current = 0;
                    private int $position = 0;

                    public function __construct(private int $from, private int $to, private int $step = 1) {}

                    public function rewind(): void
                    {
                        $this->current = $this->from;
                        $this->position = 0;
                    }

                    public function valid(): bool
                    {
                        return $this->current <= $this->to;
                    }

                    public function current(): int
                    {
                        return $this->current;
                    }

                    public function key(): int
                    {
                        return $this->position;
                    }

                    public function next(): void
                    {
                        $this->current += $this->step;
                        $this->position++;
                    }
                }
                PHP,
            'hint' => '`rewind()` resets `$current` to `$from` and `$position` to 0; `valid()` checks `$current <= $to`; `next()` adds `$step` and one to `$position`.',
            'checks' => [
                ['label' => 'new Range(0, 10, 5) gives 0, 5, 10 with keys 0, 1, 2', 'code' => 'return expect_same(iterator_to_array(new Range(0, 10, 5)), [0 => 0, 1 => 5, 2 => 10]);'],
                ['label' => 'The end is left out when the steps jump past it', 'code' => 'return expect_same(iterator_to_array(new Range(1, 8, 3)), [1, 4, 7]);'],
                ['label' => 'Looping again starts from the beginning', 'code' => <<<'PHP'
                    $range = new Range(1, 3);
                    iterator_to_array($range);
                    return expect_same(iterator_to_array($range), [1, 2, 3]);
                    PHP],
                ['label' => 'A range that ends before it starts is empty', 'code' => 'return expect_same(iterator_to_array(new Range(5, 1)), []);'],
            ],
        ],
    ],
];
