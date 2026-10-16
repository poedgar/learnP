## Waiting for several promises

Four static methods take an array of promises and give one promise for the group. They differ in what they wait for
and how they treat failures:

| Method | Fulfils when | Rejects when |
|--------|--------------|--------------|
| `Promise.all()` | every promise fulfils (with all the values, in order) | any one rejects (straight away) |
| `Promise.allSettled()` | every promise settles, either way (with a report for each) | never |
| `Promise.race()` | the first promise settles, if it fulfils | the first promise settles, if it rejects |
| `Promise.any()` | the first promise fulfils | every promise rejects |

## `Promise.all`: all or nothing

`Promise.all()` runs everything at once and gives the values in the same order as the promises, whatever order they
finish in:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

const [user, orders, settings] = await Promise.all([wait(30, 'Amara'), wait(10, ['Dune']), wait(20, { dark: true })]);
console.log(user, orders, settings);
```

If any promise rejects, the whole thing rejects with that error, and the other results are lost:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const fail = (ms, message) => new Promise((_, reject) => setTimeout(() => reject(new Error(message)), ms));

try {
    await Promise.all([wait(30, 'a'), fail(10, 'b failed'), wait(20, 'c')]);
} catch (error) {
    console.log(`Gave up: ${error.message}`);
}
```

The other promises keep running; their results are simply ignored. Promises can't be cancelled by `Promise.all()`.

## `Promise.allSettled`: a report for each

When some failures are acceptable, `Promise.allSettled()` waits for everything and describes each outcome as
`{ status: 'fulfilled', value }` or `{ status: 'rejected', reason }`:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const fail = (ms, message) => new Promise((_, reject) => setTimeout(() => reject(new Error(message)), ms));

const results = await Promise.allSettled([wait(10, 'gallery 1'), fail(20, 'gallery 2 is down'), wait(5, 'gallery 3')]);

const loaded = results.filter((r) => r.status === 'fulfilled').map((r) => r.value);
const failed = results.filter((r) => r.status === 'rejected').map((r) => r.reason.message);
console.log(loaded, failed);
```

## `Promise.race`: the first to finish

`Promise.race()` settles the same way as the first promise to settle. Racing a request against a timer is the classic
way to add a timeout:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const timeout = (ms) => new Promise((_, reject) => setTimeout(() => reject(new Error(`Timed out after ${ms} ms`)), ms));

console.log(await Promise.race([wait(10, 'fast answer'), timeout(50)]));

try {
    await Promise.race([wait(100, 'slow answer'), timeout(30)]);
} catch (error) {
    console.log(error.message);
}
```

## `Promise.any`: the first success

`Promise.any()` ignores failures until one promise fulfils. Only if every promise rejects does it reject, with an
`AggregateError` holding all the errors:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const fail = (ms, message) => new Promise((_, reject) => setTimeout(() => reject(new Error(message)), ms));

console.log(await Promise.any([fail(5, 'mirror 1 down'), wait(20, 'mirror 2'), wait(30, 'mirror 3')]));

try {
    await Promise.any([fail(5, 'a'), fail(10, 'b')]);
} catch (error) {
    console.log(error.name, error.errors.map((e) => e.message));
}
```

## Empty arrays

`Promise.all([])` and `Promise.allSettled([])` fulfil straight away with `[]`. `Promise.any([])` rejects at once, and
`Promise.race([])` stays pending forever, so guard against empty lists with those two.

```javascript
console.log(await Promise.all([]), await Promise.allSettled([]));
```

## Starting promises in time

A promise starts its work when it's **created**, not when it's awaited or passed to `Promise.all()`. So create them
together to run them together, and create them one at a time, inside a loop with `await`, to run them in turn:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

const started = performance.now();
const first = wait(40, 'first');
const second = wait(40, 'second');

console.log(await first, await second);
console.log(`Both together took about ${Math.round((performance.now() - started) / 40) * 40} ms`);
```

## Limiting how many run at once

Sending a thousand requests at once can overload a server. A common pattern runs a fixed number of **workers**, each
taking the next item when it finishes one:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

async function mapLimit(items, limit, fn) {
    const results = new Array(items.length);
    let next = 0;

    async function worker() {
        while (next < items.length) {
            const index = next++;
            results[index] = await fn(items[index]);
        }
    }

    await Promise.all(Array.from({ length: Math.min(limit, items.length) }, worker));
    return results;
}

const squares = await mapLimit([1, 2, 3, 4, 5], 2, (n) => wait(10, n * n));
console.log(squares);
```

## Putting it together

A page that needs its main data, would like some extras, and won't wait forever:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const fail = (ms, message) => new Promise((_, reject) => setTimeout(() => reject(new Error(message)), ms));
const timeout = (ms) => new Promise((_, reject) => setTimeout(() => reject(new Error('timed out')), ms));

async function loadPage() {
    const article = await Promise.race([wait(20, 'Article text'), timeout(200)]);
    const extras = await Promise.allSettled([wait(10, 'Comments'), fail(15, 'Ads blocked'), wait(30, 'Related')]);

    return {
        article,
        extras: extras.map((r) => (r.status === 'fulfilled' ? r.value : `(${r.reason.message})`)),
    };
}

console.log(await loadPage());
```

Next, try the quiz to check your understanding, then solve the challenges.
