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

    'oauth' => [
        'google' => [
            'client_id' => Env::get('GOOGLE_CLIENT_ID', ''),
            'client_secret' => Env::get('GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => str_replace(' ', '%20', (string)Env::get('GOOGLE_REDIRECT_URI', 'http://localhost/Dreambox%20Nexus/auth/google/callback/')),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => ['openid', 'email', 'profile'],
            'mock_enabled' => (bool)Env::get('AUTH_MOCK_OAUTH', true),
        ],
        'github' => [
            'client_id' => Env::get('GITHUB_CLIENT_ID', ''),
            'client_secret' => Env::get('GITHUB_CLIENT_SECRET', ''),
            'redirect_uri' => str_replace(' ', '%20', (string)Env::get('GITHUB_REDIRECT_URI', 'http://localhost/Dreambox%20Nexus/auth/github/callback/')),
            'authorize_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'userinfo_url' => 'https://api.github.com/user',
            'emails_url' => 'https://api.github.com/user/emails',
            'scopes' => ['read:user', 'user:email'],
            'mock_enabled' => (bool)Env::get('AUTH_MOCK_OAUTH', true),
        ],
    ],
];

