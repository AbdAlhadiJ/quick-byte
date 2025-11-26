<?php

namespace App\Services\Script;

class ScriptVoiceoverFormatter
{

    /**
     * SSML mappings for technical terms
     * @var array<string, string>
     */
    private array $ssmlTerms = [
        'ERM' => '<say-as interpret-as="characters">ERM</say-as>',
        'LRA' => '<say-as interpret-as="characters">LRA</say-as>',
        'PWM' => '<say-as interpret-as="characters">PWM</say-as>',
        'DC' => '<say-as interpret-as="characters">DC</say-as>',
        'AC' => '<say-as interpret-as="characters">AC</say-as>',
        'Hz' => 'Hertz',
        'kHz' => 'kilohertz',
    ];

    public static function estimateDuration(
        string $text,
        float  $speed = 1.0,
        int    $baseWpm = 150
    ): float
    {
        $wordCount = str_word_count($text, 0);
        $effectiveWpm = $baseWpm * $speed;
        $dryReadSec = ($wordCount / max($effectiveWpm, 1)) * 60;
        $shortBreaks = preg_match_all('/[.!?;]/', $text);
        $longBreaks = substr_count($text, ':');
        $extraPauseSec = ($shortBreaks * 0.3) + ($longBreaks * 0.5);
        return $dryReadSec + $extraPauseSec;
    }

    /**
     * Apply SSML term replacements and add break tags after punctuation
     *
     * @param string $text
     * @return array
     */
    public function applySsml(string $text): array
    {

        foreach ($this->ssmlTerms as $term => $ssml) {
            $pattern = '/\b' . preg_quote($term, '/') . '\b/';
            $text = preg_replace($pattern, $ssml, $text);
        }

        $estimatedDuration = $this->estimateDuration($this->cleanText($text));

        return [$text, $estimatedDuration];
    }

    public function cleanText(string $text): string
    {
        return preg_replace('/<break time="[\d.]+s"\/>/', '', $text);
    }
}
