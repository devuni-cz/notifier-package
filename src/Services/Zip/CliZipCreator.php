<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Zip;

use Devuni\Notifier\Contracts\ZipCreator;
use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class CliZipCreator implements ZipCreator
{
    public function create(string $sourcePath, string $zipPath, string $password, array $excludedFiles = []): int
    {
        NotifierLogger::get()->info('➡️ using CLI 7z strategy for ZIP creation');

        // Remove stale archive for idempotency
        if (file_exists($zipPath)) {
            File::delete($zipPath);
        }

        $command = [
            '7z', 'a',
            '-tzip',
            '-mem=AES256',
            '-p'.$password,
            '-r',
            '-bso0', // suppress standard output
            '-bsp0', // suppress progress
            $zipPath,
            '.',
        ];

        foreach ($excludedFiles as $excluded) {
            $command[] = '-xr!'.ltrim($excluded, '/');
        }

        $process = new Process($command, $sourcePath);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('CLI zip (7z) failed: '.$process->getErrorOutput());
        }

        if (! file_exists($zipPath)) {
            throw new RuntimeException('ZIP file was not created at: '.$zipPath);
        }

        chmod($zipPath, 0600);

        // Count archived files via 7z list
        return $this->countFiles($zipPath, $password);
    }

    public static function isAvailable(): bool
    {
        $process = new Process(['7z', '--help']);
        $process->run();

        return $process->isSuccessful();
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
