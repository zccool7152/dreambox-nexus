<?php
declare(strict_types=1);

namespace Nexus\Auth\OAuth;

use InvalidArgumentException;
use Nexus\Security\Session;

class OAuthManager
{
    private array $providers = [];
    private Session $session;

    public function __construct(array $config, Session $session)
    {
        $this->session = $session;
        if (isset($config['google'])) {
            $this->providers['google'] = new GoogleProvider($config['google']);
        }
        if (isset($config['github'])) {
            $this->providers['github'] = new GitHubProvider($config['github']);
        }
    }

    public function getProvider(string $name): OAuthProviderInterface
    {
        $name = strtolower($name);
        if (!isset($this->providers[$name])) {
            throw new InvalidArgumentException("Unsupported OAuth provider: '{$name}'.");
        }
        return $this->providers[$name];
    }

    public function generateState(): string
    {
        $this->session->start();
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);
        return $state;
    }

    public function validateState(?string $state): bool
    {
        $this->session->start();
        $saved = $this->session->get('oauth_state');
        if (empty($saved) || empty($state)) {
            return false;
        }
        $valid = hash_equals($saved, $state);
        $this->session->remove('oauth_state');
        return $valid;
    }
}
