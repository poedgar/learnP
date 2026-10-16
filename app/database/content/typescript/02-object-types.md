## Describing objects

An object type lists the properties an object must have, with a type for each. Give it a name with a **type alias**
(`type`) or an **interface**, and use the name wherever such objects go:

```typescript
type Book = {
    title: string;
    author: string;
    year: number;
};

function describe(book: Book): string {
    return `${book.title} by ${book.author} (${book.year})`;
}

const dune: Book = { title: 'Dune', author: 'Frank Herbert', year: 1965 };
console.log(describe(dune));
```

```typescript
// Error: a Book needs a year, and title must be a string.
type Book = { title: string; author: string; year: number };

const broken: Book = { title: 42, author: 'Anon' };
```

## Interfaces

An **interface** describes an object type much as a type alias does, and either works for most objects. Interfaces
can be **extended**, building one shape on another:

```typescript
interface Person {
    name: string;
    email: string;
}

interface Member extends Person {
    memberSince: number;
}

const amara: Member = { name: 'Amara', email: 'amara@example.com', memberSince: 2019 };

function contact(person: Person): string {
    return `${person.name} <${person.email}>`;
}

console.log(contact(amara));
```

A `Member` can be passed where a `Person` is expected: it has everything a `Person` needs.

## Optional and readonly properties

A `?` after a property name makes it optional; its type then includes `undefined`. `readonly` stops a property being
reassigned:

```typescript
interface Product {
    readonly id: number;
    name: string;
    discountPercent?: number;
}

const mug: Product = { id: 7, name: 'Mug' };
mug.name = 'Big mug';

const discount = mug.discountPercent ?? 0;
console.log(mug, discount);
```

```typescript
// Error: id is readonly.
interface Product {
    readonly id: number;
    name: string;
}

const mug: Product = { id: 7, name: 'Mug' };
mug.id = 8;
```

## Structural typing

TypeScript checks **shapes**, not names: any object with the right properties fits a type, whatever it was declared
as. That matches how JavaScript is actually used:

```typescript
interface Point {
    x: number;
    y: number;
}

function distanceFromOrigin(point: Point): number {
    return Math.hypot(point.x, point.y);
}

const city = { name: 'Leeds', x: 3, y: 4 };
console.log(distanceFromOrigin(city));
```

`city` was never declared a `Point`, but it has `x` and `y`, so it fits. Writing an object literal directly in the
call, though, is checked strictly: extra properties there are an error, because they're usually a typo:

```typescript
// Error: an object literal may only have the properties its type lists.
interface Point {
    x: number;
    y: number;
}

const p: Point = { x: 1, y: 2, z: 3 };
```

## Index signatures and `Record`

When the property names aren't known in advance, such as a lookup of prices by title, an **index signature** gives
the type of every value. `Record<Keys, Value>` says the same thing more briefly:

```typescript
const stock: { [title: string]: number } = { Dune: 3, Emma: 0 };
const prices: Record<string, number> = { Dune: 999 };

stock.Circe = 5;
console.log(stock, prices.Dune);

const currencyNames: Record<'GBP' | 'EUR', string> = { GBP: 'pound', EUR: 'euro' };
console.log(currencyNames.EUR);
```

With a union of keys, `Record` requires every one of them to be present.

## Nested and array properties

Object types nest, and properties can be arrays of other object types:

```typescript
interface OrderLine {
    title: string;
    pence: number;
    qty: number;
}

interface Order {
    id: number;
    customer: { name: string; email?: string };
    lines: OrderLine[];
}

function total(order: Order): number {
    return order.lines.reduce((sum, line) => sum + line.pence * line.qty, 0);
}

const order: Order = {
    id: 1,
    customer: { name: 'Ben' },
    lines: [
        { title: 'Dune', pence: 999, qty: 2 },
        { title: 'Emma', pence: 799, qty: 1 },
    ],
};

console.log(total(order), order.customer.email ?? 'no email');
```

## Functions as properties

Object types can include methods, written either as `name(args): result` or as a property with a function type
`(args) => result`:

```typescript
interface Counter {
    count: number;
    increment(by?: number): void;
    reset: () => void;
}

const counter: Counter = {
    count: 0,
    increment(by = 1) {
        this.count += by;
    },
    reset() {
        this.count = 0;
    },
};

counter.increment();
counter.increment(5);
console.log(counter.count);
```

## Combining types with `&`

An **intersection** `A & B` has everything from both types. It's another way to build a type from smaller ones:

```typescript
type Named = { name: string };
type Priced = { pence: number };
type Item = Named & Priced;

const item: Item = { name: 'Bookmark', pence: 150 };
console.log(`${item.name}: ${item.pence}p`);
```

## Putting it together

A small library catalogue with interfaces for each kind of record:

```typescript
interface Author {
    name: string;
    born?: number;
}

interface CatalogueEntry {
    readonly isbn: string;
    title: string;
    authors: Author[];
    copies: Record<string, number>;
}

function availableAt(entry: CatalogueEntry): string[] {
    return Object.entries(entry.copies)
        .filter(([, count]) => count > 0)
        .map(([branch]) => branch);
}

const entry: CatalogueEntry = {
    isbn: '978-0441013593',
    title: 'Dune',
    authors: [{ name: 'Frank Herbert', born: 1920 }],
    copies: { Central: 2, Riverside: 0, Hilltop: 1 },
};

console.log(`${entry.title} by ${entry.authors.map((a) => a.name).join(', ')}: ${availableAt(entry).join(', ')}`);
```

Next, try the quiz to check your understanding, then solve the challenges.
