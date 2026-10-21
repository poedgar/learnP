## When data becomes code

**Cross-site scripting (XSS)** happens when text from a user ends up in a page as HTML. A review that
says `<script>steal(document.cookie)</script>`, shown as-is, isn't a review any more: the browser
runs it, with the permissions of your site and of whoever is looking at the page. It can read what
they see, send requests as them and change the page.

```php
$review = '<script>alert("Your session is mine")</script>Great book!';

echo '<p>' . $review . '</p>' . PHP_EOL;
echo '<p>' . htmlspecialchars($review, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
```

The fix is **escaping on output**: turning the characters that mean something in HTML (`<`, `>`, `&`,
`"`, `'`) into entities like `&lt;`, so the browser shows them instead of obeying them. There are
three kinds of XSS, all fixed the same way:

- **Stored**: the payload is saved (a review, a username) and shown to everyone who visits.
- **Reflected**: it comes from the request itself (`?q=<script>...`) and is echoed straight back.
- **DOM-based**: JavaScript on the page puts untrusted data into the DOM with `innerHTML`.

## `htmlspecialchars()`, properly

PHP's escaping function needs the right options:

- `ENT_QUOTES` escapes both `"` and `'`, which matters inside attributes.
- `ENT_SUBSTITUTE` replaces invalid UTF-8 instead of returning an empty string.
- `'UTF-8'` names the character set.

Since PHP 8.1 the first two are the default, but writing them out makes the intent clear. Laravel's
`e()` helper and Blade's `{{ $value }}` call it for you. `{!! $value !!}` skips it, so it must only
ever be used for HTML you produced yourself.

## Escaping depends on where the data goes

HTML has several contexts, and each needs its own treatment:

- **Text between tags**: `htmlspecialchars()`.
- **Attribute values**: `htmlspecialchars()` too, and the attribute **must be quoted**. Unquoted,
  a space is enough to start a new attribute: `<img alt=x onerror=alert(1)>`.
- **URLs in `href` or `src`**: escaping isn't enough, because `javascript:alert(1)` contains nothing to
  escape. Check the **scheme** against an allow-list (`http`, `https`, `mailto`).
- **Inside `<script>`**: use `json_encode()` with the `JSON_HEX_*` flags, so a `</script>` in the data
  can't end the script block.

```php
$userUrl = 'javascript:alert(document.cookie)';
$escaped = htmlspecialchars($userUrl, ENT_QUOTES, 'UTF-8');

echo "<a href=\"$escaped\">Author's site</a>" . PHP_EOL;

$scheme = strtolower((string) parse_url($userUrl, PHP_URL_SCHEME));
echo in_array($scheme, ['http', 'https'], true) ? 'allowed' : 'blocked';
```

Browsers are forgiving about URLs, which attackers rely on: `JaVaScRiPt:`, leading spaces and even tabs
or newlines inside the word all still work. Normalise before checking the scheme.

## Data for JavaScript

Passing PHP data to a script with `json_encode()` is right, but its defaults leave `<` and `/` alone,
so a string containing `</script>` closes the tag early:

```php
$title = '</script><script>alert(1)</script>';

echo json_encode($title) . PHP_EOL;
echo json_encode($title, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
```

With the `JSON_HEX_*` flags, `<` becomes `<`, which JavaScript reads as the same character but the
HTML parser doesn't see as a tag. Laravel's `@json()` Blade directive uses these flags.

## Allowing some formatting

Sometimes users may format text: bold, links, line breaks. Never try to strip "dangerous" tags out of
HTML yourself; there are far too many tricks. Either:

1. **Escape everything first**, then turn a few safe markers into HTML (`**bold**` → `<strong>`).
   Whatever the user typed can't become a tag, because it was escaped before you added yours.
2. Or use a well-tested **HTML sanitizer** with an allow-list of tags and attributes, like
   HTML Purifier, or Laravel's `Str::markdown()` with HTML input disabled.

## Defence in depth: Content Security Policy

A **Content-Security-Policy** header tells the browser which scripts may run at all. With
`script-src 'self'`, an injected inline `<script>` is refused even if escaping was missed somewhere:

```text
Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none'
```

Keywords such as `'self'` and `'none'` are written in single quotes; hosts are not. CSP is a safety net,
not a replacement for escaping. Also mark session cookies `HttpOnly`, so even a successful XSS can't
read them (lesson 5).
