<?php

return [
    // Backup ZIP files are stored with permissions 0600 (read/write for owner only)
    'backup_code' => env('BACKUP_CODE'),
    'backup_url' => env('BACKUP_URL'),
    'backup_zip_password' => env('BACKUP_ZIP_PASSWORD', 'secret123'),
];
