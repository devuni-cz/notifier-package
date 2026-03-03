<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Services\Zip\ZipManager;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

final class NotifierStorageService
{
    public function __construct(
        private readonly ChunkedUploadService $uploadService,
    ) {}

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

        $zipCreator = ZipManager::resolve();
        $fileCount = $zipCreator->create($source, $path, $password, $excludedFiles);

        NotifierLogger::get()->info("✅ backup archive created ({$fileCount} files): {$path}");

        return $path;
    }

    public function sendStorageBackup(string $path): void
    {
        NotifierLogger::get()->info('➡️ preparing file for sending');

        try {
            $this->uploadService->upload($path, BackupTypeEnum::Storage->value);

            NotifierLogger::get()->info('➡️ file was sent');
            NotifierLogger::get()->info('✅ END OF BACKUP');
        } catch (Throwable $th) {
            NotifierLogger::get()->emergency('❌ an error occurred while uploading a file', [
                'error' => $th->getMessage(),
                'file_size' => filesize($path),
                'php_file_upload_limit' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
                'php_memory_limit' => ini_get('memory_limit'),
                'url' => config('notifier.backup_url'),
            ]);
            NotifierLogger::get()->emergency('❌ END OF SESSION ❌');
        } finally {
            File::delete($path);
            NotifierLogger::get()->info('➡️ backup file cleaned up');
        }
    }
}
