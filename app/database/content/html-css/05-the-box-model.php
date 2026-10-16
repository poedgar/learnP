<?php

// Lesson 5 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().
// boxOf() measures the rendered box in whole CSS pixels; checks run in an 800px-wide frame.

return [
    'slug' => 'the-box-model',
    'title' => 'The box model',
    'summary' => 'Control the size and spacing of elements: content, padding, border and margin, box-sizing, the shorthands for four sides, block, inline and inline-block, centring, margin collapsing and overflow.',
    'starter_code' => <<<'HTML'
        <!-- Change the numbers and watch the box. -->
        <style>
            .box {
                width: 200px;
                padding: 16px;
                border: 4px solid tomato;
                margin: 24px;
                background: mistyrose;
            }
        </style>

        <div class="box">A box with padding, a border and a margin.</div>
        HTML,

    'quiz' => [
        [
            'question' => 'From the inside out, what are the layers of the box model?',
            'options' => [
                'Margin, border, padding, content',
                'Content, padding, border, margin',
                'Content, margin, border, padding',
                'Padding, content, border, margin',
            ],
            'correct_option' => 1,
            'explanation' => 'Padding is inside the border and takes the background; margin is outside.',
        ],
        [
            'question' => 'With the default `box-sizing: content-box`, how wide is a box with `width: 100px; padding: 10px; border: 2px solid`?',
            'options' => ['100px', '124px', '112px', '120px'],
            'correct_option' => 1,
            'explanation' => '100 + 2 × 10 + 2 × 2 = 124. With `border-box`, it would be 100.',
        ],
        [
            'question' => 'What does `margin: 10px 20px` mean?',
            'options' => [
                'Top 10, right 20, bottom and left 0',
                'Top and bottom 10px, left and right 20px',
                'Left 10, right 20',
                'All sides 30px',
            ],
            'correct_option' => 1,
            'explanation' => 'Two values: vertical, then horizontal.',
        ],
        [
            'question' => 'Why does `width: 300px` have no effect on a `span`?',
            'options' => [
                'Spans can\'t be styled',
                'A span is inline: it flows in the text and ignores width and height, unless its display is changed',
                'The value is too big',
                'It needs !important',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `display: inline-block` or `block` for a span that needs a width.',
        ],
        [
            'question' => 'How do you centre a block of a fixed width?',
            'options' => ['`text-align: center`', '`margin: 0 auto`', '`align: center`', '`padding: auto`'],
            'correct_option' => 1,
            'explanation' => 'Automatic left and right margins share the free space equally. `text-align` centres inline content.',
        ],
        [
            'question' => 'One paragraph has `margin-bottom: 40px`, the next `margin-top: 25px`. How big is the gap between them?',
            'options' => ['65px', '40px', '25px', '15px'],
            'correct_option' => 1,
            'explanation' => 'Vertical margins between blocks collapse: the larger one wins.',
        ],
        [
            'question' => 'What does `overflow: hidden` do?',
            'options' => [
                'Hides the element',
                'Clips content that doesn\'t fit inside the box',
                'Adds scroll bars',
                'Makes the box grow',
            ],
            'correct_option' => 1,
            'explanation' => '`auto` adds scroll bars only when needed.',
        ],
        [
            'question' => 'What does `display: none` do?',
            'options' => [
                'Makes the element transparent but keeps its space',
                'Removes the element from the layout entirely, as if it weren\'t there',
                'Makes it inline',
                'Hides only its text',
            ],
            'correct_option' => 1,
            'explanation' => '`visibility: hidden` would hide it but keep its space.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An exact size',
            'prompt' => 'Make the `.panel` exactly 300 pixels wide and 120 pixels tall **including** its 10px padding and 5px border, by changing how its size is worked out. Keep the padding and border.',
            'starter_code' => <<<'HTML'
                <style>
                    .panel {
                        width: 300px;
                        height: 120px;
                        padding: 10px;
                        border: 5px solid darkcyan;
                    }
                </style>

                <div class="panel">This panel must fit a 300 × 120 space.</div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .panel {
                        box-sizing: border-box;
                        width: 300px;
                        height: 120px;
                        padding: 10px;
                        border: 5px solid darkcyan;
                    }
                </style>

                <div class="panel">This panel must fit a 300 × 120 space.</div>
                HTML,
            'hint' => '`box-sizing: border-box` makes width and height include the padding and border.',
            'checks' => [
                ['label' => '300 × 120 in all', 'code' => "const box = boxOf('.panel');\nreturn expectSame([box.width, box.height], [300, 120]);"],
                ['label' => 'Padding and border kept', 'code' => "return expectSame([styleOf('.panel', 'padding-left'), styleOf('.panel', 'border-left-width')], ['10px', '5px']);"],
            ],
        ],
        [
            'title' => 'Spacing with shorthands',
            'prompt' => 'Give `.note` padding of 8px top and bottom and 16px left and right, and a margin of 24px on top, 0 on the right, 12px at the bottom and 0 on the left, using the `padding` and `margin` shorthands.',
            'starter_code' => <<<'HTML'
                <style>
                    .note { background: lightyellow; }
                </style>

                <p class="note">Remember: meetings start at 7pm.</p>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .note {
                        background: lightyellow;
                        padding: 8px 16px;
                        margin: 24px 0 12px 0;
                    }
                </style>

                <p class="note">Remember: meetings start at 7pm.</p>
                HTML,
            'hint' => 'Two values for vertical and horizontal; four for top, right, bottom, left.',
            'checks' => [
                ['label' => 'Padding', 'code' => <<<'JS'
                    return expectSame(['top', 'right', 'bottom', 'left'].map((side) => styleOf('.note', `padding-${side}`)), ['8px', '16px', '8px', '16px']);
                    JS],
                ['label' => 'Margin', 'code' => <<<'JS'
                    return expectSame(['top', 'right', 'bottom', 'left'].map((side) => styleOf('.note', `margin-${side}`)), ['24px', '0px', '12px', '0px']);
                    JS],
                ['label' => 'Uses the shorthands', 'code' => "const code = learnerCode();\nreturn /padding\\s*:/.test(code) && /margin\\s*:/.test(code) && !/(padding|margin)-(top|right|bottom|left)/.test(code) ? true : 'Use the padding and margin shorthands.';"],
            ],
        ],
        [
            'title' => 'Badges in a line',
            'prompt' => "Make each `.badge` sit in the line like text but be exactly 90px wide (content width) with 4px of padding all round, and add a 6px margin on its right. They're `span`s now, so their width is being ignored.",
            'starter_code' => <<<'HTML'
                <style>
                    .badge { width: 90px; padding: 4px; margin-right: 6px; background: lavender; text-align: center; }
                </style>

                <p><span class="badge">sci-fi</span><span class="badge">classic</span><span class="badge">desert</span></p>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .badge { display: inline-block; width: 90px; padding: 4px; margin-right: 6px; background: lavender; text-align: center; }
                </style>

                <p><span class="badge">sci-fi</span><span class="badge">classic</span><span class="badge">desert</span></p>
                HTML,
            'hint' => '`display: inline-block` keeps them in the line but lets width apply.',
            'checks' => [
                ['label' => 'Inline blocks', 'code' => "return expectSame(styleOf('.badge', 'display'), 'inline-block');"],
                ['label' => 'Their size', 'code' => "return expectSame(boxOf('.badge').width, 98);"],
                ['label' => 'Side by side', 'code' => <<<'JS'
                    const [a, b] = [...document.querySelectorAll('.badge')].map((el) => el.getBoundingClientRect());
                    return Math.round(a.top) === Math.round(b.top) && Math.round(b.left - a.right) === 6 ? true : 'The badges should sit on one line, 6px apart.';
                    JS],
            ],
        ],
        [
            'title' => 'A centred column',
            'prompt' => "Make `.column` at most 480px wide and centred in the page with automatic margins, with 24px of padding inside. In the 800px-wide check frame, it should then start 160px from the left (with the body's own margin set to 0, as here).",
            'starter_code' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .column { background: #f4f4f4; }
                </style>

                <main class="column">
                    <h1>About the club</h1>
                    <p>We have met every month since 2009.</p>
                </main>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    body { margin: 0; }
                    .column {
                        box-sizing: border-box;
                        max-width: 480px;
                        margin: 0 auto;
                        padding: 24px;
                        background: #f4f4f4;
                    }
                </style>

                <main class="column">
                    <h1>About the club</h1>
                    <p>We have met every month since 2009.</p>
                </main>
                HTML,
            'hint' => '`max-width: 480px; margin: 0 auto;`. With `box-sizing: border-box`, the padding stays inside the 480px.',
            'checks' => [
                ['label' => 'Centred and 480px wide', 'code' => "const box = boxOf('.column');\nreturn expectSame([box.width, box.left], [480, 160]);"],
                ['label' => 'Padding inside', 'code' => "return expectSame(styleOf('.column', 'padding-top'), '24px');"],
                ['label' => 'Narrower on small screens', 'width' => 400, 'code' => "return expectSame(boxOf('.column').width, 400);"],
            ],
        ],
        [
            'title' => 'Collapsed margins',
            'prompt' => 'The two cards should be exactly 32px apart. Their margins currently collapse into one 20px gap. Without using padding, set the margins so the gap between them is 32px: give each card `margin-bottom: 32px` and no top margin.',
            'starter_code' => <<<'HTML'
                <style>
                    .card { margin: 20px 0; padding: 12px; background: #e8f4ea; }
                </style>

                <div class="card" id="first">First</div>
                <div class="card" id="second">Second</div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .card { margin: 0 0 32px; padding: 12px; background: #e8f4ea; }
                </style>

                <div class="card" id="first">First</div>
                <div class="card" id="second">Second</div>
                HTML,
            'hint' => 'Collapsed margins don\'t add up, so set one margin to the whole gap: `margin: 0 0 32px`.',
            'checks' => [
                ['label' => 'A 32px gap', 'code' => "return expectSame(boxOf('#second').top - boxOf('#first').bottom, 32);"],
                ['label' => 'Margins, not padding', 'code' => "return expectSame([styleOf('#first', 'margin-top'), styleOf('#first', 'margin-bottom'), styleOf('#first', 'padding-top')], ['0px', '32px', '12px']);"],
            ],
        ],
        [
            'title' => 'Too much content',
            'prompt' => "The quote box is a fixed 220 × 80 pixels, and its text spills out. Make it show scroll bars only when the content doesn't fit, and hide the `.secret` paragraph completely so it takes no space.",
            'starter_code' => <<<'HTML'
                <style>
                    .quote { width: 220px; height: 80px; border: 1px solid gray; }
                </style>

                <div class="quote">"It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife."</div>
                <p class="secret">This paragraph should not be there at all.</p>
                <p id="after">After the quote.</p>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .quote { width: 220px; height: 80px; border: 1px solid gray; overflow: auto; }
                    .secret { display: none; }
                </style>

                <div class="quote">"It is a truth universally acknowledged, that a single man in possession of a good fortune, must be in want of a wife."</div>
                <p class="secret">This paragraph should not be there at all.</p>
                <p id="after">After the quote.</p>
                HTML,
            'hint' => '`overflow: auto` for the quote, and `display: none` for the paragraph.',
            'checks' => [
                ['label' => 'The quote scrolls', 'code' => "return expectSame([styleOf('.quote', 'overflow-y'), boxOf('.quote').height], ['auto', 82]);"],
                ['label' => 'The secret takes no space', 'code' => "return expectSame([styleOf('.secret', 'display'), boxOf('.secret').height], ['none', 0]);"],
            ],
        ],
    ],
];
