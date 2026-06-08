<?php

declare(strict_types=1);

namespace Devuni\Notifier\Interfaces;

use RuntimeException;

interface DatabaseDumperInterface
{
    /**
     * Check if the required dump binary is available on the current system.
     */
    public static function isAvailable(): bool;

    /**
     * Dump the database into the given output file.
     *
     * Implementations are responsible for:
     * - Building the correct CLI arguments for their database engine
     * - Passing credentials securely (env vars, never argv)
     * - Applying excluded tables from package config
     * - Throwing RuntimeException on failure (with stderr included)
     *
     * @param  string  $outputPath  Absolute path where the SQL dump should be written.
     *
     * @throws RuntimeException When the dump command fails or produces no output.
     */
    public function dump(string $outputPath): void;

    /**
     * Human-readable name for logs and the check command.
     *
     * Example: "mysqldump 8.0.35" or "pg_dump (PostgreSQL) 16.1".
     */
    public function describe(): string;
}
