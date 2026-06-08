<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class ChunkedUploadService
{
    public function __construct(
        private readonly NotifierLoggerService $notifierLogger,
    ) {}

    /**
     * Upload a file using the chunked upload protocol.
     *
     * 1. Init upload → get upload_id
     * 2. Send chunks sequentially with per-chunk retry
     * 3. Finalize upload → server reassembles and verifies
     */
    public function upload(string $path, string $backupType): void
    {
        $logger = $this->notifierLogger->get();

        $baseUrl = config('notifier.backup_url');

        if (! str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('Backup URL must use HTTPS: '.$baseUrl);
        }

        $token = config('notifier.backup_code');
        $chunkSize = (int) config('notifier.chunk_size', 20 * 1024 * 1024);
        $fileSize = filesize($path);

        if ($fileSize === false) {
            throw new RuntimeException('Failed to read file size: '.$path);
        }

        $checksum = hash_file('sha256', $path);

        if ($checksum === false) {
            throw new RuntimeException('Failed to compute file checksum: '.$path);
        }

        $totalChunks = (int) ceil($fileSize / $chunkSize);
        $filename = basename($path);

        $logger->info('📦 starting chunked upload', [
            'file' => $filename,
            'size' => $fileSize,
            'chunks' => $totalChunks,
            'chunk_size' => $chunkSize,
        ]);

        // Phase 1: Init upload
        $uploadId = $this->initUpload($baseUrl, $token, $backupType, $filename, $fileSize, $totalChunks, $checksum);

        $logger->info('✅ upload initialized', ['upload_id' => $uploadId]);

        // Phase 2: Send chunks (streamed to temp files to avoid memory exhaustion)
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Could not open file for reading: '.$path);
        }

        try {
            for ($chunkNumber = 1; $chunkNumber <= $totalChunks; $chunkNumber++) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'notifier_chunk_');

                if ($tmpPath === false) {
                    throw new RuntimeException('Failed to create temporary file for chunk');
                }

                $tmpHandle = fopen($tmpPath, 'wb');

                if ($tmpHandle === false) {
                    @unlink($tmpPath);
                    throw new RuntimeException('Failed to open temporary chunk file for writing');
                }

                $bytesCopied = stream_copy_to_stream($handle, $tmpHandle, $chunkSize);
                fclose($tmpHandle);

                if ($bytesCopied === false || $bytesCopied === 0) {
                    @unlink($tmpPath);
                    throw new RuntimeException("Failed to write chunk {$chunkNumber} to temporary file");
                }

                try {
                    $this->sendChunk($baseUrl, $token, $uploadId, $chunkNumber, $tmpPath);
                } finally {
                    @unlink($tmpPath);
                }

                $logger->info("➡️ chunk {$chunkNumber}/{$totalChunks} sent");
            }
        } finally {
            fclose($handle);
        }

        // Phase 3: Finalize
        $this->finalizeUpload($baseUrl, $token, $uploadId);

        $logger->info('✅ chunked upload finalized');
    }

    private function initUpload(
        string $baseUrl,
        string $token,
        string $backupType,
        string $filename,
        int $fileSize,
        int $totalChunks,
        string $checksum,
    ): string {
        $response = Http::timeout(30)
            ->withHeaders(['X-Notifier-Token' => $token])
            ->post(mb_rtrim($baseUrl, '/').'/uploads/init', [
                'backup_type' => $backupType,
                'filename' => $filename,
                'total_size' => $fileSize,
                'total_chunks' => $totalChunks,
                'checksum' => $checksum,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Failed to initialize upload: HTTP '.$response->status().' - '.$this->formatErrorResponse($response)
            );
        }

        $uploadId = $response->json('upload_id');

        if (empty($uploadId)) {
            throw new RuntimeException('Server did not return an upload_id');
        }

        return $uploadId;
    }

    private function sendChunk(
        string $baseUrl,
        string $token,
        string $uploadId,
        int $chunkNumber,
        string $chunkPath,
        int $maxAttempts = 3,
        int $retryDelayMs = 2000,
    ): void {
        $logger = $this->notifierLogger->get();
        $lastException = null;
        $url = mb_rtrim($baseUrl, '/').'/uploads/'.$uploadId.'/chunks/'.$chunkNumber;
        $chunkChecksum = hash_file('sha256', $chunkPath);

        if ($chunkChecksum === false) {
            throw new RuntimeException("Failed to compute checksum for chunk {$chunkNumber}");
        }

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $stream = fopen($chunkPath, 'rb');

                if ($stream === false) {
                    throw new RuntimeException("Failed to open chunk file for reading: {$chunkPath}");
                }

                $response = Http::timeout(120)
                    ->withHeaders([
                        'X-Notifier-Token' => $token,
                        'X-Chunk-Checksum' => $chunkChecksum,
                    ])
                    ->attach('chunk', $stream, 'chunk_'.$chunkNumber)
                    ->post($url);

                if ($response->successful()) {
                    return;
                }

                // Retry 429 (rate limited) - it's transient, not a client mistake
                if ($response->status() === 429) {
                    $lastException = new RuntimeException(
                        "Chunk {$chunkNumber} rate limited: HTTP 429"
                    );
                } elseif ($response->status() >= 400 && $response->status() < 500) {
                    // Don't retry other 4xx errors (client mistakes)
                    throw new RuntimeException(
                        "Chunk {$chunkNumber} rejected: HTTP ".$response->status().' - '.$this->formatErrorResponse($response)
                    );
                } else {
                    $lastException = new RuntimeException(
                        "Chunk {$chunkNumber} failed: HTTP ".$response->status().' - '.$this->formatErrorResponse($response)
                    );
                }
            } catch (RuntimeException $e) {
                throw $e;
            } catch (Throwable $e) {
                $lastException = $e;
            }

            if ($attempt < $maxAttempts) {
                $logger->warning("⚠️ chunk {$chunkNumber} attempt {$attempt} failed, retrying...", [
                    'error' => $lastException->getMessage(),
                ]);
                usleep($retryDelayMs * 1000);
            }
        }

        throw $lastException ?? new RuntimeException("Chunk {$chunkNumber} failed after {$maxAttempts} attempts");
    }

    private function finalizeUpload(string $baseUrl, string $token, string $uploadId): void
    {
        $response = Http::timeout(60)
            ->withHeaders(['X-Notifier-Token' => $token])
            ->post(mb_rtrim($baseUrl, '/').'/uploads/'.$uploadId.'/finalize');

        if (! $response->successful()) {
            throw new RuntimeException(
                'Failed to finalize upload: HTTP '.$response->status().' - '.$this->formatErrorResponse($response)
            );
        }

        // Async path (server v3+ returns 202 + status_url) - poll until terminal.
        // Sync path (older server returns 200/201 with the result inline) - done.
        if ($response->status() === 202) {
            $statusUrl = $response->json('status_url');

            if (! is_string($statusUrl) || $statusUrl === '') {
                throw new RuntimeException('Server returned 202 without status_url');
            }

            // The secret token is about to be attached to status_url. Never send it
            // anywhere but the configured, HTTPS backup origin - the rest of the
            // package enforces the same HTTPS-only invariant (see upload()).
            $this->assertTrustedStatusUrl($statusUrl, $baseUrl);

            $this->waitForCompletion($statusUrl, $token, $uploadId);
        }
    }

    /**
     * Poll the status endpoint until the upload reaches a terminal state.
     * Throws on `failed` (with the server-supplied reason) or on timeout.
     */
    private function waitForCompletion(
        string $statusUrl,
        string $token,
        string $uploadId,
        int $maxWaitSeconds = 1800,
        int $pollIntervalSeconds = 5,
    ): void {
        $logger = $this->notifierLogger->get();
        $deadline = time() + $maxWaitSeconds;
        $consecutiveErrors = 0;

        while (time() < $deadline) {
            sleep($pollIntervalSeconds);

            try {
                $response = Http::timeout(30)
                    ->withOptions(['allow_redirects' => false])
                    ->withHeaders(['X-Notifier-Token' => $token])
                    ->get($statusUrl);
            } catch (Throwable $e) {
                $consecutiveErrors++;
                $logger->warning("⚠️ status poll failed (attempt {$consecutiveErrors})", [
                    'upload_id' => $uploadId,
                    'error' => $e->getMessage(),
                ]);

                if ($consecutiveErrors >= 5) {
                    throw new RuntimeException(
                        "Status polling failed {$consecutiveErrors} times in a row: ".$e->getMessage(),
                    );
                }

                continue;
            }

            if (! $response->successful()) {
                $consecutiveErrors++;

                if ($consecutiveErrors >= 5) {
                    throw new RuntimeException(
                        'Status polling kept returning HTTP '.$response->status().' - '.$this->formatErrorResponse($response),
                    );
                }

                continue;
            }

            $consecutiveErrors = 0;

            $status = $response->json('status');
            $isTerminal = (bool) $response->json('is_terminal');

            $logger->info("➡️ upload status: {$status}", [
                'upload_id' => $uploadId,
            ]);

            if (! $isTerminal) {
                continue;
            }

            if ($status === 'completed') {
                return;
            }

            $reason = $response->json('failure_reason') ?: 'unknown';
            throw new RuntimeException("Backup upload failed on server: {$reason}");
        }

        throw new RuntimeException(
            "Backup upload did not finalize within {$maxWaitSeconds}s - server may still be processing it. Check the dashboard.",
        );
    }

    /**
     * Reject a server-supplied status_url that does not share the configured
     * backup origin. The token is a long-lived shared secret; without this guard
     * a tampered/misconfigured finalize response could redirect it to a cleartext
     * or attacker-controlled host (the GET also runs with redirects disabled).
     */
    private function assertTrustedStatusUrl(string $statusUrl, string $baseUrl): void
    {
        $base = parse_url($baseUrl);
        $target = parse_url($statusUrl);

        if (! is_array($target) || ($target['scheme'] ?? null) !== 'https') {
            throw new RuntimeException('Refusing to poll non-HTTPS status_url: '.$statusUrl);
        }

        $baseHost = mb_strtolower($base['host'] ?? '');
        $targetHost = mb_strtolower($target['host'] ?? '');

        if ($targetHost === '' || $targetHost !== $baseHost) {
            throw new RuntimeException('Refusing to poll status_url on unexpected host: '.$statusUrl);
        }

        // Treat the implicit HTTPS port (443) and an explicit :443 as equivalent.
        if (($base['port'] ?? 443) !== ($target['port'] ?? 443)) {
            throw new RuntimeException('Refusing to poll status_url on unexpected port: '.$statusUrl);
        }
    }

    /**
     * Extract a meaningful error detail from the server response.
     *
     * Laravel APIs typically return JSON with "message" and/or "errors" keys.
     * Falls back to raw body if the response is not JSON.
     */
    private function formatErrorResponse(Response|PromiseInterface $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            if (isset($json['message']) && is_string($json['message'])) {
                $detail = $json['message'];

                if (isset($json['errors']) && is_array($json['errors'])) {
                    $detail .= ' '.json_encode($json['errors'], JSON_UNESCAPED_UNICODE);
                }

                return $detail;
            }

            if (isset($json['errors']) && is_array($json['errors'])) {
                return json_encode($json['errors'], JSON_UNESCAPED_UNICODE);
            }
        }

        $body = $response->body();

        return $body !== '' ? $body : '(empty response)';
    }
}
