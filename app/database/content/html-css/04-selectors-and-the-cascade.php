<?php

// Lesson 4 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().
// styleOf() gives computed values: colours as rgb(...), lengths in px.

return [
    'slug' => 'selectors-and-the-cascade',
    'title' => 'Selectors and the cascade',
    'summary' => 'Style pages with CSS: rules, selectors, pseudo-classes and pseudo-elements, colours and units, how the cascade and specificity decide which rule wins, inheritance, and custom properties.',
    'starter_code' => <<<'HTML'
        <!-- Change the styles; the page updates as you type. -->
        <style>
            h1 { color: teal; }
            .tag { background: #eee; padding: 2px 6px; border-radius: 4px; }
            li:nth-child(even) { color: gray; }
        </style>

        <h1>Reading list</h1>
        <ul>
            <li>Circe <span class="tag">myth</span></li>
            <li>Dune <span class="tag">sci-fi</span></li>
            <li>Emma <span class="tag">classic</span></li>
        </ul>
        HTML,

    'quiz' => [
        [
            'question' => 'What does the selector `.note p` match?',
            'options' => [
                'Paragraphs with the class note',
                'Paragraphs anywhere inside an element with the class note',
                'Elements with the class note inside paragraphs',
                'Only direct children',
            ],
            'correct_option' => 1,
            'explanation' => '`p.note` would be paragraphs with the class; `.note > p` only direct children.',
        ],
        [
            'question' => 'Two rules set `color` on the same paragraph: `p { color: blue; }` and `.intro { color: green; }`. Which wins?',
            'options' => ['blue', 'green', 'whichever comes later', 'neither'],
            'correct_option' => 1,
            'explanation' => 'A class is more specific than an element type, wherever the rules are.',
        ],
        [
            'question' => 'Two rules with the same specificity set the same property. Which wins?',
            'options' => ['The first one', 'The later one', 'Neither', 'The shorter one'],
            'correct_option' => 1,
            'explanation' => 'That\'s the "cascade" part: later rules override earlier ones.',
        ],
        [
            'question' => 'Which property is inherited by default?',
            'options' => ['`border`', '`padding`', '`color`', '`background`'],
            'correct_option' => 2,
            'explanation' => 'Text properties such as color and font-family pass down to children.',
        ],
        [
            'question' => 'What does `li:nth-child(2n)` select?',
            'options' => ['The second li only', 'Every second li: the 2nd, 4th, 6th…', 'The first two', 'li elements with two children'],
            'correct_option' => 1,
            'explanation' => '`odd` and `even` are shorthands for `2n+1` and `2n`.',
        ],
        [
            'question' => 'What is `1rem`, usually?',
            'options' => ['1 pixel', 'The root (html) font size, usually 16px', 'The parent\'s font size', '1% of the window'],
            'correct_option' => 1,
            'explanation' => '`em` is relative to the element\'s own font size instead.',
        ],
        [
            'question' => 'Why avoid `!important`?',
            'options' => [
                'It\'s slow',
                'It overrides the normal cascade, so later changes need more `!important`s, and styles become hard to manage',
                'Browsers ignore it',
                'It only works on ids',
            ],
            'correct_option' => 1,
            'explanation' => 'Fix specificity instead.',
        ],
        [
            'question' => 'What is `--brand: #2a6f97;` used with?',
            'options' => ['`$brand`', '`var(--brand)`', '`@brand`', '`brand()`'],
            'correct_option' => 1,
            'explanation' => 'Custom properties are read with `var()`, and can have a fallback: `var(--brand, blue)`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Your first rules',
            'prompt' => 'Add a `<style>` element that makes the `h1` the colour `darkred` and `32px` in size, and gives every paragraph a `line-height` of `1.5`.',
            'starter_code' => <<<'HTML'
                <h1>Lemon cake</h1>
                <p>Light, sharp and easy to make.</p>
                <p>Serves eight.</p>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    h1 {
                        color: darkred;
                        font-size: 32px;
                    }

                    p {
                        line-height: 1.5;
                    }
                </style>

                <h1>Lemon cake</h1>
                <p>Light, sharp and easy to make.</p>
                <p>Serves eight.</p>
                HTML,
            'hint' => 'One rule for `h1` with two declarations, one for `p`. Each declaration ends with a semicolon.',
            'checks' => [
                ['label' => 'The heading', 'code' => "return expectSame([styleOf('h1', 'color'), styleOf('h1', 'font-size')], ['rgb(139, 0, 0)', '32px']);"],
                ['label' => 'Paragraphs', 'code' => <<<'JS'
                    const p = [...document.querySelectorAll('p')].map((el) => getComputedStyle(el));
                    return expectSame(p.map((s) => Number.parseFloat(s.lineHeight) / Number.parseFloat(s.fontSize)), [1.5, 1.5]);
                    JS],
            ],
        ],
        [
            'title' => 'Classes and ids',
            'prompt' => "Style by class and id: elements with the class `price` are `bold` (font-weight 700) and green (`green`); the element with the id `sold-out` has `text-decoration: line-through`. Don't change the HTML.",
            'starter_code' => <<<'HTML'
                <style>
                </style>

                <ul>
                    <li>Dune <span class="price">£9.99</span></li>
                    <li id="sold-out">Emma <span class="price">£7.99</span></li>
                </ul>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    .price {
                        font-weight: 700;
                        color: green;
                    }

                    #sold-out {
                        text-decoration: line-through;
                    }
                </style>

                <ul>
                    <li>Dune <span class="price">£9.99</span></li>
                    <li id="sold-out">Emma <span class="price">£7.99</span></li>
                </ul>
                HTML,
            'hint' => 'A class selector starts with `.`, an id selector with `#`.',
            'checks' => [
                ['label' => 'Prices', 'code' => "const prices = [...document.querySelectorAll('.price')].map((el) => getComputedStyle(el));\nreturn expectSame(prices.map((s) => [s.fontWeight, s.color]), [['700', 'rgb(0, 128, 0)'], ['700', 'rgb(0, 128, 0)']]);"],
                ['label' => 'Sold out', 'code' => "return expectSame(styleOf('#sold-out', 'text-decoration-line'), 'line-through');"],
                ['label' => 'The HTML is unchanged', 'code' => "return expectSame([document.querySelectorAll('li').length, document.querySelectorAll('.price').length], [2, 2]);"],
            ],
        ],
        [
            'title' => 'Stripes and states',
            'prompt' => "Give every even row of the table (`tbody tr`) the background `#f0f0f0`, make the first row's text bold (font-weight 700), and give links the colour `crimson` when hovered or focused.",
            'starter_code' => <<<'HTML'
                <style>
                </style>

                <table>
                    <tbody>
                        <tr><td>Circe</td><td><a href="#c">Review</a></td></tr>
                        <tr><td>Dune</td><td><a href="#d">Review</a></td></tr>
                        <tr><td>Emma</td><td><a href="#e">Review</a></td></tr>
                        <tr><td>Odes</td><td><a href="#o">Review</a></td></tr>
                    </tbody>
                </table>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    tbody tr:nth-child(even) {
                        background: #f0f0f0;
                    }

                    tbody tr:first-child {
                        font-weight: 700;
                    }

                    a:hover,
                    a:focus {
                        color: crimson;
                    }
                </style>

                <table>
                    <tbody>
                        <tr><td>Circe</td><td><a href="#c">Review</a></td></tr>
                        <tr><td>Dune</td><td><a href="#d">Review</a></td></tr>
                        <tr><td>Emma</td><td><a href="#e">Review</a></td></tr>
                        <tr><td>Odes</td><td><a href="#o">Review</a></td></tr>
                    </tbody>
                </table>
                HTML,
            'hint' => '`:nth-child(even)`, `:first-child`, and a rule with two selectors, `a:hover, a:focus`.',
            'checks' => [
                ['label' => 'Even rows are striped', 'code' => "return expectSame([...document.querySelectorAll('tbody tr')].map((tr) => getComputedStyle(tr).backgroundColor), ['rgba(0, 0, 0, 0)', 'rgb(240, 240, 240)', 'rgba(0, 0, 0, 0)', 'rgb(240, 240, 240)']);"],
                ['label' => 'The first row is bold', 'code' => "return expectSame([...document.querySelectorAll('tbody tr')].map((tr) => getComputedStyle(tr).fontWeight), ['700', '400', '400', '400']);"],
                ['label' => 'Focused links are crimson', 'code' => "const link = document.querySelector('a');\nconst before = getComputedStyle(link).color;\nlink.focus();\nreturn expectSame([before === 'rgb(220, 20, 60)', getComputedStyle(link).color], [false, 'rgb(220, 20, 60)']);"],
            ],
        ],
        [
            'title' => 'Win the cascade',
            'prompt' => 'Without touching the HTML or the existing rules, and without `!important`, add one rule after them that makes the paragraph inside `#offers` the colour `purple`. Your selector has to be more specific than `#offers .note`.',
            'starter_code' => <<<'HTML'
                <style>
                    p { color: black; }
                    #offers .note { color: gray; }
                    /* Add your rule here. */
                </style>

                <section id="offers">
                    <p class="note special">Half price on Tuesdays.</p>
                </section>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    p { color: black; }
                    #offers .note { color: gray; }
                    #offers .note.special { color: purple; }
                </style>

                <section id="offers">
                    <p class="note special">Half price on Tuesdays.</p>
                </section>
                HTML,
            'hint' => '`#offers .note` is one id and one class. Add another class: `#offers .note.special` has one id and two classes.',
            'checks' => [
                ['label' => 'The paragraph is purple', 'code' => "return expectSame(styleOf('#offers p', 'color'), 'rgb(128, 0, 128)');"],
                ['label' => 'No !important', 'code' => "return learnerCode().includes('!important') ? 'Win with specificity, not !important.' : true;"],
                ['label' => 'The original rules are kept', 'code' => "const code = learnerCode();\nreturn code.includes('#offers .note { color: gray; }') && code.includes('p { color: black; }') ? true : 'Keep the existing rules.';"],
            ],
        ],
        [
            'title' => 'Inherited and not',
            'prompt' => 'Set the `font-family` to `Georgia, serif` and the `color` to `navy` once, on the `article`, so everything inside inherits them, and give only the `article` a `2px solid` border. Its paragraphs must not get a border.',
            'starter_code' => <<<'HTML'
                <style>
                    h2 { font-family: Georgia, serif; color: navy; }
                    p { font-family: Georgia, serif; color: navy; }
                </style>

                <article>
                    <h2>Odes</h2>
                    <p>Poems for every season.</p>
                    <p>Keats, 1819.</p>
                </article>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    article {
                        font-family: Georgia, serif;
                        color: navy;
                        border: 2px solid;
                    }
                </style>

                <article>
                    <h2>Odes</h2>
                    <p>Poems for every season.</p>
                    <p>Keats, 1819.</p>
                </article>
                HTML,
            'hint' => 'One rule on `article` sets all three; font and colour are inherited, the border isn\'t.',
            'checks' => [
                ['label' => 'Everything inherits the text style', 'code' => "return expectSame([...document.querySelectorAll('article h2, article p')].map((el) => [getComputedStyle(el).color, getComputedStyle(el).fontFamily]), Array(3).fill(['rgb(0, 0, 128)', 'Georgia, serif']));"],
                ['label' => 'Only the article has a border', 'code' => "return expectSame([styleOf('article', 'border-top-width'), styleOf('article', 'border-top-style'), styleOf('article p', 'border-top-style')], ['2px', 'solid', 'none']);"],
                ['label' => 'Set once, on the article', 'code' => "return (learnerCode().match(/font-family/g) ?? []).length === 1 ? true : 'Set font-family once, on the article.';"],
            ],
        ],
        [
            'title' => 'One colour to change',
            'prompt' => "Define a custom property `--accent` with the value `#0077b6` on `:root`, and use it with `var()` for the heading's `color`, the button's `background-color` and the box's `border-color`. Changing `--accent` should then change all three.",
            'starter_code' => <<<'HTML'
                <style>
                    h1 { color: #0077b6; }
                    button { background-color: #0077b6; color: white; border: 0; padding: 8px 16px; }
                    .box { border: 3px solid #0077b6; padding: 12px; }
                </style>

                <h1>Join the club</h1>
                <div class="box"><button>Sign up</button></div>
                HTML,
            'solution' => <<<'HTML'
                <style>
                    :root { --accent: #0077b6; }
                    h1 { color: var(--accent); }
                    button { background-color: var(--accent); color: white; border: 0; padding: 8px 16px; }
                    .box { border: 3px solid var(--accent); padding: 12px; }
                </style>

                <h1>Join the club</h1>
                <div class="box"><button>Sign up</button></div>
                HTML,
            'hint' => '`:root { --accent: #0077b6; }`, then replace each `#0077b6` with `var(--accent)`.',
            'checks' => [
                ['label' => 'The accent is used', 'code' => "return expectSame([styleOf('h1', 'color'), styleOf('button', 'background-color'), styleOf('.box', 'border-top-color')], Array(3).fill('rgb(0, 119, 182)'));"],
                ['label' => 'Changing it changes everything', 'code' => "document.documentElement.style.setProperty('--accent', 'rgb(200, 0, 0)');\nconst after = [styleOf('h1', 'color'), styleOf('button', 'background-color'), styleOf('.box', 'border-top-color')];\ndocument.documentElement.style.removeProperty('--accent');\nreturn expectSame(after, Array(3).fill('rgb(200, 0, 0)'));"],
            ],
        ],
    ],
];
