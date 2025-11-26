<?php

namespace App\Services\PlatformAuth;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class InstagramAuthService
{
    private const CACHE_PREFIX = 'instagram.oauth.';
    private const CONFIG_PREFIX = 'services.instagram.';
    private const EXPIRY_BUFFER = 300;

    public function __construct(
        private readonly Config $config,
        private readonly Cache  $cache,
    )
    {
    }

    /**
     * Build the Instagram OAuth URL (Authorization Code Flow).
     */
    public function getAuthUrl(): string
    {
        $state = Str::random(40);
        $this->cache->put(self::CACHE_PREFIX . 'state', $state, 600);

        $params = http_build_query([
            'client_id' => $this->config->get(self::CONFIG_PREFIX . 'client_id'),
            'redirect_uri' => config('app.url') . '/oauth2/instagram/callback',
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement,business_management',
            'response_type' => 'code',
            'state' => $state,
        ]);

        return 'https://www.facebook.com/v23.0/dialog/oauth?' . $params;
    }

    /**
     * Handle the callback: exchange code for a short-lived token,
     * then swap that for a long-lived token.
     */
    public function storeTokenCreds(string $code, string $incomingState): array
    {
        // 1. Validate state
        $expected = $this->cache->pull(self::CACHE_PREFIX . 'state');
        if ($incomingState !== $expected) {
            throw new RuntimeException('Invalid OAuth state');
        }

        // 2. Exchange code for short-lived token
        $short = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
            'client_id' => $this->config->get(self::CONFIG_PREFIX . 'client_id'),
            'client_secret' => $this->config->get(self::CONFIG_PREFIX . 'client_secret'),
            'redirect_uri' => config('app.url') . '/oauth2/instagram/callback',
            'code' => $code,
        ])->json();

        if (!isset($short['access_token'])) {
            throw new RuntimeException('Failed to get short-lived token: ' . json_encode($short));
        }

        $long = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->config->get(self::CONFIG_PREFIX . 'client_id'),
            'client_secret' => $this->config->get(self::CONFIG_PREFIX . 'client_secret'),
            'fb_exchange_token' => $short['access_token'],
        ])->json();

        if (!isset($long['access_token'])) {
            throw new RuntimeException('Failed to get long-lived token: ' . json_encode($long));
        }

        $expiresIn = $long['expires_in'] ?? (60 * 24 * 3600);

        $data = [
            'access_token' => $long['access_token'],
            'expires_in' => $expiresIn,
            'expires_at'   => now()->addSeconds($expiresIn)->timestamp,
        ];

        // Persist and cache
        File::put(storage_path('app/instagram_tokens.json'), json_encode($data, JSON_PRETTY_PRINT));
        $this->cache->put(self::CACHE_PREFIX . 'token', $data, $data['expires_in'] - self::EXPIRY_BUFFER);

        // Optionally persist refreshable config
        $this->config->set(self::CONFIG_PREFIX . 'access_token', $data['access_token']);

        return $data;
    }

    /**
     * Return a valid token, refreshing if needed.
     */
    public function getAccessToken(): string
    {

        $cached = $this->cache->get(self::CACHE_PREFIX . 'token')
            ?? $this->getTokenFromFile();

        if (empty($cached['access_token'])) {
            throw new RuntimeException('No Instagram access token found');
        }

        if (now()->timestamp < ($cached['expires_at'] - self::EXPIRY_BUFFER)) {
            return $cached['access_token'];
        }

        return $this->refreshLongLivedToken();
    }

    /**
     * Refresh a long-lived token (can be done indefinitely).
     */
    private function refreshLongLivedToken(): string
    {
        $current = $this->config->get(self::CONFIG_PREFIX . 'access_token');
        if (!$current) {
            throw new RuntimeException('No Instagram access token configured');
        }

        $resp = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->config->get(self::CONFIG_PREFIX . 'client_id'),
            'client_secret' => $this->config->get(self::CONFIG_PREFIX . 'client_secret'),
            'fb_exchange_token' => $current,
        ])->json();

        if (!isset($resp['access_token'])) {
            throw new RuntimeException('Instagram token refresh failed: ' . json_encode($resp));
        }

        $data = [
            'access_token' => $resp['access_token'],
            'expires_in' => $resp['expires_in'] ?? 0,
            'expires_at' => now()->addSeconds($resp['expires_in'] ?? 0)->timestamp,
        ];

        // Cache & persist
        $this->cache->put(self::CACHE_PREFIX . 'token', $data, ($resp['expires_in'] ?? 0) - self::EXPIRY_BUFFER);
        File::put(storage_path('app/instagram_tokens.json'), json_encode($data, JSON_PRETTY_PRINT));
        $this->config->set(self::CONFIG_PREFIX . 'access_token', $data['access_token']);

        Log::info('Instagram long-lived token refreshed');

        return $data['access_token'];
    }

    /**
     * Read token data from file fallback.
     */
    private function getTokenFromFile(): ?array
    {
        $path = storage_path('app/instagram_tokens.json');
        if (!File::exists($path)) {
            return null;
        }

        try {
            return json_decode(File::get($path), true);
        } catch (\Throwable $e) {
            Log::warning('Failed to read Instagram token file', ['error' => $e->getMessage()]);
            return null;
        }
    }

}
