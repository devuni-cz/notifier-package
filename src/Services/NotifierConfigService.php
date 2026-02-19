<?php

namespace Devuni\Notifier\Services;

class NotifierConfigService
{
    public function checkEnvironment(): array
    {
        $missing = [];

        if (empty(config('notifier.backup_zip_password'))) {
            $missing[] = 'BACKUP_ZIP_PASSWORD';
        }

        if (empty(config('notifier.backup_code'))) {
            $missing[] = 'BACKUP_CODE';
        }

        if (empty(config('notifier.backup_url'))) {
            $missing[] = 'BACKUP_URL';
        }

        return $missing;
    }
}
