<?php

namespace Devuni\Notifier\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class NotifierController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($response = $this->checkEnvironment()) {
            return $response;
        }

        return match ($request->param) {
            'backup_database' => $this->backupDatabase(),
            'backup_storage' => $this->backupStorage(),
            default => $this->backupTypeNotFound($request)
        };
    }

    private function checkEnvironment(): ?JsonResponse
    {
        $missing_variables = [];

        if (empty(config('notifier.backup_zip_password'))) {
            $missing_variables[] = 'BACKUP_ZIP_PASSWORD';
        }

        if (empty(config('notifier.backup_code'))) {
            $missing_variables[] = 'BACKUP_CODE';
        }

        if (empty(config('notifier.backup_url'))) {
            $missing_variables[] = 'BACKUP_URL';
        }

        if (! empty($missing_variables)) {
            return response()->json([
                'message' => 'The following environment variables are missing or empty:',
                'variables' => $missing_variables,
            ], 500);
        }

        return null;
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
            ]);
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
