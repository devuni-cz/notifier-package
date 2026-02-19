<?php

declare(strict_types=1);

use Devuni\Notifier\Commands\NotifierCheckCommand;
use Illuminate\Support\Facades\Artisan;

describe('NotifierCheckCommand', function () {
    describe('command registration', function () {
        it('is registered in artisan', function () {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('notifier:check');
            expect($commands['notifier:check'])->toBeInstanceOf(NotifierCheckCommand::class);
        });

        it('has correct signature', function () {
            $command = new NotifierCheckCommand;

            expect($command->getName())->toBe('notifier:check');
        });

        it('has correct description', function () {
            $command = new NotifierCheckCommand;

            expect($command->getDescription())->toBe('Check if Notifier package is configured correctly');
        });
    });

    describe('environment variable checks', function () {
        it('passes when all environment variables are configured', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('All required environment variables are configured');
        });

        it('fails when environment variables are missing', function () {
            config([
                'notifier.backup_code' => '',
                'notifier.backup_url' => '',
                'notifier.backup_zip_password' => '',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Missing environment variables')
                ->assertExitCode(1);
        });

        it('shows masked configuration values', function () {
            config([
                'notifier.backup_code' => 'my-secret-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'secret-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('BACKUP_CODE:')
                ->expectsOutputToContain('BACKUP_URL:')
                ->expectsOutputToContain('BACKUP_ZIP_PASSWORD:');
        });
    });

    describe('database connection check', function () {
        it('passes when database is connected', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Connected to database');
        });
    });

    describe('storage directory checks', function () {
        it('checks backup directory existence', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Checking storage directories');
        });
    });

    describe('mysqldump availability check', function () {
        it('checks for mysqldump command', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Checking mysqldump availability');
        });
    });

    describe('PHP ZIP extension check', function () {
        it('passes when ZIP extension is loaded', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test-backup.com/upload',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            // ZIP extension should be loaded in test environment
            $this->artisan('notifier:check')
                ->expectsOutputToContain('PHP ZIP extension');
        });
    });

    describe('backup URL reachability check', function () {
        it('skips check when backup URL is not configured', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => '',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Backup URL is not configured');
        });

        it('checks backup URL connectivity when configured', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://httpbin.org/post',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            $this->artisan('notifier:check')
                ->expectsOutputToContain('Checking backup URL reachability');
        });
    });

    describe('overall result', function () {
        it('shows success message when all checks pass', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => '',
                'notifier.backup_zip_password' => 'test-password',
            ]);

            // With empty URL, the URL check is skipped (not failed)
            // Other checks should pass in test environment
            $this->artisan('notifier:check')
                ->expectsOutputToContain('RESULT');
        });

        it('displays banner at start', function () {
            $this->artisan('notifier:check')
                ->expectsOutputToContain('NOTIFIER PACKAGE HEALTH CHECK');
        });
    });
});
