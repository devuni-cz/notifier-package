<?php

declare(strict_types=1);

namespace Devuni\Notifier\Controllers;

use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Requests\BackupRequest;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Http\JsonResponse;
use Throwable;

class NotifierSendBackupController
{
    public function __construct(
        private readonly NotifierDatabaseService $databaseService,
        private readonly NotifierStorageService $storageService,
    ) {}

    public function __invoke(BackupRequest $request): JsonResponse
    {
        return match ($request->backupType()) {
            BackupTypeEnum::Database => $this->backupDatabase(),
            BackupTypeEnum::Storage => $this->backupStorage(),
        };
    }

    private function backupDatabase(): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $backupPath = $this->databaseService->createDatabaseBackup();
            $this->databaseService->sendDatabaseBackup($backupPath);

            $duration = round(microtime(true) - $startTime, 2);

            return response()->json([
                'success' => true,
                'message' => 'Database backup completed successfully.',
                'backup_type' => 'database',
                'duration_seconds' => $duration,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            NotifierLogger::get()->error('Database backup failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database backup failed.',
                'backup_type' => 'database',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    private function backupStorage(): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $backupPath = $this->storageService->createStorageBackup();
            $this->storageService->sendStorageBackup($backupPath);

            $duration = round(microtime(true) - $startTime, 2);

            return response()->json([
                'success' => true,
                'message' => 'Storage backup completed successfully.',
                'backup_type' => 'storage',
                'duration_seconds' => $duration,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            NotifierLogger::get()->error('Storage backup failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Storage backup failed.',
                'backup_type' => 'storage',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
