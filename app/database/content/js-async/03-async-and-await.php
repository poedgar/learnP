<?php

// Lesson 3 of the Async JavaScript course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message, and may await. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput().

return [
    'slug' => 'async-and-await',
    'title' => 'async and await',
    'summary' => 'Write asynchronous code that reads top to bottom: async functions and await, errors with try and catch, running steps in turn or at the same time, and loops that wait.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

        async function countDown(from) {
            for (let n = from; n > 0; n--) {
                console.log(n);
                await wait(200);
            }
            return 'Lift off!';
        }

        console.log(await countDown(3));
        JS,

    'quiz' => [
        [
            'question' => 'What does an `async` function always return?',
            'options' => ['Its return value', 'A promise', '`undefined`', 'A callback'],
            'correct_option' => 1,
            'explanation' => 'The return value becomes the promise\'s value; a throw becomes its rejection.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                async function f() {
                    console.log('b');
                    await null;
                    console.log('d');
                }
                console.log('a');
                f();
                console.log('c');
                JS,
            'options' => ['`a b c d`', '`a c b d`', '`a b d c`', '`b a c d`'],
            'correct_option' => 0,
            'explanation' => 'An `async` function runs straight away up to its first `await`; the rest continues after the current code.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                async function load() {
                    throw new Error('offline');
                }
                async function main() {
                    try {
                        await load();
                        return 'loaded';
                    } catch (error) {
                        return `failed: ${error.message}`;
                    }
                }
                main().then(console.log);
                JS,
            'options' => ['`failed: offline`', '`loaded`', 'An uncaught error', '`undefined`'],
            'correct_option' => 0,
            'explanation' => 'A rejected promise throws at the `await`, where the `try`/`catch` handles it.',
        ],
        [
            'question' => 'Two independent requests each take 1 second. How long does `const a = await get1(); const b = await get2();` take?',
            'options' => [
                'About 1 second',
                'About 2 seconds: the second request only starts after the first has finished',
                'About 0 seconds',
                'It depends on the order',
            ],
            'correct_option' => 1,
            'explanation' => 'Start both first and `await Promise.all([get1(), get2()])` to take about 1 second.',
        ],
        [
            'question' => 'Why can\'t `items.forEach(async (item) => { await save(item); })` be used to save items one at a time?',
            'options' => [
                'It can',
                '`forEach()` doesn\'t wait for the promises its callback returns, so all the saves start at once and nothing waits for them',
                '`async` arrow functions are not allowed in `forEach()`',
                'It saves only the first item',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `for (const item of items) { await save(item); }`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                async function total() {
                    return 10;
                }
                const t = total();
                console.log(t > 5, typeof t);
                JS,
            'options' => ['`false object`', '`true number`', '`true object`', '`false number`'],
            'correct_option' => 0,
            'explanation' => 'Without `await`, `t` is a promise, not 10. Comparing an object with a number gives `false`.',
        ],
        [
            'question' => 'Where can `await` be used?',
            'options' => [
                'Anywhere',
                'Inside `async` functions, and at the top level of modules (and this sandbox)',
                'Only inside `try` blocks',
                'Only inside `.then()` callbacks',
            ],
            'correct_option' => 1,
            'explanation' => 'In an ordinary function, `await` is a syntax error.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const wait = (ms, v) => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                const results = [];
                for (const [ms, v] of [[30, 'slow'], [5, 'fast']]) {
                    results.push(await wait(ms, v));
                }
                console.log(results.join(' '));
                JS,
            'options' => ['`slow fast`', '`fast slow`', '`slow`', '`fast`'],
            'correct_option' => 0,
            'explanation' => 'Each pass of the loop waits for its promise, so the order of the list is kept.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Rewrite with await',
            'prompt' => "Rewrite `cityOf(id)` as an `async` function using `await` and `try`/`catch` instead of `.then()` and `.catch()`. It must still give the user's city, or `'unknown'` if either step fails.",
            'starter_code' => <<<'JS'
                const users = { 1: { name: 'Amara', addressId: 10 }, 2: { name: 'Ben', addressId: 99 } };
                const addresses = { 10: { city: 'Leeds' } };
                const later = (value, error) => new Promise((resolve, reject) => setTimeout(() => (value ? resolve(value) : reject(new Error(error))), 10));
                const fetchUser = (id) => later(users[id], 'No such user');
                const fetchAddress = (user) => later(addresses[user.addressId], 'No address');

                function cityOf(id) {
                    return fetchUser(id)
                        .then((user) => fetchAddress(user))
                        .then((address) => address.city)
                        .catch(() => 'unknown');
                }
                JS,
            'solution' => <<<'JS'
                const users = { 1: { name: 'Amara', addressId: 10 }, 2: { name: 'Ben', addressId: 99 } };
                const addresses = { 10: { city: 'Leeds' } };
                const later = (value, error) => new Promise((resolve, reject) => setTimeout(() => (value ? resolve(value) : reject(new Error(error))), 10));
                const fetchUser = (id) => later(users[id], 'No such user');
                const fetchAddress = (user) => later(addresses[user.addressId], 'No address');

                async function cityOf(id) {
                    try {
                        const user = await fetchUser(id);
                        const address = await fetchAddress(user);
                        return address.city;
                    } catch {
                        return 'unknown';
                    }
                }
                JS,
            'hint' => 'Each `.then()` becomes a line with `await`; the `.catch()` becomes a `catch` block returning `\'unknown\'`.',
            'checks' => [
                ['label' => 'Same results', 'code' => "return expectSame([await cityOf(1), await cityOf(2), await cityOf(3)], ['Leeds', 'unknown', 'unknown']);"],
                ['label' => 'Uses async and await, not then', 'code' => <<<'JS'
                    const code = learnerCode();
                    const fn = code.slice(code.indexOf('cityOf'));
                    if (!/async\s+function\s+cityOf/.test(code)) return 'Make cityOf an async function.';
                    return /\.then\(|\.catch\(/.test(fn) ? 'Replace .then() and .catch() with await and try/catch.' : true;
                    JS],
            ],
        ],
        [
            'title' => 'Load in parallel',
            'prompt' => 'Write `async function totalPrice(ids)`, which loads every product with the provided `fetchProduct(id)` (each takes 50 ms) and returns the sum of their `pence`. Load them all at the same time, so five products take about 50 ms, not 250.',
            'starter_code' => <<<'JS'
                const fetchProduct = (id) => new Promise((resolve) => setTimeout(() => resolve({ id, pence: id * 100 }), 50));

                async function totalPrice(ids) {
                    let total = 0;
                    for (const id of ids) {
                        const product = await fetchProduct(id);
                        total += product.pence;
                    }
                    return total;
                }
                JS,
            'solution' => <<<'JS'
                const fetchProduct = (id) => new Promise((resolve) => setTimeout(() => resolve({ id, pence: id * 100 }), 50));

                async function totalPrice(ids) {
                    const products = await Promise.all(ids.map((id) => fetchProduct(id)));
                    return products.reduce((sum, product) => sum + product.pence, 0);
                }
                JS,
            'hint' => 'Start every request with `ids.map(fetchProduct)`, then `await Promise.all(...)` the array of promises.',
            'checks' => [
                ['label' => 'The total', 'code' => 'return expectSame([await totalPrice([1, 2, 3]), await totalPrice([])], [600, 0]);'],
                ['label' => 'Loads at the same time', 'code' => <<<'JS'
                    const started = performance.now();
                    await totalPrice([1, 2, 3, 4, 5]);
                    const elapsed = performance.now() - started;
                    return elapsed < 150 ? true : `Five products took ${Math.round(elapsed)} ms; load them at the same time.`;
                    JS],
            ],
        ],
        [
            'title' => 'Save in order',
            'prompt' => 'Write `async function saveAll(items, save)`, where `save(item)` returns a promise. Save the items one at a time, in order, each only after the previous save has finished, and return the number saved.',
            'starter_code' => <<<'JS'
                async function saveAll(items, save) {
                    items.forEach(async (item) => {
                        await save(item);
                    });
                    return items.length;
                }
                JS,
            'solution' => <<<'JS'
                async function saveAll(items, save) {
                    for (const item of items) {
                        await save(item);
                    }
                    return items.length;
                }
                JS,
            'hint' => '`forEach()` doesn\'t wait. A `for...of` loop with `await` inside does.',
            'checks' => [
                ['label' => 'Saves in order, one at a time', 'code' => <<<'JS'
                    const saved = [];
                    let running = 0;
                    let most = 0;
                    const save = (item) => {
                        running++;
                        most = Math.max(most, running);
                        return new Promise((resolve) => setTimeout(() => {
                            running--;
                            saved.push(item);
                            resolve();
                        }, item === 'a' ? 30 : 5));
                    };
                    const count = await saveAll(['a', 'b', 'c'], save);
                    return expectSame([count, saved, most], [3, ['a', 'b', 'c'], 1]);
                    JS],
            ],
        ],
        [
            'title' => 'The first that works',
            'prompt' => "Write `async function firstAvailable(sources)`. Each source is a function returning a promise. Try them in order and return the first fulfilled value. If all fail, throw an `Error` with the message `'Nothing available'`.",
            'starter_code' => <<<'JS'
                async function firstAvailable(sources) {
                    return sources[0]();
                }
                JS,
            'solution' => <<<'JS'
                async function firstAvailable(sources) {
                    for (const source of sources) {
                        try {
                            return await source();
                        } catch {
                            // Try the next one.
                        }
                    }
                    throw new Error('Nothing available');
                }
                JS,
            'hint' => 'Loop over the sources with `try { return await source(); } catch { }`. Note `return await`: without `await`, the `try` wouldn\'t catch the rejection.',
            'checks' => [
                ['label' => 'Skips failures', 'code' => <<<'JS'
                    const fail = () => Promise.reject(new Error('down'));
                    const ok = (v) => () => new Promise((resolve) => setTimeout(() => resolve(v), 5));
                    return expectSame(await firstAvailable([fail, fail, ok('mirror 3'), ok('mirror 4')]), 'mirror 3');
                    JS],
                ['label' => 'Stops at the first success', 'code' => <<<'JS'
                    let calls = 0;
                    const source = (v) => () => {
                        calls++;
                        return Promise.resolve(v);
                    };
                    await firstAvailable([source('a'), source('b')]);
                    return expectSame(calls, 1);
                    JS],
                ['label' => 'All failing', 'code' => "return expectThrows(() => firstAvailable([() => Promise.reject(new Error('x'))]), Error, 'Nothing available');"],
            ],
        ],
        [
            'title' => 'Sleep and repeat',
            'prompt' => 'Write `async function poll(check, intervalMs, maxTries)`. Call `check()` (which returns a promise of a boolean); while it gives `false`, wait `intervalMs` and call it again, up to `maxTries` calls. Return `true` as soon as it gives `true`, or `false` after `maxTries` failed calls.',
            'starter_code' => <<<'JS'
                async function poll(check, intervalMs, maxTries) {
                    return check();
                }
                JS,
            'solution' => <<<'JS'
                const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

                async function poll(check, intervalMs, maxTries) {
                    for (let tries = 1; tries <= maxTries; tries++) {
                        if (await check()) {
                            return true;
                        }
                        if (tries < maxTries) {
                            await sleep(intervalMs);
                        }
                    }
                    return false;
                }
                JS,
            'hint' => 'A `sleep(ms)` helper returning a promise, and a loop that `await`s `check()` then `sleep()`s.',
            'checks' => [
                ['label' => 'Becomes ready', 'code' => <<<'JS'
                    let calls = 0;
                    const ready = await poll(async () => ++calls === 3, 10, 5);
                    return expectSame([ready, calls], [true, 3]);
                    JS],
                ['label' => 'Gives up', 'code' => <<<'JS'
                    let calls = 0;
                    const started = performance.now();
                    const ready = await poll(async () => {
                        calls++;
                        return false;
                    }, 20, 3);
                    const elapsed = performance.now() - started;
                    if (elapsed < 35) return `It gave up after ${Math.round(elapsed)} ms; wait between tries.`;
                    return expectSame([ready, calls], [false, 3]);
                    JS],
            ],
        ],
        [
            'title' => 'An async method',
            'prompt' => 'Write a class `Cache` whose constructor takes a `load(key)` function returning a promise, and an `async get(key)` method. The first `get()` of a key loads it; later `get()`s of that key give the stored value without loading again.',
            'starter_code' => <<<'JS'
                class Cache {
                    constructor(load) {
                        this.load = load;
                    }

                    get(key) {
                        return this.load(key);
                    }
                }
                JS,
            'solution' => <<<'JS'
                class Cache {
                    #load;
                    #values = new Map();

                    constructor(load) {
                        this.#load = load;
                    }

                    async get(key) {
                        if (!this.#values.has(key)) {
                            this.#values.set(key, await this.#load(key));
                        }
                        return this.#values.get(key);
                    }
                }
                JS,
            'hint' => 'Keep a `Map` of loaded values; in `get()`, `await` the loader only when the key isn\'t there yet.',
            'checks' => [
                ['label' => 'Loads once per key', 'code' => <<<'JS'
                    const loads = [];
                    const cache = new Cache(async (key) => {
                        loads.push(key);
                        return key.toUpperCase();
                    });
                    const values = [await cache.get('a'), await cache.get('b'), await cache.get('a')];
                    return expectSame([values, loads], [['A', 'B', 'A'], ['a', 'b']]);
                    JS],
                ['label' => 'get() returns a promise', 'code' => "const cache = new Cache(async () => 1);\nreturn cache.get('x') instanceof Promise ? true : 'Make get() async.';"],
            ],
        ],
    ],
];
