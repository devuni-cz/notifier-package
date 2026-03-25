<?php

declare(strict_types=1);

namespace Devuni\Notifier;

use Devuni\Notifier\Commands\NotifierCheckCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Services\ChunkedUploadService;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierStorageService;
use Devuni\Notifier\Services\Zip\CliZipCreator;
use Devuni\Notifier\Services\Zip\PhpZipCreator;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->app->singleton(ChunkedUploadService::class);
        $this->app->singleton(NotifierDatabaseService::class);
        $this->app->singleton(NotifierStorageService::class);

        $this->app->singleton(ZipCreator::class, function ($app): ZipCreator {
            $strategy = config('notifier.zip_strategy', 'auto');
            $logger = $app->make(NotifierLogger::class);

            return match ($strategy) {
                'cli' => CliZipCreator::isAvailable()
                    ? new CliZipCreator($logger)
                    : throw new RuntimeException('CLI zip strategy requested but 7z is not installed. Install p7zip-full.'),
                'php' => PhpZipCreator::isAvailable()
                    ? new PhpZipCreator($logger)
                    : throw new RuntimeException('PHP zip strategy requested but the zip extension is not loaded.'),
                default => match (true) {
                    CliZipCreator::isAvailable() => new CliZipCreator($logger),
                    PhpZipCreator::isAvailable() => new PhpZipCreator($logger),
                    default => throw new RuntimeException('No ZIP strategy available. Install 7z (p7zip-full) or enable the PHP zip extension.'),
                },
            };
        });

        $this->app->singleton(NotifierLogger::class, function (): NotifierLogger {
            $preferredChannel = config('notifier.logging_channel', 'backup');

            return new NotifierLogger($preferredChannel);
        });
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
