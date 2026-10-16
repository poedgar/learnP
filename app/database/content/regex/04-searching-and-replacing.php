<?php

// Lesson 4 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'searching-and-replacing',
    'title' => 'Searching and replacing',
    'summary' => 'Go through every match with matchAll, replace with $1 and $<name> or a function, split on patterns, and know how exec and lastIndex work.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const text = 'Contact amara@example.com or ben@example.org for details.';

        const masked = text.replace(/(\w)\w*@/g, '$1***@');
        console.log(masked);

        for (const match of text.matchAll(/@(\w+)\.(\w+)/g)) {
            console.log(match[1], match[2], match.index);
        }
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('a.b.c'.replace('.', '-'), 'a.b.c'.replace(/\./g, '-'));
                JS,
            'options' => ['`a-b.c a-b-c`', '`a-b-c a-b-c`', '`a.b.c a-b-c`', '`a-b.c a-b.c`'],
            'correct_option' => 0,
            'explanation' => 'A string pattern replaces only the first occurrence; a regex with `g` replaces them all.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('Okafor, Amara'.replace(/(\w+), (\w+)/, '$2 $1'));
                JS,
            'options' => ['`Amara Okafor`', '`$2 $1`', '`Okafor Amara`', '`Okafor, Amara`'],
            'correct_option' => 0,
            'explanation' => '`$1` and `$2` insert the captured groups.',
        ],
        [
            'question' => 'What does `$&` mean in a replacement string?',
            'options' => ['A literal dollar and ampersand', 'The whole matched text', 'The text before the match', 'The first group'],
            'correct_option' => 1,
            'explanation' => '`\'cat\'.replace(/a/, \'[$&]\')` gives `c[a]t`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('1 2 3'.replace(/\d/g, (d) => d * 10));
                JS,
            'options' => ['`10 20 30`', '`1 2 3`', '`d*10 d*10 d*10`', '`60`'],
            'correct_option' => 0,
            'explanation' => 'The function is called for each match, and its result replaces it.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('a1b2c3'.split(/\d/), 'x-y'.split(/(-)/));
                JS,
            'options' => ["`[ 'a', 'b', 'c', '' ] [ 'x', '-', 'y' ]`", "`[ 'a', 'b', 'c' ] [ 'x', 'y' ]`", "`[ 'a1', 'b2', 'c3' ] [ 'x-y' ]`", "`[ 'a', 'b', 'c', '' ] [ 'x', 'y' ]`"],
            'correct_option' => 0,
            'explanation' => 'The digit at the very end leaves an empty last piece; a captured separator is kept.',
        ],
        [
            'question' => 'Why does a `g` regex give `true`, then `false`, when `test()` is called twice on the same string?',
            'options' => [
                'It\'s random',
                'With `g`, the regex remembers `lastIndex` and the second search starts after the first match',
                '`test()` only works once per regex',
                'The string changed',
            ],
            'correct_option' => 1,
            'explanation' => 'Don\'t use `g` on a regex you only `test()` with.',
        ],
        [
            'question' => 'What does `matchAll()` give that `match()` with `g` doesn\'t?',
            'options' => [
                'More matches',
                'A full match object for each match, with its groups and index',
                'Only the first match',
                'The matches in reverse',
            ],
            'correct_option' => 1,
            'explanation' => '`match()` with `g` returns just the matched strings.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const found = [...'ab ab ab'.matchAll(/ab/g)].map((m) => m.index);
                console.log(found.join(','));
                JS,
            'options' => ['`0,3,6`', '`0,2,4`', '`1,4,7`', '`ab,ab,ab`'],
            'correct_option' => 0,
            'explanation' => 'Each match object has the index where it starts.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Last name first',
            'prompt' => "Write `lastFirst(names)`, which turns each `'First Last'` in a comma-separated list into `'Last, First'`, keeping the list's separators: `'Amara Okafor, Ben Ito'` → `'Okafor, Amara; Ito, Ben'`. Join the results with `'; '`.",
            'starter_code' => <<<'JS'
                function lastFirst(names) {
                    return names;
                }
                JS,
            'solution' => <<<'JS'
                function lastFirst(names) {
                    return names
                        .split(/\s*,\s*/)
                        .map((name) => name.replace(/^(\S+) (\S+)$/, '$2, $1'))
                        .join('; ');
                }
                JS,
            'hint' => 'Split on commas with any spaces around them, then `replace(/^(\S+) (\S+)$/, \'$2, $1\')` each name.',
            'checks' => [
                ['label' => 'Swaps names', 'code' => "return expectSame([lastFirst('Amara Okafor, Ben Ito'), lastFirst('Cy Lane'), lastFirst('Dee Rao ,Eli Moss')], ['Okafor, Amara; Ito, Ben', 'Lane, Cy', 'Rao, Dee; Moss, Eli']);"],
            ],
        ],
        [
            'title' => 'Every link',
            'prompt' => 'Write `links(markdown)`, returning `{ text, url }` for every Markdown link `[text](url)` in `markdown`, in order, using `matchAll()`.',
            'starter_code' => <<<'JS'
                function links(markdown) {
                    return markdown.match(/\[.*\]\(.*\)/g) ?? [];
                }
                JS,
            'solution' => <<<'JS'
                function links(markdown) {
                    return [...markdown.matchAll(/\[(?<text>[^\]]+)\]\((?<url>[^)]+)\)/g)].map(({ groups }) => ({ text: groups.text, url: groups.url }));
                }
                JS,
            'hint' => 'Named groups for the text, `[^\]]+`, and the URL, `[^)]+`, so neither runs past its closing bracket.',
            'checks' => [
                ['label' => 'Finds links', 'code' => "return expectSame(links('See [the docs](https://example.org/docs) and [home](/).'), [{ text: 'the docs', url: 'https://example.org/docs' }, { text: 'home', url: '/' }]);"],
                ['label' => 'No links', 'code' => "return expectSame(links('plain [text] and (brackets)'), []);"],
            ],
        ],
        [
            'title' => 'Snake case to camel case',
            'prompt' => "Write `camelCase(name)`, turning `snake_case` names into `camelCase`: `'first_name'` → `'firstName'`, `'is_admin_user'` → `'isAdminUser'`. Use `replace()` with a function.",
            'starter_code' => <<<'JS'
                function camelCase(name) {
                    return name.replace('_', '');
                }
                JS,
            'solution' => <<<'JS'
                function camelCase(name) {
                    return name.replace(/_([a-z])/g, (underscoreAndLetter, letter) => letter.toUpperCase());
                }
                JS,
            'hint' => 'Match an underscore followed by a letter, capture the letter, and return it upper-cased.',
            'checks' => [
                ['label' => 'Converts names', 'code' => "return expectSame(['first_name', 'is_admin_user', 'id', 'a_b_c'].map(camelCase), ['firstName', 'isAdminUser', 'id', 'aBC']);"],
            ],
        ],
        [
            'title' => 'Hide the numbers',
            'prompt' => 'Write `maskCards(text)`, replacing every card number (16 digits, optionally in groups of four separated by spaces or hyphens) with `**** **** **** ` followed by its last four digits.',
            'starter_code' => <<<'JS'
                function maskCards(text) {
                    return text.replace(/\d/g, '*');
                }
                JS,
            'solution' => <<<'JS'
                function maskCards(text) {
                    return text.replace(/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?(\d{4})\b/g, '**** **** **** $1');
                }
                JS,
            'hint' => 'Four groups of `\d{4}` with an optional `[ -]?` between them; capture only the last group.',
            'checks' => [
                ['label' => 'Masks card numbers', 'code' => "return expectSame(maskCards('Paid with 4242 4242 4242 1234, then 5555-4444-3333-9876.'), 'Paid with **** **** **** 1234, then **** **** **** 9876.');"],
                ['label' => 'Unbroken numbers too', 'code' => "return expectSame(maskCards('card 1111222233334444 ok'), 'card **** **** **** 4444 ok');"],
                ['label' => 'Leaves other numbers', 'code' => "return expectSame(maskCards('Order 12345, room 7'), 'Order 12345, room 7');"],
            ],
        ],
        [
            'title' => 'A simple template',
            'prompt' => "Write `fill(template, values)`, replacing each `:name` placeholder (a colon followed by letters) with `values[name]`, and leaving placeholders that aren't in `values` as they are: `fill('Hi :first, :missing', { first: 'Amara' })` → `'Hi Amara, :missing'`.",
            'starter_code' => <<<'JS'
                function fill(template, values) {
                    return template.replace(/:\w+/, values.first);
                }
                JS,
            'solution' => <<<'JS'
                function fill(template, values) {
                    return template.replace(/:([a-zA-Z]+)/g, (placeholder, name) => (Object.hasOwn(values, name) ? String(values[name]) : placeholder));
                }
                JS,
            'hint' => 'A replacement function receives the whole placeholder and the captured name; return the value, or the placeholder itself.',
            'checks' => [
                ['label' => 'Fills placeholders', 'code' => "return expectSame([fill('Hi :first, :missing', { first: 'Amara' }), fill(':a+:b=:c', { a: 1, b: 2, c: 3 })], ['Hi Amara, :missing', '1+2=3']);"],
                ['label' => 'Only own values', 'code' => "return expectSame(fill('x :toString y', {}), 'x :toString y');"],
            ],
        ],
        [
            'title' => 'Tokens, one at a time',
            'prompt' => "Write `tokens(expression)`, splitting a sum like `'12 + 3*(40 - 5)'` into tokens: numbers (as numbers) and the symbols `+ - * / ( )` (as strings), ignoring spaces. Throw a `SyntaxError` at the first character that's none of these.",
            'starter_code' => <<<'JS'
                function tokens(expression) {
                    return expression.split(' ');
                }
                JS,
            'solution' => <<<'JS'
                function tokens(expression) {
                    const pattern = /\s*(?:(\d+)|([+\-*/()]))/y;
                    const result = [];
                    pattern.lastIndex = 0;
                    while (pattern.lastIndex < expression.length) {
                        const start = pattern.lastIndex;
                        const match = pattern.exec(expression);
                        if (!match) {
                            if (expression.slice(start).trim() === '') break;
                            throw new SyntaxError(`Unexpected character at ${start}`);
                        }
                        result.push(match[1] !== undefined ? Number(match[1]) : match[2]);
                    }
                    return result;
                }
                JS,
            'hint' => 'A sticky regex (`y` flag) matches only at `lastIndex`, so calling `exec()` in a loop reads the text token by token; a failed match means a bad character.',
            'checks' => [
                ['label' => 'Tokens', 'code' => "return expectSame([tokens('12 + 3*(40 - 5)'), tokens('7'), tokens('  ')], [[12, '+', 3, '*', '(', 40, '-', 5, ')'], [7], []]);"],
                ['label' => 'Bad characters', 'code' => "return expectThrows(() => tokens('2 + x'), SyntaxError);"],
            ],
        ],
    ],
];
