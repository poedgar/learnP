## Two classes called `Book`

A real project uses code from many places: your own classes, a framework, and dozens of
libraries. Sooner or later two of them want the same name. The shop has a `Book`; a PDF library
has a `Book` too. PHP can't have two classes with one name, unless they live in different
**namespaces**: named groups, like folders for names.

## Declaring a namespace

`namespace Shop;` at the top of a file puts everything after it into the `Shop` namespace. The
class's full name, its **fully qualified name**, is then `Shop\Book`, and `Book::class` gives
that full name:

```php
namespace Shop;

class Book
{
    public function __construct(public string $title) {}
}

$book = new Book('Dune');
echo get_class($book) . "\n";
echo Book::class;
```

Inside the namespace, `Book` is enough. From anywhere else, write `\Shop\Book`. The leading
backslash means "start from the top", like an absolute path.

## Several namespaces in one file

Real projects put one class in each file. To show several namespaces in one example, the braced form
puts each namespace's code in `{ }`. Code that belongs to no namespace goes in `namespace { }`:

```php
namespace Shop {
    class Book
    {
        public function source(): string { return 'the shop'; }
    }
}

namespace Pdf {
    class Book
    {
        public function source(): string { return 'the PDF library'; }
    }
}

namespace {
    echo (new Shop\Book())->source() . "\n";
    echo (new Pdf\Book())->source();
}
```

Namespaces can be nested as deep as you like: `Shop\Catalogue\Book`, `Shop\Orders\Invoice`. Each `\`
is one level.

## Importing names with `use`

Writing full names everywhere gets long. `use` at the top of a namespace imports a name, so you can
refer to it by its last part. `as` gives it a different name, which is how you use two classes that
share a short name:

```php
namespace Shop\Catalogue {
    class Book
    {
        public function __construct(public string $title) {}
    }
}

namespace Pdf {
    class Book
    {
        public function __construct(public string $file) {}
    }
}

namespace App {
    use Shop\Catalogue\Book;
    use Pdf\Book as PdfBook;

    $book = new Book('Dune');
    $pdf = new PdfBook('dune.pdf');

    echo get_class($book) . ' and ' . get_class($pdf);
}
```

`use` only affects the file (or braced block) it's written in.

## PHP's own classes need a backslash

PHP's built-in classes, such as `Exception` and `DateTimeImmutable`, live in the top-level, **global**
namespace. Inside `namespace Shop;`, the name `Exception` means `Shop\Exception`, which doesn't exist:

```php
// Error: inside namespace Shop, Exception means Shop\Exception
namespace Shop;

throw new Exception('Out of stock');
```

Write `\Exception`, or import it with `use Exception;`. Functions and constants are kinder: when
`Shop\strlen()` doesn't exist, PHP falls back to the global `strlen()`, so you rarely need a backslash
for them.

```php
namespace Shop;

use InvalidArgumentException;

function checkTitle(string $title): string
{
    if (strlen($title) === 0) {
        throw new InvalidArgumentException('A title is required.');
    }
    return $title;
}

try {
    checkTitle('');
} catch (\Exception $e) {
    echo get_class($e) . ': ' . $e->getMessage();
}
```

## Autoloading

With one class per file, something has to load each file before its class is used. Writing `require`
for hundreds of files would be hopeless. Instead, PHP calls an **autoloader** the first time code uses
a class it doesn't know yet, passing the class's full name. `spl_autoload_register()` registers one:

```php
spl_autoload_register(function (string $class): void {
    echo "Asked to load $class\n";
});

var_dump(class_exists('Shop\Basket'));
```

A real autoloader turns the class name into a file path and `require`s that file. If it can't find
the class, it simply does nothing, and the next registered autoloader gets a turn.

## PSR-4: from class names to file paths

To make that conversion predictable, PHP projects follow a shared convention called **PSR-4**. A
namespace **prefix** is linked to a **base directory**, and the rest of the class name becomes the path,
with each `\` turned into `/` and `.php` added:

```php
function psr4Example(string $class): string
{
    $relative = substr($class, strlen('App\\'));
    return 'src/' . str_replace('\\', '/', $relative) . '.php';
}

echo psr4Example('App\Models\Book') . "\n";
echo psr4Example('App\Http\Controllers\BookController');
```

So `App\Models\Book` lives in `src/Models/Book.php`. Inside a PHP string, a single backslash is written
`\\`, because `\` starts escapes like `\n`.

## Composer does this for you

You won't usually write an autoloader yourself. **Composer**, PHP's package manager, reads the PSR-4
rules from a project's `composer.json` (such as `"App\\": "src/"`), generates an autoloader for your
code and every library you install, and you load it with one line, `require 'vendor/autoload.php';`.
From then on, any class just works the first time you use it.

## Putting it together

Two libraries with clashing names, imported with an alias, and PHP's own class used from inside a
namespace:

```php
namespace Shop\Catalogue {
    class Book
    {
        public function __construct(public string $title, public float $price)
        {
            if ($price < 0) {
                throw new \InvalidArgumentException('A price can\'t be negative.');
            }
        }
    }
}

namespace Shop\Pdf {
    class Book
    {
        public function __construct(public string $file) {}
    }
}

namespace App {
    use Shop\Catalogue\Book;
    use Shop\Pdf\Book as PdfBook;

    $books = [new Book('Dune', 19.99), new PdfBook('dune.pdf')];
    foreach ($books as $book) {
        echo $book::class . "\n";
    }
}
```

Next, try the quiz to check your understanding, then solve the challenges.
