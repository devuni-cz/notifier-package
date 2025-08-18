<?php

namespace Devuni\Notifier\Commands;

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
    public function handle()
    {
        $backup_path = NotifierStorageService::createStorageBackup();
        NotifierStorageService::sendStorageBackup($backup_path);
    }
}
