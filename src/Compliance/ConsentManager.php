<?php
declare(strict_types=1);

namespace Nexus\Compliance;

use Nexus\Database\Database;
use Nexus\Core\Request;

/**
 * GDPR Article 7 Granular Consent Manager
 */
class ConsentManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function recordConsent(?string $userId, string $purpose, string $version, Request $request): void
    {
        if ($this->db->getDriver() === 'mongodb') {
            $this->db->mongoInsert('user_consents', [
                'user_id' => $userId,
                'purpose' => $purpose,
                'version' => $version,
                'status' => 'granted',
                'ip_address' => $request->getClientIp(),
                'user_agent' => $request->getHeader('user-agent'),
                'granted_at' => gmdate('Y-m-d H:i:s'),
            ]);
            return;
        }

        $sql = "INSERT INTO user_consents (user_id, purpose, version, status, ip_address, user_agent, granted_at)
                VALUES (?, ?, ?, 'granted', ?, ?, CURRENT_TIMESTAMP)";

        $this->db->execute($sql, [
            $userId,
            $purpose,
            $version,
            $request->getClientIp(),
            $request->getHeader('user-agent'),
        ]);
    }

    public function withdrawConsent(string $userId, string $purpose): void
    {
        if ($this->db->getDriver() === 'mongodb') {
            $this->db->mongoUpdate(
                'user_consents',
                ['user_id' => $userId, 'purpose' => $purpose],
                ['$set' => ['status' => 'revoked', 'revoked_at' => gmdate('Y-m-d H:i:s')]],
                ['multi' => true]
            );
            return;
        }

        $this->db->execute(
            "UPDATE user_consents SET status = 'revoked', revoked_at = CURRENT_TIMESTAMP WHERE user_id = ? AND purpose = ?",
            [$userId, $purpose]
        );
    }

    public function hasConsent(string $userId, string $purpose): bool
    {
        if ($this->db->getDriver() === 'mongodb') {
            $rows = $this->db->mongoFind('user_consents', ['user_id' => $userId, 'purpose' => $purpose]);
            $row = !empty($rows) ? end($rows) : null;
            return $row !== null && ($row['status'] ?? '') === 'granted';
        }

        $row = $this->db->fetch(
            "SELECT status FROM user_consents WHERE user_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1",
            [$userId, $purpose]
        );

        return $row !== null && $row['status'] === 'granted';
    }

    public function getConsentStats(): array
    {
        if ($this->db->getDriver() === 'mongodb') {
            $granted = $this->db->mongoCount('user_consents', ['status' => 'granted']);
            $revoked = $this->db->mongoCount('user_consents', ['status' => 'revoked']);
        } else {
            $granted = (int)($this->db->fetch("SELECT COUNT(*) as c FROM user_consents WHERE status = 'granted'")['c'] ?? 0);
            $revoked = (int)($this->db->fetch("SELECT COUNT(*) as c FROM user_consents WHERE status = 'revoked'")['c'] ?? 0);
        }

        return [
            'granted' => $granted,
            'revoked' => $revoked,
            'total' => $granted + $revoked,
        ];
    }
}

