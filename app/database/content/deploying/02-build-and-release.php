<?php

// Lesson 2 of the Deploying course. The theory is in the .md file with the same name.

return [
    'slug' => 'build-and-release',
    'title' => 'Building and releasing',
    'summary' => 'The deploy steps and why each is there, versioned assets, zero-downtime releases with a symlink switch and shared storage, rollbacks, migrations that don\'t break running code, and ordering steps.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Keep three releases instead of two.
        $releases = ['20261019-120000', '20261020-170500', '20261021-093000', '20261018-080000'];
        $keep = 2;

        rsort($releases);
        $kept = array_slice($releases, 0, $keep);
        $deleted = array_slice($releases, $keep);

        echo 'keep: ' . implode(', ', $kept) . PHP_EOL . 'delete: ' . implode(', ', $deleted);
        PHP,

    'quiz' => [
        [
            'question' => 'Why `composer install --no-dev` in production?',
            'options' => [
                'It is faster to type',
                'It leaves out development-only packages such as test tools, which production doesn\'t need',
                'It skips the lock file',
                'It installs newer versions',
            ],
            'correct_option' => 1,
            'explanation' => 'Fewer packages means less to load and less that could be exploited.',
        ],
        [
            'question' => 'Why does a deploy run `php artisan queue:restart`?',
            'options' => [
                'To clear failed jobs',
                'Workers are long-running and still have the old code loaded; this makes them exit after their current job so fresh ones start',
                'To run migrations',
                'It empties the queue',
            ],
            'correct_option' => 1,
            'explanation' => 'A process manager such as Supervisor starts the new workers.',
        ],
        [
            'question' => 'Why do built assets have a hash in their name, like `app-3f2a91c4.js`?',
            'options' => [
                'For security',
                'The name changes when the content does, so assets can be cached forever and a deploy never serves an old file',
                'Browsers require it',
                'To make files smaller',
            ],
            'correct_option' => 1,
            'explanation' => 'Vite writes a manifest so `@vite` finds the current names.',
        ],
        [
            'question' => 'In a zero-downtime deploy, what makes the switch to the new release instant?',
            'options' => [
                'Copying files very quickly',
                'Pointing the `current` symlink at the new release directory',
                'Restarting the server',
                'Running migrations',
            ],
            'correct_option' => 1,
            'explanation' => 'Changing a symlink is atomic: requests see either the old release or the new one.',
        ],
        [
            'question' => 'Why do uploads and logs live in a `shared/storage` directory linked into each release?',
            'options' => [
                'To save disk space',
                'So they survive from one release to the next instead of being left in an old release directory',
                'For speed',
                'Laravel requires it',
            ],
            'correct_option' => 1,
            'explanation' => '`.env` is shared the same way.',
        ],
        [
            'question' => 'How do you roll back a release-directory deploy?',
            'options' => [
                'Restore a database backup',
                'Point `current` back at the previous release',
                'Run `git revert` on the server',
                'Delete the new release first',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s why the last few releases are kept.',
        ],
        [
            'question' => 'Which migration is safe to run while the previous release is still serving requests?',
            'options' => [
                'Renaming `title` to `name`',
                'Dropping the `isbn10` column',
                'Adding a nullable `subtitle` column',
                'Making `price` required with no default',
            ],
            'correct_option' => 2,
            'explanation' => 'Old code simply ignores a new nullable column.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $name = 'app.min.js';
                $hash = 'a1b2c3d4';

                $dot = strrpos($name, '.');
                echo substr($name, 0, $dot) . '-' . $hash . substr($name, $dot);
                PHP,
            'options' => ['`app-a1b2c3d4.min.js`', '`app.min-a1b2c3d4.js`', '`app.min.js-a1b2c3d4`', '`a1b2c3d4.js`'],
            'correct_option' => 1,
            'explanation' => '`strrpos()` finds the last dot, so only the real extension stays at the end.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Clean up old releases',
            'prompt' => 'Release directories are named by timestamp (`20261021-093000`), so they sort by name. Write `releasesToDelete(array $releases, string $current, int $keep): array`, returning the releases to delete, oldest first. Keep the newest `$keep` releases, and never delete `$current`, even if it\'s older (after a rollback).',
            'starter_code' => <<<'PHP'
                <?php

                function releasesToDelete(array $releases, string $current, int $keep): array
                {
                    return array_slice($releases, $keep);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function releasesToDelete(array $releases, string $current, int $keep): array
                {
                    rsort($releases, SORT_STRING);
                    $kept = array_slice($releases, 0, max(0, $keep));

                    $delete = array_filter($releases, fn ($release) => ! in_array($release, $kept, true) && $release !== $current);
                    sort($delete, SORT_STRING);

                    return $delete;
                }
                PHP,
            'hint' => 'Sort newest first, keep the first `$keep`, delete the rest except `$current`, and return them sorted oldest first.',
            'checks' => [
                ['label' => 'Keeps the newest', 'code' => "return expect_same(releasesToDelete(['20261019-120000', '20261021-093000', '20261018-080000', '20261020-170500'], '20261021-093000', 2), ['20261018-080000', '20261019-120000']);"],
                ['label' => 'Never the current one', 'code' => "return expect_same(releasesToDelete(['20261019-120000', '20261021-093000', '20261018-080000', '20261020-170500'], '20261018-080000', 2), ['20261019-120000']);"],
                ['label' => 'Nothing to delete', 'code' => "return expect_same(releasesToDelete(['20261021-093000'], '20261021-093000', 5), []);"],
            ],
        ],
        [
            'title' => 'Roll back to',
            'prompt' => 'Write `rollbackTarget(array $releases, string $current): ?string`, returning the newest release that is older than `$current`, or `null` if there is none (or `$current` isn\'t in the list).',
            'starter_code' => <<<'PHP'
                <?php

                function rollbackTarget(array $releases, string $current): ?string
                {
                    $index = array_search($current, $releases, true);

                    return $releases[$index - 1] ?? null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function rollbackTarget(array $releases, string $current): ?string
                {
                    if (! in_array($current, $releases, true)) {
                        return null;
                    }

                    $older = array_filter($releases, fn ($release) => strcmp($release, $current) < 0);
                    if ($older === []) {
                        return null;
                    }
                    rsort($older, SORT_STRING);

                    return $older[0];
                }
                PHP,
            'hint' => 'The list may be in any order. Keep releases that sort before `$current` and take the largest.',
            'checks' => [
                ['label' => 'The one before', 'code' => "return expect_same([rollbackTarget(['20261021-093000', '20261019-120000', '20261020-170500'], '20261021-093000'), rollbackTarget(['20261021-093000', '20261019-120000', '20261020-170500'], '20261020-170500')], ['20261020-170500', '20261019-120000']);"],
                ['label' => 'Nothing older', 'code' => "return expect_same([rollbackTarget(['20261021-093000'], '20261021-093000'), rollbackTarget(['20261021-093000', '20261020-170500'], '20261022-000000')], [null, null]);"],
            ],
        ],
        [
            'title' => 'Steps in order',
            'prompt' => 'Write `orderSteps(array $steps): array`. `$steps` maps each step to the steps it depends on. Return the step names in an order where every step comes after its dependencies; when several steps are ready, take them in the order they were listed. Throw a `LogicException` if a dependency isn\'t a step, or if the dependencies go round in a circle.',
            'starter_code' => <<<'PHP'
                <?php

                function orderSteps(array $steps): array
                {
                    return array_keys($steps);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function orderSteps(array $steps): array
                {
                    foreach ($steps as $step => $needs) {
                        foreach ($needs as $need) {
                            if (! array_key_exists($need, $steps)) {
                                throw new LogicException("{$step} depends on unknown step {$need}.");
                            }
                        }
                    }

                    $done = [];
                    while (count($done) < count($steps)) {
                        $progress = false;
                        foreach ($steps as $step => $needs) {
                            if (in_array($step, $done, true)) {
                                continue;
                            }
                            if (array_diff($needs, $done) === []) {
                                $done[] = $step;
                                $progress = true;
                                break;
                            }
                        }
                        if (! $progress) {
                            throw new LogicException('The steps depend on each other in a circle.');
                        }
                    }

                    return $done;
                }
                PHP,
            'hint' => 'Repeatedly pick the first listed step whose dependencies are all done. If a pass finds none, there\'s a circle.',
            'checks' => [
                ['label' => 'Dependencies first', 'code' => <<<'PHP'
                    $order = orderSteps([
                        'switch' => ['migrate', 'optimize'],
                        'checkout' => [],
                        'optimize' => ['composer'],
                        'composer' => ['checkout'],
                        'assets' => ['checkout'],
                        'migrate' => ['composer'],
                    ]);
                    return expect_same($order, ['checkout', 'composer', 'optimize', 'assets', 'migrate', 'switch']);
                    PHP],
                ['label' => 'Unknown steps and circles', 'code' => <<<'PHP'
                    $unknown = expect_throws(LogicException::class, fn () => orderSteps(['migrate' => ['composer']]));
                    if ($unknown !== true) return $unknown;
                    return expect_throws(LogicException::class, fn () => orderSteps(['a' => ['b'], 'b' => ['c'], 'c' => ['a'], 'd' => []]));
                    PHP],
            ],
        ],
        [
            'title' => 'Stop at the first failure',
            'prompt' => 'Write `runDeploy(array $steps): array`. `$steps` maps names to callables, in order. Run them one by one; a step fails if it returns `false` or throws. Stop at the first failure. Return `[\'completed\' => [names that succeeded], \'failed\' => name or null, \'error\' => the exception message, or null]`.',
            'starter_code' => <<<'PHP'
                <?php

                function runDeploy(array $steps): array
                {
                    $completed = [];
                    foreach ($steps as $name => $step) {
                        $step();
                        $completed[] = $name;
                    }

                    return ['completed' => $completed, 'failed' => null, 'error' => null];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runDeploy(array $steps): array
                {
                    $completed = [];
                    foreach ($steps as $name => $step) {
                        try {
                            if ($step() === false) {
                                return ['completed' => $completed, 'failed' => $name, 'error' => null];
                            }
                        } catch (Throwable $e) {
                            return ['completed' => $completed, 'failed' => $name, 'error' => $e->getMessage()];
                        }
                        $completed[] = $name;
                    }

                    return ['completed' => $completed, 'failed' => null, 'error' => null];
                }
                PHP,
            'hint' => 'Wrap each call in `try`/`catch`, and return as soon as one fails.',
            'checks' => [
                ['label' => 'All steps succeed', 'code' => "return expect_same(runDeploy(['composer' => fn () => true, 'migrate' => fn () => null, 'switch' => fn () => true]), ['completed' => ['composer', 'migrate', 'switch'], 'failed' => null, 'error' => null]);"],
                ['label' => 'A failing migration stops the switch', 'code' => <<<'PHP'
                    $switched = false;
                    $result = runDeploy(['composer' => fn () => true, 'migrate' => fn () => throw new RuntimeException('SQLSTATE[42S21]: Duplicate column name'), 'switch' => function () use (&$switched) { $switched = true; }]);
                    return expect_same([$result, $switched], [['completed' => ['composer'], 'failed' => 'migrate', 'error' => 'SQLSTATE[42S21]: Duplicate column name'], false]);
                    PHP],
                ['label' => 'false counts as a failure', 'code' => "return expect_same(runDeploy(['health-check' => fn () => false, 'switch' => fn () => true]), ['completed' => [], 'failed' => 'health-check', 'error' => null]);"],
            ],
        ],
        [
            'title' => 'Risky migrations',
            'prompt' => 'Migration operations are arrays such as `[\'op\' => \'add_column\', \'column\' => \'books.subtitle\', \'nullable\' => true]`. Write `unsafeOperations(array $operations): array`, returning the `column` (or `table`) of each operation that would break the code still running: `drop_column`, `rename_column` and `drop_table` always; `add_column` when it is not nullable and has no `default` key. `create_table` and `add_index` are safe.',
            'starter_code' => <<<'PHP'
                <?php

                function unsafeOperations(array $operations): array
                {
                    return array_column(array_filter($operations, fn ($op) => $op['op'] === 'drop_table'), 'table');
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function unsafeOperations(array $operations): array
                {
                    $unsafe = [];
                    foreach ($operations as $operation) {
                        $risky = match ($operation['op']) {
                            'drop_column', 'rename_column', 'drop_table' => true,
                            'add_column' => ! ($operation['nullable'] ?? false) && ! array_key_exists('default', $operation),
                            default => false,
                        };

                        if ($risky) {
                            $unsafe[] = $operation['column'] ?? $operation['table'];
                        }
                    }

                    return $unsafe;
                }
                PHP,
            'hint' => 'A `match` on the operation name; `add_column` needs a closer look at `nullable` and `default`.',
            'checks' => [
                ['label' => 'Finds the risky ones', 'code' => <<<'PHP'
                    $operations = [
                        ['op' => 'create_table', 'table' => 'reviews'],
                        ['op' => 'add_column', 'column' => 'books.subtitle', 'nullable' => true],
                        ['op' => 'add_column', 'column' => 'books.slug', 'nullable' => false],
                        ['op' => 'add_column', 'column' => 'books.stock', 'default' => 0],
                        ['op' => 'rename_column', 'column' => 'books.title'],
                        ['op' => 'add_index', 'column' => 'books.slug'],
                        ['op' => 'drop_column', 'column' => 'books.isbn10'],
                        ['op' => 'drop_table', 'table' => 'wishlists'],
                    ];
                    return expect_same(unsafeOperations($operations), ['books.slug', 'books.title', 'books.isbn10', 'wishlists']);
                    PHP],
                ['label' => 'A null default still counts as a default', 'code' => "return expect_same(unsafeOperations([['op' => 'add_column', 'column' => 'books.note', 'default' => null]]), []);"],
            ],
        ],
        [
            'title' => 'An asset manifest',
            'prompt' => 'Write `assetManifest(array $files): array`. `$files` maps file names to their contents. Return name => versioned name, where the first 8 characters of the `md5()` of the contents go before the **last** extension (`app.min.js` becomes `app.min-1a2b3c4d.js`), or at the end after a dash for a name without an extension. Keep the given order.',
            'starter_code' => <<<'PHP'
                <?php

                function assetManifest(array $files): array
                {
                    $manifest = [];
                    foreach ($files as $name => $contents) {
                        $manifest[$name] = md5($contents) . '-' . $name;
                    }

                    return $manifest;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function assetManifest(array $files): array
                {
                    $manifest = [];
                    foreach ($files as $name => $contents) {
                        $hash = substr(md5($contents), 0, 8);
                        $dot = strrpos($name, '.');
                        $manifest[$name] = $dot === false
                            ? "{$name}-{$hash}"
                            : substr($name, 0, $dot) . "-{$hash}" . substr($name, $dot);
                    }

                    return $manifest;
                }
                PHP,
            'hint' => '`strrpos()` finds the last dot; split the name there.',
            'checks' => [
                ['label' => 'Versioned names', 'code' => <<<'PHP'
                    $files = ['app.js' => 'console.log(1)', 'app.min.css' => 'body{}', 'LICENSE' => 'MIT'];
                    $h = fn ($c) => substr(md5($c), 0, 8);
                    return expect_same(assetManifest($files), ['app.js' => 'app-' . $h('console.log(1)') . '.js', 'app.min.css' => 'app.min-' . $h('body{}') . '.css', 'LICENSE' => 'LICENSE-' . $h('MIT')]);
                    PHP],
                ['label' => 'The name follows the content', 'code' => "return (assetManifest(['app.js' => 'v1'])['app.js'] !== assetManifest(['app.js' => 'v2'])['app.js'] && assetManifest(['a.js' => 'x'])['a.js'] === assetManifest(['a.js' => 'x'])['a.js']) || 'The same content should give the same name, and different content a different one.';"],
            ],
        ],
    ],
];
