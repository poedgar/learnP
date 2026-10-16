## Pages are made of elements

A web page is written in **HTML**: text marked up with **tags** that say what each part is. An opening tag, the
content, and a closing tag with a slash make an **element**. Elements nest inside each other, like boxes in boxes:

```html
<h1>Book club</h1>
<p>We meet on the <strong>first Monday</strong> of every month.</p>
<p>This month we're reading <em>Circe</em>.</p>
```

Press **Try it in the sandbox** under any example: the page appears below the editor and changes as you type.

## Attributes

Opening tags can carry **attributes**, extra information written `name="value"`. Some, such as `id` and `class`, work on
any element; others belong to particular elements:

```html
<p id="welcome" class="note">Welcome back!</p>
<a href="https://example.org" title="The club's old site">Visit the old site</a>
<img src="https://picsum.photos/120/80" alt="A pile of books" width="120" height="80">
```

`img` has no closing tag: some elements, called **void elements**, never have content. Others are `br`, `hr`, `input`
and `meta`.

## The shape of a document

A complete page starts with `<!doctype html>`, then an `html` element holding a `head` (information about the page,
which isn't shown) and a `body` (what's shown):

```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book club</title>
    </head>
    <body>
        <h1>Book club</h1>
        <p>All welcome.</p>
    </body>
</html>
```

`lang` tells browsers and screen readers the language; `charset` makes accented letters show correctly; the
`viewport` line makes the page fit phone screens; `title` appears in the browser tab and in search results.

## Headings

Headings run from `h1`, the main title, down to `h6`. They form an outline of the page, which screen-reader users jump
through and search engines read, so use them in order: one `h1`, then `h2`s for sections, `h3`s inside those:

```html
<h1>Book club</h1>
<h2>This month</h2>
<p>Circe, by Madeline Miller.</p>
<h2>Next month</h2>
<h3>Shortlist</h3>
<p>Dune, or Emma.</p>
```

Choose a heading level for its place in the outline, not for its size: size is CSS's job.

## Elements that say what they are

**Semantic** elements describe the role of a part of the page. They look like plain boxes, but browsers, screen readers
and search engines understand them:

- `header`: introductory content, often the site name and navigation;
- `nav`: the main links around the site;
- `main`: the page's main content (one per page);
- `article`: a self-contained piece, such as a post or a review;
- `section`: a themed part of the page, usually with a heading;
- `aside`: related but separate content, such as a sidebar;
- `footer`: closing information, such as contact details.

```html
<header>
    <p>Book club</p>
    <nav><a href="/">Home</a> | <a href="/books">Books</a></nav>
</header>
<main>
    <article>
        <h1>Review: Circe</h1>
        <p>A retelling of the Odyssey's witch.</p>
    </article>
</main>
<footer>Meetings at the library, 7pm.</footer>
```

`div` and `span` are the meaning-free alternatives, for grouping things purely for styling: `div` for blocks, `span`
inside a line of text.

## Text-level elements

Inside paragraphs, elements mark up words: `strong` for importance, `em` for stress, `a` for links, `code` for code,
`time` for dates and times (with a machine-readable `datetime`), `br` for a line break inside a paragraph:

```html
<p>
    <strong>Note:</strong> we start <em>promptly</em> on
    <time datetime="2026-11-02T19:00">2 November at 7pm</time>.<br>
    Bring your copy of <cite>Circe</cite>.
</p>
```

## Comments and whitespace

Browsers collapse runs of spaces and line breaks into a single space, so you can indent HTML freely. Comments,
`<!-- like this -->`, are ignored:

```html
<!-- The welcome message -->
<p>Several     spaces
   and line breaks
   become one space.</p>
```

## Putting it together

A small but complete page with a clear outline:

```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Book club: November</title>
    </head>
    <body>
        <header>
            <p><strong>Leeds Book Club</strong></p>
            <nav><a href="/">Home</a> · <a href="/past">Past books</a></nav>
        </header>
        <main>
            <h1>November: Circe</h1>
            <section>
                <h2>When and where</h2>
                <p><time datetime="2026-11-02T19:00">Monday 2 November, 7pm</time>, Central Library.</p>
            </section>
            <section>
                <h2>Before you come</h2>
                <p>Read at least the first half. <em>No spoilers</em> for the ending!</p>
            </section>
        </main>
        <footer>Questions? Ask at the library desk.</footer>
    </body>
</html>
```

Next, try the quiz to check your understanding, then solve the challenges.
