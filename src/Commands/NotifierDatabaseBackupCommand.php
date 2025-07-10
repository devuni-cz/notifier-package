<?php

namespace Devuni\Notifier\Commands;

use Illuminate\Console\Command;
use Devuni\Notifier\Services\NotifierDatabaseService;

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
