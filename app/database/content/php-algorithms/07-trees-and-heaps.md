## Trees

A **tree** is made of **nodes**; each node holds a value and links to its **children**. The top node is the **root**,
and nodes with no children are **leaves**. File systems, HTML pages and category menus are all trees. In a **binary
tree**, each node has at most two children, `left` and `right`:

```php
class TreeNode
{
    public function __construct(
        public int $value,
        public ?TreeNode $left = null,
        public ?TreeNode $right = null,
    ) {}
}

$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));

echo $root->value . ' has children ' . $root->left->value . ' and ' . $root->right->value;
```

## Walking a tree recursively

Trees are recursive (each child is the root of a smaller tree), so recursive functions suit them. Counting nodes and
measuring height take a few lines each:

```php
class TreeNode
{
    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
}

function size(?TreeNode $node): int
{
    return $node === null ? 0 : 1 + size($node->left) + size($node->right);
}

function height(?TreeNode $node): int
{
    return $node === null ? 0 : 1 + max(height($node->left), height($node->right));
}

$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));
echo size($root) . ' nodes, height ' . height($root);
```

## Traversal orders

There are three classic ways to visit every node depth-first, differing in when the node itself is visited relative to
its children: **pre-order** (node, left, right), **in-order** (left, node, right) and **post-order** (left, right,
node):

```php
class TreeNode
{
    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
}

function preOrder(?TreeNode $n): array { return $n ? [$n->value, ...preOrder($n->left), ...preOrder($n->right)] : []; }
function inOrder(?TreeNode $n): array { return $n ? [...inOrder($n->left), $n->value, ...inOrder($n->right)] : []; }
function postOrder(?TreeNode $n): array { return $n ? [...postOrder($n->left), ...postOrder($n->right), $n->value] : []; }

$root = new TreeNode(8, new TreeNode(3, new TreeNode(1), new TreeNode(6)), new TreeNode(10, null, new TreeNode(14)));

echo 'pre:  ' . implode(' ', preOrder($root)) . "\n";
echo 'in:   ' . implode(' ', inOrder($root)) . "\n";
echo 'post: ' . implode(' ', postOrder($root));
```

Notice that in-order gave the values sorted. That's because this tree is a binary search tree.

## Binary search trees

A **binary search tree** (BST) keeps one rule at every node: everything in the left subtree is smaller, and everything
in the right subtree is larger. Searching then works like binary search: compare with the node, and go left or right:

```php
class TreeNode
{
    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
}

function insert(?TreeNode $node, int $value): TreeNode
{
    if ($node === null) {
        return new TreeNode($value);
    }
    if ($value < $node->value) {
        $node->left = insert($node->left, $value);
    } else {
        $node->right = insert($node->right, $value);
    }
    return $node;
}

function contains(?TreeNode $node, int $value): bool
{
    while ($node !== null) {
        if ($value === $node->value) {
            return true;
        }
        $node = $value < $node->value ? $node->left : $node->right;
    }
    return false;
}

$root = null;
foreach ([1965, 1813, 1987, 1937, 2018, 1897] as $year) {
    $root = insert($root, $year);
}

var_dump(contains($root, 1937), contains($root, 2000));
```

## Balance

A BST's speed depends on its shape. Inserted in a good order, it stays bushy and each search takes O(log n) steps.
Inserted already sorted, every node goes to the right and the "tree" is really a linked list, with O(n) searches:

```php
class TreeNode
{
    public function __construct(public int $value, public ?TreeNode $left = null, public ?TreeNode $right = null) {}
}

function insert(?TreeNode $n, int $v): TreeNode
{
    if ($n === null) return new TreeNode($v);
    $v < $n->value ? $n->left = insert($n->left, $v) : $n->right = insert($n->right, $v);
    return $n;
}

function height(?TreeNode $n): int { return $n ? 1 + max(height($n->left), height($n->right)) : 0; }

$sorted = null;
foreach (range(1, 15) as $v) { $sorted = insert($sorted, $v); }

$mixed = null;
foreach ([8, 4, 12, 2, 6, 10, 14, 1, 3, 5, 7, 9, 11, 13, 15] as $v) { $mixed = insert($mixed, $v); }

echo 'sorted input: height ' . height($sorted) . "\nmixed input: height " . height($mixed);
```

Real-world trees (red-black trees, AVL trees, B-trees in databases) rebalance themselves automatically.

## Heaps

A **heap** is a tree with a different rule: every parent is smaller than its children (a **min-heap**) or larger (a
**max-heap**). The smallest (or largest) item is therefore always at the top, and taking it off or adding an item is
O(log n). It's the structure behind priority queues. PHP has `SplMinHeap` and `SplMaxHeap`:

```php
$heap = new SplMinHeap();
foreach ([42, 7, 19, 3, 25] as $n) {
    $heap->insert($n);
}

echo 'Smallest: ' . $heap->top() . "\n";
while (! $heap->isEmpty()) {
    echo $heap->extract() . ' ';
}
```

Taking every item out of a heap gives them in sorted order, which is the idea behind **heapsort**.

## Top k with a heap

To find the k largest items in a long list, keep a min-heap of the k best seen so far. Each new item that beats the
smallest of them replaces it. That's O(n log k), much less than sorting everything when k is small:

```php
function topK(array $numbers, int $k): array
{
    $heap = new SplMinHeap();
    foreach ($numbers as $n) {
        $heap->insert($n);
        if (count($heap) > $k) {
            $heap->extract();
        }
    }
    $best = iterator_to_array($heap, false);
    rsort($best);
    return $best;
}

echo implode(', ', topK([5, 91, 23, 7, 64, 18, 77, 3], 3));
```

## Putting it together

A category tree (each node with any number of children), printed with indentation by a pre-order walk:

```php
class Category
{
    public function __construct(public string $name, public array $children = []) {}
}

function show(Category $category, int $depth = 0): void
{
    echo str_repeat('  ', $depth) . $category->name . "\n";
    foreach ($category->children as $child) {
        show($child, $depth + 1);
    }
}

show(new Category('Books', [
    new Category('Fiction', [new Category('Fantasy'), new Category('Horror')]),
    new Category('Non-fiction', [new Category('History')]),
]));
```

Next, try the quiz to check your understanding, then solve the challenges.
