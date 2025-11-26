<?php

namespace App\Services\Uploader;

use App\Models\ScheduledUpload;
use App\Services\PlatformAuth\TikTokAuthService;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TikTokUploader
{
    private const INIT_ENDPOINT    = 'https://open.tiktokapis.com/v2/post/publish/video/init/';
    private const STATUS_ENDPOINT  = 'https://open.tiktokapis.com/v2/post/publish/status/fetch/';
    private const PART_SIZE_CONFIG = 'services.tiktok.chunk_size';
    private const MIN_CHUNK_SIZE   = 5 * 1024 * 1024;   // 5MB
    private const MAX_CHUNK_SIZE   = 64 * 1024 * 1024;  // 64MB
    private const MAX_CHUNKS       = 1000;
    private const SINGLE_CHUNK_THRESHOLD = 10 * 1024 * 1024; // 10MB

    public function __construct(
        protected TikTokAuthService $authService,
        protected Config $config,
        protected Cache $cache,
    ) {}

    public function upload(ScheduledUpload $upload): array
    {
        $accessToken = $this->getAccessToken();
        $filePath    = $this->validateFilePath($upload->file_path);
        $handle      = $this->openFileHandle($filePath);

        try {
            $videoSize = $this->getFileSize($handle);
            [$chunkSize, $totalChunks] = $this->calculateChunkParameters($videoSize);

            $initData = $this->initiateUpload($accessToken, $upload, $videoSize, $chunkSize, $totalChunks);
            $this->performChunkedUpload(
                $accessToken,
                $handle,
                $videoSize,
                $chunkSize,
                $initData['upload_url']
            );

            return $this->finalizeUpload($accessToken, $initData['upload_id']);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function getAccessToken(): string
    {
        $tokenPair = $this->authService->getTokenPair();
        return $tokenPair['access_token'] ?? throw new RuntimeException('Missing TikTok access token');
    }

    private function validateFilePath(string $path): string
    {
        $filePath = Storage::disk('local')->path($path);
        if (!is_readable($filePath)) {
            throw new RuntimeException("Cannot access file: {$filePath}");
        }
        return $filePath;
    }

    private function openFileHandle(string $filePath)
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Unable to open file: {$filePath}");
        }
        return $handle;
    }

    private function getFileSize($handle): int
    {
        return fstat($handle)['size'];
    }

    private function calculateChunkParameters(int $videoSize): array
    {
        // Force single-chunk for small videos to avoid validation errors
        if ($videoSize <= self::SINGLE_CHUNK_THRESHOLD) {
            return [$videoSize, 1];
        }

        $configured = $this->config->get(self::PART_SIZE_CONFIG, self::MIN_CHUNK_SIZE);
        $chunkSize  = min(max($configured, self::MIN_CHUNK_SIZE), self::MAX_CHUNK_SIZE);
        $total      = (int) ceil($videoSize / $chunkSize);

        if ($total > self::MAX_CHUNKS) {
            $chunkSize = (int) ceil($videoSize / self::MAX_CHUNKS);
            $chunkSize = min(max($chunkSize, self::MIN_CHUNK_SIZE), self::MAX_CHUNK_SIZE);
            $total     = (int) ceil($videoSize / $chunkSize);
        }

        return [$chunkSize, $total];
    }

    private function buildPostInfo(ScheduledUpload $upload): array
    {
        return [
            'title'         => trim(
                $upload->title . "\n\n" .
                $upload->description . "\n\n" .
                implode(' ', $upload->tags)
            ),
            'privacy_level' => 'SELF_ONLY',
            'is_aigc'       => true,
        ];
    }

    private function initiateUpload(
        string $accessToken,
        ScheduledUpload $upload,
        int $videoSize,
        int $chunkSize,
        int $totalChunks
    ): array {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(60)
            ->asJson()
            ->post(self::INIT_ENDPOINT, [
                'post_info'   => $this->buildPostInfo($upload),
                'source_info' => [
                    'source'              => 'FILE_UPLOAD',
                    'video_size'          => $videoSize,
                    'chunk_size'          => $chunkSize,
                    'total_chunk_count'   => $totalChunks,
                ],
            ])
            ->throw()
            ->json();

        logger()->debug('TikTok init response', $response['data']);

        if (empty($response['data']['upload_url'] ?? null)) {
            throw new RuntimeException('TikTok init failed: ' . json_encode($response));
        }

        return [
            'upload_url' => $response['data']['upload_url'],
            'upload_id'  => $response['data']['publish_id'] ?? null,
        ];
    }

    private function performChunkedUpload(
        string $accessToken,
               $handle,
        int $videoSize,
        int $chunkSize,
        string $uploadUrl
    ): void {
        $chunkCount  = 0;
        $totalChunks = (int) ceil($videoSize / $chunkSize);
        $logInterval = max(1, (int) ceil($totalChunks * 0.1));

        while (!feof($handle)) {
            $start = ftell($handle);
            $data  = fread($handle, $chunkSize);
            $len   = strlen($data);

            if ($len === 0) {
                break;
            }

            $this->uploadChunk($accessToken, $data, $uploadUrl, $start, $len, $videoSize);

            $chunkCount++;
            if ($chunkCount % $logInterval === 0 || $start + $len >= $videoSize) {
                $pct = min(100, (int) round((($start + $len) / $videoSize) * 100));
                Log::debug("TikTok upload progress: {$pct}%");
            }
        }
    }

    private function uploadChunk(
        string $accessToken,
        string $data,
        string $uploadUrl,
        int $start,
        int $len,
        int $videoSize
    ): void {
        $end = $start + $len - 1;

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Range' => "bytes {$start}-{$end}/{$videoSize}",
                'Content-Type'  => 'application/octet-stream',
            ])
            ->withBody($data, 'application/octet-stream')
            ->put($uploadUrl)
            ->throw();

        logger()->debug('TikTok chunk upload response', [
            'start' => $start,
            'end'   => $end,
            'size'  => $len,
            'response' => $response->json(),
        ]);
    }

    private function finalizeUpload(string $accessToken, ?string $uploadId): array
    {
        if (!$uploadId) {
            throw new RuntimeException('Missing upload_id for finalization');
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post(self::STATUS_ENDPOINT, ['publish_id' => $uploadId])
            ->throw()
            ->json();

        if (empty($response['data']['publish_id'] ?? null)) {
            Log::warning('TikTok upload completed but missing publish_id', $response);
        }

        return $response;
    }
}
