## The page as objects

When a browser loads a web page, it turns the HTML into a tree of objects called the **DOM** (Document Object Model).
Every element is an object you can read and change from JavaScript, through the global `document`.

In this course each example starts by putting some HTML on the page, then works with it. The sandbox shows the page
your code builds under the output, and it's live: you can click and type in it.

```javascript
document.body.innerHTML = `
    <h1 id="title">Book club</h1>
    <p class="intro">This month: <strong>Circe</strong>.</p>
`;

const heading = document.querySelector('#title');
console.log(heading.textContent);
console.log(document.querySelector('.intro strong').textContent);
```

## Finding elements with CSS selectors

`querySelector(selector)` gives the first element matching a CSS selector, or `null`. `querySelectorAll(selector)`
gives all of them, as a **NodeList**:

```javascript
document.body.innerHTML = `
    <ul id="books">
        <li class="book">Dune</li>
        <li class="book read">Emma</li>
        <li class="book read">Circe</li>
    </ul>
`;

const all = document.querySelectorAll('.book');
const read = document.querySelectorAll('#books .read');

console.log(all.length, read.length);
console.log(document.querySelector('li').textContent);
console.log(document.querySelector('.missing'));

for (const item of read) {
    console.log(item.textContent);
}
```

The selectors are the same as in CSS: `#id`, `.class`, `tag`, `parent child`, `[attribute="value"]`, and so on. A
NodeList works with `for...of`; for `map()` and `filter()`, turn it into an array with `[...list]`.

`document.getElementById('books')` is an older, still common way to find one element by id.

## Text and HTML inside an element

`textContent` is all the text inside an element, without tags. `innerHTML` is the HTML inside it, as a string:

```javascript
document.body.innerHTML = '<p id="note">Read <em>Circe</em> by Friday</p>';

const note = document.querySelector('#note');
console.log(note.textContent);
console.log(note.innerHTML);
```

## Attributes and `data-` attributes

`getAttribute()` reads an attribute as written in the HTML. Many attributes are also properties, such as `id`, `href`
and `value`. Custom data goes in `data-*` attributes, which you read through `dataset`, with the name in camelCase:

```javascript
document.body.innerHTML = `
    <a id="more" href="/books/circe" data-book-id="42" data-in-stock="true">More about Circe</a>
`;

const link = document.querySelector('#more');

console.log(link.getAttribute('href'));
console.log(link.dataset.bookId, link.dataset.inStock);
console.log(typeof link.dataset.bookId);
```

Attribute values are always strings, so convert them with `Number()` when you need numbers.

## Classes

`classList` holds an element's classes. `contains()` asks about one; finding elements by class is usually done with a
selector:

```javascript
document.body.innerHTML = '<button class="btn primary large">Save</button>';

const button = document.querySelector('button');
console.log(button.classList.contains('primary'), button.classList.contains('danger'));
console.log([...button.classList]);
```

## Moving around the tree

Every element knows its `parentElement`, its `children` (elements only), and its `previousElementSibling` and
`nextElementSibling`. `closest(selector)` walks up from an element to the nearest ancestor (or itself) that matches:

```javascript
document.body.innerHTML = `
    <table>
        <tr data-id="1"><td>Dune</td><td><button>Remove</button></td></tr>
        <tr data-id="2"><td>Emma</td><td><button>Remove</button></td></tr>
    </table>
`;

const secondButton = document.querySelectorAll('button')[1];
const row = secondButton.closest('tr');

console.log(row.dataset.id, row.children.length);
console.log(row.firstElementChild.textContent);
console.log(row.previousElementSibling.dataset.id);
```

`closest()` is especially useful in event handlers, coming soon: find the item a click belongs to.

## Searching inside an element

`querySelector()` and `querySelectorAll()` also work on any element, searching only inside it:

```javascript
document.body.innerHTML = `
    <section id="fiction"><h2>Fiction</h2><p>Dune</p><p>Emma</p></section>
    <section id="poetry"><h2>Poetry</h2><p>Odes</p></section>
`;

const poetry = document.querySelector('#poetry');
console.log(poetry.querySelectorAll('p').length, document.querySelectorAll('p').length);
```

## Putting it together

Reading a whole table of data from the page:

```javascript
document.body.innerHTML = `
    <table id="stock">
        <tr data-pence="999"><th>Dune</th><td class="qty">3</td></tr>
        <tr data-pence="799"><th>Emma</th><td class="qty">0</td></tr>
        <tr data-pence="1299"><th>Circe</th><td class="qty">5</td></tr>
    </table>
`;

const rows = [...document.querySelectorAll('#stock tr')];

const inStock = rows
    .filter((row) => Number(row.querySelector('.qty').textContent) > 0)
    .map((row) => row.querySelector('th').textContent);

const value = rows.reduce((sum, row) => sum + Number(row.dataset.pence) * Number(row.querySelector('.qty').textContent), 0);

console.log(inStock, `£${(value / 100).toFixed(2)}`);
```

Next, try the quiz to check your understanding, then solve the challenges.
