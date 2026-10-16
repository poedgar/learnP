<?php

// Lesson 2 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'object-types',
    'title' => 'Object types and interfaces',
    'summary' => 'Describe the shape of objects with type aliases and interfaces: optional and readonly properties, extending interfaces, structural typing, index signatures and Record, and intersections.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Try removing a property from the book.
        interface Book {
            title: string;
            year: number;
            subtitle?: string;
        }

        function label(book: Book): string {
            return book.subtitle ? `${book.title}: ${book.subtitle} (${book.year})` : `${book.title} (${book.year})`;
        }

        console.log(label({ title: 'Dune', year: 1965 }));
        console.log(label({ title: 'Emma', year: 1815, subtitle: 'A Novel' }));
        TS,

    'quiz' => [
        [
            'question' => 'What does `interface Member extends Person { memberSince: number }` mean?',
            'options' => [
                'A Member has everything a Person has, plus `memberSince`',
                'A Member is a class that inherits from Person',
                'A Member replaces Person',
                'A Member has only `memberSince`',
            ],
            'correct_option' => 0,
            'explanation' => 'So a Member can be used wherever a Person is expected.',
        ],
        [
            'question' => 'What is the type of `product.discount` if the interface says `discount?: number`?',
            'options' => ['`number`', '`number | undefined`', '`number | null`', '`any`'],
            'correct_option' => 1,
            'explanation' => 'An optional property may be missing, which reads as `undefined`.',
        ],
        [
            'question' => 'What does "structural typing" mean?',
            'options' => [
                'Types must be declared before use',
                'Any value with the right shape (the right properties) fits a type, whatever it was declared as',
                'Only classes can be types',
                'Types are checked at run time',
            ],
            'correct_option' => 1,
            'explanation' => 'An object with `x` and `y` numbers is a `Point`, even if no one said so.',
        ],
        [
            'question' => 'Why is `const p: Point = { x: 1, y: 2, z: 3 }` an error, when passing a variable with an extra property is fine?',
            'options' => [
                'It isn\'t an error',
                'Object literals written straight into a typed place are checked for extra properties, which are usually typos',
                'Points can\'t have numbers',
                '`z` is a reserved word',
            ],
            'correct_option' => 1,
            'explanation' => 'This "excess property check" only applies to fresh object literals.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                const counts: Record<string, number> = {};
                for (const word of ['a', 'b', 'a']) {
                    counts[word] = (counts[word] ?? 0) + 1;
                }
                console.log(counts.a, Object.keys(counts).length);
                TS,
            'options' => ['`2 2`', '`1 2`', '`2 3`', 'A type error'],
            'correct_option' => 0,
            'explanation' => '`Record<string, number>` allows any string key with a number value.',
        ],
        [
            'question' => 'Which property can\'t be reassigned after the object is created?',
            'options' => ['`name?: string`', '`readonly id: number`', '`const id: number`', '`static id: number`'],
            'correct_option' => 1,
            'explanation' => '`readonly` is checked by TypeScript; `const` isn\'t used inside object types.',
        ],
        [
            'question' => 'What does `type Item = Named & Priced` describe?',
            'options' => [
                'Something that is either Named or Priced',
                'Something with all the properties of Named and of Priced',
                'Only the properties both have in common',
                'A type error',
            ],
            'correct_option' => 1,
            'explanation' => '`&` is an intersection; `|`, coming next lesson, means either.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                interface Point {
                    x: number;
                    y: number;
                }
                const place = { name: 'Leeds', x: 3, y: 4 };
                const point: Point = place;
                console.log(point.x + point.y);
                TS,
            'options' => ['`7`', 'A type error: place has an extra property', '`34`', '`undefined`'],
            'correct_option' => 0,
            'explanation' => '`place` is a variable, not a fresh literal, so its extra `name` is fine.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Describe a book',
            'prompt' => "Write an interface `Book` with `title` and `author` (strings), `year` (a number) and an optional `series` (a string). Then type `cite(book: Book): string`, which returns `'Author, Title (Year)'`, with `', Series'` before the year's brackets when there is one.",
            'starter_code' => <<<'TS'
                function cite(book) {
                    return `${book.author}, ${book.title} (${book.year})`;
                }
                TS,
            'solution' => <<<'TS'
                interface Book {
                    title: string;
                    author: string;
                    year: number;
                    series?: string;
                }

                function cite(book: Book): string {
                    const series = book.series ? `, ${book.series}` : '';
                    return `${book.author}, ${book.title}${series} (${book.year})`;
                }
                TS,
            'hint' => 'Declare the interface with `series?: string`, and add the series only when it\'s there.',
            'checks' => [
                ['label' => 'Citations', 'code' => "return expectSame([cite({ title: 'Dune', author: 'Frank Herbert', year: 1965 }), cite({ title: 'Emma', author: 'Jane Austen', year: 1815, series: 'Penguin Classics' })], ['Frank Herbert, Dune (1965)', 'Jane Austen, Emma, Penguin Classics (1815)']);"],
                ['label' => 'The series is optional', 'compiles' => "const b: Book = { title: 'A', author: 'B', year: 2000 };\ncite(b);"],
                ['label' => 'The year is required', 'rejects' => "const c: Book = { title: 'A', author: 'B' };"],
                ['label' => 'The year is a number', 'rejects' => "cite({ title: 'A', author: 'B', year: '2000' });"],
            ],
        ],
        [
            'title' => 'Extend an interface',
            'prompt' => "Write an interface `Person` (`name`, `email`) and an interface `Employee` that **extends** it with `department` (a string) and `salary` (a number). Write `directory(people: Person[]): string[]`, returning `'NAME <EMAIL>'` for each, so it accepts lists of employees too.",
            'starter_code' => <<<'TS'
                interface Person {
                    name: string;
                }

                function directory(people: Person[]): string[] {
                    return people.map((p) => p.name);
                }
                TS,
            'solution' => <<<'TS'
                interface Person {
                    name: string;
                    email: string;
                }

                interface Employee extends Person {
                    department: string;
                    salary: number;
                }

                function directory(people: Person[]): string[] {
                    return people.map((person) => `${person.name} <${person.email}>`);
                }
                TS,
            'hint' => '`interface Employee extends Person { ... }` adds properties to everything a Person has.',
            'checks' => [
                ['label' => 'The directory', 'code' => "return expectSame(directory([{ name: 'Amara', email: 'a@example.com' }, { name: 'Ben', email: 'b@example.com', department: 'IT', salary: 1 }]), ['Amara <a@example.com>', 'Ben <b@example.com>']);"],
                ['label' => 'Employees are people', 'compiles' => "const staff: Employee[] = [{ name: 'Cy', email: 'c@example.com', department: 'Sales', salary: 30000 }];\ndirectory(staff);\nconst e: Employee = staff[0];\nconst d: string = e.department;"],
                ['label' => 'An employee needs a department', 'rejects' => "const e: Employee = { name: 'Cy', email: 'c@example.com', salary: 30000 };"],
                ['label' => 'Employee extends Person', 'code' => "return /interface\\s+Employee\\s+extends\\s+Person\\b/.test(learnerCode()) ? true : 'Declare Employee with extends Person.';"],
            ],
        ],
        [
            'title' => 'Read-only ids',
            'prompt' => 'Write an interface `Account` with a `readonly id` (a number), `owner` (a string) and `balance` (a number). Write `deposit(account: Account, pence: number): Account`, which returns a **new** account with the balance increased, leaving the original unchanged.',
            'starter_code' => <<<'TS'
                interface Account {
                    id: number;
                    owner: string;
                    balance: number;
                }

                function deposit(account: Account, pence: number): Account {
                    account.balance += pence;
                    return account;
                }
                TS,
            'solution' => <<<'TS'
                interface Account {
                    readonly id: number;
                    owner: string;
                    balance: number;
                }

                function deposit(account: Account, pence: number): Account {
                    return { ...account, balance: account.balance + pence };
                }
                TS,
            'hint' => 'Mark `id` with `readonly`, and return `{ ...account, balance: ... }`.',
            'checks' => [
                ['label' => 'Returns a new account', 'code' => "const before = { id: 1, owner: 'Amara', balance: 1000 };\nconst after = deposit(before, 250);\nreturn expectSame([after, before.balance, after !== before], [{ id: 1, owner: 'Amara', balance: 1250 }, 1000, true]);"],
                ['label' => "The id can't be changed", 'rejects' => "const a: Account = { id: 1, owner: 'A', balance: 0 };\na.id = 2;"],
                ['label' => 'The owner can be changed', 'compiles' => "const a: Account = { id: 1, owner: 'A', balance: 0 };\na.owner = 'B';"],
            ],
        ],
        [
            'title' => 'A lookup table',
            'prompt' => "Type `currencySymbols` so it must have exactly the keys `'GBP'`, `'EUR'` and `'USD'`, each with a string value, using `Record` and a `Currency` type. Then write `format(pence: number, currency: Currency): string`, e.g. `format(1250, 'EUR')` → `'€12.50'`.",
            'starter_code' => <<<'TS'
                const currencySymbols = { GBP: '£', EUR: '€', USD: '$' };

                function format(pence: number, currency: string): string {
                    return `${currencySymbols[currency]}${pence / 100}`;
                }
                TS,
            'solution' => <<<'TS'
                type Currency = 'GBP' | 'EUR' | 'USD';

                const currencySymbols: Record<Currency, string> = { GBP: '£', EUR: '€', USD: '$' };

                function format(pence: number, currency: Currency): string {
                    return `${currencySymbols[currency]}${(pence / 100).toFixed(2)}`;
                }
                TS,
            'hint' => '`type Currency = \'GBP\' | \'EUR\' | \'USD\'`, then `Record<Currency, string>`. Use `toFixed(2)` for the pence.',
            'checks' => [
                ['label' => 'Formats amounts', 'code' => "return expectSame([format(1250, 'EUR'), format(999, 'GBP'), format(5, 'USD')], ['€12.50', '£9.99', '$0.05']);"],
                ['label' => 'Only known currencies', 'rejects' => "format(100, 'JPY');"],
                ['label' => 'Every currency needs a symbol', 'rejects' => "const missing: Record<Currency, string> = { GBP: '£', EUR: '€' };"],
            ],
        ],
        [
            'title' => 'Typed order lines',
            'prompt' => 'Write interfaces `OrderLine` (`title`, `pence`, `qty`) and `Order` (`id`, an optional `note`, and `lines`, an array of order lines). Then write `orderTotal(order: Order): number` and `biggestLine(order: Order): OrderLine | undefined` (the line with the highest `pence * qty`, or `undefined` for an empty order).',
            'starter_code' => <<<'TS'
                function orderTotal(order: any): number {
                    return 0;
                }

                function biggestLine(order: any) {
                    return order.lines[0];
                }
                TS,
            'solution' => <<<'TS'
                interface OrderLine {
                    title: string;
                    pence: number;
                    qty: number;
                }

                interface Order {
                    id: number;
                    note?: string;
                    lines: OrderLine[];
                }

                const lineTotal = (line: OrderLine): number => line.pence * line.qty;

                function orderTotal(order: Order): number {
                    return order.lines.reduce((sum, line) => sum + lineTotal(line), 0);
                }

                function biggestLine(order: Order): OrderLine | undefined {
                    let biggest: OrderLine | undefined;
                    for (const line of order.lines) {
                        if (!biggest || lineTotal(line) > lineTotal(biggest)) {
                            biggest = line;
                        }
                    }
                    return biggest;
                }
                TS,
            'hint' => 'Declare `lines: OrderLine[]`. In `biggestLine()`, keep the best line so far in a variable of type `OrderLine | undefined`.',
            'checks' => [
                ['label' => 'Total and biggest line', 'code' => <<<'JS'
                    const order = { id: 1, lines: [{ title: 'Dune', pence: 999, qty: 1 }, { title: 'Pens', pence: 150, qty: 10 }, { title: 'Emma', pence: 799, qty: 1 }] };
                    return expectSame([orderTotal(order), biggestLine(order)?.title, biggestLine({ id: 2, lines: [] })], [3298, 'Pens', undefined]);
                    JS],
                ['label' => 'Orders are typed', 'compiles' => "const o: Order = { id: 1, lines: [{ title: 'A', pence: 1, qty: 1 }] };\nconst line: OrderLine | undefined = biggestLine(o);\nconst n: number = orderTotal(o);"],
                ['label' => 'Lines need a quantity', 'rejects' => "orderTotal({ id: 1, lines: [{ title: 'A', pence: 1 }] });"],
                ['label' => 'No any', 'code' => "return /\\bany\\b/.test(learnerCode()) ? 'Replace any with your interfaces.' : true;"],
            ],
        ],
        [
            'title' => 'Shapes that fit',
            'prompt' => 'Write an interface `HasArea` with a method `area(): number`, and `largest(shapes: HasArea[]): HasArea | undefined`, returning the shape with the biggest area. Any object with an `area()` method must fit, including the classes and objects in the checks, without them mentioning `HasArea`.',
            'starter_code' => <<<'TS'
                function largest(shapes) {
                    return shapes[0];
                }
                TS,
            'solution' => <<<'TS'
                interface HasArea {
                    area(): number;
                }

                function largest(shapes: HasArea[]): HasArea | undefined {
                    let best: HasArea | undefined;
                    for (const shape of shapes) {
                        if (!best || shape.area() > best.area()) {
                            best = shape;
                        }
                    }
                    return best;
                }
                TS,
            'hint' => 'An interface with just `area(): number` is enough: TypeScript checks shapes, not names.',
            'checks' => [
                ['label' => 'Finds the largest', 'code' => <<<'JS'
                    const square = { side: 3, area() { return this.side ** 2; } };
                    const circle = { r: 2, area() { return Math.PI * this.r ** 2; } };
                    return expectSame([largest([square, circle]) === circle, largest([])], [true, undefined]);
                    JS],
                ['label' => 'Any shape with area() fits', 'compiles' => "class Rect {\n    constructor(public w: number, public h: number) {}\n    area() {\n        return this.w * this.h;\n    }\n}\nconst big = largest([new Rect(2, 3), { area: () => 1 }]);"],
                ['label' => 'Things without area() do not', 'rejects' => 'largest([{ size: 4 }]);'],
            ],
        ],
    ],
];
