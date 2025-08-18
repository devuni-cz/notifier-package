<?php

namespace Devuni\Notifier\Services;

use Throwable;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;

class NotifierDatabaseService
{
    public static function createDatabaseBackup()
    {
        $output = new ConsoleOutput();

        Log::channel('backup')->info('⚙️ STARTING NEW BACKUP ⚙️');
        $output->writeln('⚙️  STARTING NEW BACKUP ⚙️');
        $output->writeln('');

        $filename = 'backup-'.Carbon::now()->format('Y-m-d').'.sql';
        Storage::disk('local')->makeDirectory('backups');
        $path = storage_path('app/private/'.$filename);

        Log::channel('backup')->info('➡️ creating backup file');
        $output->writeln('➡️  Creating backup file: ' . $filename);

        $config = config('database.connections.mysql');

        $command = [
            'mysqldump',
            '--no-tablespaces',
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--host='.$config['host'],
            '--result-file='.$path,
            $config['database'],
        ];

        $process = new Process($command);
        $process->run();
        file_exists($path) ? $output->writeln('✅ Backup file created successfully at: ' . $path) : $output->writeln('❌ Failed to create backup file.');

        return $path;
    }

    public static function sendDatabaseBackup(string $path)
    {
        $output = new ConsoleOutput();

        Log::channel('backup')->info('➡️ preparing file for sending');
        $output->writeln('');
        $output->writeln('➡️  Preparing file for sending: ' . basename($path));

        try {
            $client = new Client;

            $response = $client->post(env('BACKUP_URL'), [
                'multipart' => [
                    [
                        'name' => 'backup_file',
                        'contents' => fopen($path, 'r'),
                        'filename' => basename($path),
                    ],
                    [
                        'name' => 'backup_type',
                        'contents' => 'backup_database',
                    ],
                    [
                        'name' => 'password',
                        'contents' => env('BACKUP_CODE'),
                    ],
                ],
            ]);

            if (in_array($response->getStatusCode(), [200, 201])) {
                Log::channel('backup')->info('➡️ file was sent');
                $output->writeln('✅ File was sent successfully');
                File::delete($path);
                Log::channel('backup')->info('➡️ file was deleted');
                Log::channel('backup')->info('✅ END OF BACKUP');

                $output->writeln('');
                $output->writeln('✅ End of backup');
            }
        } catch (Throwable $th) {
            Log::channel('backup')->emergency('❌ an error occurred while uploading a file', [
                'th' => $th->getMessage(),
                'env' => env('BACKUP_URL'),
                'code' => env('BACKUP_CODE'),
            ]);
            $output->writeln('❌ An error occurred while uploading a file: ' . json_encode([
                'th' => $th->getMessage(),
                'env' => env('BACKUP_URL'),
                'code' => env('BACKUP_CODE'),
            ]));

            Log::channel('backup')->emergency('❌ END OF SESSION ❌');
            $output->writeln('');
            $output->writeln('❌ End of session');
        }
    }
}
