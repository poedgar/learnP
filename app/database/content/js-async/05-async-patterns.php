<?php

// Lesson 5 of the Async JavaScript course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message, and may await. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput().

return [
    'slug' => 'async-patterns',
    'title' => 'Async patterns',
    'summary' => 'Generators and async generators with for await, cancelling with AbortController, retrying with backoff, throttling, queues that run one job at a time, and awaiting events.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        async function* ticker(limit) {
            for (let n = 1; n <= limit; n++) {
                await sleep(100);
                yield `tick ${n}`;
            }
        }

        for await (const tick of ticker(5)) {
            console.log(tick);
            if (tick === 'tick 3') break;
        }
        console.log('stopped early');
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                function* letters() {
                    yield 'a';
                    yield 'b';
                    yield 'c';
                }
                const it = letters();
                it.next();
                console.log(it.next().value, [...it].join(''));
                JS,
            'options' => ['`b c`', '`a bc`', '`b abc`', '`a abc`'],
            'correct_option' => 0,
            'explanation' => 'The first `next()` takes `a`, the second gives `b`, and spreading takes what\'s left.',
        ],
        [
            'question' => 'What is `for await...of` for?',
            'options' => [
                'Running a loop in parallel',
                'Going through values that arrive asynchronously, such as from an async generator, waiting for each',
                'Awaiting every promise in an array at once',
                'Making a loop faster',
            ],
            'correct_option' => 1,
            'explanation' => 'It works inside async functions and at the top level of modules.',
        ],
        [
            'question' => 'How do you usually cancel async work in JavaScript?',
            'options' => [
                'Call `promise.cancel()`',
                'Pass an `AbortSignal` (from an `AbortController`) into it, and call `abort()` on the controller; the work stops when the signal fires',
                'Set the promise to `null`',
                'It can\'t be done',
            ],
            'correct_option' => 1,
            'explanation' => 'Promises themselves can\'t be cancelled; the work has to cooperate.',
        ],
        [
            'question' => 'What is exponential backoff?',
            'options' => [
                'Retrying straight away',
                'Waiting longer before each retry, doubling the delay each time',
                'Giving up after one failure',
                'Running retries in parallel',
            ],
            'correct_option' => 1,
            'explanation' => 'It gives a busy service time to recover.',
        ],
        [
            'question' => 'Which suits a search box that should query only after the user stops typing?',
            'options' => ['Throttle', 'Debounce', 'A queue', '`Promise.all()`'],
            'correct_option' => 1,
            'explanation' => 'Debounce waits for a pause; throttle acts at regular intervals while events keep coming.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const controller = new AbortController();
                const { signal } = controller;
                let heard = 0;
                signal.addEventListener('abort', () => heard++);
                controller.abort();
                controller.abort();
                console.log(signal.aborted, heard);
                JS,
            'options' => ['`true 1`', '`true 2`', '`false 0`', '`false 1`'],
            'correct_option' => 0,
            'explanation' => 'A signal aborts once; aborting again does nothing.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
                let last = Promise.resolve();
                const log = [];
                const run = (name, ms) => (last = last.then(async () => {
                    await sleep(ms);
                    log.push(name);
                }));
                run('a', 30);
                run('b', 1);
                await run('c', 10);
                console.log(log.join(''));
                JS,
            'options' => ['`abc`', '`bca`', '`cba`', '`acb`'],
            'correct_option' => 0,
            'explanation' => 'Each job is chained onto the previous one, so they run in the order they were started.',
        ],
        [
            'question' => 'What does `yield* other` do inside a generator?',
            'options' => [
                'Yields `other` itself as one value',
                'Yields every value of the iterable `other`, one by one',
                'Multiplies the yielded value',
                'Stops the generator',
            ],
            'correct_option' => 1,
            'explanation' => '`yield* data.items` hands out each item of a page in turn.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Every page',
            'prompt' => "Write an async generator `pagedItems(fetchPage)`. `fetchPage(n)` returns a promise of `{ items, next }`, where `next` is the following page number or `null`. Starting from page 1, yield every item of every page, fetching a page only when the previous one's items have been used.",
            'starter_code' => <<<'JS'
                async function* pagedItems(fetchPage) {
                    const page = await fetchPage(1);
                    yield page.items;
                }
                JS,
            'solution' => <<<'JS'
                async function* pagedItems(fetchPage) {
                    let page = 1;
                    while (page !== null) {
                        const data = await fetchPage(page);
                        yield* data.items;
                        page = data.next;
                    }
                }
                JS,
            'hint' => 'Loop while the page number isn\'t `null`: fetch it, `yield*` its items, then move to `next`.',
            'checks' => [
                ['label' => 'Every item, in order', 'code' => <<<'JS'
                    const pages = { 1: { items: ['a', 'b'], next: 2 }, 2: { items: [], next: 3 }, 3: { items: ['c'], next: null } };
                    const fetchPage = (n) => new Promise((resolve) => setTimeout(() => resolve(pages[n]), 5));
                    const items = [];
                    for await (const item of pagedItems(fetchPage)) items.push(item);
                    return expectSame(items, ['a', 'b', 'c']);
                    JS],
                ['label' => 'Fetches only what is needed', 'code' => <<<'JS'
                    const fetched = [];
                    const fetchPage = async (n) => {
                        fetched.push(n);
                        return { items: [n * 10, n * 10 + 1], next: n + 1 };
                    };
                    const items = [];
                    for await (const item of pagedItems(fetchPage)) {
                        items.push(item);
                        if (items.length === 3) break;
                    }
                    return expectSame([items, fetched], [[10, 11, 20], [1, 2]]);
                    JS],
            ],
        ],
        [
            'title' => 'Take a few',
            'prompt' => 'Write `async function take(source, count)`, returning an array of the first `count` values from the async iterable `source` (or all of them, if there are fewer). Stop reading as soon as you have enough.',
            'starter_code' => <<<'JS'
                async function take(source, count) {
                    const values = [];
                    for await (const value of source) {
                        values.push(value);
                    }
                    return values.slice(0, count);
                }
                JS,
            'solution' => <<<'JS'
                async function take(source, count) {
                    const values = [];
                    if (count <= 0) {
                        return values;
                    }
                    for await (const value of source) {
                        values.push(value);
                        if (values.length >= count) {
                            break;
                        }
                    }
                    return values;
                }
                JS,
            'hint' => '`break` out of the `for await` loop once you have `count` values; that also stops the generator.',
            'checks' => [
                ['label' => 'Takes the first values', 'code' => <<<'JS'
                    async function* numbers() {
                        for (let n = 1; n <= 5; n++) yield n;
                    }
                    return expectSame([await take(numbers(), 3), await take(numbers(), 10), await take(numbers(), 0)], [[1, 2, 3], [1, 2, 3, 4, 5], []]);
                    JS],
                ['label' => 'Works on an endless source', 'code' => <<<'JS'
                    let produced = 0;
                    async function* forever() {
                        for (let n = 0; ; n++) {
                            produced++;
                            if (produced > 1000) throw new Error('It read far too many values.');
                            yield n;
                        }
                    }
                    return expectSame([await take(forever(), 4), produced], [[0, 1, 2, 3], 4]);
                    JS],
            ],
        ],
        [
            'title' => 'A sleep you can cancel',
            'prompt' => "Write `sleep(ms, signal)`, returning a promise that fulfils after `ms` milliseconds. If the optional `signal` is aborted first, clear the timer and reject with an `Error` whose message is `'Aborted'`; if it's already aborted, reject straight away.",
            'starter_code' => <<<'JS'
                function sleep(ms, signal) {
                    return new Promise((resolve) => setTimeout(resolve, ms));
                }
                JS,
            'solution' => <<<'JS'
                function sleep(ms, signal) {
                    return new Promise((resolve, reject) => {
                        if (signal?.aborted) {
                            reject(new Error('Aborted'));
                            return;
                        }
                        const timer = setTimeout(resolve, ms);
                        signal?.addEventListener(
                            'abort',
                            () => {
                                clearTimeout(timer);
                                reject(new Error('Aborted'));
                            },
                            { once: true },
                        );
                    });
                }
                JS,
            'hint' => 'Check `signal?.aborted` first, then listen for `signal.addEventListener(\'abort\', ...)`, clearing the timer and rejecting.',
            'checks' => [
                ['label' => 'Sleeps without a signal', 'code' => "const started = performance.now();\nawait sleep(30);\nreturn performance.now() - started >= 25 ? true : 'It finished too soon.';"],
                ['label' => 'Can be aborted part-way', 'code' => <<<'JS'
                    const controller = new AbortController();
                    setTimeout(() => controller.abort(), 20);
                    const started = performance.now();
                    const result = await expectThrows(() => sleep(500, controller.signal), Error, 'Aborted');
                    if (result !== true) return result;
                    return performance.now() - started < 200 ? true : 'Stop sleeping as soon as the signal is aborted.';
                    JS],
                ['label' => 'Already aborted', 'code' => <<<'JS'
                    const controller = new AbortController();
                    controller.abort();
                    const started = performance.now();
                    const result = await expectThrows(() => sleep(300, controller.signal), Error, 'Aborted');
                    if (result !== true) return result;
                    return performance.now() - started < 100 ? true : 'Reject straight away when the signal is already aborted.';
                    JS],
            ],
        ],
        [
            'title' => 'Retry with backoff',
            'prompt' => 'Write `async function retryWithBackoff(task, attempts, baseMs)`. Call `task()`; if it rejects and attempts remain, wait `baseMs`, then `baseMs * 2`, then `baseMs * 4`, and so on, before each retry. Return the first success, or throw the last error after `attempts` calls.',
            'starter_code' => <<<'JS'
                async function retryWithBackoff(task, attempts, baseMs) {
                    for (let i = 0; i < attempts; i++) {
                        try {
                            return await task();
                        } catch {}
                    }
                }
                JS,
            'solution' => <<<'JS'
                const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

                async function retryWithBackoff(task, attempts, baseMs) {
                    let delay = baseMs;
                    for (let attempt = 1; ; attempt++) {
                        try {
                            return await task();
                        } catch (error) {
                            if (attempt >= attempts) {
                                throw error;
                            }
                            await wait(delay);
                            delay *= 2;
                        }
                    }
                }
                JS,
            'hint' => 'In the `catch`, rethrow on the last attempt; otherwise `await` a delay that doubles each time.',
            'checks' => [
                ['label' => 'Waits longer each time', 'code' => <<<'JS'
                    const times = [];
                    const started = performance.now();
                    const task = async () => {
                        times.push(performance.now() - started);
                        if (times.length < 4) throw new Error('busy');
                        return 'ok';
                    };
                    const result = await retryWithBackoff(task, 5, 20);
                    const gaps = times.slice(1).map((t, i) => t - times[i]);
                    if (result !== 'ok') return `Expected 'ok', got ${inspect(result)}.`;
                    return gaps.length === 3 && gaps[0] >= 15 && gaps[1] >= 35 && gaps[2] >= 75 ? true : `The waits were about ${gaps.map(Math.round).join(', ')} ms; expected 20, 40, 80.`;
                    JS],
                ['label' => 'Gives up with the last error', 'code' => <<<'JS'
                    let calls = 0;
                    const result = await expectThrows(() => retryWithBackoff(async () => { throw new Error(`fail ${++calls}`); }, 3, 5), Error, 'fail 3');
                    return result === true ? expectSame(calls, 3) : result;
                    JS],
            ],
        ],
        [
            'title' => 'Throttle',
            'prompt' => 'Write `throttle(fn, ms)`, which returns a function that calls `fn` (with its arguments) straight away, then ignores any further calls until `ms` milliseconds have passed since that call.',
            'starter_code' => <<<'JS'
                function throttle(fn, ms) {
                    return fn;
                }
                JS,
            'solution' => <<<'JS'
                function throttle(fn, ms) {
                    let last = -Infinity;
                    return (...args) => {
                        const now = performance.now();
                        if (now - last >= ms) {
                            last = now;
                            fn(...args);
                        }
                    };
                }
                JS,
            'hint' => 'Remember when `fn` was last called (`performance.now()`); call it again only once `ms` has passed.',
            'checks' => [
                ['label' => 'At most once per period', 'code' => <<<'JS'
                    const calls = [];
                    const onScroll = throttle((y) => calls.push(y), 40);
                    onScroll(1);
                    onScroll(2);
                    onScroll(3);
                    await new Promise((resolve) => setTimeout(resolve, 60));
                    onScroll(4);
                    onScroll(5);
                    return expectSame(calls, [1, 4]);
                    JS],
            ],
        ],
        [
            'title' => 'One job at a time',
            'prompt' => "Write `createQueue()`, returning a function `run(job)`. `job` is an async function. `run()` returns a promise of that job's result, but jobs run strictly one at a time, in the order they were given, even if `run()` is called several times at once. A failed job rejects its own promise without stopping later jobs.",
            'starter_code' => <<<'JS'
                function createQueue() {
                    return (job) => job();
                }
                JS,
            'solution' => <<<'JS'
                function createQueue() {
                    let last = Promise.resolve();
                    return (job) => {
                        const result = last.then(() => job());
                        last = result.catch(() => {});
                        return result;
                    };
                }
                JS,
            'hint' => 'Keep the last job\'s promise in a closure. Chain each new job onto it, and store a version that ignores errors as the new `last`.',
            'checks' => [
                ['label' => 'In order, never overlapping', 'code' => <<<'JS'
                    const run = createQueue();
                    let running = 0;
                    let most = 0;
                    const job = (name, ms) => async () => {
                        running++;
                        most = Math.max(most, running);
                        await new Promise((resolve) => setTimeout(resolve, ms));
                        running--;
                        return name;
                    };
                    const results = await Promise.all([run(job('a', 30)), run(job('b', 5)), run(job('c', 10))]);
                    return expectSame([results, most], [['a', 'b', 'c'], 1]);
                    JS],
                ['label' => 'A failure only affects its own job', 'code' => <<<'JS'
                    const run = createQueue();
                    const failed = run(async () => {
                        throw new Error('broken');
                    });
                    const after = run(async () => 'still works');
                    const result = await expectThrows(() => failed, Error, 'broken');
                    return result === true ? expectSame(await after, 'still works') : result;
                    JS],
                ['label' => 'Separate queues run separately', 'code' => <<<'JS'
                    const a = createQueue();
                    const b = createQueue();
                    const started = performance.now();
                    await Promise.all([a(() => new Promise((r) => setTimeout(r, 50))), b(() => new Promise((r) => setTimeout(r, 50)))]);
                    return performance.now() - started < 90 ? true : 'Each queue should be independent.';
                    JS],
            ],
        ],
    ],
];
