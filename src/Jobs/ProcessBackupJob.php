<?php

declare(strict_types=1);

namespace Devuni\Notifier\Jobs;

use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * The number of times the job may be attempted.
     * Backup files are cleaned up after each attempt, so retrying is not safe.
     */
    public int $tries = 1;

    public function __construct(
        public readonly BackupTypeEnum $backupType,
    ) {}

    public function handle(
        NotifierDatabaseService $databaseService,
        NotifierStorageService $storageService,
        NotifierLogger $notifierLogger,
    ): void {
        $logger = $notifierLogger->get();
        $startTime = microtime(true);

        $logger->info('🚀 backup job started', [
            'backup_type' => $this->backupType->value,
        ]);

        match ($this->backupType) {
            BackupTypeEnum::Database => $this->backupDatabase($databaseService),
            BackupTypeEnum::Storage => $this->backupStorage($storageService),
        };

        $duration = round(microtime(true) - $startTime, 2);

        $logger->info('✅ backup job completed', [
            'backup_type' => $this->backupType->value,
            'duration_seconds' => $duration,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        // failed() is called by the framework — resolve logger from container
        $notifierLogger = app(NotifierLogger::class);
        $notifierLogger->get()->error('❌ backup job failed', [
            'backup_type' => $this->backupType->value,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private function backupDatabase(NotifierDatabaseService $service): void
    {
        $path = $service->createDatabaseBackup();
        $service->sendDatabaseBackup($path);
    }

    private function backupStorage(NotifierStorageService $service): void
    {
        $path = $service->createStorageBackup();

        if ($path === '') {
            return;
        }

        $service->sendStorageBackup($path);
    }
}
