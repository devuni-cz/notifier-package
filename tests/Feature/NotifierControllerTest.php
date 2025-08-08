<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('validates param', function (string $uri) {
    $response = $this->getJson($uri);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['param']);
})->with([
    'missing' => '/api/backup',
    'invalid' => '/api/backup?param=invalid',
]);

it('returns error when database backup fails', function () {
    config([
        'notifier.backup_code' => 'code',
        'notifier.backup_url' => 'url',
        'notifier.backup_zip_password' => 'pass',
    ]);

    Artisan::swap(new class
    {
        public function call($command, array $parameters = [], $outputBuffer = null)
        {
            throw new Exception('test error');
        }
    });

    $response = $this->getJson('/api/backup?param=backup_database');

    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Database backup failed.',
        'error' => 'test error',
    ]);
});

it('returns error when storage backup fails', function () {
    config([
        'notifier.backup_code' => 'code',
        'notifier.backup_url' => 'url',
        'notifier.backup_zip_password' => 'pass',
    ]);

    Artisan::swap(new class
    {
        public function call($command, array $parameters = [], $outputBuffer = null)
        {
            throw new Exception('test error');
        }
    });

    $response = $this->getJson('/api/backup?param=backup_storage');

    $response->assertStatus(500);
    $response->assertJson([
        'message' => 'Storage backup failed.',
        'error' => 'test error',
    ]);
});
