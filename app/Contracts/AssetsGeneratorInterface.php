<?php

namespace App\Contracts;

use Illuminate\Http\Client\ConnectionException;

interface AssetsGeneratorInterface
{
    /**
     * Identify the platform key.
     *
     * @return string
     */
    public function platformKey(): string;

    /**
     * Generate Assets for the given text.
     *
     * @param string $text
     * @param array $options Options:
     * @return array
     *
     * @throws ConnectionException
     */
    public function generate(string $text, array $options = []): array;

    /**
     * Check Asset generation job status
     *
     * @param array $jobInfo
     * @return array
     * ]
     */
    public function checkJobStatus(array $jobInfo): array;

    /**
     * Flag is video generation process queued or not
     *
     * @return bool
     * ]
     */
    public function isQueued(): bool;
}
