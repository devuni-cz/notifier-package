<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Zip;

use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class PhpZipCreator implements ZipCreator
{
    public function create(string $sourcePath, string $zipPath, string $password, array $excludedFiles = []): int
    {
        NotifierLogger::get()->info('➡️ using PHP ZipArchive fallback for ZIP creation');

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to open ZIP archive: '.$zipPath);
        }

        $zip->setPassword($password);

        // Handle single file (e.g. SQL dump) vs directory
        if (is_file($sourcePath)) {
            $entryName = basename($sourcePath);
            NotifierLogger::get()->info('➡️ adding file: '.$sourcePath);
            $zip->addFile($sourcePath, $entryName);
            $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256);
            $zip->close();
            chmod($zipPath, 0600);

            return 1;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $fileCount = 0;

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            if ($filePath === false) {
                NotifierLogger::get()->warning('➡️ skipping file with invalid path: '.$file->getPathname());

                continue;
            }

            $relativePath = substr($filePath, strlen($sourcePath) + 1);

            if (empty($relativePath)) {
                NotifierLogger::get()->warning('➡️ skipping file with empty relative path: '.$filePath);

                continue;
            }

            if ($this->isExcluded($relativePath, $excludedFiles)) {
                NotifierLogger::get()->info('➡️ skipping excluded file: '.$relativePath);

                continue;
            }

            NotifierLogger::get()->info('➡️ adding file: '.$filePath);

            $zip->addFile($filePath, $relativePath);
            $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
            $fileCount++;
        }

        if ($fileCount === 0) {
            $zip->close();
            File::delete($zipPath);

            throw new RuntimeException('No files to backup in the source directory: '.$sourcePath);
        }

        $zip->close();

        chmod($zipPath, 0600);

        return $fileCount;
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('zip');
    }

    private function isExcluded(string $relativePath, array $excludedFiles): bool
    {
        foreach ($excludedFiles as $skip) {
            if ($relativePath === $skip || str_starts_with($relativePath, $skip.'/')) {
                return true;
            }
        }

        return false;
    }
}
