<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Devuni\Notifier\Support\NotifierLogger;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class NotifierDatabaseService
{
    public static function createDatabaseBackup() : string
    {
        NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        $path = $backupDirectory.'/'.$filename;

        NotifierLogger::get()->info('➡️ creating backup file');

        $config = config('database.connections.mysql');
        $excludedTables = config('notifier.excluded_tables', []);

        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--port='.$config['port'],
            '--host='.$config['host']
        ];

        foreach($excludedTables as $table) {
            $command[] = '--ignore-table=' . $config['database'] . '.' . $table;
        }

        $command[] = '--result-file=' . $path;
        $command[] = $config['database'];

        $process = new Process($command);
        $process->run();

        return $path;
    }

    public static function sendDatabaseBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        try {
            $response = Http::timeout(300)
                ->retry(3, 1000)
                ->attach('backup_file', file_get_contents($path), basename($path))
                ->post(config('notifier.backup_url'), [
                    'backup_type' => 'backup_database',
                    'password' => config('notifier.backup_code'),
                ]);

            if ($response->successful()) {
                NotifierLogger::get()->info('➡️ file was sent');
                File::delete($path);
                NotifierLogger::get()->info('➡️ file was deleted');
                NotifierLogger::get()->info('✅ END OF BACKUP');
            } else {
                NotifierLogger::get()->error('❌ backup file could not be sent', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $th) {
            NotifierLogger::get()->emergency('❌ an error occurred while uploading a file', [
                'error' => $th->getMessage(),
                'url' => config('notifier.backup_url'),
            ]);
            NotifierLogger::get()->emergency('❌ END OF SESSION ❌');
        }
    }
}
