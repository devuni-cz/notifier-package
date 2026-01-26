<?php

declare(strict_types=1);

use Devuni\Notifier\Support\NotifierLogger;
use Psr\Log\LoggerInterface;

it('returns a LoggerInterface instance', function (): void {
    $logger = NotifierLogger::get();

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

it('uses backup channel when it exists', function (): void {
    // Configure the backup channel
    config()->set('logging.channels.backup', [
        'driver' => 'single',
        'path' => storage_path('logs/backup.log'),
    ]);
    config()->set('notifier.logging_channel', 'backup');

    $logger = NotifierLogger::get();

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

it('falls back to default channel when configured channel does not exist', function (): void {
    // Ensure backup channel doesn't exist
    config()->set('logging.channels.backup', null);
    config()->set('notifier.logging_channel', 'backup');
    config()->set('logging.default', 'single');

    $logger = NotifierLogger::get();

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

it('respects custom logging channel from config', function (): void {
    config()->set('logging.channels.custom_channel', [
        'driver' => 'single',
        'path' => storage_path('logs/custom.log'),
    ]);
    config()->set('notifier.logging_channel', 'custom_channel');

    $logger = NotifierLogger::get();

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});
