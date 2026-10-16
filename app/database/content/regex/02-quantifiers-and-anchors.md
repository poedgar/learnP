## How many times

A **quantifier** after something says how many times it may repeat:

| Quantifier | Means |
|------------|-------|
| `?` | 0 or 1 times (optional) |
| `*` | 0 or more times |
| `+` | 1 or more times |
| `{3}` | exactly 3 times |
| `{2,4}` | 2 to 4 times |
| `{2,}` | 2 or more times |

```javascript
console.log('color colour'.match(/colou?r/g));
console.log('Order 7, 42 or 1999'.match(/\d+/g));
console.log('1 22 333 4444'.match(/\b\d{2,3}\b/g));
```

A quantifier applies to what comes just before it: one character, a character class, or a group in brackets (next
lesson). `/ab+/` means "a, then one or more b", not "ab repeated".

## Greedy and lazy

Quantifiers are **greedy**: they match as much as they can. Adding `?` after a quantifier makes it **lazy**: as little
as possible. The difference shows when a match could end in more than one place:

```javascript
const html = '<b>bold</b> and <i>italic</i>';

console.log(html.match(/<.+>/)[0]);
console.log(html.match(/<.+?>/)[0]);
console.log(html.match(/<.+?>/g));
```

The greedy `.+` runs to the last `>` in the text; the lazy `.+?` stops at the first. A negated class is often clearer
still: `/<[^>]+>/` can't run past a `>` at all.

## Anchors: where a match may be

**Anchors** match a position rather than a character. `^` is the start of the text and `$` the end, so together they
make a pattern match the **whole** string, which is what validation usually needs:

```javascript
console.log(/\d{4}/.test('in 2026 or so'), /^\d{4}$/.test('in 2026 or so'), /^\d{4}$/.test('2026'));
console.log(/^Dear /.test('Dear Amara,'), /\.$/.test('The end.'));
```

Forgetting the anchors is the most common regex mistake in validation: `/\d{4}/` accepts any text with four digits in
it somewhere.

## Word boundaries

`\b` matches a **word boundary**: between a word character and a non-word character (or the start or end). It finds
whole words:

```javascript
const text = 'the cat scattered the catalogue';

console.log(text.match(/cat/g).length);
console.log(text.match(/\bcat\b/g).length);
console.log(text.match(/\bcat\w*/g));
```

`\B` is the opposite: a position inside a word.

## Several lines

By default `^` and `$` mean the start and end of the whole text. The **`m`** (multiline) flag makes them match at the
start and end of each **line** too:

```javascript
const list = 'apples: 3\nbananas: 12\ncherries: 7';

console.log(list.match(/^\w+/g));
console.log(list.match(/^\w+/gm));
console.log(list.match(/\d+$/gm));
```

## The dot and line breaks

`.` doesn't match a line break, so a pattern with `.*` stops at the end of a line. The **`s`** flag lets `.` match line
breaks too:

```javascript
const note = 'Start\nmiddle\nEnd';

console.log(/Start.*End/.test(note), /Start.*End/s.test(note));
console.log(note.match(/Start[\s\S]*End/)[0].length);
```

`[\s\S]` (whitespace or not whitespace) is an older way to match absolutely any character.

## Putting it together

Validating a few kinds of input, each pattern anchored at both ends:

```javascript
const rules = {
    postcode: /^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/i,
    time: /^([01]?\d|2[0-3]):[0-5]\d$/,
    hashtag: /^#\w{2,30}$/,
};

const inputs = [['postcode', 'LS1 4AP'], ['postcode', 'LS14'], ['time', '9:05'], ['time', '24:00'], ['hashtag', '#books']];

for (const [rule, value] of inputs) {
    console.log(`${rule} ${value}: ${rules[rule].test(value) ? 'valid' : 'invalid'}`);
}
```

The time pattern uses `(…|…)`, alternatives in a group, which is next lesson.

Next, try the quiz to check your understanding, then solve the challenges.
