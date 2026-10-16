## One thing or another

`|` means "or": `cat|dog` matches either word. It has the lowest priority of anything in a pattern, so it splits the
whole pattern unless it's inside brackets:

```javascript
console.log('I have a cat and a dog'.match(/cat|dog/g));
console.log(/^yes|no$/.test('yes please'), /^(yes|no)$/.test('yes please'));
```

`^yes|no$` means "starts with yes, **or** ends with no", which is rarely what's meant.

## Groups

Round brackets **group** part of a pattern, so a quantifier or `|` applies to all of it:

```javascript
console.log('hahaha!'.match(/(ha)+/)[0]);
console.log(/^(Mr|Mrs|Ms|Dr) [A-Z]\w+$/.test('Dr Okafor'));
console.log('2026-10-16'.match(/\d{4}(-\d{2}){2}/)[0]);
```

## Capturing groups

Groups also **capture** what they matched. Without the `g` flag, `match()` (and `exec()`) give the captures after the
whole match, numbered by their opening bracket from the left:

```javascript
const match = 'Due on 2026-10-16'.match(/(\d{4})-(\d{2})-(\d{2})/);

console.log(match[0]);
console.log(match[1], match[2], match[3]);

const [, year, month, day] = match;
console.log(`${day}/${month}/${year}`);
```

A group that didn't take part in the match gives `undefined`.

## Named groups

`(?<name>...)` gives a group a name, and the captures appear on `match.groups`. Names make patterns much easier to
read and to change:

```javascript
const pattern = /(?<hours>\d{1,2}):(?<minutes>\d{2})/;
const { hours, minutes } = 'Doors open at 7:30pm'.match(pattern).groups;

console.log(Number(hours) * 60 + Number(minutes));
```

## Non-capturing groups

`(?:...)` groups without capturing, when you only need the brackets for `|` or a quantifier. It keeps the numbering of
the other groups simple:

```javascript
const match = 'https://example.org/books'.match(/^(?:https?):\/\/([^/]+)(\/.*)?$/);

console.log(match[1], match[2]);
```

## Backreferences

Inside a pattern, `\1` matches the same text as group 1 matched, and `\k<name>` the same as a named group. That finds
repeats:

```javascript
console.log('this is is a test'.match(/\b(\w+) \1\b/)[0]);
console.log(/^(?<quote>['"]).*\k<quote>$/.test(`"balanced"`), /^(?<quote>['"]).*\k<quote>$/.test(`"mixed'`));
```

## Groups in alternation

Alternatives are tried left to right, and the first that works wins, even if a later one would match more. Put longer
alternatives first when one is a prefix of another:

```javascript
console.log('JavaScript'.match(/Java|JavaScript/)[0]);
console.log('JavaScript'.match(/JavaScript|Java/)[0]);
```

## Putting it together

Reading log lines with named groups, and ignoring lines that don't fit:

```javascript
const lines = [
    '2026-10-16 09:14:02 INFO  started',
    '2026-10-16 09:14:05 ERROR disk full (sda1)',
    'garbled line',
    '2026-10-16 09:15:00 WARN  slow response',
];

const pattern = /^(?<date>\d{4}-\d{2}-\d{2}) (?<time>[\d:]{8}) (?<level>INFO|WARN|ERROR) +(?<message>.+)$/;

for (const line of lines) {
    const match = line.match(pattern);
    if (match) {
        const { time, level, message } = match.groups;
        console.log(`[${level}] ${time.slice(0, 5)} ${message}`);
    }
}
```

Next, try the quiz to check your understanding, then solve the challenges.
