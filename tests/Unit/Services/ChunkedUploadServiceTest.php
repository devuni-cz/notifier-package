<?php

declare(strict_types=1);

use Devuni\Notifier\Services\ChunkedUploadService;
use Devuni\Notifier\Services\NotifierLoggerService;
use Illuminate\Support\Facades\Http;

/**
 * Invoke the private status_url guard via reflection so we can test the
 * security boundary without driving the full upload + polling loop.
 */
function invokeStatusUrlGuard(string $statusUrl, string $baseUrl): void
{
    $service = new ChunkedUploadService(new NotifierLoggerService());
    $method = new ReflectionMethod($service, 'assertTrustedStatusUrl');
    $method->setAccessible(true);
    $method->invoke($service, $statusUrl, $baseUrl);
}

describe('ChunkedUploadService::assertTrustedStatusUrl', function () {
    it('accepts a same-origin HTTPS status_url', function () {
        invokeStatusUrlGuard(
            'https://notifier.example.com/uploads/abc-123/status',
            'https://notifier.example.com',
        );

        // Reaching this line means no exception was thrown.
        expect(true)->toBeTrue();
    });

    it('treats an explicit :443 port as equivalent to the implicit HTTPS port', function () {
        invokeStatusUrlGuard(
            'https://notifier.example.com:443/uploads/abc-123/status',
            'https://notifier.example.com',
        );

        expect(true)->toBeTrue();
    });

    it('rejects a cleartext http status_url', function () {
        expect(fn () => invokeStatusUrlGuard(
            'http://notifier.example.com/uploads/abc-123/status',
            'https://notifier.example.com',
        ))->toThrow(RuntimeException::class, 'Refusing to poll non-HTTPS status_url');
    });

    it('rejects a status_url on a different host', function () {
        expect(fn () => invokeStatusUrlGuard(
            'https://attacker.example.com/uploads/abc-123/status',
            'https://notifier.example.com',
        ))->toThrow(RuntimeException::class, 'Refusing to poll status_url on unexpected host');
    });

    it('rejects a look-alike subdomain suffix host', function () {
        expect(fn () => invokeStatusUrlGuard(
            'https://notifier.example.com.attacker.com/uploads/abc-123/status',
            'https://notifier.example.com',
        ))->toThrow(RuntimeException::class, 'Refusing to poll status_url on unexpected host');
    });

    it('rejects a status_url on a different port', function () {
        expect(fn () => invokeStatusUrlGuard(
            'https://notifier.example.com:8443/uploads/abc-123/status',
            'https://notifier.example.com',
        ))->toThrow(RuntimeException::class, 'Refusing to poll status_url on unexpected port');
    });
});

describe('ChunkedUploadService finalize polling', function () {
    it('never sends the token to a foreign status_url returned by finalize', function () {
        config([
            'notifier.backup_url' => 'https://notifier.example.com',
            'notifier.backup_code' => 'super-secret-token',
        ]);

        Http::fake([
            '*/uploads/init' => Http::response(['upload_id' => 'abc-123'], 200),
            '*/uploads/*/chunks/*' => Http::response([], 200),
            '*/uploads/*/finalize' => Http::response(
                ['status_url' => 'https://attacker.example.com/poll'],
                202,
            ),
        ]);

        $tmpPath = tempnam(sys_get_temp_dir(), 'notifier_test_');
        file_put_contents($tmpPath, 'backup payload');

        try {
            // The guard must fire at finalize time - before any poll/sleep happens.
            expect(fn () => (new ChunkedUploadService(new NotifierLoggerService()))
                ->upload($tmpPath, 'database'))
                ->toThrow(RuntimeException::class, 'Refusing to poll status_url on unexpected host');

            // The long-lived secret must never have left for the attacker host.
            Http::assertNotSent(
                fn ($request) => str_contains($request->url(), 'attacker.example.com'),
            );
        } finally {
            @unlink($tmpPath);
        }
    });
});
