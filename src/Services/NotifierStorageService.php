<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class NotifierStorageService
{
    public function __construct(
        private readonly ChunkedUploadService $uploadService,
        private readonly ZipCreator $zipCreator,
        private readonly NotifierLogger $notifierLogger,
    ) {}

    public function createStorageBackup(): string
    {
        $logger = $this->notifierLogger->get();

        $logger->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.zip';
        $path = $backupDirectory.'/'.$filename;

        $logger->info('➡️ creating backup file');

        $sourcePath = storage_path('app/public');

        if (! File::isDirectory($sourcePath)) {
            throw new RuntimeException(
                'Storage source directory does not exist: '.$sourcePath
                .'. Make sure the storage directory is properly linked (php artisan storage:link)'
                .' and your deployment creates the correct symlinks for the shared storage folder.'
            );
        }

        $source = realpath($sourcePath);

        if ($source === false) {
            throw new RuntimeException(
                'Storage source directory could not be resolved: '.$sourcePath
                .'. This may indicate a broken symlink in your deployment setup.'
            );
        }

        $password = config('notifier.backup_zip_password');
        $excludedFiles = config('notifier.excluded_files', []);

        try {
            $fileCount = $this->zipCreator->create($source, $path, $password, $excludedFiles);
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'No files to backup')) {
                $logger->warning('⚠️ storage directory is empty, skipping backup', [
                    'source' => $source,
                ]);

                return '';
            }

            throw $e;
        }

        $logger->info("✅ backup archive created ({$fileCount} files): {$path}");

        return $path;
    }

    public function sendStorageBackup(string $path): void
    {
        $logger = $this->notifierLogger->get();

        $logger->info('➡️ preparing file for sending');

        $size = filesize($path);

        if ($size === false || $size < 100) {
            $logger->warning('⚠️ backup archive is empty or too small, skipping upload', [
                'file_size' => $size,
                'path' => $path,
            ]);

            File::delete($path);
            $logger->info('➡️ backup file cleaned up');

            return;
        }

        try {
            $this->uploadService->upload($path, BackupTypeEnum::Storage->value);

            $logger->info('➡️ file was sent');
            $logger->info('✅ END OF BACKUP');
        } catch (Throwable $th) {
            $logger->emergency('❌ an error occurred while uploading a file', [
                'error' => $th->getMessage(),
                'file_size' => filesize($path),
                'php_file_upload_limit' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
                'php_memory_limit' => ini_get('memory_limit'),
                'url' => config('notifier.backup_url'),
            ]);

            throw $th;
        } finally {
            File::delete($path);
            $logger->info('➡️ backup file cleaned up');
        }
    }
}
