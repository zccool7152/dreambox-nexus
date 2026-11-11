<?php
declare(strict_types=1);

use Nexus\Core\Env;

return [
    'defaults' => [
        'guard' => 'session',
        'provider' => 'users',
    ],

    'mfa' => [
        'enforced_for_email' => true,
        'issuer' => Env::get('APP_NAME', 'Dreambox Nexus'),
        'digits' => 6,
        'period' => 30,
        'discrepancy' => 1,
    ],

    'lockout' => [
        'max_attempts' => (int)Env::get('PCI_ACCOUNT_LOCKOUT_ATTEMPTS', 5),
        'decay_minutes' => (int)Env::get('PCI_ACCOUNT_LOCKOUT_MINUTES', 30),
    ],

    'oauth' => (function() {
        $buildRedirectUri = function (string $envKey, string $defaultCallback): string {
            $configured = trim((string)Env::get($envKey, ''));
            $appUrl = rtrim((string)Env::get('APP_URL', ''), '/');

            // 1. Expand ${APP_URL} placeholder if present in configuration
            if ($configured !== '') {
                if (str_contains($configured, '${APP_URL}')) {
                    $configured = str_replace('${APP_URL}', $appUrl !== '' ? $appUrl : 'http://localhost', $configured);
                }
                return str_replace(' ', '%20', $configured);
            }

            // 2. Derive dynamically from APP_URL if set
            if ($appUrl !== '') {
                $appBase = \Nexus\Core\Request::getAppBase();
                if ($appBase !== '' && !str_ends_with($appUrl, $appBase)) {
                    return $appUrl . str_replace(' ', '%20', $appBase) . $defaultCallback;
                }
                return $appUrl . $defaultCallback;
            }

            // 3. Dynamic scheme and host fallback from request context
            $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $appBase = \Nexus\Core\Request::getAppBase();

            return "{$scheme}://{$host}" . str_replace(' ', '%20', $appBase) . $defaultCallback;
        };

        return [
            'google' => [
                'client_id' => Env::get('GOOGLE_CLIENT_ID', ''),
                'client_secret' => Env::get('GOOGLE_CLIENT_SECRET', ''),
                'redirect_uri' => $buildRedirectUri('GOOGLE_REDIRECT_URI', '/auth/google/callback/'),
                'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'scopes' => ['openid', 'email', 'profile'],
                'mock_enabled' => (bool)Env::get('AUTH_MOCK_OAUTH', false),
            ],
            'github' => [
                'client_id' => Env::get('GITHUB_CLIENT_ID', ''),
                'client_secret' => Env::get('GITHUB_CLIENT_SECRET', ''),
                'redirect_uri' => $buildRedirectUri('GITHUB_REDIRECT_URI', '/auth/github/callback/'),
                'authorize_url' => 'https://github.com/login/oauth/authorize',
                'token_url' => 'https://github.com/login/oauth/access_token',
                'userinfo_url' => 'https://api.github.com/user',
                'emails_url' => 'https://api.github.com/user/emails',
                'scopes' => ['read:user', 'user:email'],
                'mock_enabled' => (bool)Env::get('AUTH_MOCK_OAUTH', false),
            ],
        ];
    })(),
];

