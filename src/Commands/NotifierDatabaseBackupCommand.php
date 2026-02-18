<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Concerns\ChecksNotifierEnvironment;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Illuminate\Console\Command;

class NotifierDatabaseBackupCommand extends Command
{
    use ChecksNotifierEnvironment;

    protected $signature = 'notifier:database-backup';

    protected $description = 'Command for creating a database backup';

    public function handle(NotifierConfigService $configService, NotifierDatabaseService $databaseService): int
    {
        if ($this->checkMissingVariables($configService) === static::FAILURE) {
            return static::FAILURE;
        }

        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = $databaseService->createDatabaseBackup();
        $this->line('✅ Backup file created successfully at: '.$backup_path);
        $databaseService->sendDatabaseBackup($backup_path);

        $this->newLine();
        $this->line('✅ End of backup');

        return static::SUCCESS;
    }
}
