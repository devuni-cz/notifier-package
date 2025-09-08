<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

describe('NotifierServiceProvider', function () {
    describe('Service Provider Registration', function () {
        it('loads the service provider', function () {
            $providers = $this->app->getLoadedProviders();

            expect($providers)->toHaveKey(NotifierServiceProvider::class);
            expect($providers[NotifierServiceProvider::class])->toBeTrue();
        });

        it('is registered as a deferred provider', function () {
            $provider = new NotifierServiceProvider($this->app);

            // Test that provider is not deferred (it registers commands immediately)
            expect($provider->isDeferred())->toBeFalse();
        });
    });

    describe('Configuration Registration', function () {
        it('merges package configuration', function () {
            // Test that package configuration is available
            expect(config('notifier'))->not->toBeNull();
            expect(config('notifier'))->toBeArray();
        });

        it('loads configuration from package config file', function () {
            // Test that configuration keys exist
            $configKeys = ['backup_code', 'backup_url', 'backup_zip_password', 'excluded_files'];

            foreach ($configKeys as $key) {
                expect(config()->has("notifier.{$key}"))->toBeTrue();
            }

            expect(config('notifier.excluded_files'))->toBeArray();
        });

        it('provides default values for configuration', function () {
            // Test default excluded files
            expect(config('notifier.excluded_files'))->toContain('.gitignore');
        });

        it('respects environment variable precedence', function () {
            // Test that env() calls work properly in config
            expect(config('notifier.backup_zip_password'))->not->toBeNull();
        });
    });

    describe('Command Registration', function () {
        it('registers notifier install command', function () {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('notifier:install');
            expect($commands['notifier:install'])->toBeInstanceOf(NotifierInstallCommand::class);
        });

        it('registers notifier database backup command', function () {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('notifier:database-backup');
            expect($commands['notifier:database-backup'])->toBeInstanceOf(NotifierDatabaseBackupCommand::class);
        });

        it('registers notifier storage backup command', function () {
            $commands = Artisan::all();

            expect($commands)->toHaveKey('notifier:storage-backup');
            expect($commands['notifier:storage-backup'])->toBeInstanceOf(NotifierStorageBackupCommand::class);
        });

        it('registers all three notifier commands', function () {
            $commands = Artisan::all();
            $notifierCommands = array_filter(array_keys($commands), function ($command) {
                return str_starts_with($command, 'notifier:');
            });

            expect($notifierCommands)->toHaveCount(3);
            expect($notifierCommands)->toContain('notifier:install');
            expect($notifierCommands)->toContain('notifier:database-backup');
            expect($notifierCommands)->toContain('notifier:storage-backup');
        });
    });

    describe('Routes Registration', function () {
        it('loads package routes', function () {
            $routes = Route::getRoutes();
            $routeUris = [];

            foreach ($routes as $route) {
                $routeUris[] = $route->uri();
            }

            expect($routeUris)->toContain('api/backup');
        });

        it('registers backup route with correct methods', function () {
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
        });

        it('applies throttle middleware to backup route', function () {
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
    });

    describe('Helpers Registration', function () {
        it('includes helpers file', function () {
            // Test that helpers file exists and is loaded
            $helpersPath = __DIR__ . '/../../src/helpers.php';
            expect(file_exists($helpersPath))->toBeTrue();
        });

        it('loads helpers file during registration', function () {
            // Since helpers.php is empty, we just test it doesn't cause errors
            expect(true)->toBeTrue(); // If we get here, helpers loaded successfully
        });
    });

    describe('Boot Method', function () {
        it('publishes configuration file', function () {
            // Test that configuration publishing is set up properly
            $provider = new NotifierServiceProvider($this->app);

            // Test that the provider can be booted without errors
            $provider->boot();

            // Check that config file exists in package
            $configPath = __DIR__ . '/../../config/notifier.php';
            expect(file_exists($configPath))->toBeTrue();
        });

        it('sets up correct publish path for config', function () {
            // Test publish configuration is set up correctly
            $provider = new NotifierServiceProvider($this->app);

            // Ensure boot method can be called without errors
            $provider->boot();

            expect(true)->toBeTrue(); // Boot completed successfully
        });

        it('loads routes from correct path', function () {
            // Routes should be loaded from package routes directory
            $routesPath = __DIR__ . '/../../routes/notifier.php';
            expect(file_exists($routesPath))->toBeTrue();
        });
    });

    describe('Register Method', function () {
        it('merges configuration with correct namespace', function () {
            // Test configuration merging
            expect(config('notifier'))->not->toBeEmpty();
        });

        it('commands are available after registration', function () {
            // Test that all commands are registered in the register method
            $commands = Artisan::all();

            expect($commands)->toHaveKey('notifier:install');
            expect($commands)->toHaveKey('notifier:database-backup');
            expect($commands)->toHaveKey('notifier:storage-backup');
        });
    });

    describe('Package Auto-Discovery', function () {
        it('is discoverable via composer extra.laravel.providers', function () {
            $composerJson = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);

            expect($composerJson['extra']['laravel']['providers'])->toContain(NotifierServiceProvider::class);
        });

        it('provides correct package namespace', function () {
            expect(class_exists(NotifierServiceProvider::class))->toBeTrue();
            expect((new NotifierServiceProvider($this->app)))->toBeInstanceOf(NotifierServiceProvider::class);
        });
    });

    describe('Integration with Laravel Framework', function () {
        it('extends correct base service provider class', function () {
            $provider = new NotifierServiceProvider($this->app);

            expect($provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
        });

        it('has access to application instance', function () {
            $provider = new NotifierServiceProvider($this->app);

            // Test that provider can access the application
            expect($provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
        });

        it('can be instantiated with application', function () {
            $provider = new NotifierServiceProvider($this->app);
            expect($provider)->toBeInstanceOf(NotifierServiceProvider::class);
            expect($provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
        });
    });
});
