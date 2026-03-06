<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands;

use Devuni\Notifier\Concerns\DisplayHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class NotifierInstallCommand extends Command
{
    use DisplayHelper;

    protected $signature = 'notifier:install {--force : Overwrites existing environment variables}';

    protected $description = 'Configure environment variables for Notifier package';

    public function handle()
    {
        if ($this->ifAlreadyInstalled()) {
            error('The Notifier configuration already exists. Use --force to overwrite.');

            return self::FAILURE;
        }

        $this->displayNotifierHeader('Install');

        if ($this->ensureEnvFileExists() === self::FAILURE) {
            return self::FAILURE;
        }

        info('🔧 Please provide the required environment values:');

        $backupCode = text(
            label: 'BACKUP_CODE',
            placeholder: 'Enter your backup code',
            required: 'Backup code is required.',
        );

        $backupUrl = text(
            label: 'BACKUP_URL',
            placeholder: 'https://your-notifier-server.com',
            required: 'Backup URL is required.',
        );

        $backupPassword = password(
            label: 'BACKUP_ZIP_PASSWORD',
            placeholder: 'Enter your backup ZIP password',
            required: 'Backup password is required.',
        );

        $this->updateEnv([
            'BACKUP_CODE' => $backupCode,
            'BACKUP_URL' => $backupUrl,
            'BACKUP_ZIP_PASSWORD' => $backupPassword,
        ]);

        info('Notifier environment configuration was saved successfully!');

        return self::SUCCESS;
    }

    private function ensureEnvFileExists(): int
    {
        if (! File::exists(base_path('.env'))) {
            warning('Missing configuration file: .env');
            $this->line('<fg=gray>🔹 This package requires an <fg=green>.env</> file to store environment settings.</>');
            $this->line('<fg=gray>🔹 You can create it from the template: <fg=green>.env.example</>');
            $this->newLine();

            if (confirm('Do you want to create .env from .env.example?', default: true)) {
                File::copy(base_path('.env.example'), base_path('.env'));
                info('.env file has been created.');
                $this->newLine();
            } else {
                error('Installation aborted! .env file is required.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $line = "{$key}=\"{$value}\"";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $line, $envContent);
            } else {
                $envContent .= PHP_EOL.$line;
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function ifAlreadyInstalled(): bool
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return false;
        }
        $envContent = file_get_contents($envPath);
        $requiredKeys = ['BACKUP_CODE', 'BACKUP_URL', 'BACKUP_ZIP_PASSWORD'];
        $alreadySet = collect($requiredKeys)->every(function ($key) use ($envContent) {
            if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
                $value = mb_trim($matches[1], '"');

                return $value !== '';
            }

            return false;
        });

        return $alreadySet && ! $this->option('force');
    }
}
