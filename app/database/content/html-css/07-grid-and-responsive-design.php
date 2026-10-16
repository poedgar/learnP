<?php

// Lesson 7 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().
// Checks run in an 800px-wide frame, or as wide as their `width` says, to test media queries.

return [
    'slug' => 'grid-and-responsive-design',
    'title' => 'Grid and responsive design',
    'summary' => 'Lay out rows and columns together with CSS Grid: fr units, repeat, spanning, named areas, and pages that adapt to any screen with flexible sizes, media queries and auto-fill grids.',
    'starter_code' => <<<'HTML'
        <!-- Change the columns; resize the window to see the layout adapt. -->
        <style>
            .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
            .grid div { padding: 20px; text-align: center; background: #e7f5ff; border-radius: 6px; }
        </style>

        <div class="grid">
            <div>Circe</div><div>Dune</div><div>Emma</div><div>Odes</div><div>Ulysses</div>
        </div>
        HTML,

    'quiz' => [
        [
            'question' => 'What can Grid do that Flexbox doesn\'t?',
            'options' => [
                'Nothing',
                'Lay out rows and columns together, in two dimensions, with items lined up in both directions',
                'Centre things',
                'Wrap items',
            ],
            'correct_option' => 1,
            'explanation' => 'Flexbox handles one line at a time; Grid lines up whole rows and columns.',
        ],
        [
            'question' => 'With `grid-template-columns: 1fr 3fr` in a 400px-wide grid with no gap, how wide is the first column?',
            'options' => ['100px', '133px', '300px', '1px'],
            'correct_option' => 0,
            'explanation' => 'The free space is shared one part to three: 100px and 300px.',
        ],
        [
            'question' => 'What does `repeat(3, 1fr)` mean?',
            'options' => ['Three rows', 'Three equal columns', 'One column repeated three times as wide', '3fr'],
            'correct_option' => 1,
            'explanation' => 'It\'s shorthand for `1fr 1fr 1fr`.',
        ],
        [
            'question' => 'What does `grid-column: span 2` do to an item?',
            'options' => ['Moves it to column 2', 'Makes it two columns wide', 'Doubles its height', 'Hides two columns'],
            'correct_option' => 1,
            'explanation' => '`grid-column: 2` would place it in column 2.',
        ],
        [
            'question' => 'What is "mobile-first" CSS?',
            'options' => [
                'Only supporting phones',
                'Writing the narrow-screen styles first, then adding wider layouts with `min-width` media queries',
                'Using px everywhere',
                'Hiding content on phones',
            ],
            'correct_option' => 1,
            'explanation' => 'Narrow layouts are usually the simplest, so they make a good base.',
        ],
        [
            'question' => 'What does `@media (min-width: 600px) { ... }` do?',
            'options' => [
                'Applies its rules only when the viewport is at least 600px wide',
                'Sets the page width to 600px',
                'Applies its rules on screens narrower than 600px',
                'Scales the page',
            ],
            'correct_option' => 0,
            'explanation' => '`max-width` would apply below a width instead.',
        ],
        [
            'question' => 'What does `repeat(auto-fill, minmax(150px, 1fr))` give?',
            'options' => [
                'Exactly 150px columns',
                'As many columns as fit, each at least 150px wide, sharing the extra space',
                'One column',
                'An error',
            ],
            'correct_option' => 1,
            'explanation' => 'A grid that adapts to any width without media queries.',
        ],
        [
            'question' => 'Why put `img { max-width: 100%; height: auto; }` in a responsive page?',
            'options' => [
                'To make images load faster',
                'So images shrink to fit narrow containers, keeping their proportions, instead of overflowing',
                'To crop them',
                'To make them all the same size',
            ],
            'correct_option' => 1,
            'explanation' => '`height: auto` keeps the aspect ratio as the width shrinks.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Three equal columns',
            'prompt' => 'Make `.products` a grid of three equal columns with a 24px gap. (No body margin, so in the 800px frame each column is about 251px wide.)',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .products div { padding: 12px; background: #fff0f6; }
                </style>

                <div class="products">
                    <div id="a">Bookmark</div><div>Tote bag</div><div>Mug</div>
                    <div id="d">Pencil</div><div>Poster</div>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .products { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
                    .products div { padding: 12px; background: #fff0f6; }
                </style>

                <div class="products">
                    <div id="a">Bookmark</div><div>Tote bag</div><div>Mug</div>
                    <div id="d">Pencil</div><div>Poster</div>
                </div>
                HTML,
            'hint' => '`display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;`',
            'checks' => [
                ['label' => 'Column widths', 'code' => "return expectSame([boxOf('#a').width, boxOf('#d').width], [251, 251]);"],
                ['label' => 'The fourth item starts a new row', 'code' => "return expectSame([boxOf('#d').left, boxOf('#d').top - boxOf('#a').bottom], [0, 24]);"],
            ],
        ],
        [
            'title' => 'A fixed and a flexible column',
            'prompt' => 'Make `.app` a grid with a 240px column for the `nav` and a column taking all the rest for `main`, with 16px between them.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    nav { background: #e7f5ff; }
                    main { background: #ebfbee; }
                </style>

                <div class="app">
                    <nav>Menu</nav>
                    <main>Content</main>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .app { display: grid; grid-template-columns: 240px 1fr; gap: 16px; }
                    nav { background: #e7f5ff; }
                    main { background: #ebfbee; }
                </style>

                <div class="app">
                    <nav>Menu</nav>
                    <main>Content</main>
                </div>
                HTML,
            'hint' => '`grid-template-columns: 240px 1fr`.',
            'checks' => [
                ['label' => 'Side by side', 'code' => "return expectSame([boxOf('nav').width, boxOf('main').left, boxOf('main').width, boxOf('nav').top === boxOf('main').top], [240, 256, 544, true]);"],
                ['label' => 'Main takes the rest at other widths', 'width' => 1000, 'code' => "return expectSame(boxOf('main').width, 744);"],
            ],
        ],
        [
            'title' => 'A featured item',
            'prompt' => 'In the 4-column `.gallery`, make the `.featured` picture span two columns and two rows. The rows are 100px tall.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .gallery { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 100px; gap: 10px; }
                    .gallery div { background: #f3f0ff; }
                </style>

                <div class="gallery">
                    <div class="featured">Featured</div>
                    <div>2</div><div>3</div><div>4</div><div>5</div><div id="six">6</div>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .gallery { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 100px; gap: 10px; }
                    .gallery div { background: #f3f0ff; }
                    .gallery .featured { grid-column: span 2; grid-row: span 2; }
                </style>

                <div class="gallery">
                    <div class="featured">Featured</div>
                    <div>2</div><div>3</div><div>4</div><div>5</div><div id="six">6</div>
                </div>
                HTML,
            'hint' => '`grid-column: span 2; grid-row: span 2;` on the featured item.',
            'checks' => [
                ['label' => 'Two columns and two rows', 'code' => "const box = boxOf('.featured');\nreturn expectSame([box.width, box.height], [395, 210]);"],
                ['label' => 'The others flow around it', 'code' => "const six = boxOf('#six');\nreturn expectSame([six.left, six.top], [0, 220]);"],
            ],
        ],
        [
            'title' => 'Named areas',
            'prompt' => 'Lay out the page with `grid-template-areas`: a header across the top, then a 180px `sidebar` beside `main`, then a footer across the bottom, with a 10px gap. Give each element its area with `grid-area`.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .page > * { background: #f1f3f5; }
                </style>

                <div class="page">
                    <header>Header</header>
                    <aside>Sidebar</aside>
                    <main>Main</main>
                    <footer>Footer</footer>
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .page {
                        display: grid;
                        grid-template-columns: 180px 1fr;
                        grid-template-areas:
                            "header header"
                            "sidebar main"
                            "footer footer";
                        gap: 10px;
                    }
                    .page > * { background: #f1f3f5; }
                    header { grid-area: header; }
                    aside { grid-area: sidebar; }
                    main { grid-area: main; }
                    footer { grid-area: footer; }
                </style>

                <div class="page">
                    <header>Header</header>
                    <aside>Sidebar</aside>
                    <main>Main</main>
                    <footer>Footer</footer>
                </div>
                HTML,
            'hint' => 'Each row of the picture is a quoted string of area names; repeat a name to span columns.',
            'checks' => [
                ['label' => 'Header and footer span the page', 'code' => "return expectSame([boxOf('header').width, boxOf('footer').width], [800, 800]);"],
                ['label' => 'Sidebar beside main', 'code' => "return expectSame([boxOf('aside').width, boxOf('main').left, boxOf('aside').top === boxOf('main').top, boxOf('aside').top > boxOf('header').top], [180, 190, true, true]);"],
                ['label' => 'Uses named areas', 'code' => "return learnerCode().includes('grid-template-areas') ? true : 'Use grid-template-areas.';"],
            ],
        ],
        [
            'title' => 'Mobile first',
            'prompt' => 'Make `.cards` a grid with one column by default, two columns from 600px wide, and four columns from 1000px, using `min-width` media queries. Keep the 16px gap.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .cards { display: grid; gap: 16px; }
                    .cards div { padding: 16px; background: #fff9db; }
                </style>

                <div class="cards"><div>Circe</div><div>Dune</div><div>Emma</div><div>Odes</div></div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .cards { display: grid; gap: 16px; grid-template-columns: 1fr; }
                    .cards div { padding: 16px; background: #fff9db; }

                    @media (min-width: 600px) {
                        .cards { grid-template-columns: repeat(2, 1fr); }
                    }

                    @media (min-width: 1000px) {
                        .cards { grid-template-columns: repeat(4, 1fr); }
                    }
                </style>

                <div class="cards"><div>Circe</div><div>Dune</div><div>Emma</div><div>Odes</div></div>
                HTML,
            'hint' => 'One column first; then two `@media (min-width: …)` blocks that change `grid-template-columns`.',
            'checks' => [
                ['label' => 'One column on a phone', 'width' => 400, 'code' => "return expectSame(styleOf('.cards', 'grid-template-columns').split(' ').length, 1);"],
                ['label' => 'Two columns at 800px', 'code' => "return expectSame(styleOf('.cards', 'grid-template-columns').split(' ').length, 2);"],
                ['label' => 'Four columns at 1200px', 'width' => 1200, 'code' => "return expectSame(styleOf('.cards', 'grid-template-columns').split(' ').length, 4);"],
                ['label' => 'Mobile first', 'code' => "return /@media\\s*\\(\\s*min-width/.test(learnerCode()) && !/max-width/.test(learnerCode()) ? true : 'Use min-width media queries, starting from the narrow layout.';"],
            ],
        ],
        [
            'title' => 'A grid that fits itself',
            'prompt' => 'Without media queries, make `.covers` fit as many columns as possible, each at least 140px wide, sharing any extra space, with a 12px gap. Also make images inside shrink to their column: `max-width: 100%` and `height: auto`.',
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .covers { display: grid; grid-template-columns: repeat(4, 200px); }
                </style>

                <div class="covers">
                    <img src="https://picsum.photos/300/450?1" alt="Cover of Circe" width="300" height="450">
                    <img src="https://picsum.photos/300/450?2" alt="Cover of Dune" width="300" height="450">
                    <img src="https://picsum.photos/300/450?3" alt="Cover of Emma" width="300" height="450">
                    <img src="https://picsum.photos/300/450?4" alt="Cover of Odes" width="300" height="450">
                    <img src="https://picsum.photos/300/450?5" alt="Cover of Ulysses" width="300" height="450">
                    <img src="https://picsum.photos/300/450?6" alt="Cover of Beloved" width="300" height="450">
                </div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .covers { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
                    .covers img { max-width: 100%; height: auto; }
                </style>

                <div class="covers">
                    <img src="https://picsum.photos/300/450?1" alt="Cover of Circe" width="300" height="450">
                    <img src="https://picsum.photos/300/450?2" alt="Cover of Dune" width="300" height="450">
                    <img src="https://picsum.photos/300/450?3" alt="Cover of Emma" width="300" height="450">
                    <img src="https://picsum.photos/300/450?4" alt="Cover of Odes" width="300" height="450">
                    <img src="https://picsum.photos/300/450?5" alt="Cover of Ulysses" width="300" height="450">
                    <img src="https://picsum.photos/300/450?6" alt="Cover of Beloved" width="300" height="450">
                </div>
                HTML,
            'hint' => '`repeat(auto-fill, minmax(140px, 1fr))` and `gap: 12px`, plus the two image rules.',
            'checks' => [
                ['label' => 'Five columns at 800px', 'code' => "return expectSame(styleOf('.covers', 'grid-template-columns').split(' ').length, 5);"],
                ['label' => 'Two columns at 320px', 'width' => 320, 'code' => "return expectSame(styleOf('.covers', 'grid-template-columns').split(' ').length, 2);"],
                ['label' => 'Images fit their column', 'code' => "const img = boxOf('.covers img');\nreturn expectSame([img.width < 300, Math.abs(img.height / img.width - 1.5) < 0.05], [true, true]);"],
                ['label' => 'No media queries', 'code' => "return learnerCode().includes('@media') ? 'Do it without media queries.' : true;"],
            ],
        ],
    ],
];
