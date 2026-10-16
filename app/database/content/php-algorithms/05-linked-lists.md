## Lists made of links

An array keeps its items side by side in memory, which makes reading any position instant but inserting at the front
slow. A **linked list** keeps each item in its own **node**, which holds a value and a link to the next node. The list
only needs to remember the first node, the **head**:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

$head = new Node('Dune', new Node('Emma', new Node('Circe')));

for ($node = $head; $node !== null; $node = $node->next) {
    echo $node->value . "\n";
}
```

Walking a linked list means following `next` links until you reach `null`.

## Adding at the front is O(1)

To add at the front, make a new node pointing at the old head, and make it the new head. Nothing else moves, so it
takes the same time however long the list is:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

class LinkedList
{
    public ?Node $head = null;

    public function prepend(mixed $value): void
    {
        $this->head = new Node($value, $this->head);
    }

    public function toArray(): array
    {
        $values = [];
        for ($node = $this->head; $node !== null; $node = $node->next) {
            $values[] = $node->value;
        }
        return $values;
    }
}

$list = new LinkedList();
foreach (['Circe', 'Emma', 'Dune'] as $title) {
    $list->prepend($title);
}
echo implode(', ', $list->toArray());
```

## Reading by position is O(n)

The price: to reach the fifth item you must walk past the first four. There's no jumping straight to an index:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

function itemAt(?Node $head, int $index): mixed
{
    $node = $head;
    for ($i = 0; $i < $index && $node !== null; $i++) {
        $node = $node->next;
    }
    return $node?->value;
}

$head = new Node('A', new Node('B', new Node('C', new Node('D'))));
echo itemAt($head, 2) . ' ' . var_export(itemAt($head, 9), true);
```

## Removing a node

To remove a node, change the previous node's `next` to skip over it. The removed node is no longer reachable, and PHP
frees it. Removing the head is a special case: just move the head on:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

function remove(?Node $head, mixed $value): ?Node
{
    if ($head?->value === $value) {
        return $head->next;
    }
    for ($node = $head; $node?->next !== null; $node = $node->next) {
        if ($node->next->value === $value) {
            $node->next = $node->next->next;
            break;
        }
    }
    return $head;
}

$head = new Node('Dune', new Node('Emma', new Node('Circe')));
$head = remove($head, 'Emma');
$head = remove($head, 'Dune');
echo $head->value . ' then ' . var_export($head->next, true);
```

## Reversing a list

Reversing a linked list in place is a famous exercise: walk through it, turning each `next` link around to point
backwards, while keeping track of the previous node and the next one:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

function reverse(?Node $head): ?Node
{
    $previous = null;
    $current = $head;
    while ($current !== null) {
        $next = $current->next;
        $current->next = $previous;
        $previous = $current;
        $current = $next;
    }
    return $previous;
}

$head = reverse(new Node(1, new Node(2, new Node(3))));
for ($node = $head; $node !== null; $node = $node->next) {
    echo $node->value;
}
```

## Two pointers on a list

Many linked-list tricks use two pointers moving at different speeds. To find the middle node, move one pointer one step
at a time and the other two steps; when the fast one reaches the end, the slow one is in the middle:

```php
class Node
{
    public function __construct(public mixed $value, public ?Node $next = null) {}
}

function middle(Node $head): mixed
{
    $slow = $head;
    $fast = $head;
    while ($fast->next !== null && $fast->next->next !== null) {
        $slow = $slow->next;
        $fast = $fast->next->next;
    }
    return $slow->value;
}

echo middle(new Node('A', new Node('B', new Node('C', new Node('D', new Node('E'))))));
```

The same idea detects a loop: if the fast pointer ever meets the slow one, the list goes round in a circle.

## `SplDoublyLinkedList`, and when to use lists

PHP's `SplDoublyLinkedList` is a ready-made list where each node links both ways, so it's O(1) at both ends.
`SplStack` and `SplQueue` from the last lesson are built on it:

```php
$list = new SplDoublyLinkedList();
$list->push('Emma');
$list->push('Circe');
$list->unshift('Dune');

foreach ($list as $title) {
    echo "$title\n";
}
echo 'Last: ' . $list->top();
```

In everyday PHP, arrays are usually the better choice: they're fast for reading by position and very well optimised.
Linked lists earn their place when items are added and removed at the ends or in the middle constantly, and you hold
a reference to the node.

## Putting it together

A list that keeps itself sorted as items are inserted, by walking to the right place and splicing the new node in:

```php
class Node
{
    public function __construct(public int $value, public ?Node $next = null) {}
}

function insertSorted(?Node $head, int $value): Node
{
    if ($head === null || $value <= $head->value) {
        return new Node($value, $head);
    }
    $node = $head;
    while ($node->next !== null && $node->next->value < $value) {
        $node = $node->next;
    }
    $node->next = new Node($value, $node->next);
    return $head;
}

$head = null;
foreach ([1965, 1813, 1987, 1937] as $year) {
    $head = insertSorted($head, $year);
}

for ($node = $head; $node !== null; $node = $node->next) {
    echo $node->value . ' ';
}
```

Next, try the quiz to check your understanding, then solve the challenges.
