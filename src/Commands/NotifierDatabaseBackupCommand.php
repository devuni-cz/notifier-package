<?php

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Services\NotifierDatabaseService;
use Illuminate\Console\Command;

class NotifierDatabaseBackupCommand extends Command
{
    protected $signature = 'database:backup';

    protected $description = 'Command for creating a database backup';

    public function handle()
    {
        $backup_path = NotifierDatabaseService::createDatabaseBackup();
        $this->info($backup_path);

        return NotifierDatabaseService::sendDatabaseBackup($backup_path);
    }
}
