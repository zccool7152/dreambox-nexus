<?php
declare(strict_types=1);

namespace Nexus\Auth\OAuth;

use Nexus\Core\HttpClient;
use RuntimeException;

class GitHubProvider implements OAuthProviderInterface
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
        return 'github';
    }

    public function isMock(): bool
    {
        return empty($this->config['client_id']) || !empty($this->config['mock_enabled']);
    }

    public function getAuthorizationUrl(string $state): string
    {
        if ($this->isMock()) {
            return $this->config['redirect_uri'] . '?code=mock_github_code&state=' . rawurlencode($state);
        }

        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $this->config['scopes'] ?? ['read:user', 'user:email']),
            'state' => $state,
            'allow_signup' => 'true',
        ];

        return ($this->config['authorize_url'] ?? 'https://github.com/login/oauth/authorize') . '?' . http_build_query($params);
    }

    public function handleCallback(string $code): array
    {
        if ($this->isMock() && ($code === 'mock_github_code' || empty($this->config['client_id']))) {
            return $this->getMockUser();
        }

        // 1. Exchange code for access token
        $tokenRes = $this->http->post(
            $this->config['token_url'] ?? 'https://github.com/login/oauth/access_token',
            [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'code' => $code,
                'redirect_uri' => $this->config['redirect_uri'],
            ],
            [
                'Accept: application/json',
                'User-Agent: Dreambox-Nexus-Core-OAuth-Client',
            ]
        );

        if ($tokenRes['status'] !== 200) {
            throw new RuntimeException("GitHub OAuth token exchange failed with status {$tokenRes['status']}.");
        }

        $tokenData = json_decode($tokenRes['body'], true);
        $accessToken = $tokenData['access_token'] ?? null;
        if (empty($accessToken)) {
            throw new RuntimeException("GitHub OAuth response missing access_token.");
        }

        // 2. Fetch User Profile
        $userRes = $this->http->get(
            $this->config['userinfo_url'] ?? 'https://api.github.com/user',
            [
                "Authorization: Bearer {$accessToken}",
                'User-Agent: Dreambox-Nexus-Core-OAuth-Client',
                'Accept: application/vnd.github.v3+json',
            ]
        );

        if ($userRes['status'] !== 200) {
            throw new RuntimeException("GitHub user profile lookup failed with status {$userRes['status']}.");
        }

        $userData = json_decode($userRes['body'], true);
        $email = $userData['email'] ?? null;

        // If email is private on GitHub profile, fetch from /user/emails
        if (empty($email)) {
            $emailRes = $this->http->get(
                $this->config['emails_url'] ?? 'https://api.github.com/user/emails',
                [
                    "Authorization: Bearer {$accessToken}",
                    'User-Agent: Dreambox-Nexus-Core-OAuth-Client',
                    'Accept: application/vnd.github.v3+json',
                ]
            );

            if ($emailRes['status'] === 200) {
                $emails = json_decode($emailRes['body'], true);
                if (is_array($emails)) {
                    foreach ($emails as $item) {
                        if (!empty($item['primary']) && !empty($item['verified'])) {
                            $email = $item['email'];
                            break;
                        }
                    }
                }
            }
        }

        return [
            'provider' => 'github',
            'provider_id' => (string)($userData['id'] ?? ''),
            'email' => strtolower(trim((string)($email ?? ''))),
            'name' => (string)($userData['name'] ?? $userData['login'] ?? 'GitHub User'),
            'avatar_url' => (string)($userData['avatar_url'] ?? ''),
        ];
    }

    public function getMockUser(): array
    {
        return [
            'provider' => 'github',
            'provider_id' => 'mock_gh_id_270040223',
            'email' => '270040223+zccool7152@users.noreply.github.com',
            'name' => 'zccool7152 (GitHub)',
            'avatar_url' => 'https://avatars.githubusercontent.com/u/270040223?v=4',
        ];
    }
}

