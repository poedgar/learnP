## Looking without matching

A **lookaround** checks what comes before or after a position without including it in the match:

| Lookaround | Means |
|------------|-------|
| `(?=...)` | followed by ... (lookahead) |
| `(?!...)` | not followed by ... |
| `(?<=...)` | preceded by ... (lookbehind) |
| `(?<!...)` | not preceded by ... |

```javascript
const text = 'Dune £9.99, Emma $7.50, Circe £12.00';

console.log(text.match(/(?<=£)\d+\.\d{2}/g));
console.log(text.match(/\d+(?=\.\d{2})/g));
console.log('cats catalogue cat'.match(/cat(?!\w)/g).length);
```

The `£` isn't part of the first matches: the lookbehind only checks it's there.

## Several conditions at once

Lookaheads at the start of a pattern, all anchored with `^`, can check several independent rules about the same text,
which is hard to do any other way:

```javascript
const strong = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{10,}$/;

for (const password of ['Correct1Horse', 'correcthorse1', 'Short1A']) {
    console.log(password, strong.test(password));
}
```

Each `(?=.*X)` means "somewhere ahead there's an X"; none of them consume anything, so `.{10,}` still checks the whole
length.

## Inserting at positions

Because lookarounds match positions, `replace()` can insert text at places described by what surrounds them, such as
thousands separators:

```javascript
function withCommas(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

console.log(withCommas(1234567), withCommas(999), withCommas(1000));
```

The pattern finds positions inside the number (`\B`) followed by groups of exactly three digits up to the end.

## Unicode and the `u` flag

Without the **`u`** flag, a regex sees text as UTF-16 code units, so characters outside the basic range, such as most
emoji, count as two. With `u`, it works on whole characters, and `\p{...}` matches Unicode properties:

```javascript
console.log('😀'.match(/./g).length, '😀'.match(/./gu).length);

const words = 'café naïve Zoë résumé'.match(/\p{L}+/gu);
console.log(words);
console.log('Émile'.match(/\w+/)[0], 'Émile'.match(/\p{L}+/u)[0]);
```

`\p{L}` is any letter in any language; `\p{Lu}` an upper-case letter, `\p{N}` a number, `\p{Emoji}` an emoji. `\w`
only knows English letters, so `\p{L}` is the right tool for names and words in other languages.

## Case-insensitive matching across languages

With `u` and `i` together, case-insensitive matching follows Unicode's rules:

```javascript
console.log(/straße/iu.test('STRASSE'), /ÉCOLE/iu.test('école'));
```

"ß" isn't the same character as "SS", so the first is false; accented letters do match their upper-case forms.

## When regexes go wrong

Some patterns take a very long time on input that **almost** matches, because the engine tries an enormous number of
ways to split the text between nested quantifiers. This is called **catastrophic backtracking**:

```
/^(a+)+$/.test('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa!')   // can take minutes
```

Avoid nesting quantifiers over the same characters (`(a+)+`, `(\w*)*`), and make alternatives distinct. When a
pattern gets that complicated, a few simple steps of ordinary code are often clearer anyway. (That example isn't
runnable here on purpose: it would freeze the page.)

## Putting it together

Extracting hashtags written in any language, but not the `#` in a URL or a colour code:

```javascript
const post = 'Reading #Circe and #Ōkami 📚 see https://example.org/#top, colour #ff0000';

const tags = [...post.matchAll(/(?<![\w/])#(?![0-9a-f]{6}\b)(\p{L}[\p{L}\p{N}_]*)/giu)].map((m) => m[1]);
console.log(tags);
```

The lookbehind refuses a `#` straight after a word character or a slash, as in the URL; the negative lookahead refuses
six hexadecimal digits, a colour code; and `\p{L}` lets a tag start with a letter from any language.

Next, try the quiz to check your understanding, then solve the challenges.
