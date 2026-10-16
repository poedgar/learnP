<?php

// Lesson 6 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'regex-in-practice',
    'title' => 'Regular expressions in practice',
    'summary' => 'Use regular expressions on real tasks: read structured text, build patterns from parts, combine a regex with code, make Unicode-safe slugs, test patterns, and know when to reach for a parser instead.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). Add cases to the table.
        const pattern = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

        const cases = [['#fff', true], ['#FF0000', true], ['#ff00', false], ['fff', false]];

        for (const [input, expected] of cases) {
            console.log(pattern.test(input) === expected ? '✓' : '✗', input);
        }
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const m = 'key =  some value   '.match(/^(\w+)\s*=\s*(.*?)\s*$/);
                console.log(`[${m[2]}]`);
                JS,
            'options' => ['`[some value]`', '`[some value   ]`', '`[  some value   ]`', '`[s]`'],
            'correct_option' => 0,
            'explanation' => 'The lazy group stops as soon as `\s*$` can match the trailing spaces.',
        ],
        [
            'question' => 'Why use `String.raw` when building a pattern from strings?',
            'options' => [
                'It makes the regex faster',
                'It leaves backslashes alone, so `\d` doesn\'t need to be written `\\\\d`',
                'It escapes special characters',
                'It adds the `g` flag',
            ],
            'correct_option' => 1,
            'explanation' => '``String.raw`\d{4}` `` is the four characters `\d{4}`.',
        ],
        [
            'question' => 'Why check a date like `2026-02-30` with code as well as a regex?',
            'options' => [
                'Regexes can\'t match digits',
                'A regex can check the shape, but not that the date exists; that needs knowledge of month lengths and leap years',
                'Dates can\'t be matched with a regex',
                'It\'s faster',
            ],
            'correct_option' => 1,
            'explanation' => 'Use each tool for what it\'s good at.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('Ünïcödé'.normalize('NFD').replace(/\p{M}/gu, ''));
                JS,
            'options' => ['`Unicode`', '`Ünïcödé`', '`nicd`', '`U n i c o d e`'],
            'correct_option' => 0,
            'explanation' => 'NFD splits each accented letter into a base letter and a mark, which `\p{M}` then removes.',
        ],
        [
            'question' => 'Why can\'t a regex reliably parse HTML or JSON?',
            'options' => [
                'They\'re too long',
                'They can nest to any depth, and regular expressions can\'t keep track of arbitrarily deep nesting; use a real parser',
                'Regexes can\'t match angle brackets',
                'They can, always',
            ],
            'correct_option' => 1,
            'explanation' => '`JSON.parse()` and `DOMParser` are built for exactly this.',
        ],
        [
            'question' => 'Why sort search terms longest first before joining them with `|`?',
            'options' => [
                'It looks tidier',
                'Alternatives are tried in order, so "Java" before "JavaScript" would match the shorter word first and stop',
                'Regexes require it',
                'It removes duplicates',
            ],
            'correct_option' => 1,
            'explanation' => 'The first alternative that works wins.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const big = new RegExp(String.raw`^\d{3}-${'\\d{4}'}$`);
                console.log(big.test('123-4567'), big.source);
                JS,
            'options' => ['`true ^\d{3}-\d{4}$`', '`false ^\d{3}-\d{4}$`', '`true ^d{3}-d{4}$`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Both parts end up as `\d`: `String.raw` keeps its backslash, and `\'\\\\d{4}\'` is the string `\d{4}`.',
        ],
        [
            'question' => 'What is the best way to be confident a regex is right?',
            'options' => [
                'Read it carefully',
                'Test it against a table of inputs that should and shouldn\'t match, including the awkward edges',
                'Make it as short as possible',
                'Add more flags',
            ],
            'correct_option' => 1,
            'explanation' => 'A regex is code, and deserves tests like any other.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A CSV line with quotes',
            'prompt' => "Write `parseCsvLine(line)`, splitting a CSV line into fields. A field may be wrapped in double quotes, and then may contain commas; the quotes aren't part of the value. `'Dune,\"Herbert, Frank\",1965'` → `['Dune', 'Herbert, Frank', '1965']`.",
            'starter_code' => <<<'JS'
                function parseCsvLine(line) {
                    return line.split(',');
                }
                JS,
            'solution' => <<<'JS'
                function parseCsvLine(line) {
                    return [...line.matchAll(/(?:^|,)(?:"([^"]*)"|([^,]*))/g)].map((match) => match[1] ?? match[2]);
                }
                JS,
            'hint' => 'Each field starts at the start of the line or after a comma: `(?:^|,)`, then either `"([^"]*)"` or `([^,]*)`. Take whichever group matched.',
            'checks' => [
                ['label' => 'Quoted commas', 'code' => "return expectSame(parseCsvLine('Dune,\"Herbert, Frank\",1965'), ['Dune', 'Herbert, Frank', '1965']);"],
                ['label' => 'Empty fields', 'code' => "return expectSame([parseCsvLine('a,,c'), parseCsvLine('\"\",x'), parseCsvLine('one')], [['a', '', 'c'], ['', 'x'], ['one']]);"],
            ],
        ],
        [
            'title' => 'Compare version numbers',
            'prompt' => "Write `compareVersions(a, b)` for versions like `'1.10.2'` or `'2.0.0-beta'`, returning `-1`, `0` or `1`. Compare major, minor and patch as numbers; a version with a `-label` comes before the same version without one (labels compare alphabetically). Throw a `TypeError` for anything not in this form.",
            'starter_code' => <<<'JS'
                function compareVersions(a, b) {
                    return a < b ? -1 : a > b ? 1 : 0;
                }
                JS,
            'solution' => <<<'JS'
                function parseVersion(text) {
                    const match = text.match(/^(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z.]+))?$/);
                    if (!match) {
                        throw new TypeError(`Not a version: ${text}`);
                    }
                    return { parts: match.slice(1, 4).map(Number), label: match[4] ?? null };
                }

                function compareVersions(a, b) {
                    const x = parseVersion(a);
                    const y = parseVersion(b);
                    for (let i = 0; i < 3; i++) {
                        if (x.parts[i] !== y.parts[i]) {
                            return x.parts[i] < y.parts[i] ? -1 : 1;
                        }
                    }
                    if (x.label === y.label) return 0;
                    if (x.label === null) return 1;
                    if (y.label === null) return -1;
                    return x.label < y.label ? -1 : 1;
                }
                JS,
            'hint' => 'Parse each version with `/^(\d+)\.(\d+)\.(\d+)(?:-(…))?$/`, compare the three numbers, then the labels (no label wins).',
            'checks' => [
                ['label' => 'Numbers, not text', 'code' => "return expectSame([compareVersions('1.10.0', '1.9.3'), compareVersions('1.2.3', '1.2.3'), compareVersions('0.9.9', '1.0.0')], [1, 0, -1]);"],
                ['label' => 'Pre-release labels', 'code' => "return expectSame([compareVersions('2.0.0-beta', '2.0.0'), compareVersions('2.0.0', '2.0.0-rc'), compareVersions('2.0.0-alpha', '2.0.0-beta')], [-1, 1, -1]);"],
                ['label' => 'Bad versions', 'code' => "return expectThrows(() => compareVersions('1.2', '1.2.0'), TypeError);"],
            ],
        ],
        [
            'title' => 'Mentions, not emails',
            'prompt' => 'Write `mentions(text)`, returning the distinct user names mentioned with `@name` (letters, digits and underscores), lower-cased, in order. An `@` inside an email address, such as `amara@example.com`, is not a mention.',
            'starter_code' => <<<'JS'
                function mentions(text) {
                    return (text.match(/@\w+/g) ?? []).map((m) => m.slice(1));
                }
                JS,
            'solution' => <<<'JS'
                function mentions(text) {
                    const names = [...text.matchAll(/(?<!\w)@(\w+)/g)].map((match) => match[1].toLowerCase());
                    return [...new Set(names)];
                }
                JS,
            'hint' => 'A lookbehind refuses an `@` straight after a word character: `(?<!\w)@(\w+)`. A `Set` removes repeats.',
            'checks' => [
                ['label' => 'Finds mentions', 'code' => "return expectSame(mentions('Thanks @Amara and @ben_42! Ask @amara.'), ['amara', 'ben_42']);"],
                ['label' => 'Not emails', 'code' => "return expectSame([mentions('Write to amara@example.com or @ben'), mentions('no one')], [['ben'], []]);"],
            ],
        ],
        [
            'title' => 'Explain what is wrong',
            'prompt' => "Write `emailProblem(text)`, returning `null` for a plausible email address, or a message about the first problem found, checked in this order: `'No spaces allowed'`, `'Needs exactly one @'`, `'Nothing before the @'`, `'The domain needs a dot'` (the part after `@` must contain a full stop with something on both sides).",
            'starter_code' => <<<'JS'
                function emailProblem(text) {
                    return /^\S+@\S+\.\S+$/.test(text) ? null : 'Not an email address';
                }
                JS,
            'solution' => <<<'JS'
                function emailProblem(text) {
                    if (/\s/.test(text)) return 'No spaces allowed';
                    if ((text.match(/@/g) ?? []).length !== 1) return 'Needs exactly one @';
                    const [local, domain] = text.split('@');
                    if (local === '') return 'Nothing before the @';
                    if (!/^[^.]+(\.[^.]+)+$/.test(domain)) return 'The domain needs a dot';
                    return null;
                }
                JS,
            'hint' => 'One small check per rule, in order: `\s`, the number of `@`, the local part, and `/^[^.]+(\.[^.]+)+$/` for the domain.',
            'checks' => [
                ['label' => 'Plausible addresses', 'code' => "return expectSame(['amara@example.com', 'b.ito@mail.co.uk'].map(emailProblem), [null, null]);"],
                ['label' => 'The first problem', 'code' => "return expectSame(['a b@x.com', 'amara.example.com', 'a@b@c.com', '@example.com', 'amara@localhost', 'amara@example.', 'amara@.com'].map(emailProblem), ['No spaces allowed', 'Needs exactly one @', 'Needs exactly one @', 'Nothing before the @', 'The domain needs a dot', 'The domain needs a dot', 'The domain needs a dot']);"],
            ],
        ],
        [
            'title' => 'Tidy phone numbers',
            'prompt' => "Write `ukMobile(text)`, turning UK mobile numbers written in different ways (`'07700 900123'`, `'07700-900-123'`, `'(07700) 900123'`, `'+44 7700 900123'`) into `'07700900123'`. Return `null` if, after removing spaces, hyphens and brackets and turning `+44` into `0`, it isn't `07` followed by nine digits.",
            'starter_code' => <<<'JS'
                function ukMobile(text) {
                    return text.replace(' ', '');
                }
                JS,
            'solution' => <<<'JS'
                function ukMobile(text) {
                    const digits = text.replace(/[\s()-]/g, '').replace(/^\+44/, '0');
                    return /^07\d{9}$/.test(digits) ? digits : null;
                }
                JS,
            'hint' => 'Remove `[\s()-]` everywhere, swap a leading `\+44` for `0`, then check `/^07\d{9}$/`.',
            'checks' => [
                ['label' => 'Different ways of writing it', 'code' => "return expectSame(['07700 900123', '07700-900-123', '(07700) 900123', '+44 7700 900123', '+447700900123'].map(ukMobile), Array(5).fill('07700900123'));"],
                ['label' => 'Not mobile numbers', 'code' => "return expectSame(['0113 496 0000', '07700 90012', '07700 9001234', '+1 7700 900123', 'call me'].map(ukMobile), [null, null, null, null, null]);"],
            ],
        ],
        [
            'title' => 'Postcodes, tidied',
            'prompt' => "Write `formatPostcode(text)`, accepting UK postcodes typed loosely (any case, any spaces: `'ls14ap'`, `' sw1A 1aa '`) and returning them in standard form, upper case with one space before the last three characters (`'LS1 4AP'`, `'SW1A 1AA'`), or `null` if it isn't a postcode. Build the pattern from named parts with `String.raw`.",
            'starter_code' => <<<'JS'
                function formatPostcode(text) {
                    return text.toUpperCase();
                }
                JS,
            'solution' => <<<'JS'
                const outward = String.raw`(?<outward>[A-Z]{1,2}\d[A-Z\d]?)`;
                const inward = String.raw`(?<inward>\d[A-Z]{2})`;
                const postcode = new RegExp(`^${outward}${inward}$`);

                function formatPostcode(text) {
                    const match = text.replace(/\s/g, '').toUpperCase().match(postcode);
                    return match ? `${match.groups.outward} ${match.groups.inward}` : null;
                }
                JS,
            'hint' => 'Remove all spaces and upper-case first. The last part is always a digit and two letters; the first part is one or two letters, a digit, and maybe one more letter or digit.',
            'checks' => [
                ['label' => 'Tidies postcodes', 'code' => "return expectSame(['ls14ap', ' sw1A 1aa ', 'M1 1AE', 'b33 8th', 'EC1A1BB'].map(formatPostcode), ['LS1 4AP', 'SW1A 1AA', 'M1 1AE', 'B33 8TH', 'EC1A 1BB']);"],
                ['label' => 'Not postcodes', 'code' => "return expectSame(['LS1', '123 456', 'ABC 1DE', ''].map(formatPostcode), [null, null, null, null]);"],
                ['label' => 'Built with String.raw', 'code' => "return learnerCode().includes('String.raw') ? true : 'Build the pattern from parts with String.raw.';"],
            ],
        ],
    ],
];
