<?php

// Lesson 2 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'comparisons-and-logic',
    'title' => 'Comparisons and logic',
    'summary' => 'Compare with === rather than ==, know which values are truthy and falsy, combine conditions with && and ||, and supply defaults with ?? and ?. safely.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        const quantity = '0';

        console.log(quantity == 0, quantity === 0);
        console.log(Boolean(quantity), Boolean(Number(quantity)));
        console.log(Number(quantity) || 'none', Number(quantity) ?? 'none');
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log(1 == '1', 1 === '1', null == undefined);
                JS,
            'options' => ['`true false true`', '`true true true`', '`false false false`', '`true false false`'],
            'correct_option' => 0,
            'explanation' => '`==` converts types first; `===` doesn\'t. `null == undefined` is a special case that\'s true.',
        ],
        [
            'question' => 'Which of these values is truthy?',
            'options' => ['`0`', "`''`", "`'0'`", '`NaN`'],
            'correct_option' => 2,
            'explanation' => 'Any non-empty string is truthy, even `\'0\'`. The other three are falsy.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('' || 'guest', 0 ?? 10, null ?? 10);
                JS,
            'options' => ['`guest 0 10`', '`guest 10 10`', '` 0 10`', '`guest 0 null`'],
            'correct_option' => 0,
            'explanation' => '`||` replaces any falsy value, so `\'\'` becomes `\'guest\'`. `??` only replaces `null` and `undefined`, so `0` stays.',
        ],
        [
            'question' => 'Why is `??` often better than `||` for defaults?',
            'options' => [
                'It is faster',
                'It only replaces `null` and `undefined`, so valid values like `0` and `\'\'` are kept',
                'It works with strings and `||` doesn\'t',
                'There is no difference',
            ],
            'correct_option' => 1,
            'explanation' => 'A volume of `0` is a real setting; `volume || 50` would throw it away.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const user = { name: 'Ben', pet: null };
                console.log(user.pet?.name, user.pet?.name ?? 'no pet');
                JS,
            'options' => ['`undefined no pet`', '`null no pet`', 'An error', '`no pet no pet`'],
            'correct_option' => 0,
            'explanation' => '`?.` stops at the `null` and gives `undefined`, which `??` then replaces.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                console.log('10' < '9', 10 < 9, 'b' > 'a');
                JS,
            'options' => ['`true false true`', '`false false true`', '`true true true`', '`false false false`'],
            'correct_option' => 0,
            'explanation' => 'Two strings compare as text, character by character, so `\'1\'` comes before `\'9\'`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const stock = 3;
                console.log(stock > 0 && `${stock} left`, stock > 5 && 'plenty');
                JS,
            'options' => ['`3 left false`', '`true false`', '`3 left plenty`', '`true plenty`'],
            'correct_option' => 0,
            'explanation' => '`&&` gives its right side when the left is truthy, and the left side (here `false`) when it\'s falsy.',
        ],
        [
            'question' => 'Why is `x === NaN` never true?',
            'options' => [
                'Because `NaN` is a string',
                'Because `NaN` isn\'t equal to anything, not even itself; use `Number.isNaN(x)` instead',
                'Because `===` doesn\'t work on numbers',
                'It is true when `x` is `NaN`',
            ],
            'correct_option' => 1,
            'explanation' => '`Object.is(x, NaN)` also works.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Defaults that keep zero',
            'prompt' => "Keep the first line. Set `volume` to `settings.volume`, or `50` if it's missing (`null` or `undefined`), and `theme` to `settings.theme`, or `'light'` if it's missing. A volume of `0` is a real setting and must be kept.",
            'starter_code' => <<<'JS'
                const settings = { volume: 0, theme: null };

                const volume = settings.volume || 50;
                const theme = settings.theme || 'light';
                JS,
            'solution' => <<<'JS'
                const settings = { volume: 0, theme: null };

                const volume = settings.volume ?? 50;
                const theme = settings.theme ?? 'light';
                JS,
            'hint' => '`||` replaces every falsy value, including `0`. Which operator only replaces `null` and `undefined`?',
            'checks' => [
                ['label' => 'Zero is kept', 'code' => "return expectSame([volume, theme], [0, 'light']);"],
                ['label' => 'Missing values get defaults; empty text is kept', 'code' => <<<'JS'
                    const variant = await runVariant(learnerCode().replace('{ volume: 0, theme: null }', "{ volume: undefined, theme: '' }"), ['volume', 'theme']);
                    return expectSame([variant.volume, variant.theme], [50, '']);
                    JS],
            ],
        ],
        [
            'title' => 'A city, if there is one',
            'prompt' => "Keep the first line. Set `city` to the city of the customer's address, or `'unknown'` when there's no address. The address may be `null`.",
            'starter_code' => <<<'JS'
                const customer = { name: 'Amara', address: null };

                const city = customer.address.city;
                JS,
            'solution' => <<<'JS'
                const customer = { name: 'Amara', address: null };

                const city = customer.address?.city ?? 'unknown';
                JS,
            'hint' => '`?.` gives `undefined` instead of failing, and `??` turns that into a default.',
            'checks' => [
                ['label' => 'No address', 'code' => "return expectSame(city, 'unknown');"],
                ['label' => 'With an address', 'code' => <<<'JS'
                    const variant = await runVariant(learnerCode().replace('address: null', "address: { city: 'Leeds' }"), ['city']);
                    return expectSame(variant.city, 'Leeds');
                    JS],
            ],
        ],
        [
            'title' => 'Ticket prices',
            'prompt' => 'Keep the first two lines. Set `price` (in pence) to `0` for children under 5, `600` for under-16s, otherwise `900` for members and `1200` for everyone else. Use the conditional operator `? :`.',
            'starter_code' => <<<'JS'
                const age = 12;
                const isMember = false;

                const price = 1200;
                JS,
            'solution' => <<<'JS'
                const age = 12;
                const isMember = false;

                const price = age < 5 ? 0 : age < 16 ? 600 : isMember ? 900 : 1200;
                JS,
            'hint' => 'Chain them from the youngest: `age < 5 ? 0 : age < 16 ? 600 : …`.',
            'checks' => [
                ['label' => 'A 12-year-old', 'code' => 'return expectSame(price, 600);'],
                ['label' => 'Everyone else', 'code' => <<<'JS'
                    const priceFor = async (age, member) =>
                        (await runVariant(learnerCode().replace('age = 12', `age = ${age}`).replace('isMember = false', `isMember = ${member}`), ['price'])).price;
                    return expectSame([await priceFor(4, true), await priceFor(5, false), await priceFor(15, true), await priceFor(16, true), await priceFor(40, false)], [0, 600, 600, 900, 1200]);
                    JS],
            ],
        ],
        [
            'title' => 'Which comes first?',
            'prompt' => "Keep the first two lines. Set `first` to whichever of `a` and `b` comes first alphabetically, ignoring upper and lower case: `'banana'` comes before `'Cherry'`.",
            'starter_code' => <<<'JS'
                const a = 'Cherry';
                const b = 'banana';

                const first = a < b ? a : b;
                JS,
            'solution' => <<<'JS'
                const a = 'Cherry';
                const b = 'banana';

                const first = a.toLowerCase() < b.toLowerCase() ? a : b;
                JS,
            'hint' => 'Upper-case letters sort before lower-case ones. Compare `a.toLowerCase()` with `b.toLowerCase()`, but give back the original word.',
            'checks' => [
                ['label' => 'Cherry and banana', 'code' => "return expectSame(first, 'banana');"],
                ['label' => 'Other pairs', 'code' => <<<'JS'
                    const pick = async (a, b) => (await runVariant(learnerCode().replace("'Cherry'", `'${a}'`).replace("'banana'", `'${b}'`), ['first'])).first;
                    return expectSame([await pick('apple', 'Zebra'), await pick('Zebra', 'apple'), await pick('Mango', 'melon')], ['apple', 'apple', 'Mango']);
                    JS],
            ],
        ],
        [
            'title' => 'Comfortable or extreme?',
            'prompt' => "Keep the first line. Set `isComfortable` to whether `temperature` is from 18 to 24 inclusive, and `isExtreme` to whether it's below 0 or above 35.",
            'starter_code' => <<<'JS'
                const temperature = 21;

                const isComfortable = false;
                const isExtreme = false;
                JS,
            'solution' => <<<'JS'
                const temperature = 21;

                const isComfortable = temperature >= 18 && temperature <= 24;
                const isExtreme = temperature < 0 || temperature > 35;
                JS,
            'hint' => 'Both ends of a range: `&&`. Either end of the scale: `||`.',
            'checks' => [
                ['label' => '21 degrees', 'code' => 'return expectSame([isComfortable, isExtreme], [true, false]);'],
                ['label' => 'The edges', 'code' => <<<'JS'
                    const at = async (t) => {
                        const v = await runVariant(learnerCode().replace('= 21', `= ${t}`), ['isComfortable', 'isExtreme']);
                        return [v.isComfortable, v.isExtreme];
                    };
                    return expectSame(
                        [await at(18), await at(24), await at(25), await at(0), await at(-1), await at(35), await at(36)],
                        [[true, false], [true, false], [false, false], [false, false], [false, true], [false, false], [false, true]],
                    );
                    JS],
            ],
        ],
        [
            'title' => 'A valid age?',
            'prompt' => "Keep the first line. Set `isValidAge` to whether `input` is a whole number from 0 to 150. Watch out: `Number('')` and `Number(' ')` are `0`, and an empty answer isn't a valid age.",
            'starter_code' => <<<'JS'
                const input = '42';

                const isValidAge = Number(input) >= 0;
                JS,
            'solution' => <<<'JS'
                const input = '42';

                const age = Number(input);
                const isValidAge = input.trim() !== '' && Number.isInteger(age) && age >= 0 && age <= 150;
                JS,
            'hint' => 'Combine four conditions with `&&`: not blank (`input.trim() !== \'\'`), `Number.isInteger()`, at least 0, at most 150.',
            'checks' => [
                ['label' => "'42'", 'code' => 'return expectSame(isValidAge, true);'],
                ['label' => 'Other answers', 'code' => <<<'JS'
                    const answers = ['0', '150', '', ' ', '42.5', '-1', '151', 'abc', ' 7 '];
                    const results = [];
                    for (const answer of answers) {
                        results.push((await runVariant(learnerCode().replace("'42'", `'${answer}'`), ['isValidAge'])).isValidAge);
                    }
                    return expectSame(results, [true, true, false, false, false, false, false, false, true]);
                    JS],
            ],
        ],
    ],
];
