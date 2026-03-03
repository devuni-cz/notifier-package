<?php

declare(strict_types=1);

namespace Devuni\Notifier\Services;

use Devuni\Notifier\Support\NotifierLogger;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class ChunkedUploadService
{
    /**
     * Upload a file using the chunked upload protocol.
     *
     * 1. Init upload → get upload_id
     * 2. Send chunks sequentially with per-chunk retry
     * 3. Finalize upload → server reassembles and verifies
     */
    public function upload(string $path, string $backupType): void
    {
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

        NotifierLogger::get()->info('📦 starting chunked upload', [
            'file' => $filename,
            'size' => $fileSize,
            'chunks' => $totalChunks,
            'chunk_size' => $chunkSize,
        ]);

        // Phase 1: Init upload
        $uploadId = $this->initUpload($baseUrl, $token, $backupType, $filename, $fileSize, $totalChunks, $checksum);

        NotifierLogger::get()->info('✅ upload initialized', ['upload_id' => $uploadId]);

        // Phase 2: Send chunks
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Could not open file for reading: '.$path);
        }

        try {
            for ($chunkNumber = 1; $chunkNumber <= $totalChunks; $chunkNumber++) {
                $chunkData = fread($handle, $chunkSize);

                if ($chunkData === false) {
                    throw new RuntimeException("Failed to read chunk {$chunkNumber} from file");
                }

                $this->sendChunk($baseUrl, $token, $uploadId, $chunkNumber, $chunkData);

                NotifierLogger::get()->info("➡️ chunk {$chunkNumber}/{$totalChunks} sent");
            }
        } finally {
            fclose($handle);
        }

        // Phase 3: Finalize
        $this->finalizeUpload($baseUrl, $token, $uploadId);

        NotifierLogger::get()->info('✅ chunked upload finalized');
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
                'Failed to initialize upload: HTTP '.$response->status().' — '.$response->body()
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
        string $chunkData,
        int $maxAttempts = 3,
        int $retryDelayMs = 2000,
    ): void {
        $lastException = null;
        $url = mb_rtrim($baseUrl, '/').'/uploads/'.$uploadId.'/chunks/'.$chunkNumber;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'X-Notifier-Token' => $token,
                        'X-Chunk-Checksum' => hash('sha256', $chunkData),
                    ])
                    ->attach('chunk', $chunkData, 'chunk_'.$chunkNumber)
                    ->post($url);

                if ($response->successful()) {
                    return;
                }

                // Don't retry 4xx errors (client mistakes)
                if ($response->status() >= 400 && $response->status() < 500) {
                    throw new RuntimeException(
                        "Chunk {$chunkNumber} rejected: HTTP ".$response->status().' — '.$response->body()
                    );
                }

                $lastException = new RuntimeException(
                    "Chunk {$chunkNumber} failed: HTTP ".$response->status().' — '.$response->body()
                );
            } catch (RuntimeException $e) {
                throw $e;
            } catch (Throwable $e) {
                $lastException = $e;
            }

            if ($attempt < $maxAttempts) {
                NotifierLogger::get()->warning("⚠️ chunk {$chunkNumber} attempt {$attempt} failed, retrying...", [
                    'error' => $lastException->getMessage(),
                ]);
                usleep($retryDelayMs * 1000);
            }
        }

        throw $lastException ?? new RuntimeException("Chunk {$chunkNumber} failed after {$maxAttempts} attempts");
    }

    private function finalizeUpload(string $baseUrl, string $token, string $uploadId): void
    {
        $response = Http::timeout(300)
            ->withHeaders(['X-Notifier-Token' => $token])
            ->post(mb_rtrim($baseUrl, '/').'/uploads/'.$uploadId.'/finalize');

        if (! $response->successful()) {
            throw new RuntimeException(
                'Failed to finalize upload: HTTP '.$response->status().' — '.$response->body()
            );
        }
    }
}
