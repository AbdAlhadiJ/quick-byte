<?php

namespace App\Services\Script;

use App\Models\Article;

class ScriptPromptBuilder
{
    private const MODEL = 'gpt-4-turbo';
    private const MAX_TOKENS = 1700;

    /**
     * Build the full chat payload messages for OpenAI
     *
     * @param Article $article
     * @param string $musicCategories
     * @param string $sfx
     * @return array
     */
    public function buildFor(Article $article, string $musicCategories, string $sfx): array
    {
        return [
            'model' => self::MODEL,
            'temperature' => 0.2,
            'max_tokens' => self::MAX_TOKENS,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                $this->buildSystemMessage($musicCategories, $sfx),
                $this->buildUserMessage($article),
            ],
        ];
    }

    /**
     * Construct the system prompt message
     *
     * @param string $musicCategories
     * @param string $sfx
     * @return array
     */
    private function buildSystemMessage(string $musicCategories, string $sfx): array
    {
        return [
            'role' => 'system',
            'content' => <<<PROMPT
- You are an AI assistant that generates short, vertical news-style video scripts in JSON format, optimized for social media (TikTok, Reels, Shorts). The output must be valid JSON only (no markdown or extra text).
- Format: JSON with keys "metadata", "hook", "scenes", and "background_music".
- "metadata": object including:
    - "title": concise video title (<=50 chars).
    - "description": SEO-friendly description of the article.
    - "hashtags": array of relevant hashtags (e.g. ["#news", "#topic"]).
- "hook": a catchy opening sentence to grab attention.
- "scenes": an array of exactly 4 scene objects. Each scene must include:
    - "headline": a brief, bold headline string summarizing the scene (engaging, emotive language).
    - "visual": Generate one continuous, richly detailed description string for an AI video generator—do not break it into sub-fields or arrays. Include shot type (e.g. “wide shot,” “close-up”), camera movement (e.g. “smooth gimbal pan,” “tracking shot”), lens choice, framing, pacing (e.g. “slow reveal,” “quick cut”), lighting, ambiance, color palette, key props or characters, and any transitions or motion-graphic cues. Specify timing or duration cues where relevant (e.g. “3-second reveal,” “1-second crossfade”) and tailor everything for a vertical (9:16) composition, focusing on posture, clothing, and motion without fine facial detail.
    - "voiceover":
        Narration text should feel energetic and natural, like a lively news host or influencer. Write in a conversational tone with dynamic phrasing and emotional highs.
        • Use occasional `<break time="200ms"/>` to keep the pacing natural (but don’t overdo it).
        • For emphasis, CAPITALIZE key words or wrap them in quotation marks—e.g. “This is AMAZING!”
        • You can also use ellipses or dashes for a more human feel: “Well… here we go.”
        • Avoid too many pauses or robotic-sounding patterns.
    - "transition": Select exactly one transition name from FFmpeg's xfade list. Use only the official transition name (case-sensitive, no extra words). Examples include: fade, wipeleft, slideright, circleopen, pixelize, radial. Return only the name.
    - "sound_effect": choose exactly one of the following SFX (and return only that word or phrase, with no extra text): $sfx to accompany the transition or start of the scene.
- "background_music": a short description of the overall music style or mood (choose one of the following $musicCategories).
- Tone and Style:
    - Dynamic and emotionally engaging (as in top viral news/educational shorts). Use active voice and vivid, concise language. Headlines and voiceover should feel urgent or exciting, creating curiosity.
    - Keep narration concise (aim for ~1-2 short sentences per scene, fitting the short-form format).
    - Maintain a coherent narrative or logical flow across scenes, building interest.
    - Ensure SSML sounds fluid and natural; avoid mechanical pacing. Voiceover should not have awkward long pauses.
    - Scenes should be visually bold and cinematic. Use strong imagery and lighting contrasts. Favor clear storytelling through visuals.
- Output Requirements:
    - Only output the JSON object with the structure above. Do not include explanations, notes, or code blocks.
    - Use double quotes for JSON keys and string values. Ensure the JSON is well-formed.
PROMPT
        ];
    }

    /**
     * Construct the user prompt message with article context
     *
     * @param Article $article
     * @return array
     */
    private function buildUserMessage(Article $article): array
    {
        $headLine = $article->title;
        $summary = $article->summary;
        return [
            "role" => "user",
            "content" => "Article: {$headLine}\n"
                . "Summary: {$summary}\n"
                . "Extract 4 key points for video scenes"
        ];
    }
}
