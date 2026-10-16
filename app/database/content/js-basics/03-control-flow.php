<?php

// Lesson 3 of the JavaScript basics course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code, in the same scope; a check returns true or a
// message. Helpers: expectSame(), expectThrows(), learnerCode(), learnerOutput(), runVariant().

return [
    'slug' => 'control-flow',
    'title' => 'Control flow',
    'summary' => 'Make decisions with if and switch, and repeat work with for, while, do...while and for...of, using break and continue to control a loop.',
    'starter_code' => <<<'JS'
        // Change this code, then press Run (Ctrl+Enter).
        for (let hour = 6; hour <= 22; hour += 4) {
            let greeting;
            if (hour < 12) {
                greeting = 'Good morning';
            } else if (hour < 18) {
                greeting = 'Good afternoon';
            } else {
                greeting = 'Good evening';
            }
            console.log(`${hour}:00 ${greeting}`);
        }
        JS,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const n = 15;
                if (n > 10) {
                    console.log('big');
                } else if (n > 5) {
                    console.log('medium');
                } else {
                    console.log('small');
                }
                JS,
            'options' => ['`big`', '`big medium`', '`medium`', '`small`'],
            'correct_option' => 0,
            'explanation' => 'Only the first branch whose condition is true runs.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                const day = 'Sat';
                let kind = '';
                switch (day) {
                    case 'Sat':
                    case 'Sun':
                        kind += 'weekend ';
                    case 'Mon':
                        kind += 'monday';
                        break;
                    default:
                        kind = 'other';
                }
                console.log(kind);
                JS,
            'options' => ['`weekend monday`', '`weekend `', '`other`', '`monday`'],
            'correct_option' => 0,
            'explanation' => 'There\'s no `break` after the weekend line, so it falls through into the `Mon` case.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let out = '';
                for (let i = 0; i < 10; i += 3) {
                    out += i;
                }
                console.log(out);
                JS,
            'options' => ['`0369`', '`036`', '`0123456789`', '`18`'],
            'correct_option' => 0,
            'explanation' => '`i` is 0, 3, 6 and 9; adding a number to a string joins it as text.',
        ],
        [
            'question' => 'How many times does the body of a `do...while` loop run if its condition is false from the start?',
            'options' => ['Never', 'Once', 'Twice', 'Forever'],
            'correct_option' => 1,
            'explanation' => 'It checks the condition after each pass, so the body always runs at least once.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let found = '';
                for (const n of [3, 8, 5, 12, 7]) {
                    if (n > 10) break;
                    if (n % 2 === 1) continue;
                    found += n;
                }
                console.log(found);
                JS,
            'options' => ['`8`', '`812`', '`357`', '`8 12`'],
            'correct_option' => 0,
            'explanation' => 'Odd numbers are skipped, and the loop stops at 12, so only 8 is added.',
        ],
        [
            'question' => 'Why avoid `for...in` on arrays?',
            'options' => [
                'It doesn\'t work on arrays at all',
                'It goes through property names, so for an array you get the indexes, as strings, not the items',
                'It is slower than `for` in every case',
                'It changes the array',
            ],
            'correct_option' => 1,
            'explanation' => 'Use `for...of` for the items.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'JS'
                let n = 472;
                let sum = 0;
                while (n > 0) {
                    sum += n % 10;
                    n = Math.floor(n / 10);
                }
                console.log(sum);
                JS,
            'options' => ['`13`', '`472`', '`274`', '`7`'],
            'correct_option' => 0,
            'explanation' => 'It adds the digits: 2 + 7 + 4 = 13.',
        ],
        [
            'question' => 'What happens in the sandbox if a loop\'s condition never becomes false?',
            'options' => [
                'The page freezes forever',
                'It is stopped after two seconds with an error explaining why',
                'It runs 1000 times and then stops silently',
                'JavaScript refuses to start the loop',
            ],
            'correct_option' => 1,
            'explanation' => 'Outside the sandbox, an endless loop freezes the page; here it\'s caught for you.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'FizzBuzz',
            'prompt' => "Keep the first line. Set `result` to the numbers from 1 to `n` joined with `', '`, but with multiples of 3 replaced by `Fizz`, multiples of 5 by `Buzz`, and multiples of both by `FizzBuzz`. For `n = 5`: `'1, 2, Fizz, 4, Buzz'`.",
            'starter_code' => <<<'JS'
                const n = 15;

                let result = '';
                JS,
            'solution' => <<<'JS'
                const n = 15;

                let result = '';
                for (let i = 1; i <= n; i++) {
                    let word = String(i);
                    if (i % 15 === 0) {
                        word = 'FizzBuzz';
                    } else if (i % 3 === 0) {
                        word = 'Fizz';
                    } else if (i % 5 === 0) {
                        word = 'Buzz';
                    }
                    result += i === 1 ? word : `, ${word}`;
                }
                JS,
            'hint' => 'Check for 15 first, then 3, then 5. Add `\', \'` before every word except the first.',
            'checks' => [
                ['label' => 'Up to 15', 'code' => "return expectSame(result, '1, 2, Fizz, 4, Buzz, Fizz, 7, 8, Fizz, Buzz, 11, Fizz, 13, 14, FizzBuzz');"],
                ['label' => 'Up to 5 and up to 1', 'code' => <<<'JS'
                    const five = await runVariant(learnerCode().replace('n = 15', 'n = 5'), ['result']);
                    const one = await runVariant(learnerCode().replace('n = 15', 'n = 1'), ['result']);
                    return expectSame([five.result, one.result], ['1, 2, Fizz, 4, Buzz', '1']);
                    JS],
            ],
        ],
        [
            'title' => 'Add up the digits',
            'prompt' => 'Keep the first line. Set `digitSum` to the sum of the digits of `number` (4096 → 19), using a `while` loop with `% 10` and `Math.floor()`.',
            'starter_code' => <<<'JS'
                const number = 4096;

                let digitSum = 0;
                JS,
            'solution' => <<<'JS'
                const number = 4096;

                let digitSum = 0;
                let rest = number;
                while (rest > 0) {
                    digitSum += rest % 10;
                    rest = Math.floor(rest / 10);
                }
                JS,
            'hint' => 'Copy `number` into a `let` variable, then take off the last digit (`% 10`) and drop it (`Math.floor(x / 10)`) until nothing is left.',
            'checks' => [
                ['label' => '4096', 'code' => 'return expectSame(digitSum, 19);'],
                ['label' => 'Other numbers', 'code' => <<<'JS'
                    const sums = [];
                    for (const n of [0, 7, 99, 100001]) {
                        sums.push((await runVariant(learnerCode().replace('= 4096', `= ${n}`), ['digitSum'])).digitSum);
                    }
                    return expectSame(sums, [0, 7, 18, 2]);
                    JS],
                ['label' => 'Uses a while loop', 'code' => "return /while\\s*\\(/.test(learnerCode()) ? true : 'Use a while loop.';"],
            ],
        ],
        [
            'title' => 'Weekday or weekend',
            'prompt' => "Keep the first line. Use a `switch` to set `kind` to `'weekend'` for `'Sat'` and `'Sun'`, `'weekday'` for `'Mon'` to `'Fri'`, and `'unknown'` for anything else. Let cases that share a result fall through.",
            'starter_code' => <<<'JS'
                const day = 'Sat';

                let kind = 'unknown';
                JS,
            'solution' => <<<'JS'
                const day = 'Sat';

                let kind;
                switch (day) {
                    case 'Sat':
                    case 'Sun':
                        kind = 'weekend';
                        break;
                    case 'Mon':
                    case 'Tue':
                    case 'Wed':
                    case 'Thu':
                    case 'Fri':
                        kind = 'weekday';
                        break;
                    default:
                        kind = 'unknown';
                }
                JS,
            'hint' => 'Stack the cases with nothing between them, then one line and a `break` for each group, and a `default`.',
            'checks' => [
                ['label' => 'Every day', 'code' => <<<'JS'
                    const kinds = [];
                    for (const d of ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Funday']) {
                        kinds.push((await runVariant(learnerCode().replace("day = 'Sat'", `day = '${d}'`), ['kind'])).kind);
                    }
                    return expectSame(kinds, ['weekday', 'weekday', 'weekday', 'weekday', 'weekday', 'weekend', 'weekend', 'unknown']);
                    JS],
                ['label' => 'Uses switch', 'code' => "return /switch\\s*\\(/.test(learnerCode()) ? true : 'Use a switch.';"],
            ],
        ],
        [
            'title' => 'Count the vowels',
            'prompt' => 'Keep the first line. Set `vowels` to how many of the letters in `text` are vowels (a, e, i, o or u, in either case), going through it with `for...of`.',
            'starter_code' => <<<'JS'
                const text = 'The Left Hand of Darkness';

                let vowels = 0;
                JS,
            'solution' => <<<'JS'
                const text = 'The Left Hand of Darkness';

                let vowels = 0;
                for (const letter of text.toLowerCase()) {
                    if ('aeiou'.includes(letter)) {
                        vowels++;
                    }
                }
                JS,
            'hint' => 'Lower-case the text first, then for each letter check `\'aeiou\'.includes(letter)`.',
            'checks' => [
                ['label' => 'The Left Hand of Darkness', 'code' => 'return expectSame(vowels, 6);'],
                ['label' => 'Other text', 'code' => <<<'JS'
                    const count = async (t) => (await runVariant(learnerCode().replace("'The Left Hand of Darkness'", `'${t}'`), ['vowels'])).vowels;
                    return expectSame([await count('AEIOU aeiou'), await count('rhythm'), await count('')], [10, 0, 0]);
                    JS],
            ],
        ],
        [
            'title' => 'The first repeated letter',
            'prompt' => "Keep the first line. Set `repeated` to the first letter of `word` that has already appeared earlier in it (`'programming'` → `'r'`), or `null` if no letter repeats. Stop looking as soon as you find one.",
            'starter_code' => <<<'JS'
                const word = 'programming';

                let repeated = null;
                JS,
            'solution' => <<<'JS'
                const word = 'programming';

                let repeated = null;
                for (let i = 1; i < word.length; i++) {
                    if (word.slice(0, i).includes(word[i])) {
                        repeated = word[i];
                        break;
                    }
                }
                JS,
            'hint' => 'For each position, check whether the letter is in `word.slice(0, i)`, the part before it. `break` when it is.',
            'checks' => [
                ['label' => 'programming', 'code' => "return expectSame(repeated, 'r');"],
                ['label' => 'Other words', 'code' => <<<'JS'
                    const find = async (w) => (await runVariant(learnerCode().replace("'programming'", `'${w}'`), ['repeated'])).repeated;
                    return expectSame([await find('abcdeb'), await find('abc'), await find('aa'), await find('')], ['b', null, 'a', null]);
                    JS],
            ],
        ],
        [
            'title' => 'Collatz steps',
            'prompt' => "Keep the first line. Starting from `start`, repeatedly halve the number if it's even, or multiply it by 3 and add 1 if it's odd, until it reaches 1. Set `steps` to how many steps that takes (6 takes 8 steps; 1 takes 0).",
            'starter_code' => <<<'JS'
                const start = 6;

                let steps = 0;
                JS,
            'solution' => <<<'JS'
                const start = 6;

                let steps = 0;
                let n = start;
                while (n !== 1) {
                    n = n % 2 === 0 ? n / 2 : n * 3 + 1;
                    steps++;
                }
                JS,
            'hint' => 'A `while (n !== 1)` loop, with a `let n = start` to change, and a counter.',
            'checks' => [
                ['label' => 'From 6', 'code' => 'return expectSame(steps, 8);'],
                ['label' => 'From 1, 7 and 27', 'code' => <<<'JS'
                    const from = async (s) => (await runVariant(learnerCode().replace('start = 6', `start = ${s}`), ['steps'])).steps;
                    return expectSame([await from(1), await from(7), await from(27)], [0, 16, 111]);
                    JS],
            ],
        ],
    ],
];
