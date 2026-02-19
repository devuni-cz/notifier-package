<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Services\Zip\ZipManager;
use Devuni\Notifier\Support\NotifierLogger;
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

        $source = realpath(storage_path('app/public'));

        if ($source === false) {
            throw new \RuntimeException(
                'Storage source directory does not exist: '.storage_path('app/public')
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

        $fileStream = null;

        try {
            $checksum = hash_file('sha256', $path);
            $fileStream = fopen($path, 'r');

            $response = Http::timeout(300)
                ->retry(3, 1000)
                ->withHeaders([
                    'X-Notifier-Token' => config('notifier.backup_code'),
                    'X-Backup-Checksum' => $checksum,
                ])
                ->attach('backup_file', $fileStream, basename($path))
                ->post(config('notifier.backup_url'), [
                    'backup_type' => 'backup_storage',
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
