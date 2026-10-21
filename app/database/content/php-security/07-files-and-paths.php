<?php

// Lesson 7 of the PHP security course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'files-and-paths',
    'title' => 'Files and paths',
    'summary' => 'Uploads and downloads without surprises: path traversal and resolving paths, allow-listed extensions, checking magic bytes instead of trusting the browser, random stored names and safe download headers.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try your own sneaky file names.
        $base = 'storage/invoices';
        $requests = ['2026-03.pdf', '../../.env', 'march/../2026-03.pdf', '..%2F..%2F.env'];

        foreach ($requests as $name) {
            echo "$base/$name" . (str_contains($name, '..') ? '  <- climbs out of the folder?' : '') . PHP_EOL;
        }
        PHP,

    'quiz' => [
        [
            'question' => 'A download route reads `\'storage/invoices/\' . $_GET[\'file\']`. What can `?file=../../.env` do?',
            'options' => [
                'Nothing, the folder is fixed',
                'Read the app\'s `.env` file, two folders up, with all its secrets',
                'Delete the invoices',
                'Upload a file',
            ],
            'correct_option' => 1,
            'explanation' => 'Each `..` climbs one folder. That\'s path traversal.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $parts = [];
                foreach (explode('/', 'a/./b//c/../d') as $segment) {
                    if ($segment === '' || $segment === '.') continue;
                    if ($segment === '..') { array_pop($parts); continue; }
                    $parts[] = $segment;
                }

                echo implode('/', $parts);
                PHP,
            'options' => ['`a/b/c/d`', '`a/b/d`', '`a/d`', '`a/./b//d`'],
            'correct_option' => 1,
            'explanation' => 'Empty and `.` segments are skipped, and `..` removes `c`, leaving `a/b/d`.',
        ],
        [
            'question' => 'What is the safest way to let users download their invoices?',
            'options' => [
                'Use the file name from the URL after removing `..`',
                'Look the file up by an id in the database and check it belongs to the user',
                'Put the invoices in the public folder',
                'Base64-encode the path in the URL',
            ],
            'correct_option' => 1,
            'explanation' => 'If the request never contains a path, it can\'t contain a malicious one.',
        ],
        [
            'question' => 'An upload arrives with the name `cover.jpg` and type `image/jpeg`. What do these tell you?',
            'options' => [
                'That it\'s a JPEG',
                'Only what the visitor\'s browser claimed; both can be anything',
                'That it\'s safe to store',
                'That it was virus-checked',
            ],
            'correct_option' => 1,
            'explanation' => 'Check the contents (magic bytes) and pick the name and type yourself.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $name = 'cover.PHP.jpg';

                $parts = explode('.', strtolower($name));
                echo end($parts) . ' ' . implode(',', array_slice($parts, 1, -1));
                PHP,
            'options' => ['`jpg php`', '`php jpg`', '`jpg `', '`PHP.jpg php`'],
            'correct_option' => 0,
            'explanation' => 'The last extension is `jpg`, but there\'s a `php` part hiding in the middle, which some servers would still run.',
        ],
        [
            'question' => 'Why store uploads under a random name you generate, rather than the uploaded name?',
            'options' => [
                'Random names are shorter',
                'The uploaded name could contain paths, odd characters or a dangerous extension, and could overwrite another file',
                'It makes uploads faster',
                'Browsers require it',
            ],
            'correct_option' => 1,
            'explanation' => 'Keep the original name in the database for display, and never use it as a path.',
        ],
        [
            'question' => 'What does `X-Content-Type-Options: nosniff` do?',
            'options' => [
                'Hides the file type',
                'Stops browsers guessing the type from the contents, so a file is never treated as HTML when you said it wasn\'t',
                'Compresses the response',
                'Prevents downloading',
            ],
            'correct_option' => 1,
            'explanation' => 'Without it, a browser might decide your "text file" is a web page and run its scripts.',
        ],
        [
            'question' => 'What is "zip slip"?',
            'options' => [
                'A slow ZIP download',
                'A ZIP entry named like `../../public/shell.php` that gets written outside the folder it was unpacked into',
                'A corrupt ZIP file',
                'A ZIP bomb',
            ],
            'correct_option' => 1,
            'explanation' => 'Entry names are paths chosen by whoever made the archive, so they need the same checks.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Resolve a path',
            'prompt' => 'Write `resolvePath(string $path): ?string`, which resolves a relative path on the string alone: skip empty and `.` segments, and let `..` remove the previous segment. If a `..` would climb above the start, return `null`. Also return `null` for absolute paths (starting with `/`), paths containing a backslash or a null byte, and paths that resolve to nothing. Otherwise return the clean path joined with `/`.',
            'starter_code' => <<<'PHP'
                <?php

                function resolvePath(string $path): ?string
                {
                    return str_replace('../', '', $path);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function resolvePath(string $path): ?string
                {
                    if (str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
                        return null;
                    }

                    $parts = [];
                    foreach (explode('/', $path) as $segment) {
                        if ($segment === '' || $segment === '.') {
                            continue;
                        }
                        if ($segment === '..') {
                            if ($parts === []) {
                                return null;
                            }
                            array_pop($parts);
                            continue;
                        }
                        $parts[] = $segment;
                    }

                    return $parts === [] ? null : implode('/', $parts);
                }
                PHP,
            'hint' => 'Walk the segments as in the lesson, but return `null` when a `..` arrives and `$parts` is already empty. Check for `/`, `\\` and `"\0"` before you start.',
            'checks' => [
                ['label' => 'Clean paths', 'code' => "return expect_same([resolvePath('invoices/./2026//march.pdf'), resolvePath('a/b/../c'), resolvePath('march.pdf')], ['invoices/2026/march.pdf', 'a/c', 'march.pdf']);"],
                ['label' => 'Climbing out is refused', 'code' => "return expect_same([resolvePath('../.env'), resolvePath('invoices/../../.env'), resolvePath('a/../../b'), resolvePath('..')], [null, null, null, null]);"],
                ['label' => 'Absolute, Windows and null-byte paths are refused', 'code' => "return expect_same([resolvePath('/etc/passwd'), resolvePath('..\\\\..\\\\.env'), resolvePath(\"march.pdf\\0.png\")], [null, null, null]);"],
                ['label' => 'Nothing left is refused', 'code' => "return expect_same([resolvePath(''), resolvePath('./.'), resolvePath('a/..')], [null, null, null]);"],
            ],
        ],
        [
            'title' => 'Allowed uploads',
            'prompt' => 'Write `uploadExtension(string $filename): ?string`. Lower-case the name and split it at dots. Return the last extension if it is one of `jpg`, `jpeg`, `png`, `gif`, `webp` or `pdf`, there is at least one character before the first dot, and no other part is a script extension (`php`, `phtml`, `phar`, `php5`, `cgi`). Return `null` otherwise, including for names with a null byte, a `/` or no extension.',
            'starter_code' => <<<'PHP'
                <?php

                function uploadExtension(string $filename): ?string
                {
                    return pathinfo($filename, PATHINFO_EXTENSION);
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function uploadExtension(string $filename): ?string
                {
                    if (str_contains($filename, "\0") || str_contains($filename, '/')) {
                        return null;
                    }

                    $parts = explode('.', strtolower($filename));
                    if (count($parts) < 2 || $parts[0] === '') {
                        return null;
                    }

                    $extension = array_pop($parts);
                    if (array_intersect($parts, ['php', 'phtml', 'phar', 'php5', 'cgi']) !== []) {
                        return null;
                    }

                    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'], true) ? $extension : null;
                }
                PHP,
            'hint' => '`explode(\'.\', strtolower($filename))`, then `array_pop()` the last part. `array_intersect()` finds script extensions among the rest.',
            'checks' => [
                ['label' => 'Allowed files', 'code' => "return expect_same(array_map('uploadExtension', ['cover.jpg', 'Scan.PDF', 'my.holiday.webp', 'a.jpeg']), ['jpg', 'pdf', 'webp', 'jpeg']);"],
                ['label' => 'Scripts are refused', 'code' => "return expect_same(array_map('uploadExtension', ['shell.php', 'cover.php.jpg', 'x.PHTML.png', 'a.phar.gif']), [null, null, null, null]);"],
                ['label' => 'Other types are refused', 'code' => "return expect_same(array_map('uploadExtension', ['page.html', 'image.svg', 'archive.zip', 'README']), [null, null, null, null]);"],
                ['label' => 'Tricky names are refused', 'code' => "return expect_same(array_map('uploadExtension', [\"x.php\\0.jpg\", '../cover.jpg', '.jpg', 'cover.']), [null, null, null, null]);"],
            ],
        ],
        [
            'title' => 'What is it really?',
            'prompt' => 'Write `detectType(string $bytes): ?string`, returning a MIME type from the first bytes of a file: `image/png` for `\x89PNG\r\n\x1a\n`, `image/jpeg` for `\xFF\xD8\xFF`, `image/gif` for `GIF87a` or `GIF89a`, `application/pdf` for `%PDF-`, and `image/webp` for `RIFF`, any 4 bytes, then `WEBP`. Anything else gives `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function detectType(string $bytes): ?string
                {
                    return 'image/jpeg';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function detectType(string $bytes): ?string
                {
                    return match (true) {
                        str_starts_with($bytes, "\x89PNG\r\n\x1a\n") => 'image/png',
                        str_starts_with($bytes, "\xFF\xD8\xFF") => 'image/jpeg',
                        str_starts_with($bytes, 'GIF87a'), str_starts_with($bytes, 'GIF89a') => 'image/gif',
                        str_starts_with($bytes, '%PDF-') => 'application/pdf',
                        str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'image/webp',
                        default => null,
                    };
                }
                PHP,
            'hint' => 'Use `str_starts_with()` with double-quoted strings so escapes like `"\x89"` become real bytes. For WebP, `substr($bytes, 8, 4)` reads bytes 9 to 12.',
            'checks' => [
                ['label' => 'Images are recognised', 'code' => "return expect_same([detectType(\"\\x89PNG\\r\\n\\x1a\\n....\"), detectType(\"\\xFF\\xD8\\xFF\\xE0..\"), detectType('GIF89a...'), detectType('GIF87a'), detectType(\"RIFF\\x10\\x00\\x00\\x00WEBPVP8 \")], ['image/png', 'image/jpeg', 'image/gif', 'image/gif', 'image/webp']);"],
                ['label' => 'PDFs are recognised', 'code' => "return expect_same(detectType('%PDF-1.7 ...'), 'application/pdf');"],
                ['label' => 'Disguised scripts are not', 'code' => "return expect_same([detectType(\"<?php system(\\\$_GET['c']); ?>\"), detectType('<svg onload=alert(1)>'), detectType(''), detectType(\"RIFF\\x10\\x00\\x00\\x00WAVE\")], [null, null, null, null]);"],
                ['label' => 'Magic bytes must be at the start', 'code' => 'return expect_same(detectType("junk\\x89PNG\\r\\n\\x1a\\n"), null);'],
            ],
        ],
        [
            'title' => 'Name it yourself',
            'prompt' => 'Write `storagePath(string $extension): string`, returning a path like `uploads/7f/3a/7f3a9c...e1.png`: a random name of 40 hex characters (from `random_bytes(20)`), stored in two folders named after its first two and next two characters, with the given extension in lower case. Only the extensions `jpg`, `png`, `gif`, `webp` and `pdf` (any case) are allowed; others throw `InvalidArgumentException`.',
            'starter_code' => <<<'PHP'
                <?php

                function storagePath(string $extension): string
                {
                    return 'uploads/' . time() . '.' . $extension;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function storagePath(string $extension): string
                {
                    $extension = strtolower($extension);
                    if (! in_array($extension, ['jpg', 'png', 'gif', 'webp', 'pdf'], true)) {
                        throw new InvalidArgumentException("Extension $extension is not allowed.");
                    }

                    $name = bin2hex(random_bytes(20));

                    return sprintf('uploads/%s/%s/%s.%s', substr($name, 0, 2), substr($name, 2, 2), $name, $extension);
                }
                PHP,
            'hint' => '`bin2hex(random_bytes(20))` gives 40 hex characters; `substr($name, 0, 2)` and `substr($name, 2, 2)` give the folder names.',
            'checks' => [
                ['label' => 'The path has the right shape', 'code' => "\$path = storagePath('PNG');\nif (preg_match('#^uploads/([a-f0-9]{2})/([a-f0-9]{2})/(([a-f0-9]{40}))\\.png\$#', \$path, \$m) !== 1) return \"Unexpected path: \$path\";\nreturn str_starts_with(\$m[3], \$m[1] . \$m[2]) ?: 'The folders should be the first four characters of the name.';"],
                ['label' => 'Names are random', 'code' => "\$paths = array_map(fn () => storagePath('pdf'), range(1, 5));\nreturn count(array_unique(\$paths)) === 5 ?: 'Two uploads got the same name.';"],
                ['label' => 'Other extensions throw', 'code' => "foreach (['php', 'html', 'svg', 'jpg/../../x', ''] as \$ext) {\n    \$r = expect_throws(InvalidArgumentException::class, fn () => storagePath(\$ext));\n    if (\$r !== true) return \"For '\$ext': \$r\";\n}\nreturn true;"],
            ],
        ],
        [
            'title' => 'Stay inside the folder',
            'prompt' => 'Write `safePath(string $base, string $userPath): ?string`, joining a user-supplied path onto a base folder only if it stays inside it. The resolver from the first challenge is given as `resolvePath()`. Return `$base` without a trailing slash, a `/`, then the resolved path; or `null` if the path doesn\'t resolve. ZIP entries go through the same function, so `../../public/shell.php` must be refused.',
            'starter_code' => <<<'PHP'
                <?php

                function resolvePath(string $path): ?string
                {
                    if (str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
                        return null;
                    }

                    $parts = [];
                    foreach (explode('/', $path) as $segment) {
                        if ($segment === '' || $segment === '.') {
                            continue;
                        }
                        if ($segment === '..') {
                            if ($parts === []) {
                                return null;
                            }
                            array_pop($parts);
                            continue;
                        }
                        $parts[] = $segment;
                    }

                    return $parts === [] ? null : implode('/', $parts);
                }

                function safePath(string $base, string $userPath): ?string
                {
                    return $base . '/' . $userPath;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function resolvePath(string $path): ?string
                {
                    if (str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
                        return null;
                    }

                    $parts = [];
                    foreach (explode('/', $path) as $segment) {
                        if ($segment === '' || $segment === '.') {
                            continue;
                        }
                        if ($segment === '..') {
                            if ($parts === []) {
                                return null;
                            }
                            array_pop($parts);
                            continue;
                        }
                        $parts[] = $segment;
                    }

                    return $parts === [] ? null : implode('/', $parts);
                }

                function safePath(string $base, string $userPath): ?string
                {
                    $resolved = resolvePath($userPath);

                    return $resolved === null ? null : rtrim($base, '/') . '/' . $resolved;
                }
                PHP,
            'hint' => 'Resolve first; if that gives `null`, so does `safePath()`. `rtrim($base, \'/\')` avoids a double slash.',
            'checks' => [
                ['label' => 'Paths inside the folder', 'code' => "return expect_same([safePath('storage/invoices', '2026/march.pdf'), safePath('storage/invoices/', 'a/../b.pdf')], ['storage/invoices/2026/march.pdf', 'storage/invoices/b.pdf']);"],
                ['label' => 'Traversal is refused', 'code' => "return expect_same([safePath('storage/invoices', '../../.env'), safePath('storage/invoices', '2026/../../secret.txt')], [null, null]);"],
                ['label' => 'Zip slip is refused', 'code' => "return expect_same([safePath('storage/unpacked', '../../public/shell.php'), safePath('storage/unpacked', '/var/www/shell.php'), safePath('storage/unpacked', 'images/cover.png')], [null, null, 'storage/unpacked/images/cover.png']);"],
            ],
        ],
        [
            'title' => 'Download headers',
            'prompt' => 'Write `contentDisposition(string $filename): string` for downloads, returning `attachment; filename="..."; filename*=UTF-8\'\'...`. The first, plain name replaces every byte outside printable ASCII (32 to 126), and every `"`, `\\`, `/` and `%`, with `_`. The second is the whole name encoded with `rawurlencode()` after replacing `/` and `\\` with `_`. Line breaks must never reach the header.',
            'starter_code' => <<<'PHP'
                <?php

                function contentDisposition(string $filename): string
                {
                    return 'attachment; filename="' . $filename . '"';
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function contentDisposition(string $filename): string
                {
                    $name = str_replace(['/', '\\'], '_', $filename);
                    $fallback = preg_replace('/[^\x20-\x7e]|["\\\\%]/', '_', $name);

                    return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, rawurlencode($name));
                }
                PHP,
            'hint' => 'Replace `/` and `\\` first. For the plain name, `preg_replace(\'/[^\x20-\x7e]|["\\\\\\\\%]/\', \'_\', ...)` swaps anything unsafe, byte by byte.',
            'checks' => [
                ['label' => 'Plain names', 'code' => "return expect_same(contentDisposition('invoice-7.pdf'), \"attachment; filename=\\\"invoice-7.pdf\\\"; filename*=UTF-8''invoice-7.pdf\");"],
                ['label' => 'Non-ASCII names', 'code' => "return expect_same(contentDisposition('Bücher.pdf'), \"attachment; filename=\\\"B__cher.pdf\\\"; filename*=UTF-8''B%C3%BCcher.pdf\");"],
                ['label' => 'Quotes and slashes', 'code' => "return expect_same(contentDisposition('a\"b/c\\\\d%.pdf'), \"attachment; filename=\\\"a_b_c_d_.pdf\\\"; filename*=UTF-8''a%22b_c_d%25.pdf\");"],
                ['label' => 'No line breaks get through', 'code' => "\$header = contentDisposition(\"x.pdf\\r\\nSet-Cookie: session=evil\");\nreturn ! preg_match('/[\\r\\n]/', \$header) ?: 'A line break reached the header.';"],
            ],
        ],
    ],
];
