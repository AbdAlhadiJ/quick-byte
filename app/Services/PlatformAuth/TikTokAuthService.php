<?php

namespace App\Services\PlatformAuth;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TikTokAuthService
{
    private const CACHE_PREFIX = 'tiktok.oauth.';
    private const CONFIG_PREFIX = 'services.tiktok';
    private const EXPIRY_BUFFER = 300;

    public function __construct(
        private readonly Config $config,
        private readonly Cache  $cache,
        protected ?string $redirectUri = null
    )
    {
        $this->redirectUri = config('app.url') . '/oauth2/tiktok/callback';
    }

    /**
     * Generate the TikTok OAuth authorization URL.
     */
    public function getAuthUrl(): string
    {
        $codeVerifier = Str::random(64);

        // 2) Build the code_challenge: SHA-256 then URL-safe Base64 (no padding)
        $hash = hash('sha256', $codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $this->cache->put(self::CACHE_PREFIX . 'code_verifier', $codeVerifier, 600);

        $state = Str::random(40);
        $this->cache->put(self::CACHE_PREFIX . 'state', $state, 600);

        $params = http_build_query([
            'client_key' => $this->config->get(self::CONFIG_PREFIX . '.client_id'),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'video.upload,video.publish,user.info.basic',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return $this->config->get(self::CONFIG_PREFIX . '.oauth_authorize_url') . '?' . $params;
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     */
    public function storeTokenCreds(string $code): array
    {
        $state = request('state');
        if ($state !== $this->cache->get(self::CACHE_PREFIX . 'state')) {
            throw new RuntimeException('Invalid OAuth state');
        }

        $response = Http::asForm()->post($this->config->get(self::CONFIG_PREFIX . '.oauth_token_url'), [
            'client_key' => $this->config->get(self::CONFIG_PREFIX . '.client_id'),
            'client_secret' => $this->config->get(self::CONFIG_PREFIX . '.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code_verifier'  => $this->cache->get(self::CACHE_PREFIX . 'code_verifier'),

        ]);

        if ($response->json('error')) {
            throw new RuntimeException('TikTok token exchange failed: ' . $response->json('error_description', 'Unknown error'));
        }

        $data = $response->json();

        File::put(storage_path('app/tiktok_tokens.json'), json_encode($data, JSON_PRETTY_PRINT));

        $this->updateTokenPair($data);
        return $data;
    }

    /**
     * Get valid token pair, using cache or refresh.
     */
    public function getTokenPair(): array
    {
        $cached = $this->getValidCachedToken();
        return $cached ?: $this->refreshTokenPair();
    }

    /**
     * Refresh the access token using the refresh token.
     */
    public function refreshTokenPair(): array
    {
        $token = $this->config->get(self::CONFIG_PREFIX . '.refresh_token');

        if (!$token) {
            throw new RuntimeException('Missing TikTok refresh token');
        }

        $response = Http::asForm()->post($this->config->get(self::CONFIG_PREFIX . '.oauth_token_url'), [
            'client_key' => $this->config->get(self::CONFIG_PREFIX . '.client_id'),
            'client_secret' => $this->config->get(self::CONFIG_PREFIX . '.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $token,
        ]);

        if ($response->json('error')) {
            throw new RuntimeException('TikTok token exchange failed: ' . $response->json('error_description', 'Unknown error'));
        }

        $data = $response->json();
        $this->updateTokenPair($data);
        return $data;
    }

    /**
     * Persist new token pair to cache and config.
     */
    private function updateTokenPair(array $data): void
    {
        $expiresIn = $data['expires_in'] ?? 0;
        $data['expires_at'] = now()->addSeconds($expiresIn)->timestamp;

        if (!empty($data['refresh_token'])) {
            $this->persistRefreshToken($data['refresh_token']);
        }

        $this->cache->put(self::CACHE_PREFIX . 'token', $data, $expiresIn - self::EXPIRY_BUFFER);
    }

    /**
     * Retrieve a valid cached token if available.
     */
    private function getValidCachedToken(): ?array
    {
        $token = $this->cache->get(self::CACHE_PREFIX . 'token');
        if ($token && now()->timestamp < ($token['expires_at'] - self::EXPIRY_BUFFER)) {
            return $token;
        }
        return null;
    }

    /**
     * Persist refresh token to configuration
     */
    private function persistRefreshToken(string $refreshToken): void
    {
        if ($refreshToken !== $this->config->get(self::CONFIG_PREFIX . '.refresh_token')) {
            $this->config->set(
                self::CONFIG_PREFIX . '.refresh_token',
                $refreshToken
            );
            Log::info('Toktik refresh token updated');
        }
    }
}
