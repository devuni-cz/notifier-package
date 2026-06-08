<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierStorageService;
use Devuni\Notifier\Traits\ChecksNotifierEnvironmentTrait;
use Devuni\Notifier\Traits\DisplayHelperTrait;
use Illuminate\Console\Command;

final class NotifierStorageBackupCommand extends Command
{
    use ChecksNotifierEnvironmentTrait;
    use DisplayHelperTrait;

    protected $signature = 'notifier:storage-backup';

    protected $description = 'Command for creating a storage backup';

    public function handle(NotifierConfigService $configService, NotifierStorageService $storageService): int
    {
        $this->displayNotifierHeader('Storage Backup');

        if ($this->checkMissingVariables($configService) === self::FAILURE) {
            return self::FAILURE;
        }

        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = $storageService->createStorageBackup();
        $this->line('✅ Backup file created successfully at: '.$backup_path);
        $storageService->sendStorageBackup($backup_path);

        $this->newLine();
        $this->line('✅ End of backup');

        return self::SUCCESS;
    }
}
