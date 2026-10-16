## Things made of things

A gift box holds books, and maybe a smaller box holding more books. A menu holds items and sub-menus. A folder
holds files and folders. Code that works with such **trees** usually has to ask "is this a single thing or a
group?" over and over. The **Composite** pattern removes that question.

## The problem: checking types everywhere

Without the pattern, every operation needs two branches, and grows another for each new kind of thing:

```php
$order = [
    ['type' => 'book', 'price' => 19.99],
    ['type' => 'box', 'contents' => [
        ['type' => 'book', 'price' => 7.99],
        ['type' => 'book', 'price' => 13.99],
    ]],
];

function total(array $items): float
{
    $sum = 0.0;
    foreach ($items as $item) {
        $sum += $item['type'] === 'box' ? total($item['contents']) : $item['price'];
    }
    return $sum;
}

echo total($order);
```

## The Composite pattern

Give single items and groups **the same interface**. A single item (a **leaf**) answers directly; a group (a
**composite**) answers by asking each of its children, which may be leaves or other composites. Callers treat
both alike:

```php
interface Item
{
    public function price(): float;
}

class Book implements Item
{
    public function __construct(private string $title, private float $price) {}
    public function price(): float { return $this->price; }
}

class GiftBox implements Item
{
    private array $items = [];

    public function add(Item $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    public function price(): float
    {
        return array_sum(array_map(fn (Item $item) => $item->price(), $this->items));
    }
}

$box = (new GiftBox())
    ->add(new Book('Emma', 7.99))
    ->add((new GiftBox())->add(new Book('Circe', 13.99))->add(new Book('Dune', 19.99)));

echo $box->price();
```

The nested box needed no special handling: it's just another `Item`.

## Several operations, one structure

Each new operation is a method on the interface, implemented once by leaves and once by composites. Here a box
also counts its books and describes itself, with indentation showing the depth:

```php
interface Item
{
    public function count(): int;
    public function describe(int $depth = 0): string;
}

class Book implements Item
{
    public function __construct(private string $title) {}
    public function count(): int { return 1; }
    public function describe(int $depth = 0): string { return str_repeat('  ', $depth) . "- {$this->title}\n"; }
}

class Box implements Item
{
    private array $items = [];

    public function __construct(private string $name) {}

    public function add(Item $item): static { $this->items[] = $item; return $this; }

    public function count(): int
    {
        return array_sum(array_map(fn (Item $item) => $item->count(), $this->items));
    }

    public function describe(int $depth = 0): string
    {
        $text = str_repeat('  ', $depth) . "{$this->name} ({$this->count()} books)\n";
        foreach ($this->items as $item) {
            $text .= $item->describe($depth + 1);
        }
        return $text;
    }
}

$gift = (new Box('Birthday box'))->add(new Book('Emma'))->add((new Box('Classics'))->add(new Book('Dracula'))->add(new Book('Frankenstein')));
echo $gift->describe();
```

## Recursion does the walking

Notice there's no loop over "every level" anywhere. Each composite handles **its own** children, and each child
handles its own. The recursion through the tree falls out of the structure. That's why the pattern copes with any
depth.

## A real example: menus

Navigation menus are a classic composite: a menu holds links and sub-menus, and rendering is the same call at every
level:

```php
interface MenuEntry
{
    public function render(): string;
}

class Link implements MenuEntry
{
    public function __construct(private string $label, private string $url) {}
    public function render(): string { return "<li><a href=\"{$this->url}\">{$this->label}</a></li>"; }
}

class Menu implements MenuEntry
{
    public function __construct(private string $label, private array $entries) {}

    public function render(): string
    {
        $inner = implode('', array_map(fn (MenuEntry $e) => $e->render(), $this->entries));
        return "<li>{$this->label}<ul>$inner</ul></li>";
    }
}

$nav = new Menu('Shop', [
    new Link('Home', '/'),
    new Menu('Books', [new Link('Fantasy', '/fantasy'), new Link('Horror', '/horror')]),
]);

echo $nav->render();
```

## Design choices

Two decisions come up when using the pattern:

- **Where do `add()` and `remove()` go?** Putting them only on the composite (as above) is safe: you can't add
  children to a book. Putting them on the interface lets callers treat everything uniformly, but leaves need to
  refuse them.
- **What does an empty composite answer?** Usually the neutral value: a price of 0, a count of 0, an empty list.
  `array_sum([])` is 0, so that often comes for free.

## Putting it together

A folder tree that reports its total size and finds its largest file, all through one interface:

```php
interface Node
{
    public function size(): int;
    public function largest(): ?array;
}

class File implements Node
{
    public function __construct(private string $name, private int $bytes) {}
    public function size(): int { return $this->bytes; }
    public function largest(): ?array { return [$this->name, $this->bytes]; }
}

class Folder implements Node
{
    public function __construct(private array $children = []) {}

    public function size(): int
    {
        return array_sum(array_map(fn (Node $n) => $n->size(), $this->children));
    }

    public function largest(): ?array
    {
        $best = null;
        foreach ($this->children as $child) {
            $candidate = $child->largest();
            if ($candidate !== null && ($best === null || $candidate[1] > $best[1])) {
                $best = $candidate;
            }
        }
        return $best;
    }
}

$root = new Folder([
    new File('cover.jpg', 240_000),
    new Folder([new File('chapter1.txt', 18_000), new File('map.png', 410_000)]),
    new Folder([]),
]);

echo $root->size() . " bytes, largest: " . implode(' at ', $root->largest()) . ' bytes';
```

Next, try the quiz to check your understanding, then solve the challenges.
