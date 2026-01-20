<?php

namespace Devuni\Notifier\Services;

use Devuni\Notifier\Support\NotifierLogger;
use Throwable;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
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

    public static function sendDatabaseBackup(string $path)
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        try {
            $client = new Client;

            $response = $client->post(config('notifier.backup_url'), [
                'multipart' => [
                    [
                        'name' => 'backup_file',
                        'contents' => fopen($path, 'r'),
                        'filename' => basename($path),
                    ],
                    [
                        'name' => 'backup_type',
                        'contents' => 'backup_database',
                    ],
                    [
                        'name' => 'password',
                        'contents' => config('notifier.backup_code'),
                    ],
                ],
            ]);

            if (in_array($response->getStatusCode(), [200, 201])) {
                NotifierLogger::get()->info('➡️ file was sent');
                File::delete($path);
                NotifierLogger::get()->info('➡️ file was deleted');
                NotifierLogger::get()->info('✅ END OF BACKUP');
            }
        } catch (Throwable $th) {
            NotifierLogger::get()->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => config('notifier.backup_url'),
                'code' => config('notifier.backup_code'),
            ]);
            NotifierLogger::get()->emergency('❌ END OF SESSION ❌');
        }
    }
}
