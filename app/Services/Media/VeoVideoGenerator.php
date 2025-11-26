<?php

namespace App\Services\Media;

use App\Contracts\AssetsGeneratorInterface;
use App\Services\PlatformAuth\GcpAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VeoVideoGenerator implements AssetsGeneratorInterface
{
    private const CONFIG_PATH = 'services.google_cloud.';
    private const DEFAULT_ASPECT_RATIO = '9:16';
    private const DEFAULT_VIDEO_COUNT = 1;
    private const DEFAULT_DURATION = 8;

    public function __construct(
        protected GcpAuthService $authService,
        protected ?string        $projectId = null,
        protected ?string        $location = null,
        protected ?string        $bucket = null,
        protected ?string        $model = null,
    )
    {
        $this->projectId = $projectId ?? config(self::CONFIG_PATH . 'project_id');
        $this->location = $location ?? config(self::CONFIG_PATH . 'location');
        $this->bucket = $bucket ?? config(self::CONFIG_PATH . 'results_bucket');
        $this->model = $model ?? config(self::CONFIG_PATH . 'model');

        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        foreach (['project_id', 'location', 'results_bucket', 'model'] as $key) {
            if (empty(config(self::CONFIG_PATH . $key))) {
                throw new RuntimeException("Missing Google Cloud configuration for: $key");
            }
        }
    }

    /**
     * @inheritDoc
     * @throws ConnectionException
     * @throws RequestException
     */
    public function generate(string $text, array $options = []): array
    {
        $apiUrl = $this->buildApiUrl() . ':predictLongRunning';

        $payload = $this->createPayload($text, $options);

        $response = Http::withToken($this->authService->getGoogleAccessToken())
            ->post($apiUrl, $payload)
            ->throw();

        if ($response->failed())
            throw new RuntimeException('Missing operation name in Vertex AI response');

        $responseData = $response->json();

        if (!isset($responseData['name'])) {
            throw new RuntimeException('Missing operation name in Vertex AI response');
        }

        return [
            'external_id' => $responseData['name'],
        ];
    }

    private function buildApiUrl(): string
    {

        return sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s',
            $this->location,
            $this->projectId,
            $this->location,
            $this->model
        );
    }

    private function createPayload(string $prompt, array $options): array
    {

        return [
            'instances' => [['prompt' => $prompt]],
            'parameters' => [
                'aspectRatio' => $options['aspect_ratio'] ?? self::DEFAULT_ASPECT_RATIO,
                'storageUri' => "gs://{$this->bucket}/veo_generated_videos/",
                'sampleCount' => $options['videos_count'] ?? self::DEFAULT_VIDEO_COUNT,
                'durationSeconds' => min(
                    (int) ($options['duration']  ?? self::DEFAULT_DURATION),
                    8
                ),
                'enhancePrompt ' => true,
            ],
        ];

    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function checkJobStatus(array $jobInfo): array
    {
        $apiUrl = $this->buildApiUrl() . ':fetchPredictOperation';

        $responseData = Http::withToken($this->authService->getGoogleAccessToken())
            ->post($apiUrl, ['operationName' => $jobInfo['external_id']])
            ->throw();

        if (isset($responseData['done']) && $responseData['done']) {
            $video = null;
            if(isset($responseData['response']['videos'])){
                $video = $responseData['response']['videos'][0];
            }
            return [
                'status' => 'completed',
                'asset' => $video,
            ];
        }

        return [
            'status' => 'pending',
            'details' => $responseData,
        ];
    }


    /**
     * @inheritDoc
     */
    public function platformKey(): string
    {
        return 'veo';
    }

    /**
     * @inheritDoc
     */
    public function isQueued(): bool
    {
        return true;
    }
}
