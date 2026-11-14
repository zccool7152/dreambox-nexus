<?php
declare(strict_types=1);

namespace Nexus\Monitor;

use Nexus\Database\Database;

/**
 * NIST CSF 2.0 Anomaly Detection & Incident Threat Analyzer
 */
class AnomalyDetector
{
    private ?Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db;
    }

    public function analyze(): array
    {
        $anomalies = [];
        $threatLevel = 'LOW';

        if ($this->db !== null && $this->db->isAlive()) {
            if ($this->db->isSql()) {
                // 1. Detect Credential Spraying (Spike in failed attempts across different accounts)
                $failedCount = (int)($this->db->fetch("SELECT SUM(attempts) as c FROM login_attempts WHERE last_attempt_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)")['c'] ?? 0);
                // 2. Detect Bulk Data Export Spikes (Potential GDPR exfiltration)
                $exportCount = (int)($this->db->fetch("SELECT COUNT(*) as c FROM gdpr_requests WHERE type = 'export' AND requested_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c'] ?? 0);
            } else {
                // MongoDB Telemetry Queries
                $since15m = date('Y-m-d H:i:s', time() - 900);
                $attemptDocs = $this->db->mongoFind('login_attempts', ['last_attempt_at' => ['$gte' => $since15m]]);
                $failedCount = 0;
                foreach ($attemptDocs as $doc) {
                    $failedCount += (int)($doc['attempts'] ?? 0);
                }

                $since1h = gmdate('Y-m-d H:i:s', time() - 3600);
                $exportCount = $this->db->mongoCount('gdpr_requests', [
                    'type' => 'export',
                    'requested_at' => ['$gte' => $since1h],
                ]);
            }

            if ($failedCount > 20) {
                $anomalies[] = [
                    'severity' => 'HIGH',
                    'type' => 'CREDENTIAL_SPRAY_ATTACK',
                    'description' => "Detected {$failedCount} failed login attempts in the last 15 minutes.",
                    'timestamp' => gmdate('Y-m-d H:i:s'),
                ];
                $threatLevel = 'ELEVATED';
            }

            if ($exportCount > 5) {
                $anomalies[] = [
                    'severity' => 'CRITICAL',
                    'type' => 'ABNORMAL_DATA_EXFILTRATION',
                    'description' => "Detected {$exportCount} GDPR data export requests within one hour.",
                    'timestamp' => gmdate('Y-m-d H:i:s'),
                ];
                $threatLevel = 'CRITICAL';
            }
        }

        return [
            'threat_level' => $threatLevel,
            'anomalies_detected' => count($anomalies),
            'incidents' => $anomalies,
        ];
    }
}

