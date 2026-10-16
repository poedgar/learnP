<?php

// Lesson 3 of the JavaScript and the DOM course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope and on the same page;
// a check returns true or a message. Helpers: expectSame(), expectThrows(), learnerCode().

return [
    'slug' => 'events',
    'title' => 'Events',
    'summary' => 'Make pages respond: addEventListener, the event object, input and keyboard events, bubbling and event delegation, preventDefault, removing listeners, and custom events.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). Then click in the page below the output.
        document.body.innerHTML = `
            <button id="minus">−</button>
            <span id="count">0</span>
            <button id="plus">+</button>
        `;

        let count = 0;
        const show = () => (document.querySelector('#count').textContent = count);

        document.querySelector('#plus').addEventListener('click', () => {
            count++;
            show();
        });
        document.querySelector('#minus').addEventListener('click', () => {
            count--;
            show();
        });
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<button>x</button>';
                const b = document.querySelector('button');
                let n = 0;
                b.addEventListener('click', () => n++);
                b.addEventListener('click', () => (n += 10));
                b.click();
                b.click();
                console.log(n);
                JS,
            'options' => ['`22`', '`2`', '`20`', '`11`'],
            'correct_option' => 0,
            'explanation' => 'Every listener runs on every click: (1 + 10) × 2.',
        ],
        [
            'question' => 'What is the difference between `event.target` and `event.currentTarget`?',
            'options' => [
                'None',
                '`target` is the element where the event started; `currentTarget` is the element whose handler is running now',
                '`target` is the parent; `currentTarget` the child',
                '`currentTarget` is only for keyboard events',
            ],
            'correct_option' => 1,
            'explanation' => 'They differ when a handler on a parent receives an event that bubbled up from a child.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<div id="outer"><p id="inner">x</p></div>';
                let log = '';
                document.querySelector('#outer').addEventListener('click', () => (log += 'outer '));
                document.querySelector('#inner').addEventListener('click', () => (log += 'inner '));
                document.body.addEventListener('click', () => (log += 'body'));
                document.querySelector('#inner').click();
                console.log(log);
                JS,
            'options' => ['`inner outer body`', '`body outer inner`', '`inner`', '`outer inner body`'],
            'correct_option' => 0,
            'explanation' => 'The event runs the target\'s handlers first, then bubbles up through its ancestors.',
        ],
        [
            'question' => 'What is event delegation?',
            'options' => [
                'Copying an event to another element',
                'One listener on a parent handling events for all its children, including ones added later, using `event.target`',
                'Passing an event to a web worker',
                'Calling `stopPropagation()`',
            ],
            'correct_option' => 1,
            'explanation' => 'It relies on bubbling: the child\'s click reaches the parent\'s handler.',
        ],
        [
            'question' => 'What does `event.preventDefault()` do on a form\'s `submit` event?',
            'options' => [
                'Stops other handlers running',
                'Stops the browser\'s own action, sending the form and reloading the page, so your code can handle it',
                'Clears the form',
                'Stops the event bubbling',
            ],
            'correct_option' => 1,
            'explanation' => 'Stopping the bubbling is `stopPropagation()`, a different thing.',
        ],
        [
            'question' => 'Why does `button.removeEventListener(\'click\', () => count++)` not remove a listener added the same way?',
            'options' => [
                'Arrow functions can\'t be removed',
                'It\'s a new function, not the one that was added; you must pass the same function object',
                'removeEventListener only works for keyboard events',
                'It does remove it',
            ],
            'correct_option' => 1,
            'explanation' => 'Keep the handler in a variable, or use `{ once: true }` or an `AbortSignal`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<input>';
                const input = document.querySelector('input');
                let seen = '';
                input.addEventListener('input', () => (seen = input.value));
                input.value = 'typed';
                const before = seen;
                input.dispatchEvent(new Event('input'));
                console.log(`[${before}] [${seen}]`);
                JS,
            'options' => ['`[] [typed]`', '`[typed] [typed]`', '`[] []`', '`[typed] []`'],
            'correct_option' => 0,
            'explanation' => 'Setting `value` from code doesn\'t fire an `input` event; dispatching one does.',
        ],
        [
            'question' => 'How do you send data with your own event?',
            'options' => [
                '`new Event(\'name\', data)`',
                '`new CustomEvent(\'name\', { detail: data })`, read in the handler as `event.detail`',
                '`element.data = ...` before dispatching',
                'Events can\'t carry data',
            ],
            'correct_option' => 1,
            'explanation' => 'Then `element.dispatchEvent(event)` sends it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A like button',
            'prompt' => 'Make the `#like` button work: each click adds 1 to the number in `#likes`, and the first click also adds the class `liked` to the button.',
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<button id="like">♥ Like</button> <span id="likes">0</span> likes';
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<button id="like">♥ Like</button> <span id="likes">0</span> likes';

                const like = document.querySelector('#like');
                const likes = document.querySelector('#likes');

                like.addEventListener('click', () => {
                    likes.textContent = Number(likes.textContent) + 1;
                    like.classList.add('liked');
                });
                JS,
            'hint' => 'In a `click` listener, read the number with `Number(likes.textContent)`, add 1, write it back, and add the class.',
            'checks' => [
                ['label' => 'Counts clicks', 'code' => "const like = document.querySelector('#like');\nlike.click();\nlike.click();\nlike.click();\nreturn expectSame([document.querySelector('#likes').textContent, like.classList.contains('liked')], ['3', true]);"],
            ],
        ],
        [
            'title' => 'A live character count',
            'prompt' => "As the user types in `#message`, show how many characters are left out of 140 in `#remaining` (e.g. `'135 characters left'`), and add the class `over` to `#remaining` when the message is longer than 140 (`'-3 characters left'`).",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <textarea id="message" rows="3" cols="40"></textarea>
                    <p id="remaining">140 characters left</p>
                `;
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <textarea id="message" rows="3" cols="40"></textarea>
                    <p id="remaining">140 characters left</p>
                `;

                const message = document.querySelector('#message');
                const remaining = document.querySelector('#remaining');

                message.addEventListener('input', () => {
                    const left = 140 - message.value.length;
                    remaining.textContent = `${left} characters left`;
                    remaining.classList.toggle('over', left < 0);
                });
                JS,
            'hint' => 'Listen for `input` on the textarea, work out `140 - message.value.length`, and use `classList.toggle(\'over\', left < 0)`.',
            'checks' => [
                ['label' => 'Counts down as you type', 'code' => <<<'JS'
                    const message = document.querySelector('#message');
                    const remaining = document.querySelector('#remaining');
                    message.value = 'Hello';
                    message.dispatchEvent(new Event('input', { bubbles: true }));
                    return expectSame([remaining.textContent, remaining.classList.contains('over')], ['135 characters left', false]);
                    JS],
                ['label' => 'Over the limit, and back', 'code' => <<<'JS'
                    const message = document.querySelector('#message');
                    const remaining = document.querySelector('#remaining');
                    const type = (text) => {
                        message.value = text;
                        message.dispatchEvent(new Event('input', { bubbles: true }));
                        return [remaining.textContent, remaining.classList.contains('over')];
                    };
                    return expectSame([type('x'.repeat(143)), type('x'.repeat(140))], [['-3 characters left', true], ['0 characters left', false]]);
                    JS],
            ],
        ],
        [
            'title' => 'One listener for the whole list',
            'prompt' => "Add **one** click listener to `#todo` (not to each item) so that clicking an item's `.delete` button removes that item, and clicking anywhere else on an item toggles its `done` class. It must work for items added later too.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="todo">
                        <li><span>Buy milk</span> <button class="delete">Delete</button></li>
                        <li><span>Call Ben</span> <button class="delete">Delete</button></li>
                    </ul>
                `;

                for (const button of document.querySelectorAll('.delete')) {
                    button.addEventListener('click', () => button.parentElement.remove());
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="todo">
                        <li><span>Buy milk</span> <button class="delete">Delete</button></li>
                        <li><span>Call Ben</span> <button class="delete">Delete</button></li>
                    </ul>
                `;

                document.querySelector('#todo').addEventListener('click', (event) => {
                    const item = event.target.closest('li');
                    if (!item) {
                        return;
                    }
                    if (event.target.closest('.delete')) {
                        item.remove();
                    } else {
                        item.classList.toggle('done');
                    }
                });
                JS,
            'hint' => 'In the list\'s handler, `event.target.closest(\'li\')` finds the item; check `event.target.closest(\'.delete\')` to tell a delete from a toggle.',
            'checks' => [
                ['label' => 'Toggles and deletes', 'code' => <<<'JS'
                    const todo = document.querySelector('#todo');
                    todo.querySelector('li span').click();
                    const firstDone = todo.querySelector('li').classList.contains('done');
                    todo.querySelectorAll('.delete')[1].click();
                    return expectSame([firstDone, [...todo.querySelectorAll('li span')].map((s) => s.textContent)], [true, ['Buy milk']]);
                    JS],
                ['label' => 'Works for new items', 'code' => <<<'JS'
                    const todo = document.querySelector('#todo');
                    todo.insertAdjacentHTML('beforeend', '<li><span>Pay rent</span> <button class="delete">Delete</button></li>');
                    const added = todo.lastElementChild;
                    added.querySelector('span').click();
                    const toggled = added.classList.contains('done');
                    added.querySelector('.delete').click();
                    return expectSame([toggled, todo.contains(added)], [true, false]);
                    JS],
                ['label' => 'Uses one listener', 'code' => "return /querySelectorAll\\([^)]*\\)[\\s\\S]*addEventListener|forEach[\\s\\S]*addEventListener/.test(learnerCode()) ? 'Add a single listener to #todo instead of one per button.' : true;"],
            ],
        ],
        [
            'title' => 'Links handled in the page',
            'prompt' => "Make clicks on links inside `#tabs` show the matching panel instead of following the link: prevent the default action, then show the `.panel` whose `id` matches the link's `href` (without the `#`) and hide the others with the `hidden` property.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <nav id="tabs"><a href="#about">About</a> | <a href="#books">Books</a></nav>
                    <section class="panel" id="about">About the club</section>
                    <section class="panel" id="books" hidden>Our books</section>
                `;
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <nav id="tabs"><a href="#about">About</a> | <a href="#books">Books</a></nav>
                    <section class="panel" id="about">About the club</section>
                    <section class="panel" id="books" hidden>Our books</section>
                `;

                document.querySelector('#tabs').addEventListener('click', (event) => {
                    const link = event.target.closest('a');
                    if (!link) {
                        return;
                    }
                    event.preventDefault();
                    const id = link.getAttribute('href').slice(1);
                    for (const panel of document.querySelectorAll('.panel')) {
                        panel.hidden = panel.id !== id;
                    }
                });
                JS,
            'hint' => 'Call `event.preventDefault()`, take the id from `link.getAttribute(\'href\').slice(1)`, and set each panel\'s `hidden`.',
            'checks' => [
                ['label' => 'Shows the right panel', 'code' => <<<'JS'
                    const click = (text) => {
                        const link = [...document.querySelectorAll('#tabs a')].find((a) => a.textContent === text);
                        const event = new MouseEvent('click', { bubbles: true, cancelable: true });
                        link.dispatchEvent(event);
                        return [event.defaultPrevented, [...document.querySelectorAll('.panel')].filter((p) => !p.hidden).map((p) => p.id)];
                    };
                    return expectSame([click('Books'), click('About')], [[true, ['books']], [true, ['about']]]);
                    JS],
            ],
        ],
        [
            'title' => 'Keyboard shortcuts',
            'prompt' => "Listen for `keydown` on the whole `document`. When the key is `'n'`, add a new `<li>Note N</li>` to `#notes` (N counting from 1); when it's `'Escape'`, empty `#notes`. Ignore keys pressed while typing in an `<input>`.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <p>Press n for a new note, Escape to clear. <input placeholder="Typing here does nothing special"></p>
                    <ul id="notes"></ul>
                `;
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <p>Press n for a new note, Escape to clear. <input placeholder="Typing here does nothing special"></p>
                    <ul id="notes"></ul>
                `;

                const notes = document.querySelector('#notes');
                let next = 1;

                document.addEventListener('keydown', (event) => {
                    if (event.target.closest?.('input')) {
                        return;
                    }
                    if (event.key === 'n') {
                        const li = document.createElement('li');
                        li.textContent = `Note ${next++}`;
                        notes.append(li);
                    } else if (event.key === 'Escape') {
                        notes.replaceChildren();
                    }
                });
                JS,
            'hint' => 'Check `event.key`. Keys typed in the input bubble up to the document too; skip them with `event.target.closest(\'input\')`.',
            'checks' => [
                ['label' => 'n adds notes, Escape clears', 'code' => <<<'JS'
                    const press = (key, target = document.body) => target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
                    press('n');
                    press('x');
                    press('n');
                    const two = [...document.querySelectorAll('#notes li')].map((li) => li.textContent);
                    press('Escape');
                    const none = document.querySelectorAll('#notes li').length;
                    press('n');
                    return expectSame([two, none, document.querySelector('#notes li')?.textContent], [['Note 1', 'Note 2'], 0, 'Note 3']);
                    JS],
                ['label' => 'Typing in the input is ignored', 'code' => <<<'JS'
                    document.querySelector('#notes').replaceChildren();
                    document.querySelector('input').dispatchEvent(new KeyboardEvent('keydown', { key: 'n', bubbles: true }));
                    return expectSame(document.querySelectorAll('#notes li').length, 0);
                    JS],
            ],
        ],
        [
            'title' => 'Announce it',
            'prompt' => "Write `addToBasket(title, pence)`, which dispatches a `CustomEvent` called `'basket:add'` on `#basket`, with `{ title, pence }` as its `detail`. Then listen for that event on `document` (it must bubble) and keep `#total` showing the basket total, e.g. `'Total: £17.98'`.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<div id="basket"></div><p id="total">Total: £0.00</p>';

                function addToBasket(title, pence) {
                    document.querySelector('#basket').dispatchEvent(new Event('basket:add'));
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<div id="basket"></div><p id="total">Total: £0.00</p>';

                function addToBasket(title, pence) {
                    document.querySelector('#basket').dispatchEvent(new CustomEvent('basket:add', { detail: { title, pence }, bubbles: true }));
                }

                let total = 0;
                document.addEventListener('basket:add', (event) => {
                    total += event.detail.pence;
                    document.querySelector('#total').textContent = `Total: £${(total / 100).toFixed(2)}`;
                });
                JS,
            'hint' => '`new CustomEvent(\'basket:add\', { detail: { title, pence }, bubbles: true })`. The document\'s listener reads `event.detail.pence`.',
            'checks' => [
                ['label' => 'The event and its detail', 'code' => <<<'JS'
                    let received = null;
                    document.querySelector('#basket').addEventListener('basket:add', (event) => (received = event.detail), { once: true });
                    addToBasket('Atlas', 100);
                    return expectSame(received, { title: 'Atlas', pence: 100 });
                    JS],
                ['label' => 'The total follows', 'code' => <<<'JS'
                    const before = Number(document.querySelector('#total').textContent.replace(/[^\d.]/g, ''));
                    addToBasket('Dune', 999);
                    addToBasket('Emma', 799);
                    const after = Number(document.querySelector('#total').textContent.replace(/[^\d.]/g, ''));
                    return expectSame([Math.round((after - before) * 100), /^Total: £\d+\.\d\d$/.test(document.querySelector('#total').textContent)], [1798, true]);
                    JS],
            ],
        ],
    ],
];
