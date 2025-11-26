<?php

namespace App\Services\Uploader;

use App\Models\ScheduledUpload;
use App\Services\PlatformAuth\InstagramAuthService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

class InstagramUploader
{
    private const GRAPH_VERSION = 'v23.0';
    private const API_BASE = 'https://graph.facebook.com/';
    private const POLL_INTERVAL = 5; // seconds
    private const MAX_POLL_ATTEMPTS = 20;

    public function __construct(
        protected InstagramAuthService $authService,
        protected ?string              $igUserId = null // auto-discover if null
    )
    {
        if (!$this->igUserId) {
            $this->igUserId = $this->fetchIGUserId();
            if (!$this->igUserId) {
                throw new RuntimeException('Unable to determine Instagram user ID.');
            }
        }
    }

    /**
     * Publish a single-photo post.
     */
    public function uploadPhoto(string $imageUrl, string $caption = '', bool $shareToFeed = true): array
    {
        $token = $this->authService->getAccessToken();

        $response = Http::post(
            self::API_BASE . self::GRAPH_VERSION . "/{$this->igUserId}/media",
            [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'share_to_feed' => $shareToFeed,
                'access_token' => $token,
            ]
        );

        $body = $response->json();
        if (!$response->ok() || empty($body['id'])) {
            Log::error('Instagram create-photo-container failed', ['body' => $response->body()]);
            throw new RuntimeException('Failed to create Instagram photo container.');
        }

        return $this->publishContainer($body['id'], $token);
    }

    /**
     * Publish a single-video post (Reel).
     */
    public function upload(
        ScheduledUpload $upload,
        int             $thumbOffsetMs = 0,
        bool            $shareToFeed = false
    ): array
    {

        $token = $this->authService->getAccessToken();

        $expiry = now()->addMinutes(10);

        $videoUrl = Storage::disk('local')->temporaryUrl($upload->file_path, $expiry);

        $params = [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' =>  trim(
                $upload->title . "\n\n" .
                $upload->description . "\n\n" .
                implode(' ', $upload->tags)
            ),
            'access_token' => $token,
        ];
        if ($thumbOffsetMs > 0) {
            $params['thumb_offset'] = $thumbOffsetMs;
        }
        if ($shareToFeed) {
            $params['share_to_feed'] = true;
        }

        $response = Http::post(
            self::API_BASE . self::GRAPH_VERSION . "/{$this->igUserId}/media",
            $params
        );

        $body = $response->json();
        if (!$response->ok() || empty($body['id'])) {
            Log::error('Instagram create-video-container failed', ['body' => $response->body()]);
            throw new RuntimeException('Failed to create Instagram video container.');
        }

        $containerId = $body['id'];
        $status = $this->pollContainerStatus($containerId, $token);

        if ($status !== 'FINISHED') {
            $details = $this->getContainerErrorDetails($containerId, $token);
            Log::error('Instagram video processing failed', ['container' => $containerId, 'status' => $status, 'details' => $details]);
            throw new RuntimeException("Video processing failed (status: {$status}). Details: {$details}");
        }

        return $this->publishContainer($containerId, $token);
    }

    /**
     * Polls the container until ready or error.
     */
    protected function pollContainerStatus(string $containerId, string $token): string
    {
        $attempt = 0;
        do {
            sleep(self::POLL_INTERVAL);
            $status = $this->checkContainerStatus($containerId, $token);
            Log::info("Instagram container status ({$containerId}): {$status}");
            $attempt++;
        } while (!in_array($status, ['FINISHED', 'ERROR'], true) && $attempt < self::MAX_POLL_ATTEMPTS);

        return $status;
    }

    /**
     * Retrieves error details from a failed container, including OAuth errors.
     */
    protected function getContainerErrorDetails(string $containerId, string $token): string
    {
        $resp = Http::get(
            self::API_BASE . self::GRAPH_VERSION . "/{$containerId}",
            [
                'fields' => 'status_code,status,id',
                'access_token' => $token,
            ]
        );

        $raw = $resp->body();
        $data = null;
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return "Invalid JSON response: {$raw}";
        }

        if (isset($data['error'])) {
            $err = $data['error'];
            $msg = $err['message'] ?? 'No message';
            $code = $err['code'] ?? 'No code';
            return "OAuthException (code {$code}): {$msg}";
        }

        $message = $data['error_message'] ?? 'No error_message';
        $code = $data['failure_code'] ?? 'No failure_code';
        return "Code: {$code}, Message: {$message}";
    }

    /**
     * Publish a container by ID.
     */
    protected function publishContainer(string $creationId, string $token): array
    {
        $resp = Http::post(
            self::API_BASE . self::GRAPH_VERSION . "/{$this->igUserId}/media_publish",
            [
                'creation_id' => $creationId,
                'access_token' => $token,
            ]
        );

        $body = $resp->json();
        if (!$resp->ok() || empty($body['id'])) {
            Log::error('Instagram publish-media failed', ['body' => $resp->body()]);
            throw new RuntimeException('Failed to publish Instagram media.');
        }

        return $body;
    }

    /**
     * Checks the status of a media container.
     */
    protected function checkContainerStatus(string $containerId, string $token): string
    {
        $resp = Http::get(
            self::API_BASE . self::GRAPH_VERSION . "/{$containerId}",
            [
                'fields' => 'status_code',
                'access_token' => $token,
            ]
        );

        $body = $resp->json();
        if (!$resp->ok() || empty($body['status_code'])) {
            Log::error('Instagram container-status failed', ['body' => $resp->body()]);
            throw new RuntimeException('Failed to check Instagram container status.');
        }

        return $body['status_code'];
    }

    /**
     * Auto-fetch the Instagram Business/Creator Account ID.
     */
    protected function fetchIGUserId(): ?string
    {
        $token = $this->authService->getAccessToken();

        $resp = Http::withToken($token)
            ->acceptJson()
            ->get(self::API_BASE . self::GRAPH_VERSION . '/me/accounts', [
                'fields' => 'instagram_business_account',
            ]);

        if (!$resp->ok()) {
            Log::error('Failed to fetch Facebook Pages for IG user discovery', ['body' => $resp->body()]);
            return null;
        }

        foreach ($resp->json('data', []) as $page) {
            if (!empty($page['instagram_business_account']['id'] ?? null)) {
                return $page['instagram_business_account']['id'];
            }
        }

        return null;
    }
}
