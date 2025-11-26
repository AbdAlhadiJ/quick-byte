<?php

namespace App\Jobs;

use App\Contracts\AssetsGeneratorInterface;
use App\Enums\AssetStatus;
use App\Enums\GeneratorType;
use App\Helpers\FileHelpers;
use App\Jobs\Middleware\ThrottlesElevenLabs;
use App\Models\ScriptScene;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class GenerateAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function middleware(): array
    {
        return [new ThrottlesElevenLabs];
    }


    /**
     * @param ScriptScene $scene
     * @param string|null $generatorType
     */
    public function __construct(
        protected ScriptScene $scene,
        protected ?string     $generatorType = null
    )
    {
    }


    /**
     * Execute the job.
     *
     * @throws LockTimeoutException
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $generators = [
            GeneratorType::AUDIO->value => app('audio_generator'),
            GeneratorType::VISUAL->value => app('visual_generator'),
        ];
        if ($this->generatorType) {
            if (!isset($generators[$this->generatorType])) {
                throw new InvalidArgumentException("Unknown generator type: {$this->generatorType}");
            }
            $generators = [$this->generatorType => $generators[$this->generatorType]];
        }

        foreach ($generators as $type => $generator) {
            $this->processGenerator($type, $generator);
        }
    }

    /**
     * @throws ConnectionException
     */
    protected function processGenerator(string $type, AssetsGeneratorInterface $generator): void
    {

        $payload = match ($type) {
            'audio' => $this->scene->voiceover,
            'visual' => $this->scene->visual,
            default => throw new InvalidArgumentException("Unknown asset type: {$type}"),
        };

        $options = !is_array($payload)
            ? ['duration' => $this->scene->duration]
            : [];

        $response = $generator->generate(
            text: is_array($payload) ? $payload['text'] : $payload,
            options: $options
        );

        $isQueued = $generator->isQueued();
        $provider = $generator->platformKey();
        $status = $isQueued ? AssetStatus::QUEUED : AssetStatus::COMPLETED;

        $localPath = null;
        if (!$isQueued && isset($response['file_path'])) {
            $localPath = FileHelpers::createScriptAssetFilePath(
                prefix: $type . '_',
                extension: $type === GeneratorType::AUDIO->value ? 'mp3' : 'mp4',
                subdir: $this->scene->script_id . '/generated_voiceover'
            );
            Storage::disk('local')->move($response['file_path'], $localPath);
        }

        $this->scene->assets()->create([
            'source' => $provider,
            'script_id' => $this->scene->script_id,
            'type' => $type,
            'external_id' => $response['external_id'],
            'status' => $status,
            'local_path' => $localPath,
            'raw_response' => $response,
            'metadata' => data_get($response, 'metadata', []),
        ]);

    }


}
