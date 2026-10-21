<?php

// Lesson 5 of the REST APIs course. The theory is in the .md file with the same name.

return [
    'slug' => 'pagination-caching-and-versions',
    'title' => 'Pagination, caching and versions',
    'summary' => 'Large collections a page at a time with offsets or cursors, allow-listed filtering and sorting, HTTP caching with Cache-Control, ETags and 304s, and versioning an API without breaking clients.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try other pages and page sizes.
        $books = array_map(fn ($i) => ['id' => $i, 'title' => "Book {$i}"], range(1, 45));

        $page = 2;
        $perPage = 20;
        $data = array_slice($books, ($page - 1) * $perPage, $perPage);

        echo json_encode([
            'data' => array_column($data, 'id'),
            'meta' => ['current_page' => $page, 'last_page' => (int) ceil(count($books) / $perPage), 'total' => count($books)],
        ]);
        PHP,

    'quiz' => [
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $total = 45;
                $perPage = 20;

                echo (int) ceil($total / $perPage) . ' ' . (3 - 1) * $perPage;
                PHP,
            'options' => ['`2 40`', '`3 40`', '`3 60`', '`2 20`'],
            'correct_option' => 1,
            'explanation' => 'Three pages (20, 20 and 5 rows), and page 3 skips 40 rows.',
        ],
        [
            'question' => 'Why cap `per_page`?',
            'options' => [
                'Because JSON has a size limit',
                'A client could ask for every row in one page, which is the problem pagination was meant to avoid',
                'Laravel can\'t paginate more than 100',
                'To make page numbers shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'Clamp it to a sensible range, such as 1 to 100.',
        ],
        [
            'question' => 'What is the main advantage of cursor pagination over page numbers on a huge, busy table?',
            'options' => [
                'It shows the total count',
                'It stays fast at any depth and doesn\'t skip or repeat rows when rows are added while paging',
                'It lets you jump to any page',
                'It needs no ordering',
            ],
            'correct_option' => 1,
            'explanation' => '`WHERE id > last ORDER BY id LIMIT 20` uses the index directly; the trade-off is no page jumping and no total.',
        ],
        [
            'question' => 'Why is a cursor sent to clients as an opaque string?',
            'options' => [
                'To hide the data',
                'So clients just pass it back, and you can change what it contains without breaking them',
                'Because ids are secret',
                'It is shorter',
            ],
            'correct_option' => 1,
            'explanation' => 'Clients shouldn\'t build or parse cursors themselves.',
        ],
        [
            'question' => 'What does `?sort=-published_at,title` usually mean?',
            'options' => [
                'Remove published_at and sort by title',
                'Newest published first, then by title A to Z',
                'Oldest first, then title Z to A',
                'Only books with no publish date',
            ],
            'correct_option' => 1,
            'explanation' => 'A leading `-` means descending; fields are applied in order.',
        ],
        [
            'question' => 'A client sends `If-None-Match` with the ETag it already has, and the resource hasn\'t changed. What do you answer?',
            'options' => ['`200` with the full body', '`304 Not Modified` with no body', '`204 No Content`', '`412 Precondition Failed`'],
            'correct_option' => 1,
            'explanation' => 'The client reuses its copy, and the body isn\'t sent again.',
        ],
        [
            'question' => 'Which `Cache-Control` suits a response with one user\'s order history?',
            'options' => ['`public, max-age=3600`', '`private` (or `no-store` if it\'s sensitive)', '`immutable`', 'No header at all'],
            'correct_option' => 1,
            'explanation' => '`public` would let shared caches such as CDNs serve it to other people.',
        ],
        [
            'question' => 'Which change needs a new API version?',
            'options' => [
                'Adding a new field to the book JSON',
                'Adding a new endpoint',
                'Renaming the `title` field to `name`',
                'Making a slow endpoint faster',
            ],
            'correct_option' => 2,
            'explanation' => 'Renaming or removing breaks existing clients; adding doesn\'t.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A page of results',
            'prompt' => 'Write `paginate(array $items, int $page, int $perPage): array` returning `[\'data\' => ..., \'meta\' => [\'current_page\', \'last_page\', \'per_page\', \'total\']]`. Clamp `$perPage` to 1–100 and treat a page below 1 as 1. `last_page` is at least 1. A page beyond the last returns an empty `data` list (keeping the requested page number). `data` is a list (keys 0, 1, ...).',
            'starter_code' => <<<'PHP'
                <?php

                function paginate(array $items, int $page, int $perPage): array
                {
                    return [
                        'data' => array_slice($items, $page * $perPage, $perPage),
                        'meta' => ['current_page' => $page, 'last_page' => count($items) / $perPage, 'per_page' => $perPage, 'total' => count($items)],
                    ];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function paginate(array $items, int $page, int $perPage): array
                {
                    $perPage = min(100, max(1, $perPage));
                    $page = max(1, $page);
                    $total = count($items);

                    return [
                        'data' => array_values(array_slice($items, ($page - 1) * $perPage, $perPage)),
                        'meta' => [
                            'current_page' => $page,
                            'last_page' => max(1, (int) ceil($total / $perPage)),
                            'per_page' => $perPage,
                            'total' => $total,
                        ],
                    ];
                }
                PHP,
            'hint' => 'Clamp first. The offset is `($page - 1) * $perPage`, and `ceil()` gives the last page.',
            'checks' => [
                ['label' => 'Pages', 'code' => <<<'PHP'
                    $items = range(1, 45);
                    $page3 = paginate($items, 3, 20);
                    return expect_same([paginate($items, 1, 20)['data'][0], $page3['data'], $page3['meta']], [1, [41, 42, 43, 44, 45], ['current_page' => 3, 'last_page' => 3, 'per_page' => 20, 'total' => 45]]);
                    PHP],
                ['label' => 'Silly input', 'code' => <<<'PHP'
                    $items = range(1, 45);
                    return expect_same([paginate($items, 0, 20)['meta']['current_page'], paginate($items, 1, 1000)['meta']['per_page'], paginate($items, 1, 0)['meta']['per_page'], paginate($items, 9, 20)['data']], [1, 100, 1, []]);
                    PHP],
                ['label' => 'No items', 'code' => "return expect_same(paginate([], 1, 20), ['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0]]);"],
            ],
        ],
        [
            'title' => 'Cursors',
            'prompt' => 'Write `cursorPage(array $rows, ?string $cursor, int $limit): array` for rows sorted by `id`. The cursor is `base64_encode(\'id:N\')`, meaning "rows with an id greater than N"; `null` starts at the beginning. Return `[\'data\' => up to $limit rows, \'next_cursor\' => cursor for the last row returned, or null if there are no more rows]`. A cursor that doesn\'t decode to `id:` followed by digits throws an `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function cursorPage(array $rows, ?string $cursor, int $limit): array
                {
                    $after = $cursor === null ? 0 : (int) $cursor;

                    $data = array_slice($rows, $after, $limit);

                    return ['data' => $data, 'next_cursor' => (string) ($after + $limit)];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function cursorPage(array $rows, ?string $cursor, int $limit): array
                {
                    $after = null;
                    if ($cursor !== null) {
                        $decoded = base64_decode($cursor, true);
                        if ($decoded === false || preg_match('/^id:(\d+)$/', $decoded, $match) !== 1) {
                            throw new InvalidArgumentException('Invalid cursor.');
                        }
                        $after = (int) $match[1];
                    }

                    $remaining = array_values(array_filter($rows, fn ($row) => $after === null || $row['id'] > $after));
                    $data = array_slice($remaining, 0, $limit);
                    $more = count($remaining) > count($data);

                    return [
                        'data' => $data,
                        'next_cursor' => $more && $data !== [] ? base64_encode('id:' . end($data)['id']) : null,
                    ];
                }
                PHP,
            'hint' => 'Decode with `base64_decode($cursor, true)` and check it with a regex. Keep rows with a larger id, take `$limit`, and only give a next cursor if rows are left over.',
            'checks' => [
                ['label' => 'Walks through the rows', 'code' => <<<'PHP'
                    $rows = array_map(fn ($id) => ['id' => $id], [2, 5, 9, 14, 20]);
                    $first = cursorPage($rows, null, 2);
                    $second = cursorPage($rows, $first['next_cursor'], 2);
                    $third = cursorPage($rows, $second['next_cursor'], 2);
                    return expect_same([array_column($first['data'], 'id'), $first['next_cursor'], array_column($second['data'], 'id'), array_column($third['data'], 'id'), $third['next_cursor']], [[2, 5], base64_encode('id:5'), [9, 14], [20], null]);
                    PHP],
                ['label' => 'The last page has no next cursor', 'code' => "return expect_same(cursorPage([['id' => 1], ['id' => 2]], null, 2)['next_cursor'], null);"],
                ['label' => 'Bad cursors', 'code' => <<<'PHP'
                    foreach (['not base64!', base64_encode('page:2'), base64_encode('id:')] as $bad) {
                        $result = expect_throws(InvalidArgumentException::class, fn () => cursorPage([['id' => 1]], $bad, 10));
                        if ($result !== true) return $result;
                    }
                    return true;
                    PHP],
            ],
        ],
        [
            'title' => 'Sort, safely',
            'prompt' => 'Write `parseSort(?string $sort, array $allowed): array` for `?sort=-published_at,title`. Return a list of `[column, \'asc\'|\'desc\']` pairs in order (a leading `-` means `desc`). Ignore empty parts and spaces around names. Throw an `InvalidArgumentException` (`Cannot sort by [x].`) for a column not in `$allowed`. `null` or empty gives `[]`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseSort(?string $sort, array $allowed): array
                {
                    return array_map(fn ($f) => [$f, 'asc'], explode(',', (string) $sort));
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseSort(?string $sort, array $allowed): array
                {
                    $result = [];
                    foreach (explode(',', (string) $sort) as $field) {
                        $field = trim($field);
                        if ($field === '') {
                            continue;
                        }

                        $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
                        $column = ltrim($field, '-');
                        if (! in_array($column, $allowed, true)) {
                            throw new InvalidArgumentException("Cannot sort by [{$column}].");
                        }

                        $result[] = [$column, $direction];
                    }

                    return $result;
                }
                PHP,
            'hint' => 'For each part: trim, skip empties, read the `-`, strip it with `ltrim()`, and check the allow-list.',
            'checks' => [
                ['label' => 'Parses the fields', 'code' => "return expect_same(parseSort('-published_at, title,,price', ['title', 'published_at', 'price']), [['published_at', 'desc'], ['title', 'asc'], ['price', 'asc']]);"],
                ['label' => 'Nothing to sort', 'code' => "return expect_same([parseSort(null, ['title']), parseSort('', ['title'])], [[], []]);"],
                ['label' => 'Not allowed', 'code' => <<<'PHP'
                    try {
                        parseSort('title,-password', ['title']);
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), 'Cannot sort by [password].');
                    }
                    return 'Sorting by a column outside the allow-list should throw.';
                    PHP],
            ],
        ],
        [
            'title' => 'Filters',
            'prompt' => 'Write `applyFilters(array $rows, array $filters, array $allowed): array` for `?filter[status]=published,draft&filter[author_id]=3`. Keep rows that match every filter, where a filter\'s comma-separated values mean "any of these" (compared as strings). Throw an `InvalidArgumentException` (`Cannot filter by [x].`) for a filter not in `$allowed`. Return a list.',
            'starter_code' => <<<'PHP'
                <?php

                function applyFilters(array $rows, array $filters, array $allowed): array
                {
                    foreach ($filters as $column => $value) {
                        $rows = array_filter($rows, fn ($row) => $row[$column] == $value);
                    }

                    return $rows;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function applyFilters(array $rows, array $filters, array $allowed): array
                {
                    foreach ($filters as $column => $value) {
                        if (! in_array($column, $allowed, true)) {
                            throw new InvalidArgumentException("Cannot filter by [{$column}].");
                        }

                        $accepted = array_map('trim', explode(',', (string) $value));
                        $rows = array_filter($rows, fn ($row) => in_array((string) ($row[$column] ?? ''), $accepted, true));
                    }

                    return array_values($rows);
                }
                PHP,
            'hint' => 'Split each filter on commas, then keep rows whose value (as a string) is in that list. `array_values()` at the end.',
            'checks' => [
                ['label' => 'Every filter, any value', 'code' => <<<'PHP'
                    $rows = [['id' => 1, 'status' => 'published', 'author_id' => 3], ['id' => 2, 'status' => 'draft', 'author_id' => 3], ['id' => 3, 'status' => 'archived', 'author_id' => 3], ['id' => 4, 'status' => 'published', 'author_id' => 5]];
                    return expect_same(array_column(applyFilters($rows, ['status' => 'published,draft', 'author_id' => '3'], ['status', 'author_id']), 'id'), [1, 2]);
                    PHP],
                ['label' => 'No filters', 'code' => "return expect_same(applyFilters([['id' => 1]], [], ['status']), [['id' => 1]]);"],
                ['label' => 'Not allowed', 'code' => "return expect_throws(InvalidArgumentException::class, fn () => applyFilters([['id' => 1, 'email' => 'x']], ['email' => 'x'], ['status']));"],
            ],
        ],
        [
            'title' => 'ETags and 304',
            'prompt' => 'Write `conditionalGet(array $body, array $headers): array` returning `[\'status\', \'headers\', \'body\']`. The ETag is `"` + `md5(json_encode($body))` + `"` (with the quotes). If the request\'s `If-None-Match` header (any capitalisation of the name) is `*`, or is a comma-separated list containing the ETag, answer 304 with an empty string body. Otherwise answer 200 with `json_encode($body)`. Both carry the `ETag` header.',
            'starter_code' => <<<'PHP'
                <?php

                function conditionalGet(array $body, array $headers): array
                {
                    return ['status' => 200, 'headers' => [], 'body' => json_encode($body)];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function conditionalGet(array $body, array $headers): array
                {
                    $json = json_encode($body);
                    $etag = '"' . md5($json) . '"';
                    $ifNoneMatch = array_change_key_case($headers, CASE_LOWER)['if-none-match'] ?? null;

                    if ($ifNoneMatch !== null) {
                        $tags = array_map('trim', explode(',', $ifNoneMatch));
                        if (in_array('*', $tags, true) || in_array($etag, $tags, true)) {
                            return ['status' => 304, 'headers' => ['ETag' => $etag], 'body' => ''];
                        }
                    }

                    return ['status' => 200, 'headers' => ['ETag' => $etag], 'body' => $json];
                }
                PHP,
            'hint' => 'Compute the ETag first. Split `If-None-Match` on commas and trim each tag.',
            'checks' => [
                ['label' => 'A first request gets the body', 'code' => <<<'PHP'
                    $body = ['data' => ['id' => 7, 'title' => 'Dune']];
                    $etag = '"' . md5(json_encode($body)) . '"';
                    return expect_same(conditionalGet($body, []), ['status' => 200, 'headers' => ['ETag' => $etag], 'body' => json_encode($body)]);
                    PHP],
                ['label' => 'A matching ETag gets 304', 'code' => <<<'PHP'
                    $body = ['data' => ['id' => 7, 'title' => 'Dune']];
                    $etag = '"' . md5(json_encode($body)) . '"';
                    return expect_same([conditionalGet($body, ['If-None-Match' => $etag]), conditionalGet($body, ['if-none-match' => "\"old\", {$etag}"])['status'], conditionalGet($body, ['If-None-Match' => '*'])['status']], [['status' => 304, 'headers' => ['ETag' => $etag], 'body' => ''], 304, 304]);
                    PHP],
                ['label' => 'A stale ETag gets the new body', 'code' => "return expect_same(conditionalGet(['data' => []], ['If-None-Match' => '\"stale\"'])['status'], 200);"],
            ],
        ],
        [
            'title' => 'Which version',
            'prompt' => 'Write `apiVersion(string $path, array $headers, array $supported, int $default): int`. A path like `/api/v2/books` names the version. Otherwise an `Accept` header (any capitalisation of the name) containing `application/vnd.cornerbooks.vN+json` does. Otherwise use `$default`. If the chosen version isn\'t in `$supported`, throw an `InvalidArgumentException` (`API version N is not supported.`).',
            'starter_code' => <<<'PHP'
                <?php

                function apiVersion(string $path, array $headers, array $supported, int $default): int
                {
                    return $default;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function apiVersion(string $path, array $headers, array $supported, int $default): int
                {
                    $version = $default;
                    $accept = array_change_key_case($headers, CASE_LOWER)['accept'] ?? '';

                    if (preg_match('#^/api/v(\d+)(/|$)#', $path, $match) === 1) {
                        $version = (int) $match[1];
                    } elseif (preg_match('#application/vnd\.cornerbooks\.v(\d+)\+json#', $accept, $match) === 1) {
                        $version = (int) $match[1];
                    }

                    if (! in_array($version, $supported, true)) {
                        throw new InvalidArgumentException("API version {$version} is not supported.");
                    }

                    return $version;
                }
                PHP,
            'hint' => 'Two regexes, tried in order: `#^/api/v(\d+)(/|$)#` on the path, then one on the Accept header.',
            'checks' => [
                ['label' => 'From the path', 'code' => "return expect_same([apiVersion('/api/v2/books', [], [1, 2], 1), apiVersion('/api/v1', ['Accept' => 'application/vnd.cornerbooks.v2+json'], [1, 2], 2)], [2, 1]);"],
                ['label' => 'From the header, or the default', 'code' => "return expect_same([apiVersion('/api/books', ['accept' => 'application/vnd.cornerbooks.v2+json'], [1, 2], 1), apiVersion('/api/books', ['Accept' => 'application/json'], [1, 2], 1), apiVersion('/api/v2x/books', [], [1, 2], 1)], [2, 1, 1]);"],
                ['label' => 'Unsupported versions', 'code' => <<<'PHP'
                    try {
                        apiVersion('/api/v3/books', [], [1, 2], 2);
                    } catch (InvalidArgumentException $e) {
                        return expect_same($e->getMessage(), 'API version 3 is not supported.');
                    }
                    return 'Version 3 should be refused.';
                    PHP],
            ],
        ],
    ],
];
