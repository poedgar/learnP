## Responding to the user

Pages react through **events**: a click, a key press, typing, scrolling. `addEventListener(type, handler)` runs a
function each time an event of that type happens on an element. Try clicking the button in the page below the output:

```javascript
document.body.innerHTML = '<button id="greet">Say hello</button><p id="out"></p>';

let clicks = 0;
document.querySelector('#greet').addEventListener('click', () => {
    clicks++;
    document.querySelector('#out').textContent = `Hello! (${clicks})`;
    console.log('clicked', clicks);
});

document.querySelector('#greet').click();
```

`element.click()` fires a click from code, which is also how tests check that handlers work.

## The event object

The handler receives an **event object** describing what happened. `event.type` is its kind and `event.target` is
the element where it started:

```javascript
document.body.innerHTML = '<button id="a">A</button> <button id="b">B</button>';

function report(event) {
    console.log(`${event.type} on ${event.target.id}`);
}

for (const button of document.querySelectorAll('button')) {
    button.addEventListener('click', report);
}

document.querySelector('#b').click();
document.querySelector('#a').click();
```

## Keyboard and input events

`input` fires as the value of a field changes, and `change` when it's committed (on leaving the field, or picking a
new option). Key events say which key in `event.key`:

```javascript
document.body.innerHTML = '<input id="name" placeholder="Your name"> <p id="preview"></p>';

const input = document.querySelector('#name');
input.addEventListener('input', () => {
    document.querySelector('#preview').textContent = `Hello, ${input.value || 'stranger'}!`;
});
input.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        input.value = '';
        input.dispatchEvent(new Event('input'));
    }
});

input.value = 'Amara';
input.dispatchEvent(new Event('input'));
console.log(document.querySelector('#preview').textContent);
```

`dispatchEvent(new Event('input'))` sends an event from code, as if the user had typed.

## Bubbling

Most events **bubble**: after running the handlers on the target, they run those on its parent, then its parent's
parent, all the way up to the document. `event.currentTarget` is the element whose handler is running now:

```javascript
document.body.innerHTML = '<div id="card"><button id="buy">Buy</button></div>';

document.querySelector('#buy').addEventListener('click', (event) => {
    console.log('button handler', event.currentTarget.id);
});
document.querySelector('#card').addEventListener('click', (event) => {
    console.log('card handler', event.currentTarget.id, 'target:', event.target.id);
});

document.querySelector('#buy').click();
```

`event.stopPropagation()` stops an event bubbling further. Use it sparingly; code further up may need to know.

## Event delegation

Because events bubble, one handler on a parent can serve all its children, including children added later. In the
handler, `event.target.closest()` finds which child was used:

```javascript
document.body.innerHTML = '<ul id="list"><li>Dune <button data-action="remove">×</button></li></ul>';

const list = document.querySelector('#list');
list.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action="remove"]');
    if (!button) {
        return;
    }
    console.log(`removing ${button.parentElement.firstChild.textContent.trim()}`);
    button.closest('li').remove();
});

list.insertAdjacentHTML('beforeend', '<li>Emma <button data-action="remove">×</button></li>');
list.querySelectorAll('button')[1].click();
console.log(list.children.length);
```

This is simpler and faster than adding a handler to every item.

## Stopping the default action

Some events have a **default action**: a link goes to its page, a form submits and reloads, a key types a character.
`event.preventDefault()` stops it, so your code can decide what happens instead:

```javascript
document.body.innerHTML = '<a id="help" href="/help">Help</a>';

document.querySelector('#help').addEventListener('click', (event) => {
    event.preventDefault();
    console.log('Showing help in the page instead');
});

const click = new MouseEvent('click', { bubbles: true, cancelable: true });
document.querySelector('#help').dispatchEvent(click);
console.log(click.defaultPrevented);
```

## Removing listeners, and `once`

`removeEventListener()` takes off a handler, but only if it's given the same function, so keep a reference to it.
The `once` option removes a handler automatically after it runs:

```javascript
document.body.innerHTML = '<button id="go">Go</button>';
const button = document.querySelector('#go');

let count = 0;
const handler = () => count++;

button.addEventListener('click', handler);
button.click();
button.removeEventListener('click', handler);
button.click();

button.addEventListener('click', () => console.log('first click only'), { once: true });
button.click();
button.click();

console.log(count);
```

## Custom events

`new CustomEvent(type, { detail })` makes your own events, carrying data in `detail`. Parts of a page can announce
things this way without knowing who's listening:

```javascript
document.body.innerHTML = '<div id="basket"></div>';
const basket = document.querySelector('#basket');

basket.addEventListener('item-added', (event) => {
    console.log(`Added ${event.detail.title} at £${event.detail.price}`);
});

basket.dispatchEvent(new CustomEvent('item-added', { detail: { title: 'Dune', price: 9.99 } }));
```

## Putting it together

A star rating built with delegation and data attributes. Click the stars in the page:

```javascript
document.body.innerHTML = `
    <style>.star { font-size: 2rem; cursor: pointer; background: none; border: none; } .on { color: gold; }</style>
    <div id="rating">${[1, 2, 3, 4, 5].map((n) => `<button class="star" data-value="${n}" aria-label="${n} stars">★</button>`).join('')}</div>
    <p id="chosen">No rating yet</p>
`;

const rating = document.querySelector('#rating');

rating.addEventListener('click', (event) => {
    const star = event.target.closest('.star');
    if (!star) return;
    const value = Number(star.dataset.value);
    for (const s of rating.querySelectorAll('.star')) {
        s.classList.toggle('on', Number(s.dataset.value) <= value);
    }
    document.querySelector('#chosen').textContent = `You rated it ${value} out of 5`;
});

rating.querySelectorAll('.star')[3].click();
console.log(document.querySelector('#chosen').textContent, rating.querySelectorAll('.on').length);
```

Next, try the quiz to check your understanding, then solve the challenges.
