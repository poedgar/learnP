## Never trust input

Everything a visitor sends can be wrong, missing or hostile. Laravel's validator checks incoming data
against **rules** before your code uses it, and when something fails it stops the request with a
list of messages: the browser is sent back to the form, or an API client gets a 422 response with the
errors as JSON.

```laravel
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'isbn' => ['required', new Isbn()],
    'price' => 'required|numeric|min:0',
    'tags' => 'array',
    'tags.*' => 'string|distinct',
]);

Book::create($validated);
```

`$validated` holds only the fields that have rules, so anything extra the visitor slipped in, like
`is_featured`, never reaches `create()`.

## Rules are strings, parsed once

`'required|string|max:255'` is a compact way of writing a list of rules, some with parameters. The
validator splits it at `|`, then each rule at the first `:`, then the parameters at `,`:

```php
$definition = 'required|in:paperback,hardback,ebook|max:20';

foreach (explode('|', $definition) as $rule) {
    [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');
    $parameters = $parameters === '' ? [] : explode(',', $parameters);
    echo $name . ' ' . json_encode($parameters) . PHP_EOL;
}
```

Rules can also be given as an array, which you need when a parameter itself contains a `|` (a
regular expression, say) or when a rule is an object.

## Checking a field

For each field, the validator runs its rules in order and collects a message for every rule that
fails. Messages are templates: `:attribute` becomes a readable version of the field name, and other
placeholders like `:max` become the rule's parameters:

```php
$messages = [
    'required' => 'The :attribute field is required.',
    'max' => 'The :attribute field must not be greater than :max characters.',
];

$field = 'first_name';
$value = str_repeat('a', 30);
$errors = [];

if ($value === '') {
    $errors[] = $messages['required'];
}
if (strlen($value) > 20) {
    $errors[] = str_replace(':max', '20', $messages['max']);
}

$attribute = str_replace('_', ' ', $field);
foreach ($errors as $error) {
    echo str_replace(':attribute', $attribute, $error) . PHP_EOL;
}
```

A few rules change how the others behave:

- Most rules **skip empty values**: `max:255` says nothing about a missing title. Only `required`
  (and its relatives) complain about a value being absent.
- **`nullable`** allows `null`, so the other rules are skipped when the value is null.
- **`sometimes`** validates the field only if it was sent at all.
- **`bail`** stops checking a field at its first failure, so you get one message, not five.
- `min` and `max` measure **size**: the value for numbers, the length for strings, the count for
  arrays.

## Your own rules

When no built-in rule fits, write a class implementing `ValidationRule`. Its `validate()` method
receives the attribute, the value and a `$fail` closure to call with a message:

```laravel
class Isbn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^97[89]\d{10}$/', (string) $value)) {
            $fail('The :attribute must be a valid ISBN-13.');
        }
    }
}
```

## Nested data

Requests often contain lists of records, like the lines of an order. A `*` in a rule's key applies it
to every element, and errors are reported with the real position, so the form can show each message
next to the right line:

```laravel
$request->validate([
    'lines' => 'required|array|min:1',
    'lines.*.book_id' => 'required|integer',
    'lines.*.quantity' => 'required|integer|min:1',
]);
// An error might be: 'lines.2.quantity' => ['The lines.2.quantity field must be at least 1.']
```

Expanding the wildcard means looking at the data to see which positions exist:

```php
$data = ['lines' => [['quantity' => 2], ['quantity' => 0], ['quantity' => 5]]];

$keys = [];
foreach (array_keys($data['lines']) as $index) {
    $keys[] = "lines.$index.quantity";
}

echo implode(', ', $keys);
```

## Form requests

Validation rules in a controller make it long, and the same rules are often needed in two places. A
**form request** is a class that holds them, along with authorization:

```laravel
class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Book::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?? $this->input('title'))]);
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:255', 'slug' => 'required|unique:books'];
    }
}

public function store(StoreBookRequest $request) // validated before this line runs
{
    Book::create($request->validated());
}
```

Type-hinting it is enough: the container builds it, and as it's resolved Laravel runs, in order,
`authorize()` (failing with 403), `prepareForValidation()` to tidy the input, and the rules (failing
with a `ValidationException`, which becomes the redirect or the 422). By the time your controller
method runs, the data is known to be good.
