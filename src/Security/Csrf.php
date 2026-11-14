<?php
declare(strict_types=1);

namespace Nexus\Security;

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
            : (session_id() ?: ($_COOKIE['nexus_session'] ?? 'default_session'));

        return $this->generateToken($resolvedId);
    }

    public function generateToken(string $sessionId): string
    {
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
            : (session_id() ?: ($_COOKIE['nexus_session'] ?? 'default_session'));

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
        if ($token === '') {
            return false;
        }

        return $this->validateToken($token, $sessionId) || $this->validateToken($token, 'default_session');
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

