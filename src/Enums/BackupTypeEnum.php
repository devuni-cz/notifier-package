<?php

declare(strict_types=1);

namespace Devuni\Notifier\Enums;

enum BackupTypeEnum: string
{
    case Database = 'backup_database';
    case Storage = 'backup_storage';

    /**
     * Get all valid backup type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get validation rule string for Laravel validation.
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::values());
    }
}
