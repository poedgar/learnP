## Reading what was typed

Form fields have a `value`: the text in an `<input>` or `<textarea>`, or the chosen option of a `<select>`. It's
always a string. Checkboxes and radio buttons use `checked` instead:

```javascript
document.body.innerHTML = `
    <input id="name" value="Amara">
    <input id="age" type="number" value="34">
    <select id="format"><option>paperback</option><option selected>ebook</option></select>
    <label><input id="gift" type="checkbox" checked> Gift wrap</label>
`;

console.log(document.querySelector('#name').value);
console.log(document.querySelector('#age').value, typeof document.querySelector('#age').value);
console.log(document.querySelector('#age').valueAsNumber);
console.log(document.querySelector('#format').value, document.querySelector('#gift').checked);
```

`valueAsNumber` reads a number field as a number (`NaN` if it's empty).

## Handling a form's `submit`

Put fields in a `<form>` and listen for its `submit` event, rather than for clicks on the button: it fires for the
button **and** for pressing Enter in a field. Its default action sends the form and reloads the page, so call
`preventDefault()` to handle it in JavaScript:

```javascript
document.body.innerHTML = `
    <form id="search">
        <input name="q" placeholder="Title or author">
        <button>Search</button>
    </form>
    <p id="status"></p>
`;

const form = document.querySelector('#search');
form.addEventListener('submit', (event) => {
    event.preventDefault();
    const term = form.elements.q.value.trim();
    document.querySelector('#status').textContent = term ? `Searching for "${term}"` : 'Type something to search for';
});

form.elements.q.value = '  dune ';
form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
console.log(document.querySelector('#status').textContent);
```

`form.elements` gives the fields by their `name`. The last two lines simulate a submit, as a test would; in the page
below, you can type and press Enter.

## All the fields at once: `FormData`

`new FormData(form)` reads every named field. `Object.fromEntries()` turns it into an ordinary object; for fields that
can have several values, such as a group of checkboxes, use `getAll()`:

```javascript
document.body.innerHTML = `
    <form id="order">
        <input name="title" value="Circe">
        <input name="qty" type="number" value="2">
        <label><input type="checkbox" name="extras" value="wrap" checked> Gift wrap</label>
        <label><input type="checkbox" name="extras" value="card" checked> Card</label>
        <label><input type="checkbox" name="extras" value="bag"> Bag</label>
    </form>
`;

const data = new FormData(document.querySelector('#order'));

console.log(Object.fromEntries(data));
console.log(data.getAll('extras'));
```

Unchecked checkboxes aren't included at all.

## Built-in validation

HTML attributes such as `required`, `type="email"`, `min`, `max` and `pattern` describe valid input. `checkValidity()`
says whether a field, or a whole form, passes, and `validity` says why not:

```javascript
document.body.innerHTML = `
    <form id="signup">
        <input name="email" type="email" required>
        <input name="age" type="number" min="13" max="120">
    </form>
`;

const form = document.querySelector('#signup');
const { email, age } = form.elements;

console.log(form.checkValidity(), email.validity.valueMissing);

email.value = 'not an email';
console.log(email.checkValidity(), email.validity.typeMismatch);

email.value = 'amara@example.com';
age.value = '9';
console.log(email.checkValidity(), age.validity.rangeUnderflow, form.checkValidity());
```

## Your own validation messages

For rules HTML can't express, check in JavaScript, and show the message next to the field. Linking it with
`aria-describedby` and marking the field `aria-invalid` lets screen readers announce it too:

```javascript
document.body.innerHTML = `
    <form id="pw">
        <input name="password" type="password" aria-describedby="pw-error">
        <p id="pw-error"></p>
    </form>
`;

function checkPassword(input) {
    const problems = [];
    if (input.value.length < 10) problems.push('at least 10 characters');
    if (!/\d/.test(input.value)) problems.push('a digit');

    const message = problems.length ? `Needs ${problems.join(' and ')}.` : '';
    document.querySelector('#pw-error').textContent = message;
    input.setAttribute('aria-invalid', String(problems.length > 0));
    return problems.length === 0;
}

const input = document.querySelector('#pw').elements.password;
input.value = 'short';
console.log(checkPassword(input), document.querySelector('#pw-error').textContent);
input.value = 'much longer 42';
console.log(checkPassword(input), input.getAttribute('aria-invalid'));
```

## Reacting while the user types

The `input` event lets a form respond straight away, such as working out a total as quantities change. Listening on
the form catches the events of every field inside it:

```javascript
document.body.innerHTML = `
    <form id="calc">
        <input name="qty" type="number" value="1" min="0"> × £<span id="price">7.99</span>
        <p>Total: <output id="total">£7.99</output></p>
    </form>
`;

const form = document.querySelector('#calc');
form.addEventListener('input', () => {
    const qty = form.elements.qty.valueAsNumber || 0;
    const pence = Math.round(qty * Number(document.querySelector('#price').textContent) * 100);
    document.querySelector('#total').textContent = `£${(pence / 100).toFixed(2)}`;
});

form.elements.qty.value = '3';
form.elements.qty.dispatchEvent(new Event('input', { bubbles: true }));
console.log(document.querySelector('#total').textContent);
```

## Resetting and disabling

`form.reset()` puts every field back to its starting value. Disabling the button while something is being saved stops
the form being sent twice:

```javascript
document.body.innerHTML = '<form id="f"><input name="note" value="draft"><button>Save</button></form>';

const form = document.querySelector('#f');
form.elements.note.value = 'changed';
form.reset();

const button = form.querySelector('button');
button.disabled = true;

console.log(form.elements.note.value, button.disabled);
```

## Putting it together

A sign-up form: read it with `FormData`, validate it, and show the result. Try it in the page:

```javascript
document.body.innerHTML = `
    <form id="join" novalidate>
        <label>Name <input name="name" required></label>
        <label>Email <input name="email" type="email" required></label>
        <button>Join</button>
    </form>
    <p id="message" role="status"></p>
`;

const form = document.querySelector('#join');
form.addEventListener('submit', (event) => {
    event.preventDefault();
    const { name, email } = Object.fromEntries(new FormData(form));
    const message = document.querySelector('#message');
    if (!form.checkValidity()) {
        message.textContent = 'Please fill in your name and a valid email address.';
        return;
    }
    message.textContent = `Welcome, ${name.trim()}! We'll write to ${email}.`;
    form.reset();
});

form.elements.name.value = 'Ben';
form.elements.email.value = 'ben@example.com';
form.dispatchEvent(new Event('submit', { cancelable: true }));
console.log(document.querySelector('#message').textContent);
```

`novalidate` stops the browser showing its own pop-up messages, so the page can show its own.

Next, try the quiz to check your understanding, then solve the challenges.
