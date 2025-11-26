<?php

namespace App\Console\Commands;

use App\Services\PlatformAuth\TikTokAuthService;
use App\Services\PlatformAuth\YouTubeAuthService;
use App\Services\PlatformAuth\InstagramAuthService;
use Google\Exception;
use Illuminate\Console\Command;

class OAuthAuthorize extends Command
{
    /**
     * The name and signature of the console command.
     * Now requires a platform argument: tiktok, youtube, instagram, or all.
     *
     * @var string
     */
    protected $signature = 'oauth:authorize {platform : tiktok|youtube|instagram|all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Authorize one or more platform APIs and display their OAuth URLs. (platform is required)';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $platform = strtolower($this->argument('platform'));
        $available = ['tiktok', 'youtube', 'instagram', 'all'];

        if (! in_array($platform, $available)) {
            $this->error("Invalid platform: '{$platform}'. Valid options: tiktok, youtube, instagram, all.");
            return 1;
        }

        $toProcess = $platform === 'all'
            ? ['tiktok', 'youtube', 'instagram']
            : [$platform];

        foreach ($toProcess as $serviceName) {
            switch ($serviceName) {
                case 'tiktok':
                    $url = app(TikTokAuthService::class)->getAuthUrl();
                    $this->info("👉 TikTok Authorization URL:");
                    $this->line($url);
                    break;

                case 'youtube':
                    $client = app(YouTubeAuthService::class)->createGoogleClient();
                    $url = $client->createAuthUrl();
                    $this->info("👉 YouTube Authorization URL:");
                    $this->line($url);
                    break;

                case 'instagram':
                    $url = app(InstagramAuthService::class)->getAuthUrl();
                    $this->info("👉 Instagram Authorization URL:");
                    $this->line($url);
                    break;
            }

            $this->line(''); // spacing
        }

        return 0;
    }
}
