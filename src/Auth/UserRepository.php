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
    private static array $memoryStore = [];

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
            if ($user === null && strlen($id) === 24 && ctype_xdigit($id)) {
                $oidClass = '\\MongoDB\\BSON\\ObjectId';
                $user = $this->db->mongoFindOne('users', ['_id' => new $oidClass($id)]);
            }
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

        if (isset(self::$memoryStore[$id])) {
            return $this->normalizeUser(self::$memoryStore[$id]);
        }

        $fallback = $this->getFallbackMemoryUser($id);
        return $fallback ? $this->normalizeUser($fallback) : null;
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

        foreach (self::$memoryStore as $mUser) {
            if (strtolower($mUser['email'] ?? '') === $email) {
                return $this->normalizeUser($mUser);
            }
        }

        $fallback = $this->getFallbackMemoryUserByEmail($email);
        return $fallback ? $this->normalizeUser($fallback) : null;
    }

    public function findByUsername(string $username): ?array
    {
        $username = trim($username);

        if ($this->isMongo()) {
            $user = $this->db->mongoFindOne('users', ['username' => $username]);
            return $user ? $this->normalizeUser($user) : null;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $user = $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);
            if ($user) {
                $user['roles'] = $this->getUserRolesFromSql($user['id']);
                $user['groups'] = $this->getUserGroupsFromSql($user['id']);
                return $this->normalizeUser($user);
            }
        }

        foreach (self::$memoryStore as $mUser) {
            if (($mUser['username'] ?? '') === $username) {
                return $this->normalizeUser($mUser);
            }
        }

        if (strpos($username, '@') === false && ($username === 'nexus_admin' || $username === 'admin')) {
            $fallback = $this->getFallbackMemoryUser('usr_admin_tim');
            return $fallback ? $this->normalizeUser($fallback) : null;
        }

        return null;
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

        $users = ['usr_admin_tim' => $this->normalizeUser($this->getFallbackMemoryUser('usr_admin_tim'))];
        foreach (self::$memoryStore as $mId => $mUser) {
            $users[$mId] = $this->normalizeUser($mUser);
        }
        return array_values($users);
    }

    public function create(array $data): array
    {
        $id = $data['id'] ?? 'usr_' . bin2hex(random_bytes(8));
        $email = strtolower(trim($data['email']));
        $name = trim($data['name']);
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            $username = strstr($email, '@', true) ?: $id;
        }
        $rawPref = $data['display_preference'] ?? 'name';
        $displayPreference = in_array($rawPref, ['name', 'username'], true) ? $rawPref : 'name';
        $provider = $data['auth_provider'] ?? 'email';
        $providerId = $data['provider_id'] ?? null;
        $status = $data['status'] ?? 'active';
        $roles = $data['roles'] ?? ['Member'];
        $groups = $data['groups'] ?? [];
        $is2fa = !empty($data['is_2fa_enabled']) ? 1 : 0;
        $mustChange = !empty($data['must_change_password']) ? 1 : 0;
        $defaultMail = in_array('SuperAdmin', $roles, true) || in_array('SecurityOfficer', $roles, true);
        $mailAccess = array_key_exists('mail_access', $data) ? (!empty($data['mail_access']) ? 1 : 0) : ($defaultMail ? 1 : 0);
        $mailboxAddress = isset($data['mailbox_address']) && trim((string)$data['mailbox_address']) !== ''
            ? strtolower(trim((string)$data['mailbox_address']))
            : null;

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
                'username' => $username,
                'display_preference' => $displayPreference,
                'password_hash' => $passwordHash,
                'auth_provider' => $provider,
                'provider_id' => $providerId,
                'avatar_url' => $data['avatar_url'] ?? '',
                'status' => $status,
                'mail_access' => $mailAccess,
                'mailbox_address' => $mailboxAddress,
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
            $this->writeMailOverlay($id, ['mail_access' => (bool)$mailAccess, 'mailbox_address' => $mailboxAddress]);
            return $this->normalizeUser($doc);
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $this->db->execute(
                "INSERT INTO users (id, email, name, username, display_preference, password_hash, auth_provider, provider_id, avatar_url, status, is_2fa_enabled, must_change_password)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$id, $email, $name, $username, $displayPreference, $passwordHash, $provider, $providerId, $data['avatar_url'] ?? '', $status, $is2fa, $mustChange]
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

            try {
                $this->db->execute("UPDATE users SET mail_access = ?, mailbox_address = ? WHERE id = ?", [$mailAccess, $mailboxAddress, $id]);
            } catch (\Throwable) {}
            $this->writeMailOverlay($id, ['mail_access' => (bool)$mailAccess, 'mailbox_address' => $mailboxAddress]);

            return $this->findById($id);
        }

        // Memory fallback
        $doc = [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'username' => $username,
            'display_preference' => $displayPreference,
            'password_hash' => $passwordHash,
            'auth_provider' => $provider,
            'provider_id' => $providerId,
            'avatar_url' => $data['avatar_url'] ?? '',
            'status' => $status,
            'mail_access' => $mailAccess,
            'mailbox_address' => $mailboxAddress,
            'is_2fa_enabled' => $is2fa,
            'must_change_password' => $mustChange,
            'totp_secret' => $totpSecret,
            'recovery_codes' => $recoveryCodes,
            'roles' => $roles,
            'groups' => $groups,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        self::$memoryStore[$id] = $doc;
        $this->writeMailOverlay($id, ['mail_access' => (bool)$mailAccess, 'mailbox_address' => $mailboxAddress]);
        return $this->normalizeUser($doc);
    }

    public function update(string $id, array $fields): bool
    {
        $fields['updated_at'] = date('Y-m-d H:i:s');

        $overlayPatch = [];
        if (array_key_exists('mail_access', $fields)) {
            $overlayPatch['mail_access'] = !empty($fields['mail_access']);
        }
        if (array_key_exists('mailbox_address', $fields)) {
            $overlayPatch['mailbox_address'] = $fields['mailbox_address'];
        }
        if (!empty($overlayPatch)) {
            $this->writeMailOverlay($id, $overlayPatch);
        }

        if ($this->isMongo()) {
            $updateSet = [];
            foreach ($fields as $k => $v) {
                $updateSet[$k] = $k === 'mail_access' ? (!empty($v) ? 1 : 0) : $v;
            }
            $mod = $this->db->mongoUpdate('users', ['id' => $id], ['$set' => $updateSet]);
            if ($mod === 0 && strlen($id) === 24 && ctype_xdigit($id)) {
                $oidClass = '\\MongoDB\\BSON\\ObjectId';
                $mod = $this->db->mongoUpdate('users', ['_id' => new $oidClass($id)], ['$set' => $updateSet]);
            }
            return true;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $allowed = ['name', 'status', 'is_2fa_enabled', 'password_hash', 'avatar_url', 'must_change_password', 'username', 'display_preference'];
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

            if (!empty($overlayPatch)) {
                try {
                    $mSets = [];
                    $mParams = [];
                    if (array_key_exists('mail_access', $fields)) {
                        $mSets[] = 'mail_access = ?';
                        $mParams[] = !empty($fields['mail_access']) ? 1 : 0;
                    }
                    if (array_key_exists('mailbox_address', $fields)) {
                        $mSets[] = 'mailbox_address = ?';
                        $mParams[] = $fields['mailbox_address'];
                    }
                    if (!empty($mSets)) {
                        $mParams[] = $id;
                        $this->db->execute("UPDATE users SET " . implode(', ', $mSets) . " WHERE id = ?", $mParams);
                    }
                } catch (\Throwable) {}
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

        if (isset(self::$memoryStore[$id])) {
            foreach ($fields as $k => $v) {
                self::$memoryStore[$id][$k] = $v;
            }
            self::$memoryStore[$id]['updated_at'] = date('Y-m-d H:i:s');
        }

        return true;
    }

    public function delete(string $id): bool
    {
        $this->deleteMailOverlay($id);

        if ($this->isMongo()) {
            $deleted = $this->db->mongoDelete('users', ['id' => $id]);
            if ($deleted === 0 && strlen($id) === 24 && ctype_xdigit($id)) {
                $oidClass = '\\MongoDB\\BSON\\ObjectId';
                $deleted = $this->db->mongoDelete('users', ['_id' => new $oidClass($id)]);
            }
            if ($deleted === 0) {
                $deleted = $this->db->mongoDelete('users', ['_id' => $id]);
            }
            return $deleted > 0;
        }

        if ($this->db !== null && $this->db->isAlive()) {
            $this->db->execute("DELETE FROM user_roles WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM user_groups WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM user_mfa WHERE user_id = ?", [$id]);
            $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            return true;
        }

        unset(self::$memoryStore[$id]);
        return true;
    }

    private function normalizeUser(array $u): array
    {
        $id = (string)($u['id'] ?? $u['_id'] ?? '');
        $roles = is_array($u['roles'] ?? null) ? $u['roles'] : ['Member'];
        $overlay = $this->getMailOverlayForUser($id);
        $defaultMailAccess = in_array('SuperAdmin', $roles, true) || in_array('SecurityOfficer', $roles, true);
        $mailAccess = array_key_exists('mail_access', $overlay)
            ? !empty($overlay['mail_access'])
            : (array_key_exists('mail_access', $u) ? !empty($u['mail_access']) : $defaultMailAccess);
        $mailboxAddress = array_key_exists('mailbox_address', $overlay)
            ? $overlay['mailbox_address']
            : ($u['mailbox_address'] ?? null);

        return [
            'id' => $id,
            'email' => (string)($u['email'] ?? ''),
            'name' => (string)($u['name'] ?? ''),
            'username' => (string)($u['username'] ?? ''),
            'display_preference' => in_array((string)($u['display_preference'] ?? 'name'), ['name', 'username'], true) ? (string)($u['display_preference'] ?? 'name') : 'name',
            'password_hash' => $u['password_hash'] ?? null,
            'auth_provider' => (string)($u['auth_provider'] ?? 'email'),
            'provider_id' => $u['provider_id'] ?? null,
            'avatar_url' => (string)($u['avatar_url'] ?? ''),
            'status' => (string)($u['status'] ?? 'active'),
            'mail_access' => $mailAccess,
            'mailbox_address' => is_string($mailboxAddress) && $mailboxAddress !== '' ? strtolower($mailboxAddress) : null,
            'is_2fa_enabled' => !empty($u['is_2fa_enabled']),
            'must_change_password' => !empty($u['must_change_password']),
            'totp_secret' => $u['totp_secret'] ?? null,
            'recovery_codes' => is_array($u['recovery_codes'] ?? null) ? $u['recovery_codes'] : (json_decode($u['recovery_codes'] ?? '[]', true) ?: []),
            'roles' => $roles,
            'groups' => is_array($u['groups'] ?? null) ? $u['groups'] : [],
            'created_at' => (string)($u['created_at'] ?? date('Y-m-d H:i:s')),
        ];
    }

    private function mailOverlayPath(): string
    {
        return dirname(__DIR__, 2) . '/storage/app/private/mail/user_mail_permissions.json';
    }

    private function readAllMailOverlays(): array
    {
        $path = $this->mailOverlayPath();
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function getMailOverlayForUser(string $userId): array
    {
        if ($userId === '') {
            return [];
        }
        $all = $this->readAllMailOverlays();
        return isset($all[$userId]) && is_array($all[$userId]) ? $all[$userId] : [];
    }

    private function writeMailOverlay(string $userId, array $patch): void
    {
        if ($userId === '') {
            return;
        }
        $path = $this->mailOverlayPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);
        $fp = @fopen($path . '.lock', 'c+');
        @chmod($path . '.lock', 0666);
        if ($fp !== false) {
            @flock($fp, LOCK_EX);
        }
        try {
            $all = $this->readAllMailOverlays();
            $current = isset($all[$userId]) && is_array($all[$userId]) ? $all[$userId] : [];
            $all[$userId] = array_merge($current, $patch);
            file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            @chmod($path, 0666);
        } finally {
            if ($fp !== false) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    private function deleteMailOverlay(string $userId): void
    {
        $path = $this->mailOverlayPath();
        if (!is_file($path)) {
            return;
        }
        $fp = @fopen($path . '.lock', 'c+');
        if ($fp !== false) {
            @flock($fp, LOCK_EX);
        }
        try {
            $all = $this->readAllMailOverlays();
            if (array_key_exists($userId, $all)) {
                unset($all[$userId]);
                file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            }
        } finally {
            if ($fp !== false) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
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
        $adminEmail = 'admin@nexus.local';
        $admin = $this->findByEmail($adminEmail);
        if ($admin === null) {
            $this->create([
                'id' => 'usr_admin_tim',
                'email' => $adminEmail,
                'name' => 'Timothy Dunn',
                'username' => 'nexus_admin',
                'password' => '123qweasd',
                'auth_provider' => 'email',
                'status' => 'active',
                'roles' => ['SuperAdmin'],
                'groups' => ['SecOps'],
                'is_2fa_enabled' => false,
                'must_change_password' => true,
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
                'username' => 'nexus_admin',
                'display_preference' => 'name',
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
        return null;
    }
}
