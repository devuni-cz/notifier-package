<?php

use Devuni\Notifier\Services\NotifierStorageService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    config()->set('notifier.backup_path', storage_path('app/backups'));
    config()->set('notifier.backup_zip_password', 'zip');
    config()->set('notifier.log_channel', 'backup');

    File::ensureDirectoryExists(storage_path('app/public'));
    File::put(storage_path('app/public/test.txt'), 'data');
});

it('creates a zip with restrictive permissions', function () {
    $path = NotifierStorageService::createStorageBackup();

    $perms = substr(sprintf('%o', fileperms($path)), -4);
    expect($perms)->toBe('0600');

    unlink($path);
});

it('falls back to temp directory when path is not writable', function () {
    config()->set('notifier.backup_path', '/proc/notifier');

    $path = NotifierStorageService::createStorageBackup();

    $fallback = rtrim(sys_get_temp_dir(), '/').'/notifier-backups';
    expect($path)->toStartWith($fallback);

    unlink($path);
});
