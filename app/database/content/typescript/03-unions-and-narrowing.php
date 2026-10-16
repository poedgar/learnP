<?php

// Lesson 3 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'unions-and-narrowing',
    'title' => 'Unions and narrowing',
    'summary' => 'Model values that can be one of several types: unions, narrowing with typeof, in and instanceof, literal unions, discriminated unions, exhaustiveness with never, and type predicates.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Try calling toUpperCase() before the typeof check.
        function shout(value: string | number | null): string {
            if (value === null) {
                return 'NOTHING';
            }
            if (typeof value === 'number') {
                return `${value}!`;
            }
            return value.toUpperCase() + '!';
        }

        console.log(shout('hello'), shout(42), shout(null));
        TS,

    'quiz' => [
        [
            'question' => 'With `let id: number | string`, why is `id.toUpperCase()` an error?',
            'options' => [
                'Strings don\'t have toUpperCase()',
                'Only what every member of the union supports can be used, and numbers don\'t have it; narrow first',
                'Unions can\'t have methods',
                'It isn\'t an error',
            ],
            'correct_option' => 1,
            'explanation' => 'Inside `if (typeof id === \'string\')`, the call is fine.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                function size(value: string | string[]): number {
                    return Array.isArray(value) ? value.length * 10 : value.length;
                }
                console.log(size('abc'), size(['a', 'b']));
                TS,
            'options' => ['`3 20`', '`30 20`', '`3 2`', 'A type error'],
            'correct_option' => 0,
            'explanation' => '`Array.isArray()` narrows: the array branch multiplies by 10.',
        ],
        [
            'question' => 'What makes a union "discriminated"?',
            'options' => [
                'It has more than two members',
                'Every member is an object with a shared literal property, such as `kind`, whose value says which member it is',
                'It contains `never`',
                'It is declared with `interface`',
            ],
            'correct_option' => 1,
            'explanation' => 'Checking that property narrows to the matching member.',
        ],
        [
            'question' => 'Why assign the value to a `never` variable in a `switch`\'s `default`?',
            'options' => [
                'To make it run faster',
                'So that adding a new member to the union later is a type error there, until the new case is handled',
                'To throw an error at run time',
                'It is required by `switch`',
            ],
            'correct_option' => 1,
            'explanation' => 'If every case is handled, only `never` reaches the default, so the assignment type-checks.',
        ],
        [
            'question' => 'What does `function isFilm(item: Item): item is Film` tell TypeScript?',
            'options' => [
                'That `isFilm` returns a Film',
                'That when it returns true, `item` is a Film, so code (and `filter()`) can narrow with it',
                'That `item` must be a Film',
                'Nothing; it\'s a comment',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a type predicate: a check of your own that narrows.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'TS'
                type Pet = { kind: 'dog'; barks: boolean } | { kind: 'fish'; fins: number };
                const pets: Pet[] = [{ kind: 'dog', barks: true }, { kind: 'fish', fins: 4 }];
                for (const pet of pets) {
                    console.log(pet.kind === 'dog' ? `dog ${pet.barks}` : `fish ${pet.fins}`);
                }
                TS,
            'options' => ['`dog true fish 4`', 'A type error', '`dog undefined fish 4`', '`dog true fish undefined`'],
            'correct_option' => 0,
            'explanation' => 'Checking `kind` narrows each pet, so each branch can read its own properties.',
        ],
        [
            'question' => 'What does the `in` operator narrow by?',
            'options' => [
                'Whether a value is in an array',
                'Whether an object has a property: `if (\'email\' in contact)`',
                'Whether a number is in range',
                'It doesn\'t narrow',
            ],
            'correct_option' => 1,
            'explanation' => 'Members of a union without that property are ruled out.',
        ],
        [
            'question' => 'Why use a result type like `{ ok: true; value: T } | { ok: false; error: string }` instead of throwing?',
            'options' => [
                'Exceptions don\'t exist in TypeScript',
                'The possible failure is part of the type, so callers must check `ok` before they can reach `value`',
                'It is faster',
                'It is required for async code',
            ],
            'correct_option' => 1,
            'explanation' => 'A thrown exception isn\'t visible in a function\'s type.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Narrow with typeof',
            'prompt' => "Write `normalise(value: string | number | boolean): string`: numbers become their value with 2 decimal places, booleans become `'yes'` or `'no'`, and strings are trimmed.",
            'starter_code' => <<<'TS'
                function normalise(value: string | number | boolean): string {
                    return value.trim();
                }
                TS,
            'solution' => <<<'TS'
                function normalise(value: string | number | boolean): string {
                    if (typeof value === 'number') {
                        return value.toFixed(2);
                    }
                    if (typeof value === 'boolean') {
                        return value ? 'yes' : 'no';
                    }
                    return value.trim();
                }
                TS,
            'hint' => 'Handle `typeof value === \'number\'` and `\'boolean\'` first; what\'s left must be a string.',
            'checks' => [
                ['label' => 'Each type', 'code' => "return expectSame([normalise(3), normalise(2.5), normalise(true), normalise(false), normalise('  hi  ')], ['3.00', '2.50', 'yes', 'no', 'hi']);"],
                ['label' => 'Only those types', 'rejects' => 'normalise(null);'],
            ],
        ],
        [
            'title' => 'A discriminated union',
            'prompt' => "Define `type Notification` as a union of `{ kind: 'email'; address: string }`, `{ kind: 'sms'; phone: string }` and `{ kind: 'push'; deviceId: string }`. Write `destination(n: Notification): string`, returning e.g. `'email to amara@example.com'`, `'sms to 07700 900123'` or `'push to device abc'`.",
            'starter_code' => <<<'TS'
                type Notification = { kind: string; to: string };

                function destination(n: Notification): string {
                    return `${n.kind} to ${n.to}`;
                }
                TS,
            'solution' => <<<'TS'
                type Notification =
                    | { kind: 'email'; address: string }
                    | { kind: 'sms'; phone: string }
                    | { kind: 'push'; deviceId: string };

                function destination(n: Notification): string {
                    switch (n.kind) {
                        case 'email':
                            return `email to ${n.address}`;
                        case 'sms':
                            return `sms to ${n.phone}`;
                        case 'push':
                            return `push to device ${n.deviceId}`;
                    }
                }
                TS,
            'hint' => 'Write the three object types joined with `|`, then `switch (n.kind)`: each case can read its own property.',
            'checks' => [
                ['label' => 'Each kind', 'code' => "return expectSame([destination({ kind: 'email', address: 'amara@example.com' }), destination({ kind: 'sms', phone: '07700 900123' }), destination({ kind: 'push', deviceId: 'abc' })], ['email to amara@example.com', 'sms to 07700 900123', 'push to device abc']);"],
                ['label' => 'Each kind has its own data', 'rejects' => "destination({ kind: 'email', phone: '0123' });"],
                ['label' => 'No other kinds', 'rejects' => "destination({ kind: 'fax', address: 'x' });"],
            ],
        ],
        [
            'title' => 'Handle every case',
            'prompt' => "Write `price(ticket: Ticket): number` for the given `Ticket` union (adult 1200, child 600, senior 900, and `group` 1000 per person), with a `never` check in the `default` branch, so that adding a new kind of ticket makes `price()` a type error until it's handled.",
            'starter_code' => <<<'TS'
                type Ticket =
                    | { kind: 'adult' }
                    | { kind: 'child'; age: number }
                    | { kind: 'senior' }
                    | { kind: 'group'; people: number };

                function price(ticket: Ticket): number {
                    if (ticket.kind === 'child') return 600;
                    return 1200;
                }
                TS,
            'solution' => <<<'TS'
                type Ticket =
                    | { kind: 'adult' }
                    | { kind: 'child'; age: number }
                    | { kind: 'senior' }
                    | { kind: 'group'; people: number };

                function price(ticket: Ticket): number {
                    switch (ticket.kind) {
                        case 'adult':
                            return 1200;
                        case 'child':
                            return 600;
                        case 'senior':
                            return 900;
                        case 'group':
                            return 1000 * ticket.people;
                        default: {
                            const unhandled: never = ticket;
                            return unhandled;
                        }
                    }
                }
                TS,
            'hint' => 'A `switch` on `ticket.kind` with a case for each, and `default: { const unhandled: never = ticket; return unhandled; }`.',
            'checks' => [
                ['label' => 'Prices', 'code' => "return expectSame([price({ kind: 'adult' }), price({ kind: 'child', age: 8 }), price({ kind: 'senior' }), price({ kind: 'group', people: 6 })], [1200, 600, 900, 6000]);"],
                ['label' => 'Has a never check', 'code' => "return /:\\s*never\\b/.test(learnerCode()) ? true : 'Add a never check in the default branch.';"],
            ],
        ],
        [
            'title' => 'A type predicate',
            'prompt' => 'Write `isEmail(contact: Contact): contact is EmailContact` for the given types, then `emailAddresses(contacts: Contact[]): string[]`, which uses it with `filter()` to return the email addresses, in order.',
            'starter_code' => <<<'TS'
                interface EmailContact {
                    name: string;
                    email: string;
                }

                interface PhoneContact {
                    name: string;
                    phone: string;
                }

                type Contact = EmailContact | PhoneContact;

                function isEmail(contact: Contact): boolean {
                    return 'email' in contact;
                }

                function emailAddresses(contacts: Contact[]): string[] {
                    return [];
                }
                TS,
            'solution' => <<<'TS'
                interface EmailContact {
                    name: string;
                    email: string;
                }

                interface PhoneContact {
                    name: string;
                    phone: string;
                }

                type Contact = EmailContact | PhoneContact;

                function isEmail(contact: Contact): contact is EmailContact {
                    return 'email' in contact;
                }

                function emailAddresses(contacts: Contact[]): string[] {
                    return contacts.filter(isEmail).map((contact) => contact.email);
                }
                TS,
            'hint' => 'Change the return type to `contact is EmailContact`; then `contacts.filter(isEmail)` is an `EmailContact[]`.',
            'checks' => [
                ['label' => 'The addresses', 'code' => "return expectSame(emailAddresses([{ name: 'A', email: 'a@x.com' }, { name: 'B', phone: '1' }, { name: 'C', email: 'c@x.com' }]), ['a@x.com', 'c@x.com']);"],
                ['label' => 'isEmail narrows', 'compiles' => "const c: Contact = { name: 'A', email: 'a@x.com' };\nif (isEmail(c)) {\n    const e: string = c.email;\n}\nconst onlyEmails: EmailContact[] = [c].filter(isEmail);"],
            ],
        ],
        [
            'title' => 'Return a result',
            'prompt' => "Write `type Result<T> = { ok: true; value: T } | { ok: false; error: string }` and `parseAge(text: string): Result<number>`, which succeeds for whole numbers from 0 to 150 and otherwise returns an error message. Then `describeAge(text: string): string` returns `'Age N'` or `'Problem: MESSAGE'`.",
            'starter_code' => <<<'TS'
                function parseAge(text: string): number {
                    return Number(text);
                }

                function describeAge(text: string): string {
                    return `Age ${parseAge(text)}`;
                }
                TS,
            'solution' => <<<'TS'
                type Result<T> = { ok: true; value: T } | { ok: false; error: string };

                function parseAge(text: string): Result<number> {
                    const age = Number(text);
                    if (text.trim() === '' || !Number.isInteger(age) || age < 0 || age > 150) {
                        return { ok: false, error: `Not an age: ${text}` };
                    }
                    return { ok: true, value: age };
                }

                function describeAge(text: string): string {
                    const result = parseAge(text);
                    return result.ok ? `Age ${result.value}` : `Problem: ${result.error}`;
                }
                TS,
            'hint' => 'Return `{ ok: true, value: age }` or `{ ok: false, error: ... }`. Checking `result.ok` narrows it.',
            'checks' => [
                ['label' => 'Results', 'code' => "return expectSame([parseAge('34'), parseAge('abc').ok, parseAge('151').ok, parseAge('').ok], [{ ok: true, value: 34 }, false, false, false]);"],
                ['label' => 'Descriptions', 'code' => "const bad = describeAge('x');\nreturn expectSame([describeAge('7'), bad.startsWith('Problem: ')], ['Age 7', true]);"],
                ['label' => 'The value needs a check first', 'rejects' => "const n: number = parseAge('3').value;"],
                ['label' => 'After checking, the value is a number', 'compiles' => "const r = parseAge('3');\nif (r.ok) {\n    const n: number = r.value;\n}"],
            ],
        ],
        [
            'title' => 'Narrow with in and instanceof',
            'prompt' => "Write `when(value: Date | number | { from: Date; to: Date }): string`. A `Date` gives its year (e.g. `'2026'`); a number is a year, also given back as text; a range gives `'FROM to TO'` using the years.",
            'starter_code' => <<<'TS'
                function when(value: Date | number | { from: Date; to: Date }): string {
                    return String(value);
                }
                TS,
            'solution' => <<<'TS'
                function when(value: Date | number | { from: Date; to: Date }): string {
                    if (value instanceof Date) {
                        return String(value.getUTCFullYear());
                    }
                    if (typeof value === 'number') {
                        return String(value);
                    }
                    return `${value.from.getUTCFullYear()} to ${value.to.getUTCFullYear()}`;
                }
                TS,
            'hint' => '`value instanceof Date` narrows to a Date; `typeof value === \'number\'` to a number; what remains is the range.',
            'checks' => [
                ['label' => 'Each kind', 'code' => <<<'JS'
                    const d = (y) => new Date(Date.UTC(y, 5, 1));
                    return expectSame([when(d(2026)), when(1999), when({ from: d(1914), to: d(1918) })], ['2026', '1999', '1914 to 1918']);
                    JS],
                ['label' => 'A range needs both ends', 'rejects' => 'when({ from: new Date() });'],
            ],
        ],
    ],
];
