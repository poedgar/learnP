## Your first JavaScript

JavaScript runs in every web browser, and on servers with Node.js. A program is a list of **statements**, run from
top to bottom. `console.log()` prints values, which is how you'll see what your code does:

```javascript
console.log('Hello, world!');
console.log(6 * 7);
console.log('Six times seven is', 6 * 7);
```

Given several values, `console.log()` prints them separated by spaces. Statements usually end with a semicolon.

## Variables: `let` and `const`

A **variable** is a name for a value. Declare one with `const` if it will never be reassigned, or `let` if it will:

```javascript
const title = 'Dune';
let copiesSold = 10;

copiesSold = copiesSold + 5;
copiesSold += 1;

console.log(title, copiesSold);
```

Reassigning a `const` is an error. Most variables never change, so prefer `const` and use `let` only when you need
to reassign:

```javascript
// Error: a const can't be reassigned.
const year = 1965;
year = 1966;
```

You may see `var` in older code. It works differently from `let` in ways that cause bugs; don't use it in new code.

## Numbers

JavaScript has one number type for whole numbers and decimals alike. The usual operators work, plus `%` (the
remainder) and `**` (powers):

```javascript
console.log(7 + 2, 7 - 2, 7 * 2, 7 / 2);
console.log(7 % 2, 2 ** 10);
console.log(Math.floor(7 / 2), Math.round(2.5), Math.max(3, 9, 4));
```

There's no separate integer division: `7 / 2` is `3.5`, so use `Math.floor()` to drop the fraction. Decimals are
stored in binary, so some sums come out slightly off, just as in most languages:

```javascript
console.log(0.1 + 0.2);
console.log((0.1 + 0.2).toFixed(2));
console.log(1 / 0, -1 / 0, 0 / 0);
```

`NaN` means "not a number": the result of maths that has no answer. For money, count whole pence.

## Strings

Text goes in single quotes, double quotes or backticks. Strings have a `length` and many methods; none of them
change the original string, they return a new one:

```javascript
const name = '  Ursula K. Le Guin  ';

console.log(name.trim());
console.log(name.trim().toUpperCase());
console.log(name.trim().length);
console.log(name.includes('Le Guin'), name.trim().startsWith('U'));
console.log(name.trim().slice(0, 6));
```

`slice(start, end)` takes characters from `start` up to, but not including, `end`.

## Template literals

Backticks make a **template literal**: `${...}` inside it is replaced by the value of the expression, and it can
span several lines:

```javascript
const book = 'Circe';
const pence = 1299;

console.log(`${book} costs £${(pence / 100).toFixed(2)}.`);
console.log(`Line one
Line two`);
```

`padStart()` pads a string to a length, which is handy for times and prices:

```javascript
const minutes = 5;
console.log(`10:${String(minutes).padStart(2, '0')}`);
```

## Booleans, `null` and `undefined`

`true` and `false` are **booleans**. Two values mean "nothing": `undefined` is what a variable holds before it's given
a value, and `null` is set on purpose to mean "no value":

```javascript
let nickname;
const middleName = null;

console.log(nickname, middleName, 5 > 3);
```

## Asking for the type

`typeof` tells you a value's type, as a string. Two results surprise everyone: `typeof null` is `'object'` (a
mistake from 1995 that can't be fixed now), and `NaN`, "not a number", is a number:

```javascript
console.log(typeof 42, typeof 'hi', typeof true);
console.log(typeof undefined, typeof null, typeof NaN);
console.log(typeof [1, 2], typeof { a: 1 });
```

## Converting between types

`Number()` turns a string into a number, or `NaN` if it can't. `parseInt()` and `parseFloat()` read a number from the
start of a string and stop at the first character that doesn't fit. `String()` goes the other way:

```javascript
console.log(Number('42'), Number('42px'), Number(''));
console.log(parseInt('42px'), parseFloat('3.5kg'), parseInt('px42'));
console.log(String(99) + 1, Number('99') + 1);
console.log(Number.isNaN(Number('abc')));
```

Use `Number.isNaN()` to test for `NaN`: `NaN` is the only value that isn't equal to itself, so `x === NaN` is always
false.

## Putting it together

A receipt line built from a few values:

```javascript
const item = 'The Left Hand of Darkness';
const quantity = 2;
const pricePence = 899;

const totalPence = quantity * pricePence;
const pounds = Math.floor(totalPence / 100);
const pence = String(totalPence % 100).padStart(2, '0');

console.log(`${quantity} × ${item}: £${pounds}.${pence}`);
```

Next, try the quiz to check your understanding, then solve the challenges.
