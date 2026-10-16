## Styling with CSS

**CSS** describes how a page looks. A **rule** has a **selector**, saying which elements it applies to, and
**declarations**, each a **property** and a **value**. Rules go in a `<style>` element (or a separate `.css` file):

```html
<style>
    h1 {
        color: darkslateblue;
        font-family: Georgia, serif;
    }

    p {
        color: #444;
        line-height: 1.6;
    }
</style>

<h1>Book club</h1>
<p>We read one book a month and meet to talk about it.</p>
```

## Selectors

The most common selectors pick elements by type, by class (`.name`) or by id (`#name`), and can be combined:

| Selector | Matches |
|----------|---------|
| `p` | every `p` element |
| `.note` | every element with `class="note"` |
| `#intro` | the element with `id="intro"` |
| `article p` | `p` elements anywhere inside an `article` |
| `ul > li` | `li` elements that are direct children of a `ul` |
| `h1, h2` | both `h1` and `h2` |
| `a[href^="https"]` | links whose `href` starts with "https" |

```html
<style>
    .note { background: lightyellow; padding: 4px; }
    #intro { font-size: 1.25rem; }
    article p { color: darkgreen; }
    a[href^="https"] { font-weight: bold; }
</style>

<p id="intro">Welcome!</p>
<p class="note">Meetings start at 7pm.</p>
<article><p>This paragraph is inside an article.</p></article>
<p><a href="https://example.org">Secure link</a> and <a href="/local">local link</a></p>
```

## Pseudo-classes and pseudo-elements

A **pseudo-class** selects elements in a state or position: `:hover` (under the pointer), `:focus`, `:first-child`,
`:nth-child(2n)` (every second one), `:not(...)`. A **pseudo-element** styles a part of an element, such as
`::first-line`, or adds content with `::before` and `::after`:

```html
<style>
    li:nth-child(odd) { background: #f3f3f3; }
    li:first-child { font-weight: bold; }
    a:hover, a:focus { color: crimson; }
    .new::after { content: " (new!)"; color: crimson; }
</style>

<ul>
    <li>Circe</li>
    <li class="new">Dune</li>
    <li>Emma</li>
    <li><a href="#">More books</a></li>
</ul>
```

## Colours and units

Colours can be names (`tomato`), hex (`#ff6347`), `rgb(255 99 71)` or `hsl(9 100% 64%)`. Lengths use units: `px`
(CSS pixels), `rem` (relative to the root font size, usually 16px), `em` (relative to the element's own font size),
`%` (of the parent), and `vw` / `vh` (of the window):

```html
<style>
    .box { color: white; background: hsl(210 70% 40%); padding: 1rem; width: 50%; }
    .box small { font-size: 0.75em; }
</style>

<div class="box">A box half as wide as the page <small>with smaller print</small></div>
```

## The cascade

When several rules set the same property on an element, the **cascade** decides which wins. More **specific**
selectors beat less specific ones: an id beats classes, and classes (and attributes and pseudo-classes) beat element
types. When specificity is equal, the rule that comes **later** wins:

```html
<style>
    p { color: gray; }
    .warning { color: orange; }
    #urgent { color: red; }
    p { color: black; }
</style>

<p>Plain: black, the later of the two p rules.</p>
<p class="warning">Class: orange, beats p.</p>
<p class="warning" id="urgent">Id: red, beats the class.</p>
```

Specificity is counted as (ids, classes, types): `#nav a` is (1, 0, 1), `.menu li a` is (0, 1, 2), so `#nav a` wins.
`!important` overrides all this, which makes styles hard to change later; avoid it.

## Inheritance

Some properties, mostly about text (`color`, `font-family`, `font-size`, `line-height`), are **inherited**: children
get them from their parent unless something sets them directly. Others, like `border`, `padding` and `background`,
aren't:

```html
<style>
    article { color: navy; font-family: Georgia, serif; border: 2px solid navy; }
</style>

<article>
    <h2>Inherited</h2>
    <p>This text is navy and in Georgia, but has no border of its own.</p>
</article>
```

## Custom properties

**Custom properties** (CSS variables) start with `--`. Set them once, often on `:root`, and use them with `var()`. Changing
one changes everything that uses it:

```html
<style>
    :root {
        --brand: #2a6f97;
        --space: 12px;
    }

    .button { background: var(--brand); color: white; padding: var(--space) calc(var(--space) * 2); border: none; }
    .notice { border-left: 4px solid var(--brand); padding: var(--space); }
</style>

<p class="notice">Members get a discount.</p>
<button class="button">Join now</button>
```

## Putting it together

A styled card using classes, a pseudo-class, a pseudo-element and variables:

```html
<style>
    :root { --accent: #b5179e; }
    .card { font-family: system-ui, sans-serif; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; max-width: 20rem; }
    .card h2 { margin: 0 0 0.5rem; color: var(--accent); }
    .card .meta { color: #666; font-size: 0.875rem; }
    .card a { color: var(--accent); }
    .card a:hover { text-decoration: none; }
    .card .meta::before { content: "★ "; }
</style>

<article class="card">
    <h2>Circe</h2>
    <p class="meta">Madeline Miller, 2018</p>
    <p>A witch, exiled to an island, finds her power. <a href="#">Read the review</a></p>
</article>
```

Next, try the quiz to check your understanding, then solve the challenges.
