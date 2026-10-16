<?php

// Lesson 1 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'patterns-and-character-classes',
    'title' => 'Patterns and character classes',
    'summary' => 'Write your first regular expressions: literal matches, test and match, flags, escaping special characters, character classes and the \d, \w and \s shorthands.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const text = 'Call 0113 496 0000 or 07700 900 123 before 5pm.';

        console.log(/\d/.test(text));
        console.log(text.match(/\d/g).length, 'digits');
        console.log(text.match(/[A-Z]/g));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(/at/.test('Batman'), /at/.test('BAT'), /at/i.test('BAT'));
                JS,
            'options' => ['`true false true`', '`true true true`', '`false false true`', '`true false false`'],
            'correct_option' => 0,
            'explanation' => 'Matching is case-sensitive unless the `i` flag is set.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('a1b22c333'.match(/\d/g).length, 'a1b22'.match(/\d/)[0]);
                JS,
            'options' => ['`6 1`', '`3 1`', '`6 22`', '`1 1`'],
            'correct_option' => 0,
            'explanation' => 'With `g`, every single digit is a match (6 of them); without it, only the first.',
        ],
        [
            'question' => 'What does `.` match?',
            'options' => [
                'A full stop',
                'Any single character except a line break',
                'Any number of characters',
                'The end of the string',
            ],
            'correct_option' => 1,
            'explanation' => 'Escape it, `\.`, to match a full stop.',
        ],
        [
            'question' => 'What does `[^0-9]` match?',
            'options' => [
                'A digit at the start of the text',
                'Any one character that isn\'t a digit',
                'The characters ^, 0 and 9',
                'Zero to nine digits',
            ],
            'correct_option' => 1,
            'explanation' => 'A `^` straight after `[` negates the set. The same as `\D`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('gray grey graay'.match(/gr[ae]y/g).join(' '));
                JS,
            'options' => ['`gray grey`', '`gray grey graay`', '`gray`', '`grey`'],
            'correct_option' => 0,
            'explanation' => 'A character class matches exactly one character, so "graay" doesn\'t fit.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('hello world'.match(/xyz/), 'hello'.search(/l/));
                JS,
            'options' => ['`null 2`', '`undefined 2`', '`[] 2`', '`null 3`'],
            'correct_option' => 0,
            'explanation' => '`match()` gives `null` when nothing matches; `search()` gives the first position, counting from 0.',
        ],
        [
            'question' => 'Which pattern matches the text `$5.00` literally?',
            'options' => ['`/$5.00/`', '`/\$5\.00/`', '`/[$5.00]/`', '`/\\$5\\.00/`'],
            'correct_option' => 1,
            'explanation' => '`$` and `.` are special, so each needs a backslash.',
        ],
        [
            'question' => 'Why escape user input before `new RegExp(input)`?',
            'options' => [
                'It makes the pattern faster',
                'Characters such as `+`, `(` and `.` in the input would otherwise be read as pattern syntax',
                'RegExp only accepts escaped strings',
                'To remove spaces',
            ],
            'correct_option' => 1,
            'explanation' => 'Searching for "C++" without escaping is even a syntax error.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Does it mention a number?',
            'prompt' => 'Write `hasDigit(text)`, returning whether `text` contains at least one digit, using `test()` and a regex.',
            'starter_code' => <<<'JS'
                function hasDigit(text) {
                    return text.includes('1');
                }
                JS,
            'solution' => <<<'JS'
                function hasDigit(text) {
                    return /\d/.test(text);
                }
                JS,
            'hint' => '`/\d/` matches any one digit.',
            'checks' => [
                ['label' => 'Finds digits', 'code' => "return expectSame([hasDigit('Room 7'), hasDigit('no digits'), hasDigit('2026'), hasDigit('')], [true, false, true, false]);"],
                ['label' => 'Uses a regex', 'code' => "return /\\/[^/]+\\/\\w*\\.test\\(/.test(learnerCode()) ? true : 'Use a regex with test().';"],
            ],
        ],
        [
            'title' => 'Count the vowels',
            'prompt' => 'Write `vowelCount(text)`, returning how many vowels (a, e, i, o, u, in either case) `text` contains, using `match()` with a character class and flags. Text without vowels gives `0`.',
            'starter_code' => <<<'JS'
                function vowelCount(text) {
                    return text.match(/[aeiou]/).length;
                }
                JS,
            'solution' => <<<'JS'
                function vowelCount(text) {
                    return (text.match(/[aeiou]/gi) ?? []).length;
                }
                JS,
            'hint' => 'Add the `g` and `i` flags, and remember that `match()` gives `null` when there\'s no match.',
            'checks' => [
                ['label' => 'Counts vowels', 'code' => "return expectSame([vowelCount('Dune'), vowelCount('AEIOU and aeiou'), vowelCount('rhythm'), vowelCount('')], [2, 11, 0, 0]);"],
            ],
        ],
        [
            'title' => 'Find the prices',
            'prompt' => 'Write `prices(text)`, returning every price written like `£12.99` (a pound sign, digits, a full stop, two digits) in `text`, as an array of strings. Assume prices have one or two digits before the full stop. Return `[]` when there are none.',
            'starter_code' => <<<'JS'
                function prices(text) {
                    return text.match(/£\d.\d\d/g);
                }
                JS,
            'solution' => <<<'JS'
                function prices(text) {
                    return text.match(/£\d?\d\.\d\d/g) ?? [];
                }
                JS,
            'hint' => 'Escape the full stop with `\.`. One or two digits can be written `\d?\d` (`?` means optional; there\'s more about it next lesson).',
            'checks' => [
                ['label' => 'Finds prices', 'code' => "return expectSame(prices('Dune £9.99, Emma £12.50, both £22.49'), ['£9.99', '£12.50', '£22.49']);"],
                ['label' => 'Needs a real full stop', 'code' => "return expectSame([prices('£9x99 is not a price'), prices('none')], [[], []]);"],
            ],
        ],
        [
            'title' => 'Not letters or digits',
            'prompt' => "Write `symbols(text)`, returning a string of every character in `text` that isn't a letter, a digit or a space, in order. Use a negated character class.",
            'starter_code' => <<<'JS'
                function symbols(text) {
                    return text.match(/\W/g).join('');
                }
                JS,
            'solution' => <<<'JS'
                function symbols(text) {
                    return (text.match(/[^A-Za-z0-9 ]/g) ?? []).join('');
                }
                JS,
            'hint' => '`[^A-Za-z0-9 ]` matches one character that is none of those. `\W` also matches spaces, which you don\'t want.',
            'checks' => [
                ['label' => 'Finds symbols', 'code' => "return expectSame([symbols('Hi, Ben! (Room 4-B)'), symbols('plain text 42'), symbols('')], [',!(-)', '', '']);"],
                ['label' => 'Underscores count as symbols', 'code' => "return expectSame(symbols('snake_case'), '_');"],
            ],
        ],
        [
            'title' => 'Search safely',
            'prompt' => 'Write `escapeRegex(text)`, which puts a backslash before every regex special character (`. * + ? ^ $ { } ( ) | [ ] \\ /`), and `containsTerm(text, term)`, which uses it with `new RegExp` to check, ignoring case, whether `text` contains `term` literally.',
            'starter_code' => <<<'JS'
                function escapeRegex(text) {
                    return text;
                }

                function containsTerm(text, term) {
                    return new RegExp(term, 'i').test(text);
                }
                JS,
            'solution' => <<<'JS'
                function escapeRegex(text) {
                    return text.replace(/[.*+?^${}()|[\]\\/]/g, '\\$&');
                }

                function containsTerm(text, term) {
                    return new RegExp(escapeRegex(term), 'i').test(text);
                }
                JS,
            'hint' => '`text.replace(/[.*+?^${}()|[\]\\\/]/g, \'\\\\$&\')`: `$&` in the replacement is the matched character.',
            'checks' => [
                ['label' => 'Escapes special characters', 'code' => "return expectSame([escapeRegex('a.b'), escapeRegex('(1+1)?'), escapeRegex('plain')], ['a\\\\.b', '\\\\(1\\\\+1\\\\)\\\\?', 'plain']);"],
                ['label' => 'Searches literally', 'code' => "return expectSame([containsTerm('Learn C++ today', 'c++'), containsTerm('Price: $5', '$5'), containsTerm('abc', 'a.c'), containsTerm('a.c', 'a.c')], [true, true, false, true]);"],
            ],
        ],
        [
            'title' => 'Tidy whitespace',
            'prompt' => 'Write `words(text)`, returning the words of `text` as an array, splitting on any whitespace (spaces, tabs, line breaks) and dropping empty strings.',
            'starter_code' => <<<'JS'
                function words(text) {
                    return text.split(' ');
                }
                JS,
            'solution' => <<<'JS'
                function words(text) {
                    return text.split(/\s/).filter((word) => word !== '');
                }
                JS,
            'hint' => '`split()` accepts a regex: `/\s/` splits on any whitespace character. Then filter out the empty pieces.',
            'checks' => [
                ['label' => 'Splits on any whitespace', 'code' => "return expectSame([words('one two\\tthree\\nfour'), words('  spaced   out  '), words('')], [['one', 'two', 'three', 'four'], ['spaced', 'out'], []]);"],
            ],
        ],
    ],
];
