## One of several types

A **union** type `A | B` holds a value of either type. It describes things JavaScript does all the time: an id that's
a number or a string, a result that's a value or `null`:

```typescript
function formatId(id: number | string): string {
    return `#${id}`;
}

let input: string | null = null;
input = 'hello';

console.log(formatId(42), formatId('A-7'), input);
```

With a union, you can only use what **every** member supports. `id.toUpperCase()` would be an error above, because
numbers don't have it.

## Narrowing

Inside a check, TypeScript **narrows** a union to the types that can get there. `typeof` checks narrow primitives,
and each branch then sees the specific type:

```typescript
function padId(id: number | string): string {
    if (typeof id === 'number') {
        return String(id).padStart(5, '0');
    }
    return id.toUpperCase();
}

console.log(padId(42), padId('ab-7'));
```

Other checks narrow too: truthiness (`if (value)`), equality (`=== null`), `Array.isArray()`, `instanceof` for classes,
and `in` for properties:

```typescript
function lengthOf(value: string | string[] | null): number {
    if (value === null) {
        return 0;
    }
    if (Array.isArray(value)) {
        return value.length;
    }
    return value.length;
}

function describe(date: Date | string): string {
    return date instanceof Date ? date.toISOString().slice(0, 10) : date;
}

console.log(lengthOf(null), lengthOf(['a', 'b']), lengthOf('abc'), describe(new Date(Date.UTC(2026, 0, 2))), describe('soon'));
```

## Unions of literals

Unions of literal types list exact allowed values, which is clearer and safer than plain strings:

```typescript
type Status = 'draft' | 'published' | 'archived';

function badge(status: Status): string {
    if (status === 'published') {
        return '✓ live';
    }
    return status === 'draft' ? '✎ draft' : '🗄 archived';
}

console.log(badge('draft'), badge('published'));
```

```typescript
// Error: 'deleted' isn't one of the allowed statuses.
type Status = 'draft' | 'published' | 'archived';
const status: Status = 'deleted';
```

## Discriminated unions

A powerful pattern: a union of object types that share one literal property, the **discriminant**, often called
`kind` or `type`. Checking it narrows to the matching object type, with its own properties:

```typescript
type Shape =
    | { kind: 'circle'; radius: number }
    | { kind: 'rectangle'; width: number; height: number }
    | { kind: 'triangle'; base: number; height: number };

function area(shape: Shape): number {
    switch (shape.kind) {
        case 'circle':
            return Math.PI * shape.radius ** 2;
        case 'rectangle':
            return shape.width * shape.height;
        case 'triangle':
            return (shape.base * shape.height) / 2;
    }
}

console.log(area({ kind: 'rectangle', width: 3, height: 4 }), area({ kind: 'triangle', base: 6, height: 2 }).toFixed(1));
```

Inside `case 'circle'`, TypeScript knows `shape` has a `radius`, and that it has no `width`.

## Exhaustiveness with `never`

`never` is the type with no values. After every member of a union has been handled, what's left is `never`, so
assigning the value to a `never` variable proves every case is covered. If someone adds a new member later, that line
becomes a type error, pointing at the code that needs updating:

```typescript
type Payment = { method: 'card'; last4: string } | { method: 'paypal'; email: string };

function describe(payment: Payment): string {
    switch (payment.method) {
        case 'card':
            return `card ending ${payment.last4}`;
        case 'paypal':
            return `PayPal (${payment.email})`;
        default: {
            const unhandled: never = payment;
            return unhandled;
        }
    }
}

console.log(describe({ method: 'card', last4: '4242' }));
```

```typescript
// Error: the 'voucher' case isn't handled, so payment isn't never in the default branch.
type Payment = { method: 'card'; last4: string } | { method: 'paypal'; email: string } | { method: 'voucher'; code: string };

function describe(payment: Payment): string {
    switch (payment.method) {
        case 'card':
            return `card ending ${payment.last4}`;
        case 'paypal':
            return `PayPal (${payment.email})`;
        default: {
            const unhandled: never = payment;
            return unhandled;
        }
    }
}
```

## Type predicates

A function returning `value is Type` is a **type guard**: when it returns true, TypeScript narrows its argument.
That lets you write your own checks, and lets `filter()` narrow arrays:

```typescript
interface Book {
    kind: 'book';
    title: string;
}

interface Film {
    kind: 'film';
    title: string;
    minutes: number;
}

type Item = Book | Film;

function isFilm(item: Item): item is Film {
    return item.kind === 'film';
}

const items: Item[] = [
    { kind: 'book', title: 'Dune' },
    { kind: 'film', title: 'Dune', minutes: 155 },
];

const films = items.filter(isFilm);
console.log(films.map((film) => film.minutes));
```

Without the predicate, `films` would still be `Item[]`, and `film.minutes` an error.

## Results instead of exceptions

Discriminated unions are a good way to return either a success or a failure, making callers handle both:

```typescript
type Result<T> = { ok: true; value: T } | { ok: false; error: string };

function parseQuantity(text: string): Result<number> {
    const n = Number(text);
    return Number.isInteger(n) && n > 0 ? { ok: true, value: n } : { ok: false, error: `Not a quantity: ${text}` };
}

for (const input of ['3', 'lots']) {
    const result = parseQuantity(input);
    console.log(result.ok ? `quantity ${result.value}` : result.error);
}
```

`Result<T>` is a **generic** type, with a type parameter `T`; generics are the next lesson.

## Putting it together

Events in a reading app, each kind with its own data, handled exhaustively:

```typescript
type ReadingEvent =
    | { type: 'started'; title: string }
    | { type: 'progress'; title: string; page: number }
    | { type: 'finished'; title: string; rating: 1 | 2 | 3 | 4 | 5 };

function message(event: ReadingEvent): string {
    switch (event.type) {
        case 'started':
            return `Started ${event.title}`;
        case 'progress':
            return `Page ${event.page} of ${event.title}`;
        case 'finished':
            return `Finished ${event.title}: ${'★'.repeat(event.rating)}`;
        default: {
            const unhandled: never = event;
            return unhandled;
        }
    }
}

const events: ReadingEvent[] = [
    { type: 'started', title: 'Circe' },
    { type: 'progress', title: 'Circe', page: 120 },
    { type: 'finished', title: 'Circe', rating: 5 },
];

console.log(events.map(message).join('\n'));
```

Next, try the quiz to check your understanding, then solve the challenges.
