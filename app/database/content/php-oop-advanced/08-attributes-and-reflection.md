## Code that looks at code

**Reflection** lets a program inspect classes, methods and properties while it runs: what a class
is called, which methods it has, what its constructor needs. Frameworks use it to wire objects
together, map web addresses to methods and validate forms. It starts with a `ReflectionClass`:

```php
class Book
{
    public function __construct(public string $title, private float $price) {}

    public function describe(): string
    {
        return $this->title;
    }

    private function secret(): void {}
}

$class = new ReflectionClass(Book::class);

echo $class->getName() . "\n";
foreach ($class->getMethods() as $method) {
    echo $method->getName() . ($method->isPublic() ? ' (public)' : ' (not public)') . "\n";
}
```

`new ReflectionClass($object)` works with an object too.

## Properties and their types

`getProperties()` returns a `ReflectionProperty` for each property. Each one knows its name, its
visibility and its type, and can read the value from an object, even a private one:

```php
class Book
{
    public function __construct(public string $title, private float $price) {}
}

$book = new Book('Dune', 19.99);

foreach ((new ReflectionClass($book))->getProperties() as $property) {
    echo $property->getName() . ': ' . $property->getType() . ' = ' . $property->getValue($book) . "\n";
}
```

Reading private values this way ignores the class's rules, so keep it for tools such as debuggers,
serialisers and tests.

## What does the constructor need?

`getConstructor()->getParameters()` lists the constructor's parameters, with their names, types and
default values. That's enough to create an object from an array of data:

```php
class Book
{
    public function __construct(public string $title, public float $price = 9.99) {}
}

$constructor = (new ReflectionClass(Book::class))->getConstructor();

foreach ($constructor->getParameters() as $parameter) {
    echo $parameter->getName() . ': ' . $parameter->getType();
    if ($parameter->isDefaultValueAvailable()) {
        echo ' = ' . $parameter->getDefaultValue();
    }
    echo "\n";
}

$book = (new ReflectionClass(Book::class))->newInstanceArgs(['title' => 'Emma']);
echo $book->title . ' costs ' . $book->price;
```

`newInstanceArgs()` accepts named arguments as array keys, just like a normal call would.

## Attributes: notes attached to code

An **attribute** is a note written on a class, method, property or parameter with `#[ ]`. It does
nothing by itself; it's data that other code can read with reflection. An attribute is just a
class marked with `#[Attribute]`:

```php
#[Attribute]
class Route
{
    public function __construct(public string $path, public string $method = 'GET') {}
}

class BookController
{
    #[Route('/books')]
    public function list(): string
    {
        return 'All books';
    }

    #[Route('/books', method: 'POST')]
    public function add(): string
    {
        return 'Book added';
    }
}

echo 'No routes are registered until something reads the attributes.';
```

## Reading attributes

Every reflection object has `getAttributes()`. Each result knows the attribute's name and arguments,
and `newInstance()` creates the attribute object itself:

```php
#[Attribute]
class Route
{
    public function __construct(public string $path, public string $method = 'GET') {}
}

class BookController
{
    #[Route('/books')]
    public function list(): string { return 'All books'; }

    #[Route('/books', method: 'POST')]
    public function add(): string { return 'Book added'; }

    public function helper(): void {}
}

foreach ((new ReflectionClass(BookController::class))->getMethods() as $method) {
    foreach ($method->getAttributes(Route::class) as $attribute) {
        $route = $attribute->newInstance();
        echo "{$route->method} {$route->path} → {$method->getName()}()\n";
    }
}
```

Passing a class name to `getAttributes()` returns only attributes of that class.

## Where an attribute may go

`#[Attribute(Attribute::TARGET_PROPERTY)]` limits an attribute to properties (there are also
`TARGET_CLASS`, `TARGET_METHOD` and others, combined with `|`). PHP only checks this when
`newInstance()` creates the attribute, and stops with an error if it's in the wrong place:

```php
// Error: MaxLength may only be used on properties
#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(public int $max) {}
}

#[MaxLength(10)]
class Book {}

(new ReflectionClass(Book::class))->getAttributes()[0]->newInstance();
```

## A tiny validator

Attributes and reflection together make a small validation library. The rules sit next to the
properties they're about, and one function checks any object:

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(public int $max) {}
}

class Review
{
    public function __construct(
        #[MaxLength(10)] public string $title,
        public string $body,
    ) {}
}

function errors(object $object): array
{
    $errors = [];
    foreach ((new ReflectionClass($object))->getProperties() as $property) {
        foreach ($property->getAttributes(MaxLength::class) as $attribute) {
            $rule = $attribute->newInstance();
            if (strlen($property->getValue($object)) > $rule->max) {
                $errors[] = "{$property->getName()} is longer than {$rule->max} characters";
            }
        }
    }
    return $errors;
}

print_r(errors(new Review('Far too long a title', 'Great book.')));
```

## When to use reflection

Reflection is the right tool for frameworks and tools that must work with classes they've never
seen. In ordinary code, it's usually a sign that something should be an interface or a normal method
call instead: reflection is slower, ignores `private`, and hides what's going on from editors and
readers.

## Putting it together

A tiny router that reads `#[Route]` attributes and calls the matching method:

```php
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(public string $path) {}
}

class ShopController
{
    #[Route('/')]
    public function home(): string { return 'Welcome to the bookshop'; }

    #[Route('/books')]
    public function books(): string { return 'Dune, Emma, Circe'; }
}

function dispatch(object $controller, string $path): string
{
    foreach ((new ReflectionObject($controller))->getMethods() as $method) {
        foreach ($method->getAttributes(Route::class) as $attribute) {
            if ($attribute->newInstance()->path === $path) {
                return $method->invoke($controller);
            }
        }
    }
    return '404 Not Found';
}

$shop = new ShopController();
echo dispatch($shop, '/books') . "\n";
echo dispatch($shop, '/authors');
```

Next, try the quiz to check your understanding, then solve the challenges.
