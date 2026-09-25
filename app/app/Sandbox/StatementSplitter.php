<?php

namespace App\Sandbox;

/**
 * Splits a SQL script into individual statements, respecting string literals,
 * quoted identifiers, comments and CREATE TRIGGER ... BEGIN ... END bodies.
 */
final class StatementSplitter
{
    /**
     * Each statement's `keywords` holds its upper-cased words with literals and
     * comments removed, so it can be checked for forbidden commands.
     *
     * @return list<array{sql: string, keywords: list<string>}>
     */
    public static function split(string $script): array
    {
        $statements = [];
        $original = '';
        $bare = '';
        $length = strlen($script);

        for ($i = 0; $i < $length; $i++) {
            $char = $script[$i];
            $next = $script[$i + 1] ?? '';

            if ($char === '-' && $next === '-') {
                $end = strpos($script, "\n", $i);
                $end = $end === false ? $length : $end;
                $original .= substr($script, $i, $end - $i);
                $bare .= ' ';
                $i = $end - 1;

                continue;
            }

            if ($char === '/' && $next === '*') {
                $end = strpos($script, '*/', $i + 2);
                $end = $end === false ? $length : $end + 2;
                $original .= substr($script, $i, $end - $i);
                $bare .= ' ';
                $i = $end - 1;

                continue;
            }

            $closing = match ($char) {
                "'" => "'",
                '"' => '"',
                '`' => '`',
                '[' => ']',
                default => null,
            };

            if ($closing !== null) {
                $end = $i + 1;
                while ($end < $length) {
                    if ($script[$end] === $closing) {
                        // Doubled quote characters are escapes inside quoted text.
                        if ($closing !== ']' && ($script[$end + 1] ?? '') === $closing) {
                            $end += 2;

                            continue;
                        }
                        break;
                    }
                    $end++;
                }
                $end = min($end + 1, $length);
                $original .= substr($script, $i, $end - $i);
                $bare .= $char === "'" ? " '' " : ' _ident_ ';
                $i = $end - 1;

                continue;
            }

            if ($char === ';' && ! self::insideTriggerBody($bare)) {
                self::push($statements, $original, $bare);
                $original = '';
                $bare = '';

                continue;
            }

            $original .= $char;
            $bare .= $char;
        }

        self::push($statements, $original, $bare);

        return $statements;
    }

    private static function insideTriggerBody(string $bare): bool
    {
        return preg_match('/^\s*CREATE\s+(TEMP\s+|TEMPORARY\s+)?TRIGGER\b/i', $bare) === 1
            && preg_match('/\bEND\s*$/i', $bare) !== 1;
    }

    private static function push(array &$statements, string $original, string $bare): void
    {
        $sql = trim($original);
        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $bare, $matches);

        if ($sql === '' || $matches[0] === []) {
            return;
        }

        $statements[] = [
            'sql' => $sql,
            'keywords' => array_map('strtoupper', $matches[0]),
        ];
    }
}
