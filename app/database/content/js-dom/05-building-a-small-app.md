## State first, page second

As a page gets more interactive, keeping track of what's on it gets hard if every handler edits the page directly.
A simple, sturdy approach is to keep the data in one place, the **state**, and write one **render** function that
draws the page from the state. Handlers never touch the page: they change the state, then call `render()`.

```javascript
document.body.innerHTML = '<p id="view"></p><button id="more">More</button>';

const state = { count: 0 };

function render() {
    document.querySelector('#view').textContent = `Count: ${state.count}`;
}

document.querySelector('#more').addEventListener('click', () => {
    state.count++;
    render();
});

render();
document.querySelector('#more').click();
console.log(document.querySelector('#view').textContent);
```

The page is then always a picture of the state, and you can understand it by reading `render()` alone.

## Rendering lists from state

`render()` can rebuild a whole list from an array each time. For small lists that's fast enough, and it can't get out
of step with the data. Keep an id on each element, in a `data-` attribute, so handlers know which item was used:

```javascript
document.body.innerHTML = '<ul id="list"></ul><p id="left"></p>';

const state = {
    todos: [
        { id: 1, text: 'Buy milk', done: true },
        { id: 2, text: 'Write report', done: false },
    ],
};

function render() {
    const items = state.todos.map((todo) => {
        const li = document.createElement('li');
        li.dataset.id = todo.id;
        li.textContent = todo.text;
        li.classList.toggle('done', todo.done);
        return li;
    });
    document.querySelector('#list').replaceChildren(...items);
    const left = state.todos.filter((todo) => !todo.done).length;
    document.querySelector('#left').textContent = `${left} left`;
}

render();
console.log(document.querySelector('#list').innerHTML, document.querySelector('#left').textContent);
```

## Actions change the state

Functions that change the state, often called **actions**, are easy to test on their own: call them, then look at
the state or the page.

```javascript
const state = { todos: [], nextId: 1 };

function addTodo(text) {
    const trimmed = text.trim();
    if (trimmed === '') {
        return;
    }
    state.todos.push({ id: state.nextId++, text: trimmed, done: false });
}

function toggleTodo(id) {
    const todo = state.todos.find((t) => t.id === id);
    if (todo) {
        todo.done = !todo.done;
    }
}

addTodo('  Call Ben ');
addTodo('   ');
addTodo('Pay rent');
toggleTodo(1);

console.log(state.todos);
```

## One listener for every button

With event delegation and a `data-action` attribute on each button, one listener can route every click to the right
action:

```javascript
document.body.innerHTML = `
    <ul id="list">
        <li data-id="1">Dune <button data-action="like">♥</button> <button data-action="remove">×</button></li>
        <li data-id="2">Emma <button data-action="like">♥</button> <button data-action="remove">×</button></li>
    </ul>
`;

const actions = {
    like: (id) => console.log(`liked ${id}`),
    remove: (id) => console.log(`removed ${id}`),
};

document.querySelector('#list').addEventListener('click', (event) => {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const id = Number(button.closest('[data-id]').dataset.id);
    actions[button.dataset.action](id);
});

document.querySelectorAll('[data-action="remove"]')[1].click();
document.querySelectorAll('[data-action="like"]')[0].click();
```

## Derived data

Anything that can be worked out from the state, such as counts, totals or a filtered list, should be worked out in
`render()`, not stored separately. Stored copies drift out of date; derived values can't:

```javascript
const state = {
    filter: 'active',
    todos: [
        { id: 1, text: 'Buy milk', done: true },
        { id: 2, text: 'Write report', done: false },
        { id: 3, text: 'Call Ben', done: false },
    ],
};

const visible = state.todos.filter((todo) => (state.filter === 'all' ? true : state.filter === 'done' ? todo.done : !todo.done));
console.log(visible.map((todo) => todo.text), `${visible.length} of ${state.todos.length} shown`);
```

## Building HTML strings safely

Building a list with template literals and `innerHTML` is concise, but any text from users must be **escaped**, so
characters such as `<` show as text instead of becoming HTML:

