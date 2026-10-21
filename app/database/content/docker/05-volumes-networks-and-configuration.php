<?php

// Lesson 5 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'volumes-networks-and-configuration',
    'title' => 'Volumes, networks and configuration',
    'summary' => 'What lives outside a container: named volumes and bind mounts, user-defined networks and container names as hosts, configuration from environment variables and env files, and logging to standard output.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a bind mount like ./src:/app:ro.
        foreach (['shop-data:/var/lib/mysql', './src:/app', '/etc/nginx/conf.d:/etc/nginx/conf.d:ro'] as $volume) {
            $parts = explode(':', $volume);
            $source = $parts[0];
            $kind = str_starts_with($source, '/') || str_starts_with($source, '.') ? 'bind mount' : 'named volume';
            $readOnly = ($parts[2] ?? '') === 'ro' ? ', read-only' : '';
            echo "$source → {$parts[1]} ($kind$readOnly)" . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Where should a database container keep its data?',
            'options' => [
                'In the container\'s own filesystem',
                'In a named volume, which outlives the container',
                'In the image',
                'In an environment variable',
            ],
            'correct_option' => 1,
            'explanation' => 'Containers are replaced all the time; a volume keeps the data from one to the next.',
        ],
        [
            'question' => 'What is the difference between `-v shop-data:/data` and `-v ./src:/app`?',
            'options' => [
                'None',
                'The first is a named volume managed by Docker; the second is a bind mount of a folder from your machine',
                'The first is read-only',
                'The second is faster in production',
            ],
            'correct_option' => 1,
            'explanation' => 'A source that is a path (starting with `/` or `.`) is a bind mount; a plain name is a volume.',
        ],
        [
            'question' => 'Inside the `app` container, what does `localhost` refer to?',
            'options' => ['Your computer', 'The `app` container itself', 'The database container', 'Docker Hub'],
            'correct_option' => 1,
            'explanation' => 'Each container has its own network namespace. To reach the database, use its name, like `db`.',
        ],
        [
            'question' => 'On a user-defined network, how does the `app` container reach the database container named `db`?',
            'options' => [
                'By its IP address, which you look up each time',
                'By the host name `db`, which Docker\'s DNS resolves',
                'Through `localhost:3306`',
                'It can\'t without publishing a port',
            ],
            'correct_option' => 1,
            'explanation' => 'Container names work as host names on the same network, and no port needs publishing.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $env = ['APP_DEBUG' => 'false', 'DB_PORT' => '3306'];

                var_dump((bool) $env['APP_DEBUG'], $env['APP_DEBUG'] === 'true', (int) $env['DB_PORT']);
                PHP,
            'options' => [
                '`bool(false)`, `bool(false)`, `int(3306)`',
                '`bool(true)`, `bool(false)`, `int(3306)`',
                '`bool(true)`, `bool(true)`, `string(4) "3306"`',
                '`bool(false)`, `bool(true)`, `int(3306)`',
            ],
            'correct_option' => 1,
            'explanation' => 'Environment variables are strings, and a non-empty string like `\'false\'` is truthy. Compare the text instead.',
        ],
        [
            'question' => 'Why pass configuration as environment variables instead of building it into the image?',
            'options' => [
                'It\'s faster',
                'So one image runs unchanged in development, staging and production, and secrets stay out of the image',
                'Images can\'t contain files',
                'Docker requires it',
            ],
            'correct_option' => 1,
            'explanation' => 'Build once, configure at run time: that\'s a twelve-factor rule Laravel follows with `env()`.',
        ],
        [
            'question' => 'What does `:ro` at the end of `-v ./config:/etc/app:ro` do?',
            'options' => ['Runs it as root', 'Mounts it read-only inside the container', 'Removes it after use', 'Rotates its logs'],
            'correct_option' => 1,
            'explanation' => 'The container can read the files but not change them.',
        ],
        [
            'question' => 'Where should an app in a container write its logs?',
            'options' => [
                'To a file inside the container',
                'To standard output or standard error, where `docker logs` and log collectors read them',
                'To the image',
                'To a volume only',
            ],
            'correct_option' => 1,
            'explanation' => 'In Laravel, set `LOG_CHANNEL=stderr`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Keep the data',
            'prompt' => 'Fill `$commands` with two commands: create a named volume called `shop-data`, then start `mysql:8.4` in the background, named `db`, with `shop-data` mounted at `/var/lib/mysql` and `MYSQL_ROOT_PASSWORD` set to a password of your choice.',
            'starter_code' => <<<'PHP'
                <?php

                $commands = [
                    'docker run -d --name db mysql:8.4',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $commands = [
                    'docker volume create shop-data',
                    'docker run -d --name db -v shop-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4',
                ];
                PHP,
            'hint' => '`docker volume create shop-data` first; then `-v shop-data:/var/lib/mysql` in the run command.',
            'checks' => [
                ['label' => 'The volume is created', 'code' => "return expect_same(learnp_shell_words(\$GLOBALS['commands'][0] ?? ''), ['docker', 'volume', 'create', 'shop-data']);"],
                ['label' => 'The database uses it', 'code' => "\$run = learnp_docker_run(\$GLOBALS['commands'][1] ?? '');\nreturn in_array('shop-data:/var/lib/mysql', \$run['options']['volume'] ?? [], true) ?: 'Mount it with -v shop-data:/var/lib/mysql.';"],
                ['label' => 'Detached, named db, mysql:8.4', 'code' => "\$run = learnp_docker_run(\$GLOBALS['commands'][1] ?? '');\nreturn (in_array('detach', \$run['flags'] ?? [], true) && (\$run['options']['name'] ?? []) === ['db'] && (\$run['image'] ?? null) === 'mysql:8.4') ?: 'Run mysql:8.4 detached, named db.';"],
                ['label' => 'A root password', 'code' => "\$run = learnp_docker_run(\$GLOBALS['commands'][1] ?? '');\nreturn (bool) preg_grep('/^MYSQL_ROOT_PASSWORD=.+/', \$run['options']['env'] ?? []) ?: 'Set MYSQL_ROOT_PASSWORD with -e.';"],
            ],
        ],
        [
            'title' => 'Live code in development',
            'prompt' => 'Set `$command` to serve the current folder with PHP\'s built-in server, deleting the container afterwards: bind-mount `"$(pwd)"` to `/app`, set the working directory to `/app` with `-w`, publish port `8000` to `8000`, use `php:8.4-cli`, and run `php -S 0.0.0.0:8000`.',
            'starter_code' => <<<'PHP'
                <?php

                $command = 'docker run php:8.4-cli php -S localhost:8000';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $command = 'docker run --rm -v "$(pwd)":/app -w /app -p 8000:8000 php:8.4-cli php -S 0.0.0.0:8000';
                PHP,
            'hint' => 'The server must listen on `0.0.0.0`, not `localhost`, or it would only accept connections from inside the container.',
            'checks' => [
                ['label' => 'The folder is mounted at /app', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('\$(pwd):/app', \$run['options']['volume'] ?? [], true) ?: 'Bind-mount \"\$(pwd)\" to /app with -v.';"],
                ['label' => 'Works in /app, removed afterwards', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn ((\$run['options']['workdir'] ?? []) === ['/app'] && in_array('rm', \$run['flags'] ?? [], true)) ?: 'Add -w /app and --rm.';"],
                ['label' => 'Port 8000', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('8000:8000', \$run['options']['publish'] ?? [], true) ?: 'Publish -p 8000:8000.';"],
                ['label' => 'Listens on every interface', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn expect_same([\$run['image'] ?? null, \$run['args'] ?? []], ['php:8.4-cli', ['php', '-S', '0.0.0.0:8000']]);"],
            ],
        ],
        [
            'title' => 'A private network',
            'prompt' => 'Fill `$commands` with three commands: create a network `shop`; start `mysql:8.4` detached on it, named `db`, with a `MYSQL_ROOT_PASSWORD`, and **no** published ports; start the image `shop:1.0` detached on the same network, named `app`, with `DB_HOST` set to `db` and port `8000` published to `8000`.',
            'starter_code' => <<<'PHP'
                <?php

                $commands = [
                    'docker run -d --name db -p 3306:3306 -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4',
                    'docker run -d --name app -e DB_HOST=localhost -p 8000:8000 shop:1.0',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $commands = [
                    'docker network create shop',
                    'docker run -d --name db --network shop -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4',
                    'docker run -d --name app --network shop -e DB_HOST=db -p 8000:8000 shop:1.0',
                ];
                PHP,
            'hint' => 'Add `--network shop` to both runs. The app reaches the database by its name, `db`; `localhost` would be the app container itself.',
            'checks' => [
                ['label' => 'The network is created', 'code' => "return expect_same(learnp_shell_words(\$GLOBALS['commands'][0] ?? ''), ['docker', 'network', 'create', 'shop']);"],
                ['label' => 'The database is private', 'code' => "\$db = learnp_docker_run(\$GLOBALS['commands'][1] ?? '');\nreturn ((\$db['options']['network'] ?? []) === ['shop'] && (\$db['options']['name'] ?? []) === ['db'] && (\$db['image'] ?? null) === 'mysql:8.4' && empty(\$db['options']['publish']) && preg_grep('/^MYSQL_ROOT_PASSWORD=.+/', \$db['options']['env'] ?? [])) ?: 'Run db on the shop network with a root password and no -p.';"],
                ['label' => 'The app finds the database by name', 'code' => "\$app = learnp_docker_run(\$GLOBALS['commands'][2] ?? '');\nreturn ((\$app['options']['network'] ?? []) === ['shop'] && (\$app['options']['name'] ?? []) === ['app'] && in_array('DB_HOST=db', \$app['options']['env'] ?? [], true)) ?: 'Run app on the shop network with -e DB_HOST=db.';"],
                ['label' => 'The app is published', 'code' => "\$app = learnp_docker_run(\$GLOBALS['commands'][2] ?? '');\nreturn (in_array('8000:8000', \$app['options']['publish'] ?? [], true) && (\$app['image'] ?? null) === 'shop:1.0' && in_array('detach', \$app['flags'] ?? [], true)) ?: 'Run shop:1.0 detached with -p 8000:8000.';"],
            ],
        ],
        [
            'title' => 'Read a -v option',
            'prompt' => 'Write `parseVolume(string $spec): array` for `-v` values like `source:target` or `source:target:ro`, returning `type` (`bind` if the source starts with `/`, `.` or `~`, otherwise `volume`), `source`, `target` and `read_only` (true only for `ro`). A spec with only a target (`/data`) is an anonymous volume: `type` `volume` and `source` `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseVolume(string $spec): array
                {
                    [$source, $target] = explode(':', $spec);

                    return ['type' => 'volume', 'source' => $source, 'target' => $target, 'read_only' => false];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseVolume(string $spec): array
                {
                    $parts = explode(':', $spec);

                    if (count($parts) === 1) {
                        return ['type' => 'volume', 'source' => null, 'target' => $parts[0], 'read_only' => false];
                    }

                    [$source, $target] = $parts;
                    $options = isset($parts[2]) ? explode(',', $parts[2]) : [];

                    return [
                        'type' => preg_match('#^[/.~]#', $source) === 1 ? 'bind' : 'volume',
                        'source' => $source,
                        'target' => $target,
                        'read_only' => in_array('ro', $options, true),
                    ];
                }
                PHP,
            'hint' => 'Split at `:`. One part means an anonymous volume; a third part holds options such as `ro` (there may be several, separated by commas).',
            'checks' => [
                ['label' => 'Named volumes', 'code' => "return expect_same(parseVolume('shop-data:/var/lib/mysql'), ['type' => 'volume', 'source' => 'shop-data', 'target' => '/var/lib/mysql', 'read_only' => false]);"],
                ['label' => 'Bind mounts', 'code' => "return expect_same([parseVolume('./src:/app'), parseVolume('/etc/nginx:/etc/nginx:ro'), parseVolume('~/.ssh:/root/.ssh:ro')], [['type' => 'bind', 'source' => './src', 'target' => '/app', 'read_only' => false], ['type' => 'bind', 'source' => '/etc/nginx', 'target' => '/etc/nginx', 'read_only' => true], ['type' => 'bind', 'source' => '~/.ssh', 'target' => '/root/.ssh', 'read_only' => true]]);"],
                ['label' => 'Anonymous volumes and options', 'code' => "return expect_same([parseVolume('/data'), parseVolume('cache:/tmp/cache:rw')], [['type' => 'volume', 'source' => null, 'target' => '/data', 'read_only' => false], ['type' => 'volume', 'source' => 'cache', 'target' => '/tmp/cache', 'read_only' => false]]);"],
            ],
        ],
        [
            'title' => 'Read an env file',
            'prompt' => 'Write `parseEnvFile(string $contents): array` for `--env-file` files. Skip blank lines and lines starting with `#` (after leading spaces). Each other line is `KEY=value`, split at the first `=`, with spaces around the key trimmed. A value wrapped in matching double or single quotes loses the quotes; otherwise it is trimmed. Lines without `=` are ignored. Later lines win.',
            'starter_code' => <<<'PHP'
                <?php

                function parseEnvFile(string $contents): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseEnvFile(string $contents): array
                {
                    $env = [];

                    foreach (preg_split('/\r?\n/', $contents) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                            continue;
                        }

                        [$key, $value] = explode('=', $line, 2);
                        $value = trim($value);
                        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                            $value = substr($value, 1, -1);
                        }

                        $env[trim($key)] = $value;
                    }

                    return $env;
                }
                PHP,
            'hint' => 'Split each line at the first `=` with `explode(\'=\', $line, 2)`. Only strip quotes when the first and last characters are the same quote.',
            'checks' => [
                ['label' => 'Keys and values', 'code' => "return expect_same(parseEnvFile(\"DB_HOST=db\\nDB_PORT=3306\\n\"), ['DB_HOST' => 'db', 'DB_PORT' => '3306']);"],
                ['label' => 'Comments, blanks and junk', 'code' => "return expect_same(parseEnvFile(\"# Database\\n\\n  # indented comment\\nDB_HOST = db\\nnot a setting\\n\"), ['DB_HOST' => 'db']);"],
                ['label' => 'Quotes and = in values', 'code' => "return expect_same(parseEnvFile(\"APP_NAME=\\\"Corner Books\\\"\\nGREETING='hi there'\\nDSN=mysql:host=db;port=3306\\nODD=\\\"unmatched'\"), ['APP_NAME' => 'Corner Books', 'GREETING' => 'hi there', 'DSN' => 'mysql:host=db;port=3306', 'ODD' => \"\\\"unmatched'\"]);"],
                ['label' => 'Later lines win', 'code' => "return expect_same(parseEnvFile(\"A=1\\nA=2\"), ['A' => '2']);"],
            ],
        ],
        [
            'title' => 'Configuration from the environment',
            'prompt' => 'Write `appConfig(array $env): array`, turning environment variables (all strings) into settings: `db_host` from `DB_HOST` (default `127.0.0.1`), `db_port` as an int from `DB_PORT` (default 3306), and `debug`, true only if `APP_DEBUG` is `true` or `1` (any case). `APP_KEY` is required: if it\'s missing or empty, throw `RuntimeException` with the message `APP_KEY is not set.` Otherwise include it as `key`.',
            'starter_code' => <<<'PHP'
                <?php

                function appConfig(array $env): array
                {
                    return [
                        'db_host' => $env['DB_HOST'],
                        'db_port' => $env['DB_PORT'],
                        'debug' => (bool) $env['APP_DEBUG'],
                        'key' => $env['APP_KEY'],
                    ];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function appConfig(array $env): array
                {
                    if (($env['APP_KEY'] ?? '') === '') {
                        throw new RuntimeException('APP_KEY is not set.');
                    }

                    return [
                        'db_host' => $env['DB_HOST'] ?? '127.0.0.1',
                        'db_port' => (int) ($env['DB_PORT'] ?? 3306),
                        'debug' => in_array(strtolower($env['APP_DEBUG'] ?? ''), ['true', '1'], true),
                        'key' => $env['APP_KEY'],
                    ];
                }
                PHP,
            'hint' => 'Check `APP_KEY` first. Never cast `APP_DEBUG` with `(bool)`: compare its lower-cased text with `true` and `1`.',
            'checks' => [
                ['label' => 'Values are converted', 'code' => "return expect_same(appConfig(['DB_HOST' => 'db', 'DB_PORT' => '3307', 'APP_DEBUG' => 'TRUE', 'APP_KEY' => 'base64:abc']), ['db_host' => 'db', 'db_port' => 3307, 'debug' => true, 'key' => 'base64:abc']);"],
                ['label' => 'Defaults', 'code' => "return expect_same(appConfig(['APP_KEY' => 'k']), ['db_host' => '127.0.0.1', 'db_port' => 3306, 'debug' => false, 'key' => 'k']);"],
                ['label' => '"false" means false', 'code' => "return expect_same([appConfig(['APP_KEY' => 'k', 'APP_DEBUG' => 'false'])['debug'], appConfig(['APP_KEY' => 'k', 'APP_DEBUG' => '0'])['debug'], appConfig(['APP_KEY' => 'k', 'APP_DEBUG' => '1'])['debug']], [false, false, true]);"],
                ['label' => 'A missing key fails loudly', 'code' => "foreach ([[], ['APP_KEY' => '']] as \$env) {\n    try { appConfig(\$env); return 'No exception without APP_KEY.'; } catch (RuntimeException \$e) { if (\$e->getMessage() !== 'APP_KEY is not set.') return 'Wrong message: ' . \$e->getMessage(); }\n}\nreturn true;"],
            ],
        ],
    ],
];
