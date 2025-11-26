<?php

namespace App\Contracts;

use App\Models\Script;

interface VideoAssemblerInterface
{
    /**
     * Start Video Generation FFMpeg Process
     *
     * @param Script $script
     * @param string $musicPath
     * @return string
     */
    public function render(Script $script, string $musicPath): string;
}
