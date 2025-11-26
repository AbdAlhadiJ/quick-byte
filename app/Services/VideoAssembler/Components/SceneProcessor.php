<?php

namespace App\Services\VideoAssembler\Components;

use App\Enums\AssetStatus;
use App\Enums\GeneratorType;
use App\Helpers\FileHelpers;
use App\Models\Asset;
use App\Models\Script;
use App\Models\ScriptScene;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Drivers\UnknownDurationException;
use ProtoneMedia\LaravelFFMpeg\Filesystem\Media;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as LaravelFFMpeg;

class SceneProcessor
{
    public function __construct(
        protected AssSubtitleBuilder $captionBuilder,
        protected MediaProbe         $mediaProbe

    )
    {
    }

    /**
     * Processes all scenes of a script: applies captions and exports
     * video and audio tracks for each scene.
     *
     * @param Script $script
     * @return array
     * @throws UnknownDurationException
     */
    public function processScenes(Script $script): array
    {
        $results = [];

        foreach ($script->scenes->sortBy('order') as $index => $scene) {
            $results[] = $this->processScene($scene, $index);
        }
        return $results;
    }

    /**
     * Processes a single scene: applies subtitles to the video, exports video and audio.
     *
     * @param ScriptScene $scene
     * @return array
     * @throws UnknownDurationException
     */
    protected function processScene(ScriptScene $scene, int $index): array
    {
        $video = $this->getAsset($scene, GeneratorType::VISUAL->value);
        $audio = $this->getAsset($scene, GeneratorType::AUDIO->value);

        $delayMs = $index === 0  ? 0 : 0.5;

        $assFile = $this->captionBuilder->buildWordLevelSubtitles(
            wordAlignment: $audio->metadata['word_alignment'],
            offsetSeconds: $delayMs
        );

        $videoOut = $this->exportMergedClip(
            $video['local_path'],
            $audio['local_path'],
            $assFile,
            $delayMs
        );

        return [
            'video' => $videoOut,
            'transition' => $scene->transition ?? null,
            'sfx' => 'sfx/' . $scene->sound_effect . '.mp3'
        ];
    }

    /**
     * Retrieve Scene Asset Local Pth
     *
     * @param ScriptScene $scene
     * @param string $type
     * @return Asset|null
     */
    protected function getAsset(ScriptScene $scene, string $type): ?Asset
    {
        foreach ($scene->assets as $asset) {
            if ($asset->type === $type && $asset->status === AssetStatus::COMPLETED->value) {
                return $asset;
            }
        }

        return null;
    }


    /**
     * Merges video and audio inputs, trims duration, applies ASS subtitles, and saves the final clip.
     *
     * @param string $videoPath
     * @param string $audioPath
     * @param string $assPath
     * @param float $delayMs
     * @return string Path to the exported video clip
     * @throws UnknownDurationException
     */
    protected function exportMergedClip(
        string $videoPath,
        string $audioPath,
        string $assPath,
        float  $delayMs
    ): string
    {

        $outputPath = FileHelpers::createTempFilePath('scene_final_', 'mp4', 'scenes');

        $exporter = LaravelFFMpeg::fromDisk('local')
            ->open([$videoPath, $audioPath])
            ->export();

        $videoDuration = $this->mediaProbe->getDuration($videoPath);
        $audioDuration = $this->mediaProbe->getDuration($audioPath) + $delayMs;

        if ($audioDuration > $videoDuration) {
            $loops = ceil($audioDuration / $videoDuration) - 1;

            $exporter->addFilter(
                '[0:v]',
                sprintf('trim=0:%.2f,setpts=PTS-STARTPTS,split=2[fwd][rev_in]', $videoDuration),
                null
            );

            $exporter->addFilter(
                '[rev_in]',
                'reverse,setpts=PTS-STARTPTS',
                '[rev]'
            );

            $exporter->addFilter(
                '[fwd][rev]',
                sprintf('concat=n=2:v=1:a=0,loop=%d:1:0,trim=0:%.2f,setpts=PTS-STARTPTS', $loops, $audioDuration),
                '[vout]'
            );
        } else {
            // Trim video to audio duration
            $exporter->addFilter(
                '[0:v]',
                sprintf('trim=0:%.2f,setpts=PTS-STARTPTS', $audioDuration),
                '[vout]'
            );
        }

        $fontDir = public_path('fonts');

        $exporter->addFilter(
            '[vout]',
            "ass={$assPath}:fontsdir={$fontDir}",
            '[vsub]'
        );

        $audioDelay = $delayMs * 1000;

        $exporter->addFilter(
            "[1:a]",
            "adelay={$audioDelay}:all=1",
            "[aout]"
        );


        // Output mapping
        $exporter->addFormatOutputMapping(
            (new X264)->setAudioCodec('aac'),
            Media::make('local', $outputPath),
            ['[vsub]', '[aout]']
        )->save($outputPath);

        return $outputPath;

    }


}
