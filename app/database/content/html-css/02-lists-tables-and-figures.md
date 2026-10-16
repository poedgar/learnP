## Lists

HTML has three kinds of list. `ul` is an unordered list (bullets), `ol` an ordered one (numbers), and each item is an
`li`. A list can contain another list inside an item:

```html
<h2>Shopping</h2>
<ul>
    <li>Bread</li>
    <li>Fruit
        <ul>
            <li>Apples</li>
            <li>Pears</li>
        </ul>
    </li>
</ul>

<h2>Method</h2>
<ol>
    <li>Preheat the oven.</li>
    <li>Mix the ingredients.</li>
    <li>Bake for 30 minutes.</li>
</ol>
```

`ol` takes `start` (the first number) and `reversed`; screen readers announce how many items a list has, which plain
paragraphs can't do.

## Description lists

A `dl` pairs **terms** (`dt`) with **descriptions** (`dd`): a glossary, or details such as "Author: …":

```html
<dl>
    <dt>Author</dt>
    <dd>Madeline Miller</dd>
    <dt>Published</dt>
    <dd>2018</dd>
    <dt>Pages</dt>
    <dd>393</dd>
</dl>
```

## Tables for tabular data

Use a `table` for data with rows and columns, such as a timetable or a price list (not for page layout, which is CSS's
job). A `caption` names the table; `thead` holds header rows and `tbody` the data; `th` is a header cell and `td` a data
cell:

```html
<table>
    <caption>Opening hours</caption>
    <thead>
        <tr><th scope="col">Day</th><th scope="col">Opens</th><th scope="col">Closes</th></tr>
    </thead>
    <tbody>
        <tr><th scope="row">Monday</th><td>9:00</td><td>17:00</td></tr>
        <tr><th scope="row">Saturday</th><td>10:00</td><td>13:00</td></tr>
    </tbody>
</table>
```

`scope="col"` and `scope="row"` tell screen readers whether a header belongs to a column or a row, so they can read
each cell with its headers.

## Cells across rows and columns

`colspan` and `rowspan` make a cell cover more than one column or row:

```html
<table>
    <tr><th>Item</th><th colspan="2">Price</th></tr>
    <tr><td>Dune</td><td>£9.99</td><td>paperback</td></tr>
    <tr><td rowspan="2">Emma</td><td>£7.99</td><td>paperback</td></tr>
    <tr><td>£14.99</td><td>hardback</td></tr>
</table>
```

## Images and alternative text

Every `img` needs an `alt` attribute: text that stands in for the image when it can't be seen, read by screen readers
and shown if the image fails to load. Describe what matters about the image; for a purely decorative image, use an
empty `alt=""` so it's skipped:

```html
<img src="https://picsum.photos/160/100" alt="Readers chatting in the library" width="160" height="100">
<img src="https://picsum.photos/20/20" alt="" width="20" height="20">
```

Setting `width` and `height` lets the browser reserve the space before the image loads, so the page doesn't jump.

## Figures and captions

A `figure` groups an image (or a chart, a code listing, a quote) with its `figcaption`:

```html
<figure>
    <img src="https://picsum.photos/240/120" alt="A bar chart of books read each month" width="240" height="120">
    <figcaption>Books read by the club in 2026.</figcaption>
</figure>
```

## Quotes and citations

`blockquote` holds a long quotation, with `cite` for the source's URL; `q` is a short quotation inside a sentence,
and `cite` (the element) the title of a work:

```html
<blockquote cite="https://example.org/circe">
    <p>"But in a solitary life, there are rare moments when another soul dips near yours."</p>
</blockquote>
<p>As the narrator of <cite>Circe</cite> says, <q>witchcraft is not a thing you are born to</q>.</p>
```

## Putting it together

A book page with a figure, a details list, a table of editions and a list of reviews:

```html
<article>
    <h1>Circe</h1>
    <figure>
        <img src="https://picsum.photos/120/180" alt="The gold and black cover of Circe" width="120" height="180">
        <figcaption>The first edition cover.</figcaption>
    </figure>
    <dl>
        <dt>Author</dt><dd>Madeline Miller</dd>
        <dt>Genre</dt><dd>Myth retelling</dd>
    </dl>
    <table>
        <caption>Editions</caption>
        <thead><tr><th scope="col">Format</th><th scope="col">Price</th></tr></thead>
        <tbody>
            <tr><th scope="row">Paperback</th><td>£9.99</td></tr>
            <tr><th scope="row">Hardback</th><td>£18.99</td></tr>
        </tbody>
    </table>
    <h2>What members said</h2>
    <ul>
        <li><q>Couldn't put it down.</q> (Amara)</li>
        <li><q>The ending stayed with me.</q> (Ben)</li>
    </ul>
</article>
```

Next, try the quiz to check your understanding, then solve the challenges.
