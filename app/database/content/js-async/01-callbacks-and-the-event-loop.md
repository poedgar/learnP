## Code that finishes later

Most code runs top to bottom, each line finishing before the next starts. But some work takes time: a timer, a network
request, reading a file. JavaScript doesn't wait for it. It asks for the work to be done, carries on, and runs a
**callback** function when the result is ready:

```javascript
console.log('Ordering coffee');

setTimeout(() => {
    console.log('Coffee is ready');
}, 100);

console.log('Reading the paper while waiting');
```

`setTimeout(callback, ms)` runs the callback after at least `ms` milliseconds. Everything after it runs first.

## One thing at a time

JavaScript runs on a single thread: it does one thing at a time. The **call stack** holds the functions running right
now. When something finishes later, its callback joins a **queue**, and the **event loop** takes the next callback from
the queue only once the stack is empty. So even a delay of 0 waits for the current code to finish:

```javascript
setTimeout(() => console.log('timer'), 0);

for (let i = 1; i <= 3; i++) {
    console.log(`working ${i}`);
}

console.log('done');
```

That's also why long-running code is a problem: while a loop runs, no callback can run, and in a browser the page
freezes. The sandbox stops loops that run too long.

## Tasks and microtasks

There are two queues. Timers and events go into the **task** queue. Promise callbacks, which come next lesson, and
`queueMicrotask()` go into the **microtask** queue, which is emptied completely after the current code and before the
next task:

```javascript
console.log('1: start');

setTimeout(() => console.log('4: task (timer)'), 0);
queueMicrotask(() => console.log('3: microtask'));

console.log('2: end of the script');
```

## Repeating timers

`setInterval()` runs a callback repeatedly. Both timer functions return an id that `clearTimeout()` or
`clearInterval()` uses to cancel them:

```javascript
let ticks = 0;

const id = setInterval(() => {
    ticks++;
    console.log(`tick ${ticks}`);
    if (ticks === 3) {
        clearInterval(id);
        console.log('stopped');
    }
}, 50);

const cancelled = setTimeout(() => console.log('never shown'), 10);
clearTimeout(cancelled);
```

## Callbacks deliver results

A function that does slow work can't `return` its result, because the result doesn't exist yet when it returns.
Instead it takes a callback and calls it with the result later:

```javascript
function lookUpPrice(title, callback) {
    const prices = { dune: 999, emma: 799 };
    setTimeout(() => callback(prices[title] ?? null), 50);
}

lookUpPrice('dune', (pence) => {
    console.log(`Dune costs ${pence}p`);
});

console.log('Looking it up...');
```

## Error-first callbacks

Slow work can fail. A widespread convention, from Node.js, is the **error-first callback**: the first argument is an
error, or `null` if all went well, and the result comes second:

```javascript
function divideLater(a, b, callback) {
    setTimeout(() => {
        if (b === 0) {
            callback(new RangeError('Cannot divide by zero'));
        } else {
            callback(null, a / b);
        }
    }, 10);
}

divideLater(10, 4, (error, result) => {
    console.log(error ? error.message : `10 / 4 = ${result}`);
});

divideLater(1, 0, (error, result) => {
    console.log(error ? error.message : result);
});
```

An async function should always call its callback asynchronously, never sometimes straight away and sometimes later,
or callers can't rely on what has run when.

## Callback hell

Steps that depend on each other nest inside each other's callbacks, and the code marches off to the right. Errors must
be checked at every level:

```javascript
function step(name, callback) {
    setTimeout(() => callback(null, name), 10);
}

step('log in', (error, a) => {
    step('load profile', (error, b) => {
        step('load orders', (error, c) => {
            console.log([a, b, c].join(' → '));
        });
    });
});
```

This "pyramid of doom" is what promises and `async`/`await`, coming next, were invented to fix.

## Waiting for several at once

Running several slow tasks at the same time and waiting for all of them needs a counter. Results can arrive in any
order, so store each one at its own position:

```javascript
function fetchLength(word, callback) {
    setTimeout(() => callback(word.length), 60 - word.length * 10);
}

const words = ['a', 'bbb', 'cc'];
const lengths = [];
let finished = 0;

words.forEach((word, i) => {
    fetchLength(word, (length) => {
        lengths[i] = length;
        finished++;
        if (finished === words.length) {
            console.log(lengths);
        }
    });
});
```

## Putting it together

A kettle and toast made at the same time, with breakfast served when both are ready:

```javascript
function boilKettle(callback) {
    setTimeout(() => callback('tea'), 80);
}

function makeToast(callback) {
    setTimeout(() => callback('toast'), 50);
}

const ready = [];
function serve(item) {
    ready.push(item);
    console.log(`${item} is ready`);
    if (ready.length === 2) {
        console.log(`Breakfast: ${ready.join(' and ')}`);
    }
}

boilKettle(serve);
makeToast(serve);
console.log('Both started');
```

Next, try the quiz to check your understanding, then solve the challenges.
