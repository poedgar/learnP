<?php

// Lesson 5 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'arrays',
    'title' => 'Arrays',
    'summary' => 'Store lists in arrays: add, remove and find items, transform them with map, filter and reduce, sort them correctly, copy, spread and destructure them.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const temperatures = [14, 19, 23, 17, 25, 12];

        const warm = temperatures.filter((t) => t >= 18);
        const inFahrenheit = temperatures.map((t) => Math.round(t * 1.8 + 32));
        const average = temperatures.reduce((sum, t) => sum + t, 0) / temperatures.length;

        console.log(warm, inFahrenheit, average.toFixed(1));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const xs = ['a', 'b', 'c'];
                console.log(xs.length, xs[3], xs.at(-1));
                JS,
            'options' => ['`3 undefined c`', '`3 c c`', '`2 undefined c`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Indexes start at 0, so `xs[3]` is past the end, which gives `undefined`. `at(-1)` is the last item.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log([10, 9, 100, 1].sort().join(' '));
                JS,
            'options' => ['`1 10 100 9`', '`1 9 10 100`', '`100 10 9 1`', '`10 9 100 1`'],
            'correct_option' => 0,
            'explanation' => 'Without a compare function, `sort()` compares items as strings, so `\'100\'` comes before `\'9\'`.',
        ],
        [
            'question' => 'Which of these changes the array it\'s called on?',
            'options' => ['`map()`', '`slice()`', '`splice()`', '`filter()`'],
            'correct_option' => 2,
            'explanation' => '`splice()` removes and inserts in place; the others return a new array.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const total = [3, 5, 7].reduce((sum, n) => sum + n * 2, 0);
                console.log(total);
                JS,
            'options' => ['`30`', '`15`', '`27`', '`[6, 10, 14]`'],
            'correct_option' => 0,
            'explanation' => 'Starting from 0, it adds 6, 10 and 14.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const a = [1, 2];
                const b = a;
                b.push(3);
                console.log(a.length, a === b, [1, 2, 3] === [1, 2, 3]);
                JS,
            'options' => ['`3 true false`', '`2 false false`', '`3 true true`', '`2 true false`'],
            'correct_option' => 0,
            'explanation' => '`b` is the same array as `a`. Two separate arrays are never `===`, whatever they contain.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const [first, , third, ...rest] = [10, 20, 30, 40, 50];
                console.log(first, third, rest.length);
                JS,
            'options' => ['`10 30 2`', '`10 20 3`', '`10 30 3`', '`20 40 1`'],
            'correct_option' => 0,
            'explanation' => 'The empty slot skips 20; `...rest` collects 40 and 50.',
        ],
        [
            'question' => 'What is the difference between `find()` and `filter()`?',
            'options' => [
                'None',
                '`find()` gives the first matching item (or `undefined`); `filter()` gives an array of every match',
                '`find()` gives the index',
                '`filter()` changes the array',
            ],
            'correct_option' => 1,
            'explanation' => '`findIndex()` is the one that gives the position.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const words = ['pear', 'fig', 'banana'];
                console.log(words.some((w) => w.length > 5), words.every((w) => w.length > 2));
                JS,
            'options' => ['`true true`', '`true false`', '`false true`', '`false false`'],
            'correct_option' => 0,
            'explanation' => '"banana" has 6 letters, and every word has more than 2.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Even numbers, doubled',
            'prompt' => 'Write `doubledEvens(numbers)`, which returns a new array of the even numbers in `numbers`, each doubled, in order. It must not change `numbers`.',
            'starter_code' => <<<'JS'
                function doubledEvens(numbers) {
                    return numbers;
                }
                JS,
            'solution' => <<<'JS'
                function doubledEvens(numbers) {
                    return numbers.filter((n) => n % 2 === 0).map((n) => n * 2);
                }
                JS,
            'hint' => 'Chain `filter()` and `map()`.',
            'checks' => [
                ['label' => 'Doubles the even numbers', 'code' => 'return expectSame([doubledEvens([1, 2, 3, 4, 6]), doubledEvens([1, 3]), doubledEvens([])], [[4, 8, 12], [], []]);'],
                ['label' => 'Leaves the input alone', 'code' => "const input = [2, 5, 8];\ndoubledEvens(input);\nreturn expectSame(input, [2, 5, 8]);"],
            ],
        ],
        [
            'title' => 'The basket total',
            'prompt' => 'Write `basketTotal(items)`, where each item is `{ pence, qty }`, returning the total in pence, with `reduce()`. An empty basket is `0`.',
            'starter_code' => <<<'JS'
                function basketTotal(items) {
                    return items.reduce((sum, item) => sum + item.pence);
                }
                JS,
            'solution' => <<<'JS'
                function basketTotal(items) {
                    return items.reduce((sum, item) => sum + item.pence * item.qty, 0);
                }
                JS,
            'hint' => 'Multiply each price by its quantity, and give `reduce()` a starting value of `0`.',
            'checks' => [
                ['label' => 'Totals', 'code' => 'return expectSame([basketTotal([{ pence: 999, qty: 2 }, { pence: 250, qty: 4 }]), basketTotal([{ pence: 120, qty: 1 }])], [2998, 120]);'],
                ['label' => 'Empty', 'code' => 'return expectSame(basketTotal([]), 0);'],
            ],
        ],
        [
            'title' => 'Sorted numbers, as a copy',
            'prompt' => 'Write `sortedNumbers(numbers)`, returning a new array of the numbers from smallest to largest (`[10, 9, 100, 1]` → `[1, 9, 10, 100]`), without changing `numbers`.',
            'starter_code' => <<<'JS'
                function sortedNumbers(numbers) {
                    return numbers.sort();
                }
                JS,
            'solution' => <<<'JS'
                function sortedNumbers(numbers) {
                    return [...numbers].sort((a, b) => a - b);
                }
                JS,
            'hint' => 'Copy first with `[...numbers]`, and sort with the compare function `(a, b) => a - b`.',
            'checks' => [
                ['label' => 'Sorted as numbers', 'code' => 'return expectSame([sortedNumbers([10, 9, 100, 1]), sortedNumbers([-5, 3, -20, 0])], [[1, 9, 10, 100], [-20, -5, 0, 3]]);'],
                ['label' => 'Leaves the input alone', 'code' => "const input = [3, 1, 2];\nconst sorted = sortedNumbers(input);\nreturn expectSame([input, sorted === input], [[3, 1, 2], false]);"],
            ],
        ],
        [
            'title' => 'Without duplicates',
            'prompt' => 'Write `unique(items)`, returning the items with duplicates removed, keeping the first of each in its original order: `[3, 1, 3, 2, 1]` → `[3, 1, 2]`.',
            'starter_code' => <<<'JS'
                function unique(items) {
                    return items;
                }
                JS,
            'solution' => <<<'JS'
                function unique(items) {
                    const result = [];
                    for (const item of items) {
                        if (!result.includes(item)) {
                            result.push(item);
                        }
                    }
                    return result;
                }
                JS,
            'hint' => 'Build a new array, pushing each item only if it isn\'t already `includes()`d.',
            'checks' => [
                ['label' => 'Removes duplicates in order', 'code' => "return expectSame([unique([3, 1, 3, 2, 1]), unique(['b', 'a', 'b']), unique([])], [[3, 1, 2], ['b', 'a'], []]);"],
            ],
        ],
        [
            'title' => 'In chunks',
            'prompt' => 'Write `chunk(items, size)`, splitting `items` into arrays of `size` items, with a shorter last one if needed: `chunk([1, 2, 3, 4, 5], 2)` → `[[1, 2], [3, 4], [5]]`.',
            'starter_code' => <<<'JS'
                function chunk(items, size) {
                    return [items];
                }
                JS,
            'solution' => <<<'JS'
                function chunk(items, size) {
                    const chunks = [];
                    for (let i = 0; i < items.length; i += size) {
                        chunks.push(items.slice(i, i + size));
                    }
                    return chunks;
                }
                JS,
            'hint' => 'Step through the indexes `size` at a time, and `slice(i, i + size)` each piece.',
            'checks' => [
                ['label' => 'Chunks', 'code' => "return expectSame([chunk([1, 2, 3, 4, 5], 2), chunk(['a', 'b', 'c'], 3), chunk([1, 2], 5)], [[[1, 2], [3, 4], [5]], [['a', 'b', 'c']], [[1, 2]]]);"],
                ['label' => 'Empty', 'code' => 'return expectSame(chunk([], 3), []);'],
            ],
        ],
        [
            'title' => 'Top scorers',
            'prompt' => "Write `topScorers(players, count)`, where each player is `{ name, score }`. Return the names of the `count` highest scorers, highest first, with equal scores in alphabetical order of name. Don't change `players`.",
            'starter_code' => <<<'JS'
                function topScorers(players, count) {
                    return players.map((p) => p.name).slice(0, count);
                }
                JS,
            'solution' => <<<'JS'
                function topScorers(players, count) {
                    return [...players]
                        .sort((a, b) => b.score - a.score || a.name.localeCompare(b.name))
                        .slice(0, count)
                        .map((p) => p.name);
                }
                JS,
            'hint' => 'Sort a copy with `(a, b) => b.score - a.score || a.name.localeCompare(b.name)`, then `slice()` and `map()` to names.',
            'checks' => [
                ['label' => 'Highest first, ties by name', 'code' => <<<'JS'
                    const players = [
                        { name: 'Dee', score: 30 },
                        { name: 'Cy', score: 42 },
                        { name: 'Ben', score: 17 },
                        { name: 'Amara', score: 42 },
                    ];
                    return expectSame([topScorers(players, 3), topScorers(players, 1), topScorers(players, 10).length], [['Amara', 'Cy', 'Dee'], ['Amara'], 4]);
                    JS],
                ['label' => 'Leaves the players alone', 'code' => "const players = [{ name: 'B', score: 1 }, { name: 'A', score: 2 }];\ntopScorers(players, 2);\nreturn expectSame(players.map((p) => p.name), ['B', 'A']);"],
            ],
        ],
    ],
];
