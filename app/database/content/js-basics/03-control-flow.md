## Making decisions with `if`

`if` runs a block of code only when its condition is truthy. `else if` tries another condition, and `else` catches
everything left:

```javascript
const score = 72;

if (score >= 70) {
    console.log('Distinction');
} else if (score >= 40) {
    console.log('Pass');
} else {
    console.log('Fail');
}
```

Only the first matching branch runs. Always use braces, even for one line: it avoids a classic bug when a second line
is added later.

## `switch`

`switch` compares one value with several cases using `===`. Each case runs until a `break`; without one, it falls
through into the next case, which lets several cases share code:

```javascript
const format = 'paperback';

switch (format) {
    case 'hardback':
    case 'paperback':
        console.log('Ships by post');
        break;
    case 'ebook':
        console.log('Instant download');
        break;
    default:
        console.log('Unknown format');
}
```

Forgetting a `break` is a common bug, so many people prefer an object lookup or `if` for simple cases.

## `for` loops

A `for` loop has three parts: a starting statement, a condition checked before each pass, and an update run after
each pass:

```javascript
for (let i = 1; i <= 3; i++) {
    console.log(`Pass ${i}`);
}

let line = '';
for (let i = 10; i > 0; i -= 3) {
    line += i + ' ';
}
console.log(line);
```

`i++` adds one to `i`. A variable declared with `let` in the loop belongs to that loop only.

## `while` and `do...while`

`while` repeats as long as its condition is true, checking it first. Use it when you don't know in advance how many
passes you need:

```javascript
let balance = 100;
let years = 0;

while (balance < 200) {
    balance *= 1.07;
    years++;
}

console.log(`${years} years, £${balance.toFixed(2)}`);
```

`do...while` checks the condition after each pass, so its body always runs at least once:

```javascript
let attempts = 0;
do {
    attempts++;
} while (attempts < 0);
console.log(attempts);
```

A loop whose condition never becomes false runs forever. The sandbox stops a loop after two seconds and tells you.

## Looping over things with `for...of`

`for...of` goes through each item of anything iterable: arrays, strings, and more. It's the clearest way to visit
every item:

```javascript
for (const letter of 'Emma') {
    console.log(letter);
}

let total = 0;
for (const price of [4.5, 10, 2.25]) {
    total += price;
}
console.log(total);
```

There's also `for...in`, which goes through an object's property names. On arrays it gives the indexes as strings, so
avoid it there.

## `break` and `continue`

`break` leaves a loop straight away; `continue` skips to the next pass:

```javascript
for (let n = 1; n <= 10; n++) {
    if (n % 2 === 0) {
        continue;
    }
    if (n > 7) {
        break;
    }
    console.log(n);
}
```

## Building up a result

Most loops follow the same pattern: start with an empty result, and add to it on each pass:

```javascript
const word = 'mississippi';
let count = 0;
let positions = '';

for (let i = 0; i < word.length; i++) {
    if (word[i] === 's') {
        count++;
        positions += i + ' ';
    }
}

console.log(count, positions.trim());
```

`word[i]` is the character at position `i`, counting from 0.

## Digits with `%` and `Math.floor`

`% 10` gives the last digit of a whole number, and `Math.floor(n / 10)` removes it. Repeating that takes a number
apart:

```javascript
let n = 2026;
let reversed = '';

while (n > 0) {
    reversed += n % 10;
    n = Math.floor(n / 10);
}

console.log(reversed);
```

## Putting it together

A times table, with `continue` to skip a row and `if` to mark one:

```javascript
const table = 7;

for (let i = 1; i <= 6; i++) {
    if (i === 4) {
        continue;
    }

    const product = table * i;
    const note = product > 30 ? ' (big)' : '';
    console.log(`${i} × ${table} = ${product}${note}`);
}
```

Next, try the quiz to check your understanding, then solve the challenges.
