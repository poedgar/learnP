## Lists of values

An **array** is an ordered list. Items are numbered from 0, `length` says how many there are, and `at(-1)` reaches
from the end:

```javascript
const books = ['Dune', 'Emma', 'Circe'];

console.log(books[0], books.length, books.at(-1));
console.log(books[5]);
```

Reading past the end gives `undefined`, not an error.

## Adding and removing

`push()` and `pop()` work at the end, `unshift()` and `shift()` at the start. They change the array itself:

```javascript
const queue = ['Amara'];

queue.push('Ben', 'Cy');
queue.unshift('Dee');
const first = queue.shift();
const last = queue.pop();

console.log(queue, first, last);
```

A `const` array can still be changed: `const` stops the variable pointing at a different array, not changes to the
array's contents.

## Finding things

`includes()` says whether a value is there, `indexOf()` where (or `-1`), and `find()` and `findIndex()` search with a
function of your own:

```javascript
const prices = [4.99, 12.5, 7.25, 30];

console.log(prices.includes(7.25), prices.indexOf(30), prices.indexOf(99));
console.log(prices.find((p) => p > 10), prices.findIndex((p) => p > 10));
console.log(prices.some((p) => p > 25), prices.every((p) => p > 1));
```

## `slice` and `splice`

`slice(start, end)` copies part of an array and leaves the original alone. `splice(start, count, ...items)` removes
and inserts in place, returning what it removed:

```javascript
const letters = ['a', 'b', 'c', 'd', 'e'];

console.log(letters.slice(1, 3), letters);

const removed = letters.splice(1, 2, 'X');
console.log(removed, letters);
```

## Transforming with `map`, `filter` and `reduce`

Three methods cover most list work, and none of them changes the original:

- `map(fn)` makes a new array with `fn` applied to every item;
- `filter(fn)` keeps the items for which `fn` returns something truthy;
- `reduce(fn, start)` combines all the items into one value.

```javascript
const orders = [
    { item: 'Dune', pence: 999, qty: 2 },
    { item: 'Emma', pence: 799, qty: 1 },
    { item: 'Circe', pence: 1299, qty: 3 },
];

const lineTotals = orders.map((o) => o.pence * o.qty);
const bigOrders = orders.filter((o) => o.qty > 1);
const grandTotal = lineTotals.reduce((sum, total) => sum + total, 0);

console.log(lineTotals, bigOrders.length, grandTotal);
```

The callbacks also receive the index as a second argument: `map((item, i) => ...)`.

## Sorting

`sort()` sorts in place and, by default, compares items **as strings**, which is wrong for numbers. Pass a compare
function that returns a negative number when `a` should come first:

```javascript
const numbers = [10, 9, 100, 1];

console.log([...numbers].sort());
console.log([...numbers].sort((a, b) => a - b));
console.log([...numbers].sort((a, b) => b - a));

const names = ['emma', 'Ben', 'amara'];
console.log([...names].sort((a, b) => a.localeCompare(b)));
```

`[...numbers]` makes a copy first, so the original isn't changed. Newer browsers also have `toSorted()`, which returns
a sorted copy.

## Copying, joining and spreading

The spread syntax `...` copies arrays and joins them. `join()` turns an array into a string and `split()` does the
reverse:

```javascript
const fiction = ['Dune', 'Emma'];
const poetry = ['Odes'];

const all = [...fiction, ...poetry, 'Atlas'];
console.log(all.join(', '));
console.log('red,green,blue'.split(','));
console.log(Array.from({ length: 4 }, (_, i) => i * i));
```

## Destructuring

**Destructuring** takes items out of an array into variables, by position. `...rest` collects what's left:

```javascript
const [winner, runnerUp, ...others] = ['Amara', 'Ben', 'Cy', 'Dee'];
console.log(winner, runnerUp, others);

let a = 1;
let b = 2;
[a, b] = [b, a];
console.log(a, b);
```

## Arrays hold references

Assigning an array to another variable doesn't copy it: both names refer to the same array. Copy explicitly when you
need an independent one:

```javascript
const original = [1, 2, 3];
const alias = original;
const copy = [...original];

alias.push(4);

console.log(original, copy);
console.log(original === alias, original === copy);
```

Two different arrays are never `===`, even with the same contents.

## Putting it together

A leaderboard: filter, sort a copy, then map to text:

```javascript
const players = [
    { name: 'Amara', score: 42 },
    { name: 'Ben', score: 17 },
    { name: 'Cy', score: 42 },
    { name: 'Dee', score: 30 },
];

const board = players
    .filter((p) => p.score >= 20)
    .toSorted((a, b) => b.score - a.score || a.name.localeCompare(b.name))
    .map((p, i) => `${i + 1}. ${p.name} (${p.score})`);

console.log(board.join('\n'));
```

`b.score - a.score || a.name.localeCompare(b.name)` sorts by score, and by name when scores are equal.

Next, try the quiz to check your understanding, then solve the challenges.
