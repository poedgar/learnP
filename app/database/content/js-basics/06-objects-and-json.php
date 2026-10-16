<?php

// Lesson 6 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'objects-and-json',
    'title' => 'Objects and JSON',
    'summary' => 'Group named values in objects: read and change properties, write methods, go through keys and entries, destructure, spread and merge, and turn data into JSON and back.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const book = { title: 'Dune', author: 'Frank Herbert', year: 1965, tags: ['sci-fi'] };

        const { title, year, pages = 'unknown' } = book;
        console.log(`${title} (${year}), ${pages} pages`);

        const updated = { ...book, year: 2021, edition: 'anniversary' };
        console.log(updated);
        console.log(JSON.stringify(updated));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const key = 'colour';
                const car = { colour: 'red', 'top speed': 120 };
                console.log(car.key, car[key], car['top speed']);
                JS,
            'options' => ['`undefined red 120`', '`red red 120`', 'An error', '`colour red 120`'],
            'correct_option' => 0,
            'explanation' => '`car.key` looks for a property literally named "key". Brackets use the variable\'s value.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const a = { x: 1, y: 2 };
                const b = { ...a, y: 3, z: 4 };
                console.log(a.y, b.y, Object.keys(b).length);
                JS,
            'options' => ['`2 3 3`', '`3 3 3`', '`2 2 3`', '`2 3 2`'],
            'correct_option' => 0,
            'explanation' => 'Spread copies into a new object; the later `y` wins there, and `a` is unchanged.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const { name, age = 30, city: town = 'Leeds' } = { name: 'Cy', age: undefined, city: 'York' };
                console.log(name, age, town);
                JS,
            'options' => ['`Cy 30 York`', '`Cy undefined York`', '`Cy 30 Leeds`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Defaults apply to `undefined`; `city: town` renames the property.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const original = { tags: ['a'] };
                const copy = { ...original };
                copy.tags.push('b');
                console.log(original.tags.length);
                JS,
            'options' => ['`2`', '`1`', '`0`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Spread is a shallow copy: both objects share the same `tags` array. `structuredClone()` would copy it too.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(JSON.stringify({ a: 1, b: undefined, c: () => 1, d: null }));
                JS,
            'options' => ['`{"a":1,"d":null}`', '`{"a":1,"b":undefined,"d":null}`', '`{"a":1,"b":null,"c":null,"d":null}`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'JSON has no `undefined` or functions, so those properties are left out. `null` is kept.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const counter = {
                    count: 0,
                    increment() {
                        this.count++;
                        return this.count;
                    },
                };
                counter.increment();
                console.log(counter.increment());
                JS,
            'options' => ['`2`', '`1`', '`NaN`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Called as `counter.increment()`, `this` is `counter`.',
        ],
        [
            'question' => 'What does `Object.entries({ a: 1, b: 2 })` give?',
            'options' => ["`[['a', 1], ['b', 2]]`", "`['a', 'b']`", '`[1, 2]`', '`{ a: 1, b: 2 }`'],
            'correct_option' => 0,
            'explanation' => 'An array of `[key, value]` pairs. `Object.fromEntries()` turns pairs back into an object.',
        ],
        [
            'question' => 'What does `JSON.parse("{ name: \'Dune\' }")` do?',
            'options' => [
                'Returns `{ name: \'Dune\' }`',
                'Throws a `SyntaxError`: JSON needs double quotes around names and strings',
                'Returns `null`',
                'Returns the string unchanged',
            ],
            'correct_option' => 1,
            'explanation' => 'Valid JSON would be `{"name": "Dune"}`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Describe a book',
            'prompt' => "Write `describeBook(book)` using destructuring in its parameter. Given `{ title, author, year }`, return `'TITLE by AUTHOR (YEAR)'`; without a year, leave out the brackets; without an author, use `'Unknown'`.",
            'starter_code' => <<<'JS'
                function describeBook(book) {
                    return `${book.title} by ${book.author} (${book.year})`;
                }
                JS,
            'solution' => <<<'JS'
                function describeBook({ title, author = 'Unknown', year }) {
                    const base = `${title} by ${author}`;
                    return year === undefined ? base : `${base} (${year})`;
                }
                JS,
            'hint' => 'Write the parameter as `{ title, author = \'Unknown\', year }`, then add the year only when it isn\'t `undefined`.',
            'checks' => [
                ['label' => 'Full details', 'code' => "return expectSame(describeBook({ title: 'Emma', author: 'Jane Austen', year: 1815 }), 'Emma by Jane Austen (1815)');"],
                ['label' => 'Missing details', 'code' => "return expectSame([describeBook({ title: 'Emma', author: 'Jane Austen' }), describeBook({ title: 'Beowulf' })], ['Emma by Jane Austen', 'Beowulf by Unknown']);"],
                ['label' => 'Destructures its parameter', 'code' => "return /function\\s+describeBook\\s*\\(\\s*\\{/.test(learnerCode()) ? true : 'Destructure the book in the parameter list: describeBook({ title, ... }).';"],
            ],
        ],
        [
            'title' => 'Pick some properties',
            'prompt' => "Write `pick(object, keys)`, returning a new object with only the properties of `object` named in `keys`. Names that `object` doesn't have are left out.",
            'starter_code' => <<<'JS'
                function pick(object, keys) {
                    return object;
                }
                JS,
            'solution' => <<<'JS'
                function pick(object, keys) {
                    const result = {};
                    for (const key of keys) {
                        if (Object.hasOwn(object, key)) {
                            result[key] = object[key];
                        }
                    }
                    return result;
                }
                JS,
            'hint' => 'Start with `{}` and copy each key that `Object.hasOwn(object, key)` says is there.',
            'checks' => [
                ['label' => 'Picks properties', 'code' => "const user = { id: 3, name: 'Ben', email: 'ben@example.com', admin: false };\nreturn expectSame(pick(user, ['name', 'admin']), { name: 'Ben', admin: false });"],
                ['label' => 'Leaves out missing names, and the original alone', 'code' => "const user = { id: 3, nickname: undefined };\nconst picked = pick(user, ['id', 'nickname', 'age']);\nreturn expectSame([Object.keys(picked), picked !== user, Object.keys(user)], [['id', 'nickname'], true, ['id', 'nickname']]);"],
            ],
        ],
        [
            'title' => 'Settings with overrides',
            'prompt' => 'Write `withSettings(overrides)`, returning `DEFAULTS` with `overrides` applied. Overrides of `display` are merged into the default `display` rather than replacing it. `DEFAULTS` must never change.',
            'starter_code' => <<<'JS'
                const DEFAULTS = { sound: true, display: { theme: 'light', fontSize: 16 } };

                function withSettings(overrides) {
                    return Object.assign(DEFAULTS, overrides);
                }
                JS,
            'solution' => <<<'JS'
                const DEFAULTS = { sound: true, display: { theme: 'light', fontSize: 16 } };

                function withSettings(overrides) {
                    return {
                        ...DEFAULTS,
                        ...overrides,
                        display: { ...DEFAULTS.display, ...overrides.display },
                    };
                }
                JS,
            'hint' => 'Spread `DEFAULTS` then `overrides` into a new object, and build `display` the same way from both `display` objects.',
            'checks' => [
                ['label' => 'Overrides apply, display is merged', 'code' => "return expectSame(withSettings({ sound: false, display: { fontSize: 20 } }), { sound: false, display: { theme: 'light', fontSize: 20 } });"],
                ['label' => 'No overrides', 'code' => "return expectSame(withSettings({}), { sound: true, display: { theme: 'light', fontSize: 16 } });"],
                ['label' => 'DEFAULTS never changes', 'code' => "withSettings({ sound: false, display: { theme: 'dark' } });\nconst result = withSettings({});\nresult.display.fontSize = 99;\nreturn expectSame(DEFAULTS, { sound: true, display: { theme: 'light', fontSize: 16 } });"],
            ],
        ],
        [
            'title' => 'Swap keys and values',
            'prompt' => "Write `invert(object)`, returning a new object whose keys are the values of `object` and whose values are its keys: `{ a: 'x', b: 'y' }` → `{ x: 'a', y: 'b' }`. Use `Object.entries()` and `Object.fromEntries()`.",
            'starter_code' => <<<'JS'
                function invert(object) {
                    return {};
                }
                JS,
            'solution' => <<<'JS'
                function invert(object) {
                    return Object.fromEntries(Object.entries(object).map(([key, value]) => [value, key]));
                }
                JS,
            'hint' => 'Turn the object into pairs, swap each pair with `map(([key, value]) => [value, key])`, and turn it back.',
            'checks' => [
                ['label' => 'Inverts', 'code' => "return expectSame([invert({ a: 'x', b: 'y' }), invert({ uk: 'GBP', fr: 'EUR' }), invert({})], [{ x: 'a', y: 'b' }, { GBP: 'uk', EUR: 'fr' }, {}]);"],
                ['label' => 'Uses entries and fromEntries', 'code' => "const code = learnerCode();\nreturn code.includes('Object.entries') && code.includes('Object.fromEntries') ? true : 'Use Object.entries() and Object.fromEntries().';"],
            ],
        ],
        [
            'title' => 'Group by genre',
            'prompt' => 'Write `titlesByGenre(books)`, where each book is `{ title, genre }`, returning an object from each genre to an array of its titles, in the order they appear.',
            'starter_code' => <<<'JS'
                function titlesByGenre(books) {
                    const groups = {};
                    for (const book of books) {
                        groups[book.genre] = [book.title];
                    }
                    return groups;
                }
                JS,
            'solution' => <<<'JS'
                function titlesByGenre(books) {
                    const groups = {};
                    for (const { title, genre } of books) {
                        groups[genre] ??= [];
                        groups[genre].push(title);
                    }
                    return groups;
                }
                JS,
            'hint' => 'Create the array the first time you see a genre (`groups[genre] ??= []`), then `push()` onto it.',
            'checks' => [
                ['label' => 'Groups titles', 'code' => <<<'JS'
                    const books = [
                        { title: 'Dune', genre: 'sci-fi' },
                        { title: 'Emma', genre: 'romance' },
                        { title: 'Solaris', genre: 'sci-fi' },
                        { title: 'Persuasion', genre: 'romance' },
                        { title: 'Odes', genre: 'poetry' },
                    ];
                    return expectSame(titlesByGenre(books), { 'sci-fi': ['Dune', 'Solaris'], romance: ['Emma', 'Persuasion'], poetry: ['Odes'] });
                    JS],
                ['label' => 'No books', 'code' => 'return expectSame(titlesByGenre([]), {});'],
            ],
        ],
        [
            'title' => 'Total of paid orders',
            'prompt' => "Write `paidTotal(json)`. `json` is JSON text of an array of orders like `{\"id\": 1, \"status\": \"paid\", \"pence\": 999}`. Return the total pence of the orders whose status is `'paid'`.",
            'starter_code' => <<<'JS'
                function paidTotal(json) {
                    return json.length;
                }
                JS,
            'solution' => <<<'JS'
                function paidTotal(json) {
                    return JSON.parse(json)
                        .filter((order) => order.status === 'paid')
                        .reduce((sum, order) => sum + order.pence, 0);
                }
                JS,
            'hint' => '`JSON.parse()` gives an ordinary array; then `filter()` and `reduce()`.',
            'checks' => [
                ['label' => 'Adds up paid orders', 'code' => <<<'JS'
                    const json = JSON.stringify([
                        { id: 1, status: 'paid', pence: 999 },
                        { id: 2, status: 'refunded', pence: 450 },
                        { id: 3, status: 'paid', pence: 1250 },
                        { id: 4, status: 'pending', pence: 300 },
                    ]);
                    return expectSame([paidTotal(json), paidTotal('[]')], [2249, 0]);
                    JS],
            ],
        ],
    ],
];
