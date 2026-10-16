## Graphs

A **graph** is a set of **nodes** (also called vertices) joined by **edges**. Unlike a tree, a graph can have cycles
and nodes with many connections. Road maps, friendships, links between web pages and "customers who bought this
also bought" are all graphs. Edges can be one-way (**directed**) or two-way, and can carry a **weight**, such as a
distance.

## Storing a graph: adjacency lists

The usual way to store a graph is an **adjacency list**: a map from each node to the list of its neighbours. For
the shop's delivery towns:

```php
$roads = [
    'Leeds' => ['York', 'Manchester'],
    'York' => ['Leeds', 'Hull'],
    'Manchester' => ['Leeds', 'Liverpool'],
    'Hull' => ['York'],
    'Liverpool' => ['Manchester'],
];

echo 'From Leeds you can reach ' . implode(' and ', $roads['Leeds']) . ' directly.';
```

It uses memory in proportion to the number of edges, and listing a node's neighbours is instant.

## Breadth-first search

**Breadth-first search** (BFS) explores a graph in rings: first every neighbour of the start, then all of their
neighbours, and so on. A queue keeps the order, and a set of visited nodes stops it going round in circles:

```php
$roads = [
    'Leeds' => ['York', 'Manchester'],
    'York' => ['Leeds', 'Hull'],
    'Manchester' => ['Leeds', 'Liverpool'],
    'Hull' => ['York'],
    'Liverpool' => ['Manchester'],
];

function bfs(array $graph, string $start): array
{
    $visited = [$start => true];
    $queue = new SplQueue();
    $queue->enqueue($start);
    $order = [];

    while (! $queue->isEmpty()) {
        $node = $queue->dequeue();
        $order[] = $node;
        foreach ($graph[$node] ?? [] as $neighbour) {
            if (! isset($visited[$neighbour])) {
                $visited[$neighbour] = true;
                $queue->enqueue($neighbour);
            }
        }
    }
    return $order;
}

echo implode(' → ', bfs($roads, 'Leeds'));
```

BFS visits every node and edge once: O(V + E).

## Shortest paths by number of steps

Because BFS works outwards ring by ring, the first time it reaches a node is by the **fewest** edges. Remembering
where each node was reached from lets you rebuild the route:

```php
$roads = [
    'Leeds' => ['York', 'Manchester'],
    'York' => ['Leeds', 'Hull'],
    'Manchester' => ['Leeds', 'Liverpool'],
    'Hull' => ['York'],
    'Liverpool' => ['Manchester'],
];

function route(array $graph, string $from, string $to): ?array
{
    $cameFrom = [$from => null];
    $queue = new SplQueue();
    $queue->enqueue($from);

    while (! $queue->isEmpty()) {
        $node = $queue->dequeue();
        if ($node === $to) {
            $path = [];
            for ($step = $to; $step !== null; $step = $cameFrom[$step]) {
                array_unshift($path, $step);
            }
            return $path;
        }
        foreach ($graph[$node] ?? [] as $next) {
            if (! array_key_exists($next, $cameFrom)) {
                $cameFrom[$next] = $node;
                $queue->enqueue($next);
            }
        }
    }
    return null;
}

echo implode(' → ', route($roads, 'Hull', 'Liverpool'));
```

## Depth-first search

**Depth-first search** (DFS) goes as deep as it can along one path before backing up to try the next. It's naturally
recursive, with a visited set:

```php
$links = [
    'home' => ['books', 'about'],
    'books' => ['dune', 'emma'],
    'dune' => ['home'],
    'emma' => [],
    'about' => [],
];

function dfs(array $graph, string $node, array &$visited = []): array
{
    $visited[$node] = true;
    $order = [$node];
    foreach ($graph[$node] ?? [] as $next) {
        if (! isset($visited[$next])) {
            $order = [...$order, ...dfs($graph, $next, $visited)];
        }
    }
    return $order;
}

echo implode(' → ', dfs($links, 'home'));
```

Use DFS for "is there any path?", for exploring every possibility, and for detecting cycles. Use BFS when you need the
fewest steps.

