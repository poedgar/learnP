<?php

// Lesson 3 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'layers-and-caching',
    'title' => 'Layers and caching',
    'summary' => 'How images are built from layers, how the build cache decides what to rebuild, ordering instructions so dependencies stay cached, `.dockerignore`, and cleaning up in the same layer.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try changing composer.lock instead.
        $steps = [
            'FROM php:8.4-cli',
            'WORKDIR /app',
            'COPY composer.json composer.lock ./',
            'RUN composer install --no-dev',
            'COPY . .',
        ];
        $changedFile = 'app/Models/Book.php';

        $rebuilding = false;
        foreach ($steps as $step) {
            $copies = str_starts_with($step, 'COPY') && (str_contains($step, ' . ') || str_contains($step, $changedFile));
            $rebuilding = $rebuilding || $copies;
            echo ($rebuilding ? 'rebuild  ' : 'cached   ') . $step . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Which instructions add a filesystem layer to an image?',
            'options' => ['Only `FROM`', '`RUN`, `COPY` and `ADD`', 'Every instruction equally', 'Only `CMD`'],
            'correct_option' => 1,
            'explanation' => 'Those three change files. Instructions like `ENV`, `WORKDIR` and `CMD` only change the image\'s settings.',
        ],
        [
            'question' => 'One build step\'s inputs changed. Which steps does Docker rebuild?',
            'options' => [
                'Only that step',
                'That step and every step after it',
                'Every step',
                'Every step before it',
            ],
            'correct_option' => 1,
            'explanation' => 'Each layer sits on the one before, so everything above a changed layer must be rebuilt.',
        ],
        [
            'question' => 'Why copy `composer.json` and `composer.lock` and run `composer install` before `COPY . .`?',
            'options' => [
                'Composer requires it',
                'So editing your own code doesn\'t invalidate the cached `composer install` layer',
                'It makes the image smaller',
                'To keep the files secret',
            ],
            'correct_option' => 1,
            'explanation' => 'Dependencies change rarely; your code changes all the time. Put the rarely-changing steps first.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $layers = ['base' => 80, 'apt-get install' => 120, 'rm -rf /var/lib/apt/lists/*' => 0];

                echo array_sum($layers) . ' MB';
                PHP,
            'options' => ['`80 MB`', '`200 MB`', '`120 MB`', '`0 MB`'],
            'correct_option' => 1,
            'explanation' => 'Deleting files in a later layer adds a zero-sized layer; it can\'t shrink the earlier one. Clean up in the same `RUN`.',
        ],
        [
            'question' => 'Which file keeps `.git`, `.env` and `node_modules` out of the build context?',
            'options' => ['`.gitignore`', '`.dockerignore`', '`Dockerfile.ignore`', '`.env`'],
            'correct_option' => 1,
            'explanation' => 'It has the same pattern syntax as `.gitignore`, but Docker reads its own file.',
        ],
        [
            'question' => 'Why does a `.dockerignore` file make builds faster?',
            'options' => [
                'It compresses the image',
                'Less is sent and copied, and changes to ignored files (like logs) no longer invalidate `COPY . .`',
                'It skips the `RUN` steps',
                'It doesn\'t; it only hides files',
            ],
            'correct_option' => 1,
            'explanation' => 'A smaller, steadier build context means faster transfers and more cache hits.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $dockerfile = "FROM php:8.4-cli\nRUN apt-get update\nRUN apt-get install -y git\nRUN rm -rf /var/lib/apt/lists/*\nCOPY . .\nCMD [\"php\", \"-a\"]";

                $layers = 0;
                foreach (explode("\n", $dockerfile) as $line) {
                    if (preg_match('/^(RUN|COPY|ADD) /', $line)) {
                        $layers++;
                    }
                }

                echo $layers;
                PHP,
            'options' => ['`2`', '`4`', '`5`', '`6`'],
            'correct_option' => 1,
            'explanation' => 'Three `RUN`s and a `COPY` add four layers on top of the base image. Chaining the `RUN`s would make it two.',
        ],
        [
            'question' => 'When would you build with `--no-cache`?',
            'options' => [
                'Always',
                'When you need a completely fresh build, for example to pick up new security updates from the package manager',
                'To make the build faster',
                'Never; it\'s deprecated',
            ],
            'correct_option' => 1,
            'explanation' => 'A cached `apt-get install` layer keeps old package versions until something invalidates it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Dependencies first',
            'prompt' => 'Reorder this Dockerfile so changing the app\'s code doesn\'t rerun `composer install`: copy `composer.json` and `composer.lock` into the working directory, run `composer install`, and only then copy everything else. Keep every instruction (you\'ll add one `COPY`).',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2
                WORKDIR /app
                COPY . .
                RUN composer install --no-dev --no-scripts
                CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM composer:2
                WORKDIR /app
                COPY composer.json composer.lock ./
                RUN composer install --no-dev --no-scripts
                COPY . .
                CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
                DOCKERFILE;
                PHP,
            'hint' => 'Add `COPY composer.json composer.lock ./` before the `RUN`, and move `COPY . .` after it.',
            'checks' => [
                ['label' => 'Dependency files are copied first', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$i = array_search('RUN', array_column(\$d, 'instruction'), true);\n\$before = array_column(array_slice(\$d, 0, \$i === false ? 0 : \$i), 'args');\nreturn (bool) preg_grep('/^composer\\.json composer\\.lock (\\.\\/|\\.|\\/app\\/?)\$/', \$before) ?: 'Copy composer.json and composer.lock before running composer install.';"],
                ['label' => 'The rest is copied after installing', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$run = array_search('RUN', array_column(\$d, 'instruction'), true);\n\$all = array_search(['instruction' => 'COPY', 'args' => '. .'], \$d, true);\nreturn (\$run !== false && \$all !== false && \$all > \$run) ?: 'COPY . . must come after RUN composer install.';"],
                ['label' => 'Nothing else changed', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn expect_same([\$d[0] ?? null, \$d[1] ?? null, end(\$d), count(\$d)], [['instruction' => 'FROM', 'args' => 'composer:2'], ['instruction' => 'WORKDIR', 'args' => '/app'], ['instruction' => 'CMD', 'args' => '[\"php\", \"artisan\", \"serve\", \"--host=0.0.0.0\"]'], 6]);"],
            ],
        ],
        [
            'title' => 'A .dockerignore',
            'prompt' => 'Write a `.dockerignore` in `$dockerignore`, one pattern per line, that keeps out the Git folder (`.git`), the environment file (`.env`), installed dependencies (`vendor` and `node_modules`) and Laravel\'s logs (`storage/logs`). Lines starting with `#` are comments.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerignore = <<<'IGNORE'
                # Keep these out of the image
                IGNORE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerignore = <<<'IGNORE'
                # Keep these out of the image
                .git
                .env
                vendor
                node_modules
                storage/logs
                IGNORE;
                PHP,
            'hint' => 'One path per line: `.git`, `.env`, `vendor`, `node_modules`, `storage/logs`.',
            'checks' => [
                ['label' => 'Every path is ignored', 'code' => "\$lines = array_filter(array_map(fn (\$l) => rtrim(trim(\$l), '/'), explode(\"\\n\", \$GLOBALS['dockerignore'] ?? '')), fn (\$l) => \$l !== '' && \$l[0] !== '#');\n\$lines = array_map(fn (\$l) => preg_replace('#/\\*+\$#', '', ltrim(\$l, '/')), \$lines);\n\$missing = array_diff(['.git', '.env', 'vendor', 'node_modules', 'storage/logs'], \$lines);\nreturn \$missing === [] ?: 'Missing: ' . implode(', ', \$missing);"],
                ['label' => 'Not too much', 'code' => "\$lines = array_filter(array_map('trim', explode(\"\\n\", \$GLOBALS['dockerignore'] ?? '')), fn (\$l) => \$l !== '' && \$l[0] !== '#');\nreturn ! array_intersect(\$lines, ['*', '.', '**', 'app', 'composer.json', 'composer.lock']) ?: 'That would leave out files the build needs.';"],
            ],
        ],
        [
            'title' => 'What gets rebuilt?',
            'prompt' => 'Write `firstRebuiltStep(array $steps, array $changedFiles): ?int`. Each step is an instruction line like `COPY composer.json composer.lock ./`. Return the index of the first step a change to any of the files would rebuild, or `null` if none: a `COPY` (or `ADD`) whose sources (every word but the last, ignoring options starting with `--`) include `.` copies every file; otherwise it copies exactly the files named.',
            'starter_code' => <<<'PHP'
                <?php

                function firstRebuiltStep(array $steps, array $changedFiles): ?int
                {
                    return null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function firstRebuiltStep(array $steps, array $changedFiles): ?int
                {
                    foreach ($steps as $index => $step) {
                        $words = preg_split('/\s+/', trim($step));
                        if (! in_array(strtoupper($words[0]), ['COPY', 'ADD'], true)) {
                            continue;
                        }

                        $sources = array_filter(array_slice($words, 1, -1), fn (string $word) => ! str_starts_with($word, '--'));
                        if (in_array('.', $sources, true) || array_intersect($sources, $changedFiles) !== []) {
                            return $index;
                        }
                    }

                    return null;
                }
                PHP,
            'hint' => 'Split each step into words. The sources are `array_slice($words, 1, -1)` without the `--` options; `.` means everything.',
            'checks' => [
                ['label' => 'Code changes rebuild from COPY . .', 'code' => "\$steps = ['FROM php:8.4-cli', 'COPY composer.json composer.lock ./', 'RUN composer install', 'COPY . .', 'RUN php artisan view:cache'];\nreturn expect_same(firstRebuiltStep(\$steps, ['app/Models/Book.php']), 3);"],
                ['label' => 'Lock file changes rebuild earlier', 'code' => "\$steps = ['FROM php:8.4-cli', 'COPY composer.json composer.lock ./', 'RUN composer install', 'COPY . .'];\nreturn expect_same(firstRebuiltStep(\$steps, ['README.md', 'composer.lock']), 1);"],
                ['label' => 'Options are not sources', 'code' => "\$steps = ['FROM node:22', 'COPY --chown=node:node package.json ./', 'RUN npm ci'];\nreturn expect_same([firstRebuiltStep(\$steps, ['package.json']), firstRebuiltStep(\$steps, ['--chown=node:node'])], [1, null]);"],
                ['label' => 'Nothing copied, nothing rebuilt', 'code' => "return expect_same(firstRebuiltStep(['FROM php:8.4-cli', 'RUN echo hi'], ['index.php']), null);"],
            ],
        ],
        [
            'title' => 'Clean up in the same layer',
            'prompt' => 'This Dockerfile installs packages in one `RUN` and deletes the package lists in another, so they stay in the image. Merge them into a **single** `RUN` that updates, installs `git` and `unzip` with `--no-install-recommends`, and removes `/var/lib/apt/lists/*`.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                RUN apt-get update
                RUN apt-get install -y --no-install-recommends git unzip
                RUN rm -rf /var/lib/apt/lists/*
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM php:8.4-cli
                RUN apt-get update \
                    && apt-get install -y --no-install-recommends git unzip \
                    && rm -rf /var/lib/apt/lists/*
                DOCKERFILE;
                PHP,
            'hint' => 'Chain the three commands with `&&` in one `RUN`.',
            'checks' => [
                ['label' => 'One RUN', 'code' => "\$runs = array_values(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN'));\nreturn count(\$runs) === 1 ?: 'Use one RUN.';"],
                ['label' => 'Update, install and clean in order', 'code' => "\$run = array_values(array_filter(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), fn (\$i) => \$i['instruction'] === 'RUN'))[0]['args'] ?? '';\nreturn preg_match('#apt-get update\\s*&&\\s*apt-get install -y --no-install-recommends git unzip\\s*&&\\s*rm -rf /var/lib/apt/lists/\\*#', \$run) === 1 ?: 'Chain update, install and rm with &&.';"],
            ],
        ],
        [
            'title' => 'Count the layers',
            'prompt' => 'Write `countLayers(string $dockerfile): int`, counting the instructions that add a filesystem layer: `RUN`, `COPY` and `ADD` (in any case). Comments and blank lines don\'t count, and a line ending in `\` continues on the next line, so it is still one instruction.',
            'starter_code' => <<<'PHP'
                <?php

                function countLayers(string $dockerfile): int
                {
                    return count(explode("\n", $dockerfile));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function countLayers(string $dockerfile): int
                {
                    $joined = preg_replace('/\\\\\r?\n/', ' ', $dockerfile);
                    $layers = 0;

                    foreach (explode("\n", $joined) as $line) {
                        if (preg_match('/^\s*(RUN|COPY|ADD)\s/i', $line) === 1) {
                            $layers++;
                        }
                    }

                    return $layers;
                }
                PHP,
            'hint' => 'Join continued lines first by replacing a backslash followed by a newline with a space. Then count lines starting with one of the three instructions.',
            'checks' => [
                ['label' => 'RUN, COPY and ADD count', 'code' => 'return expect_same(countLayers("FROM php:8.4-cli\\nWORKDIR /app\\nCOPY . .\\nRUN composer install\\nADD config.tar.gz /etc/app\\nCMD [\\"php\\", \\"-a\\"]"), 3);'],
                ['label' => 'Continued lines are one instruction', 'code' => 'return expect_same(countLayers("FROM php:8.4-cli\\nRUN apt-get update \\\\\\n    && apt-get install -y git \\\\\\n    && rm -rf /var/lib/apt/lists/*"), 1);'],
                ['label' => 'Comments and case', 'code' => 'return expect_same(countLayers("# RUN this is a comment\\nfrom php:8.4-cli\\nrun echo hi\\n\\ncopy . ."), 2);'],
            ],
        ],
        [
            'title' => 'A front-end build',
            'prompt' => 'Write a Dockerfile in `$dockerfile` that builds the app\'s front end with Node: from `node:22-alpine`, working in `/app`, copying `package.json` and `package-lock.json` first, installing with `npm ci` (which installs exactly what the lock file says), then copying everything and running `npm run build`.',
            'starter_code' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM node
                COPY . .
                RUN npm install
                RUN npm run build
                DOCKERFILE;
                PHP,
            'solution' => <<<'PHP'
                <?php

                $dockerfile = <<<'DOCKERFILE'
                FROM node:22-alpine
                WORKDIR /app
                COPY package.json package-lock.json ./
                RUN npm ci
                COPY . .
                RUN npm run build
                DOCKERFILE;
                PHP,
            'hint' => 'Same pattern as Composer: copy the two package files, `RUN npm ci`, then `COPY . .` and the build.',
            'checks' => [
                ['label' => 'node:22-alpine in /app', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn expect_same(array_slice(\$d, 0, 2), [['instruction' => 'FROM', 'args' => 'node:22-alpine'], ['instruction' => 'WORKDIR', 'args' => '/app']]);"],
                ['label' => 'Lock file, npm ci, then the rest', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\n\$args = array_map(fn (\$i) => \$i['instruction'] . ' ' . \$i['args'], \$d);\n\$pkg = array_search(true, array_map(fn (\$a) => preg_match('#^COPY package\\.json package-lock\\.json (\\./|\\.|/app/?)\$#', \$a) === 1, \$args), true);\n\$ci = array_search('RUN npm ci', \$args, true);\n\$all = array_search('COPY . .', \$args, true);\nreturn (\$pkg !== false && \$ci !== false && \$all !== false && \$pkg < \$ci && \$ci < \$all) ?: 'Copy the package files, run npm ci, then COPY . .';"],
                ['label' => 'Builds last', 'code' => "\$d = learnp_dockerfile(\$GLOBALS['dockerfile'] ?? '');\nreturn expect_same(end(\$d), ['instruction' => 'RUN', 'args' => 'npm run build']);"],
                ['label' => 'No npm install', 'code' => "return ! str_contains(implode(' ', array_column(learnp_dockerfile(\$GLOBALS['dockerfile'] ?? ''), 'args')), 'npm install') ?: 'Use npm ci, which follows the lock file exactly.';"],
            ],
        ],
    ],
];
