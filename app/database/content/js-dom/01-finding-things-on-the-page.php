<?php

// Lesson 1 of the JavaScript and the DOM course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope and on the same page;
// a check returns true or a message. Helpers: expectSame(), expectThrows(), learnerCode().

return [
    'slug' => 'finding-things-on-the-page',
    'title' => 'Finding things on the page',
    'summary' => 'Read a web page from JavaScript: the DOM, querySelector and querySelectorAll, text and HTML, attributes and data- attributes, classes, and moving around the tree.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). The page appears below the output.
        document.body.innerHTML = `
            <h1>Reading list</h1>
            <ul>
                <li data-pages="412">Dune</li>
                <li data-pages="474" class="done">Emma</li>
                <li data-pages="393" class="done">Circe</li>
            </ul>
        `;

        const done = document.querySelectorAll('li.done');
        const pages = [...done].reduce((sum, li) => sum + Number(li.dataset.pages), 0);

        console.log(`${done.length} books finished, ${pages} pages`);
        JS,

    'quiz' => [
        [
            'question' => 'What is the DOM?',
            'options' => [
                'A JavaScript library',
                'The tree of objects the browser builds from a page\'s HTML, which JavaScript can read and change',
                'A CSS framework',
                'The server that sends the page',
            ],
            'correct_option' => 1,
            'explanation' => 'Every element is an object, reached from the global `document`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<p class="a">one</p><p class="a">two</p><p>three</p>';
                console.log(document.querySelector('.a').textContent, document.querySelectorAll('.a').length);
                JS,
            'options' => ['`one 2`', '`one 3`', '`two 2`', '`one,two 2`'],
            'correct_option' => 0,
            'explanation' => '`querySelector()` gives the first match; `querySelectorAll()` all of them.',
        ],
        [
            'question' => 'What does `document.querySelector(\'.nothing-like-this\')` give when nothing matches?',
            'options' => ['An empty NodeList', '`null`', '`undefined`', 'It throws'],
            'correct_option' => 1,
            'explanation' => 'So check for `null` before using the result, or use `?.`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<p id="p">Hello <b>world</b></p>';
                const p = document.querySelector('#p');
                console.log(p.textContent, '|', p.innerHTML);
                JS,
            'options' => ['`Hello world | Hello <b>world</b>`', '`Hello <b>world</b> | Hello world`', '`Hello world | Hello world`', '`Hello | <b>world</b>`'],
            'correct_option' => 0,
            'explanation' => '`textContent` is just the text; `innerHTML` keeps the tags.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<div data-user-id="7" data-is-admin="false"></div>';
                const div = document.querySelector('div');
                console.log(div.dataset.userId + 1, div.dataset.isAdmin === false);
                JS,
            'options' => ['`71 false`', '`8 true`', '`8 false`', '`71 true`'],
            'correct_option' => 0,
            'explanation' => 'Data attributes are strings: `\'7\' + 1` is `\'71\'`, and `\'false\'` isn\'t the boolean `false`.',
        ],
        [
            'question' => 'What does `element.closest(\'li\')` do?',
            'options' => [
                'Finds the nearest `li` inside the element',
                'Walks up from the element (starting with itself) to the nearest ancestor that is an `li`, or gives `null`',
                'Finds the next `li` sibling',
                'Closes the `li`',
            ],
            'correct_option' => 1,
            'explanation' => 'Handy for finding which item a clicked button belongs to.',
        ],
        [
            'question' => 'How do you use `map()` on the result of `querySelectorAll()`?',
            'options' => [
                'Call `.map()` on it directly',
                'Turn it into an array first, with `[...list]` or `Array.from(list)`',
                'You can\'t',
                'Use `.forEach()` instead, which returns an array',
            ],
            'correct_option' => 1,
            'explanation' => 'A NodeList has `forEach()` and works with `for...of`, but isn\'t an array.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<ul><li>a</li><li>b</li></ul><ol><li>c</li></ol>';
                const ol = document.querySelector('ol');
                console.log(ol.querySelectorAll('li').length, document.querySelectorAll('li').length);
                JS,
            'options' => ['`1 3`', '`3 3`', '`1 1`', '`2 3`'],
            'correct_option' => 0,
            'explanation' => 'Called on an element, `querySelectorAll()` only searches inside it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'All the titles',
            'prompt' => "Write `titles()`, returning an array of the text of every element with the class `book`, in page order. It reads the page each time it's called, so it must still be right after the page changes.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="shelf">
                        <li class="book">Dune</li>
                        <li class="book">Emma</li>
                        <li class="note">(more coming)</li>
                        <li class="book">Circe</li>
                    </ul>
                `;

                function titles() {
                    return ['Dune', 'Emma', 'Circe'];
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <ul id="shelf">
                        <li class="book">Dune</li>
                        <li class="book">Emma</li>
                        <li class="note">(more coming)</li>
                        <li class="book">Circe</li>
                    </ul>
                `;

                function titles() {
                    return [...document.querySelectorAll('.book')].map((book) => book.textContent);
                }
                JS,
            'hint' => '`[...document.querySelectorAll(\'.book\')].map((el) => el.textContent)`.',
            'checks' => [
                ['label' => 'The titles', 'code' => "return expectSame(titles(), ['Dune', 'Emma', 'Circe']);"],
                ['label' => 'Reads the page as it is now', 'code' => "document.querySelector('#shelf').innerHTML += '<li class=\"book\">Odes</li>';\ndocument.querySelector('.book').remove();\nreturn expectSame(titles(), ['Emma', 'Circe', 'Odes']);"],
            ],
        ],
        [
            'title' => 'The basket total',
            'prompt' => "Write `basketTotal()`, returning the total in pence of the `data-pence` attributes of the `.item` elements inside `#basket` (other parts of the page also have prices, which don't count).",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <aside><span class="item" data-pence="5000">Gift card (not in the basket)</span></aside>
                    <ul id="basket">
                        <li class="item" data-pence="999">Dune</li>
                        <li class="item" data-pence="799">Emma</li>
                    </ul>
                `;

                function basketTotal() {
                    let total = 0;
                    for (const item of document.querySelectorAll('.item')) {
                        total += item.dataset.pence;
                    }
                    return total;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <aside><span class="item" data-pence="5000">Gift card (not in the basket)</span></aside>
                    <ul id="basket">
                        <li class="item" data-pence="999">Dune</li>
                        <li class="item" data-pence="799">Emma</li>
                    </ul>
                `;

                function basketTotal() {
                    let total = 0;
                    for (const item of document.querySelectorAll('#basket .item')) {
                        total += Number(item.dataset.pence);
                    }
                    return total;
                }
                JS,
            'hint' => 'Narrow the selector to `#basket .item`, and convert each `dataset.pence` with `Number()`.',
            'checks' => [
                ['label' => 'The total', 'code' => 'return expectSame(basketTotal(), 1798);'],
                ['label' => 'Follows the page', 'code' => "document.querySelector('#basket').insertAdjacentHTML('beforeend', '<li class=\"item\" data-pence=\"1299\">Circe</li>');\nreturn expectSame(basketTotal(), 3097);"],
            ],
        ],
        [
            'title' => 'Find by data attribute',
            'prompt' => "Write `titleOf(id)`, returning the text of the `.title` inside the `<li>` whose `data-id` is `id` (a number), or `null` if there's no such book.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <ul>
                        <li data-id="7"><span class="title">Dune</span> <span class="author">Frank Herbert</span></li>
                        <li data-id="12"><span class="title">Emma</span> <span class="author">Jane Austen</span></li>
                    </ul>
                `;

                function titleOf(id) {
                    return document.querySelector('.title').textContent;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <ul>
                        <li data-id="7"><span class="title">Dune</span> <span class="author">Frank Herbert</span></li>
                        <li data-id="12"><span class="title">Emma</span> <span class="author">Jane Austen</span></li>
                    </ul>
                `;

                function titleOf(id) {
                    const item = document.querySelector(`li[data-id="${id}"]`);
                    return item ? item.querySelector('.title').textContent : null;
                }
                JS,
            'hint' => 'An attribute selector finds the item: `li[data-id="12"]`. Then search inside it for `.title`.',
            'checks' => [
                ['label' => 'Finds books', 'code' => "return expectSame([titleOf(12), titleOf(7)], ['Emma', 'Dune']);"],
                ['label' => 'Missing books', 'code' => 'return expectSame(titleOf(99), null);'],
            ],
        ],
        [
            'title' => 'External links',
            'prompt' => 'Write `externalLinks()`, returning the text of every link (`<a>`) whose `href` attribute, as written in the HTML, starts with `http://` or `https://`.',
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <nav>
                        <a href="/about">About us</a>
                        <a href="https://example.org/books">Book reviews</a>
                        <a href="#top">Back to top</a>
                        <a href="http://example.com">Old site</a>
                    </nav>
                `;

                function externalLinks() {
                    return [...document.querySelectorAll('a')].map((a) => a.textContent);
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <nav>
                        <a href="/about">About us</a>
                        <a href="https://example.org/books">Book reviews</a>
                        <a href="#top">Back to top</a>
                        <a href="http://example.com">Old site</a>
                    </nav>
                `;

                function externalLinks() {
                    return [...document.querySelectorAll('a')]
                        .filter((a) => /^https?:\/\//.test(a.getAttribute('href') ?? ''))
                        .map((a) => a.textContent);
                }
                JS,
            'hint' => 'The `href` property gives a full URL, even for `/about`; `getAttribute(\'href\')` gives what\'s written.',
            'checks' => [
                ['label' => 'Only external links', 'code' => "return expectSame(externalLinks(), ['Book reviews', 'Old site']);"],
                ['label' => 'Follows the page', 'code' => "document.querySelector('nav').insertAdjacentHTML('beforeend', '<a href=\"https://example.net\">Partner</a><a>No link</a>');\nreturn expectSame(externalLinks(), ['Book reviews', 'Old site', 'Partner']);"],
            ],
        ],
        [
            'title' => 'Which row?',
            'prompt' => "Write `rowIdOf(element)`, taking any element inside the table (such as one of its buttons) and returning the `data-id` of the table row it's in, as a number, or `null` if it isn't in a row.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <table>
                        <tr data-id="101"><td>Dune</td><td><button class="remove">Remove</button></td></tr>
                        <tr data-id="102"><td>Emma</td><td><span><button class="remove">Remove</button></span></td></tr>
                    </table>
                    <button id="clear">Clear all</button>
                `;

                function rowIdOf(element) {
                    return element.parentElement.parentElement.dataset.id;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <table>
                        <tr data-id="101"><td>Dune</td><td><button class="remove">Remove</button></td></tr>
                        <tr data-id="102"><td>Emma</td><td><span><button class="remove">Remove</button></span></td></tr>
                    </table>
                    <button id="clear">Clear all</button>
                `;

                function rowIdOf(element) {
                    const row = element.closest('tr');
                    return row ? Number(row.dataset.id) : null;
                }
                JS,
            'hint' => '`element.closest(\'tr\')` finds the row however deep the element is, or `null`.',
            'checks' => [
                ['label' => 'Finds the row', 'code' => "const [first, second] = document.querySelectorAll('.remove');\nreturn expectSame([rowIdOf(first), rowIdOf(second), rowIdOf(document.querySelector('td'))], [101, 102, 101]);"],
                ['label' => 'Outside the table', 'code' => "return expectSame(rowIdOf(document.querySelector('#clear')), null);"],
            ],
        ],
        [
            'title' => 'A section summary',
            'prompt' => "Write `summary(sectionId)`, returning text like `'Fiction: 2 books'` for the `<section>` with that id: its `<h2>` heading and how many `.book` elements are inside it (`'1 book'` for one).",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <section id="fiction"><h2>Fiction</h2><p class="book">Dune</p><p class="book">Emma</p></section>
                    <section id="poetry"><h2>Poetry</h2><p class="book">Odes</p></section>
                    <section id="atlases"><h2>Atlases</h2></section>
                `;

                function summary(sectionId) {
                    return `${sectionId}: ${document.querySelectorAll('.book').length} books`;
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <section id="fiction"><h2>Fiction</h2><p class="book">Dune</p><p class="book">Emma</p></section>
                    <section id="poetry"><h2>Poetry</h2><p class="book">Odes</p></section>
                    <section id="atlases"><h2>Atlases</h2></section>
                `;

                function summary(sectionId) {
                    const section = document.getElementById(sectionId);
                    const count = section.querySelectorAll('.book').length;
                    return `${section.querySelector('h2').textContent}: ${count} ${count === 1 ? 'book' : 'books'}`;
                }
                JS,
            'hint' => 'Find the section, then search inside it (`section.querySelectorAll(\'.book\')`) and read its `h2`.',
            'checks' => [
                ['label' => 'Summaries', 'code' => "return expectSame([summary('fiction'), summary('poetry'), summary('atlases')], ['Fiction: 2 books', 'Poetry: 1 book', 'Atlases: 0 books']);"],
            ],
        ],
    ],
];
