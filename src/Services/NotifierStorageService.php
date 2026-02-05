<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Throwable;
use ZipArchive;
use Carbon\Carbon;
use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Devuni\Notifier\Support\NotifierLogger;

class NotifierStorageService
{
    public static function createStorageBackup(): string
    {
        NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-' . Carbon::now()->format('Y-m-d') . '.zip';
        $path = $backupDirectory . '/' . $filename;

        $zip = new ZipArchive();

        NotifierLogger::get()->info('➡️ creating backup file');

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create ZIP archive');
        }

        $password = config('notifier.backup_zip_password');
        $excludedFiles = config('notifier.excluded_files', []);

        $zip->setPassword($password);

        $source = realpath(storage_path('app/public'));

        if (! $source || count(File::allFiles($source)) === 0) {
            throw new RuntimeException('No files to backup in directory: ' . $source);
        }

        NotifierLogger::get()->info('➡️ adding files to the backup');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            if ($filePath === false) {
                NotifierLogger::get()->warning('➡️ skipping invalid file: ' . $file->getPathname());
                continue;
            }

            $relativePath = substr($filePath, strlen($source) + 1);

            if ($relativePath === '') {
                continue;
            }

            foreach ($excludedFiles as $skip) {
                if ($relativePath === $skip || str_starts_with($relativePath, $skip . '/')) {
                    continue 2;
                }
            }

            $zip->addFile($filePath, $relativePath);
            $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
        }

        $zip->close();

        chmod($path, 0777);

        NotifierLogger::get()->info('➡️ backup created', [
            'path' => $path,
            'size_mb' => round(filesize($path) / 1024 / 1024, 2),
        ]);

        return $path;
    }

    public static function sendStorageBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending', [
            'size_mb' => round(filesize($path) / 1024 / 1024, 2),
        ]);

        try {
            $stream = fopen($path, 'r');

            if ($stream === false) {
                throw new \RuntimeException('Unable to open backup file for streaming');
            }

            $response = Http::timeout(300)
                ->retry(3, 1000)
                ->attach(
                    'backup_file',
                    $stream,
                    basename($path)
                )
                ->post(config('notifier.backup_url'), [
                    'backup_type' => 'backup_storage',
                    'password' => config('notifier.backup_code'),
                ]);

            fclose($stream);

            if (! $response->successful()) {
                NotifierLogger::get()->error('❌ backup upload failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            NotifierLogger::get()->info('➡️ file was sent successfully');

            File::delete($path);

            NotifierLogger::get()->info('➡️ local backup deleted');
            NotifierLogger::get()->info('✅ END OF BACKUP');
        } catch (Throwable $th) {
            NotifierLogger::get()->emergency('❌ an error occurred while uploading a file', [
                'error' => $th->getMessage(),
                'url' => config('notifier.backup_url'),
            ]);

            NotifierLogger::get()->emergency('❌ END OF SESSION ❌');
        }
    }
}
