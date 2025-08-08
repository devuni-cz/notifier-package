<?php

declare(strict_types=1);

use Devuni\Notifier\Services\NotifierStorageService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;

describe('NotifierStorageService', function () {
    beforeEach(function () {
        putenv('BACKUP_URL=http://example.com');
        putenv('BACKUP_CODE=code');
        File::ensureDirectoryExists(storage_path('app/public'));
        File::put(storage_path('app/public/test.txt'), 'content');
        File::ensureDirectoryExists(storage_path('app/private'));
        config(['notifier.backup_zip_password' => 'zip']);
    });

    afterEach(function () {
        Mockery::close();
        config(['notifier.backup_zip_password' => 'secret123']);
    });

    it('creates storage backup zip', function () {
        $path = NotifierStorageService::createStorageBackup();

        expect(File::exists($path))->toBeTrue();
    });

    it('sends storage backup successfully', function () {
        $path = NotifierStorageService::createStorageBackup();

        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldReceive('post')->andReturn(new Response(200));

        NotifierStorageService::sendStorageBackup($path);

        expect(File::exists($path))->toBeFalse();
    });

    it('keeps zip when sending storage backup fails', function () {
        $path = NotifierStorageService::createStorageBackup();

        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldReceive('post')->andThrow(new \Exception('fail'));

        NotifierStorageService::sendStorageBackup($path);

        expect(File::exists($path))->toBeTrue();
    });
});
