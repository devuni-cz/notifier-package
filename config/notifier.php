<?php

return [
    'backup_code' => env('BACKUP_CODE') ?: env('NOTIFIER_BACKUP_CODE'),
    'backup_url' => env('BACKUP_URL') ?: env('NOTIFIER_URL'),
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD') ?: env('NOTIFIER_BACKUP_PASSWORD', 'secret123'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Preferred logging channel for notifier.
    |
    */
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),

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
    ]
];
