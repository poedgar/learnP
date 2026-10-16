<?php

// Lesson 2 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().

return [
    'slug' => 'lists-tables-and-figures',
    'title' => 'Lists, tables and figures',
    'summary' => 'Mark up lists (unordered, ordered and description lists), accessible data tables with captions and header cells, images with good alternative text, figures, and quotations.',
    'starter_code' => <<<'HTML'
        <!-- Change this page; it updates as you type. -->
        <h1>Top three books</h1>
        <ol>
            <li>Circe</li>
            <li>Dune</li>
            <li>Emma</li>
        </ol>
        <table>
            <caption>Prices</caption>
            <tr><th scope="col">Book</th><th scope="col">Price</th></tr>
            <tr><td>Circe</td><td>£9.99</td></tr>
        </table>
        HTML,

    'quiz' => [
        [
            'question' => 'Which list should you use for the steps of a recipe?',
            'options' => ['`ul`', '`ol`', '`dl`', 'Paragraphs'],
            'correct_option' => 1,
            'explanation' => 'Order matters in a method, so use an ordered list.',
        ],
        [
            'question' => 'What do `dt` and `dd` mark up?',
            'options' => [
                'Table cells',
                'A term and its description, inside a description list (`dl`)',
                'Dates and durations',
                'Headings',
            ],
            'correct_option' => 1,
            'explanation' => 'Good for glossaries and for "label: value" details.',
        ],
        [
            'question' => 'What is the right alt text for a purely decorative image?',
            'options' => ['Leave the alt attribute out', '`alt=""`', '`alt="image"`', '`alt="decoration"`'],
            'correct_option' => 1,
            'explanation' => 'An empty alt tells screen readers to skip it; a missing alt makes them read the file name.',
        ],
        [
            'question' => 'What does `scope="col"` on a `th` do?',
            'options' => [
                'Makes the column wider',
                'Says the header belongs to its column, so screen readers can announce it with each cell below',
                'Sorts the column',
                'Hides the header',
            ],
            'correct_option' => 1,
            'explanation' => '`scope="row"` is for headers at the start of a row.',
        ],
        [
            'question' => 'Should tables be used to lay out a page in columns?',
            'options' => [
                'Yes, it\'s the usual way',
                'No: tables are for tabular data; layout is done with CSS, such as Flexbox and Grid',
                'Only for two columns',
                'Only on phones',
            ],
            'correct_option' => 1,
            'explanation' => 'Layout tables confuse screen readers and don\'t adapt to small screens.',
        ],
        [
            'question' => 'What does `colspan="2"` on a cell do?',
            'options' => ['Makes it two rows tall', 'Makes it span two columns', 'Adds two cells', 'Doubles its width in pixels'],
            'correct_option' => 1,
            'explanation' => '`rowspan` spans rows.',
        ],
        [
            'question' => 'Why give an image `width` and `height` attributes?',
            'options' => [
                'They\'re required',
                'The browser can reserve the space before the image loads, so the page doesn\'t jump about',
                'They make the image load faster',
                'They crop the image',
            ],
            'correct_option' => 1,
            'explanation' => 'CSS can still resize it.',
        ],
        [
            'question' => 'What does `figcaption` belong inside?',
            'options' => ['`img`', '`figure`', '`table`', '`caption`'],
            'correct_option' => 1,
            'explanation' => 'A `figure` groups the image with its caption.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A nested list',
            'prompt' => 'Make an unordered list with three items: `Fiction`, `Poetry` and `History`. Inside the `Fiction` item, add an ordered list of `Circe` then `Dune`.',
            'starter_code' => <<<'HTML'
                <p>Fiction, Poetry, History</p>
                HTML,
            'solution' => <<<'HTML'
                <ul>
                    <li>Fiction
                        <ol>
                            <li>Circe</li>
                            <li>Dune</li>
                        </ol>
                    </li>
                    <li>Poetry</li>
                    <li>History</li>
                </ul>
                HTML,
            'hint' => 'The inner `ol` goes inside the first `li`, after its text.',
            'checks' => [
                ['label' => 'Three top-level items', 'code' => "return expectSame([...document.querySelectorAll('body > ul > li')].map((li) => li.firstChild.textContent.trim()), ['Fiction', 'Poetry', 'History']);"],
                ['label' => 'An ordered list inside Fiction', 'code' => "return expectSame([...document.querySelectorAll('body > ul > li:first-child > ol > li')].map((li) => li.textContent.trim()), ['Circe', 'Dune']);"],
            ],
        ],
        [
            'title' => 'Book details',
            'prompt' => 'Turn the details into a description list (`dl`), with `Title`, `Author` and `Year` as terms and their values as descriptions, in that order.',
            'starter_code' => <<<'HTML'
                <p>Title: Dune</p>
                <p>Author: Frank Herbert</p>
                <p>Year: 1965</p>
                HTML,
            'solution' => <<<'HTML'
                <dl>
                    <dt>Title</dt>
                    <dd>Dune</dd>
                    <dt>Author</dt>
                    <dd>Frank Herbert</dd>
                    <dt>Year</dt>
                    <dd>1965</dd>
                </dl>
                HTML,
            'hint' => 'Each "Label: value" becomes `<dt>Label</dt><dd>value</dd>`.',
            'checks' => [
                ['label' => 'Terms', 'code' => "return expectSame([...document.querySelectorAll('dl dt')].map((e) => e.textContent.trim()), ['Title', 'Author', 'Year']);"],
                ['label' => 'Descriptions', 'code' => "return expectSame([...document.querySelectorAll('dl dd')].map((e) => e.textContent.trim()), ['Dune', 'Frank Herbert', '1965']);"],
                ['label' => 'No paragraphs left', 'code' => "return expectSame(document.querySelectorAll('p').length, 0);"],
            ],
        ],
        [
            'title' => 'An accessible table',
            'prompt' => 'Make a table with the caption `Fees`, a `thead` row of column headers `Item` and `Fee` (`th` with `scope="col"`), and a `tbody` with two rows: `Late return` / `£1.00` and `Lost card` / `£3.50`, where the first cell of each row is a `th` with `scope="row"`.',
            'starter_code' => <<<'HTML'
                <p>Late return: £1.00. Lost card: £3.50.</p>
                HTML,
            'solution' => <<<'HTML'
                <table>
                    <caption>Fees</caption>
                    <thead>
                        <tr><th scope="col">Item</th><th scope="col">Fee</th></tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">Late return</th><td>£1.00</td></tr>
                        <tr><th scope="row">Lost card</th><td>£3.50</td></tr>
                    </tbody>
                </table>
                HTML,
            'hint' => 'Put the caption first inside `table`; header cells are `th`, data cells `td`.',
            'checks' => [
                ['label' => 'Caption and column headers', 'code' => "const ths = [...document.querySelectorAll('table thead th')];\nreturn expectSame([document.querySelector('table caption')?.textContent.trim(), ths.map((th) => th.textContent.trim()), ths.map((th) => th.getAttribute('scope'))], ['Fees', ['Item', 'Fee'], ['col', 'col']]);"],
                ['label' => 'Rows with row headers', 'code' => "const rows = [...document.querySelectorAll('table tbody tr')];\nreturn expectSame(rows.map((tr) => [tr.querySelector('th[scope=\"row\"]')?.textContent.trim(), tr.querySelector('td')?.textContent.trim()]), [['Late return', '£1.00'], ['Lost card', '£3.50']]);"],
            ],
        ],
        [
            'title' => 'Spanning cells',
            'prompt' => 'Fix the timetable so it lines up: the `Lunch` cell should span both columns of its row (`colspan`), and `Room 2` should cover the last two rows of the first column (`rowspan`), with those rows then having only one other cell each.',
            'starter_code' => <<<'HTML'
                <table>
                    <tr><th>Room</th><th>Session</th></tr>
                    <tr><td>Room 1</td><td>Talks</td></tr>
                    <tr><td>Lunch</td></tr>
                    <tr><td>Room 2</td><td>Workshop A</td></tr>
                    <tr><td>Room 2</td><td>Workshop B</td></tr>
                </table>
                HTML,
            'solution' => <<<'HTML'
                <table>
                    <tr><th>Room</th><th>Session</th></tr>
                    <tr><td>Room 1</td><td>Talks</td></tr>
                    <tr><td colspan="2">Lunch</td></tr>
                    <tr><td rowspan="2">Room 2</td><td>Workshop A</td></tr>
                    <tr><td>Workshop B</td></tr>
                </table>
                HTML,
            'hint' => 'Add `colspan="2"` to Lunch, `rowspan="2"` to the first Room 2, and delete the second Room 2 cell.',
            'checks' => [
                ['label' => 'Lunch spans two columns', 'code' => "const lunch = [...document.querySelectorAll('td')].find((td) => td.textContent.trim() === 'Lunch');\nreturn expectSame(lunch?.colSpan, 2);"],
                ['label' => 'Room 2 spans two rows', 'code' => "const rooms = [...document.querySelectorAll('td')].filter((td) => td.textContent.trim() === 'Room 2');\nreturn expectSame([rooms.length, rooms[0]?.rowSpan, rooms[0]?.parentElement.nextElementSibling?.children.length], [1, 2, 1]);"],
            ],
        ],
        [
            'title' => 'A figure with a caption',
            'prompt' => 'Wrap the image in a `figure` with a `figcaption` saying `Our meeting room.` Give the image the alternative text `Armchairs around a low table`, and mark the small divider image as decorative.',
            'starter_code' => <<<'HTML'
                <img src="https://picsum.photos/240/160" width="240" height="160">
                <p>Our meeting room.</p>
                <img class="divider" src="https://picsum.photos/200/4" width="200" height="4">
                HTML,
            'solution' => <<<'HTML'
                <figure>
                    <img src="https://picsum.photos/240/160" alt="Armchairs around a low table" width="240" height="160">
                    <figcaption>Our meeting room.</figcaption>
                </figure>
                <img class="divider" src="https://picsum.photos/200/4" alt="" width="200" height="4">
                HTML,
            'hint' => 'Decorative images get an empty `alt=""`, not a missing one.',
            'checks' => [
                ['label' => 'A figure and its caption', 'code' => "return expectSame([document.querySelector('figure > img')?.getAttribute('alt'), document.querySelector('figure > figcaption')?.textContent.trim()], ['Armchairs around a low table', 'Our meeting room.']);"],
                ['label' => 'The divider is decorative', 'code' => "const divider = document.querySelector('img.divider');\nreturn divider?.hasAttribute('alt') && divider.getAttribute('alt') === '' ? true : 'Give the divider alt=\"\".';"],
                ['label' => 'No caption paragraph left', 'code' => "return expectSame(document.querySelectorAll('p').length, 0);"],
            ],
        ],
        [
            'title' => 'Quotes',
            'prompt' => 'Put the long quotation in a `blockquote` whose `cite` attribute is `https://example.org/emma`, and in the sentence below it, mark `Emma` as a `cite` element and the short quotation as a `q` (without the quotation marks, which `q` adds itself).',
            'starter_code' => <<<'HTML'
                <p>"I may have lost my heart, but not my self-control."</p>
                <p>In Emma, Mr Knightley says "Badly done, Emma!"</p>
                HTML,
            'solution' => <<<'HTML'
                <blockquote cite="https://example.org/emma">
                    <p>"I may have lost my heart, but not my self-control."</p>
                </blockquote>
                <p>In <cite>Emma</cite>, Mr Knightley says <q>Badly done, Emma!</q></p>
                HTML,
            'hint' => 'The `blockquote` wraps the first paragraph; the second paragraph uses `<cite>` and `<q>` inline.',
            'checks' => [
                ['label' => 'The block quotation', 'code' => "const quote = document.querySelector('blockquote');\nreturn expectSame([quote?.getAttribute('cite'), quote?.textContent.includes('self-control')], ['https://example.org/emma', true]);"],
                ['label' => 'cite and q', 'code' => "return expectSame([document.querySelector('p cite')?.textContent, document.querySelector('p q')?.textContent], ['Emma', 'Badly done, Emma!']);"],
            ],
        ],
    ],
];
