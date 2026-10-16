## Collecting input

A `form` groups the fields people fill in. Each field needs a `name` (the key its value is sent under) and a **label**
that says what it's for. A `button` inside the form sends it:

```html
<form action="/search" method="get">
    <label for="q">Search the catalogue</label>
    <input id="q" name="q" type="search" placeholder="Title or author">
    <button>Search</button>
</form>
```

`action` is where the form is sent and `method` how: `get` puts the values in the address (good for searches), `post`
sends them in the request body (for anything that changes data).

## Labels

A `label` connects to its field either by `for`, matching the field's `id`, or by wrapping it. Either way, clicking the
label focuses the field, and screen readers announce the label when the field is reached. A placeholder is **not** a
label: it disappears as soon as you type.

```html
<form>
    <label for="email">Email</label>
    <input id="email" name="email" type="email">

    <label><input type="checkbox" name="news"> Send me the newsletter</label>
</form>
```

## Types of input

The `type` attribute changes what an `input` accepts, and on phones it brings up the right keyboard:

```html
<form>
    <p><label>Email <input type="email" name="email"></label></p>
    <p><label>Phone <input type="tel" name="phone"></label></p>
    <p><label>Age <input type="number" name="age" min="0" max="120"></label></p>
    <p><label>Date of visit <input type="date" name="visit"></label></p>
    <p><label>Password <input type="password" name="password"></label></p>
    <p><label>Rating <input type="range" name="rating" min="1" max="5"></label></p>
</form>
```

## Choosing from options

Radio buttons offer a single choice from a group sharing a `name`; checkboxes allow any number. A `select` gives a
drop-down list, and a `textarea` takes several lines of text:

```html
<form>
    <fieldset>
        <legend>Format</legend>
        <label><input type="radio" name="format" value="paperback" checked> Paperback</label>
        <label><input type="radio" name="format" value="ebook"> E-book</label>
    </fieldset>

    <label for="branch">Collect from</label>
    <select id="branch" name="branch">
        <option value="central">Central Library</option>
        <option value="hill" selected>Hilltop</option>
    </select>

    <label for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="3"></textarea>
</form>
```

A `fieldset` with a `legend` groups related fields and names the group, which is how a screen reader knows what the
radio buttons are choosing.

## Built-in validation

Attributes describe what's valid, and the browser checks before sending: `required`, `minlength` and `maxlength`,
`min` and `max`, `pattern` (a regular expression the whole value must match), and the types themselves:

```html
<form>
    <p><label>Name <input name="name" required minlength="2"></label></p>
    <p><label>Postcode <input name="postcode" pattern="[A-Za-z]{1,2}\d[A-Za-z\d]? ?\d[A-Za-z]{2}" required></label></p>
    <p><label>Copies <input name="copies" type="number" min="1" max="5" value="1"></label></p>
    <button>Reserve</button>
</form>
```

Try pressing **Reserve** in the sandbox with the fields empty. The server must check everything again too: anyone can
send a request without using your form.

## Helpful extras

`autocomplete` tells browsers which saved details fit a field, and `aria-describedby` links a field to a hint, which
screen readers read with it:

```html
<form>
    <label for="new-password">Choose a password</label>
    <input id="new-password" name="password" type="password" autocomplete="new-password" aria-describedby="password-hint">
    <p id="password-hint">At least 10 characters, with a number.</p>
</form>
```

## Buttons

A `button` in a form submits it unless it has `type="button"` (for buttons that run JavaScript) or `type="reset"`
(which puts the fields back as they were):

```html
<form>
    <input name="title" value="Dune">
    <button type="submit">Save</button>
    <button type="reset">Undo changes</button>
    <button type="button">Preview</button>
</form>
```

## Putting it together

A complete, accessible booking form:

```html
<form action="/bookings" method="post">
    <h1>Book a reading room</h1>

    <label for="who">Your name</label>
    <input id="who" name="name" autocomplete="name" required>

    <label for="mail">Email</label>
    <input id="mail" name="email" type="email" autocomplete="email" required>

    <fieldset>
        <legend>Room</legend>
        <label><input type="radio" name="room" value="quiet" required> Quiet room</label>
        <label><input type="radio" name="room" value="group"> Group room</label>
    </fieldset>

    <label for="day">Date</label>
    <input id="day" name="date" type="date" required>

    <label><input type="checkbox" name="accessible"> I need step-free access</label>

    <button>Request the room</button>
</form>
```

Next, try the quiz to check your understanding, then solve the challenges.
