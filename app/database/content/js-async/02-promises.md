## A value that arrives later

A **promise** is an object standing for a result that isn't ready yet. It starts **pending**, and later becomes
**fulfilled** with a value or **rejected** with an error. Either way it's then **settled**, and never changes again.

`.then(callback)` runs a callback with the value once the promise is fulfilled:

```javascript
const ready = new Promise((resolve) => {
    setTimeout(() => resolve('the tea'), 50);
});

ready.then((drink) => console.log(`Here is ${drink}`));
console.log('Waiting...');
```

## Making promises

`new Promise((resolve, reject) => { ... })` runs its function straight away. Call `resolve(value)` to fulfil the
promise and `reject(error)` to reject it; only the first call counts:

```javascript
function wait(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function divide(a, b) {
    return new Promise((resolve, reject) => {
        if (b === 0) {
            reject(new RangeError('Cannot divide by zero'));
        } else {
            resolve(a / b);
        }
    });
}

wait(30).then(() => console.log('30 ms later'));
divide(10, 4).then((result) => console.log(result));
divide(1, 0).catch((error) => console.log(error.message));
```

`Promise.resolve(value)` and `Promise.reject(error)` make already-settled promises.

## Handling errors

`.catch(callback)` handles a rejection, and `.finally(callback)` runs whatever happened. A rejection with no handler
is reported as an "uncaught (in promise)" error:

```javascript
function loadUser(id) {
    return id > 0 ? Promise.resolve({ id, name: 'Amara' }) : Promise.reject(new Error(`No user ${id}`));
}

loadUser(0)
    .then((user) => console.log(user.name))
    .catch((error) => console.log(`Failed: ${error.message}`))
    .finally(() => console.log('Finished loading'));
```

Throwing inside a `.then()` callback rejects the promise it returns, so `throw` and `reject` behave the same.

## Chaining

`.then()` returns a **new** promise for whatever its callback returns. If the callback returns a promise, the chain
waits for it. That turns nested callbacks into a flat list of steps:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

wait(20, 2)
    .then((n) => n * 10)
    .then((n) => wait(20, n + 1))
    .then((n) => console.log(`Result: ${n}`));
```

An error anywhere in a chain skips the remaining `.then()` steps and goes to the next `.catch()`:

```javascript
Promise.resolve(5)
    .then((n) => {
        if (n < 10) {
            throw new Error('Too small');
        }
        return n;
    })
    .then((n) => console.log('never reached', n))
    .catch((error) => {
        console.log(`Caught: ${error.message}`);
        return 0;
    })
    .then((n) => console.log(`Carrying on with ${n}`));
```

A `.catch()` that returns a value recovers: the chain continues fulfilled.

## A common mistake: forgetting to return

If a `.then()` callback starts a promise but doesn't return it, the chain doesn't wait for it:

```javascript
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
let log = '';

Promise.resolve()
    .then(() => {
        wait(30).then(() => (log += 'slow '));
    })
    .then(() => (log += 'next '));

setTimeout(() => console.log(log), 60);
```

`next` comes first, because nothing told the chain to wait for `slow`. Add `return` before `wait(30)` to fix it.

## Promises are microtasks

Promise callbacks never run straight away, even for an already-fulfilled promise. They run as **microtasks**: after
the current code, but before timers:

```javascript
setTimeout(() => console.log('4: timer'), 0);
Promise.resolve().then(() => console.log('3: then'));
console.log('1: sync');
console.log('2: still sync');
```

## Turning callbacks into promises

A function taking an error-first callback can be wrapped in a promise once, and used with `.then()` from then on:

```javascript
function oldStyleRead(name, callback) {
    setTimeout(() => (name ? callback(null, `contents of ${name}`) : callback(new Error('No name'))), 10);
}

function read(name) {
    return new Promise((resolve, reject) => {
        oldStyleRead(name, (error, text) => (error ? reject(error) : resolve(text)));
    });
}

read('notes.txt').then(console.log);
read('').catch((error) => console.log(error.message));
```

## Putting it together

Making breakfast as a chain, with a step that can fail and a recovery:

```javascript
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function toast(slices) {
    return wait(30).then(() => {
        if (slices > 2) {
            throw new Error('The toaster only takes two slices');
        }
        return `${slices} slices of toast`;
    });
}

toast(2)
    .then((done) => console.log(done))
    .then(() => toast(4))
    .catch((error) => {
        console.log(`Problem: ${error.message}`);
        return toast(2);
    })
    .then((done) => console.log(`Second try: ${done}`))
    .finally(() => console.log('Kitchen closed'));
```

Next, try the quiz to check your understanding, then solve the challenges.
