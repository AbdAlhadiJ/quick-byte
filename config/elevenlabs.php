<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ElevenLabs API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for ElevenLabs API integration.
    | You can set your API key, base URL, and other optional parameters here.
    |
    */

    'api_key' => env('ELEVENLABS_API_KEY'),

    'api_endpoint' => env('ELEVENLABS_API_ENDPOINT', 'https://api.elevenlabs.io/v1/text-to-speech'),

    'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),

    'default_model_id' => env('ELEVENLABS_DEFAULT_MODEL_ID', 'eleven_monolingual_v1'),

    'default_language' => env('ELEVENLABS_DEFAULT_LANGUAGE', 'en'),

    'default_stability' => env('ELEVENLABS_DEFAULT_STABILITY', 0.35),

    'default_similarity_boost' => env('ELEVENLABS_DEFAULT_SIMILARITY_BOOST', 0.9),

    'default_style' => env('ELEVENLABS_DEFAULT_STYLE', 0.7),

    'default_speaker_boost' => env('ELEVENLABS_DEFAULT_SPEAKER_BOOST', true),

    'default_output_format' => env('ELEVENLABS_DEFAULT_OUTPUT_FORMAT', 'mp3_44100_128'),

    'timeout' => env('ELEVENLABS_TIMEOUT', 30),

    'headers' => [
        // 'X-Custom-Header' => 'value',
    ],

];
