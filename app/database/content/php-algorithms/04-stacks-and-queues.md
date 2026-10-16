## Two ways to wait in line

A **stack** and a **queue** both hold items to deal with later. They differ in which item comes out next:

- a **stack** is last in, first out (**LIFO**), like a pile of plates: you take the one you put on top most recently;
- a **queue** is first in, first out (**FIFO**), like a line at the till: whoever arrived first is served first.

Both add and remove in O(1), and many algorithms are built on them.

## Stacks with arrays

A PHP array works as a stack: `array_push()` (or `$stack[] = …`) puts an item on top, `array_pop()` takes the top
one off, and `end()` peeks at it without removing it:

```php
$stack = [];
$stack[] = 'Dune';
$stack[] = 'Emma';
$stack[] = 'Circe';

echo 'Top: ' . end($stack) . "\n";
echo array_pop($stack) . "\n";
echo array_pop($stack) . "\n";
echo count($stack) . ' left';
```

Both ends of the operation happen at the end of the array, which is why they're fast.

## Queues with arrays, and their cost

An array can be a queue too: add at the end, and take from the front with `array_shift()`. But `array_shift()`
renumbers every remaining item, so it's O(n) and becomes slow for long queues:

```php
$queue = ['Amara', 'Ben'];
$queue[] = 'Cy';

echo 'Serving ' . array_shift($queue) . "\n";
echo 'Serving ' . array_shift($queue) . "\n";
echo implode(', ', $queue) . ' still waiting';
```

For real work, use a class designed for it.

## `SplStack` and `SplQueue`

PHP's Standard PHP Library (SPL) has proper data structures. `SplStack` and `SplQueue` both offer O(1) operations at
both ends, and say what they mean:

```php
$queue = new SplQueue();
$queue->enqueue('Amara');
$queue->enqueue('Ben');
$queue->enqueue('Cy');
echo $queue->dequeue() . ' first, ' . count($queue) . " waiting\n";

$stack = new SplStack();
$stack->push('page 1');
$stack->push('page 2');
$stack->push('page 3');
echo 'Back to ' . $stack->pop() . ', then ' . $stack->top();
```

Both can be looped over with `foreach` and counted with `count()`.

## A stack in action: matching brackets

Checking that brackets are balanced is the classic stack problem. Push each opening bracket; for each closing one, the
top of the stack must be its partner:

```php
function balanced(string $text): bool
{
    $pairs = [')' => '(', ']' => '[', '}' => '{'];
    $stack = [];

    foreach (str_split($text) as $char) {
        if (in_array($char, $pairs, true)) {
            $stack[] = $char;
        } elseif (isset($pairs[$char])) {
            if (array_pop($stack) !== $pairs[$char]) {
                return false;
            }
        }
    }

    return $stack === [];
}

foreach (['(a[b]{c})', '(a[b)]', '((x)', ''] as $text) {
    echo "'$text': " . var_export(balanced($text), true) . "\n";
}
```

## Undo with a stack

An undo history is a stack: every change is pushed, and undo pops the most recent. (You met this as the Command pattern
in the design patterns course.)

```php
$text = '';
$history = new SplStack();

foreach (['Dune', ' is', ' great'] as $typed) {
    $history->push($text);
    $text .= $typed;
}

echo "$text\n";
$text = $history->pop();
echo "$text\n";
$text = $history->pop();
echo $text;
```

## A queue in action: processing in order

Queues keep work in arrival order: print jobs, customer messages, orders to pack. A queue also drives the breadth-first
search you'll meet in lesson 8:

```php
$orders = new SplQueue();
foreach (['A-1', 'A-2', 'A-3'] as $id) {
    $orders->enqueue($id);
}

while (! $orders->isEmpty()) {
    $id = $orders->dequeue();
    echo "Packing $id\n";
    if ($id === 'A-1') {
        $orders->enqueue('A-1 (gift wrap)');
    }
}
```

The gift wrap job joined the back of the queue, after the orders already waiting.

## Priority queues

Sometimes the next item should be the most **important**, not the oldest: express orders before standard ones.
`SplPriorityQueue` always hands out the item with the highest priority first:

```php
$packing = new SplPriorityQueue();
$packing->insert('Standard order A-1', 1);
$packing->insert('Express order A-2', 10);
$packing->insert('Standard order A-3', 1);
$packing->insert('Express order A-4', 10);

while (! $packing->isEmpty()) {
    echo $packing->extract() . "\n";
}
```

Items with the same priority come out in no guaranteed order. It's built on a **heap**, which lesson 7 explains.

## Putting it together

Evaluating a calculation written in **reverse Polish notation**, where operators come after their numbers (`3 4 +`
means 3 + 4), with a stack:

```php
function rpn(string $expression): float
{
    $stack = [];
    foreach (explode(' ', $expression) as $token) {
        if (is_numeric($token)) {
            $stack[] = (float) $token;
            continue;
        }
        $right = array_pop($stack);
        $left = array_pop($stack);
        $stack[] = match ($token) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $left / $right,
        };
    }
    return array_pop($stack);
}

echo rpn('3 4 + 2 *') . ' ' . rpn('10 2 8 * + 3 -');
```

Next, try the quiz to check your understanding, then solve the challenges.
