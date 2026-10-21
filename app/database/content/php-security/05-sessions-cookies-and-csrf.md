## The session id is a key

HTTP forgets everything between requests, so a logged-in visitor is remembered with a **session**:
the server keeps data under a random id, and the browser sends the id back in a cookie on every
request. Whoever presents that id *is* the user, as far as your app can tell. Protecting a login
mostly means protecting that one cookie.

## Cookie flags

A cookie is set with a `Set-Cookie` header, and its **attributes** decide where it goes:

- **`Secure`**: only sent over HTTPS, so it can't be read on an open Wi-Fi network.
- **`HttpOnly`**: invisible to JavaScript (`document.cookie`), so an XSS bug can't steal it.
- **`SameSite`**: whether it's sent when *another site* causes the request. `Lax` (the browser
  default today) sends it on normal links to your site but not on cross-site form posts; `Strict` never
  sends it cross-site; `None` always does, and requires `Secure`.
- **`Path`**, **`Domain`** and **`Max-Age`**/**`Expires`** limit where and how long it lives.

```php
$id = bin2hex(random_bytes(20));

$header = sprintf('Set-Cookie: session=%s; Path=/; Max-Age=%d; Secure; HttpOnly; SameSite=Lax', $id, 2 * 60 * 60);

echo $header;
```

Laravel's `config/session.php` sets these (`secure`, `http_only`, `same_site`), and its cookies are
also encrypted.

## Session fixation

If an attacker can make a victim use a session id **the attacker already knows** (through a link or a
planted cookie), and the id stays the same after the victim logs in, the attacker is now logged in as
the victim. The fix is simple: **give the session a new id whenever privileges change**, at login and
logout. In plain PHP that's `session_regenerate_id(true)`; Laravel does it in `Auth::login()`, and its
logout calls `$request->session()->invalidate()`.

Sessions should also **time out**: after a period with no activity (an idle timeout), and after a
maximum lifetime however active they are (an absolute timeout).

## Cross-site request forgery

Cookies go with *every* request to your site, including ones another site makes the browser send. A
page on `evil.example` can hold a hidden form that posts to `https://shop.example/account/email`; if
the victim is logged in, their browser attaches the session cookie and the request looks completely
genuine. That's **CSRF**: the attacker can't read the response, but they can make the victim *do*
things.

```html
<form action="https://shop.example/account/email" method="POST">
  <input type="hidden" name="email" value="attacker@evil.example">
</form>
<script>document.forms[0].submit()</script>
```

## CSRF tokens

The standard defence is a secret **token** that the attacker's page can't know:

1. Generate a random token once per session and store it in the session.
2. Put it in every form as a hidden field (Blade's `@csrf`), or in a header for JavaScript requests.
3. On every request that **changes state** (POST, PUT, PATCH, DELETE), compare the submitted token with
   the session's using `hash_equals()`. No match, no action (Laravel responds with 419).

`GET` requests must therefore never change anything: a link or an `<img src>` can trigger a GET from
any site, and the token check deliberately skips them.

```php
$session = ['csrf' => bin2hex(random_bytes(32))];

$fromOurForm = $session['csrf'];
$fromEvilPage = 'guess';

var_dump(hash_equals($session['csrf'], $fromOurForm), hash_equals($session['csrf'], $fromEvilPage));
```

`SameSite=Lax` cookies already stop most cross-site form posts, and checking the `Origin` header
(which browsers set on cross-site POSTs) adds another layer. Tokens remain the main defence, since
they don't depend on browser behaviour.

## Remember me

"Remember me" keeps a user logged in for weeks with a long-lived cookie. That cookie is a password, so
it gets the reset-token treatment: random, stored only as a hash, compared with `hash_equals()`. A
common design splits it into a **selector**, used to find the database row, and a **validator**, which
is only stored hashed. Looking a row up by a secret value would leak timing information about it; the
selector carries no secret, and the validator is compared in constant time.
