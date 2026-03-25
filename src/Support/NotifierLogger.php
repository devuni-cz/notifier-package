<?php

declare(strict_types=1);

namespace Devuni\Notifier\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final class NotifierLogger
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly string $preferredChannel = 'backup',
    ) {
        $this->logger = $this->hasChannel($this->preferredChannel)
            ? Log::channel($this->preferredChannel)
            : Log::channel('daily');
    }

    public function get(): LoggerInterface
    {
        return $this->logger;
    }

    public function hasChannel(?string $channel = null): bool
    {
        $channel ??= $this->preferredChannel;

        return config("logging.channels.$channel") !== null;
    }

    public function getPreferredChannel(): string
    {
        return $this->preferredChannel;
    }

    public function isUsingPreferredChannel(): bool
    {
        return $this->hasChannel($this->preferredChannel);
    }
}
