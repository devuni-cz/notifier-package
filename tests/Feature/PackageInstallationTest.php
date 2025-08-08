<?php

declare(strict_types=1);

namespace Devuni\Notifier\Commands {
    function shell_exec($command) {
        return json_encode(['versions' => ['test-version']]);
    }
}

namespace {
use Illuminate\Support\Facades\File;

it('can install the package', function () {
    File::put(base_path('.env'), '');
    File::put(base_path('.env.example'), '');

    $this->artisan('notifier:install')
        ->expectsQuestion('BACKUP_CODE: ', 'code')
        ->expectsQuestion('BACKUP_URL: ', 'http://example.com')
        ->expectsQuestion('BACKUP_ZIP_PASSWORD: ', 'zip')
        ->assertExitCode(0);

    $env = File::get(base_path('.env'));
    expect($env)->toContain('BACKUP_CODE="code"', 'BACKUP_URL="http://example.com"', 'BACKUP_ZIP_PASSWORD="zip"');
});

it('can publish configuration', function () {
    $configPath = config_path('notifier.php');
    if (File::exists($configPath)) {
        File::delete($configPath);
    }

    $this->artisan('vendor:publish', ['--tag' => 'config', '--force' => true])->assertExitCode(0);

    expect(File::exists($configPath))->toBeTrue();
});
}
