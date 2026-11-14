<?php
declare(strict_types=1);

namespace Nexus\Compliance;

use Nexus\Database\Database;
use Nexus\Events\AuditLogger;
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
        $isMongo = $this->db->getDriver() === 'mongodb';

        // 1. Gather all data across user-related collections/tables
        if ($isMongo) {
            $userConsents = $this->db->mongoFind('user_consents', ['user_id' => $userId]);
            $rawLogs = $this->db->mongoFind('audit_logs', ['user_id' => $userId]);
            $auditLogs = array_map(static fn(array $r): array => [
                'id' => $r['id'] ?? null,
                'action' => $r['action'] ?? '',
                'resource' => $r['resource'] ?? '',
                'ip_address' => $r['ip_address'] ?? '',
                'created_at' => $r['created_at'] ?? '',
            ], $rawLogs);
            $mfaRecord = $this->db->mongoFindOne('user_mfa', ['user_id' => $userId]);
        } else {
            $userConsents = $this->db->fetchAll("SELECT * FROM user_consents WHERE user_id = ?", [$userId]);
            $auditLogs = $this->db->fetchAll("SELECT id, action, resource, ip_address, created_at FROM audit_logs WHERE user_id = ?", [$userId]);
            $mfaRecord = $this->db->fetch("SELECT is_enabled, created_at FROM user_mfa WHERE user_id = ?", [$userId]);
        }

        $exportData = [
            'user_id' => $userId,
            'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'mfa_status' => $mfaRecord ? ['is_enabled' => $mfaRecord['is_enabled'] ?? 0, 'created_at' => $mfaRecord['created_at'] ?? null] : ['is_enabled' => 0],
            'consents' => $userConsents,
            'audit_events' => $auditLogs,
        ];

        // 2. Persist to secure storage
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userId);
        $filename = "exports/gdpr_export_{$safeId}_" . time() . ".json";
        $this->storage->put($filename, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 3. Record completed request
        if ($isMongo) {
            $this->db->mongoInsert('gdpr_requests', [
                'user_id' => $userId,
                'type' => 'export',
                'status' => 'completed',
                'export_path' => $filename,
                'requested_at' => gmdate('Y-m-d H:i:s'),
                'completed_at' => gmdate('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->execute(
                "INSERT INTO gdpr_requests (user_id, type, status, export_path, completed_at) VALUES (?, 'export', 'completed', ?, CURRENT_TIMESTAMP)",
                [$userId, $filename]
            );
        }

        return $exportData;
    }

    public function eraseData(string $userId, bool $anonymizeAudit = true): bool
    {
        $anonUserId = $this->anonymizer->anonymizeString($userId);

        if ($this->db->getDriver() === 'mongodb') {
            $this->db->mongoDelete('sessions', ['user_id' => $userId]);
            $this->db->mongoDelete('user_mfa', ['user_id' => $userId]);
            $this->db->mongoUpdate(
                'user_consents',
                ['user_id' => $userId],
                ['$set' => ['status' => 'revoked', 'revoked_at' => gmdate('Y-m-d H:i:s')]],
                ['multi' => true]
            );
            $this->db->mongoInsert('gdpr_requests', [
                'user_id' => $anonUserId,
                'type' => 'erase',
                'status' => 'completed',
                'requested_at' => gmdate('Y-m-d H:i:s'),
                'completed_at' => gmdate('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->transaction(function (Database $db) use ($userId, $anonUserId): bool {
                // 1. Remove active sessions
                $db->execute("DELETE FROM sessions WHERE user_id = ?", [$userId]);

                // 2. Remove MFA credentials
                $db->execute("DELETE FROM user_mfa WHERE user_id = ?", [$userId]);

                // 3. Mark consents as revoked
                $db->execute("UPDATE user_consents SET status = 'revoked', revoked_at = CURRENT_TIMESTAMP WHERE user_id = ?", [$userId]);

                // 4. Log completed request under anonymized subject identifier
                $db->execute(
                    "INSERT INTO gdpr_requests (user_id, type, status, completed_at) VALUES (?, 'erase', 'completed', CURRENT_TIMESTAMP)",
                    [$anonUserId]
                );

                return true;
            });
        }

        // Preserve append-only audit hash chain by appending an immutable erasure event instead of mutating prior records
        if ($anonymizeAudit) {
            (new AuditLogger($this->db))->log(
                'gdpr-erase-' . bin2hex(random_bytes(4)),
                $anonUserId,
                'GDPR_ERASURE',
                'users',
                $anonUserId,
                '127.0.0.1',
                'Nexus-GDPR',
                null,
                ['pseudonymized_subject' => $anonUserId, 'erased_at' => gmdate('Y-m-d\TH:i:s\Z')]
            );
        }

        return true;
    }

    public function getPendingRequests(): array
    {
        if ($this->db->getDriver() === 'mongodb') {
            return $this->db->mongoFind('gdpr_requests', ['status' => 'pending'], ['sort' => ['requested_at' => 1]]);
        }

        return $this->db->fetchAll("SELECT * FROM gdpr_requests WHERE status = 'pending' ORDER BY requested_at ASC");
    }
}

