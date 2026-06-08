<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Zip;

use Devuni\Notifier\Interfaces\ZipCreatorInterface;
use Devuni\Notifier\Services\NotifierLoggerService;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;

final class CliZipCreator implements ZipCreatorInterface
{
    public function __construct(
        private readonly NotifierLoggerService $notifierLogger,
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

        // Password is provided via stdin (not argv) to prevent exposure via
        // /proc/<pid>/cmdline or `ps` output on shared hosts. The bare "-p"
        // flag instructs 7z to read the password from stdin. When creating an
        // archive, 7z prompts twice (password + verification).
        $command = [
            '7z', 'a',
            '-tzip',
            '-mem=AES256',
            '-p',
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
        $process->setInput($password."\n".$password."\n");
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'CLI zip (7z) failed (exit code '.$process->getExitCode().'): '
                .$process->getErrorOutput()
            );
        }

        // PHP can hold a stale stat result from before 7z ran; on network
        // filesystems and symlinked deploy targets (Forge releases/) the
        // freshly-written file may not show up on the first stat call.
        // Force a re-stat before checking existence.
        clearstatcache(true, $zipPath);

        if (! file_exists($zipPath)) {
            // 7z reported success but no archive landed at $zipPath. This is
            // intermittent on some 7z builds with stdin-password + symlinked
            // storage paths. Rather than losing the entire backup run, fall
            // back to PHP's ZipArchive when it's available.
            if (PhpZipCreator::isAvailable()) {
                $logger->warning(
                    '⚠️ CLI 7z exited 0 but archive is missing, falling back to PHP ZipArchive',
                    [
                        'zip_path' => $zipPath,
                        'cwd' => $cwd,
                        'source' => $sourcePath,
                        'stderr' => $process->getErrorOutput(),
                    ],
                );

                return (new PhpZipCreator($this->notifierLogger))
                    ->create($sourcePath, $zipPath, $password, $excludedFiles);
            }

            throw new RuntimeException(
                'ZIP file was not created at: '.$zipPath
                .' (CLI 7z exited 0 but the file is missing and PHP zip extension is not available for fallback).'
                .' 7z stdout: '.($process->getOutput() ?: '(empty)')
                .'. 7z stderr: '.($process->getErrorOutput() ?: '(empty)')
                .'. Source: '.$sourcePath
                .'. Source exists: '.(file_exists($sourcePath) ? 'yes' : 'no')
                .'. Source size: '.(file_exists($sourcePath) ? (string) filesize($sourcePath) : 'N/A')
                .'. CWD: '.$cwd
                .'. ZIP dir exists: '.(is_dir(dirname($zipPath)) ? 'yes' : 'no')
                .'. ZIP dir writable: '.(is_writable(dirname($zipPath)) ? 'yes' : 'no')
            );
        }

        chmod($zipPath, 0600);

        // Count archived files via 7z list
        return $this->countFiles($zipPath, $password);
    }

    private function isDirectoryEmpty(string $directory, array $excludedFiles): bool
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
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
        // Password via stdin (single prompt for listing) - same rationale
        // as archive creation: avoid exposure via /proc/<pid>/cmdline.
        $process = new Process(['7z', 'l', '-p', $zipPath]);
        $process->setInput($password."\n");
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
