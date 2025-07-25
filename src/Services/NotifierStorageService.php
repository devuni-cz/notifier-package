<?php

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class NotifierStorageService
{
    public static function createStorageBackup()
    {
        Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');

        Storage::disk('local')->makeDirectory('backups');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.zip';

        $path = storage_path('app/private/'.$filename);

        $zip = new ZipArchive;

        Log::channel('backup')->info('➡️ creating backup file');
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            Log::channel('backup')->info('➡️ adding files to the backup');

            $password = config('notifier.backup_zip_password');

            $zip->setPassword($password);

            $source = storage_path('app\public');

            if (count(File::allFiles($source)) === 0) {
                Log::channel('backup')->info('❌ No files to backup in the source directory: '.$source);
                throw new \Exception('No files to backup in the source directory: '.$source);
            }            

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                Log::channel('backup')->info('➡️ adding file: '.$file->getRealPath());
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

            Log::channel('backup')->info('➡️ closing the backup file');
            $zip->close();

            chmod($path, 0777);
        }

        Log::channel('backup')->info($path);

        return $path;
    }

    public static function sendStorageBackup(string $path)
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
                        'contents' => 'backup_storage',
                    ],
                    [
                        'name' => 'password',
                        'contents' => env('BACKUP_CODE'),
                    ],
                ],
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                Log::channel('backup')->info('➡️ file was sent');

                File::delete($path);

                Log::channel('backup')->info('➡️ file was deleted');
                Log::channel('backup')->info('✅ END OF BACKUP');
            } else {
                Log::error('❌ backup file could not be sent');
            }

            return $response->getBody();
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
