<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class NotifierStorageService
{
    public function createStorageBackup(): string
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

            if ($source === false) {
                throw new \RuntimeException(
                    'Storage source directory does not exist: '.storage_path('app/public')
                );
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $fileCount = 0;

            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getRealPath();

                if ($filePath === false) {
                    NotifierLogger::get()->warning('➡️ skipping file with invalid path: '.$file->getPathname());

                    continue;
                }

                $relativePath = substr($filePath, strlen($source) + 1);

                if (empty($relativePath)) {
                    NotifierLogger::get()->warning('➡️ skipping file with empty relative path: '.$filePath);

                    continue;
                }

                if ($this->isExcluded($relativePath, $excludedFiles)) {
                    NotifierLogger::get()->info('➡️ skipping excluded file: '.$relativePath);

                    continue;
                }

                NotifierLogger::get()->info('➡️ adding file: '.$filePath);

                $zip->addFile($filePath, $relativePath);
                $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                $fileCount++;
            }

            if ($fileCount === 0) {
                $zip->close();
                File::delete($path);

                throw new \RuntimeException('No files to backup in the source directory: '.$source);
            }

            NotifierLogger::get()->info("➡️ closing the backup file ({$fileCount} files)");

            $zip->close();

            chmod($path, 0600);
        }

        NotifierLogger::get()->info($path);

        return $path;
    }

    /**
     * Determine if a relative path matches any exclusion pattern.
     */
    private function isExcluded(string $relativePath, array $excludedFiles): bool
    {
        foreach ($excludedFiles as $skip) {
            if ($relativePath === $skip || str_starts_with($relativePath, $skip.'/')) {
                return true;
            }
        }

        return false;
    }

    public function sendStorageBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        $fileStream = null;

        try {
            $fileStream = fopen($path, 'r');

            $response = Http::timeout(300)
                ->retry(3, 1000)
                ->attach('backup_file', $fileStream, basename($path))
                ->post(config('notifier.backup_url'), [
                    'backup_type' => 'backup_storage',
                    'password' => config('notifier.backup_code'),
                ]);

            if ($response->successful()) {
                NotifierLogger::get()->info('➡️ file was sent');
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
        } finally {
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            File::delete($path);
            NotifierLogger::get()->info('➡️ backup file cleaned up');
        }
    }
}