```javascript
const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
const escape = (text) => String(text).replace(/[&<>"']/g, (c) => ESCAPES[c]);

document.body.innerHTML = '<ul id="comments"></ul>';
const comments = [{ who: 'Ben', text: 'Loved it <3' }, { who: '<b>Eve</b>', text: 'Hi' }];

document.querySelector('#comments').innerHTML = comments.map((c) => `<li><strong>${escape(c.who)}</strong>: ${escape(c.text)}</li>`).join('');

console.log(document.querySelectorAll('#comments b').length, document.querySelector('#comments').textContent);
```

## Keeping the user's place

Rebuilding an element the user is typing into loses their cursor. So keep inputs outside the part that `render()`
rebuilds, or only update their value when it really changed. Here the input sits outside the list:

```javascript
document.body.innerHTML = '<input id="filter" placeholder="Filter"><ul id="list"></ul>';

const books = ['Dune', 'Emma', 'Circe', 'Odes'];
const state = { query: '' };

function render() {
    const items = books
        .filter((title) => title.toLowerCase().includes(state.query.toLowerCase()))
        .map((title) => Object.assign(document.createElement('li'), { textContent: title }));
    document.querySelector('#list').replaceChildren(...items);
}

document.querySelector('#filter').addEventListener('input', (event) => {
    state.query = event.target.value;
    render();
});

render();
document.querySelector('#filter').value = 'e';
document.querySelector('#filter').dispatchEvent(new Event('input'));
console.log([...document.querySelectorAll('#list li')].map((li) => li.textContent));
```

## Saving between visits

In a normal web page, `localStorage.setItem(key, text)` keeps data between visits, and `JSON.stringify()` turns the
state into text for it. The sandbox can't use storage, and some browsers block it too, so wrap it in `try`:

```javascript
function save(state) {
    try {
        localStorage.setItem('todos', JSON.stringify(state.todos));
        return 'saved';
    } catch (error) {
        return `not saved (${error.name})`;
    }
}

console.log(save({ todos: [] }));
```

## Putting it together

A complete little to-do app: state, actions, render, a form and delegation. Use it in the page:

```javascript
document.body.innerHTML = `
    <style>.done { text-decoration: line-through; opacity: 0.6; } li button { margin-left: 0.5rem; }</style>
    <form id="new"><input name="text" placeholder="What needs doing?"> <button>Add</button></form>
    <ul id="list"></ul>
    <p id="left"></p>
`;

const state = { todos: [], nextId: 1 };

const actions = {
    add(text) {
        if (text.trim()) state.todos.push({ id: state.nextId++, text: text.trim(), done: false });
    },
    toggle(id) {
        const todo = state.todos.find((t) => t.id === id);
        todo.done = !todo.done;
    },
    remove(id) {
        state.todos = state.todos.filter((t) => t.id !== id);
    },
};

function render() {
    document.querySelector('#list').replaceChildren(
        ...state.todos.map((todo) => {
            const li = document.createElement('li');
            li.dataset.id = todo.id;
            li.classList.toggle('done', todo.done);
            const text = Object.assign(document.createElement('span'), { textContent: todo.text });
            text.dataset.action = 'toggle';
            const remove = Object.assign(document.createElement('button'), { textContent: '×', type: 'button' });
            remove.dataset.action = 'remove';
            li.append(text, remove);
            return li;
        }),
    );
    document.querySelector('#left').textContent = `${state.todos.filter((t) => !t.done).length} left`;
}

document.querySelector('#new').addEventListener('submit', (event) => {
    event.preventDefault();
    actions.add(event.target.elements.text.value);
    event.target.reset();
    render();
});

document.querySelector('#list').addEventListener('click', (event) => {
    const target = event.target.closest('[data-action]');
    if (!target) return;
    actions[target.dataset.action](Number(target.closest('li').dataset.id));
    render();
});

actions.add('Try clicking me');
actions.add('Or remove me');
render();
```

Next, try the quiz to check your understanding, then solve the challenges.
