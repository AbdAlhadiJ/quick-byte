<?php

namespace App\Services\VideoAssembler\Components;

use App\Helpers\FileHelpers;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Drivers\UnknownDurationException;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;
use ProtoneMedia\LaravelFFMpeg\Filesystem\Media;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class TransitionBuilder
{
    public function __construct(
        protected MediaProbe $mediaProbe
    )
    {
    }

    /**
     * Applies video xfade transitions and mixes scene audio tracks together.
     *
     * @param array $scenes
     * @return string
     * @throws UnknownDurationException
     */
    public function applyTransitions(array $scenes): string
    {
        $videoPaths = array_column($scenes, 'video');

        $sfxPaths = array_map(fn($scene) => $scene['sfx'], array_filter(
            $scenes,
            fn($i) => $i < count($scenes) - 1,
            ARRAY_FILTER_USE_KEY
        ));

        $exporter = FFMpeg::fromDisk('local')
            ->open(array_merge($videoPaths, $sfxPaths))
            ->export();

        $durations = $this->mediaProbe->getDurations($videoPaths);

        [$finalVideoLabel, $finalAudioLabel] =
            $this->chainVideoTransitions($exporter, $scenes, $durations);


        // 6. Export combined stream
        $output = FileHelpers::createTempFilePath('transitions_', 'mp4');

        $format = (new X264)
            ->setAudioCodec('aac')
            ->setAdditionalParameters(['-shortest']);

        $exporter->addFormatOutputMapping(
            $format,
            Media::make('local', $output),
            [$finalVideoLabel, $finalAudioLabel]
        )
            ->save();

        return $output;
    }

    /**
     * Adds chained xfade filters between video streams.
     *
     * @param MediaExporter $exporter
     * @param array<int, array{video: string, transition: array|null}> $scenes
     * @param float[] $durations
     * @return string[]
     */
    protected function chainVideoTransitions(MediaExporter $exporter, array $scenes, array $durations): array
    {
        $videoCount = count($scenes);

        $exporter->addFilter('[0:v]', 'null', '[v0]');
        $prevVLabel = '[v0]';
        $elapsed = $durations[0];

        for ($i = 1; $i < $videoCount; $i++) {
            if ($i === 0) continue;

            $type   = $scenes[$i]['transition'] ?: 'fade';
            $dur = 0.5;
            $offset = $elapsed - $dur;

            $inLabels = "{$prevVLabel}[{$i}:v]";
            $outLabel = "[v{$i}]";
            $xfade    = "xfade=transition={$type}:duration={$dur}:offset={$offset}";

            $exporter->addFilter($inLabels, $xfade, $outLabel);

            $prevVLabel = $outLabel;
            $elapsed   += $durations[$i] - $dur;
        }

        $exporter->addFilter('[0:a]', 'anull', '[a0]');
        $prevALabel = '[a0]';
        $elapsed     = $durations[0];
        for ($i = 1; $i < $videoCount; $i++) {
            $dur    = 0.5;
            $offset = $elapsed - $dur;
            $delay  = (int)round($offset * 1000);

            $inAudioLabels = "{$prevALabel}[{$i}:a]";
            $outAudioLabel = "[a_mid{$i}]";
            $crossfade     = "acrossfade=d={$dur}:c1=tri:c2=tri";
            $exporter->addFilter($inAudioLabels, $crossfade, $outAudioLabel);

            // 3) now overlay the SFX, if present
            $sfxInputIndex  = $videoCount + ($i - 1);
            $delayedSfxLabel = "[sfx_del{$i}]";
            $exporter->addFilter(
                "[{$sfxInputIndex}:a]",
                "adelay={$delay}|{$delay},volume=0.3",
                $delayedSfxLabel
            );

            $mixedLabel = "[a{$i}]";
            $exporter->addFilter(
                "{$outAudioLabel}{$delayedSfxLabel}",
                "amix=inputs=2:duration=first:dropout_transition=0",
                $mixedLabel
            );

            $prevALabel = $mixedLabel;
            $elapsed   += $durations[$i] - $dur;
        }
        $totalDuration = $elapsed;

        $normalizedALabel = '[a_normalized]';
        $exporter->addFilter($prevALabel, 'loudnorm=I=-23:TP=-2.0:LRA=14', $normalizedALabel);

        $fadedALabel = '[a_faded]';
        $exporter->addFilter($normalizedALabel, "afade=out:st=".($totalDuration - 0.05).":d=0.05", $fadedALabel);

        return [$prevVLabel, $fadedALabel];


    }


}
