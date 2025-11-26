<?php

namespace App\Services\Media;

use App\Contracts\AssetsGeneratorInterface;
use App\Helpers\FileHelpers;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use RuntimeException;


class ElevenLabsService implements AssetsGeneratorInterface
{

    public function __construct(protected array $config)
    {
    }

    /**
     * Generate a voiceover MP3 URL for the given text.
     *
     * @param string $text The text to synthesize.
     * @param array $options Options:
     * @return array
     *
     * @throws ConnectionException
     * @throws LimiterTimeoutException
     */
    public function generate(string $text, array $options = []): array
    {
        $voiceId = $options['voice_id'] ?? $this->config['default_voice_id'];
        $payload = $this->buildPayload($text, $options);

        $response = Redis::throttle('elevenlabs:tts')
            ->allow(1)
            ->every(1)
            ->block(30)
            ->then(
                fn() => $this->postToApi($voiceId, $payload),
                fn() => throw new ConnectionException('Could not acquire ElevenLabs throttle slot')
            );

        $this->ensureSuccessfulResponse($response);

        $tmpFilePath = $this->saveAudioFile($response);

        return [
            'external_id' => 'elevenLabs',
            'file_path'   => $tmpFilePath,
            'metadata'    => ['word_alignment' => $this->extractWordAlignment($response)],
        ];


    }


    /**
     * Build the HTTP payload for ElevenLabs TTS.
     *
     * @param string $text Text to synthesize.
     * @param array $options Options array.
     * @return array             Payload data.
     */
    private function buildPayload(string $text, array $options): array
    {

        return [
            'text' => $text,
            'model_id' => Arr::get($options, 'model_id', $this->config['default_model_id']),
            'voice_settings' => [
                'stability' => Arr::get($options, 'stability', $this->config['default_stability']),
                'use_speaker_boost' => Arr::get($options, 'use_speaker_boost', $this->config['default_speaker_boost']),
                'similarity_boost' => Arr::get($options, 'similarity_boost', $this->config['default_similarity_boost']),
                'style' => Arr::get($options, 'style', $this->config['default_style']),
                'speed' => Arr::get($options, 'speed', 1.0),
            ],
            'previous_text' => Arr::get($options, 'previous_text', ''),
            'next_text' => Arr::get($options, 'next_text', ''),
            "timestamp_format" => "word"
        ];
    }


    /**
     * Send a POST request to the ElevenLabs TTS endpoint.
     *
     * @param string $voiceId Voice identifier for URL.
     * @param array $payload Request body.
     * @return Response          HTTP client response.
     * @throws ConnectionException
     */
    private function postToApi(string $voiceId, array $payload): Response
    {
        $url = $this->config['api_endpoint'] . '/' . $voiceId . '/with-timestamps';

        return Http::withHeaders([
            'xi-api-key' => $this->config['api_key'],
        ])->retry(5, 1000, function ($exception, $request) {
                return optional($exception->response)->status() === 429
                    || $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->post($url, $payload);
    }

    /**
     * Ensure the HTTP response is successful or throw.
     *
     * @param Response $response
     * @return void
     *
     * @throws RuntimeException
     */
    private function ensureSuccessfulResponse(Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException(
                'ElevenLabs API error [' . $response->status() . ']: ' . $response->body()
            );
        }
    }

    /**
     * Persist raw MP3 data to storage and return its public URL.
     *
     * @param Response $response
     * @return string              Public URL to the file.
     */
    private function saveAudioFile(Response $response): string
    {
        $tmpFilePath = FileHelpers::createTempFilePath('audio_', 'mp3');

        Storage::disk('local')->put($tmpFilePath, base64_decode($response['audio_base64']));

        return $tmpFilePath;
    }

    /**
     * @inheritDoc
     */
    public function platformKey(): string
    {
        return 'elevenlabs';
    }

    /**
     * @inheritDoc
     */
    public function checkJobStatus(array $jobInfo): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isQueued(): bool
    {
        return false;
    }

    protected function extractWordAlignment($response): array
    {

        $chars = $response['alignment']['characters'];
        $starts = $response['alignment']['character_start_times_seconds'];
        $ends = $response['alignment']['character_end_times_seconds'];

        $words = [];
        $word = '';
        $start = null;
        foreach ($chars as $i => $char) {
            if ($char === ' ') {
                if ($word !== '') {
                    $words[] = ['word' => $word, 'start_time' => $start, 'end_time' => $ends[$i - 1]];
                    $word = '';
                    $start = null;
                }
                continue;
            }

            $word .= $char;
            if ($start === null) {
                $start = $starts[$i];
            }
        }
        if ($word !== '') {
            $words[] = ['word' => $word, 'start_time' => $start, 'end_time' => $ends[array_key_last($ends)]];
        }


        return $words;
    }
}
