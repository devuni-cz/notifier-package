<?php

declare(strict_types=1);

namespace Devuni\Notifier\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class NotifierLogger
{
    /**
     * Get the configured logger instance.
     * Falls back to 'daily' channel if preferred channel doesn't exist.
     */
    public static function get(): LoggerInterface
    {
        $preferredChannel = self::getPreferredChannel();

        return self::hasChannel($preferredChannel)
            ? Log::channel($preferredChannel)
            : Log::channel('daily');
    }

    public static function hasChannel(?string $channel = null): bool
    {
        $channel ??= self::getPreferredChannel();

        return config("logging.channels.$channel") !== null;
    }

    public static function getPreferredChannel(): string
    {
        return config('notifier.logging_channel', 'backup');
    }

    public static function isUsingPreferredChannel(): bool
    {
        return self::hasChannel(self::getPreferredChannel());
    }
}
