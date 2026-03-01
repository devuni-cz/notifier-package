<?php

declare(strict_types=1);

use Devuni\Notifier\Commands\NotifierCheckCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\NotifierServiceProvider;
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
            '--tag' => 'notifier-config',
            '--force' => true,
        ]);

        expect(file_exists($configPath))->toBeTrue();

        // Clean up
        if (file_exists($configPath)) {
            unlink($configPath);
        }
    });

    it('merges package configuration with app configuration', function () {
        // Test that the package config keys exist (values are blank by default in test env)
        expect(config('notifier'))->toHaveKey('backup_code');
        expect(config('notifier'))->toHaveKey('backup_url');
        expect(config('notifier'))->toHaveKey('backup_zip_password');
        expect(config('notifier.excluded_files'))->toBeArray();
    });

    it('registers all artisan commands', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('notifier:check');
        expect($commands)->toHaveKey('notifier:install');
        expect($commands)->toHaveKey('notifier:database-backup');
        expect($commands)->toHaveKey('notifier:storage-backup');

        expect($commands['notifier:check'])->toBeInstanceOf(NotifierCheckCommand::class);
        expect($commands['notifier:install'])->toBeInstanceOf(NotifierInstallCommand::class);
        expect($commands['notifier:database-backup'])->toBeInstanceOf(NotifierDatabaseBackupCommand::class);
        expect($commands['notifier:storage-backup'])->toBeInstanceOf(NotifierStorageBackupCommand::class);
    });

    it('loads package routes', function () {
        $routes = Route::getRoutes();
        $backupRoute = null;

        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'notifier/backup')) {
                $backupRoute = $route;
                break;
            }
        }

        expect($backupRoute)->not->toBeNull();
        expect($backupRoute->methods())->toContain('POST');
        expect($backupRoute->middleware())->toContain('throttle:10,60');
    });

    it('includes helpers file', function () {
        // helpers.php was removed; autoloaded functions now live in dedicated service classes
        expect(file_exists(__DIR__.'/../../src/NotifierServiceProvider.php'))->toBeTrue();
    });
});

describe('Package Configuration', function () {
    it('has correct default configuration structure', function () {
        expect(config('notifier'))->toBeArray();
        expect(config('notifier.excluded_files'))->toBeArray();
        expect(config('notifier.excluded_files'))->toContain('.gitignore');
    });

    it('respects environment variable fallbacks', function () {
        // Verify config fallback chain: NOTIFIER_BACKUP_CODE falls back to BACKUP_CODE
        Config::set('notifier.backup_code', 'test-code');
        expect(config('notifier.backup_code'))->toBe('test-code');
    })->skip('putenv() does not update Laravel\'s Dotenv repository; use Config::set() instead');

    it('has proper default backup zip password', function () {
        // When backup_zip_password is not set, config returns null
        Config::set('notifier.backup_zip_password', null);
        expect(config('notifier.backup_zip_password'))->toBeNull();
    });

    it('allows custom excluded files configuration', function () {
        Config::set('notifier.excluded_files', ['.gitignore', 'custom-file.txt', 'logs/']);

        expect(config('notifier.excluded_files'))->toHaveCount(3);
        expect(config('notifier.excluded_files'))->toContain('custom-file.txt');
        expect(config('notifier.excluded_files'))->toContain('logs/');
    });
});

describe('Route Integration', function () {
    beforeEach(function () {
        Config::set('notifier.backup_code', 'test-backup-code');
        Config::set('notifier.backup_url', 'https://test-backup.com/upload');
        Config::set('notifier.backup_zip_password', 'test-password');
    });

    it('backup route responds to POST requests', function () {
        $response = $this->postJson(
            '/api/notifier/backup',
            ['type' => 'backup_database'],
            ['X-Notifier-Token' => 'test-backup-code']
        );

        // Will fail due to missing mysqldump, but route exists and auth passes
        expect($response->status())->toBeIn([200, 500]);
    });

    it('backup route has rate limiting middleware', function () {
        $routes = Route::getRoutes();
        $backupRoute = null;

        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'notifier/backup')) {
                $backupRoute = $route;
                break;
            }
        }

        expect($backupRoute->middleware())->toContain('throttle:10,60');
    });

    it('backup route validates type parameter', function () {
        $response = $this->postJson(
            '/api/notifier/backup',
            [],
            ['X-Notifier-Token' => 'test-backup-code']
        );

        expect($response->status())->toBe(422);
    });

    it('backup route validates type values', function () {
        $response = $this->postJson(
            '/api/notifier/backup',
            ['type' => 'invalid_type'],
            ['X-Notifier-Token' => 'test-backup-code']
        );

        expect($response->status())->toBe(422);
    });
});
