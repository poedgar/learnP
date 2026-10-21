<?php

// Lesson 4 of the Deploying course. The theory is in the .md file with the same name.

return [
    'slug' => 'ci-cd',
    'title' => 'CI/CD and releasing safely',
    'summary' => 'Continuous integration and delivery: a GitHub Actions workflow, caching and secrets, pipeline stages, building once, semantic versions and conventional commits, feature flags, canaries and blue-green deploys.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Change the rollout percentage.
        $percentage = 10;

        foreach ([17, 42, 1001, 2024] as $userId) {
            $bucket = crc32("new-checkout:{$userId}") % 100;
            echo "user {$userId}: " . ($bucket < $percentage ? 'new checkout' : 'old checkout') . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does continuous integration give a team?',
            'options' => [
                'Faster laptops',
                'Tests and checks run automatically on a clean machine for every push, so broken changes are caught before merging',
                'Automatic code writing',
                'A staging server',
            ],
            'correct_option' => 1,
            'explanation' => 'Nobody has to remember to run the tests.',
        ],
        [
            'question' => 'Why are small, frequent deploys safer than big, rare ones?',
            'options' => [
                'They aren\'t',
                'Each changes little, so problems are easy to find and rolling back loses little',
                'Servers prefer them',
                'They skip testing',
            ],
            'correct_option' => 1,
            'explanation' => 'A deploy with 200 changes that breaks something is a long hunt.',
        ],
        [
            'question' => 'In the workflow, why is the cache key `composer-${{ hashFiles(\'composer.lock\') }}`?',
            'options' => [
                'It is required syntax',
                'The cache is reused until the lock file changes, and rebuilt when dependencies change',
                'To encrypt the cache',
                'To cache the lock file',
            ],
            'correct_option' => 1,
            'explanation' => 'A key that follows the inputs is never stale.',
        ],
        [
            'question' => 'Where should a deploy key used by a workflow live?',
            'options' => [
                'In the YAML file',
                'In the repository\'s encrypted secrets, read as `${{ secrets.DEPLOY_KEY }}`',
                'In `.env.example`',
                'In a comment',
            ],
            'correct_option' => 1,
            'explanation' => 'Anything in the YAML is visible to everyone who can read the repository.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $versions = ['1.10.0', '1.9.12', '1.2.3'];

                sort($versions, SORT_STRING);
                echo implode(' ', $versions) . ' | ';

                usort($versions, 'version_compare');
                echo implode(' ', $versions);
                PHP,
            'options' => [
                '`1.10.0 1.2.3 1.9.12 | 1.2.3 1.9.12 1.10.0`',
                '`1.2.3 1.9.12 1.10.0 | 1.2.3 1.9.12 1.10.0`',
                '`1.10.0 1.2.3 1.9.12 | 1.10.0 1.2.3 1.9.12`',
                '`1.9.12 1.2.3 1.10.0 | 1.2.3 1.10.0 1.9.12`',
            ],
            'correct_option' => 0,
            'explanation' => 'As text, "1.10" sorts before "1.2"; versions must be compared part by part as numbers.',
        ],
        [
            'question' => 'Under semantic versioning, a release adds a new endpoint and changes nothing else. The current version is 2.4.1. What is next?',
            'options' => ['`2.4.2`', '`2.5.0`', '`3.0.0`', '`2.4.1-1`'],
            'correct_option' => 1,
            'explanation' => 'A new, backwards-compatible feature bumps MINOR and resets PATCH.',
        ],
        [
            'question' => 'Why build the release once and deploy the same artifact to staging and production?',
            'options' => [
                'To save CI minutes only',
                'So what was tested on staging is exactly what reaches production',
                'Production can\'t build',
                'Artifacts are smaller',
            ],
            'correct_option' => 1,
            'explanation' => 'Rebuilding could pick up different dependencies or code.',
        ],
        [
            'question' => 'Why put users into rollout buckets by hashing their id instead of picking at random on each request?',
            'options' => [
                'Hashing is faster',
                'The same user always gets the same bucket, so the feature doesn\'t flicker on and off for them',
                'Random numbers are insecure',
                'It uses less memory',
            ],
            'correct_option' => 1,
            'explanation' => 'Including the flag name in the hash gives each flag its own buckets.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Compare versions',
            'prompt' => 'Write `compareVersions(string $a, string $b): int` returning -1, 0 or 1, without `version_compare()`. Versions are `MAJOR.MINOR.PATCH`, optionally with a leading `v` and a pre-release after a dash (`1.0.0-beta.2`). Compare the three numbers as numbers. A pre-release is lower than the same version without one. Two pre-releases compare dot-separated part by part: numbers as numbers, words alphabetically, a number below a word, and if one runs out first it is lower.',
            'starter_code' => <<<'PHP'
                <?php

                function compareVersions(string $a, string $b): int
                {
                    return strcmp($a, $b) <=> 0;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function compareVersions(string $a, string $b): int
                {
                    $parse = function (string $version): array {
                        [$core, $pre] = array_pad(explode('-', ltrim($version, 'v'), 2), 2, null);

                        return [array_map('intval', explode('.', $core)), $pre === null ? null : explode('.', $pre)];
                    };

                    [$coreA, $preA] = $parse($a);
                    [$coreB, $preB] = $parse($b);

                    if ($coreA !== $coreB) {
                        return $coreA <=> $coreB;
                    }
                    if ($preA === null || $preB === null) {
                        return ($preA === null) <=> ($preB === null);
                    }

                    foreach ($preA as $i => $partA) {
                        if (! isset($preB[$i])) {
                            return 1;
                        }
                        $partB = $preB[$i];
                        $numericA = isNumericPart($partA);
                        $numericB = isNumericPart($partB);
                        if ($numericA && $numericB) {
                            $result = (int) $partA <=> (int) $partB;
                        } elseif ($numericA !== $numericB) {
                            $result = $numericA ? -1 : 1;
                        } else {
                            $result = strcmp($partA, $partB) <=> 0;
                        }
                        if ($result !== 0) {
                            return $result;
                        }
                    }

                    return count($preA) < count($preB) ? -1 : 0;
                }

                function isNumericPart(string $part): bool
                {
                    return preg_match('/^\d+$/', $part) === 1;
                }
                PHP,
            'hint' => 'Split into the numbers and the pre-release parts. PHP compares arrays of numbers element by element with `<=>`. Then handle "no pre-release beats a pre-release", and compare pre-release parts in a loop.',
            'checks' => [
                ['label' => 'Numbers, not text', 'code' => "return expect_same([compareVersions('1.10.0', '1.9.12'), compareVersions('1.2.3', 'v1.2.3'), compareVersions('2.0.0', '10.0.0'), compareVersions('1.2.10', '1.2.9')], [1, 0, -1, 1]);"],
                ['label' => 'Pre-releases', 'code' => <<<'PHP'
                    $ordered = ['1.0.0-alpha', '1.0.0-alpha.1', '1.0.0-alpha.beta', '1.0.0-beta', '1.0.0-beta.2', '1.0.0-beta.11', '1.0.0-rc.1', '1.0.0'];
                    $shuffled = ['1.0.0-beta.11', '1.0.0', '1.0.0-alpha.beta', '1.0.0-alpha', '1.0.0-rc.1', '1.0.0-beta.2', '1.0.0-alpha.1', '1.0.0-beta'];
                    usort($shuffled, 'compareVersions');
                    return expect_same($shuffled, $ordered);
                    PHP],
                ['label' => 'Without version_compare', 'code' => "return ! str_contains(learnp_code(), 'version_compare') || 'Compare the parts yourself, without version_compare().';"],
            ],
        ],
        [
            'title' => 'The next version',
            'prompt' => 'Write `nextVersion(string $current, array $commits): string` from Conventional Commits messages. If any message\'s type has a `!` (like `feat!:` or `fix(api)!:`) or the message contains `BREAKING CHANGE`, bump MAJOR (and reset the others). Otherwise any `feat` bumps MINOR (resetting PATCH). Otherwise any `fix` or `perf` bumps PATCH. Otherwise the version stays. A type may have a scope in brackets: `feat(search): ...`.',
            'starter_code' => <<<'PHP'
                <?php

                function nextVersion(string $current, array $commits): string
                {
                    [$major, $minor, $patch] = explode('.', $current);

                    return "{$major}.{$minor}." . ($patch + 1);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function nextVersion(string $current, array $commits): string
                {
                    [$major, $minor, $patch] = array_map('intval', explode('.', $current));
                    $bump = null;

                    foreach ($commits as $message) {
                        if (preg_match('/^(\w+)(\([^)]*\))?(!)?:/', $message, $match) !== 1) {
                            continue;
                        }
                        [, $type] = $match;
                        if (($match[3] ?? '') === '!' || str_contains($message, 'BREAKING CHANGE')) {
                            $bump = 'major';
                            break;
                        }
                        if ($type === 'feat') {
                            $bump = 'minor';
                        } elseif (in_array($type, ['fix', 'perf'], true) && $bump === null) {
                            $bump = 'patch';
                        }
                    }

                    return match ($bump) {
                        'major' => ($major + 1) . '.0.0',
                        'minor' => "{$major}." . ($minor + 1) . '.0',
                        'patch' => "{$major}.{$minor}." . ($patch + 1),
                        default => "{$major}.{$minor}.{$patch}",
                    };
                }
                PHP,
            'hint' => 'A regex such as `/^(\w+)(\([^)]*\))?(!)?:/` reads the type, scope and `!`. Keep the biggest bump you have seen.',
            'checks' => [
                ['label' => 'Bumps', 'code' => <<<'PHP'
                    return expect_same([
                        nextVersion('2.4.1', ['fix: rounding in totals', 'docs: README']),
                        nextVersion('2.4.1', ['fix: rounding', 'feat(search): search by author']),
                        nextVersion('2.4.1', ['feat: new checkout', 'fix(api)!: return 422 for invalid ISBNs']),
                        nextVersion('2.4.1', ["refactor: tidy\n\nBREAKING CHANGE: drops the v1 API"]),
                        nextVersion('2.4.1', ['perf: faster search']),
                    ], ['2.4.2', '2.5.0', '3.0.0', '3.0.0', '2.4.2']);
                    PHP],
                ['label' => 'Nothing to release', 'code' => "return expect_same([nextVersion('1.0.0', ['docs: typo', 'chore: update deps', 'Merge branch main']), nextVersion('1.0.0', [])], ['1.0.0', '1.0.0']);"],
            ],
        ],
        [
            'title' => 'Does the workflow run',
            'prompt' => 'A workflow\'s `on` section is an array such as `[\'push\' => [\'branches\' => [\'main\', \'release/*\']], \'pull_request\' => []]`. Write `workflowRuns(array $on, string $event, string $branch): bool`. The event must be listed; if it has a `branches` list, the branch must match one of them, where `*` matches any characters except `/` and `**` matches anything.',
            'starter_code' => <<<'PHP'
                <?php

                function workflowRuns(array $on, string $event, string $branch): bool
                {
                    return isset($on[$event]);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function workflowRuns(array $on, string $event, string $branch): bool
                {
                    if (! array_key_exists($event, $on)) {
                        return false;
                    }

                    $branches = $on[$event]['branches'] ?? null;
                    if ($branches === null) {
                        return true;
                    }

                    foreach ($branches as $pattern) {
                        $regex = preg_quote($pattern, '#');
                        $regex = str_replace(['\*\*', '\*'], ['.*', '[^/]*'], $regex);
                        if (preg_match("#^{$regex}$#", $branch) === 1) {
                            return true;
                        }
                    }

                    return false;
                }
                PHP,
            'hint' => 'Quote the pattern, then replace `\*\*` before `\*`, so the double star isn\'t turned into two single ones.',
            'checks' => [
                ['label' => 'Events and branches', 'code' => <<<'PHP'
                    $on = ['push' => ['branches' => ['main', 'release/*']], 'pull_request' => []];
                    return expect_same([workflowRuns($on, 'push', 'main'), workflowRuns($on, 'push', 'release/1.4'), workflowRuns($on, 'push', 'feature/search'), workflowRuns($on, 'pull_request', 'feature/search'), workflowRuns($on, 'schedule', 'main')], [true, true, false, true, false]);
                    PHP],
                ['label' => 'Stars and double stars', 'code' => <<<'PHP'
                    $on = ['push' => ['branches' => ['feature/*', 'hotfix/**']]];
                    return expect_same([workflowRuns($on, 'push', 'feature/search'), workflowRuns($on, 'push', 'feature/search/ui'), workflowRuns($on, 'push', 'hotfix/2026/oct'), workflowRuns($on, 'push', 'mainline')], [true, false, true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'A pipeline',
            'prompt' => 'Write `runPipeline(array $jobs): array`. Each job is `name => [\'needs\' => [...], \'run\' => callable]`, listed after the jobs it needs. Run the jobs in order: a job whose needed jobs all passed runs, and passes if it returns without throwing (and doesn\'t return `false`); a job with a needed job that failed or was skipped is `skipped` and not run. Return name => `passed`, `failed` or `skipped`.',
            'starter_code' => <<<'PHP'
                <?php

                function runPipeline(array $jobs): array
                {
                    $results = [];
                    foreach ($jobs as $name => $job) {
                        $job['run']();
                        $results[$name] = 'passed';
                    }

                    return $results;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function runPipeline(array $jobs): array
                {
                    $results = [];
                    foreach ($jobs as $name => $job) {
                        foreach ($job['needs'] ?? [] as $need) {
                            if (($results[$need] ?? null) !== 'passed') {
                                $results[$name] = 'skipped';
                                continue 2;
                            }
                        }

                        try {
                            $results[$name] = $job['run']() === false ? 'failed' : 'passed';
                        } catch (Throwable) {
                            $results[$name] = 'failed';
                        }
                    }

                    return $results;
                }
                PHP,
            'hint' => 'Before running a job, check every job it needs has `passed`; `continue 2` skips to the next job from inside the inner loop.',
            'checks' => [
                ['label' => 'Everything green', 'code' => <<<'PHP'
                    $ran = [];
                    $job = function ($name, $needs = []) use (&$ran) { return ['needs' => $needs, 'run' => function () use (&$ran, $name) { $ran[] = $name; }]; };
                    $results = runPipeline(['lint' => $job('lint'), 'test' => $job('test'), 'build' => $job('build', ['lint', 'test']), 'deploy' => $job('deploy', ['build'])]);
                    return expect_same([$results, $ran], [['lint' => 'passed', 'test' => 'passed', 'build' => 'passed', 'deploy' => 'passed'], ['lint', 'test', 'build', 'deploy']]);
                    PHP],
                ['label' => 'A failing test stops the deploy', 'code' => <<<'PHP'
                    $deployed = false;
                    $results = runPipeline([
                        'lint' => ['needs' => [], 'run' => fn () => true],
                        'test' => ['needs' => [], 'run' => fn () => throw new RuntimeException('2 tests failed')],
                        'docs' => ['needs' => ['lint'], 'run' => fn () => true],
                        'build' => ['needs' => ['lint', 'test'], 'run' => fn () => true],
                        'deploy' => ['needs' => ['build'], 'run' => function () use (&$deployed) { $deployed = true; }],
                    ]);
                    return expect_same([$results, $deployed], [['lint' => 'passed', 'test' => 'failed', 'docs' => 'passed', 'build' => 'skipped', 'deploy' => 'skipped'], false]);
                    PHP],
            ],
        ],
        [
            'title' => 'Feature flags',
            'prompt' => 'Write `featureEnabled(array $flag, array $user): bool`. A flag is `[\'name\' => ..., \'enabled\' => bool, \'users\' => [ids], \'roles\' => [names], \'percentage\' => 0-100]` (only `name` and `enabled` are always present). A disabled flag is off for everyone. Otherwise it\'s on if the user\'s `id` is in `users`, or their `role` is in `roles`, or `crc32("NAME:ID") % 100` is below `percentage`.',
            'starter_code' => <<<'PHP'
                <?php

                function featureEnabled(array $flag, array $user): bool
                {
                    return $flag['enabled'];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function featureEnabled(array $flag, array $user): bool
                {
                    if (! $flag['enabled']) {
                        return false;
                    }
                    if (in_array($user['id'], $flag['users'] ?? [], true)) {
                        return true;
                    }
                    if (isset($user['role']) && in_array($user['role'], $flag['roles'] ?? [], true)) {
                        return true;
                    }

                    return crc32("{$flag['name']}:{$user['id']}") % 100 < ($flag['percentage'] ?? 0);
                }
                PHP,
            'hint' => 'Check in order: switched off, named users, roles, then the percentage bucket.',
            'checks' => [
                ['label' => 'Off, users and roles', 'code' => <<<'PHP'
                    $flag = ['name' => 'new-checkout', 'enabled' => true, 'users' => [7], 'roles' => ['staff']];
                    return expect_same([featureEnabled(['name' => 'x', 'enabled' => false, 'users' => [7]], ['id' => 7]), featureEnabled($flag, ['id' => 7]), featureEnabled($flag, ['id' => 8, 'role' => 'staff']), featureEnabled($flag, ['id' => 9, 'role' => 'customer'])], [false, true, true, false]);
                    PHP],
                ['label' => 'A stable percentage', 'code' => <<<'PHP'
                    $flag = ['name' => 'new-checkout', 'enabled' => true, 'percentage' => 25];
                    $on = 0;
                    foreach (range(1, 1000) as $id) {
                        $enabled = featureEnabled($flag, ['id' => $id]);
                        if ($enabled !== (crc32("new-checkout:{$id}") % 100 < 25)) return "User {$id} is in the wrong bucket.";
                        if ($enabled !== featureEnabled($flag, ['id' => $id])) return 'The same user must always get the same answer.';
                        $on += $enabled ? 1 : 0;
                    }
                    return ($on > 150 && $on < 350) || "About a quarter should be on, but {$on} of 1000 were.";
                    PHP],
                ['label' => '0% and 100%', 'code' => "return expect_same([featureEnabled(['name' => 'f', 'enabled' => true, 'percentage' => 0], ['id' => 5]), featureEnabled(['name' => 'f', 'enabled' => true, 'percentage' => 100], ['id' => 5])], [false, true]);"],
            ],
        ],
        [
            'title' => 'Promote the canary?',
            'prompt' => 'Write `canaryVerdict(array $baseline, array $canary, float $tolerance, int $minRequests = 100): string`. Both arrays have `requests` and `errors`. Return `wait` if the canary has served fewer than `$minRequests` requests; `rollback` if the canary\'s error rate is more than `$tolerance` above the baseline\'s (rates as fractions, so 0.01 is 1%); otherwise `promote`. A baseline with no requests has an error rate of 0.',
            'starter_code' => <<<'PHP'
                <?php

                function canaryVerdict(array $baseline, array $canary, float $tolerance, int $minRequests = 100): string
                {
                    return $canary['errors'] > $baseline['errors'] ? 'rollback' : 'promote';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function canaryVerdict(array $baseline, array $canary, float $tolerance, int $minRequests = 100): string
                {
                    if ($canary['requests'] < $minRequests) {
                        return 'wait';
                    }

                    $rate = fn (array $stats) => $stats['requests'] > 0 ? $stats['errors'] / $stats['requests'] : 0.0;

                    return $rate($canary) > $rate($baseline) + $tolerance ? 'rollback' : 'promote';
                }
                PHP,
            'hint' => 'Compare error **rates**, not error counts: the canary gets far fewer requests than the baseline.',
            'checks' => [
                ['label' => 'Rates, not counts', 'code' => "return expect_same([canaryVerdict(['requests' => 10000, 'errors' => 50], ['requests' => 500, 'errors' => 3], 0.005), canaryVerdict(['requests' => 10000, 'errors' => 50], ['requests' => 500, 'errors' => 9], 0.005)], ['promote', 'rollback']);"],
                ['label' => 'Too early to tell', 'code' => "return expect_same([canaryVerdict(['requests' => 10000, 'errors' => 0], ['requests' => 40, 'errors' => 40], 0.01), canaryVerdict(['requests' => 0, 'errors' => 0], ['requests' => 200, 'errors' => 1], 0.01)], ['wait', 'promote']);"],
            ],
        ],
    ],
];
