<?php

// Lesson 9 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'dynamic-programming-and-techniques',
    'title' => 'Dynamic programming, two pointers and sliding windows',
    'summary' => 'Build answers from smaller answers with dynamic programming tables, walk sorted arrays with two pointers, and slide windows along a list instead of recounting.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        function fewestCoins(array $coins, int $amount): int
        {
            $best = array_fill(0, $amount + 1, PHP_INT_MAX);
            $best[0] = 0;
            for ($a = 1; $a <= $amount; $a++) {
                foreach ($coins as $coin) {
                    if ($coin <= $a && $best[$a - $coin] !== PHP_INT_MAX) {
                        $best[$a] = min($best[$a], $best[$a - $coin] + 1);
                    }
                }
            }
            return $best[$amount] === PHP_INT_MAX ? -1 : $best[$amount];
        }

        echo fewestCoins([1, 2, 5], 11);
        PHP,

    'quiz' => [
        [
            'question' => 'When does dynamic programming help?',
            'options' => [
                'When the input is sorted',
                'When a problem breaks into smaller sub-problems that repeat, so each can be solved once and reused',
                'Only for problems about money',
                'When the answer is random',
            ],
            'correct_option' => 1,
            'explanation' => 'Storing each sub-problem\'s answer turns repeated work into a table lookup.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $ways = [1, 1];
                for ($n = 2; $n <= 5; $n++) {
                    $ways[$n] = $ways[$n - 1] + $ways[$n - 2];
                }
                echo $ways[5];
                PHP,
            'options' => ['`5`', '`8`', '`13`', '`3`'],
            'correct_option' => 1,
            'explanation' => 'The table fills 1, 1, 2, 3, 5, 8: each entry from the two before it.',
        ],
        [
            'question' => 'What is the difference between "top-down" and "bottom-up" dynamic programming?',
            'options' => [
                'There is none',
                'Top-down is memoized recursion from the big problem; bottom-up fills a table from the smallest problems with a loop',
                'Bottom-up only works on trees',
                'Top-down never stores answers',
            ],
            'correct_option' => 1,
            'explanation' => 'Both solve each sub-problem once; they just work in opposite directions.',
        ],
        [
            'question' => 'What must be true of the array for the "pointers from both ends" pair-sum technique?',
            'options' => ['It must be sorted', 'It must have an even length', 'It must contain no negatives', 'Nothing'],
            'correct_option' => 0,
            'explanation' => 'Sorting tells you which pointer to move: too small, move left up; too big, move right down.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $sorted = [1, 2, 4, 7, 11];
                [$left, $right, $steps] = [0, 4, 0];
                while ($left < $right) {
                    $steps++;
                    $sum = $sorted[$left] + $sorted[$right];
                    if ($sum === 9) break;
                    $sum < 9 ? $left++ : $right--;
                }
                echo "{$sorted[$left]}+{$sorted[$right]} in $steps steps";
                PHP,
            'options' => ['`2+7 in 3 steps`', '`1+11 in 1 steps`', '`2+7 in 2 steps`', '`4+7 in 3 steps`'],
            'correct_option' => 0,
            'explanation' => '1+11 = 12 is too big, so right moves; 1+7 = 8 is too small, so left moves; 2+7 = 9.',
        ],
        [
            'question' => 'Why is a sliding window faster than adding up every window from scratch?',
            'options' => [
                'It uses a faster loop',
                'Moving the window adds one new item and removes one old one, instead of re-adding all k items',
                'It sorts the data first',
                'It skips half the windows',
            ],
            'correct_option' => 1,
            'explanation' => 'Each step costs O(1), so the whole scan is O(n) rather than O(n × k).',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $sales = [3, 1, 4, 1, 5];
                $window = $sales[0] + $sales[1];
                $best = $window;
                for ($i = 2; $i < count($sales); $i++) {
                    $window += $sales[$i] - $sales[$i - 2];
                    $best = max($best, $window);
                }
                echo $best;
                PHP,
            'options' => ['`5`', '`6`', '`4`', '`14`'],
            'correct_option' => 1,
            'explanation' => 'The 2-day windows sum to 4, 5, 5 and 6; the best is the last one, 1 + 5.',
        ],
        [
            'question' => 'In the knapsack solution, why does the inner loop go from the full capacity down to the book\'s weight?',
            'options' => [
                'It\'s faster',
                'So each book is counted at most once: smaller capacities haven\'t been updated with this book yet',
                'Because weights must be sorted',
                'It doesn\'t matter which way it goes',
            ],
            'correct_option' => 1,
            'explanation' => 'Going upwards would let the same book be added again and again.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Ways to make change',
            'prompt' => "Write `countWays(array \$coins, int \$amount): int` returning how many different combinations of coins (any number of each, order doesn't matter) make the amount, with a bottom-up DP table. For coins 1, 2 and 5 and amount 5 there are 4 ways.",
            'starter_code' => <<<'PHP'
                <?php

                function countWays(array $coins, int $amount): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function countWays(array $coins, int $amount): int
                {
                    $ways = array_fill(0, $amount + 1, 0);
                    $ways[0] = 1;

                    foreach ($coins as $coin) {
                        for ($a = $coin; $a <= $amount; $a++) {
                            $ways[$a] += $ways[$a - $coin];
                        }
                    }

                    return $ways[$amount];
                }
                PHP,
            'hint' => 'Start with `$ways[0] = 1`. For each coin in turn, for every amount from the coin upwards, add `$ways[$a - $coin]` to `$ways[$a]`. Looping coins on the outside counts combinations, not orderings.',
            'checks' => [
                ['label' => 'Small amounts', 'code' => 'return expect_same([countWays([1, 2, 5], 5), countWays([2], 3), countWays([1, 2], 0)], [4, 0, 1]);'],
                ['label' => 'A bigger amount, quickly', 'code' => 'return expect_same(countWays([1, 2, 5, 10, 20, 50, 100, 200], 200), 73682);'],
            ],
        ],
        [
            'title' => 'Longest common subsequence',
            'prompt' => "Write `lcs(string \$a, string \$b): int` returning the length of the longest common subsequence of two strings (letters in the same order, not necessarily next to each other), with a DP table. `lcs('ABCBDAB', 'BDCABA')` is 4.",
            'starter_code' => <<<'PHP'
                <?php

                function lcs(string $a, string $b): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function lcs(string $a, string $b): int
                {
                    $table = array_fill(0, strlen($a) + 1, array_fill(0, strlen($b) + 1, 0));

                    for ($i = 1; $i <= strlen($a); $i++) {
                        for ($j = 1; $j <= strlen($b); $j++) {
                            $table[$i][$j] = $a[$i - 1] === $b[$j - 1]
                                ? $table[$i - 1][$j - 1] + 1
                                : max($table[$i - 1][$j], $table[$i][$j - 1]);
                        }
                    }

                    return $table[strlen($a)][strlen($b)];
                }
                PHP,
            'hint' => 'A table one bigger than each string, filled with 0. Matching letters extend the diagonal by one; otherwise take the larger of the cell above and the cell to the left.',
            'checks' => [
                ['label' => 'Examples', 'code' => "return expect_same([lcs('ABCBDAB', 'BDCABA'), lcs('dune', 'done'), lcs('abc', 'xyz'), lcs('', 'abc')], [4, 3, 0, 0]);"],
            ],
        ],
        [
            'title' => 'Closest pair to a budget',
            'prompt' => 'Write `closestPair(array $sorted, int $budget): array` that, for a list of prices sorted from cheapest to dearest, returns the two prices whose sum is closest to the budget without going over it, as `[smaller, larger]`, or `[]` if no pair fits. Use two pointers.',
            'starter_code' => <<<'PHP'
                <?php

                function closestPair(array $sorted, int $budget): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function closestPair(array $sorted, int $budget): array
                {
                    $left = 0;
                    $right = count($sorted) - 1;
                    $best = [];
                    $bestSum = -1;

                    while ($left < $right) {
                        $sum = $sorted[$left] + $sorted[$right];
                        if ($sum > $budget) {
                            $right--;
                            continue;
                        }
                        if ($sum > $bestSum) {
                            $bestSum = $sum;
                            $best = [$sorted[$left], $sorted[$right]];
                        }
                        $left++;
                    }

                    return $best;
                }
                PHP,
            'hint' => 'Start at both ends. Over budget: move the right pointer in. Within budget: remember it if it\'s the best so far, then move the left pointer up to try a bigger sum.',
            'checks' => [
                ['label' => 'Closest without going over', 'code' => 'return expect_same([closestPair([3, 7, 9, 12, 15], 20), closestPair([5, 10, 15], 25), closestPair([1, 2, 3], 100)], [[7, 12], [10, 15], [2, 3]]);'],
                ['label' => 'Nothing fits', 'code' => 'return expect_same([closestPair([10, 20], 5), closestPair([4], 10)], [[], []]);'],
                ['label' => 'Only one loop', 'code' => "\$loops = preg_match_all('/\\\\b(for|foreach|while)\\\\b/', learnp_code());\nreturn \$loops <= 1 ?: 'Use two pointers in one loop.';"],
            ],
        ],
        [
            'title' => 'Best average week',
            'prompt' => 'Write `bestAverage(array $sales, int $days): float` returning the highest average over any run of `$days` consecutive days, rounded to 2 places, using a sliding window.',
            'starter_code' => <<<'PHP'
                <?php

                function bestAverage(array $sales, int $days): float
                {
                    return 0.0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bestAverage(array $sales, int $days): float
                {
                    $window = array_sum(array_slice($sales, 0, $days));
                    $best = $window;

                    for ($i = $days; $i < count($sales); $i++) {
                        $window += $sales[$i] - $sales[$i - $days];
                        $best = max($best, $window);
                    }

                    return round($best / $days, 2);
                }
                PHP,
            'hint' => 'Find the best total with a sliding window, then divide by `$days` once at the end.',
            'checks' => [
                ['label' => 'Best 3-day average', 'code' => 'return expect_same(bestAverage([4, 2, 12, 3, 8, 1, 9, 7], 3), 7.67);'],
                ['label' => 'A window as long as the list', 'code' => 'return expect_same(bestAverage([1, 2, 3, 4], 4), 2.5);'],
            ],
        ],
        [
            'title' => 'Shortest run to the target',
            'prompt' => 'Write `shortestRun(array $sales, int $target): int` returning the length of the shortest run of consecutive days whose sales add up to at least the target, or 0 if no run does. All sales are positive. Use a window that grows and shrinks.',
            'starter_code' => <<<'PHP'
                <?php

                function shortestRun(array $sales, int $target): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shortestRun(array $sales, int $target): int
                {
                    $best = PHP_INT_MAX;
                    $sum = 0;
                    $left = 0;

                    foreach ($sales as $right => $value) {
                        $sum += $value;
                        while ($sum >= $target) {
                            $best = min($best, $right - $left + 1);
                            $sum -= $sales[$left++];
                        }
                    }

                    return $best === PHP_INT_MAX ? 0 : $best;
                }
                PHP,
            'hint' => 'Add each day on the right; while the sum reaches the target, record the length and drop days from the left.',
            'checks' => [
                ['label' => 'Examples', 'code' => 'return expect_same([shortestRun([2, 3, 1, 2, 4, 3], 7), shortestRun([1, 4, 4], 4), shortestRun([1, 1, 1], 10)], [2, 1, 0]);'],
            ],
        ],
        [
            'title' => 'Fill the parcel',
            'prompt' => 'A parcel can hold a limited weight. Each book is `[weight, value]`. Write `bestValue(array $books, int $capacity): int` returning the highest total value of books that fit, each used at most once, with dynamic programming.',
            'starter_code' => <<<'PHP'
                <?php

                function bestValue(array $books, int $capacity): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bestValue(array $books, int $capacity): int
                {
                    $best = array_fill(0, $capacity + 1, 0);

                    foreach ($books as [$weight, $value]) {
                        for ($c = $capacity; $c >= $weight; $c--) {
                            $best[$c] = max($best[$c], $best[$c - $weight] + $value);
                        }
                    }

                    return $best[$capacity];
                }
                PHP,
            'hint' => 'A table of the best value for each capacity, starting at 0. For each book, go through capacities from the top down to its weight, trying "with this book".',
            'checks' => [
                ['label' => 'Examples', 'code' => 'return expect_same([bestValue([[3, 25], [2, 20], [4, 40], [1, 8]], 6), bestValue([[5, 10]], 4), bestValue([[1, 1], [1, 1], [1, 1]], 2)], [60, 0, 2]);'],
                ['label' => 'More books', 'code' => 'return expect_same(bestValue([[12, 4], [2, 2], [1, 1], [1, 2], [4, 10]], 15), 15);'],
            ],
        ],
    ],
];
