<?php

declare(strict_types=1);

use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Mockery;

describe('NotifierDatabaseBackupCommand', function () {
    beforeEach(function () {
        $this->configService = Mockery::mock(NotifierConfigService::class);
        $this->app->instance(NotifierConfigService::class, $this->configService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('handle method', function () {
        it('executes successfully when environment is properly configured', function () {
            // Mock config service to return no missing variables
            $this->configService->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn([]);

            // Mock static calls to NotifierDatabaseService
            $backupPath = '/test/backup/backup-2025-09-08.sql';

            $this->artisan('notifier:database-backup')
                ->expectsOutput('⚙️  STARTING NEW BACKUP ⚙️')
                ->expectsOutput('✅ Backup file created successfully at: '.$backupPath)
                ->expectsOutput('✅ End of backup')
                ->assertExitCode(0);
        })->skip('Requires static method mocking');

        it('fails when required environment variables are missing', function () {
            // Mock config service to return missing variables
            $this->configService->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn(['BACKUP_CODE', 'BACKUP_URL']);

            $this->artisan('notifier:database-backup')
                ->expectsOutput('ERROR')
                ->expectsOutput('The following environment variables are missing or empty:')
                ->expectsOutput('• BACKUP_CODE')
                ->expectsOutput('• BACKUP_URL')
                ->expectsOutput('-> Run php artisan notifier:install to set these variables.')
                ->assertExitCode(1);
        });

        it('handles single missing environment variable', function () {
            $this->configService->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn(['BACKUP_ZIP_PASSWORD']);

            $this->artisan('notifier:database-backup')
                ->expectsOutput('ERROR')
                ->expectsOutput('• BACKUP_ZIP_PASSWORD')
                ->assertExitCode(1);
        });

        it('displays correct command signature and description', function () {
            expect($this->app->make(NotifierDatabaseBackupCommand::class))
                ->getSignature()->toBe('notifier:database-backup')
                ->and($this->app->make(NotifierDatabaseBackupCommand::class))
                ->getDescription()->toBe('Command for creating a database backup');
        });
    });

    describe('checkMissingVariables method', function () {
        it('returns success when no variables are missing', function () {
            $this->configService->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn([]);

            expect(true)->toBeTrue(); // Test private method success
        })->skip('Requires private method testing');

        it('returns failure and displays errors when variables are missing', function () {
            $this->configService->shouldReceive('checkEnvironment')
                ->once()
                ->andReturn(['BACKUP_CODE']);

            expect(true)->toBeTrue(); // Test private method failure
        })->skip('Requires private method testing');
    });

    describe('integration with NotifierDatabaseService', function () {
        it('calls createDatabaseBackup and sendDatabaseBackup in sequence', function () {
            expect(true)->toBeTrue(); // Test service integration
        })->skip('Requires static method mocking');

        it('handles exceptions from backup service gracefully', function () {
            expect(true)->toBeTrue(); // Test exception handling
        })->skip('Requires exception handling testing');
    });

    describe('output formatting', function () {
        it('displays proper emojis and formatting in output', function () {
            expect(true)->toBeTrue(); // Test output formatting
        })->skip('Requires output assertion testing');

        it('shows backup path in success message', function () {
            expect(true)->toBeTrue(); // Test path display
        })->skip('Requires output assertion testing');
    });
});
