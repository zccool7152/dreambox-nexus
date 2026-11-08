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
        $this->db->execute(
            "UPDATE user_consents SET status = 'revoked', revoked_at = CURRENT_TIMESTAMP WHERE user_id = ? AND purpose = ?",
            [$userId, $purpose]
        );
    }

    public function hasConsent(string $userId, string $purpose): bool
    {
        $row = $this->db->fetch(
            "SELECT status FROM user_consents WHERE user_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1",
            [$userId, $purpose]
        );

        return $row !== null && $row['status'] === 'granted';
    }

    public function getConsentStats(): array
    {
        $granted = (int)($this->db->fetch("SELECT COUNT(*) as c FROM user_consents WHERE status = 'granted'")['c'] ?? 0);
        $revoked = (int)($this->db->fetch("SELECT COUNT(*) as c FROM user_consents WHERE status = 'revoked'")['c'] ?? 0);

        return [
            'granted' => $granted,
            'revoked' => $revoked,
            'total' => $granted + $revoked,
        ];
    }
}

