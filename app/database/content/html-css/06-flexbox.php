<?php

// Lesson 6 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().
// boxOf() measures the rendered box in whole CSS pixels; checks run in an 800px-wide frame.

return [
    'slug' => 'flexbox',
    'title' => 'Flexbox',
    'summary' => 'Lay out rows and columns with Flexbox: the main and cross axes, justify-content and align-items, centring, growing and shrinking with flex, wrapping, and adjusting single items.',
    'starter_code' => <<<'HTML'
        <!-- Change justify-content and align-items and see what happens. -->
        <style>
            .row { display: flex; justify-content: space-between; align-items: center; gap: 8px; height: 120px; padding: 8px; background: #eef; }
            .row div { padding: 12px; background: #9cf; }
        </style>

        <div class="row">
            <div>One</div>
            <div>Two</div>
            <div>Three</div>
        </div>
        HTML,

    'quiz' => [
        [
            'question' => 'Which element gets `display: flex`?',
            'options' => [
                'Each item to be arranged',
                'The parent (the container) whose children are to be arranged',
                'The body only',
                'Any element; it has the same effect',
            ],
            'correct_option' => 1,
            'explanation' => 'Its direct children become flex items.',
        ],
        [
            'question' => 'In a `flex-direction: column` container, what does `justify-content` control?',
            'options' => ['The horizontal position', 'The vertical position, along the main axis, which is now vertical', 'The text alignment', 'Nothing'],
            'correct_option' => 1,
            'explanation' => '`justify-content` always works along the main axis, whichever way it runs.',
        ],
        [
            'question' => 'How do you centre a child both horizontally and vertically in a flex container?',
            'options' => [
                '`text-align: center`',
                '`justify-content: center` and `align-items: center`',
                '`margin: center`',
                '`align-content: middle`',
            ],
            'correct_option' => 1,
            'explanation' => 'The container also needs a height for vertical centring to show.',
        ],
        [
            'question' => 'Two items have `flex: 1` and `flex: 3`. How is the spare space shared?',
            'options' => ['Equally', 'The second gets three times as much as the first', 'The first gets it all', 'It isn\'t shared'],
            'correct_option' => 1,
            'explanation' => 'Grow factors are relative: 1 part and 3 parts.',
        ],
        [
            'question' => 'What does `flex: 0 0 200px` mean for an item?',
            'options' => [
                'It\'s hidden',
                'It doesn\'t grow, doesn\'t shrink, and starts at 200px: a fixed 200px item',
                'It grows to 200px',
                'It\'s 200px tall',
            ],
            'correct_option' => 1,
            'explanation' => 'The three values are flex-grow, flex-shrink and flex-basis.',
        ],
        [
            'question' => 'What happens to flex items that don\'t fit in one line, by default?',
            'options' => ['They wrap onto a new line', 'They shrink to fit on one line', 'They\'re hidden', 'The container scrolls'],
            'correct_option' => 1,
            'explanation' => 'Set `flex-wrap: wrap` to let them move to new lines.',
        ],
        [
            'question' => 'What does `margin-left: auto` on one flex item do?',
            'options' => [
                'Nothing in Flexbox',
                'Takes up all the free space before it, pushing it and the items after it to the far end',
                'Centres it',
                'Removes its margin',
            ],
            'correct_option' => 1,
            'explanation' => 'Handy for putting an account link at the right of a navigation bar.',
        ],
        [
            'question' => 'What is `gap` for in a flex container?',
            'options' => [
                'Padding inside each item',
                'Space between items, without extra space at the outer edges',
                'The container\'s margin',
                'Space between lines of text',
            ],
            'correct_option' => 1,
            'explanation' => 'Unlike margins on items, `gap` never adds space before the first or after the last.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A row of buttons',
            'prompt' => 'Lay out the buttons in `.actions` in a row with 12px between them, all pushed to the right-hand end of the container.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .actions { padding: 8px; background: #f4f4f4; }
                </style>

                <div class="actions">
                    <button id="cancel">Cancel</button>
                    <button id="save">Save</button>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .actions { display: flex; justify-content: flex-end; gap: 12px; padding: 8px; background: #f4f4f4; }
                </style>

                <div class="actions">
                    <button id="cancel">Cancel</button>
                    <button id="save">Save</button>
                </div>
                HTML,
            'hint' => '`display: flex`, `gap: 12px`, and `justify-content: flex-end`.',
            'checks' => [
                ['label' => 'A flex row', 'code' => "return expectSame([styleOf('.actions', 'display'), styleOf('.actions', 'column-gap')], ['flex', '12px']);"],
                ['label' => 'At the right, 12px apart', 'code' => "const cancel = boxOf('#cancel');\nconst save = boxOf('#save');\nreturn expectSame([save.right, save.left - cancel.right, cancel.top === save.top], [792, 12, true]);"],
            ],
        ],
        [
            'title' => 'Centre it',
            'prompt' => 'Centre the `.message` horizontally and vertically inside `.screen`, which is 300px tall, using Flexbox on `.screen`.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .screen { height: 300px; background: #222; }
                    .message { width: 200px; padding: 16px; background: white; text-align: center; }
                </style>

                <div class="screen">
                    <div class="message">Loading your books…</div>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .screen { display: flex; justify-content: center; align-items: center; height: 300px; background: #222; }
                    .message { width: 200px; padding: 16px; background: white; text-align: center; }
                </style>

                <div class="screen">
                    <div class="message">Loading your books…</div>
                </div>
                HTML,
            'hint' => 'Three declarations on the container: `display: flex`, `justify-content: center`, `align-items: center`.',
            'checks' => [
                ['label' => 'Centred', 'code' => <<<'JS'
                    const screen = boxOf('.screen');
                    const message = boxOf('.message');
                    const centre = (b) => [Math.round((b.left + b.right) / 2), Math.round((b.top + b.bottom) / 2)];
                    return expectSame(centre(message), centre(screen));
                    JS],
                ['label' => 'With Flexbox', 'code' => "return expectSame(styleOf('.screen', 'display'), 'flex');"],
            ],
        ],
        [
            'title' => 'Sidebar and content',
            'prompt' => 'Make `.layout` a row where `.sidebar` is always exactly 200px wide and `.content` takes all the remaining width, with a 16px gap. (The body has no margin, so in the 800px frame the content is 584px wide.)',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .sidebar { background: #eef; }
                    .content { background: #efe; }
                </style>

                <div class="layout">
                    <aside class="sidebar">Filters</aside>
                    <main class="content">Results</main>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .layout { display: flex; gap: 16px; }
                    .sidebar { flex: 0 0 200px; background: #eef; }
                    .content { flex: 1; background: #efe; }
                </style>

                <div class="layout">
                    <aside class="sidebar">Filters</aside>
                    <main class="content">Results</main>
                </div>
                HTML,
            'hint' => '`flex: 0 0 200px` on the sidebar, `flex: 1` on the content, `display: flex; gap: 16px` on the layout.',
            'checks' => [
                ['label' => 'The widths', 'code' => "return expectSame([boxOf('.sidebar').width, boxOf('.content').width, boxOf('.content').left], [200, 584, 216]);"],
                ['label' => "The sidebar doesn't shrink", 'width' => 400, 'code' => "return expectSame([boxOf('.sidebar').width, boxOf('.content').width], [200, 184]);"],
            ],
        ],
        [
            'title' => 'Wrapping cards',
            'prompt' => "Lay out the `.card`s in rows that wrap: each card is 180px wide (it doesn't grow or shrink), with a 20px gap both between cards and between rows. In the 800px frame (no body margin), three cards fit on the first row.",
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .card { height: 60px; background: #fde2e4; }
                </style>

                <div class="cards">
                    <div class="card">1</div><div class="card">2</div><div class="card">3</div>
                    <div class="card">4</div><div class="card">5</div>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .cards { display: flex; flex-wrap: wrap; gap: 20px; }
                    .card { flex: 0 0 180px; height: 60px; background: #fde2e4; }
                </style>

                <div class="cards">
                    <div class="card">1</div><div class="card">2</div><div class="card">3</div>
                    <div class="card">4</div><div class="card">5</div>
                </div>
                HTML,
            'hint' => '`flex-wrap: wrap` and `gap: 20px` on the container; `flex: 0 0 180px` on each card.',
            'checks' => [
                ['label' => 'Rows that wrap', 'code' => <<<'JS'
                    const tops = [...document.querySelectorAll('.card')].map((c) => Math.round(c.getBoundingClientRect().top));
                    return expectSame(tops, [0, 0, 0, 0, 80]);
                    JS],
                ['label' => 'Fixed width', 'code' => "return expectSame([...document.querySelectorAll('.card')].map((c) => Math.round(c.getBoundingClientRect().width)), Array(5).fill(180));"],
                ['label' => 'Fewer per row when narrow', 'width' => 400, 'code' => <<<'JS'
                    const tops = [...document.querySelectorAll('.card')].map((c) => Math.round(c.getBoundingClientRect().top));
                    return expectSame(tops, [0, 0, 80, 80, 160]);
                    JS],
            ],
        ],
        [
            'title' => 'A navigation bar',
            'prompt' => 'Make `.nav` a row of links with 16px between them, centred vertically in its 56px height, with the `.account` link pushed to the far right using an automatic margin.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .nav { height: 56px; padding: 0 16px; background: #1d3557; }
                    .nav a { color: white; }
                </style>

                <nav class="nav">
                    <a href="#" id="home">Home</a>
                    <a href="#" id="books">Books</a>
                    <a href="#" class="account">Sign in</a>
                </nav>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .nav { display: flex; align-items: center; gap: 16px; height: 56px; padding: 0 16px; background: #1d3557; }
                    .nav a { color: white; }
                    .nav .account { margin-left: auto; }
                </style>

                <nav class="nav">
                    <a href="#" id="home">Home</a>
                    <a href="#" id="books">Books</a>
                    <a href="#" class="account">Sign in</a>
                </nav>
                HTML,
            'hint' => '`display: flex; align-items: center; gap: 16px;` on the nav, and `margin-left: auto` on `.account`.',
            'checks' => [
                ['label' => 'Links in a row, 16px apart', 'code' => "return expectSame([boxOf('#books').left - boxOf('#home').right, boxOf('#home').top === boxOf('#books').top], [16, true]);"],
                ['label' => 'Vertically centred', 'code' => "const nav = boxOf('.nav');\nconst link = boxOf('#home');\nreturn Math.abs((link.top + link.bottom) / 2 - (nav.top + nav.bottom) / 2) <= 1 ? true : 'Centre the links vertically with align-items.';"],
                ['label' => 'Sign in at the far right', 'code' => "return expectSame([boxOf('.account').right, styleOf('.account', 'margin-left') !== '0px'], [784, true]);"],
            ],
        ],
        [
            'title' => 'Equal columns',
            'prompt' => "Make the three `.plan` boxes share the width of `.plans` equally, with a 24px gap, and all stretch to the same height as the tallest. Make the middle one, `.featured`, take twice as much of the space as each of the others. (Padding and borders aren't shared out, so it's twice as wide inside its padding, not overall.)",
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .plan { border: 1px solid #ccc; padding: 12px; }
                </style>

                <div class="plans">
                    <div class="plan" id="basic">Basic</div>
                    <div class="plan featured" id="pro">Pro<br>Everything in Basic<br>Plus more</div>
                    <div class="plan" id="team">Team</div>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .plans { display: flex; gap: 24px; }
                    .plan { flex: 1; border: 1px solid #ccc; padding: 12px; }
                    .featured { flex: 2; }
                </style>

                <div class="plans">
                    <div class="plan" id="basic">Basic</div>
                    <div class="plan featured" id="pro">Pro<br>Everything in Basic<br>Plus more</div>
                    <div class="plan" id="team">Team</div>
                </div>
                HTML,
            'hint' => '`flex: 1` on every plan and `flex: 2` on the featured one. Flex items stretch to the same height by default.',
            'checks' => [
                ['label' => 'Shares of the width', 'code' => <<<'JS'
                    // Each box has 26px of padding and border on top of its share of the free space.
                    const inner = (id) => boxOf(id).width - 26;
                    const [basic, pro, team] = [inner('#basic'), inner('#pro'), inner('#team')];
                    const fills = Math.abs(basic + pro + team + 78 + 48 - 800) <= 2;
                    return fills && Math.abs(basic - team) <= 1 && Math.abs(pro - 2 * basic) <= 2 ? true : `The plans are ${[basic, pro, team].map(Math.round).join(', ')}px wide inside; expected about 169, 337, 169.`;
                    JS],
                ['label' => 'The same height', 'code' => "return expectSame([boxOf('#basic').height === boxOf('#pro').height, boxOf('#team').height === boxOf('#pro').height], [true, true]);"],
            ],
        ],
    ],
];
