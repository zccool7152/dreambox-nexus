<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Auth\Auth;
use Nexus\Auth\Gate;
use Nexus\Core\Env;
use Nexus\Core\MiddlewareInterface;
use Nexus\Core\Request;
use Nexus\Core\Response;

/**
 * Access Control Middleware for Cyber-Noir Administrative Telemetry
 *
 * Enforces dual-layer defense-in-depth:
 * 1. Network Boundary: Checks client IP against configured allowlist (localhost / SecOps CIDR).
 * 2. Identity & RBAC: Validates active administrator session or bearer token via Auth & Gate.
 */
class MonitorAccessMiddleware implements MiddlewareInterface
{
    private ?Auth $auth;
    private ?Gate $gate;
    private array $allowedIps;
    private bool $allowLocal;

    public function __construct(
        ?Auth $auth = null,
        ?Gate $gate = null,
        ?array $allowedIps = null,
        ?bool $allowLocal = null
    ) {
        $this->auth = $auth;
        $this->gate = $gate;

        if ($allowedIps !== null) {
            $this->allowedIps = $allowedIps;
        } else {
            $envIps = (string)Env::get('MONITOR_ALLOWED_IPS', '127.0.0.1,::1');
            $this->allowedIps = array_filter(array_map('trim', explode(',', $envIps)));
        }

        $this->allowLocal = $allowLocal ?? (bool)Env::get('MONITOR_ALLOW_LOCAL', true);
    }

    public function handle(Request $request, callable $next): Response
    {
        $clientIp = $request->getClientIp();
        $isLocal = in_array($clientIp, ['127.0.0.1', '::1'], true) || in_array($clientIp, $this->allowedIps, true);
        $isProduction = (string)Env::get('APP_ENV', 'local') === 'production';
        $strictAuth = (bool)Env::get('MONITOR_REQUIRE_AUTH', false);

        $isAuthorized = false;

        // Layer 1: In local/staging environments with allowLocal enabled, allow authorized IPs
        if ($isLocal && $this->allowLocal && !$isProduction && !$strictAuth) {
            $isAuthorized = true;
        }

        // Layer 2: In production or strict mode, require authenticated admin session/token
        if (!$isAuthorized && $this->auth !== null) {
            $user = $this->auth->user($request);
            if ($user !== null) {
                if ($this->gate !== null) {
                    $isAuthorized = $this->gate->allows('view-telemetry', $user);
                } else {
                    $isAuthorized = true;
                }
            }
        }

        if (!$isAuthorized) {
            $isJson = str_starts_with($request->getPath(), '/api/') ||
                      str_contains($request->getHeader('accept') ?? '', 'application/json');

            if ($isJson) {
                return Response::json(
                    [],
                    'Forbidden: Administrative or local SecOps authorization required to access telemetry.',
                    403,
                    ['WWW-Authenticate' => 'Bearer realm="Nexus Telemetry"'],
                    $request->getTraceId()
                );
            }

            $safeIp = htmlspecialchars($clientIp, ENT_QUOTES, 'UTF-8');
            $safeTrace = htmlspecialchars($request->getTraceId(), ENT_QUOTES, 'UTF-8');

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden // Nexus SecOps</title>
    <style>
        :root { --bg: #05070d; --card: #0a0e17; --border: #1a2234; --accent-red: #ff3366; --text: #f8fafc; --muted: #64748b; }
        body { background: var(--bg); color: var(--text); font-family: 'JetBrains Mono', monospace; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .box { background: var(--card); border: 1px solid var(--border); border-top: 3px solid var(--accent-red); padding: 40px; max-width: 500px; text-align: center; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        h1 { color: var(--accent-red); font-size: 1.5rem; letter-spacing: 2px; margin: 0 0 16px 0; }
        p { color: var(--muted); font-size: 0.85rem; line-height: 1.6; margin: 8px 0; }
        .ip { color: #00f2fe; background: rgba(0, 242, 254, 0.1); padding: 2px 8px; border-radius: 3px; font-weight: bold; }
        .btn { display: inline-block; margin-top: 24px; padding: 10px 20px; background: transparent; border: 1px solid #00f2fe; color: #00f2fe; text-decoration: none; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s; }
        .btn:hover { background: #00f2fe; color: var(--bg); }
    </style>
</head>
<body>
    <div class="box">
        <h1>[ 403 // ACCESS DENIED ]</h1>
        <p>The Cyber-Noir Observability HUD is restricted to authenticated SecOps administrators and authorized IP networks.</p>
        <p>Client Ingress IP: <span class="ip">{$safeIp}</span></p>
        <p style="font-size: 0.75rem; color: #475569; margin-top: 16px;">Trace ID: {$safeTrace}</p>
        <a href="/" class="btn">&larr; Return to Application Gateway</a>
    </div>
</body>
</html>
HTML;
            return Response::html($html, 403);
        }

        return $next($request);
    }
}

