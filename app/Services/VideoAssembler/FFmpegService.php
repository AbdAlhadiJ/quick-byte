<?php

namespace App\Services\VideoAssembler;

use App\Contracts\VideoAssemblerInterface;
use App\Models\Script;
use App\Services\VideoAssembler\Components\AudioMixer;
use App\Services\VideoAssembler\Components\MediaProbe;
use App\Services\VideoAssembler\Components\SceneProcessor;
use App\Services\VideoAssembler\Components\TransitionBuilder;
use ProtoneMedia\LaravelFFMpeg\Drivers\UnknownDurationException;

class FFmpegService implements VideoAssemblerInterface
{

    public function __construct(
        protected SceneProcessor    $sceneProcessor,
        protected TransitionBuilder $transitionBuilder,
        protected AudioMixer        $audioMixer,
        protected MediaProbe        $mediaProbe
    )
    {
    }

    /**
     * @inheritDoc
     * @throws UnknownDurationException
     */
    public function render(Script $script, string $musicPath): string
    {
        $processedScenes = $this->sceneProcessor->processScenes($script);

        $transitionedVideo = $this->transitionBuilder->applyTransitions($processedScenes);

        return $this->audioMixer->mixWithBackground($transitionedVideo, $musicPath);

    }
}
