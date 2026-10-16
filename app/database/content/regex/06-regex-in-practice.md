## Reading structured text

Regular expressions shine at pulling data out of text with a regular shape: settings files, log lines, simple CSV.
A pattern per line, anchored at both ends, either matches cleanly or tells you the line is bad:

```javascript
const lines = ['name = Amara', 'age=34', '# a comment', '', 'city = Leeds   ', 'broken line'];

const settings = {};
for (const line of lines) {
    const match = line.match(/^\s*(\w+)\s*=\s*(.*?)\s*$/);
    if (match) {
        settings[match[1]] = match[2];
    }
}

console.log(settings);
```

`(.*?)\s*$` takes the value but leaves trailing spaces out: the lazy group stops as soon as the rest can be matched by
`\s*$`.

## Building patterns from parts

Long patterns are hard to read. Building them from named pieces with `String.raw` (which leaves backslashes alone)
and `new RegExp` keeps each piece understandable and reusable:

```javascript
const year = String.raw`(?<year>\d{4})`;
const month = String.raw`(?<month>0[1-9]|1[0-2])`;
const day = String.raw`(?<day>0[1-9]|[12]\d|3[01])`;

const isoDate = new RegExp(`^${year}-${month}-${day}$`);

console.log(isoDate.test('2026-10-16'), isoDate.test('2026-13-01'));
console.log('2026-10-16'.match(isoDate).groups);
```

## Regex plus code

Some rules are easier in code than in a pattern: a regex can check that a date **looks** right, but a few lines of code
check that it **exists** (no 31st of February). Use each for what it's good at:

```javascript
function isRealDate(text) {
    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return false;
    const [year, month, day] = match.slice(1).map(Number);
    const date = new Date(Date.UTC(year, month - 1, day));
    return date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
}

console.log(isRealDate('2024-02-29'), isRealDate('2026-02-29'), isRealDate('2026-02-3'));
```

## Unicode-safe slugs

Accented letters can be split into a base letter and a separate accent mark with `normalize('NFD')`; `\p{M}` matches
those marks, so removing them leaves plain letters:

```javascript
const title = 'Crème Brûlée à la Maison!';

const plain = title.normalize('NFD').replace(/\p{M}/gu, '');
console.log(plain);
console.log(plain.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
```

## Testing a regex

A regex is code, and deserves tests: a table of inputs that should and shouldn't match, run together, catches the
surprises:

```javascript
const ukMobile = /^07\d{3} ?\d{6}$/;

const cases = [
    ['07700 900123', true],
    ['07700900123', true],
    ['0770 0900123', false],
    ['08700 900123', false],
    ['07700 9001234', false],
];

for (const [input, expected] of cases) {
    const actual = ukMobile.test(input);
    console.log(`${actual === expected ? '✓' : '✗'} ${input}`);
}
```

## When not to use a regex

Regular expressions can't properly parse nested structures: HTML, JSON, programming languages, balanced brackets of any
depth. Use a real parser for those: `JSON.parse()`, the DOM's `DOMParser`, or a library. And if a pattern needs a
paragraph of explanation, plain code that does the same in a few steps may be kinder to the next reader.

```javascript
const json = '{"title": "Dune", "tags": ["sci-fi", "classic"], "nested": {"a": {"b": 1}}}';

const data = JSON.parse(json);
console.log(data.tags.length, data.nested.a.b);
```

## Putting it together

Highlighting search terms in text: escape each term, join them into one alternation, longest first, and wrap matches
in a replacement function:

```javascript
function escapeRegex(text) {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function highlight(text, terms) {
    const alternatives = [...terms].sort((a, b) => b.length - a.length).map(escapeRegex);
    const pattern = new RegExp(`(${alternatives.join('|')})`, 'gi');
    return text.replace(pattern, '[$1]');
}

console.log(highlight('Learn C++ and C, then JavaScript and Java.', ['C++', 'Java', 'JavaScript']));
```

Next, try the quiz to check your understanding, then solve the challenges.
