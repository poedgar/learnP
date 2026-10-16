<?php

// Lesson 2 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'sorting',
    'title' => 'Sorting',
    'summary' => 'Write selection, insertion, merge and quick sort yourself, compare their speed and stability, and sort small numbers without comparing at all.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function mergeSort(array $items): array
        {
            if (count($items) <= 1) {
                return $items;
            }
            $middle = intdiv(count($items), 2);
            $left = mergeSort(array_slice($items, 0, $middle));
            $right = mergeSort(array_slice($items, $middle));

            $merged = [];
            while ($left && $right) {
                $merged[] = $left[0] <= $right[0] ? array_shift($left) : array_shift($right);
            }
            return [...$merged, ...$left, ...$right];
        }

        echo implode(', ', mergeSort([38, 27, 43, 3, 9, 82, 10]));
        PHP,

    'quiz' => [
        [
            'question' => 'How does selection sort work?',
            'options' => [
                'It repeatedly finds the smallest remaining item and swaps it into the next position',
                'It splits the list in half and merges the sorted halves',
                'It slides each item left into a sorted section',
                'It picks a pivot and partitions around it',
            ],
            'correct_option' => 0,
            'explanation' => 'Each pass selects the smallest of what\'s left. It always does about n²/2 comparisons.',
        ],
        [
            'question' => 'On which kind of input is insertion sort especially fast?',
            'options' => ['Random data', 'Data that\'s already nearly sorted', 'Data sorted in reverse', 'Very large data'],
            'correct_option' => 1,
            'explanation' => 'Each item only slides a short way, so a nearly sorted list takes close to O(n).',
        ],
        [
            'question' => 'What is the Big-O of merge sort?',
            'options' => ['O(n)', 'O(n log n)', 'O(n²)', 'O(log n)'],
            'correct_option' => 1,
            'explanation' => 'Halving gives log n levels, and merging each level touches all n items.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                function merge(array $a, array $b): array
                {
                    $out = [];
                    while ($a && $b) {
                        $out[] = $a[0] <= $b[0] ? array_shift($a) : array_shift($b);
                    }
                    return [...$out, ...$a, ...$b];
                }

                echo implode(',', merge([1, 4, 9], [2, 3, 10, 11]));
                PHP,
            'options' => ['`1,4,9,2,3,10,11`', '`1,2,3,4,9,10,11`', '`1,2,3,4,9`', '`11,10,9,4,3,2,1`'],
            'correct_option' => 1,
            'explanation' => 'Merging two sorted lists repeatedly takes the smaller front item, then adds whatever is left.',
        ],
        [
            'question' => 'When is quicksort slow?',
            'options' => [
                'Never',
                'When the pivot keeps splitting the list very unevenly, such as the first item of an already-sorted list',
                'When the list has an even number of items',
                'When the items are strings',
            ],
            'correct_option' => 1,
            'explanation' => 'Uneven splits make it O(n²). Choosing the pivot at random, or the middle item, avoids the usual bad cases.',
        ],
        [
            'question' => 'What does it mean for a sort to be stable?',
            'options' => [
                'It never crashes',
                'Items that compare equal keep their original order',
                'It always takes the same time',
                'It doesn\'t use recursion',
            ],
            'correct_option' => 1,
            'explanation' => 'Stability lets you sort by one field and then another without losing the first order among ties.',
        ],
        [
            'question' => 'Since PHP 8.0, are PHP\'s own sorting functions stable?',
            'options' => ['Yes', 'No', 'Only `sort()`', 'Only for strings'],
            'correct_option' => 0,
            'explanation' => '`sort()`, `usort()`, `asort()` and the rest all keep equal items in their original order.',
        ],
        [
            'question' => 'Why can counting sort beat O(n log n)?',
            'options' => [
                'It uses a faster computer',
                'It doesn\'t compare items; it counts how many of each small value there are',
                'It sorts only half the list',
                'It can\'t; that\'s a myth',
            ],
            'correct_option' => 1,
            'explanation' => 'The O(n log n) limit applies to sorts that compare. Counting works when values come from a small range.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Bubble sort',
            'prompt' => "Write `bubbleSort(array \$items): array`: repeatedly walk through the list, swapping neighbours that are in the wrong order, until a full pass makes no swaps. Don't use PHP's sorting functions.",
            'starter_code' => <<<'PHP'
                <?php

                function bubbleSort(array $items): array
                {
                    return $items;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bubbleSort(array $items): array
                {
                    $items = array_values($items);
                    do {
                        $swapped = false;
                        for ($i = 0; $i < count($items) - 1; $i++) {
                            if ($items[$i] > $items[$i + 1]) {
                                [$items[$i], $items[$i + 1]] = [$items[$i + 1], $items[$i]];
                                $swapped = true;
                            }
                        }
                    } while ($swapped);

                    return $items;
                }
                PHP,
            'hint' => 'A `do { … } while ($swapped);` loop around a `for` that compares `$items[$i]` with `$items[$i + 1]` and swaps them when needed.',
            'checks' => [
                ['label' => 'Sorts numbers', 'code' => 'return expect_same([bubbleSort([5, 1, 4, 2, 8]), bubbleSort([]), bubbleSort([3, 3, 1])], [[1, 2, 4, 5, 8], [], [1, 3, 3]]);'],
                ['label' => 'Sorts words', 'code' => "return expect_same(bubbleSort(['Emma', 'Circe', 'Dune']), ['Circe', 'Dune', 'Emma']);"],
                ['label' => 'No built-in sorting', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Write the sort yourself.';"],
            ],
        ],
        [
            'title' => 'Merge two sorted lists',
            'prompt' => 'Write `mergeSorted(array $a, array $b): array` that merges two lists, each already sorted from smallest to largest, into one sorted list, in O(n) time: walk through both with two indexes, rather than sorting the joined list.',
            'starter_code' => <<<'PHP'
                <?php

                function mergeSorted(array $a, array $b): array
                {
                    return [...$a, ...$b];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function mergeSorted(array $a, array $b): array
                {
                    $merged = [];
                    $i = 0;
                    $j = 0;

                    while ($i < count($a) && $j < count($b)) {
                        if ($a[$i] <= $b[$j]) {
                            $merged[] = $a[$i++];
                        } else {
                            $merged[] = $b[$j++];
                        }
                    }

                    return [...$merged, ...array_slice($a, $i), ...array_slice($b, $j)];
                }
                PHP,
            'hint' => 'Keep an index into each list. Take the smaller current item and move that index on; when one list runs out, add the rest of the other.',
            'checks' => [
                ['label' => 'Merges correctly', 'code' => 'return expect_same([mergeSorted([1, 4, 9], [2, 3, 10, 11]), mergeSorted([], [1, 2]), mergeSorted([5], [])], [[1, 2, 3, 4, 9, 10, 11], [1, 2], [5]]);'],
                ['label' => 'Equal items keep a\'s first', 'code' => 'return expect_same(mergeSorted([1, 2], [2, 3]), [1, 2, 2, 3]);'],
                ['label' => 'No sorting the joined list', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Merge with two indexes; don\\'t sort.';"],
            ],
        ],
        [
            'title' => 'Insertion sort for titles',
            'prompt' => 'Write `insertionSort(array $titles): array` that sorts strings alphabetically with the insertion sort algorithm: each title in turn slides left past larger titles into its place.',
            'starter_code' => <<<'PHP'
                <?php

                function insertionSort(array $titles): array
                {
                    return $titles;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function insertionSort(array $titles): array
                {
                    $titles = array_values($titles);
                    for ($i = 1; $i < count($titles); $i++) {
                        $current = $titles[$i];
                        $j = $i - 1;
                        while ($j >= 0 && strcmp($titles[$j], $current) > 0) {
                            $titles[$j + 1] = $titles[$j];
                            $j--;
                        }
                        $titles[$j + 1] = $current;
                    }

                    return $titles;
                }
                PHP,
            'hint' => 'For each title from the second onwards, shift larger titles one place right with a `while` loop, then drop the title into the gap.',
            'checks' => [
                ['label' => 'Sorts titles', 'code' => "return expect_same(insertionSort(['Emma', 'Beloved', 'Dune', 'Circe', 'Dracula']), ['Beloved', 'Circe', 'Dracula', 'Dune', 'Emma']);"],
                ['label' => 'Empty and single lists', 'code' => "return expect_same([insertionSort([]), insertionSort(['Dune'])], [[], ['Dune']]);"],
                ['label' => 'No built-in sorting', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Write the sort yourself.';"],
            ],
        ],
        [
            'title' => 'Quicksort with a middle pivot',
            'prompt' => 'Write `quickSort(array $items): array` that picks the **middle** item as the pivot and partitions the rest into three groups (smaller, equal, larger), sorting the smaller and larger groups recursively. Choosing the middle avoids the slow case on sorted input.',
            'starter_code' => <<<'PHP'
                <?php

                function quickSort(array $items): array
                {
                    return $items;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function quickSort(array $items): array
                {
                    if (count($items) <= 1) {
                        return array_values($items);
                    }

                    $items = array_values($items);
                    $pivot = $items[intdiv(count($items), 2)];

                    $smaller = array_filter($items, fn ($x) => $x < $pivot);
                    $equal = array_filter($items, fn ($x) => $x == $pivot);
                    $larger = array_filter($items, fn ($x) => $x > $pivot);

                    return [...quickSort($smaller), ...$equal, ...quickSort($larger)];
                }
                PHP,
            'hint' => 'Stop when there are 0 or 1 items. Pick `$items[intdiv(count($items), 2)]` as the pivot, split with three `array_filter()` calls, and join the sorted parts with `...`.',
            'checks' => [
                ['label' => 'Sorts, with duplicates', 'code' => 'return expect_same(quickSort([10, 7, 8, 9, 1, 5, 7]), [1, 5, 7, 7, 8, 9, 10]);'],
                ['label' => 'Edge cases', 'code' => 'return expect_same([quickSort([]), quickSort([3]), quickSort([2, 1])], [[], [3], [1, 2]]);'],
                ['label' => 'Copes with a large sorted list', 'code' => "\$sorted = range(1, 3000);\nreturn expect_same(quickSort(\$sorted), \$sorted);"],
            ],
        ],
        [
            'title' => 'Sort the ratings',
            'prompt' => "Write `sortRatings(array \$ratings): array` that sorts star ratings (whole numbers from 1 to 5) from lowest to highest with **counting sort**: count each rating, then write them out. Don't use comparison sorting.",
            'starter_code' => <<<'PHP'
                <?php

                function sortRatings(array $ratings): array
                {
                    return $ratings;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sortRatings(array $ratings): array
                {
                    $counts = array_fill(1, 5, 0);
                    foreach ($ratings as $rating) {
                        $counts[$rating]++;
                    }

                    $sorted = [];
                    foreach ($counts as $rating => $count) {
                        for ($i = 0; $i < $count; $i++) {
                            $sorted[] = $rating;
                        }
                    }

                    return $sorted;
                }
                PHP,
            'hint' => '`array_fill(1, 5, 0)` gives counters for 1 to 5. Count, then add each rating to the output as many times as it was counted.',
            'checks' => [
                ['label' => 'Ratings are sorted', 'code' => 'return expect_same(sortRatings([4, 5, 1, 3, 5, 4, 2, 5]), [1, 2, 3, 4, 4, 5, 5, 5]);'],
                ['label' => 'Missing values and an empty list', 'code' => 'return expect_same([sortRatings([5, 1, 5]), sortRatings([])], [[1, 5, 5], []]);'],
                ['label' => 'No comparison sorting', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Count the ratings instead of sorting them.';"],
            ],
        ],
        [
            'title' => 'Rank with tie-breakers',
            'prompt' => 'Set `$ranking` to the titles from `$books`, ordered by rating (highest first), then by number of reviews (most first), then by title (A to Z), using `usort()` with one comparison.',
            'starter_code' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Emma', 'rating' => 4.5, 'reviews' => 120],
                    ['title' => 'Dune', 'rating' => 4.5, 'reviews' => 300],
                    ['title' => 'Circe', 'rating' => 4.8, 'reviews' => 90],
                    ['title' => 'Beloved', 'rating' => 4.5, 'reviews' => 120],
                ];
                $ranking = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $books = [
                    ['title' => 'Emma', 'rating' => 4.5, 'reviews' => 120],
                    ['title' => 'Dune', 'rating' => 4.5, 'reviews' => 300],
                    ['title' => 'Circe', 'rating' => 4.8, 'reviews' => 90],
                    ['title' => 'Beloved', 'rating' => 4.5, 'reviews' => 120],
                ];

                usort($books, fn ($a, $b) => [$b['rating'], $b['reviews'], $a['title']] <=> [$a['rating'], $a['reviews'], $b['title']]);
                $ranking = array_column($books, 'title');
                PHP,
            'hint' => 'Compare arrays: put `$b` first for the "highest first" keys and `$a` first for the title.',
            'checks' => [
                ['label' => '$ranking is right', 'code' => "return expect_same(\$GLOBALS['ranking'] ?? null, ['Circe', 'Dune', 'Beloved', 'Emma']);"],
                ['label' => 'It uses usort()', 'code' => "return str_contains(learnp_code(), 'usort(') ?: 'Sort with usort().';"],
            ],
        ],
    ],
];
