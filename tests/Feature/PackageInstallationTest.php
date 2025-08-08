<?php

declare(strict_types=1);

use Illuminate\Console\Command;

it('returns failure when .env creation is declined', function () {
    $envPath = base_path('.env');
    if (file_exists($envPath)) {
        unlink($envPath);
    }

    $this->artisan('notifier:install')
        ->expectsConfirmation('Do you want to create it from .env.example', 'no')
        ->assertExitCode(Command::FAILURE);
});

it('returns success when environment is configured', function () {
    $envPath = base_path('.env');
    if (file_exists($envPath)) {
        unlink($envPath);
    }

    $this->artisan('notifier:install')
        ->expectsConfirmation('Do you want to create it from .env.example', 'yes')
        ->expectsQuestion('BACKUP_CODE: ', 'code')
        ->expectsQuestion('BACKUP_URL: ', 'https://example.com')
        ->expectsQuestion('BACKUP_ZIP_PASSWORD: ', 'secret')
        ->assertExitCode(Command::SUCCESS);

    expect(file_exists($envPath))->toBeTrue();
    unlink($envPath);
});
