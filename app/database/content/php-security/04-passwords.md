## Assume the database leaks

Databases get copied: through an injection bug, a stolen backup, a misconfigured server. When that
happens, the passwords in it must be useless to the thief. People reuse passwords, so a leaked
password from your bookshop is a key to their email and bank too.

That rules out storing passwords as they are, and it also rules out **encrypting** them: anything
that can be decrypted can be decrypted by whoever steals the key. Passwords are **hashed**: turned
into a fingerprint that can be checked but not reversed.

## Fast hashes are the wrong tool

`md5()` and `sha1()` (and even `sha256`) are designed to be **fast**, which is exactly wrong here. A
graphics card can try billions of guesses per second against them, and identical passwords give
identical hashes, so one lookup table cracks every user who chose `password123`:

```php
foreach (['password123', 'password123', 'correct horse battery staple'] as $password) {
    echo md5($password) . PHP_EOL;
}
```

Password hashing algorithms (**bcrypt**, **Argon2**) fix both problems. They are deliberately **slow**
(tunable with a *cost*), and each hash includes a random **salt**, so the same password never gives the
same hash twice.

## `password_hash()` and `password_verify()`

PHP does all of this for you, and you should never build your own:

```php
$hash = password_hash('correct horse battery staple', PASSWORD_DEFAULT, ['cost' => 10]);

echo $hash . PHP_EOL;
var_dump(password_verify('correct horse battery staple', $hash));
var_dump(password_verify('Correct horse battery staple', $hash));
```

The hash string contains everything needed to check it later: `$2y$` (bcrypt), `10$` (the cost), the
salt, then the hash itself. Store it in a column of at least 255 characters, since future algorithms
may produce longer strings. `password_verify()` compares in constant time.

`PASSWORD_DEFAULT` is bcrypt today; PHP may change it in a later version, and `password_needs_rehash()`
tells you when a stored hash uses older settings. The right moment to upgrade is when the user logs in,
because that's the only time you have the plain password. Laravel's `Hash::make()`, `Hash::check()` and
`Hash::needsRehash()` wrap these functions, and Laravel rehashes on login automatically.

## Moving away from old hashes

Many older apps still have `md5` hashes. You can't convert them without the passwords, but you can
upgrade each one the next time its owner logs in: check the old way, and if it matches, replace the
stored value with a `password_hash()` of the password they just typed.

## What makes a good password policy

Modern guidance (NIST SP 800-63B) is simpler than the rules many sites still use:

- Require **length** (at least 8 characters, better 12 or more) and allow long passphrases.
- **Don't** require mixtures of upper case, digits and symbols, or force regular changes; they lead to
  `Password1!` and post-it notes.
- **Do** reject passwords known from breaches (`password`, `123456`...) and ones containing obvious
  context, like the user's own email or the site's name.

## Reset tokens are passwords too

A "forgot password" email contains a link with a random **token**. Anyone holding a valid token can
take over the account, so treat it like a password:

- Make it long and truly random: `bin2hex(random_bytes(32))`, never `rand()`, `uniqid()` or a time.
- Store only a **hash** of it (a fast hash like SHA-256 is fine here, because the token is already
  random and long), so a database leak doesn't hand out working links.
- Give it a short **expiry** (Laravel uses 60 minutes), make it single-use, and compare with
  `hash_equals()`.

```php
$token = bin2hex(random_bytes(32));
$stored = hash('sha256', $token);

echo strlen($token) . ' characters in the email' . PHP_EOL;
var_dump(hash_equals($stored, hash('sha256', $token)));
```

## Slowing down guessing

Even well-hashed passwords can be guessed online, one login attempt at a time. **Rate limiting**
caps failed attempts per account and per IP address within a time window (Laravel's login allows five
per minute by default), and a successful login clears the count. Say "These credentials do not match
our records" for both a wrong email and a wrong password, so attackers can't learn which emails have
accounts.
