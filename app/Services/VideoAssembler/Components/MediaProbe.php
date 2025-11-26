<?php

namespace App\Services\VideoAssembler\Components;

use Illuminate\Support\Arr;
use ProtoneMedia\LaravelFFMpeg\Drivers\UnknownDurationException;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as LaravelFFMpeg;

class MediaProbe
{
    /**
     * Get the duration in seconds of a single media file.
     *
     * @param string $path
     * @return float
     * @throws UnknownDurationException
     */
    public function getDuration(string $path): float
    {
        return LaravelFFMpeg::open($path)
            ->getDurationInMiliseconds() / 1000;
    }

    /**
     * Get the frame count of a media file.
     *
     * @param string $path
     * @return int
     * @throws UnknownDurationException
     */
    public function getFrameCount(string $path)
    {
        $video = LaravelFFMpeg::open($path)->getStreams();

        $video = Arr::first($video);

        return $video->get('nb_frames');

    }
    /**
     * Get the durations of multiple media files.
     *
     * @param string[] $paths
     * @return float[]
     * @throws UnknownDurationException
     */
    public function getDurations(array $paths): array
    {
        return array_map(fn($path) => $this->getDuration($path), $paths);
    }

}
