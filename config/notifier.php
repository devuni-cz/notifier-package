<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | The backup code is used to authenticate API requests from the central
    | notifier application. This must match on both sides.
    |
    */
    'backup_code' => env('NOTIFIER_BACKUP_CODE', env('BACKUP_CODE')),

    /*
    |--------------------------------------------------------------------------
    | Central Notifier URL
    |--------------------------------------------------------------------------
    |
    | The URL where backup files will be sent. This is the endpoint on
    | the central notifier.devuni.cz application.
    |
    */
    'backup_url' => env('NOTIFIER_URL', env('BACKUP_URL')),

    /*
    |--------------------------------------------------------------------------
    | Backup ZIP Password
    |--------------------------------------------------------------------------
    |
    | Password used to encrypt the storage backup ZIP files.
    | This should be a strong, unique password.
    |
    */
    'backup_zip_password' => env('NOTIFIER_BACKUP_PASSWORD', env('BACKUP_ZIP_PASSWORD')),

    /*
    |--------------------------------------------------------------------------
    | Excluded Database Tables
    |--------------------------------------------------------------------------
    |
    | Database tables that should be excluded from the database backup.
    | Useful for excluding large log tables or temporary data.
    |
    | Examples:
    | - 'telescope_entries'      -> Laravel Telescope data
    | - 'telescope_entries_tags' -> Telescope relation table
    | - 'pulse_entries'          -> Laravel Pulse data
    | - 'sessions'               -> User sessions
    | - 'cache'                  -> Cache table
    |
    */
    'excluded_tables' => [],

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Files or directories that should be excluded from storage backup.
    | Paths are relative to storage/app/public.
    |
    | Examples:
    | - '.gitignore'        -> exclude .gitignore file
    | - 'temp'              -> exclude entire temp directory
    | - 'logs/debug.log'    -> exclude specific file
    |
    */
    'excluded_files' => [
        '.gitignore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | The logging channel used for backup operations.
    | Falls back to 'daily' if the specified channel doesn't exist.
    |
    */
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Control whether the package registers its API routes and
    | customize the route prefix.
    |
    | Set 'routes_enabled' to false if you want to define your
    | own routes using the package controller.
    |
    */
    'routes_enabled' => env('NOTIFIER_ROUTES_ENABLED', true),
    'route_prefix' => env('NOTIFIER_ROUTE_PREFIX', 'api/notifier'),
];
