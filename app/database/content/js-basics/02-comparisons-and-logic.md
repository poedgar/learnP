## Comparing values

Comparison operators give a boolean: `<`, `>`, `<=`, `>=`, and for equality `===` and `!==`. Strings compare
alphabetically, character by character:

```javascript
console.log(3 < 5, 5 <= 5, 10 > 20);
console.log('apple' < 'banana', 'Zebra' < 'apple');
console.log('10' < '9', 10 < 9);
```

Upper-case letters sort before lower-case ones, and strings of digits compare as text, so `'10' < '9'`. To sort words
the way people expect, use `localeCompare()`:

```javascript
console.log('Zebra'.localeCompare('apple'), 'apple'.localeCompare('Zebra'));
console.log('apple'.toLowerCase() < 'Zebra'.toLowerCase());
```

`localeCompare()` gives a negative number if the first string comes first, positive if it comes after, and `0` if
they're equal.

## `===` and `==`

`===` (strict equality) is true only when both values have the same type and value. `==` (loose equality) converts
types first, following rules that are hard to remember:

```javascript
console.log(1 === 1, 1 === '1');
console.log(1 == '1', 0 == '', 0 == '0', '' == '0');
console.log(null == undefined, null === undefined);
```

`0 == ''` and `0 == '0'` are both true, but `'' == '0'` is false. Always use `===` and `!==`. The one common exception
is `x == null`, which is true for both `null` and `undefined`.

`NaN` isn't equal to anything, itself included. `Object.is()` is like `===` but treats `NaN` as equal to itself:

```javascript
console.log(NaN === NaN, Object.is(NaN, NaN), Number.isNaN(NaN));
```

## Truthy and falsy

Anywhere JavaScript expects a boolean, such as in an `if`, it converts the value. Exactly eight values are **falsy**:
`false`, `0`, `-0`, `0n`, `''`, `null`, `undefined` and `NaN`. Everything else is **truthy**, including `'0'`,
`'false'`, `[]` and `{}`:

```javascript
console.log(Boolean(0), Boolean(''), Boolean(null), Boolean(NaN));
console.log(Boolean('0'), Boolean('false'), Boolean([]), Boolean({}));
console.log(!!'hello', !'hello');
```

`!` flips truthiness, so `!!value` converts to a real boolean.

## `&&`, `||` and `!`

`&&` is true when both sides are, `||` when either is. They **short-circuit**: the right side is only evaluated if
needed. They also return one of their operands rather than `true` or `false`:

```javascript
const age = 20;
const hasTicket = true;

console.log(age >= 18 && hasTicket);
console.log(age < 13 || age > 65);
console.log('' || 'default', 'chosen' || 'default');
console.log(0 && 'never', 1 && 'yes');
```

`a || b` gives `a` if it's truthy, otherwise `b`; `a && b` gives `a` if it's falsy, otherwise `b`.

## Defaults with `??`

`||` is often used for defaults, but it replaces every falsy value, including a perfectly good `0` or `''`. The
**nullish coalescing** operator `??` only replaces `null` and `undefined`:

```javascript
const volume = 0;
const nickname = '';

console.log(volume || 50, volume ?? 50);
console.log(nickname || 'anonymous', nickname ?? 'anonymous');
console.log(undefined ?? 'fallback', null ?? 'fallback');
```

`??=` assigns only if the variable is `null` or `undefined`:

```javascript
let theme = null;
theme ??= 'light';
console.log(theme);
```

## Optional chaining

Reading a property of `null` or `undefined` is an error. `?.` stops early and gives `undefined` instead, which pairs
well with `??`:

```javascript
const order = { customer: { name: 'Amara', address: null } };

console.log(order.customer?.name);
console.log(order.customer?.address?.city);
console.log(order.customer?.address?.city ?? 'no city');
```

```javascript
// Error: reading a property of null fails without ?.
const order = { customer: { address: null } };
console.log(order.customer.address.city);
```

## The conditional operator

`condition ? a : b` gives `a` if the condition is truthy and `b` otherwise. It's an expression, so it can go anywhere a
value can:

```javascript
const stock = 0;
const label = stock > 0 ? `${stock} in stock` : 'Sold out';
console.log(label);
```

Nested ones chain from left to right; keep them short or use `if`, which comes next lesson.

## Checking numbers

`Number.isInteger()` says whether a number is whole. Combined with comparisons, it can validate input:

```javascript
const text = '42';
const value = Number(text);

console.log(Number.isInteger(value), Number.isInteger(4.5), Number.isInteger(Number('abc')));
console.log(text.trim() !== '' && Number.isInteger(value) && value >= 0);
```

`Number('')` is `0`, not `NaN`, which is why an empty string needs its own check.

## Putting it together

Deciding what to show for a product, with defaults and safe property access:

```javascript
const product = { name: 'Bookmark', price: 0, stock: 12, discount: null, supplier: undefined };

const price = product.price === 0 ? 'Free' : `£${product.price}`;
const discount = product.discount ?? 'none';
const supplier = product.supplier?.name ?? 'unknown';
const available = product.stock > 0 && price !== 'Free';

console.log(price, discount, supplier, available);
```

Next, try the quiz to check your understanding, then solve the challenges.
