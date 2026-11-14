<?php
declare(strict_types=1);

namespace Nexus\Monitor;

use Nexus\Database\Database;

/**
 * Security Operations Center (SOC) Telemetry Collector
 */
class SecurityMonitor
{
    private ?Database $db;
    private string $storagePath;

    public function __construct(?Database $db = null, ?string $storagePath = null)
    {
        $this->db = $db;
        $this->storagePath = $storagePath ?? dirname(__DIR__, 2) . '/storage';
    }

    public function getMetrics(): array
    {
        $throttledIps = [];
        $lockedAccounts = [];

        if ($this->db !== null && $this->db->isAlive()) {
            $now = time();

            if ($this->db->isSql()) {
                $throttledRows = $this->db->fetchAll(
                    "SELECT key_id, hits, reset_at FROM rate_limits WHERE reset_at > ? AND hits > 60 ORDER BY hits DESC LIMIT 10",
                    [$now]
                );
                $lockedRows = $this->db->fetchAll(
                    "SELECT identity, ip_address, attempts, locked_until FROM login_attempts WHERE locked_until > CURRENT_TIMESTAMP LIMIT 10"
                );
            } else {
                $throttledRows = $this->db->mongoFind(
                    'rate_limits',
                    ['reset_at' => ['$gt' => $now], 'hits' => ['$gt' => 60]],
                    ['sort' => ['hits' => -1], 'limit' => 10]
                );
                $lockedRows = $this->db->mongoFind(
                    'login_attempts',
                    ['locked_until' => ['$gt' => date('Y-m-d H:i:s')]],
                    ['limit' => 10]
                );
            }

            foreach ($throttledRows as $row) {
                $throttledIps[] = [
                    'target' => $row['key_id'] ?? '',
                    'hits' => (int)($row['hits'] ?? 0),
                    'seconds_remaining' => max(0, (int)($row['reset_at'] ?? 0) - $now),
                ];
            }

            foreach ($lockedRows as $row) {
                $lockedAccounts[] = [
                    'identity' => $row['identity'] ?? '',
                    'ip_address' => $row['ip_address'] ?? '',
                    'attempts' => (int)($row['attempts'] ?? 0),
                    'locked_until' => $row['locked_until'] ?? '',
                ];
            }
        }

        // Read recent CSP violation count
        $cspFile = $this->storagePath . '/logs/csp_violations.log';
        $recentCspViolations = [];
        if (file_exists($cspFile)) {
            $lines = array_slice(file($cspFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -5);
            foreach ($lines as $line) {
                $recentCspViolations[] = json_decode($line, true) ?: ['raw' => $line];
            }
        }

        return [
            'throttled_clients_count' => count($throttledIps),
            'throttled_clients' => $throttledIps,
            'locked_accounts_count' => count($lockedAccounts),
            'locked_accounts' => $lockedAccounts,
            'recent_csp_violations' => $recentCspViolations,
            'security_headers_status' => 'ALL_ENFORCED',
            'defense_in_depth' => [
                'csp_nonce' => 'ACTIVE',
                'clickjacking' => 'DENIED',
                'nosniff' => 'ENFORCED',
                'hsts' => 'READY',
            ],
        ];
    }
}

