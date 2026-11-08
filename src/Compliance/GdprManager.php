<?php
declare(strict_types=1);

namespace Nexus\Compliance;

use Nexus\Database\Database;
use Nexus\Storage\Storage;
use RuntimeException;

/**
 * GDPR Data Subject Rights (DSR) Orchestrator
 *
 * Implements Article 17 (Right to Erasure) & Article 20 (Data Portability).
 */
class GdprManager
{
    private Database $db;
    private Storage $storage;
    private DataAnonymizer $anonymizer;

    public function __construct(Database $db, Storage $storage, ?DataAnonymizer $anonymizer = null)
    {
        $this->db = $db;
        $this->storage = $storage;
        $this->anonymizer = $anonymizer ?? new DataAnonymizer();
    }

    public function exportData(string $userId): array
    {
        // 1. Gather all data across user-related tables
        $userConsents = $this->db->fetchAll("SELECT * FROM user_consents WHERE user_id = ?", [$userId]);
        $auditLogs = $this->db->fetchAll("SELECT id, action, resource, ip_address, created_at FROM audit_logs WHERE user_id = ?", [$userId]);
        $mfaRecord = $this->db->fetch("SELECT is_enabled, created_at FROM user_mfa WHERE user_id = ?", [$userId]);

        $exportData = [
            'user_id' => $userId,
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'mfa_status' => $mfaRecord ?: ['is_enabled' => 0],
            'consents' => $userConsents,
            'audit_events' => $auditLogs,
        ];

        // 2. Persist to secure storage
        $filename = "exports/gdpr_export_{$userId}_" . time() . ".json";
        $this->storage->put($filename, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 3. Record completed request
        $this->db->execute(
            "INSERT INTO gdpr_requests (user_id, type, status, export_path, completed_at) VALUES (?, 'export', 'completed', ?, CURRENT_TIMESTAMP)",
            [$userId, $filename]
        );

        return $exportData;
    }

    public function eraseData(string $userId, bool $anonymizeAudit = true): bool
    {
        return $this->db->transaction(function (Database $db) use ($userId, $anonymizeAudit): bool {
            // 1. Remove active sessions
            $db->execute("DELETE FROM sessions WHERE user_id = ?", [$userId]);

            // 2. Remove MFA credentials
            $db->execute("DELETE FROM user_mfa WHERE user_id = ?", [$userId]);

            // 3. Mark consents as revoked
            $db->execute("UPDATE user_consents SET status = 'revoked', revoked_at = CURRENT_TIMESTAMP WHERE user_id = ?", [$userId]);

            // 4. Anonymize or redact audit records while preserving hash-chain integrity
            if ($anonymizeAudit) {
                $anonUserId = $this->anonymizer->anonymizeString($userId);
                $db->execute("UPDATE audit_logs SET user_id = ? WHERE user_id = ?", [$anonUserId, $userId]);
            }

            // 5. Log completed request
            $db->execute(
                "INSERT INTO gdpr_requests (user_id, type, status, completed_at) VALUES (?, 'erase', 'completed', CURRENT_TIMESTAMP)",
                [$userId]
            );

            return true;
        });
    }

    public function getPendingRequests(): array
    {
        return $this->db->fetchAll("SELECT * FROM gdpr_requests WHERE status = 'pending' ORDER BY requested_at ASC");
    }
}

