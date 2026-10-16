<?php

// Lesson 3 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'recursion',
    'title' => 'Recursion',
    'summary' => 'Solve problems with smaller versions of themselves: base cases, the call stack, recursion on nested data, memoization, and generating every combination.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function factorial(int $n): int
        {
            return $n <= 1 ? 1 : $n * factorial($n - 1);
        }

        foreach ([0, 1, 5, 10] as $n) {
            echo "$n! = " . factorial($n) . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What two parts does every recursive function need?',
            'options' => [
                'A loop and a return value',
                'A base case answered directly, and a recursive case that makes the problem smaller',
                'Two parameters',
                'A global variable and a counter',
            ],
            'correct_option' => 1,
            'explanation' => 'Without a base case, or without getting smaller, the recursion never stops.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function sum(int $n): int
                {
                    return $n === 0 ? 0 : $n + sum($n - 1);
                }

                echo sum(5);
                PHP,
            'options' => ['`5`', '`10`', '`15`', '`120`'],
            'correct_option' => 2,
            'explanation' => '5 + 4 + 3 + 2 + 1 + 0 = 15.',
        ],
        [
            'question' => 'What happens to a recursive function with no base case?',
            'options' => [
                'It returns `null`',
                'It keeps calling itself until the program runs out of memory or is stopped',
                'PHP stops it after 100 calls',
                'It runs once',
            ],
            'correct_option' => 1,
            'explanation' => 'Every unfinished call takes space on the call stack, and nothing ever finishes.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function depth(array $items): int
                {
                    $deepest = 0;
                    foreach ($items as $item) {
                        if (is_array($item)) {
                            $deepest = max($deepest, depth($item));
                        }
                    }
                    return 1 + $deepest;
                }

                echo depth([1, [2, [3]], [4]]);
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`4`'],
            'correct_option' => 2,
            'explanation' => 'The outer list is depth 1, `[2, [3]]` adds one, and `[3]` adds one more.',
        ],
        [
            'question' => 'Why is the plain recursive Fibonacci function so slow?',
            'options' => [
                'Recursion is always slow in PHP',
                'It calculates the same values again and again, so the work roughly doubles with each extra n',
                'It uses floating-point numbers',
                'It has no base case',
            ],
            'correct_option' => 1,
            'explanation' => '`fib(n)` calls `fib(n−1)` and `fib(n−2)`, which each call the same smaller values repeatedly: O(2ⁿ).',
        ],
        [
            'question' => 'What does memoization do?',
            'options' => [
                'Turns recursion into a loop',
                'Remembers each answer the first time it\'s worked out, so it\'s never calculated twice',
                'Frees memory after each call',
                'Sorts the input first',
            ],
            'correct_option' => 1,
            'explanation' => 'With a cache, Fibonacci goes from O(2ⁿ) to O(n).',
        ],
        [
            'question' => 'How many permutations do 4 different items have?',
            'options' => ['4', '8', '16', '24'],
            'correct_option' => 3,
            'explanation' => '4! = 4 × 3 × 2 × 1 = 24. The count grows very quickly.',
        ],
        [
            'question' => 'When is recursion usually the clearer choice?',
            'options' => [
                'Adding up a flat list of numbers',
                'Working with trees or nested data, where each part contains smaller parts of the same shape',
                'Printing the numbers 1 to 10',
                'Never; loops are always clearer',
            ],
            'correct_option' => 1,
            'explanation' => 'The function\'s shape mirrors the data\'s shape. Simple repetition reads better as a loop.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Power, recursively',
            'prompt' => 'Write a recursive function `power(int $base, int $exponent): int` (exponent 0 or more) without using `**` or `pow()`. Any number to the power 0 is 1.',
            'starter_code' => <<<'PHP'
                <?php

                function power(int $base, int $exponent): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function power(int $base, int $exponent): int
                {
                    if ($exponent === 0) {
                        return 1;
                    }

                    return $base * power($base, $exponent - 1);
                }
                PHP,
            'hint' => 'Base case: exponent 0 gives 1. Otherwise `$base * power($base, $exponent - 1)`.',
            'checks' => [
                ['label' => 'Powers', 'code' => 'return expect_same([power(2, 10), power(5, 3), power(7, 0), power(-2, 3)], [1024, 125, 1, -8]);'],
                ['label' => 'Recursive, without ** or pow()', 'code' => "\$code = learnp_code();\nif (str_contains(\$code, '**') || str_contains(\$code, 'pow(')) return 'Don\\'t use ** or pow().';\nreturn preg_match_all('/\\\\bpower\\\\s*\\\\(/', \$code) >= 2 ?: 'power() should call itself.';"],
            ],
        ],
        [
            'title' => 'Reverse a string',
            'prompt' => 'Write a recursive function `reverseText(string $text): string` without `strrev()` or loops: the reverse of a text is the reverse of everything after its first character, followed by that first character.',
            'starter_code' => <<<'PHP'
                <?php

                function reverseText(string $text): string
                {
                    return $text;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function reverseText(string $text): string
                {
                    if (strlen($text) <= 1) {
                        return $text;
                    }

                    return reverseText(substr($text, 1)) . $text[0];
                }
                PHP,
            'hint' => 'Base case: a text of 0 or 1 characters is its own reverse. Otherwise `reverseText(substr($text, 1)) . $text[0]`.',
            'checks' => [
                ['label' => 'Reverses text', 'code' => "return expect_same([reverseText('Dune'), reverseText('a'), reverseText('')], ['enuD', 'a', '']);"],
                ['label' => 'No strrev() or loops', 'code' => "return ! preg_match('/\\\\b(strrev|for|foreach|while)\\\\b/', learnp_code()) ?: 'Use recursion, without strrev() or loops.';"],
            ],
        ],
        [
            'title' => 'Count the files',
            'prompt' => 'A folder is an array whose values are either file sizes (ints) or folders (arrays). Write a recursive function `folderSize(array $folder): int` returning the total size of every file at any depth.',
            'starter_code' => <<<'PHP'
                <?php

                function folderSize(array $folder): int
                {
                    return array_sum($folder);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function folderSize(array $folder): int
                {
                    $total = 0;
                    foreach ($folder as $entry) {
                        $total += is_array($entry) ? folderSize($entry) : $entry;
                    }

                    return $total;
                }
                PHP,
            'hint' => 'Loop over the entries: add an int directly, and call `folderSize()` for an array.',
            'checks' => [
                ['label' => 'Nested folders', 'code' => "return expect_same(folderSize(['notes.txt' => 120, 'photos' => ['a.jpg' => 3000, 'b.jpg' => 2500, 'old' => ['c.jpg' => 1000]], 'empty' => []]), 6620);"],
                ['label' => 'An empty folder', 'code' => 'return expect_same(folderSize([]), 0);'],
            ],
        ],
        [
            'title' => 'Fast Fibonacci',
            'prompt' => 'Write `fib(int $n): int` (with `fib(0) = 0` and `fib(1) = 1`) recursively, with memoization, so that `fib(80)` returns instantly. A static array inside the function makes a good cache.',
            'starter_code' => <<<'PHP'
                <?php

                function fib(int $n): int
                {
                    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function fib(int $n): int
                {
                    static $memo = [];

                    if ($n < 2) {
                        return $n;
                    }

                    return $memo[$n] ??= fib($n - 1) + fib($n - 2);
                }
                PHP,
            'hint' => '`static $memo = [];` keeps its value between calls. Return `$memo[$n] ??= fib($n - 1) + fib($n - 2);`.',
            'checks' => [
                ['label' => 'Small values', 'code' => 'return expect_same(array_map(\'fib\', [0, 1, 2, 3, 10, 20]), [0, 1, 1, 2, 55, 6765]);'],
                ['label' => 'fib(80), quickly', 'code' => <<<'PHP'
                    // Time a medium call first, so an un-memoized fib() fails fast instead of running for ever.
                    $start = microtime(true);
                    fib(28);
                    if (microtime(true) - $start > 0.02) return 'fib(28) was slow, so fib(80) would take far too long. Cache each answer as it\'s worked out.';
                    return expect_same(fib(80), 23416728348467685);
                    PHP],
            ],
        ],
        [
            'title' => 'Every subset',
            'prompt' => "Write a recursive function `subsets(array \$items): array` returning every subset of the items (including the empty one), as lists in the original order. For `['A', 'B']` the subsets are `[]`, `['B']`, `['A']` and `['A', 'B']`; their order in the result doesn't matter.",
            'starter_code' => <<<'PHP'
                <?php

                function subsets(array $items): array
                {
                    return [$items];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function subsets(array $items): array
                {
                    if ($items === []) {
                        return [[]];
                    }

                    $first = array_shift($items);
                    $withoutFirst = subsets($items);
                    $withFirst = array_map(fn (array $subset) => [$first, ...$subset], $withoutFirst);

                    return [...$withoutFirst, ...$withFirst];
                }
                PHP,
            'hint' => 'The empty list has one subset, `[[]]`. Otherwise take the first item off: the subsets are those of the rest, plus each of those with the first item added at the front.',
            'checks' => [
                ['label' => 'Subsets of two items', 'code' => "\$result = array_map(fn (\$s) => implode('', \$s), subsets(['A', 'B']));\nsort(\$result);\nreturn expect_same(\$result, ['', 'A', 'AB', 'B']);"],
                ['label' => 'Subsets of three items, in order', 'code' => "\$result = array_map(fn (\$s) => implode('', \$s), subsets(['A', 'B', 'C']));\nsort(\$result);\nreturn expect_same(\$result, ['', 'A', 'AB', 'ABC', 'AC', 'B', 'BC', 'C']);"],
                ['label' => 'The empty list has one subset', 'code' => 'return expect_same(subsets([]), [[]]);'],
            ],
        ],
        [
            'title' => 'Climbing the stairs',
            'prompt' => 'You can climb 1 or 2 steps at a time. Write `ways(int $steps): int` returning how many different ways there are to climb `$steps` steps (1 way for 0 steps, 1 for 1 step, 2 for 2 steps…). Use recursion with memoization, so `ways(60)` is instant.',
            'starter_code' => <<<'PHP'
                <?php

                function ways(int $steps): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function ways(int $steps, array &$memo = []): int
                {
                    if ($steps <= 1) {
                        return 1;
                    }

                    return $memo[$steps] ??= ways($steps - 1, $memo) + ways($steps - 2, $memo);
                }
                PHP,
            'hint' => 'The last move was either 1 step or 2, so `ways(n) = ways(n - 1) + ways(n - 2)`. Cache the answers as in the lesson.',
            'checks' => [
                ['label' => 'Small staircases', 'code' => 'return expect_same(array_map(fn ($n) => ways($n), [0, 1, 2, 3, 4, 5]), [1, 1, 2, 3, 5, 8]);'],
                ['label' => '60 steps, quickly', 'code' => <<<'PHP'
                    $start = microtime(true);
                    ways(25);
                    if (microtime(true) - $start > 0.02) return 'ways(25) was slow, so ways(60) would take far too long. Remember each answer as it\'s worked out.';
                    return expect_same(ways(60), 2504730781961);
                    PHP],
            ],
        ],
    ],
];
