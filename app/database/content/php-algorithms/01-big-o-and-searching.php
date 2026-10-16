<?php

// Lesson 1 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'big-o-and-searching',
    'title' => 'Big-O and searching',
    'summary' => 'Measure algorithms by how their steps grow with Big-O notation, spot quadratic loops, and search with linear and binary search.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function binarySearch(array $sorted, int $target): int
        {
            [$low, $high] = [0, count($sorted) - 1];
            while ($low <= $high) {
                $middle = intdiv($low + $high, 2);
                if ($sorted[$middle] === $target) {
                    return $middle;
                }
                $sorted[$middle] < $target ? $low = $middle + 1 : $high = $middle - 1;
            }
            return -1;
        }

        echo binarySearch([3, 8, 15, 23, 42, 57], 23);
        PHP,

    'quiz' => [
        [
            'question' => 'What does Big-O notation describe?',
            'options' => [
                'The exact running time in seconds',
                'How an algorithm\'s steps (or memory) grow as its input grows',
                'How many lines of code an algorithm has',
                'Which programming language is fastest',
            ],
            'correct_option' => 1,
            'explanation' => 'It ignores constant factors and machines, and focuses on the shape of growth.',
        ],
        [
            'question' => 'What is the Big-O of reading `$prices[\'Dune\']` from a PHP array?',
            'options' => ['O(1)', 'O(log n)', 'O(n)', 'O(n²)'],
            'correct_option' => 0,
            'explanation' => 'PHP arrays are hash maps, so a key lookup takes about the same time however big the array is.',
        ],
        [
            'question' => 'What is the Big-O of this function, for `n` titles?',
            'code' => <<<'PHP'
                function pairs(array $titles): int
                {
                    $count = 0;
                    foreach ($titles as $a) {
                        foreach ($titles as $b) {
                            $count++;
                        }
                    }
                    return $count;
                }

                echo pairs(['Dune', 'Emma', 'Circe']);
                PHP,
            'options' => ['O(1)', 'O(n)', 'O(n²)', 'O(log n)'],
            'correct_option' => 2,
            'explanation' => 'For each of the n titles, the inner loop runs n times: n × n steps. It prints 9 for 3 titles.',
        ],
        [
            'question' => 'What must be true of a list before you can use binary search on it?',
            'options' => ['It must be short', 'It must be sorted', 'It must contain only strings', 'Nothing'],
            'correct_option' => 1,
            'explanation' => 'Binary search throws away half the list each step, which only works if the order tells it which half to keep.',
        ],
        [
            'question' => 'About how many steps does binary search need for a sorted list of a million items?',
            'options' => ['About 20', 'About 1,000', 'About 500,000', 'About a million'],
            'correct_option' => 0,
            'explanation' => 'Each step halves the list; 2²⁰ is just over a million.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $sorted = [10, 20, 30, 40, 50, 60, 70];
                [$low, $high, $steps] = [0, 6, 0];
                while ($low <= $high) {
                    $steps++;
                    $middle = intdiv($low + $high, 2);
                    if ($sorted[$middle] === 60) {
                        break;
                    }
                    $sorted[$middle] < 60 ? $low = $middle + 1 : $high = $middle - 1;
                }
                echo $steps;
                PHP,
            'options' => ['`1`', '`2`', '`3`', '`6`'],
            'correct_option' => 1,
            'explanation' => 'Middle index 3 holds 40, too small; the right half 4–6 has middle 5, which holds 60. Two steps.',
        ],
        [
            'question' => 'Which grows fastest as n gets large?',
            'options' => ['O(n log n)', 'O(n²)', 'O(n)', 'O(log n)'],
            'correct_option' => 1,
            'explanation' => 'From slowest-growing to fastest: log n, n, n log n, n².',
        ],
        [
            'question' => 'A function sums a list using one running total. What is its extra space complexity?',
            'options' => ['O(1)', 'O(n)', 'O(n²)', 'O(log n)'],
            'correct_option' => 0,
            'explanation' => 'However long the list, it only ever needs one extra variable.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Find it the slow way',
            'prompt' => "Write `linearSearch(array \$items, mixed \$target): int` returning the index of the first item that is identical (`===`) to the target, or -1. Don't use `array_search()`.",
            'starter_code' => <<<'PHP'
                <?php

                function linearSearch(array $items, mixed $target): int
                {
                    return -1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function linearSearch(array $items, mixed $target): int
                {
                    foreach (array_values($items) as $index => $item) {
                        if ($item === $target) {
                            return $index;
                        }
                    }

                    return -1;
                }
                PHP,
            'hint' => 'Loop with the index, and return it as soon as `$item === $target`.',
            'checks' => [
                ['label' => 'Items are found', 'code' => "return expect_same([linearSearch(['Emma', 'Dune', 'Circe'], 'Circe'), linearSearch([5, 3, 5], 5)], [2, 0]);"],
                ['label' => 'Missing items give -1, and types matter', 'code' => "return expect_same([linearSearch(['Emma'], 'Dune'), linearSearch(['1', '2'], 2)], [-1, -1]);"],
                ['label' => 'No array_search()', 'code' => "return ! str_contains(learnp_code(), 'array_search') ?: 'Write the loop yourself.';"],
            ],
        ],
        [
            'title' => 'Search the shelf',
            'prompt' => "Write `findTitle(array \$sortedTitles, string \$title): int`, a binary search over titles sorted alphabetically (compare them with `<=>` or `strcmp()`), returning the index of the title or -1. Don't loop over every title.",
            'starter_code' => <<<'PHP'
                <?php

                function findTitle(array $sortedTitles, string $title): int
                {
                    return array_search($title, $sortedTitles) ?: -1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function findTitle(array $sortedTitles, string $title): int
                {
                    $low = 0;
                    $high = count($sortedTitles) - 1;

                    while ($low <= $high) {
                        $middle = intdiv($low + $high, 2);
                        $comparison = $sortedTitles[$middle] <=> $title;

                        if ($comparison === 0) {
                            return $middle;
                        }
                        if ($comparison < 0) {
                            $low = $middle + 1;
                        } else {
                            $high = $middle - 1;
                        }
                    }

                    return -1;
                }
                PHP,
            'hint' => 'The same loop as the lesson\'s binary search, but compare strings with `$sortedTitles[$middle] <=> $title`: 0 means found, negative means look right, positive means look left.',
            'checks' => [
                ['label' => 'Titles are found', 'code' => "\$shelf = ['Beloved', 'Circe', 'Dracula', 'Dune', 'Emma'];\nreturn expect_same([findTitle(\$shelf, 'Beloved'), findTitle(\$shelf, 'Dune'), findTitle(\$shelf, 'Emma')], [0, 3, 4]);"],
                ['label' => 'Missing titles give -1', 'code' => "return expect_same([findTitle(['Circe', 'Dune'], 'Beloved'), findTitle(['Circe', 'Dune'], 'Zorba'), findTitle([], 'Dune')], [-1, -1, -1]);"],
                ['label' => 'It doesn\'t look at every title', 'code' => "return ! preg_match('/\\b(foreach|array_search|in_array)\\b/', learnp_code()) ?: 'Use a binary search, not a loop over every title.';"],
            ],
        ],
        [
            'title' => 'Count the steps',
            'prompt' => 'Write `searchSteps(array $sorted, int $target): int` that performs a binary search and returns how many middle items it looked at before finding the target (or before giving up).',
            'starter_code' => <<<'PHP'
                <?php

                function searchSteps(array $sorted, int $target): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function searchSteps(array $sorted, int $target): int
                {
                    $low = 0;
                    $high = count($sorted) - 1;
                    $steps = 0;

                    while ($low <= $high) {
                        $steps++;
                        $middle = intdiv($low + $high, 2);
                        if ($sorted[$middle] === $target) {
                            return $steps;
                        }
                        if ($sorted[$middle] < $target) {
                            $low = $middle + 1;
                        } else {
                            $high = $middle - 1;
                        }
                    }

                    return $steps;
                }
                PHP,
            'hint' => 'The binary search loop, with `$steps++` at the top of each round. Use `intdiv($low + $high, 2)` for the middle.',
            'checks' => [
                ['label' => 'The middle item takes one step', 'code' => 'return expect_same(searchSteps([10, 20, 30, 40, 50, 60, 70], 40), 1);'],
                ['label' => 'Other positions', 'code' => 'return expect_same([searchSteps([10, 20, 30, 40, 50, 60, 70], 60), searchSteps([10, 20, 30, 40, 50, 60, 70], 10), searchSteps([10, 20, 30, 40, 50, 60, 70], 75)], [2, 3, 3]);'],
                ['label' => 'A million items need about 20 steps', 'code' => "\$steps = searchSteps(range(1, 1000000), 1);\nreturn (\$steps <= 20) ?: \"That took \$steps steps; binary search needs at most 20.\";"],
            ],
        ],
        [
            'title' => 'The last copy',
            'prompt' => "In a sorted list, equal values sit together. Write `lastIndexOf(array \$sorted, int \$value): int` returning the index of the **last** occurrence of the value, or -1, using binary search. For `[1, 3, 3, 3, 5]` and 3, that's 3.",
            'starter_code' => <<<'PHP'
                <?php

                function lastIndexOf(array $sorted, int $value): int
                {
                    return -1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function lastIndexOf(array $sorted, int $value): int
                {
                    $low = 0;
                    $high = count($sorted) - 1;
                    $found = -1;

                    while ($low <= $high) {
                        $middle = intdiv($low + $high, 2);
                        if ($sorted[$middle] === $value) {
                            $found = $middle;
                            $low = $middle + 1;
                        } elseif ($sorted[$middle] < $value) {
                            $low = $middle + 1;
                        } else {
                            $high = $middle - 1;
                        }
                    }

                    return $found;
                }
                PHP,
            'hint' => 'When the middle equals the value, remember its index and keep searching the **right** half, in case there\'s a later copy.',
            'checks' => [
                ['label' => 'The last of several', 'code' => 'return expect_same([lastIndexOf([1, 3, 3, 3, 5], 3), lastIndexOf([2, 2, 2, 2], 2), lastIndexOf([1, 2, 3], 1)], [3, 3, 0]);'],
                ['label' => 'Missing values give -1', 'code' => 'return expect_same([lastIndexOf([1, 3, 5], 4), lastIndexOf([], 1)], [-1, -1]);'],
                ['label' => 'It doesn\'t look at every item', 'code' => "return ! preg_match('/\\b(foreach|array_search|array_keys|in_array)\\b/', learnp_code()) ?: 'Use a binary search, not a loop over every item.';"],
            ],
        ],
        [
            'title' => 'Duplicates, quickly',
            'prompt' => "`hasDuplicate()` compares every pair, which is O(n²). Rewrite it to be O(n): go through the list once, remembering what you've seen in an array keyed by value.",
            'starter_code' => <<<'PHP'
                <?php

                function hasDuplicate(array $items): bool
                {
                    for ($i = 0; $i < count($items); $i++) {
                        for ($j = $i + 1; $j < count($items); $j++) {
                            if ($items[$i] === $items[$j]) {
                                return true;
                            }
                        }
                    }
                    return false;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function hasDuplicate(array $items): bool
                {
                    $seen = [];
                    foreach ($items as $item) {
                        if (isset($seen[$item])) {
                            return true;
                        }
                        $seen[$item] = true;
                    }

                    return false;
                }
                PHP,
            'hint' => 'Keep `$seen = []`. For each item, return `true` if `isset($seen[$item])`, otherwise set `$seen[$item] = true`.',
            'checks' => [
                ['label' => 'Right answers', 'code' => "return expect_same([hasDuplicate(['Dune', 'Emma', 'Dune']), hasDuplicate(['Dune', 'Emma']), hasDuplicate([])], [true, false, false]);"],
                ['label' => 'Only one loop', 'code' => <<<'PHP'
                    $loops = preg_match_all('/\b(for|foreach|while)\b/', learnp_code());
                    if ($loops > 1) return "Your code has $loops loops; an O(n) version needs only one.";
                    return expect_same(hasDuplicate(range(1, 50000)), false);
                    PHP],
            ],
        ],
        [
            'title' => 'Name the growth',
            'prompt' => "Set `\$growth` to an array giving the Big-O of each function below, written exactly as `'O(1)'`, `'O(log n)'`, `'O(n)'`, `'O(n log n)'` or `'O(n²)'`:\n\n- `'first'`: returns `\$items[0]`;\n- `'total'`: adds up every item with one loop;\n- `'halving'`: `while (\$n > 1) { \$n = intdiv(\$n, 2); }`;\n- `'allPairs'`: a loop inside a loop, both over all the items;\n- `'goodSort'`: PHP's `sort()`.",
            'starter_code' => <<<'PHP'
                <?php

                $growth = [
                    'first' => '',
                    'total' => '',
                    'halving' => '',
                    'allPairs' => '',
                    'goodSort' => '',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $growth = [
                    'first' => 'O(1)',
                    'total' => 'O(n)',
                    'halving' => 'O(log n)',
                    'allPairs' => 'O(n²)',
                    'goodSort' => 'O(n log n)',
                ];
                PHP,
            'hint' => 'One step whatever the size; one step per item; halving each time; every pair; and the speed of good sorting algorithms.',
            'checks' => [
                ['label' => 'first, total and halving', 'code' => "\$g = \$GLOBALS['growth'] ?? [];\nreturn expect_same([\$g['first'] ?? null, \$g['total'] ?? null, \$g['halving'] ?? null], ['O(1)', 'O(n)', 'O(log n)']);"],
                ['label' => 'allPairs and goodSort', 'code' => "\$g = \$GLOBALS['growth'] ?? [];\nreturn expect_same([\$g['allPairs'] ?? null, \$g['goodSort'] ?? null], ['O(n²)', 'O(n log n)']);"],
            ],
        ],
    ],
];
