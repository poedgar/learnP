<?php

// Lesson 4 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'small-and-safe-images',
    'title' => 'Small and safe images',
    'summary' => 'Production-ready images: smaller bases, multi-stage builds with `COPY --from`, running as an ordinary user, keeping secrets out with secret mounts, pinning by digest and health checks.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try fixing the problems it finds.
        $dockerfile = [
            'FROM php:latest',
            'ENV DB_PASSWORD=hunter2',
            'COPY . .',
            'CMD ["php-fpm"]',
        ];

        $problems = [];
        foreach ($dockerfile as $line) {
            if (preg_match('/^FROM [^:@\s]+(:latest)?(\s|$)/', $line)) $problems[] = "Unpinned base image: $line";
            if (preg_match('/^(ENV|ARG) \S*(PASSWORD|SECRET|TOKEN|KEY)/i', $line)) $problems[] = "Secret baked in: $line";
        }
        if (! preg_grep('/^USER /', $dockerfile)) $problems[] = 'Runs as root: no USER instruction';

        echo implode(PHP_EOL, $problems ?: ['Looks good!']);
        PHP,

    'quiz' => [
        [
            'question' => 'Why keep build tools like Composer and Node out of the final image?',
            'options' => [
                'They don\'t work in containers',
                'They aren\'t needed to run the app, and every extra program is more to download, patch and possibly exploit',
                'Docker forbids it',
                'They slow down PHP',
            ],
            'correct_option' => 1,
            'explanation' => 'Build tools belong in build stages; the final image only needs what runs the app.',
        ],
        [
            'question' => 'In a multi-stage Dockerfile, which stage becomes the image?',
            'options' => ['The first', 'The last', 'All of them, merged', 'The largest'],
            'correct_option' => 1,
            'explanation' => 'Earlier stages only matter for what the last one copies from them with `COPY --from`.',
        ],
        [
            'question' => 'What does `COPY --from=vendor /app/vendor ./vendor` do?',
            'options' => [
                'Downloads the vendor folder from the internet',
                'Copies `/app/vendor` from the build stage named `vendor` into this stage',
                'Copies from the host\'s vendor folder',
                'Renames the stage',
            ],
            'correct_option' => 1,
            'explanation' => 'Stages are named with `FROM image AS name`.',
        ],
        [
            'question' => 'Why add a `USER` instruction?',
            'options' => [
                'To log in to Docker Hub',
                'So the app doesn\'t run as root, limiting what an attacker can do after breaking into it',
                'It is required by PHP',
                'To set the file owner of the image',
            ],
            'correct_option' => 1,
            'explanation' => 'Install packages as root first, then switch to an ordinary user before the app starts.',
        ],
        [
            'question' => 'A Dockerfile has `ENV STRIPE_SECRET=sk_live_...`. Who can read the key?',
            'options' => [
                'Nobody, images are encrypted',
                'Anyone who can pull the image, for example with `docker inspect` or `docker history`',
                'Only the container\'s root user',
                'Only Docker Hub staff',
            ],
            'correct_option' => 1,
            'explanation' => 'Pass runtime secrets as environment variables when the container starts, not in the image.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $images = ['php:8.4-fpm-alpine', 'php', 'php:latest', 'php@sha256:' . str_repeat('0', 64)];

                foreach ($images as $image) {
                    $pinned = str_contains($image, '@') || (str_contains($image, ':') && ! str_ends_with($image, ':latest'));
                    echo ($pinned ? 'pinned' : 'not pinned') . PHP_EOL;
                }
                PHP,
            'options' => [
                'pinned, not pinned, not pinned, pinned',
                'pinned, not pinned, pinned, pinned',
                'pinned, pinned, not pinned, not pinned',
                'not pinned, not pinned, not pinned, pinned',
            ],
            'correct_option' => 0,
            'explanation' => 'A version tag or a digest pins an image; no tag or `latest` doesn\'t.',
        ],
        [
            'question' => 'How should a build that needs a private Composer token get it?',
            'options' => [
                '`ARG COMPOSER_TOKEN` and `--build-arg`',
                'Copy `auth.json` in, then delete it in the next step',
                'A BuildKit secret mount, which exposes it to one `RUN` without storing it in any layer',
                'Put it in `ENV`',
            ],
            'correct_option' => 2,
            'explanation' => 'Build arguments and deleted files both stay readable in the image.',
        ],
        [
            'question' => 'What does a `HEALTHCHECK` tell Docker?',
            'options' => [
                'How much memory the container may use',
                'How to test that the app actually works, so broken containers can be restarted and traffic held back',
                'Which port to publish',
                'When to rebuild the image',
            ],
            'correct_option' => 1,
            'explanation' => 'A running process isn\'t necessarily a working app; the health check asks the app itself.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Two stages',
            'prompt' => 'Turn this into a multi-stage build. The first stage, named `vendor`, is `FROM composer:2 AS vendor`, works in `/app`, copies `composer.json` and `composer.lock` and runs `composer install --no-dev --no-scripts`. The final stage is `FROM php:8.4-fpm-alpine`, works in `/var/www`, copies the app, then copies `/app/vendor` from the `vendor` stage into `./vendor`.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2
                WORKDIR /var/www
                COPY . .
                RUN composer install --no-dev --no-scripts
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2 AS vendor
                WORKDIR /app
                COPY composer.json composer.lock ./
                RUN composer install --no-dev --no-scripts

                FROM php:8.4-fpm-alpine
                WORKDIR /var/www
                COPY . .
                COPY --from=vendor /app/vendor ./vendor
                DOCKERFILE;
                PHP,
            'hint' => 'Two `FROM` lines. Name the first stage with `AS vendor`, and use `COPY --from=vendor` in the second.',
            'checks' => [
                ['label' => 'Two stages, the first named vendor', 'code' => "\$froms = array_column(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'FROM'), 'args');\nreturn expect_same(array_values(\$froms), ['composer:2 AS vendor', 'php:8.4-fpm-alpine']);"],
                ['label' => 'The vendor stage installs dependencies', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$second = array_keys(array_column(\$d, 'instruction'), 'FROM')[1] ?? count(\$d);\n\$stage = implode(\"\\n\", array_map(fn (\$i) => \$i['instruction'] . ' ' . \$i['args'], array_slice(\$d, 0, \$second)));\nreturn (str_contains(\$stage, 'WORKDIR /app') && preg_match('#COPY composer\\.json composer\\.lock (\\./|\\.|/app/?)\\n#', \$stage . \"\\n\") === 1 && str_contains(\$stage, 'RUN composer install --no-dev --no-scripts')) ?: 'The vendor stage should work in /app, copy the composer files and run composer install.';"],
                ['label' => 'The final stage copies from vendor', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$second = array_keys(array_column(\$d, 'instruction'), 'FROM')[1] ?? count(\$d);\n\$final = array_map(fn (\$i) => \$i['instruction'] . ' ' . \$i['args'], array_slice(\$d, \$second));\nreturn (in_array('WORKDIR /var/www', \$final, true) && in_array('COPY . .', \$final, true) && (in_array('COPY --from=vendor /app/vendor ./vendor', \$final, true) || in_array('COPY --from=vendor /app/vendor /var/www/vendor', \$final, true))) ?: 'The final stage should work in /var/www, COPY . . and COPY --from=vendor /app/vendor ./vendor.';"],
                ['label' => 'No Composer in the final stage', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$second = array_keys(array_column(\$d, 'instruction'), 'FROM')[1] ?? 0;\nreturn ! preg_grep('/composer install/', array_column(array_slice(\$d, \$second), 'args')) ?: 'Install dependencies in the vendor stage only.';"],
            ],
        ],
        [
            'title' => 'An ordinary user',
            'prompt' => 'Make this Alpine-based image run as a user called `app`. After installing packages, add `RUN addgroup -S app && adduser -S app -G app`, copy the code with `--chown=app:app`, and switch to the user with `USER app` before `CMD`. The package installation must still run as root (before `USER`).',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-fpm-alpine
                RUN apk add --no-cache icu-libs
                WORKDIR /var/www
                COPY . .
                CMD ["php-fpm"]
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-fpm-alpine
                RUN apk add --no-cache icu-libs
                RUN addgroup -S app && adduser -S app -G app
                WORKDIR /var/www
                COPY --chown=app:app . .
                USER app
                CMD ["php-fpm"]
                DOCKERFILE;
                PHP,
            'hint' => 'Order: install packages, create the user, copy with `--chown`, `USER app`, then `CMD`.',
            'checks' => [
                ['label' => 'The user is created', 'code' => "\$runs = array_column(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN'), 'args');\nreturn (bool) preg_grep('/addgroup -S app\\s*&&\\s*adduser -S app -G app/', \$runs) ?: 'Create the user with addgroup and adduser.';"],
                ['label' => 'Files belong to app', 'code' => "return in_array(['instruction' => 'COPY', 'args' => '--chown=app:app . .'], learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), true) ?: 'Copy with COPY --chown=app:app . .';"],
                ['label' => 'USER app before CMD, after installing', 'code' => "\$d = array_map(fn (\$i) => \$i['instruction'] . ' ' . \$i['args'], learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''));\n\$user = array_search('USER app', \$d, true);\n\$cmd = array_search('CMD [\"php-fpm\"]', \$d, true);\n\$apk = array_search('RUN apk add --no-cache icu-libs', \$d, true);\nreturn (\$user !== false && \$cmd !== false && \$apk !== false && \$apk < \$user && \$user < \$cmd) ?: 'Install packages first, then USER app, then CMD.';"],
            ],
        ],
        [
            'title' => 'Get the secrets out',
            'prompt' => 'This Dockerfile bakes a Composer token and a database password into the image. Remove the `ARG` and `ENV` lines with secrets, and instead give `composer install` the token through a secret mount: `RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json composer install --no-dev`. The database password will come from the environment at run time, so it simply goes.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2
                ARG COMPOSER_TOKEN=ghp_abc123
                ENV DB_PASSWORD=hunter2
                ENV APP_ENV=production
                WORKDIR /app
                COPY . .
                RUN echo "{\"github-oauth\":{\"github.com\":\"$COMPOSER_TOKEN\"}}" > auth.json && composer install --no-dev
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2
                ENV APP_ENV=production
                WORKDIR /app
                COPY . .
                RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json composer install --no-dev
                DOCKERFILE;
                PHP,
            'hint' => 'Delete the two secret lines and replace the `RUN` with the secret-mount version. Keep `APP_ENV`, which isn\'t a secret.',
            'checks' => [
                ['label' => 'No secrets in ARG or ENV', 'code' => "\$lines = array_map(fn (\$i) => \$i['instruction'] . ' ' . \$i['args'], array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => in_array(\$i['instruction'], ['ARG', 'ENV'], true)));\nreturn ! preg_grep('/(PASSWORD|SECRET|TOKEN|ghp_|hunter2)/i', \$lines) ?: 'Remove the ARG and ENV lines holding secrets.';"],
                ['label' => 'A secret mount for Composer', 'code' => "\$runs = array_column(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN'), 'args');\nreturn in_array('--mount=type=secret,id=composer_auth,target=/root/.composer/auth.json composer install --no-dev', \$runs, true) ?: 'Use RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json composer install --no-dev.';"],
                ['label' => 'No token anywhere', 'code' => "return ! preg_match('/ghp_|hunter2|COMPOSER_TOKEN/', \$GLOBALS['dockerfile'] ?? 'ghp_') ?: 'The token is still in the Dockerfile.';"],
                ['label' => 'APP_ENV stays', 'code' => "return in_array(['instruction' => 'ENV', 'args' => 'APP_ENV=production'], learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), true) ?: 'Keep ENV APP_ENV=production; it is not a secret.';"],
            ],
        ],
        [
            'title' => 'A health check',
            'prompt' => 'Add a `HEALTHCHECK` to this Dockerfile, before `CMD`, that runs every `30s` with a `3s` timeout and `3` retries, and checks the app with `wget -qO- http://localhost:8000/up || exit 1`.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli-alpine
                WORKDIR /app
                COPY . .
                EXPOSE 8000
                CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli-alpine
                WORKDIR /app
                COPY . .
                EXPOSE 8000
                HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
                    CMD wget -qO- http://localhost:8000/up || exit 1
                CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
                DOCKERFILE;
                PHP,
            'hint' => '`HEALTHCHECK` takes its options, then `CMD` and the command to run.',
            'checks' => [
                ['label' => 'A health check before CMD', 'code' => "\$d = array_column(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 'instruction');\n\$health = array_search('HEALTHCHECK', \$d, true);\n\$cmd = array_search('CMD', \$d, true);\nreturn (\$health !== false && \$cmd !== false && \$health < \$cmd) ?: 'Add a HEALTHCHECK before CMD.';"],
                ['label' => 'Timings', 'code' => "\$h = array_values(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'HEALTHCHECK'))[0]['args'] ?? '';\n\$w = learnp_shell_words(\$h);\nreturn (in_array('--interval=30s', \$w, true) && in_array('--timeout=3s', \$w, true) && in_array('--retries=3', \$w, true)) ?: 'Use --interval=30s --timeout=3s --retries=3.';"],
                ['label' => 'Asks the app', 'code' => "\$h = array_values(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'HEALTHCHECK'))[0]['args'] ?? '';\nreturn str_ends_with(\$h, 'CMD wget -qO- http://localhost:8000/up || exit 1') ?: 'The check should be CMD wget -qO- http://localhost:8000/up || exit 1.';"],
            ],
        ],
        [
            'title' => 'Pinned or not?',
            'prompt' => 'Write `isPinned(string $image): bool`. An image is pinned if it has a digest (`@sha256:` followed by 64 hex characters), or a tag other than `latest`. A tag is the part after a `:` in the last path segment, so the `:5000` in `localhost:5000/shop` is a registry port, not a tag.',
            'starter_code' => <<<'PHP'
                <?php

                function isPinned(string $image): bool
                {
                    return str_contains($image, ':');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function isPinned(string $image): bool
                {
                    if (str_contains($image, '@')) {
                        return preg_match('/@sha256:[a-f0-9]{64}$/', $image) === 1;
                    }

                    $last = substr($image, (int) strrpos($image, '/'));
                    $colon = strrpos($last, ':');
                    if ($colon === false) {
                        return false;
                    }

                    $tag = substr($last, $colon + 1);

                    return $tag !== '' && $tag !== 'latest';
                }
                PHP,
            'hint' => 'If there\'s an `@`, the image is pinned only by a valid digest, so check that with a pattern. Otherwise look only at the part after the last `/` for a `:`.',
            'checks' => [
                ['label' => 'Tags pin', 'code' => "return expect_same(array_map('isPinned', ['php:8.4-fpm-alpine', 'ghcr.io/acme/shop:2.1', 'localhost:5000/shop:1.0']), [true, true, true]);"],
                ['label' => 'No tag or latest does not', 'code' => "return expect_same(array_map('isPinned', ['php', 'php:latest', 'localhost:5000/shop', 'php:']), [false, false, false, false]);"],
                ['label' => 'Digests pin', 'code' => "\$d = str_repeat('ab', 32);\nreturn expect_same([isPinned(\"php@sha256:\$d\"), isPinned(\"php:latest@sha256:\$d\"), isPinned('php@sha256:short')], [true, true, false]);"],
            ],
        ],
        [
            'title' => 'A security audit',
            'prompt' => 'Write `auditDockerfile(array $instructions): array` for instructions as `[\'instruction\' => ..., \'args\' => ...]` (upper-case instructions), returning problems in this order: `The image runs as root.` unless the **last** `USER` sets a user other than `root` or `0`; `Secret in NAME.` for each `ENV` or `ARG` whose name contains `PASSWORD`, `SECRET`, `TOKEN` or `KEY` (any case), where NAME is the variable\'s name; and `Use COPY instead of ADD for local files.` for each `ADD` whose source doesn\'t start with `http://` or `https://`.',
            'starter_code' => <<<'PHP'
                <?php

                function auditDockerfile(array $instructions): array
                {
                    return [];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function auditDockerfile(array $instructions): array
                {
                    $problems = [];

                    $users = array_column(array_filter($instructions, fn (array $i) => $i['instruction'] === 'USER'), 'args');
                    $user = end($users);
                    if ($user === false || in_array(trim(explode(':', $user)[0]), ['root', '0'], true)) {
                        $problems[] = 'The image runs as root.';
                    }

                    foreach ($instructions as ['instruction' => $instruction, 'args' => $args]) {
                        if (in_array($instruction, ['ENV', 'ARG'], true)) {
                            $name = preg_split('/[=\s]/', trim($args))[0];
                            if (preg_match('/PASSWORD|SECRET|TOKEN|KEY/i', $name) === 1) {
                                $problems[] = "Secret in $name.";
                            }
                        }
                    }

                    foreach ($instructions as ['instruction' => $instruction, 'args' => $args]) {
                        if ($instruction === 'ADD' && preg_match('#^https?://#', explode(' ', trim($args))[0]) !== 1) {
                            $problems[] = 'Use COPY instead of ADD for local files.';
                        }
                    }

                    return $problems;
                }
                PHP,
            'hint' => 'Find the last `USER` with `array_filter()` and `end()`. A variable\'s name is everything before the first `=` or space.',
            'checks' => [
                ['label' => 'A clean file', 'code' => "return expect_same(auditDockerfile([['instruction' => 'FROM', 'args' => 'php:8.4-fpm-alpine'], ['instruction' => 'ENV', 'args' => 'APP_ENV=production'], ['instruction' => 'USER', 'args' => 'app'], ['instruction' => 'CMD', 'args' => '[\"php-fpm\"]']]), []);"],
                ['label' => 'Root users', 'code' => "return expect_same([auditDockerfile([['instruction' => 'FROM', 'args' => 'php']]), auditDockerfile([['instruction' => 'USER', 'args' => 'app'], ['instruction' => 'USER', 'args' => 'root']]), auditDockerfile([['instruction' => 'USER', 'args' => '0:0']])], [['The image runs as root.'], ['The image runs as root.'], ['The image runs as root.']]);"],
                ['label' => 'Secrets in variables', 'code' => "return expect_same(auditDockerfile([['instruction' => 'USER', 'args' => 'app'], ['instruction' => 'ARG', 'args' => 'NPM_TOKEN'], ['instruction' => 'ENV', 'args' => 'DB_PASSWORD=hunter2'], ['instruction' => 'ENV', 'args' => 'stripe_secret_key sk_live'], ['instruction' => 'ENV', 'args' => 'APP_ENV=production']]), ['Secret in NPM_TOKEN.', 'Secret in DB_PASSWORD.', 'Secret in stripe_secret_key.']);"],
                ['label' => 'ADD for local files', 'code' => "return expect_same(auditDockerfile([['instruction' => 'USER', 'args' => 'app'], ['instruction' => 'ADD', 'args' => 'config.tar.gz /etc/app'], ['instruction' => 'ADD', 'args' => 'https://example.com/tool.tar.gz /opt/']]), ['Use COPY instead of ADD for local files.']);"],
            ],
        ],
    ],
];
