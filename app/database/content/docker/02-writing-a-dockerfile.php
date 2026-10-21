<?php

// Lesson 2 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'writing-a-dockerfile',
    'title' => 'Writing a Dockerfile',
    'summary' => 'Building images from a Dockerfile: `FROM`, `WORKDIR`, `COPY`, `RUN`, `ENV`, `EXPOSE` and `CMD`, exec form versus shell form, installing packages and extensions, and build arguments.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding an EXPOSE line.
        $dockerfile = <<<'DOCKERFILE'
        FROM php:8.4-cli
        WORKDIR /app
        COPY . .
        CMD ["php", "index.php"]
        DOCKERFILE;

        foreach (explode("\n", $dockerfile) as $number => $line) {
            [$instruction, $args] = explode(' ', $line, 2);
            echo 'Step ' . ($number + 1) . ": $instruction → $args" . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Which instruction must come first in a Dockerfile (apart from `ARG`)?',
            'options' => ['`WORKDIR`', '`FROM`', '`RUN`', '`CMD`'],
            'correct_option' => 1,
            'explanation' => 'Every image starts from a base image named by `FROM`.',
        ],
        [
            'question' => 'What is the difference between `RUN` and `CMD`?',
            'options' => [
                'None',
                '`RUN` executes while the image is being built; `CMD` is the default command when a container starts',
                '`CMD` runs during the build; `RUN` when the container starts',
                '`RUN` is for Linux, `CMD` for Windows',
            ],
            'correct_option' => 1,
            'explanation' => 'Installing packages is a `RUN`; starting your app is the `CMD`.',
        ],
        [
            'question' => 'What does `EXPOSE 8000` do?',
            'options' => [
                'Publishes port 8000 on the host',
                'Documents that the app listens on port 8000; publishing still needs `-p`',
                'Opens the firewall',
                'Starts a web server on 8000',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s information for people and tools; `docker run -p` is what publishes a port.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $forms = ['["php", "artisan", "serve"]', "['php', 'artisan']", 'php artisan serve'];

                foreach ($forms as $args) {
                    echo is_array(json_decode($args, true)) ? 'exec ' : 'shell ';
                }
                PHP,
            'options' => ['`exec exec shell `', '`exec shell shell `', '`shell shell shell `', '`exec exec exec `'],
            'correct_option' => 1,
            'explanation' => 'Single quotes aren\'t valid JSON, so the second line would quietly run through a shell.',
        ],
        [
            'question' => 'Why prefer `CMD ["php", "artisan", "serve"]` over `CMD php artisan serve`?',
            'options' => [
                'It is shorter',
                'In exec form PHP is the main process and receives the stop signal, so it can shut down cleanly',
                'Shell form doesn\'t work',
                'Exec form is faster to build',
            ],
            'correct_option' => 1,
            'explanation' => 'In shell form `/bin/sh` is the main process and doesn\'t forward the signal, so Docker ends up killing the app.',
        ],
        [
            'question' => 'Why put `apt-get update` and `apt-get install` in the same `RUN`?',
            'options' => [
                'It is required syntax',
                'If they were separate, a cached old `update` step could be reused with a new `install` line, installing from a stale package list',
                'It installs faster',
                'To use less memory',
            ],
            'correct_option' => 1,
            'explanation' => 'Keeping them together means the list is always refreshed when the install line changes.',
        ],
        [
            'question' => 'What does `WORKDIR /app` do?',
            'options' => [
                'Copies the app into /app',
                'Sets the folder that later instructions and the container work in, creating it if needed',
                'Mounts a volume at /app',
                'Deletes /app',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s like `cd`, but it lasts for the rest of the Dockerfile and the running container.',
        ],
        [
            'question' => 'What is `ARG` for?',
            'options' => [
                'Secrets such as API keys',
                'Values used only while building, which can be changed with `--build-arg`',
                'Command-line arguments for the app',
                'Environment variables in the running container',
            ],
            'correct_option' => 1,
            'explanation' => 'Build arguments show up in the image history, so never use them for secrets.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Your first Dockerfile',
            'prompt' => 'Write a Dockerfile in `$dockerfile` that starts from `php:8.4-cli`, works in `/srv/shop`, copies the whole build context into it, and by default runs `php public/index.php`, written in exec form.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                WORKDIR /srv/shop
                COPY . .
                CMD ["php", "public/index.php"]
                DOCKERFILE;
                PHP,
            'hint' => 'Four lines: `FROM`, `WORKDIR`, `COPY . .` and `CMD` with a JSON array.',
            'checks' => [
                ['label' => 'From php:8.4-cli', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn expect_same(\$d[0] ?? null, ['instruction' => 'FROM', 'args' => 'php:8.4-cli']);"],
                ['label' => 'Works in /srv/shop', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn in_array(['instruction' => 'WORKDIR', 'args' => '/srv/shop'], \$d, true) ?: 'Add WORKDIR /srv/shop.';"],
                ['label' => 'Copies everything', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn (in_array(['instruction' => 'COPY', 'args' => '. .'], \$d, true) || in_array(['instruction' => 'COPY', 'args' => '. /srv/shop'], \$d, true)) ?: 'Copy the build context with COPY . .';"],
                ['label' => 'Runs php public/index.php in exec form', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$cmd = array_values(array_filter(\$d, fn (\$i) => \$i['instruction'] === 'CMD'));\nreturn expect_same(json_decode(end(\$cmd)['args'] ?? '', true), ['php', 'public/index.php']);"],
            ],
        ],
        [
            'title' => 'Install an extension',
            'prompt' => 'Write a Dockerfile in `$dockerfile` that starts from `php:8.4-fpm` and, in a **single** `RUN`, updates the package list, installs `libzip-dev` with `apt-get install -y --no-install-recommends`, installs the PHP extension `zip` with `docker-php-ext-install`, and removes `/var/lib/apt/lists/*`. Spread it over several lines with `\` if you like.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-fpm
                RUN apt-get update
                RUN apt-get install -y libzip-dev
                RUN docker-php-ext-install zip
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-fpm
                RUN apt-get update \
                    && apt-get install -y --no-install-recommends libzip-dev \
                    && docker-php-ext-install zip \
                    && rm -rf /var/lib/apt/lists/*
                DOCKERFILE;
                PHP,
            'hint' => 'Join the commands with `&&` in one `RUN`, ending each line but the last with `\`.',
            'checks' => [
                ['label' => 'One RUN', 'code' => "\$runs = array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN');\nreturn count(\$runs) === 1 ?: 'Use a single RUN instruction.';"],
                ['label' => 'Update and install together', 'code' => "\$run = implode(' ', array_column(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN'), 'args'));\nreturn (str_contains(\$run, 'apt-get update') && preg_match('/apt-get install -y --no-install-recommends\\s.*libzip-dev/', \$run) === 1) ?: 'Update, then apt-get install -y --no-install-recommends libzip-dev.';"],
                ['label' => 'The zip extension', 'code' => "\$run = implode(' ', array_column(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 'args'));\nreturn preg_match('/docker-php-ext-install\\s+(\\S+\\s+)*zip\\b/', \$run) === 1 ?: 'Install zip with docker-php-ext-install.';"],
                ['label' => 'Package lists removed', 'code' => "\$run = implode(' ', array_column(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 'args'));\nreturn str_contains(\$run, 'rm -rf /var/lib/apt/lists/*') ?: 'Finish with rm -rf /var/lib/apt/lists/*.';"],
                ['label' => 'Still php:8.4-fpm', 'code' => "return expect_same(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '')[0] ?? null, ['instruction' => 'FROM', 'args' => 'php:8.4-fpm']);"],
            ],
        ],
        [
            'title' => 'Fix the CMD',
            'prompt' => 'This Dockerfile\'s `CMD` is in shell form, and its `ENTRYPOINT` looks like an array but uses single quotes. Rewrite both in exec form (valid JSON with double quotes), keeping the same words: the entrypoint `php artisan` and the default command `serve --host=0.0.0.0`.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                WORKDIR /app
                COPY . .
                ENTRYPOINT ['php', 'artisan']
                CMD serve --host=0.0.0.0
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                WORKDIR /app
                COPY . .
                ENTRYPOINT ["php", "artisan"]
                CMD ["serve", "--host=0.0.0.0"]
                DOCKERFILE;
                PHP,
            'hint' => 'Exec form is a JSON array: square brackets, double quotes, commas.',
            'checks' => [
                ['label' => 'ENTRYPOINT in exec form', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$entry = array_values(array_filter(\$d, fn (\$i) => \$i['instruction'] === 'ENTRYPOINT'));\nreturn expect_same(json_decode(\$entry[0]['args'] ?? '', true), ['php', 'artisan']);"],
                ['label' => 'CMD in exec form', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$cmd = array_values(array_filter(\$d, fn (\$i) => \$i['instruction'] === 'CMD'));\nreturn expect_same(json_decode(\$cmd[0]['args'] ?? '', true), ['serve', '--host=0.0.0.0']);"],
                ['label' => 'The rest is unchanged', 'code' => "return expect_same(array_slice(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 0, 3), [['instruction' => 'FROM', 'args' => 'php:8.4-cli'], ['instruction' => 'WORKDIR', 'args' => '/app'], ['instruction' => 'COPY', 'args' => '. .']]);"],
            ],
        ],
        [
            'title' => 'Settings and a port',
            'prompt' => 'Write a Dockerfile in `$dockerfile` for a Laravel app: from `php:8.4-cli`, working in `/var/www`, copying everything, setting the environment variable `APP_ENV` to `production`, documenting port `8000`, and running `php artisan serve --host=0.0.0.0 --port=8000` in exec form.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                WORKDIR /var/www
                COPY . .
                ENV APP_ENV=production
                EXPOSE 8000
                CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
                DOCKERFILE;
                PHP,
            'hint' => '`ENV APP_ENV=production`, `EXPOSE 8000`, and the `CMD` as a JSON array of five words.',
            'checks' => [
                ['label' => 'Works in /var/www and copies the app', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn (in_array(['instruction' => 'WORKDIR', 'args' => '/var/www'], \$d, true) && in_array('COPY', array_column(\$d, 'instruction'), true)) ?: 'Set WORKDIR /var/www and COPY the app.';"],
                ['label' => 'APP_ENV is production', 'code' => "\$env = array_column(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'ENV'), 'args');\nreturn (bool) preg_grep('/(^|\\s)APP_ENV[= ]production(\\s|\$)/', \$env) ?: 'Add ENV APP_ENV=production.';"],
                ['label' => 'Port 8000 is documented', 'code' => "return in_array(['instruction' => 'EXPOSE', 'args' => '8000'], learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), true) ?: 'Add EXPOSE 8000.';"],
                ['label' => 'Serves on 0.0.0.0:8000', 'code' => "\$cmd = array_values(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'CMD'));\nreturn expect_same(json_decode(end(\$cmd)['args'] ?? '', true), ['php', 'artisan', 'serve', '--host=0.0.0.0', '--port=8000']);"],
            ],
        ],
        [
            'title' => 'Choose the PHP version',
            'prompt' => 'Make the PHP version a build argument. Add `ARG PHP_VERSION=8.4` **before** `FROM`, and use it in the `FROM` line so the base image is `php:${PHP_VERSION}-cli`. Keep the rest.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                WORKDIR /app
                COPY . .
                CMD ["php", "index.php"]
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                ARG PHP_VERSION=8.4
                FROM php:${PHP_VERSION}-cli
                WORKDIR /app
                COPY . .
                CMD ["php", "index.php"]
                DOCKERFILE;
                PHP,
            'hint' => 'The `ARG` line goes first, and the `FROM` line uses `${PHP_VERSION}`.',
            'checks' => [
                ['label' => 'ARG comes first, with a default', 'code' => "return expect_same(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '')[0] ?? null, ['instruction' => 'ARG', 'args' => 'PHP_VERSION=8.4']);"],
                ['label' => 'FROM uses it', 'code' => "return expect_same(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '')[1] ?? null, ['instruction' => 'FROM', 'args' => 'php:\${PHP_VERSION}-cli']);"],
                ['label' => 'The rest is kept', 'code' => "return expect_same(array_column(array_slice(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 2), 'instruction'), ['WORKDIR', 'COPY', 'CMD']);"],
            ],
        ],
        [
            'title' => 'A Dockerfile linter',
            'prompt' => 'Write `lintDockerfile(string $dockerfile): array`, returning a list of problems in this order, using the given `instructions()` parser: `The first instruction must be FROM (or ARG).` if the first is something else; `Pin the base image to a version.` for each `FROM` whose image (the first word) has no tag, or the tag `latest` (a digest counts as pinned); `Use the JSON form for CMD.` if a `CMD` isn\'t a JSON array; `Only the last CMD takes effect.` if there is more than one `CMD`.',
            'starter_code' => <<<'PHP'
                <?php

                function instructions(string $dockerfile): array
                {
                    $result = [];
                    foreach (explode("\n", str_replace("\\\n", ' ', $dockerfile)) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        [$instruction, $args] = array_pad(explode(' ', $line, 2), 2, '');
                        $result[] = ['instruction' => strtoupper($instruction), 'args' => trim($args)];
                    }

                    return $result;
                }

                function lintDockerfile(string $dockerfile): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function instructions(string $dockerfile): array
                {
                    $result = [];
                    foreach (explode("\n", str_replace("\\\n", ' ', $dockerfile)) as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        [$instruction, $args] = array_pad(explode(' ', $line, 2), 2, '');
                        $result[] = ['instruction' => strtoupper($instruction), 'args' => trim($args)];
                    }

                    return $result;
                }

                function lintDockerfile(string $dockerfile): array
                {
                    $instructions = instructions($dockerfile);
                    $problems = [];

                    if (! in_array($instructions[0]['instruction'] ?? null, ['FROM', 'ARG'], true)) {
                        $problems[] = 'The first instruction must be FROM (or ARG).';
                    }

                    $cmds = 0;
                    foreach ($instructions as ['instruction' => $instruction, 'args' => $args]) {
                        if ($instruction === 'FROM') {
                            $image = explode(' ', $args)[0];
                            $name = explode('@', $image)[0];
                            $tag = str_contains(basename($name), ':') ? substr($name, strrpos($name, ':') + 1) : null;
                            if (! str_contains($image, '@') && ($tag === null || $tag === 'latest')) {
                                $problems[] = 'Pin the base image to a version.';
                            }
                        }

                        if ($instruction === 'CMD') {
                            $cmds++;
                            if (! is_array(json_decode($args, true))) {
                                $problems[] = 'Use the JSON form for CMD.';
                            }
                        }
                    }

                    if ($cmds > 1) {
                        $problems[] = 'Only the last CMD takes effect.';
                    }

                    return $problems;
                }
                PHP,
            'hint' => 'Loop over `instructions($dockerfile)`. For `FROM`, look at the first word only (there may be `AS name` after it). A tag is a `:` in the last part of the name, after any `/`.',
            'checks' => [
                ['label' => 'A clean Dockerfile', 'code' => 'return expect_same(lintDockerfile("FROM php:8.4-cli\\nWORKDIR /app\\nCOPY . .\\nCMD [\\"php\\", \\"index.php\\"]"), []);'],
                ['label' => 'Unpinned images', 'code' => "return expect_same([lintDockerfile(\"FROM php\\nCMD [\\\"php\\\"]\"), lintDockerfile(\"FROM php:latest AS base\\nCMD [\\\"php\\\"]\"), lintDockerfile(\"FROM localhost:5000/php\\nCMD [\\\"php\\\"]\")], [['Pin the base image to a version.'], ['Pin the base image to a version.'], ['Pin the base image to a version.']]);"],
                ['label' => 'Digests and ARGs are fine', 'code' => "return expect_same([lintDockerfile('FROM php@sha256:' . str_repeat('a', 64)), lintDockerfile(\"ARG V=8.4\\nFROM php:\\\${V}-cli\")], [[], []]);"],
                ['label' => 'CMD problems', 'code' => "return expect_same(lintDockerfile(\"FROM php:8.4-cli\\nCMD php -a\\nCMD [\\\"php\\\", \\\"index.php\\\"]\"), ['Use the JSON form for CMD.', 'Only the last CMD takes effect.']);"],
                ['label' => 'FROM must come first', 'code' => "return expect_same(lintDockerfile(\"WORKDIR /app\\nFROM php:8.4-cli\"), ['The first instruction must be FROM (or ARG).']);"],
            ],
        ],
    ],
];
