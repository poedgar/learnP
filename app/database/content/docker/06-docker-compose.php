<?php

// Lesson 6 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Compose files are written as JSON (YAML is a superset of JSON, so Compose reads them),
// because the sandbox can decode JSON but has no YAML parser.

return [
    'slug' => 'docker-compose',
    'title' => 'Docker Compose',
    'summary' => 'Running a whole app with one file: services, `build` and `image`, ports, environment and service names as hosts, named volumes, `depends_on` with health checks, and the everyday `docker compose` commands.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding a redis service.
        $compose = <<<'JSON'
        {
          "services": {
            "app": { "build": ".", "ports": ["8000:8000"], "environment": { "DB_HOST": "db" } },
            "db": { "image": "mysql:8.4", "volumes": ["db-data:/var/lib/mysql"] }
          },
          "volumes": { "db-data": {} }
        }
        JSON;

        $config = json_decode($compose, true);
        foreach ($config['services'] as $name => $service) {
            $source = isset($service['build']) ? 'built from ' . $service['build'] : 'image ' . $service['image'];
            echo "$name: $source" . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does Docker Compose do?',
            'options' => [
                'Builds a single image faster',
                'Describes several containers (services), their networks and volumes in one file, and starts them together',
                'Replaces the Dockerfile',
                'Hosts your app in the cloud',
            ],
            'correct_option' => 1,
            'explanation' => '`docker compose up` does the work of many `docker run`, `docker network create` and `docker volume create` commands.',
        ],
        [
            'question' => 'In a Compose project, how does the `app` service connect to the `db` service?',
            'options' => [
                'Through `localhost`',
                'By the host name `db`, on the network Compose creates',
                'Only through published ports',
                'By IP address, which you set in the file',
            ],
            'correct_option' => 1,
            'explanation' => 'Service names are host names on the project network.',
        ],
        [
            'question' => 'What is the difference between `build: .` and `image: mysql:8.4`?',
            'options' => [
                'None',
                '`build` makes an image from the Dockerfile in that folder; `image` uses an existing image',
                '`image` builds faster',
                '`build` is only for production',
            ],
            'correct_option' => 1,
            'explanation' => 'Your own app is usually built; databases and caches come from ready-made images.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $config = json_decode('{"services": {"db": {"volumes": ["db-data:/var/lib/mysql", "./backups:/backups"]}}, "volumes": {}}', true);

                foreach ($config['services']['db']['volumes'] as $volume) {
                    $source = explode(':', $volume)[0];
                    $named = ! str_starts_with($source, '.') && ! str_starts_with($source, '/');
                    $declared = array_key_exists($source, $config['volumes']);
                    echo "$source: " . ($named ? ($declared ? 'ok' : 'needs declaring') : 'bind mount') . PHP_EOL;
                }
                PHP,
            'options' => [
                '`db-data: ok`, `./backups: ok`',
                '`db-data: needs declaring`, `./backups: bind mount`',
                '`db-data: bind mount`, `./backups: needs declaring`',
                '`db-data: needs declaring`, `./backups: needs declaring`',
            ],
            'correct_option' => 1,
            'explanation' => 'Named volumes must appear under the top-level `volumes` key; bind mounts don\'t.',
        ],
        [
            'question' => 'With just `depends_on: [db]`, what does Compose wait for before starting `app`?',
            'options' => [
                'Until the database accepts connections',
                'Only until the `db` container has started, which isn\'t the same as ready',
                'Nothing',
                'Until `db` passes its health check',
            ],
            'correct_option' => 1,
            'explanation' => 'Add a `healthcheck` to `db` and `condition: service_healthy` to wait until it\'s really ready.',
        ],
        [
            'question' => 'Which command runs the migrations inside the running `app` service?',
            'options' => [
                '`docker compose run php artisan migrate`',
                '`docker compose exec app php artisan migrate`',
                '`docker exec php artisan migrate`',
                '`docker compose up migrate`',
            ],
            'correct_option' => 1,
            'explanation' => '`exec` runs a command in a service that\'s already running.',
        ],
        [
            'question' => 'What does `docker compose down -v` do that `docker compose down` doesn\'t?',
            'options' => [
                'Shows more output',
                'Also removes the named volumes, which deletes the data in them',
                'Stops only the volumes',
                'Nothing',
            ],
            'correct_option' => 1,
            'explanation' => 'Handy for a clean slate in development, and dangerous anywhere data matters.',
        ],
        [
            'question' => 'Why can a compose file be written in JSON?',
            'options' => [
                'Compose converts it',
                'YAML is a superset of JSON, so valid JSON is valid YAML',
                'Only Compose v1 supported it',
                'It can\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s why the challenges here can use JSON, which PHP decodes with `json_decode()`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Two services',
            'prompt' => 'Write a compose file in `$compose` (as JSON) with two services: `app`, built from `.`, publishing `8000:8000`, with the environment variable `DB_HOST` set to `db`; and `db`, from the image `mysql:8.4`, with `MYSQL_DATABASE` set to `shop` and a `MYSQL_ROOT_PASSWORD` of your choice. Write `environment` as an object.',
            'starter_code' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "app": { "build": "." }
                  }
                }
                JSON;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "app": {
                      "build": ".",
                      "ports": ["8000:8000"],
                      "environment": { "DB_HOST": "db" }
                    },
                    "db": {
                      "image": "mysql:8.4",
                      "environment": { "MYSQL_DATABASE": "shop", "MYSQL_ROOT_PASSWORD": "change-me" }
                    }
                  }
                }
                JSON;
                PHP,
            'hint' => 'Each service is an object under `services`. Ports are a list of strings; environment is an object of names to values.',
            'checks' => [
                ['label' => 'Valid JSON', 'code' => "return is_array(json_decode(\$GLOBALS['compose'] ?? '', true)) ?: 'The compose file is not valid JSON.';"],
                ['label' => 'The app is built and published', 'code' => "\$app = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['app'] ?? [];\nreturn ((\$app['build'] ?? null) === '.' && in_array('8000:8000', \$app['ports'] ?? [], true)) ?: 'app needs build: . and ports 8000:8000.';"],
                ['label' => 'The app finds the database by name', 'code' => "\$app = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['app'] ?? [];\nreturn expect_same(\$app['environment']['DB_HOST'] ?? null, 'db');"],
                ['label' => 'The database', 'code' => "\$db = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['db'] ?? [];\nreturn ((\$db['image'] ?? null) === 'mysql:8.4' && (\$db['environment']['MYSQL_DATABASE'] ?? null) === 'shop' && (\$db['environment']['MYSQL_ROOT_PASSWORD'] ?? '') !== '') ?: 'db needs image mysql:8.4, MYSQL_DATABASE shop and a MYSQL_ROOT_PASSWORD.';"],
            ],
        ],
        [
            'title' => 'Keep the data',
            'prompt' => 'Give the database a named volume: mount `db-data` at `/var/lib/mysql` in the `db` service, and declare `db-data` under the top-level `volumes` key (as an empty object). Keep everything else.',
            'starter_code' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "db": {
                      "image": "mysql:8.4",
                      "environment": { "MYSQL_ROOT_PASSWORD": "change-me" }
                    }
                  }
                }
                JSON;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "db": {
                      "image": "mysql:8.4",
                      "environment": { "MYSQL_ROOT_PASSWORD": "change-me" },
                      "volumes": ["db-data:/var/lib/mysql"]
                    }
                  },
                  "volumes": {
                    "db-data": {}
                  }
                }
                JSON;
                PHP,
            'hint' => 'Add `"volumes": ["db-data:/var/lib/mysql"]` to the service and `"volumes": { "db-data": {} }` at the top level.',
            'checks' => [
                ['label' => 'Mounted in db', 'code' => "\$db = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['db'] ?? [];\nreturn in_array('db-data:/var/lib/mysql', \$db['volumes'] ?? [], true) ?: 'Mount db-data:/var/lib/mysql in db.';"],
                ['label' => 'Declared at the top level', 'code' => "\$config = json_decode(\$GLOBALS['compose'] ?? '', true) ?? [];\nreturn array_key_exists('db-data', \$config['volumes'] ?? []) ?: 'Declare db-data under the top-level volumes.';"],
                ['label' => 'The rest is kept', 'code' => "\$db = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['db'] ?? [];\nreturn expect_same([\$db['image'] ?? null, \$db['environment'] ?? null], ['mysql:8.4', ['MYSQL_ROOT_PASSWORD' => 'change-me']]);"],
            ],
        ],
        [
            'title' => 'Wait until it\'s ready',
            'prompt' => 'Make `app` wait until the database really accepts connections. Give `db` a `healthcheck` whose `test` is `["CMD", "mysqladmin", "ping", "-h", "localhost"]`, with an `interval` of `5s` and `10` `retries`. Then make `app` depend on `db` with the condition `service_healthy` (write `depends_on` as an object).',
            'starter_code' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "app": { "build": ".", "depends_on": ["db"] },
                    "db": { "image": "mysql:8.4" }
                  }
                }
                JSON;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $compose = <<<'JSON'
                {
                  "services": {
                    "app": {
                      "build": ".",
                      "depends_on": { "db": { "condition": "service_healthy" } }
                    },
                    "db": {
                      "image": "mysql:8.4",
                      "healthcheck": {
                        "test": ["CMD", "mysqladmin", "ping", "-h", "localhost"],
                        "interval": "5s",
                        "retries": 10
                      }
                    }
                  }
                }
                JSON;
                PHP,
            'hint' => '`depends_on` becomes `{ "db": { "condition": "service_healthy" } }`; `healthcheck` is an object with `test`, `interval` and `retries`.',
            'checks' => [
                ['label' => 'db has a health check', 'code' => "\$h = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['db']['healthcheck'] ?? [];\nreturn expect_same([\$h['test'] ?? null, \$h['interval'] ?? null, \$h['retries'] ?? null], [['CMD', 'mysqladmin', 'ping', '-h', 'localhost'], '5s', 10]);"],
                ['label' => 'app waits for it to be healthy', 'code' => "\$app = json_decode(\$GLOBALS['compose'] ?? '', true)['services']['app'] ?? [];\nreturn expect_same(\$app['depends_on'] ?? null, ['db' => ['condition' => 'service_healthy']]);"],
            ],
        ],
        [
            'title' => 'The everyday commands',
            'prompt' => 'Fill `$commands` with five Compose commands, in order: start everything in the background; follow the output of the `app` service; run `php artisan migrate` in the running `app` service; stop and remove everything; stop and remove everything **including** the named volumes.',
            'starter_code' => <<<'PHP'
                <?php

                $commands = [
                    'docker compose up',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $commands = [
                    'docker compose up -d',
                    'docker compose logs -f app',
                    'docker compose exec app php artisan migrate',
                    'docker compose down',
                    'docker compose down -v',
                ];
                PHP,
            'hint' => '`up -d`, `logs -f app`, `exec app ...`, `down`, and `down -v`.',
            'checks' => [
                ['label' => 'Five commands', 'code' => "return expect_same(count(\$GLOBALS['commands'] ?? []), 5);"],
                ['label' => 'Start in the background', 'code' => "\$w = learnp_shell_words(\$GLOBALS['commands'][0] ?? '');\nreturn (array_slice(\$w, 0, 3) === ['docker', 'compose', 'up'] && array_intersect(['-d', '--detach'], \$w)) ?: 'Use docker compose up -d.';"],
                ['label' => 'Follow the logs of app', 'code' => "\$w = learnp_shell_words(\$GLOBALS['commands'][1] ?? '');\nreturn (array_slice(\$w, 0, 3) === ['docker', 'compose', 'logs'] && array_intersect(['-f', '--follow'], \$w) && end(\$w) === 'app') ?: 'Use docker compose logs -f app.';"],
                ['label' => 'Migrate inside app', 'code' => "return expect_same(learnp_shell_words(\$GLOBALS['commands'][2] ?? ''), ['docker', 'compose', 'exec', 'app', 'php', 'artisan', 'migrate']);"],
                ['label' => 'down, then down with volumes', 'code' => "\$down = learnp_shell_words(\$GLOBALS['commands'][3] ?? '');\n\$all = learnp_shell_words(\$GLOBALS['commands'][4] ?? '');\nreturn (\$down === ['docker', 'compose', 'down'] && array_slice(\$all, 0, 3) === ['docker', 'compose', 'down'] && array_intersect(['-v', '--volumes'], \$all)) ?: 'Use docker compose down, then docker compose down -v.';"],
            ],
        ],
        [
            'title' => 'A development override',
            'prompt' => 'Write `$override`, a `compose.override` file (as JSON) for development, which Compose merges into the main file. For `app`, bind-mount `.` to `/var/www` so code changes show up immediately. For `db`, publish `3306:3306` so you can connect with a database tool. Add a `mailpit` service from the image `axllent/mailpit:v1.20` publishing `8025:8025`.',
            'starter_code' => <<<'PHP'
                <?php

                $override = <<<'JSON'
                {
                  "services": {}
                }
                JSON;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $override = <<<'JSON'
                {
                  "services": {
                    "app": { "volumes": [".:/var/www"] },
                    "db": { "ports": ["3306:3306"] },
                    "mailpit": { "image": "axllent/mailpit:v1.20", "ports": ["8025:8025"] }
                  }
                }
                JSON;
                PHP,
            'hint' => 'An override only lists what it adds: `app` gets `volumes`, `db` gets `ports`, and `mailpit` is a new service.',
            'checks' => [
                ['label' => 'The code is mounted', 'code' => "\$app = json_decode(\$GLOBALS['override'] ?? '', true)['services']['app'] ?? [];\nreturn (in_array('.:/var/www', \$app['volumes'] ?? [], true) || in_array('./:/var/www', \$app['volumes'] ?? [], true)) ?: 'Bind-mount .:/var/www in app.';"],
                ['label' => 'The database port is published', 'code' => "\$db = json_decode(\$GLOBALS['override'] ?? '', true)['services']['db'] ?? [];\nreturn in_array('3306:3306', \$db['ports'] ?? [], true) ?: 'Publish 3306:3306 for db.';"],
                ['label' => 'A mail catcher', 'code' => "\$mail = json_decode(\$GLOBALS['override'] ?? '', true)['services']['mailpit'] ?? [];\nreturn expect_same([\$mail['image'] ?? null, \$mail['ports'] ?? null], ['axllent/mailpit:v1.20', ['8025:8025']]);"],
            ],
        ],
        [
            'title' => 'Check a compose file',
            'prompt' => 'Write `composeProblems(array $config): array` for a decoded compose file, returning problems in this order, service by service: `NAME needs an image or a build.`; `NAME depends on unknown service OTHER.` (for `depends_on` written as a list or an object); and `Volume VOLUME is not declared.` for each named volume a service mounts that isn\'t under the top-level `volumes` (bind mounts start with `.`, `/` or `~`; mounts with a single path are anonymous and fine).',
            'starter_code' => <<<'PHP'
                <?php

                function composeProblems(array $config): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function composeProblems(array $config): array
                {
                    $services = $config['services'] ?? [];
                    $declared = array_keys($config['volumes'] ?? []);
                    $problems = [];

                    foreach ($services as $name => $service) {
                        if (! isset($service['image']) && ! isset($service['build'])) {
                            $problems[] = "$name needs an image or a build.";
                        }

                        $dependsOn = $service['depends_on'] ?? [];
                        $dependencies = array_is_list($dependsOn) ? $dependsOn : array_keys($dependsOn);
                        foreach ($dependencies as $dependency) {
                            if (! array_key_exists($dependency, $services)) {
                                $problems[] = "$name depends on unknown service $dependency.";
                            }
                        }

                        foreach ($service['volumes'] ?? [] as $volume) {
                            $parts = explode(':', $volume);
                            if (count($parts) < 2 || preg_match('#^[./~]#', $parts[0]) === 1) {
                                continue;
                            }
                            if (! in_array($parts[0], $declared, true)) {
                                $problems[] = "Volume {$parts[0]} is not declared.";
                            }
                        }
                    }

                    return $problems;
                }
                PHP,
            'hint' => '`array_is_list()` tells a list `depends_on` from an object one; for an object, the service names are its keys. A volume\'s source is the part before the first `:`.',
            'checks' => [
                ['label' => 'A good file', 'code' => "return expect_same(composeProblems(['services' => ['app' => ['build' => '.', 'depends_on' => ['db' => ['condition' => 'service_healthy']], 'volumes' => ['./src:/var/www']], 'db' => ['image' => 'mysql:8.4', 'volumes' => ['db-data:/var/lib/mysql', '/tmp/cache']]], 'volumes' => ['db-data' => []]]), []);"],
                ['label' => 'Missing image or build', 'code' => "return expect_same(composeProblems(['services' => ['worker' => ['command' => 'php artisan queue:work']]]), ['worker needs an image or a build.']);"],
                ['label' => 'Unknown dependencies', 'code' => "return expect_same(composeProblems(['services' => ['app' => ['build' => '.', 'depends_on' => ['db', 'redis']], 'db' => ['image' => 'mysql:8.4']]]), ['app depends on unknown service redis.']);"],
                ['label' => 'Undeclared volumes', 'code' => "return expect_same(composeProblems(['services' => ['db' => ['image' => 'mysql:8.4', 'volumes' => ['db-data:/var/lib/mysql', '~/backups:/backups']], 'cache' => ['image' => 'redis:7.4', 'volumes' => ['redis-data:/data']]], 'volumes' => ['other' => []]]), ['Volume db-data is not declared.', 'Volume redis-data is not declared.']);"],
            ],
        ],
    ],
];
