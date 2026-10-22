<?php

// Lesson 5 of the Deploying course. The theory is in the .md file with the same name.

return [
    'slug' => 'monitoring-and-incidents',
    'title' => 'Monitoring, backups and incidents',
    'summary' => 'Running a live app: log levels and structured logs, error tracking, metrics and percentiles, alerts that matter, maintenance mode, backups you have restored, and handling incidents without blame.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Add a very slow request and compare.
        $timesMs = [80, 95, 102, 110, 120, 130, 140, 150, 900, 2400];
        sort($timesMs);

        $average = array_sum($timesMs) / count($timesMs);
        $median = $timesMs[(int) ceil(0.5 * count($timesMs)) - 1];

        echo "average {$average} ms, median {$median} ms";
        PHP,

    'quiz' => [
        [
            'question' => 'A log channel has `level` set to `warning`. Which of these is written?',
            'options' => ['`Log::info(...)`', '`Log::debug(...)`', '`Log::error(...)`', '`Log::notice(...)`'],
            'correct_option' => 2,
            'explanation' => 'The level is a minimum: warning and everything more severe.',
        ],
        [
            'question' => 'Why log `[\'order_id\' => 981]` as context instead of the sentence "Order 981 was placed"?',
            'options' => [
                'It is shorter',
                'Structured fields can be searched and filtered by log tools',
                'Laravel can\'t log sentences',
                'For security',
            ],
            'correct_option' => 1,
            'explanation' => 'Then "every log line for order 981" is one query.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $times = [100, 100, 100, 100, 100, 100, 100, 100, 100, 5000];
                sort($times);

                $average = array_sum($times) / count($times);
                $p50 = $times[(int) ceil(0.5 * count($times)) - 1];

                echo "{$average} {$p50}";
                PHP,
            'options' => ['`590 100`', '`100 100`', '`590 590`', '`5000 100`'],
            'correct_option' => 0,
            'explanation' => 'One very slow request drags the average up while the median barely notices; percentiles describe users better.',
        ],
        [
            'question' => 'What does an error tracker such as Sentry or Flare add over reading log files?',
            'options' => [
                'Nothing',
                'Exceptions grouped by cause, with stack traces, request details and a notification when a new one appears',
                'Faster PHP',
                'Backups',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s the quickest way to see what a deploy broke.',
        ],
        [
            'question' => 'Why should an alert need the problem to last several minutes?',
            'options' => [
                'To save money',
                'So a single blip doesn\'t page anyone; alerts that fire for nothing get ignored',
                'Because metrics are delayed',
                'It shouldn\'t',
            ],
            'correct_option' => 1,
            'explanation' => 'Every alert should be something a person needs to act on.',
        ],
        [
            'question' => 'What does `php artisan down --secret="b7a1c-staff-preview"` allow?',
            'options' => [
                'Nobody can reach the site',
                'Visiting /b7a1c-staff-preview sets a cookie that lets you use the site while everyone else sees the maintenance page',
                'It sets the admin password',
                'It deploys to a secret server',
            ],
            'correct_option' => 1,
            'explanation' => 'Handy for checking a change before bringing the site back up.',
        ],
        [
            'question' => 'When can you rely on a backup?',
            'options' => [
                'As soon as it is taken',
                'When you have actually restored it successfully, and copies live somewhere other than the server',
                'When it is encrypted',
                'When it is on the same disk',
            ],
            'correct_option' => 1,
            'explanation' => 'Untested backups fail exactly when you need them.',
        ],
        [
            'question' => 'A bad deploy is causing errors. What comes first?',
            'options' => [
                'Finding who wrote the bug',
                'Mitigating: roll back or switch off the flag, then investigate',
                'Writing the post-mortem',
                'Fixing the code properly',
            ],
            'correct_option' => 1,
            'explanation' => 'Stop the damage first; understand it after.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Above the minimum',
            'prompt' => 'Write `filterLogs(array $entries, string $minimum): array`. Entries are `[level, message]` pairs, and the levels from least to most severe are debug, info, notice, warning, error, critical, alert, emergency. Keep the entries at or above `$minimum`, in order. An unknown level (in an entry or as the minimum) throws an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function filterLogs(array $entries, string $minimum): array
                {
                    return array_filter($entries, fn ($entry) => $entry[0] >= $minimum);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function filterLogs(array $entries, string $minimum): array
                {
                    $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
                    $rank = function (string $level) use ($levels): int {
                        $index = array_search($level, $levels, true);
                        if ($index === false) {
                            throw new InvalidArgumentException("Unknown log level {$level}.");
                        }

                        return $index;
                    };

                    $min = $rank($minimum);

                    return array_values(array_filter($entries, fn ($entry) => $rank($entry[0]) >= $min));
                }
                PHP,
            'hint' => 'Turn levels into positions in the list; comparing the names as strings doesn\'t follow severity.',
            'checks' => [
                ['label' => 'Keeps the severe ones', 'code' => <<<'PHP'
                    $entries = [['debug', 'Query took 3ms'], ['info', 'Order placed'], ['warning', 'Payment retried'], ['error', 'Payment failed'], ['emergency', 'Database down']];
                    return expect_same(filterLogs($entries, 'warning'), [['warning', 'Payment retried'], ['error', 'Payment failed'], ['emergency', 'Database down']]);
                    PHP],
                ['label' => 'Unknown levels', 'code' => <<<'PHP'
                    $bad = expect_throws(InvalidArgumentException::class, fn () => filterLogs([['info', 'x']], 'loud'));
                    if ($bad !== true) return $bad;
                    return expect_throws(InvalidArgumentException::class, fn () => filterLogs([['fatal', 'x']], 'info'));
                    PHP],
            ],
        ],
        [
            'title' => 'A JSON log line',
            'prompt' => 'Write `logLine(string $level, string $message, array $context, DateTimeImmutable $time): string` returning one line of JSON with the keys `time` (ISO 8601, `DATE_ATOM`), `level`, `message` and `context`. In the context, at any depth, replace the value of any key named `password`, `token`, `secret` or `card_number` (any case) with `[redacted]`. Don\'t escape slashes.',
            'starter_code' => <<<'PHP'
                <?php

                function logLine(string $level, string $message, array $context, DateTimeImmutable $time): string
                {
                    return json_encode(compact('level', 'message', 'context'));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function logLine(string $level, string $message, array $context, DateTimeImmutable $time): string
                {
                    $redact = function (array $data) use (&$redact): array {
                        foreach ($data as $key => $value) {
                            if (is_string($key) && in_array(strtolower($key), ['password', 'token', 'secret', 'card_number'], true)) {
                                $data[$key] = '[redacted]';
                            } elseif (is_array($value)) {
                                $data[$key] = $redact($value);
                            }
                        }

                        return $data;
                    };

                    return json_encode([
                        'time' => $time->format(DATE_ATOM),
                        'level' => $level,
                        'message' => $message,
                        'context' => $redact($context),
                    ], JSON_UNESCAPED_SLASHES);
                }
                PHP,
            'hint' => 'A recursive closure needs `use (&$redact)` to call itself. `JSON_UNESCAPED_SLASHES` keeps URLs readable.',
            'checks' => [
                ['label' => 'The line', 'code' => <<<'PHP'
                    $line = logLine('error', 'Payment failed', ['order_id' => 981, 'url' => 'https://pay.test/charge'], new DateTimeImmutable('2026-10-21 09:00:00', new DateTimeZone('UTC')));
                    return expect_same($line, '{"time":"2026-10-21T09:00:00+00:00","level":"error","message":"Payment failed","context":{"order_id":981,"url":"https://pay.test/charge"}}');
                    PHP],
                ['label' => 'Secrets are redacted', 'code' => <<<'PHP'
                    $line = logLine('info', 'Login', ['email' => 'ada@example.com', 'Password' => 'hunter2', 'payment' => ['card_number' => '4242424242424242', 'last4' => '4242'], 'headers' => ['token' => 'abc']], new DateTimeImmutable('2026-10-21 09:00:00+00:00'));
                    $context = json_decode($line, true)['context'];
                    if (str_contains($line, 'hunter2') || str_contains($line, '4242424242424242') || str_contains($line, '"abc"')) return 'A secret reached the log.';
                    return expect_same($context, ['email' => 'ada@example.com', 'Password' => '[redacted]', 'payment' => ['card_number' => '[redacted]', 'last4' => '4242'], 'headers' => ['token' => '[redacted]']]);
                    PHP],
            ],
        ],
        [
            'title' => 'Percentiles',
            'prompt' => 'Write `percentile(array $values, float $p): float|int` using the nearest-rank method: sort the values, and return the one at position `ceil($p / 100 * count)` (counting from 1), with position at least 1. An empty list, or `$p` outside 0–100, throws an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function percentile(array $values, float $p): float|int
                {
                    return $values[(int) ($p / 100 * count($values))];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function percentile(array $values, float $p): float|int
                {
                    if ($values === [] || $p < 0 || $p > 100) {
                        throw new InvalidArgumentException('Need some values and a percentile from 0 to 100.');
                    }

                    sort($values);
                    $rank = max(1, (int) ceil($p / 100 * count($values)));

                    return $values[$rank - 1];
                }
                PHP,
            'hint' => 'Sort first. Positions count from 1, array keys from 0.',
            'checks' => [
                ['label' => 'p50, p95 and p100', 'code' => <<<'PHP'
                    $times = [2400, 80, 150, 95, 110, 900, 102, 120, 140, 130];
                    return expect_same([percentile($times, 50), percentile($times, 95), percentile($times, 100), percentile($times, 0), percentile([7], 99)], [120, 2400, 2400, 80, 7]);
                    PHP],
                ['label' => 'A larger sample', 'code' => 'return expect_same([percentile(range(1, 200), 95), percentile(range(1, 200), 99.5)], [190, 199]);'],
                ['label' => 'Bad input', 'code' => <<<'PHP'
                    $empty = expect_throws(InvalidArgumentException::class, fn () => percentile([], 50));
                    if ($empty !== true) return $empty;
                    return expect_throws(InvalidArgumentException::class, fn () => percentile([1, 2], 101));
                    PHP],
            ],
        ],
        [
            'title' => 'Wake someone up?',
            'prompt' => 'Write `shouldAlert(array $errorRates, float $threshold, int $minutes): bool`. `$errorRates` holds one error rate per minute, oldest first. Alert only if there are at least `$minutes` readings and each of the last `$minutes` is **above** the threshold.',
            'starter_code' => <<<'PHP'
                <?php

                function shouldAlert(array $errorRates, float $threshold, int $minutes): bool
                {
                    return max($errorRates) > $threshold;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function shouldAlert(array $errorRates, float $threshold, int $minutes): bool
                {
                    if ($minutes < 1 || count($errorRates) < $minutes) {
                        return false;
                    }

                    foreach (array_slice($errorRates, -$minutes) as $rate) {
                        if ($rate <= $threshold) {
                            return false;
                        }
                    }

                    return true;
                }
                PHP,
            'hint' => '`array_slice($errorRates, -$minutes)` gives the last readings.',
            'checks' => [
                ['label' => 'A sustained problem', 'code' => 'return expect_same([shouldAlert([0.01, 0.03, 0.04, 0.05, 0.03, 0.06], 0.02, 5), shouldAlert([0.01, 0.03, 0.04, 0.05], 0.02, 3)], [true, true]);'],
                ['label' => 'A blip', 'code' => 'return expect_same([shouldAlert([0.01, 0.01, 0.30, 0.01, 0.01], 0.02, 3), shouldAlert([0.05, 0.05, 0.05, 0.02], 0.02, 3), shouldAlert([0.5, 0.5], 0.02, 5)], [false, false, false]);'],
            ],
        ],
        [
            'title' => 'Down for maintenance',
            'prompt' => 'Write `maintenanceResponse(bool $down, string $secret, string $path, array $cookies): array` returning `[\'status\' => ..., \'set_cookie\' => bool]`. Not down: 200. Down and the path is `/` followed by the secret: 302 (a redirect home) and set the bypass cookie. Down and the `laravel_maintenance` cookie equals the secret: 200. Otherwise 503. `set_cookie` is only true in the bypass case. Compare the secret with `hash_equals()`.',
            'starter_code' => <<<'PHP'
                <?php

                function maintenanceResponse(bool $down, string $secret, string $path, array $cookies): array
                {
                    return ['status' => $down ? 503 : 200, 'set_cookie' => false];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function maintenanceResponse(bool $down, string $secret, string $path, array $cookies): array
                {
                    if (! $down) {
                        return ['status' => 200, 'set_cookie' => false];
                    }
                    if ($secret !== '' && hash_equals('/' . $secret, $path)) {
                        return ['status' => 302, 'set_cookie' => true];
                    }
                    if ($secret !== '' && isset($cookies['laravel_maintenance']) && hash_equals($secret, (string) $cookies['laravel_maintenance'])) {
                        return ['status' => 200, 'set_cookie' => false];
                    }

                    return ['status' => 503, 'set_cookie' => false];
                }
                PHP,
            'hint' => 'Check in order: not down, the secret path, the cookie, and finally 503.',
            'checks' => [
                ['label' => 'Up, and down', 'code' => "return expect_same([maintenanceResponse(false, 's3', '/books', []), maintenanceResponse(true, 's3', '/books', [])], [['status' => 200, 'set_cookie' => false], ['status' => 503, 'set_cookie' => false]]);"],
                ['label' => 'The bypass', 'code' => "return expect_same([maintenanceResponse(true, 'b7a1c-staff', '/b7a1c-staff', []), maintenanceResponse(true, 'b7a1c-staff', '/books', ['laravel_maintenance' => 'b7a1c-staff']), maintenanceResponse(true, 'b7a1c-staff', '/books', ['laravel_maintenance' => 'guess'])], [['status' => 302, 'set_cookie' => true], ['status' => 200, 'set_cookie' => false], ['status' => 503, 'set_cookie' => false]]);"],
                ['label' => 'Uses hash_equals', 'code' => "return str_contains(learnp_code(), 'hash_equals') || 'Compare the secret with hash_equals().';"],
            ],
        ],
        [
            'title' => 'Which backups to keep',
            'prompt' => 'Write `backupsToKeep(array $dates, int $daily, int $weekly, int $monthly): array` for daily backups named by date (`2026-10-21`). Keep a backup if it is one of the newest `$daily`; or the newest backup of its ISO week (`format(\'o-W\')`), for the newest `$weekly` weeks that have backups; or the newest of its month, for the newest `$monthly` months. Return the kept dates, newest first, without duplicates.',
            'starter_code' => <<<'PHP'
                <?php

                function backupsToKeep(array $dates, int $daily, int $weekly, int $monthly): array
                {
                    rsort($dates);

                    return array_slice($dates, 0, $daily);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function backupsToKeep(array $dates, int $daily, int $weekly, int $monthly): array
                {
                    rsort($dates);
                    $keep = array_slice($dates, 0, $daily);

                    $newestPer = function (string $format, int $count) use ($dates): array {
                        $newest = [];
                        foreach ($dates as $date) {
                            $period = (new DateTimeImmutable($date))->format($format);
                            $newest[$period] ??= $date;
                        }

                        return array_slice(array_values($newest), 0, $count);
                    };

                    $keep = array_unique([...$keep, ...$newestPer('o-W', $weekly), ...$newestPer('Y-m', $monthly)]);
                    rsort($keep);

                    return $keep;
                }
                PHP,
            'hint' => 'Sort newest first. Walking that list, the first date you see for each week (or month) is its newest backup.',
            'checks' => [
                ['label' => 'Daily, weekly and monthly', 'code' => <<<'PHP'
                    $dates = [];
                    for ($d = new DateTimeImmutable('2026-08-01'); $d <= new DateTimeImmutable('2026-10-21'); $d = $d->modify('+1 day')) {
                        $dates[] = $d->format('Y-m-d');
                    }
                    shuffle($dates);
                    return expect_same(backupsToKeep($dates, 3, 3, 3), ['2026-10-21', '2026-10-20', '2026-10-19', '2026-10-18', '2026-10-11', '2026-09-30', '2026-08-31']);
                    PHP],
                ['label' => 'Few backups', 'code' => "return expect_same(backupsToKeep(['2026-10-01', '2026-09-15'], 7, 4, 12), ['2026-10-01', '2026-09-15']);"],
            ],
        ],
    ],
];
