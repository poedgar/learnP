<?php

// Lesson 4 of the JavaScript and the DOM course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope and on the same page;
// a check returns true or a message. Helpers: expectSame(), expectThrows(), learnerCode().
// Checks submit forms with a cancelable 'submit' event rather than a real submission, which would
// reload the sandbox frame.

return [
    'slug' => 'forms',
    'title' => 'Forms',
    'summary' => 'Work with forms: read values and checkboxes, handle submit with preventDefault, collect fields with FormData, validate with HTML attributes and your own rules, and react as the user types.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter). Then try the form in the page below.
        document.body.innerHTML = `
            <form id="greet">
                <input name="name" placeholder="Your name" required>
                <button>Greet me</button>
            </form>
            <p id="hello"></p>
        `;

        const form = document.querySelector('#greet');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            document.querySelector('#hello').textContent = `Hello, ${form.elements.name.value}!`;
        });
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<input id="n" type="number" value="5">';
                const n = document.querySelector('#n');
                console.log(n.value + 1, n.valueAsNumber + 1);
                JS,
            'options' => ['`51 6`', '`6 6`', '`51 51`', '`6 51`'],
            'correct_option' => 0,
            'explanation' => '`value` is always a string, even for number fields; `valueAsNumber` is a number.',
        ],
        [
            'question' => 'Why listen for a form\'s `submit` event rather than the button\'s `click`?',
            'options' => [
                'Buttons don\'t have click events',
                '`submit` fires both for the button and for pressing Enter in a field',
                '`click` doesn\'t work on forms',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'Listening for `submit` covers every way of sending the form.',
        ],
        [
            'question' => 'What happens if a `submit` handler doesn\'t call `event.preventDefault()`?',
            'options' => [
                'Nothing',
                'The browser sends the form as usual, which loads a new page and loses the page\'s current state',
                'The form is cleared',
                'An error is thrown',
            ],
            'correct_option' => 1,
            'explanation' => 'Handled in JavaScript, the default action is usually not wanted.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = `<form>
                    <input name="a" value="1">
                    <input type="checkbox" name="b" value="yes">
                    <input type="checkbox" name="c" value="on" checked>
                </form>`;
                console.log(JSON.stringify(Object.fromEntries(new FormData(document.querySelector('form')))));
                JS,
            'options' => ['`{"a":"1","c":"on"}`', '`{"a":"1","b":"","c":"on"}`', '`{"a":1,"c":true}`', '`{"a":"1","b":"yes","c":"on"}`'],
            'correct_option' => 0,
            'explanation' => 'Unchecked checkboxes aren\'t included, and every value is a string.',
        ],
        [
            'question' => 'How do you read all the ticked values of several checkboxes that share the name `extras`?',
            'options' => [
                '`formData.get(\'extras\')`',
                '`new FormData(form).getAll(\'extras\')`',
                '`form.elements.extras.value`',
                '`Object.fromEntries(new FormData(form)).extras`',
            ],
            'correct_option' => 1,
            'explanation' => '`get()` and `Object.fromEntries()` keep only one value per name.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                document.body.innerHTML = '<input id="e" type="email" required>';
                const e = document.querySelector('#e');
                const empty = e.checkValidity();
                e.value = 'amara@example.com';
                console.log(empty, e.checkValidity());
                JS,
            'options' => ['`false true`', '`true true`', '`false false`', '`true false`'],
            'correct_option' => 0,
            'explanation' => 'A required field fails while empty; a well-formed email address passes.',
        ],
        [
            'question' => 'What does `form.reset()` do?',
            'options' => [
                'Empties every field',
                'Puts every field back to the value it started with in the HTML',
                'Reloads the page',
                'Removes the form',
            ],
            'correct_option' => 1,
            'explanation' => 'A field with `value="draft"` goes back to "draft".',
        ],
        [
            'question' => 'Why link an error message to its field with `aria-describedby`?',
            'options' => [
                'It styles the message red',
                'Screen readers then read the message with the field, so everyone learns what\'s wrong',
                'It validates the field',
                'It is required for `checkValidity()`',
            ],
            'correct_option' => 1,
            'explanation' => 'Pair it with `aria-invalid="true"` on the field.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Read an order form',
            'prompt' => "Write `readOrder()`, returning `{ title, quantity, giftWrap, format }` from the form `#order`: `quantity` as a number, `giftWrap` as a boolean (the checkbox), and the others as strings. It reads the form's current values.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <form id="order">
                        <input name="title" value="Circe">
                        <input name="quantity" type="number" value="2">
                        <label><input name="giftWrap" type="checkbox"> Gift wrap</label>
                        <select name="format"><option>paperback</option><option>hardback</option></select>
                    </form>
                `;

                function readOrder() {
                    return Object.fromEntries(new FormData(document.querySelector('#order')));
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <form id="order">
                        <input name="title" value="Circe">
                        <input name="quantity" type="number" value="2">
                        <label><input name="giftWrap" type="checkbox"> Gift wrap</label>
                        <select name="format"><option>paperback</option><option>hardback</option></select>
                    </form>
                `;

                function readOrder() {
                    const { title, quantity, giftWrap, format } = document.querySelector('#order').elements;
                    return {
                        title: title.value,
                        quantity: Number(quantity.value),
                        giftWrap: giftWrap.checked,
                        format: format.value,
                    };
                }
                JS,
            'hint' => 'Use `form.elements` to reach each field: `Number()` for the quantity, `checked` for the checkbox.',
            'checks' => [
                ['label' => 'Starting values', 'code' => "return expectSame(readOrder(), { title: 'Circe', quantity: 2, giftWrap: false, format: 'paperback' });"],
                ['label' => 'After changes', 'code' => <<<'JS'
                    const { title, quantity, giftWrap, format } = document.querySelector('#order').elements;
                    title.value = 'Dune';
                    quantity.value = '5';
                    giftWrap.checked = true;
                    format.value = 'hardback';
                    return expectSame(readOrder(), { title: 'Dune', quantity: 5, giftWrap: true, format: 'hardback' });
                    JS],
            ],
        ],
        [
            'title' => 'Handle the submit',
            'prompt' => "When `#subscribe` is submitted, prevent the normal submission and, if the email field contains `@`, put `'Thanks! We\'ll write to EMAIL.'` in `#result` and clear the field; otherwise put `'Please enter your email address.'` there.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <form id="subscribe">
                        <input name="email" placeholder="you@example.com">
                        <button>Subscribe</button>
                    </form>
                    <p id="result"></p>
                `;

                document.querySelector('#subscribe button').addEventListener('click', () => {
                    document.querySelector('#result').textContent = 'Thanks!';
                });
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <form id="subscribe">
                        <input name="email" placeholder="you@example.com">
                        <button>Subscribe</button>
                    </form>
                    <p id="result"></p>
                `;

                const form = document.querySelector('#subscribe');
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const email = form.elements.email.value.trim();
                    const result = document.querySelector('#result');
                    if (email.includes('@')) {
                        result.textContent = `Thanks! We'll write to ${email}.`;
                        form.elements.email.value = '';
                    } else {
                        result.textContent = 'Please enter your email address.';
                    }
                });
                JS,
            'hint' => 'Listen for `submit` on the form, call `event.preventDefault()`, then read `form.elements.email.value`.',
            'checks' => [
                ['label' => 'A valid email', 'code' => <<<'JS'
                    const form = document.querySelector('#subscribe');
                    form.elements.email.value = 'amara@example.com';
                    const event = new Event('submit', { bubbles: true, cancelable: true });
                    form.dispatchEvent(event);
                    return expectSame([event.defaultPrevented, document.querySelector('#result').textContent, form.elements.email.value], [true, "Thanks! We'll write to amara@example.com.", '']);
                    JS],
                ['label' => 'No email', 'code' => <<<'JS'
                    const form = document.querySelector('#subscribe');
                    form.elements.email.value = 'not-an-address';
                    const event = new Event('submit', { bubbles: true, cancelable: true });
                    form.dispatchEvent(event);
                    return expectSame([event.defaultPrevented, document.querySelector('#result').textContent], [true, 'Please enter your email address.']);
                    JS],
            ],
        ],
        [
            'title' => 'Ticked toppings',
            'prompt' => "Write `chosenToppings()`, returning an array of the `value`s of the ticked `topping` checkboxes in `#pizza`, using `FormData`; and keep `#summary` showing e.g. `'2 toppings: olives, basil'` (or `'No toppings'`) whenever a box is ticked or unticked.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <form id="pizza">
                        <label><input type="checkbox" name="topping" value="olives"> Olives</label>
                        <label><input type="checkbox" name="topping" value="peppers"> Peppers</label>
                        <label><input type="checkbox" name="topping" value="basil"> Basil</label>
                    </form>
                    <p id="summary">No toppings</p>
                `;

                function chosenToppings() {
                    return [new FormData(document.querySelector('#pizza')).get('topping')];
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <form id="pizza">
                        <label><input type="checkbox" name="topping" value="olives"> Olives</label>
                        <label><input type="checkbox" name="topping" value="peppers"> Peppers</label>
                        <label><input type="checkbox" name="topping" value="basil"> Basil</label>
                    </form>
                    <p id="summary">No toppings</p>
                `;

                function chosenToppings() {
                    return new FormData(document.querySelector('#pizza')).getAll('topping');
                }

                document.querySelector('#pizza').addEventListener('change', () => {
                    const toppings = chosenToppings();
                    document.querySelector('#summary').textContent = toppings.length
                        ? `${toppings.length} topping${toppings.length === 1 ? '' : 's'}: ${toppings.join(', ')}`
                        : 'No toppings';
                });
                JS,
            'hint' => '`getAll(\'topping\')` gives every ticked value. Listen for `change` on the form to update the summary.',
            'checks' => [
                ['label' => 'Reads the ticked boxes', 'code' => <<<'JS'
                    const boxes = document.querySelectorAll('#pizza input');
                    const none = chosenToppings();
                    boxes[0].checked = true;
                    boxes[2].checked = true;
                    return expectSame([none, chosenToppings()], [[], ['olives', 'basil']]);
                    JS],
                ['label' => 'The summary follows', 'code' => <<<'JS'
                    const boxes = document.querySelectorAll('#pizza input');
                    boxes.forEach((box) => (box.checked = false));
                    const tick = (i, on) => {
                        boxes[i].checked = on;
                        boxes[i].dispatchEvent(new Event('change', { bubbles: true }));
                        return document.querySelector('#summary').textContent;
                    };
                    return expectSame([tick(1, true), tick(2, true), tick(1, false), tick(2, false)], ['1 topping: peppers', '2 toppings: peppers, basil', '1 topping: basil', 'No toppings']);
                    JS],
            ],
        ],
        [
            'title' => 'Validate a field',
            'prompt' => "Write `validateUsername()`, which checks `#username`: it must be 3 to 15 characters of letters, digits or underscores. Put a message in `#username-error` (empty when valid), set the field's `aria-invalid` to `'true'` or `'false'`, and return whether it's valid. Run it whenever the field changes (`input`).",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <label>Username <input id="username" aria-describedby="username-error"></label>
                    <p id="username-error"></p>
                `;

                function validateUsername() {
                    return document.querySelector('#username').value !== '';
                }
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <label>Username <input id="username" aria-describedby="username-error"></label>
                    <p id="username-error"></p>
                `;

                const username = document.querySelector('#username');

                function validateUsername() {
                    const value = username.value;
                    let message = '';
                    if (value.length < 3 || value.length > 15) {
                        message = 'Use 3 to 15 characters.';
                    } else if (!/^\w+$/.test(value)) {
                        message = 'Use only letters, digits and underscores.';
                    }
                    document.querySelector('#username-error').textContent = message;
                    username.setAttribute('aria-invalid', String(message !== ''));
                    return message === '';
                }

                username.addEventListener('input', validateUsername);
                JS,
            'hint' => 'Check the length, then `/^\w+$/` (letters, digits and underscores). Write the message, set `aria-invalid`, return the result.',
            'checks' => [
                ['label' => 'Valid and invalid names', 'code' => <<<'JS'
                    const input = document.querySelector('#username');
                    const check = (name) => {
                        input.value = name;
                        return [validateUsername(), input.getAttribute('aria-invalid'), document.querySelector('#username-error').textContent !== ''];
                    };
                    return expectSame(
                        [check('amara_34'), check('ab'), check('x'.repeat(16)), check('has space'), check('abc')],
                        [[true, 'false', false], [false, 'true', true], [false, 'true', true], [false, 'true', true], [true, 'false', false]],
                    );
                    JS],
                ['label' => 'Checks as you type', 'code' => <<<'JS'
                    const input = document.querySelector('#username');
                    input.value = 'no!';
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    return expectSame(input.getAttribute('aria-invalid'), 'true');
                    JS],
            ],
        ],
        [
            'title' => 'A live total',
            'prompt' => "Keep `#total` showing the order total, e.g. `'£25.97'`, as the quantities in `#basket` change. Each `<input>` has a `data-pence` price; the total is the sum of price × quantity, with empty or invalid quantities counting as 0. Listen once, on the form.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <form id="basket">
                        <label>Dune <input type="number" min="0" value="1" data-pence="999"></label>
                        <label>Emma <input type="number" min="0" value="0" data-pence="799"></label>
                        <label>Circe <input type="number" min="0" value="2" data-pence="1299"></label>
                    </form>
                    <p>Total: <output id="total">£35.97</output></p>
                `;
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <form id="basket">
                        <label>Dune <input type="number" min="0" value="1" data-pence="999"></label>
                        <label>Emma <input type="number" min="0" value="0" data-pence="799"></label>
                        <label>Circe <input type="number" min="0" value="2" data-pence="1299"></label>
                    </form>
                    <p>Total: <output id="total">£35.97</output></p>
                `;

                const basket = document.querySelector('#basket');
                basket.addEventListener('input', () => {
                    let pence = 0;
                    for (const input of basket.querySelectorAll('input')) {
                        const qty = Number(input.value);
                        pence += (Number.isFinite(qty) ? qty : 0) * Number(input.dataset.pence);
                    }
                    document.querySelector('#total').textContent = `£${(pence / 100).toFixed(2)}`;
                });
                JS,
            'hint' => 'One `input` listener on the form: add up `Number(input.value) * Number(input.dataset.pence)` for every input.',
            'checks' => [
                ['label' => 'Follows the quantities', 'code' => <<<'JS'
                    const inputs = document.querySelectorAll('#basket input');
                    const set = (i, value) => {
                        inputs[i].value = value;
                        inputs[i].dispatchEvent(new Event('input', { bubbles: true }));
                        return document.querySelector('#total').textContent;
                    };
                    return expectSame([set(1, '2'), set(0, ''), set(2, '0')], ['£51.95', '£41.96', '£15.98']);
                    JS],
                ['label' => 'Listens on the form', 'code' => "return /#basket['\"]\\)\\s*\\.addEventListener|basket\\.addEventListener/.test(learnerCode()) ? true : 'Add one input listener to the form.';"],
            ],
        ],
        [
            'title' => 'Sign up properly',
            'prompt' => "Handle `#signup`'s submit: prevent the default; if `form.checkValidity()` fails, put `'Please check the highlighted fields.'` in `#message`. Otherwise disable the button, put `'Welcome, NAME!'` in `#message` (NAME trimmed) and reset the form.",
            'starter_code' => <<<'JS'
                document.body.innerHTML = `
                    <form id="signup" novalidate>
                        <label>Name <input name="name" required minlength="2"></label>
                        <label>Age <input name="age" type="number" min="13" required></label>
                        <button>Sign up</button>
                    </form>
                    <p id="message" role="status"></p>
                `;
                JS,
            'solution' => <<<'JS'
                document.body.innerHTML = `
                    <form id="signup" novalidate>
                        <label>Name <input name="name" required minlength="2"></label>
                        <label>Age <input name="age" type="number" min="13" required></label>
                        <button>Sign up</button>
                    </form>
                    <p id="message" role="status"></p>
                `;

                const form = document.querySelector('#signup');
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const message = document.querySelector('#message');
                    if (!form.checkValidity()) {
                        message.textContent = 'Please check the highlighted fields.';
                        return;
                    }
                    form.querySelector('button').disabled = true;
                    message.textContent = `Welcome, ${form.elements.name.value.trim()}!`;
                    form.reset();
                });
                JS,
            'hint' => 'The `required`, `minlength` and `min` attributes already describe the rules; `form.checkValidity()` applies them.',
            'checks' => [
                ['label' => 'Invalid input is refused', 'code' => <<<'JS'
                    const form = document.querySelector('#signup');
                    form.elements.name.value = 'Ben';
                    form.elements.age.value = '9';
                    const event = new Event('submit', { cancelable: true });
                    form.dispatchEvent(event);
                    return expectSame([event.defaultPrevented, document.querySelector('#message').textContent, form.querySelector('button').disabled], [true, 'Please check the highlighted fields.', false]);
                    JS],
                ['label' => 'Valid input is accepted', 'code' => <<<'JS'
                    const form = document.querySelector('#signup');
                    form.elements.name.value = '  Amara ';
                    form.elements.age.value = '34';
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                    return expectSame([document.querySelector('#message').textContent, form.querySelector('button').disabled, form.elements.name.value], ['Welcome, Amara!', true, '']);
                    JS],
            ],
        ],
    ],
];
