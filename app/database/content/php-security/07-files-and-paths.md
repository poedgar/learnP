## File names are input too

Uploads and downloads are where web apps touch the file system, and every part of a request about a
file can lie: the file name, its extension, the type the browser claims (`Content-Type`), even the
contents. Three things go wrong most often:

- **Path traversal**: a name like `../../.env` reaches files outside the folder you meant.
- **Executable uploads**: a "picture" called `shell.php` lands in a public folder and the web server
  runs it.
- **Unsafe downloads**: a file served with the wrong headers is shown as a web page, with its scripts.

## Path traversal

A download route that does `readfile('storage/invoices/' . $_GET['file'])` will happily serve
`storage/invoices/../../.env`, which is your app's secrets. `..` means "the folder above", and it can
be hidden as `./`, doubled slashes or backslashes on Windows.

The safest fix is not to use names from the request at all: look files up by an **id** in your
database. When you must accept a path, resolve it first and then check that the result is still
inside the allowed folder. PHP's `realpath()` resolves paths on disk; working on the string alone
looks like this:

```php
function resolve(string $path): array
{
    $parts = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $segment;
    }

    return $parts;
}

echo implode('/', resolve('invoices/2026/../../../.env')) . PHP_EOL;
echo implode('/', resolve('invoices/./2026//march.pdf'));
```

Notice the first result: `array_pop()` on an empty array quietly does nothing, so climbing above the
start looks harmless here but means "outside the folder" for real. A safe resolver must **notice** that
and refuse.

## Uploads: trust nothing the browser says

When a file is uploaded, PHP gives you the name the user's computer had (`$_FILES['cover']['name']`)
and the type the browser *claims* (`['type']`). Both are just text the visitor sent. Instead:

- **Allow-list extensions**, and look at all of them: `cover.php.jpg` can be run as PHP by some
  Apache setups.
- **Check the contents**: real files start with recognisable **magic bytes**. A PNG always begins with
  `\x89PNG\r\n\x1a\n`, a PDF with `%PDF-`. Laravel's `mimes:` rule uses the contents, not the name.
- **Choose the name yourself**: store `7f3a9c...e1.png`, a random name with the extension you decided
  on, and keep the original name only as data in your database.
- **Store outside the public folder** (Laravel's `storage/app/private`), or at least where the server
  will never execute scripts, and limit the size.

```php
$upload = ['name' => 'holiday.jpg', 'type' => 'image/jpeg', 'bytes' => "<?php system(\$_GET['c']); ?>"];

$looksLikeJpeg = str_starts_with($upload['bytes'], "\xFF\xD8\xFF");

echo 'The browser says ' . $upload['type'] . ', the bytes say ' . ($looksLikeJpeg ? 'JPEG' : 'not a JPEG');
```

SVG images deserve a special mention: they're XML and can contain `<script>`. Either don't accept them,
or never serve them from your own domain as images users can open directly.

## Serving files safely

When you send a stored file back, you choose the headers:

- `Content-Type` from what *you* detected, plus `X-Content-Type-Options: nosniff`, so browsers don't
  guess and decide your "text file" is HTML.
- `Content-Disposition: attachment` makes the browser download instead of display. The file name in
  that header is user data too: quotes or line breaks in it could break the header, and non-ASCII
  names need the `filename*` form. Laravel's `Storage::download($path, $name)` writes it for you.

```text
Content-Disposition: attachment; filename="Invoice_.pdf"; filename*=UTF-8''Invoice%E2%84%96.pdf
```

## Archives

Unpacking a ZIP is path traversal in disguise: an entry named `../../public/shell.php` is written
wherever its name points (the "zip slip" bug). Every entry name must go through the same
resolve-and-check as any other path.
