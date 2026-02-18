<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services\Zip;

use Devuni\Notifier\Contracts\ZipCreator;
use RuntimeException;

class ZipManager
{
    /**
     * Resolve the best available ZipCreator strategy.
     *
     * Priority: config override → CLI 7z → PHP ZipArchive
     */
    public static function resolve(): ZipCreator
    {
        $strategy = config('notifier.zip_strategy', 'auto');

        return match ($strategy) {
            'cli' => self::resolveCliOrFail(),
            'php' => self::resolvePhpOrFail(),
            default => self::resolveAuto(),
        };
    }

    private static function resolveAuto(): ZipCreator
    {
        if (CliZipCreator::isAvailable()) {
            return new CliZipCreator;
        }

        if (PhpZipCreator::isAvailable()) {
            return new PhpZipCreator;
        }

        throw new RuntimeException(
            'No ZIP strategy available. Install 7z (p7zip-full) or enable the PHP zip extension.'
        );
    }

    private static function resolveCliOrFail(): ZipCreator
    {
        if (! CliZipCreator::isAvailable()) {
            throw new RuntimeException(
                'CLI zip strategy requested but 7z is not installed. Install p7zip-full.'
            );
        }

        return new CliZipCreator;
    }

    private static function resolvePhpOrFail(): ZipCreator
    {
        if (! PhpZipCreator::isAvailable()) {
            throw new RuntimeException(
                'PHP zip strategy requested but the zip extension is not loaded.'
            );
        }

        return new PhpZipCreator;
    }
}
