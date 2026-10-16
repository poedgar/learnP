<?php

// Lesson 6 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'type-level-tools',
    'title' => 'Type-level tools',
    'summary' => 'Compute types from other types: typeof and keyof, indexed access, mapped types, template literal types, conditional types with infer, satisfies, and branded types.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Add a colour and see the type follow.
        const COLOURS = { red: '#e33', green: '#3a7', blue: '#36f' } as const;

        type Colour = keyof typeof COLOURS;
        type Hex = (typeof COLOURS)[Colour];

        function swatch(colour: Colour): string {
            return `${colour} is ${COLOURS[colour]}`;
        }

        const hex: Hex = '#3a7';
        console.log(swatch('blue'), hex);
        TS,

    'quiz' => [
        [
            'question' => 'With `const SIZES = { s: 1, m: 2 }`, what is `keyof typeof SIZES`?',
            'options' => ["`'s' | 'm'`", '`1 | 2`', '`string`', '`{ s: number; m: number }`'],
            'correct_option' => 0,
            'explanation' => '`typeof SIZES` is the object\'s type, and `keyof` gives its property names.',
        ],
        [
            'question' => 'What does the mapped type `{ [K in keyof T]: string }` produce?',
            'options' => [
                'An array of T\'s keys',
                'An object type with the same keys as T, each of type string',
                'T unchanged',
                'A union of strings',
            ],
            'correct_option' => 1,
            'explanation' => 'It goes through every key of T and gives each the type after the colon.',
        ],
        [
            'question' => 'What is ``type Pos = `${\'x\' | \'y\'}${1 | 2}` ``?',
            'options' => ["`'x1' | 'x2' | 'y1' | 'y2'`", "`'x' | 'y' | 1 | 2`", '`string`', "`'xy12'`"],
            'correct_option' => 0,
            'explanation' => 'A template literal type combines every member of the unions inside it.',
        ],
        [
            'question' => "What is `T extends string ? 'text' : 'other'` when T is `42`?",
            'options' => ["`'other'`", "`'text'`", '`never`', '`42`'],
            'correct_option' => 0,
            'explanation' => '`42` doesn\'t fit `string`, so the conditional type takes the second branch.',
        ],
        [
            'question' => 'What does `infer` do in `T extends Promise<infer V> ? V : T`?',
            'options' => [
                'Makes TypeScript guess the type of T',
                'Names the part of T that matches `V`, here the promise\'s value type, so it can be used in the result',
                'Converts T to a promise',
                'Nothing; it\'s a comment',
            ],
            'correct_option' => 1,
            'explanation' => '`Unpromise<Promise<number>>` is `number`.',
        ],
        [
            'question' => 'What is the advantage of `const config = {...} satisfies Config` over `const config: Config = {...}`?',
            'options' => [
                'None',
                'Both check the value against Config, but `satisfies` keeps the value\'s own, more precise type',
                '`satisfies` skips the check',
                '`satisfies` makes the object readonly',
            ],
            'correct_option' => 1,
            'explanation' => 'With an annotation, properties are only known by the broader types in Config.',
        ],
        [
            'question' => 'What is a branded type such as `number & { readonly brand: \'UserId\' }` for?',
            'options' => [
                'Making numbers faster',
                'Keeping values with the same underlying type but different meanings, such as user ids and order ids, from being mixed up',
                'Adding a property at run time',
                'Logging',
            ],
            'correct_option' => 1,
            'explanation' => 'The brand exists only in the type system.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                type Keys<T> = { [K in keyof T]: K }[keyof T];
                const book = { title: 'Dune', year: 1965 };
                const key: Keys<typeof book> = 'year';
                console.log(key, book[key]);
                TS,
            'options' => ['`year 1965`', 'A type error', '`title Dune`', '`year undefined`'],
            'correct_option' => 0,
            'explanation' => '`Keys<typeof book>` is `\'title\' | \'year\'`, so `\'year\'` is allowed.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Keys from an object',
            'prompt' => "Define `type Plan` as the keys of `PLANS` and `type PlanDetails` as the type of one of its values, both worked out from `PLANS` with `typeof`, `keyof` and an indexed access. Then write `monthly(plan: Plan): string`, e.g. `'Pro: £12.00 a month'`.",
            'starter_code' => <<<'TS'
                const PLANS = {
                    basic: { label: 'Basic', pence: 500 },
                    pro: { label: 'Pro', pence: 1200 },
                    team: { label: 'Team', pence: 3000 },
                };

                type Plan = string;
                type PlanDetails = object;

                function monthly(plan: Plan): string {
                    return plan;
                }
                TS,
            'solution' => <<<'TS'
                const PLANS = {
                    basic: { label: 'Basic', pence: 500 },
                    pro: { label: 'Pro', pence: 1200 },
                    team: { label: 'Team', pence: 3000 },
                };

                type Plan = keyof typeof PLANS;
                type PlanDetails = (typeof PLANS)[Plan];

                function monthly(plan: Plan): string {
                    const details: PlanDetails = PLANS[plan];
                    return `${details.label}: £${(details.pence / 100).toFixed(2)} a month`;
                }
                TS,
            'hint' => '`keyof typeof PLANS` for the keys, and `(typeof PLANS)[Plan]` for a value.',
            'checks' => [
                ['label' => 'Monthly prices', 'code' => "return expectSame([monthly('pro'), monthly('basic')], ['Pro: £12.00 a month', 'Basic: £5.00 a month']);"],
                ['label' => 'Only the plans that exist', 'rejects' => "monthly('enterprise');"],
                ['label' => 'Plan details have a label and pence', 'compiles' => "const d: PlanDetails = { label: 'X', pence: 1 };\nconst p: Plan = 'team';"],
                ['label' => 'Plan details need both', 'rejects' => "const d: PlanDetails = { label: 'X' };"],
            ],
        ],
        [
            'title' => 'A mapped type',
            'prompt' => "Write a mapped type `Validators<T>`, giving every property of `T` a function `(value: T[K]) => boolean` that checks that property's value. Then `validate<T>(value: T, validators: Validators<T>): (keyof T)[]` returns the names of the properties that fail.",
            'starter_code' => <<<'TS'
                type Validators<T> = any;

                function validate<T>(value: T, validators: Validators<T>): (keyof T)[] {
                    return [];
                }
                TS,
            'solution' => <<<'TS'
                type Validators<T> = { [K in keyof T]: (value: T[K]) => boolean };

                function validate<T>(value: T, validators: Validators<T>): (keyof T)[] {
                    const failing: (keyof T)[] = [];
                    for (const key of Object.keys(validators) as (keyof T)[]) {
                        if (!validators[key](value[key])) {
                            failing.push(key);
                        }
                    }
                    return failing;
                }
                TS,
            'hint' => '`{ [K in keyof T]: (value: T[K]) => boolean }`. Go through the validators\' keys (`Object.keys(...) as (keyof T)[]`).',
            'checks' => [
                ['label' => 'Finds failing properties', 'code' => <<<'JS'
                    const rules = { name: (v) => v.length > 0, age: (v) => v >= 18 };
                    return expectSame([validate({ name: 'Amara', age: 34 }, rules), validate({ name: '', age: 12 }, rules)], [[], ['name', 'age']]);
                    JS],
                ['label' => 'Each validator gets the right type', 'compiles' => "const v: Validators<{ name: string; age: number }> = { name: (s) => s.trim() !== '', age: (n) => n.toFixed(0) !== '0' };"],
                ['label' => 'Every property needs a validator', 'rejects' => 'const v: Validators<{ name: string; age: number }> = { name: () => true };'],
                ['label' => 'Validators get the property type', 'rejects' => 'const v: Validators<{ age: number }> = { age: (s: string) => s.length > 0 };'],
            ],
        ],
        [
            'title' => 'Event names',
            'prompt' => "With template literal types, define `type EventName` as every combination of `Entity` and `Action` written as `'entity:action'`, such as `'book:created'`. Then write `parseEvent(name: EventName): { entity: Entity; action: Action }`.",
            'starter_code' => <<<'TS'
                type Entity = 'book' | 'member' | 'loan';
                type Action = 'created' | 'updated' | 'deleted';
                type EventName = string;

                function parseEvent(name: EventName) {
                    return name.split(':');
                }
                TS,
            'solution' => <<<'TS'
                type Entity = 'book' | 'member' | 'loan';
                type Action = 'created' | 'updated' | 'deleted';
                type EventName = `${Entity}:${Action}`;

                function parseEvent(name: EventName): { entity: Entity; action: Action } {
                    const [entity, action] = name.split(':') as [Entity, Action];
                    return { entity, action };
                }
                TS,
            'hint' => '`type EventName = `${Entity}:${Action}``. The split parts are plain strings, so assert them as `[Entity, Action]`.',
            'checks' => [
                ['label' => 'Parses names', 'code' => "return expectSame([parseEvent('book:created'), parseEvent('loan:deleted')], [{ entity: 'book', action: 'created' }, { entity: 'loan', action: 'deleted' }]);"],
                ['label' => 'Every combination is allowed', 'compiles' => "const names: EventName[] = ['book:created', 'member:updated', 'loan:deleted'];"],
                ['label' => 'Nothing else is', 'rejects' => "const bad: EventName = 'book:archived';"],
                ['label' => 'The result is typed', 'compiles' => "const e: Entity = parseEvent('member:created').entity;"],
            ],
        ],
        [
            'title' => 'Unwrap types with infer',
            'prompt' => 'Write the conditional types `ItemOf<T>` (the element type for an array type, otherwise `never`) and `ResolvedOf<T>` (the value type for a `Promise`, otherwise `T` itself), using `infer`. Write `firstResolved` to `await` the first promise in an array and return its value.',
            'starter_code' => <<<'TS'
                type ItemOf<T> = T;
                type ResolvedOf<T> = T;

                async function firstResolved<T>(promises: Promise<T>[]): Promise<T | undefined> {
                    return undefined;
                }
                TS,
            'solution' => <<<'TS'
                type ItemOf<T> = T extends (infer Item)[] ? Item : never;
                type ResolvedOf<T> = T extends Promise<infer Value> ? Value : T;

                async function firstResolved<T>(promises: Promise<T>[]): Promise<T | undefined> {
                    return promises.length > 0 ? await promises[0] : undefined;
                }
                TS,
            'hint' => '`T extends (infer Item)[] ? Item : never`, and the same pattern with `Promise<infer Value>`.',
            'checks' => [
                ['label' => 'firstResolved()', 'code' => 'return expectSame([await firstResolved([Promise.resolve(1), Promise.resolve(2)]), await firstResolved([])], [1, undefined]);'],
                ['label' => 'ItemOf', 'compiles' => "const s: ItemOf<string[]> = 'a';\nconst n: ItemOf<number[]> = 1;"],
                ['label' => 'ItemOf of a non-array is never', 'rejects' => "const x: ItemOf<string> = 'a';"],
                ['label' => 'ResolvedOf', 'compiles' => "const v: ResolvedOf<Promise<number>> = 3;\nconst same: ResolvedOf<string> = 'plain';"],
                ['label' => 'ResolvedOf unwraps the promise', 'rejects' => 'const v: ResolvedOf<Promise<number>> = Promise.resolve(3);'],
            ],
        ],
        [
            'title' => 'Checked but precise',
            'prompt' => "Declare `ROUTES` so it's checked against `Record<RouteName, string>` (every route must be there, each a string) but keeps its exact string values: `ROUTES.home` must have the type `'/'`, not `string`. Then write `link(name: RouteName): string`, e.g. `'<a href=\"/books\">books</a>'`.",
            'starter_code' => <<<'TS'
                type RouteName = 'home' | 'books' | 'about';

                const ROUTES: Record<RouteName, string> = {
                    home: '/',
                    books: '/books',
                    about: '/about',
                };

                function link(name: RouteName): string {
                    return ROUTES[name];
                }
                TS,
            'solution' => <<<'TS'
                type RouteName = 'home' | 'books' | 'about';

                const ROUTES = {
                    home: '/',
                    books: '/books',
                    about: '/about',
                } as const satisfies Record<RouteName, string>;

                function link(name: RouteName): string {
                    return `<a href="${ROUTES[name]}">${name}</a>`;
                }
                TS,
            'hint' => '`as const` keeps the exact strings, and `satisfies Record<RouteName, string>` checks the object: `{ ... } as const satisfies ...`.',
            'checks' => [
                ['label' => 'Links', 'code' => "return expectSame([link('books'), link('home')], ['<a href=\"/books\">books</a>', '<a href=\"/\">home</a>']);"],
                ['label' => 'Keeps exact values', 'compiles' => "const home: '/' = ROUTES.home;\nconst books: '/books' = ROUTES.books;"],
                ['label' => 'Uses satisfies', 'code' => "return /satisfies\\s+Record\\s*<\\s*RouteName/.test(learnerCode()) ? true : 'Check ROUTES with satisfies Record<RouteName, string>.';"],
            ],
        ],
        [
            'title' => 'Ids that can\'t be mixed up',
            'prompt' => 'Make `BookId` and `MemberId` branded types over `number`, with functions `bookId(n: number): BookId` and `memberId(n: number): MemberId` that throw a `RangeError` unless `n` is a positive whole number. Type `lend(book: BookId, member: MemberId): string` so that passing the ids the wrong way round is a type error.',
            'starter_code' => <<<'TS'
                type BookId = number;
                type MemberId = number;

                const bookId = (n: number): BookId => n;
                const memberId = (n: number): MemberId => n;

                function lend(book: BookId, member: MemberId): string {
                    return `book ${book} to member ${member}`;
                }
                TS,
            'solution' => <<<'TS'
                type BookId = number & { readonly brand: 'BookId' };
                type MemberId = number & { readonly brand: 'MemberId' };

                function checkId(n: number): number {
                    if (!Number.isInteger(n) || n <= 0) {
                        throw new RangeError(`Not a valid id: ${n}`);
                    }
                    return n;
                }

                const bookId = (n: number): BookId => checkId(n) as BookId;
                const memberId = (n: number): MemberId => checkId(n) as MemberId;

                function lend(book: BookId, member: MemberId): string {
                    return `book ${book} to member ${member}`;
                }
                TS,
            'hint' => 'Intersect `number` with an object type holding a different `brand` literal for each, and assert with `as` after checking.',
            'checks' => [
                ['label' => 'Lends', 'code' => "return expectSame(lend(bookId(12), memberId(7)), 'book 12 to member 7');"],
                ['label' => 'Ids are checked', 'code' => <<<'JS'
                    for (const bad of [0, -3, 2.5]) {
                        const result = await expectThrows(() => bookId(bad), RangeError);
                        if (result !== true) return `bookId(${bad}): ${result}`;
                    }
                    return expectThrows(() => memberId(0), RangeError);
                    JS],
                ['label' => "Can't be swapped", 'rejects' => 'lend(memberId(7), bookId(12));'],
                ['label' => 'Plain numbers need checking first', 'rejects' => 'lend(12, 7);'],
            ],
        ],
    ],
];
