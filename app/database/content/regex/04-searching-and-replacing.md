## Every match, with its groups

`match()` with the `g` flag gives only the matched strings, losing the groups. `matchAll()` gives an iterator of full
match objects, each with its groups and `index`. It needs the `g` flag:

```javascript
const text = 'Dune (1965), Emma (1815), Circe (2018)';

for (const match of text.matchAll(/(?<title>\w+) \((?<year>\d{4})\)/g)) {
    console.log(match.groups.title, match.groups.year, 'at', match.index);
}

const years = [...text.matchAll(/\((\d{4})\)/g)].map((m) => Number(m[1]));
console.log(years);
```

## Replacing

`replace(pattern, replacement)` replaces the first match, or every match with the `g` flag. `replaceAll()` does the
same but insists on `g`. In the replacement string, `$1`, `$2` and `$<name>` insert groups, and `$&` the whole match:

```javascript
console.log('2026-10-16'.replace(/(\d+)-(\d+)-(\d+)/, '$3/$2/$1'));
console.log('Amara Okafor'.replace(/(?<first>\w+) (?<last>\w+)/, '$<last>, $<first>'));
console.log('cat bat rat'.replace(/[cbr]at/g, '[$&]'));
console.log('a-b-c'.replaceAll('-', ' + '));
```

`$$` inserts a literal dollar sign.

## Replacing with a function

A function as the replacement is called for each match with the match, then each group, then more details. Whatever
it returns is put in. That allows any transformation:

```javascript
const prices = 'Dune £9.99, Emma £7.99';

const doubled = prices.replace(/£(\d+\.\d{2})/g, (whole, amount) => `£${(Number(amount) * 2).toFixed(2)}`);
console.log(doubled);

const titleCase = 'the left hand of darkness'.replace(/\b\w/g, (letter) => letter.toUpperCase());
console.log(titleCase);
```

## Splitting

`split()` takes a regex too, which handles messy separators. Capturing groups in the separator are kept in the result:

```javascript
console.log('a, b;c ;  d'.split(/\s*[,;]\s*/));
console.log('1+2-3'.split(/([+-])/));
console.log('one  two\tthree'.split(/\s+/));
```

## `exec()`, `lastIndex` and the sticky flag

A regex with `g` remembers where it got to in `lastIndex`. `exec()` finds the next match from there, which is how
matching worked before `matchAll()`:

```javascript
const pattern = /\d+/g;
const text = 'a1 b22 c333';
let match;

while ((match = pattern.exec(text)) !== null) {
    console.log(match[0], 'ends at', pattern.lastIndex);
}
```

That memory catches people out: calling `test()` again with the same `g` regex continues from `lastIndex`:

```javascript
const hasDigit = /\d/g;
console.log(hasDigit.test('a1'), hasDigit.test('a1'), hasDigit.test('a1'));
```

Leave `g` off a regex you only `test()` with. The **`y`** (sticky) flag matches only exactly at `lastIndex`, which
suits tokenizers that read text piece by piece.

## Counting and finding positions

`search()` gives the index of the first match, and `matchAll()` gives every position:

```javascript
const text = 'needle in a haystack with another needle';

console.log(text.search(/needle/), [...text.matchAll(/needle/g)].map((m) => m.index));
```

## Putting it together

A tiny template system: `{{name}}` placeholders replaced from an object, leaving unknown ones visible:

```javascript
function render(template, values) {
    return template.replace(/\{\{\s*(\w+)\s*\}\}/g, (placeholder, key) => (key in values ? String(values[key]) : placeholder));
}

console.log(render('Hello {{ name }}, you have {{count}} new {{things}}.', { name: 'Amara', count: 3 }));
```

Next, try the quiz to check your understanding, then solve the challenges.
