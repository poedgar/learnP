<?php

// Lesson 4 of the Functional PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Checks that test laziness read from a source that throws after 1000 items, so eager code
// fails quickly instead of looping forever.

return [
    'slug' => 'pipelines-and-lazy-collections',
    'title' => 'Pipelines and lazy collections',
    'summary' => 'Compose small functions into pipelines, process long or endless sequences lazily with generators, and build a fluent, immutable collection class.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function countdown(int $from): Generator
        {
            for ($n = $from; $n > 0; $n--) {
                yield $n;
            }
        }

        foreach (countdown(5) as $n) {
            echo "$n... ";
        }
        echo 'lift off!';
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function pipe(callable ...$steps): Closure
                {
                    return fn (mixed $value) => array_reduce($steps, fn ($carry, $step) => $step($carry), $value);
                }

                $calc = pipe(fn ($n) => $n + 1, fn ($n) => $n * 10);
                echo $calc(2);
                PHP,
            'options' => ['`30`', '`21`', '`12`', '`20`'],
            'correct_option' => 0,
            'explanation' => '`pipe()` runs the steps in order: 2 + 1 = 3, then 3 × 10 = 30.',
        ],
        [
            'question' => 'What does PHP 8.5\'s `|>` operator do?',
            'options' => [
                'Compares two values',
                'Passes the value on its left as the only argument to the callable on its right',
                'Runs two functions in parallel',
                'Joins two arrays',
            ],
            'correct_option' => 1,
            'explanation' => '`$text |> trim(...) |> strtolower(...)` is `strtolower(trim($text))`, written in reading order.',
        ],
        [
            'question' => 'What does it mean that `array_map()` is eager?',
            'options' => [
                'It runs faster than a loop',
                'It builds the whole result array straight away, processing every item even if you only need a few',
                'It changes the original array',
                'It only works on small arrays',
            ],
            'correct_option' => 1,
            'explanation' => 'Squaring a thousand numbers to use three of them is the cost of being eager.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function numbers(): Generator
                {
                    echo 'A';
                    yield 1;
                    echo 'B';
                    yield 2;
                    echo 'C';
                }

                $gen = numbers();
                echo 'start ';
                foreach ($gen as $n) {
                    echo $n;
                    break;
                }
                PHP,
            'options' => ['`start A1`', '`ABCstart 12`', '`start A1B2C`', '`Astart 1`'],
            'correct_option' => 0,
            'explanation' => 'Calling `numbers()` runs nothing. The loop runs the body up to the first `yield`, and `break` stops it there.',
        ],
        [
            'question' => 'Why can a generator like `naturals()` be infinite without freezing the program?',
            'options' => [
                'PHP limits generators to 1000 items',
                'It only makes a value when the loop asks for the next one, so the loop decides when to stop',
                'It runs in the background',
                'It can\'t; an infinite generator always freezes',
            ],
            'correct_option' => 1,
            'explanation' => 'Generators are lazy: values are produced on demand, and `break` (or `take()`) stops asking.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function letters(): Generator
                {
                    yield 'a';
                    yield 'b';
                }

                $gen = letters();
                echo implode('', iterator_to_array($gen));

                try {
                    echo implode('', iterator_to_array($gen));
                } catch (Exception $e) {
                    echo ' ' . get_class($e);
                }
                PHP,
            'options' => ['`ab Exception`', '`abab`', '`ab`', '`ab TypeError`'],
            'correct_option' => 0,
            'explanation' => 'A generator can only be run once. Rewinding one that has already finished throws an `Exception`.',
        ],
        [
            'question' => 'What does `iterator_to_array($gen, false)` do differently from `iterator_to_array($gen)`?',
            'options' => [
                'It only takes the first item',
                'It ignores the generator\'s keys and returns a plain list, so items with the same key don\'t overwrite each other',
                'It keeps the generator running',
                'Nothing',
            ],
            'correct_option' => 1,
            'explanation' => 'With `false`, the result is numbered 0, 1, 2… regardless of the yielded keys.',
        ],
        [
            'question' => 'In the theory\'s `Lazy` collection, why does it store a function that **makes** a generator rather than a generator?',
            'options' => [
                'Functions use less memory',
                'A generator can only be run once, so making a fresh one each time lets the collection be looped over again',
                'PHP can\'t store generators in properties',
                'To make it eager',
            ],
            'correct_option' => 1,
            'explanation' => 'Each `toArray()` call starts a new generator from the stored function.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Compose from the right',
            'prompt' => 'Write `compose(callable ...$fns): Closure`, the mirror image of `pipe()`: it runs the functions from **right to left**, so `compose($f, $g)($x)` is `$f($g($x))`. With no functions, it returns the value unchanged.',
            'starter_code' => <<<'PHP'
                <?php

                function compose(callable ...$fns): Closure
                {
                    return fn (mixed $value) => array_reduce($fns, fn ($carry, $fn) => $fn($carry), $value);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function compose(callable ...$fns): Closure
                {
                    return fn (mixed $value) => array_reduce(array_reverse($fns), fn ($carry, $fn) => $fn($carry), $value);
                }
                PHP,
            'hint' => 'It\'s `pipe()` with the list of functions reversed: `array_reverse($fns)`.',
            'checks' => [
                ['label' => 'Right to left', 'code' => "return expect_same([compose(fn (\$n) => \$n * 10, fn (\$n) => \$n + 1)(2), compose(strtoupper(...), trim(...), strrev(...))(' ab ')], [30, 'BA']);"],
                ['label' => 'No functions returns the value', 'code' => "return expect_same(compose()('same'), 'same');"],
            ],
        ],
        [
            'title' => 'A lazy map',
            'prompt' => "Write `lazyMap(callable \$f, iterable \$items): Generator`, which yields `\$f(\$item)` for each item **as it's asked for**, keeping the keys. It must work on an endless source.",
            'starter_code' => <<<'PHP'
                <?php

                function lazyMap(callable $f, iterable $items): Generator
                {
                    $result = [];
                    foreach ($items as $key => $item) {
                        $result[$key] = $f($item);
                    }
                    yield from $result;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function lazyMap(callable $f, iterable $items): Generator
                {
                    foreach ($items as $key => $item) {
                        yield $key => $f($item);
                    }
                }
                PHP,
            'hint' => 'Yield inside the loop instead of building an array: `yield $key => $f($item);`.',
            'checks' => [
                ['label' => 'Maps and keeps keys', 'code' => "return expect_same(iterator_to_array(lazyMap(fn (\$n) => \$n * 2, ['a' => 1, 'b' => 2])), ['a' => 2, 'b' => 4]);"],
                ['label' => 'Works lazily on an endless source', 'code' => "\$source = (function () {\n    for (\$i = 1; ; \$i++) {\n        if (\$i > 1000) {\n            throw new RuntimeException('It read more than 1000 items, so it isn\\'t lazy.');\n        }\n        yield \$i;\n    }\n})();\n\$got = [];\nforeach (lazyMap(fn (\$n) => \$n * \$n, \$source) as \$square) {\n    \$got[] = \$square;\n    if (count(\$got) === 4) {\n        break;\n    }\n}\nreturn expect_same(\$got, [1, 4, 9, 16]);"],
            ],
        ],
        [
            'title' => 'Take while it holds',
            'prompt' => 'Write `takeWhile(callable $test, iterable $items): Generator`, which yields items (with their keys) as long as `$test($item)` is true, and stops at the first item that fails, without reading further.',
            'starter_code' => <<<'PHP'
                <?php

                function takeWhile(callable $test, iterable $items): Generator
                {
                    foreach ($items as $key => $item) {
                        if ($test($item)) {
                            yield $key => $item;
                        }
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function takeWhile(callable $test, iterable $items): Generator
                {
                    foreach ($items as $key => $item) {
                        if (! $test($item)) {
                            return;
                        }
                        yield $key => $item;
                    }
                }
                PHP,
            'hint' => 'When the test fails, `return;` ends the generator.',
            'checks' => [
                ['label' => 'Stops at the first failure', 'code' => 'return expect_same(iterator_to_array(takeWhile(fn ($n) => $n < 10, [3, 5, 12, 4])), [3, 5]);'],
                ['label' => 'Works on an endless source', 'code' => "\$source = (function () {\n    for (\$i = 1; ; \$i++) {\n        if (\$i > 1000) {\n            throw new RuntimeException('It read more than 1000 items, so it didn\\'t stop.');\n        }\n        yield \$i;\n    }\n})();\nreturn expect_same(iterator_to_array(takeWhile(fn (\$n) => \$n * \$n < 50, \$source), false), [1, 2, 3, 4, 5, 6, 7]);"],
            ],
        ],
        [
            'title' => 'An endless Fibonacci sequence',
            'prompt' => 'Write `fibonacci(): Generator`, which yields the Fibonacci numbers 0, 1, 1, 2, 3, 5, 8… forever.',
            'starter_code' => <<<'PHP'
                <?php

                function fibonacci(): Generator
                {
                    yield 0;
                    yield 1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function fibonacci(): Generator
                {
                    [$a, $b] = [0, 1];
                    while (true) {
                        yield $a;
                        [$a, $b] = [$b, $a + $b];
                    }
                }
                PHP,
            'hint' => 'Keep two numbers, and in an endless `while (true)` loop yield the first, then move both along: `[$a, $b] = [$b, $a + $b];`.',
            'checks' => [
                ['label' => 'The first 12 numbers', 'code' => "\$got = [];\nforeach (fibonacci() as \$n) {\n    \$got[] = \$n;\n    if (count(\$got) === 12) {\n        break;\n    }\n}\nreturn expect_same(\$got, [0, 1, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89]);"],
                ['label' => 'It keeps going', 'code' => "\$count = 0;\nforeach (fibonacci() as \$n) {\n    if (++\$count === 50) {\n        return expect_same(\$n, 7778742049);\n    }\n}\nreturn 'The sequence ended after ' . \$count . ' numbers.';"],
            ],
        ],
        [
            'title' => 'Batches from a stream',
            'prompt' => "Write `chunk(int \$size, iterable \$items): Generator`, which yields lists of `\$size` items, plus a shorter last list if items are left over. It must yield each batch as soon as it's full, so it works on an endless source.",
            'starter_code' => <<<'PHP'
                <?php

                function chunk(int $size, iterable $items): Generator
                {
                    $all = [];
                    foreach ($items as $item) {
                        $all[] = $item;
                    }
                    yield from array_chunk($all, $size);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function chunk(int $size, iterable $items): Generator
                {
                    $batch = [];
                    foreach ($items as $item) {
                        $batch[] = $item;
                        if (count($batch) === $size) {
                            yield $batch;
                            $batch = [];
                        }
                    }
                    if ($batch !== []) {
                        yield $batch;
                    }
                }
                PHP,
            'hint' => 'Collect items in `$batch`; yield it and start a new one whenever it reaches `$size`. After the loop, yield what\'s left if it isn\'t empty.',
            'checks' => [
                ['label' => 'Batches with a short last one', 'code' => "return expect_same(iterator_to_array(chunk(2, ['a', 'b', 'c', 'd', 'e']), false), [['a', 'b'], ['c', 'd'], ['e']]);"],
                ['label' => 'No empty batch at the end', 'code' => 'return expect_same([iterator_to_array(chunk(2, [1, 2, 3, 4]), false), iterator_to_array(chunk(3, []), false)], [[[1, 2], [3, 4]], []]);'],
                ['label' => 'Works on an endless source', 'code' => "\$source = (function () {\n    for (\$i = 1; ; \$i++) {\n        if (\$i > 1000) {\n            throw new RuntimeException('It read more than 1000 items, so it isn\\'t lazy.');\n        }\n        yield \$i;\n    }\n})();\nforeach (chunk(3, \$source) as \$batch) {\n    return expect_same(\$batch, [1, 2, 3]);\n}\nreturn 'No batch was yielded.';"],
            ],
        ],
        [
            'title' => 'A lazy, immutable pipeline',
            'prompt' => 'Write a final class `Pipeline` with a static `of(iterable $items): self`, and methods `map(Closure $f): self`, `filter(Closure $keep): self`, `take(int $count): self` and `toArray(): array` (a plain list). Each step returns a **new** Pipeline and leaves the original unchanged, nothing runs until `toArray()`, and it must work on an endless source.',
            'starter_code' => <<<'PHP'
                <?php

                final class Pipeline
                {
                    private function __construct(private array $items) {}

                    public static function of(iterable $items): self
                    {
                        return new self([...$items]);
                    }

                    public function toArray(): array
                    {
                        return array_values($this->items);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                final class Pipeline
                {
                    private function __construct(private Closure $source) {}

                    public static function of(iterable $items): self
                    {
                        return new self(fn () => yield from $items);
                    }

                    public function map(Closure $f): self
                    {
                        $source = $this->source;

                        return new self(function () use ($source, $f) {
                            foreach ($source() as $item) {
                                yield $f($item);
                            }
                        });
                    }

                    public function filter(Closure $keep): self
                    {
                        $source = $this->source;

                        return new self(function () use ($source, $keep) {
                            foreach ($source() as $item) {
                                if ($keep($item)) {
                                    yield $item;
                                }
                            }
                        });
                    }

                    public function take(int $count): self
                    {
                        $source = $this->source;

                        return new self(function () use ($source, $count) {
                            if ($count <= 0) {
                                return;
                            }
                            foreach ($source() as $item) {
                                yield $item;
                                if (--$count === 0) {
                                    return;
                                }
                            }
                        });
                    }

                    public function toArray(): array
                    {
                        return iterator_to_array(($this->source)(), false);
                    }
                }
                PHP,
            'hint' => 'Store a closure that makes a generator, like the theory\'s `Lazy` class. Each method wraps the current closure in a new one that loops over `$source()` and yields. `take()` returns once it has yielded enough.',
            'checks' => [
                ['label' => 'Filter, map and take', 'code' => 'return expect_same(Pipeline::of([1, 2, 3, 4, 5, 6])->filter(fn ($n) => $n % 2 === 0)->map(fn ($n) => $n * 10)->take(2)->toArray(), [20, 40]);'],
                ['label' => 'Steps return new pipelines', 'code' => "\$base = Pipeline::of([1, 2, 3]);\n\$doubled = \$base->map(fn (\$n) => \$n * 2);\nreturn expect_same([\$base->toArray(), \$doubled->toArray(), \$doubled !== \$base], [[1, 2, 3], [2, 4, 6], true]);"],
                ['label' => 'Nothing runs until toArray()', 'code' => "\$calls = 0;\n\$p = Pipeline::of([1, 2, 3])->map(function (\$n) use (&\$calls) {\n    \$calls++;\n    return \$n;\n});\nreturn expect_same([\$calls, count(\$p->toArray()), \$calls], [0, 3, 3]);"],
                ['label' => 'Works on an endless source', 'code' => "\$source = (function () {\n    for (\$i = 1; ; \$i++) {\n        if (\$i > 1000) {\n            throw new RuntimeException('It read more than 1000 items, so it isn\\'t lazy.');\n        }\n        yield \$i;\n    }\n})();\nreturn expect_same(Pipeline::of(\$source)->filter(fn (\$n) => \$n % 3 === 0)->map(fn (\$n) => \$n * \$n)->take(3)->toArray(), [9, 36, 81]);"],
            ],
        ],
    ],
];
