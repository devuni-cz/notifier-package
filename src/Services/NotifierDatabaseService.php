<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final class NotifierDatabaseService
{
    public function __construct(
        private readonly ChunkedUploadService $uploadService,
        private readonly ZipCreator $zipCreator,
        private readonly NotifierLogger $notifierLogger,
    ) {}

    public function createDatabaseBackup(): string
    {
        $logger = $this->notifierLogger->get();

        $logger->info('⚙️ STARTING NEW BACKUP ⚙️');

        $backupDirectory = storage_path('app/private');
        File::ensureDirectoryExists($backupDirectory);

        $filename = 'backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.sql';
        $path = $backupDirectory.'/'.$filename;

        $logger->info('➡️ creating backup file');

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
        $process->setTimeout(600);
        $process->setEnv(['MYSQL_PWD' => $config['password']]);
        $process->run();

        if (! $process->isSuccessful()) {
            $logger->error('❌ mysqldump failed', [
                'exitCode' => $process->getExitCode(),
                'error' => $process->getErrorOutput(),
            ]);

            throw new RuntimeException('Database backup failed: '.$process->getErrorOutput());
        }

        // Encrypt the SQL dump into a password-protected ZIP
        $password = config('notifier.backup_zip_password');

        if (! empty($password)) {
            $zipPath = $backupDirectory.'/backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.zip';

            $this->zipCreator->create($path, $zipPath, $password, []);

            File::delete($path);
            $logger->info('➡️ SQL dump encrypted into ZIP archive');

            return $zipPath;
        }

        return $path;
    }

    public function sendDatabaseBackup(string $path): void
    {
        $logger = $this->notifierLogger->get();

        $logger->info('➡️ preparing file for sending');

        try {
            $this->uploadService->upload($path, BackupTypeEnum::Database->value);

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
