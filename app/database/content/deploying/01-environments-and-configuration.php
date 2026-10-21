<?php

// Lesson 1 of the Deploying course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'environments-and-configuration',
    'title' => 'Environments and configuration',
    'summary' => 'Same code, different configuration: environments, the .env file and .env.example, the settings that matter in production, config files and config caching, and keeping secrets secret.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try APP_DEBUG=true or an http:// URL.
        $env = ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://cornerbooks.test'];

        $problems = [];
        if ($env['APP_DEBUG'] === 'true') {
            $problems[] = 'APP_DEBUG must be false';
        }
        if (! str_starts_with($env['APP_URL'], 'https://')) {
            $problems[] = 'APP_URL should use https';
        }

        echo $problems ? implode(PHP_EOL, $problems) : 'Ready for production';
        PHP,

    'quiz' => [
        [
            'question' => 'What should differ between your laptop and production?',
            'options' => [
                'The code',
                'Only the configuration (environment variables)',
                'The PHP files in app/',
                'The routes',
            ],
            'correct_option' => 1,
            'explanation' => 'Then what you tested is exactly what runs.',
        ],
        [
            'question' => 'Why is `.env` not committed to Git?',
            'options' => [
                'Git can\'t store dotfiles',
                'It holds secrets and differs on every machine; `.env.example` is committed instead',
                'It is too big',
                'Laravel deletes it',
            ],
            'correct_option' => 1,
            'explanation' => 'A leaked repository is the most common way secrets leak.',
        ],
        [
            'question' => 'What does `APP_DEBUG=true` do in production?',
            'options' => [
                'Logs more',
                'Shows anyone who triggers an error the stack trace, queries and environment values',
                'Makes the app faster',
                'Nothing in production',
            ],
            'correct_option' => 1,
            'explanation' => 'Always `false` in production.',
        ],
        [
            'question' => 'What happens if you change `APP_KEY` on a live app?',
            'options' => [
                'Nothing',
                'Everyone is logged out, and data encrypted with the old key can no longer be decrypted',
                'Passwords stop working',
                'The database is wiped',
            ],
            'correct_option' => 1,
            'explanation' => 'Passwords are hashed, not encrypted, so they survive; sessions, cookies and encrypted columns don\'t.',
        ],
        [
            'question' => 'After `php artisan config:cache`, what does `env(\'POSTMARK_TOKEN\')` return inside a controller?',
            'options' => ['The token', '`null`, because .env is no longer read', 'An exception', 'The cached token'],
            'correct_option' => 1,
            'explanation' => 'Read settings with `config()`; only config files should call `env()`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $line = 'DB_PASSWORD="s3cret #1" # the main database';

                [$name, $value] = explode('=', $line, 2);
                $value = trim($value);
                $quoted = str_starts_with($value, '"');
                $value = $quoted ? substr($value, 1, strpos($value, '"', 1) - 1) : $value;

                echo "{$name}: {$value}";
                PHP,
            'options' => ['`DB_PASSWORD: s3cret #1`', '`DB_PASSWORD: s3cret`', '`DB_PASSWORD: "s3cret #1" # the main database`', '`DB_PASSWORD: `'],
            'correct_option' => 0,
            'explanation' => 'Inside quotes, `#` is part of the value; the comment comes after the closing quote.',
        ],
        [
            'question' => 'A staging API token is copied into production "to save time". What is the problem?',
            'options' => [
                'None',
                'Each environment should have its own secrets, so a leak or test in one can\'t touch the other',
                'Tokens only work once',
                'Staging tokens are shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'Separate secrets also let you rotate one without breaking the other.',
        ],
        [
            'question' => 'You suspect a database password leaked. What should you do?',
            'options' => [
                'Delete the log file',
                'Rotate it: create a new password, update the environment, and revoke the old one',
                'Wait and see',
                'Change APP_KEY',
            ],
            'correct_option' => 1,
            'explanation' => 'Assume a leaked secret is in someone\'s hands.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Read a .env file',
            'prompt' => 'Write `parseEnv(string $contents): array`. Skip blank lines and lines starting with `#`. Each other line is `NAME=value`, optionally starting with `export `. A value in double or single quotes is the text between the quotes (a `#` inside is kept). An unquoted value ends at ` #` (a space then `#`, starting a comment) and is trimmed. An empty value is `\'\'`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseEnv(string $contents): array
                {
                    $env = [];
                    foreach (explode("\n", $contents) as $line) {
                        [$name, $value] = explode('=', $line);
                        $env[$name] = $value;
                    }

                    return $env;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseEnv(string $contents): array
                {
                    $env = [];
                    foreach (preg_split('/\r?\n/', $contents) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                            continue;
                        }
                        if (str_starts_with($line, 'export ')) {
                            $line = substr($line, 7);
                        }

                        [$name, $value] = explode('=', $line, 2);
                        $value = trim($value);

                        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                            $end = strpos($value, $value[0], 1);
                            $value = substr($value, 1, ($end === false ? strlen($value) : $end) - 1);
                        } else {
                            $comment = strpos($value, ' #');
                            $value = trim($comment === false ? $value : substr($value, 0, $comment));
                        }

                        $env[trim($name)] = $value;
                    }

                    return $env;
                }
                PHP,
            'hint' => 'Split into lines and handle three cases for the value: quoted (find the matching closing quote), unquoted (cut at ` #`), or empty.',
            'checks' => [
                ['label' => 'Plain values and comments', 'code' => <<<'PHP'
                    $env = parseEnv("# Corner Books\nAPP_ENV=production\n\nAPP_DEBUG=false # never true here\nexport DB_PORT=3306\nEMPTY=\n");
                    return expect_same($env, ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'DB_PORT' => '3306', 'EMPTY' => '']);
                    PHP],
                ['label' => 'Quoted values', 'code' => <<<'PHP'
                    $env = parseEnv("APP_NAME=\"Corner Books\"\nDB_PASSWORD='s3cret #1' # main db\nURL=https://x.test/#top");
                    return expect_same($env, ['APP_NAME' => 'Corner Books', 'DB_PASSWORD' => 's3cret #1', 'URL' => 'https://x.test/#top']);
                    PHP],
            ],
        ],
        [
            'title' => 'Reuse other values',
            'prompt' => 'Write `expandEnv(array $env): array`, which replaces each `${NAME}` in the values with the value of `NAME`, going through the variables in order, so a value can use any variable defined (and already expanded) **before** it. A reference to a variable that is unknown or defined later becomes an empty string.',
            'starter_code' => <<<'PHP'
                <?php

                function expandEnv(array $env): array
                {
                    return $env;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function expandEnv(array $env): array
                {
                    $expanded = [];
                    foreach ($env as $name => $value) {
                        $expanded[$name] = preg_replace_callback(
                            '/\$\{([A-Z0-9_]+)\}/i',
                            fn ($match) => $expanded[$match[1]] ?? '',
                            $value,
                        );
                    }

                    return $expanded;
                }
                PHP,
            'hint' => 'Build a new array as you go, and use `preg_replace_callback()` to look each `${NAME}` up in what you have built so far.',
            'checks' => [
                ['label' => 'Earlier values', 'code' => "return expect_same(expandEnv(['APP_NAME' => 'Corner Books', 'MAIL_FROM_NAME' => '\${APP_NAME}', 'APP_URL' => 'https://cornerbooks.test', 'ASSET_URL' => '\${APP_URL}/assets']), ['APP_NAME' => 'Corner Books', 'MAIL_FROM_NAME' => 'Corner Books', 'APP_URL' => 'https://cornerbooks.test', 'ASSET_URL' => 'https://cornerbooks.test/assets']);"],
                ['label' => 'Chains, later and unknown names', 'code' => "return expect_same(expandEnv(['A' => 'x', 'B' => '\${A}y', 'C' => '\${B}z', 'D' => '\${E}-\${NOPE}', 'E' => 'late']), ['A' => 'x', 'B' => 'xy', 'C' => 'xyz', 'D' => '-', 'E' => 'late']);"],
            ],
        ],
        [
            'title' => 'Ready for production?',
            'prompt' => 'Write `productionProblems(array $env): array`, returning these messages, in this order, for the problems found: `APP_ENV must be production` (unless it is exactly `production`); `APP_DEBUG must be false` (if it is `true` or `1`, any case); `APP_KEY is missing or invalid` (it must be `base64:` followed by base64 that decodes to exactly 32 bytes); `APP_URL must use https`; `LOG_LEVEL should not be debug` (any case). Missing variables count as empty.',
            'starter_code' => <<<'PHP'
                <?php

                function productionProblems(array $env): array
                {
                    $problems = [];
                    if ($env['APP_DEBUG'] === 'true') {
                        $problems[] = 'APP_DEBUG must be false';
                    }

                    return $problems;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function productionProblems(array $env): array
                {
                    $get = fn (string $name) => (string) ($env[$name] ?? '');
                    $problems = [];

                    if ($get('APP_ENV') !== 'production') {
                        $problems[] = 'APP_ENV must be production';
                    }
                    if (in_array(strtolower($get('APP_DEBUG')), ['true', '1'], true)) {
                        $problems[] = 'APP_DEBUG must be false';
                    }

                    $key = $get('APP_KEY');
                    $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;
                    if ($decoded === false || strlen($decoded) !== 32) {
                        $problems[] = 'APP_KEY is missing or invalid';
                    }

                    if (! str_starts_with($get('APP_URL'), 'https://')) {
                        $problems[] = 'APP_URL must use https';
                    }
                    if (strtolower($get('LOG_LEVEL')) === 'debug') {
                        $problems[] = 'LOG_LEVEL should not be debug';
                    }

                    return $problems;
                }
                PHP,
            'hint' => 'A small helper `fn ($name) => (string) ($env[$name] ?? \'\')` keeps each check to a line. Decode the key with `base64_decode(..., true)`.',
            'checks' => [
                ['label' => 'A good production config', 'code' => "return expect_same(productionProblems(['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:' . base64_encode(str_repeat('k', 32)), 'APP_URL' => 'https://cornerbooks.test', 'LOG_LEVEL' => 'warning']), []);"],
                ['label' => 'Everything wrong', 'code' => "return expect_same(productionProblems(['APP_ENV' => 'local', 'APP_DEBUG' => 'TRUE', 'APP_KEY' => 'base64:' . base64_encode('short'), 'APP_URL' => 'http://cornerbooks.test', 'LOG_LEVEL' => 'Debug']), ['APP_ENV must be production', 'APP_DEBUG must be false', 'APP_KEY is missing or invalid', 'APP_URL must use https', 'LOG_LEVEL should not be debug']);"],
                ['label' => 'Missing values', 'code' => "return expect_same(productionProblems(['APP_ENV' => 'production', 'APP_DEBUG' => '1']), ['APP_DEBUG must be false', 'APP_KEY is missing or invalid', 'APP_URL must use https']);"],
            ],
        ],
        [
            'title' => 'Hide the secrets',
            'prompt' => 'Write `maskSecrets(array $config): array` for printing configuration safely. Any value whose key ends in `password`, `secret`, `token` or `key` (any case, so `DB_PASSWORD` and `api_key` count) becomes `********`, unless it is `null` or an empty string. Nested arrays are handled the same way, all the way down.',
            'starter_code' => <<<'PHP'
                <?php

                function maskSecrets(array $config): array
                {
                    unset($config['password']);

                    return $config;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function maskSecrets(array $config): array
                {
                    foreach ($config as $key => $value) {
                        if (is_array($value)) {
                            $config[$key] = maskSecrets($value);
                        } elseif (is_string($key) && preg_match('/(password|secret|token|key)$/i', $key) === 1 && $value !== null && $value !== '') {
                            $config[$key] = '********';
                        }
                    }

                    return $config;
                }
                PHP,
            'hint' => 'Recurse into arrays first; otherwise test the key with `/(password|secret|token|key)$/i`.',
            'checks' => [
                ['label' => 'Masks secret-looking keys', 'code' => "return expect_same(maskSecrets(['DB_HOST' => '10.0.1.5', 'DB_PASSWORD' => 'hunter2', 'APP_KEY' => 'base64:abc', 'api_token' => 'xyz', 'keyboard' => 'qwerty']), ['DB_HOST' => '10.0.1.5', 'DB_PASSWORD' => '********', 'APP_KEY' => '********', 'api_token' => '********', 'keyboard' => 'qwerty']);"],
                ['label' => 'Nested, and empty values left alone', 'code' => "return expect_same(maskSecrets(['mail' => ['host' => 'smtp.test', 'password' => null, 'mailers' => ['postmark' => ['token' => 'pm_123']]], 'stripe' => ['secret' => '']]), ['mail' => ['host' => 'smtp.test', 'password' => null, 'mailers' => ['postmark' => ['token' => '********']]], 'stripe' => ['secret' => '']]);"],
            ],
        ],
        [
            'title' => 'Merge the configuration',
            'prompt' => 'Write `mergeConfig(array $base, array $override): array`. Keys in `$override` replace those in `$base`; when both values are associative arrays, merge them the same way, recursively. A list (as `array_is_list()` says) in `$override` replaces the base value entirely rather than being merged.',
            'starter_code' => <<<'PHP'
                <?php

                function mergeConfig(array $base, array $override): array
                {
                    return array_merge($base, $override);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function mergeConfig(array $base, array $override): array
                {
                    foreach ($override as $key => $value) {
                        if (is_array($value) && ! array_is_list($value) && isset($base[$key]) && is_array($base[$key]) && ! array_is_list($base[$key])) {
                            $base[$key] = mergeConfig($base[$key], $value);
                        } else {
                            $base[$key] = $value;
                        }
                    }

                    return $base;
                }
                PHP,
            'hint' => 'Only recurse when both sides are arrays that are not lists; otherwise the override wins.',
            'checks' => [
                ['label' => 'Deep merge', 'code' => "return expect_same(mergeConfig(['app' => ['name' => 'Laravel', 'debug' => true, 'url' => 'http://localhost'], 'cache' => 'file'], ['app' => ['name' => 'Corner Books', 'debug' => false], 'queue' => 'redis']), ['app' => ['name' => 'Corner Books', 'debug' => false, 'url' => 'http://localhost'], 'cache' => 'file', 'queue' => 'redis']);"],
                ['label' => 'Lists are replaced', 'code' => "return expect_same(mergeConfig(['trusted_proxies' => ['10.0.0.1', '10.0.0.2'], 'mail' => ['to' => ['a@test']]], ['trusted_proxies' => ['*'], 'mail' => ['to' => ['b@test']]]), ['trusted_proxies' => ['*'], 'mail' => ['to' => ['b@test']]]);"],
                ['label' => 'A scalar replaces an array', 'code' => "return expect_same(mergeConfig(['logging' => ['level' => 'debug']], ['logging' => 'stderr']), ['logging' => 'stderr']);"],
            ],
        ],
        [
            'title' => 'Write .env.example',
            'prompt' => 'Write `envExample(array $env, array $keep): string`, turning a real environment into the contents of `.env.example`. Every variable gets a line `NAME=value`, in order, but the value is only kept for names in `$keep`; all others are left empty. A kept value containing a space or `#` is wrapped in double quotes. Lines end with `\n`.',
            'starter_code' => <<<'PHP'
                <?php

                function envExample(array $env, array $keep): string
                {
                    $lines = '';
                    foreach ($env as $name => $value) {
                        $lines .= "{$name}={$value}\n";
                    }

                    return $lines;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function envExample(array $env, array $keep): string
                {
                    $lines = '';
                    foreach ($env as $name => $value) {
                        $value = in_array($name, $keep, true) ? (string) $value : '';
                        if (preg_match('/[\s#]/', $value) === 1) {
                            $value = "\"{$value}\"";
                        }
                        $lines .= "{$name}={$value}\n";
                    }

                    return $lines;
                }
                PHP,
            'hint' => 'Blank the value unless the name is in `$keep`, then quote it if it contains a space or `#`.',
            'checks' => [
                ['label' => 'Secrets are left out', 'code' => <<<'PHP'
                    $env = ['APP_NAME' => 'Corner Books', 'APP_ENV' => 'production', 'APP_KEY' => 'base64:abc', 'DB_PASSWORD' => 'hunter2', 'DB_PORT' => '3306', 'MAIL_FROM' => 'shop#1@example.com'];
                    return expect_same(envExample($env, ['APP_NAME', 'APP_ENV', 'DB_PORT', 'MAIL_FROM']), "APP_NAME=\"Corner Books\"\nAPP_ENV=production\nAPP_KEY=\nDB_PASSWORD=\nDB_PORT=3306\nMAIL_FROM=\"shop#1@example.com\"\n");
                    PHP],
                ['label' => 'Nothing kept', 'code' => "return expect_same(envExample(['A' => 'x y', 'B' => ''], []), \"A=\\nB=\\n\");"],
            ],
        ],
    ],
];
