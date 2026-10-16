## Function types

A **function type** describes a function's parameters and result: `(a: number, b: number) => number`. Use it for
callbacks and for variables that hold functions:

```typescript
type Operation = (a: number, b: number) => number;

const add: Operation = (a, b) => a + b;
const multiply: Operation = (a, b) => a * b;

function applyAll(ops: Operation[], a: number, b: number): number[] {
    return ops.map((op) => op(a, b));
}

console.log(applyAll([add, multiply], 3, 4));
```

When a function is assigned to a typed variable, its parameters don't need annotating: TypeScript knows them from
the type. That's called **contextual typing**, and it's why `(a, b) => a + b` needs no types above.

## Overloads

Sometimes a function's result type depends on its arguments. **Overloads** list each form above the one real
implementation, so callers get the precise type:

```typescript
function parse(value: string): number;
function parse(value: string[]): number[];
function parse(value: string | string[]): number | number[] {
    return Array.isArray(value) ? value.map(Number) : Number(value);
}

const one: number = parse('42');
const many: number[] = parse(['1', '2']);
console.log(one, many);
```

Overloads are useful but easy to overdo; a union parameter, or generics, often does the job.

## Generics

A function like "return the first item of an array" works for any type of array, but should give back the **same**
type it got. A **generic** function has a type parameter, written `<T>`, that stands for whatever type it's called
with:

```typescript
function first<T>(items: T[]): T | undefined {
    return items[0];
}

const word = first(['dune', 'emma']);
const number = first([3, 1, 2]);

console.log(word?.toUpperCase(), number?.toFixed(1));
```

TypeScript infers `T` from the argument: `string` in the first call, `number` in the second. Without generics, the
result would have to be `any` (no checking) or `unknown` (no use).

## Several type parameters

Type parameters work like ordinary parameters, and there can be several. A generic `map` takes items of one type and
a function turning them into another:

```typescript
function mapItems<In, Out>(items: In[], transform: (item: In) => Out): Out[] {
    const result: Out[] = [];
    for (const item of items) {
        result.push(transform(item));
    }
    return result;
}

const lengths = mapItems(['dune', 'emma', 'circe'], (word) => word.length);
console.log(lengths, lengths.reduce((a, b) => a + b, 0));
```

## Constraints

Sometimes the type parameter must have certain features. `T extends Something` **constrains** it: any type that fits
`Something` is allowed, and inside the function, those features can be used:

```typescript
function longest<T extends { length: number }>(items: T[]): T | undefined {
    let best: T | undefined;
    for (const item of items) {
        if (!best || item.length > best.length) {
            best = item;
        }
    }
    return best;
}

console.log(longest(['a', 'abc', 'ab']), longest([[1], [1, 2, 3]]));
```

```typescript
// Error: numbers don't have a length, so they don't fit the constraint.
function longest<T extends { length: number }>(items: T[]): T | undefined {
    return items[0];
}

longest([1, 2, 3]);
```

## `keyof` and property access

`keyof T` is the union of a type's property names. Combined with a constraint `K extends keyof T`, it lets a function
safely take a property name, and `T[K]` is the type of that property:

```typescript
function pluck<T, K extends keyof T>(items: T[], key: K): T[K][] {
    return items.map((item) => item[key]);
}

const books = [
    { title: 'Dune', year: 1965 },
    { title: 'Emma', year: 1815 },
];

const years = pluck(books, 'year');
console.log(years, years[0] + 1);
```

```typescript
// Error: 'author' isn't a property of these books.
function pluck<T, K extends keyof T>(items: T[], key: K): T[K][] {
    return items.map((item) => item[key]);
}

pluck([{ title: 'Dune', year: 1965 }], 'author');
```

## Generic types

Types and interfaces can be generic too, describing containers that hold any type:

```typescript
interface Box<T> {
    value: T;
    label: string;
}

type Pair<A, B> = [A, B];

const priced: Box<number> = { value: 999, label: 'Dune' };
const named: Pair<string, number> = ['Amara', 34];

function wrap<T>(value: T, label: string): Box<T> {
    return { value, label };
}

console.log(priced.value, named[0], wrap(true, 'flag'));
```

Built-in types are generic in the same way: `Array<T>`, `Promise<T>`, `Map<K, V>`, `Set<T>`, `Record<K, V>`.

## Typing async functions

An `async` function returns a `Promise` of its result type, written `Promise<T>`:

```typescript
interface User {
    id: number;
    name: string;
}

async function loadUser(id: number): Promise<User> {
    await new Promise((resolve) => setTimeout(resolve, 10));
    return { id, name: id === 1 ? 'Amara' : 'Ben' };
}

const user = await loadUser(1);
console.log(user.name);
```

## Putting it together

A small generic cache that works for any kind of value, with its key type and loader typed:

```typescript
class Cache<K, V> {
    private store = new Map<K, V>();

    constructor(private load: (key: K) => V) {}

    get(key: K): V {
        if (!this.store.has(key)) {
            this.store.set(key, this.load(key));
        }
        return this.store.get(key) as V;
    }

    get size(): number {
        return this.store.size;
    }
}

const squares = new Cache((n: number) => n * n);
const shouts = new Cache((word: string) => `${word.toUpperCase()}!`);

console.log(squares.get(12), squares.get(12), squares.size, shouts.get('hi'));
```

`as V` is a **type assertion**: telling TypeScript a value's type when you know better than it can work out. Here
`get()` could return `undefined`, but the line before makes sure it won't. Use assertions sparingly: they're
unchecked.

Next, try the quiz to check your understanding, then solve the challenges.
