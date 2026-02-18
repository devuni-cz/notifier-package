<?php

declare(strict_types=1);

namespace Devuni\Notifier;

use Devuni\Notifier\Commands\NotifierCheckCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;
use Illuminate\Support\ServiceProvider;

class NotifierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/notifier.php' => config_path('notifier.php'),
        ], 'config');

        if (config('notifier.routes_enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/notifier.php');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifier.php', 'notifier');

        $this->app->singleton(NotifierConfigService::class);
        $this->app->singleton(NotifierDatabaseService::class);
        $this->app->singleton(NotifierStorageService::class);

        $this->commands([
            NotifierCheckCommand::class,
            NotifierDatabaseBackupCommand::class,
            NotifierInstallCommand::class,
            NotifierStorageBackupCommand::class,
        ]);

        require_once __DIR__.'/helpers.php';
    }
}
