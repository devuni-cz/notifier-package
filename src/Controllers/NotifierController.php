<?php

namespace Devuni\Notifier\Controllers;

use Devuni\Notifier\Services\NotifierConfigService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class NotifierController
{
    public function __invoke(Request $request, NotifierConfigService $configService): JsonResponse
    {
        $request->validate(['param' => 'required|in:backup_database,backup_storage']);

        $missingVariables = $configService->checkEnvironment();

        if (!empty($missingVariables)) {
            return response()->json([
                'message'   => 'The following environment variables are missing or empty:',
                'variables' => $missingVariables,
            ], 500);
        }

        return match ($request->param) {
            'backup_database' => $this->backupDatabase(),
            'backup_storage' => $this->backupStorage(),
            default => $this->backupTypeNotFound($request)
        };
    }

    private function backupDatabase(): JsonResponse
    {
        try {
            Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');

            Artisan::call('notifier:database-backup');

            return response()->json([
                'message' => 'Database backup has been created successfully.',
            ]);
        } catch (Exception $e) {
            Log::channel('backup')->error('Database backup failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Database backup failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function backupStorage(): JsonResponse
    {
        try {
            Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');

            Artisan::call('notifier:storage-backup');

            return response()->json([
                'message' => 'Storage backup has been created successfully.',
            ]);
        } catch (Exception $e) {
            Log::channel('backup')->error('Storage backup failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Storage backup failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function backupTypeNotFound(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Backup type not found.',
            'request' => $request->param,
        ], 400);
    }
}
