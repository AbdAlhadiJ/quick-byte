<?php

namespace App\Services\PlatformAuth;

use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\YouTube;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

class YouTubeAuthService
{
    private const CONFIG_PREFIX = 'services.youtube';
    private const TOKEN_CACHE_KEY = 'youtube.oauth.token';
    private const EXPIRY_BUFFER = 60; // seconds

    public function __construct(
        private readonly Config $config,
        private readonly Cache  $cache,
    )
    {
    }

    /**
     * Get valid OAuth token pair
     *
     * @return array{
     *     access_token: string,
     *     refresh_token?: string,
     *     expires_at: int,
     *     expires_in: int
     * }
     *
     * @throws RuntimeException|GoogleException|InvalidArgumentException
     */
    public function getTokenPair(): array
    {
        if ($tokenPair = $this->getValidCachedToken()) {
            return $tokenPair;
        }

        return $this->refreshTokenPair();
    }

    /**
     * Update and persist token pair
     *
     * @param array $tokenPair
     * @return void
     * @throws InvalidArgumentException
     */
    public function updateTokenPair(array $tokenPair): void
    {
        $this->validateTokenStructure($tokenPair);

        if (!isset($tokenPair['expires_at'])) {
            $tokenPair['expires_at'] = time() + ($tokenPair['expires_in'] ?? 3600);
        }

        if (!empty($tokenPair['refresh_token'])) {
            $this->persistRefreshToken($tokenPair['refresh_token']);
        }

        $this->cache->set(
            self::TOKEN_CACHE_KEY,
            $tokenPair,
            $tokenPair['expires_in'] ?? 3600
        );
    }

    /**
     * Refresh and store new token pair
     *
     * @return array
     * @throws GoogleException
     * @throws InvalidArgumentException
     */
    public function refreshTokenPair(): array
    {
        $client = $this->createGoogleClient();
        $refreshToken = $this->getStoredRefreshToken();

        $client->fetchAccessTokenWithRefreshToken($refreshToken);
        $tokenData = $client->getAccessToken();

        if (empty($tokenData['access_token'])) {
            throw new RuntimeException('Failed to fetch YouTube access token');
        }

        $this->updateTokenPair($tokenData);
        return $tokenData;
    }

    /**
     * Create configured Google Client
     * @throws GoogleException
     */
    public function createGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setAuthConfig($this->getCredentials());
        $client->addScope(YouTube::YOUTUBE_UPLOAD);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    /**
     * Get valid cached token if available
     *
     * @return array|null
     * @throws InvalidArgumentException
     */
    private function getValidCachedToken(): ?array
    {
        $tokenPair = $this->cache->get(self::TOKEN_CACHE_KEY);

        return $tokenPair && time() < ($tokenPair['expires_at'] - self::EXPIRY_BUFFER)
            ? $tokenPair
            : null;
    }

    /**
     * Get stored refresh token
     */
    private function getStoredRefreshToken(): string
    {
        $token = $this->config->get(self::CONFIG_PREFIX . '.refresh_token');

        if (!$token) {
            throw new RuntimeException('Missing YouTube refresh token');
        }

        return $token;
    }

    /**
     * Get service credentials
     */
    private function getCredentials(): string
    {
        $credentials = $this->config->get(self::CONFIG_PREFIX . '.credentials');

        if (empty($credentials)) {
            throw new RuntimeException('YouTube API credentials not configured');
        }

        return $credentials;
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
            Log::info('YouTube refresh token updated');
        }
    }

    /**
     * Validate token structure
     *
     * @throws RuntimeException
     */
    private function validateTokenStructure(array $token): void
    {
        $required = ['access_token', 'expires_in'];

        foreach ($required as $key) {
            if (!array_key_exists($key, $token)) {
                throw new RuntimeException("Invalid token structure: Missing $key");
            }
        }
    }

    /**
     * Clear cached tokens
     *
     * @throws InvalidArgumentException
     */
    public function clearTokenCache(): void
    {
        $this->cache->delete(self::TOKEN_CACHE_KEY);
    }

    /**
     * Store token credentials from authorization code
     *
     * @param string $code Authorization code received from Google OAuth
     * @throws GoogleException
     */
    public function storeTokenCreds(string $code): array
    {
        $token = $this->createGoogleClient()
            ->fetchAccessTokenWithAuthCode($code);

        File::put(storage_path('app/yt_tokens.json'), json_encode($token, JSON_PRETTY_PRINT));

        return $token;
    }
}
