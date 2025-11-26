<?php

namespace App\Services\VideoAssembler\Components;

use App\Helpers\FileHelpers;
use FFMpeg\Format\Video\X264;
use ProtoneMedia\LaravelFFMpeg\Drivers\UnknownDurationException;
use ProtoneMedia\LaravelFFMpeg\Filesystem\Media;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as LaravelFFMpeg;

class AudioMixer
{
    public function __construct(
        protected MediaProbe $mediaProbe
    )
    {
    }

    /**
     * Mixes the transitioned video’s audio (voice) with background music,
     * applying normalization and fade-out on the music track.
     *
     * @param string $transitionedVideoPath
     * @param string $musicPath
     * @return string Path to the final mixed video file
     * @throws UnknownDurationException
     */
    public function mixWithBackground(string $transitionedVideoPath, string $musicPath): string
    {
        $voiceDuration = $this->mediaProbe->getDuration($transitionedVideoPath);
        $fadeDur       = 1.0;
        $fadeStart     = max(0, $voiceDuration - $fadeDur);

        $exporter = LaravelFFMpeg::fromDisk('local')
            ->open([$transitionedVideoPath, $musicPath])
            ->export();


        $exporter->addFilter(
            '[0:v]',
            "trim=0:{$voiceDuration},setpts=PTS-STARTPTS",
            '[final_video]'
        );

        $exporter->addFilter(
            '[1:a]',
            "volume=0.08,atrim=0:{$voiceDuration},asetpts=PTS-STARTPTS",
            '[music_prep]'
        );

        $exporter->addFilter(
            '[0:a]',
            "afade=t=out:st={$fadeStart}:d={$fadeDur}",
            '[voice_faded]'
        );

        $exporter->addFilter(
            '[voice_faded]',
            'asplit=2',
            '[voice_main][sc]'
        );

        $exporter->addFilter(
            '[music_prep][sc]',
            'sidechaincompress=threshold=-30dB:ratio=5:attack=50:release=200:makeup=1.5',
            '[music_ducked]'
        );
        $exporter->addFilter(
            '[voice_main][music_ducked]',
            'amix=inputs=2:duration=first:dropout_transition=1000:normalize=0',
            '[mixed]'
        );

        // 7. Final loudness normalization
        $exporter->addFilter(
            '[mixed]',
            'loudnorm=I=-23:TP=-2.0:LRA=14:linear=true',
            '[final_audio]'
        );


        $output = FileHelpers::createTempFilePath('final_vid_', 'mp4');

        $format = (new X264)
            ->setKiloBitrate(1500)
            ->setAudioKiloBitrate(128)
            ->setAudioCodec('aac')
            ->setAudioChannels(2)
            ->setAdditionalParameters([
                '-profile:v', 'high',
                '-level', '4.1',
                '-pix_fmt', 'yuv420p',
                '-movflags', '+faststart',
                '-ar', '48000',

            ]);

        $exporter->addFormatOutputMapping(
            $format,
            Media::make('local', $output),
            ['[final_video]', '[final_audio]']
        )
            ->save($output);

        return $output;
    }

}
