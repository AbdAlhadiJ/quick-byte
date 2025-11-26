<?php

namespace App\Services\Uploader;

use App\Models\ScheduledUpload;
use App\Services\PlatformAuth\YouTubeAuthService;
use Google\Client as GoogleClient;
use Google\Exception;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

class YouTubeUploader
{

    private const DEFAULT_DESCRIPTION_TEMPLATE =
        "QuickByte delivers bite-sized tech news, from AI breakthroughs to the coolest gadgets – all in seconds!\n" .
        "Stay ahead with fast tech insights on the latest trends!\n" .
        "Subscribe now for daily tech updates and never miss a byte of innovation. 🚀";

    private const PARTS = 'snippet,status';
    private const DEFAULT_CHUNK_SIZE = 1 * 1024 * 1024; // 1 MB

    public function __construct(
        protected YouTubeAuthService $authService,
        protected Config             $config,
        protected Cache              $cache,
    )
    {
    }

    /**
     * Upload a video file to YouTube with JSON response
     *
     * @param ScheduledUpload $upload
     * @return Video JSON response structure
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function upload(ScheduledUpload $upload): Video
    {
        $client = $this->initializeClient();
        $youtube = new YouTube($client);

        $video = $this->createYouTubeVideo($upload);
        return $this->resumableUpload($youtube, $video, $upload);

    }

    /**
     * Initialize and configure Google Client
     * @return GoogleClient
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function initializeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setAccessToken($this->authService->getTokenPair());

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->authService->updateTokenPair($client->getAccessToken());
        }

        return $client;
    }

    /**
     * Create YouTube video resource
     */
    protected function createYouTubeVideo(ScheduledUpload $upload): Video
    {
        $tags = $upload->tags
            ? implode(' ', $upload->tags)
            : '';

        $fullDescription = trim(implode("\n\n", [
            $upload->description,
            self::DEFAULT_DESCRIPTION_TEMPLATE,
            $tags,
        ]));

        $snippet = new VideoSnippet();
        $snippet->setTitle($upload->title);
        $snippet->setDescription($fullDescription);
        $snippet->setCategoryId($upload->category_id ?? '28');
        $snippet->setTags($upload->cleaned_tags ?? []);

        $status = new VideoStatus();
        $status->privacyStatus = 'public';
        $status->selfDeclaredMadeForKids = false;
        $status->containsSyntheticMedia = true;

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        return $video;
    }

    /**
     * Handle resumable upload process
     * @throws \Exception
     */
    protected function resumableUpload(YouTube $youtube, Video $video, ScheduledUpload $upload): mixed
    {
        $chunkSize = $this->config->get('services.youtube.chunk_size', self::DEFAULT_CHUNK_SIZE);
        $filePath = Storage::disk('local')->path($upload->file_path);
        $fileSize = filesize($filePath);
        $mimeType = Storage::mimeType($upload->file_path);

        $client = $youtube->getClient();
        $client->setDefer(true);

        $insertRequest = $youtube->videos->insert(self::PARTS, $video);
        $media = new MediaFileUpload($client, $insertRequest, $mimeType, null, true, $chunkSize);
        $media->setFileSize($fileSize);

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Failed to open file: {$filePath}");
        }

        $result = false;
        $retryCount = 0;
        $maxRetries = 3;

        try {
            while (!$result && !feof($handle)) {
                $chunk = fread($handle, $chunkSize);

                try {
                    $result = $media->nextChunk($chunk);
                } catch (\Exception $e) {
                    if ($retryCount++ < $maxRetries) {
                        Log::warning("Upload retry attempt {$retryCount} for {$filePath}");
                        continue;
                    }
                    throw $e;
                }
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }

        if (!$result) {
            throw new RuntimeException('Upload did not complete successfully');
        }

        return $result;
    }

}
