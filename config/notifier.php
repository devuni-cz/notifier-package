<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'backup_code' => env('BACKUP_CODE'),

    /*
    |--------------------------------------------------------------------------
    | Remote endpoint
    |--------------------------------------------------------------------------
    */
    'backup_url' => env('BACKUP_URL'),

    /*
    |--------------------------------------------------------------------------
    | Password used to encrypt storage backups
    |--------------------------------------------------------------------------
    */
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD', 'secret123'),

    /*
    |--------------------------------------------------------------------------
    | Location where backup files will be stored before uploading.
    |--------------------------------------------------------------------------
    */
    'backup_path' => env('NOTIFIER_BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Fallback path used when the configured backup path is not writable.
    |--------------------------------------------------------------------------
    */
    'backup_fallback_path' => env('NOTIFIER_FALLBACK_BACKUP_PATH', sys_get_temp_dir().'/notifier-backups'),

    /*
    |--------------------------------------------------------------------------
    | Default filesystem disk used for temporary backup storage.
    |--------------------------------------------------------------------------
    */
    'default_disk' => env('NOTIFIER_DEFAULT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Log channel used by the notifier when creating backups.
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('NOTIFIER_LOG_CHANNEL', 'backup'),
];
