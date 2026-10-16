<?php

// Lesson 2 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'quantifiers-and-anchors',
    'title' => 'Quantifiers and anchors',
    'summary' => 'Say how many times something repeats with ?, *, + and {n,m}, choose greedy or lazy matching, and pin matches down with ^, $, \b and the m and s flags.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const codes = ['AB12', 'AB123', 'A12', 'ab12', 'AB12X'];

        for (const code of codes) {
            console.log(code, /^[A-Z]{2}\d{2,3}$/.test(code));
        }
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('a aa aaa'.match(/a+/g).length, 'xyz'.match(/a*/)[0] === '');
                JS,
            'options' => ['`3 true`', '`6 true`', '`3 false`', '`1 true`'],
            'correct_option' => 0,
            'explanation' => '`a+` matches each run of a\'s; `a*` can match zero characters, so it matches the empty string at the start.',
        ],
        [
            'question' => 'What does `/^\d{3}$/` accept?',
            'options' => [
                'Any text containing three digits',
                'Text that is exactly three digits and nothing else',
                'Up to three digits',
                'Three digits at the start',
            ],
            'correct_option' => 1,
            'explanation' => '`^` and `$` anchor the pattern to the start and end of the text.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const s = '"one" and "two"';
                console.log(s.match(/".*"/)[0], '|', s.match(/".*?"/)[0]);
                JS,
            'options' => ['`"one" and "two" | "one"`', '`"one" | "one"`', '`"one" and "two" | "one" and "two"`', '`"two" | "one"`'],
            'correct_option' => 0,
            'explanation' => 'The greedy `.*` runs to the last quote; the lazy `.*?` stops at the first one it can.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('art cart artist'.match(/\bart\b/g).length);
                JS,
            'options' => ['`1`', '`3`', '`2`', '`0`'],
            'correct_option' => 0,
            'explanation' => 'Only the first "art" is a whole word; the others are inside "cart" and "artist".',
        ],
        [
            'question' => 'What does the `m` flag change?',
            'options' => [
                'It makes the pattern match many times',
                'It makes `^` and `$` match at the start and end of every line, not just the whole text',
                'It lets `.` match line breaks',
                'It ignores case',
            ],
            'correct_option' => 1,
            'explanation' => 'Letting `.` match line breaks is the `s` flag.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const text = 'one\ntwo';
                console.log(/one.two/.test(text), /one.two/s.test(text));
                JS,
            'options' => ['`false true`', '`true true`', '`false false`', '`true false`'],
            'correct_option' => 0,
            'explanation' => 'Without the `s` flag, `.` doesn\'t match the line break between the words.',
        ],
        [
            'question' => 'What does `/colou?r/` match?',
            'options' => ['"colour" only', '"color" and "colour"', '"colouur" too', '"colr"'],
            'correct_option' => 1,
            'explanation' => '`u?` means the u is optional: zero or one of it.',
        ],
        [
            'question' => 'Why does `/\d{4}/.test(\'tel 01134\')` give `true`, though the input isn\'t a four-digit year?',
            'options' => [
                'It\'s a bug in JavaScript',
                'Without anchors, the pattern only needs to match somewhere inside the text, and "0113" is four digits',
                '`{4}` means at least four',
                'test() ignores letters',
            ],
            'correct_option' => 1,
            'explanation' => 'Anchor validation patterns: `/^\d{4}$/`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Exactly a year',
            'prompt' => "Write `isYear(text)`, true only when `text` is exactly four digits starting with `19` or `20`, such as `'1999'` or `'2026'`.",
            'starter_code' => <<<'JS'
                function isYear(text) {
                    return /\d{4}/.test(text);
                }
                JS,
            'solution' => <<<'JS'
                function isYear(text) {
                    return /^(19|20)\d{2}$/.test(text);
                }
                JS,
            'hint' => 'Anchor it with `^` and `$`. The century can be written `(19|20)` (alternatives in a group).',
            'checks' => [
                ['label' => 'Years', 'code' => "return expectSame(['1999', '2026', '1900'].map(isYear), [true, true, true]);"],
                ['label' => 'Not years', 'code' => "return expectSame(['2026!', 'in 2026', '1850', '20266', '202', ''].map(isYear), [false, false, false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'Numbers in text',
            'prompt' => "Write `numbers(text)`, returning every whole number in `text` (runs of digits) as numbers, e.g. `'Rooms 3, 12 and 107'` → `[3, 12, 107]`.",
            'starter_code' => <<<'JS'
                function numbers(text) {
                    return text.match(/\d/g).map(Number);
                }
                JS,
            'solution' => <<<'JS'
                function numbers(text) {
                    return (text.match(/\d+/g) ?? []).map(Number);
                }
                JS,
            'hint' => '`\d+` matches a whole run of digits. Convert each match with `Number`.',
            'checks' => [
                ['label' => 'Finds numbers', 'code' => "return expectSame([numbers('Rooms 3, 12 and 107'), numbers('2026-10-16'), numbers('none')], [[3, 12, 107], [2026, 10, 16], []]);"],
            ],
        ],
        [
            'title' => 'Strip the tags',
            'prompt' => "Write `stripTags(html)`, removing every HTML tag (anything from `<` to the next `>`) and keeping the text: `'<p>Hi <b>there</b></p>'` → `'Hi there'`.",
            'starter_code' => <<<'JS'
                function stripTags(html) {
                    return html.replace(/<.*>/g, '');
                }
                JS,
            'solution' => <<<'JS'
                function stripTags(html) {
                    return html.replace(/<[^>]*>/g, '');
                }
                JS,
            'hint' => 'The greedy `<.*>` swallows everything from the first `<` to the last `>`. Use a lazy quantifier, or `[^>]*`.',
            'checks' => [
                ['label' => 'Keeps the text', 'code' => "return expectSame([stripTags('<p>Hi <b>there</b></p>'), stripTags('a <br> b <img src=\"x\"> c'), stripTags('plain')], ['Hi there', 'a  b  c', 'plain']);"],
            ],
        ],
        [
            'title' => 'Whole words only',
            'prompt' => "Write `countWord(text, word)`, returning how many times `word` appears in `text` as a whole word, ignoring case: in `'The cat scattered the cats'`, `'cat'` appears once. `word` contains only letters.",
            'starter_code' => <<<'JS'
                function countWord(text, word) {
                    return text.split(word).length - 1;
                }
                JS,
            'solution' => <<<'JS'
                function countWord(text, word) {
                    return (text.match(new RegExp(`\\b${word}\\b`, 'gi')) ?? []).length;
                }
                JS,
            'hint' => 'Build `new RegExp(`\\b${word}\\b`, \'gi\')`: in a template string, a backslash must be doubled.',
            'checks' => [
                ['label' => 'Counts whole words', 'code' => "return expectSame([countWord('The cat scattered the cats', 'cat'), countWord('Cat, CAT and cat.', 'cat'), countWord('category', 'cat')], [1, 3, 0]);"],
            ],
        ],
        [
            'title' => 'Line by line',
            'prompt' => "Write `headings(markdown)`, returning the text of every line that starts with `# ` (one hash and a space) in a multi-line string, using the `m` flag: `'# Intro\\ntext\\n# Next'` → `['Intro', 'Next']`. Lines starting with `##` don't count.",
            'starter_code' => <<<'JS'
                function headings(markdown) {
                    return markdown.match(/^# .*/g) ?? [];
                }
                JS,
            'solution' => <<<'JS'
                function headings(markdown) {
                    return (markdown.match(/^# .*$/gm) ?? []).map((line) => line.slice(2));
                }
                JS,
            'hint' => 'With `gm`, `^` matches at the start of each line. Then remove the `# ` from each match.',
            'checks' => [
                ['label' => 'Finds headings', 'code' => "return expectSame([headings('# Intro\\nSome text\\n# Next steps\\n## Detail'), headings('no headings')], [['Intro', 'Next steps'], []]);"],
                ['label' => 'Only at the start of a line', 'code' => "return expectSame(headings('see # this\\n#tag\\n# Real'), ['Real']);"],
            ],
        ],
        [
            'title' => 'A username rule',
            'prompt' => 'Write `isUsername(text)`: 3 to 16 characters long, only lower-case letters, digits and underscores, and starting with a letter.',
            'starter_code' => <<<'JS'
                function isUsername(text) {
                    return /[a-z0-9_]{3,16}/.test(text);
                }
                JS,
            'solution' => <<<'JS'
                function isUsername(text) {
                    return /^[a-z][a-z0-9_]{2,15}$/.test(text);
                }
                JS,
            'hint' => 'One letter first, then 2 to 15 more allowed characters, anchored at both ends.',
            'checks' => [
                ['label' => 'Good usernames', 'code' => "return expectSame(['amara', 'ben_42', 'abc', 'a234567890123456'].map(isUsername), [true, true, true, true]);"],
                ['label' => 'Bad usernames', 'code' => "return expectSame(['ab', '1amara', 'Amara', 'amara!', 'a2345678901234567', 'has space', ''].map(isUsername), [false, false, false, false, false, false, false]);"],
            ],
        ],
    ],
];
