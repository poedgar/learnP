<?php

// Lesson 8 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'errors-maps-and-sets',
    'title' => 'Errors, Map and Set',
    'summary' => 'Throw and catch errors, catch only what you expect, clean up with finally, define your own error classes, and store data in Maps and Sets.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const seen = new Set();
        const counts = new Map();

        for (const word of 'the quick brown fox jumps over the lazy dog the end'.split(' ')) {
            seen.add(word);
            counts.set(word, (counts.get(word) ?? 0) + 1);
        }

        console.log(seen.size, counts.get('the'));

        try {
            null.toUpperCase();
        } catch (error) {
            console.log(`${error.name}: ${error.message}`);
        }
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let log = '';
                try {
                    log += 'a';
                    throw new Error('x');
                    log += 'b';
                } catch (error) {
                    log += 'c';
                } finally {
                    log += 'd';
                }
                console.log(log);
                JS,
            'options' => ['`acd`', '`abcd`', '`ad`', '`ac`'],
            'correct_option' => 0,
            'explanation' => 'The `throw` skips the rest of the `try`; `catch` runs, then `finally` always runs.',
        ],
        [
            'question' => 'Which error do you get from `undefinedVariable + 1` when the variable was never declared?',
            'options' => ['`TypeError`', '`ReferenceError`', '`RangeError`', '`SyntaxError`'],
            'correct_option' => 1,
            'explanation' => 'An unknown name is a `ReferenceError`; using the wrong kind of value is a `TypeError`.',
        ],
        [
            'question' => 'Why check `error instanceof` and rethrow inside a `catch`?',
            'options' => [
                'It makes the code faster',
                'A `catch` catches everything, including bugs, so handle only the errors you expect and pass the rest on',
                'Errors can only be caught once',
                'It is required by JavaScript',
            ],
            'correct_option' => 1,
            'explanation' => 'Swallowing unexpected errors hides bugs.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                class AppError extends Error {
                    constructor(message) {
                        super(message);
                        this.name = 'AppError';
                    }
                }
                const e = new AppError('nope');
                console.log(e instanceof AppError, e instanceof Error, `${e.name}: ${e.message}`);
                JS,
            'options' => ['`true true AppError: nope`', '`true false AppError: nope`', '`true true Error: nope`', '`false true AppError: nope`'],
            'correct_option' => 0,
            'explanation' => 'An `AppError` is also an `Error`. Setting `name` changes how it describes itself.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const m = new Map([['a', 1]]);
                m.set('b', 2).set('a', 3);
                console.log(m.size, m.get('a'), m.get('z'));
                JS,
            'options' => ['`2 3 undefined`', '`3 1 undefined`', '`2 1 null`', '`3 3 undefined`'],
            'correct_option' => 0,
            'explanation' => 'Setting an existing key replaces its value. `set()` returns the map, so calls chain.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const s = new Set([1, 2, 2, 3]);
                s.add(3).add(4);
                console.log(s.size, s.has(2));
                JS,
            'options' => ['`4 true`', '`6 true`', '`5 true`', '`4 false`'],
            'correct_option' => 0,
            'explanation' => 'A set ignores values it already holds: 1, 2, 3 and 4.',
        ],
        [
            'question' => 'When is a `Map` a better choice than a plain object?',
            'options' => [
                'Never',
                'When keys aren\'t simple strings (objects, numbers), or entries are added and removed often',
                'When you need JSON',
                'When the keys are fixed and known in advance',
            ],
            'correct_option' => 1,
            'explanation' => 'Plain objects suit records with fixed property names.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const visits = new Map();
                visits.set({ id: 1 }, 'first');
                console.log(visits.get({ id: 1 }), visits.size);
                JS,
            'options' => ['`undefined 1`', '`first 1`', '`first 2`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Object keys are compared by identity; `{ id: 1 }` written again is a different object.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Throw the right error',
            'prompt' => "Write `parseRating(text)`, returning the number in `text` if it's a whole number from 1 to 5. Throw a `TypeError` if it isn't a whole number at all, and a `RangeError` if it is but is outside 1 to 5.",
            'starter_code' => <<<'JS'
                function parseRating(text) {
                    return Number(text);
                }
                JS,
            'solution' => <<<'JS'
                function parseRating(text) {
                    const rating = Number(text);
                    if (text.trim() === '' || !Number.isInteger(rating)) {
                        throw new TypeError(`Not a whole number: ${text}`);
                    }
                    if (rating < 1 || rating > 5) {
                        throw new RangeError(`Ratings go from 1 to 5, not ${rating}`);
                    }
                    return rating;
                }
                JS,
            'hint' => 'Check `Number.isInteger(Number(text))` first (and that the text isn\'t blank), then the range.',
            'checks' => [
                ['label' => 'Valid ratings', 'code' => "return expectSame([parseRating('1'), parseRating('5'), parseRating(' 3 ')], [1, 5, 3]);"],
                ['label' => 'Not whole numbers', 'code' => <<<'JS'
                    for (const bad of ['abc', '2.5', '']) {
                        const result = await expectThrows(() => parseRating(bad), TypeError);
                        if (result !== true) return `'${bad}': ${result}`;
                    }
                    return true;
                    JS],
                ['label' => 'Out of range', 'code' => <<<'JS'
                    for (const bad of ['0', '6', '-2']) {
                        const result = await expectThrows(() => parseRating(bad), RangeError);
                        if (result !== true) return `'${bad}': ${result}`;
                    }
                    return true;
                    JS],
            ],
        ],
        [
            'title' => 'A validation error',
            'prompt' => "Write a class `ValidationError` extending `Error`, with `name` set to `'ValidationError'` and a `field` property. Then write `validateUser(user)`, which throws a `ValidationError` for field `'name'` if the name is missing or blank, or for field `'email'` if the email doesn't contain `@`. Otherwise it returns `true`.",
            'starter_code' => <<<'JS'
                function validateUser(user) {
                    if (!user.name) {
                        throw new Error('name');
                    }
                    return true;
                }
                JS,
            'solution' => <<<'JS'
                class ValidationError extends Error {
                    constructor(field, message) {
                        super(message);
                        this.name = 'ValidationError';
                        this.field = field;
                    }
                }

                function validateUser(user) {
                    if (!user.name?.trim()) {
                        throw new ValidationError('name', 'A name is required');
                    }
                    if (!user.email?.includes('@')) {
                        throw new ValidationError('email', 'That email address is not valid');
                    }
                    return true;
                }
                JS,
            'hint' => 'Call `super(message)`, then set `this.name` and `this.field`. `user.name?.trim()` is falsy when the name is missing or blank.',
            'checks' => [
                ['label' => 'Valid users pass', 'code' => "return expectSame(validateUser({ name: 'Amara', email: 'amara@example.com' }), true);"],
                ['label' => 'Errors name the field', 'code' => <<<'JS'
                    const fieldFor = (user) => {
                        try {
                            validateUser(user);
                            return 'nothing thrown';
                        } catch (error) {
                            return error instanceof ValidationError && error instanceof Error ? `${error.name}:${error.field}` : `wrong error: ${error}`;
                        }
                    };
                    return expectSame(
                        [fieldFor({ email: 'a@b.c' }), fieldFor({ name: '  ', email: 'a@b.c' }), fieldFor({ name: 'Ben', email: 'ben.example.com' }), fieldFor({ name: 'Ben' })],
                        ['ValidationError:name', 'ValidationError:name', 'ValidationError:email', 'ValidationError:email'],
                    );
                    JS],
            ],
        ],
        [
            'title' => 'Parse safely',
            'prompt' => "Write `safeParse(json)`, which returns `{ ok: true, value }` when `json` is valid JSON and `{ ok: false, error }` (with the error's message) when it isn't. Errors other than a `SyntaxError` should not be swallowed: if `json` isn't a string at all, throw a `TypeError`.",
            'starter_code' => <<<'JS'
                function safeParse(json) {
                    return { ok: true, value: JSON.parse(json) };
                }
                JS,
            'solution' => <<<'JS'
                function safeParse(json) {
                    if (typeof json !== 'string') {
                        throw new TypeError('safeParse() needs a string');
                    }
                    try {
                        return { ok: true, value: JSON.parse(json) };
                    } catch (error) {
                        if (error instanceof SyntaxError) {
                            return { ok: false, error: error.message };
                        }
                        throw error;
                    }
                }
                JS,
            'hint' => 'Check the type first. In the `catch`, only turn a `SyntaxError` into `{ ok: false, ... }`; rethrow anything else.',
            'checks' => [
                ['label' => 'Valid JSON', 'code' => "return expectSame([safeParse('{\"a\": [1, 2]}'), safeParse('null')], [{ ok: true, value: { a: [1, 2] } }, { ok: true, value: null }]);"],
                ['label' => 'Invalid JSON', 'code' => "const result = safeParse('{ nope }');\nreturn expectSame([result.ok, typeof result.error, result.error.length > 0], [false, 'string', true]);"],
                ['label' => 'Not a string', 'code' => 'return expectThrows(() => safeParse(42), TypeError);'],
            ],
        ],
        [
            'title' => 'Word frequencies',
            'prompt' => 'Write `wordFrequencies(text)`, returning a `Map` from each word (lower-cased, split on spaces, ignoring empty strings) to how many times it appears, in order of first appearance.',
            'starter_code' => <<<'JS'
                function wordFrequencies(text) {
                    return new Map();
                }
                JS,
            'solution' => <<<'JS'
                function wordFrequencies(text) {
                    const counts = new Map();
                    for (const word of text.toLowerCase().split(' ')) {
                        if (word !== '') {
                            counts.set(word, (counts.get(word) ?? 0) + 1);
                        }
                    }
                    return counts;
                }
                JS,
            'hint' => 'For each word, `counts.set(word, (counts.get(word) ?? 0) + 1)`.',
            'checks' => [
                ['label' => 'Counts words', 'code' => "return expectSame(wordFrequencies('The cat and THE  hat'), new Map([['the', 2], ['cat', 1], ['and', 1], ['hat', 1]]));"],
                ['label' => 'Returns a Map', 'code' => "const result = wordFrequencies('a b a');\nreturn result instanceof Map ? expectSame([...result.keys()], ['a', 'b']) : 'Return a Map.';"],
            ],
        ],
        [
            'title' => 'In both, in one',
            'prompt' => 'Write `inBoth(a, b)` and `onlyInFirst(a, b)`, which take two arrays and return arrays of the unique values found in both, and in `a` but not `b`, in the order they first appear in `a`. Use a `Set` so each lookup is quick.',
            'starter_code' => <<<'JS'
                function inBoth(a, b) {
                    return a.filter((x) => b.includes(x));
                }

                function onlyInFirst(a, b) {
                    return a;
                }
                JS,
            'solution' => <<<'JS'
                function inBoth(a, b) {
                    const other = new Set(b);
                    return [...new Set(a)].filter((x) => other.has(x));
                }

                function onlyInFirst(a, b) {
                    const other = new Set(b);
                    return [...new Set(a)].filter((x) => !other.has(x));
                }
                JS,
            'hint' => 'Make a `Set` of `b` for `has()`, and `[...new Set(a)]` to go through `a` without duplicates.',
            'checks' => [
                ['label' => 'inBoth()', 'code' => 'return expectSame([inBoth([1, 2, 2, 3, 4], [4, 2, 9]), inBoth([1], [])], [[2, 4], []]);'],
                ['label' => 'onlyInFirst()', 'code' => "return expectSame([onlyInFirst([1, 2, 2, 3, 3], [2]), onlyInFirst(['a'], ['a'])], [[1, 3], []]);"],
                ['label' => 'Uses a Set', 'code' => "return learnerCode().includes('new Set') ? true : 'Use a Set for the lookups.';"],
            ],
        ],
        [
            'title' => 'Group into a Map',
            'prompt' => 'Write `groupBy(items, keyOf)`, returning a `Map` from each key (`keyOf(item)`) to an array of the items with that key, in their original order. Keys can be any value, such as numbers or booleans.',
            'starter_code' => <<<'JS'
                function groupBy(items, keyOf) {
                    const groups = {};
                    for (const item of items) {
                        groups[keyOf(item)] = item;
                    }
                    return groups;
                }
                JS,
            'solution' => <<<'JS'
                function groupBy(items, keyOf) {
                    const groups = new Map();
                    for (const item of items) {
                        const key = keyOf(item);
                        if (!groups.has(key)) {
                            groups.set(key, []);
                        }
                        groups.get(key).push(item);
                    }
                    return groups;
                }
                JS,
            'hint' => 'Create an empty array the first time a key appears, then `groups.get(key).push(item)`.',
            'checks' => [
                ['label' => 'Groups by length', 'code' => "return expectSame(groupBy(['fig', 'pear', 'kiwi', 'date', 'banana'], (w) => w.length), new Map([[3, ['fig']], [4, ['pear', 'kiwi', 'date']], [6, ['banana']]]));"],
                ['label' => 'Boolean keys', 'code' => <<<'JS'
                    const groups = groupBy([1, 2, 3, 4, 5], (n) => n % 2 === 0);
                    return groups instanceof Map ? expectSame([groups.get(true), groups.get(false)], [[2, 4], [1, 3, 5]]) : 'Return a Map.';
                    JS],
            ],
        ],
    ],
];
