## Every element is a box

The browser lays out every element as a rectangular **box** with four layers, from the inside out:

- the **content**: text, images, child elements;
- **padding**: space inside the box, around the content, filled by the background;
- the **border**: a line around the padding;
- **margin**: space outside the border, separating the box from its neighbours (always transparent).

```html
<style>
    .box {
        width: 200px;
        padding: 20px;
        border: 5px solid steelblue;
        margin: 30px;
        background: lightblue;
    }
</style>

<div class="box">Content</div>
<div class="box">Another box</div>
```

## Sizing: `content-box` and `border-box`

By default, `width` sets only the **content**: padding and border are added on top, so the box above is
200 + 2 × 20 + 2 × 5 = 250 pixels wide. With `box-sizing: border-box`, `width` includes the padding and border, which
is far easier to work with. Many sites set it on everything:

```html
<style>
    .a, .b { width: 200px; padding: 20px; border: 5px solid; margin-bottom: 8px; }
    .b { box-sizing: border-box; }
</style>

<div class="a">content-box: 250px wide in all</div>
<div class="b">border-box: 200px wide in all</div>
```

```html
<style>
    *, *::before, *::after { box-sizing: border-box; }
</style>
<p>The usual line at the top of a stylesheet.</p>
```

## Shorthands for four sides

`margin`, `padding` and `border-width` take one to four values, going clockwise from the top:

| Written | Means |
|---------|-------|
| `10px` | all four sides |
| `10px 20px` | top and bottom 10px, left and right 20px |
| `10px 20px 30px` | top 10, left and right 20, bottom 30 |
| `10px 20px 30px 40px` | top, right, bottom, left |

Each side can also be set on its own: `margin-top`, `padding-left`, `border-bottom`, and so on.

## Block and inline

Elements are either **block** or **inline** by default. Block elements (`div`, `p`, `h1`, `section`, `li`) start on a new
line and take the whole available width. Inline elements (`span`, `a`, `strong`, `em`) sit within a line of text, and
ignore `width`, `height` and top and bottom margins. `display` changes this:

```html
<style>
    span { background: gold; width: 300px; margin: 20px; }
    .badge { display: inline-block; width: 80px; text-align: center; margin: 4px; background: lightgreen; }
    .stacked { display: block; }
</style>

<p>An inline <span>span ignores its width</span> and sits in the text.</p>
<p><span class="badge">One</span><span class="badge">Two</span> are inline-blocks.</p>
<p><a class="stacked" href="#">A link on its own line</a></p>
```

`inline-block` flows in a line like text but accepts a width and height. `display: none` removes an element from the
page completely.

## Centring a block

A block with a set `width` (or `max-width`) can be centred by giving it automatic left and right margins:

```html
<style>
    .page { max-width: 300px; margin: 0 auto; background: #eef; padding: 8px; }
</style>

<div class="page">This column is centred, and narrows on small screens.</div>
```

## Margin collapsing

When two block elements' vertical margins meet, they **collapse** into one: the larger wins, rather than adding up.
Padding and borders never collapse:

```html
<style>
    .one { margin-bottom: 30px; background: #fdd; }
    .two { margin-top: 20px; background: #ddf; }
</style>

<p class="one">30px margin below</p>
<p class="two">20px margin above: the gap is 30px, not 50px</p>
```

## Overflow

When content is bigger than its box, `overflow` decides what happens: `visible` (the default, spilling out), `hidden`
(clipped), `scroll` or `auto` (scroll bars when needed):

```html
<style>
    .small { width: 150px; height: 60px; border: 1px solid; overflow: auto; }
</style>

<div class="small">This box has much more text than fits inside it, so it gets a scroll bar instead of spilling over.</div>
```

## Putting it together

A simple card component, sized predictably with `border-box`:

```html
<style>
    * { box-sizing: border-box; }
    .card {
        width: 260px;
        margin: 16px auto;
        padding: 16px 20px;
        border: 1px solid #ccc;
        border-radius: 12px;
        background: white;
        font-family: system-ui, sans-serif;
    }
    .card h2 { margin: 0 0 8px; font-size: 1.25rem; }
    .card .tag { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #e0f2f1; font-size: 0.8rem; }
</style>

<article class="card">
    <h2>Dune</h2>
    <p>Frank Herbert's desert epic.</p>
    <span class="tag">sci-fi</span> <span class="tag">classic</span>
</article>
```

Next, try the quiz to check your understanding, then solve the challenges.
