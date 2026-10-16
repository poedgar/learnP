<?php

// Lesson 3 of the Regular expressions course. The theory is in the .md file with the same name.
// The course uses JavaScript's regular expressions. Each challenge's checks run after the learner's
// code, in the same scope; a check returns true or a message. Helpers: expectSame(), learnerCode().

return [
    'slug' => 'groups-and-alternation',
    'title' => 'Groups and alternation',
    'summary' => 'Match one thing or another with |, group parts of a pattern, capture what they matched by number or by name, and refer back to it with backreferences.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const pattern = /(?<day>\d{1,2}) (?<month>Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (?<year>\d{4})/;

        const match = 'Published on 16 Oct 2026, in London.'.match(pattern);
        console.log(match[0]);
        console.log(match.groups);
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(/^cat|dog$/.test('catalogue'), /^(cat|dog)$/.test('catalogue'));
                JS,
            'options' => ['`true false`', '`false false`', '`true true`', '`false true`'],
            'correct_option' => 0,
            'explanation' => '`^cat|dog$` means "starts with cat, or ends with dog". The brackets make the anchors apply to both.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const m = 'Amara Okafor'.match(/(\w+) (\w+)/);
                console.log(m[2], m[1], m.length);
                JS,
            'options' => ['`Okafor Amara 3`', '`Amara Okafor 3`', '`Okafor Amara 2`', '`Amara Okafor 2`'],
            'correct_option' => 0,
            'explanation' => 'Index 0 is the whole match, then one entry per capturing group.',
        ],
        [
            'question' => 'What does `(?:ab)+` do differently from `(ab)+`?',
            'options' => [
                'It matches differently',
                'It matches the same text but doesn\'t capture, so it doesn\'t add an entry to the results',
                'It\'s optional',
                'It matches "ab" exactly once',
            ],
            'correct_option' => 1,
            'explanation' => 'Use non-capturing groups when you only need the brackets.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const { h, m } = 'at 9:45'.match(/(?<h>\d+):(?<m>\d+)/).groups;
                console.log(Number(h) + Number(m));
                JS,
            'options' => ['`54`', '`945`', '`9:45`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Named groups appear on `.groups`; the captures are strings, converted with `Number()`.',
        ],
        [
            'question' => 'What does `\1` in `/(\w)\1/` match?',
            'options' => [
                'The digit 1',
                'The same text that group 1 matched, so the pattern finds a doubled character',
                'Any one character',
                'The start of a group',
            ],
            'correct_option' => 1,
            'explanation' => 'It matches "ll" in "hello" and "oo" in "book".',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('Javascript'.match(/Java|Javascript/)[0]);
                JS,
            'options' => ['`Java`', '`Javascript`', '`null`', 'An error'],
            'correct_option' => 0,
            'explanation' => 'Alternatives are tried left to right, and the first that works wins.',
        ],
        [
            'question' => 'What is the value of an optional group that didn\'t take part in the match?',
            'options' => ['`\'\'`', '`undefined`', '`null`', 'The previous match'],
            'correct_option' => 1,
            'explanation' => '`\'a\'.match(/a(b)?/)[1]` is `undefined`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('2026-10-16'.match(/(\d+)-(\d+)-(\d+)/).slice(1).reverse().join('/'));
                JS,
            'options' => ['`16/10/2026`', '`2026/10/16`', '`2026-10-16`', '`6102/01/61`'],
            'correct_option' => 0,
            'explanation' => '`slice(1)` drops the whole match, leaving the three captures, which are then reversed.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'One of these words',
            'prompt' => 'Write `isWeekday(text)`, true only when `text` is exactly one of `Mon`, `Tue`, `Wed`, `Thu` or `Fri`, in any case.',
            'starter_code' => <<<'JS'
                function isWeekday(text) {
                    return /^Mon|Tue|Wed|Thu|Fri$/.test(text);
                }
                JS,
            'solution' => <<<'JS'
                function isWeekday(text) {
                    return /^(Mon|Tue|Wed|Thu|Fri)$/i.test(text);
                }
                JS,
            'hint' => 'Group the alternatives so the anchors apply to all of them, and add the `i` flag.',
            'checks' => [
                ['label' => 'Weekdays', 'code' => "return expectSame(['Mon', 'tue', 'WED', 'Thu', 'fri'].map(isWeekday), [true, true, true, true, true]);"],
                ['label' => 'Not weekdays', 'code' => "return expectSame(['Sat', 'Monday', 'xFri', 'Tue Wed', ''].map(isWeekday), [false, false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'Swap the date round',
            'prompt' => "Write `toUkDate(iso)`, turning a date like `'2026-10-16'` into `'16/10/2026'`, using capturing groups. Return `null` if `iso` isn't in that form.",
            'starter_code' => <<<'JS'
                function toUkDate(iso) {
                    return iso.split('-').join('/');
                }
                JS,
            'solution' => <<<'JS'
                function toUkDate(iso) {
                    const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (!match) {
                        return null;
                    }
                    const [, year, month, day] = match;
                    return `${day}/${month}/${year}`;
                }
                JS,
            'hint' => 'Match `^(\d{4})-(\d{2})-(\d{2})$`, then destructure the groups: `const [, year, month, day] = match`.',
            'checks' => [
                ['label' => 'Converts dates', 'code' => "return expectSame([toUkDate('2026-10-16'), toUkDate('1999-01-02')], ['16/10/2026', '02/01/1999']);"],
                ['label' => 'Rejects other text', 'code' => "return expectSame([toUkDate('16/10/2026'), toUkDate('2026-1-16'), toUkDate('x2026-10-16')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Named parts of a time',
            'prompt' => "Write `minutesSinceMidnight(text)` for times like `'7:05'`, `'19:45'` or `'7:05pm'`. Use named groups `hours`, `minutes` and an optional `period` (`am` or `pm`). `12am` is midnight and `12pm` noon. Return `null` for anything else.",
            'starter_code' => <<<'JS'
                function minutesSinceMidnight(text) {
                    const [hours, minutes] = text.split(':');
                    return Number(hours) * 60 + Number(minutes);
                }
                JS,
            'solution' => <<<'JS'
                function minutesSinceMidnight(text) {
                    const match = text.match(/^(?<hours>\d{1,2}):(?<minutes>[0-5]\d)(?<period>am|pm)?$/);
                    if (!match) {
                        return null;
                    }
                    let hours = Number(match.groups.hours);
                    const { period } = match.groups;
                    if (period) {
                        hours = (hours % 12) + (period === 'pm' ? 12 : 0);
                    }
                    return hours * 60 + Number(match.groups.minutes);
                }
                JS,
            'hint' => '`(?<period>am|pm)?` is an optional named group; when it\'s missing, `groups.period` is `undefined`. With a period, `hours % 12`, plus 12 for pm.',
            'checks' => [
                ['label' => '24-hour times', 'code' => "return expectSame([minutesSinceMidnight('7:05'), minutesSinceMidnight('19:45'), minutesSinceMidnight('0:00')], [425, 1185, 0]);"],
                ['label' => 'am and pm', 'code' => "return expectSame([minutesSinceMidnight('7:05pm'), minutesSinceMidnight('12:30am'), minutesSinceMidnight('12:30pm')], [1145, 30, 750]);"],
                ['label' => 'Nonsense', 'code' => "return expectSame([minutesSinceMidnight('7.05'), minutesSinceMidnight('7:5'), minutesSinceMidnight('7:05xm')], [null, null, null]);"],
                ['label' => 'Uses named groups', 'code' => "return /\\(\\?<hours>/.test(learnerCode()) && /\\(\\?<minutes>/.test(learnerCode()) ? true : 'Use named groups hours and minutes.';"],
            ],
        ],
        [
            'title' => 'Doubled words',
            'prompt' => "Write `doubledWords(text)`, returning every word that's immediately repeated (`'the the'`), ignoring case, as lower-case strings in order: `'It is is a test test.'` → `['is', 'test']`.",
            'starter_code' => <<<'JS'
                function doubledWords(text) {
                    return [];
                }
                JS,
            'solution' => <<<'JS'
                function doubledWords(text) {
                    return [...text.matchAll(/\b(\w+) \1\b/gi)].map((match) => match[1].toLowerCase());
                }
                JS,
            'hint' => 'A backreference: `/\b(\w+) \1\b/gi`. `matchAll()` gives each match with its groups.',
            'checks' => [
                ['label' => 'Finds repeats', 'code' => "return expectSame([doubledWords('It is is a test test.'), doubledWords('The the end'), doubledWords('no repeats here')], [['is', 'test'], ['the'], []]);"],
                ['label' => 'Whole words only', 'code' => "return expectSame(doubledWords('this is isn\\'t it'), []);"],
            ],
        ],
        [
            'title' => 'Split a URL',
            'prompt' => "Write `parseUrl(url)`, returning `{ protocol, host, path }` for URLs like `'https://example.org/books/1'` (`protocol` is `'http'` or `'https'`, `path` is `'/'` when missing), or `null` if `url` doesn't look like one.",
            'starter_code' => <<<'JS'
                function parseUrl(url) {
                    const [protocol, rest] = url.split('://');
                    return { protocol, host: rest, path: '/' };
                }
                JS,
            'solution' => <<<'JS'
                function parseUrl(url) {
                    const match = url.match(/^(?<protocol>https?):\/\/(?<host>[^/]+)(?<path>\/.*)?$/);
                    if (!match) {
                        return null;
                    }
                    const { protocol, host, path = '/' } = match.groups;
                    return { protocol, host, path };
                }
                JS,
            'hint' => 'Named groups for each part; the path group is optional, and destructuring can default it: `path = \'/\'`.',
            'checks' => [
                ['label' => 'Parses URLs', 'code' => "return expectSame([parseUrl('https://example.org/books/1'), parseUrl('http://localhost:8000')], [{ protocol: 'https', host: 'example.org', path: '/books/1' }, { protocol: 'http', host: 'localhost:8000', path: '/' }]);"],
                ['label' => 'Not URLs', 'code' => "return expectSame([parseUrl('ftp://example.org'), parseUrl('example.org/books'), parseUrl('https://')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Matching quotes',
            'prompt' => "Write `quoted(text)`, returning the contents of every quoted phrase in `text`, where a phrase starts with `'` or `\"` and ends with the **same** kind of quote: in `` `She said \"it's fine\" and 'ok'` ``, the phrases are `it's fine` and `ok`.",
            'starter_code' => <<<'JS'
                function quoted(text) {
                    return [...text.matchAll(/['"](.*?)['"]/g)].map((m) => m[1]);
                }
                JS,
            'solution' => <<<'JS'
                function quoted(text) {
                    return [...text.matchAll(/(['"])(.*?)\1/g)].map((m) => m[2]);
                }
                JS,
            'hint' => 'Capture the opening quote, `([\'"])`, and end with `\1` so only the same kind closes it.',
            'checks' => [
                ['label' => 'The same kind closes', 'code' => "return expectSame(quoted(`She said \"it's fine\" and 'ok'`), [\"it's fine\", 'ok']);"],
                ['label' => 'Several phrases', 'code' => "return expectSame([quoted(`\"a\" \"b\" 'c'`), quoted('no quotes')], [['a', 'b', 'c'], []]);"],
            ],
        ],
    ],
];
