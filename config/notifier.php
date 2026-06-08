<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | The backup code is used to authenticate API requests from the central
    | notifier application. This must match on both sides.
    |
    */
    'backup_code' => env('NOTIFIER_BACKUP_CODE', env('BACKUP_CODE')),

    /*
    |--------------------------------------------------------------------------
    | Central Notifier URL
    |--------------------------------------------------------------------------
    |
    | The URL where backup files will be sent. This is the endpoint on
    | the central notifier.devuni.cz application.
    |
    */
    'backup_url' => env('NOTIFIER_URL', env('BACKUP_URL')),

    /*
    |--------------------------------------------------------------------------
    | Backup ZIP Password
    |--------------------------------------------------------------------------
    |
    | Password used to encrypt the storage backup ZIP files.
    | This should be a strong, unique password.
    |
    */
    'backup_zip_password' => env('NOTIFIER_BACKUP_PASSWORD', env('BACKUP_ZIP_PASSWORD')),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Which Laravel database connection to back up. When null (default), the
    | package uses your app's default connection from config('database.default').
    |
    | Supported drivers: mysql, mariadb, pgsql (including YugabyteDB via YSQL).
    |
    */
    'database_connection' => env('NOTIFIER_DATABASE_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Dump Binary
    |--------------------------------------------------------------------------
    |
    | Which CLI binary to use for PostgreSQL/YugabyteDB dumps.
    |
    | Options:
    | - null (default) : Auto-detect - prefers `ysql_dump` (YugabyteDB), falls
    |                    back to `pg_dump` (standard PostgreSQL).
    | - 'pg_dump'      : Force standard PostgreSQL client.
    | - 'ysql_dump'    : Force YugabyteDB's ysql_dump (a pg_dump fork with
    |                    Yugabyte-specific optimizations).
    |
    | Only applies when the active connection driver is `pgsql`.
    |
    */
    'postgres_dump_binary' => env('NOTIFIER_POSTGRES_DUMP_BINARY'),

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Schema
    |--------------------------------------------------------------------------
    |
    | Default schema for unqualified table names in `excluded_tables` when
    | using a PostgreSQL/YugabyteDB connection. If a table name in the list
    | already contains a dot (e.g. "audit.events"), it is passed through
    | as-is.
    |
    */
    'postgres_schema' => env('NOTIFIER_POSTGRES_SCHEMA', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Database Tables
    |--------------------------------------------------------------------------
    |
    | Database tables that should be excluded from the database backup.
    | Useful for excluding large log tables or temporary data.
    |
    | Write plain table names - the package prefixes them automatically
    | (database name for MySQL/MariaDB, `postgres_schema` for PostgreSQL).
    | Fully qualified names containing a dot are passed through as-is.
    |
    | Examples:
    | - 'telescope_entries'      -> Laravel Telescope data
    | - 'telescope_entries_tags' -> Telescope relation table
    | - 'pulse_entries'          -> Laravel Pulse data
    | - 'sessions'               -> User sessions
    | - 'cache'                  -> Cache table
    | - 'audit.events'           -> Fully qualified - uses literal "audit" schema/db
    |
    */
    'excluded_tables' => [],

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Files or directories that should be excluded from storage backup.
    | Paths are relative to storage/app/public.
    |
    | Examples:
    | - '.gitignore'        -> exclude .gitignore file
    | - 'temp'              -> exclude entire temp directory
    | - 'logs/debug.log'    -> exclude specific file
    |
    */
    'excluded_files' => [
        '.gitignore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | The logging channel used for backup operations.
    | Falls back to 'daily' if the specified channel doesn't exist.
    |
    */
    'logging_channel' => env('NOTIFIER_LOGGING_CHANNEL', 'backup'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Control whether the package registers its API routes and
    | customize the route prefix.
    |
    | Set 'routes_enabled' to false if you want to define your
    | own routes using the package controller.
    |
    */
    'routes_enabled' => env('NOTIFIER_ROUTES_ENABLED', true),
    'route_prefix' => env('NOTIFIER_ROUTE_PREFIX', 'api/notifier'),

    /*
    |--------------------------------------------------------------------------
    | ZIP Strategy
    |--------------------------------------------------------------------------
    |
    | Controls how storage backup ZIP archives are created.
    |
    | Options:
    | - 'auto' (default) : Use CLI 7z if available, fall back to PHP ZipArchive
    | - 'cli'            : Force CLI 7z (requires p7zip-full package)
    | - 'php'            : Force PHP ZipArchive extension
    |
    | CLI 7z is recommended for production - it uses less memory,
    | handles large files better, and runs in a separate process.
    |
    */
    'zip_strategy' => env('NOTIFIER_ZIP_STRATEGY', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | The size of each chunk in bytes when uploading backup files.
    | Default is 20 MB.
    |
    | If you are using for example Cloudflare, you must stay under their upload limit (100 MB) of free plan.
    |
    */
    'chunk_size' => env('NOTIFIER_CHUNK_SIZE', 20 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | The queue connection used for backup jobs dispatched via the HTTP API.
    | When set to anything other than 'sync', backups are offloaded to a
    | queue worker - avoiding PHP max_execution_time limits.
    |
    | Supported: 'sync', 'database', 'redis', 'sqs', 'beanstalkd'
    |            (any connection defined in config/queue.php)
    |
    | 'sync'       - runs backup synchronously in the HTTP request (default)
    | 'database'   - dispatches to the jobs table (requires queue:table migration)
    | 'redis'      - dispatches to Redis (requires phpredis or predis)
    | 'sqs'        - dispatches to Amazon SQS
    | 'beanstalkd' - dispatches to Beanstalkd
    |
    | Artisan commands are not affected - they always run synchronously.
    |
    */
    'queue_connection' => env('NOTIFIER_QUEUE_CONNECTION', 'sync'),
];
