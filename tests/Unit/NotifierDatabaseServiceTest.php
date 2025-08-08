<?php

declare(strict_types=1);

use Carbon\Carbon;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('returns backup path when process succeeds', function () {
    Storage::fake('local');

    Config::set('database.connections.mysql', [
        'username' => 'user',
        'password' => 'password',
        'host' => '127.0.0.1',
        'database' => 'database',
    ]);

    Carbon::setTestNow('2024-01-01');

    $mock = Mockery::mock('overload:Symfony\\Component\\Process\\Process');
    $mock->shouldReceive('run')->once();
    $mock->shouldReceive('isSuccessful')->once()->andReturnTrue();

    $path = NotifierDatabaseService::createDatabaseBackup();

    expect($path)->toBe(storage_path('app/private/backup-2024-01-01.sql'));

    Carbon::setTestNow();
});

it('throws an exception when process fails', function () {
    Storage::fake('local');

    Config::set('database.connections.mysql', [
        'username' => 'user',
        'password' => 'password',
        'host' => '127.0.0.1',
        'database' => 'database',
    ]);

    $mock = Mockery::mock('overload:Symfony\\Component\\Process\\Process');
    $mock->shouldReceive('run')->once();
    $mock->shouldReceive('isSuccessful')->once()->andReturnFalse();
    $mock->shouldReceive('getErrorOutput')->once()->andReturn('error');

    NotifierDatabaseService::createDatabaseBackup();
})->throws(RuntimeException::class);

afterEach(function () {
    Mockery::close();
    Carbon::setTestNow();
});
