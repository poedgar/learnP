## Logging people in

Almost every app needs accounts. Laravel's **starter kits** (Breeze, or the React, Vue and Livewire
kits) generate the whole lot in one command: registration, login, logout, password reset and email
verification, with pages and tests. It's worth knowing what they do underneath, because you'll extend
and debug that code.

```laravel
use Illuminate\Support\Facades\Auth;

if (Auth::attempt(['email' => $email, 'password' => $password], remember: true)) {
    $request->session()->regenerate();          // new session id, against session fixation

    return redirect()->intended('/dashboard');  // back to the page they wanted
}

return back()->withErrors(['email' => 'These credentials do not match our records.']);
```

`Auth::attempt()` finds the user by email, checks the password with `password_verify()` (passwords are
stored with `Hash::make()`, never as they are) and, if it matches, stores the user's id in the session.
After that, `Auth::check()`, `Auth::user()` and `$request->user()` tell any code who is logged in:

```php
$users = [
    ['id' => 1, 'email' => 'ada@example.com', 'password' => password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4])],
];
$session = [];

$user = array_values(array_filter($users, fn ($u) => $u['email'] === 'ada@example.com'))[0] ?? null;
if ($user !== null && password_verify('correct horse', $user['password'])) {
    $session['user_id'] = $user['id'];
}

echo isset($session['user_id']) ? "Logged in as user {$session['user_id']}" : 'Wrong email or password';
```

## Protecting routes with middleware

Pages that need an account get the `auth` **middleware**. A guest who visits one is redirected to the
login page, and the URL they wanted is remembered in the session, so after logging in
`redirect()->intended()` sends them back there. The `guest` middleware does the opposite for the login
and register pages: logged-in users are sent on to the dashboard.

```laravel
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class);
    Route::resource('orders', OrderController::class);
});

Route::get('/login', ShowLoginForm::class)->middleware('guest');
```

## Logging out

Logging out must really end the session: forget the user, **invalidate** the session (throw all its
data away and give it a new id) and **regenerate the CSRF token**:

```laravel
Auth::logout();
$request->session()->invalidate();
$request->session()->regenerateToken();

return redirect('/');
```

## Who may do what

Being logged in isn't the same as being allowed. Laravel answers "may this user do this?" with **gates**
and **policies** (the PHP security course goes deeper), and Blade can show or hide parts of a page with
`@can`:

```laravel
Gate::define('manage-stock', fn (User $user) => $user->role === 'admin');

if ($request->user()->cannot('manage-stock')) {
    abort(403);
}
```

```blade
@can('manage-stock')
    <a href="{{ route('stock.edit') }}">Edit stock</a>
@endcan
```

Simple apps often check a **role** column instead, with a small middleware used like
`->middleware('role:admin,editor')`, where the parameters after the colon are the roles that may pass.
Hiding a link is only for convenience: the route itself must check too.
