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
        Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        Storage::disk('local')->makeDirectory('backups');
        $path = storage_path('app/private/'.$filename);

        Log::channel('backup')->info('➡️ creating backup file');

        $command = 'mysqldump --no-tablespaces --user='.env('DB_USERNAME').' --password='.env('DB_PASSWORD').' --host='.env('DB_HOST').' '.env('DB_DATABASE').' > '.$path;
        exec($command);

        sleep(5);

        return $path;
    }

    public static function sendDatabaseBackup(string $path)
    {
        Log::channel('backup')->info('➡️ preparing file for sending');

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
                Log::channel('backup')->info('➡️ file was sent');
                File::delete($path);
                Log::channel('backup')->info('➡️ file was deleted');
                Log::channel('backup')->info('✅ END OF BACKUP');
            }
        } catch (Throwable $th) {
            Log::channel('backup')->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => env('BACKUP_URL'),
                'code' => env('BACKUP_CODE'),
            ]);
            Log::channel('backup')->emergency('❌ END OF SESSION ❌');
        }
    }
}
