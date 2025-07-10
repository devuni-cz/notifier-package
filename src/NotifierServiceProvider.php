<?php

namespace Devuni\Notifier;

use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Illuminate\Support\ServiceProvider;

class NotifierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/notifier.php' => config_path('notifier.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifier.php', 'notifier');

        $this->commands([
            NotifierDatabaseBackupCommand::class,
        ]);

        require_once __DIR__.'/helpers.php';
    }
}
