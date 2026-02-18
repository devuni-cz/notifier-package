<?php

declare(strict_types=1);

namespace Devuni\Notifier\Contracts;

interface ZipCreator
{
    /**
     * Create a password-protected ZIP archive from a source directory.
     *
     * @param  string  $sourcePath  Absolute path to the directory to archive.
     * @param  string  $zipPath  Absolute path for the output ZIP file.
     * @param  string  $password  Password for encryption.
     * @param  array<string>  $excludedFiles  Relative paths to exclude.
     * @return int Number of files added to the archive.
     *
     * @throws \RuntimeException When archive creation fails.
     */
    public function create(string $sourcePath, string $zipPath, string $password, array $excludedFiles = []): int;

    /**
     * Check if this strategy is available on the current system.
     */
    public static function isAvailable(): bool;
}
