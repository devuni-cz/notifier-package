<?php

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierStorageService;
use Illuminate\Console\Command;

class NotifierStorageBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifier:storage-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for creating a storage backup';

    /**
     * Execute the console command.
     */
    public function handle(NotifierConfigService $configService)
    {
        if ($this->checkMissingVariables($configService) === static::FAILURE) {
            return static::FAILURE;
        }

        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = NotifierStorageService::createStorageBackup();
        $this->line('✅ Backup file created successfully at: ' . $backup_path);
        NotifierStorageService::sendStorageBackup($backup_path);

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
