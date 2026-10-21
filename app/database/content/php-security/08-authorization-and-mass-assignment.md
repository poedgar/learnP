## Logged in is not the same as allowed

**Authentication** answers "who are you?"; **authorization** answers "may you do this?". Plenty of apps
get the first right and forget the second. The most common bug in the whole OWASP Top 10 is **broken
access control**: a logged-in user changes `/invoices/1041` to `/invoices/1040` and sees somebody
else's invoice, because the code checked that they were logged in but not that the invoice was theirs.

```php
$invoices = [
    1040 => ['id' => 1040, 'user_id' => 7, 'total' => 120],
    1041 => ['id' => 1041, 'user_id' => 42, 'total' => 35],
];
$currentUser = ['id' => 42];

$requested = 1040;
$invoice = $invoices[$requested] ?? null;

echo $invoice === null || $invoice['user_id'] !== $currentUser['id'] ? 'Not found' : "Total: {$invoice['total']}";
```

This is called an **insecure direct object reference** (IDOR). Every request that names a record by id
needs an ownership check, and the simplest way to never forget is to **query through the user**:
`$request->user()->invoices()->findOrFail($id)` can't return someone else's invoice.

Should the answer be 403 Forbidden or 404 Not Found? 404 doesn't even confirm that invoice 1040
exists, which is usually better for private data.

## Deny by default

Authorization code should start from "no" and list what's allowed. An ability nobody defined, a role
nobody expected, a missing check: each should mean **refused**. Laravel's **gates** and **policies**
work this way:

```laravel
Gate::define('edit-book', fn (User $user, Book $book) => $user->id === $book->author_id);

class BookPolicy
{
    public function update(User $user, Book $book): bool
    {
        return $user->isEditor() || $user->id === $book->author_id;
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->isAdmin();
    }
}

// In a controller:
$this->authorize('update', $book); // 403 if the policy says no
```

`Gate::before()` lets one rule override the rest (a super-admin who may do anything), and returning
`null` from it means "no opinion, carry on".

## Check on the server, every time

Hiding the "Delete" button from non-admins is a nicety, not security. Anyone can send the request the
button would have sent. The check belongs in the code that handles the request, and on every route
that changes or reveals something, not just the obvious ones.

## Mass assignment

Copying a whole request into a model is convenient and dangerous:

```php
$user = ['id' => 42, 'name' => 'Ada', 'email' => 'ada@example.com', 'role' => 'reader'];
$input = ['name' => 'Ada L.', 'role' => 'admin'];

$updated = array_merge($user, $input);

echo $updated['role'];
```

The profile form never had a `role` field, but nothing stops a visitor adding one. Only copy the fields
this action is meant to change, which is what Eloquent's `$fillable` and `$request->validated()` are for.
Ask "may *this user* change *this field*?" too: users may edit their own name, but changing a role is an
admin's decision, and even an admin shouldn't be able to remove their own admin role by accident.

## Redirects are part of it

After logging in, apps often send people back where they came from: `/login?redirect=/basket`. If any
URL is accepted, `/login?redirect=https://evil.example/login` makes your own trusted login page send
the victim to a copy that asks for their password again. That's an **open redirect**. Only accept local
paths (a single leading `/`, and not `//`, which browsers read as another host) or an allow-list of
destinations. Laravel's `redirect()->intended()` only uses URLs your app stored itself.

## Putting it together

The security habits from this course boil down to a few questions to ask of every request:

1. Where does each value come from, and have I checked it? (lesson 1)
2. Where is each value going, and is it escaped or bound for that place? (lessons 2 and 3)
3. Is this user who they say they are, and is their session safe? (lessons 4 and 5)
4. Could the data I handed out have been changed? (lesson 6)
5. Does a file name or path reach the disk? (lesson 7)
6. Is this user allowed to do *this*, to *this* record? (this lesson)
