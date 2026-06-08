<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Interfaces\DatabaseDumperInterface;
use Devuni\Notifier\Services\Database\LazyDatabaseDumper;
use Devuni\Notifier\Services\Database\MysqlDumper;
use Devuni\Notifier\Services\Database\PostgresDumper;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierLoggerService;
use Devuni\Notifier\Services\Zip\CliZipCreator;
use Devuni\Notifier\Services\Zip\PhpZipCreator;
use Devuni\Notifier\Traits\DisplayHelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

final class NotifierCheckCommand extends Command
{
    use DisplayHelperTrait;

    protected $signature = 'notifier:check';

    protected $description = 'Check if Notifier package is configured correctly';

    private bool $hasErrors = false;

    public function handle(NotifierConfigService $configService, NotifierLoggerService $notifierLogger): int
    {
        $this->displayNotifierHeader('Health Check');

        $this->checkEnvironmentVariables($configService);
        $this->checkDatabaseConnection();
        $this->checkStorageDirectories();
        $this->checkDatabaseDumpTool();
        $this->checkZipAvailability();
        $this->checkLoggingChannel($notifierLogger);
        $this->checkQueueConfiguration();
        $this->checkBackupUrlReachability();

        $this->newLine();

        if ($this->hasErrors) {
            $this->line('<bg=red;fg=white;options=bold> RESULT </> <fg=red>Some checks failed. Please fix the issues above.</>');
            $this->newLine();

            return self::FAILURE;
        }

        $this->line('<bg=green;fg=white;options=bold> RESULT </> <fg=green>All checks passed! Notifier package is ready to use.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Check if all required environment variables are set.
     */
    private function checkEnvironmentVariables(NotifierConfigService $configService): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking environment variables...</>');

        $missing = $configService->checkEnvironment();

        if (empty($missing)) {
            $this->line('   <fg=green>✓</> All required environment variables are configured');
            $this->showConfiguredValues();
        } else {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> Missing environment variables:');
            foreach ($missing as $variable) {
                $this->line("      <fg=red>•</> {$variable}");
            }
            $this->line('   <fg=gray>→ Run: php artisan notifier:install</>');
        }
        $this->newLine();
    }

    /**
     * Show configured values (masked for security).
     */
    private function showConfiguredValues(): void
    {
        $backupCode = config('notifier.backup_code');
        $backupUrl = config('notifier.backup_url');
        $backupPassword = config('notifier.backup_zip_password');

        $this->line('   <fg=gray>NOTIFIER_BACKUP_CODE:</> '.$this->maskValue($backupCode));
        $this->line('   <fg=gray>NOTIFIER_URL:</> '.$backupUrl);
        $this->line('   <fg=gray>NOTIFIER_BACKUP_PASSWORD:</> '.$this->maskValue($backupPassword));
    }

    /**
     * Mask a value for display (show first 3 and last 3 characters).
     */
    private function maskValue(?string $value): string
    {
        if (empty($value)) {
            return '<fg=red>(empty)</>';
        }

        $length = mb_strlen($value);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 3).str_repeat('*', $length - 6).mb_substr($value, -3);
    }

