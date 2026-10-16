<?php

// Lesson 3 of the Functional PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'currying-and-partial-application',
    'title' => 'Currying and partial application',
    'summary' => 'Fix some of a function\'s arguments in advance to make new functions, write general partial() and curry() helpers, and design functions with their data last.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function partial(callable $f, mixed ...$fixed): Closure
        {
            return fn (mixed ...$rest) => $f(...$fixed, ...$rest);
        }

        $greet = fn (string $greeting, string $name) => "$greeting, $name!";
        $hello = partial($greet, 'Hello');

        echo $hello('Amara') . "\n" . $hello('Ben');
        PHP,

    'quiz' => [
        [
            'question' => 'What does partial application do?',
            'options' => [
                'Calls a function with only some of its arguments, which is an error',
                'Fixes some of a function\'s arguments now, giving a new function that takes the rest',
                'Runs half of a function',
                'Splits a function into two files',
            ],
            'correct_option' => 1,
            'explanation' => '`$withVat = fn ($price) => priceWith(0.2, $price)` partially applies the rate.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function partial(callable $f, mixed ...$fixed): Closure
                {
                    return fn (mixed ...$rest) => $f(...$fixed, ...$rest);
                }

                $between = fn (int $min, int $max, int $n) => $n >= $min && $n <= $max;
                $isDigit = partial($between, 0, 9);

                var_dump($isDigit(7), $isDigit(12));
                PHP,
            'options' => [
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(true)`',
                '`bool(true)` then `bool(true)`',
                'An error',
            ],
            'correct_option' => 0,
            'explanation' => '`$isDigit` is `$between` with `$min` and `$max` fixed as 0 and 9.',
        ],
        [
            'question' => 'What is currying?',
            'options' => [
                'Turning a function of several arguments into a chain of one-argument functions',
                'Caching a function\'s results',
                'Combining two functions into one',
                'Sorting a function\'s arguments',
            ],
            'correct_option' => 0,
            'explanation' => 'A curried `add(a, b)` is called as `add(a)(b)`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $join = fn (string $sep) => fn (array $parts) => implode($sep, $parts);
                $withDashes = $join('-');
                echo $withDashes(['a', 'b', 'c']) . ' ' . $join('+')(['1', '2']);
                PHP,
            'options' => ['`a-b-c 1+2`', '`abc 12`', '`- +`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Each call to `$join` fixes a separator and returns a function waiting for the parts.',
        ],
        [
            'question' => 'Why do functional libraries usually put the data argument last?',
            'options' => [
                'It runs faster',
                'So fixing the settings first leaves a function waiting for the data, ready for `array_map()` or a pipeline',
                'PHP requires it',
                'So the data is optional',
            ],
            'correct_option' => 1,
            'explanation' => '`array_map($multiply(1.2), $prices)` works because the factor comes first.',
        ],
        [
            'question' => 'PHP\'s `str_repeat($text, $times)` takes the data first. How do you make a function that triples any text?',
            'options' => [
                'Fix the first argument with `partial()`',
                'Fix the last argument, for example `fn ($t) => str_repeat($t, 3)` or a `partialRight()` helper',
                'It can\'t be done',
                'Use `str_repeat(3)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Here it\'s the trailing argument that\'s fixed, so you need to fix from the right.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $volume = fn (int $w) => fn (int $h) => fn (int $d) => $w * $h * $d;
                $flat = $volume(10)(10);
                echo $flat(1) + $flat(2);
                PHP,
            'options' => ['`300`', '`100`', '`200`', '`21`'],
            'correct_option' => 0,
            'explanation' => '`$flat` has width and height fixed at 10: 100 + 200 = 300.',
        ],
        [
            'question' => 'How is a configured invokable object, such as `new Formatter(\'£\', 2)`, related to partial application?',
            'options' => [
                'It isn\'t',
                'Its constructor fixes the settings, and calling it supplies the remaining argument, just like a partially applied function',
                'It\'s a form of currying that only works with objects',
                'It replaces closures entirely',
            ],
            'correct_option' => 1,
            'explanation' => 'Closures and small invokable objects are two ways of expressing the same idea.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Swap the first two arguments',
            'prompt' => "Write `flip(callable \$f): Closure`, returning a closure that calls `\$f` with its first two arguments swapped and any further arguments passed on unchanged. For example, `flip(str_repeat(...))(3, 'ab')` returns `'ababab'`.",
            'starter_code' => <<<'PHP'
                <?php

                function flip(callable $f): Closure
                {
                    return fn (mixed ...$args) => $f(...$args);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function flip(callable $f): Closure
                {
                    return fn (mixed $a, mixed $b, mixed ...$rest) => $f($b, $a, ...$rest);
                }
                PHP,
            'hint' => 'Name the first two parameters and collect the rest: `fn ($a, $b, ...$rest) => $f($b, $a, ...$rest)`.',
            'checks' => [
                ['label' => 'The first two arguments are swapped', 'code' => "return expect_same([flip(str_repeat(...))(3, 'ab'), flip(fn (\$a, \$b) => \"\$a/\$b\")('x', 'y')], ['ababab', 'y/x']);"],
                ['label' => 'Further arguments are passed on', 'code' => "return expect_same(flip(fn (\$a, \$b, \$c, \$d) => \"\$a\$b\$c\$d\")(1, 2, 3, 4), '2134');"],
            ],
        ],
        [
            'title' => 'Fix the last arguments',
            'prompt' => "Write `partialRight(callable \$f, mixed ...\$fixed): Closure`, which puts the fixed arguments **after** the ones given later. Then set `\$twoDecimals` to `number_format` with its last three arguments fixed as `2`, `'.'` and `','`.",
            'starter_code' => <<<'PHP'
                <?php

                function partialRight(callable $f, mixed ...$fixed): Closure
                {
                    return fn (mixed ...$first) => $f(...$fixed, ...$first);
                }

                $twoDecimals = null;
                PHP,
            'solution' => <<<'PHP'
                <?php

                function partialRight(callable $f, mixed ...$fixed): Closure
                {
                    return fn (mixed ...$first) => $f(...$first, ...$fixed);
                }

                $twoDecimals = partialRight(number_format(...), 2, '.', ',');
                PHP,
            'hint' => 'Swap the order of the spreads. `number_format(...)` turns the built-in function into a closure you can pass.',
            'checks' => [
                ['label' => 'Fixed arguments come last', 'code' => "return expect_same([partialRight(str_repeat(...), 3)('ab'), partialRight(fn (\$a, \$b) => \"\$a/\$b\", 'end')('start')], ['ababab', 'start/end']);"],
                ['label' => '$twoDecimals formats numbers', 'code' => "return expect_same((\$GLOBALS['twoDecimals'])(1234567.891), '1,234,567.89');"],
            ],
        ],
        [
            'title' => 'Curry by hand',
            'prompt' => 'Set `$between` to a **curried** function, called as `$between($min)($max)($n)`, that returns whether `$n` is from `$min` to `$max` inclusive. Then set `$isTeen` to `$between` with `$min` = 13 and `$max` = 19 fixed.',
            'starter_code' => <<<'PHP'
                <?php

                $between = fn (int $min, int $max, int $n) => $n >= $min && $n <= $max;
                $isTeen = null;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $between = fn (int $min) => fn (int $max) => fn (int $n) => $n >= $min && $n <= $max;
                $isTeen = $between(13)(19);
                PHP,
            'hint' => 'Nest arrow functions: `fn ($min) => fn ($max) => fn ($n) => …`. Then `$isTeen = $between(13)(19);`.',
            'checks' => [
                ['label' => '$between is curried', 'code' => "\$b = \$GLOBALS['between'];\nreturn expect_same([\$b(1)(5)(3), \$b(1)(5)(6)], [true, false]);"],
                ['label' => '$isTeen', 'code' => "\$t = \$GLOBALS['isTeen'];\nreturn expect_same([\$t(13), \$t(19), \$t(12), \$t(20)], [true, true, false, false]);"],
            ],
        ],
        [
            'title' => 'A general curry',
            'prompt' => "Write `curry(Closure \$f): Closure` that collects arguments, given one or more at a time, until it has as many as `\$f`'s required parameters, then calls `\$f` with them. `curry(fn (\$a, \$b, \$c) => …)` can be called as `(1)(2)(3)`, `(1, 2)(3)` or `(1)(2, 3)`.",
            'starter_code' => <<<'PHP'
                <?php

                function curry(Closure $f): Closure
                {
                    return $f;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function curry(Closure $f): Closure
                {
                    $needed = (new ReflectionFunction($f))->getNumberOfRequiredParameters();

                    $collect = function (array $args) use (&$collect, $f, $needed): mixed {
                        if (count($args) >= $needed) {
                            return $f(...$args);
                        }

                        return fn (mixed ...$more) => $collect([...$args, ...$more]);
                    };

                    return fn (mixed ...$args) => $collect($args);
                }
                PHP,
            'hint' => '`(new ReflectionFunction($f))->getNumberOfRequiredParameters()` says how many are needed. A recursive inner closure keeps the arguments so far, and either calls `$f` or returns a closure waiting for more.',
            'checks' => [
                ['label' => 'Any grouping of arguments', 'code' => "\$volume = curry(fn (\$w, \$h, \$d) => \$w * \$h * \$d);\nreturn expect_same([\$volume(2)(3)(4), \$volume(2, 3)(4), \$volume(2)(3, 4), \$volume(2, 3, 4)], [24, 24, 24, 24]);"],
                ['label' => 'Partially applied functions are reusable', 'code' => "\$greet = curry(fn (\$greeting, \$name) => \"\$greeting, \$name\");\n\$hello = \$greet('Hello');\nreturn expect_same([\$hello('Amara'), \$hello('Ben')], ['Hello, Amara', 'Hello, Ben']);"],
            ],
        ],
        [
            'title' => 'Settings first, data last',
            'prompt' => 'Write three functions in "settings first, data last" style, each taking its setting and returning a closure that takes a price: `addPercent(float $percent)`, `minus(float $amount)` and `roundTo(int $places)`. Then set `$final` to the prices with 20% added, then 1.5 taken off, then rounded to 2 places, using `array_map()` three times.',
            'starter_code' => <<<'PHP'
                <?php

                $prices = [10.0, 7.99, 13.5];
                $final = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                function addPercent(float $percent): Closure
                {
                    return fn (float $price) => $price * (1 + $percent / 100);
                }

                function minus(float $amount): Closure
                {
                    return fn (float $price) => $price - $amount;
                }

                function roundTo(int $places): Closure
                {
                    return fn (float $price) => round($price, $places);
                }

                $prices = [10.0, 7.99, 13.5];
                $final = array_map(roundTo(2), array_map(minus(1.5), array_map(addPercent(20), $prices)));
                PHP,
            'hint' => 'Each function returns `fn (float $price) => …`. Nest the `array_map()` calls so 20% is added first.',
            'checks' => [
                ['label' => 'Each function returns a closure', 'code' => 'return expect_same([addPercent(50)(100.0), minus(2.5)(10.0), roundTo(1)(3.14159)], [150.0, 7.5, 3.1]);'],
                ['label' => '$final', 'code' => "return expect_same(\$GLOBALS['final'] ?? null, [10.5, 8.09, 14.7]);"],
            ],
        ],
        [
            'title' => 'A price formatter, two ways',
            'prompt' => "Write a curried closure `\$formatWith` called as `\$formatWith(\$symbol)(\$decimals)(\$amount)` returning e.g. `'£1,234.50'`, and an invokable class `Money` whose constructor takes the symbol and decimals and whose `__invoke(float \$amount)` returns the same text. Both must give identical results.",
            'starter_code' => <<<'PHP'
                <?php

                $formatWith = null;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $formatWith = fn (string $symbol) => fn (int $decimals) => fn (float $amount) => $symbol . number_format($amount, $decimals);

                final class Money
                {
                    public function __construct(private string $symbol, private int $decimals) {}

                    public function __invoke(float $amount): string
                    {
                        return $this->symbol . number_format($amount, $this->decimals);
                    }
                }
                PHP,
            'hint' => 'The closure nests three arrow functions; the class stores the two settings and uses them in `__invoke()`.',
            'checks' => [
                ['label' => 'The curried formatter', 'code' => "\$f = \$GLOBALS['formatWith'];\nreturn expect_same([\$f('£')(2)(1234.5), \$f('€')(0)(99.6)], ['£1,234.50', '€100']);"],
                ['label' => 'The invokable class gives the same results', 'code' => "\$f = \$GLOBALS['formatWith'];\n\$pounds = new Money('£', 2);\nreturn expect_same([array_map(\$pounds, [3.5, 1200.0]), \$f('£')(2)(3.5)], [['£3.50', '£1,200.00'], '£3.50']);"],
            ],
        ],
    ],
];
