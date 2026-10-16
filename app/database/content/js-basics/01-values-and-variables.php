<?php

// Lesson 1 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'values-and-variables',
    'title' => 'Values and variables',
    'summary' => 'Print with console.log, name values with const and let, and work with numbers, strings, template literals, booleans, null and undefined.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const name = 'Amara';
        let visits = 3;

        visits += 1;

        console.log(`Welcome back, ${name}! This is visit number ${visits}.`);
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let count = 2;
                count += 3;
                count = count * 2;
                console.log(count);
                JS,
            'options' => ['`10`', '`7`', '`8`', '`5`'],
            'correct_option' => 0,
            'explanation' => '2 + 3 is 5, and 5 × 2 is 10.',
        ],
        [
            'question' => 'When should you declare a variable with `const` rather than `let`?',
            'options' => [
                'When it holds a number',
                'When it will never be reassigned, which is most of the time',
                'Only for values that never change inside, like strings',
                'Never; `let` is always better',
            ],
            'correct_option' => 1,
            'explanation' => '`const` stops reassignment. Prefer it, and use `let` only when you need to reassign.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(7 / 2, Math.floor(7 / 2), 7 % 2);
                JS,
            'options' => ['`3.5 3 1`', '`3 3 1`', '`3.5 4 1`', '`3.5 3 0.5`'],
            'correct_option' => 0,
            'explanation' => 'Division keeps the fraction; `Math.floor()` drops it; `%` gives the remainder, 1.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const title = '  dune  ';
                console.log(`[${title.trim().toUpperCase()}]`, title.length);
                JS,
            'options' => ['`[DUNE] 8`', '`[DUNE] 4`', '`[  DUNE  ] 8`', '`[dune] 4`'],
            'correct_option' => 0,
            'explanation' => '`trim()` and `toUpperCase()` return a new string; `title` itself still has its spaces, so its length is 8.',
        ],
        [
            'question' => 'What does `typeof null` give?',
            'options' => ["`'null'`", "`'object'`", "`'undefined'`", 'An error'],
            'correct_option' => 1,
            'explanation' => 'A famous mistake from the first version of JavaScript, kept so old code keeps working.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(Number('12px'), parseInt('12px'), String(5) + 5, Number('5') + 5);
                JS,
            'options' => ['`NaN 12 55 10`', '`12 12 10 10`', '`NaN 12 10 55`', '`0 12 55 10`'],
            'correct_option' => 0,
            'explanation' => '`Number()` needs the whole string to be a number; `parseInt()` reads digits from the start. `+` with a string joins text.',
        ],
        [
            'question' => 'How do you check whether `x` is `NaN`?',
            'options' => ['`x === NaN`', '`Number.isNaN(x)`', '`typeof x === \'NaN\'`', '`x == undefined`'],
            'correct_option' => 1,
            'explanation' => '`NaN` is the only value not equal to itself, so `x === NaN` is always false.',
        ],
        [
            'question' => 'What is the difference between `undefined` and `null`?',
            'options' => [
                'There is none',
                '`undefined` is what a variable holds before it has a value; `null` is set on purpose to mean "no value"',
                '`null` is a number',
                '`undefined` is an error',
            ],
            'correct_option' => 1,
            'explanation' => 'Both mean "nothing", but `null` is always a deliberate choice.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A greeting',
            'prompt' => 'Keep the first two lines. Set `greeting` to `Hello, NAME! You are AGE years old.` using a template literal and the two variables, so it still works if they change.',
            'starter_code' => <<<'JS'
                const name = 'Amara';
                const age = 34;

                let greeting = '';
                JS,
            'solution' => <<<'JS'
                const name = 'Amara';
                const age = 34;

                const greeting = `Hello, ${name}! You are ${age} years old.`;
                JS,
            'hint' => 'Backticks and `${name}`: `Hello, ${name}! ...`.',
            'checks' => [
                ['label' => 'greeting', 'code' => "return expectSame(greeting, 'Hello, Amara! You are 34 years old.');"],
                ['label' => 'It uses the variables', 'code' => <<<'JS'
                    const variant = await runVariant(learnerCode().replace("'Amara'", "'Ben'").replace('= 34', '= 7'), ['greeting']);
                    return expectSame(variant.greeting, 'Hello, Ben! You are 7 years old.');
                    JS],
            ],
        ],
        [
            'title' => 'Hours and minutes',
            'prompt' => "Keep the first line. From `minutes`, set `duration` to text like `'2h 15m'` and `clock` to text like `'2:15'`, with the minutes always two digits in `clock` (`125` minutes is `'2h 5m'` and `'2:05'`).",
            'starter_code' => <<<'JS'
                const minutes = 135;

                const duration = '';
                const clock = '';
                JS,
            'solution' => <<<'JS'
                const minutes = 135;

                const hours = Math.floor(minutes / 60);
                const rest = minutes % 60;
                const duration = `${hours}h ${rest}m`;
                const clock = `${hours}:${String(rest).padStart(2, '0')}`;
                JS,
            'hint' => '`Math.floor(minutes / 60)` gives the hours and `minutes % 60` what is left. `String(rest).padStart(2, \'0\')` pads it.',
            'checks' => [
                ['label' => 'For 135 minutes', 'code' => "return expectSame([duration, clock], ['2h 15m', '2:15']);"],
                ['label' => 'For 125 and 59 minutes', 'code' => <<<'JS'
                    const a = await runVariant(learnerCode().replace('= 135', '= 125'), ['duration', 'clock']);
                    const b = await runVariant(learnerCode().replace('= 135', '= 59'), ['duration', 'clock']);
                    return expectSame([a.duration, a.clock, b.duration, b.clock], ['2h 5m', '2:05', '0h 59m', '0:59']);
                    JS],
            ],
        ],
        [
            'title' => 'Fix the constant',
            'prompt' => 'This code stops with `TypeError: Assignment to constant variable.` Fix it so `count` ends up as `3`. `limit` never changes, so keep it a `const`.',
            'starter_code' => <<<'JS'
                const limit = 3;
                const count = 0;

                count += 1;
                count += 1;
                count += 1;

                console.log(`${count} of ${limit}`);
                JS,
            'solution' => <<<'JS'
                const limit = 3;
                let count = 0;

                count += 1;
                count += 1;
                count += 1;

                console.log(`${count} of ${limit}`);
                JS,
            'hint' => 'A variable you reassign must be declared with `let`.',
            'checks' => [
                ['label' => 'count is 3', 'code' => 'return expectSame(count, 3);'],
                ['label' => 'limit is still a const', 'code' => "return /const\\s+limit\\b/.test(learnerCode()) ? true : 'Keep limit as a const.';"],
                ['label' => 'It prints the count', 'code' => "return expectSame(learnerOutput(), '3 of 3');"],
            ],
        ],
        [
            'title' => 'Surprising types',
            'prompt' => 'Use `typeof` to set `typeOfNull`, `typeOfList` and `typeOfNaN` to the types JavaScript reports for `null`, the array `[1, 2, 3]` and `NaN`. Predict them first, then run your code to see.',
            'starter_code' => <<<'JS'
                const typeOfNull = 'null';
                const typeOfList = 'array';
                const typeOfNaN = 'NaN';
                JS,
            'solution' => <<<'JS'
                const typeOfNull = typeof null;
                const typeOfList = typeof [1, 2, 3];
                const typeOfNaN = typeof NaN;
                JS,
            'hint' => 'Write `typeof null` and so on, rather than typing the answers.',
            'checks' => [
                ['label' => 'The types', 'code' => "return expectSame([typeOfNull, typeOfList, typeOfNaN], ['object', 'object', 'number']);"],
                ['label' => 'Worked out with typeof', 'code' => "return (learnerCode().match(/typeof/g) ?? []).length >= 3 ? true : 'Use typeof for each one.';"],
            ],
        ],
        [
            'title' => 'Tidy a title',
            'prompt' => "Keep the first line. Set `tidy` to `raw` with the spaces at both ends removed, all in lower case except the first letter, which is upper case: `'   dune BY Frank HERBERT  '` becomes `'Dune by frank herbert'`.",
            'starter_code' => <<<'JS'
                const raw = '   dune BY Frank HERBERT  ';

                const tidy = raw;
                JS,
            'solution' => <<<'JS'
                const raw = '   dune BY Frank HERBERT  ';

                const lower = raw.trim().toLowerCase();
                const tidy = lower.slice(0, 1).toUpperCase() + lower.slice(1);
                JS,
            'hint' => 'Trim and lower-case first. Then join the first character, upper-cased (`slice(0, 1)`), to the rest (`slice(1)`).',
            'checks' => [
                ['label' => 'tidy', 'code' => "return expectSame(tidy, 'Dune by frank herbert');"],
                ['label' => 'Works for other titles', 'code' => <<<'JS'
                    const variant = await runVariant(learnerCode().replace("'   dune BY Frank HERBERT  '", "' THE HOBBIT '"), ['tidy']);
                    return expectSame(variant.tidy, 'The hobbit');
                    JS],
            ],
        ],
        [
            'title' => 'Reading a weight',
            'prompt' => "Keep the first line. Set `weight` to the number at the start of `input` (`42.5` for `'42.5kg'`), `strict` to what `Number()` makes of the whole of `input`, and `isPlainNumber` to whether `input` is a plain number with nothing after it.",
            'starter_code' => <<<'JS'
                const input = '42.5kg';

                const weight = 0;
                const strict = 0;
                const isPlainNumber = true;
                JS,
            'solution' => <<<'JS'
                const input = '42.5kg';

                const weight = parseFloat(input);
                const strict = Number(input);
                const isPlainNumber = !Number.isNaN(strict);
                JS,
            'hint' => '`parseFloat()` reads from the start; `Number()` needs the whole string. `Number.isNaN()` tells you whether that worked.',
            'checks' => [
                ['label' => "For '42.5kg'", 'code' => 'return expectSame([weight, strict, isPlainNumber], [42.5, NaN, false]);'],
                ['label' => "For '10'", 'code' => <<<'JS'
                    const variant = await runVariant(learnerCode().replace("'42.5kg'", "'10'"), ['weight', 'strict', 'isPlainNumber']);
                    return expectSame([variant.weight, variant.strict, variant.isPlainNumber], [10, 10, true]);
                    JS],
            ],
        ],
    ],
];
