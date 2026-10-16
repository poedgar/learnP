<?php

// Lesson 5 of the JavaScript and the DOM course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope and on the same page;
// a check returns true or a message. Helpers: expectSame(), expectThrows(), learnerCode().

return [
    'slug' => 'building-a-small-app',
    'title' => 'Building a small app',
    'summary' => 'Structure an interactive page: keep the data in one state object, draw the page from it with a render function, change it through actions, and derive counts, filters and sort orders.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). Then use the app in the page below.
        document.body.innerHTML = `
            <button data-step="-1">−</button>
            <strong id="value"></strong>
            <button data-step="1">+</button>
            <p id="note"></p>
        `;

        const state = { value: 5, min: 0, max: 10 };

        function render() {
            document.querySelector('#value').textContent = state.value;
            document.querySelector('#note').textContent = state.value === state.max ? 'That is the most.' : '';
        }

        document.body.addEventListener('click', (event) => {
            const step = event.target.dataset.step;
            if (step === undefined) return;
            state.value = Math.min(state.max, Math.max(state.min, state.value + Number(step)));
            render();
        });

        render();
        JS,

    'quiz' => [
        [
            'question' => 'In the state-and-render approach, what do event handlers do?',
            'options' => [
                'Change the page directly',
                'Change the state, then call `render()` to redraw the page from it',
                'Call `render()` only',
                'Reload the page',
            ],
            'correct_option' => 1,
            'explanation' => 'The page is then always a picture of the state.',
        ],
        [
            'question' => 'Why work out counts and filtered lists inside `render()` rather than storing them in the state?',
            'options' => [
                'It\'s required by the DOM',
                'Values derived from the state can\'t get out of date; stored copies have to be kept in step by hand',
                'Storing numbers in objects is slow',
                'render() can\'t read the state',
            ],
            'correct_option' => 1,
            'explanation' => 'Store the facts; compute everything else.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const state = { items: [{ qty: 2 }, { qty: 3 }] };
                const count = () => state.items.reduce((n, item) => n + item.qty, 0);
                state.items.push({ qty: 1 });
                console.log(count());
                JS,
            'options' => ['`6`', '`5`', '`3`', '`2`'],
            'correct_option' => 0,
            'explanation' => 'A derived value is computed from the state when asked, so it includes the new item.',
        ],
        [
            'question' => 'Why put a `data-id` on each rendered list item?',
            'options' => [
                'For styling',
                'So a handler can tell which item in the state a clicked element belongs to',
                'Browsers need it for lists',
                'It makes rendering faster',
            ],
            'correct_option' => 1,
            'explanation' => '`event.target.closest(\'[data-id]\').dataset.id` gives the id.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const ESCAPES = { '<': '&lt;', '>': '&gt;', '&': '&amp;' };
                const escape = (t) => t.replace(/[<>&]/g, (c) => ESCAPES[c]);
                document.body.innerHTML = `<p>${escape('<i>a</i> & b')}</p>`;
                console.log(document.querySelectorAll('i').length, document.querySelector('p').textContent);
                JS,
            'options' => ['`0 <i>a</i> & b`', '`1 a & b`', '`0 &lt;i&gt;a&lt;/i&gt; &amp; b`', '`1 <i>a</i> & b`'],
            'correct_option' => 0,
            'explanation' => 'Escaped, the tags are shown as text, so no `<i>` element is created.',
        ],
        [
            'question' => 'Why keep a text input outside the part of the page that `render()` rebuilds?',
            'options' => [
                'Inputs can\'t be rendered',
                'Rebuilding it replaces the element, losing the user\'s cursor and focus while they type',
                'It\'s faster to type in',
                'Forms must be outside lists',
            ],
            'correct_option' => 1,
            'explanation' => 'Or only change its value when it really differs.',
        ],
        [
            'question' => 'What does `data-action="remove"` on a button help with?',
            'options' => [
                'Removing the button automatically',
                'One delegated listener can look up which action to run from the attribute',
                'It disables the button',
                'Nothing; data attributes are only for CSS',
            ],
            'correct_option' => 1,
            'explanation' => '`actions[button.dataset.action](id)` routes every click.',
        ],
        [
            'question' => 'Why wrap `localStorage` calls in `try`/`catch`?',
            'options' => [
                'They\'re slow',
                'Storage can be unavailable (blocked, private browsing, sandboxed pages) and then it throws',
                'localStorage always throws',
                'To make them asynchronous',
            ],
            'correct_option' => 1,
            'explanation' => 'An app should still work, without saving, when storage isn\'t available.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Render from the state',
            'prompt' => "Write `render()`, which draws the page from `state`: one `<li>` per book in `#shelf`, with the book's `id` in `data-id`, its title as text, and the class `selected` on the selected book; and `#details` showing `'TITLE by AUTHOR'` for the selected book, or `'Pick a book'` when none is.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"></ul><p id="details"></p>';

                const state = {
                    books: [
                        { id: 1, title: 'Dune', author: 'Frank Herbert' },
                        { id: 2, title: 'Emma', author: 'Jane Austen' },
                    ],
                    selected: null,
                };

                function render() {
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"></ul><p id="details"></p>';

                const state = {
                    books: [
                        { id: 1, title: 'Dune', author: 'Frank Herbert' },
                        { id: 2, title: 'Emma', author: 'Jane Austen' },
                    ],
                    selected: null,
                };

                function render() {
                    const items = state.books.map((book) => {
                        const li = document.createElement('li');
                        li.dataset.id = book.id;
                        li.textContent = book.title;
                        li.classList.toggle('selected', book.id === state.selected);
                        return li;
                    });
                    document.querySelector('#shelf').replaceChildren(...items);

                    const book = state.books.find((b) => b.id === state.selected);
                    document.querySelector('#details').textContent = book ? `${book.title} by ${book.author}` : 'Pick a book';
                }

                render();
                JS,
            'hint' => 'Map the books to `<li>` elements and `replaceChildren()` them in. Find the selected book for the details.',
            'checks' => [
                ['label' => 'Nothing selected', 'code' => <<<'JS'
                    render();
                    const items = [...document.querySelectorAll('#shelf li')];
                    return expectSame([items.map((li) => [li.dataset.id, li.textContent, li.className]), document.querySelector('#details').textContent], [[['1', 'Dune', ''], ['2', 'Emma', '']], 'Pick a book']);
                    JS],
                ['label' => 'Follows the state', 'code' => <<<'JS'
                    state.selected = 2;
                    state.books.push({ id: 3, title: 'Circe', author: 'Madeline Miller' });
                    render();
                    const selected = [...document.querySelectorAll('#shelf li.selected')].map((li) => li.textContent);
                    return expectSame([document.querySelectorAll('#shelf li').length, selected, document.querySelector('#details').textContent], [3, ['Emma'], 'Emma by Jane Austen']);
                    JS],
            ],
        ],
        [
            'title' => 'Click to select',
            'prompt' => 'Write the action `select(id)`, which sets `state.selected` and calls `render()`, and add one click listener on `#shelf` so that clicking a book selects it, and clicking the selected book again clears the selection.',
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"></ul><p id="details"></p>';

                const state = {
                    books: [
                        { id: 1, title: 'Dune', author: 'Frank Herbert' },
                        { id: 2, title: 'Emma', author: 'Jane Austen' },
                    ],
                    selected: null,
                };

                function render() {
                    document.querySelector('#shelf').replaceChildren(
                        ...state.books.map((book) => {
                            const li = Object.assign(document.createElement('li'), { textContent: book.title });
                            li.dataset.id = book.id;
                            li.classList.toggle('selected', book.id === state.selected);
                            return li;
                        }),
                    );
                    const book = state.books.find((b) => b.id === state.selected);
                    document.querySelector('#details').textContent = book ? `${book.title} by ${book.author}` : 'Pick a book';
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"></ul><p id="details"></p>';

                const state = {
                    books: [
                        { id: 1, title: 'Dune', author: 'Frank Herbert' },
                        { id: 2, title: 'Emma', author: 'Jane Austen' },
                    ],
                    selected: null,
                };

                function render() {
                    document.querySelector('#shelf').replaceChildren(
                        ...state.books.map((book) => {
                            const li = Object.assign(document.createElement('li'), { textContent: book.title });
                            li.dataset.id = book.id;
                            li.classList.toggle('selected', book.id === state.selected);
                            return li;
                        }),
                    );
                    const book = state.books.find((b) => b.id === state.selected);
                    document.querySelector('#details').textContent = book ? `${book.title} by ${book.author}` : 'Pick a book';
                }

                function select(id) {
                    state.selected = id;
                    render();
                }

                document.querySelector('#shelf').addEventListener('click', (event) => {
                    const item = event.target.closest('li');
                    if (!item) return;
                    const id = Number(item.dataset.id);
                    select(state.selected === id ? null : id);
                });

                render();
                JS,
            'hint' => 'The `<li>` elements are rebuilt on every render, so listen on `#shelf` and read `Number(item.dataset.id)`.',
            'checks' => [
                ['label' => 'select()', 'code' => "select(1);\nreturn expectSame([state.selected, document.querySelector('#details').textContent], [1, 'Dune by Frank Herbert']);"],
                ['label' => 'Clicking selects and clears', 'code' => <<<'JS'
                    const clickBook = (title) => [...document.querySelectorAll('#shelf li')].find((li) => li.textContent === title).click();
                    clickBook('Emma');
                    const first = document.querySelector('#details').textContent;
                    clickBook('Emma');
                    const second = document.querySelector('#details').textContent;
                    clickBook('Dune');
                    return expectSame([first, second, document.querySelector('#details').textContent, state.selected], ['Emma by Jane Austen', 'Pick a book', 'Dune by Frank Herbert', 1]);
                    JS],
            ],
        ],
        [
            'title' => 'A basket with derived totals',
            'prompt' => "Write `changeQty(id, delta)`, which changes an item's `qty` in `state.items` (removing the item when it reaches 0) and calls `render()`. Write `render()` so that `#count` shows the total number of items (e.g. `'3 items'`, `'1 item'`) and `#total` the total price (e.g. `'£27.97'`), both worked out from `state.items`.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<p id="count"></p><p id="total"></p>';

                const state = {
                    items: [
                        { id: 1, title: 'Dune', pence: 999, qty: 1 },
                        { id: 2, title: 'Circe', pence: 1299, qty: 2 },
                    ],
                    count: 3,
                };

                function changeQty(id, delta) {
                    state.count += delta;
                }

                function render() {
                    document.querySelector('#count').textContent = `${state.count} items`;
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<p id="count"></p><p id="total"></p>';

                const state = {
                    items: [
                        { id: 1, title: 'Dune', pence: 999, qty: 1 },
                        { id: 2, title: 'Circe', pence: 1299, qty: 2 },
                    ],
                };

                function changeQty(id, delta) {
                    const item = state.items.find((i) => i.id === id);
                    if (item) {
                        item.qty += delta;
                        state.items = state.items.filter((i) => i.qty > 0);
                    }
                    render();
                }

                function render() {
                    const count = state.items.reduce((n, item) => n + item.qty, 0);
                    const pence = state.items.reduce((sum, item) => sum + item.pence * item.qty, 0);
                    document.querySelector('#count').textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
                    document.querySelector('#total').textContent = `£${(pence / 100).toFixed(2)}`;
                }

                render();
                JS,
            'hint' => 'Don\'t store the count: work out the count and the total from `state.items` with `reduce()` each time you render.',
            'checks' => [
                ['label' => 'Starting totals', 'code' => "render();\nreturn expectSame([document.querySelector('#count').textContent, document.querySelector('#total').textContent], ['3 items', '£35.97']);"],
                ['label' => 'Changing quantities', 'code' => <<<'JS'
                    const read = () => [document.querySelector('#count').textContent, document.querySelector('#total').textContent];
                    changeQty(2, -1);
                    const a = read();
                    changeQty(1, -1);
                    const b = read();
                    return expectSame([a, b, state.items.map((i) => i.id)], [['2 items', '£22.98'], ['1 item', '£12.99'], [2]]);
                    JS],
                ['label' => 'Works out totals from the items', 'code' => <<<'JS'
                    state.items.push({ id: 9, title: 'Atlas', pence: 2500, qty: 2 });
                    render();
                    return expectSame(document.querySelector('#count').textContent, `${state.items.reduce((n, i) => n + i.qty, 0)} items`);
                    JS],
            ],
        ],
        [
            'title' => 'Filter buttons',
            'prompt' => "Clicking a button in `#filters` sets `state.filter` to its `data-filter` (`all`, `active` or `done`) and re-renders. Write `render()` to list the matching tasks' text in `#tasks`, and set `aria-pressed` to `'true'` on the current filter's button and `'false'` on the others.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <div id="filters">
                        <button data-filter="all">All</button>
                        <button data-filter="active">Active</button>
                        <button data-filter="done">Done</button>
                    </div>
                    <ul id="tasks"></ul>
                `;

                const state = {
                    filter: 'all',
                    tasks: [
                        { text: 'Buy milk', done: true },
                        { text: 'Write report', done: false },
                        { text: 'Call Ben', done: false },
                    ],
                };

                function render() {
                    document.querySelector('#tasks').innerHTML = state.tasks.map((t) => `<li>${t.text}</li>`).join('');
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <div id="filters">
                        <button data-filter="all">All</button>
                        <button data-filter="active">Active</button>
                        <button data-filter="done">Done</button>
                    </div>
                    <ul id="tasks"></ul>
                `;

                const state = {
                    filter: 'all',
                    tasks: [
                        { text: 'Buy milk', done: true },
                        { text: 'Write report', done: false },
                        { text: 'Call Ben', done: false },
                    ],
                };

                const matches = {
                    all: () => true,
                    active: (task) => !task.done,
                    done: (task) => task.done,
                };

                function render() {
                    const items = state.tasks
                        .filter(matches[state.filter])
                        .map((task) => Object.assign(document.createElement('li'), { textContent: task.text }));
                    document.querySelector('#tasks').replaceChildren(...items);

                    for (const button of document.querySelectorAll('#filters button')) {
                        button.setAttribute('aria-pressed', String(button.dataset.filter === state.filter));
                    }
                }

                document.querySelector('#filters').addEventListener('click', (event) => {
                    const button = event.target.closest('[data-filter]');
                    if (!button) return;
                    state.filter = button.dataset.filter;
                    render();
                });

                render();
                JS,
            'hint' => 'Keep a map from filter name to a test function, filter the tasks with it in `render()`, and set every button\'s `aria-pressed`.',
            'checks' => [
                ['label' => 'Filters the list', 'code' => <<<'JS'
                    const click = (name) => {
                        document.querySelector(`[data-filter="${name}"]`).click();
                        return [...document.querySelectorAll('#tasks li')].map((li) => li.textContent);
                    };
                    return expectSame([click('active'), click('done'), click('all')], [['Write report', 'Call Ben'], ['Buy milk'], ['Buy milk', 'Write report', 'Call Ben']]);
                    JS],
                ['label' => 'Shows the chosen filter', 'code' => <<<'JS'
                    document.querySelector('[data-filter="done"]').click();
                    return expectSame([...document.querySelectorAll('#filters button')].map((b) => b.getAttribute('aria-pressed')), ['false', 'false', 'true']);
                    JS],
            ],
        ],
        [
            'title' => 'Sort by a column',
            'prompt' => "Clicking a column's button in the table header sorts the rows by that column (`title` alphabetically, `year` numerically), ascending; clicking the same column again reverses the order. Keep `{ sortBy, ascending }` in `state` and render `#rows` from `state.books`, one `<tr>` with two `<td>`s per book.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <table>
                        <thead><tr><th><button data-sort="title">Title</button></th><th><button data-sort="year">Year</button></th></tr></thead>
                        <tbody id="rows"></tbody>
                    </table>
                `;

                const state = {
                    sortBy: 'title',
                    ascending: true,
                    books: [
                        { title: 'Emma', year: 1815 },
                        { title: 'Dune', year: 1965 },
                        { title: 'Circe', year: 2018 },
                    ],
                };

                function render() {
                    document.querySelector('#rows').innerHTML = state.books.map((b) => `<tr><td>${b.title}</td><td>${b.year}</td></tr>`).join('');
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <table>
                        <thead><tr><th><button data-sort="title">Title</button></th><th><button data-sort="year">Year</button></th></tr></thead>
                        <tbody id="rows"></tbody>
                    </table>
                `;

                const state = {
                    sortBy: 'title',
                    ascending: true,
                    books: [
                        { title: 'Emma', year: 1815 },
                        { title: 'Dune', year: 1965 },
                        { title: 'Circe', year: 2018 },
                    ],
                };

                function compare(a, b) {
                    const order = state.sortBy === 'year' ? a.year - b.year : a.title.localeCompare(b.title);
                    return state.ascending ? order : -order;
                }

                function render() {
                    const rows = [...state.books].sort(compare).map((book) => {
                        const tr = document.createElement('tr');
                        for (const value of [book.title, book.year]) {
                            tr.append(Object.assign(document.createElement('td'), { textContent: value }));
                        }
                        return tr;
                    });
                    document.querySelector('#rows').replaceChildren(...rows);
                }

                document.querySelector('thead').addEventListener('click', (event) => {
                    const button = event.target.closest('[data-sort]');
                    if (!button) return;
                    const column = button.dataset.sort;
                    state.ascending = state.sortBy === column ? !state.ascending : true;
                    state.sortBy = column;
                    render();
                });

                render();
                JS,
            'hint' => 'Sort a copy of the books in `render()` with a compare function based on `state.sortBy` and `state.ascending`; the click handler only changes those two values.',
            'checks' => [
                ['label' => 'Sorted by title to start with', 'code' => "render();\nreturn expectSame([...document.querySelectorAll('#rows tr')].map((tr) => tr.firstElementChild.textContent), ['Circe', 'Dune', 'Emma']);"],
                ['label' => 'Clicking sorts and reverses', 'code' => <<<'JS'
                    const titles = () => [...document.querySelectorAll('#rows tr')].map((tr) => tr.firstElementChild.textContent);
                    const click = (column) => {
                        document.querySelector(`[data-sort="${column}"]`).click();
                        return titles();
                    };
                    return expectSame([click('year'), click('year'), click('title'), click('title')], [['Emma', 'Dune', 'Circe'], ['Circe', 'Dune', 'Emma'], ['Circe', 'Dune', 'Emma'], ['Emma', 'Dune', 'Circe']]);
                    JS],
                ['label' => 'Rows have two cells', 'code' => "return expectSame([...document.querySelectorAll('#rows tr')].every((tr) => tr.children.length === 2 && tr.firstElementChild.tagName === 'TD'), true);"],
            ],
        ],
        [
            'title' => 'Undo',
            'prompt' => "Make the counter's buttons work through actions that remember history: `+1`, `+10` and `Reset` change `state.value` (saving the previous value in `state.history` first), and `Undo` restores the most recent saved value (doing nothing when there's none). `render()` shows the value in `#value` and disables `Undo` when there's nothing to undo.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <p id="value"></p>
                    <button data-action="add1">+1</button>
                    <button data-action="add10">+10</button>
                    <button data-action="reset">Reset</button>
                    <button data-action="undo">Undo</button>
                `;

                const state = { value: 0, history: [] };

                function render() {
                    document.querySelector('#value').textContent = state.value;
                }

                render();
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <p id="value"></p>
                    <button data-action="add1">+1</button>
                    <button data-action="add10">+10</button>
                    <button data-action="reset">Reset</button>
                    <button data-action="undo">Undo</button>
                `;

                const state = { value: 0, history: [] };

                function change(next) {
                    state.history.push(state.value);
                    state.value = next;
                }

                const actions = {
                    add1: () => change(state.value + 1),
                    add10: () => change(state.value + 10),
                    reset: () => change(0),
                    undo: () => {
                        if (state.history.length > 0) {
                            state.value = state.history.pop();
                        }
                    },
                };

                function render() {
                    document.querySelector('#value').textContent = state.value;
                    document.querySelector('[data-action="undo"]').disabled = state.history.length === 0;
                }

                document.body.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-action]');
                    if (!button) return;
                    actions[button.dataset.action]();
                    render();
                });

                render();
                JS,
            'hint' => 'Every changing action first pushes `state.value` onto `state.history`; undo pops it back off.',
            'checks' => [
                ['label' => 'Changes and undoes', 'code' => <<<'JS'
                    const press = (action) => document.querySelector(`[data-action="${action}"]`).click();
                    const value = () => document.querySelector('#value').textContent;
                    const seen = [];
                    for (const action of ['add1', 'add10', 'add1', 'undo', 'reset', 'undo', 'undo']) {
                        press(action);
                        seen.push(value());
                    }
                    return expectSame(seen, ['1', '11', '12', '11', '0', '11', '1']);
                    JS],
                ['label' => 'Undo is disabled when there is nothing to undo', 'code' => <<<'JS'
                    const undo = document.querySelector('[data-action="undo"]');
                    while (!undo.disabled && state.history.length) undo.click();
                    const disabled = undo.disabled;
                    document.querySelector('[data-action="add1"]').click();
                    return expectSame([disabled, undo.disabled], [true, false]);
                    JS],
            ],
        ],
    ],
];
