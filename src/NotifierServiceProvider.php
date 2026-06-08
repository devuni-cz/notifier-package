<?php

declare(strict_types=1);

namespace Devuni\Notifier;

use Devuni\Notifier\Commands\NotifierCheckCommand;
use Devuni\Notifier\Commands\NotifierDatabaseBackupCommand;
use Devuni\Notifier\Commands\NotifierInstallCommand;
use Devuni\Notifier\Commands\NotifierStorageBackupCommand;
use Devuni\Notifier\Interfaces\DatabaseDumperInterface;
use Devuni\Notifier\Interfaces\ZipCreatorInterface;
use Devuni\Notifier\Services\ChunkedUploadService;
use Devuni\Notifier\Services\Database\MysqlDumper;
use Devuni\Notifier\Services\Database\PostgresDumper;
use Devuni\Notifier\Services\NotifierConfigService;
use Devuni\Notifier\Services\NotifierDatabaseService;
use Devuni\Notifier\Services\NotifierLoggerService;
use Devuni\Notifier\Services\NotifierStorageService;
use Devuni\Notifier\Services\Zip\CliZipCreator;
use Devuni\Notifier\Services\Zip\PhpZipCreator;
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

        // Bind lazily: the actual dumper is resolved on first use, so unsupported
        // drivers (e.g. sqlite in test envs) don't blow up at container resolution
        // time for code paths that don't actually dump the database.
        $this->app->singleton(DatabaseDumperInterface::class, fn ($app): DatabaseDumperInterface => new Services\Database\LazyDatabaseDumper(
            fn (): DatabaseDumperInterface => self::resolveDumper($app),
        ));

        $this->app->singleton(ZipCreatorInterface::class, function ($app): ZipCreatorInterface {
            $strategy = config('notifier.zip_strategy', 'auto');
            $logger = $app->make(NotifierLoggerService::class);

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

        $this->app->singleton(NotifierLoggerService::class, function (): NotifierLoggerService {
            $preferredChannel = config('notifier.logging_channel', 'backup');

            return new NotifierLoggerService($preferredChannel);
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

    /**
     * Resolve the concrete DatabaseDumperInterface implementation for the currently configured connection.
     *
     * @throws RuntimeException When the connection or driver is unsupported.
     */
    private static function resolveDumper(\Illuminate\Contracts\Foundation\Application $app): DatabaseDumperInterface
    {
        $logger = $app->make(NotifierLoggerService::class);

        $connection = config('notifier.database_connection') ?: config('database.default');

        if (empty($connection)) {
            throw new RuntimeException(
                'No database connection configured. Set NOTIFIER_DATABASE_CONNECTION or '
                .'configure a default Laravel database connection.'
            );
        }

        $driver = config("database.connections.{$connection}.driver");

        return match ($driver) {
            'mysql', 'mariadb' => new MysqlDumper($connection, $logger),
            'pgsql' => new PostgresDumper($connection, $logger),
            null => throw new RuntimeException(
                "Database connection '{$connection}' is not configured in config/database.php."
            ),
            default => throw new RuntimeException(
                "Unsupported database driver '{$driver}' for connection '{$connection}'. "
                .'Supported drivers: mysql, mariadb, pgsql.'
            ),
        };
    }
}
