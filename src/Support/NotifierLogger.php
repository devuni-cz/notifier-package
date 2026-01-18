<?php

declare(strict_types=1);

namespace Devuni\Notifier\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class NotifierLogger
{
    public static function get(): LoggerInterface
    {
        $channel = config('notifier.logging_channel', 'backup');

        // Use configured channel if it exists, otherwise use app's default
        if (! config("logging.channels.{$channel}")) {
            $channel = config('logging.default', 'stack');
        }

        return Log::channel($channel);
    }
}
