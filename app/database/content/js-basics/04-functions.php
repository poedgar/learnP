<?php

// Lesson 4 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'functions',
    'title' => 'Functions',
    'summary' => 'Write functions as declarations, expressions and arrows; use defaults, rest parameters and spread; understand scope and closures; pass functions around; and recurse.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        function shout(text, times = 1) {
            let result = '';
            for (let i = 0; i < times; i++) {
                result += text.toUpperCase() + '! ';
            }
            return result.trim();
        }

        const whisper = (text) => text.toLowerCase() + '...';

        console.log(shout('hello'));
        console.log(shout('again', 3));
        console.log(whisper('QUIETLY NOW'));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function add(a, b) {
                    a + b;
                }
                console.log(add(2, 3));
                JS,
            'options' => ['`undefined`', '`5`', '`23`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Without `return`, a function gives back `undefined`. The sum is worked out and thrown away.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const square = (n) => n * n;
                const half = (n) => {
                    n / 2;
                };
                console.log(square(4), half(4));
                JS,
            'options' => ['`16 undefined`', '`16 2`', '`8 2`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'A one-expression arrow returns it automatically; once there are braces, you need `return`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function label(name, prefix = 'Dr') {
                    return `${prefix} ${name}`;
                }
                console.log(label('Okafor'), label('Ito', undefined), label('Le Guin', null));
                JS,
            'options' => ['`Dr Okafor Dr Ito null Le Guin`', '`Dr Okafor Dr Ito Dr Le Guin`', '`Dr Okafor undefined Ito null Le Guin`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Defaults replace `undefined`, whether it\'s missing or passed explicitly, but not `null`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function count(...items) {
                    return items.length;
                }
                const list = ['a', 'b', 'c'];
                console.log(count(list), count(...list), count());
                JS,
            'options' => ['`1 3 0`', '`3 3 0`', '`1 1 0`', '`3 3 undefined`'],
            'correct_option' => 0,
            'explanation' => 'Passing the array gives one argument; spreading it gives three.',
        ],
        [
            'question' => 'What is a closure?',
            'options' => [
                'A function that has finished running',
                'A function together with the variables from where it was created, which it can still use later',
                'The closing brace of a function',
                'A function with no parameters',
            ],
            'correct_option' => 1,
            'explanation' => '`makeAdder(10)` returns a function that still knows `amount` is 10.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function makeCounter() {
                    let n = 0;
                    return () => ++n;
                }
                const a = makeCounter();
                const b = makeCounter();
                a();
                a();
                console.log(a(), b());
                JS,
            'options' => ['`3 1`', '`3 4`', '`1 1`', '`2 1`'],
            'correct_option' => 0,
            'explanation' => 'Each call to `makeCounter()` makes its own `n`, so `b` starts again from 0.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function sum(n) {
                    return n <= 0 ? 0 : n + sum(n - 1);
                }
                console.log(sum(4));
                JS,
            'options' => ['`10`', '`4`', '`0`', '`24`'],
            'correct_option' => 0,
            'explanation' => '4 + 3 + 2 + 1 + 0 = 10. The base case, `n <= 0`, stops the recursion.',
        ],
        [
            'question' => 'Why can a function declaration be called before the line it\'s written on, but a `const` arrow can\'t?',
            'options' => [
                'Arrow functions are slower',
                'Declarations are set up before the code runs; a `const` variable only exists from its line onwards',
                'They both can',
                'Arrow functions must be called inside other functions',
            ],
            'correct_option' => 1,
            'explanation' => 'Using a `let` or `const` before its line is a `ReferenceError`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A full name',
            'prompt' => "Write `fullName(first, last, middle)`, which joins the names with single spaces. `middle` is optional: `fullName('Ursula', 'Le Guin')` is `'Ursula Le Guin'` and `fullName('Ursula', 'Le Guin', 'K.')` is `'Ursula K. Le Guin'`.",
            'starter_code' => <<<'JS'
                function fullName(first, last, middle) {
                    return `${first} ${middle} ${last}`;
                }
                JS,
            'solution' => <<<'JS'
                function fullName(first, last, middle = '') {
                    return middle === '' ? `${first} ${last}` : `${first} ${middle} ${last}`;
                }
                JS,
            'hint' => 'Give `middle` a default of `\'\'`, then leave it out of the result when it\'s empty.',
            'checks' => [
                ['label' => 'Without a middle name', 'code' => "return expectSame(fullName('Ursula', 'Le Guin'), 'Ursula Le Guin');"],
                ['label' => 'With a middle name', 'code' => "return expectSame(fullName('Ursula', 'Le Guin', 'K.'), 'Ursula K. Le Guin');"],
            ],
        ],
        [
            'title' => 'An average of any length',
            'prompt' => 'Write `average(...numbers)`, the mean of any number of arguments, rounded to 2 decimal places as a number. With no arguments it returns `0`.',
            'starter_code' => <<<'JS'
                function average(numbers) {
                    return 0;
                }
                JS,
            'solution' => <<<'JS'
                function average(...numbers) {
                    if (numbers.length === 0) {
                        return 0;
                    }
                    let sum = 0;
                    for (const n of numbers) {
                        sum += n;
                    }
                    return Math.round((sum / numbers.length) * 100) / 100;
                }
                JS,
            'hint' => 'A rest parameter `...numbers` collects the arguments into an array. Round with `Math.round(x * 100) / 100`.',
            'checks' => [
                ['label' => 'Some numbers', 'code' => 'return expectSame([average(2, 4, 9), average(10), average(1, 2)], [5, 10, 1.5]);'],
                ['label' => 'Rounding', 'code' => 'return expectSame(average(1, 2, 2), 1.67);'],
                ['label' => 'No numbers', 'code' => 'return expectSame(average(), 0);'],
            ],
        ],
        [
            'title' => 'A counter with a closure',
            'prompt' => 'Write `makeCounter(start = 0)`, which returns a function. Each call of that function adds 1 and returns the new count. Each counter keeps its own count.',
            'starter_code' => <<<'JS'
                let count = 0;

                function makeCounter(start = 0) {
                    return () => {
                        count += 1;
                        return count;
                    };
                }
                JS,
            'solution' => <<<'JS'
                function makeCounter(start = 0) {
                    let count = start;
                    return () => {
                        count += 1;
                        return count;
                    };
                }
                JS,
            'hint' => 'Move `count` inside `makeCounter()`, starting at `start`, so each counter gets its own.',
            'checks' => [
                ['label' => 'Counts up', 'code' => "const next = makeCounter();\nreturn expectSame([next(), next(), next()], [1, 2, 3]);"],
                ['label' => 'Counters are separate', 'code' => "const a = makeCounter();\nconst b = makeCounter(10);\na();\na();\nreturn expectSame([a(), b(), b()], [3, 11, 12]);"],
            ],
        ],
        [
            'title' => 'Only once',
            'prompt' => "Write `once(fn)`, which returns a function that calls `fn` (with whatever arguments it gets) the first time it's called and returns its result. Later calls don't call `fn` again; they return that first result.",
            'starter_code' => <<<'JS'
                function once(fn) {
                    return fn;
                }
                JS,
            'solution' => <<<'JS'
                function once(fn) {
                    let called = false;
                    let result;
                    return (...args) => {
                        if (!called) {
                            called = true;
                            result = fn(...args);
                        }
                        return result;
                    };
                }
                JS,
            'hint' => 'Keep `called` and `result` in the closure. Pass the arguments on with `(...args) => fn(...args)`.',
            'checks' => [
                ['label' => 'Calls fn the first time only', 'code' => "let calls = 0;\nconst init = once((x) => {\n    calls++;\n    return x * 2;\n});\nreturn expectSame([init(5), init(100), init(7), calls], [10, 10, 10, 1]);"],
                ['label' => 'A result of undefined is remembered too', 'code' => "let calls = 0;\nconst log = once(() => {\n    calls++;\n});\nlog();\nlog();\nreturn expectSame(calls, 1);"],
            ],
        ],
        [
            'title' => 'Compose functions',
            'prompt' => 'Write `compose(f, g)`, which returns a function that takes `x` and returns `f(g(x))`, and `repeat(fn, times)`, which returns a function applying `fn` to its argument `times` times in a row (`repeat(fn, 0)` returns its argument unchanged).',
            'starter_code' => <<<'JS'
                function compose(f, g) {
                    return (x) => g(f(x));
                }

                function repeat(fn, times) {
                    return (x) => fn(x);
                }
                JS,
            'solution' => <<<'JS'
                function compose(f, g) {
                    return (x) => f(g(x));
                }

                function repeat(fn, times) {
                    return (x) => {
                        let value = x;
                        for (let i = 0; i < times; i++) {
                            value = fn(value);
                        }
                        return value;
                    };
                }
                JS,
            'hint' => '`compose` applies `g` first. `repeat` can loop `times` times, feeding each result into the next call.',
            'checks' => [
                ['label' => 'compose()', 'code' => "const inc = (n) => n + 1;\nconst dbl = (n) => n * 2;\nreturn expectSame([compose(inc, dbl)(5), compose(dbl, inc)(5)], [11, 12]);"],
                ['label' => 'repeat()', 'code' => "const dbl = (n) => n * 2;\nconst exclaim = (s) => s + '!';\nreturn expectSame([repeat(dbl, 3)(1), repeat(exclaim, 2)('hi'), repeat(dbl, 0)(7)], [8, 'hi!!', 7]);"],
            ],
        ],
        [
            'title' => 'Recursive power',
            'prompt' => 'Write `power(base, exponent)` for whole-number exponents of 0 or more, **recursively** (without `**`, `Math.pow` or a loop): any number to the power 0 is 1, and `power(b, e)` is `b * power(b, e - 1)`.',
            'starter_code' => <<<'JS'
                function power(base, exponent) {
                    return base * exponent;
                }
                JS,
            'solution' => <<<'JS'
                function power(base, exponent) {
                    if (exponent === 0) {
                        return 1;
                    }
                    return base * power(base, exponent - 1);
                }
                JS,
            'hint' => 'The base case is `exponent === 0`; otherwise return `base * power(base, exponent - 1)`.',
            'checks' => [
                ['label' => 'Powers', 'code' => 'return expectSame([power(2, 10), power(5, 3), power(7, 0), power(-2, 3)], [1024, 125, 1, -8]);'],
                ['label' => 'It recurses', 'code' => <<<'JS'
                    const code = learnerCode();
                    if (/\*\*|Math\.pow|\bfor\s*\(|\bwhile\s*\(/.test(code)) {
                        return 'Solve it with recursion, without **, Math.pow or a loop.';
                    }
                    return (code.match(/power\s*\(/g) ?? []).length >= 2 ? true : 'power() should call itself.';
                    JS],
            ],
        ],
    ],
];
