<?php

namespace Database\Seeders;

class PhpSecurityCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-security',
            'title' => 'PHP security',
            'language' => 'php',
            'description' => 'How web apps get attacked and how to stop it, in plain PHP: untrusted input, XSS, SQL injection, passwords, sessions and CSRF, signed tokens, file paths and authorization.',
            'position' => 11,
        ];
    }

    protected function directory(): string
    {
        return 'php-security';
    }

    protected function lessons(): array
    {
        return [
            '01-untrusted-input',
            '02-xss-and-escaping',
            '03-sql-injection',
            '04-passwords',
            '05-sessions-cookies-and-csrf',
            '06-tokens-and-signatures',
            '07-files-and-paths',
            '08-authorization-and-mass-assignment',
        ];
    }
}
