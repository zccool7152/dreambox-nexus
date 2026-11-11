<?php
declare(strict_types=1);

namespace Nexus\Auth\OAuth;

use Nexus\Core\HttpClient;
use RuntimeException;

class GoogleProvider implements OAuthProviderInterface
{
    private array $config;
    private HttpClient $http;

    public function __construct(array $config, ?HttpClient $http = null)
    {
        $this->config = $config;
        $this->http = $http ?? new HttpClient(10, 3, true);
    }

    public function getName(): string
    {
        return 'google';
    }

    public function isMock(): bool
    {
        return !empty($this->config['mock_enabled']);
    }

    public function getAuthorizationUrl(string $state): string
    {
        if ($this->isMock()) {
            return $this->config['redirect_uri'] . '?code=mock_google_code&state=' . rawurlencode($state);
        }

        if (empty($this->config['client_id'])) {
            throw new RuntimeException("Google OAuth Client ID is not configured in .env. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.");
        }

        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $this->config['scopes'] ?? ['openid', 'email', 'profile']),
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return ($this->config['authorize_url'] ?? 'https://accounts.google.com/o/oauth2/v2/auth') . '?' . http_build_query($params);
    }

    public function handleCallback(string $code): array
    {
        if ($this->isMock() && $code === 'mock_google_code') {
            return $this->getMockUser();
        }

        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            throw new RuntimeException("Google OAuth credentials missing in .env (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET).");
        }

        // 1. Exchange authorization code for access token
        $tokenRes = $this->http->post(
            $this->config['token_url'] ?? 'https://oauth2.googleapis.com/token',
            [
                'code' => $code,
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri'],
                'grant_type' => 'authorization_code',
            ],
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if ($tokenRes['status'] !== 200) {
            $errBody = json_decode($tokenRes['body'], true);
            $errDesc = $errBody['error_description'] ?? $errBody['error'] ?? $tokenRes['body'];
            throw new RuntimeException("Google OAuth token exchange failed with status {$tokenRes['status']}: {$errDesc}");
        }

        $tokenData = json_decode($tokenRes['body'], true);
        $accessToken = $tokenData['access_token'] ?? null;
        if (empty($accessToken)) {
            throw new RuntimeException("Google OAuth response missing access_token.");
        }

        // 2. Fetch User Profile
        $userRes = $this->http->get(
            $this->config['userinfo_url'] ?? 'https://openidconnect.googleapis.com/v1/userinfo',
            ["Authorization: Bearer {$accessToken}"]
        );

        if ($userRes['status'] !== 200) {
            throw new RuntimeException("Google user profile lookup failed with status {$userRes['status']}.");
        }

        $userData = json_decode($userRes['body'], true);

        return [
            'provider' => 'google',
            'provider_id' => (string)($userData['sub'] ?? ''),
            'email' => strtolower(trim((string)($userData['email'] ?? ''))),
            'name' => (string)($userData['name'] ?? 'Google User'),
            'avatar_url' => (string)($userData['picture'] ?? ''),
        ];
    }

    public function getMockUser(): array
    {
        return [
            'provider' => 'google',
            'provider_id' => 'mock_google_sub_' . substr(md5('admin@nexus.local'), 0, 12),
            'email' => 'admin@nexus.local',
            'name' => 'Timothy Dunn (Google)',
            'avatar_url' => 'https://lh3.googleusercontent.com/a/default-user',
        ];
    }
}

