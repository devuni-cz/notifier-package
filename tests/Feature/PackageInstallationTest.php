<?php

declare(strict_types=1);

it('can publish configuration', function () {
    $this->artisan('vendor:publish', ['--tag' => 'config'])->assertExitCode(0);

    expect(file_exists(config_path('notifier.php')))->toBeTrue();
});

it('protects the backup route', function () {
    config()->set('notifier.backup_code', 'test');

    $this->get('/api/backup?param=backup_storage')->assertStatus(401);
    config()->set('notifier.backup_url', 'https://example.com');
    config()->set('notifier.backup_zip_password', 'zip');

    \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/public'));
    \Illuminate\Support\Facades\File::put(storage_path('app/public/test.txt'), 'data');
    config()->set('notifier.backup_path', storage_path('app/backups'));
    config()->set('notifier.log_channel', 'backup');

    $this->withHeaders(['X-Backup-Token' => 'test'])
        ->get('/api/backup?param=backup_storage')
        ->assertStatus(200);
});

it('rejects invalid backup type', function () {
    config()->set('notifier.backup_code', 'test');
    config()->set('notifier.backup_url', 'https://example.com');
    config()->set('notifier.backup_zip_password', 'zip');

    $this->withHeaders(['X-Backup-Token' => 'test'])
        ->getJson('/api/backup?param=invalid')
        ->assertStatus(422);
});
