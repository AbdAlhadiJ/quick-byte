<?php

namespace App\Services\Script;

use Illuminate\Support\Arr;

readonly class ScriptProcessor
{
    public function __construct(
        private ScriptVoiceoverFormatter    $voiceoverFormatter,
    )
    {
    }

    /**
     * Process raw script array into FFmpeg-ready structured script
     *
     * @param array $scriptBatch
     * @return array
     */
    public function process(array $scriptBatch): array
    {
        $scripts = [];

        foreach ($scriptBatch as $itemWrapper) {

            $record = $itemWrapper['item'] ?? $itemWrapper;

            $rawContent = Arr::get($record, 'response.body.choices.0.message.content');

            $decoded = json_decode($rawContent, true);

            $script = [
                'article_id' => Arr::get($record, 'custom_id'),
                'title' => $decoded['metadata']['title'],
                'hook' => $decoded['hook'],
                'payload' => $decoded,
                'bg_music' => $decoded['background_music'],
                'metadata' => $decoded['metadata'],
            ];

            $script['scenes'] = $this->formatScenes($decoded['scenes']);

            $scripts[] = $script;
        }
        return $scripts;

    }

    /**
     * Structure scene payload
     *
     * @param mixed $script
     * @return array
     */
    private function formatScenes(mixed $script): array
    {
        $scenes = [];

        foreach ($script as $index => $scene) {

            list($processedText, $speechDuration) = $this->voiceoverFormatter->applySsml(
                $scene['voiceover']
            );

            $prevRaw = $rawScenes[$index - 1]['voiceover'] ?? '';
            $nextRaw = $rawScenes[$index + 1]['voiceover'] ?? '';

            $prevText = $this->voiceoverFormatter->cleanText($prevRaw);
            $nextText = $this->voiceoverFormatter->cleanText($nextRaw);


            $voiceOver = [
                'text' => $processedText,
                'previous_text' => $prevText,
                'next_text' => $nextText,
            ];

            $transitionDuration = 0.5;

            $totalDuration = $speechDuration + $transitionDuration;

            $scene = [
                'order' => $index + 1,
                'headline' => $scene['headline'],
                'visual' => $scene['visual'],
                'voiceover' => $voiceOver,
                'transition' => $scene['transition'],
                'sound_effect' => $scene['sound_effect'],
                'duration' => $totalDuration,
            ];

            $scenes[] = $scene;
        }

        return $scenes;
    }
}
