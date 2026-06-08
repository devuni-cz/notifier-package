<?php

declare(strict_types=1);

use Devuni\Notifier\Services\NotifierLoggerService;
use Psr\Log\LoggerInterface;

it('returns a LoggerInterface instance', function (): void {
    $notifierLogger = new NotifierLoggerService;

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
});

it('uses backup channel when it exists', function (): void {
    config()->set('logging.channels.backup', [
        'driver' => 'single',
        'path' => storage_path('logs/backup.log'),
    ]);

    $notifierLogger = new NotifierLoggerService('backup');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->isUsingPreferredChannel())->toBeTrue();
});

it('falls back to default channel when configured channel does not exist', function (): void {
    config()->set('logging.channels.backup', null);
    config()->set('logging.default', 'single');

    $notifierLogger = new NotifierLoggerService('backup');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->isUsingPreferredChannel())->toBeFalse();
});

it('respects custom logging channel from config', function (): void {
    config()->set('logging.channels.custom_channel', [
        'driver' => 'single',
        'path' => storage_path('logs/custom.log'),
    ]);

    $notifierLogger = new NotifierLoggerService('custom_channel');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->getPreferredChannel())->toBe('custom_channel');
    expect($notifierLogger->isUsingPreferredChannel())->toBeTrue();
});
