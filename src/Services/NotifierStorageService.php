<?php

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class NotifierStorageService
{
    public static function createStorageBackup()
    {
        Log::channel(config('notifier.log_channel'))->info('⚙️ STARTING NEW BACKUP ⚙️');

        $directory = rtrim(config('notifier.backup_path'), '/');
        File::ensureDirectoryExists($directory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.zip';

        $path = $directory.'/'.$filename;

        $zip = new ZipArchive;

        Log::channel(config('notifier.log_channel'))->info('➡️ creating backup file');
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            Log::channel(config('notifier.log_channel'))->info('➡️ adding files to the backup');

            $password = config('notifier.backup_zip_password');

            $zip->setPassword($password);

            $source = storage_path('app/public');

            if (count(File::allFiles($source)) === 0) {
                Log::channel(config('notifier.log_channel'))->info('❌ No files to backup in the source directory: '.$source);
                throw new \Exception('No files to backup in the source directory: '.$source);
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                Log::channel(config('notifier.log_channel'))->info('➡️ adding file: '.$file->getRealPath());
                // Skip directories (they will be added automatically)
                if (! $file->isDir()) {
                    // Get real and relative path for the current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($source) + 1);

                    // Add file to the ZIP archive
                    $zip->addFile($filePath, $relativePath);

                    // Encrypt the file with the password
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                }
            }

            Log::channel(config('notifier.log_channel'))->info('➡️ closing the backup file');
            $zip->close();

            if (! chmod($path, 0600)) {
                Log::channel(config('notifier.log_channel'))->warning('Could not set permissions on backup file', ['path' => $path]);
            }
        }

        Log::channel(config('notifier.log_channel'))->info($path);

        return $path;
    }

    public static function sendStorageBackup(string $path)
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
                        'contents' => 'backup_storage',
                    ],
                    [
                        'name' => 'password',
                        'contents' => config('notifier.backup_code'),
                    ],
                ],
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                Log::channel(config('notifier.log_channel'))->info('➡️ file was sent');

                File::delete($path);

                Log::channel(config('notifier.log_channel'))->info('➡️ file was deleted');
                Log::channel(config('notifier.log_channel'))->info('✅ END OF BACKUP');
            } else {
                Log::channel(config('notifier.log_channel'))->error('❌ backup file could not be sent');
            }

            return $response->getBody();
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
