<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Services\PlatformAuth\InstagramAuthService;
use App\Services\PlatformAuth\TikTokAuthService;
use App\Services\PlatformAuth\YouTubeAuthService;
use Illuminate\Support\Facades\Redirect;

class OAuthCallbackController extends Controller
{
    protected $platforms = [
        'youtube' => ['service' => YouTubeAuthService::class, 'env_key' => 'YOUTUBE_REFRESH_TOKEN'],
        'tiktok' => ['service' => TikTokAuthService::class, 'env_key' => 'TIKTOK_REFRESH_TOKEN'],
        'instagram' => [ 'service' => InstagramAuthService::class, 'env_key' => 'INSTAGRAM_REFRESH_TOKEN' ],
    ];

    public function handle(string $platform)
    {
        if (!isset($this->platforms[$platform])) {
            abort(404);
        }

        $request = request();

        if ($code = $request->input('code')) {
            $cfg = $this->platforms[$platform];
            $service = app($cfg['service']);
            $token = $service->storeTokenCreds($code, $request->input('state'));
            if($token && isset($token['refresh_token'])) {
                Helper::setEnvValue($cfg['env_key'], $token['refresh_token']);
            }
        }

        return Redirect::route('admin.dashboard')
            ->with('status', ucfirst($platform) . ' OAuth completed');
    }

}
