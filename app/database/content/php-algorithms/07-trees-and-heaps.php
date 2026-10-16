<?php

// Lesson 7 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'trees-and-heaps',
    'title' => 'Trees and heaps',
    'summary' => 'Walk trees recursively in pre-, in- and post-order, search and insert in binary search trees, see why balance matters, and use heaps for priorities and top-k problems.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class TreeNode
        {
            public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
        }

        function inOrder(?TreeNode $n): array
        {
            return $n ? [...inOrder($n->left), $n->value, ...inOrder($n->right)] : [];
        }

        $root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10));
        echo implode(' ', inOrder($root));
        PHP,

    'quiz' => [
        [
            'question' => 'In a tree, what is a leaf?',
            'options' => ['The top node', 'A node with no children', 'A node with two children', 'The deepest level'],
            'correct_option' => 1,
            'explanation' => 'The root is at the top; leaves are the nodes at the ends of the branches.',
        ],
        [
            'question' => 'What rule does a binary search tree keep at every node?',
            'options' => [
                'Both children are larger than the node',
                'Everything on the left is smaller and everything on the right is larger',
                'The tree is always balanced',
                'Every node has two children',
            ],
            'correct_option' => 1,
            'explanation' => 'That rule lets a search go left or right at each node, like binary search.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class N
                {
                    public function __construct(public string $v, public ?N $l = null, public ?N $r = null) {}
                }

                function pre(?N $n): string { return $n ? $n->v . pre($n->l) . pre($n->r) : ''; }
                function post(?N $n): string { return $n ? post($n->l) . post($n->r) . $n->v : ''; }

                $tree = new N('A', new N('B', new N('D')), new N('C'));
                echo pre($tree) . ' ' . post($tree);
                PHP,
            'options' => ['`ABDC DBCA`', '`ABCD DCBA`', '`DBAC ABDC`', '`ABDC ABDC`'],
            'correct_option' => 0,
            'explanation' => 'Pre-order visits a node before its children; post-order after them.',
        ],
        [
            'question' => 'Which traversal of a binary search tree gives its values in sorted order?',
            'options' => ['Pre-order', 'In-order', 'Post-order', 'None of them'],
            'correct_option' => 1,
            'explanation' => 'Left subtree (all smaller), then the node, then the right subtree (all larger).',
        ],
        [
            'question' => 'What happens when you insert already-sorted values into a plain binary search tree?',
            'options' => [
                'It stays perfectly balanced',
                'Every node goes to the same side, so it becomes a long chain with O(n) searches',
                'Insertion fails',
                'The values are stored in reverse',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s why production trees rebalance themselves.',
        ],
        [
            'question' => 'What is always at the top of a min-heap?',
            'options' => ['The largest item', 'The smallest item', 'The most recent item', 'The middle item'],
            'correct_option' => 1,
            'explanation' => 'Every parent is smaller than its children, so the smallest item is the root.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $heap = new SplMaxHeap();
                foreach ([4, 10, 7, 1] as $n) {
                    $heap->insert($n);
                }
                $heap->extract();
                echo $heap->top();
                PHP,
            'options' => ['`10`', '`7`', '`4`', '`1`'],
            'correct_option' => 1,
            'explanation' => 'A max-heap gives the largest first: 10 is taken off, leaving 7 on top.',
        ],
        [
            'question' => 'To find the 5 largest of a million numbers, why keep a min-heap of size 5?',
            'options' => [
                'Because heaps sort the whole million',
                'Each new number only needs comparing with the smallest of the current best five, in O(log 5)',
                'Because max-heaps can\'t hold numbers',
                'It isn\'t faster than sorting',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s O(n log k) instead of O(n log n), and uses only k items of memory.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Sum and leaves',
            'prompt' => 'Write two recursive functions for binary trees: `treeSum(?TreeNode $node): int` adding up every value, and `countLeaves(?TreeNode $node): int` counting the nodes with no children.',
            'starter_code' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function treeSum(?TreeNode $node): int
                {
                    return 0;
                }

                function countLeaves(?TreeNode $node): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function treeSum(?TreeNode $node): int
                {
                    return $node === null ? 0 : $node->value + treeSum($node->left) + treeSum($node->right);
                }

                function countLeaves(?TreeNode $node): int
                {
                    if ($node === null) {
                        return 0;
                    }
                    if ($node->left === null && $node->right === null) {
                        return 1;
                    }

                    return countLeaves($node->left) + countLeaves($node->right);
                }
                PHP,
            'hint' => 'An empty tree gives 0. A node with no children is one leaf. Otherwise combine the answers for the left and right subtrees.',
            'checks' => [
                ['label' => 'Sum', 'code' => "\$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));\nreturn expect_same([treeSum(\$root), treeSum(null)], [42, 0]);"],
                ['label' => 'Leaves', 'code' => "\$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));\nreturn expect_same([countLeaves(\$root), countLeaves(new TreeNode(1)), countLeaves(null)], [3, 1, 0]);"],
            ],
        ],
        [
            'title' => 'Build a search tree',
            'prompt' => 'Write `bstInsert(?TreeNode $node, int $value): TreeNode` (smaller values to the left, others to the right) and `bstValues(?TreeNode $node): array` returning the values in sorted order with an in-order walk. Inserting 50, 30, 70, 20, 40 and calling `bstValues()` gives `[20, 30, 40, 50, 70]`.',
            'starter_code' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function bstInsert(?TreeNode $node, int $value): TreeNode
                {
                    if ($node === null) {
                        return new TreeNode($value);
                    }
                    if ($value < $node->value) {
                        $node->left = bstInsert($node->left, $value);
                    } else {
                        $node->right = bstInsert($node->right, $value);
                    }

                    return $node;
                }

                function bstValues(?TreeNode $node): array
                {
                    return $node === null ? [] : [...bstValues($node->left), $node->value, ...bstValues($node->right)];
                }
                PHP,
            'hint' => 'Insert recursively, returning the (possibly new) node. The in-order walk is left values, the node, then right values.',
            'checks' => [
                ['label' => 'The tree keeps the BST rule', 'code' => "\$root = null;\nforeach ([50, 30, 70, 20, 40] as \$v) { \$root = bstInsert(\$root, \$v); }\nreturn expect_same([\$root->value, \$root->left->value, \$root->right->value, \$root->left->left->value, \$root->left->right->value], [50, 30, 70, 20, 40]);"],
                ['label' => 'Values come out sorted', 'code' => "\$root = null;\nforeach ([5, 9, 1, 7, 3, 8] as \$v) { \$root = bstInsert(\$root, \$v); }\nreturn expect_same(bstValues(\$root), [1, 3, 5, 7, 8, 9]);"],
            ],
        ],
        [
            'title' => 'Smallest and largest',
            'prompt' => 'In a binary search tree, the smallest value is as far left as you can go, and the largest as far right. Write `bstMin(TreeNode $root): int` and `bstMax(TreeNode $root): int` using loops, without visiting every node.',
            'starter_code' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function bstMin(TreeNode $root): int
                {
                    return $root->value;
                }

                function bstMax(TreeNode $root): int
                {
                    return $root->value;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function bstMin(TreeNode $root): int
                {
                    while ($root->left !== null) {
                        $root = $root->left;
                    }

                    return $root->value;
                }

                function bstMax(TreeNode $root): int
                {
                    while ($root->right !== null) {
                        $root = $root->right;
                    }

                    return $root->value;
                }
                PHP,
            'hint' => 'Keep following `->left` while it isn\'t `null`; the node you stop at holds the minimum.',
            'checks' => [
                ['label' => 'Minimum and maximum', 'code' => "\$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));\nreturn expect_same([bstMin(\$root), bstMax(\$root)], [1, 14]);"],
                ['label' => 'A single node', 'code' => 'return expect_same([bstMin(new TreeNode(5)), bstMax(new TreeNode(5))], [5, 5]);'],
            ],
        ],
        [
            'title' => 'Is it a search tree?',
            'prompt' => 'Write `isBst(?TreeNode $node, ?int $min = null, ?int $max = null): bool` checking the BST rule for the **whole** tree: every value must be greater than everything to its left and less than everything to its right, not just its own children. Equal values count as invalid.',
            'starter_code' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function isBst(?TreeNode $node, ?int $min = null, ?int $max = null): bool
                {
                    if ($node === null) {
                        return true;
                    }
                    if ($node->left !== null && $node->left->value >= $node->value) {
                        return false;
                    }
                    if ($node->right !== null && $node->right->value <= $node->value) {
                        return false;
                    }

                    return isBst($node->left) && isBst($node->right);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function isBst(?TreeNode $node, ?int $min = null, ?int $max = null): bool
                {
                    if ($node === null) {
                        return true;
                    }
                    if (($min !== null && $node->value <= $min) || ($max !== null && $node->value >= $max)) {
                        return false;
                    }

                    return isBst($node->left, $min, $node->value) && isBst($node->right, $node->value, $max);
                }
                PHP,
            'hint' => 'Pass down the allowed range. Going left, the node\'s value becomes the new maximum; going right, the new minimum.',
            'checks' => [
                ['label' => 'A valid tree', 'code' => 'return expect_same(isBst(new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)))), true);'],
                ['label' => 'A violation deeper down', 'code' => 'return expect_same(isBst(new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(9)), new TreeNode(10))), false);'],
                ['label' => 'Duplicates and empty trees', 'code' => 'return expect_same([isBst(new TreeNode(5, new TreeNode(5))), isBst(null)], [false, true]);'],
            ],
        ],
        [
            'title' => 'The k smallest',
            'prompt' => "Write `smallestK(array \$numbers, int \$k): array` returning the k smallest numbers in ascending order, using a heap. Don't sort the whole list.",
            'starter_code' => <<<'PHP'
                <?php

                function smallestK(array $numbers, int $k): array
                {
                    return array_slice($numbers, 0, $k);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function smallestK(array $numbers, int $k): array
                {
                    $heap = new SplMinHeap();
                    foreach ($numbers as $n) {
                        $heap->insert($n);
                    }

                    $smallest = [];
                    for ($i = 0; $i < $k && ! $heap->isEmpty(); $i++) {
                        $smallest[] = $heap->extract();
                    }

                    return $smallest;
                }
                PHP,
            'hint' => 'Put every number into an `SplMinHeap`, then `extract()` k times (or until it\'s empty).',
            'checks' => [
                ['label' => 'The k smallest, in order', 'code' => 'return expect_same([smallestK([42, 7, 19, 3, 25, 7], 3), smallestK([5, 1], 5), smallestK([4], 0)], [[3, 7, 7], [1, 5], []]);'],
                ['label' => 'No sorting', 'code' => "return ! preg_match('/\\\\b(u?[ak]?r?sort)\\\\s*\\\\(/', learnp_code()) ?: 'Use a heap instead of sorting.';"],
            ],
        ],
        [
            'title' => 'Every path to a leaf',
            'prompt' => "Write `paths(?TreeNode \$node): array` returning every path from the root to a leaf, as strings of values joined by `->`, left paths first. For the tree 1 (left 2 (right 5), right 3) that's `['1->2->5', '1->3']`.",
            'starter_code' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function paths(?TreeNode $node): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class TreeNode
                {
                    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
                }

                function paths(?TreeNode $node): array
                {
                    if ($node === null) {
                        return [];
                    }
                    if ($node->left === null && $node->right === null) {
                        return [(string) $node->value];
                    }

                    $below = [...paths($node->left), ...paths($node->right)];

                    return array_map(fn (string $path) => "{$node->value}->$path", $below);
                }
                PHP,
            'hint' => 'A leaf is a path on its own. Otherwise, get the paths of both subtrees and put this node\'s value and `->` in front of each.',
            'checks' => [
                ['label' => 'Paths in a small tree', 'code' => "return expect_same(paths(new TreeNode(1, new TreeNode(2, null, new TreeNode(5)), new TreeNode(3))), ['1->2->5', '1->3']);"],
                ['label' => 'A single node, and no tree', 'code' => "return expect_same([paths(new TreeNode(7)), paths(null)], [['7'], []]);"],
            ],
        ],
    ],
];
