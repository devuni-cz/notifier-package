<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Services\Zip\ZipManager;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Throwable;

class NotifierDatabaseService
{
    public function createDatabaseBackup(): string
    {
        NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        $path = $backupDirectory.'/'.$filename;

        NotifierLogger::get()->info('➡️ creating backup file');

        $config = config('database.connections.mysql');
        $excludedTables = config('notifier.excluded_tables', []);

        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--single-transaction',
            '--quick',
            '--user='.$config['username'],
            '--port='.$config['port'],
            '--host='.$config['host'],
        ];

        foreach ($excludedTables as $table) {
            $command[] = '--ignore-table='.$config['database'].'.'.$table;
        }

        $command[] = '--result-file='.$path;
        $command[] = $config['database'];

        $process = new Process($command);
        $process->setEnv(['MYSQL_PWD' => $config['password']]);
        $process->run();

        if (! $process->isSuccessful()) {
            NotifierLogger::get()->error('❌ mysqldump failed', [
                'exitCode' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new \RuntimeException('Database backup failed: '.$process->getErrorOutput());
        }

        // Encrypt the SQL dump into a password-protected ZIP
        $password = config('notifier.backup_zip_password');

        if (! empty($password)) {
            $zipPath = $backupDirectory.'/backup-'.Carbon::now()->format('Y-m-d').'.zip';

            $zipCreator = ZipManager::resolve();
            $zipCreator->create($path, $zipPath, $password, []);

            File::delete($path);
            NotifierLogger::get()->info('➡️ SQL dump encrypted into ZIP archive');

            return $zipPath;
        }

        return $path;
    }

    public function sendDatabaseBackup(string $path): void
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
                    'backup_type' => 'backup_database',
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
