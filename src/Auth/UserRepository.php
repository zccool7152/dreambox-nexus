<?php
declare(strict_types=1);

namespace Nexus\Auth;

use Nexus\Crypto\Aead;
use Nexus\Crypto\Hasher;
use Nexus\Database\Database;
use RuntimeException;

class UserRepository
{
    private ?Database $db;
    private Hasher $hasher;
    private ?Aead $crypto;

    public function __construct(?Database $db = null, ?Hasher $hasher = null, ?Aead $crypto = null)
    {
        $this->db = $db;
        $this->hasher = $hasher ?? new Hasher();
        $this->crypto = $crypto;
        $this->ensureDefaultAdmin();
    }

    public function isMongo(): bool
    {
        return $this->db !== null && $this->db->getDriver() === 'mongodb' && $this->db->isAlive();
    }

    public function findById(string $id): ?array
    {
        if ($this->isMongo()) {
            $user = $this->db->mongoFindOne('users', ['id' => $id]);
            return $user ? $this->normalizeUser($user) : null;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
            if ($user) {
                $user['roles'] = $this->getUserRolesFromSql($id);
                $user['groups'] = $this->getUserGroupsFromSql($id);
                return $this->normalizeUser($user);
            }
        }

        return $this->getFallbackMemoryUser($id);
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));

        if ($this->isMongo()) {
            $user = $this->db->mongoFindOne('users', ['email' => $email]);
            return $user ? $this->normalizeUser($user) : null;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
            if ($user) {
                $user['roles'] = $this->getUserRolesFromSql($user['id']);
                $user['groups'] = $this->getUserGroupsFromSql($user['id']);
                return $this->normalizeUser($user);
            }
        }

        return $this->getFallbackMemoryUserByEmail($email);
    }

    public function findByOAuth(string $provider, string $providerId): ?array
    {
        if ($this->isMongo()) {
            $user = $this->db->mongoFindOne('users', [
                'auth_provider' => $provider,
                'provider_id' => $providerId,
            ]);
            return $user ? $this->normalizeUser($user) : null;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE auth_provider = ? AND provider_id = ?",
                [$provider, $providerId]
            );
            if ($user) {
                $user['roles'] = $this->getUserRolesFromSql($user['id']);
                $user['groups'] = $this->getUserGroupsFromSql($user['id']);
                return $this->normalizeUser($user);
            }
        }

        return null;
    }

    public function all(): array
    {
        if ($this->isMongo()) {
            $docs = $this->db->mongoFind('users', [], ['sort' => ['created_at' => -1]]);
            return array_map([$this, 'normalizeUser'], $docs);
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $rows = $this->db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
            $users = [];
            foreach ($rows as $r) {
                $r['roles'] = $this->getUserRolesFromSql($r['id']);
                $r['groups'] = $this->getUserGroupsFromSql($r['id']);
                $users[] = $this->normalizeUser($r);
            }
            return $users;
        }

        return [$this->getFallbackMemoryUser('usr_admin_001')];
    }

    public function create(array $data): array
    {
        $id = $data['id'] ?? 'usr_' . bin2hex(random_bytes(8));
        $email = strtolower(trim($data['email']));
        $name = trim($data['name']);
        $provider = $data['auth_provider'] ?? 'email';
        $providerId = $data['provider_id'] ?? null;
        $status = $data['status'] ?? 'active';
        $roles = $data['roles'] ?? ['Member'];
        $groups = $data['groups'] ?? [];
        $is2fa = !empty($data['is_2fa_enabled']) ? 1 : 0;
        $mustChange = !empty($data['must_change_password']) ? 1 : 0;

        $passwordHash = null;
        if (!empty($data['password'])) {
            $passwordHash = $this->hasher->hash($data['password']);
        } elseif (!empty($data['password_hash'])) {
            $passwordHash = $data['password_hash'];
        }

        $totpSecret = $data['totp_secret'] ?? null;
        $recoveryCodes = $data['recovery_codes'] ?? [];

        if ($this->isMongo()) {
            $doc = [
                'id' => $id,
                'email' => $email,
                'name' => $name,
                'password_hash' => $passwordHash,
                'auth_provider' => $provider,
                'provider_id' => $providerId,
                'avatar_url' => $data['avatar_url'] ?? '',
                'status' => $status,
                'is_2fa_enabled' => $is2fa,
                'must_change_password' => $mustChange,
                'totp_secret' => $totpSecret,
                'recovery_codes' => $recoveryCodes,
                'roles' => $roles,
                'groups' => $groups,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->mongoInsert('users', $doc);
            return $this->normalizeUser($doc);
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $this->db->execute(
                "INSERT INTO users (id, email, name, password_hash, auth_provider, provider_id, avatar_url, status, is_2fa_enabled, must_change_password)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$id, $email, $name, $passwordHash, $provider, $providerId, $data['avatar_url'] ?? '', $status, $is2fa, $mustChange]
            );

            // Save MFA table record if secret exists
            if ($totpSecret !== null) {
                $this->db->execute(
                    "INSERT INTO user_mfa (user_id, totp_secret, recovery_codes, is_enabled) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE totp_secret = VALUES(totp_secret), recovery_codes = VALUES(recovery_codes), is_enabled = VALUES(is_enabled)",
                    [$id, $totpSecret, json_encode($recoveryCodes), $is2fa]
                );
            }

            // Sync roles
            foreach ($roles as $rName) {
                $roleRow = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$rName]);
                if ($roleRow) {
                    $this->db->execute("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$id, $roleRow['id']]);
                }
            }

            return $this->findById($id);
        }

        // Memory fallback
        $doc = [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'password_hash' => $passwordHash,
            'auth_provider' => $provider,
            'provider_id' => $providerId,
            'avatar_url' => $data['avatar_url'] ?? '',
            'status' => $status,
            'is_2fa_enabled' => $is2fa,
            'totp_secret' => $totpSecret,
            'recovery_codes' => $recoveryCodes,
            'roles' => $roles,
            'groups' => $groups,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->normalizeUser($doc);
    }

    public function update(string $id, array $fields): bool
    {
        $fields['updated_at'] = date('Y-m-d H:i:s');

        if ($this->isMongo()) {
            $updateSet = [];
            foreach ($fields as $k => $v) {
                $updateSet[$k] = $v;
            }
            $mod = $this->db->mongoUpdate('users', ['id' => $id], ['$set' => $updateSet]);
            return $mod > 0;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $allowed = ['name', 'status', 'is_2fa_enabled', 'password_hash', 'avatar_url', 'must_change_password'];
            $sets = [];
            $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $fields)) {
                    $sets[] = "{$f} = ?";
                    $params[] = $fields[$f];
                }
            }
            if (!empty($sets)) {
                $params[] = $id;
                $this->db->execute("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?", $params);
            }

            if (isset($fields['roles']) && is_array($fields['roles'])) {
                $this->db->execute("DELETE FROM user_roles WHERE user_id = ?", [$id]);
                foreach ($fields['roles'] as $rName) {
                    $roleRow = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$rName]);
                    if ($roleRow) {
                        $this->db->execute("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)", [$id, $roleRow['id']]);
                    }
                }
            }

            return true;
        }

        return true;
    }

    public function delete(string $id): bool
    {
        if ($this->isMongo()) {
            return $this->db->mongoDelete('users', ['id' => $id]) > 0;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $this->db->execute("DELETE FROM user_roles WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM user_groups WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM user_mfa WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            return true;
        }

        return true;
    }

    private function normalizeUser(array $u): array
    {
        return [
            'id' => (string)($u['id'] ?? $u['_id'] ?? ''),
            'email' => (string)($u['email'] ?? ''),
            'name' => (string)($u['name'] ?? ''),
            'password_hash' => $u['password_hash'] ?? null,
            'auth_provider' => (string)($u['auth_provider'] ?? 'email'),
            'provider_id' => $u['provider_id'] ?? null,
            'avatar_url' => (string)($u['avatar_url'] ?? ''),
            'status' => (string)($u['status'] ?? 'active'),
            'is_2fa_enabled' => !empty($u['is_2fa_enabled']),
            'must_change_password' => !empty($u['must_change_password']),
            'totp_secret' => $u['totp_secret'] ?? null,
            'recovery_codes' => is_array($u['recovery_codes'] ?? null) ? $u['recovery_codes'] : (json_decode($u['recovery_codes'] ?? '[]', true) ?: []),
            'roles' => is_array($u['roles'] ?? null) ? $u['roles'] : ['Member'],
            'groups' => is_array($u['groups'] ?? null) ? $u['groups'] : [],
            'created_at' => (string)($u['created_at'] ?? date('Y-m-d H:i:s')),
        ];
    }

    private function getUserRolesFromSql(string $userId): array
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return ['Member'];
        }
        $rows = $this->db->fetchAll(
            "SELECT r.name FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?",
            [$userId]
        );
        return array_column($rows, 'name') ?: ['Member'];
    }

    private function getUserGroupsFromSql(string $userId): array
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return [];
        }
        $rows = $this->db->fetchAll(
            "SELECT g.name FROM groups g INNER JOIN user_groups ug ON g.id = ug.group_id WHERE ug.user_id = ?",
            [$userId]
        );
        return array_column($rows, 'name');
    }

    private function ensureDefaultAdmin(): void
    {
        $timEmail = 'admin@nexus.local';
        $tim = $this->findByEmail($timEmail);
        if ($tim === null) {
            $this->create([
                'id' => 'usr_admin_tim',
                'email' => $timEmail,
                'name' => 'Timothy Dunn',
                'password' => '123qweasd',
                'auth_provider' => 'email',
                'status' => 'active',
                'roles' => ['SuperAdmin'],
                'groups' => ['SecOps'],
                'is_2fa_enabled' => false,
                'must_change_password' => true,
            ]);
        } else {
            // Ensure password is reset to 123qweasd and must_change_password is active
            $this->update($tim['id'], [
                'password_hash' => $this->hasher->hash('123qweasd'),
                'must_change_password' => 1,
                'roles' => ['SuperAdmin'],
            ]);
        }

        // Also ensure fallback admin@localhost exists for headless tests
        $adminEmail = 'admin@localhost';
        if ($this->findByEmail($adminEmail) === null) {
            $this->create([
                'id' => 'usr_admin_001',
                'email' => $adminEmail,
                'name' => 'Nexus SuperAdmin',
                'password' => 'NexusAdmin@2026!',
                'auth_provider' => 'email',
                'status' => 'active',
                'roles' => ['SuperAdmin'],
                'groups' => ['SecOps'],
                'is_2fa_enabled' => false,
                'must_change_password' => false,
            ]);
        }
    }

    private function getFallbackMemoryUser(string $id): ?array
    {
        if ($id === 'usr_admin_tim' || $id === 'usr_admin_001') {
            return [
                'id' => 'usr_admin_tim',
                'email' => 'admin@nexus.local',
                'name' => 'Timothy Dunn',
                'password_hash' => $this->hasher->hash('123qweasd'),
                'auth_provider' => 'email',
                'provider_id' => null,
                'avatar_url' => '',
                'status' => 'active',
                'is_2fa_enabled' => false,
                'must_change_password' => true,
                'totp_secret' => null,
                'recovery_codes' => [],
                'roles' => ['SuperAdmin'],
                'groups' => ['SecOps'],
                'created_at' => '2026-11-09 00:00:00',
            ];
        }
        return null;
    }

    private function getFallbackMemoryUserByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === 'admin@nexus.local') {
            return $this->getFallbackMemoryUser('usr_admin_tim');
        }
        if ($email === 'admin@localhost') {
            return $this->getFallbackMemoryUser('usr_admin_001');
        }
        return null;
    }
}
