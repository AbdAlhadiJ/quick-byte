<?php

use App\Services\News\Adapters\NewsApiAdapter;

return [
    'newsapi' => [
        'driver' => NewsApiAdapter::class,
        'enabled' => env('NEWSAPI_ENABLED', false),
        'base_api' => env('NEWSAPI_BASE_API', 'https://newsapi.org/v2/top-headlines'),
        'key' => env('NEWSAPI_API_KEY'),
        'limit' => env('NEWSAPI_LIMIT', 25),
        'category' => 'technology',
    ],
    'gnews' => [
        'driver' => \App\Services\News\Adapters\GNewsAdapter::class,
        'enabled' => env('GNEWS_ENABLED', false),
        'base_api' => env('GNEWS_BASE_API', 'https://gnews.io/api/v4/top-headlines'),
        'key' => env('GNEWS_API_KEY'),
        'limit' => env('GNEWS_LIMIT', 25),
        'category' => 'technology',
    ],
];
