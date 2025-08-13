<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;

it('loads the service provider', function () {
    $providers = $this->app->getLoadedProviders();

    expect($providers)->toHaveKey(NotifierServiceProvider::class);
    expect($providers[NotifierServiceProvider::class])->toBeTrue();
});

it('merges package configuration', function () {
    expect(config('notifier.backup_zip_password'))->toBe('secret123');
});