## Connected groups

DFS (or BFS) from each node not yet visited finds the separate **connected components** of a graph: groups of nodes
that can reach each other. Here, readers who share books:

```php
$sharedBooks = [
    'Amara' => ['Ben'],
    'Ben' => ['Amara', 'Cy'],
    'Cy' => ['Ben'],
    'Dee' => ['Eli'],
    'Eli' => ['Dee'],
    'Fay' => [],
];

function component(array $graph, string $start, array &$seen): array
{
    $group = [];
    $stack = [$start];
    $seen[$start] = true;
    while ($stack) {
        $node = array_pop($stack);
        $group[] = $node;
        foreach ($graph[$node] as $next) {
            if (! isset($seen[$next])) {
                $seen[$next] = true;
                $stack[] = $next;
            }
        }
    }
    sort($group);
    return $group;
}

$seen = [];
foreach (array_keys($sharedBooks) as $reader) {
    if (! isset($seen[$reader])) {
        echo implode(', ', component($sharedBooks, $reader, $seen)) . "\n";
    }
}
```

This version uses its own stack instead of recursion: the same depth-first idea, without the risk of very deep
recursion.

## Weighted graphs and Dijkstra's algorithm

When edges have distances, the fewest **steps** isn't always the shortest **distance**. **Dijkstra's algorithm** finds
the shortest distance from a start node to every other node. It always settles the closest unsettled node next, using
a priority queue, and updates its neighbours' best-known distances:

```php
$distances = [
    'Leeds' => ['York' => 40, 'Manchester' => 70, 'Sheffield' => 55],
    'York' => ['Leeds' => 40, 'Hull' => 60],
    'Manchester' => ['Leeds' => 70, 'Sheffield' => 60],
    'Sheffield' => ['Leeds' => 55, 'Manchester' => 60, 'Hull' => 110],
    'Hull' => ['York' => 60, 'Sheffield' => 110],
];

function dijkstra(array $graph, string $start): array
{
    $best = [$start => 0];
    $queue = new SplPriorityQueue();
    $queue->insert($start, 0);

    while (! $queue->isEmpty()) {
        $node = $queue->extract();
        foreach ($graph[$node] as $next => $length) {
            $distance = $best[$node] + $length;
            if (! isset($best[$next]) || $distance < $best[$next]) {
                $best[$next] = $distance;
                $queue->insert($next, -$distance);
            }
        }
    }
    asort($best);
    return $best;
}

print_r(dijkstra($distances, 'Manchester'));
```

`SplPriorityQueue` hands out the **highest** priority first, so distances are inserted as negative numbers to get the
nearest first. Dijkstra needs weights of zero or more.

## Putting it together

Planning a delivery route with the shortest distance and the towns along the way, by also remembering each town's
best previous stop:

```php
$distances = [
    'Leeds' => ['York' => 40, 'Sheffield' => 55],
    'York' => ['Leeds' => 40, 'Hull' => 60],
    'Sheffield' => ['Leeds' => 55, 'Hull' => 110],
    'Hull' => ['York' => 60, 'Sheffield' => 110],
];

function shortestRoute(array $graph, string $from, string $to): array
{
    $best = [$from => 0];
    $previous = [];
    $queue = new SplPriorityQueue();
    $queue->insert($from, 0);

    while (! $queue->isEmpty()) {
        $node = $queue->extract();
        foreach ($graph[$node] as $next => $length) {
            $distance = $best[$node] + $length;
            if (! isset($best[$next]) || $distance < $best[$next]) {
                $best[$next] = $distance;
                $previous[$next] = $node;
                $queue->insert($next, -$distance);
            }
        }
    }

    $path = [$to];
    while (end($path) !== $from) {
        $path[] = $previous[end($path)];
    }
    return [array_reverse($path), $best[$to]];
}

[$path, $km] = shortestRoute($distances, 'Sheffield', 'Hull');
echo implode(' → ', $path) . " ($km km)";
```

Next, try the quiz to check your understanding, then solve the challenges.
