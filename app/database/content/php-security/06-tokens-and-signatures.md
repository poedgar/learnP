## Data that comes back

Apps often hand data to the browser and expect it back unchanged: a download link that should only
work for a day, an "unsubscribe" link for one address, a token saying who's logged in to an API. The
visitor holds that data, so they can edit it. A **signature** makes any edit detectable.

## HMAC: a signature from a secret

An **HMAC** is a hash of the data mixed with a secret **key** that only the server knows. Anyone can
see the data and the signature, but without the key nobody can make a valid signature for different
data, and changing a single character changes the whole signature:

```php
$key = 'a-long-random-secret-from-config';

$signature = hash_hmac('sha256', 'download=book-7&user=42', $key);
$forged = hash_hmac('sha256', 'download=book-8&user=42', $key);

echo $signature . PHP_EOL . $forged;
```

To check a signature, compute it again from the data and compare. Use a real HMAC: a plain hash of
`key . data` is open to *length-extension* attacks with SHA-256, which `hash_hmac()` is built to avoid.
In Laravel, the key is `APP_KEY`, and it must stay secret and differ between environments.

## Comparing secrets: timing attacks

`===` stops at the first character that differs, so comparing a guess against the real signature
takes slightly longer the more leading characters are right. Measured over many requests, that tiny
difference lets an attacker discover a signature one character at a time. `hash_equals()` always takes
the same time, so use it for every comparison involving a secret: signatures, tokens, hashes.

## Randomness

Tokens and keys must be **unpredictable**. `rand()`, `mt_rand()`, `uniqid()`, `time()` and
`md5(time())` are all predictable to an attacker who can see a few values or guess roughly when one was
made. Use the operating system's secure generator: `random_bytes()` for raw bytes, then `bin2hex()` or
base64 to make them printable.

## Signed URLs

Laravel's `URL::temporarySignedRoute('download', now()->addDay(), ['book' => 7])` produces a URL with
two extra query parameters, `expires` and `signature`. The signature covers the whole URL including
`expires`, so neither the book nor the expiry time can be changed, and the `signed` middleware checks
it on the way in. You'll build the same thing.

## Self-contained tokens

A signed token can carry data the server doesn't need to store: who the user is and when the token
expires. JSON Web Tokens (JWT) are the common format: three **base64url** parts joined with dots, the
header, the payload and the signature.

```php
$b64url = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

$header = $b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$payload = $b64url(json_encode(['sub' => 42, 'exp' => 1767225600]));
$signature = $b64url(hash_hmac('sha256', "$header.$payload", 'secret', true));

echo "$header.$payload.$signature";
```

Base64url is base64 with `-` and `_` instead of `+` and `/` and no `=` padding, so it is safe in URLs.
Note that base64 is an **encoding**, not encryption: anyone can read the payload. Never put secrets in it.

## Don't let the token choose how it's checked

A JWT header names its algorithm, and early libraries trusted it. An attacker could send
`"alg": "none"` and no signature, and some servers accepted the token unchecked. Always decide the
algorithm on the server and reject tokens that say anything else.

Self-contained tokens also can't be cancelled before they expire, since nothing is stored. Keep their
lifetime short, or keep a list of revoked token ids.

## Rotating keys

Keys leak, and good practice is to change them now and then. To change a key without logging everyone
out, keep the old one around for a while: **sign** with the newest key and **accept** signatures made
with any key on the list. Laravel supports this with `APP_PREVIOUS_KEYS`.
