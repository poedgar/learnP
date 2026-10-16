## Two-dimensional layout

Flexbox lays things out along one line at a time. **CSS Grid** handles rows **and** columns together, which suits page
layouts and galleries. `grid-template-columns` defines the columns; the children fill the cells in order:

```html
<style>
    .grid { display: grid; grid-template-columns: 100px 200px 100px; gap: 8px; }
    .grid div { padding: 8px; background: #e0ecff; }
</style>

<div class="grid">
    <div>1</div><div>2</div><div>3</div>
    <div>4</div><div>5</div><div>6</div>
</div>
```

## The `fr` unit and `repeat()`

The **`fr`** unit is a fraction of the free space, so `1fr 2fr` shares it one to two. `repeat(3, 1fr)` writes three
equal columns briefly:

```html
<style>
    .three { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .wide { display: grid; grid-template-columns: 200px 1fr; gap: 12px; margin-top: 12px; }
    .three div, .wide div { padding: 8px; background: #fde2e4; }
</style>

<div class="three"><div>A third</div><div>A third</div><div>A third</div></div>
<div class="wide"><div>200px</div><div>The rest</div></div>
```

## Spanning and placing

Items can span several columns or rows, or be placed on particular grid **lines** (numbered from 1 at the left or top
edge):

```html
<style>
    .board { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 60px; gap: 6px; }
    .board div { background: #dfe; padding: 6px; }
    .feature { grid-column: span 2; grid-row: span 2; background: #9d9 !important; }
    .last { grid-column: 4; }
</style>

<div class="board">
    <div class="feature">Featured (2 × 2)</div>
    <div>b</div><div>c</div><div>d</div><div>e</div><div class="last">At line 4</div>
</div>
```

## Named areas

`grid-template-areas` draws the layout as a picture of names, and each item takes an area with `grid-area`. It's the
clearest way to describe a page layout:

```html
<style>
    .page {
        display: grid;
        grid-template-columns: 160px 1fr;
        grid-template-areas:
            "header header"
            "sidebar main"
            "footer footer";
        gap: 8px;
    }
    .page > * { padding: 8px; background: #eef; }
    header { grid-area: header; }
    aside { grid-area: sidebar; }
    main { grid-area: main; }
    footer { grid-area: footer; }
</style>

<div class="page">
    <header>Book club</header>
    <aside>Filters</aside>
    <main>Books</main>
    <footer>© 2026</footer>
</div>
```

## Responsive design

A **responsive** page adapts to the screen it's on. Start with the viewport `meta` tag, so phones don't pretend to be
wide screens, then use flexible units (`%`, `fr`, `rem`), `max-width` instead of fixed widths, and images that can
shrink:

```html
<style>
    img { max-width: 100%; height: auto; }
    .text { max-width: 60ch; }
</style>

<img src="https://picsum.photos/1200/300" alt="A wide banner of bookshelves" width="1200" height="300">
<p class="text">A line of about 60 characters is comfortable to read on any screen.</p>
```

`ch` is the width of the "0" character, so `60ch` is a good reading width.

## Media queries

A **media query** applies rules only when a condition is true, usually a minimum or maximum width. Writing the phone
layout first and adding wider layouts with `min-width` queries is called **mobile-first**:

```html
<style>
    .cards { display: grid; grid-template-columns: 1fr; gap: 12px; }
    .cards div { padding: 12px; background: #fff3bf; }

    @media (min-width: 600px) {
        .cards { grid-template-columns: repeat(2, 1fr); }
    }

    @media (min-width: 900px) {
        .cards { grid-template-columns: repeat(3, 1fr); }
    }
</style>

<div class="cards"><div>Circe</div><div>Dune</div><div>Emma</div><div>Odes</div></div>
```

Make the sandbox window narrower and wider to see the columns change.

## Responsive without media queries

`repeat(auto-fill, minmax(150px, 1fr))` makes as many columns as fit, each at least 150px, sharing any extra space. The
grid adapts to any width with no breakpoints at all:

```html
<style>
    .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; }
    .gallery div { aspect-ratio: 1; background: #d0ebff; display: grid; place-items: center; }
</style>

<div class="gallery"><div>1</div><div>2</div><div>3</div><div>4</div><div>5</div><div>6</div><div>7</div></div>
```

`clamp(min, preferred, max)` does the same for sizes: `font-size: clamp(1.5rem, 4vw, 3rem)` scales a heading with the
window, within limits.

## Putting it together

A responsive page layout: one column on phones, a sidebar layout from 700px:

```html
<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, sans-serif; }
    .layout { display: grid; gap: 12px; padding: 12px; grid-template-areas: "header" "main" "sidebar" "footer"; }
    .layout > * { padding: 12px; border-radius: 8px; background: #f1f3f5; }
    header { grid-area: header; }
    main { grid-area: main; }
    aside { grid-area: sidebar; }
    footer { grid-area: footer; }

    @media (min-width: 700px) {
        .layout {
            grid-template-columns: 1fr 220px;
            grid-template-areas: "header header" "main sidebar" "footer footer";
        }
    }

    h1 { font-size: clamp(1.5rem, 5vw, 2.5rem); margin: 0; }
</style>

<div class="layout">
    <header><h1>Leeds Book Club</h1></header>
    <main>This month: Circe, by Madeline Miller.</main>
    <aside>Next meeting: 2 November</aside>
    <footer>Central Library, 7pm</footer>
</div>
```

Next, try the quiz to check your understanding, then solve the challenges.
