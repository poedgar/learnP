## Generators: values on demand

A **generator function**, written `function*`, can pause. Each `yield` hands out a value and stops until the next
value is asked for. Calling it gives an iterator, which `for...of` and spread can use:

```javascript
function* countUpTo(limit) {
    for (let n = 1; n <= limit; n++) {
        yield n;
    }
}

for (const n of countUpTo(3)) {
    console.log(n);
}
console.log([...countUpTo(5)]);
```

Values are produced only when needed, so a generator can even be endless, as long as the loop that uses it stops.

## Async generators and `for await`

An **async generator**, `async function*`, can `await` between values. `for await...of` goes through it, waiting for
each value. It suits data that arrives in pieces, such as pages of results from an API:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const pages = { 1: { items: ['a', 'b'], next: 2 }, 2: { items: ['c'], next: 3 }, 3: { items: ['d', 'e'], next: null } };

async function* allItems() {
    let page = 1;
    while (page !== null) {
        const data = await wait(10, pages[page]);
        yield* data.items;
        page = data.next;
    }
}

for await (const item of allItems()) {
    console.log(item);
}
```

`yield*` hands out every value of another iterable in turn. Breaking out of a `for await` loop stops the generator, so
no more pages are fetched.

## Cancelling with `AbortController`

Promises can't be cancelled from outside, so the convention is to pass in an **`AbortSignal`**. An `AbortController`
creates one and `abort()` triggers it; the work checks `signal.aborted`, or listens for its `abort` event, and stops:

```javascript
function sleep(ms, signal) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(resolve, ms);
        signal?.addEventListener('abort', () => {
            clearTimeout(timer);
            reject(new Error('Cancelled'));
        });
    });
}

const controller = new AbortController();
setTimeout(() => controller.abort(), 30);

try {
    await sleep(1000, controller.signal);
    console.log('slept');
} catch (error) {
    console.log(error.message, controller.signal.aborted);
}
```

`fetch()` and many other browser APIs take a `signal` option in the same way.

## Retrying with backoff

When something fails because a service is busy, trying again at once usually fails again. **Exponential backoff**
waits longer before each retry: 100 ms, then 200, then 400:

```javascript
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

let calls = 0;
async function busyService() {
    calls++;
    if (calls < 3) {
        throw new Error('busy');
    }
    return 'ok';
}

for (let attempt = 1, delay = 20; ; attempt++, delay *= 2) {
    try {
        console.log(`attempt ${attempt}: ${await busyService()}`);
        break;
    } catch (error) {
        console.log(`attempt ${attempt}: ${error.message}, waiting ${delay} ms`);
        await sleep(delay);
    }
}
```

Real code also stops after a maximum number of attempts, and often adds a little randomness so many clients don't
retry at the same moment.

## Throttling and debouncing

Events like scrolling, typing or resizing can fire hundreds of times a second. Two techniques tame them:

- **Debounce**: wait until the events stop for a while, then act once (search as you type).
- **Throttle**: act at most once in each period, ignoring the rest (update a position while scrolling).

```javascript
function throttleLog(ms) {
    let last = -Infinity;
    return (message) => {
        const now = performance.now();
        if (now - last >= ms) {
            last = now;
            console.log(message);
        }
    };
}

const log = throttleLog(50);
for (let i = 1; i <= 5; i++) {
    log(`event ${i}`);
}
setTimeout(() => log('event 6, later'), 70);
```

## One at a time: a queue

Sometimes async jobs must not overlap, such as writes to the same file. Chaining each new job onto the previous one's
promise makes them run strictly in turn, even when they're started at the same moment:

```javascript
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

let last = Promise.resolve();
function exclusive(job) {
    const result = last.then(job);
    last = result.catch(() => {});
    return result;
}

const write = (text, ms) => async () => {
    await sleep(ms);
    console.log(`wrote ${text}`);
    return text;
};

const results = await Promise.all([exclusive(write('a', 30)), exclusive(write('b', 5)), exclusive(write('c', 10))]);
console.log(results);
```

`last` swallows errors with `.catch()` so one failed job doesn't block the ones after it; each caller still gets its
own job's error.

## Events as promises

A one-off event can be turned into a promise, so code can `await` it. Here an `EventTarget`, the building block of DOM
events, sends a custom event:

```javascript
function once(target, type) {
    return new Promise((resolve) => target.addEventListener(type, resolve, { once: true }));
}

const door = new EventTarget();
setTimeout(() => door.dispatchEvent(new Event('open')), 20);

const event = await once(door, 'open');
console.log(`The door is ${event.type}`);
```

## Putting it together

Streaming search results with a time budget: an async generator produces results, and a signal stops it early:

```javascript
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function* search(term, signal) {
    for (const shelf of ['A', 'B', 'C', 'D', 'E']) {
        if (signal.aborted) {
            return;
        }
        await sleep(20);
        yield `${term} on shelf ${shelf}`;
    }
}

const controller = new AbortController();
setTimeout(() => controller.abort(), 70);

const found = [];
for await (const result of search('Dune', controller.signal)) {
    found.push(result);
}
console.log(found);
```

Next, try the quiz to check your understanding, then solve the challenges.
