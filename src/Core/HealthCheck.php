<?php
declare(strict_types=1);

namespace Nexus\Core;

use Nexus\Database\Database;

/**
 * Production Health & Liveness Probes
 */
class HealthCheck
{
    public function liveness(Request $request, ?string $baseDir = null): Response
    {
        $accept = $request->getHeader('accept', '');
        $format = $request->getString('format');
        $wantsJson = str_contains($accept, 'application/json') || $format === 'json';

        // When viewed in a browser (text/html without explicit json query), render the visual UI
        if (!$wantsJson && str_contains($accept, 'text/html') && $baseDir !== null) {
            $user = null;
            try {
                $db = new Database();
                $session = new \Nexus\Security\Session($db);
                $hasher = new \Nexus\Crypto\Hasher();
                $auth = new \Nexus\Auth\Auth($session, $hasher, $db);
                $user = $auth->user($request);
            } catch (\Throwable $e) {}

            $view = new \Nexus\View\View($baseDir . '/resources/views');
            $html = $view->render('health', [
                'traceId' => $request->getTraceId(),
                'user' => $user,
                'currentUser' => $user,
                'appBase' => Request::getAppBase(),
            ]);
            return Response::html($html);
        }

        return Response::json([
            'status' => 'UP',
            'timestamp' => time(),
        ], 'Service is alive.', 200, [], $request->getTraceId());
    }

    public function readiness(Request $request, Database $db, string $storagePath): Response
    {
        $checks = [
            'database' => $db->isAlive(),
            'storage_writable' => is_writable($storagePath),
        ];

        $healthy = !in_array(false, $checks, true);
        $statusCode = $healthy ? 200 : 503;
        $message = $healthy ? 'Service is ready to accept traffic.' : 'Service dependencies unhealthy.';

        return Response::json([
            'status' => $healthy ? 'READY' : 'DEGRADED',
            'checks' => $checks,
        ], $message, $statusCode, [], $request->getTraceId());
    }
}

