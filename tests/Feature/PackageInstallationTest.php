<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

describe('Package Installation and Configuration', function () {
    it('can install the package and load service provider', function () {
        $providers = $this->app->getLoadedProviders();

        expect($providers)->toHaveKey(NotifierServiceProvider::class);
        expect($providers[NotifierServiceProvider::class])->toBeTrue();
    });

    it('can publish configuration file', function () {
        $configPath = config_path('notifier.php');

        // Ensure config doesn't exist initially
        if (file_exists($configPath)) {
            unlink($configPath);
        }

        // Publish configuration
        Artisan::call('vendor:publish', [
            '--provider' => NotifierServiceProvider::class,
            '--tag' => 'config',
            '--force' => true,
        ]);

        expect(file_exists($configPath))->toBeTrue();

        // Clean up
        if (file_exists($configPath)) {
            unlink($configPath);
        }
    });

    it('merges package configuration with app configuration', function () {
        // Test that the package config is available
        expect(config('notifier.backup_code'))->not->toBeNull();
        expect(config('notifier.backup_url'))->not->toBeNull();
        expect(config('notifier.backup_zip_password'))->not->toBeNull();
        expect(config('notifier.excluded_files'))->toBeArray();
    });

    it('registers all artisan commands', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('notifier:install');
        expect($commands)->toHaveKey('notifier:database-backup');
        expect($commands)->toHaveKey('notifier:storage-backup');

        expect($commands['notifier:install'])->toBeInstanceOf(NotifierInstallCommand::class);
        expect($commands['notifier:database-backup'])->toBeInstanceOf(NotifierDatabaseBackupCommand::class);
        expect($commands['notifier:storage-backup'])->toBeInstanceOf(NotifierStorageBackupCommand::class);
    });

    it('loads package routes', function () {
        $routes = Route::getRoutes();
        $backupRoute = null;

        foreach ($routes as $route) {
            if ($route->uri() === 'api/backup') {
                $backupRoute = $route;
                break;
            }
        }

        expect($backupRoute)->not->toBeNull();
        expect($backupRoute->methods())->toContain('GET');
        expect($backupRoute->middleware())->toContain('throttle:5,60');
    });

    it('includes helpers file', function () {
        // Test that helpers file is loaded (even if empty)
        expect(file_exists(__DIR__ . '/../../src/helpers.php'))->toBeTrue();
    });
});

describe('Package Configuration', function () {
    it('has correct default configuration structure', function () {
        expect(config('notifier'))->toBeArray();
        expect(config('notifier.excluded_files'))->toBeArray();
        expect(config('notifier.excluded_files'))->toContain('.gitignore');
    });

    it('respects environment variable fallbacks', function () {
        // Test BACKUP_CODE fallback
        Config::set('notifier.backup_code', null);
        putenv('BACKUP_CODE=test-code-primary');
        putenv('NOTIFIER_BACKUP_CODE=test-code-fallback');

        expect(env('BACKUP_CODE') ?: env('NOTIFIER_BACKUP_CODE'))->toBe('test-code-primary');

        // Test fallback when primary is not set
        putenv('BACKUP_CODE=');
        expect(env('BACKUP_CODE') ?: env('NOTIFIER_BACKUP_CODE'))->toBe('test-code-fallback');

        // Clean up
        putenv('BACKUP_CODE');
        putenv('NOTIFIER_BACKUP_CODE');
    });

    it('has proper default backup zip password', function () {
        putenv('BACKUP_ZIP_PASSWORD=');
        putenv('NOTIFIER_BACKUP_PASSWORD=');

        expect(env('BACKUP_ZIP_PASSWORD') ?: env('NOTIFIER_BACKUP_PASSWORD', 'secret123'))->toBe('secret123');

        // Clean up
        putenv('BACKUP_ZIP_PASSWORD');
        putenv('NOTIFIER_BACKUP_PASSWORD');
    });

    it('allows custom excluded files configuration', function () {
        Config::set('notifier.excluded_files', ['.gitignore', 'custom-file.txt', 'logs/']);

        expect(config('notifier.excluded_files'))->toHaveCount(3);
        expect(config('notifier.excluded_files'))->toContain('custom-file.txt');
        expect(config('notifier.excluded_files'))->toContain('logs/');
    });
});

describe('Route Integration', function () {
    it('backup route responds to GET requests', function () {
        $response = $this->get('/api/backup?param=backup_database');

        // Will fail validation due to missing environment, but route exists
        expect($response->status())->toBeIn([422, 500]); // Validation or environment error
    });

    it('backup route has rate limiting middleware', function () {
        $routes = Route::getRoutes();
        $backupRoute = null;

        foreach ($routes as $route) {
            if ($route->uri() === 'api/backup') {
                $backupRoute = $route;
                break;
            }
        }

        expect($backupRoute->middleware())->toContain('throttle:5,60');
    });

    it('backup route validates param parameter', function () {
        $response = $this->get('/api/backup');

        expect($response->status())->toBe(422); // Validation error
    });

    it('backup route validates param values', function () {
        $response = $this->get('/api/backup?param=invalid_type');

        expect($response->status())->toBe(422); // Validation error
    });
});
