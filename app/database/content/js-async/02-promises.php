<?php

// Lesson 2 of the Async JavaScript course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message, and may await. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput().

return [
    'slug' => 'promises',
    'title' => 'Promises',
    'summary' => 'Represent results that arrive later with promises: create them, handle values and errors with then, catch and finally, chain steps, and wrap callback functions.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));

        wait(50, 'first')
            .then((value) => {
                console.log(value);
                return wait(50, 'second');
            })
            .then((value) => console.log(value))
            .finally(() => console.log('done'));

        console.log('started');
        JS,

    'quiz' => [
        [
            'question' => 'What are the three states of a promise?',
            'options' => [
                'Started, running, finished',
                'Pending, then either fulfilled (with a value) or rejected (with an error)',
                'Sync, async, settled',
                'Open, closed, cancelled',
            ],
            'correct_option' => 1,
            'explanation' => 'Once fulfilled or rejected, a promise is settled and never changes.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const p = new Promise((resolve, reject) => {
                    resolve('yes');
                    reject(new Error('no'));
                    resolve('again');
                });
                p.then((v) => console.log(v)).catch((e) => console.log(e.message));
                JS,
            'options' => ['`yes`', '`no`', '`again`', '`yes no`'],
            'correct_option' => 0,
            'explanation' => 'Only the first `resolve` or `reject` counts.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                Promise.resolve(2)
                    .then((n) => n * 3)
                    .then((n) => n + 1)
                    .then((n) => console.log(n));
                JS,
            'options' => ['`7`', '`2`', '`6`', '`9`'],
            'correct_option' => 0,
            'explanation' => 'Each `.then()` passes on what its callback returns: 2 × 3 = 6, then 7.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                Promise.resolve(1)
                    .then(() => {
                        throw new Error('broken');
                    })
                    .then(() => console.log('A'))
                    .catch((e) => console.log('B', e.message))
                    .then(() => console.log('C'));
                JS,
            'options' => ['`B broken C`', '`A B broken C`', '`B broken`', '`A C`'],
            'correct_option' => 0,
            'explanation' => 'The error skips to `.catch()`, which handles it, so the chain carries on to `C`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                setTimeout(() => console.log('timeout'), 0);
                Promise.resolve().then(() => console.log('promise'));
                console.log('script');
                JS,
            'options' => ['`script promise timeout`', '`script timeout promise`', '`promise script timeout`', '`timeout promise script`'],
            'correct_option' => 0,
            'explanation' => 'Promise callbacks are microtasks: they run after the script but before the next timer.',
        ],
        [
            'question' => 'A `.then()` callback calls `save()`, which returns a promise, but doesn\'t `return` it. What happens?',
            'options' => [
                'The chain waits for the save anyway',
                'The chain doesn\'t wait: the next step runs straight away, and a failed save isn\'t caught by the chain',
                'It\'s a syntax error',
                'The save never starts',
            ],
            'correct_option' => 1,
            'explanation' => 'Always return promises from `.then()` callbacks.',
        ],
        [
            'question' => 'When does the function passed to `new Promise(...)` run?',
            'options' => ['Straight away, synchronously', 'When `.then()` is called', 'After a timer', 'Never, unless awaited'],
            'correct_option' => 0,
            'explanation' => 'The executor runs immediately; only the callbacks attached with `.then()` wait.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                Promise.reject(new Error('lost'))
                    .finally(() => console.log('tidy up'))
                    .catch((e) => console.log('caught', e.message));
                JS,
            'options' => ['`tidy up caught lost`', '`caught lost tidy up`', '`tidy up`', '`caught lost`'],
            'correct_option' => 0,
            'explanation' => '`finally()` runs on success or failure, and passes the rejection on to the `catch()` after it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A promise to wait',
            'prompt' => 'Write `delay(ms, value)`, which returns a promise that is fulfilled with `value` after `ms` milliseconds.',
            'starter_code' => <<<'JS'
                function delay(ms, value) {
                    setTimeout(() => value, ms);
                }
                JS,
            'solution' => <<<'JS'
                function delay(ms, value) {
                    return new Promise((resolve) => setTimeout(() => resolve(value), ms));
                }
                JS,
            'hint' => 'Return `new Promise((resolve) => ...)` and call `resolve(value)` from the timer.',
            'checks' => [
                ['label' => 'Fulfils with the value', 'code' => "const result = delay(20, 'ok');\nreturn result instanceof Promise ? expectSame(await result, 'ok') : 'Return a promise.';"],
                ['label' => 'After the delay', 'code' => <<<'JS'
                    const started = performance.now();
                    await delay(50);
                    const elapsed = performance.now() - started;
                    return elapsed >= 45 ? true : `It was fulfilled after ${Math.round(elapsed)} ms, not 50.`;
                    JS],
            ],
        ],
        [
            'title' => 'Promisify',
            'prompt' => 'Write `promisify(fn)`. `fn` takes some arguments and an error-first callback last. `promisify` returns a function that takes the same arguments (without the callback) and returns a promise, rejected with the error or fulfilled with the result.',
            'starter_code' => <<<'JS'
                function promisify(fn) {
                    return (...args) => Promise.resolve(fn(...args));
                }
                JS,
            'solution' => <<<'JS'
                function promisify(fn) {
                    return (...args) =>
                        new Promise((resolve, reject) => {
                            fn(...args, (error, result) => (error ? reject(error) : resolve(result)));
                        });
                }
                JS,
            'hint' => 'Return `(...args) => new Promise((resolve, reject) => fn(...args, callback))`, where the callback rejects or resolves.',
            'checks' => [
                ['label' => 'Fulfils with the result', 'code' => <<<'JS'
                    const add = (a, b, callback) => setTimeout(() => callback(null, a + b), 5);
                    const addAsync = promisify(add);
                    return expectSame(await addAsync(2, 3), 5);
                    JS],
                ['label' => 'Rejects with the error', 'code' => <<<'JS'
                    const fail = (callback) => setTimeout(() => callback(new RangeError('nope')), 5);
                    return expectThrows(() => promisify(fail)(), RangeError, 'nope');
                    JS],
            ],
        ],
        [
            'title' => 'A chain with a fallback',
            'prompt' => "Using the provided `fetchUser(id)` and `fetchAddress(user)`, write `cityOf(id)` with `.then()` and `.catch()` (no `async`/`await`). It returns a promise of the user's city, or `'unknown'` if either step fails.",
            'starter_code' => <<<'JS'
                const users = { 1: { id: 1, name: 'Amara', addressId: 10 }, 2: { id: 2, name: 'Ben', addressId: 99 } };
                const addresses = { 10: { city: 'Leeds' } };

                const fetchUser = (id) =>
                    new Promise((resolve, reject) => setTimeout(() => (users[id] ? resolve(users[id]) : reject(new Error('No such user'))), 10));
                const fetchAddress = (user) =>
                    new Promise((resolve, reject) =>
                        setTimeout(() => (addresses[user.addressId] ? resolve(addresses[user.addressId]) : reject(new Error('No address'))), 10),
                    );

                function cityOf(id) {
                    fetchUser(id).then((user) => fetchAddress(user));
                }
                JS,
            'solution' => <<<'JS'
                const users = { 1: { id: 1, name: 'Amara', addressId: 10 }, 2: { id: 2, name: 'Ben', addressId: 99 } };
                const addresses = { 10: { city: 'Leeds' } };

                const fetchUser = (id) =>
                    new Promise((resolve, reject) => setTimeout(() => (users[id] ? resolve(users[id]) : reject(new Error('No such user'))), 10));
                const fetchAddress = (user) =>
                    new Promise((resolve, reject) =>
                        setTimeout(() => (addresses[user.addressId] ? resolve(addresses[user.addressId]) : reject(new Error('No address'))), 10),
                    );

                function cityOf(id) {
                    return fetchUser(id)
                        .then((user) => fetchAddress(user))
                        .then((address) => address.city)
                        .catch(() => 'unknown');
                }
                JS,
            'hint' => 'Return the whole chain: `fetchUser(id).then(fetchAddress).then((a) => a.city).catch(() => \'unknown\')`.',
            'checks' => [
                ['label' => 'A city', 'code' => "return expectSame(await cityOf(1), 'Leeds');"],
                ['label' => 'Failures become unknown', 'code' => "return expectSame([await cityOf(2), await cityOf(3)], ['unknown', 'unknown']);"],
                ['label' => 'Uses then and catch', 'code' => "const code = learnerCode();\nreturn /\\basync\\b|\\bawait\\b/.test(code) ? 'Solve this one with .then() and .catch(), without async or await.' : true;"],
            ],
        ],
        [
            'title' => 'Give up after a while',
            'prompt' => "Write `withTimeout(promise, ms)`, returning a promise that settles like `promise` if it settles within `ms` milliseconds, and otherwise rejects with an `Error` whose message is `'Timed out'`. Don't use `Promise.race()` (that's next lesson): use `new Promise` and both outcomes.",
            'starter_code' => <<<'JS'
                function withTimeout(promise, ms) {
                    return promise;
                }
                JS,
            'solution' => <<<'JS'
                function withTimeout(promise, ms) {
                    return new Promise((resolve, reject) => {
                        const timer = setTimeout(() => reject(new Error('Timed out')), ms);
                        promise.then(
                            (value) => {
                                clearTimeout(timer);
                                resolve(value);
                            },
                            (error) => {
                                clearTimeout(timer);
                                reject(error);
                            },
                        );
                    });
                }
                JS,
            'hint' => 'In a new promise, start a timer that rejects, and pass `promise`\'s value or error on with `promise.then(resolve, reject)`. Only the first one counts.',
            'checks' => [
                ['label' => 'Quick enough', 'code' => "const quick = new Promise((resolve) => setTimeout(() => resolve('fast'), 10));\nreturn expectSame(await withTimeout(quick, 100), 'fast');"],
                ['label' => 'Too slow', 'code' => "const slow = new Promise((resolve) => setTimeout(() => resolve('slow'), 200));\nreturn expectThrows(() => withTimeout(slow, 30), Error, 'Timed out');"],
                ['label' => 'Passes failures on', 'code' => "const broken = new Promise((resolve, reject) => setTimeout(() => reject(new TypeError('broken')), 10));\nreturn expectThrows(() => withTimeout(broken, 100), TypeError, 'broken');"],
                ['label' => "Doesn't use Promise.race", 'code' => "return learnerCode().includes('Promise.race') ? 'Build it without Promise.race().' : true;"],
            ],
        ],
        [
            'title' => 'Try again',
            'prompt' => 'Write `retry(task, attempts)`. `task` is a function returning a promise. Call it; if it rejects, call it again, up to `attempts` calls in all. Return a promise of the first success, or that rejects with the last error if every attempt fails.',
            'starter_code' => <<<'JS'
                function retry(task, attempts) {
                    return task();
                }
                JS,
            'solution' => <<<'JS'
                function retry(task, attempts) {
                    return task().catch((error) => {
                        if (attempts <= 1) {
                            throw error;
                        }
                        return retry(task, attempts - 1);
                    });
                }
                JS,
            'hint' => 'In `.catch()`, either rethrow (no attempts left) or `return retry(task, attempts - 1)`, so the chain waits for the next try.',
            'checks' => [
                ['label' => 'Succeeds on the third try', 'code' => <<<'JS'
                    let calls = 0;
                    const flaky = () => (++calls < 3 ? Promise.reject(new Error(`fail ${calls}`)) : Promise.resolve(`ok after ${calls}`));
                    return expectSame([await retry(flaky, 5), calls], ['ok after 3', 3]);
                    JS],
                ['label' => 'Gives up with the last error', 'code' => <<<'JS'
                    let calls = 0;
                    const broken = () => Promise.reject(new Error(`fail ${++calls}`));
                    const result = await expectThrows(() => retry(broken, 3), Error, 'fail 3');
                    return result === true ? expectSame(calls, 3) : result;
                    JS],
                ['label' => 'One attempt is one call', 'code' => <<<'JS'
                    let calls = 0;
                    await retry(() => Promise.reject(new Error(String(++calls))), 1).catch(() => {});
                    return expectSame(calls, 1);
                    JS],
            ],
        ],
        [
            'title' => 'One promise at a time',
            'prompt' => 'Write `mapInSequence(items, fn)`, where `fn(item)` returns a promise. Call `fn` for each item in turn, only after the previous promise is fulfilled, and return a promise of the array of results. Use `reduce()` to build the chain.',
            'starter_code' => <<<'JS'
                function mapInSequence(items, fn) {
                    return Promise.resolve(items.map(fn));
                }
                JS,
            'solution' => <<<'JS'
                function mapInSequence(items, fn) {
                    return items.reduce(
                        (chain, item) => chain.then((results) => fn(item).then((result) => [...results, result])),
                        Promise.resolve([]),
                    );
                }
                JS,
            'hint' => 'Start from `Promise.resolve([])`. For each item, `chain.then((results) => fn(item).then((r) => [...results, r]))`.',
            'checks' => [
                ['label' => 'Results in order', 'code' => <<<'JS'
                    const slowDouble = (n) => new Promise((resolve) => setTimeout(() => resolve(n * 2), 30 - n * 5));
                    return expectSame(await mapInSequence([1, 2, 3, 4], slowDouble), [2, 4, 6, 8]);
                    JS],
                ['label' => 'One at a time', 'code' => <<<'JS'
                    let running = 0;
                    let most = 0;
                    const task = (n) => {
                        running++;
                        most = Math.max(most, running);
                        return new Promise((resolve) => setTimeout(() => {
                            running--;
                            resolve(n);
                        }, 10));
                    };
                    await mapInSequence([1, 2, 3], task);
                    return expectSame(most, 1);
                    JS],
                ['label' => 'No items', 'code' => 'return expectSame(await mapInSequence([], (n) => Promise.resolve(n)), []);'],
            ],
        ],
    ],
];
