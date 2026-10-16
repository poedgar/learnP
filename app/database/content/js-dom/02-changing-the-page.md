## Changing text

Assigning to `textContent` replaces everything inside an element with plain text. It's the safe, everyday way to
show a value:

```javascript
document.body.innerHTML = '<p id="count">0 items</p>';

const count = document.querySelector('#count');
count.textContent = '3 items';

console.log(document.body.innerHTML);
```

## Creating and adding elements

`document.createElement(tag)` makes a new element, not yet on the page. Fill it in, then put it somewhere:

- `parent.append(...nodes)` adds at the end, `parent.prepend(...nodes)` at the start;
- `el.before(...nodes)` and `el.after(...nodes)` add next to an element;
- `el.remove()` takes it off the page, and `el.replaceWith(other)` swaps it.

```javascript
document.body.innerHTML = '<ul id="shelf"><li>Emma</li></ul>';

const shelf = document.querySelector('#shelf');

const dune = document.createElement('li');
dune.textContent = 'Dune';
shelf.append(dune);

const atlas = document.createElement('li');
atlas.textContent = 'Atlas';
shelf.prepend(atlas);

dune.before('— ');

console.log(shelf.innerHTML);
```

`append()` and its friends also take plain strings, which become text.

## Moving elements

An element can only be in one place. Adding one that's already on the page **moves** it:

```javascript
document.body.innerHTML = '<ol id="queue"><li>Amara</li><li>Ben</li><li>Cy</li></ol>';

const queue = document.querySelector('#queue');
const cy = queue.lastElementChild;
queue.prepend(cy);

console.log([...queue.children].map((li) => li.textContent));
```

`el.cloneNode(true)` makes a copy, with everything inside it, if you need the element in two places.

## Classes and styles

`classList.add()`, `remove()` and `toggle()` change classes; `toggle()` returns whether the class is now there. Styles
are best kept in CSS and switched with classes; for a one-off value, `el.style` sets inline styles, with property names
in camelCase:

```javascript
document.body.innerHTML = '<button id="like">Like</button>';

const like = document.querySelector('#like');
like.classList.add('btn');
const liked = like.classList.toggle('liked');

like.style.backgroundColor = 'gold';
like.style.padding = '0.5rem 1rem';

console.log(liked, like.className, like.getAttribute('style'));
```

## Attributes

`setAttribute()` and `removeAttribute()` change attributes; `toggleAttribute()` adds or removes a boolean one such as
`disabled` or `hidden`. `dataset` writes `data-` attributes:

```javascript
document.body.innerHTML = '<button id="save">Save</button>';

const save = document.querySelector('#save');
save.setAttribute('aria-label', 'Save the book');
save.toggleAttribute('disabled', true);
save.dataset.bookId = '42';

console.log(save.outerHTML);
```

## `innerHTML` and safety

Assigning HTML to `innerHTML` is quick for building many elements at once, but anything in it is treated as HTML.
Text that came from a user, such as a name or a comment, could contain tags, and even run scripts in the page. So
never put such text into `innerHTML` without escaping it:

```javascript
document.body.innerHTML = '<p id="a"></p><p id="b"></p>';

const comment = 'I <b>loved</b> it <img src=x onerror="alert(1)">';

document.querySelector('#a').innerHTML = comment;
document.querySelector('#b').textContent = comment;

console.log(document.querySelector('#a').children.length, document.querySelector('#b').children.length);
```

The first paragraph now contains real elements, including an image with an attack in its `onerror`; the second shows
the text exactly as typed. With `createElement` and `textContent` this can't happen.

## Building a list from data

A common job is to turn an array into elements. Build each one, then add them all; `replaceChildren()` clears an
element and puts new children in, in one step:

```javascript
document.body.innerHTML = '<ul id="results"><li>old result</li></ul>';

const books = [
    { title: 'Dune', year: 1965 },
    { title: 'Circe', year: 2018 },
];

const items = books.map((book) => {
    const li = document.createElement('li');
    li.textContent = `${book.title} (${book.year})`;
    li.dataset.year = book.year;
    return li;
});

document.querySelector('#results').replaceChildren(...items);
console.log(document.querySelector('#results').innerHTML);
```

## Showing and hiding

The `hidden` attribute hides an element, and is easy to switch from JavaScript:

```javascript
document.body.innerHTML = '<p id="empty">Your basket is empty.</p><ul id="basket"></ul>';

function showBasket(items) {
    document.querySelector('#empty').hidden = items.length > 0;
    document.querySelector('#basket').replaceChildren(...items.map((item) => Object.assign(document.createElement('li'), { textContent: item })));
}

showBasket(['Dune']);
console.log(document.querySelector('#empty').hidden, document.querySelectorAll('#basket li').length);
```

`Object.assign(element, { textContent: ... })` sets several properties of a new element at once.

## Putting it together

A progress bar driven from data:

```javascript
document.body.innerHTML = `
    <style>
        .track { background: #eee; border-radius: 4px; width: 16rem; }
        .bar { background: #3a7; height: 1rem; border-radius: 4px; }
    </style>
    <div class="track"><div class="bar" id="bar"></div></div>
    <p id="label"></p>
`;

function setProgress(done, total) {
    const percent = total === 0 ? 0 : Math.round((done / total) * 100);
    document.querySelector('#bar').style.width = `${percent}%`;
    document.querySelector('#label').textContent = `${done} of ${total} chapters (${percent}%)`;
}

setProgress(7, 12);
console.log(document.querySelector('#label').textContent);
```

Next, try the quiz to check your understanding, then solve the challenges.
