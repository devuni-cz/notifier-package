<?php

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierDatabaseService;
use Illuminate\Console\Command;

class NotifierDatabaseBackupCommand extends Command
{
    protected $signature = 'notifier:database-backup';

    protected $description = 'Command for creating a database backup';

    public function handle()
    {
        $this->line('⚙️  STARTING NEW BACKUP ⚙️');
        $this->newLine();

        $backup_path = NotifierDatabaseService::createDatabaseBackup();
        $this->line('✅ Backup file created successfully at: ' . $backup_path);
        NotifierDatabaseService::sendDatabaseBackup($backup_path);

        $this->newLine();
        $this->line('✅ End of backup');            
    }
}
