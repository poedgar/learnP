<?php

// Lesson 5 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Checks build their own lists from the Node class in the starter code.

return [
    'slug' => 'linked-lists',
    'title' => 'Linked lists',
    'summary' => 'Build lists out of linked nodes: walking, adding at the front in O(1), removing, reversing in place, and the two-pointer tricks.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class Node
        {
            public function __construct(public mixed $value, public ?Node $next = null) {}
        }

        $head = new Node('Dune', new Node('Emma', new Node('Circe')));
        $head = new Node('Beloved', $head);

        for ($node = $head; $node !== null; $node = $node->next) {
            echo $node->value . "\n";
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does each node of a singly linked list hold?',
            'options' => [
                'Its index and value',
                'A value and a link to the next node',
                'Links to every other node',
                'A copy of the whole list',
            ],
            'correct_option' => 1,
            'explanation' => 'Following the `next` links from the head visits every node in turn.',
        ],
        [
            'question' => 'What is the Big-O of adding a new item at the front of a linked list?',
            'options' => ['O(1)', 'O(log n)', 'O(n)', 'O(n²)'],
            'correct_option' => 0,
            'explanation' => 'Create a node pointing at the old head and make it the head. Nothing else moves.',
        ],
        [
            'question' => 'What is the Big-O of reading the item at position k in a linked list?',
            'options' => ['O(1)', 'O(k), because you must walk past the k items before it', 'O(log n)', 'O(n²)'],
            'correct_option' => 1,
            'explanation' => 'Unlike an array, a linked list can\'t jump to a position.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Node
                {
                    public function __construct(public int $value, public ?Node $next = null) {}
                }

                $head = new Node(1, new Node(2, new Node(3)));
                $head->next = $head->next->next;

                for ($n = $head; $n !== null; $n = $n->next) {
                    echo $n->value;
                }
                PHP,
            'options' => ['`123`', '`13`', '`12`', '`3`'],
            'correct_option' => 1,
            'explanation' => 'Pointing the first node past the second removes the second from the list.',
        ],
        [
            'question' => 'How do you remove the head of a linked list?',
            'options' => [
                'Set the head\'s value to `null`',
                'Make the head\'s `next` node the new head',
                'Walk to the end and back',
                'You can\'t remove the head',
            ],
            'correct_option' => 1,
            'explanation' => 'Once nothing refers to the old head, PHP frees it.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Node
                {
                    public function __construct(public int $value, public ?Node $next = null) {}
                }

                $previous = null;
                $current = new Node(1, new Node(2, new Node(3)));
                while ($current !== null) {
                    $next = $current->next;
                    $current->next = $previous;
                    $previous = $current;
                    $current = $next;
                }

                for ($n = $previous; $n !== null; $n = $n->next) {
                    echo $n->value;
                }
                PHP,
            'options' => ['`123`', '`321`', '`3`', '`1`'],
            'correct_option' => 1,
            'explanation' => 'Each link is turned around, so the old last node becomes the head of the reversed list.',
        ],
        [
            'question' => 'In the two-pointer trick for finding the middle node, how fast does each pointer move?',
            'options' => [
                'Both one step at a time',
                'One moves one step at a time, the other two',
                'One starts at the end and walks back',
                'They jump to random nodes',
            ],
            'correct_option' => 1,
            'explanation' => 'When the fast pointer reaches the end, the slow one has covered half the distance.',
        ],
        [
            'question' => 'In everyday PHP, when is a linked list a better choice than an array?',
            'options' => [
                'Almost always',
                'When items are constantly added and removed at the ends or in the middle, and you rarely need a position',
                'When you need to read items by position quickly',
                'When you want to sort the items',
            ],
            'correct_option' => 1,
            'explanation' => 'Arrays are fast to read by position and well optimised, so they\'re the default.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'How long is it?',
            'prompt' => 'Write `listLength(?Node $head): int` returning the number of nodes in the list, by walking it.',
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function listLength(?Node $head): int
                {
                    return $head === null ? 0 : 1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function listLength(?Node $head): int
                {
                    $count = 0;
                    for ($node = $head; $node !== null; $node = $node->next) {
                        $count++;
                    }

                    return $count;
                }
                PHP,
            'hint' => 'Follow `->next` from the head, counting nodes, until you reach `null`.',
            'checks' => [
                ['label' => 'Counts nodes', 'code' => "return expect_same([listLength(new Node('a', new Node('b', new Node('c')))), listLength(new Node('a')), listLength(null)], [3, 1, 0]);"],
            ],
        ],
        [
            'title' => 'From an array, and back',
            'prompt' => 'Write `fromArray(array $values): ?Node` building a linked list with the values in the same order (or `null` for an empty array), and `toArray(?Node $head): array` turning a list back into an array.',
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function fromArray(array $values): ?Node
                {
                    return null;
                }

                function toArray(?Node $head): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function fromArray(array $values): ?Node
                {
                    $head = null;
                    foreach (array_reverse($values) as $value) {
                        $head = new Node($value, $head);
                    }

                    return $head;
                }

                function toArray(?Node $head): array
                {
                    $values = [];
                    for ($node = $head; $node !== null; $node = $node->next) {
                        $values[] = $node->value;
                    }

                    return $values;
                }
                PHP,
            'hint' => 'Building is easiest backwards: go through the values in reverse, putting each new node at the front.',
            'checks' => [
                ['label' => 'Round trip', 'code' => "return expect_same([toArray(fromArray(['Dune', 'Emma', 'Circe'])), toArray(fromArray([]))], [['Dune', 'Emma', 'Circe'], []]);"],
                ['label' => 'fromArray() builds real nodes', 'code' => "\$head = fromArray([1, 2]);\nreturn (\$head instanceof Node && \$head->value === 1 && \$head->next?->value === 2 && \$head->next->next === null) ?: 'fromArray([1, 2]) should give Node(1) → Node(2).';"],
            ],
        ],
        [
            'title' => 'Remove every copy',
            'prompt' => 'Write `removeAll(?Node $head, mixed $value): ?Node` that removes **every** node holding the value (compared with `===`) and returns the new head, which may be different, or `null`.',
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function removeAll(?Node $head, mixed $value): ?Node
                {
                    return $head;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function removeAll(?Node $head, mixed $value): ?Node
                {
                    while ($head !== null && $head->value === $value) {
                        $head = $head->next;
                    }

                    $node = $head;
                    while ($node?->next !== null) {
                        if ($node->next->value === $value) {
                            $node->next = $node->next->next;
                        } else {
                            $node = $node->next;
                        }
                    }

                    return $head;
                }
                PHP,
            'hint' => 'First skip any matching nodes at the front. Then walk on, and whenever the **next** node matches, link past it (without moving on, in case the one after matches too).',
            'checks' => [
                ['label' => 'Copies anywhere are removed', 'code' => <<<'PHP'
                    $list = new Node(1, new Node(2, new Node(1, new Node(1, new Node(3, new Node(1))))));
                    $values = [];
                    for ($n = removeAll($list, 1); $n !== null; $n = $n->next) $values[] = $n->value;
                    return expect_same($values, [2, 3]);
                    PHP],
                ['label' => 'Everything removed gives null', 'code' => "return expect_same(removeAll(new Node('x', new Node('x')), 'x'), null);"],
                ['label' => 'Nothing to remove', 'code' => "\$list = new Node('a', new Node('b'));\nreturn (removeAll(\$list, 'z') === \$list && \$list->next->value === 'b') ?: 'The list should be unchanged.';"],
            ],
        ],
        [
            'title' => 'Reverse it in place',
            'prompt' => 'Write `reverseList(?Node $head): ?Node` that reverses the list by turning its links around, without creating any new nodes, and returns the new head.',
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function reverseList(?Node $head): ?Node
                {
                    return $head;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function reverseList(?Node $head): ?Node
                {
                    $reversed = null;
                    while ($head !== null) {
                        $rest = $head->next;
                        $head->next = $reversed;
                        $reversed = $head;
                        $head = $rest;
                    }

                    return $reversed;
                }
                PHP,
            'hint' => 'Keep three variables: the reversed part so far, the current node, and the rest of the list. Point the current node at the reversed part, then move on.',
            'checks' => [
                ['label' => 'The list is reversed', 'code' => <<<'PHP'
                    $values = [];
                    for ($n = reverseList(new Node('a', new Node('b', new Node('c')))); $n !== null; $n = $n->next) $values[] = $n->value;
                    return expect_same($values, ['c', 'b', 'a']);
                    PHP],
                ['label' => 'The same nodes are reused', 'code' => "\$last = new Node('c');\n\$head = new Node('a', new Node('b', \$last));\nreturn (reverseList(\$head) === \$last && \$head->next === null) ?: 'Turn the existing links round instead of making new nodes.';"],
                ['label' => 'Empty and single lists', 'code' => "\$one = new Node('x');\nreturn expect_same([reverseList(null), reverseList(\$one) === \$one], [null, true]);"],
            ],
        ],
        [
            'title' => 'Does it loop?',
            'prompt' => "A broken list might link back to an earlier node, so walking it would never end. Write `hasLoop(?Node \$head): bool` using two pointers: a slow one moving one step at a time and a fast one moving two. If they ever meet, there's a loop.",
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function hasLoop(?Node $head): bool
                {
                    return false;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public mixed $value, public ?Node $next = null) {}
                }

                function hasLoop(?Node $head): bool
                {
                    $slow = $head;
                    $fast = $head;

                    while ($fast !== null && $fast->next !== null) {
                        $slow = $slow->next;
                        $fast = $fast->next->next;
                        if ($slow === $fast) {
                            return true;
                        }
                    }

                    return false;
                }
                PHP,
            'hint' => 'While the fast pointer and its next node aren\'t `null`, move slow one step and fast two. Compare them with `===`.',
            'checks' => [
                ['label' => 'Lists without a loop', 'code' => 'return expect_same([hasLoop(new Node(1, new Node(2, new Node(3)))), hasLoop(new Node(1)), hasLoop(null)], [false, false, false]);'],
                ['label' => 'Lists with a loop', 'code' => <<<'PHP'
                    // Nodes that give up after many reads, so a walk that never stops fails instead of hanging.
                    if (! class_exists('GuardedNode', false)) {
                        eval('class GuardedNode extends Node {
                            public static int $reads = 0;
                            public ?Node $next {
                                get {
                                    if (++self::$reads > 100000) {
                                        throw new RuntimeException("hasLoop() kept walking round the loop. Use a slow and a fast pointer, and stop when they meet.");
                                    }
                                    return $this->next;
                                }
                            }
                        }');
                    }
                    GuardedNode::$reads = 0;
                    $c = new GuardedNode(3);
                    $head = new GuardedNode(1, new GuardedNode(2, $c));
                    $c->next = $head->next;
                    $self = new GuardedNode('me');
                    $self->next = $self;
                    return expect_same([hasLoop($head), hasLoop($self)], [true, true]);
                    PHP],
            ],
        ],
        [
            'title' => 'Merge two sorted lists',
            'prompt' => 'Write `mergeLists(?Node $a, ?Node $b): ?Node` that merges two linked lists of numbers, each sorted from smallest to largest, into one sorted list, by relinking the existing nodes.',
            'starter_code' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public int $value, public ?Node $next = null) {}
                }

                function mergeLists(?Node $a, ?Node $b): ?Node
                {
                    return $a ?? $b;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Node
                {
                    public function __construct(public int $value, public ?Node $next = null) {}
                }

                function mergeLists(?Node $a, ?Node $b): ?Node
                {
                    $start = new Node(0);
                    $tail = $start;

                    while ($a !== null && $b !== null) {
                        if ($a->value <= $b->value) {
                            $tail->next = $a;
                            $a = $a->next;
                        } else {
                            $tail->next = $b;
                            $b = $b->next;
                        }
                        $tail = $tail->next;
                    }
                    $tail->next = $a ?? $b;

                    return $start->next;
                }
                PHP,
            'hint' => 'A dummy starting node makes it easy: keep a `$tail`, attach the smaller of the two current nodes to it, and move on. Attach whatever is left at the end.',
            'checks' => [
                ['label' => 'Merged in order', 'code' => <<<'PHP'
                    $values = [];
                    for ($n = mergeLists(new Node(1, new Node(4, new Node(9))), new Node(2, new Node(3, new Node(10)))); $n !== null; $n = $n->next) $values[] = $n->value;
                    return expect_same($values, [1, 2, 3, 4, 9, 10]);
                    PHP],
                ['label' => 'One list empty', 'code' => "\$b = new Node(5);\nreturn expect_same([mergeLists(null, \$b) === \$b, mergeLists(null, null)], [true, null]);"],
            ],
        ],
    ],
];
