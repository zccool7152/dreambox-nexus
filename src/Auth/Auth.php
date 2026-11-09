<?php
declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Core\Request;
use Nexus\Crypto\Hasher;
use Nexus\Database\Database;
use Nexus\Security\Session;

/**
 * Dual-Guard Authentication Manager
 */
class Auth
{
    private Session $session;
    private Hasher $hasher;
    private ?Database $db;
    private ?array $currentUser = null;

    public function __construct(Session $session, Hasher $hasher, ?Database $db = null)
    {
        $this->session = $session;
        $this->hasher = $hasher;
        $this->db = $db;
    }

    public function check(Request $request): bool
    {
        return $this->user($request) !== null;
    }

    public function user(Request $request): ?array
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        // 1. Check Bearer Token Guard (Stateless API)
        $bearer = $request->getBearerToken();
        if ($bearer !== null && $this->db !== null && $this->db->isAlive()) {
            $tokenHash = hash('sha256', $bearer);
            $tokenRow = $this->db->fetch(
                "SELECT user_id FROM cache_store WHERE cache_key = ? AND expiration > ?",
                ['token:' . $tokenHash, time()]
            );

            if ($tokenRow) {
                $this->currentUser = [
                    'id' => $tokenRow['user_id'],
                    'guard' => 'bearer',
                ];
                return $this->currentUser;
            }
        }

        // 2. Check Stateful Session Guard
        $this->session->start();
        $userId = $this->session->get('user_id');

        if ($userId !== null) {
            $this->currentUser = [
                'id' => $userId,
                'email' => $this->session->get('user_email', ''),
                'name' => $this->session->get('user_name', 'User'),
                'roles' => $this->session->get('user_roles', ['Member']),
                'avatar_url' => $this->session->get('user_avatar', ''),
                'auth_provider' => $this->session->get('auth_provider', 'email'),
                'guard' => 'session',
            ];
            return $this->currentUser;
        }

        return null;
    }

    public function loginWithSession(string $userId, string $email, string $name = 'User', array $roles = ['Member'], string $avatar = '', string $provider = 'email'): void
    {
        $this->session->start();
        $this->session->regenerate();
        $this->session->set('user_id', $userId);
        $this->session->set('user_email', $email);
        $this->session->set('user_name', $name);
        $this->session->set('user_roles', $roles);
        $this->session->set('user_avatar', $avatar);
        $this->session->set('auth_provider', $provider);
        $this->currentUser = [
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'roles' => $roles,
            'avatar_url' => $avatar,
            'auth_provider' => $provider,
            'guard' => 'session'
        ];
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->currentUser = null;
    }
}

