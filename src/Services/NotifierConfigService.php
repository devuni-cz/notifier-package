<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

final class NotifierConfigService
{
    public function checkEnvironment(): array
    {
        $missing = [];

        if (empty(config('notifier.backup_zip_password'))) {
            $missing[] = 'NOTIFIER_BACKUP_PASSWORD';
        }

        if (empty(config('notifier.backup_code'))) {
            $missing[] = 'NOTIFIER_BACKUP_CODE';
        }

        if (empty(config('notifier.backup_url'))) {
            $missing[] = 'NOTIFIER_URL';
        }

        return $missing;
    }
}
