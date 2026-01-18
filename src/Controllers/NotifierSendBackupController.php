<?php

declare(strict_types=1);

namespace Devuni\Notifier\Controllers;

use Devuni\Notifier\Enums\BackupTypeEnum;
use Devuni\Notifier\Requests\BackupRequest;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Support\NotifierLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class NotifierSendBackupController
{
    public function __invoke(BackupRequest $request, NotifierConfigService $configService): JsonResponse
    {
        $missingVariables = $configService->checkEnvironment();

        if (! empty($missingVariables)) {
            return response()->json([
                'message' => 'The following environment variables are missing or empty:',
                'variables' => $missingVariables,
            ], 500);
        }

        return match ($request->backupType()) {
            BackupTypeEnum::Database => $this->backupDatabase(),
            BackupTypeEnum::Storage => $this->backupStorage(),
        };
    }

    private function backupDatabase(): JsonResponse
    {
        try {
            NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

            Artisan::call('notifier:database-backup');

            return response()->json([
                'message' => 'Database backup has been created successfully.',
            ]);
        } catch (Exception $e) {
            NotifierLogger::get()->error('Database backup failed.', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Database backup failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function backupStorage(): JsonResponse
    {
        try {
            NotifierLogger::get()->info('⚙️ STARTING NEW BACKUP ⚙️');

            Artisan::call('notifier:storage-backup');

            return response()->json([
                'message' => 'Storage backup has been created successfully.',
            ]);
        } catch (Exception $e) {
            NotifierLogger::get()->error('Storage backup failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Storage backup failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
