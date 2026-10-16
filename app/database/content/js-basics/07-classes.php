<?php

// Lesson 7 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'classes',
    'title' => 'Classes',
    'summary' => 'Describe kinds of objects with classes: constructors, methods and fields, private # fields, getters and setters, static members, inheritance with extends and super, and keeping this.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        class Lamp {
            #on = false;

            constructor(room) {
                this.room = room;
            }

            toggle() {
                this.#on = !this.#on;
                return this;
            }

            get status() {
                return `The ${this.room} lamp is ${this.#on ? 'on' : 'off'}`;
            }
        }

        const lamp = new Lamp('kitchen');
        console.log(lamp.status);
        console.log(lamp.toggle().status);
        JS,

    'quiz' => [
        [
            'question' => 'What does `new` do with a class?',
            'options' => [
                'Copies the class',
                'Creates an instance and runs the constructor to set it up',
                'Calls a static method',
                'Makes the class private',
            ],
            'correct_option' => 1,
            'explanation' => 'Each `new Book(...)` is a separate object with its own data and the class\'s shared methods.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                class Tally {
                    count = 0;
                    add(n) {
                        this.count += n;
                        return this;
                    }
                }
                const a = new Tally().add(2).add(3);
                const b = new Tally().add(10);
                console.log(a.count, b.count);
                JS,
            'options' => ['`5 10`', '`15 15`', '`5 15`', '`3 10`'],
            'correct_option' => 0,
            'explanation' => 'Each instance has its own `count`. Returning `this` lets `add()` calls chain.',
        ],
        [
            'question' => 'What is special about a field named `#secret`?',
            'options' => [
                'Nothing; `#` is just part of the name',
                'It\'s private: only code inside the class can read or change it',
                'It\'s static',
                'It can\'t be changed after the constructor',
            ],
            'correct_option' => 1,
            'explanation' => 'Code outside that tries `obj.#secret` doesn\'t even parse.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                class Circle {
                    constructor(r) {
                        this.r = r;
                    }
                    get diameter() {
                        return this.r * 2;
                    }
                    static unit() {
                        return new Circle(1);
                    }
                }
                console.log(new Circle(3).diameter, Circle.unit().diameter, typeof new Circle(1).unit);
                JS,
            'options' => ['`6 2 undefined`', '`6 2 function`', '`[Function] 2 undefined`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'A getter is read without brackets. Static methods belong to the class, not its instances.',
        ],
        [
            'question' => 'In a child class\'s constructor, what must happen before `this` is used?',
            'options' => ['Nothing', 'A call to `super(...)`', 'A call to `this.constructor()`', 'A `return` statement'],
            'correct_option' => 1,
            'explanation' => 'The parent\'s constructor sets up the object first.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                class Base {
                    name() {
                        return 'base';
                    }
                    hello() {
                        return `hello from ${this.name()}`;
                    }
                }
                class Child extends Base {
                    name() {
                        return 'child';
                    }
                }
                console.log(new Child().hello());
                JS,
            'options' => ['`hello from child`', '`hello from base`', 'An error', '`hello from undefined`'],
            'correct_option' => 0,
            'explanation' => '`this` is the `Child` instance, so `this.name()` runs the overriding method.',
        ],
        [
            'question' => 'Why does `const f = obj.method; f();` often fail inside a class?',
            'options' => [
                'Methods can\'t be stored in variables',
                'Called on its own, the method has no object, so `this` is `undefined`',
                'It creates a new instance',
                'It calls the parent class instead',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `() => obj.method()`, `obj.method.bind(obj)`, or an arrow-function field.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                class Fraction {
                    constructor(top, bottom) {
                        this.top = top;
                        this.bottom = bottom;
                    }
                    toString() {
                        return `${this.top}/${this.bottom}`;
                    }
                }
                console.log(`Half is ${new Fraction(1, 2)}`);
                JS,
            'options' => ['`Half is 1/2`', '`Half is [object Object]`', '`Half is Fraction { top: 1, bottom: 2 }`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'A template literal turns the object into text with its `toString()` method.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A counter class',
            'prompt' => 'Write a class `Counter` whose instances start at 0, with `increment()` (adds 1 and returns the counter, so calls can chain), `reset()`, and a getter `value`.',
            'starter_code' => <<<'JS'
                class Counter {
                    increment() {
                        this.count++;
                    }
                }
                JS,
            'solution' => <<<'JS'
                class Counter {
                    #count = 0;

                    increment() {
                        this.#count++;
                        return this;
                    }

                    reset() {
                        this.#count = 0;
                    }

                    get value() {
                        return this.#count;
                    }
                }
                JS,
            'hint' => 'Start the count in a field, return `this` from `increment()`, and read it with `get value()`.',
            'checks' => [
                ['label' => 'Counts and chains', 'code' => "const c = new Counter();\nc.increment().increment().increment();\nreturn expectSame(c.value, 3);"],
                ['label' => 'Resets', 'code' => "const c = new Counter().increment();\nc.reset();\nreturn expectSame([c.value, c.increment().value], [0, 1]);"],
                ['label' => 'Counters are separate', 'code' => "const a = new Counter().increment().increment();\nconst b = new Counter();\nreturn expectSame([a.value, b.value], [2, 0]);"],
            ],
        ],
        [
            'title' => 'A bank account',
            'prompt' => "Write a class `BankAccount` with a constructor taking an opening balance (default 0), `deposit(pence)` and `withdraw(pence)`, and a getter `balance`. Keep the balance in a private field. Amounts of 0 or less throw a `RangeError`; withdrawing more than the balance throws an `Error` with the message `'Insufficient funds'`.",
            'starter_code' => <<<'JS'
                class BankAccount {
                    constructor(balance) {
                        this.balance = balance;
                    }

                    withdraw(pence) {
                        this.balance -= pence;
                    }
                }
                JS,
            'solution' => <<<'JS'
                class BankAccount {
                    #balance;

                    constructor(opening = 0) {
                        this.#balance = opening;
                    }

                    get balance() {
                        return this.#balance;
                    }

                    deposit(pence) {
                        this.#check(pence);
                        this.#balance += pence;
                    }

                    withdraw(pence) {
                        this.#check(pence);
                        if (pence > this.#balance) {
                            throw new Error('Insufficient funds');
                        }
                        this.#balance -= pence;
                    }

                    #check(pence) {
                        if (pence <= 0) {
                            throw new RangeError('Amounts must be positive');
                        }
                    }
                }
                JS,
            'hint' => 'Store the balance in `#balance`, expose it with `get balance()`, and check amounts before changing anything.',
            'checks' => [
                ['label' => 'Deposits and withdrawals', 'code' => "const account = new BankAccount(1000);\naccount.deposit(500);\naccount.withdraw(200);\nreturn expectSame([account.balance, new BankAccount().balance], [1300, 0]);"],
                ['label' => 'Bad amounts are refused', 'code' => <<<'JS'
                    const account = new BankAccount(100);
                    const results = [
                        await expectThrows(() => account.deposit(0), RangeError),
                        await expectThrows(() => account.withdraw(-5), RangeError),
                        await expectThrows(() => account.withdraw(101), Error, 'Insufficient funds'),
                    ];
                    const failed = results.find((r) => r !== true);
                    return failed ?? expectSame(account.balance, 100);
                    JS],
                ['label' => "The balance can't be set from outside", 'code' => "const account = new BankAccount(100);\ntry {\n    account.balance = 1000000;\n} catch {}\nreturn expectSame(account.balance, 100);"],
            ],
        ],
        [
            'title' => 'Temperatures',
            'prompt' => 'Write a class `Temperature` with a constructor taking degrees Celsius (stored in a `celsius` property), a getter `fahrenheit`, and a static method `fromFahrenheit(f)` that creates a `Temperature`. Fahrenheit is Celsius × 1.8 + 32.',
            'starter_code' => <<<'JS'
                class Temperature {
                    constructor(celsius) {
                        this.celsius = celsius;
                    }
                }
                JS,
            'solution' => <<<'JS'
                class Temperature {
                    constructor(celsius) {
                        this.celsius = celsius;
                    }

                    get fahrenheit() {
                        return this.celsius * 1.8 + 32;
                    }

                    static fromFahrenheit(f) {
                        return new Temperature((f - 32) / 1.8);
                    }
                }
                JS,
            'hint' => 'The getter uses `this.celsius`. The static method reverses the formula and returns `new Temperature(...)`.',
            'checks' => [
                ['label' => 'fahrenheit', 'code' => 'return expectSame([new Temperature(100).fahrenheit, new Temperature(0).fahrenheit, new Temperature(-40).fahrenheit], [212, 32, -40]);'],
                ['label' => 'fromFahrenheit()', 'code' => "const boiling = Temperature.fromFahrenheit(212);\nreturn expectSame([boiling instanceof Temperature, boiling.celsius, Temperature.fromFahrenheit(32).celsius], [true, 100, 0]);"],
            ],
        ],
        [
            'title' => 'Managers are employees',
            'prompt' => "Write `Employee` (constructor `(name, salary)`, `describe()` returning `'NAME earns £SALARY'`) and `Manager extends Employee` (constructor `(name, salary, reports)`, where `reports` is a number), whose `describe()` adds `' and manages N people'` to the employee description, using `super`.",
            'starter_code' => <<<'JS'
                class Employee {
                    constructor(name, salary) {
                        this.name = name;
                        this.salary = salary;
                    }
                }

                class Manager {
                }
                JS,
            'solution' => <<<'JS'
                class Employee {
                    constructor(name, salary) {
                        this.name = name;
                        this.salary = salary;
                    }

                    describe() {
                        return `${this.name} earns £${this.salary}`;
                    }
                }

                class Manager extends Employee {
                    constructor(name, salary, reports) {
                        super(name, salary);
                        this.reports = reports;
                    }

                    describe() {
                        return `${super.describe()} and manages ${this.reports} people`;
                    }
                }
                JS,
            'hint' => 'Call `super(name, salary)` first in the manager\'s constructor, and `super.describe()` inside its `describe()`.',
            'checks' => [
                ['label' => 'Employees', 'code' => "return expectSame(new Employee('Ben', 31000).describe(), 'Ben earns £31000');"],
                ['label' => 'Managers', 'code' => "const amara = new Manager('Amara', 52000, 4);\nreturn expectSame([amara.describe(), amara instanceof Employee, amara.name], ['Amara earns £52000 and manages 4 people', true, 'Amara']);"],
                ['label' => 'Uses super', 'code' => "return /super\\.describe\\(\\)/.test(learnerCode()) ? true : 'Build on the employee description with super.describe().';"],
            ],
        ],
        [
            'title' => 'Keep this',
            'prompt' => '`[1, 2, 3].forEach(timer.tick)` fails, because `tick` loses its `this`. Change the `Timer` class, not the call, so that passing `timer.tick` around works.',
            'starter_code' => <<<'JS'
                class Timer {
                    ticks = 0;

                    tick() {
                        this.ticks++;
                    }
                }

                const timer = new Timer();
                [1, 2, 3].forEach(timer.tick);
                JS,
            'solution' => <<<'JS'
                class Timer {
                    ticks = 0;

                    tick = () => {
                        this.ticks++;
                    };
                }

                const timer = new Timer();
                [1, 2, 3].forEach(timer.tick);
                JS,
            'hint' => 'Make `tick` an arrow-function field: `tick = () => { ... };`. It keeps the `this` of its instance.',
            'checks' => [
                ['label' => 'The timer ticked three times', 'code' => 'return expectSame(timer.ticks, 3);'],
                ['label' => 'Works for other timers too', 'code' => "const other = new Timer();\nconst tick = other.tick;\ntick();\nsetTimeout(other.tick, 0);\nawait new Promise((resolve) => setTimeout(resolve, 20));\nreturn expectSame([other.ticks, timer.ticks], [2, 3]);"],
            ],
        ],
        [
            'title' => 'A stack',
            'prompt' => 'Write a class `Stack` with `push(item)` (returns the stack, so calls chain), `pop()` and `peek()` (both `undefined` when empty), a getter `size`, `toArray()` (a copy, bottom first) and a static `from(items)` that pushes each item in order.',
            'starter_code' => <<<'JS'
                class Stack {
                    items = [];

                    push(item) {
                        this.items.unshift(item);
                    }
                }
                JS,
            'solution' => <<<'JS'
                class Stack {
                    #items = [];

                    static from(items) {
                        const stack = new Stack();
                        for (const item of items) {
                            stack.push(item);
                        }
                        return stack;
                    }

                    push(item) {
                        this.#items.push(item);
                        return this;
                    }

                    pop() {
                        return this.#items.pop();
                    }

                    peek() {
                        return this.#items.at(-1);
                    }

                    get size() {
                        return this.#items.length;
                    }

                    toArray() {
                        return [...this.#items];
                    }
                }
                JS,
            'hint' => 'Keep the items in a private array and use its `push()`, `pop()` and `at(-1)`. Copy it in `toArray()` with `[...]`.',
            'checks' => [
                ['label' => 'Push, peek and pop', 'code' => "const s = new Stack().push('a').push('b').push('c');\nconst popped = s.pop();\nreturn expectSame([popped, s.peek(), s.size], ['c', 'b', 2]);"],
                ['label' => 'Empty stacks', 'code' => "const s = new Stack();\nreturn expectSame([s.pop(), s.peek(), s.size], [undefined, undefined, 0]);"],
                ['label' => 'from() and toArray()', 'code' => "const s = Stack.from([1, 2, 3]);\nconst items = s.toArray();\nitems.push(99);\nreturn expectSame([s.toArray(), s.peek(), s instanceof Stack], [[1, 2, 3], 3, true]);"],
            ],
        ],
    ],
];
