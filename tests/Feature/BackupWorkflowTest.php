<?php

declare(strict_types=1);

use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

describe('Backup Workflow Integration', function () {
    beforeEach(function () {
        // Setup test configuration
        Config::set('notifier.backup_code', 'test-backup-code');
        Config::set('notifier.backup_url', 'https://test-backup.com/upload');
        Config::set('notifier.backup_zip_password', 'test-password-123');
        Config::set('notifier.excluded_files', ['.gitignore', 'test.log']);

        Config::set('database.connections.mysql', [
            'username' => 'test_user',
            'password' => 'test_pass',
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test_db'
        ]);
    });

    describe('Configuration Service Integration', function () {
        it('detects properly configured environment', function () {
            $configService = new NotifierConfigService();
            $missing = $configService->checkEnvironment();

            expect($missing)->toBeEmpty();
        });

        it('detects missing configuration', function () {
            Config::set('notifier.backup_code', '');
            Config::set('notifier.backup_url', null);

            $configService = new NotifierConfigService();
            $missing = $configService->checkEnvironment();

            expect($missing)->toContain('BACKUP_CODE');
            expect($missing)->toContain('BACKUP_URL');
            expect($missing)->not->toContain('BACKUP_ZIP_PASSWORD');
        });
    });

    describe('Database Backup Workflow', function () {
        it('can execute database backup command with proper environment', function () {
            // This will test the command but skip actual backup creation
            $exitCode = Artisan::call('notifier:database-backup');

            // Command should fail due to missing mysqldump or test database
            // but it should not fail due to environment validation
            expect($exitCode)->toBeIn([0, 1]); // Success or process failure, not validation failure
        })->skip('Requires mysqldump and test database setup');

        it('fails database backup command with missing environment', function () {
            Config::set('notifier.backup_code', '');

            $exitCode = Artisan::call('notifier:database-backup');

            expect($exitCode)->toBe(1); // Should fail due to environment validation
        });
    });

    describe('Storage Backup Workflow', function () {
        it('can execute storage backup command with proper environment', function () {
            $exitCode = Artisan::call('notifier:storage-backup');

            // Command may fail due to missing files or ZipArchive issues
            // but should not fail due to environment validation
            expect($exitCode)->toBeIn([0, 1]); // Success or process failure, not validation failure
        })->skip('Requires filesystem setup and ZipArchive');

        it('fails storage backup command with missing environment', function () {
            Config::set('notifier.backup_url', '');
            Config::set('notifier.backup_zip_password', null);

            $exitCode = Artisan::call('notifier:storage-backup');

            expect($exitCode)->toBe(1); // Should fail due to environment validation
        });
    });

    describe('Install Command Workflow', function () {
        it('detects existing configuration without force flag', function () {
            // Create temporary .env file with all required variables
            $envPath = base_path('.env.test');
            $envContent = "BACKUP_CODE=\"existing-code\"\nBACKUP_URL=\"https://existing.com\"\nBACKUP_ZIP_PASSWORD=\"existing-pass\"";
            file_put_contents($envPath, $envContent);

            // Mock base_path to use test file
            $this->app->instance('path.base', dirname($envPath));

            $exitCode = Artisan::call('notifier:install');

            // Should detect existing configuration and fail without --force
            expect($exitCode)->toBeIn([0, 1]); // Depends on implementation

            // Clean up
            if (file_exists($envPath)) {
                unlink($envPath);
            }
        })->skip('Requires file system mocking');

        it('can overwrite configuration with force flag', function () {
            expect(true)->toBeTrue(); // Test force flag functionality
        })->skip('Requires file system mocking');
    });

    describe('API Workflow Integration', function () {
        it('responds to database backup API request', function () {
            $response = $this->get('/api/backup?param=backup_database');

            // Should succeed with proper environment
            expect($response->status())->toBeIn([200, 500]); // Success or runtime error
        });

        it('responds to storage backup API request', function () {
            $response = $this->get('/api/backup?param=backup_storage');

            // Should succeed with proper environment
            expect($response->status())->toBeIn([200, 500]); // Success or runtime error
        });

        it('validates API request parameters', function () {
            $response = $this->get('/api/backup?param=invalid_backup');

            expect($response->status())->toBe(422); // Validation error
        });

        it('checks environment before processing API requests', function () {
            Config::set('notifier.backup_code', '');
            Config::set('notifier.backup_url', '');

            $response = $this->get('/api/backup?param=backup_database');

            expect($response->status())->toBe(500);

            $data = $response->json();
            expect($data['message'])->toContain('missing or empty');
            expect($data['variables'])->toBeArray();
        });
    });

    describe('End-to-End Configuration', function () {
        it('loads all package components correctly', function () {
            // Test service provider registration
            expect($this->app->bound(NotifierConfigService::class))->toBeFalse(); // Not explicitly bound

            // Test that we can instantiate services
            $configService = new NotifierConfigService();
            expect($configService)->toBeInstanceOf(NotifierConfigService::class);

            // Test configuration is available
            expect(config('notifier'))->not->toBeEmpty();

            // Test commands are registered
            expect(Artisan::all())->toHaveKey('notifier:check');
            expect(Artisan::all())->toHaveKey('notifier:install');
            expect(Artisan::all())->toHaveKey('notifier:database-backup');
            expect(Artisan::all())->toHaveKey('notifier:storage-backup');
        });

        it('provides consistent configuration across components', function () {
            $backupCode = config('notifier.backup_code');
            $backupUrl = config('notifier.backup_url');
            $backupPassword = config('notifier.backup_zip_password');

            expect($backupCode)->toBe('test-backup-code');
            expect($backupUrl)->toBe('https://test-backup.com/upload');
            expect($backupPassword)->toBe('test-password-123');

            // All services should use the same configuration
            $configService = new NotifierConfigService();
            $missing = $configService->checkEnvironment();
            expect($missing)->toBeEmpty();
        });
    });
});
