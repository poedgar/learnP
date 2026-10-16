## Naming a piece of work

A **function** packages code under a name, so you can run it whenever you need it, with different inputs each time.
**Parameters** are the inputs; `return` sends a value back:

```javascript
function area(width, height) {
    return width * height;
}

console.log(area(3, 4), area(10, 2));
```

A function without a `return` gives back `undefined`. `return` also ends the function straight away, which makes
early exits easy:

```javascript
function describe(score) {
    if (score < 0) {
        return 'invalid';
    }
    return score >= 50 ? 'pass' : 'fail';
}

console.log(describe(-3), describe(72), describe(10));
```

## Three ways to write a function

A function can also be stored in a variable, as a **function expression** or, more often, as an **arrow function**.
Arrows with a single expression return it without needing `return` or braces:

```javascript
const double = function (n) {
    return n * 2;
};

const triple = (n) => n * 3;

const describe = (name, age) => {
    const decade = Math.floor(age / 10) * 10;
    return `${name} is in their ${decade}s`;
};

console.log(double(4), triple(4), describe('Amara', 34));
```

To return an object from a one-line arrow, wrap it in brackets: `() => ({ ok: true })`.

## Default values and missing arguments

A missing argument is `undefined`. A **default value** replaces it:

```javascript
function greet(name, greeting = 'Hello') {
    return `${greeting}, ${name}!`;
}

console.log(greet('Ben'));
console.log(greet('Ben', 'Welcome'));
console.log(greet());
```

## Any number of arguments

A **rest parameter**, `...name`, collects the remaining arguments into an array. The **spread** syntax does the
opposite, turning an array into separate arguments:

```javascript
function largest(...numbers) {
    return numbers.length === 0 ? null : Math.max(...numbers);
}

const scores = [12, 40, 7];

console.log(largest(3, 9, 2), largest(), largest(...scores));
```

## Scope: where variables can be seen

Variables declared inside a function, or inside any `{ }` block, exist only there. Code inside can see variables from
outside, but not the other way round:

```javascript
const site = 'learnP';

function visit() {
    const page = 'home';
    return `${site}/${page}`;
}

console.log(visit());
console.log(typeof page);
```

A function **declaration** (`function name() {}`) can be called before the line where it's written, because
declarations are set up first. `const` and `let` variables can't be used before their line.

## Closures

A function remembers the variables around it when it was created, even after the outer function has finished. That's
a **closure**, and it lets a function keep private state:

```javascript
function makeAdder(amount) {
    return (n) => n + amount;
}

const addTen = makeAdder(10);
const addOne = makeAdder(1);

console.log(addTen(5), addOne(5));
```

Each call to `makeAdder()` creates a new `amount`, so `addTen` and `addOne` don't interfere.

```javascript
function makeTally() {
    let count = 0;
    return () => {
        count += 1;
        return count;
    };
}

const tally = makeTally();
tally();
tally();
console.log(tally());
```

Nothing outside can reach `count`; only the returned function can.

## Functions as values

Functions are values: you can pass them to other functions and return them. A function passed in to be called later
is a **callback**:

```javascript
function applyToEach(numbers, transform) {
    let result = '';
    for (const n of numbers) {
        result += transform(n) + ' ';
    }
    return result.trim();
}

console.log(applyToEach([1, 2, 3], (n) => n * n));
console.log(applyToEach([1, 2, 3], String));
```

## Recursion

A function can call itself. Each call must get closer to a **base case** that returns without calling again,
otherwise it never stops:

```javascript
function countdown(n) {
    if (n === 0) {
        return 'lift off';
    }
    return `${n} ${countdown(n - 1)}`;
}

console.log(countdown(3));
```

## Putting it together

A small price calculator built from functions, with a default, a rest parameter and a closure:

```javascript
const withVat = (pence, rate = 0.2) => Math.round(pence * (1 + rate));

function total(...prices) {
    let sum = 0;
    for (const price of prices) {
        sum += price;
    }
    return sum;
}

function makeFormatter(symbol) {
    return (pence) => `${symbol}${(pence / 100).toFixed(2)}`;
}

const pounds = makeFormatter('£');
console.log(pounds(withVat(total(999, 1250, 500))));
```

Next, try the quiz to check your understanding, then solve the challenges.
