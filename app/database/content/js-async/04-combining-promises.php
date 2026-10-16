<?php

// Lesson 4 of the Async JavaScript course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message, and may await. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput().

return [
    'slug' => 'combining-promises',
    'title' => 'Combining promises',
    'summary' => 'Wait for groups of promises with Promise.all, allSettled, race and any; add timeouts; and control how much runs at once.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
        const fail = (ms, message) => new Promise((_, reject) => setTimeout(() => reject(new Error(message)), ms));

        const tasks = () => [wait(30, 'one'), fail(10, 'two broke'), wait(20, 'three')];

        console.log(await Promise.allSettled(tasks()));
        console.log(await Promise.any(tasks()));
        console.log(await Promise.race(tasks()).catch((error) => `race: ${error.message}`));
        console.log(await Promise.all(tasks()).catch((error) => `all: ${error.message}`));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const wait = (ms, v) => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                const values = await Promise.all([wait(30, 'a'), wait(10, 'b'), wait(20, 'c')]);
                console.log(values.join(''));
                JS,
            'options' => ['`abc`', '`bca`', '`b`', '`cba`'],
            'correct_option' => 0,
            'explanation' => '`Promise.all()` gives values in the order of the promises, not the order they finished.',
        ],
        [
            'question' => 'What happens to `Promise.all([a, b, c])` if `b` rejects?',
            'options' => [
                'It waits for all three, then rejects',
                'It rejects straight away with `b`\'s error, and the values of `a` and `c` are lost (though they keep running)',
                'It fulfils with `[a, undefined, c]`',
                'It cancels `a` and `c`',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `Promise.allSettled()` when some failures are acceptable.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const results = await Promise.allSettled([Promise.resolve(1), Promise.reject(new Error('x'))]);
                console.log(results.map((r) => r.status).join(' '));
                JS,
            'options' => ['`fulfilled rejected`', '`1 x`', 'An error', '`resolved rejected`'],
            'correct_option' => 0,
            'explanation' => 'Each result is `{ status: \'fulfilled\', value }` or `{ status: \'rejected\', reason }`.',
        ],
        [
            'question' => 'Which method would you use to add a timeout to a request?',
            'options' => ['`Promise.all()`', '`Promise.race()`, against a promise that rejects after the timeout', '`Promise.allSettled()`', '`Promise.resolve()`'],
            'correct_option' => 1,
            'explanation' => 'Whichever settles first, the request or the timer, decides the result.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const wait = (ms, v) => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                const fail = (ms) => new Promise((_, reject) => setTimeout(() => reject(new Error('down')), ms));
                console.log(await Promise.any([fail(5), wait(30, 'b'), wait(20, 'c')]));
                JS,
            'options' => ['`c`', '`b`', 'An error: down', '`down`'],
            'correct_option' => 0,
            'explanation' => '`Promise.any()` ignores the failure and gives the first value to arrive, after 20 ms.',
        ],
        [
            'question' => 'When does a promise\'s work start?',
            'options' => [
                'When it\'s awaited',
                'When it\'s created',
                'When it\'s passed to `Promise.all()`',
                'When `.then()` is called',
            ],
            'correct_option' => 1,
            'explanation' => 'So create promises together to run them at the same time.',
        ],
        [
            'question' => 'What does `Promise.any()` reject with when every promise rejects?',
            'options' => ['The first error', 'The last error', 'An `AggregateError` holding all the errors in `errors`', '`undefined`'],
            'correct_option' => 2,
            'explanation' => 'There\'s no single error to report, so it gives them all.',
        ],
        [
            'question' => 'What does `Promise.all([])` give?',
            'options' => ['A promise that never settles', 'A promise fulfilled with `[]` straight away', 'An error', '`undefined`'],
            'correct_option' => 1,
            'explanation' => 'Nothing to wait for. `Promise.race([])`, on the other hand, never settles.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A profile, in parallel',
            'prompt' => "Write `async function profile(id)` using the provided functions (each takes 40 ms). Load the user, their posts and their followers at the same time, and return text like `'Amara: 3 posts, 2 followers'`.",
            'starter_code' => <<<'JS'
                const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
                const fetchUser = (id) => wait(40, { id, name: id === 1 ? 'Amara' : 'Ben' });
                const fetchPosts = (id) => wait(40, id === 1 ? ['a', 'b', 'c'] : []);
                const fetchFollowers = (id) => wait(40, id === 1 ? ['Ben', 'Cy'] : ['Amara']);

                async function profile(id) {
                    const user = await fetchUser(id);
                    return user.name;
                }
                JS,
            'solution' => <<<'JS'
                const wait = (ms, value) => new Promise((resolve) => setTimeout(() => resolve(value), ms));
                const fetchUser = (id) => wait(40, { id, name: id === 1 ? 'Amara' : 'Ben' });
                const fetchPosts = (id) => wait(40, id === 1 ? ['a', 'b', 'c'] : []);
                const fetchFollowers = (id) => wait(40, id === 1 ? ['Ben', 'Cy'] : ['Amara']);

                async function profile(id) {
                    const [user, posts, followers] = await Promise.all([fetchUser(id), fetchPosts(id), fetchFollowers(id)]);
                    return `${user.name}: ${posts.length} posts, ${followers.length} followers`;
                }
                JS,
            'hint' => 'Destructure `await Promise.all([fetchUser(id), fetchPosts(id), fetchFollowers(id)])`.',
            'checks' => [
                ['label' => 'The text', 'code' => "return expectSame([await profile(1), await profile(2)], ['Amara: 3 posts, 2 followers', 'Ben: 0 posts, 1 followers']);"],
                ['label' => 'Loads in parallel', 'code' => <<<'JS'
                    const started = performance.now();
                    await profile(1);
                    const elapsed = performance.now() - started;
                    return elapsed < 100 ? true : `It took ${Math.round(elapsed)} ms; load all three at the same time.`;
                    JS],
            ],
        ],
        [
            'title' => 'Whatever loaded',
            'prompt' => 'Write `async function loadAll(sources)`, where each source is a function returning a promise. Run them all at once and return `{ loaded, failed }`: the fulfilled values and the error messages of the rejections, each in the order of the sources.',
            'starter_code' => <<<'JS'
                async function loadAll(sources) {
                    const loaded = await Promise.all(sources.map((source) => source()));
                    return { loaded, failed: [] };
                }
                JS,
            'solution' => <<<'JS'
                async function loadAll(sources) {
                    const results = await Promise.allSettled(sources.map((source) => source()));
                    return {
                        loaded: results.filter((r) => r.status === 'fulfilled').map((r) => r.value),
                        failed: results.filter((r) => r.status === 'rejected').map((r) => r.reason.message),
                    };
                }
                JS,
            'hint' => '`Promise.allSettled()` gives a report for each; filter by `status` and map to `value` or `reason.message`.',
            'checks' => [
                ['label' => 'Sorts out the results', 'code' => <<<'JS'
                    const wait = (ms, v) => () => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                    const fail = (ms, m) => () => new Promise((_, reject) => setTimeout(() => reject(new Error(m)), ms));
                    return expectSame(await loadAll([wait(20, 'a'), fail(5, 'b broke'), wait(5, 'c'), fail(15, 'd broke')]), { loaded: ['a', 'c'], failed: ['b broke', 'd broke'] });
                    JS],
                ['label' => 'Nothing to load', 'code' => 'return expectSame(await loadAll([]), { loaded: [], failed: [] });'],
            ],
        ],
        [
            'title' => 'The fastest mirror',
            'prompt' => "Write `async function fastest(mirrors)`, where each mirror is a function returning a promise. Start them all and return the first value to arrive from any that works. If every mirror fails, or there are none, return `'offline'`.",
            'starter_code' => <<<'JS'
                async function fastest(mirrors) {
                    return Promise.race(mirrors.map((mirror) => mirror()));
                }
                JS,
            'solution' => <<<'JS'
                async function fastest(mirrors) {
                    try {
                        return await Promise.any(mirrors.map((mirror) => mirror()));
                    } catch {
                        return 'offline';
                    }
                }
                JS,
            'hint' => '`Promise.any()` skips failures. It rejects (with an `AggregateError`) when all fail, and at once for an empty list.',
            'checks' => [
                ['label' => 'Skips a quick failure', 'code' => <<<'JS'
                    const wait = (ms, v) => () => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                    const fail = (ms) => () => new Promise((_, reject) => setTimeout(() => reject(new Error('down')), ms));
                    return expectSame(await fastest([fail(5), wait(40, 'slow'), wait(20, 'quick')]), 'quick');
                    JS],
                ['label' => 'All down, or none', 'code' => <<<'JS'
                    const fail = (ms) => () => new Promise((_, reject) => setTimeout(() => reject(new Error('down')), ms));
                    return expectSame([await fastest([fail(5), fail(10)]), await fastest([])], ['offline', 'offline']);
                    JS],
            ],
        ],
        [
            'title' => 'A time limit',
            'prompt' => "Write `timeLimit(fn, ms)`, which returns an async function. Calling it calls `fn` with the same arguments, and gives its result, unless that takes longer than `ms` milliseconds, in which case it rejects with an `Error` whose message is `'Time limit exceeded'`. Use `Promise.race()`.",
            'starter_code' => <<<'JS'
                function timeLimit(fn, ms) {
                    return fn;
                }
                JS,
            'solution' => <<<'JS'
                function timeLimit(fn, ms) {
                    return async (...args) => {
                        let timer;
                        const limit = new Promise((_, reject) => {
                            timer = setTimeout(() => reject(new Error('Time limit exceeded')), ms);
                        });
                        try {
                            return await Promise.race([fn(...args), limit]);
                        } finally {
                            clearTimeout(timer);
                        }
                    };
                }
                JS,
            'hint' => 'Race `fn(...args)` against a promise that rejects after `ms`. Clearing that timer in `finally` keeps things tidy.',
            'checks' => [
                ['label' => 'In time', 'code' => <<<'JS'
                    const add = async (a, b) => new Promise((resolve) => setTimeout(() => resolve(a + b), 10));
                    return expectSame(await timeLimit(add, 100)(2, 3), 5);
                    JS],
                ['label' => 'Too slow', 'code' => <<<'JS'
                    const slow = () => new Promise((resolve) => setTimeout(() => resolve('late'), 200));
                    return expectThrows(() => timeLimit(slow, 30)(), Error, 'Time limit exceeded');
                    JS],
                ['label' => 'Passes failures on', 'code' => "return expectThrows(() => timeLimit(async () => { throw new TypeError('bad'); }, 100)(), TypeError, 'bad');"],
            ],
        ],
        [
            'title' => 'Build Promise.all',
            'prompt' => 'Write `all(promises)`, which behaves like `Promise.all()`, without using `Promise.all`, `allSettled`, `race` or `any`: fulfil with the values in order once all have fulfilled, reject as soon as one rejects, and fulfil with `[]` for an empty array.',
            'starter_code' => <<<'JS'
                function all(promises) {
                    return new Promise((resolve) => {
                        const values = [];
                        promises.forEach((p) => p.then((value) => values.push(value)));
                        resolve(values);
                    });
                }
                JS,
            'solution' => <<<'JS'
                function all(promises) {
                    return new Promise((resolve, reject) => {
                        const values = new Array(promises.length);
                        let remaining = promises.length;
                        if (remaining === 0) {
                            resolve([]);
                            return;
                        }
                        promises.forEach((promise, i) => {
                            Promise.resolve(promise).then((value) => {
                                values[i] = value;
                                remaining--;
                                if (remaining === 0) {
                                    resolve(values);
                                }
                            }, reject);
                        });
                    });
                }
                JS,
            'hint' => 'Count the promises still pending; store each value at its index; `resolve()` when the count reaches 0. Pass `reject` as the error handler.',
            'checks' => [
                ['label' => 'Values in order', 'code' => <<<'JS'
                    const wait = (ms, v) => new Promise((resolve) => setTimeout(() => resolve(v), ms));
                    return expectSame(await all([wait(30, 'a'), wait(5, 'b'), wait(15, 'c')]), ['a', 'b', 'c']);
                    JS],
                ['label' => 'Rejects early', 'code' => <<<'JS'
                    const started = performance.now();
                    const slow = new Promise((resolve) => setTimeout(resolve, 200));
                    const broken = new Promise((_, reject) => setTimeout(() => reject(new Error('broken')), 10));
                    const result = await expectThrows(() => all([slow, broken]), Error, 'broken');
                    if (result !== true) return result;
                    return performance.now() - started < 100 ? true : 'Reject as soon as one promise rejects.';
                    JS],
                ['label' => 'Empty', 'code' => 'return expectSame(await all([]), []);'],
                ['label' => 'Built by hand', 'code' => "return /Promise\\.(all|allSettled|race|any)\\b/.test(learnerCode()) ? 'Build it without the built-in combinators.' : true;"],
            ],
        ],
        [
            'title' => 'In batches',
            'prompt' => 'Write `async function inBatches(items, size, fn)`, where `fn(item)` returns a promise. Process the items `size` at a time: each batch runs in parallel, and the next batch starts only when the whole batch has finished. Return all the results in order.',
            'starter_code' => <<<'JS'
                async function inBatches(items, size, fn) {
                    return Promise.all(items.map(fn));
                }
                JS,
            'solution' => <<<'JS'
                async function inBatches(items, size, fn) {
                    const results = [];
                    for (let i = 0; i < items.length; i += size) {
                        const batch = items.slice(i, i + size);
                        results.push(...(await Promise.all(batch.map(fn))));
                    }
                    return results;
                }
                JS,
            'hint' => 'Loop over the items `size` at a time; for each slice, `await Promise.all(slice.map(fn))` and add the results.',
            'checks' => [
                ['label' => 'Results in order', 'code' => <<<'JS'
                    const square = (n) => new Promise((resolve) => setTimeout(() => resolve(n * n), 10));
                    return expectSame(await inBatches([1, 2, 3, 4, 5], 2, square), [1, 4, 9, 16, 25]);
                    JS],
                ['label' => 'At most size at once, batch by batch', 'code' => <<<'JS'
                    let running = 0;
                    let most = 0;
                    const task = (n) => {
                        running++;
                        most = Math.max(most, running);
                        return new Promise((resolve) => setTimeout(() => {
                            running--;
                            resolve(n);
                        }, 20));
                    };
                    const started = performance.now();
                    await inBatches([1, 2, 3, 4, 5, 6], 3, task);
                    const elapsed = performance.now() - started;
                    if (most !== 3) return `Up to ${most} ran at once; run 3 at a time.`;
                    return elapsed >= 35 ? true : 'The second batch should wait for the first.';
                    JS],
            ],
        ],
    ],
];
