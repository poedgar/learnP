<?php

// Lesson 2 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'xss-and-escaping',
    'title' => 'XSS and escaping',
    'summary' => 'Cross-site scripting and how escaping on output stops it: `htmlspecialchars()` with the right flags, attributes, URLs and scheme allow-lists, JSON inside scripts, safe formatting and Content Security Policy.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try your own nasty reviewer names.
        $reviews = [
            ['name' => 'Ada', 'text' => 'Loved it!'],
            ['name' => '<img src=x onerror=alert(1)>', 'text' => 'Meh'],
        ];

        foreach ($reviews as $review) {
            $name = htmlspecialchars($review['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $text = htmlspecialchars($review['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            echo "<p><strong>$name</strong>: $text</p>" . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What is cross-site scripting (XSS)?',
            'options' => [
                'Running PHP on another server',
                'User-supplied text ending up in a page as HTML or script, so the browser runs it',
                'Loading scripts from a CDN',
                'A way to share sessions between sites',
            ],
            'correct_option' => 1,
            'explanation' => 'The injected script runs with your site\'s permissions, in the victim\'s browser.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                echo htmlspecialchars('<b>"Tom" & \'Jerry\'</b>', ENT_QUOTES, 'UTF-8');
                PHP,
            'options' => [
                '`<b>"Tom" & \'Jerry\'</b>`',
                '`&lt;b&gt;&quot;Tom&quot; &amp; &#039;Jerry&#039;&lt;/b&gt;`',
                '`&lt;b&gt;"Tom" &amp; \'Jerry\'&lt;/b&gt;`',
                '`Tom & Jerry`',
            ],
            'correct_option' => 1,
            'explanation' => 'With `ENT_QUOTES`, all five special characters are turned into entities.',
        ],
        [
            'question' => 'In Blade, what is the difference between `{{ $bio }}` and `{!! $bio !!}`?',
            'options' => [
                'None',
                '`{{ }}` escapes the value; `{!! !!}` outputs it as raw HTML',
                '`{!! !!}` escapes twice',
                '`{{ }}` only works for strings',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `{!! !!}` only for HTML you generated and trust, never for user input.',
        ],
        [
            'question' => 'Why is escaping alone not enough for a user-supplied URL in `href`?',
            'options' => [
                'URLs can\'t be escaped',
                'A URL like `javascript:alert(1)` has nothing to escape, so the scheme must be checked too',
                'Browsers ignore escaped URLs',
                'It is enough',
            ],
            'correct_option' => 1,
            'explanation' => 'Only allow schemes you expect, such as `http`, `https` and `mailto`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $alt = 'x onerror=alert(1)';

                echo '<img src="cover.jpg" alt=' . htmlspecialchars($alt) . '>';
                PHP,
            'options' => [
                '`<img src="cover.jpg" alt=x onerror=alert(1)>` (and it is unsafe)',
                '`<img src="cover.jpg" alt="x onerror=alert(1)">`',
                '`<img src="cover.jpg" alt=x>`',
                'An error',
            ],
            'correct_option' => 0,
            'explanation' => 'The value had nothing to escape, but the attribute isn\'t quoted, so the space starts a new `onerror` attribute. Always quote attributes.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                echo json_encode('</script>', JSON_HEX_TAG);
                PHP,
            'options' => ['`"</script>"`', '`"<\/script>"`', '`"\u003C\/script\u003E"`', '`"&lt;/script&gt;"`'],
            'correct_option' => 2,
            'explanation' => '`JSON_HEX_TAG` writes `<` and `>` as `\u003C` and `\u003E`, which can\'t close a `<script>` tag.',
        ],
        [
            'question' => 'You want to let users write `**bold**` in comments. What is the safe order?',
            'options' => [
                'Turn `**...**` into `<strong>`, then remove dangerous tags',
                'Escape the whole comment, then turn `**...**` into `<strong>`',
                'Only allow it for logged-in users',
                'Use `strip_tags()` and output the rest raw',
            ],
            'correct_option' => 1,
            'explanation' => 'Escaping first means nothing the user typed can be a tag; only the tags you add afterwards are real.',
        ],
        [
            'question' => 'What does `Content-Security-Policy: script-src \'self\'` do?',
            'options' => [
                'Escapes all output',
                'Tells the browser to run only scripts from your own site, refusing injected inline scripts',
                'Encrypts scripts',
                'Blocks all JavaScript',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s a second line of defence: even if escaping is missed, injected inline scripts are refused.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An escaping helper',
            'prompt' => 'Write `e(mixed $value): string`, like Laravel\'s. It escapes `$value` for HTML with `htmlspecialchars()`, using `ENT_QUOTES | ENT_SUBSTITUTE` and UTF-8. `null` becomes an empty string; numbers and other scalars are converted to strings first.',
            'starter_code' => <<<'PHP'
                <?php

                function e(mixed $value): string
                {
                    return (string) $value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function e(mixed $value): string
                {
                    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
                PHP,
            'hint' => '`(string) ($value ?? \'\')` handles `null`; then pass it to `htmlspecialchars()` with the flags and charset.',
            'checks' => [
                ['label' => 'Tags and ampersands', 'code' => "return expect_same(e('<script>a & b</script>'), '&lt;script&gt;a &amp; b&lt;/script&gt;');"],
                ['label' => 'Both kinds of quote', 'code' => "return expect_same(e('\"x\" \\'y\\''), '&quot;x&quot; &#039;y&#039;');"],
                ['label' => 'null and numbers', 'code' => "return expect_same([e(null), e(42), e(9.5)], ['', '42', '9.5']);"],
                ['label' => 'Invalid UTF-8 is replaced, not dropped', 'code' => 'return expect_same(e("Dune\\xff"), "Dune\\u{FFFD}");'],
            ],
        ],
        [
            'title' => 'A review card',
            'prompt' => 'Write `reviewHtml(array $review): string`, returning `<article class="review" data-stars="4"><h3>Ada</h3><p>Loved it!</p></article>` for a review with `name`, `stars` and `text`. Every value must be escaped, including the stars in the attribute (it may not be a number).',
            'starter_code' => <<<'PHP'
                <?php

                function reviewHtml(array $review): string
                {
                    return "<article class=\"review\" data-stars=\"{$review['stars']}\"><h3>{$review['name']}</h3><p>{$review['text']}</p></article>";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function reviewHtml(array $review): string
                {
                    $escape = fn (mixed $value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    return sprintf(
                        '<article class="review" data-stars="%s"><h3>%s</h3><p>%s</p></article>',
                        $escape($review['stars']),
                        $escape($review['name']),
                        $escape($review['text']),
                    );
                }
                PHP,
            'hint' => 'Escape each of the three values with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, \'UTF-8\')` before putting it into the string.',
            'checks' => [
                ['label' => 'A normal review', 'code' => "return expect_same(reviewHtml(['name' => 'Ada', 'stars' => 4, 'text' => 'Loved it!']), '<article class=\"review\" data-stars=\"4\"><h3>Ada</h3><p>Loved it!</p></article>');"],
                ['label' => 'Scripts in the text are harmless', 'code' => "\$html = reviewHtml(['name' => 'X', 'stars' => 1, 'text' => '<script>alert(1)</script>']);\nreturn ! str_contains(\$html, '<script') ?: 'A <script> tag got through.';"],
                ['label' => 'The attribute can\'t be broken out of', 'code' => "\$html = reviewHtml(['name' => 'X', 'stars' => '5\" onmouseover=\"alert(1)', 'text' => 'ok']);\nreturn ! str_contains(\$html, '\" onmouseover') ?: 'The stars value broke out of its attribute.';"],
                ['label' => 'Names are escaped too', 'code' => "return str_contains(reviewHtml(['name' => '<b>Bob</b>', 'stars' => 3, 'text' => 'ok']), '<h3>&lt;b&gt;Bob&lt;/b&gt;</h3>') ?: 'The name was not escaped.';"],
            ],
        ],
        [
            'title' => 'Safe links',
            'prompt' => 'Write `safeUrl(string $url): string`. Return the URL if it is a relative path starting with a single `/` (not `//`), or if its scheme is `http`, `https` or `mailto` in any case; return `#` for anything else. Browsers ignore spaces, tabs and newlines, so remove all whitespace and control characters (bytes 0 to 32) before checking. Then write `linkHtml(string $url, string $text): string`, returning `<a href="...">...</a>` with the safe URL and both values escaped.',
            'starter_code' => <<<'PHP'
                <?php

                function safeUrl(string $url): string
                {
                    return $url;
                }

                function linkHtml(string $url, string $text): string
                {
                    return '<a href="' . $url . '">' . $text . '</a>';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function safeUrl(string $url): string
                {
                    $clean = preg_replace('/[\x00-\x20]+/', '', $url);

                    if (str_starts_with($clean, '/') && ! str_starts_with($clean, '//')) {
                        return $url;
                    }

                    return preg_match('/^(https?|mailto):/i', $clean) === 1 ? $url : '#';
                }

                function linkHtml(string $url, string $text): string
                {
                    $escape = fn (string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    return '<a href="' . $escape(safeUrl($url)) . '">' . $escape($text) . '</a>';
                }
                PHP,
            'hint' => 'Strip `[\x00-\x20]` with `preg_replace()` before looking at the start. A case-insensitive pattern like `/^(https?|mailto):/i` checks the scheme.',
            'checks' => [
                ['label' => 'Good URLs pass', 'code' => "return expect_same(array_map('safeUrl', ['https://example.com/a?b=1', 'HTTP://example.com', 'mailto:ada@example.com', '/books/7']), ['https://example.com/a?b=1', 'HTTP://example.com', 'mailto:ada@example.com', '/books/7']);"],
                ['label' => 'Scripts are blocked in every disguise', 'code' => "return expect_same(array_map('safeUrl', ['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', \"java\\tscript:alert(1)\", \"\\x01javascript:alert(1)\", 'data:text/html,<script>alert(1)</script>']), ['#', '#', '#', '#', '#', '#']);"],
                ['label' => 'Protocol-relative and other URLs are blocked', 'code' => "return expect_same(array_map('safeUrl', ['//evil.example', 'ftp://example.com', 'example.com']), ['#', '#', '#']);"],
                ['label' => 'linkHtml() escapes both parts', 'code' => "return expect_same([linkHtml('/search?q=a&b=\"c\"', '<Dune>'), linkHtml('javascript:x', 'Home')], ['<a href=\"/search?q=a&amp;b=&quot;c&quot;\">&lt;Dune&gt;</a>', '<a href=\"#\">Home</a>']);"],
            ],
        ],
        [
            'title' => 'Data for a script',
            'prompt' => 'Write `inlineData(string $name, mixed $data): string`, returning `<script>window.book = {...};</script>` where the data is JSON encoded so that nothing in it (like `</script>`, quotes or `&`) can break out of the script block. Use the `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS` and `JSON_HEX_QUOT` flags, and throw on values JSON can\'t encode. `$name` must be a simple identifier (letters, digits, `_`, not starting with a digit); anything else throws `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function inlineData(string $name, mixed $data): string
                {
                    return "<script>window.$name = " . json_encode($data) . ";</script>";
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function inlineData(string $name, mixed $data): string
                {
                    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
                        throw new InvalidArgumentException("Invalid variable name: $name");
                    }

                    $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

                    return "<script>window.$name = $json;</script>";
                }
                PHP,
            'hint' => 'Combine the four `JSON_HEX_*` flags with `JSON_THROW_ON_ERROR` using `|`. Check the name with an anchored pattern like `/^[A-Za-z_][A-Za-z0-9_]*$/`.',
            'checks' => [
                ['label' => 'Ordinary data', 'code' => "return expect_same(inlineData('book', ['id' => 7, 'title' => 'Dune']), '<script>window.book = {\"id\":7,\"title\":\"Dune\"};</script>');"],
                ['label' => '</script> can\'t close the block', 'code' => "\$html = inlineData('book', ['title' => '</script><script>alert(1)</script>']);\nreturn substr_count(\$html, '</script>') === 1 ?: 'The data closed the script tag.';"],
                ['label' => 'Quotes and & are hex-encoded', 'code' => "return expect_same(inlineData('q', \"Tom's \\\"&\\\"\"), '<script>window.q = \"Tom\\\\u0027s \\\\u0022\\\\u0026\\\\u0022\";</script>');"],
                ['label' => 'Bad names and unencodable data throw', 'code' => "\$a = expect_throws(InvalidArgumentException::class, fn () => inlineData('x;alert(1)//', 1));\nif (\$a !== true) return \$a;\nreturn expect_throws(JsonException::class, fn () => inlineData('x', \"\\xff\"));"],
            ],
        ],
        [
            'title' => 'Bold, safely',
            'prompt' => 'Write `formatComment(string $text): string`. Escape the whole comment for HTML first, then turn `**words**` into `<strong>words</strong>` (shortest match, not across lines) and each newline into `<br>`. Nothing the user typed may become a real tag.',
            'starter_code' => <<<'PHP'
                <?php

                function formatComment(string $text): string
                {
                    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

                    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function formatComment(string $text): string
                {
                    $html = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

                    return str_replace("\n", '<br>', $html);
                }
                PHP,
            'hint' => 'Swap the order: escape first, then add your own tags. `.` doesn\'t match a newline, so the bold pattern can\'t cross lines.',
            'checks' => [
                ['label' => 'Bold and line breaks', 'code' => "return expect_same(formatComment(\"A **great** read\\nAnd **fast**\"), 'A <strong>great</strong> read<br>And <strong>fast</strong>');"],
                ['label' => 'Typed tags stay text', 'code' => "return expect_same(formatComment('<b>hi</b> <script>x</script>'), '&lt;b&gt;hi&lt;/b&gt; &lt;script&gt;x&lt;/script&gt;');"],
                ['label' => 'Tags inside bold stay text', 'code' => "return expect_same(formatComment('**<img src=x onerror=alert(1)>**'), '<strong>&lt;img src=x onerror=alert(1)&gt;</strong>');"],
                ['label' => 'Bold doesn\'t cross lines', 'code' => "return expect_same(formatComment(\"**a\\nb**\"), '**a<br>b**');"],
            ],
        ],
        [
            'title' => 'A Content Security Policy',
            'prompt' => 'Write `cspHeader(array $policy): string`, building the header value from directives mapped to lists of sources, in the order given: `[\'default-src\' => [\'self\'], \'script-src\' => [\'self\', \'https://cdn.example.com\']]` gives `default-src \'self\'; script-src \'self\' https://cdn.example.com`. The keywords `self`, `none`, `unsafe-inline`, `unsafe-eval` and `strict-dynamic`, and sources starting with `nonce-` or `sha256-`, are wrapped in single quotes; hosts are not. A directive with no sources is written alone, like `upgrade-insecure-requests`.',
            'starter_code' => <<<'PHP'
                <?php

                function cspHeader(array $policy): string
                {
                    $parts = [];
                    foreach ($policy as $directive => $sources) {
                        $parts[] = $directive . ' ' . implode(' ', $sources);
                    }

                    return implode('; ', $parts);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cspHeader(array $policy): string
                {
                    $keywords = ['self', 'none', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic'];

                    $parts = [];
                    foreach ($policy as $directive => $sources) {
                        $quoted = array_map(
                            fn (string $source) => in_array($source, $keywords, true) || preg_match('/^(nonce|sha256)-/', $source) === 1
                                ? "'$source'"
                                : $source,
                            $sources,
                        );
                        $parts[] = trim($directive . ' ' . implode(' ', $quoted));
                    }

                    return implode('; ', $parts);
                }
                PHP,
            'hint' => 'Map each source: quote it if it\'s one of the keywords or starts with `nonce-` or `sha256-`. `trim()` removes the trailing space for directives without sources.',
            'checks' => [
                ['label' => 'Keywords are quoted, hosts are not', 'code' => "return expect_same(cspHeader(['default-src' => ['self'], 'script-src' => ['self', 'https://cdn.example.com'], 'object-src' => ['none']]), \"default-src 'self'; script-src 'self' https://cdn.example.com; object-src 'none'\");"],
                ['label' => 'Nonces and hashes are quoted', 'code' => "return expect_same(cspHeader(['script-src' => ['nonce-r4nd0m', 'sha256-abc=', 'strict-dynamic']]), \"script-src 'nonce-r4nd0m' 'sha256-abc=' 'strict-dynamic'\");"],
                ['label' => 'Directives without sources', 'code' => "return expect_same(cspHeader(['upgrade-insecure-requests' => [], 'img-src' => ['self', 'data:']]), \"upgrade-insecure-requests; img-src 'self' data:\");"],
                ['label' => 'Only whole keywords are quoted', 'code' => "return expect_same(cspHeader(['connect-src' => ['selfish.example.com', 'https://none.example']]), 'connect-src selfish.example.com https://none.example');"],
            ],
        ],
    ],
];
