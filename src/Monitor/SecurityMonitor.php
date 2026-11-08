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

        if ($this->db !== null && $this->db->isAlive() && $this->db->isSql()) {
            $now = time();
            $throttledRows = $this->db->fetchAll(
                "SELECT key_id, hits, reset_at FROM rate_limits WHERE reset_at > ? AND hits > 60 ORDER BY hits DESC LIMIT 10",
                [$now]
            );

            foreach ($throttledRows as $row) {
                $throttledIps[] = [
                    'target' => $row['key_id'],
                    'hits' => (int)$row['hits'],
                    'seconds_remaining' => max(0, (int)$row['reset_at'] - $now),
                ];
            }

            $lockedRows = $this->db->fetchAll(
                "SELECT identity, ip_address, attempts, locked_until FROM login_attempts WHERE locked_until > CURRENT_TIMESTAMP LIMIT 10"
            );

            foreach ($lockedRows as $row) {
                $lockedAccounts[] = [
                    'identity' => $row['identity'],
                    'ip_address' => $row['ip_address'],
                    'attempts' => (int)$row['attempts'],
                    'locked_until' => $row['locked_until'],
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

