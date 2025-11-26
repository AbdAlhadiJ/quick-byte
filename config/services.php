<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'pinecone' => [
        'api_key' => env('PINECONE_API_KEY', ''),
        'index_host' => env('PINECONE_INDEXHOST'),
    ],
    'scrapdo' => [
        'api_key' => env('SCRAPDO_API_KEY', ''),
        'base_url' => env('SCRAPDO_BASE_URL', 'https://api.scrape.do/'),
    ],

    'huggingface' => [
        'api_key' => env('HUGGINGFACE_API_KEY'),
        'base_uri' => 'https://api-inference.huggingface.co/',
        'model' => env('HUGGINGFACE_MODEL', 'facebook/bart-large-cnn'),
        'max_length' => env('HUGGINGFACE_MAX_LENGTH', 200),
        'min_length' => env('HUGGINGFACE_MIN_LENGTH', 150),
        'max_tokens' => '1024'
    ],

    'youtube' => [
        'credentials' => env('YOUTUBE_APPLICATION_CREDENTIALS'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
        'chunk_size' => 1048576, // 1MB

    ],

    'google_cloud' => [
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'project_id' => env('GCP_PROJECT_ID'),
        'location' => env('GCP_LOCATION'),
        'results_bucket' => env('GCP_RESULTS_BUCKET'),
        'model' => env('GCP_MODEL', 'veo-2.0-generate-001'),
    ],
    'tiktok' =>[
        'client_id' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect'      => env('TIKTOK_REDIRECT_URI'),
        'refresh_token' => env('TIKTOK_REFRESH_TOKEN'),
        'oauth_authorize_url' => env('OAUTH_AUTHORIZE_URL','https://www.tiktok.com/v2/auth/authorize/'),
        'oauth_token_url' => env('OAUTH_TOKEN_URL','https://open.tiktokapis.com/v2/oauth/token/'),
        'chunk_size' => 1048576,
    ],

    'instagram' => [
        'client_id'     => env('IG_APP_ID'),
        'client_secret' => env('IG_APP_SECRET'),
        'redirect'      => env('IG_REDIRECT_URI'),
        'access_token'  => env('IG_TOKEN', ''),
],

];
