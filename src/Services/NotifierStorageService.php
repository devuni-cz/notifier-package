<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Devuni\Notifier\Support\NotifierLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class NotifierStorageService
{
    public static function createStorageBackup() : string
    {
        NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.zip';
        $path = $backupDirectory.'/'.$filename;

        $zip = new ZipArchive;

        NotifierLogger::get()->info('➡️ creating backup file');

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            NotifierLogger::get()->info('➡️ adding files to the backup');

            $password = config('notifier.backup_zip_password');
            $excludedFiles = config('notifier.excluded_files', []);

            $zip->setPassword($password);

            $source = realpath(storage_path('app/public'));

            if (count(File::allFiles($source)) === 0) {
                NotifierLogger::get()->info('❌ No files to backup in the source directory: '.$source);
                throw new \Exception('No files to backup in the source directory: '.$source);
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                // Skip directories (they will be added automatically)
                if (! $file->isDir()) {
                    // Get real and relative path for the current file
                    $filePath = $file->getRealPath();

                    // Skip if getRealPath() returns false (broken symlink, etc.)
                    if ($filePath === false) {
                        NotifierLogger::get()->warning('➡️ skipping file with invalid path: '.$file->getPathname());
                        continue;
                    }

                    $relativePath = substr($filePath, strlen($source) + 1);

                    // Skip files with empty relative paths
                    if (empty($relativePath)) {
                        NotifierLogger::get()->warning('➡️ skipping file with empty relative path: '.$filePath);
                        continue;
                    }

                    foreach ($excludedFiles as $skip) {
                        if ($relativePath === $skip || str_starts_with($relativePath, $skip.'/')) {
                            NotifierLogger::get()->info('➡️ skipping excluded file: '.$relativePath);
                            continue 2;
                        }
                    }

                    NotifierLogger::get()->info('➡️ adding file: '.$file->getRealPath());

                    // Add file to the ZIP archive
                    $zip->addFile($filePath, $relativePath);

                    // Encrypt the file with the password
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                }
            }

            NotifierLogger::get()->info('➡️ closing the backup file');

            $zip->close();

            chmod($path, 0777);
        }

        NotifierLogger::get()->info($path);

        return $path;
    }

    public static function sendStorageBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        try {
            $fileStream = fopen($path, 'r');

            $response = Http::timeout(300)
                ->retry(3, 1000)
                ->attach('backup_file', $fileStream, basename($path))
                ->post(config('notifier.backup_url'), [
                    'backup_type' => 'backup_storage',
                    'password' => config('notifier.backup_code'),
                ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

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
