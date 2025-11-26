<?php

namespace App\Services\Script;

class ScriptVisualPromptFormatter
{
    private array $visualPrefixes = [
        'runwayml' => 'CINEMATIC SHOT: %s | motion_speed=0.8, motion_consistency=high, 30fps',
        'vertex ai' => 'CINEMATIC ULTRA-DETAIL: %s | kinematic_behavior=realistic, physics_accuracy=high, motion_precision=0.9, 8K, RED camera, Academy ratio, 30fps',
    ];

    /**
     * Format a raw visual prompt based on the service source.
     *
     * @param string $prompt
     * @param string $service
     * @return string
     */
    public function format(string $prompt, string $service): string
    {
        $lowerService = strtolower($service);

        if (str_contains($lowerService, 'pixabay')) {
            $keywords = implode(', ', array_slice(explode(' ', $prompt), 0, 5));
            return "Stock footage: {$keywords} | 4K, cinematic";
        }

        if (isset($this->visualPrefixes[$lowerService])) {
            return sprintf($this->visualPrefixes[$lowerService], $prompt);
        }

        return $prompt;
    }
}
