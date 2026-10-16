## Objects group named values

An **object** holds **properties**: names (keys) with values. Read them with a dot, or with brackets when the name
is in a variable or isn't a valid identifier:

```javascript
const book = {
    title: 'Dune',
    author: 'Frank Herbert',
    'page count': 412,
};

const field = 'author';

console.log(book.title, book[field], book['page count']);
console.log(book.isbn);
```

A property that doesn't exist reads as `undefined`.

## Adding, changing and removing

Assigning to a property creates or replaces it; `delete` removes one. `in` and `Object.hasOwn()` tell you whether a
property exists, even one whose value is `undefined`:

```javascript
const user = { name: 'Amara' };

user.email = 'amara@example.com';
user.name = 'Amara Okafor';
user.nickname = undefined;
delete user.email;

console.log(user);
console.log('nickname' in user, Object.hasOwn(user, 'email'));
```

## Shorthand and computed keys

When a variable has the same name as the property, write it once. Brackets around a key compute its name:

```javascript
const title = 'Emma';
const year = 1815;
const key = 'genre';

const novel = { title, year, [key]: 'romance', [`${key}Code`]: 'ROM' };
console.log(novel);
```

## Methods and `this`

A function stored in an object is a **method**. Inside it, `this` is the object the method was called on:

```javascript
const basket = {
    items: [999, 450],
    total() {
        return this.items.reduce((sum, pence) => sum + pence, 0);
    },
};

console.log(basket.total());
```

`this` depends on how the function is **called**, not where it was written; a method copied into a variable and
called on its own loses it. Arrow functions don't have their own `this`, so they're poor choices for methods.

## Going through an object

`Object.keys()`, `Object.values()` and `Object.entries()` give arrays you can loop over or transform;
`Object.fromEntries()` builds an object back from `[key, value]` pairs:

```javascript
const stock = { dune: 3, emma: 0, circe: 5 };

console.log(Object.keys(stock), Object.values(stock));

for (const [title, count] of Object.entries(stock)) {
    console.log(`${title}: ${count}`);
}

const inStock = Object.fromEntries(Object.entries(stock).filter(([, count]) => count > 0));
console.log(inStock);
```

## Destructuring objects

Object destructuring takes properties out by **name**. You can rename them, give defaults, and collect the rest:

```javascript
const order = { id: 7, customer: 'Ben', items: 3 };

const { id, customer: buyer, express = false, ...details } = order;
console.log(id, buyer, express, details);

function summary({ title, price = 0 }) {
    return `${title}: £${price}`;
}
console.log(summary({ title: 'Map' }));
```

Destructuring in a parameter list is a neat way to accept named options.

## Spreading and merging

Spread copies an object's properties into a new one. Later properties win, which makes it easy to apply overrides to
defaults:

```javascript
const defaults = { theme: 'light', fontSize: 16, sound: true };
const chosen = { fontSize: 20 };

const settings = { ...defaults, ...chosen };
console.log(settings, defaults.fontSize);
```

Spread copies only one level deep: nested objects are shared, not copied. `structuredClone()` makes a deep copy:

```javascript
const original = { name: 'Dune', tags: ['sci-fi'] };
const shallow = { ...original };
const deep = structuredClone(original);

original.tags.push('classic');
console.log(shallow.tags, deep.tags);
```

## JSON

**JSON** is a text format for data, based on JavaScript's object syntax. `JSON.stringify()` turns values into JSON
text and `JSON.parse()` turns it back:

```javascript
const book = { title: 'Circe', year: 2018, tags: ['myth'], read: true, rating: null };

const text = JSON.stringify(book);
console.log(text);
console.log(JSON.stringify({ a: 1, b: [2, 3] }, null, 2));

const back = JSON.parse(text);
console.log(back.tags[0], back === book);
```

JSON has no functions, `undefined` or dates: functions and `undefined` values are left out, and dates become strings.
`JSON.parse()` throws a `SyntaxError` on invalid text:

```javascript
// Error: JSON needs double quotes around names and strings.
console.log(JSON.parse("{ title: 'Dune' }"));
```

## Putting it together

Counting words into an object, then turning the counts into a sorted report:

```javascript
const text = 'the cat and the hat and the bat';

const counts = {};
for (const word of text.split(' ')) {
    counts[word] = (counts[word] ?? 0) + 1;
}

const report = Object.entries(counts)
    .sort(([a, x], [b, y]) => y - x || a.localeCompare(b))
    .map(([word, n]) => `${word} ×${n}`);

console.log(counts);
console.log(report.join(', '));
```

Next, try the quiz to check your understanding, then solve the challenges.
