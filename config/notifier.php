<?php

return [
    'backup_code' => env('BACKUP_CODE') ?: env('NOTIFIER_BACKUP_CODE'),
    'backup_url' => env('BACKUP_URL') ?: env('NOTIFIER_URL'),
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD') ?: env('NOTIFIER_BACKUP_PASSWORD', 'secret123'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Database Tables
    |--------------------------------------------------------------------------
    |
    | Here you may specify a list of database tables that should be
    | excluded from the database backup process.
    | Any table name listed here will be ignored when generating
    | the SQL dump.
    |
    | Examples:
    | 'telescope_entries'        -> exclude Laravel Telescope data
    | 'telescope_entries_tags'  -> exclude Telescope relation table
    | 'pulse_entries'           -> exclude Laravel Pulse data
    */
    'excluded_tables' => [
        'cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Here you may specify a list of files or files in directories that should be
    | excluded from the backup process. Any file path that
    | matches an entry in this array will not be copied into storage
    | or included inside the generated ZIP archive.
    |
    | Examples:
    | '.gitignore'       -> exclude the .gitignore file
    | 'public\text.txt'  -> exclude a specific file inside public folder
    */
    'excluded_files' => [
        '.gitignore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Preferred logging channel for notifier.
    |
    */
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),
];
