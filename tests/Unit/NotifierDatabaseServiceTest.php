<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services {
    function exec($command) {
        $parts = explode('>', $command);
        $path = trim($parts[1] ?? '');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, 'dump');
    }
    function sleep($seconds) {}
}

namespace {
    use Devuni\Notifier\Services\NotifierDatabaseService;
    use GuzzleHttp\Psr7\Response;
    use Illuminate\Support\Facades\File;
    
    afterEach(function () {
        Mockery::close();
    });

    beforeEach(function () {
        putenv('BACKUP_URL=http://example.com');
        putenv('BACKUP_CODE=code');
    });

    it('creates database backup file', function () {
        $path = NotifierDatabaseService::createDatabaseBackup();

        expect(File::exists($path))->toBeTrue();
    });

    it('sends database backup successfully', function () {
        $path = NotifierDatabaseService::createDatabaseBackup();

        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldReceive('post')->andReturn(new Response(200));

        NotifierDatabaseService::sendDatabaseBackup($path);

        expect(File::exists($path))->toBeFalse();
    });

    it('keeps file when sending database backup fails', function () {
        $path = NotifierDatabaseService::createDatabaseBackup();

        $client = Mockery::mock('overload:GuzzleHttp\\Client');
        $client->shouldReceive('post')->andThrow(new \Exception('fail'));

        NotifierDatabaseService::sendDatabaseBackup($path);

        expect(File::exists($path))->toBeTrue();
    });
}
