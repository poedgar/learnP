## A form, start to finish

Adding a book through a web page takes four steps, and Laravel has a tool for each:

1. A `GET` route shows the form (`create`).
2. The browser `POST`s it to another route (`store`).
3. The app **validates** the input. If it's wrong, it **redirects back** to the form with error
   messages and the visitor's input still filled in.
4. If it's right, it saves, then redirects to a page with a success message.

```blade
<form method="POST" action="{{ route('books.store') }}">
    @csrf

    <label for="title">Title</label>
    <input id="title" name="title" value="{{ old('title') }}">
    @error('title')
        <p class="error">{{ $message }}</p>
    @enderror

    <button>Save</button>
</form>
```

- `@csrf` adds a hidden token that proves the form came from your site (see the PHP security course).
- `old('title')` is what the visitor typed last time, so a failed submit doesn't empty the form.
- `@error('title')` shows the field's first error message as `$message`.

## Validating in the controller

`$request->validate()` takes rules per field. If anything fails, it stops the action right there and
sends the visitor back with the errors and old input; nothing after it runs:

```laravel
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'year' => 'nullable|integer|between:1450,2100',
    ]);

    Book::create($validated);

    return redirect()->route('books.index')->with('status', 'Book saved.');
}
```

A few rules worth knowing: `required`, `nullable`, `string`, `integer`, `numeric`, `email`, `min`/`max`
(length for text, value for numbers), `between`, `in:a,b,c`, `unique:books,isbn`, `confirmed` (the field
must match `field_confirmation`, as for passwords), `date`, `url`.

Each rule is a small check. `required` means "present and not empty":

```php
foreach (['Dune', '', '   ', null, '0'] as $value) {
    $present = $value !== null && trim($value) !== '';
    echo var_export($value, true) . ' is ' . ($present ? 'present' : 'missing') . PHP_EOL;
}
```

Notice `'0'` counts as present: a zero is a real answer.

## Old input and errors live in the session

The redirect after a failed submit is a brand-new request, so the errors and old input are **flashed**
into the session (lesson 2) for exactly one request. Passwords are never flashed back: Laravel leaves out
`password` and `password_confirmation`, so they aren't stored in the session or shown in the form again.

## PUT, PATCH and DELETE from a form

HTML forms can only send `GET` and `POST`. To reach an `update` or `destroy` route, a form posts with a
hidden `_method` field, which Laravel uses as the real method. Blade writes it with `@method`:

```blade
<form method="POST" action="{{ route('books.update', $book) }}">
    @csrf
    @method('PUT')
    ...
</form>
```

## When validation gets big

Long rule lists move into a **form request** class (`php artisan make:request StoreBookRequest`), which
also holds the authorization check. The controller just type-hints it, and by the time the method runs
the input is known to be valid. The Advanced Laravel course builds one from scratch.
