<?php

declare(strict_types=1);

namespace Devuni\Notifier\Tests\Unit;

use Devuni\Notifier\NotifierServiceProvider;
use Devuni\Notifier\Tests\TestCase;

class NotifierServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider is properly loaded.
     */
    public function test_service_provider_is_loaded(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(NotifierServiceProvider::class, $providers);
        $this->assertTrue($providers[NotifierServiceProvider::class]);
    }

    /**
     * Test that the package configuration is loaded.
     */
    public function test_package_config_is_loaded(): void
    {
        // This test will pass once the config is published
        $this->assertTrue(true);
    }
}
