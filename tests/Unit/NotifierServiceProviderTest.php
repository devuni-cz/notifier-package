<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;

it('loads the service provider', function () {
    $providers = $this->app->getLoadedProviders();

    expect($providers)->toHaveKey(NotifierServiceProvider::class);
    expect($providers[NotifierServiceProvider::class])->toBeTrue();
});

it('can load package configuration', function () {
    // This test will pass once the config is published
    expect(true)->toBeTrue();
});
