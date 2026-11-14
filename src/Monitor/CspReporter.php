<?php
declare(strict_types=1);

namespace Nexus\Monitor;

use Nexus\Core\Request;
use Nexus\Core\Response;

/**
 * Browser Content-Security-Policy Violation Ingestion Controller
 */
class CspReporter
{
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? dirname(__DIR__, 2) . '/storage/logs/csp_violations.log';
    }

    public function ingest(Request $request): Response
    {
        $raw = substr($request->getRawBody(), 0, 8192);
        if (trim($raw) === '') {
            return Response::noContent();
        }

        $decoded = json_decode($raw, true);
        $entry = [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'ip' => $request->getClientIp(),
            'report' => $decoded['csp-report'] ?? $decoded,
        ];

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logPath, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);

        return Response::noContent();
    }
}

