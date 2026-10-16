<?php

// Lesson 5 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'lookarounds-and-unicode',
    'title' => 'Lookarounds and Unicode',
    'summary' => 'Check what comes before or after a match with lookahead and lookbehind, combine several rules in one pattern, work with any language using the u flag and \p{...}, and avoid catastrophic backtracking.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const text = 'Tickets: £12 adults, £6 children, 3 for £30. Café crème: €4.';

        console.log(text.match(/(?<=£)\d+/g));
        console.log(text.match(/\d+(?! for)/g));
        console.log(text.match(/\p{L}+/gu).filter((w) => /[^a-z]/i.test(w)));
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('$5 £7 €9'.match(/(?<=£)\d/g), '$5 £7 €9'.match(/\d(?= €)/g));
                JS,
            'options' => ["`[ '7' ] [ '7' ]`", "`[ '£7' ] [ '7 €' ]`", "`[ '7' ] [ '9' ]`", '`null null`'],
            'correct_option' => 0,
            'explanation' => 'The lookbehind needs a £ before the digit; the lookahead needs " €" after it. Neither is part of the match.',
        ],
        [
            'question' => 'What does `(?!...)` mean?',
            'options' => [
                'An optional group',
                'Not followed by ...: a position where the given pattern doesn\'t come next',
                'A comment',
                'Preceded by ...',
            ],
            'correct_option' => 1,
            'explanation' => 'It checks without matching any characters.',
        ],
        [
            'question' => 'Why can `/^(?=.*\d)(?=.*[A-Z]).{8,}$/` check several rules at once?',
            'options' => [
                'Because `^` repeats',
                'Each lookahead checks something about the rest of the text without consuming it, so all of them, and `.{8,}`, look at the same text',
                'Regexes always check everything',
                'It doesn\'t; only the first lookahead counts',
            ],
            'correct_option' => 1,
            'explanation' => 'This is the usual way to write "must contain a digit and an upper-case letter and be 8 long".',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('naïve'.match(/\w+/)[0], 'naïve'.match(/\p{L}+/u)[0]);
                JS,
            'options' => ['`na naïve`', '`naïve naïve`', '`na na`', 'An error'],
            'correct_option' => 0,
            'explanation' => '`\w` only knows English letters, so it stops at ï; `\p{L}` matches letters of any language.',
        ],
        [
            'question' => 'What does the `u` flag do?',
            'options' => [
                'Makes the pattern upper-case',
                'Treats the text as whole Unicode characters and enables `\p{...}` property classes',
                'Matches only unique characters',
                'Ignores case',
            ],
            'correct_option' => 1,
            'explanation' => 'Without it, an emoji counts as two characters.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('🙂🙂'.length, [...'🙂🙂'].length, '🙂🙂'.match(/./gu).length);
                JS,
            'options' => ['`4 2 2`', '`2 2 2`', '`4 4 4`', '`4 2 4`'],
            'correct_option' => 0,
            'explanation' => 'Each emoji is two UTF-16 code units; spreading the string and the `u` flag both work in whole characters.',
        ],
        [
            'question' => 'What is catastrophic backtracking?',
            'options' => [
                'A regex syntax error',
                'A pattern with nested quantifiers taking an enormous time on text that almost matches, trying every way to split it',
                'Matching backwards',
                'A lookbehind that fails',
            ],
            'correct_option' => 1,
            'explanation' => '`/^(a+)+$/` on a long run of a\'s followed by "!" is the classic example.',
        ],
        [
            'question' => 'Which pattern matches a word starting with an upper-case letter in any language?',
            'options' => ['`/\b[A-Z]\w*/`', '`/\p{Lu}\p{L}*/u`', '`/[A-Z]+/i`', '`/\W\w+/`'],
            'correct_option' => 1,
            'explanation' => '`\p{Lu}` is any upper-case letter, such as É or Ö; `[A-Z]` is English only.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Amounts in pounds',
            'prompt' => "Write `poundAmounts(text)`, returning the numbers written after a `£` sign (whole or with two decimals) as numbers, without the sign being part of the match: use a lookbehind. Amounts in other currencies don't count.",
            'starter_code' => <<<'JS'
                function poundAmounts(text) {
                    return (text.match(/\d+(\.\d\d)?/g) ?? []).map(Number);
                }
                JS,
            'solution' => <<<'JS'
                function poundAmounts(text) {
                    return (text.match(/(?<=£)\d+(?:\.\d{2})?/g) ?? []).map(Number);
                }
                JS,
            'hint' => '`(?<=£)` before the number pattern; `(?:\.\d{2})?` for the optional pence.',
            'checks' => [
                ['label' => 'Pounds only', 'code' => "return expectSame([poundAmounts('Dune £9.99, Emma $7.50, 3 for £20'), poundAmounts('€5 only')], [[9.99, 20], []]);"],
                ['label' => 'Uses a lookbehind', 'code' => "return learnerCode().includes('(?<=') ? true : 'Use a lookbehind, (?<=...).';"],
            ],
        ],
        [
            'title' => 'Several rules at once',
            'prompt' => 'Write `isSecure(password)` as a single regex test: at least 12 characters, at least one digit, at least one character that is neither a letter nor a digit, and no spaces anywhere.',
            'starter_code' => <<<'JS'
                function isSecure(password) {
                    return password.length >= 12;
                }
                JS,
            'solution' => <<<'JS'
                function isSecure(password) {
                    return /^(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{12,}$/.test(password);
                }
                JS,
            'hint' => 'A lookahead for each rule: `(?=.*\d)`, `(?=.*[^A-Za-z0-9])`, and a negative one `(?!.*\s)`, then `.{12,}`.',
            'checks' => [
                ['label' => 'Secure', 'code' => "return expectSame(['correct-horse1', 'Battery!staple9', '1!aaaaaaaaaa'].map(isSecure), [true, true, true]);"],
                ['label' => 'Not secure', 'code' => "return expectSame(['short-1', 'no digits here!', 'nosymbols12345', 'has a space 1!', ''].map(isSecure), [false, false, false, false, false]);"],
                ['label' => 'One regex', 'code' => "return (learnerCode().match(/\\(\\?[=!]/g) ?? []).length >= 2 ? true : 'Use lookaheads in a single pattern.';"],
            ],
        ],
        [
            'title' => 'Groups of four',
            'prompt' => "Write `groupsOfFour(code)`, which removes any spaces in `code` and then puts a space after every four characters, but not at the end: `'GB82WEST12345698765432'` → `'GB82 WEST 1234 5698 7654 32'`.",
            'starter_code' => <<<'JS'
                function groupsOfFour(code) {
                    return code.replace(/(.{4})/g, '$1 ');
                }
                JS,
            'solution' => <<<'JS'
                function groupsOfFour(code) {
                    return code.replace(/\s/g, '').replace(/(.{4})(?!$)/g, '$1 ');
                }
                JS,
            'hint' => 'After four characters, add a space only if the end of the text doesn\'t follow: `(?!$)`.',
            'checks' => [
                ['label' => 'Groups', 'code' => "return expectSame([groupsOfFour('GB82WEST12345698765432'), groupsOfFour('12345678'), groupsOfFour('12 34 5')], ['GB82 WEST 1234 5698 7654 32', '1234 5678', '1234 5']);"],
            ],
        ],
        [
            'title' => 'Words in any language',
            'prompt' => "Write `uniqueWords(text)`, returning the distinct words of `text` in lower case, in order of first appearance. A word is a run of letters in any language, which may contain apostrophes (`'café'`, `'naïve'`, `\"don't\"`).",
            'starter_code' => <<<'JS'
                function uniqueWords(text) {
                    return [...new Set(text.toLowerCase().match(/\w+/g))];
                }
                JS,
            'solution' => <<<'JS'
                function uniqueWords(text) {
                    return [...new Set((text.toLowerCase().match(/\p{L}+(?:'\p{L}+)*/gu) ?? []))];
                }
                JS,
            'hint' => '`\p{L}+` with the `u` flag; an apostrophe can be allowed between letters with `(?:\'\p{L}+)*`.',
            'checks' => [
                ['label' => 'Any language', 'code' => "return expectSame(uniqueWords('Café, café crème — NAÏVE naïve!'), ['café', 'crème', 'naïve']);"],
                ['label' => 'Apostrophes', 'code' => "return expectSame([uniqueWords(\"Don't stop, don't! It's Zoë's.\"), uniqueWords('123 !!!')], [[\"don't\", 'stop', \"it's\", \"zoë's\"], []]);"],
            ],
        ],
        [
            'title' => 'Initials',
            'prompt' => "Write `initials(name)`, returning the upper-case letters that start each part of a name, in any language: `'Émile Zola'` → `'ÉZ'`, `'Ōe Kenzaburō'` → `'ŌK'`, `'jean-paul Sartre'` → `'S'` (only parts that start with an upper-case letter count).",
            'starter_code' => <<<'JS'
                function initials(name) {
                    return (name.match(/\b[A-Z]/g) ?? []).join('');
                }
                JS,
            'solution' => <<<'JS'
                function initials(name) {
                    return (name.match(/(?<!\p{L})\p{Lu}/gu) ?? []).join('');
                }
                JS,
            'hint' => '`\b` and `[A-Z]` only know English. Use `\p{Lu}` for an upper-case letter, and `(?<!\p{L})` for "not just after another letter".',
            'checks' => [
                ['label' => 'Initials', 'code' => "return expectSame(['Émile Zola', 'Ōe Kenzaburō', 'jean-paul Sartre', 'Ursula K. Le Guin'].map(initials), ['ÉZ', 'ŌK', 'S', 'UKLG']);"],
            ],
        ],
        [
            'title' => 'Standalone numbers',
            'prompt' => "Write `standaloneNumbers(text)`, returning the whole numbers that stand on their own: not part of a word like `A4` or `mp3`, and not part of a decimal like `2.5`. `'Buy 3 A4 pads for 12 pounds, v2.5'` → `[3, 12]`.",
            'starter_code' => <<<'JS'
                function standaloneNumbers(text) {
                    return (text.match(/\d+/g) ?? []).map(Number);
                }
                JS,
            'solution' => <<<'JS'
                function standaloneNumbers(text) {
                    return (text.match(/(?<![\w.])\d+(?![\w.]*\w)/g) ?? []).map(Number);
                }
                JS,
            'hint' => 'Refuse a letter, digit or full stop just before (`(?<![\w.])`), and a word character or a decimal part just after.',
            'checks' => [
                ['label' => 'Standalone only', 'code' => "return expectSame([standaloneNumbers('Buy 3 A4 pads for 12 pounds, v2.5'), standaloneNumbers('mp3 4x4 42 1.5 7.')], [[3, 12], [42, 7]]);"],
            ],
        ],
    ],
];
