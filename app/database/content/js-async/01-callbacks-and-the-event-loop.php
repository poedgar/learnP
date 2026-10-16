<?php

// Lesson 1 of the Async JavaScript course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message, and may await. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput().

return [
    'slug' => 'callbacks-and-the-event-loop',
    'title' => 'Callbacks and the event loop',
    'summary' => 'See why JavaScript doesn\'t wait: timers, the call stack and the event loop, tasks and microtasks, callbacks and error-first callbacks, and coordinating several at once.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        console.log('A');

        setTimeout(() => console.log('B (after 100 ms)'), 100);
        setTimeout(() => console.log('C (after 0 ms)'), 0);
        queueMicrotask(() => console.log('D (microtask)'));

        console.log('E');
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                setTimeout(() => console.log('b'), 0);
                console.log('a');
                JS,
            'options' => ['`a b`', '`b a`', '`a`', '`b`'],
            'correct_option' => 0,
            'explanation' => 'Even with a delay of 0, the callback waits until the current code has finished.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                setTimeout(() => console.log('timer'), 0);
                queueMicrotask(() => console.log('micro'));
                console.log('sync');
                JS,
            'options' => ['`sync micro timer`', '`sync timer micro`', '`timer micro sync`', '`micro sync timer`'],
            'correct_option' => 0,
            'explanation' => 'The script runs first; then all microtasks; then the next task, the timer.',
        ],
        [
            'question' => 'Why does a long loop freeze a web page?',
            'options' => [
                'Loops are slow in JavaScript',
                'JavaScript does one thing at a time; while the loop runs, no other callback, including those for clicks and drawing, can run',
                'The browser limits loops to a second',
                'It doesn\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'The event loop only takes the next callback when the call stack is empty.',
        ],
        [
            'question' => 'Why can\'t a function that starts slow work simply `return` the result?',
            'options' => [
                'Functions can\'t return objects',
                'The result doesn\'t exist yet when the function returns; it has to be delivered later, through a callback',
                'Because `return` is synchronous only in classes',
                'It can, using `return await`',
            ],
            'correct_option' => 1,
            'explanation' => 'Promises, next lesson, are another way to deliver a result later.',
        ],
        [
            'question' => 'In an error-first callback `(error, result) => { ... }`, what is `error` when everything went well?',
            'options' => ['`undefined` or `null`, conventionally `null`', '`false`', 'An `Error` with an empty message', '`0`'],
            'correct_option' => 0,
            'explanation' => 'Callers check `if (error)` first, then use the result.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let n = 0;
                const id = setInterval(() => {
                    n++;
                    if (n === 3) clearInterval(id);
                }, 10);
                console.log(n);
                JS,
            'options' => ['`0`', '`3`', '`1`', '`undefined`'],
            'correct_option' => 0,
            'explanation' => '`console.log(n)` runs before any of the interval\'s callbacks.',
        ],
        [
            'question' => 'What is "callback hell"?',
            'options' => [
                'A callback that throws',
                'Deeply nested callbacks for steps that depend on each other, which are hard to read and to handle errors in',
                'A callback that is never called',
                'Calling a callback twice',
            ],
            'correct_option' => 1,
            'explanation' => 'Promises and `async`/`await` flatten it.',
        ],
        [
            'question' => 'Why should an async function always call its callback asynchronously, never sometimes straight away?',
            'options' => [
                'It\'s faster',
                'So callers can rely on the code after the call always running before the callback',
                'Synchronous callbacks are not allowed',
                'It doesn\'t matter',
            ],
            'correct_option' => 1,
            'explanation' => 'A function that is sometimes synchronous and sometimes not causes bugs that are very hard to find.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Later, with a value',
            'prompt' => 'Write `later(ms, value, callback)`, which calls `callback(value)` after `ms` milliseconds (and never straight away).',
            'starter_code' => <<<'JS'
                function later(ms, value, callback) {
                    callback(value);
                }
                JS,
            'solution' => <<<'JS'
                function later(ms, value, callback) {
                    setTimeout(() => callback(value), ms);
                }
                JS,
            'hint' => 'Pass an arrow function to `setTimeout()` that calls `callback(value)`.',
            'checks' => [
                ['label' => 'Calls back with the value, later', 'code' => <<<'JS'
                    const started = performance.now();
                    let received = 'not yet';
                    later(60, 'done', (value) => (received = value));
                    const immediately = received;
                    await new Promise((resolve) => setTimeout(resolve, 30));
                    const halfway = received;
                    await new Promise((resolve) => setTimeout(resolve, 60));
                    return expectSame([immediately, halfway, received], ['not yet', 'not yet', 'done']);
                    JS],
            ],
        ],
        [
            'title' => 'Countdown',
            'prompt' => "Write `countdown(from, done)`, which logs `from`, `from - 1`, … down to `1`, one number every 20 milliseconds (the first straight away), then logs `'Lift off!'` and calls `done()`.",
            'starter_code' => <<<'JS'
                function countdown(from, done) {
                    for (let n = from; n >= 1; n--) {
                        setTimeout(() => console.log(n), 20);
                    }
                    done();
                }
                JS,
            'solution' => <<<'JS'
                function countdown(from, done) {
                    let n = from;
                    console.log(n);
                    const id = setInterval(() => {
                        n--;
                        if (n >= 1) {
                            console.log(n);
                        } else {
                            clearInterval(id);
                            console.log('Lift off!');
                            done();
                        }
                    }, 20);
                }
                JS,
            'hint' => 'Log the first number straight away, then use `setInterval()` to log the rest; when you get below 1, `clearInterval()`, log `Lift off!` and call `done()`.',
            'checks' => [
                ['label' => 'Counts down, then calls done()', 'code' => <<<'JS'
                    const before = learnerOutput().length;
                    const started = performance.now();
                    await new Promise((resolve) => countdown(3, resolve));
                    const elapsed = performance.now() - started;
                    const printed = learnerOutput().slice(before).trim().split('\n');
                    if (elapsed < 35) return `That took ${Math.round(elapsed)} ms; wait 20 ms between numbers.`;
                    return expectSame(printed, ['3', '2', '1', 'Lift off!']);
                    JS],
            ],
        ],
        [
            'title' => 'One after another',
            'prompt' => 'Write `runInSequence(tasks, done)`. Each task is a function taking a callback, which it calls with a result when it finishes. Run the tasks one at a time, each starting only after the previous one has finished, then call `done(results)` with the results in order. With no tasks, call `done([])` (asynchronously).',
            'starter_code' => <<<'JS'
                function runInSequence(tasks, done) {
                    const results = [];
                    for (const task of tasks) {
                        task((result) => results.push(result));
                    }
                    done(results);
                }
                JS,
            'solution' => <<<'JS'
                function runInSequence(tasks, done) {
                    const results = [];

                    function next(index) {
                        if (index === tasks.length) {
                            done(results);
                            return;
                        }
                        tasks[index]((result) => {
                            results.push(result);
                            next(index + 1);
                        });
                    }

                    setTimeout(() => next(0), 0);
                }
                JS,
            'hint' => 'A helper `next(index)` runs `tasks[index]`, and in its callback stores the result and calls `next(index + 1)`. When `index` reaches the end, call `done()`.',
            'checks' => [
                ['label' => 'Results in order', 'code' => <<<'JS'
                    const task = (value, ms) => (callback) => setTimeout(() => callback(value), ms);
                    const results = await new Promise((resolve) => runInSequence([task('a', 30), task('b', 5), task('c', 15)], resolve));
                    return expectSame(results, ['a', 'b', 'c']);
                    JS],
                ['label' => 'Never two at once', 'code' => <<<'JS'
                    let running = 0;
                    let most = 0;
                    const task = (ms) => (callback) => {
                        running++;
                        most = Math.max(most, running);
                        setTimeout(() => {
                            running--;
                            callback(ms);
                        }, ms);
                    };
                    await new Promise((resolve) => runInSequence([task(10), task(10), task(10)], resolve));
                    return expectSame(most, 1);
                    JS],
                ['label' => 'No tasks', 'code' => <<<'JS'
                    let result = 'not called';
                    runInSequence([], (results) => (result = results));
                    const sync = result;
                    await new Promise((resolve) => setTimeout(resolve, 10));
                    return expectSame([sync, result], ['not called', []]);
                    JS],
            ],
        ],
        [
            'title' => 'All at once',
            'prompt' => "Write `runInParallel(tasks, done)`, with tasks like those in the previous challenge. Start them all at once, and call `done(results)` when the last finishes, with each result at its task's position (not the order they finished in).",
            'starter_code' => <<<'JS'
                function runInParallel(tasks, done) {
                    const results = [];
                    for (const task of tasks) {
                        task((result) => {
                            results.push(result);
                            if (results.length === tasks.length) done(results);
                        });
                    }
                }
                JS,
            'solution' => <<<'JS'
                function runInParallel(tasks, done) {
                    const results = new Array(tasks.length);
                    let finished = 0;

                    if (tasks.length === 0) {
                        setTimeout(() => done([]), 0);
                        return;
                    }

                    tasks.forEach((task, i) => {
                        task((result) => {
                            results[i] = result;
                            finished++;
                            if (finished === tasks.length) {
                                done(results);
                            }
                        });
                    });
                }
                JS,
            'hint' => 'Store each result at `results[i]` and count how many have finished; call `done()` when the count reaches the number of tasks.',
            'checks' => [
                ['label' => 'Results by position', 'code' => <<<'JS'
                    const task = (value, ms) => (callback) => setTimeout(() => callback(value), ms);
                    const results = await new Promise((resolve) => runInParallel([task('slow', 50), task('fast', 5), task('middle', 25)], resolve));
                    return expectSame(results, ['slow', 'fast', 'middle']);
                    JS],
                ['label' => 'Runs them at the same time', 'code' => <<<'JS'
                    const task = (ms) => (callback) => setTimeout(() => callback(ms), ms);
                    const started = performance.now();
                    await new Promise((resolve) => runInParallel([task(60), task(60), task(60)], resolve));
                    const elapsed = performance.now() - started;
                    return elapsed < 150 ? true : `Three 60 ms tasks took ${Math.round(elapsed)} ms, so they didn't run at the same time.`;
                    JS],
            ],
        ],
        [
            'title' => 'An error-first callback',
            'prompt' => "Write `readConfig(text, callback)`, which parses `text` as JSON and calls `callback(null, config)`, or `callback(error)` if it isn't valid JSON. Always call back asynchronously, after the current code has finished, even though parsing is quick.",
            'starter_code' => <<<'JS'
                function readConfig(text, callback) {
                    callback(null, JSON.parse(text));
                }
                JS,
            'solution' => <<<'JS'
                function readConfig(text, callback) {
                    setTimeout(() => {
                        let config;
                        try {
                            config = JSON.parse(text);
                        } catch (error) {
                            callback(error);
                            return;
                        }
                        callback(null, config);
                    }, 0);
                }
                JS,
            'hint' => 'Do the work inside `setTimeout(() => { ... }, 0)`. Catch the parse error and pass it as the first argument.',
            'checks' => [
                ['label' => 'Valid JSON', 'code' => <<<'JS'
                    const [error, config] = await new Promise((resolve) => readConfig('{"debug": true}', (...args) => resolve(args)));
                    return expectSame([error, config], [null, { debug: true }]);
                    JS],
                ['label' => 'Invalid JSON gives an error', 'code' => <<<'JS'
                    const [error, config] = await new Promise((resolve) => readConfig('{ nope', (...args) => resolve(args)));
                    return error instanceof SyntaxError && config === undefined ? true : 'Call back with the SyntaxError as the first argument, and no config.';
                    JS],
                ['label' => 'Always asynchronous', 'code' => <<<'JS'
                    let order = '';
                    readConfig('{}', () => (order += 'callback '));
                    readConfig('{', () => (order += 'callback '));
                    order += 'after ';
                    await new Promise((resolve) => setTimeout(resolve, 10));
                    return expectSame(order, 'after callback callback ');
                    JS],
            ],
        ],
        [
            'title' => 'Debounce',
            'prompt' => 'Write `debounce(fn, ms)`, which returns a function. Calling it waits `ms` milliseconds before calling `fn`; calling it again within that time restarts the wait. `fn` is called once, with the arguments of the last call.',
            'starter_code' => <<<'JS'
                function debounce(fn, ms) {
                    return (...args) => setTimeout(() => fn(...args), ms);
                }
                JS,
            'solution' => <<<'JS'
                function debounce(fn, ms) {
                    let timer = null;
                    return (...args) => {
                        clearTimeout(timer);
                        timer = setTimeout(() => fn(...args), ms);
                    };
                }
                JS,
            'hint' => 'Keep the timer id in the closure, and `clearTimeout()` it before starting a new one.',
            'checks' => [
                ['label' => 'Only the last call counts', 'code' => <<<'JS'
                    const calls = [];
                    const search = debounce((text) => calls.push(text), 40);
                    search('d');
                    search('du');
                    await new Promise((resolve) => setTimeout(resolve, 15));
                    search('dun');
                    search('dune');
                    await new Promise((resolve) => setTimeout(resolve, 80));
                    return expectSame(calls, ['dune']);
                    JS],
                ['label' => 'Waits each time it is called', 'code' => <<<'JS'
                    const calls = [];
                    const save = debounce((n) => calls.push(n), 30);
                    save(1);
                    await new Promise((resolve) => setTimeout(resolve, 60));
                    save(2);
                    const before = [...calls];
                    await new Promise((resolve) => setTimeout(resolve, 60));
                    return expectSame([before, calls], [[1], [1, 2]]);
                    JS],
            ],
        ],
    ],
];
