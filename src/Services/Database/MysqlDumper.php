<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Database;

use Devuni\Notifier\Interfaces\DatabaseDumperInterface;
use Devuni\Notifier\Services\NotifierLoggerService;
use RuntimeException;
use Symfony\Component\Process\Process;

final class MysqlDumper implements DatabaseDumperInterface
{
    /**
     * @param  string  $connection  Laravel database connection name (e.g. "mysql").
     */
    public function __construct(
        private readonly string $connection,
        private readonly NotifierLoggerService $notifierLogger,
    ) {}

    public static function isAvailable(): bool
    {
        $process = new Process(['mysqldump', '--version']);
        $process->run();

        return $process->isSuccessful();
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

        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--single-transaction',
            '--quick',
            '--user='.($config['username'] ?? ''),
            '--port='.($config['port'] ?? 3306),
            '--host='.($config['host'] ?? '127.0.0.1'),
        ];

        // Exclusion: user writes plain table names; we prefix with database here.
        // Fully-qualified names (containing a dot) are passed through as-is.
        foreach (config('notifier.excluded_tables', []) as $table) {
            $qualified = str_contains((string) $table, '.') ? (string) $table : $database.'.'.$table;
            $command[] = '--ignore-table='.$qualified;
        }

        $command[] = '--result-file='.$outputPath;
        $command[] = $database;

        $process = new Process($command);
        $process->setTimeout(600);
        // Password via env var (not argv) to keep it out of /proc/*/cmdline and `ps` output.
        $process->setEnv(['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
        $process->run();

        if (! $process->isSuccessful()) {
            $logger->error('❌ mysqldump failed', [
                'exitCode' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new RuntimeException('Database backup failed: '.$process->getErrorOutput());
        }
    }

    public function describe(): string
    {
        $process = new Process(['mysqldump', '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            return 'mysqldump (version unknown)';
        }

        return mb_trim($process->getOutput());
    }
}
