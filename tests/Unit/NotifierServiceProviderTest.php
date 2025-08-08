<?php

declare(strict_types=1);

use Devuni\Notifier\NotifierServiceProvider;

it('loads the service provider', function () {
    $providers = $this->app->getLoadedProviders();

    expect($providers)->toHaveKey(NotifierServiceProvider::class);
    expect($providers[NotifierServiceProvider::class])->toBeTrue();
});

it('can load package configuration', function () {
    expect(config('notifier.backup_zip_password'))->toBe(env('BACKUP_ZIP_PASSWORD', 'secret123'));
});
