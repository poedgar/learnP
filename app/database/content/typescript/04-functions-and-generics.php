<?php

// Lesson 4 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'functions-and-generics',
    'title' => 'Functions and generics',
    'summary' => 'Type functions and callbacks, write overloads, and make functions and types generic with type parameters, constraints, keyof and indexed access.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Hover-style: what type is each result?
        function last<T>(items: T[]): T | undefined {
            return items[items.length - 1];
        }

        const title = last(['Dune', 'Emma']);
        const year = last([1965, 1815]);

        console.log(title?.toLowerCase(), year && year + 1);
        TS,

    'quiz' => [
        [
            'question' => 'What does the `<T>` in `function first<T>(items: T[]): T | undefined` do?',
            'options' => [
                'Makes the function run faster',
                'Declares a type parameter: the function works for any item type, and returns that same type',
                'Limits it to strings',
                'Turns off type checking',
            ],
            'correct_option' => 1,
            'explanation' => '`first([\'a\'])` gives `string | undefined`; `first([1])` gives `number | undefined`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                function pair<A, B>(a: A, b: B): [B, A] {
                    return [b, a];
                }
                const [x, y] = pair('one', 2);
                console.log(x + 1, y.length);
                TS,
            'options' => ['`3 3`', '`one1 1`', 'A type error', '`21 3`'],
            'correct_option' => 0,
            'explanation' => 'The pair is swapped: `x` is the number 2 and `y` the string `\'one\'`.',
        ],
        [
            'question' => 'What does `T extends { length: number }` mean in a type parameter list?',
            'options' => [
                'T must be a class that extends another',
                'T can be any type that has a numeric `length`, such as strings and arrays, and inside the function `length` can be used',
                'T must be exactly `{ length: number }`',
                'T is an array',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a constraint on what the type parameter can be.',
        ],
        [
            'question' => 'What is `keyof { title: string; year: number }`?',
            'options' => ["`'title' | 'year'`", '`string | number`', '`string`', '`[\'title\', \'year\']`'],
            'correct_option' => 0,
            'explanation' => 'The union of the property names, as literal types.',
        ],
        [
            'question' => 'With `book: { title: string; year: number }`, what is the type `(typeof book)[\'year\']`?',
            'options' => ['`number`', "`'year'`", '`string`', '`unknown`'],
            'correct_option' => 0,
            'explanation' => 'An indexed access type `T[K]` is the type of property K of T.',
        ],
        [
            'question' => 'What is the return type of `async function load(): Promise<string>` when called as `load()`?',
            'options' => ['`string`', '`Promise<string>`', '`void`', '`any`'],
            'correct_option' => 1,
            'explanation' => 'Awaiting it gives a `string`.',
        ],
        [
            'question' => 'Why don\'t the parameters in `const add: (a: number, b: number) => number = (a, b) => a + b` need annotations?',
            'options' => [
                'They do; it\'s an error without them',
                'TypeScript knows their types from the variable\'s function type (contextual typing)',
                'Arrow functions never need types',
                'They\'re inferred as any',
            ],
            'correct_option' => 1,
            'explanation' => 'The same happens for callbacks such as those passed to `map()`.',
        ],
        [
            'question' => 'What does a type assertion like `value as User` do?',
            'options' => [
                'Converts the value into a User at run time',
                'Tells TypeScript to treat the value as a User, without any check; if you\'re wrong, nothing catches it',
                'Checks the value is a User and throws if not',
                'Creates a new User',
            ],
            'correct_option' => 1,
            'explanation' => 'Prefer narrowing or type guards, which really check.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A generic last()',
            'prompt' => "Write a generic `lastOf<T>(items: T[]): T | undefined`, returning the last item of the array, or `undefined` for an empty one. The result's type must be the item type: `lastOf(['a'])` is `string | undefined`.",
            'starter_code' => <<<'TS'
                function lastOf(items: any[]): any {
                    return items[items.length - 1];
                }
                TS,
            'solution' => <<<'TS'
                function lastOf<T>(items: T[]): T | undefined {
                    return items[items.length - 1];
                }
                TS,
            'hint' => 'Add `<T>` after the name, and use `T[]` and `T | undefined` instead of `any`.',
            'checks' => [
                ['label' => 'The last item', 'code' => "return expectSame([lastOf([1, 2, 3]), lastOf(['a']), lastOf([])], [3, 'a', undefined]);"],
                ['label' => 'Keeps the item type', 'compiles' => "const s: string | undefined = lastOf(['a', 'b']);\nconst n: number | undefined = lastOf([1]);"],
                ['label' => "Doesn't lose the type", 'rejects' => "const wrong: number | undefined = lastOf(['a']);"],
            ],
        ],
        [
            'title' => 'Group by a key',
            'prompt' => 'Write a generic `groupBy<T, K extends string>(items: T[], keyOf: (item: T) => K): Record<K, T[]>`, returning an object from each key to the items with that key, in order.',
            'starter_code' => <<<'TS'
                function groupBy(items, keyOf) {
                    return {};
                }
                TS,
            'solution' => <<<'TS'
                function groupBy<T, K extends string>(items: T[], keyOf: (item: T) => K): Record<K, T[]> {
                    const groups = {} as Record<K, T[]>;
                    for (const item of items) {
                        const key = keyOf(item);
                        (groups[key] ??= []).push(item);
                    }
                    return groups;
                }
                TS,
            'hint' => 'Start with `{} as Record<K, T[]>` (it fills up as you go), and push each item onto `groups[key] ??= []`.',
            'checks' => [
                ['label' => 'Groups items', 'code' => "return expectSame(groupBy(['apple', 'avocado', 'banana', 'cherry', 'blueberry'], (w) => w[0]), { a: ['apple', 'avocado'], b: ['banana', 'blueberry'], c: ['cherry'] });"],
                ['label' => 'Typed by the items', 'compiles' => "const byParity = groupBy([1, 2, 3], (n) => (n % 2 === 0 ? 'even' : 'odd'));\nconst evens: number[] = byParity.even;"],
                ['label' => 'Keys are strings', 'rejects' => 'groupBy([1, 2], (n) => n);'],
            ],
        ],
        [
            'title' => 'Sort by a key',
            'prompt' => "Write `sortBy<T, K extends keyof T>(items: T[], key: K): T[]`, returning a sorted copy of `items`, ordered by property `key` from smallest to largest (compare the values with `<` and `>`). Asking for a property the items don't have must be a type error.",
            'starter_code' => <<<'TS'
                function sortBy<T>(items: T[], key: string): T[] {
                    return items;
                }
                TS,
            'solution' => <<<'TS'
                function sortBy<T, K extends keyof T>(items: T[], key: K): T[] {
                    return [...items].sort((a, b) => (a[key] < b[key] ? -1 : a[key] > b[key] ? 1 : 0));
                }
                TS,
            'hint' => 'A second type parameter `K extends keyof T` for the key; then `a[key]` is allowed. Sort a copy.',
            'checks' => [
                ['label' => 'Sorts by the key', 'code' => "const books = [{ title: 'Emma', year: 1815 }, { title: 'Circe', year: 2018 }, { title: 'Dune', year: 1965 }];\nreturn expectSame([sortBy(books, 'title').map((b) => b.title), sortBy(books, 'year').map((b) => b.year), books[0].title], [['Circe', 'Dune', 'Emma'], [1815, 1965, 2018], 'Emma']);"],
                ['label' => 'Keeps the item type', 'compiles' => "const sorted: { title: string; year: number }[] = sortBy([{ title: 'A', year: 1 }], 'year');"],
                ['label' => 'Unknown properties are errors', 'rejects' => "sortBy([{ title: 'Dune', year: 1965 }], 'author');"],
            ],
        ],
        [
            'title' => 'The lowest score',
            'prompt' => "Write a generic `minBy<T>(items: T[], score: (item: T) => number): T | undefined`, returning the item with the lowest score (the first one, if there's a tie), or `undefined` for an empty array. The result must have the items' type.",
            'starter_code' => <<<'TS'
                function minBy(items: any[], score: (item: any) => number): any {
                    return items[0];
                }
                TS,
            'solution' => <<<'TS'
                function minBy<T>(items: T[], score: (item: T) => number): T | undefined {
                    let best: T | undefined;
                    let bestScore = Infinity;
                    for (const item of items) {
                        const value = score(item);
                        if (value < bestScore) {
                            best = item;
                            bestScore = value;
                        }
                    }
                    return best;
                }
                TS,
            'hint' => 'Keep the best item and its score so far; the callback\'s parameter has type `T` too.',
            'checks' => [
                ['label' => 'Finds the lowest', 'code' => "const flights = [{ to: 'Rome', pence: 8900 }, { to: 'Oslo', pence: 4500 }, { to: 'Nice', pence: 4500 }];\nreturn expectSame([minBy(flights, (f) => f.pence)?.to, minBy(['ccc', 'a', 'bb'], (w) => w.length), minBy([], () => 0)], ['Oslo', 'a', undefined]);"],
                ['label' => 'The callback knows the item type', 'compiles' => "const shortest: string | undefined = minBy(['aa', 'b'], (word) => word.length);"],
                ['label' => 'The score must be a number', 'rejects' => "minBy(['a'], (word) => word);"],
                ['label' => 'No any', 'code' => "return /\\bany\\b/.test(learnerCode()) ? 'Use a type parameter instead of any.' : true;"],
            ],
        ],
        [
            'title' => 'Overloads',
            'prompt' => 'Write `format` with two overloads: `format(value: number): string` (two decimal places) and `format(value: Date): string` (`YYYY-MM-DD`, in UTC), and one implementation. Any other argument type must be a type error.',
            'starter_code' => <<<'TS'
                function format(value: any): string {
                    return String(value);
                }
                TS,
            'solution' => <<<'TS'
                function format(value: number): string;
                function format(value: Date): string;
                function format(value: number | Date): string {
                    return typeof value === 'number' ? value.toFixed(2) : value.toISOString().slice(0, 10);
                }
                TS,
            'hint' => 'Two signature lines ending in `;`, then the implementation taking `number | Date`.',
            'checks' => [
                ['label' => 'Formats', 'code' => 'return expectSame([format(3.14159), format(new Date(Date.UTC(2026, 9, 16)))], [\'3.14\', \'2026-10-16\']);'],
                ['label' => 'Rejects other types', 'rejects' => "format('soon');"],
                ['label' => 'Has two overloads', 'code' => "return (learnerCode().match(/function\\s+format\\s*\\([^)]*\\)\\s*:\\s*string\\s*;/g) ?? []).length === 2 ? true : 'Write two overload signatures, each ending with a semicolon.';"],
            ],
        ],
        [
            'title' => 'A generic result type',
            'prompt' => 'Write a generic `interface Paged<T>` with `items: T[]`, `page: number` and `total: number`, and `async function loadPage<T>(all: T[], page: number, size: number): Promise<Paged<T>>`, returning that page of `all` (pages counted from 1) after waiting 10 ms.',
            'starter_code' => <<<'TS'
                async function loadPage(all: any[], page: number, size: number) {
                    return { items: all, page, total: all.length };
                }
                TS,
            'solution' => <<<'TS'
                interface Paged<T> {
                    items: T[];
                    page: number;
                    total: number;
                }

                async function loadPage<T>(all: T[], page: number, size: number): Promise<Paged<T>> {
                    await new Promise((resolve) => setTimeout(resolve, 10));
                    const start = (page - 1) * size;
                    return { items: all.slice(start, start + size), page, total: all.length };
                }
                TS,
            'hint' => 'The interface takes `<T>`, and the function returns `Promise<Paged<T>>`. Slice from `(page - 1) * size`.',
            'checks' => [
                ['label' => 'Pages', 'code' => "const letters = ['a', 'b', 'c', 'd', 'e'];\nreturn expectSame([await loadPage(letters, 1, 2), await loadPage(letters, 3, 2)], [{ items: ['a', 'b'], page: 1, total: 5 }, { items: ['e'], page: 3, total: 5 }]);"],
                ['label' => 'Typed by the items', 'compiles' => "async function check() {\n    const p: Paged<number> = await loadPage([1, 2, 3], 1, 2);\n    const first: number = p.items[0];\n}"],
                ['label' => 'Returns a promise', 'rejects' => "const notAwaited: Paged<string> = loadPage(['a'], 1, 1);"],
            ],
        ],
    ],
];
