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

final class NotifierServiceProvider extends ServiceProvider
{
    public static function basePath(string $path): string
    {
        return __DIR__.'/..'.$path;
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::basePath('/config/notifier.php'), 'notifier');

        $this->app->singleton(NotifierConfigService::class);
        $this->app->singleton(NotifierDatabaseService::class);
        $this->app->singleton(NotifierStorageService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::basePath('/config/notifier.php') => config_path('notifier.php'),
            ], 'notifier-config');

            $this->commands([
                NotifierCheckCommand::class,
                NotifierDatabaseBackupCommand::class,
                NotifierInstallCommand::class,
                NotifierStorageBackupCommand::class,
            ]);
        }

        if (config('notifier.routes_enabled', true)) {
            $this->loadRoutesFrom(self::basePath('/routes/web.php'));
        }
    }
}
