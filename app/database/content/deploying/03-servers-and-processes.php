<?php

// Lesson 3 of the Deploying course. The theory is in the .md file with the same name.

return [
    'slug' => 'servers-and-processes',
    'title' => 'Servers and processes',
    'summary' => 'What runs in production: nginx and PHP-FPM, the public document root, sizing worker pools, queue workers under Supervisor, the scheduler and cron expressions, health checks and where logs go.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try a URL for a file that doesn't exist.
        $publicFiles = ['/favicon.ico', '/build/app-3f2a91c4.js', '/robots.txt'];

        foreach (['/build/app-3f2a91c4.js', '/books/7?tab=reviews'] as $uri) {
            $path = parse_url($uri, PHP_URL_PATH);
            echo $uri . ' → ' . (in_array($path, $publicFiles, true) ? "the file {$path}" : 'Laravel (index.php)') . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'Why must the web server\'s document root be `public/` and not the project folder?',
            'options' => [
                'It is faster',
                'Otherwise files such as `.env` and `vendor/` could be downloaded by anyone',
                'Laravel won\'t start otherwise',
                'For SEO',
            ],
            'correct_option' => 1,
            'explanation' => 'Only `public/` is meant to be reachable from the web.',
        ],
        [
            'question' => 'What does `try_files $uri $uri/ /index.php?$query_string;` do?',
            'options' => [
                'Always runs Laravel',
                'Serves the file if it exists, then a directory, and otherwise hands the request to Laravel\'s index.php',
                'Tries three servers',
                'Caches files',
            ],
            'correct_option' => 1,
            'explanation' => 'Images and built assets never touch PHP.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $memoryMb = 4096;
                $reservedMb = 1024;
                $perWorkerMb = 70;

                echo intdiv($memoryMb - $reservedMb, $perWorkerMb);
                PHP,
            'options' => ['`43`', '`58`', '`44`', '`70`'],
            'correct_option' => 0,
            'explanation' => '3072 MB for PHP divided by 70 MB per worker is 43 whole workers.',
        ],
        [
            'question' => 'What keeps queue workers running after they crash?',
            'options' => ['Cron', 'A process manager such as Supervisor or systemd', 'nginx', 'PHP-FPM'],
            'correct_option' => 1,
            'explanation' => 'With `autorestart=true`, Supervisor starts a worker again whenever it exits.',
        ],
        [
            'question' => 'Why run workers with `--max-time=3600`?',
            'options' => [
                'Jobs may only take an hour',
                'Each worker exits after an hour and is restarted fresh, which keeps slow memory leaks in check',
                'To save electricity',
                'It is required by Redis',
            ],
            'correct_option' => 1,
            'explanation' => 'Long-running PHP processes can slowly grow; regular restarts are cheap insurance.',
        ],
        [
            'question' => 'When does the cron expression `*/15 9-17 * * 1-5` run?',
            'options' => [
                'Every 15 hours',
                'Every 15 minutes from 9:00 to 17:45 on weekdays',
                'At 9:15 and 17:15 every day',
                'On the 15th of each month',
            ],
            'correct_option' => 1,
            'explanation' => 'Minute `*/15`, hours 9 to 17, any day of the month, any month, Monday to Friday.',
        ],
        [
            'question' => 'Your app runs on three servers, each with the scheduler cron entry. How do you stop the nightly report being sent three times?',
            'options' => ['Remove cron from two servers by hand', '`->onOneServer()` on the scheduled task', 'Run it in a queue', 'You can\'t'],
            'correct_option' => 1,
            'explanation' => 'It takes a cache lock, so only the first server runs it.',
        ],
        [
            'question' => 'What is the `/up` route for?',
            'options' => [
                'Uploading files',
                'Health checks: load balancers and monitors call it to see whether the app is working',
                'Upgrading Laravel',
                'Scrolling to the top',
            ],
            'correct_option' => 1,
            'explanation' => 'A server that fails it stops getting traffic.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Does cron run now',
            'prompt' => 'Write `cronMatches(string $expression, DateTimeImmutable $time): bool` for five-field cron expressions (minute, hour, day of month, month, day of week with 0 or 7 for Sunday). Each field is a comma-separated list of `*`, `N`, `A-B`, each optionally followed by `/STEP` (a step over that range, or over the whole field for `*`). As in real cron, when **both** day of month and day of week are restricted (not `*`), a time matches if **either** does.',
            'starter_code' => <<<'PHP'
                <?php

                function cronMatches(string $expression, DateTimeImmutable $time): bool
                {
                    [$minute, $hour] = explode(' ', $expression);

                    return ($minute === '*' || (int) $minute === (int) $time->format('i'))
                        && ($hour === '*' || (int) $hour === (int) $time->format('G'));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cronMatches(string $expression, DateTimeImmutable $time): bool
                {
                    $fields = preg_split('/\s+/', trim($expression));
                    if (count($fields) !== 5) {
                        throw new InvalidArgumentException("Invalid cron expression: {$expression}");
                    }
                    [$minute, $hour, $day, $month, $weekday] = $fields;

                    $matches = function (string $field, int $value, int $min, int $max): bool {
                        foreach (explode(',', $field) as $part) {
                            [$range, $step] = array_pad(explode('/', $part, 2), 2, '1');
                            if ($range === '*') {
                                [$from, $to] = [$min, $max];
                            } elseif (str_contains($range, '-')) {
                                [$from, $to] = array_map('intval', explode('-', $range, 2));
                            } else {
                                $from = $to = (int) $range;
                                if (str_contains($part, '/')) {
                                    $to = $max;
                                }
                            }
                            if ($value >= $from && $value <= $to && ($value - $from) % max(1, (int) $step) === 0) {
                                return true;
                            }
                        }

                        return false;
                    };

                    $weekdayNow = (int) $time->format('w');
                    $dayMatches = $matches($day, (int) $time->format('j'), 1, 31);
                    $weekdayMatches = $matches($weekday, $weekdayNow, 0, 7) || ($weekdayNow === 0 && $matches($weekday, 7, 0, 7));

                    if ($day !== '*' && $weekday !== '*') {
                        $daysOk = $dayMatches || $weekdayMatches;
                    } else {
                        $daysOk = $dayMatches && $weekdayMatches;
                    }

                    return $daysOk
                        && $matches($minute, (int) $time->format('i'), 0, 59)
                        && $matches($hour, (int) $time->format('G'), 0, 23)
                        && $matches($month, (int) $time->format('n'), 1, 12);
                }
                PHP,
            'hint' => 'Write one helper that checks a value against a field: split on commas, then each part into range and step. Sunday is 0 from `format(\'w\')`; also accept 7.',
            'checks' => [
                ['label' => 'Simple fields', 'code' => <<<'PHP'
                    $t = new DateTimeImmutable('2026-10-21 02:00:00');   // a Wednesday
                    return expect_same([cronMatches('* * * * *', $t), cronMatches('0 2 * * *', $t), cronMatches('30 2 * * *', $t), cronMatches('0 2 21 10 *', $t), cronMatches('0 2 * * 3', $t), cronMatches('0 2 * * 4', $t)], [true, true, false, true, true, false]);
                    PHP],
                ['label' => 'Lists, ranges and steps', 'code' => <<<'PHP'
                    $at = fn ($s) => new DateTimeImmutable($s);
                    return expect_same([
                        cronMatches('*/15 9-17 * * 1-5', $at('2026-10-21 09:45')),
                        cronMatches('*/15 9-17 * * 1-5', $at('2026-10-21 09:50')),
                        cronMatches('*/15 9-17 * * 1-5', $at('2026-10-24 10:00')),
                        cronMatches('0 8,12,18 * * *', $at('2026-10-21 12:00')),
                        cronMatches('5-20/5 * * * *', $at('2026-10-21 12:15')),
                        cronMatches('5-20/5 * * * *', $at('2026-10-21 12:25')),
                    ], [true, false, false, true, true, false]);
                    PHP],
                ['label' => 'Sundays and the day rule', 'code' => <<<'PHP'
                    $sunday = new DateTimeImmutable('2026-10-25 00:00');
                    $first = new DateTimeImmutable('2026-10-01 00:00');   // a Thursday
                    return expect_same([cronMatches('0 0 * * 0', $sunday), cronMatches('0 0 * * 7', $sunday), cronMatches('0 0 1 * 0', $sunday), cronMatches('0 0 1 * 0', $first), cronMatches('0 0 15 * 0', $first)], [true, true, true, true, false]);
                    PHP],
            ],
        ],
        [
            'title' => 'File or Laravel',
            'prompt' => 'Write `resolveRequest(string $uri, array $publicFiles): string`, deciding what nginx does with a request. If any part of the path starts with a dot (except `.well-known`), return `403`. If the path is one of `$publicFiles`, return the path. Otherwise return `/index.php`, followed by `?` and the query string if the URI had one.',
            'starter_code' => <<<'PHP'
                <?php

                function resolveRequest(string $uri, array $publicFiles): string
                {
                    return in_array($uri, $publicFiles, true) ? $uri : '/index.php';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function resolveRequest(string $uri, array $publicFiles): string
                {
                    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
                    $query = parse_url($uri, PHP_URL_QUERY);

                    foreach (explode('/', $path) as $segment) {
                        if (str_starts_with($segment, '.') && $segment !== '.well-known') {
                            return '403';
                        }
                    }

                    if (in_array($path, $publicFiles, true)) {
                        return $path;
                    }

                    return '/index.php' . ($query !== null && $query !== '' ? "?{$query}" : '');
                }
                PHP,
            'hint' => '`parse_url()` splits the path and the query. Check every path segment for a leading dot.',
            'checks' => [
                ['label' => 'Files and Laravel', 'code' => "\$files = ['/favicon.ico', '/build/app-3f2a91c4.js']; return expect_same([resolveRequest('/favicon.ico', \$files), resolveRequest('/build/app-3f2a91c4.js?v=2', \$files), resolveRequest('/books/7?tab=reviews', \$files), resolveRequest('/', \$files)], ['/favicon.ico', '/build/app-3f2a91c4.js', '/index.php?tab=reviews', '/index.php']);"],
                ['label' => 'Hidden files are forbidden', 'code' => "return expect_same([resolveRequest('/.env', []), resolveRequest('/.git/config', []), resolveRequest('/uploads/.htaccess', []), resolveRequest('/.well-known/security.txt', ['/.well-known/security.txt'])], ['403', '403', '403', '/.well-known/security.txt']);"],
            ],
        ],
        [
            'title' => 'How many PHP workers',
            'prompt' => 'Write `maxChildren(int $totalMb, int $reservedMb, array $workerSamplesMb): int`. Size the pool for the **largest** measured worker: divide the memory left after the reserved part by it, rounding down. Throw an `InvalidArgumentException` if there are no samples or not even one worker fits.',
            'starter_code' => <<<'PHP'
                <?php

                function maxChildren(int $totalMb, int $reservedMb, array $workerSamplesMb): int
                {
                    return intdiv($totalMb, (int) (array_sum($workerSamplesMb) / count($workerSamplesMb)));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function maxChildren(int $totalMb, int $reservedMb, array $workerSamplesMb): int
                {
                    if ($workerSamplesMb === []) {
                        throw new InvalidArgumentException('Measure at least one worker.');
                    }

                    $children = intdiv(max(0, $totalMb - $reservedMb), (int) ceil(max($workerSamplesMb)));
                    if ($children < 1) {
                        throw new InvalidArgumentException('Not even one worker fits in the memory left.');
                    }

                    return $children;
                }
                PHP,
            'hint' => 'Use `max()` of the samples, not the average: a pool sized for the average runs out of memory under heavy requests.',
            'checks' => [
                ['label' => 'Sized for the largest worker', 'code' => "return expect_same([maxChildren(4096, 1024, [52, 61, 70, 58]), maxChildren(2048, 512, [40])], [43, 38]);"],
                ['label' => 'Impossible sizes', 'code' => <<<'PHP'
                    $none = expect_throws(InvalidArgumentException::class, fn () => maxChildren(4096, 1024, []));
                    if ($none !== true) return $none;
                    return expect_throws(InvalidArgumentException::class, fn () => maxChildren(1024, 1000, [60]));
                    PHP],
            ],
        ],
        [
            'title' => 'Time for a fresh worker',
            'prompt' => 'Write `restartReason(int $startedAt, int $now, int $maxTime, ?int $restartSignalAt, int $memoryMb, int $memoryLimitMb): ?string`, which a worker checks between jobs. Return `restart signal` if `queue:restart` was signalled at or after the worker started; otherwise `memory` if the memory used has reached the limit; otherwise `max time` if the worker has been running for at least `$maxTime` seconds (0 means no limit); otherwise `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function restartReason(int $startedAt, int $now, int $maxTime, ?int $restartSignalAt, int $memoryMb, int $memoryLimitMb): ?string
                {
                    return $restartSignalAt !== null ? 'restart signal' : null;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function restartReason(int $startedAt, int $now, int $maxTime, ?int $restartSignalAt, int $memoryMb, int $memoryLimitMb): ?string
                {
                    if ($restartSignalAt !== null && $restartSignalAt >= $startedAt) {
                        return 'restart signal';
                    }
                    if ($memoryMb >= $memoryLimitMb) {
                        return 'memory';
                    }
                    if ($maxTime > 0 && $now - $startedAt >= $maxTime) {
                        return 'max time';
                    }

                    return null;
                }
                PHP,
            'hint' => 'Three checks in order. A restart signal from before the worker started is an old one it can ignore.',
            'checks' => [
                ['label' => 'Each reason', 'code' => "return expect_same([restartReason(1000, 1500, 3600, 1200, 50, 128), restartReason(1000, 1500, 3600, 900, 130, 128), restartReason(1000, 4600, 3600, null, 50, 128), restartReason(1000, 1500, 3600, 900, 50, 128)], ['restart signal', 'memory', 'max time', null]);"],
                ['label' => 'No time limit', 'code' => "return expect_same(restartReason(0, 999999, 0, null, 10, 128), null);"],
            ],
        ],
        [
            'title' => 'A health check',
            'prompt' => 'Write `healthReport(array $checks): array`. Each check maps a name to a callable that returns `true` when healthy; returning anything else or throwing means it\'s failing. Return `[\'status\' => 200 or 503, \'checks\' => [name => \'ok\' or \'failing\']]`. The status is 503 if any check fails. Never include exception messages: health endpoints are public.',
            'starter_code' => <<<'PHP'
                <?php

                function healthReport(array $checks): array
                {
                    $results = [];
                    foreach ($checks as $name => $check) {
                        $results[$name] = $check() ? 'ok' : 'failing';
                    }

                    return ['status' => 200, 'checks' => $results];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function healthReport(array $checks): array
                {
                    $results = [];
                    foreach ($checks as $name => $check) {
                        try {
                            $results[$name] = $check() === true ? 'ok' : 'failing';
                        } catch (Throwable) {
                            $results[$name] = 'failing';
                        }
                    }

                    return [
                        'status' => in_array('failing', $results, true) ? 503 : 200,
                        'checks' => $results,
                    ];
                }
                PHP,
            'hint' => 'Catch `Throwable` around each check, and compare the result with `=== true`.',
            'checks' => [
                ['label' => 'All healthy', 'code' => "return expect_same(healthReport(['database' => fn () => true, 'cache' => fn () => true]), ['status' => 200, 'checks' => ['database' => 'ok', 'cache' => 'ok']]);"],
                ['label' => 'Failures, without details', 'code' => <<<'PHP'
                    $report = healthReport(['database' => fn () => throw new PDOException('SQLSTATE[HY000] [2002] Connection refused to 10.0.1.5 as admin'), 'cache' => fn () => true, 'queue' => fn () => 'backlog: 5000']);
                    if (str_contains(json_encode($report), '10.0.1.5')) return 'The exception message leaked into the report.';
                    return expect_same($report, ['status' => 503, 'checks' => ['database' => 'failing', 'cache' => 'ok', 'queue' => 'failing']]);
                    PHP],
            ],
        ],
        [
            'title' => 'Rotate the logs',
            'prompt' => 'The `daily` log channel writes files like `laravel-2026-10-21.log`. Write `logsToDelete(array $files, DateTimeImmutable $today, int $days): array`, returning the files older than the last `$days` days (today counts as one), oldest first. Ignore files that don\'t follow the pattern.',
            'starter_code' => <<<'PHP'
                <?php

                function logsToDelete(array $files, DateTimeImmutable $today, int $days): array
                {
                    sort($files);

                    return array_slice($files, 0, count($files) - $days);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function logsToDelete(array $files, DateTimeImmutable $today, int $days): array
                {
                    $oldestKept = $today->modify('-' . ($days - 1) . ' days')->format('Y-m-d');

                    $delete = [];
                    foreach ($files as $file) {
                        if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $file, $match) === 1 && $match[1] < $oldestKept) {
                            $delete[] = $file;
                        }
                    }
                    sort($delete);

                    return $delete;
                }
                PHP,
            'hint' => 'Work out the oldest date to keep; dates in `Y-m-d` form compare correctly as strings.',
            'checks' => [
                ['label' => 'Keeps the last days', 'code' => <<<'PHP'
                    $files = ['laravel-2026-10-21.log', 'laravel-2026-10-15.log', 'laravel-2026-10-19.log', 'worker.log', 'laravel-2026-10-14.log', 'laravel-2026-10-20.log'];
                    return expect_same(logsToDelete($files, new DateTimeImmutable('2026-10-21'), 3), ['laravel-2026-10-14.log', 'laravel-2026-10-15.log']);
                    PHP],
                ['label' => 'Across a month', 'code' => "return expect_same(logsToDelete(['laravel-2026-09-30.log', 'laravel-2026-10-01.log', 'laravel-2026-09-29.log'], new DateTimeImmutable('2026-10-01'), 2), ['laravel-2026-09-29.log']);"],
            ],
        ],
    ],
];
