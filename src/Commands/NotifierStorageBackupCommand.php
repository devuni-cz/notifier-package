<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Concerns\ChecksNotifierEnvironment;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierStorageService;
use Illuminate\Console\Command;

class NotifierStorageBackupCommand extends Command
{
    use ChecksNotifierEnvironment;

    protected $signature = 'notifier:storage-backup';

    protected $description = 'Command for creating a storage backup';

    public function handle(NotifierConfigService $configService, NotifierStorageService $storageService): int
    {
        if ($this->checkMissingVariables($configService) === static::FAILURE) {
            return static::FAILURE;
        }

        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = $storageService->createStorageBackup();
        $this->line('✅ Backup file created successfully at: '.$backup_path);
        $storageService->sendStorageBackup($backup_path);

        $this->newLine();
        $this->line('✅ End of backup');

        return static::SUCCESS;
    }
}
