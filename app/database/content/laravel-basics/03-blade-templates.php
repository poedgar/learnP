<?php

// Lesson 3 of the Laravel basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'blade-templates',
    'title' => 'Blade templates',
    'summary' => 'Keeping HTML in views: escaped and raw echoes, directives and how they compile, loops with `$loop`, layouts with `@yield` and `@section`, and components with attributes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a book title with < and & in it.
        $books = [
            ['title' => 'Dune', 'author' => 'Frank Herbert'],
            ['title' => 'Piranesi', 'author' => 'Susanna Clarke'],
        ];

        $e = fn (string $value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        echo '<ul>' . PHP_EOL;
        foreach ($books as $book) {
            echo '  <li>' . $e($book['title']) . ' by ' . $e($book['author']) . '</li>' . PHP_EOL;
        }
        echo '</ul>';
        PHP,

    'quiz' => [
        [
            'question' => 'Where do Blade templates live?',
            'options' => ['`app/Views`', '`resources/views`', '`public/views`', '`routes/views`'],
            'correct_option' => 1,
            'explanation' => '`view(\'books.index\')` loads `resources/views/books/index.blade.php`.',
        ],
        [
            'question' => 'What is the difference between `{{ $title }}` and `{!! $title !!}`?',
            'options' => [
                'None',
                '`{{ }}` escapes the value for HTML; `{!! !!}` prints it raw',
                '`{!! !!}` is faster',
                '`{{ }}` only works for strings',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `{{ }}` for anything that might contain user input.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $title = '<b>Dune</b> & more';

                echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                PHP,
            'options' => [
                '`<b>Dune</b> & more`',
                '`&lt;b&gt;Dune&lt;/b&gt; &amp; more`',
                '`Dune & more`',
                '`&lt;b&gt;Dune&lt;/b&gt; & more`',
            ],
            'correct_option' => 1,
            'explanation' => 'Tags and the ampersand become entities, so the browser shows them as text.',
        ],
        [
            'question' => 'What does `@forelse ($books as $book) ... @empty ... @endforelse` do?',
            'options' => [
                'Loops twice',
                'Loops over the books, or shows the `@empty` part if there are none',
                'Skips empty books',
                'Empties the array after the loop',
            ],
            'correct_option' => 1,
            'explanation' => 'It saves writing an `@if (count($books))` around a `@foreach`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $titles = ['Dune', 'Piranesi', 'Foundation'];

                foreach ($titles as $index => $title) {
                    $isLast = $index === count($titles) - 1;
                    echo $title . ($isLast ? '.' : ', ');
                }
                PHP,
            'options' => ['`Dune, Piranesi, Foundation, `', '`Dune, Piranesi, Foundation.`', '`Dune. Piranesi. Foundation.`', '`Dune, Piranesi, Foundation`'],
            'correct_option' => 1,
            'explanation' => 'This is what `$loop->last` is for in Blade.',
        ],
        [
            'question' => 'What does `@if ($book->inStock)` compile to?',
            'options' => [
                '`<?php if ($book->inStock): ?>`',
                '`<?php echo $book->inStock; ?>`',
                '`{{ if $book->inStock }}`',
                'It isn\'t compiled; Blade interprets it each time',
            ],
            'correct_option' => 0,
            'explanation' => 'Blade turns directives into plain PHP once, then reuses the compiled file.',
        ],
        [
            'question' => 'A layout has `<title>@yield(\'title\', \'Corner Books\')</title>`. A page doesn\'t define a `title` section. What is the title?',
            'options' => ['Empty', '`Corner Books`', '`title`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'The second argument of `@yield` is the default.',
        ],
        [
            'question' => 'In a component, what is `$slot`?',
            'options' => [
                'The component\'s file name',
                'The content between the component\'s opening and closing tags',
                'The first attribute',
                'A free space in the layout',
            ],
            'correct_option' => 1,
            'explanation' => 'For `<x-alert>Saved!</x-alert>`, `$slot` is `Saved!`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Echo values',
            'prompt' => 'Write `render(string $template, array $data): string`. Replace each `{{ $name }}` (spaces inside the braces optional) with the value from `$data` escaped by `htmlspecialchars()`, and each `{!! $name !!}` with the raw value. Unknown names become an empty string.',
            'starter_code' => <<<'PHP'
                <?php

                function render(string $template, array $data): string
                {
                    return $template;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function render(string $template, array $data): string
                {
                    $html = preg_replace_callback(
                        '/\{!!\s*\$(\w+)\s*!!\}/',
                        fn (array $m) => (string) ($data[$m[1]] ?? ''),
                        $template,
                    );

                    return preg_replace_callback(
                        '/\{\{\s*\$(\w+)\s*\}\}/',
                        fn (array $m) => htmlspecialchars((string) ($data[$m[1]] ?? ''), ENT_QUOTES, 'UTF-8'),
                        $html,
                    );
                }
                PHP,
            'hint' => 'Use `preg_replace_callback()` with a pattern like `/\{\{\s*\$(\w+)\s*\}\}/`. Handle `{!! !!}` with its own pattern.',
            'checks' => [
                ['label' => 'Values are escaped', 'code' => "return expect_same(render('<h1>{{ \$title }}</h1>', ['title' => 'Tom & <Jerry>']), '<h1>Tom &amp; &lt;Jerry&gt;</h1>');"],
                ['label' => 'Raw values are not', 'code' => "return expect_same(render('<div>{!! \$html !!}</div>', ['html' => '<em>Hi</em>']), '<div><em>Hi</em></div>');"],
                ['label' => 'Spacing and several values', 'code' => "return expect_same(render('{{\$a}}-{{ \$b }}-{!!\$c!!}', ['a' => 1, 'b' => '\"', 'c' => '\"']), '1-&quot;-\"');"],
                ['label' => 'Unknown names are empty', 'code' => "return expect_same(render('[{{ \$missing }}]', []), '[]');"],
            ],
        ],
        [
            'title' => 'forelse',
            'prompt' => 'Write `bookList(array $books): string`, doing what this Blade does: for each book, `<li>Title by Author</li>` inside one `<ul>...</ul>`, with both values escaped; and for no books, `<p>No books yet.</p>`. Books are arrays with `title` and `author`.',
            'starter_code' => <<<'PHP'
                <?php

                function bookList(array $books): string
                {
                    $html = '<ul>';
                    foreach ($books as $book) {
                        $html .= "<li>{$book['title']} by {$book['author']}</li>";
                    }

                    return $html . '</ul>';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bookList(array $books): string
                {
                    if ($books === []) {
                        return '<p>No books yet.</p>';
                    }

                    $e = fn (string $value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    $html = '<ul>';
                    foreach ($books as $book) {
                        $html .= '<li>' . $e($book['title']) . ' by ' . $e($book['author']) . '</li>';
                    }

                    return $html . '</ul>';
                }
                PHP,
            'hint' => 'Check for an empty array first. Escape each value with `htmlspecialchars()` inside the loop.',
            'checks' => [
                ['label' => 'A list of books', 'code' => "return expect_same(bookList([['title' => 'Dune', 'author' => 'Frank Herbert'], ['title' => 'Piranesi', 'author' => 'Susanna Clarke']]), '<ul><li>Dune by Frank Herbert</li><li>Piranesi by Susanna Clarke</li></ul>');"],
                ['label' => 'No books', 'code' => "return expect_same(bookList([]), '<p>No books yet.</p>');"],
                ['label' => 'Values are escaped', 'code' => "return expect_same(bookList([['title' => '<script>', 'author' => 'A & B']]), '<ul><li>&lt;script&gt; by A &amp; B</li></ul>');"],
            ],
        ],
        [
            'title' => 'The $loop variable',
            'prompt' => 'Write `withLoop(array $items): array`, returning one entry per item, as Blade\'s `$loop` would describe it: `[\'item\' => ..., \'index\' => 0-based, \'iteration\' => 1-based, \'first\' => bool, \'last\' => bool, \'count\' => total]`. It must work for arrays with any keys, not just lists.',
            'starter_code' => <<<'PHP'
                <?php

                function withLoop(array $items): array
                {
                    return array_map(fn ($item) => ['item' => $item], $items);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function withLoop(array $items): array
                {
                    $count = count($items);
                    $result = [];
                    $index = 0;

                    foreach ($items as $item) {
                        $result[] = [
                            'item' => $item,
                            'index' => $index,
                            'iteration' => $index + 1,
                            'first' => $index === 0,
                            'last' => $index === $count - 1,
                            'count' => $count,
                        ];
                        $index++;
                    }

                    return $result;
                }
                PHP,
            'hint' => 'Count yourself with an `$index` that starts at 0, rather than trusting the array\'s keys.',
            'checks' => [
                ['label' => 'Three items', 'code' => "return expect_same(withLoop(['a', 'b', 'c'])[1], ['item' => 'b', 'index' => 1, 'iteration' => 2, 'first' => false, 'last' => false, 'count' => 3]);"],
                ['label' => 'First and last', 'code' => "\$loop = withLoop(['a', 'b', 'c']);\nreturn expect_same([\$loop[0]['first'], \$loop[0]['last'], \$loop[2]['first'], \$loop[2]['last']], [true, false, false, true]);"],
                ['label' => 'Any keys, and a single item', 'code' => "return expect_same([withLoop(['x' => 'Dune', 'y' => 'Piranesi'])[1]['index'], withLoop(['only'])[0]], [1, ['item' => 'only', 'index' => 0, 'iteration' => 1, 'first' => true, 'last' => true, 'count' => 1]]);"],
                ['label' => 'Nothing in, nothing out', 'code' => 'return expect_same(withLoop([]), []);'],
            ],
        ],
        [
            'title' => 'A layout',
            'prompt' => 'Write `renderLayout(string $layout, array $sections): string`. Replace each `@yield(\'name\')` with that section\'s content, and each `@yield(\'name\', \'default\')` with the content or, if the page has no such section, the default. A missing section without a default becomes an empty string.',
            'starter_code' => <<<'PHP'
                <?php

                function renderLayout(string $layout, array $sections): string
                {
                    return $layout;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function renderLayout(string $layout, array $sections): string
                {
                    return preg_replace_callback(
                        "/@yield\\('([\\w.-]+)'(?:,\\s*'([^']*)')?\\)/",
                        fn (array $m) => $sections[$m[1]] ?? ($m[2] ?? ''),
                        $layout,
                    );
                }
                PHP,
            'hint' => 'One `preg_replace_callback()` can handle both forms if the default part is optional: `(?:,\s*\'([^\']*)\')?`.',
            'checks' => [
                ['label' => 'Sections fill the gaps', 'code' => "return expect_same(renderLayout(\"<title>@yield('title')</title><main>@yield('content')</main>\", ['title' => 'All books', 'content' => '<h1>Books</h1>']), '<title>All books</title><main><h1>Books</h1></main>');"],
                ['label' => 'Defaults are used when missing', 'code' => "return expect_same(renderLayout(\"<title>@yield('title', 'Corner Books')</title>\", []), '<title>Corner Books</title>');"],
                ['label' => 'A section beats the default', 'code' => "return expect_same(renderLayout(\"<title>@yield('title', 'Corner Books')</title>\", ['title' => 'Dune']), '<title>Dune</title>');"],
                ['label' => 'Missing without a default is empty', 'code' => "return expect_same(renderLayout(\"<aside>@yield('sidebar')</aside>\", []), '<aside></aside>');"],
            ],
        ],
        [
            'title' => 'Component attributes',
            'prompt' => 'Write `attributes(array $attributes, string $defaultClass = \'\'): string`, turning attributes into HTML like `$attributes->merge()`. The default class comes first, followed by any `class` given, separated by a space. Then each attribute in order (with `class` first if there is one) as ` name="value"` with the value escaped; `true` gives just ` name`, and `false` or `null` leaves the attribute out.',
            'starter_code' => <<<'PHP'
                <?php

                function attributes(array $attributes, string $defaultClass = ''): string
                {
                    $html = '';
                    foreach ($attributes as $name => $value) {
                        $html .= " $name=\"$value\"";
                    }

                    return $html;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function attributes(array $attributes, string $defaultClass = ''): string
                {
                    $class = trim($defaultClass . ' ' . ($attributes['class'] ?? ''));
                    unset($attributes['class']);
                    if ($class !== '') {
                        $attributes = ['class' => $class] + $attributes;
                    }

                    $html = '';
                    foreach ($attributes as $name => $value) {
                        if ($value === true) {
                            $html .= " $name";
                        } elseif ($value !== false && $value !== null) {
                            $html .= " $name=\"" . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
                        }
                    }

                    return $html;
                }
                PHP,
            'hint' => 'Work out the merged class first, take `class` out of the array and put it back at the front with `[\'class\' => $class] + $attributes`. Then loop, handling `true`, `false` and `null`.',
            'checks' => [
                ['label' => 'Classes are merged', 'code' => "return expect_same(attributes(['class' => 'mb-4', 'role' => 'alert'], 'alert alert-error'), ' class=\"alert alert-error mb-4\" role=\"alert\"');"],
                ['label' => 'Booleans and null', 'code' => "return expect_same(attributes(['type' => 'submit', 'disabled' => true, 'hidden' => false, 'title' => null]), ' type=\"submit\" disabled');"],
                ['label' => 'Values are escaped', 'code' => "return expect_same(attributes(['title' => 'Save \"Dune\" & go']), ' title=\"Save &quot;Dune&quot; &amp; go\"');"],
                ['label' => 'Only a default class, or none', 'code' => "return expect_same([attributes([], 'btn'), attributes([])], [' class=\"btn\"', '']);"],
            ],
        ],
        [
            'title' => 'Compile @if',
            'prompt' => 'Write `compileIfs(string $template): string`, doing Blade\'s job for conditions: `@if (condition)` becomes `<?php if (condition): ?>`, `@elseif (condition)` becomes `<?php elseif (condition): ?>`, `@else` becomes `<?php else: ?>` and `@endif` becomes `<?php endif; ?>`. A space before the parentheses is optional, and conditions may contain their own parentheses (one level deep), like `@if (count($books) > 0)`.',
            'starter_code' => <<<'PHP'
                <?php

                function compileIfs(string $template): string
                {
                    return $template;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function compileIfs(string $template): string
                {
                    $condition = '\s*\(((?:[^()]|\([^()]*\))*)\)';

                    $php = preg_replace("/@elseif$condition/", '<?php elseif ($1): ?>', $template);
                    $php = preg_replace("/@if$condition/", '<?php if ($1): ?>', $php);
                    $php = str_replace('@endif', '<?php endif; ?>', $php);

                    return preg_replace('/@else\b/', '<?php else: ?>', $php);
                }
                PHP,
            'hint' => 'Handle `@elseif` before `@if`, and `@endif` before `@else`, so the shorter words don\'t match inside the longer ones. A pattern like `\((?:[^()]|\([^()]*\))*\)` matches parentheses with one level of nesting inside.',
            'checks' => [
                ['label' => 'if and endif', 'code' => "return expect_same(compileIfs('@if (\$book->inStock)In stock@endif'), '<?php if (\$book->inStock): ?>In stock<?php endif; ?>');"],
                ['label' => 'elseif and else', 'code' => "return expect_same(compileIfs('@if(\$a)A@elseif(\$b)B@else C@endif'), '<?php if (\$a): ?>A<?php elseif (\$b): ?>B<?php else: ?> C<?php endif; ?>');"],
                ['label' => 'Parentheses inside conditions', 'code' => "return expect_same(compileIfs('@if (count(\$books) > 0)Some@endif'), '<?php if (count(\$books) > 0): ?>Some<?php endif; ?>');"],
                ['label' => 'The compiled template runs', 'code' => "\$php = compileIfs('@if (\$n > 1)many@elseif (\$n === 1)one@else none@endif');\n\$run = function (int \$n) use (\$php) { ob_start(); eval('?>' . \$php); return ob_get_clean(); };\nreturn expect_same([\$run(5), \$run(1), \$run(0)], ['many', 'one', ' none']);"],
            ],
        ],
    ],
];
