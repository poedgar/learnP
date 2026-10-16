## Typed classes

Classes in TypeScript declare their properties with types. **Access modifiers** say who may use each member:
`public` (anyone, the default), `private` (only this class) and `protected` (this class and its subclasses).
`readonly` stops reassignment after the constructor:

```typescript
class Account {
    readonly id: number;
    private balance = 0;

    constructor(id: number, public owner: string) {
        this.id = id;
    }

    deposit(pence: number): void {
        this.balance += pence;
    }

    get pounds(): string {
        return (this.balance / 100).toFixed(2);
    }
}

const account = new Account(1, 'Amara');
account.deposit(2500);
console.log(account.owner, account.pounds);
```

`public owner: string` in the constructor's parameters is a **parameter property**: it declares the property and
assigns the argument to it in one go.

```typescript
// Error: balance is private, and id is readonly.
class Account {
    readonly id = 1;
    private balance = 0;
}

const account = new Account();
account.balance = 1000000;
account.id = 2;
```

`private` is checked by TypeScript only; JavaScript's own `#private` fields are private at run time too. Either works;
`#` is stricter.

## Implementing interfaces

`implements` promises that a class has everything an interface describes. TypeScript checks the promise:

```typescript
interface Shape {
    area(): number;
    readonly name: string;
}

class Square implements Shape {
    readonly name = 'square';

    constructor(private side: number) {}

    area(): number {
        return this.side ** 2;
    }
}

const shapes: Shape[] = [new Square(3), { name: 'unit', area: () => 1 }];
console.log(shapes.map((s) => `${s.name}: ${s.area()}`));
```

## Abstract classes

An **abstract** class can't be created directly. It provides shared code, and declares `abstract` methods that each
subclass must write:

```typescript
abstract class Report {
    constructor(protected title: string) {}

    abstract body(): string;

    render(): string {
        return `== ${this.title} ==\n${this.body()}`;
    }
}

class SalesReport extends Report {
    constructor(private total: number) {
        super('Sales');
    }

    body(): string {
        return `Total: £${this.total}`;
    }
}

console.log(new SalesReport(1234).render());
```

## Utility types

TypeScript comes with generic **utility types** that build new types from existing ones, so related types stay in
step:

```typescript
interface Book {
    id: number;
    title: string;
    author: string;
    year: number;
}

type BookUpdate = Partial<Omit<Book, 'id'>>;
type BookSummary = Pick<Book, 'title' | 'author'>;
type FrozenBook = Readonly<Book>;

function update(book: Book, changes: BookUpdate): Book {
    return { ...book, ...changes };
}

const dune: Book = { id: 1, title: 'Dune', author: 'Frank Herbert', year: 1965 };
const summary: BookSummary = { title: dune.title, author: dune.author };

console.log(update(dune, { year: 2021 }), summary);
```

- `Partial<T>`: every property optional. `Required<T>`: every property required.
- `Readonly<T>`: every property readonly.
- `Pick<T, Keys>`: only those properties. `Omit<T, Keys>`: all but those.
- `Record<Keys, Value>`: an object with those keys.

## Types from functions

`ReturnType<F>` and `Parameters<F>` read a function type's result and parameter list, so a type can follow a
function without being written out twice. `Awaited<T>` unwraps a promise:

```typescript
function createUser(name: string, age: number) {
    return { name, age, createdAt: new Date(Date.UTC(2026, 0, 1)) };
}

type User = ReturnType<typeof createUser>;
type UserArgs = Parameters<typeof createUser>;

const args: UserArgs = ['Ben', 31];
const ben: User = createUser(...args);

async function loadCount(): Promise<number> {
    return 42;
}
type Count = Awaited<ReturnType<typeof loadCount>>;
const count: Count = await loadCount();

console.log(ben.name, ben.createdAt.toISOString().slice(0, 4), count);
```

`typeof createUser`, in a type position, means "the type of the value `createUser`".

## Excluding and extracting

`Exclude<Union, Members>` removes members from a union, and `Extract<Union, Members>` keeps only those. `NonNullable<T>`
removes `null` and `undefined`:

```typescript
type Status = 'draft' | 'review' | 'published' | 'archived';
type Live = Extract<Status, 'published'>;
type NotLive = Exclude<Status, 'published'>;
type MaybeName = string | null | undefined;

const s: NotLive = 'review';
const name: NonNullable<MaybeName> = 'Amara';

console.log(s, name);
```

## Classes with generics

A class can take type parameters too, like the built-in `Map<K, V>`:

```typescript
class Queue<T> {
    private items: T[] = [];

    enqueue(item: T): this {
        this.items.push(item);
        return this;
    }

    dequeue(): T | undefined {
        return this.items.shift();
    }

    get length(): number {
        return this.items.length;
    }
}

const jobs = new Queue<string>().enqueue('print').enqueue('email');
console.log(jobs.dequeue(), jobs.length);
```

Returning `this` keeps chaining typed even in subclasses.

## Putting it together

A repository class that stores any kind of record with an id, using utility types for creating and updating:

```typescript
interface Entity {
    id: number;
}

class Repository<T extends Entity> {
    private records = new Map<number, T>();
    private nextId = 1;

    add(data: Omit<T, 'id'>): T {
        const record = { ...data, id: this.nextId++ } as T;
        this.records.set(record.id, record);
        return record;
    }

    update(id: number, changes: Partial<Omit<T, 'id'>>): T | undefined {
        const existing = this.records.get(id);
        if (!existing) return undefined;
        const updated = { ...existing, ...changes };
        this.records.set(id, updated);
        return updated;
    }

    all(): readonly T[] {
        return [...this.records.values()];
    }
}

interface Member extends Entity {
    name: string;
    email: string;
}

const members = new Repository<Member>();
members.add({ name: 'Amara', email: 'amara@example.com' });
members.add({ name: 'Ben', email: 'ben@example.com' });
members.update(2, { email: 'ben@example.org' });

console.log(members.all());
```

Next, try the quiz to check your understanding, then solve the challenges.
