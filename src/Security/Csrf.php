<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Core\Logger;
use Nexus\Core\MiddlewareInterface;
use Nexus\Core\Request;
use Nexus\Core\Response;

/**
 * Timing-Safe HMAC CSRF Protection Middleware
 */
class Csrf implements MiddlewareInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        if (trim($secret) === '' || $secret === 'default_secret' || strlen($secret) < 16) {
            throw new \InvalidArgumentException('CSRF secret must be a high-entropy key of at least 16 characters.');
        }
        $this->secret = $secret;
    }

    public function generate(?string $sessionId = null): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $resolvedId = ($sessionId !== null && $sessionId !== '')
            ? $sessionId
            : (session_id() ?: (string)($_COOKIE['nexus_session'] ?? ''));

        if ($resolvedId === '' || $resolvedId === 'default_session') {
            throw new \RuntimeException('Cannot generate CSRF token without an active session ID.');
        }

        return $this->generateToken($resolvedId);
    }

    public function generateToken(string $sessionId): string
    {
        if (trim($sessionId) === '' || $sessionId === 'default_session') {
            throw new \InvalidArgumentException('CSRF token generation requires a non-empty session ID.');
        }
        $entropy = bin2hex(random_bytes(16));
        $hmac = hash_hmac('sha256', $sessionId . '|' . $entropy, $this->secret);
        return $entropy . '.' . $hmac;
    }

    public function validateToken(?string $token, string $sessionId): bool
    {
        if ($token === null || !str_contains($token, '.')) {
            return false;
        }

        $resolvedId = $sessionId !== ''
            ? $sessionId
            : (session_id() ?: (string)($_COOKIE['nexus_session'] ?? ''));

        if ($resolvedId === '' || $resolvedId === 'default_session') {
            return false;
        }

        [$entropy, $hmac] = explode('.', $token, 2);
        $expectedHmac = hash_hmac('sha256', $resolvedId . '|' . $entropy, $this->secret);

        return hash_equals($expectedHmac, $hmac);
    }

    public function verifyRequest(Request $request, string $sessionId = ''): bool
    {
        if ($request->getBearerToken() !== null) {
            return true;
        }

        $token = $request->getHeader('x-csrf-token') ?? (string)($request->get('csrf_token') ?: $request->get('_token', ''));
        $resolvedId = $sessionId !== ''
            ? $sessionId
            : (session_id() ?: (string)($_COOKIE['nexus_session'] ?? ''));

        if ($token === '') {
            (new Logger())->warning('CSRF verification failed: missing token', [
                'method' => $request->getMethod(),
                'path' => $request->getPath(),
                'has_session' => $resolvedId !== '',
            ], $request->getTraceId());
            return false;
        }

        $valid = $this->validateToken($token, $resolvedId);
        if (!$valid) {
            (new Logger())->warning('CSRF verification failed: token mismatch or missing session', [
                'method' => $request->getMethod(),
                'path' => $request->getPath(),
                'has_session' => $resolvedId !== '',
            ], $request->getTraceId());
        }

        return $valid;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Safe HTTP methods bypass CSRF verification
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $sessionId = $_COOKIE['nexus_session'] ?? '';
        if (!$this->verifyRequest($request, $sessionId)) {
            return Response::error(
                'CSRF token validation failed or token expired.',
                ['csrf' => ['Invalid or missing CSRF token.']],
                419,
                [],
                $request->getTraceId()
            );
        }

        return $next($request);
    }
}

