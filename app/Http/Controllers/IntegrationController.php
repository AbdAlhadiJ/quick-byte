<?php

namespace App\Http\Controllers;

use App\Services\PlatformAuth\InstagramAuthService;
use App\Services\PlatformAuth\TikTokAuthService;
use App\Services\PlatformAuth\YouTubeAuthService;
use Exception;

class IntegrationController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(string $platform)
    {
        $available = ['tiktok', 'youtube', 'instagram'];

        if (!in_array($platform, $available)) {
            throw new Exception('Invalid platform: ' . $platform . '. Valid options: tiktok, youtube, instagram.');
        }

        switch ($platform) {
            case 'tiktok':
                $url = app(TikTokAuthService::class)->getAuthUrl();
                break;

            case 'youtube':
                $client = app(YouTubeAuthService::class)->createGoogleClient();
                $url = $client->createAuthUrl();
                break;

            case 'instagram':
                $url = app(InstagramAuthService::class)->getAuthUrl();
                break;
        }

        return response()->json(['authorize_url' => $url]);
    }
}
