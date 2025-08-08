<?php

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NotifierDatabaseService
{
    public static function createDatabaseBackup()
    {
        $logChannel = config('notifier.log_channel');
        $disk = config('notifier.default_disk');
        $backupDir = config('notifier.paths.backup');

        Log::channel($logChannel)->info('⚙️ STARTING NEW BACKUP ⚙️');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        Storage::disk($disk)->makeDirectory($backupDir);
        $path = storage_path('app/'.$backupDir.'/'.$filename);

        Log::channel($logChannel)->info('➡️ creating backup file');

        $command = 'mysqldump --no-tablespaces --user='.env('DB_USERNAME').' --password='.env('DB_PASSWORD').' --host='.env('DB_HOST').' '.env('DB_DATABASE').' > '.$path;
        exec($command);

        sleep(5);

        return $path;
    }

    public static function sendDatabaseBackup(string $path)
    {
        $logChannel = config('notifier.log_channel');

        Log::channel($logChannel)->info('➡️ preparing file for sending');

        try {
            $client = new Client;

            $response = $client->post(env('BACKUP_URL'), [
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
                        'contents' => env('BACKUP_CODE'),
                    ],
                ],
            ]);

            if (in_array($response->getStatusCode(), [200, 201])) {
                Log::channel($logChannel)->info('➡️ file was sent');
                File::delete($path);
                Log::channel($logChannel)->info('➡️ file was deleted');
                Log::channel($logChannel)->info('✅ END OF BACKUP');
            }
        } catch (Throwable $th) {
            Log::channel($logChannel)->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => env('BACKUP_URL'),
                'code' => env('BACKUP_CODE'),
            ]);
            Log::channel($logChannel)->emergency('❌ END OF SESSION ❌');
        }
    }
}
