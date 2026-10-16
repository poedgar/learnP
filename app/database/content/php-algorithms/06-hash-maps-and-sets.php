<?php

// Lesson 6 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'hash-maps-and-sets',
    'title' => 'Hash maps and sets',
    'summary' => 'Look things up in O(1) with hash maps, count and group in one pass, use arrays as sets, and turn O(n²) problems into O(n) ones.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $sales = ['Dune', 'Emma', 'Dune', 'Circe', 'Dune', 'Emma'];

        $counts = [];
        foreach ($sales as $title) {
            $counts[$title] = ($counts[$title] ?? 0) + 1;
        }

        arsort($counts);
        print_r($counts);
        PHP,

    'quiz' => [
        [
            'question' => 'What is the Big-O of looking up a key in a PHP array?',
            'options' => ['O(1) on average', 'O(log n)', 'O(n)', 'O(n²)'],
            'correct_option' => 0,
            'explanation' => 'PHP arrays are hash maps: the key\'s hash says where to look, however many entries there are.',
        ],
        [
            'question' => 'Why can building a map first turn an O(n²) algorithm into an O(n) one?',
            'options' => [
                'Maps sort the data',
                'Each "is it there?" question becomes O(1) instead of a search through a list',
                'Maps use less memory',
                'It can\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'You spend O(n) memory on the map, and save a whole search for every item.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $set = [];
                foreach (['a', 'b', 'a', 'c', 'b', 'a'] as $x) {
                    $set[$x] = true;
                }
                echo count($set);
                PHP,
            'options' => ['`6`', '`3`', '`1`', '`0`'],
            'correct_option' => 1,
            'explanation' => 'Keys are unique, so adding the same key again keeps one entry: a, b and c.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = ['Dune' => true, 'Emma' => true];
                $b = ['Emma' => true, 'Circe' => true];
                echo implode(',', array_keys(array_intersect_key($a, $b)));
                PHP,
            'options' => ['`Dune,Emma,Circe`', '`Emma`', '`Dune`', '`Dune,Circe`'],
            'correct_option' => 1,
            'explanation' => 'The intersection keeps the keys found in both.',
        ],
        [
            'question' => 'In the two-sum solution, what does the map store?',
            'options' => [
                'Every pair of prices',
                'Each price seen so far, with its position',
                'The sorted prices',
                'The running total',
            ],
            'correct_option' => 1,
            'explanation' => 'For each new price, the partner it needs is looked up in the map of prices already seen.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $map = [];
                $map['7'] = 'a';
                $map[7] = 'b';
                $map['07'] = 'c';
                echo count($map);
                PHP,
            'options' => ['`1`', '`2`', '`3`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`\'7\'` is converted to the int key 7, so it\'s the same entry as `7`. `\'07\'` isn\'t a canonical number, so it stays a separate string key.',
        ],
        [
            'question' => 'What does `array_count_values([\'x\', \'y\', \'x\'])` return?',
            'options' => ["`['x' => 2, 'y' => 1]`", '`3`', "`['x', 'y']`", '`[2, 1]`'],
            'correct_option' => 0,
            'explanation' => 'A map from each value to how many times it appears.',
        ],
        [
            'question' => 'What does `$amara + $ben` give for two sets stored as arrays of keys?',
            'options' => [
                'Only the keys in both',
                'Every key from either, each once',
                'The keys only in `$amara`',
                'An error',
            ],
            'correct_option' => 1,
            'explanation' => 'Array `+` keeps all of the left side\'s keys and adds the right side\'s missing ones: a union.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Most popular title',
            'prompt' => 'Write `bestSeller(array $sales): ?string` returning the title that appears most often in the list (the one that reached that count first, if several tie), or `null` for no sales. Count with a map in one pass.',
            'starter_code' => <<<'PHP'
                <?php

                function bestSeller(array $sales): ?string
                {
                    return $sales[0] ?? null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bestSeller(array $sales): ?string
                {
                    $counts = [];
                    $best = null;

                    foreach ($sales as $title) {
                        $counts[$title] = ($counts[$title] ?? 0) + 1;
                        if ($best === null || $counts[$title] > $counts[$best]) {
                            $best = $title;
                        }
                    }

                    return $best;
                }
                PHP,
            'hint' => 'Count as you go, and update the best title whenever a title\'s count goes strictly above the best one\'s count.',
            'checks' => [
                ['label' => 'The most common title', 'code' => "return expect_same(bestSeller(['Emma', 'Dune', 'Dune', 'Circe', 'Dune', 'Emma']), 'Dune');"],
                ['label' => 'Ties go to whoever got there first, and no sales', 'code' => "return expect_same([bestSeller(['Emma', 'Dune', 'Dune', 'Emma']), bestSeller([])], ['Dune', null]);"],
            ],
        ],
        [
            'title' => 'Two prices for a gift card',
            'prompt' => "Write `findPair(array \$prices, int \$total): ?array` returning the positions `[i, j]` (with i < j) of the first pair of prices that add up exactly to `\$total`, or `null`. Use a map so it's O(n).",
            'starter_code' => <<<'PHP'
                <?php

                function findPair(array $prices, int $total): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function findPair(array $prices, int $total): ?array
                {
                    $seen = [];
                    foreach ($prices as $j => $price) {
                        $partner = $total - $price;
                        if (isset($seen[$partner])) {
                            return [$seen[$partner], $j];
                        }
                        $seen[$price] ??= $j;
                    }

                    return null;
                }
                PHP,
            'hint' => 'For each price, check whether `$total - $price` is already in the map of earlier prices; then add this price with its position.',
            'checks' => [
                ['label' => 'A pair is found', 'code' => 'return expect_same([findPair([8, 15, 3, 7, 12], 15), findPair([5, 5], 10)], [[0, 3], [0, 1]]);'],
                ['label' => 'No pair', 'code' => 'return expect_same([findPair([1, 2, 3], 100), findPair([10], 20), findPair([], 5)], [null, null, null]);'],
                ['label' => 'Only one loop', 'code' => "\$loops = preg_match_all('/\\\\b(for|foreach|while)\\\\b/', learnp_code());\nreturn \$loops <= 1 ?: 'Use a map and one loop, not a loop inside a loop.';"],
            ],
        ],
        [
            'title' => 'Read by both',
            'prompt' => 'Write `readByBoth(array $a, array $b): array` returning the titles that appear in both reading lists, in the order they appear in `$a`, each once. Turn `$b` into a set so each check is O(1).',
            'starter_code' => <<<'PHP'
                <?php

                function readByBoth(array $a, array $b): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function readByBoth(array $a, array $b): array
                {
                    $inB = array_flip($b);
                    $both = [];

                    foreach ($a as $title) {
                        if (isset($inB[$title])) {
                            $both[$title] = true;
                        }
                    }

                    return array_keys($both);
                }
                PHP,
            'hint' => '`array_flip($b)` makes a set. Collect matches as keys of another array, so repeats are kept once, and return its keys.',
            'checks' => [
                ['label' => 'Shared titles, in a\'s order', 'code' => "return expect_same(readByBoth(['Dune', 'Emma', 'Circe', 'Emma'], ['Circe', 'Beloved', 'Emma']), ['Emma', 'Circe']);"],
                ['label' => 'Nothing in common', 'code' => "return expect_same(readByBoth(['Dune'], ['Emma']), []);"],
            ],
        ],
        [
            'title' => 'Anagrams',
            'prompt' => "Two words are anagrams if they use exactly the same letters, the same number of times (ignoring case): `'Listen'` and `'Silent'`. Write `areAnagrams(string \$a, string \$b): bool` by counting letters in a map. Don't sort.",
            'starter_code' => <<<'PHP'
                <?php

                function areAnagrams(string $a, string $b): bool
                {
                    return strlen($a) === strlen($b);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function areAnagrams(string $a, string $b): bool
                {
                    $counts = [];
                    foreach (str_split(strtolower($a)) as $letter) {
                        $counts[$letter] = ($counts[$letter] ?? 0) + 1;
                    }
                    foreach (str_split(strtolower($b)) as $letter) {
                        $counts[$letter] = ($counts[$letter] ?? 0) - 1;
                    }

                    return array_filter($counts) === [];
                }
                PHP,
            'hint' => 'Add one for each letter of the first word and take one away for each letter of the second. They\'re anagrams if every count ends at 0.',
            'checks' => [
                ['label' => 'Anagrams', 'code' => "return expect_same([areAnagrams('Listen', 'Silent'), areAnagrams('Dormitory', 'dirtyroom'), areAnagrams('', '')], [true, true, true]);"],
                ['label' => 'Not anagrams', 'code' => "return expect_same([areAnagrams('Dune', 'Done'), areAnagrams('aab', 'abb'), areAnagrams('abc', 'abcd')], [false, false, false]);"],
                ['label' => 'No sorting', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Count letters with a map instead of sorting.';"],
            ],
        ],
        [
            'title' => 'Group by author',
            'prompt' => "Write `byAuthor(array \$books): array` grouping books (each `['title' => …, 'author' => …]`) into a map from author to their titles, in the original order, with the authors sorted alphabetically.",
            'starter_code' => <<<'PHP'
                <?php

                function byAuthor(array $books): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function byAuthor(array $books): array
                {
                    $groups = [];
                    foreach ($books as $book) {
                        $groups[$book['author']][] = $book['title'];
                    }
                    ksort($groups);

                    return $groups;
                }
                PHP,
            'hint' => '`$groups[$book[\'author\']][] = $book[\'title\'];` in a loop, then `ksort()`.',
            'checks' => [
                ['label' => 'Grouped and sorted by author', 'code' => "\$books = [\n    ['title' => 'Emma', 'author' => 'Jane Austen'],\n    ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien'],\n    ['title' => 'Persuasion', 'author' => 'Jane Austen'],\n];\nreturn expect_same(byAuthor(\$books), ['J.R.R. Tolkien' => ['The Hobbit'], 'Jane Austen' => ['Emma', 'Persuasion']]);"],
            ],
        ],
        [
            'title' => 'Longest run without repeats',
            'prompt' => "Write `longestUnique(string \$text): int` returning the length of the longest stretch of `\$text` in which no character repeats. For `'abcabcbb'` it's 3 (`'abc'`). Remember where you last saw each character in a map, so it's O(n).",
            'starter_code' => <<<'PHP'
                <?php

                function longestUnique(string $text): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function longestUnique(string $text): int
                {
                    $lastSeen = [];
                    $start = 0;
                    $best = 0;

                    foreach (str_split($text) as $i => $char) {
                        if (isset($lastSeen[$char]) && $lastSeen[$char] >= $start) {
                            $start = $lastSeen[$char] + 1;
                        }
                        $lastSeen[$char] = $i;
                        $best = max($best, $i - $start + 1);
                    }

                    return $text === '' ? 0 : $best;
                }
                PHP,
            'hint' => 'Keep the start of the current stretch. When a character repeats inside it, move the start just past its previous position. Track the best length as you go.',
            'checks' => [
                ['label' => 'Examples', 'code' => "return expect_same(array_map('longestUnique', ['abcabcbb', 'bbbbb', 'pwwkew', 'dune', '']), [3, 1, 3, 4, 0]);"],
                ['label' => 'Repeats far apart', 'code' => "return expect_same(longestUnique('abba'), 2);"],
            ],
        ],
    ],
];
