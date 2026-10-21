<?php

// Lesson 6 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'tokens-and-signatures',
    'title' => 'Tokens and signatures',
    'summary' => 'Detecting tampering with data you hand out: HMAC signatures, timing-safe comparison, secure randomness, signed URLs, base64url tokens and JWTs, refusing `alg: none`, and rotating keys.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try changing one character of $data.
        $key = 'a-long-random-secret-from-config';
        $data = 'unsubscribe=ada@example.com';

        $signature = hash_hmac('sha256', $data, $key);
        echo "$data&sig=$signature" . PHP_EOL;

        $tampered = 'unsubscribe=bob@example.com';
        $valid = hash_equals(hash_hmac('sha256', $tampered, $key), $signature);
        echo 'Tampered link valid? ' . var_export($valid, true);
        PHP,

    'quiz' => [
        [
            'question' => 'What does an HMAC signature let the server check?',
            'options' => [
                'That the data is encrypted',
                'That the data was produced by someone holding the secret key and hasn\'t been changed',
                'That the user is logged in',
                'That the data is valid JSON',
            ],
            'correct_option' => 1,
            'explanation' => 'Without the key, nobody can make a matching signature for different data.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = hash_hmac('sha256', 'book=7', 'key-one');
                $b = hash_hmac('sha256', 'book=7', 'key-two');
                $c = hash_hmac('sha256', 'book=7', 'key-one');

                var_dump($a === $b, $a === $c, strlen($a));
                PHP,
            'options' => [
                '`bool(true)`, `bool(true)`, `int(64)`',
                '`bool(false)`, `bool(true)`, `int(64)`',
                '`bool(false)`, `bool(false)`, `int(32)`',
                '`bool(false)`, `bool(true)`, `int(32)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Same data and key, same signature; a different key gives a different one. SHA-256 in hex is 64 characters.',
        ],
        [
            'question' => 'Why compare signatures with `hash_equals()` instead of `===`?',
            'options' => [
                '`===` doesn\'t work on strings',
                '`hash_equals()` takes the same time however many characters match, so timing can\'t reveal the signature bit by bit',
                'It is faster',
                'It ignores case',
            ],
            'correct_option' => 1,
            'explanation' => '`===` stops at the first difference, which leaks how much of a guess was right.',
        ],
        [
            'question' => 'Which is a good way to make a random token?',
            'options' => ['`md5(time())`', '`uniqid()`', '`bin2hex(random_bytes(32))`', '`mt_rand()`'],
            'correct_option' => 2,
            'explanation' => 'Only `random_bytes()` uses a cryptographically secure generator; the others can be predicted.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $b64url = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

                echo base64_encode("\xfb\xff") . ' ' . $b64url("\xfb\xff");
                PHP,
            'options' => ['`+/8= -_8`', '`-_8 +/8=`', '`+/8= +/8`', '`-_8= -_8`'],
            'correct_option' => 0,
            'explanation' => 'Base64url swaps `+` and `/` for `-` and `_` and drops the `=` padding, so it fits in URLs.',
        ],
        [
            'question' => 'A JWT\'s payload is base64url encoded. Who can read it?',
            'options' => [
                'Only the server with the key',
                'Anyone who has the token, since base64 is an encoding, not encryption',
                'Nobody',
                'Only the user it was issued to',
            ],
            'correct_option' => 1,
            'explanation' => 'The signature stops changes, not reading. Never put secrets in a JWT payload.',
        ],
        [
            'question' => 'A token arrives with the header `{"alg": "none"}` and no signature. What should the server do?',
            'options' => [
                'Accept it, since there is nothing to check',
                'Reject it: the server decides the algorithm, and anything else is refused',
                'Check it with SHA-1 instead',
                'Ask the user to log in again, then accept it',
            ],
            'correct_option' => 1,
            'explanation' => 'Letting the token pick how it\'s verified lets an attacker pick "not at all".',
        ],
        [
            'question' => 'How do you change a signing key without invalidating every link and session at once?',
            'options' => [
                'You can\'t',
                'Sign with the new key, but still accept signatures made with the previous keys for a while',
                'Use both keys concatenated',
                'Stop signing for a day',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s key rotation, which Laravel supports with `APP_PREVIOUS_KEYS`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Sign and verify',
            'prompt' => 'Write `sign(string $data, string $key): string`, returning the hex HMAC-SHA256 of the data, and `verify(string $data, mixed $signature, string $key): bool`, which recomputes it and compares with `hash_equals()`. A signature that isn\'t a string never verifies.',
            'starter_code' => <<<'PHP'
                <?php

                function sign(string $data, string $key): string
                {
                    return hash('sha256', $key . $data);
                }

                function verify(string $data, mixed $signature, string $key): bool
                {
                    return sign($data, $key) == $signature;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function sign(string $data, string $key): string
                {
                    return hash_hmac('sha256', $data, $key);
                }

                function verify(string $data, mixed $signature, string $key): bool
                {
                    return is_string($signature) && hash_equals(sign($data, $key), $signature);
                }
                PHP,
            'hint' => '`hash_hmac(\'sha256\', $data, $key)` makes the signature. Check `is_string()` before `hash_equals()`.',
            'checks' => [
                ['label' => 'A real HMAC', 'code' => "return expect_same(sign('book=7', 'k'), hash_hmac('sha256', 'book=7', 'k'));"],
                ['label' => 'Verifying', 'code' => "\$sig = sign('book=7', 'k');\nreturn expect_same([verify('book=7', \$sig, 'k'), verify('book=8', \$sig, 'k'), verify('book=7', \$sig, 'other key'), verify('book=7', strtoupper(\$sig), 'k')], [true, false, false, false]);"],
                ['label' => 'Non-strings never verify', 'code' => "return expect_same([verify('x', true, 'k'), verify('x', null, 'k'), verify('x', 0, 'k')], [false, false, false]);"],
                ['label' => 'hash_equals() is used', 'code' => "return str_contains(learnp_code(), 'hash_equals') ?: 'Compare signatures with hash_equals().';"],
            ],
        ],
        [
            'title' => 'A temporary signed URL',
            'prompt' => 'Write `signedUrl(string $url, int $expires, string $key): string`, which adds `expires=...` to the URL\'s query (with `?` or `&` as needed) and then `&signature=...`, the HMAC-SHA256 of everything before `&signature=`. Then write `hasValidSignature(string $url, int $now, string $key): bool`: the URL must end with a `signature` parameter that matches, and `expires` must be later than `$now`.',
            'starter_code' => <<<'PHP'
                <?php

                function signedUrl(string $url, int $expires, string $key): string
                {
                    return $url;
                }

                function hasValidSignature(string $url, int $now, string $key): bool
                {
                    return true;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function signedUrl(string $url, int $expires, string $key): string
                {
                    $unsigned = $url . (str_contains($url, '?') ? '&' : '?') . 'expires=' . $expires;

                    return $unsigned . '&signature=' . hash_hmac('sha256', $unsigned, $key);
                }

                function hasValidSignature(string $url, int $now, string $key): bool
                {
                    if (preg_match('/^(.*)&signature=([a-f0-9]{64})$/', $url, $m) !== 1) {
                        return false;
                    }
                    [, $unsigned, $signature] = $m;

                    if (! hash_equals(hash_hmac('sha256', $unsigned, $key), $signature)) {
                        return false;
                    }

                    parse_str((string) parse_url($unsigned, PHP_URL_QUERY), $query);

                    return isset($query['expires']) && isDigits($query['expires']) && (int) $query['expires'] > $now;
                }

                function isDigits(mixed $value): bool
                {
                    return is_string($value) && preg_match('/^\d+$/', $value) === 1;
                }
                PHP,
            'hint' => 'Split off the signature with a pattern like `/^(.*)&signature=([a-f0-9]{64})$/`, check it, then read `expires` from the rest with `parse_url()` and `parse_str()`.',
            'checks' => [
                ['label' => 'The URL gets expires and a signature', 'code' => "\$u = signedUrl('https://shop.example/download/7', 2000, 'k');\nreturn expect_same(\$u, 'https://shop.example/download/7?expires=2000&signature=' . hash_hmac('sha256', 'https://shop.example/download/7?expires=2000', 'k'));"],
                ['label' => 'Existing queries are kept', 'code' => "\$u = signedUrl('https://shop.example/unsubscribe?email=ada%40example.com', 2000, 'k');\nreturn str_starts_with(\$u, 'https://shop.example/unsubscribe?email=ada%40example.com&expires=2000&signature=') && hasValidSignature(\$u, 1000, 'k') ?: \"Unexpected URL: \$u\";"],
                ['label' => 'Untouched URLs pass until they expire', 'code' => "\$u = signedUrl('https://shop.example/download/7', 2000, 'k');\nreturn expect_same([hasValidSignature(\$u, 1999, 'k'), hasValidSignature(\$u, 2000, 'k')], [true, false]);"],
                ['label' => 'Tampering is caught', 'code' => "\$u = signedUrl('https://shop.example/download/7', 2000, 'k');\nreturn expect_same([hasValidSignature(str_replace('/7?', '/8?', \$u), 1000, 'k'), hasValidSignature(str_replace('expires=2000', 'expires=9999', \$u), 1000, 'k'), hasValidSignature(\$u, 1000, 'other'), hasValidSignature('https://shop.example/download/7', 1000, 'k')], [false, false, false, false]);"],
            ],
        ],
        [
            'title' => 'base64url',
            'prompt' => 'Write `base64UrlEncode(string $bytes): string` (base64 with `-` and `_` for `+` and `/`, no `=` padding) and `base64UrlDecode(string $text): ?string`, which reverses it, returning `null` if the text contains anything other than `A-Z`, `a-z`, `0-9`, `-` and `_`, or has a length that can\'t be valid (a remainder of 1 when divided by 4).',
            'starter_code' => <<<'PHP'
                <?php

                function base64UrlEncode(string $bytes): string
                {
                    return base64_encode($bytes);
                }

                function base64UrlDecode(string $text): ?string
                {
                    return base64_decode($text);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function base64UrlEncode(string $bytes): string
                {
                    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
                }

                function base64UrlDecode(string $text): ?string
                {
                    if (preg_match('/^[A-Za-z0-9_-]*$/', $text) !== 1 || strlen($text) % 4 === 1) {
                        return null;
                    }

                    $decoded = base64_decode(strtr($text, '-_', '+/'), true);

                    return $decoded === false ? null : $decoded;
                }
                PHP,
            'hint' => 'Encoding: `rtrim(strtr(base64_encode(...), \'+/\', \'-_\'), \'=\')`. Decoding: check the characters and length, swap the characters back, and use `base64_decode(..., true)`, which works without padding.',
            'checks' => [
                ['label' => 'URL-safe characters, no padding', 'code' => "return expect_same([base64UrlEncode(\"\\xfb\\xff\"), base64UrlEncode('Dune'), base64UrlEncode('')], ['-_8', 'RHVuZQ', '']);"],
                ['label' => 'Round trips', 'code' => "foreach (['', 'a', 'ab', 'abc', '{\"sub\":42}', random_bytes(33)] as \$bytes) {\n    if (base64UrlDecode(base64UrlEncode(\$bytes)) !== \$bytes) return 'A value did not survive the round trip.';\n}\nreturn true;"],
                ['label' => 'Bad characters give null', 'code' => "return expect_same([base64UrlDecode('+/8='), base64UrlDecode('ab cd'), base64UrlDecode('RHVu.ZQ')], [null, null, null]);"],
                ['label' => 'Impossible lengths give null', 'code' => "return expect_same(base64UrlDecode('RHVuZ'), null);"],
            ],
        ],
        [
            'title' => 'A signed token',
            'prompt' => 'Write `makeToken(array $payload, string $key): string`, returning `payload.signature`: the base64url JSON of the payload, a dot, and the base64url of the **raw** HMAC-SHA256 (`hash_hmac(..., true)`) of the first part. Then write `readToken(string $token, string $key, int $now): ?array`, returning the payload if the signature matches and its `exp` is an integer later than `$now`, and `null` otherwise. The base64url helpers are given.',
            'starter_code' => <<<'PHP'
                <?php

                function b64url(string $bytes): string
                {
                    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
                }

                function unb64url(string $text): ?string
                {
                    $decoded = base64_decode(strtr($text, '-_', '+/'), true);

                    return $decoded === false ? null : $decoded;
                }

                function makeToken(array $payload, string $key): string
                {
                    return b64url(json_encode($payload));
                }

                function readToken(string $token, string $key, int $now): ?array
                {
                    return json_decode(unb64url($token) ?? '', true);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function b64url(string $bytes): string
                {
                    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
                }

                function unb64url(string $text): ?string
                {
                    $decoded = base64_decode(strtr($text, '-_', '+/'), true);

                    return $decoded === false ? null : $decoded;
                }

                function makeToken(array $payload, string $key): string
                {
                    $body = b64url(json_encode($payload));

                    return $body . '.' . b64url(hash_hmac('sha256', $body, $key, true));
                }

                function readToken(string $token, string $key, int $now): ?array
                {
                    $parts = explode('.', $token);
                    if (count($parts) !== 2) {
                        return null;
                    }
                    [$body, $signature] = $parts;

                    if (! hash_equals(b64url(hash_hmac('sha256', $body, $key, true)), $signature)) {
                        return null;
                    }

                    $payload = json_decode(unb64url($body) ?? '', true);
                    if (! is_array($payload) || ! is_int($payload['exp'] ?? null) || $payload['exp'] <= $now) {
                        return null;
                    }

                    return $payload;
                }
                PHP,
            'hint' => 'Sign the encoded payload, not the array. When reading, check the signature before decoding anything, then check that `exp` is an int later than `$now`.',
            'checks' => [
                ['label' => 'Tokens round-trip', 'code' => "\$t = makeToken(['sub' => 42, 'exp' => 2000], 'k');\nreturn expect_same(readToken(\$t, 'k', 1000), ['sub' => 42, 'exp' => 2000]);"],
                ['label' => 'The format is payload.signature', 'code' => "\$t = makeToken(['sub' => 1, 'exp' => 5], 'k');\n[\$body, \$sig] = explode('.', \$t) + [1 => ''];\nreturn \$body === b64url('{\"sub\":1,\"exp\":5}') && \$sig === b64url(hash_hmac('sha256', \$body, 'k', true)) ?: 'The token does not have the described format.';"],
                ['label' => 'Changed payloads and wrong keys fail', 'code' => "\$t = makeToken(['sub' => 42, 'exp' => 2000], 'k');\n[, \$sig] = explode('.', \$t) + [1 => ''];\n\$forged = b64url('{\"sub\":1,\"exp\":2000}') . '.' . \$sig;\nreturn expect_same([readToken(\$forged, 'k', 1000), readToken(\$t, 'other', 1000), readToken('nonsense', 'k', 1000)], [null, null, null]);"],
                ['label' => 'Expired or exp-less tokens fail', 'code' => "return expect_same([readToken(makeToken(['sub' => 42, 'exp' => 2000], 'k'), 'k', 2000), readToken(makeToken(['sub' => 42], 'k'), 'k', 0), readToken(makeToken(['exp' => '9999'], 'k'), 'k', 0)], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Don\'t trust the header',
            'prompt' => 'Write `decodeJwt(string $token, string $key): ?array` for HS256 JSON Web Tokens: `header.payload.signature`, each base64url. Return the payload only if there are exactly three parts, the header decodes to exactly `alg` `HS256` (any other value, including `none`, is refused), and the signature is the base64url raw HMAC-SHA256 of `header.payload`. Otherwise return `null`. The helpers are given.',
            'starter_code' => <<<'PHP'
                <?php

                function b64url(string $bytes): string
                {
                    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
                }

                function unb64url(string $text): ?string
                {
                    $decoded = base64_decode(strtr($text, '-_', '+/'), true);

                    return $decoded === false ? null : $decoded;
                }

                function decodeJwt(string $token, string $key): ?array
                {
                    [$header, $payload, $signature] = explode('.', $token) + [null, null, null];
                    $alg = json_decode(unb64url($header) ?? '', true)['alg'] ?? 'none';

                    if ($alg !== 'none' && ! hash_equals(b64url(hash_hmac('sha256', "$header.$payload", $key, true)), (string) $signature)) {
                        return null;
                    }

                    return json_decode(unb64url($payload ?? '') ?? '', true);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function b64url(string $bytes): string
                {
                    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
                }

                function unb64url(string $text): ?string
                {
                    $decoded = base64_decode(strtr($text, '-_', '+/'), true);

                    return $decoded === false ? null : $decoded;
                }

                function decodeJwt(string $token, string $key): ?array
                {
                    $parts = explode('.', $token);
                    if (count($parts) !== 3) {
                        return null;
                    }
                    [$header, $payload, $signature] = $parts;

                    $decodedHeader = json_decode(unb64url($header) ?? '', true);
                    if (! is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== 'HS256') {
                        return null;
                    }

                    if (! hash_equals(b64url(hash_hmac('sha256', "$header.$payload", $key, true)), $signature)) {
                        return null;
                    }

                    $claims = json_decode(unb64url($payload) ?? '', true);

                    return is_array($claims) ? $claims : null;
                }
                PHP,
            'hint' => 'Decide the algorithm yourself: refuse any header whose `alg` isn\'t exactly `HS256`, then always check the signature.',
            'checks' => [
                ['label' => 'A valid HS256 token', 'code' => "\$h = b64url('{\"alg\":\"HS256\",\"typ\":\"JWT\"}');\n\$p = b64url('{\"sub\":42}');\n\$t = \"\$h.\$p.\" . b64url(hash_hmac('sha256', \"\$h.\$p\", 'k', true));\nreturn expect_same(decodeJwt(\$t, 'k'), ['sub' => 42]);"],
                ['label' => 'alg: none is refused', 'code' => "\$h = b64url('{\"alg\":\"none\",\"typ\":\"JWT\"}');\n\$p = b64url('{\"sub\":1,\"admin\":true}');\nreturn expect_same([decodeJwt(\"\$h.\$p.\", 'k'), decodeJwt(\"\$h.\$p\", 'k')], [null, null]);"],
                ['label' => 'Other algorithms are refused', 'code' => "\$h = b64url('{\"alg\":\"HS512\"}');\n\$p = b64url('{\"sub\":1}');\n\$t = \"\$h.\$p.\" . b64url(hash_hmac('sha256', \"\$h.\$p\", 'k', true));\nreturn expect_same(decodeJwt(\$t, 'k'), null);"],
                ['label' => 'Bad signatures and shapes are refused', 'code' => "\$h = b64url('{\"alg\":\"HS256\"}');\n\$p = b64url('{\"sub\":42}');\n\$sig = b64url(hash_hmac('sha256', \"\$h.\$p\", 'other', true));\nreturn expect_same([decodeJwt(\"\$h.\$p.\$sig\", 'k'), decodeJwt('a.b.c.d', 'k'), decodeJwt('', 'k')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Rotating keys',
            'prompt' => 'Write `Signer`, constructed with a list of keys, newest first. `sign(string $data)` returns the hex HMAC-SHA256 with the **newest** key. `verify(string $data, string $signature)` accepts a signature made with **any** of the keys, comparing with `hash_equals()`. An empty key list throws `InvalidArgumentException` from the constructor.',
            'starter_code' => <<<'PHP'
                <?php

                class Signer
                {
                    public function __construct(private array $keys) {}

                    public function sign(string $data): string
                    {
                        return hash_hmac('sha256', $data, end($this->keys));
                    }

                    public function verify(string $data, string $signature): bool
                    {
                        return hash_equals($this->sign($data), $signature);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Signer
                {
                    public function __construct(private array $keys)
                    {
                        if ($keys === []) {
                            throw new InvalidArgumentException('At least one key is needed.');
                        }
                        $this->keys = array_values($keys);
                    }

                    public function sign(string $data): string
                    {
                        return hash_hmac('sha256', $data, $this->keys[0]);
                    }

                    public function verify(string $data, string $signature): bool
                    {
                        foreach ($this->keys as $key) {
                            if (hash_equals(hash_hmac('sha256', $data, $key), $signature)) {
                                return true;
                            }
                        }

                        return false;
                    }
                }
                PHP,
            'hint' => 'Sign with `$this->keys[0]`. In `verify()`, loop over every key and return true as soon as one matches.',
            'checks' => [
                ['label' => 'Signing uses the newest key', 'code' => "return expect_same((new Signer(['new', 'old']))->sign('x'), hash_hmac('sha256', 'x', 'new'));"],
                ['label' => 'Old signatures still verify', 'code' => "\$old = (new Signer(['old']))->sign('book=7');\n\$rotated = new Signer(['new', 'old']);\nreturn expect_same([\$rotated->verify('book=7', \$old), \$rotated->verify('book=7', \$rotated->sign('book=7'))], [true, true]);"],
                ['label' => 'Retired keys stop working', 'code' => "\$ancient = (new Signer(['ancient']))->sign('book=7');\nreturn expect_same([(new Signer(['new', 'old']))->verify('book=7', \$ancient), (new Signer(['new', 'old']))->verify('book=8', (new Signer(['old']))->sign('book=7'))], [false, false]);"],
                ['label' => 'No keys, no signer', 'code' => 'return expect_throws(InvalidArgumentException::class, fn () => new Signer([]));'],
            ],
        ],
    ],
];
