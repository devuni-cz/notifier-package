<?php

use Devuni\Notifier\Services\NotifierDatabaseService;
use Mockery;
use Symfony\Component\Process\Process;

beforeEach(function () {
    config()->set('database.connections.mysql', [
        'username' => 'user',
        'password' => 'pass',
        'host' => 'localhost',
        'database' => 'db',
    ]);
    config()->set('notifier.backup_path', storage_path('app/backups'));
    config()->set('notifier.log_channel', 'backup');
});

it('creates a database backup on success', function () {
    $process = Mockery::mock('overload:'.Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('isSuccessful')->andReturn(true);

    $path = NotifierDatabaseService::createDatabaseBackup();

    expect($path)->toEndWith('.sql');
});

it('throws an exception when mysqldump fails', function () {
    $process = Mockery::mock('overload:'.Process::class);
    $process->shouldReceive('run');
    $process->shouldReceive('isSuccessful')->andReturn(false);
    $process->shouldReceive('getErrorOutput')->andReturn('failed');

    NotifierDatabaseService::createDatabaseBackup();
})->throws(RuntimeException::class);
