<?php

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class NotifierDatabaseService
{
    public static function createDatabaseBackup()
    {
        Log::channel(config('notifier.log_channel'))->info('⚙️ STARTING NEW BACKUP ⚙️');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        $directory = rtrim(config('notifier.backup_path'), '/');

        try {
            File::ensureDirectoryExists($directory);
        } catch (Throwable $e) {
            Log::channel(config('notifier.log_channel'))->warning('Primary backup path is not writable, falling back to temp directory', [
                'path' => $directory,
                'error' => $e->getMessage(),
            ]);

            $directory = rtrim(config('notifier.backup_fallback_path'), '/');
            File::ensureDirectoryExists($directory);
        }

        $path = $directory.'/'.$filename;

        Log::channel(config('notifier.log_channel'))->info('➡️ creating backup file');

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

        if (! $process->isSuccessful()) {
            Log::channel(config('notifier.log_channel'))->error('mysqldump failed', [
                'error' => $process->getErrorOutput(),
            ]);
            throw new \RuntimeException('Database backup failed.');
        }

        return $path;
    }

    public static function sendDatabaseBackup(string $path)
    {
        Log::channel(config('notifier.log_channel'))->info('➡️ preparing file for sending');

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
                Log::channel(config('notifier.log_channel'))->info('➡️ file was sent');
                File::delete($path);
                Log::channel(config('notifier.log_channel'))->info('➡️ file was deleted');
                Log::channel(config('notifier.log_channel'))->info('✅ END OF BACKUP');
            }
        } catch (Throwable $th) {
            Log::channel(config('notifier.log_channel'))->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => config('notifier.backup_url'),
                'code' => config('notifier.backup_code'),
            ]);
            Log::channel(config('notifier.log_channel'))->emergency('❌ END OF SESSION ❌');
        }
    }
}
