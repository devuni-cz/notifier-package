<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Database;

use Closure;
use Devuni\Notifier\Interfaces\DatabaseDumperInterface;

/**
 * Defers dumper resolution until the first method call.
 *
 * The service provider binds this proxy so that container resolution doesn't
 * eagerly fail when the configured driver is unsupported (e.g. sqlite in test
 * environments where nothing actually calls dump()). The real driver-specific
 * dumper is constructed on demand.
 */
final class LazyDatabaseDumper implements DatabaseDumperInterface
{
    private ?DatabaseDumperInterface $resolved = null;

    /**
     * @param  Closure(): DatabaseDumperInterface  $resolver
     */
    public function __construct(
        private readonly Closure $resolver,
    ) {}

    public static function isAvailable(): bool
    {
        // The proxy itself is always "available"; whether the resolved dumper
        // is depends on which driver is configured. Use describe() / dump() to
        // surface that.
        return true;
    }

    public function dump(string $outputPath): void
    {
        $this->resolve()->dump($outputPath);
    }

    public function describe(): string
    {
        return $this->resolve()->describe();
    }

    /**
     * Return the underlying dumper (resolving it on first call).
     *
     * Useful for tests and the check command, which want to inspect the real
     * implementation without going through a dump() call.
     */
    public function resolve(): DatabaseDumperInterface
    {
        return $this->resolved ??= ($this->resolver)();
    }
}
