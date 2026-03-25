<?php

declare(strict_types=1);

use Devuni\Notifier\Support\NotifierLogger;
use Psr\Log\LoggerInterface;

it('returns a LoggerInterface instance', function (): void {
    $notifierLogger = new NotifierLogger;

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
});

it('uses backup channel when it exists', function (): void {
    config()->set('logging.channels.backup', [
        'driver' => 'single',
        'path' => storage_path('logs/backup.log'),
    ]);

    $notifierLogger = new NotifierLogger('backup');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->isUsingPreferredChannel())->toBeTrue();
});

it('falls back to default channel when configured channel does not exist', function (): void {
    config()->set('logging.channels.backup', null);
    config()->set('logging.default', 'single');

    $notifierLogger = new NotifierLogger('backup');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->isUsingPreferredChannel())->toBeFalse();
});

it('respects custom logging channel from config', function (): void {
    config()->set('logging.channels.custom_channel', [
        'driver' => 'single',
        'path' => storage_path('logs/custom.log'),
    ]);

    $notifierLogger = new NotifierLogger('custom_channel');

    expect($notifierLogger->get())->toBeInstanceOf(LoggerInterface::class);
    expect($notifierLogger->getPreferredChannel())->toBe('custom_channel');
    expect($notifierLogger->isUsingPreferredChannel())->toBeTrue();
});
