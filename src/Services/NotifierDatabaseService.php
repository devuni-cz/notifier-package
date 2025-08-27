<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Throwable;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class NotifierDatabaseService
{
    public static function createDatabaseBackup(): string
    {
        Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        $path = $backupDirectory.'/'.$filename;

        Log::channel('backup')->info('➡️ creating backup file');

        $config = config('database.connections.mysql');

        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--host='.$config['host'],
            '--result-file='.$path,
            $config['database'],
        ];

        $process = new Process($command);
        $process->run();

        return $path;
    }

    public static function sendDatabaseBackup(string $path)
    {
        Log::channel('backup')->info('➡️ preparing file for sending');

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
                Log::channel('backup')->info('➡️ file was sent');
                File::delete($path);
                Log::channel('backup')->info('➡️ file was deleted');
                Log::channel('backup')->info('✅ END OF BACKUP');
            }
        } catch (Throwable $th) {
            Log::channel('backup')->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => config('notifier.backup_url'),
                'code' => config('notifier.backup_code'),
            ]);

            Log::channel('backup')->emergency('❌ END OF SESSION ❌');
        }
    }
}
