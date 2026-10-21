<?php

// Lesson 4 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'passwords',
    'title' => 'Passwords',
    'summary' => 'Storing passwords so a leak doesn\'t expose them: why fast hashes fail, `password_hash()` and `password_verify()`, rehashing and leaving md5 behind, password policy, reset tokens and rate limiting.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Run it twice and compare the hashes.
        $password = 'correct horse battery staple';

        $first = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
        $second = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);

        echo $first . PHP_EOL . $second . PHP_EOL;
        var_dump($first === $second, password_verify($password, $first), password_verify($password, $second));
        PHP,

    'quiz' => [
        [
            'question' => 'Why should passwords be hashed rather than encrypted?',
            'options' => [
                'Hashing is faster',
                'Anything encrypted can be decrypted by whoever gets the key; a hash can only be checked, not reversed',
                'Encryption isn\'t available in PHP',
                'Hashes are shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'If the database leaks, the key often leaks with it. With hashes there is no key to steal.',
        ],
        [
            'question' => 'What is wrong with storing `md5($password)`?',
            'options' => [
                'Nothing, md5 can\'t be reversed',
                'md5 is fast and unsalted, so attackers can try billions of guesses per second and crack identical passwords all at once',
                'md5 hashes are too long',
                'md5 only works for ASCII',
            ],
            'correct_option' => 1,
            'explanation' => 'Password hashing needs to be slow and salted, which bcrypt and Argon2 are.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $a = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 4]);
                $b = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 4]);

                var_dump($a === $b, password_verify('dune', $b));
                PHP,
            'options' => [
                '`bool(true)`, `bool(true)`',
                '`bool(false)`, `bool(true)`',
                '`bool(false)`, `bool(false)`',
                '`bool(true)`, `bool(false)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Each hash has its own random salt, so they differ, but both verify because the salt is stored inside the hash.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $hash = password_hash('dune', PASSWORD_BCRYPT, ['cost' => 4]);

                echo substr($hash, 0, 7);
                PHP,
            'options' => ['`$2y$04$`', '`bcrypt:`', '`$argon2`', '`dune$04`'],
            'correct_option' => 0,
            'explanation' => 'The hash starts with the algorithm (`2y` is bcrypt) and the cost, so it can be checked and upgraded later.',
        ],
        [
            'question' => 'When should an app upgrade a stored hash to newer settings?',
            'options' => [
                'Never',
                'In a nightly job that rehashes every hash',
                'When the user logs in successfully, since that\'s when you have the plain password',
                'When the user changes their email',
            ],
            'correct_option' => 2,
            'explanation' => 'A hash can\'t be rehashed without the password. `password_needs_rehash()` tells you when to do it at login.',
        ],
        [
            'question' => 'Which password rule follows current guidance (NIST)?',
            'options' => [
                'At least one upper-case letter, one digit and one symbol',
                'Change it every 90 days',
                'A minimum length, and rejecting passwords known from breaches',
                'At most 16 characters',
            ],
            'correct_option' => 2,
            'explanation' => 'Composition rules and forced changes make passwords worse. Length and blocking known passwords help.',
        ],
        [
            'question' => 'How should a password reset token be stored in the database?',
            'options' => [
                'As it is, so it can be emailed again',
                'As a hash, with an expiry time, and deleted once used',
                'Encrypted with the user\'s password',
                'It shouldn\'t be stored',
            ],
            'correct_option' => 1,
            'explanation' => 'A stolen table of working reset links is as bad as a table of passwords.',
        ],
        [
            'question' => 'A login form says "No account with that email" for unknown emails and "Wrong password" otherwise. What\'s the problem?',
            'options' => [
                'None, it is helpful',
                'It tells attackers which emails have accounts, which helps them target guessing and phishing',
                'It is slower',
                'It breaks password managers',
            ],
            'correct_option' => 1,
            'explanation' => 'Use one message for both, like Laravel\'s "These credentials do not match our records."',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Hash and verify',
            'prompt' => 'Write `hashPassword(string $password): string`, returning a hash made with `password_hash()` and `PASSWORD_DEFAULT`, and `checkPassword(string $password, string $hash): bool`, using `password_verify()`. Never store or compare the password itself.',
            'starter_code' => <<<'PHP'
                <?php

                function hashPassword(string $password): string
                {
                    return md5($password);
                }

                function checkPassword(string $password, string $hash): bool
                {
                    return md5($password) === $hash;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function hashPassword(string $password): string
                {
                    return password_hash($password, PASSWORD_DEFAULT);
                }

                function checkPassword(string $password, string $hash): bool
                {
                    return password_verify($password, $hash);
                }
                PHP,
            'hint' => '`password_hash($password, PASSWORD_DEFAULT)` and `password_verify($password, $hash)` do all the work.',
            'checks' => [
                ['label' => 'A real password hash', 'code' => "\$info = password_get_info(hashPassword('dune'));\nreturn \$info['algo'] !== null && \$info['algo'] !== 0 ?: 'That is not a password_hash() hash.';"],
                ['label' => 'Hashes are salted', 'code' => "return hashPassword('dune') !== hashPassword('dune') ?: 'The same password gave the same hash twice.';"],
                ['label' => 'Checking works', 'code' => "\$hash = password_hash('correct horse', PASSWORD_DEFAULT, ['cost' => 4]);\nreturn expect_same([checkPassword('correct horse', \$hash), checkPassword('Correct horse', \$hash), checkPassword('', \$hash)], [true, false, false]);"],
            ],
        ],
        [
            'title' => 'Rehash on login',
            'prompt' => 'Write `logIn(string $password, string $storedHash): array`. If the password is wrong, return `[\'ok\' => false, \'hash\' => $storedHash]`. If it\'s right, return `ok` true and the hash to keep: a fresh `password_hash()` with `PASSWORD_DEFAULT` and a cost of 10 if `password_needs_rehash()` says the stored one is out of date (for those same settings), otherwise the stored hash unchanged.',
            'starter_code' => <<<'PHP'
                <?php

                function logIn(string $password, string $storedHash): array
                {
                    return ['ok' => password_verify($password, $storedHash), 'hash' => $storedHash];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function logIn(string $password, string $storedHash): array
                {
                    if (! password_verify($password, $storedHash)) {
                        return ['ok' => false, 'hash' => $storedHash];
                    }

                    $options = ['cost' => 10];
                    $hash = password_needs_rehash($storedHash, PASSWORD_DEFAULT, $options)
                        ? password_hash($password, PASSWORD_DEFAULT, $options)
                        : $storedHash;

                    return ['ok' => true, 'hash' => $hash];
                }
                PHP,
            'hint' => 'Verify first. Then pass the same `[\'cost\' => 10]` options to both `password_needs_rehash()` and `password_hash()`.',
            'checks' => [
                ['label' => 'Wrong passwords keep the old hash', 'code' => "\$old = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 4]);\nreturn expect_same(logIn('arrakis', \$old), ['ok' => false, 'hash' => \$old]);"],
                ['label' => 'Old settings are upgraded', 'code' => "\$old = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 4]);\n\$result = logIn('dune', \$old);\nreturn \$result['ok'] && \$result['hash'] !== \$old && password_get_info(\$result['hash'])['options']['cost'] === 10 && password_verify('dune', \$result['hash']) ?: 'The hash was not upgraded to cost 10.';"],
                ['label' => 'Current hashes are kept', 'code' => "\$current = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 10]);\nreturn expect_same(logIn('dune', \$current), ['ok' => true, 'hash' => \$current]);"],
            ],
        ],
        [
            'title' => 'Leaving md5 behind',
            'prompt' => 'Old accounts have md5 hashes (32 hex characters). Write `logInLegacy(string $password, string $stored): array` returning `ok` and the hash to store. For an md5 hash, compare `md5($password)` with `hash_equals()`, and on success replace it with `password_hash($password, PASSWORD_DEFAULT, [\'cost\' => 10])`. Any other stored value is checked with `password_verify()` and kept as it is.',
            'starter_code' => <<<'PHP'
                <?php

                function logInLegacy(string $password, string $stored): array
                {
                    return ['ok' => md5($password) == $stored, 'hash' => $stored];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function logInLegacy(string $password, string $stored): array
                {
                    if (preg_match('/^[a-f0-9]{32}$/', $stored) === 1) {
                        if (! hash_equals($stored, md5($password))) {
                            return ['ok' => false, 'hash' => $stored];
                        }

                        return ['ok' => true, 'hash' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 10])];
                    }

                    return ['ok' => password_verify($password, $stored), 'hash' => $stored];
                }
                PHP,
            'hint' => 'Recognise md5 hashes with `/^[a-f0-9]{32}$/`. `hash_equals($stored, md5($password))` compares safely.',
            'checks' => [
                ['label' => 'md5 users are upgraded', 'code' => "\$result = logInLegacy('dune', md5('dune'));\nreturn \$result['ok'] && str_starts_with(\$result['hash'], '\$2y\$') && password_verify('dune', \$result['hash']) ?: 'The md5 hash was not replaced with a password hash.';"],
                ['label' => 'Wrong passwords fail and keep the hash', 'code' => "return expect_same(logInLegacy('arrakis', md5('dune')), ['ok' => false, 'hash' => md5('dune')]);"],
                ['label' => 'Modern hashes still work', 'code' => "\$hash = password_hash('dune', PASSWORD_DEFAULT, ['cost' => 4]);\nreturn expect_same([logInLegacy('dune', \$hash), logInLegacy('x', \$hash)['ok']], [['ok' => true, 'hash' => \$hash], false]);"],
                ['label' => 'No loose comparison', 'code' => "// md5('240610708') starts with 0e and looks like 0 to ==.\nreturn expect_same(logInLegacy('QNKCDZO', md5('240610708'))['ok'], false);"],
            ],
        ],
        [
            'title' => 'A sensible policy',
            'prompt' => 'Write `passwordProblems(string $password, string $email, array $breached): array`, returning a list of messages (empty if the password is fine), in this order: `Use at least 12 characters.` if it\'s shorter; `This password has appeared in a data breach.` if it\'s in `$breached` ignoring case; `Don\'t include your email address.` if it contains the part of the email before `@` (ignoring case, and only if that part is at least 3 characters).',
            'starter_code' => <<<'PHP'
                <?php

                function passwordProblems(string $password, string $email, array $breached): array
                {
                    $problems = [];
                    if (! preg_match('/[A-Z]/', $password) || ! preg_match('/[0-9]/', $password)) {
                        $problems[] = 'Use an upper-case letter and a digit.';
                    }

                    return $problems;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function passwordProblems(string $password, string $email, array $breached): array
                {
                    $problems = [];
                    $lower = strtolower($password);

                    if (strlen($password) < 12) {
                        $problems[] = 'Use at least 12 characters.';
                    }

                    if (in_array($lower, array_map('strtolower', $breached), true)) {
                        $problems[] = 'This password has appeared in a data breach.';
                    }

                    $name = strtolower(strstr($email, '@', true) ?: $email);
                    if (strlen($name) >= 3 && str_contains($lower, $name)) {
                        $problems[] = 'Don\'t include your email address.';
                    }

                    return $problems;
                }
                PHP,
            'hint' => 'Compare lower-cased versions. `strstr($email, \'@\', true)` gives the part before the `@`.',
            'checks' => [
                ['label' => 'A good passphrase passes', 'code' => "return expect_same(passwordProblems('correct horse battery staple', 'ada@example.com', ['password']), []);"],
                ['label' => 'No composition rules', 'code' => "return expect_same(passwordProblems('alllowercaseletters', 'ada@example.com', []), []);"],
                ['label' => 'Short and breached', 'code' => "return expect_same(passwordProblems('Password1', 'ada@example.com', ['password1', '123456']), ['Use at least 12 characters.', 'This password has appeared in a data breach.']);"],
                ['label' => 'Email names', 'code' => "return expect_same([passwordProblems('LovelaceAndBabbage', 'lovelace@example.com', []), passwordProblems('averylongpassphrase', 'ab@example.com', [])], [[\"Don't include your email address.\"], []]);"],
            ],
        ],
        [
            'title' => 'Reset tokens',
            'prompt' => 'Write `createResetToken(): array` returning `[\'token\' => ..., \'hash\' => ...]`: a token of 64 hex characters from `random_bytes(32)`, and its SHA-256 hash (hex) for the database. Then write `resetTokenValid(string $token, string $storedHash, int $createdAt, int $now): bool`, true only if the token\'s hash matches (compared with `hash_equals()`) and less than 60 minutes (3600 seconds) have passed.',
            'starter_code' => <<<'PHP'
                <?php

                function createResetToken(): array
                {
                    $token = md5((string) time());

                    return ['token' => $token, 'hash' => $token];
                }

                function resetTokenValid(string $token, string $storedHash, int $createdAt, int $now): bool
                {
                    return $token == $storedHash;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function createResetToken(): array
                {
                    $token = bin2hex(random_bytes(32));

                    return ['token' => $token, 'hash' => hash('sha256', $token)];
                }

                function resetTokenValid(string $token, string $storedHash, int $createdAt, int $now): bool
                {
                    return $now - $createdAt < 3600 && hash_equals($storedHash, hash('sha256', $token));
                }
                PHP,
            'hint' => '`bin2hex(random_bytes(32))` gives 64 hex characters. `hash(\'sha256\', $token)` gives the hash to store and to compare.',
            'checks' => [
                ['label' => 'Tokens are long and random', 'code' => "\$a = createResetToken();\n\$b = createResetToken();\nreturn preg_match('/^[a-f0-9]{64}\$/', \$a['token']) === 1 && \$a['token'] !== \$b['token'] ?: 'Tokens should be 64 random hex characters.';"],
                ['label' => 'Only the hash is stored', 'code' => "\$t = createResetToken();\nreturn expect_same(\$t['hash'], hash('sha256', \$t['token']));"],
                ['label' => 'A fresh token is valid; others are not', 'code' => "\$t = createResetToken();\nreturn expect_same([resetTokenValid(\$t['token'], \$t['hash'], 1000, 1100), resetTokenValid(\$t['hash'], \$t['hash'], 1000, 1100), resetTokenValid('nope', \$t['hash'], 1000, 1100)], [true, false, false]);"],
                ['label' => 'Tokens expire after an hour', 'code' => "\$t = createResetToken();\nreturn expect_same([resetTokenValid(\$t['token'], \$t['hash'], 0, 3599), resetTokenValid(\$t['token'], \$t['hash'], 0, 3600)], [true, false]);"],
            ],
        ],
        [
            'title' => 'Slow down guessing',
            'prompt' => 'Finish `LoginThrottle`, constructed with a maximum number of failed attempts and a window in seconds. `hit(string $key, int $now)` records a failure; `tooManyAttempts(string $key, int $now)` is true when the key has at least the maximum number of failures within the last `$window` seconds (older ones don\'t count); `clear(string $key)` forgets a key after a successful login. Then write `throttleKey(string $email, string $ip): string`, giving `ada@example.com|203.0.113.5` with the email trimmed and lower-cased.',
            'starter_code' => <<<'PHP'
                <?php

                class LoginThrottle
                {
                    public function __construct(private int $maxAttempts, private int $window) {}

                    public function hit(string $key, int $now): void
                    {
                    }

                    public function tooManyAttempts(string $key, int $now): bool
                    {
                        return false;
                    }

                    public function clear(string $key): void
                    {
                    }
                }

                function throttleKey(string $email, string $ip): string
                {
                    return $email;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class LoginThrottle
                {
                    private array $failures = [];

                    public function __construct(private int $maxAttempts, private int $window) {}

                    public function hit(string $key, int $now): void
                    {
                        $this->failures[$key][] = $now;
                    }

                    public function tooManyAttempts(string $key, int $now): bool
                    {
                        $recent = array_filter($this->failures[$key] ?? [], fn (int $time) => $now - $time < $this->window);
                        $this->failures[$key] = array_values($recent);

                        return count($recent) >= $this->maxAttempts;
                    }

                    public function clear(string $key): void
                    {
                        unset($this->failures[$key]);
                    }
                }

                function throttleKey(string $email, string $ip): string
                {
                    return strtolower(trim($email)) . '|' . $ip;
                }
                PHP,
            'hint' => 'Keep a list of failure times per key. When checking, keep only the times where `$now - $time < $window` and count them.',
            'checks' => [
                ['label' => 'Blocked after the maximum', 'code' => "\$t = new LoginThrottle(5, 60);\nfor (\$i = 0; \$i < 4; \$i++) { \$t->hit('k', 100 + \$i); }\nif (\$t->tooManyAttempts('k', 110)) return 'Blocked after only 4 failures.';\n\$t->hit('k', 105);\nreturn expect_same(\$t->tooManyAttempts('k', 110), true);"],
                ['label' => 'Old failures expire', 'code' => "\$t = new LoginThrottle(3, 60);\nforeach ([0, 10, 20] as \$time) { \$t->hit('k', \$time); }\nreturn expect_same([\$t->tooManyAttempts('k', 59), \$t->tooManyAttempts('k', 61)], [true, false]);"],
                ['label' => 'clear() and separate keys', 'code' => "\$t = new LoginThrottle(2, 60);\n\$t->hit('a', 1); \$t->hit('a', 2); \$t->hit('b', 3);\n\$before = [\$t->tooManyAttempts('a', 5), \$t->tooManyAttempts('b', 5)];\n\$t->clear('a');\nreturn expect_same([\$before, \$t->tooManyAttempts('a', 5)], [[true, false], false]);"],
                ['label' => 'Keys combine email and IP', 'code' => "return expect_same([throttleKey(' Ada@Example.com ', '203.0.113.5'), throttleKey('ada@example.com', '198.51.100.7')], ['ada@example.com|203.0.113.5', 'ada@example.com|198.51.100.7']);"],
            ],
        ],
    ],
];
