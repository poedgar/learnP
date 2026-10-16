## When things go wrong

When JavaScript hits a problem it can't continue from, it **throws** an error: an object with a `name` and a
`message`. If nothing catches it, the program stops there. `try...catch` catches it, so you can decide what happens
instead:

```javascript
try {
    const data = JSON.parse('not json');
    console.log(data);
} catch (error) {
    console.log(`Could not read the data: ${error.name}`);
}

console.log('The program carries on');
```

Built-in errors include `TypeError` (the wrong kind of value, such as calling something that isn't a function),
`ReferenceError` (an unknown variable), `RangeError` (a number out of range) and `SyntaxError`.

## Throwing your own

`throw` raises an error yourself, for situations your code can't handle. Throw `Error` objects, or one of the built-in
kinds, so the catcher gets a message and a stack trace:

```javascript
function parseQuantity(text) {
    const n = Number(text);
    if (!Number.isInteger(n)) {
        throw new TypeError(`Not a whole number: ${text}`);
    }
    if (n < 1) {
        throw new RangeError(`Quantity must be at least 1, got ${n}`);
    }
    return n;
}

for (const input of ['3', 'two', '0']) {
    try {
        console.log(parseQuantity(input));
    } catch (error) {
        console.log(`${error.name}: ${error.message}`);
    }
}
```

## Catching only what you expect

A `catch` catches **everything** thrown in its `try`, including bugs. Check the kind of error with `instanceof`, handle
the ones you expect, and throw the rest again:

```javascript
function quantityOr(text, fallback) {
    try {
        return parseInt(text, 10) > 0 ? parseInt(text, 10) : fallback;
    } catch (error) {
        if (error instanceof TypeError) {
            return fallback;
        }
        throw error;
    }
}

console.log(quantityOr('5', 1), quantityOr('x', 1));
```

## `finally`

A `finally` block runs whether the `try` succeeded or threw, which suits clean-up that must always happen:

```javascript
function withLog(task) {
    console.log('start');
    try {
        return task();
    } finally {
        console.log('finish');
    }
}

console.log(withLog(() => 42));

try {
    withLog(() => {
        throw new Error('failed');
    });
} catch (error) {
    console.log(error.message);
}
```

## Error classes of your own

Extending `Error` gives a kind of error your code can recognise with `instanceof`, and can carry extra details.
Setting `name` makes it print clearly. `cause` records the error that led to it:

```javascript
class NotFoundError extends Error {
    constructor(what, options) {
        super(`${what} was not found`, options);
        this.name = 'NotFoundError';
        this.what = what;
    }
}

function findBook(title) {
    try {
        throw new Error('database offline');
    } catch (error) {
        throw new NotFoundError(title, { cause: error });
    }
}

try {
    findBook('Dune');
} catch (error) {
    console.log(error instanceof NotFoundError, error.name, error.what, error.cause.message);
}
```

## Maps

A **`Map`** stores key and value pairs, like an object, but its keys can be **any** value (numbers, objects, even
functions), it remembers the order things were added, and it knows its `size`:

```javascript
const stock = new Map();

stock.set('dune', 3);
stock.set('emma', 0);
stock.set('dune', 4);

console.log(stock.get('dune'), stock.get('circe'), stock.has('emma'), stock.size);

for (const [title, count] of stock) {
    console.log(title, count);
}

stock.delete('emma');
console.log([...stock.keys()]);
```

Use a `Map` when keys aren't simple strings, or are added and removed a lot. Plain objects suit records with fixed,
known property names.

```javascript
const visits = new Map();
const amara = { name: 'Amara' };
const ben = { name: 'Ben' };

visits.set(amara, 2);
visits.set(ben, 5);

console.log(visits.get(amara), visits.get({ name: 'Amara' }));
```

Object keys are compared by identity: a different object with the same contents is a different key.

## Sets

A **`Set`** holds unique values: adding one that's already there does nothing. It's the quickest way to remove
duplicates, or to ask whether something is in a collection:

```javascript
const tags = new Set(['sci-fi', 'classic', 'sci-fi']);
tags.add('desert');
tags.add('classic');

console.log(tags.size, tags.has('desert'), [...tags]);

const unique = [...new Set([3, 1, 3, 2, 1])];
console.log(unique);
```

Combining sets uses `filter()` on one with `has()` on the other:

```javascript
const a = new Set([1, 2, 3, 4]);
const b = new Set([3, 4, 5]);

console.log([...a].filter((x) => b.has(x)), [...a].filter((x) => !b.has(x)));
console.log([...new Set([...a, ...b])]);
```

## Putting it together

Reading orders defensively: invalid lines are reported, not fatal, and a `Map` totals each customer:

```javascript
const lines = ['amara,999', 'ben,450', 'oops', 'amara,1250', 'cy,abc'];

const totals = new Map();
const problems = [];

for (const line of lines) {
    try {
        const [name, pence] = line.split(',');
        if (pence === undefined || Number.isNaN(Number(pence))) {
            throw new Error(`Bad line: ${line}`);
        }
        totals.set(name, (totals.get(name) ?? 0) + Number(pence));
    } catch (error) {
        problems.push(error.message);
    }
}

console.log(totals);
console.log(problems);
```

Next, try the quiz to check your understanding, then solve the challenges.
