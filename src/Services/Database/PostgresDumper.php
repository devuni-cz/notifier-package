<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Database;

use Devuni\Notifier\Interfaces\DatabaseDumperInterface;
use Devuni\Notifier\Services\NotifierLoggerService;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final class PostgresDumper implements DatabaseDumperInterface
{
    /**
     * @param  string  $connection  Laravel database connection name (e.g. "pgsql").
     */
    public function __construct(
        private readonly string $connection,
        private readonly NotifierLoggerService $notifierLogger,
    ) {}

    /**
     * Available if either ysql_dump (YugabyteDB) or pg_dump (PostgreSQL) is on PATH.
     */
    public static function isAvailable(): bool
    {
        return self::binaryAvailable('ysql_dump') || self::binaryAvailable('pg_dump');
    }

    public function dump(string $outputPath): void
    {
        $logger = $this->notifierLogger->get();

        $config = config("database.connections.{$this->connection}");

        if (! is_array($config)) {
            throw new RuntimeException("Database connection '{$this->connection}' is not configured.");
        }

        $database = $config['database'] ?? null;

        if (empty($database)) {
            throw new RuntimeException("Database connection '{$this->connection}' has no database name configured.");
        }

        $binary = $this->resolveBinary();
        $schema = (string) config('notifier.postgres_schema', 'public');

        $command = [
            $binary,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? 5432),
            '--username='.($config['username'] ?? ''),
            '--dbname='.$database,
            '--file='.$outputPath,
            '--no-owner',     // dumps without ownership info - restores cleanly into a different role
            '--no-privileges', // dumps without GRANT/REVOKE statements
            '--no-password',  // never prompt; rely on PGPASSWORD env var
        ];

        // Exclusion: user writes plain table names; we prefix with the configured schema here.
        // Fully-qualified names (containing a dot) are passed through as-is.
        foreach (config('notifier.excluded_tables', []) as $table) {
            $qualified = str_contains((string) $table, '.') ? (string) $table : $schema.'.'.$table;
            $command[] = '--exclude-table='.$qualified;
        }

        $process = new Process($command);
        $process->setTimeout(600);
        // Password via PGPASSWORD env var (not argv) - same rationale as MYSQL_PWD: keeps
        // it out of /proc/*/cmdline and `ps` output on shared hosts.
        $process->setEnv(['PGPASSWORD' => (string) ($config['password'] ?? '')]);
        $process->run();

        if (! $process->isSuccessful()) {
            $logger->error('❌ '.$binary.' failed', [
                'exitCode' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new RuntimeException('Database backup failed: '.$process->getErrorOutput());
        }
    }

    public function describe(): string
    {
        $binary = $this->resolveBinary();
        $process = new Process([$binary, '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            return $binary.' (version unknown)';
        }

        return mb_trim($process->getOutput());
    }

    private static function binaryAvailable(string $binary): bool
    {
        $process = new Process([$binary, '--version']);

        try {
            $process->run();
        } catch (Throwable) {
            return false;
        }

        return $process->isSuccessful();
    }

    /**
     * Resolve which Postgres-compatible dump binary to use.
     *
     * Resolution order:
     * 1. Explicit config override (`notifier.postgres_dump_binary`)
     * 2. ysql_dump if installed (YugabyteDB fork - preferred for YSQL deployments)
     * 3. pg_dump (standard PostgreSQL)
     *
     * @throws RuntimeException When no compatible binary is installed.
     */
    private function resolveBinary(): string
    {
        $override = config('notifier.postgres_dump_binary');

        if (! empty($override)) {
            if (! self::binaryAvailable((string) $override)) {
                throw new RuntimeException(
                    "Configured postgres_dump_binary '{$override}' is not available on PATH."
                );
            }

            return (string) $override;
        }

        if (self::binaryAvailable('ysql_dump')) {
            return 'ysql_dump';
        }

        if (self::binaryAvailable('pg_dump')) {
            return 'pg_dump';
        }

        throw new RuntimeException(
            'Neither ysql_dump nor pg_dump is installed. '
            .'Install PostgreSQL client tools (e.g. `apt install postgresql-client`) '
            .'or YugabyteDB tools (https://docs.yugabyte.com).'
        );
    }
}
