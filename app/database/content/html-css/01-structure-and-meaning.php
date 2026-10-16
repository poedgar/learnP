<?php

// Lesson 1 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().

return [
    'slug' => 'structure-and-meaning',
    'title' => 'Structure and meaning',
    'summary' => 'Write HTML: elements and attributes, the shape of a document, a sensible outline of headings, and semantic elements such as header, nav, main, article and footer.',
    'starter_code' => <<<'HTML'
        <!-- Change this page; it updates as you type. -->
        <h1>My reading list</h1>
        <p>Books I want to read this year:</p>
        <p><strong>Dune</strong> by Frank Herbert</p>
        <p><em>Circe</em> by Madeline Miller</p>
        HTML,

    'quiz' => [
        [
            'question' => 'What is an HTML element?',
            'options' => [
                'Just a tag',
                'An opening tag, its content and a closing tag, together, such as `<p>Hello</p>`',
                'A CSS rule',
                'An attribute',
            ],
            'correct_option' => 1,
            'explanation' => 'Void elements, such as `<img>`, have no content or closing tag.',
        ],
        [
            'question' => 'Which part of a document holds what\'s shown on the page?',
            'options' => ['`head`', '`body`', '`title`', '`meta`'],
            'correct_option' => 1,
            'explanation' => 'The `head` holds information about the page, such as its title.',
        ],
        [
            'question' => 'Why use headings in order (h1, then h2, then h3) rather than picking them by size?',
            'options' => [
                'Browsers refuse other orders',
                'They form the page\'s outline, which screen readers and search engines use; size is set with CSS',
                'Smaller headings load faster',
                'It doesn\'t matter',
            ],
            'correct_option' => 1,
            'explanation' => 'Skipping levels confuses the outline.',
        ],
        [
            'question' => 'Which element marks the main content of a page?',
            'options' => ['`<body>`', '`<main>`', '`<div id="main">`', '`<section>`'],
            'correct_option' => 1,
            'explanation' => 'There should be one `main` per page; it lets screen-reader users jump straight to the content.',
        ],
        [
            'question' => 'What does the `lang` attribute on `<html>` do?',
            'options' => [
                'Translates the page',
                'Says which language the page is in, so screen readers pronounce it correctly and browsers can offer translation',
                'Sets the programming language',
                'Nothing',
            ],
            'correct_option' => 1,
            'explanation' => '`<html lang="en">` for English, `"fr"` for French, and so on.',
        ],
        [
            'question' => 'What is the difference between `div` and `section`?',
            'options' => [
                'None',
                '`section` is a themed part of the page, usually with a heading; `div` has no meaning and is only for grouping',
                '`div` is for inline text',
                '`section` can\'t contain paragraphs',
            ],
            'correct_option' => 1,
            'explanation' => 'Prefer elements that say what they are, and use `div` when none fits.',
        ],
        [
            'question' => 'How does a browser show `<p>a     b</p>`?',
            'options' => ['With five spaces between a and b', 'With one space between a and b', 'As "ab"', 'On two lines'],
            'correct_option' => 1,
            'explanation' => 'Runs of whitespace collapse into one space.',
        ],
        [
            'question' => 'Which element is void (has no closing tag)?',
            'options' => ['`<p>`', '`<img>`', '`<a>`', '`<strong>`'],
            'correct_option' => 1,
            'explanation' => 'Others include `br`, `hr`, `input` and `meta`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A complete document',
            'prompt' => 'Write a complete HTML document in English (`lang="en"`) with a `utf-8` charset, the title `Recipes`, and a body with an `h1` saying `My recipes`.',
            'starter_code' => <<<'HTML'
                <h1>My recipes</h1>
                HTML,
            'solution' => <<<'HTML'
                <!doctype html>
                <html lang="en">
                    <head>
                        <meta charset="utf-8">
                        <title>Recipes</title>
                    </head>
                    <body>
                        <h1>My recipes</h1>
                    </body>
                </html>
                HTML,
            'hint' => '`<!doctype html>`, then `<html lang="en">` with a `head` (meta charset and title) and a `body`.',
            'checks' => [
                ['label' => 'The title and language', 'code' => "return expectSame([document.title, document.documentElement.lang], ['Recipes', 'en']);"],
                ['label' => 'A doctype and charset', 'code' => <<<'JS'
                    const code = learnerCode().toLowerCase();
                    if (!code.trimStart().startsWith('<!doctype html>')) return 'Start with <!doctype html>.';
                    return /<meta\s+charset=["']?utf-8/.test(code) ? true : 'Add <meta charset="utf-8"> to the head.';
                    JS],
                ['label' => 'The heading', 'code' => "return expectSame(document.querySelector('h1')?.textContent, 'My recipes');"],
            ],
        ],
        [
            'title' => 'A sensible outline',
            'prompt' => "Mark up this recipe with headings: the recipe name as the page's only `h1`, and `Ingredients` and `Method` as `h2`s. Keep the paragraphs.",
            'starter_code' => <<<'HTML'
                <p><strong>Lemon cake</strong></p>
                <p>Ingredients</p>
                <p>Flour, butter, sugar, eggs and two lemons.</p>
                <p>Method</p>
                <p>Mix everything, then bake for 45 minutes.</p>
                HTML,
            'solution' => <<<'HTML'
                <h1>Lemon cake</h1>
                <h2>Ingredients</h2>
                <p>Flour, butter, sugar, eggs and two lemons.</p>
                <h2>Method</h2>
                <p>Mix everything, then bake for 45 minutes.</p>
                HTML,
            'hint' => 'Turn the three label paragraphs into headings: one `h1` and two `h2`s.',
            'checks' => [
                ['label' => 'One h1', 'code' => "const h1s = document.querySelectorAll('h1');\nreturn expectSame([h1s.length, h1s[0]?.textContent.trim()], [1, 'Lemon cake']);"],
                ['label' => 'Two h2s', 'code' => "return expectSame([...document.querySelectorAll('h2')].map((h) => h.textContent.trim()), ['Ingredients', 'Method']);"],
                ['label' => 'The text is still there', 'code' => "return expectSame(document.querySelectorAll('p').length, 2);"],
            ],
        ],
        [
            'title' => 'Semantic layout',
            'prompt' => 'Replace the `div`s with semantic elements: a `header` containing a `nav` with the links, a `main` containing an `article` with the post, and a `footer`. No `div`s should remain.',
            'starter_code' => <<<'HTML'
                <div class="top">
                    <div class="links"><a href="/">Home</a> <a href="/about">About</a></div>
                </div>
                <div class="content">
                    <div class="post">
                        <h1>Why I read slowly</h1>
                        <p>Some books deserve it.</p>
                    </div>
                </div>
                <div class="bottom">© 2026 Amara</div>
                HTML,
            'solution' => <<<'HTML'
                <header>
                    <nav><a href="/">Home</a> <a href="/about">About</a></nav>
                </header>
                <main>
                    <article>
                        <h1>Why I read slowly</h1>
                        <p>Some books deserve it.</p>
                    </article>
                </main>
                <footer>© 2026 Amara</footer>
                HTML,
            'hint' => 'Each `div` has a role: the top is a `header`, the links a `nav`, the content `main`, the post an `article`, the bottom a `footer`.',
            'checks' => [
                ['label' => 'Header with navigation', 'code' => "return expectSame(document.querySelectorAll('header nav a').length, 2);"],
                ['label' => 'Main with an article', 'code' => "return expectSame(document.querySelector('main article h1')?.textContent, 'Why I read slowly');"],
                ['label' => 'A footer', 'code' => "return expectSame(document.querySelector('footer')?.textContent.trim(), '© 2026 Amara');"],
                ['label' => 'No divs left', 'code' => "return expectSame(document.querySelectorAll('div').length, 0);"],
            ],
        ],
        [
            'title' => 'Links and an image',
            'prompt' => 'Make `Circe` a link to `https://example.org/circe` that opens with the tooltip `About the book` (the `title` attribute), and add an image from `https://picsum.photos/200/300` before the paragraph, 200 wide and 300 high, with the alternative text `The cover of Circe`.',
            'starter_code' => <<<'HTML'
                <p>This month we read Circe by Madeline Miller.</p>
                HTML,
            'solution' => <<<'HTML'
                <img src="https://picsum.photos/200/300" alt="The cover of Circe" width="200" height="300">
                <p>This month we read <a href="https://example.org/circe" title="About the book">Circe</a> by Madeline Miller.</p>
                HTML,
            'hint' => '`<a href="..." title="...">Circe</a>`, and `<img src="..." alt="..." width="200" height="300">`.',
            'checks' => [
                ['label' => 'The link', 'code' => "const a = document.querySelector('p a');\nreturn expectSame([a?.textContent, a?.getAttribute('href'), a?.getAttribute('title')], ['Circe', 'https://example.org/circe', 'About the book']);"],
                ['label' => 'The image', 'code' => "const img = document.querySelector('img');\nreturn expectSame([img?.getAttribute('src'), img?.getAttribute('alt'), img?.getAttribute('width'), img?.getAttribute('height')], ['https://picsum.photos/200/300', 'The cover of Circe', '200', '300']);"],
                ['label' => 'The image comes first', 'code' => "return expectSame(document.body.firstElementChild?.tagName, 'IMG');"],
            ],
        ],
        [
            'title' => 'Words that matter',
            'prompt' => 'Mark up the notice: `Important:` as `strong`, `not` as `em`, the date as a `time` element with `datetime="2026-12-24"`, and put a line break (`br`) before `Thank you`. It should all stay one paragraph.',
            'starter_code' => <<<'HTML'
                <p>Important: the library is not open on 24 December. Thank you for your patience.</p>
                HTML,
            'solution' => <<<'HTML'
                <p><strong>Important:</strong> the library is <em>not</em> open on <time datetime="2026-12-24">24 December</time>.<br>Thank you for your patience.</p>
                HTML,
            'hint' => 'Wrap each part in its element; `<br>` is void, with no closing tag.',
            'checks' => [
                ['label' => 'strong and em', 'code' => "return expectSame([document.querySelector('p strong')?.textContent, document.querySelector('p em')?.textContent], ['Important:', 'not']);"],
                ['label' => 'A time element', 'code' => "const t = document.querySelector('p time');\nreturn expectSame([t?.textContent, t?.getAttribute('datetime')], ['24 December', '2026-12-24']);"],
                ['label' => 'A line break before Thank you', 'code' => "const br = document.querySelector('p br');\nreturn br && br.nextSibling?.textContent.trim().startsWith('Thank you') ? true : 'Put <br> just before Thank you.';"],
                ['label' => 'One paragraph', 'code' => "return expectSame(document.querySelectorAll('p').length, 1);"],
            ],
        ],
        [
            'title' => 'Several articles',
            'prompt' => "Make a page for two book reviews: a `main` with an `h1` saying `Reviews`, then two `article`s, each with an `h2` (the book's title: `Dune`, then `Emma`) and a paragraph. Give each article a `data-rating` attribute: `5` for Dune, `4` for Emma.",
            'starter_code' => <<<'HTML'
                <h1>Reviews</h1>
                HTML,
            'solution' => <<<'HTML'
                <main>
                    <h1>Reviews</h1>
                    <article data-rating="5">
                        <h2>Dune</h2>
                        <p>A desert planet, and a lot of politics.</p>
                    </article>
                    <article data-rating="4">
                        <h2>Emma</h2>
                        <p>Matchmaking goes wrong, wittily.</p>
                    </article>
                </main>
                HTML,
            'hint' => 'Each `article` holds its own `h2` and paragraph; `data-rating="5"` is an attribute on the article\'s opening tag.',
            'checks' => [
                ['label' => 'Two articles in main', 'code' => "return expectSame([...document.querySelectorAll('main > article')].map((a) => a.querySelector('h2')?.textContent), ['Dune', 'Emma']);"],
                ['label' => 'Ratings', 'code' => "return expectSame([...document.querySelectorAll('article')].map((a) => a.dataset.rating), ['5', '4']);"],
                ['label' => 'Each has a paragraph', 'code' => "return expectSame([...document.querySelectorAll('article')].every((a) => a.querySelector('p')?.textContent.trim()), true);"],
                ['label' => 'The heading', 'code' => "return expectSame(document.querySelector('main h1')?.textContent, 'Reviews');"],
            ],
        ],
    ],
];
