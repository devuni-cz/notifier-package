<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Zip;

use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

final class CliZipCreator implements ZipCreator
{
    public function __construct(
        private readonly NotifierLogger $notifierLogger,
    ) {}

    public static function isAvailable(): bool
    {
        $process = new Process(['7z', '--help']);
        $process->run();

        return $process->isSuccessful();
    }

    public function create(string $sourcePath, string $zipPath, string $password, array $excludedFiles = []): int
    {
        $logger = $this->notifierLogger->get();

        $logger->info('➡️ using CLI 7z strategy for ZIP creation');

        // Remove stale archive for idempotency
        if (file_exists($zipPath)) {
            File::delete($zipPath);
        }

        // Handle single file (e.g. SQL dump) vs directory
        $isFile = is_file($sourcePath);

        // Early check: if source is a directory, verify it has files to archive
        if (! $isFile && $this->isDirectoryEmpty($sourcePath, $excludedFiles)) {
            throw new RuntimeException('No files to backup in the source directory: '.$sourcePath);
        }

        $cwd = $isFile ? dirname($sourcePath) : $sourcePath;
        $target = $isFile ? basename($sourcePath) : '.';

        $command = [
            '7z', 'a',
            '-tzip',
            '-mem=AES256',
            '-p'.$password,
            $zipPath,
            $target,
        ];

        if (! $isFile) {
            array_splice($command, 6, 0, ['-r']);

            foreach ($excludedFiles as $excluded) {
                $command[] = '-xr!'.mb_ltrim($excluded, '/');
            }
        }

        $process = new Process($command, $cwd);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'CLI zip (7z) failed (exit code '.$process->getExitCode().'): '
                .$process->getErrorOutput()
            );
        }

        if (! file_exists($zipPath)) {
            throw new RuntimeException(
                'ZIP file was not created at: '.$zipPath
                .'. 7z stdout: '.($process->getOutput() ?: '(empty)')
                .'. 7z stderr: '.($process->getErrorOutput() ?: '(empty)')
                .'. Source: '.$sourcePath
                .'. Source exists: '.(file_exists($sourcePath) ? 'yes' : 'no')
                .'. Source size: '.(file_exists($sourcePath) ? (string) filesize($sourcePath) : 'N/A')
            );
        }

        chmod($zipPath, 0600);

        // Count archived files via 7z list
        return $this->countFiles($zipPath, $password);
    }

    private function isDirectoryEmpty(string $directory, array $excludedFiles): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePath = mb_substr($file->getPathname(), mb_strlen($directory) + 1);

            $excluded = false;
            foreach ($excludedFiles as $skip) {
                $skip = mb_ltrim($skip, '/');
                if ($relativePath === $skip || str_starts_with($relativePath, $skip.'/')) {
                    $excluded = true;
                    break;
                }
            }

            if (! $excluded) {
                return false;
            }
        }

        return true;
    }

    private function countFiles(string $zipPath, string $password): int
    {
        $process = new Process(['7z', 'l', '-p'.$password, $zipPath]);
        $process->run();

        if (! $process->isSuccessful()) {
            return 0;
        }

        // Parse the summary line: "X files, Y folders"
        if (preg_match('/(\d+)\s+files/', $process->getOutput(), $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
