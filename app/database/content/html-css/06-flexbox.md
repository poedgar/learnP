## Laying things out in a row

**Flexbox** arranges the children of an element in a row or a column, and shares out the space between them.
`display: flex` on the parent, the **flex container**, makes its children **flex items**, laid out in a row by
default:

```html
<style>
    .toolbar { display: flex; gap: 8px; padding: 8px; background: #eef; }
    .toolbar button { padding: 6px 12px; }
</style>

<div class="toolbar">
    <button>Bold</button>
    <button>Italic</button>
    <button>Link</button>
</div>
```

`gap` sets the space between items, without margins on the outer edges.

## Direction and the two axes

`flex-direction` chooses the **main axis**: `row` (left to right, the default) or `column` (top to bottom). The
**cross axis** runs the other way. `justify-content` places items along the main axis, `align-items` along the cross
axis:

```html
<style>
    .bar { display: flex; justify-content: space-between; align-items: center; height: 80px; padding: 0 12px; background: #2a6f97; color: white; }
    .stack { display: flex; flex-direction: column; gap: 4px; margin-top: 12px; }
</style>

<header class="bar">
    <strong>Book club</strong>
    <nav>Home · Books · About</nav>
</header>
<div class="stack">
    <span>One</span><span>Two</span><span>Three</span>
</div>
```

Common values: `justify-content: flex-start | center | flex-end | space-between | space-around`, and
`align-items: stretch | flex-start | center | flex-end | baseline`.

## Centring anything

The classic hard problem in CSS, centring something both ways, takes two lines with Flexbox:

```html
<style>
    .stage { display: flex; justify-content: center; align-items: center; height: 160px; background: #fde; }
</style>

<div class="stage"><p>Perfectly centred</p></div>
```

## Growing and shrinking

Items can share out spare space. `flex-grow` says how much of the free space an item takes, relative to the others;
`flex-shrink` how much it gives up when there isn't enough; and `flex-basis` its starting size. The shorthand
`flex: 1` means "grow and shrink equally from zero":

```html
<style>
    .row { display: flex; gap: 8px; }
    .row > div { padding: 8px; background: #dfe; }
    .sidebar { flex: 0 0 120px; }
    .content { flex: 1; }
</style>

<div class="row">
    <div class="sidebar">Sidebar: always 120px</div>
    <div class="content">Content: takes the rest</div>
</div>
```

```html
<style>
    .split { display: flex; }
    .split > div { padding: 8px; }
    .one { flex: 1; background: #fee; }
    .two { flex: 2; background: #eef; }
</style>

<div class="split"><div class="one">flex: 1</div><div class="two">flex: 2, twice as wide</div></div>
```

## Wrapping

By default, flex items stay on one line and shrink to fit. `flex-wrap: wrap` lets them move onto new lines instead,
which makes simple responsive grids:

```html
<style>
    .tags { display: flex; flex-wrap: wrap; gap: 6px; width: 260px; }
    .tags span { padding: 4px 10px; border-radius: 999px; background: #e7e0f7; }
</style>

<div class="tags">
    <span>fantasy</span><span>myth</span><span>retelling</span><span>greek</span><span>prize-winner</span><span>2018</span>
</div>
```

## One item on its own

`align-self` overrides `align-items` for one item; `margin-left: auto` on an item pushes it (and everything after it)
to the far end, a neat way to split a toolbar; `order` changes the display order without changing the HTML (use
sparingly, as screen readers follow the HTML order):

```html
<style>
    .nav { display: flex; gap: 12px; align-items: center; padding: 8px; background: #f4f4f4; }
    .nav .account { margin-left: auto; }
</style>

<nav class="nav">
    <a href="#">Home</a>
    <a href="#">Books</a>
    <a href="#" class="account">My account</a>
</nav>
```

## Putting it together

A media object, the common "picture beside text" layout, and a row of equal cards:

```html
<style>
    .media { display: flex; gap: 12px; align-items: flex-start; }
    .media img { flex: 0 0 64px; border-radius: 50%; }
    .media h3 { margin: 0 0 4px; }

    .cards { display: flex; gap: 12px; margin-top: 16px; }
    .cards article { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 8px; }
</style>

<div class="media">
    <img src="https://picsum.photos/64/64" alt="" width="64" height="64">
    <div>
        <h3>Amara Okafor</h3>
        <p>Has read 42 books this year, mostly myths and science fiction.</p>
    </div>
</div>

<div class="cards">
    <article>Circe</article>
    <article>Dune</article>
    <article>Emma</article>
</div>
```

Next, try the quiz to check your understanding, then solve the challenges.
