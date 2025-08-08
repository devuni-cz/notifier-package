<?php

namespace Devuni\Notifier\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class NotifierInstallCommand extends Command
{
    protected $signature = 'notifier:install';
    protected $description = 'Configure environment variables for Notifier package';

    public function handle()
    {
        $this->displayBanner();
        $this->ensureEnvFileExists();
        $this->newLine();

        $this->info('🔧 Please provide the required environment values:');
        $this->newLine();

        $backupCode = $this->askRequired('BACKUP_CODE: ');
        $backupUrl = $this->askRequired('BACKUP_URL: ');
        $backupPassword = $this->askRequired('BACKUP_ZIP_PASSWORD: ');

        $this->updateEnv([
            'BACKUP_CODE' => $backupCode,
            'BACKUP_URL' => $backupUrl,
            'BACKUP_ZIP_PASSWORD' => $backupPassword,
        ]);

        $this->newLine();
        $this->info('✅ Notifier environment configuration was saved successfully!');
    }

    private function ensureEnvFileExists() : void 
    {
        if (!File::exists(base_path('.env'))){
            $this->warn('⚠️ .env file does not exists.');

            if ($this->confirm('Do you want to create it from .env.example', true)) {
                File::copy(base_path('.env.example'), base_path('.env'));
                $this->info('✅ .env file created from env.example');
            } 
            else {
                $this->error('❌ Installation aborted. .env file is required.');
                exit(1);
            }
        }
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
        $this->line('<fg=bright-blue;options=bold>
 _   _         _    _   __  _               _____              _                       
| \ | |       | |  (_) / _|(_)             |  __ \            | |                      
|  \| |  ___  | |_  _ | |_  _   ___  _ __  | |__) |__ _   ___ | | __ __ _   __ _   ___ 
|     | / _ \ | __|| ||  _|| | / _ \|  __| |  ___// _  | / __|| |/ // _  | / _  | / _ \
| |\  || (_) || |_ | || |  | ||  __/| |    | |   | (_| || (__ |   <| (_| || (_| ||  __/
|_| \_| \___/  \__||_||_|  |_| \___||_|    |_|    \____| \___||_|\_\\____| \__,  | \___|
                                                                              / |      
                                                                             |__/       
    </>');
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
        $json = json_decode(shell_exec('composer show devuni/notifier-package --format=json'), true);
        return $json['versions'][0] ?? 'unkown';
    }
}


