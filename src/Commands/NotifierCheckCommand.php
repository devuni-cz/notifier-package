<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierConfigService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class NotifierCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifier:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if Notifier package is configured correctly';

    /**
     * Track if any check failed.
     */
    private bool $hasErrors = false;

    /**
     * Execute the console command.
     */
    public function handle(NotifierConfigService $configService): int
    {
        $this->displayBanner();

        $this->checkEnvironmentVariables($configService);
        $this->checkDatabaseConnection();
        $this->checkStorageDirectories();
        $this->checkMysqldumpAvailability();
        $this->checkZipExtension();
        $this->checkBackupUrlReachability();

        $this->newLine();

        if ($this->hasErrors) {
            $this->line('<bg=red;fg=white;options=bold> RESULT </> <fg=red>Some checks failed. Please fix the issues above.</>');
            $this->newLine();
            return static::FAILURE;
        }

        $this->line('<bg=green;fg=white;options=bold> RESULT </> <fg=green>All checks passed! Notifier package is ready to use.</>');
        $this->newLine();
        return static::SUCCESS;
    }

    /**
     * Display the command banner.
     */
    private function displayBanner(): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>╔══════════════════════════════════════════╗</>');
        $this->line('<fg=cyan;options=bold>║       NOTIFIER PACKAGE HEALTH CHECK      ║</>');
        $this->line('<fg=cyan;options=bold>╚══════════════════════════════════════════╝</>');
        $this->newLine();
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

        $this->line('   <fg=gray>BACKUP_CODE:</> ' . $this->maskValue($backupCode));
        $this->line('   <fg=gray>BACKUP_URL:</> ' . $backupUrl);
        $this->line('   <fg=gray>BACKUP_ZIP_PASSWORD:</> ' . $this->maskValue($backupPassword));
    }

    /**
     * Mask a value for display (show first 3 and last 3 characters).
     */
    private function maskValue(?string $value): string
    {
        if (empty($value)) {
            return '<fg=red>(empty)</>';
        }

        $length = strlen($value);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 3) . str_repeat('*', $length - 6) . substr($value, -3);
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
     * Check if mysqldump is available for database backups.
     */
    private function checkMysqldumpAvailability(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking mysqldump availability...</>');

        $result = shell_exec('which mysqldump 2>/dev/null') ?? shell_exec('where mysqldump 2>nul');

        if (!empty(trim($result ?? ''))) {
            $version = shell_exec('mysqldump --version 2>&1');
            $this->line('   <fg=green>✓</> mysqldump is available');
            if ($version) {
                $this->line('   <fg=gray>' . trim($version) . '</>');
            }
        } else {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> mysqldump is not available');
            $this->line('   <fg=gray>→ Install MySQL client tools to enable database backups</>');
        }
        $this->newLine();
    }

    /**
     * Check if ZIP extension is available.
     */
    private function checkZipExtension(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking PHP ZIP extension...</>');

        if (extension_loaded('zip')) {
            $this->line('   <fg=green>✓</> PHP ZIP extension is loaded');
        } else {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> PHP ZIP extension is not loaded');
            $this->line('   <fg=gray>→ Install php-zip extension to enable storage backups</>');
        }
        $this->newLine();
    }

    /**
     * Check if the backup URL is reachable.
     */
    private function checkBackupUrlReachability(): void
    {
        $this->line('<fg=yellow;options=bold>🔍 Checking backup URL reachability...</>');

        $backupUrl = config('notifier.backup_url');

        if (empty($backupUrl)) {
            $this->line('   <fg=yellow>⚠</> Backup URL is not configured, skipping connectivity check');
            return;
        }

        try {
            $client = new Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);

            // Extract base URL for connectivity check
            $parsedUrl = parse_url($backupUrl);
            $baseUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');

            if (!empty($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }

            $response = $client->head($baseUrl);
            $statusCode = $response->getStatusCode();

            if ($statusCode < 500) {
                $this->line("   <fg=green>✓</> Backup server is reachable: <fg=cyan>{$baseUrl}</>");
                $this->line("   <fg=gray>Response status: {$statusCode}</>");
            } else {
                $this->hasErrors = true;
                $this->line("   <fg=red>✗</> Backup server returned error: {$statusCode}");
            }
        } catch (GuzzleException $e) {
            $this->hasErrors = true;
            $this->line('   <fg=red>✗</> Cannot reach backup server');
            $this->line("   <fg=gray>→ Error: {$e->getMessage()}</>");
        }
    }
}
