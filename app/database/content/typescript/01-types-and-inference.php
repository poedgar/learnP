<?php

// Lesson 1 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'types-and-inference',
    'title' => 'Types and inference',
    'summary' => 'Add types to JavaScript: annotations, inference, function types, strict null checks, tuples, any versus unknown, and literal types.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Try passing a string to double().
        function double(n: number): number {
            return n * 2;
        }

        const values = [1, 2, 3];
        const doubled = values.map(double);

        console.log(doubled);
        TS,

    'quiz' => [
        [
            'question' => 'What happens to TypeScript\'s types when the code runs?',
            'options' => [
                'They\'re checked again at run time',
                'They\'re removed: TypeScript checks them first, then produces ordinary JavaScript',
                'They make the code run faster',
                'They\'re turned into comments',
            ],
            'correct_option' => 1,
            'explanation' => 'Types exist only for the checker. At run time it\'s just JavaScript.',
        ],
        [
            'question' => 'What type does TypeScript infer for `let count = 3`?',
            'options' => ['`number`', '`3`', '`any`', '`unknown`'],
            'correct_option' => 0,
            'explanation' => 'A `let` can change, so it gets the general type. `const count = 3` would get the literal type `3`.',
        ],
        [
            'question' => 'Where are type annotations most needed?',
            'options' => [
                'On every variable',
                'On function parameters, which TypeScript can\'t work out on its own',
                'On return statements',
                'Nowhere; TypeScript infers everything',
            ],
            'correct_option' => 1,
            'explanation' => 'Variables usually get their types from their values.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                function label(name: string, prefix?: string): string {
                    return prefix ? `${prefix} ${name}` : name;
                }
                console.log(label('Okafor', 'Dr'), label('Ito'));
                TS,
            'options' => ['`Dr Okafor Ito`', '`Dr Okafor undefined Ito`', 'A type error', '`Okafor Ito`'],
            'correct_option' => 0,
            'explanation' => '`prefix?` is optional, so it can be left out, and is then `undefined`.',
        ],
        [
            'question' => 'What is the difference between `any` and `unknown`?',
            'options' => [
                'None',
                '`any` turns checking off entirely; `unknown` accepts anything but must be checked (narrowed) before it\'s used',
                '`unknown` is only for errors',
                '`any` can\'t hold objects',
            ],
            'correct_option' => 1,
            'explanation' => 'Prefer `unknown` for values whose type you don\'t know yet.',
        ],
        [
            'question' => 'Why is `const found = list.find(...); found.trim();` a type error with strict checks?',
            'options' => [
                '`find()` doesn\'t exist on arrays',
                '`find()` may return `undefined`, and calling a method on `undefined` would crash',
                '`trim()` is not a string method',
                'It isn\'t an error',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `found?.trim()`, or check `if (found)` first.',
        ],
        [
            'question' => 'What does `[string, number]` describe?',
            'options' => [
                'An array of strings and numbers in any order',
                'A tuple: an array of exactly two items, a string and then a number',
                'A string or a number',
                'An object with string keys and number values',
            ],
            'correct_option' => 1,
            'explanation' => '`(string | number)[]` would be any mix of the two, of any length.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                const SIZES = ['S', 'M', 'L'] as const;
                type Size = (typeof SIZES)[number];
                const pick: Size = 'L';
                console.log(SIZES.length, pick);
                TS,
            'options' => ['`3 L`', 'A type error', '`3 Size`', '`undefined L`'],
            'correct_option' => 0,
            'explanation' => '`Size` is `\'S\' | \'M\' | \'L\'`, so `\'L\'` is allowed. Types don\'t change what runs.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Annotate a function',
            'prompt' => 'Give `pricePence()` types: `pounds` is a number, `pence` is a number with a default of `0`, and it returns a number. Passing a string for either must then be a type error.',
            'starter_code' => <<<'TS'
                function pricePence(pounds, pence) {
                    return pounds * 100 + pence;
                }
                TS,
            'solution' => <<<'TS'
                function pricePence(pounds: number, pence: number = 0): number {
                    return pounds * 100 + pence;
                }
                TS,
            'hint' => 'Write `pounds: number` and `pence: number = 0` (or just `pence = 0`), and `: number` after the brackets.',
            'checks' => [
                ['label' => 'Works out pence', 'code' => 'return expectSame([pricePence(9, 99), pricePence(12)], [999, 1200]);'],
                ['label' => 'Returns a number', 'compiles' => 'const p: number = pricePence(1, 50);'],
                ['label' => 'Rejects text', 'rejects' => "pricePence('9', 99);"],
                ['label' => 'Rejects text for pence too', 'rejects' => "pricePence(9, '99');"],
            ],
        ],
        [
            'title' => 'Fix the type errors',
            'prompt' => "This code doesn't type-check. Fix it so it does, and so `totalPence` is `1449`. Change the data where it's wrong; don't use `any`.",
            'starter_code' => <<<'TS'
                const prices = [999, '450'];
                let totalPence: number = 0;

                for (const price of prices) {
                    totalPence += price;
                }

                const label: string = totalPence;
                TS,
            'solution' => <<<'TS'
                const prices = [999, 450];
                let totalPence: number = 0;

                for (const price of prices) {
                    totalPence += price;
                }

                const label: string = String(totalPence);
                TS,
            'hint' => 'The price that\'s a string should be a number, and a number can be turned into a string with `String()`.',
            'checks' => [
                ['label' => 'The total', 'code' => "return expectSame([totalPence, label], [1449, '1449']);"],
                ['label' => 'No any', 'code' => "return /\\bany\\b/.test(learnerCode()) ? 'Fix it without any.' : true;"],
                ['label' => 'prices is a list of numbers', 'compiles' => 'const first: number = prices[0];'],
            ],
        ],
        [
            'title' => 'Return a tuple',
            'prompt' => 'Write `minMax(values: number[]): [number, number]`, returning the smallest and largest of a non-empty list as a two-item tuple.',
            'starter_code' => <<<'TS'
                function minMax(values: number[]) {
                    return [Math.min(...values), Math.max(...values)];
                }
                TS,
            'solution' => <<<'TS'
                function minMax(values: number[]): [number, number] {
                    return [Math.min(...values), Math.max(...values)];
                }
                TS,
            'hint' => 'Without a return type, TypeScript infers `number[]`, an array of any length. Write `: [number, number]`.',
            'checks' => [
                ['label' => 'Smallest and largest', 'code' => 'return expectSame([minMax([3, 9, -2, 7]), minMax([5])], [[-2, 9], [5, 5]]);'],
                ['label' => 'Two named parts', 'compiles' => 'const [lo, hi]: [number, number] = minMax([1, 2]);'],
                ['label' => 'No third part', 'rejects' => 'const [a, b, c] = minMax([1, 2]);'],
            ],
        ],
        [
            'title' => 'Unknown input',
            'prompt' => 'Write `toNumber(value: unknown): number`. Return numbers as they are; turn strings into numbers with `Number()`, throwing a `TypeError` if that gives `NaN`; throw a `TypeError` for anything else.',
            'starter_code' => <<<'TS'
                function toNumber(value: unknown): number {
                    return Number(value);
                }
                TS,
            'solution' => <<<'TS'
                function toNumber(value: unknown): number {
                    if (typeof value === 'number') {
                        return value;
                    }
                    if (typeof value === 'string') {
                        const parsed = Number(value);
                        if (Number.isNaN(parsed)) {
                            throw new TypeError(`Not a number: ${value}`);
                        }
                        return parsed;
                    }
                    throw new TypeError(`Can't turn ${typeof value} into a number`);
                }
                TS,
            'hint' => 'Check `typeof value === \'number\'` and `typeof value === \'string\'`; inside each check, TypeScript knows the type.',
            'checks' => [
                ['label' => 'Numbers and text', 'code' => "return expectSame([toNumber(4), toNumber('2.5'), toNumber('7')], [4, 2.5, 7]);"],
                ['label' => 'Bad text', 'code' => "return expectThrows(() => toNumber('seven'), TypeError);"],
                ['label' => 'Other values', 'code' => <<<'JS'
                    for (const bad of [null, undefined, [1], true]) {
                        const result = await expectThrows(() => toNumber(bad), TypeError);
                        if (result !== true) return `${inspect(bad)}: ${result}`;
                    }
                    return true;
                    JS],
                ['label' => 'Returns a number', 'rejects' => "const s: string = toNumber('1');"],
            ],
        ],
        [
            'title' => 'Only these directions',
            'prompt' => "Write `move(position: [number, number], direction: Direction): [number, number]`, with a type `Direction` allowing only `'up'`, `'down'`, `'left'` and `'right'`. `up` takes 1 off y (the second number), `down` adds 1 to y, and `left` and `right` change x.",
            'starter_code' => <<<'TS'
                type Direction = string;

                function move(position: [number, number], direction: Direction): [number, number] {
                    return position;
                }
                TS,
            'solution' => <<<'TS'
                type Direction = 'up' | 'down' | 'left' | 'right';

                function move([x, y]: [number, number], direction: Direction): [number, number] {
                    switch (direction) {
                        case 'up':
                            return [x, y - 1];
                        case 'down':
                            return [x, y + 1];
                        case 'left':
                            return [x - 1, y];
                        case 'right':
                            return [x + 1, y];
                    }
                }
                TS,
            'hint' => 'Make `Direction` a union of four string literals with `|`, and `switch` on it.',
            'checks' => [
                ['label' => 'Moves', 'code' => "return expectSame([move([2, 2], 'up'), move([2, 2], 'down'), move([2, 2], 'left'), move([2, 2], 'right')], [[2, 1], [2, 3], [1, 2], [3, 2]]);"],
                ['label' => 'Accepts the four directions', 'compiles' => "const d: Direction = 'left';\nmove([0, 0], d);"],
                ['label' => 'Rejects anything else', 'rejects' => "move([0, 0], 'north');"],
            ],
        ],
        [
            'title' => 'A fixed list of sizes',
            'prompt' => "Change the code so that `SIZES` can't be changed and `Size` is exactly `'S' | 'M' | 'L' | 'XL'`, worked out from `SIZES` (so adding a size to the list adds it to the type). Then write `nextSize(size: Size): Size`, giving the next size up, or `'XL'` for `'XL'`.",
            'starter_code' => <<<'TS'
                const SIZES = ['S', 'M', 'L', 'XL'];
                type Size = (typeof SIZES)[number];

                function nextSize(size: Size): Size {
                    return size;
                }
                TS,
            'solution' => <<<'TS'
                const SIZES = ['S', 'M', 'L', 'XL'] as const;
                type Size = (typeof SIZES)[number];

                function nextSize(size: Size): Size {
                    const index = SIZES.indexOf(size);
                    return SIZES[Math.min(index + 1, SIZES.length - 1)];
                }
                TS,
            'hint' => 'Add `as const` to the array. Then find the size\'s index and return the one after it, stopping at the last.',
            'checks' => [
                ['label' => 'The next size', 'code' => "return expectSame(['S', 'M', 'L', 'XL'].map(nextSize), ['M', 'L', 'XL', 'XL']);"],
                ['label' => 'Size allows the listed sizes', 'compiles' => "const a: Size = 'S';\nconst b: Size = 'XL';"],
                ['label' => 'Size allows nothing else', 'rejects' => "const c: Size = 'XXL';"],
                ['label' => "SIZES can't be changed", 'rejects' => "SIZES.push('XXL');"],
            ],
        ],
    ],
];
