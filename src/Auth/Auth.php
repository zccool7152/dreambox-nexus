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
            $tokenRow = null;
            try {
                if (!$this->db->isSql()) {
                    $tokenRow = $this->db->mongoFindOne('cache_store', [
                        'cache_key' => 'token:' . $tokenHash,
                        'expiration' => ['$gt' => time()]
                    ]);
                } else {
                    $tokenRow = $this->db->fetch(
                        "SELECT user_id FROM cache_store WHERE cache_key = ? AND expiration > ?",
                        ['token:' . $tokenHash, time()]
                    );
                }
            } catch (\Throwable) {
                $tokenRow = null;
            }

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
                'username' => $this->session->get('user_username', ''),
                'display_preference' => $this->session->get('user_display_preference', 'name'),
                'roles' => $this->session->get('user_roles', ['Member']),
                'groups' => $this->session->get('user_groups', []),
                'avatar_url' => $this->session->get('user_avatar', ''),
                'auth_provider' => $this->session->get('auth_provider', 'email'),
                'must_change_password' => !empty($this->session->get('must_change_password')),
                'guard' => 'session',
            ];
            return $this->currentUser;
        }

        return null;
    }

    public function loginWithSession(
        string $userId,
        string $email,
        string $name = 'User',
        array $roles = ['Member'],
        string $avatar = '',
        string $provider = 'email',
        bool $mustChangePassword = false,
        string $username = '',
        string $displayPreference = 'name',
        array $groups = []
    ): void {
        $this->session->start();
        $this->session->regenerate();
        $this->session->set('user_id', $userId);
        $this->session->set('user_email', $email);
        $this->session->set('user_name', $name);
        $this->session->set('user_roles', $roles);
        $this->session->set('user_avatar', $avatar);
        $this->session->set('auth_provider', $provider);
        $this->session->set('must_change_password', $mustChangePassword);
        $this->session->set('user_username', $username !== '' ? $username : (strstr($email, '@', true) ?: 'user'));
        $this->session->set('user_display_preference', in_array($displayPreference, ['name', 'username'], true) ? $displayPreference : 'name');
        $this->session->set('user_groups', $groups);

        $this->currentUser = [
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'username' => $this->session->get('user_username'),
            'display_preference' => $this->session->get('user_display_preference'),
            'roles' => $roles,
            'groups' => $groups,
            'avatar_url' => $avatar,
            'auth_provider' => $provider,
            'must_change_password' => $mustChangePassword,
            'guard' => 'session'
        ];
    }

    public function updateSessionUser(array $fields): void
    {
        $this->session->start();
        if (isset($fields['name'])) {
            $this->session->set('user_name', $fields['name']);
        }
        if (isset($fields['username'])) {
            $this->session->set('user_username', $fields['username']);
        }
        if (isset($fields['display_preference'])) {
            $this->session->set('user_display_preference', $fields['display_preference']);
        }
        if (isset($fields['avatar_url'])) {
            $this->session->set('user_avatar', $fields['avatar_url']);
        }
        if (isset($fields['roles'])) {
            $this->session->set('user_roles', $fields['roles']);
        }
        if (isset($fields['groups'])) {
            $this->session->set('user_groups', $fields['groups']);
        }

        $this->currentUser = null; // force reload from session on next check
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->currentUser = null;
        if (isset($_COOKIE[session_name()])) {
            unset($_COOKIE[session_name()]);
        }
    }
}

