## Types from values

`typeof value`, used where a type is expected, gives the type of a value. With `keyof`, it turns an object you
already have into a union of its keys, so the code and the types can't drift apart:

```typescript
const PRICES = { small: 250, medium: 320, large: 390 };

type Size = keyof typeof PRICES;

function price(size: Size): number {
    return PRICES[size];
}

console.log(price('medium'));
```

Adding a size to `PRICES` adds it to `Size` automatically.

## Indexed access types

`Type['property']` is the type of a property, and `Type[number]` the type of an array's elements. They work on any
type, including nested ones:

```typescript
interface Order {
    id: number;
    customer: { name: string; tier: 'gold' | 'silver' };
    lines: { title: string; pence: number }[];
}

type Tier = Order['customer']['tier'];
type Line = Order['lines'][number];

const tier: Tier = 'gold';
const line: Line = { title: 'Dune', pence: 999 };
console.log(tier, line);
```

## Mapped types

A **mapped type** builds an object type by going through a union of keys, written `{ [K in Keys]: ... }`. Combined
with `keyof`, it transforms every property of another type. Utility types such as `Partial` and `Readonly` are mapped
types:

```typescript
type MyPartial<T> = { [K in keyof T]?: T[K] };
type Flags<T> = { [K in keyof T]: boolean };

interface Settings {
    theme: string;
    fontSize: number;
}

const changes: MyPartial<Settings> = { fontSize: 18 };
const touched: Flags<Settings> = { theme: false, fontSize: true };

console.log(changes, touched);
```

Modifiers can be added or removed with `+` and `-`: `{ -readonly [K in keyof T]-?: T[K] }` makes every property
writable and required.

## Template literal types

Types can be built from strings, like template literals for types. With unions inside, they combine every option:

```typescript
type Side = 'top' | 'bottom';
type Edge = 'left' | 'right';
type Corner = `${Side}-${Edge}`;

type Getters<T> = { [K in keyof T as `get${Capitalize<string & K>}`]: () => T[K] };

const corner: Corner = 'top-right';

const getters: Getters<{ name: string; age: number }> = {
    getName: () => 'Amara',
    getAge: () => 34,
};

console.log(corner, getters.getName(), getters.getAge());
```

`as` in a mapped type renames each key; `Capitalize`, `Uppercase` and `Lowercase` change a string literal type's case.

## Conditional types

A **conditional type** chooses between two types with `A extends B ? X : Y`: if `A` fits `B`, the result is `X`,
otherwise `Y`. Given a union, it's applied to each member separately:

```typescript
type IsString<T> = T extends string ? 'yes' : 'no';

type A = IsString<'hello'>;
type B = IsString<42>;
type OnlyStrings<T> = T extends string ? T : never;
type C = OnlyStrings<'a' | 1 | 'b' | true>;

const a: A = 'yes';
const b: B = 'no';
const c: C = 'b';
console.log(a, b, c);
```

`never` disappears from unions, which is how `OnlyStrings` filters, and how the built-in `Exclude` and `Extract` work.

## `infer`

Inside a conditional type, `infer` names a part of a type so it can be used in the result. This is how `ReturnType`
and `Awaited` are built:

```typescript
type ElementOf<T> = T extends (infer Item)[] ? Item : never;
type Unpromise<T> = T extends Promise<infer Value> ? Value : T;
type FirstArg<F> = F extends (first: infer A, ...rest: any[]) => any ? A : never;

const item: ElementOf<string[]> = 'dune';
const value: Unpromise<Promise<number>> = 42;
const arg: FirstArg<(name: string, age: number) => void> = 'Amara';

console.log(item, value, arg);
```

## `satisfies`

Annotating a variable with a type makes TypeScript forget the value's more precise type. `satisfies` checks that a
value fits a type **without** changing what's inferred, so you get both the check and the detail:

```typescript
type Colour = 'red' | 'green' | 'blue';

const palette = {
    red: '#e33',
    green: [40, 180, 90],
    blue: '#36f',
} satisfies Record<Colour, string | number[]>;

console.log(palette.red.toUpperCase(), palette.green.map((n) => n / 255).length);
```

With `const palette: Record<Colour, string | number[]> = ...` instead, `palette.red.toUpperCase()` would be an error,
because `red` would only be known as `string | number[]`.

## Branded types

Two numbers with different meanings, such as a user id and an order id, are both `number`, so they can be mixed up.
A **brand** makes them distinct types; values get the brand from a function that checks them:

```typescript
type UserId = number & { readonly brand: 'UserId' };
type OrderId = number & { readonly brand: 'OrderId' };

const userId = (n: number) => n as UserId;
const orderId = (n: number) => n as OrderId;

function loadUser(id: UserId): string {
    return `user ${id}`;
}

console.log(loadUser(userId(7)));
```

```typescript
// Error: an OrderId isn't a UserId, even though both are numbers underneath.
type UserId = number & { readonly brand: 'UserId' };
type OrderId = number & { readonly brand: 'OrderId' };

function loadUser(id: UserId): string {
    return `user ${id}`;
}

loadUser(5 as OrderId);
```

The brand exists only in the types; at run time, the values are plain numbers.

## Putting it together

Typed event handlers: one map of event names to payloads drives the type of every handler, with no repetition:

```typescript
interface Events {
    login: { user: string };
    purchase: { user: string; pence: number };
    logout: undefined;
}

type Handlers = { [E in keyof Events as `on${Capitalize<E>}`]: (payload: Events[E]) => string };

const handlers: Handlers = {
    onLogin: ({ user }) => `${user} logged in`,
    onPurchase: ({ user, pence }) => `${user} spent £${(pence / 100).toFixed(2)}`,
    onLogout: () => 'goodbye',
};

function emit<E extends keyof Events>(event: E, payload: Events[E]): string {
    const name = `on${event[0].toUpperCase()}${event.slice(1)}` as keyof Handlers;
    return (handlers[name] as (payload: Events[E]) => string)(payload);
}

console.log(emit('login', { user: 'Amara' }));
console.log(emit('purchase', { user: 'Amara', pence: 1999 }));
console.log(emit('logout', undefined));
```

Next, try the quiz to check your understanding, then solve the challenges.
