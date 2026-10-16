## Promises that read like ordinary code

An **`async` function** always returns a promise. Inside it, **`await`** pauses the function until a promise settles,
then gives its value, so asynchronous steps can be written one after another, like synchronous code:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

async function makeBreakfast() {
    const tea = await wait(30, 'tea');
    console.log(`${tea} is ready`);
    const toast = await wait(30, 'toast');
    console.log(`${toast} is ready`);
    return `${tea} and ${toast}`;
}

makeBreakfast().then((meal) => console.log(`Breakfast: ${meal}`));
console.log('The kitchen is busy');
```

While one `async` function waits, other code keeps running: `await` pauses only that function, not the program.

## What an `async` function returns

Whatever an `async` function returns becomes the value of its promise; whatever it throws becomes the rejection:

```javascript
async function answer() {
    return 42;
}

async function fail() {
    throw new Error('nope');
}

console.log(answer() instanceof Promise);
answer().then((n) => console.log(n));
fail().catch((error) => console.log(error.message));
```

## Errors with `try`/`catch`

An `await`ed promise that rejects throws at the `await`, so ordinary `try`/`catch`/`finally` handles it:

```javascript
const load = (ok) => new Promise((resolve, reject) => setTimeout(() => (ok ? resolve('data') : reject(new Error('offline'))), 20));

async function show(ok) {
    try {
        const data = await load(ok);
        console.log(`Loaded ${data}`);
    } catch (error) {
        console.log(`Could not load: ${error.message}`);
    } finally {
        console.log('Spinner hidden');
    }
}

await show(true);
await show(false);
```

## Top-level `await`

In modules, and in this sandbox, `await` also works outside any function, at the top level of the code, as the last
example did. In ordinary scripts and older environments it's only allowed inside `async` functions.

## One after another, or at the same time?

Each `await` waits before the next line starts. That's right when a step needs the previous result, but slow when the
steps are independent:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

let started = performance.now();
const a = await wait(50, 'a');
const b = await wait(50, 'b');
console.log(`In turn: ${a}${b} after about ${Math.round((performance.now() - started) / 50) * 50} ms`);

started = performance.now();
const [c, d] = await Promise.all([wait(50, 'c'), wait(50, 'd')]);
console.log(`Together: ${c}${d} after about ${Math.round((performance.now() - started) / 50) * 50} ms`);
```

Starting both promises first, then awaiting them together with `Promise.all()`, runs them at the same time. There's
more about `Promise.all()` in the next lesson.

## Loops with `await`

`await` inside a `for...of` loop handles one item at a time, in order. `forEach()` doesn't wait for an `async`
callback, so it can't be used for that:

```javascript
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function saveAll(items) {
    for (const item of items) {
        await wait(10);
        console.log(`saved ${item}`);
    }
    console.log('all saved');
}

await saveAll(['a', 'b', 'c']);
```

```javascript
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

['a', 'b'].forEach(async (item) => {
    await wait(10);
    console.log(`saved ${item}`);
});
console.log('forEach has already returned');
```

## Forgetting `await`

Without `await`, you get the promise rather than its value. It's an object, so it's always truthy, and calling methods
for the value on it fails:

```javascript
async function price() {
    return 999;
}

const withoutAwait = price();
const withAwait = await price();

console.log(typeof withoutAwait, typeof withAwait);
console.log(withoutAwait instanceof Promise);
```

## Async arrow functions and methods

Arrow functions and methods can be `async` too:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

const double = async (n) => (await wait(10, n)) * 2;

class Shelf {
    books = ['Dune'];

    async add(title) {
        await wait(10);
        this.books.push(title);
        return this.books.length;
    }
}

console.log(await double(21), await new Shelf().add('Emma'));
```

## Putting it together

Loading a user, then their orders, with a fallback for errors and the independent requests in parallel:

```javascript
const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
const fetchUser = (id) => (id === 1 ? wait(20, { id, name: 'Amara' }) : Promise.reject(new Error('No such user')));
const fetchOrders = (id) => wait(30, [{ pence: 999 }, { pence: 450 }]);
const fetchWishlist = (id) => wait(30, ['Circe']);

async function summary(id) {
    try {
        const user = await fetchUser(id);
        const [orders, wishlist] = await Promise.all([fetchOrders(id), fetchWishlist(id)]);
        const spent = orders.reduce((sum, o) => sum + o.pence, 0);
        return `${user.name}: ${orders.length} orders, £${(spent / 100).toFixed(2)}, wants ${wishlist.join(', ')}`;
    } catch (error) {
        return `Unavailable (${error.message})`;
    }
}

console.log(await summary(1));
console.log(await summary(2));
```

Next, try the quiz to check your understanding, then solve the challenges.
