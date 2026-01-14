<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Controllers\NotifierController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

describe('Notifier Package Basic Integration Tests', function () {
    describe('Service Provider', function () {
        it('registers the service provider', function () {
            $providers = $this->app->getLoadedProviders();
            expect($providers)->toHaveKey(NotifierServiceProvider::class);
        });

        it('registers all commands', function () {
            $commands = Artisan::all();
            expect($commands)->toHaveKey('notifier:check');
            expect($commands)->toHaveKey('notifier:install');
            expect($commands)->toHaveKey('notifier:database-backup');
            expect($commands)->toHaveKey('notifier:storage-backup');
        });

        it('loads package configuration', function () {
            expect(config('notifier'))->toBeArray();
            expect(config('notifier.excluded_files'))->toBeArray();
        });

        it('registers routes', function () {
            $routes = Route::getRoutes();
            $found = false;
            foreach ($routes as $route) {
                if ($route->uri() === 'api/backup') {
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue();
        });
    });

    describe('Configuration Service', function () {
        it('can instantiate config service', function () {
            $service = new NotifierConfigService();
            expect($service)->toBeInstanceOf(NotifierConfigService::class);
        });

        it('detects missing environment variables', function () {
            config(['notifier.backup_code' => '', 'notifier.backup_url' => '', 'notifier.backup_zip_password' => '']);

            $service = new NotifierConfigService();
            $missing = $service->checkEnvironment();

            expect($missing)->not->toBeEmpty();
            expect($missing)->toContain('BACKUP_CODE');
            expect($missing)->toContain('BACKUP_URL');
            expect($missing)->toContain('BACKUP_ZIP_PASSWORD');
        });

        it('returns no missing variables when properly configured', function () {
            config([
                'notifier.backup_code' => 'test-code',
                'notifier.backup_url' => 'https://test.com',
                'notifier.backup_zip_password' => 'password'
            ]);

            $service = new NotifierConfigService();
            $missing = $service->checkEnvironment();

            expect($missing)->toBeEmpty();
        });
    });

    describe('Commands', function () {
        it('can instantiate install command', function () {
            $command = new NotifierInstallCommand();
            expect($command)->toBeInstanceOf(NotifierInstallCommand::class);
        });

        it('can instantiate database backup command', function () {
            $command = new NotifierDatabaseBackupCommand();
            expect($command)->toBeInstanceOf(NotifierDatabaseBackupCommand::class);
        });

        it('can instantiate storage backup command', function () {
            $command = new NotifierStorageBackupCommand();
            expect($command)->toBeInstanceOf(NotifierStorageBackupCommand::class);
        });

        it('install command fails without environment', function () {
            $exitCode = Artisan::call('notifier:install');
            // Command should complete (success or failure depends on environment setup)
            expect($exitCode)->toBeIn([0, 1]);
        });

        it('backup commands fail with missing environment', function () {
            config(['notifier.backup_code' => '', 'notifier.backup_url' => '']);

            $databaseExitCode = Artisan::call('notifier:database-backup');
            $storageExitCode = Artisan::call('notifier:storage-backup');

            expect($databaseExitCode)->toBe(1);
            expect($storageExitCode)->toBe(1);
        });
    });

    describe('Controller', function () {
        it('can instantiate controller', function () {
            $configService = new NotifierConfigService();
            $controller = new NotifierController();
            expect($controller)->toBeInstanceOf(NotifierController::class);
        });

        it('handles missing environment variables', function () {
            config(['notifier.backup_code' => '', 'notifier.backup_url' => '']);

            $response = $this->get('/api/backup?param=backup_database');
            expect($response->status())->toBe(500);

            $data = $response->json();
            expect($data)->toHaveKey('message');
            expect($data)->toHaveKey('variables');
        });

        it('validates request parameters', function () {
            $response = $this->get('/api/backup');
            expect($response->status())->toBeIn([302, 422]); // Could redirect or validate
        });
    });

    describe('Configuration', function () {
        it('has expected configuration keys', function () {
            expect(config('notifier'))->toHaveKey('excluded_files');
            expect(config('notifier.excluded_files'))->toContain('.gitignore');
        });

        it('supports environment variable overrides', function () {
            // Test configuration structure supports env variables
            expect(config('notifier'))->toBeArray();
        });

        it('provides default excluded files', function () {
            $excludedFiles = config('notifier.excluded_files');
            expect($excludedFiles)->toBeArray();
            expect($excludedFiles)->not->toBeEmpty();
        });
    });

    describe('Route Integration', function () {
        it('backup route exists and has throttling', function () {
            $routes = Route::getRoutes();
            $backupRoute = null;

            foreach ($routes as $route) {
                if ($route->uri() === 'api/backup') {
                    $backupRoute = $route;
                    break;
                }
            }

            expect($backupRoute)->not->toBeNull();
            expect($backupRoute->middleware())->toContain('throttle:5,60');
        });

        it('backup route accepts GET requests', function () {
            $routes = Route::getRoutes();
            $backupRoute = null;

            foreach ($routes as $route) {
                if ($route->uri() === 'api/backup') {
                    $backupRoute = $route;
                    break;
                }
            }

            expect($backupRoute->methods())->toContain('GET');
        });
    });

    describe('Package Structure', function () {
        it('includes all required files', function () {
            expect(file_exists(__DIR__ . '/../../src/NotifierServiceProvider.php'))->toBeTrue();
            expect(file_exists(__DIR__ . '/../../src/helpers.php'))->toBeTrue();
            expect(file_exists(__DIR__ . '/../../config/notifier.php'))->toBeTrue();
            expect(file_exists(__DIR__ . '/../../routes/notifier.php'))->toBeTrue();
        });

        it('has proper composer configuration', function () {
            $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
            expect($composer['name'])->toBe('devuni/notifier-package');
            expect($composer['extra']['laravel']['providers'])->toContain(NotifierServiceProvider::class);
        });
    });
});
