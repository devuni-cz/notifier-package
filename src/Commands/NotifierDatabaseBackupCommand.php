<?php

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Illuminate\Console\Command;

class NotifierDatabaseBackupCommand extends Command
{
    protected $signature = 'notifier:database-backup';

    protected $description = 'Command for creating a database backup';

    public function handle(NotifierConfigService $configService)
    {
        if ($this->checkMissingVariables($configService) === static::FAILURE) {
            return static::FAILURE;
        }

        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = NotifierDatabaseService::createDatabaseBackup();
        $this->line('✅ Backup file created successfully at: ' . $backup_path);
        NotifierDatabaseService::sendDatabaseBackup($backup_path);

        $this->newLine();
        $this->line('✅ End of backup');
        return static::SUCCESS;
    }

    private function checkMissingVariables(NotifierConfigService $configService) : int
    {
        $missingVariables = $configService->checkEnvironment();

        if (!empty($missingVariables)) {
            $this->newLine();
            $this->line('<bg=red;fg=white;options=bold> ERROR </> The following environment variables are missing or empty:');
            $this->newLine();
            foreach ($missingVariables as $var) {
                $this->line("   • <fg=gray>$var</>");
            }
            $this->newLine();
            $this->line('-> Run <fg=gray>php artisan notifier:install</> to set these variables.');

            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}
