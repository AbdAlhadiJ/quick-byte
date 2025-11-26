<?php

namespace App\Services\PlatformAuth;

use Google\Auth\Credentials\ServiceAccountCredentials;
use RuntimeException;

class GcpAuthService
{
    private const CONFIG_PATH = 'services.google_cloud.';
    private const TOKEN_EXPIRY_BUFFER = 60;

    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function getGoogleAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $credentialsPath = config(self::CONFIG_PATH . 'credentials');
        $this->validateCredentialsFile($credentialsPath);

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/cloud-platform'],
            $credentialsPath
        );

        $token = $credentials->fetchAuthToken();

        $this->handleTokenResponse($token);

        $this->accessToken = $token['access_token'];
        $this->tokenExpiry = time() + ($token['expires_in'] ?? 3600) - self::TOKEN_EXPIRY_BUFFER;

        return $this->accessToken;

    }

    private function validateCredentialsFile(?string $path): void
    {
        if (!$path || !file_exists($path)) {
            throw new RuntimeException(
                'Google Cloud credentials file not found. Check GOOGLE_APPLICATION_CREDENTIALS configuration.'
            );
        }
    }

    private function handleTokenResponse(array $token): void
    {
        if (empty($token['access_token'])) {
            throw new RuntimeException('Failed to retrieve Google Cloud access token');
        }
    }

}
