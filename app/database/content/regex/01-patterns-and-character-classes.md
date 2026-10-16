## What a regular expression is

A **regular expression** (regex) is a small pattern that describes text: "three digits", "a word starting with a
capital letter", "anything between brackets". JavaScript writes them between slashes. `test()` asks whether a string
contains a match:

```javascript
const pattern = /cat/;

console.log(pattern.test('concatenate'), pattern.test('dog'));
console.log('The cat sat'.search(/cat/));
```

Most characters match themselves, so `/cat/` finds the letters c, a, t in a row, anywhere in the string. `search()`
gives the position of the first match, or `-1`.

## Getting the match

`match()` gives details of the first match: the matched text and its `index`. With the **`g`** (global) flag it gives
every match instead, as an array of strings:

```javascript
const text = 'Dune, then Dune Messiah, then Children of Dune';

const first = text.match(/Dune/);
console.log(first[0], first.index);

console.log(text.match(/Dune/g));
console.log('no match here'.match(/Dune/));
```

With no match, `match()` gives `null`, so check before using the result.

## Flags

Flags go after the closing slash and change how the pattern works:

- `i`: ignore case;
- `g`: find every match, not just the first;
- `m`, `s`, `u` and `y` come in later lessons.

```javascript
console.log(/dune/.test('DUNE'), /dune/i.test('DUNE'));
console.log('Ba ba BA'.match(/ba/gi));
```

## Special characters

Some characters have special meanings: `. * + ? ^ $ { } ( ) [ ] | \ /`. To match one literally, put a backslash before
it, called **escaping**:

```javascript
console.log(/1+1/.test('1+1'), /1\+1/.test('1+1'));
console.log(/\$9\.99/.test('Only $9.99!'));
console.log(/a.c/.test('abc'), /a\.c/.test('abc'));
```

`.` on its own matches any single character except a line break, so `/a.c/` matches "abc", "a-c", "a c" and so on.

## Character classes

Square brackets match **one** character from a set. A hyphen gives a range, and a `^` straight after the opening
bracket negates the set:

```javascript
console.log('grey gray'.match(/gr[ae]y/g));
console.log('A1 b2 C3'.match(/[A-Z][0-9]/g));
console.log('cat, cot, cut, cxt'.match(/c[^aeiou]t/g));
```

Inside brackets, most special characters lose their meaning: `[.?]` matches a full stop or a question mark.

## Shorthand classes

Common sets have shorthands. Upper-case versions mean the opposite:

| Class | Matches | Opposite |
|-------|---------|----------|
| `\d` | a digit, `[0-9]` | `\D` |
| `\w` | a "word" character, `[A-Za-z0-9_]` | `\W` |
| `\s` | whitespace: space, tab, line break | `\S` |

```javascript
console.log('Room 101, floor 3'.match(/\d/g));
console.log('a_b c-d'.match(/\w/g).join(''));
console.log('one two\tthree'.split(/\s/));
```

`\w` covers only English letters; accented letters need the Unicode features in lesson 5.

## Building a regex from text

`new RegExp(text, flags)` builds a pattern from a string, for example from user input. Any special characters in the
text must be escaped first, or they're treated as pattern syntax, which can change what matches or even fail:

```javascript
const search = 'C++';

try {
    new RegExp(search);
} catch (error) {
    console.log(`${error.name}: the + signs are read as pattern syntax`);
}

const escaped = new RegExp('C\\+\\+', 'i');
console.log(escaped.source, escaped.test('Learn c++ today'));
```

In a string, a backslash itself must be written `\\`, so `new RegExp('\\d')` is `/\d/`. Writing a function that escapes
any text is one of the challenges.

## Putting it together

Checking a few things about a product code with small patterns:

```javascript
const codes = ['AB-1234', 'ab-1234', 'AB1234', 'AB-12x4'];

for (const code of codes) {
    const hasPrefix = /[A-Z][A-Z]-/.test(code);
    const digits = (code.match(/\d/g) ?? []).length;
    const odd = /[^A-Z0-9-]/i.test(code);
    console.log(`${code}: prefix ${hasPrefix}, ${digits} digits${odd ? ', has an odd character' : ''}`);
}
```

Next, try the quiz to check your understanding, then solve the challenges.
