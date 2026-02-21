<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Services\Zip\ZipManager;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifierStorageService
{
    public function createStorageBackup(): string
    {
        NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.zip';
        $path = $backupDirectory.'/'.$filename;

        NotifierLogger::get()->info('➡️ creating backup file');

        $sourcePath = storage_path('app/public');

        if (! File::isDirectory($sourcePath)) {
            throw new \RuntimeException(
                'Storage source directory does not exist: '.$sourcePath
                .'. Make sure the storage directory is properly linked (php artisan storage:link)'
                .' and your deployment creates the correct symlinks for the shared storage folder.'
            );
        }

        $source = realpath($sourcePath);

        if ($source === false) {
            throw new \RuntimeException(
                'Storage source directory could not be resolved: '.$sourcePath
                .'. This may indicate a broken symlink in your deployment setup.'
            );
        }

        $password = config('notifier.backup_zip_password');
        $excludedFiles = config('notifier.excluded_files', []);

        $zipCreator = ZipManager::resolve();
        $fileCount = $zipCreator->create($source, $path, $password, $excludedFiles);

        NotifierLogger::get()->info("✅ backup archive created ({$fileCount} files): {$path}");

        return $path;
    }

    public function sendStorageBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        $backupUrl = config('notifier.backup_url');

        if (! str_starts_with($backupUrl, 'https://')) {
            throw new \RuntimeException('Backup URL must use HTTPS: '.$backupUrl);
        }

        try {
            $checksum = hash_file('sha256', $path);
            $response = $this->uploadWithRetry(
                path: $path,
                checksum: $checksum,
                backupType: 'backup_storage'
            );

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
            File::delete($path);
            NotifierLogger::get()->info('➡️ backup file cleaned up');
        }
    }

    /**
     * Upload a file with manual retry logic.
     *
     * Re-opens the file stream on each attempt to avoid "resource (closed)" errors
     * that occur when Laravel's Http::retry() reuses a consumed stream.
     */
    private function uploadWithRetry(string $path, string $checksum, string $backupType, int $maxAttempts = 3, int $retryDelayMs = 1000): Response
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $fileStream = fopen($path, 'r');

            try {
                /** @var Response $response */
                $response = Http::timeout(300)
                    ->withHeaders([
                        'X-Notifier-Token' => config('notifier.backup_code'),
                        'X-Backup-Checksum' => $checksum,
                    ])
                    ->attach('backup_file', $fileStream, basename($path))
                    ->post(config('notifier.backup_url'), [
                        'backup_type' => $backupType,
                    ]);

                return $response;
            } catch (Throwable $e) {
                $lastException = $e;

                if ($attempt < $maxAttempts) {
                    usleep($retryDelayMs * 1000);
                }
            } finally {
                if (is_resource($fileStream)) {
                    fclose($fileStream);
                }
            }
        }

        throw $lastException;
    }
}
