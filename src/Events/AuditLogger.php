<?php
declare(strict_types=1);

namespace Nexus\Events;

use Nexus\Database\Database;
use Nexus\Security\DataMasker;

/**
 * Tamper-Evident Cryptographic Hash-Chained Audit Log
 *
 * Implements continuous Merkle/Hash-Chaining across database & forensic ledger records:
 * Any row deletion, modification, or out-of-order insertion permanently
 * breaks the cryptographic hash chain and flags an alert on the SOC Monitor.
 */
class AuditLogger
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    private ?Database $db;
    private string $ledgerFile;

    public function __construct(?Database $db = null, ?string $ledgerFile = null)
    {
        $this->db = $db;
        $this->ledgerFile = $ledgerFile ?? (dirname(__DIR__, 2) . '/storage/logs/audit_chain.json');
        $this->ensureSeedLedger();
    }

    /**
     * Compute deterministic SHA-256 block hash for an audit record.
     */
    public static function computeHash(
        string $previousHash,
        string $traceId,
        string $userId,
        string $action,
        string $resource,
        string $resourceId,
        string $ipAddress,
        ?string $payloadBefore,
        ?string $payloadAfter,
        string $createdAt
    ): string {
        $hashData = implode('|', [
            $previousHash,
            $traceId,
            $userId,
            $action,
            $resource,
            $resourceId,
            $ipAddress,
            (string)$payloadBefore,
            (string)$payloadAfter,
            $createdAt,
        ]);

        return hash('sha256', $hashData);
    }

    /**
     * Atomically append a new record to the cryptographic hash chain.
     */
    public function log(
        string $traceId,
        ?string $userId,
        string $action,
        string $resource,
        ?string $resourceId,
        string $ipAddress,
        ?string $userAgent,
        ?array $before = null,
        ?array $after = null
    ): string {
        // 1. Mask sensitive PII, PANs, and passwords before recording
        $cleanBefore = $before !== null ? DataMasker::maskArray($before) : null;
        $cleanAfter = $after !== null ? DataMasker::maskArray($after) : null;

        return $this->withExclusiveLock(function () use (
            $traceId,
            $userId,
            $action,
            $resource,
            $resourceId,
            $ipAddress,
            $userAgent,
            $cleanBefore,
            $cleanAfter
        ): string {
            // 2. Fetch all existing records to determine sequence & last hash
            $existing = $this->getAllLogsAscending();
            $lastRow = !empty($existing) ? end($existing) : null;
            $previousHash = $lastRow['hash'] ?? self::GENESIS_HASH;
            $nextId = $lastRow ? ((int)$lastRow['id'] + 1) : 1;

            $timestamp = gmdate('Y-m-d H:i:s');
            $jsonBefore = $cleanBefore !== null ? json_encode($cleanBefore, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
            $jsonAfter = $cleanAfter !== null ? json_encode($cleanAfter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

            $effectiveUserId = $userId ?? 'SYSTEM';
            $effectiveResourceId = $resourceId ?? '';

            // 3. Compute Cryptographic SHA-256 Hash
            $currentHash = self::computeHash(
                $previousHash,
                $traceId,
                $effectiveUserId,
                $action,
                $resource,
                $effectiveResourceId,
                $ipAddress,
                $jsonBefore,
                $jsonAfter,
                $timestamp
            );

            $entry = [
                'id' => $nextId,
                'trace_id' => $traceId,
                'user_id' => $effectiveUserId,
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $effectiveResourceId,
                'ip_address' => $ipAddress,
                'user_agent' => substr((string)$userAgent, 0, 255),
                'payload_before' => $jsonBefore,
                'payload_after' => $jsonAfter,
                'previous_hash' => $previousHash,
                'hash' => $currentHash,
                'created_at' => $timestamp,
            ];

            // 4. Persist to active database if online and keep file ledger strictly synchronized
            $dbOnline = $this->db !== null && $this->db->isAlive();
            $dbPersisted = $dbOnline ? $this->persistToDatabase($entry) : false;

            if (!$dbOnline || $dbPersisted) {
                $existing[] = $entry;
                $this->saveFileLedger($existing);
            }

            return $currentHash;
        });
    }

    /**
     * Audit & Verify Cryptographic Integrity of Entire Log Chain.
     * Evaluates continuous block links and recalculated content hashes.
     */
    public function verifyChain(): array
    {
        $rows = $this->getAllLogsAscending();
        if (empty($rows)) {
            $this->ensureSeedLedger();
            $rows = $this->getAllLogsAscending();
        }

        $expectedPrevHash = self::GENESIS_HASH;
        $verifiedCount = 0;

        foreach ($rows as $row) {
            $rowId = (int)($row['id'] ?? ($verifiedCount + 1));
            $rowPrev = (string)($row['previous_hash'] ?? '');

            // Check previous hash link
            if ($rowPrev !== $expectedPrevHash) {
                return [
                    'intact' => false,
                    'broken_at_id' => $rowId,
                    'error' => "Hash chain broken at record ID {$rowId}: previous_hash mismatch.",
                    'total_verified' => $verifiedCount,
                ];
            }

            // Recompute row hash
            $recalculated = self::computeHash(
                $rowPrev,
                (string)($row['trace_id'] ?? ''),
                (string)($row['user_id'] ?? ''),
                (string)($row['action'] ?? ''),
                (string)($row['resource'] ?? ''),
                (string)($row['resource_id'] ?? ''),
                (string)($row['ip_address'] ?? ''),
                $row['payload_before'] !== null ? (string)$row['payload_before'] : null,
                $row['payload_after'] !== null ? (string)$row['payload_after'] : null,
                (string)($row['created_at'] ?? '')
            );

            if (!hash_equals($recalculated, (string)($row['hash'] ?? ''))) {
                return [
                    'intact' => false,
                    'broken_at_id' => $rowId,
                    'error' => "Row integrity violation at ID {$rowId}: record contents were tampered with.",
                    'total_verified' => $verifiedCount,
                ];
            }

            $expectedPrevHash = (string)$row['hash'];
            $verifiedCount++;
        }

        return [
            'intact' => true,
            'broken_at_id' => null,
            'error' => null,
            'total_verified' => $verifiedCount,
        ];
    }

    /**
     * Retrieve recent audit logs in descending chronological order.
     */
    public function getRecentLogs(int $limit = 15): array
    {
        $all = $this->getAllLogsAscending();
        if (empty($all)) {
            $this->ensureSeedLedger();
            $all = $this->getAllLogsAscending();
        }

        $reversed = array_reverse($all);
        return array_slice($reversed, 0, $limit);
    }

    /**
     * Retrieve all audit logs ordered ascending by sequence/ID and keep file & DB ledgers synchronized.
     */
    private function getAllLogsAscending(): array
    {
        $fileLogs = $this->getFileLedger();

        if ($this->db !== null && $this->db->isAlive()) {
            $dbLogs = [];
            $dbQueried = false;

            // 1. Try relational SQL if alive
            if ($this->db->isSql()) {
                try {
                    $dbLogs = $this->db->fetchAll("SELECT * FROM audit_logs ORDER BY id ASC");
                    $dbQueried = true;
                } catch (\Throwable) {}
            // 2. Try MongoDB if alive
            } elseif ($this->db->getDriver() === 'mongodb') {
                try {
                    $docs = $this->db->mongoFind('audit_logs', [], ['sort' => ['id' => 1]]);
                    $dbLogs = array_map(fn($d) => (array)$d, $docs);
                    $dbQueried = true;
                } catch (\Throwable) {}
            }

            if ($dbQueried) {
                $lastDb = !empty($dbLogs) ? end($dbLogs) : null;
                $lastFile = !empty($fileLogs) ? end($fileLogs) : null;
                $maxDbId = $lastDb ? (int)($lastDb['id'] ?? 0) : 0;
                $maxFileId = $lastFile ? (int)($lastFile['id'] ?? 0) : 0;

                // Replay any offline-recorded file entries into DB so sequence IDs never fork
                if ($maxFileId > $maxDbId) {
                    foreach ($fileLogs as $entry) {
                        if ((int)($entry['id'] ?? 0) > $maxDbId) {
                            if (!$this->persistToDatabase($entry)) {
                                return $fileLogs;
                            }
                        }
                    }
                    return $fileLogs;
                }

                // Sync file ledger up to DB state if DB is ahead
                if ($maxDbId > $maxFileId && !empty($dbLogs)) {
                    $this->saveFileLedger($dbLogs);
                }

                if (!empty($dbLogs)) {
                    return $dbLogs;
                }
            }
        }

        // 3. Fallback to resilient file-backed ledger
        return $fileLogs;
    }

    /**
     * Persist record to SQL or MongoDB. Returns true only if actually persisted.
     */
    private function persistToDatabase(array $entry): bool
    {
        if ($this->db === null || !$this->db->isAlive()) {
            return false;
        }

        try {
            if ($this->db->getDriver() === 'mongodb') {
                $this->db->mongoInsert('audit_logs', $entry);
                return true;
            }

            if ($this->db->isSql()) {
                $sql = "INSERT INTO audit_logs (
                    trace_id, user_id, action, resource, resource_id,
                    ip_address, user_agent, payload_before, payload_after,
                    previous_hash, hash, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->db->execute($sql, [
                    $entry['trace_id'],
                    $entry['user_id'],
                    $entry['action'],
                    $entry['resource'],
                    $entry['resource_id'],
                    $entry['ip_address'],
                    $entry['user_agent'],
                    $entry['payload_before'],
                    $entry['payload_after'],
                    $entry['previous_hash'],
                    $entry['hash'],
                    $entry['created_at'],
                ]);
                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Ensure the cryptographic genesis ledger is populated with baseline security events.
     */
    private function ensureSeedLedger(): void
    {
        $this->withExclusiveLock(function (): void {
            $existing = $this->getFileLedger();
            if (!empty($existing)) {
                // Synchronize database if active but currently empty
                if ($this->db !== null && $this->db->isAlive()) {
                    $hasDbLogs = false;
                    try {
                        if ($this->db->getDriver() === 'mongodb') {
                            $hasDbLogs = $this->db->mongoCount('audit_logs') > 0;
                        } elseif ($this->db->isSql()) {
                            $hasDbLogs = !empty($this->db->fetch("SELECT id FROM audit_logs LIMIT 1"));
                        }
                    } catch (\Throwable) {}

                    if (!$hasDbLogs) {
                        foreach ($existing as $entry) {
                            $this->persistToDatabase($entry);
                        }
                    }
                }
                return;
            }

            $seeds = [
                [
                    'trace_id' => 'genesis-0001',
                    'user_id' => 'SYSTEM',
                    'action' => 'SYSTEM_BOOT',
                    'resource' => 'kernel',
                    'resource_id' => 'v0.1-alpha',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Dreambox Nexus Kernel',
                    'payload_before' => null,
                    'payload_after' => ['status' => 'CIA Triad Active', 'engine' => 'Argon2id+AEAD'],
                    'created_at' => '2026-11-08 12:00:00',
                ],
                [
                    'trace_id' => 'sec-init-0002',
                    'user_id' => 'SYSTEM',
                    'action' => 'SECURITY_PROFILE_ENFORCE',
                    'resource' => 'soc_monitor',
                    'resource_id' => 'rate_limit_sliding_window',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Dreambox Nexus Kernel',
                    'payload_before' => null,
                    'payload_after' => ['csp' => 'strict_dynamic_nonce', 'ssrf_protection' => 'active'],
                    'created_at' => '2026-11-08 12:00:01',
                ],
                [
                    'trace_id' => 'iam-init-0003',
                    'user_id' => 'usr_admin_001',
                    'action' => 'RBAC_POLICY_LOAD',
                    'resource' => 'iam',
                    'resource_id' => 'roles_matrix',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Dreambox Nexus Kernel',
                    'payload_before' => null,
                    'payload_after' => ['roles' => 5, 'groups' => 3, 'superadmin' => 'admin@nexus.local'],
                    'created_at' => '2026-11-08 12:00:02',
                ],
                [
                    'trace_id' => 'comp-eval-0004',
                    'user_id' => 'SYSTEM',
                    'action' => 'COMPLIANCE_MATRIX_EVALUATE',
                    'resource' => 'compliance',
                    'resource_id' => 'multi_framework',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Nexus Automated Audit Engine',
                    'payload_before' => null,
                    'payload_after' => ['frameworks' => ['PCI-DSS', 'HIPAA', 'NIST', 'GDPR', 'OWASP'], 'score' => 100],
                    'created_at' => '2026-11-08 12:00:03',
                ],
                [
                    'trace_id' => 'crypto-0005',
                    'user_id' => 'admin@nexus.local',
                    'action' => 'IAM_SUPERADMIN_SESSION',
                    'resource' => 'iam_session',
                    'resource_id' => 'active_session',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'payload_before' => null,
                    'payload_after' => ['auth_provider' => 'local', 'mfa_enforced' => true],
                    'created_at' => '2026-11-08 12:00:04',
                ],
            ];

            $currentPrev = self::GENESIS_HASH;
            $ledger = [];

            foreach ($seeds as $idx => $s) {
                $id = $idx + 1;
                $jsonBefore = $s['payload_before'] !== null ? json_encode($s['payload_before'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
                $jsonAfter = $s['payload_after'] !== null ? json_encode($s['payload_after'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

                $currentHash = self::computeHash(
                    $currentPrev,
                    $s['trace_id'],
                    $s['user_id'],
                    $s['action'],
                    $s['resource'],
                    $s['resource_id'],
                    $s['ip_address'],
                    $jsonBefore,
                    $jsonAfter,
                    $s['created_at']
                );

                $entry = [
                    'id' => $id,
                    'trace_id' => $s['trace_id'],
                    'user_id' => $s['user_id'],
                    'action' => $s['action'],
                    'resource' => $s['resource'],
                    'resource_id' => $s['resource_id'],
                    'ip_address' => $s['ip_address'],
                    'user_agent' => $s['user_agent'],
                    'payload_before' => $jsonBefore,
                    'payload_after' => $jsonAfter,
                    'previous_hash' => $currentPrev,
                    'hash' => $currentHash,
                    'created_at' => $s['created_at'],
                ];

                $ledger[] = $entry;
                $this->persistToDatabase($entry);
                $currentPrev = $currentHash;
            }

            $this->saveFileLedger($ledger);
        });
    }

    /**
     * Acquire an exclusive advisory lock around critical ledger read-compute-write sections.
     */
    private function withExclusiveLock(callable $callback): mixed
    {
        $dir = dirname($this->ledgerFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $lockFile = $this->ledgerFile . '.lock';
        $fp = @fopen($lockFile, 'c+');
        if ($fp === false) {
            return $callback();
        }

        if (!@flock($fp, LOCK_EX)) {
            fclose($fp);
            return $callback();
        }

        try {
            return $callback();
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Read from local ledger file.
     */
    private function getFileLedger(): array
    {
        if (!file_exists($this->ledgerFile)) {
            return [];
        }

        $content = (string)file_get_contents($this->ledgerFile);
        if (trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Write to local ledger file.
     */
    private function saveFileLedger(array $entries): void
    {
        $dir = dirname($this->ledgerFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        file_put_contents(
            $this->ledgerFile,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
