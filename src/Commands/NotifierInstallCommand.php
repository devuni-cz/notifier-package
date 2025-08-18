<?php

namespace Devuni\Notifier\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Composer\InstalledVersions;


class NotifierInstallCommand extends Command
{
    protected $signature = 'notifier:install';
    protected $description = 'Configure environment variables for Notifier package';

    public function handle()
    {
        $this->displayBanner();
        if($this->ensureEnvFileExists() === static::FAILURE) {
            return static::FAILURE;
        }
        $this->newLine();

        $this->info('🔧 Please provide the required environment values:');
        $this->newLine();

        $backupCode = $this->askRequired('👉 BACKUP_CODE: ');
        $backupUrl = $this->askRequired('👉 BACKUP_URL: ');
        $backupPassword = $this->askRequired('👉 BACKUP_ZIP_PASSWORD: ');

        $this->updateEnv([
            'BACKUP_CODE' => $backupCode,
            'BACKUP_URL' => $backupUrl,
            'BACKUP_ZIP_PASSWORD' => $backupPassword,
        ]);

        $this->info('✅ Notifier environment configuration was saved successfully!');
        return static::SUCCESS;
    }

    private function ensureEnvFileExists(): int
    {
        if (!File::exists(base_path('.env'))) {
            $this->warn('❗️ Missing configuration file: <fg=gray>.env</>');
            $this->newLine();
            $this->line('<fg=gray>🔹 This package requires an <fg=green>.env</> file to store environment settings.</>');
            $this->line('<fg=gray>🔹 You can create it from the template: <fg=green>.env.example</>');
            $this->newLine();

            if ($this->confirm('👉 Do you want to create <fg=gray>.env</> from <fg=gray>.env.example</> ?', true)) {
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->info('<fg=green;options=bold>✅ <fg=gray>.env</> file has been created.</>');
            }
            else {
                $this->error('<fg=white;options=bold;bg=red>❌ Installation aborted! .env file is required.</>');
                return static::FAILURE;
            }
        }
        return static::SUCCESS;
    }

    private function askRequired(string $question) : string
    {
        do {
            $value = $this->ask($question);
            if (empty($value)) {
                $this->error('This field is required. Please enter a value!');
            }
        } while (empty($value));

        return $value;
    }

    private function updateEnv(array $data) : void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach($data as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $line = "{$key}=\"{$value}\"";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $line, $envContent);
            } else {
                $envContent .= PHP_EOL . $line;
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function displayBanner(): void
    {
        $this->line("<fg=bright-blue;options=bold>
    _   __      __  _ _____                               __
   / | / /___  / /_(_) __(_)__  _____   ____  ____ ______/ /______ _____ ____
  /  |/ / __ \/ __/ / /_/ / _ \/ ___/  / __ \/ __ `/ ___/ //_/ __ `/ __ `/ _ \
 / /|  / /_/ / /_/ / __/ /  __/ /     / /_/ / /_/ / /__/ ,< / /_/ / /_/ /  __/
/_/ |_/\____/\__/_/_/ /_/\___/_/     / .___/\__,_/\___/_/|_|\__,_/\__, /\___/
                                    /_/                          /____/
        </>");
        $this->line('<fg=bright-blue;options=bold>🎉 Welcome to the Notifier environment setup wizard</>');
        $this->line('<fg=gray>──────────────────────────────────────────────────────────────────────────────</>');
        $this->line('<fg=bright-blue>📦 Package:</>          <fg=white;options=bold>devuni/notifier-package</>');
        $this->line('<fg=bright-blue>📁 Repository:</>       <fg=cyan;options=underscore>https://github.com/devuni-cz/notifier-package</>');
        $this->line('<fg=bright-blue>🌐 Website:</>          <fg=cyan;options=underscore>https://devuni.cz</>');
        $this->line('<fg=bright-blue>🔨 Developed by:</>     <fg=white>Devuni team</>');
        $this->line('<fg=bright-blue>📅 Version:</>          <fg=white>'. $this->getCurrentVersion() . '</>');
        $this->line('<fg=gray>──────────────────────────────────────────────────────────────────────────────</>');
        $this->newLine();
    }

    private function getCurrentVersion(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('devuni/notifier-package') ?? 'custom';
        } catch (\OutOfBoundsException $e) {
            return 'unknown';
        }
    }
}


