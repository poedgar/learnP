<?php

// Lesson 4 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'stacks-and-queues',
    'title' => 'Stacks and queues',
    'summary' => 'Last in first out and first in first out: stacks and queues with arrays and SPL classes, priority queues, and the classic problems they solve.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $stack = new SplStack();
        $queue = new SplQueue();

        foreach (['Dune', 'Emma', 'Circe'] as $title) {
            $stack->push($title);
            $queue->enqueue($title);
        }

        echo 'Stack gives ' . $stack->pop() . ', queue gives ' . $queue->dequeue();
        PHP,

    'quiz' => [
        [
            'question' => 'Which item does a stack give back next?',
            'options' => ['The first one added', 'The most recently added one', 'The smallest one', 'A random one'],
            'correct_option' => 1,
            'explanation' => 'Last in, first out, like taking the top plate off a pile.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $queue = new SplQueue();
                $queue->enqueue('A');
                $queue->enqueue('B');
                $queue->enqueue('C');
                $queue->dequeue();
                $queue->enqueue('D');
                echo $queue->dequeue() . $queue->dequeue();
                PHP,
            'options' => ['`BC`', '`DC`', '`CD`', '`AB`'],
            'correct_option' => 0,
            'explanation' => 'A leaves first; D joins the back behind B and C, so the next two out are B then C.',
        ],
        [
            'question' => 'Why is `array_shift()` a poor way to take items from a long queue?',
            'options' => [
                'It removes the last item instead',
                'It renumbers every remaining item, so each call is O(n)',
                'It doesn\'t work on arrays',
                'It\'s deprecated',
            ],
            'correct_option' => 1,
            'explanation' => '`SplQueue::dequeue()` takes from the front in O(1).',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $stack = [];
                foreach ([1, 2, 3, 4] as $n) {
                    $stack[] = $n;
                    if ($n % 2 === 0) {
                        array_pop($stack);
                    }
                }
                echo implode(',', $stack);
                PHP,
            'options' => ['`1,3`', '`2,4`', '`1,2,3,4`', '`1`'],
            'correct_option' => 0,
            'explanation' => 'Each even number is pushed and immediately popped again, leaving 1 and 3.',
        ],
        [
            'question' => 'Why is a stack the right tool for checking that brackets match?',
            'options' => [
                'Because brackets are usually at the end of the text',
                'Because each closing bracket must match the most recently opened one that\'s still open',
                'Because stacks sort the brackets',
                'Because stacks are faster than strings',
            ],
            'correct_option' => 1,
            'explanation' => 'The top of the stack is always the innermost open bracket, exactly the one that must close next.',
        ],
        [
            'question' => 'Which data structure suits "serve customers in the order they arrived"?',
            'options' => ['A stack', 'A queue', 'A priority queue', 'A sorted array'],
            'correct_option' => 1,
            'explanation' => 'First in, first out.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $q = new SplPriorityQueue();
                $q->insert('low', 1);
                $q->insert('high', 9);
                $q->insert('medium', 5);
                echo $q->extract() . ' ' . $q->extract();
                PHP,
            'options' => ['`low medium`', '`high medium`', '`low high`', '`high low`'],
            'correct_option' => 1,
            'explanation' => 'A priority queue always gives the highest priority next, whatever the order items were added.',
        ],
        [
            'question' => 'What is the Big-O of pushing onto and popping from a stack?',
            'options' => ['O(1)', 'O(log n)', 'O(n)', 'O(n²)'],
            'correct_option' => 0,
            'explanation' => 'Both happen at the top, without touching any other item.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Reverse with a stack',
            'prompt' => "Write `reverseWords(string \$sentence): string` that reverses the order of the words, by pushing each word onto a stack and popping them off. Don't use `array_reverse()`.",
            'starter_code' => <<<'PHP'
                <?php

                function reverseWords(string $sentence): string
                {
                    return $sentence;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function reverseWords(string $sentence): string
                {
                    $stack = new SplStack();
                    foreach (explode(' ', $sentence) as $word) {
                        $stack->push($word);
                    }

                    $words = [];
                    while (! $stack->isEmpty()) {
                        $words[] = $stack->pop();
                    }

                    return implode(' ', $words);
                }
                PHP,
            'hint' => 'Push every word, then pop until the stack is empty, collecting the words.',
            'checks' => [
                ['label' => 'Words are reversed', 'code' => "return expect_same([reverseWords('the name of the wind'), reverseWords('Dune')], ['wind the of name the', 'Dune']);"],
                ['label' => 'No array_reverse()', 'code' => "return ! str_contains(learnp_code(), 'array_reverse') ?: 'Use a stack instead of array_reverse().';"],
            ],
        ],
        [
            'title' => 'Balanced brackets',
            'prompt' => 'Write `isBalanced(string $text): bool` that is `true` when every `(`, `[` and `{` in the text is closed by its partner in the right order, ignoring all other characters.',
            'starter_code' => <<<'PHP'
                <?php

                function isBalanced(string $text): bool
                {
                    return substr_count($text, '(') === substr_count($text, ')');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isBalanced(string $text): bool
                {
                    $partners = [')' => '(', ']' => '[', '}' => '{'];
                    $stack = [];

                    foreach (str_split($text) as $char) {
                        if ($char === '(' || $char === '[' || $char === '{') {
                            $stack[] = $char;
                        } elseif (isset($partners[$char]) && array_pop($stack) !== $partners[$char]) {
                            return false;
                        }
                    }

                    return $stack === [];
                }
                PHP,
            'hint' => 'Push opening brackets. For a closing bracket, pop and check it\'s the partner. At the end, the stack must be empty.',
            'checks' => [
                ['label' => 'Balanced texts', 'code' => "return expect_same(array_map('isBalanced', ['(a[b]{c})', '', 'no brackets', '{[()()]}']), [true, true, true, true]);"],
                ['label' => 'Unbalanced texts', 'code' => "return expect_same(array_map('isBalanced', ['(a[b)]', '((x)', ')(', '}']), [false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'A browser history',
            'prompt' => "Write a class `History` for a browser's Back and Forward buttons, using two stacks. `visit(string \$page)` goes to a new page (and clears the forward history), `back(): ?string` goes back one page and returns it, and `forward(): ?string` goes forward again. Both return `null` when there's nowhere to go. `current(): ?string` returns the page you're on.",
            'starter_code' => <<<'PHP'
                <?php

                class History
                {
                    private ?string $current = null;

                    public function visit(string $page): void
                    {
                        $this->current = $page;
                    }

                    public function current(): ?string
                    {
                        return $this->current;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class History
                {
                    private ?string $current = null;
                    private array $back = [];
                    private array $forward = [];

                    public function visit(string $page): void
                    {
                        if ($this->current !== null) {
                            $this->back[] = $this->current;
                        }
                        $this->current = $page;
                        $this->forward = [];
                    }

                    public function back(): ?string
                    {
                        if ($this->back === []) {
                            return null;
                        }
                        $this->forward[] = $this->current;
                        $this->current = array_pop($this->back);

                        return $this->current;
                    }

                    public function forward(): ?string
                    {
                        if ($this->forward === []) {
                            return null;
                        }
                        $this->back[] = $this->current;
                        $this->current = array_pop($this->forward);

                        return $this->current;
                    }

                    public function current(): ?string
                    {
                        return $this->current;
                    }
                }
                PHP,
            'hint' => 'Keep a "back" stack and a "forward" stack. Going back pushes the current page onto "forward" and pops from "back"; going forward does the opposite. Visiting a new page clears "forward".',
            'checks' => [
                ['label' => 'Back and forward', 'code' => "\$h = new History();\nforeach (['home', 'books', 'dune'] as \$p) { \$h->visit(\$p); }\nreturn expect_same([\$h->back(), \$h->back(), \$h->back(), \$h->forward(), \$h->current()], ['books', 'home', null, 'books', 'books']);"],
                ['label' => 'Visiting clears forward', 'code' => "\$h = new History();\n\$h->visit('home');\n\$h->visit('books');\n\$h->back();\n\$h->visit('about');\nreturn expect_same([\$h->forward(), \$h->back()], [null, 'home']);"],
            ],
        ],
        [
            'title' => 'Serve in order',
            'prompt' => "Write `serve(array \$events): array`. Each event is either `'arrive NAME'` or `'serve'`. Use an `SplQueue`: arrivals join the queue, and each `'serve'` takes the person at the front. Return the names in the order they were served (a `'serve'` with nobody waiting does nothing).",
            'starter_code' => <<<'PHP'
                <?php

                function serve(array $events): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function serve(array $events): array
                {
                    $queue = new SplQueue();
                    $served = [];

                    foreach ($events as $event) {
                        if (str_starts_with($event, 'arrive ')) {
                            $queue->enqueue(substr($event, 7));
                        } elseif ($event === 'serve' && ! $queue->isEmpty()) {
                            $served[] = $queue->dequeue();
                        }
                    }

                    return $served;
                }
                PHP,
            'hint' => 'For arrivals, `enqueue(substr($event, 7))`. For `serve`, `dequeue()` if the queue isn\'t empty.',
            'checks' => [
                ['label' => 'Served in arrival order', 'code' => "return expect_same(serve(['arrive Amara', 'arrive Ben', 'serve', 'arrive Cy', 'serve', 'serve']), ['Amara', 'Ben', 'Cy']);"],
                ['label' => 'Serving an empty queue does nothing', 'code' => "return expect_same(serve(['serve', 'arrive Dee', 'serve', 'serve']), ['Dee']);"],
            ],
        ],
        [
            'title' => 'Express first',
            'prompt' => "Write `packingOrder(array \$orders): array`. Each order is `['id' => …, 'priority' => …]`, where priority is `'express'`, `'standard'` or `'economy'`. Return the ids in packing order: all express orders first, then standard, then economy, keeping arrival order within each priority.",
            'starter_code' => <<<'PHP'
                <?php

                function packingOrder(array $orders): array
                {
                    return array_column($orders, 'id');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function packingOrder(array $orders): array
                {
                    $ranks = ['express' => 3, 'standard' => 2, 'economy' => 1];
                    $queue = new SplPriorityQueue();
                    $serial = PHP_INT_MAX;

                    foreach ($orders as $order) {
                        // Equal priorities come out in no fixed order, so add a falling serial number to keep arrival order.
                        $queue->insert($order['id'], [$ranks[$order['priority']], $serial--]);
                    }

                    $ids = [];
                    while (! $queue->isEmpty()) {
                        $ids[] = $queue->extract();
                    }

                    return $ids;
                }
                PHP,
            'hint' => 'An `SplPriorityQueue` with a priority of `[rank, serial]` works, where the serial number goes down for each order so earlier orders win ties. Sorting with `usort()` by rank (it\'s stable) works too.',
            'checks' => [
                ['label' => 'Express, then standard, then economy, in arrival order', 'code' => "\$orders = [\n    ['id' => 'A', 'priority' => 'standard'],\n    ['id' => 'B', 'priority' => 'express'],\n    ['id' => 'C', 'priority' => 'economy'],\n    ['id' => 'D', 'priority' => 'express'],\n    ['id' => 'E', 'priority' => 'standard'],\n];\nreturn expect_same(packingOrder(\$orders), ['B', 'D', 'A', 'E', 'C']);"],
                ['label' => 'No orders', 'code' => 'return expect_same(packingOrder([]), []);'],
            ],
        ],
        [
            'title' => 'Evaluate it',
            'prompt' => "Write `evaluate(string \$expression): int` for expressions in reverse Polish notation with whole numbers and the operators `+`, `-` and `*`, separated by spaces: `'2 3 + 4 *'` is 20. Use a stack.",
            'starter_code' => <<<'PHP'
                <?php

                function evaluate(string $expression): int
                {
                    return 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function evaluate(string $expression): int
                {
                    $stack = [];
                    foreach (explode(' ', trim($expression)) as $token) {
                        if (is_numeric($token)) {
                            $stack[] = (int) $token;
                            continue;
                        }
                        $right = array_pop($stack);
                        $left = array_pop($stack);
                        $stack[] = match ($token) {
                            '+' => $left + $right,
                            '-' => $left - $right,
                            '*' => $left * $right,
                        };
                    }

                    return array_pop($stack);
                }
                PHP,
            'hint' => 'Push numbers. For an operator, pop the right number, then the left one, and push the result. The answer is what\'s left at the end.',
            'checks' => [
                ['label' => 'Simple expressions', 'code' => "return expect_same([evaluate('2 3 + 4 *'), evaluate('5 1 2 + 4 * + 3 -'), evaluate('7')], [20, 14, 7]);"],
                ['label' => 'Order matters for subtraction', 'code' => "return expect_same(evaluate('10 4 -'), 6);"],
            ],
        ],
    ],
];
