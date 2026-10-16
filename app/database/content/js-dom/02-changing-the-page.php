<?php

// Lesson 2 of the JavaScript and the DOM course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope and on the same page;
// a check returns true or a message. Helpers: expectSame(), expectThrows(), learnerCode().

return [
    'slug' => 'changing-the-page',
    'title' => 'Changing the page',
    'summary' => 'Change a page from JavaScript: set text, create, add, move and remove elements, switch classes, styles and attributes, and build lists from data safely, without innerHTML pitfalls.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). The page appears below the output.
        document.body.innerHTML = '<h1>Shopping list</h1><ul id="list"></ul>';

        const list = document.querySelector('#list');

        for (const item of ['Bread', 'Milk', 'Tea']) {
            const li = document.createElement('li');
            li.textContent = item;
            list.append(li);
        }

        list.firstElementChild.style.fontWeight = 'bold';
        list.lastElementChild.remove();
        console.log(list.children.length);
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<ul><li>b</li></ul>';
                const ul = document.querySelector('ul');
                const a = document.createElement('li');
                a.textContent = 'a';
                ul.prepend(a);
                ul.append('!');
                console.log(ul.textContent);
                JS,
            'options' => ['`ab!`', '`ba!`', '`a`', '`!ab`'],
            'correct_option' => 0,
            'explanation' => '`prepend()` adds at the start, `append()` at the end; a string becomes text.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<div id="a"><p>x</p></div><div id="b"></div>';
                const p = document.querySelector('p');
                document.querySelector('#b').append(p);
                console.log(document.querySelector('#a').children.length, document.querySelector('#b').children.length);
                JS,
            'options' => ['`0 1`', '`1 1`', '`1 0`', '`0 0`'],
            'correct_option' => 0,
            'explanation' => 'An element can only be in one place, so appending it elsewhere moves it.',
        ],
        [
            'question' => 'Why not put text typed by a user into `innerHTML`?',
            'options' => [
                'It is slow',
                'It\'s treated as HTML, so it can add elements to the page and even run an attacker\'s script; use `textContent` instead',
                'innerHTML can\'t hold text',
                'It changes the user\'s text to upper case',
            ],
            'correct_option' => 1,
            'explanation' => 'This is how cross-site scripting (XSS) attacks work.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<p class="a"></p>';
                const p = document.querySelector('p');
                const first = p.classList.toggle('b');
                const second = p.classList.toggle('a');
                console.log(first, second, p.className);
                JS,
            'options' => ['`true false b`', '`false true a b`', '`true true a b`', '`false false a`'],
            'correct_option' => 0,
            'explanation' => '`toggle()` returns whether the class is there afterwards: `b` was added, `a` removed.',
        ],
        [
            'question' => 'How do you set the CSS `background-color` of an element from JavaScript?',
            'options' => [
                '`el.style.background-color = \'gold\'`',
                '`el.style.backgroundColor = \'gold\'`',
                '`el.css(\'background-color\', \'gold\')`',
                '`el.backgroundColor = \'gold\'`',
            ],
            'correct_option' => 1,
            'explanation' => 'Style properties use camelCase in JavaScript.',
        ],
        [
            'question' => 'What does `list.replaceChildren(...items)` do?',
            'options' => [
                'Adds the items to the end',
                'Removes everything inside `list` and puts the items in instead',
                'Replaces `list` itself',
                'Swaps the items\' order',
            ],
            'correct_option' => 1,
            'explanation' => 'With no arguments, it simply empties the element.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<p id="p"></p>';
                const p = document.querySelector('#p');
                p.textContent = '<b>bold</b>';
                console.log(p.children.length, p.innerHTML);
                JS,
            'options' => ['`0 &lt;b&gt;bold&lt;/b&gt;`', '`1 <b>bold</b>`', '`0 <b>bold</b>`', '`1 bold`'],
            'correct_option' => 0,
            'explanation' => '`textContent` sets text, so the tags are shown as characters, escaped in the HTML.',
        ],
        [
            'question' => 'Which attribute hides an element without any CSS?',
            'options' => ['`invisible`', '`hidden`', '`display`', '`aria-hidden`'],
            'correct_option' => 1,
            'explanation' => '`el.hidden = true` sets it; `aria-hidden` only hides it from screen readers.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Add a book, safely',
            'prompt' => 'Write `addBook(title)`, which adds an `<li class="book">` with `title` as its text to the end of `#shelf`, and returns the new element. Titles can contain `<` and `>`, which must appear as text, not become tags.',
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"><li class="book">Dune</li></ul>';

                function addBook(title) {
                    document.querySelector('#shelf').innerHTML += `<li class="book">${title}</li>`;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<ul id="shelf"><li class="book">Dune</li></ul>';

                function addBook(title) {
                    const li = document.createElement('li');
                    li.className = 'book';
                    li.textContent = title;
                    document.querySelector('#shelf').append(li);
                    return li;
                }
                JS,
            'hint' => 'Use `createElement(\'li\')`, set `className` and `textContent`, then `append()` it.',
            'checks' => [
                ['label' => 'Adds to the end', 'code' => "const added = addBook('Emma');\nconst items = [...document.querySelectorAll('#shelf .book')].map((li) => li.textContent);\nreturn expectSame([items, added?.tagName], [['Dune', 'Emma'], 'LI']);"],
                ['label' => 'Tags in a title stay text', 'code' => "const added = addBook('<b>Bold</b> & brave');\nreturn expectSame([added.textContent, added.children.length], ['<b>Bold</b> & brave', 0]);"],
            ],
        ],
        [
            'title' => 'Mark as read',
            'prompt' => "Write `toggleRead(title)`, which finds the `.book` whose text is `title`, switches its `read` class on or off, and returns whether it's now read. If there's no such book, return `null`.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="shelf">
                        <li class="book">Dune</li>
                        <li class="book read">Emma</li>
                    </ul>
                `;

                function toggleRead(title) {
                    document.querySelector('.book').classList.add('read');
                    return true;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="shelf">
                        <li class="book">Dune</li>
                        <li class="book read">Emma</li>
                    </ul>
                `;

                function toggleRead(title) {
                    const book = [...document.querySelectorAll('.book')].find((li) => li.textContent === title);
                    return book ? book.classList.toggle('read') : null;
                }
                JS,
            'hint' => 'Find the book with `[...document.querySelectorAll(\'.book\')].find(...)`, then return `classList.toggle(\'read\')`.',
            'checks' => [
                ['label' => 'Toggles the right book', 'code' => <<<'JS'
                    const results = [toggleRead('Dune'), toggleRead('Emma'), toggleRead('Dune')];
                    const read = [...document.querySelectorAll('.book.read')].map((li) => li.textContent);
                    return expectSame([results, read], [[true, false, false], []]);
                    JS],
                ['label' => 'Missing books', 'code' => "return expectSame(toggleRead('Atlas'), null);"],
            ],
        ],
        [
            'title' => 'Render a list',
            'prompt' => "Write `renderResults(books)`, where each book is `{ title, author }`. Replace whatever is in `#results` with one `<li>` per book, with text `'TITLE by AUTHOR'`. With no books, show a single `<li class=\"empty\">No results</li>`.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<ul id="results"><li>Searching...</li></ul>';

                function renderResults(books) {
                    for (const book of books) {
                        const li = document.createElement('li');
                        li.textContent = book.title;
                        document.querySelector('#results').append(li);
                    }
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<ul id="results"><li>Searching...</li></ul>';

                function renderResults(books) {
                    const items = books.map(({ title, author }) => {
                        const li = document.createElement('li');
                        li.textContent = `${title} by ${author}`;
                        return li;
                    });
                    if (items.length === 0) {
                        const empty = document.createElement('li');
                        empty.className = 'empty';
                        empty.textContent = 'No results';
                        items.push(empty);
                    }
                    document.querySelector('#results').replaceChildren(...items);
                }
                JS,
            'hint' => 'Build an array of `<li>` elements, add the empty message if it\'s empty, then `replaceChildren(...items)`.',
            'checks' => [
                ['label' => 'Replaces the list', 'code' => <<<'JS'
                    renderResults([{ title: 'Dune', author: 'Frank Herbert' }, { title: 'Emma', author: 'Jane Austen' }]);
                    renderResults([{ title: 'Circe', author: 'Madeline Miller' }]);
                    return expectSame([...document.querySelectorAll('#results li')].map((li) => li.textContent), ['Circe by Madeline Miller']);
                    JS],
                ['label' => 'No results', 'code' => <<<'JS'
                    renderResults([]);
                    const items = document.querySelectorAll('#results li');
                    return expectSame([items.length, items[0]?.className, items[0]?.textContent], [1, 'empty', 'No results']);
                    JS],
            ],
        ],
        [
            'title' => 'A progress bar',
            'prompt' => "Write `setProgress(percent)`, which sets `#bar`'s inline `width` style to `percent` followed by `%`, its `aria-valuenow` attribute to the number, and `#label`'s text to e.g. `'40% done'`. Keep `percent` between 0 and 100 and round it to a whole number.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <div class="track"><div id="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div></div>
                    <p id="label"></p>
                `;

                function setProgress(percent) {
                    document.querySelector('#bar').style.width = percent;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <div class="track"><div id="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div></div>
                    <p id="label"></p>
                `;

                function setProgress(percent) {
                    const value = Math.round(Math.min(Math.max(percent, 0), 100));
                    const bar = document.querySelector('#bar');
                    bar.style.width = `${value}%`;
                    bar.setAttribute('aria-valuenow', String(value));
                    document.querySelector('#label').textContent = `${value}% done`;
                }
                JS,
            'hint' => 'Clamp with `Math.min(Math.max(percent, 0), 100)`, then set `style.width`, `setAttribute(\'aria-valuenow\', ...)` and the label\'s `textContent`.',
            'checks' => [
                ['label' => 'Shows the progress', 'code' => <<<'JS'
                    setProgress(40);
                    const bar = document.querySelector('#bar');
                    return expectSame([bar.style.width, bar.getAttribute('aria-valuenow'), document.querySelector('#label').textContent], ['40%', '40', '40% done']);
                    JS],
                ['label' => 'Rounds and stays in range', 'code' => <<<'JS'
                    const read = () => [document.querySelector('#bar').style.width, document.querySelector('#label').textContent];
                    setProgress(66.6);
                    const a = read();
                    setProgress(150);
                    const b = read();
                    setProgress(-5);
                    return expectSame([a, b, read()], [['67%', '67% done'], ['100%', '100% done'], ['0%', '0% done']]);
                    JS],
            ],
        ],
        [
            'title' => 'Clear the finished ones',
            'prompt' => 'Write `removeDone()`, which removes every `<li>` with the class `done` from the page, and returns how many it removed.',
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="tasks">
                        <li class="done">Buy milk</li>
                        <li>Write report</li>
                        <li class="done">Call Ben</li>
                        <li class="done urgent">Pay rent</li>
                    </ul>
                `;

                function removeDone() {
                    document.querySelector('.done').remove();
                    return 1;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="tasks">
                        <li class="done">Buy milk</li>
                        <li>Write report</li>
                        <li class="done">Call Ben</li>
                        <li class="done urgent">Pay rent</li>
                    </ul>
                `;

                function removeDone() {
                    const done = document.querySelectorAll('li.done');
                    for (const li of done) {
                        li.remove();
                    }
                    return done.length;
                }
                JS,
            'hint' => '`querySelectorAll()` gives a fixed list, so you can `remove()` each element while looping over it.',
            'checks' => [
                ['label' => 'Removes them all', 'code' => "const removed = removeDone();\nreturn expectSame([removed, [...document.querySelectorAll('#tasks li')].map((li) => li.textContent)], [3, ['Write report']]);"],
                ['label' => 'Nothing to remove', 'code' => "removeDone();\nreturn expectSame(removeDone(), 0);"],
            ],
        ],
        [
            'title' => 'Move to the top',
            'prompt' => "Write `moveToTop(name)`, which moves the `<li>` whose text is `name` to the start of its list (without copying it), and returns `true`; or returns `false` if there's no such item.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = '<ol id="queue"><li>Amara</li><li>Ben</li><li>Cy</li></ol>';

                function moveToTop(name) {
                    const queue = document.querySelector('#queue');
                    const li = document.createElement('li');
                    li.textContent = name;
                    queue.prepend(li);
                    return true;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = '<ol id="queue"><li>Amara</li><li>Ben</li><li>Cy</li></ol>';

                function moveToTop(name) {
                    const item = [...document.querySelectorAll('#queue li')].find((li) => li.textContent === name);
                    if (!item) {
                        return false;
                    }
                    item.parentElement.prepend(item);
                    return true;
                }
                JS,
            'hint' => 'Find the existing `<li>`, then `prepend()` it to its own parent: adding an element that\'s already on the page moves it.',
            'checks' => [
                ['label' => 'Moves the item', 'code' => <<<'JS'
                    const cy = document.querySelector('#queue').lastElementChild;
                    const moved = moveToTop('Cy');
                    const order = [...document.querySelectorAll('#queue li')].map((li) => li.textContent);
                    return expectSame([moved, order, document.querySelector('#queue').firstElementChild === cy], [true, ['Cy', 'Amara', 'Ben'], true]);
                    JS],
                ['label' => 'Unknown names', 'code' => "const result = moveToTop('Dee');\nreturn expectSame([result, document.querySelectorAll('#queue li').length], [false, 3]);"],
            ],
        ],
    ],
];