    /**
     * Check database connection.
     */
    private function checkDatabaseConnection(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking database connection...</>');

        try {
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();
            $this->line("   <fg=green>✓</> Connected to database: <fg=cyan>{$databaseName}</>");
        } catch (Throwable $e) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> Database connection failed');
            $this->line("   <fg=gray>→ Error: {$e->getMessage()}</>");
        }
        $this->newLine();
    }

    /**
     * Check if storage directories exist and are writable.
     */
    private function checkStorageDirectories(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking storage directories...</>');

        $directories = [
            'Backup directory' => storage_path('app/private'),
            'Public storage' => storage_path('app/public'),
        ];

        foreach ($directories as $name => $path) {
            if (File::isDirectory($path)) {
                if (is_writable($path)) {
                    $this->line("   <fg=green>✓</> {$name}: <fg=cyan>{$path}</>");
                } else {
                    $this->hasErrors = true;
                    $this->line("   <fg=red>✗</> {$name} is not writable: <fg=cyan>{$path}</>");
                }
            } else {
                $this->line("   <fg=yellow>⚠</> {$name} does not exist: <fg=cyan>{$path}</>");
                $this->line('      <fg=gray>→ Will be created automatically during backup</>');
            }
        }
        $this->newLine();
    }

    /**
     * Check if the right dump binary is available for the configured driver.
     */
    private function checkDatabaseDumpTool(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking database dump tool...</>');

        $connection = config('notifier.database_connection') ?: config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $this->line("   <fg=gray>Connection:</> <fg=cyan>{$connection}</> <fg=gray>(driver: {$driver})</>");

        try {
            $dumper = app(DatabaseDumperInterface::class);

            // Unwrap LazyDatabaseDumper to expose the concrete implementation
            if ($dumper instanceof LazyDatabaseDumper) {
                $dumper = $dumper->resolve();
            }
        } catch (Throwable $e) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> '.$e->getMessage());
            $this->newLine();

            return;
        }

        $available = match (true) {
            $dumper instanceof MysqlDumper => MysqlDumper::isAvailable(),
            $dumper instanceof PostgresDumper => PostgresDumper::isAvailable(),
            default => false,
        };

        if ($available) {
            $this->line('   <fg=green>✓</> '.$dumper->describe());
        } else {
            $this->hasErrors = true;
            $hint = match (true) {
                $dumper instanceof MysqlDumper => 'Install MySQL client tools (e.g. `apt install mysql-client` or `mariadb-client`)',
                $dumper instanceof PostgresDumper => 'Install PostgreSQL client tools (`apt install postgresql-client`) or YugabyteDB tools',
                default => 'No dump tool available for this driver',
            };
            $this->line('   <fg=red>✗</> Required dump binary is not available on PATH');
            $this->line("   <fg=gray>→ {$hint}</>");
        }

        $this->newLine();
    }

    private function checkZipAvailability(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking ZIP archive tools...</>');

        $strategy = config('notifier.zip_strategy', 'auto');
        $cliAvailable = CliZipCreator::isAvailable();
        $phpAvailable = PhpZipCreator::isAvailable();

        if ($cliAvailable) {
            $this->line('   <fg=green>✓</> CLI 7z is available (recommended for production)');
        } else {
            $this->line('   <fg=yellow>⚠</> CLI 7z is not installed');
            $this->line('   <fg=gray>→ Install: sudo apt install p7zip-full</>');
        }

        if ($phpAvailable) {
            $this->line('   <fg=green>✓</> PHP ZIP extension is loaded (fallback)');
        } else {
            $this->line('   <fg=yellow>⚠</> PHP ZIP extension is not loaded');
        }

        if (! $cliAvailable && ! $phpAvailable) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> No ZIP strategy available - storage backups will fail');
        } else {
            $active = $cliAvailable ? 'cli (7z)' : 'php (ZipArchive)';

            if ($strategy !== 'auto') {
                $active = $strategy;
            }

            $this->line("   <fg=gray>Active strategy:</> <fg=cyan>{$active}</> <fg=gray>(config: {$strategy})</>");
        }

        $this->newLine();
    }

    /**
     * Check if the preferred logging channel is configured.
     */
    private function checkLoggingChannel(NotifierLoggerService $notifierLogger): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking logging channel...</>');

        $preferredChannel = $notifierLogger->getPreferredChannel();

        if ($notifierLogger->isUsingPreferredChannel()) {
            $this->line("   <fg=green>✓</> Logging channel '<fg=cyan>{$preferredChannel}</>' is configured");
        } else {
            $this->line("   <fg=yellow>⚠</> Logging channel '<fg=cyan>{$preferredChannel}</>' not found, using '<fg=cyan>daily</>' fallback");
            $this->line('   <fg=gray>→ Add the channel to config/logging.php for dedicated backup logs</>');
        }
        $this->newLine();
    }

    private function checkQueueConfiguration(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking queue configuration...</>');

        $connection = config('notifier.queue_connection', 'sync');

        if ($connection === 'sync') {
            $this->line('   <fg=gray>ℹ</> Queue connection is "sync" - backups run synchronously in the HTTP request');
            $this->line('   <fg=gray>→ Set NOTIFIER_QUEUE_CONNECTION to database, redis, or another async driver to offload backups</>');
        } else {
            $this->line("   <fg=green>✓</> Backups dispatched to queue: <fg=cyan>{$connection}</>");
        }

        $this->newLine();
    }

    private function checkBackupUrlReachability(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking backup URL reachability...</>');

        $backupUrl = config('notifier.backup_url');

        if (empty($backupUrl)) {
            $this->line('   <fg=yellow>⚠</> Backup URL is not configured, skipping connectivity check');

            return;
        }

        if (! str_starts_with($backupUrl, 'https://')) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> Backup URL must use HTTPS: <fg=cyan>'.$backupUrl.'</>');

            return;
        }

        try {
            $parsedUrl = parse_url($backupUrl);
            $baseUrl = ($parsedUrl['scheme'] ?? 'https').'://'.($parsedUrl['host'] ?? '');

            if (! empty($parsedUrl['port'])) {
                $baseUrl .= ':'.$parsedUrl['port'];
            }

            $response = Http::timeout(5)
                ->connectTimeout(5)
                ->head($baseUrl);

            $statusCode = $response->status();

            if ($statusCode < 500) {
                $this->line("   <fg=green>✓</> Backup server is reachable: <fg=cyan>{$baseUrl}</>");
                $this->line("   <fg=gray>Response status: {$statusCode}</>");
            } else {
                $this->hasErrors = true;
                $this->line("   <fg=red>✗</> Backup server returned error: {$statusCode}");
            }
        } catch (Throwable $e) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> Cannot reach backup server');
            $this->line("   <fg=gray>→ Error: {$e->getMessage()}</>");
        }
    }
}
