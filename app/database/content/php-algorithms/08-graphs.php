<?php

// Lesson 8 of the Algorithms and data structures course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'graphs',
    'title' => 'Graphs',
    'summary' => 'Store graphs as adjacency lists, explore them with breadth-first and depth-first search, find connected groups, and find shortest distances with Dijkstra\'s algorithm.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        $roads = [
            'Leeds' => ['York', 'Manchester'],
            'York' => ['Leeds', 'Hull'],
            'Manchester' => ['Leeds'],
            'Hull' => ['York'],
        ];

        $visited = ['Leeds' => true];
        $queue = new SplQueue();
        $queue->enqueue('Leeds');

        while (! $queue->isEmpty()) {
            $town = $queue->dequeue();
            echo "$town\n";
            foreach ($roads[$town] as $next) {
                if (! isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue->enqueue($next);
                }
            }
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What is an adjacency list?',
            'options' => [
                'A sorted list of every node',
                'A map from each node to the list of its neighbours',
                'A grid of every pair of nodes',
                'A list of the shortest paths',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s compact, and listing a node\'s neighbours is instant.',
        ],
        [
            'question' => 'Which data structure drives breadth-first search?',
            'options' => ['A stack', 'A queue', 'A heap', 'A linked list'],
            'correct_option' => 1,
            'explanation' => 'First in, first out keeps nodes in order of distance from the start, ring by ring.',
        ],
        [
            'question' => 'Why do graph searches keep a set of visited nodes?',
            'options' => [
                'To sort the nodes',
                'So they don\'t go round cycles for ever, or process a node twice',
                'To count the edges',
                'They don\'t need one',
            ],
            'correct_option' => 1,
            'explanation' => 'Graphs can have cycles, unlike trees.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $graph = ['A' => ['B', 'C'], 'B' => ['D'], 'C' => ['D'], 'D' => []];

                $visited = ['A' => true];
                $queue = new SplQueue();
                $queue->enqueue('A');
                $order = '';
                while (! $queue->isEmpty()) {
                    $node = $queue->dequeue();
                    $order .= $node;
                    foreach ($graph[$node] as $next) {
                        if (! isset($visited[$next])) {
                            $visited[$next] = true;
                            $queue->enqueue($next);
                        }
                    }
                }
                echo $order;
                PHP,
            'options' => ['`ABCD`', '`ABDC`', '`ACDB`', '`ABD`'],
            'correct_option' => 0,
            'explanation' => 'BFS visits A, then both its neighbours B and C, then D, which is only visited once.',
        ],
        [
            'question' => 'Why does BFS find the route with the fewest steps?',
            'options' => [
                'It sorts the edges first',
                'It explores outwards ring by ring, so it reaches each node by the shortest number of edges first',
                'It tries every possible route',
                'It doesn\'t; DFS does',
            ],
            'correct_option' => 1,
            'explanation' => 'Every node one step away is visited before any node two steps away.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $graph = ['A' => ['B', 'C'], 'B' => ['D'], 'C' => [], 'D' => []];

                function visit(array $graph, string $node, array &$seen): string
                {
                    $seen[$node] = true;
                    $out = $node;
                    foreach ($graph[$node] as $next) {
                        if (! isset($seen[$next])) {
                            $out .= visit($graph, $next, $seen);
                        }
                    }
                    return $out;
                }

                $seen = [];
                echo visit($graph, 'A', $seen);
                PHP,
            'options' => ['`ABCD`', '`ABDC`', '`ACBD`', '`ADBC`'],
            'correct_option' => 1,
            'explanation' => 'DFS goes deep first: from A to B, then down to D, and only then back up to try C.',
        ],
        [
            'question' => 'When do you need Dijkstra\'s algorithm rather than BFS?',
            'options' => [
                'When the edges have different lengths (weights), and you want the shortest total distance',
                'When the graph has no cycles',
                'When you want to visit every node',
                'When the graph is a tree',
            ],
            'correct_option' => 0,
            'explanation' => 'BFS counts edges; Dijkstra adds up their weights.',
        ],
        [
            'question' => 'Why does the lesson\'s Dijkstra insert `-$distance` into `SplPriorityQueue`?',
            'options' => [
                'Because distances are negative',
                'Because `SplPriorityQueue` gives the highest priority first, and we want the nearest node first',
                'To make the algorithm faster',
                'It\'s a mistake',
            ],
            'correct_option' => 1,
            'explanation' => 'Negating the distance turns "smallest distance" into "highest priority".',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Who can you reach?',
            'prompt' => 'Write `reachable(array $graph, string $start): array` returning every node that can be reached from `$start` (including itself), sorted alphabetically. The graph is an adjacency list, and may have cycles.',
            'starter_code' => <<<'PHP'
                <?php

                function reachable(array $graph, string $start): array
                {
                    return [$start, ...($graph[$start] ?? [])];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function reachable(array $graph, string $start): array
                {
                    $seen = [$start => true];
                    $stack = [$start];

                    while ($stack) {
                        $node = array_pop($stack);
                        foreach ($graph[$node] ?? [] as $next) {
                            if (! isset($seen[$next])) {
                                $seen[$next] = true;
                                $stack[] = $next;
                            }
                        }
                    }

                    $nodes = array_keys($seen);
                    sort($nodes);

                    return $nodes;
                }
                PHP,
            'hint' => 'BFS or DFS from the start with a visited set. The visited set\'s keys are the answer; sort them.',
            'checks' => [
                ['label' => 'Following one-way links', 'code' => "\$g = ['a' => ['b'], 'b' => ['c'], 'c' => ['a'], 'd' => ['a']];\nreturn expect_same([reachable(\$g, 'a'), reachable(\$g, 'd')], [['a', 'b', 'c'], ['a', 'b', 'c', 'd']]);"],
                ['label' => 'A node with no links', 'code' => "return expect_same(reachable(['x' => []], 'x'), ['x']);"],
            ],
        ],
        [
            'title' => 'Fewest changes',
            'prompt' => "Bus routes connect stops (an adjacency list). Write `fewestStops(array \$graph, string \$from, string \$to): int` returning the smallest number of journeys (edges) from one stop to the other, 0 if they're the same stop, or -1 if it can't be done. Use breadth-first search.",
            'starter_code' => <<<'PHP'
                <?php

                function fewestStops(array $graph, string $from, string $to): int
                {
                    return -1;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function fewestStops(array $graph, string $from, string $to): int
                {
                    $steps = [$from => 0];
                    $queue = new SplQueue();
                    $queue->enqueue($from);

                    while (! $queue->isEmpty()) {
                        $stop = $queue->dequeue();
                        if ($stop === $to) {
                            return $steps[$stop];
                        }
                        foreach ($graph[$stop] ?? [] as $next) {
                            if (! isset($steps[$next])) {
                                $steps[$next] = $steps[$stop] + 1;
                                $queue->enqueue($next);
                            }
                        }
                    }

                    return -1;
                }
                PHP,
            'hint' => 'BFS, storing each stop\'s number of steps when you first reach it (its parent\'s steps plus one). Return as soon as you take the destination off the queue.',
            'checks' => [
                ['label' => 'Fewest journeys', 'code' => "\$g = ['A' => ['B', 'C'], 'B' => ['D'], 'C' => ['D', 'E'], 'D' => ['F'], 'E' => ['F'], 'F' => []];\nreturn expect_same([fewestStops(\$g, 'A', 'F'), fewestStops(\$g, 'A', 'E'), fewestStops(\$g, 'C', 'C')], [3, 2, 0]);"],
                ['label' => 'Unreachable', 'code' => "return expect_same(fewestStops(['A' => ['B'], 'B' => [], 'C' => ['A']], 'A', 'C'), -1);"],
            ],
        ],
        [
            'title' => 'Groups of friends',
            'prompt' => 'Friendships go both ways. Write `countGroups(array $friends): int` counting the separate groups in an adjacency list, where everyone in a group is connected through friends of friends. People with no friends are a group of one.',
            'starter_code' => <<<'PHP'
                <?php

                function countGroups(array $friends): int
                {
                    return count($friends);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function countGroups(array $friends): int
                {
                    $seen = [];
                    $groups = 0;

                    foreach (array_keys($friends) as $person) {
                        if (isset($seen[$person])) {
                            continue;
                        }
                        $groups++;
                        $stack = [$person];
                        $seen[$person] = true;
                        while ($stack) {
                            $current = array_pop($stack);
                            foreach ($friends[$current] ?? [] as $friend) {
                                if (! isset($seen[$friend])) {
                                    $seen[$friend] = true;
                                    $stack[] = $friend;
                                }
                            }
                        }
                    }

                    return $groups;
                }
                PHP,
            'hint' => 'For each person not yet seen, start a new group and visit everyone reachable from them (BFS or DFS), marking them seen.',
            'checks' => [
                ['label' => 'Three groups', 'code' => "\$f = ['Amara' => ['Ben'], 'Ben' => ['Amara', 'Cy'], 'Cy' => ['Ben'], 'Dee' => ['Eli'], 'Eli' => ['Dee'], 'Fay' => []];\nreturn expect_same(countGroups(\$f), 3);"],
                ['label' => 'Everyone connected, and nobody', 'code' => "return expect_same([countGroups(['a' => ['b'], 'b' => ['c', 'a'], 'c' => ['b']]), countGroups([])], [1, 0]);"],
            ],
        ],
        [
            'title' => 'Does it go round in circles?',
            'prompt' => "Book recommendations form a directed graph (`'Dune' => ['Hyperion']` means Dune recommends Hyperion). Write `hasCycle(array \$graph): bool` that is `true` if following recommendations can ever lead back to a book you started from. Use DFS, tracking which books are on the current path.",
            'starter_code' => <<<'PHP'
                <?php

                function hasCycle(array $graph): bool
                {
                    return false;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function hasCycle(array $graph): bool
                {
                    $done = [];

                    $visit = function (string $node, array $onPath) use (&$visit, &$done, $graph): bool {
                        if (isset($onPath[$node])) {
                            return true;
                        }
                        if (isset($done[$node])) {
                            return false;
                        }
                        $onPath[$node] = true;
                        foreach ($graph[$node] ?? [] as $next) {
                            if ($visit($next, $onPath)) {
                                return true;
                            }
                        }
                        $done[$node] = true;

                        return false;
                    };

                    foreach (array_keys($graph) as $node) {
                        if ($visit($node, [])) {
                            return true;
                        }
                    }

                    return false;
                }
                PHP,
            'hint' => 'DFS from every node. Pass along the set of nodes on the current path; reaching one of them again means a cycle. Mark fully explored nodes as done, so they aren\'t explored twice.',
            'checks' => [
                ['label' => 'A cycle', 'code' => "return expect_same(hasCycle(['Dune' => ['Hyperion'], 'Hyperion' => ['Foundation'], 'Foundation' => ['Dune']]), true);"],
                ['label' => 'Shared books but no cycle', 'code' => "return expect_same(hasCycle(['A' => ['B', 'C'], 'B' => ['D'], 'C' => ['D'], 'D' => []]), false);"],
                ['label' => 'A book recommending itself', 'code' => "return expect_same(hasCycle(['X' => ['X']]), true);"],
            ],
        ],
        [
            'title' => 'Shortest distances',
            'prompt' => "Write `shortestDistances(array \$graph, string \$start): array` using Dijkstra's algorithm on a weighted graph (`'Leeds' => ['York' => 40, …]`), returning the shortest distance from the start to every reachable town, sorted by distance (then by name for ties).",
            'starter_code' => <<<'PHP'
                <?php

                function shortestDistances(array $graph, string $start): array
                {
                    return [$start => 0] + ($graph[$start] ?? []);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shortestDistances(array $graph, string $start): array
                {
                    $best = [$start => 0];
                    $queue = new SplPriorityQueue();
                    $queue->insert($start, 0);

                    while (! $queue->isEmpty()) {
                        $node = $queue->extract();
                        foreach ($graph[$node] ?? [] as $next => $length) {
                            $distance = $best[$node] + $length;
                            if (! isset($best[$next]) || $distance < $best[$next]) {
                                $best[$next] = $distance;
                                $queue->insert($next, -$distance);
                            }
                        }
                    }

                    uksort($best, fn ($a, $b) => [$best[$a], $a] <=> [$best[$b], $b]);

                    return $best;
                }
                PHP,
            'hint' => 'Dijkstra as in the lesson. To sort by distance and then name, `uksort($best, fn ($a, $b) => [$best[$a], $a] <=> [$best[$b], $b])`.',
            'checks' => [
                ['label' => 'Distances from Manchester', 'code' => "\$g = [\n    'Leeds' => ['York' => 40, 'Manchester' => 70, 'Sheffield' => 55],\n    'York' => ['Leeds' => 40, 'Hull' => 60],\n    'Manchester' => ['Leeds' => 70, 'Sheffield' => 60],\n    'Sheffield' => ['Leeds' => 55, 'Manchester' => 60, 'Hull' => 110],\n    'Hull' => ['York' => 60, 'Sheffield' => 110],\n];\nreturn expect_same(shortestDistances(\$g, 'Manchester'), ['Manchester' => 0, 'Sheffield' => 60, 'Leeds' => 70, 'York' => 110, 'Hull' => 170]);"],
                ['label' => 'A longer route can be shorter', 'code' => "\$g = ['A' => ['B' => 10, 'C' => 1], 'C' => ['B' => 2], 'B' => []];\nreturn expect_same(shortestDistances(\$g, 'A'), ['A' => 0, 'C' => 1, 'B' => 3]);"],
            ],
        ],
        [
            'title' => 'The route itself',
            'prompt' => "Write `bestRoute(array \$graph, string \$from, string \$to): ?array` returning `['path' => [...towns...], 'distance' => …]` for the shortest route in a weighted graph, or `null` if there's no route.",
            'starter_code' => <<<'PHP'
                <?php

                function bestRoute(array $graph, string $from, string $to): ?array
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function bestRoute(array $graph, string $from, string $to): ?array
                {
                    $best = [$from => 0];
                    $previous = [];
                    $queue = new SplPriorityQueue();
                    $queue->insert($from, 0);

                    while (! $queue->isEmpty()) {
                        $node = $queue->extract();
                        foreach ($graph[$node] ?? [] as $next => $length) {
                            $distance = $best[$node] + $length;
                            if (! isset($best[$next]) || $distance < $best[$next]) {
                                $best[$next] = $distance;
                                $previous[$next] = $node;
                                $queue->insert($next, -$distance);
                            }
                        }
                    }

                    if (! isset($best[$to])) {
                        return null;
                    }

                    $path = [$to];
                    while ($path[0] !== $from) {
                        array_unshift($path, $previous[$path[0]]);
                    }

                    return ['path' => $path, 'distance' => $best[$to]];
                }
                PHP,
            'hint' => 'Dijkstra, also remembering `$previous[$next] = $node` whenever a distance improves. Then walk back from the destination through `$previous`.',
            'checks' => [
                ['label' => 'The shortest route', 'code' => "\$g = ['Leeds' => ['York' => 40, 'Sheffield' => 55], 'York' => ['Leeds' => 40, 'Hull' => 60], 'Sheffield' => ['Leeds' => 55, 'Hull' => 110], 'Hull' => ['York' => 60, 'Sheffield' => 110]];\nreturn expect_same(bestRoute(\$g, 'Sheffield', 'Hull'), ['path' => ['Sheffield', 'Hull'], 'distance' => 110]);"],
                ['label' => 'A route through other towns', 'code' => "\$g = ['A' => ['B' => 10, 'C' => 1], 'C' => ['B' => 2], 'B' => ['D' => 1], 'D' => []];\nreturn expect_same(bestRoute(\$g, 'A', 'D'), ['path' => ['A', 'C', 'B', 'D'], 'distance' => 4]);"],
                ['label' => 'No route, and the same town', 'code' => "return expect_same([bestRoute(['A' => [], 'B' => []], 'A', 'B'), bestRoute(['A' => []], 'A', 'A')], [null, ['path' => ['A'], 'distance' => 0]]);"],
            ],
        ],
    ],
];
