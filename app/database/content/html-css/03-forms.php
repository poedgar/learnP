<?php

// Lesson 3 of the HTML & CSS course. The theory is in the .md file with the same name.
// The learner's code is the page. Checks are JavaScript run in that page; they return true or a
// message. Helpers: expectSame(), styleOf(selector, property), boxOf(selector), learnerCode().

return [
    'slug' => 'forms',
    'title' => 'Forms',
    'summary' => 'Build forms that work for everyone: labelled fields, the right input types, radio buttons, checkboxes and selects grouped with fieldsets, built-in validation, and buttons.',
    'starter_code' => <<<'HTML'
        <!-- Change this page; it updates as you type. Try submitting it empty. -->
        <form>
            <label for="name">Name</label>
            <input id="name" name="name" required>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>
            <button>Join the club</button>
        </form>
        HTML,

    'quiz' => [
        [
            'question' => 'Why isn\'t a placeholder enough to label a field?',
            'options' => [
                'Placeholders aren\'t allowed',
                'It disappears when you start typing, and isn\'t reliably read as the field\'s name; use a label',
                'It\'s too small',
                'It is enough',
            ],
            'correct_option' => 1,
            'explanation' => 'A real `label` stays visible, and clicking it focuses the field.',
        ],
        [
            'question' => 'How does `<label for="email">` find its field?',
            'options' => [
                'By the field\'s `name`',
                'By the field\'s `id`, which must be `email`',
                'By the next element',
                'By the field\'s type',
            ],
            'correct_option' => 1,
            'explanation' => 'Alternatively, put the field inside the label.',
        ],
        [
            'question' => 'What does `type="email"` give you?',
            'options' => [
                'Nothing beyond a text box',
                'A check that the value looks like an email address, and an email keyboard on phones',
                'An email is sent',
                'The field becomes required',
            ],
            'correct_option' => 1,
            'explanation' => 'Add `required` if it mustn\'t be empty.',
        ],
        [
            'question' => 'What makes radio buttons a group where only one can be chosen?',
            'options' => ['Being in the same paragraph', 'Sharing the same `name`', 'Sharing the same `id`', 'Being inside a `select`'],
            'correct_option' => 1,
            'explanation' => 'Each still needs its own `value`.',
        ],
        [
            'question' => 'What is a `fieldset` with a `legend` for?',
            'options' => [
                'Styling only',
                'Grouping related fields and naming the group, so screen readers know what a set of radio buttons chooses',
                'Hiding fields',
                'Validation',
            ],
            'correct_option' => 1,
            'explanation' => 'The legend is read before each option in the group.',
        ],
        [
            'question' => 'Why must the server check data that the form already validated?',
            'options' => [
                'It doesn\'t need to',
                'Anyone can send a request without using the form, or with its checks removed',
                'Browsers don\'t validate',
                'For speed',
            ],
            'correct_option' => 1,
            'explanation' => 'Browser validation is for convenience, not security.',
        ],
        [
            'question' => 'What does a `button` inside a form do when clicked, if it has no `type`?',
            'options' => ['Nothing', 'Submits the form', 'Resets the form', 'Runs JavaScript'],
            'correct_option' => 1,
            'explanation' => 'Use `type="button"` for buttons that shouldn\'t submit.',
        ],
        [
            'question' => 'When should a form use `method="get"` rather than `post`?',
            'options' => [
                'Always',
                'For things like searches, where the values can safely go in the address; use post for anything that changes data',
                'For passwords',
                'For file uploads',
            ],
            'correct_option' => 1,
            'explanation' => 'GET requests can be bookmarked and shared; that\'s fine for searches.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Label every field',
            'prompt' => 'Give each field a `label` connected to it with `for` and `id`: `First name` for the first, `Email` for the second. Keep the `name`s as they are, and remove the placeholders.',
            'starter_code' => <<<'HTML'
                <form>
                    <input name="first" placeholder="First name">
                    <input name="email" placeholder="Email">
                    <button>Send</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <label for="first">First name</label>
                    <input id="first" name="first">
                    <label for="email">Email</label>
                    <input id="email" name="email">
                    <button>Send</button>
                </form>
                HTML,
            'hint' => 'Add an `id` to each input and a `<label for="that-id">` before it.',
            'checks' => [
                ['label' => 'Labels are connected', 'code' => <<<'JS'
                    const label = (name) => [...document.querySelector(`[name="${name}"]`)?.labels ?? []].map((l) => l.textContent.trim());
                    return expectSame([label('first'), label('email')], [['First name'], ['Email']]);
                    JS],
                ['label' => 'No placeholders', 'code' => "return expectSame(document.querySelectorAll('[placeholder]').length, 0);"],
            ],
        ],
        [
            'title' => 'The right types',
            'prompt' => 'Give each field the right `type`: `email` for the email address, `tel` for the phone number, `number` for the number of tickets (from 1 to 6), and `date` for the date. Make the email and the date required.',
            'starter_code' => <<<'HTML'
                <form>
                    <label>Email <input name="email"></label>
                    <label>Phone <input name="phone"></label>
                    <label>Tickets <input name="tickets"></label>
                    <label>Date <input name="date"></label>
                    <button>Book</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <label>Email <input name="email" type="email" required></label>
                    <label>Phone <input name="phone" type="tel"></label>
                    <label>Tickets <input name="tickets" type="number" min="1" max="6"></label>
                    <label>Date <input name="date" type="date" required></label>
                    <button>Book</button>
                </form>
                HTML,
            'hint' => '`type="email"`, `type="tel"`, `type="number" min="1" max="6"` and `type="date"`, plus `required` on two of them.',
            'checks' => [
                ['label' => 'Types', 'code' => <<<'JS'
                    return expectSame(['email', 'phone', 'tickets', 'date'].map((n) => document.querySelector(`[name="${n}"]`)?.type), ['email', 'tel', 'number', 'date']);
                    JS],
                ['label' => 'Ticket limits', 'code' => "const t = document.querySelector('[name=\"tickets\"]');\nreturn expectSame([t?.min, t?.max], ['1', '6']);"],
                ['label' => 'Required fields', 'code' => <<<'JS'
                    return expectSame(['email', 'phone', 'tickets', 'date'].map((n) => document.querySelector(`[name="${n}"]`)?.required), [true, false, false, true]);
                    JS],
            ],
        ],
        [
            'title' => 'One choice, in a group',
            'prompt' => 'Replace the text field with a `fieldset` whose `legend` is `Delivery`, containing three radio buttons named `delivery` with the values `standard`, `express` and `collect`, each inside its own label (`Standard`, `Express`, `Collect in store`). `standard` should be chosen to begin with.',
            'starter_code' => <<<'HTML'
                <form>
                    <label>Delivery (standard, express or collect) <input name="delivery"></label>
                    <button>Continue</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <fieldset>
                        <legend>Delivery</legend>
                        <label><input type="radio" name="delivery" value="standard" checked> Standard</label>
                        <label><input type="radio" name="delivery" value="express"> Express</label>
                        <label><input type="radio" name="delivery" value="collect"> Collect in store</label>
                    </fieldset>
                    <button>Continue</button>
                </form>
                HTML,
            'hint' => 'All three radio inputs share `name="delivery"`; `checked` chooses one to start with.',
            'checks' => [
                ['label' => 'A named group', 'code' => "return expectSame(document.querySelector('fieldset legend')?.textContent.trim(), 'Delivery');"],
                ['label' => 'Three options', 'code' => "const radios = [...document.querySelectorAll('fieldset input[type=\"radio\"][name=\"delivery\"]')];\nreturn expectSame(radios.map((r) => [r.value, r.labels[0]?.textContent.trim()]), [['standard', 'Standard'], ['express', 'Express'], ['collect', 'Collect in store']]);"],
                ['label' => 'Standard to begin with', 'code' => "return expectSame(new FormData(document.querySelector('form')).get('delivery'), 'standard');"],
            ],
        ],
        [
            'title' => 'A drop-down and notes',
            'prompt' => 'Add a `select` named `branch`, labelled `Branch`, with options `Central` (value `central`), `Riverside` (`riverside`) and `Hilltop` (`hilltop`), with Riverside chosen to begin with; and a `textarea` named `notes`, labelled `Notes`, 4 rows high and limited to 200 characters.',
            'starter_code' => <<<'HTML'
                <form>
                    <button>Reserve</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <label for="branch">Branch</label>
                    <select id="branch" name="branch">
                        <option value="central">Central</option>
                        <option value="riverside" selected>Riverside</option>
                        <option value="hilltop">Hilltop</option>
                    </select>
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" maxlength="200"></textarea>
                    <button>Reserve</button>
                </form>
                HTML,
            'hint' => '`selected` on an `option` chooses it to begin with; `rows` and `maxlength` go on the `textarea`.',
            'checks' => [
                ['label' => 'The select', 'code' => "const select = document.querySelector('select[name=\"branch\"]');\nreturn expectSame([select?.labels[0]?.textContent.trim(), [...(select?.options ?? [])].map((o) => [o.value, o.textContent.trim()]), select?.value], ['Branch', [['central', 'Central'], ['riverside', 'Riverside'], ['hilltop', 'Hilltop']], 'riverside']);"],
                ['label' => 'The notes', 'code' => "const notes = document.querySelector('textarea[name=\"notes\"]');\nreturn expectSame([notes?.labels[0]?.textContent.trim(), notes?.rows, notes?.maxLength], ['Notes', 4, 200]);"],
            ],
        ],
        [
            'title' => 'Validation rules',
            'prompt' => 'Add validation attributes so the browser enforces: the name is required and at least 2 characters; the membership number is required and is `M` followed by exactly 6 digits (use `pattern`); the age is a number from 16 to 120.',
            'starter_code' => <<<'HTML'
                <form>
                    <label>Name <input name="name"></label>
                    <label>Membership number <input name="member"></label>
                    <label>Age <input name="age"></label>
                    <button>Renew</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <label>Name <input name="name" required minlength="2"></label>
                    <label>Membership number <input name="member" required pattern="M\d{6}"></label>
                    <label>Age <input name="age" type="number" min="16" max="120"></label>
                    <button>Renew</button>
                </form>
                HTML,
            'hint' => '`required minlength="2"`, `required pattern="M\d{6}"` (the pattern must match the whole value), and `type="number" min="16" max="120"`.',
            'checks' => [
                ['label' => 'The name', 'code' => <<<'JS'
                    // Browsers only enforce minlength on what a person typed, so check the attribute itself.
                    const name = document.querySelector('[name="name"]');
                    name.value = '';
                    return expectSame([name.required, name.minLength, name.checkValidity()], [true, 2, false]);
                    JS],
                ['label' => 'The membership number', 'code' => <<<'JS'
                    const member = document.querySelector('[name="member"]');
                    const valid = (v) => { member.value = v; return member.checkValidity(); };
                    return expectSame([valid('M123456'), valid(''), valid('M12345'), valid('X123456'), valid('M1234567')], [true, false, false, false, false]);
                    JS],
                ['label' => 'The age', 'code' => <<<'JS'
                    const age = document.querySelector('[name="age"]');
                    const valid = (v) => { age.value = v; return age.checkValidity(); };
                    return expectSame([age.type, valid('16'), valid('15'), valid('121'), valid('')], ['number', true, false, false, true]);
                    JS],
            ],
        ],
        [
            'title' => 'The right buttons',
            'prompt' => 'Make the buttons behave: `Save` submits the form, `Start again` resets it, and `Show preview` does neither (it would run JavaScript). Then link the hint paragraph to the summary field with `aria-describedby`.',
            'starter_code' => <<<'HTML'
                <form>
                    <label for="summary">Summary</label>
                    <textarea id="summary" name="summary"></textarea>
                    <p id="summary-hint">One or two sentences, no spoilers.</p>
                    <button>Show preview</button>
                    <button>Start again</button>
                    <button>Save</button>
                </form>
                HTML,
            'solution' => <<<'HTML'
                <form>
                    <label for="summary">Summary</label>
                    <textarea id="summary" name="summary" aria-describedby="summary-hint"></textarea>
                    <p id="summary-hint">One or two sentences, no spoilers.</p>
                    <button type="button">Show preview</button>
                    <button type="reset">Start again</button>
                    <button type="submit">Save</button>
                </form>
                HTML,
            'hint' => 'Give each button a `type`: `button`, `reset` or `submit`. `aria-describedby` takes the hint\'s `id`.',
            'checks' => [
                ['label' => 'Button types', 'code' => "const type = (text) => [...document.querySelectorAll('button')].find((b) => b.textContent.trim() === text)?.type;\nreturn expectSame([type('Save'), type('Start again'), type('Show preview')], ['submit', 'reset', 'button']);"],
                ['label' => 'The hint is linked', 'code' => "return expectSame(document.querySelector('#summary')?.getAttribute('aria-describedby'), 'summary-hint');"],
            ],
        ],
    ],
];
