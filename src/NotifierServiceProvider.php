<?php

declare(strict_types=1);

namespace Devuni\Notifier;

use Illuminate\Support\ServiceProvider;
use Devuni\Notifier\Commands\NotifierCheckCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;

class NotifierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/notifier.php' => config_path('notifier.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/../routes/notifier.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifier.php', 'notifier');

        $this->commands([
            NotifierCheckCommand::class,
            NotifierDatabaseBackupCommand::class,
            NotifierInstallCommand::class,
            NotifierStorageBackupCommand::class,
        ]);

        require_once __DIR__.'/helpers.php';
    }
}
