## Views

HTML doesn't belong inside controllers. Laravel keeps it in **views**: Blade templates in
`resources/views`, with names like `books/index.blade.php`. A controller passes data to a view, and the
view decides how it looks:

```laravel
// In the controller:
return view('books.index', ['books' => $books, 'shop' => 'Corner Books']);
```

```blade
{{-- resources/views/books/index.blade.php --}}
<h1>{{ $shop }}</h1>

@forelse ($books as $book)
    <p>{{ $book->title }} by {{ $book->author }}</p>
@empty
    <p>No books yet.</p>
@endforelse
```

Blade is compiled into plain PHP the first time it's used (look in `storage/framework/views`), so it's
as fast as hand-written PHP.

## Echoing values

`{{ $value }}` prints a value **escaped** with `htmlspecialchars()`, so a book called
`<script>...</script>` shows up as text instead of running. That makes `{{ }}` the right choice almost
every time. `{!! $value !!}` prints it **raw**, and is only for HTML you produced yourself.

Here is what `{{ }}` boils down to:

```php
$title = 'Tom & Jerry <Special Edition>';

echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>' . PHP_EOL;
echo '<h1>' . $title . '</h1>';
```

## Directives

Words starting with `@` are **directives**, Blade's control structures:

- `@if`, `@elseif`, `@else`, `@endif`, and `@unless` for "if not".
- `@foreach`, `@for`, `@while`, and `@forelse ... @empty ... @endforelse` for "loop, or say there's
  nothing".
- `@isset`, `@empty`, `@auth`, `@guest`, `@csrf`, `@method`...

Inside a `@foreach`, Blade gives you a `$loop` variable: `$loop->first`, `$loop->last`,
`$loop->iteration` (counting from 1), `$loop->index` (from 0) and `$loop->count`. It's handy for commas
between items or for marking the first row.

Each directive compiles to a line of PHP. `@if ($book->inStock)` becomes
`<?php if ($book->inStock): ?>`, and `@endif` becomes `<?php endif; ?>`.

## Layouts

Every page shares a header and footer. A **layout** holds them, with `@yield` marking the gaps, and each
page fills the gaps with `@section`:

```blade
{{-- layouts/app.blade.php --}}
<html>
  <head><title>@yield('title', 'Corner Books')</title></head>
  <body>@yield('content')</body>
</html>

{{-- books/index.blade.php --}}
@extends('layouts.app')

@section('title', 'All books')

@section('content')
    <h1>All books</h1>
@endsection
```

The second argument of `@yield` is a default, used when a page doesn't define that section.

## Components

Repeated pieces of HTML, like an alert box or a book card, become **components**, used like HTML tags
whose name starts with `x-`. Attributes you pass that the component doesn't use are collected in
`$attributes`, so the component can put them on its outer element, merging classes with its own:

```blade
<x-alert type="error" class="mb-4">Your card was declined.</x-alert>

{{-- components/alert.blade.php --}}
<div {{ $attributes->merge(['class' => 'alert alert-' . $type]) }}>
    {{ $slot }}
</div>
```

That renders `<div class="alert alert-error mb-4">Your card was declined.</div>`. `$slot` is whatever was
between the opening and closing tags.

Turning an array of attributes into HTML is a small loop, with every value escaped:

```php
$attributes = ['class' => 'btn btn-primary', 'type' => 'submit', 'title' => 'Save "Dune"'];

$html = '';
foreach ($attributes as $name => $value) {
    $html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
}

echo "<button$html>Save</button>";
```
