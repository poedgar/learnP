<?php

// Lesson 5 of the TypeScript course. The theory is in the .md file with the same name.
// Code with type errors doesn't run, so every check fails until the code type-checks. Checks are
// { label, code } (JavaScript run after the compiled code), { label, compiles } (TypeScript added to
// the end of the code that must type-check) or { label, rejects } (TypeScript that must be a type error).

return [
    'slug' => 'classes-and-utility-types',
    'title' => 'Classes and utility types',
    'summary' => 'Type classes with access modifiers, parameter properties, implements and abstract classes, and derive types with Partial, Pick, Omit, Readonly, Record, ReturnType and friends.',
    'starter_code' => <<<'TS'
        // Change this code, then press Run (Ctrl+Enter). Try reading wallet.coins from outside.
        class Wallet {
            private coins: number[] = [];

            constructor(public readonly owner: string) {}

            add(pence: number): this {
                this.coins.push(pence);
                return this;
            }

            get total(): number {
                return this.coins.reduce((a, b) => a + b, 0);
            }
        }

        const wallet = new Wallet('Ben').add(50).add(20);
        console.log(wallet.owner, wallet.total);
        TS,

    'quiz' => [
        [
            'question' => 'What does `constructor(private name: string) {}` do?',
            'options' => [
                'Nothing; it\'s a mistake',
                'Declares a private property `name` and assigns the argument to it',
                'Makes the constructor private',
                'Creates a local variable',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a parameter property, saving a declaration and an assignment.',
        ],
        [
            'question' => 'What is the difference between `private` and `protected`?',
            'options' => [
                'None',
                '`private` members are only usable inside the class; `protected` ones also in subclasses',
                '`protected` members are readonly',
                '`private` works at run time; `protected` doesn\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'Both are checked by TypeScript only. `#name` fields are private at run time too.',
        ],
        [
            'question' => 'What does `class Square implements Shape` make TypeScript check?',
            'options' => [
                'That Square extends a Shape class',
                'That Square has every property and method the Shape interface describes',
                'Nothing at all',
                'That Shape has no methods',
            ],
            'correct_option' => 1,
            'explanation' => 'Missing something the interface needs is a type error.',
        ],
        [
            'question' => 'What can\'t you do with an abstract class?',
            'options' => [
                'Extend it',
                'Create an instance of it directly with `new`',
                'Give it methods with bodies',
                'Give it a constructor',
            ],
            'correct_option' => 1,
            'explanation' => 'Subclasses supply the abstract parts, and those can be created.',
        ],
        [
            'question' => 'With `interface Book { id: number; title: string; year: number }`, what is `Pick<Book, \'title\'>`?',
            'options' => ['`{ title: string }`', '`{ id: number; year: number }`', '`string`', '`Book`'],
            'correct_option' => 0,
            'explanation' => '`Omit<Book, \'title\'>` would be the other two.',
        ],
        [
            'question' => 'What does `Partial<Book>` describe?',
            'options' => [
                'A Book with some properties removed',
                'An object with Book\'s properties, all optional',
                'Half of a Book',
                'A readonly Book',
            ],
            'correct_option' => 1,
            'explanation' => 'Useful for updates, where any subset of fields may change.',
        ],
        [
            'question' => 'What is `ReturnType<typeof createUser>` for?',
            'options' => [
                'Calling createUser',
                'Getting the type of what createUser returns, so it doesn\'t have to be written out separately',
                'Checking createUser returns something',
                'It\'s not valid TypeScript',
            ],
            'correct_option' => 1,
            'explanation' => 'If the function changes, the type follows automatically.',
        ],
        [
            'question' => "What is `Exclude<'a' | 'b' | 'c', 'b'>`?",
            'options' => ["`'a' | 'c'`", "`'b'`", "`'a' | 'b' | 'c'`", '`never`'],
            'correct_option' => 0,
            'explanation' => '`Extract` would keep only `\'b\'`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A typed class',
            'prompt' => "Write a class `Thermostat` with a `readonly room` (set by the constructor, as a parameter property) and a **private** `target` temperature starting at 20. `set(celsius: number)` changes the target, throwing a `RangeError` outside 5 to 30. A getter `display` returns e.g. `'Kitchen: 21°C'`.",
            'starter_code' => <<<'TS'
                class Thermostat {
                    target = 20;

                    constructor(room) {
                        this.room = room;
                    }
                }
                TS,
            'solution' => <<<'TS'
                class Thermostat {
                    private target = 20;

                    constructor(readonly room: string) {}

                    set(celsius: number): void {
                        if (celsius < 5 || celsius > 30) {
                            throw new RangeError(`Choose 5 to 30, not ${celsius}`);
                        }
                        this.target = celsius;
                    }

                    get display(): string {
                        return `${this.room}: ${this.target}°C`;
                    }
                }
                TS,
            'hint' => '`constructor(readonly room: string) {}` declares and sets `room`. Mark `target` as `private`.',
            'checks' => [
                ['label' => 'Works', 'code' => "const t = new Thermostat('Kitchen');\nconst before = t.display;\nt.set(21);\nconst result = await expectThrows(() => t.set(31), RangeError);\nreturn result === true ? expectSame([before, t.display], ['Kitchen: 20°C', 'Kitchen: 21°C']) : result;"],
                ['label' => 'The target is private', 'rejects' => "new Thermostat('Hall').target = 25;"],
                ['label' => 'The room is read-only', 'rejects' => "new Thermostat('Hall').room = 'Attic';"],
                ['label' => 'The room can be read', 'compiles' => "const r: string = new Thermostat('Hall').room;"],
            ],
        ],
        [
            'title' => 'Implement an interface',
            'prompt' => "Write classes `EmailSender` and `SmsSender` that both `implements Sender` (given), and `notifyAll(senders: Sender[], message: string): string[]`, collecting what each `send()` returns. `EmailSender` takes an address and returns `'email to ADDRESS: MESSAGE'`; `SmsSender` takes a number and returns `'sms to NUMBER: MESSAGE'`.",
            'starter_code' => <<<'TS'
                interface Sender {
                    readonly channel: string;
                    send(message: string): string;
                }

                class EmailSender {
                    constructor(private address: string) {}
                }
                TS,
            'solution' => <<<'TS'
                interface Sender {
                    readonly channel: string;
                    send(message: string): string;
                }

                class EmailSender implements Sender {
                    readonly channel = 'email';

                    constructor(private address: string) {}

                    send(message: string): string {
                        return `email to ${this.address}: ${message}`;
                    }
                }

                class SmsSender implements Sender {
                    readonly channel = 'sms';

                    constructor(private number: string) {}

                    send(message: string): string {
                        return `sms to ${this.number}: ${message}`;
                    }
                }

                function notifyAll(senders: Sender[], message: string): string[] {
                    return senders.map((sender) => sender.send(message));
                }
                TS,
            'hint' => 'Each class needs a `channel` and a `send()` method to keep the promise made by `implements Sender`.',
            'checks' => [
                ['label' => 'Sends through each', 'code' => "return expectSame(notifyAll([new EmailSender('a@x.com'), new SmsSender('07700 900123')], 'Hi'), ['email to a@x.com: Hi', 'sms to 07700 900123: Hi']);"],
                ['label' => 'Both implement Sender', 'code' => "const code = learnerCode();\nreturn /class\\s+EmailSender\\s+implements\\s+Sender/.test(code) && /class\\s+SmsSender\\s+implements\\s+Sender/.test(code) ? true : 'Declare both classes with implements Sender.';"],
                ['label' => 'They fit the interface', 'compiles' => "const senders: Sender[] = [new EmailSender('a'), new SmsSender('1')];\nconst c: string = senders[0].channel;"],
            ],
        ],
        [
            'title' => 'An abstract base',
            'prompt' => 'Write an abstract class `Discount` with an abstract method `amount(total: number): number` and a method `apply(total: number): number` returning the total minus the amount, never below 0. Then `PercentOff` (constructor takes a percent) and `FixedOff` (takes pence) extend it.',
            'starter_code' => <<<'TS'
                class Discount {
                    apply(total: number): number {
                        return total;
                    }
                }
                TS,
            'solution' => <<<'TS'
                abstract class Discount {
                    abstract amount(total: number): number;

                    apply(total: number): number {
                        return Math.max(0, total - this.amount(total));
                    }
                }

                class PercentOff extends Discount {
                    constructor(private percent: number) {
                        super();
                    }

                    amount(total: number): number {
                        return Math.round((total * this.percent) / 100);
                    }
                }

                class FixedOff extends Discount {
                    constructor(private pence: number) {
                        super();
                    }

                    amount(): number {
                        return this.pence;
                    }
                }
                TS,
            'hint' => 'Mark the class and the method `abstract`. Each subclass calls `super()` and writes its own `amount()`.',
            'checks' => [
                ['label' => 'Discounts', 'code' => 'return expectSame([new PercentOff(10).apply(2000), new FixedOff(500).apply(2000), new FixedOff(500).apply(300)], [1800, 1500, 0]);'],
                ['label' => "Discount itself can't be created", 'rejects' => 'new Discount();'],
                ['label' => 'Subclasses are Discounts', 'compiles' => 'const all: Discount[] = [new PercentOff(5), new FixedOff(1)];'],
            ],
        ],
        [
            'title' => 'Updates with Partial and Omit',
            'prompt' => "Write `type ProfileUpdate`, which allows any of `Profile`'s properties except `id` to be given (all optional), using utility types. Then `applyUpdate(profile: Profile, changes: ProfileUpdate): Profile`, returning a new profile with the changes applied.",
            'starter_code' => <<<'TS'
                interface Profile {
                    id: number;
                    name: string;
                    email: string;
                    bio: string;
                }

                type ProfileUpdate = Profile;

                function applyUpdate(profile: Profile, changes: ProfileUpdate): Profile {
                    return changes;
                }
                TS,
            'solution' => <<<'TS'
                interface Profile {
                    id: number;
                    name: string;
                    email: string;
                    bio: string;
                }

                type ProfileUpdate = Partial<Omit<Profile, 'id'>>;

                function applyUpdate(profile: Profile, changes: ProfileUpdate): Profile {
                    return { ...profile, ...changes };
                }
                TS,
            'hint' => '`Omit<Profile, \'id\'>` removes the id; wrapping it in `Partial<...>` makes the rest optional.',
            'checks' => [
                ['label' => 'Applies changes', 'code' => "const p = { id: 1, name: 'Amara', email: 'a@x.com', bio: '' };\nconst updated = applyUpdate(p, { bio: 'Reader' });\nreturn expectSame([updated, p.bio], [{ id: 1, name: 'Amara', email: 'a@x.com', bio: 'Reader' }, '']);"],
                ['label' => 'Any subset can change', 'compiles' => "const a: ProfileUpdate = {};\nconst b: ProfileUpdate = { name: 'B', email: 'b@x.com' };"],
                ['label' => "The id can't change", 'rejects' => 'const c: ProfileUpdate = { id: 2 };'],
                ['label' => 'Uses utility types', 'code' => "return /Partial\\s*</.test(learnerCode()) && /Omit\\s*</.test(learnerCode()) ? true : 'Build ProfileUpdate with Partial and Omit.';"],
            ],
        ],
        [
            'title' => 'Types that follow a function',
            'prompt' => 'Without writing the types out by hand, define `type Order` as whatever `createOrder()` returns, and `type OrderArgs` as its parameters, using `ReturnType` and `Parameters`. Then write `createOrders(list: OrderArgs[]): Order[]`.',
            'starter_code' => <<<'TS'
                function createOrder(customer: string, pence: number, express = false) {
                    return { customer, pence, express, reference: `${customer.slice(0, 3).toUpperCase()}-${pence}` };
                }

                type Order = { customer: string };
                type OrderArgs = string[];

                function createOrders(list: OrderArgs[]): Order[] {
                    return [];
                }
                TS,
            'solution' => <<<'TS'
                function createOrder(customer: string, pence: number, express = false) {
                    return { customer, pence, express, reference: `${customer.slice(0, 3).toUpperCase()}-${pence}` };
                }

                type Order = ReturnType<typeof createOrder>;
                type OrderArgs = Parameters<typeof createOrder>;

                function createOrders(list: OrderArgs[]): Order[] {
                    return list.map((args) => createOrder(...args));
                }
                TS,
            'hint' => '`ReturnType<typeof createOrder>` and `Parameters<typeof createOrder>`; spread each argument list into the call.',
            'checks' => [
                ['label' => 'Creates orders', 'code' => "return expectSame(createOrders([['Amara', 999], ['Ben', 450, true]]).map((o) => [o.reference, o.express]), [['AMA-999', false], ['BEN-450', true]]);"],
                ['label' => 'Order has every field', 'compiles' => "const o: Order = createOrder('Cy', 1);\nconst ref: string = o.reference;\nconst fast: boolean = o.express;"],
                ['label' => 'OrderArgs follows the parameters', 'rejects' => "const bad: OrderArgs = ['Cy', '100'];"],
            ],
        ],
        [
            'title' => 'A generic class',
            'prompt' => 'Write a generic class `TypedStack<T>` with `push(item: T): this` (for chaining), `pop(): T | undefined`, `peek(): T | undefined`, and a `size` getter. Its items must be private. A `TypedStack<number>` must reject strings.',
            'starter_code' => <<<'TS'
                class TypedStack {
                    items: any[] = [];

                    push(item: any) {
                        this.items.push(item);
                    }
                }
                TS,
            'solution' => <<<'TS'
                class TypedStack<T> {
                    private items: T[] = [];

                    push(item: T): this {
                        this.items.push(item);
                        return this;
                    }

                    pop(): T | undefined {
                        return this.items.pop();
                    }

                    peek(): T | undefined {
                        return this.items.at(-1);
                    }

                    get size(): number {
                        return this.items.length;
                    }
                }
                TS,
            'hint' => 'Add `<T>` to the class name, store `private items: T[]`, and return `this` from `push()`.',
            'checks' => [
                ['label' => 'A working stack', 'code' => "const s = new TypedStack().push(1).push(2).push(3);\nconst top = s.pop();\nreturn expectSame([top, s.peek(), s.size, new TypedStack().pop()], [3, 2, 2, undefined]);"],
                ['label' => 'Typed items', 'compiles' => "const s = new TypedStack<string>().push('a');\nconst top: string | undefined = s.pop();"],
                ['label' => 'Rejects the wrong type', 'rejects' => "new TypedStack<number>().push('one');"],
                ['label' => 'Items are private', 'rejects' => 'new TypedStack<number>().items;'],
            ],
        ],
    ],
];
