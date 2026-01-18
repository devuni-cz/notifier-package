<?php

namespace Devuni\Notifier\Support;

use Illuminate\Support\Facades\Log;

class NotifierLogger
{
    public static function get()
    {
        $preferredChannel = config('notifier.logging_channel', 'backup');

        if (config("logging.channels.$preferredChannel")) {
            return Log::channel($preferredChannel);
        }

        return Log::channel('daily');
    }
}
