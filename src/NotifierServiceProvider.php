<?php

namespace Devuni\Notifier;

use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Http\Middleware\VerifyBackupToken;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class NotifierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/notifier.php' => config_path('notifier.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/../routes/notifier.php');

        $this->app->make(Router::class)->aliasMiddleware('auth.backup', VerifyBackupToken::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifier.php', 'notifier');

        $this->commands([
            NotifierDatabaseBackupCommand::class,
            NotifierStorageBackupCommand::class,
            NotifierInstallCommand::class,
        ]);

        require_once __DIR__.'/helpers.php';
    }
}
