<?php

declare(strict_types=1);

namespace Devuni\Notifier\Concerns;

use Devuni\Notifier\Services\NotifierConfigService;

trait ChecksNotifierEnvironment
{
    private function checkMissingVariables(NotifierConfigService $configService): int
    {
        $missingVariables = $configService->checkEnvironment();

        if (! empty($missingVariables)) {
            $this->newLine();
            $this->line('<bg=red;fg=white;options=bold> ERROR </> The following environment variables are missing or empty:');
            $this->newLine();

            foreach ($missingVariables as $var) {
                $this->line("   • <fg=gray>{$var}</>");
            }

            $this->newLine();
            $this->line('-> Run <fg=gray>php artisan notifier:install</> to set these variables.');

            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}
