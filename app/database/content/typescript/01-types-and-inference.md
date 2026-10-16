## JavaScript with types

**TypeScript** is JavaScript plus **types**: notes saying what kind of value each variable, parameter and return value
holds. A compiler checks those notes before the code runs, and reports mistakes such as passing a string where a
number is needed. Then the types are removed, leaving ordinary JavaScript.

In this course, TypeScript checks your code first. Code with **type errors doesn't run**: fix them, then it will.

```typescript
function area(width: number, height: number): number {
    return width * height;
}

console.log(area(3, 4));
```

```typescript
// Error: a string isn't a number, so TypeScript stops this before it runs.
function area(width: number, height: number): number {
    return width * height;
}

console.log(area('3', 4));
```

In plain JavaScript, `area('3', 4)` would quietly give `12`, and `area('three', 4)` would give `NaN`. TypeScript
catches both before anyone runs the code.

## Annotations

A **type annotation** goes after a name, following a colon. The basic types are `number`, `string`, `boolean`,
`null` and `undefined`, and arrays are written `number[]` (or `Array<number>`):

```typescript
const title: string = 'Dune';
let pages: number = 412;
const inStock: boolean = true;
const ratings: number[] = [5, 4, 5];
const tags: string[] = [];

pages = pages + 10;
tags.push('sci-fi');

console.log(title, pages, inStock, ratings, tags);
```

## Inference

You rarely need to annotate variables: TypeScript **infers** their types from the values they start with. Hover over
names in an editor and it shows what it worked out. Annotations matter most on function parameters, which can't be
inferred:

```typescript
const title = 'Emma';
let year = 1815;
const prices = [9.99, 4.5];

year = 1816;
const total = prices.reduce((sum, price) => sum + price, 0);

console.log(title.toUpperCase(), year, total.toFixed(2));
```

```typescript
// Error: year was inferred as a number, so it can't hold a string later.
let year = 1815;
year = 'eighteen fifteen';
```

A `const` holding a string gets the type of that exact string, such as `'Emma'`, because it can never change. More
on that below.

## Function types

Parameters need types; the return type is usually inferred, but writing it documents the function and catches
mistakes inside it. Optional parameters get a `?`, and defaults work as in JavaScript:

```typescript
function greet(name: string, greeting = 'Hello', punctuation?: string): string {
    return `${greeting}, ${name}${punctuation ?? '!'}`;
}

console.log(greet('Ben'), greet('Ben', 'Welcome', '.'));
```

A function that returns nothing has the return type `void`.

## `null` and `undefined` are separate types

With TypeScript's strict checks, which this course uses, `null` and `undefined` aren't allowed unless a type says so.
That rules out a whole class of "cannot read properties of undefined" bugs:

```typescript
// Error: find() may return undefined, so .toUpperCase() could fail.
const books = ['Dune', 'Emma'];
const found = books.find((title) => title.startsWith('C'));
console.log(found.toUpperCase());
```

```typescript
const books = ['Dune', 'Emma'];
const found = books.find((title) => title.startsWith('C'));
console.log(found?.toUpperCase() ?? 'not found');
```

## Tuples

A **tuple** is an array with a fixed length and a known type at each position, written `[string, number]`:

```typescript
function splitPrice(text: string): [string, number] {
    const [currency, amount] = text.split(' ');
    return [currency, Number(amount)];
}

const [currency, amount] = splitPrice('GBP 9.99');
console.log(currency, amount * 2);
```

## `any` and `unknown`

`any` switches type checking off: anything goes, in and out. It's an escape hatch, and it hides mistakes. `unknown`
is the safe opposite: any value can go **in**, but you must check what it is before using it:

```typescript
function describe(value: unknown): string {
    if (typeof value === 'number') {
        return `the number ${value.toFixed(1)}`;
    }
    if (typeof value === 'string') {
        return `the text "${value}"`;
    }
    return 'something else';
}

console.log(describe(3), describe('hi'), describe([1]));
```

```typescript
// Error: an unknown value can't be used until it's been checked.
function shout(value: unknown): string {
    return value.toUpperCase();
}
```

Data from outside the program, such as JSON from a server, is best treated as `unknown`.

## Literal types and `as const`

A **literal type** is a single exact value: `'GBP'`, `42`, `true`. Combined with `|`, they list the allowed values
precisely. `as const` makes an array or object read-only and keeps its literal types:

```typescript
let currency: 'GBP' | 'EUR' | 'USD' = 'GBP';
currency = 'EUR';

const SIZES = ['S', 'M', 'L'] as const;
type Size = (typeof SIZES)[number];

const mine: Size = 'M';
console.log(currency, SIZES, mine);
```

`type Size = ...` gives a type a name; `(typeof SIZES)[number]` means "the type of any element of `SIZES`", which is
`'S' | 'M' | 'L'`. There's much more about both in the coming lessons.

## Putting it together

Typed functions working together, with inference doing most of the work:

```typescript
function parsePence(text: string): number | null {
    const match = /^£?(\d+)\.(\d{2})$/.exec(text.trim());
    return match ? Number(match[1]) * 100 + Number(match[2]) : null;
}

function formatPence(pence: number): string {
    return `£${(pence / 100).toFixed(2)}`;
}

const inputs = ['£9.99', '4.50', 'free'];
const valid = inputs.map(parsePence).filter((p): p is number => p !== null);
const total = valid.reduce((sum, p) => sum + p, 0);

console.log(valid, formatPence(total));
```

Next, try the quiz to check your understanding, then solve the challenges.
