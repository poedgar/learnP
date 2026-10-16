<?php

// Lesson 5 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'composite',
    'title' => 'Composite',
    'summary' => 'Treat single items and groups of items the same way, so trees of any depth (boxes, menus, folders) can be priced, counted and rendered with one call.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try nesting another box.
        interface Item { public function price(): float; }

        class Book implements Item
        {
            public function __construct(private float $price) {}
            public function price(): float { return $this->price; }
        }

        class Box implements Item
        {
            public function __construct(private array $items) {}
            public function price(): float { return array_sum(array_map(fn (Item $i) => $i->price(), $this->items)); }
        }

        echo (new Box([new Book(7.99), new Box([new Book(13.99), new Book(19.99)])]))->price();
        PHP,

    'quiz' => [
        [
            'question' => 'What problem does the Composite pattern solve?',
            'options' => [
                'Creating objects of the right class',
                'Treating single items and groups of items the same way, in trees of any depth',
                'Making sure only one object exists',
                'Converting between interfaces',
            ],
            'correct_option' => 1,
            'explanation' => 'Leaves and composites share an interface, so callers never ask "is this one thing or a group?".',
        ],
        [
            'question' => 'In the pattern, what is a leaf?',
            'options' => [
                'An object that holds other objects',
                'A single object with no children, which answers directly',
                'The top of the tree',
                'An abstract class',
            ],
            'correct_option' => 1,
            'explanation' => 'A book in a box is a leaf; the box is a composite.',
        ],
        [
            'question' => 'How does a composite answer a question such as `price()`?',
            'options' => [
                'It looks the answer up in a database',
                'It asks each child the same question and combines their answers',
                'It always returns 0',
                'It throws, because only leaves can answer',
            ],
            'correct_option' => 1,
            'explanation' => 'Each child may itself be a composite, so the question travels all the way down the tree.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Node { public function count(): int; }

                class Leaf implements Node { public function count(): int { return 1; } }

                class Group implements Node
                {
                    public function __construct(private array $children) {}
                    public function count(): int { return array_sum(array_map(fn (Node $n) => $n->count(), $this->children)); }
                }

                echo (new Group([new Leaf(), new Group([new Leaf(), new Leaf()]), new Group([])]))->count();
                PHP,
            'options' => ['`3`', '`4`', '`2`', '`5`'],
            'correct_option' => 0,
            'explanation' => 'Only leaves count 1. The inner group gives 2, the empty group 0: 1 + 2 + 0 = 3.',
        ],
        [
            'question' => 'What should an empty composite usually answer?',
            'options' => [
                'Throw an exception',
                'The neutral value, such as 0 for a price or an empty list',
                '`null` for everything',
                'The answer of its parent',
            ],
            'correct_option' => 1,
            'explanation' => 'An empty box costs nothing. `array_sum([])` is 0, so this often comes for free.',
        ],
        [
            'question' => 'Why does the pattern handle any depth without a loop over levels?',
            'options' => [
                'Because PHP limits trees to three levels',
                'Because each composite deals only with its own children, and the recursion follows the structure',
                'Because composites are flattened first',
                'It doesn\'t; you must loop over every level',
            ],
            'correct_option' => 1,
            'explanation' => 'Each call goes one level down; together they cover the whole tree.',
        ],
        [
            'question' => 'What is the downside of putting `add()` on the shared interface instead of only on composites?',
            'options' => [
                'Nothing',
                'Leaves must implement `add()` too, and refuse it, because a book can\'t contain things',
                'Composites can no longer have children',
                'It stops recursion from working',
            ],
            'correct_option' => 1,
            'explanation' => 'It buys uniformity at the cost of methods that make no sense for leaves.',
        ],
        [
            'question' => 'Which of these is a natural composite?',
            'options' => [
                'A list of prices',
                'A navigation menu that holds links and sub-menus',
                'A single date',
                'A database connection',
            ],
            'correct_option' => 1,
            'explanation' => 'Menus contain entries, some of which contain more entries: a tree where every node renders itself.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Bundles of books',
            'prompt' => 'Write `Bundle`, implementing `Product`, holding any products (books or other bundles) passed to its constructor. Its `price()` is the total of its contents with 10% off, rounded to 2 places, so bundles inside bundles get the discount at each level.',
            'starter_code' => <<<'PHP'
                <?php

                interface Product
                {
                    public function price(): float;
                }

                class Book implements Product
                {
                    public function __construct(private float $price) {}
                    public function price(): float { return $this->price; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Product
                {
                    public function price(): float;
                }

                class Book implements Product
                {
                    public function __construct(private float $price) {}
                    public function price(): float { return $this->price; }
                }

                class Bundle implements Product
                {
                    public function __construct(private array $products) {}

                    public function price(): float
                    {
                        $total = array_sum(array_map(fn (Product $product) => $product->price(), $this->products));

                        return round($total * 0.9, 2);
                    }
                }
                PHP,
            'hint' => 'Add up `$product->price()` for each product, then take 10% off and round.',
            'checks' => [
                ['label' => 'A bundle of books', 'code' => 'return expect_same((new Bundle([new Book(10.0), new Book(20.0)]))->price(), 27.0);'],
                ['label' => 'A bundle inside a bundle', 'code' => 'return expect_same((new Bundle([new Book(10.0), new Bundle([new Book(10.0), new Book(10.0)])]))->price(), 25.2);'],
                ['label' => 'An empty bundle costs 0', 'code' => 'return expect_same((new Bundle([]))->price(), 0.0);'],
            ],
        ],
        [
            'title' => 'How deep is it?',
            'prompt' => 'Add `depth(): int` to the `Node` interface and implement it: a `Leaf` has depth 1, and a `Branch` has 1 plus the depth of its deepest child (an empty branch has depth 1).',
            'starter_code' => <<<'PHP'
                <?php

                interface Node
                {
                }

                class Leaf implements Node
                {
                }

                class Branch implements Node
                {
                    public function __construct(private array $children = []) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Node
                {
                    public function depth(): int;
                }

                class Leaf implements Node
                {
                    public function depth(): int
                    {
                        return 1;
                    }
                }

                class Branch implements Node
                {
                    public function __construct(private array $children = []) {}

                    public function depth(): int
                    {
                        $deepest = 0;
                        foreach ($this->children as $child) {
                            $deepest = max($deepest, $child->depth());
                        }

                        return 1 + $deepest;
                    }
                }
                PHP,
            'hint' => 'A branch finds the largest `depth()` among its children (0 if there are none) and adds 1.',
            'checks' => [
                ['label' => 'Leaves and empty branches', 'code' => 'return expect_same([(new Leaf())->depth(), (new Branch())->depth()], [1, 1]);'],
                ['label' => 'Nested branches', 'code' => 'return expect_same((new Branch([new Leaf(), new Branch([new Branch([new Leaf()])]), new Leaf()]))->depth(), 4);'],
                ['label' => 'depth() is on the interface', 'code' => "return (new ReflectionClass('Node'))->hasMethod('depth') ?: 'Declare depth() in the Node interface.';"],
            ],
        ],
        [
            'title' => 'Every title in the box',
            'prompt' => 'Give `Item` a method `titles(): array`. A `Book` returns its own title in a list; a `Box` returns every title inside it, at any depth, in order.',
            'starter_code' => <<<'PHP'
                <?php

                interface Item
                {
                    public function titles(): array;
                }

                class Book implements Item
                {
                    public function __construct(private string $title) {}

                    public function titles(): array
                    {
                        return [];
                    }
                }

                class Box implements Item
                {
                    public function __construct(private array $items) {}

                    public function titles(): array
                    {
                        return [];
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Item
                {
                    public function titles(): array;
                }

                class Book implements Item
                {
                    public function __construct(private string $title) {}

                    public function titles(): array
                    {
                        return [$this->title];
                    }
                }

                class Box implements Item
                {
                    public function __construct(private array $items) {}

                    public function titles(): array
                    {
                        $titles = [];
                        foreach ($this->items as $item) {
                            $titles = [...$titles, ...$item->titles()];
                        }

                        return $titles;
                    }
                }
                PHP,
            'hint' => 'A book returns `[$this->title]`. A box joins the lists from all its items, for example with `[...$titles, ...$item->titles()]`.',
            'checks' => [
                ['label' => 'A single book', 'code' => "return expect_same((new Book('Dune'))->titles(), ['Dune']);"],
                ['label' => 'Nested boxes, in order', 'code' => "return expect_same((new Box([new Book('Emma'), new Box([new Book('Dracula'), new Box([])]), new Book('Circe')]))->titles(), ['Emma', 'Dracula', 'Circe']);"],
            ],
        ],
        [
            'title' => 'Render a menu',
            'prompt' => "Write `Link` and `Menu`, both implementing `MenuEntry`. `Link('Home', '/')` renders as `Home (/)`. A `Menu` with a label and entries renders its label, then each entry on its own line indented by two more spaces per level. `render(int \$depth = 0)` takes the depth.\n\n```\nShop\n  Home (/)\n  Books\n    Fantasy (/fantasy)\n```",
            'starter_code' => <<<'PHP'
                <?php

                interface MenuEntry
                {
                    public function render(int $depth = 0): string;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface MenuEntry
                {
                    public function render(int $depth = 0): string;
                }

                class Link implements MenuEntry
                {
                    public function __construct(private string $label, private string $url) {}

                    public function render(int $depth = 0): string
                    {
                        return str_repeat('  ', $depth) . "{$this->label} ({$this->url})";
                    }
                }

                class Menu implements MenuEntry
                {
                    public function __construct(private string $label, private array $entries) {}

                    public function render(int $depth = 0): string
                    {
                        $lines = [str_repeat('  ', $depth) . $this->label];
                        foreach ($this->entries as $entry) {
                            $lines[] = $entry->render($depth + 1);
                        }

                        return implode("\n", $lines);
                    }
                }
                PHP,
            'hint' => 'Each entry indents itself with `str_repeat(\'  \', $depth)`. A menu renders its label, then each entry with `$depth + 1`, joined by `"\n"`.',
            'checks' => [
                ['label' => 'A link', 'code' => "return expect_same((new Link('Home', '/'))->render(), 'Home (/)');"],
                ['label' => 'A nested menu', 'code' => "\$menu = new Menu('Shop', [new Link('Home', '/'), new Menu('Books', [new Link('Fantasy', '/fantasy')])]);\nreturn expect_same(\$menu->render(), \"Shop\\n  Home (/)\\n  Books\\n    Fantasy (/fantasy)\");"],
            ],
        ],
        [
            'title' => 'Find the heaviest parcel',
            'prompt' => 'Write `Parcel` (a leaf with a name and a weight in grams) and `Crate` (holding parcels and crates), both implementing `Shipment`, with `weight(): int` (a crate weighs its contents plus 500 g for itself) and `heaviestParcel(): ?string` (the name of the heaviest parcel anywhere inside, or `null` if there are none).',
            'starter_code' => <<<'PHP'
                <?php

                interface Shipment
                {
                    public function weight(): int;
                    public function heaviestParcel(): ?string;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Shipment
                {
                    public function weight(): int;
                    public function heaviestParcel(): ?string;
                }

                class Parcel implements Shipment
                {
                    public function __construct(public readonly string $name, private int $grams) {}

                    public function weight(): int { return $this->grams; }

                    public function heaviestParcel(): ?string { return $this->name; }
                }

                class Crate implements Shipment
                {
                    public function __construct(private array $contents = []) {}

                    public function weight(): int
                    {
                        return 500 + array_sum(array_map(fn (Shipment $s) => $s->weight(), $this->contents));
                    }

                    public function heaviestParcel(): ?string
                    {
                        $best = null;
                        $bestWeight = -1;
                        foreach ($this->parcels() as $parcel) {
                            if ($parcel->weight() > $bestWeight) {
                                $best = $parcel->name;
                                $bestWeight = $parcel->weight();
                            }
                        }

                        return $best;
                    }

                    private function parcels(): array
                    {
                        $parcels = [];
                        foreach ($this->contents as $item) {
                            $parcels = $item instanceof Crate ? [...$parcels, ...$item->parcels()] : [...$parcels, $item];
                        }

                        return $parcels;
                    }
                }
                PHP,
            'hint' => 'Weights add up recursively, plus 500 for each crate. For the heaviest parcel, collect all parcels inside (recursively), then pick the one with the largest weight.',
            'checks' => [
                ['label' => 'Weights include each crate', 'code' => "return expect_same((new Crate([new Parcel('books', 2000), new Crate([new Parcel('maps', 300)])]))->weight(), 3300);"],
                ['label' => 'The heaviest parcel at any depth', 'code' => "return expect_same((new Crate([new Parcel('books', 2000), new Crate([new Parcel('atlas', 2500), new Crate([])])]))->heaviestParcel(), 'atlas');"],
                ['label' => 'No parcels gives null', 'code' => 'return expect_same((new Crate([new Crate([])]))->heaviestParcel(), null);'],
            ],
        ],
        [
            'title' => 'Discount a whole tree',
            'prompt' => "Give `Item` a method `withDiscount(int \$percent): Item` returning a **new** tree with every book's price reduced by that percentage (rounded to 2 places), leaving the original unchanged. Boxes rebuild themselves from their discounted contents.",
            'starter_code' => <<<'PHP'
                <?php

                interface Item
                {
                    public function price(): float;
                    public function withDiscount(int $percent): Item;
                }

                class Book implements Item
                {
                    public function __construct(private float $price) {}
                    public function price(): float { return $this->price; }
                    public function withDiscount(int $percent): Item { return $this; }
                }

                class Box implements Item
                {
                    public function __construct(private array $items) {}
                    public function price(): float { return round(array_sum(array_map(fn (Item $i) => $i->price(), $this->items)), 2); }
                    public function withDiscount(int $percent): Item { return $this; }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Item
                {
                    public function price(): float;
                    public function withDiscount(int $percent): Item;
                }

                class Book implements Item
                {
                    public function __construct(private float $price) {}
                    public function price(): float { return $this->price; }

                    public function withDiscount(int $percent): Item
                    {
                        return new Book(round($this->price * (100 - $percent) / 100, 2));
                    }
                }

                class Box implements Item
                {
                    public function __construct(private array $items) {}
                    public function price(): float { return round(array_sum(array_map(fn (Item $i) => $i->price(), $this->items)), 2); }

                    public function withDiscount(int $percent): Item
                    {
                        return new Box(array_map(fn (Item $item) => $item->withDiscount($percent), $this->items));
                    }
                }
                PHP,
            'hint' => 'A book returns `new Book(…)` with the lower price. A box returns `new Box(…)` built from each item\'s `withDiscount($percent)`.',
            'checks' => [
                ['label' => 'The discounted tree', 'code' => 'return expect_same((new Box([new Book(10.0), new Box([new Book(20.0)])]))->withDiscount(25)->price(), 22.5);'],
                ['label' => 'The original is unchanged', 'code' => "\$box = new Box([new Book(10.0), new Book(5.0)]);\n\$box->withDiscount(50);\nreturn expect_same(\$box->price(), 15.0);"],
                ['label' => 'New objects are returned', 'code' => "\$book = new Book(10.0);\nreturn (\$book->withDiscount(10) !== \$book) ?: 'withDiscount() should return a new Book.';"],
            ],
        ],
    ],
];
