<?php

namespace App\Runner;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * The disable_functions list for learner code. It's built from the child
 * binary's own function list, because `php -n` still has every extension that
 * was compiled in, and this web process may have a different set (pcntl is often
 * CLI-only). disable_functions has no wildcards, so prefixed families are expanded here.
 */
final class DisabledFunctions
{
    /** Run programs, change the process or its settings, send mail, touch links or permissions, resolve hosts, open sockets. */
    private const NAMES = [
        'exec', 'shell_exec', 'system', 'passthru', 'popen',
        'putenv', 'ini_set', 'ini_alter', 'ini_restore', 'set_time_limit', 'ignore_user_abort', 'dl',
        'mail', 'mb_send_mail', 'error_log', 'syslog', 'openlog',
        'link', 'symlink', 'chmod', 'chown', 'chgrp', 'lchown', 'lchgrp', 'touch',
        'fsockopen', 'pfsockopen',
        'gethostbyname', 'gethostbynamel', 'gethostbyaddr', 'dns_get_record', 'dns_check_record', 'checkdnsrr', 'getmxrr', 'dns_get_mx',
        'mysqli_connect', 'mysqli_real_connect', 'pg_connect', 'pg_pconnect',
    ];

    private const PREFIXES = [
        'proc_', 'pcntl_', 'posix_', 'socket_', 'stream_socket_', 'curl_', 'ftp_',
        'shmop_', 'sem_', 'shm_', 'msg_', 'ldap_', 'snmp', 'odbc_', 'imap_',
    ];

    /** @var array<string, list<string>> */
    private static array $cache = [];

    /**
     * @return list<string>
     */
    public static function for(string $phpBinary): array
    {
        return self::$cache[$phpBinary] ??= self::build($phpBinary);
    }

    /**
     * @return list<string>
     */
    private static function build(string $phpBinary): array
    {
        $process = new Process([$phpBinary, '-n', '-r', 'echo json_encode(get_defined_functions()["internal"]);']);
        $process->mustRun();

        $prefixed = array_filter(
            json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR),
            fn (string $function) => Str::startsWith($function, self::PREFIXES),
        );

        return array_values(array_unique([...self::NAMES, ...$prefixed]));
    }
}
