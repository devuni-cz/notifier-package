<?php

namespace Devuni\Notifier\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;
use Symfony\Component\Console\Output\ConsoleOutput;

class NotifierStorageService
{
    public static function createStorageBackup()
    {
        $output = new ConsoleOutput();

        Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');
        $output->writeln('⚙️  STARTING NEW BACKUP ⚙️');
        $output->writeln('');

        Storage::disk('local')->makeDirectory('backups');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.zip';

        $path = storage_path('app/private/'.$filename);

        $zip = new ZipArchive;

        Log::channel('backup')->info('➡️ creating backup file');
        $output->writeln('➡️  Creating backup file: ' . $filename);

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            Log::channel('backup')->info('➡️ adding files to the backup');
            $output->writeln('➡️  Adding files to the backup');

            $password = config('notifier.backup_zip_password');
            $excludedFiles = config('notifier.excluded_files', []);

            $zip->setPassword($password);

            $source = storage_path('app/public');

            if (count(File::allFiles($source)) === 0) {
                Log::channel('backup')->info('❌ No files to backup in the source directory: '.$source);
                throw new \Exception('No files to backup in the source directory: '.$source);
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                // Skip directories (they will be added automatically)
                if (! $file->isDir()) {
                    // Get real and relative path for the current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($source) + 1);

                    foreach($excludedFiles as $skip) {
                        if ($relativePath === $skip || str_starts_with($relativePath, $skip.'/')) {
                            Log::channel('backup')->info('➡️ skipping excluded file: '. $relativePath);
                            continue 2;
                        }
                    }

                    Log::channel('backup')->info('➡️ adding file: '.$file->getRealPath());

                    // Add file to the ZIP archive
                    $zip->addFile($filePath, $relativePath);

                    // Encrypt the file with the password
                    $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
                }
            }

            Log::channel('backup')->info('➡️ closing the backup file');
            $output->writeln('➡️  Closing the backup file');

            $zip->close();

            chmod($path, 0777);
        }

        Log::channel('backup')->info($path);
        $output->writeln('✅ Backup file created successfully at: ' . $path);

        return $path;
    }

    public static function sendStorageBackup(string $path)
    {
        $output = new ConsoleOutput();

        Log::channel('backup')->info('➡️ preparing file for sending');
        $output->writeln('');
        $output->writeln('➡️  Preparing file for sending: ' . basename($path));

        try {
            $client = new Client;

            $response = $client->post(config('notifier.backup_url'), [
                'multipart' => [
                    [
                        'name' => 'backup_file',
                        'contents' => fopen($path, 'r'),
                        'filename' => basename($path),
                    ],
                    [
                        'name' => 'backup_type',
                        'contents' => 'backup_storage',
                    ],
                    [
                        'name' => 'password',
                        'contents' => config('notifier.backup_code'),
                    ],
                ],
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                Log::channel('backup')->info('➡️ file was sent');
                $output->writeln('✅ File was sent successfully');
                File::delete($path);
                Log::channel('backup')->info('➡️ file was deleted');
                Log::channel('backup')->info('✅ END OF BACKUP');

                $output->writeln('');
                $output->writeln('✅ End of backup');
            } else {
                Log::error('❌ backup file could not be sent');
                $output->writeln('❌ Backup file could not be sent');
            }

            return $response->getBody();
        } catch (Throwable $th) {
            Log::channel('backup')->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => config('notifier.backup_url'),
                'code' => config('notifier.backup_code'),
            ]);
            $output->writeln('❌ An error occurred while uploading a file: ' . json_encode([
                'th' => $th->getMessage(),
                'env' => config('notifier.backup_url'),
                'code' => config('notifier.backup_code'),
            ]));

            Log::channel('backup')->emergency('❌ END OF SESSION ❌');
            $output->writeln('');
            $output->writeln('❌ End of session');
        }
    }
}
