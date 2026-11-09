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
        $this->secret = $secret;
    }

    public function generate(?string $sessionId = null): string
    {
        return $this->generateToken($sessionId ?? (session_id() ?: 'default_session'));
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

        [$entropy, $hmac] = explode('.', $token, 2);
        $expectedHmac = hash_hmac('sha256', $sessionId . '|' . $entropy, $this->secret);

        return hash_equals($expectedHmac, $hmac);
    }

    public function handle(Request $request, callable $next): Response
    {
        // Safe HTTP methods bypass CSRF verification
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Check if API Bearer token is present (stateless API bypass)
        if ($request->getBearerToken() !== null) {
            return $next($request);
        }

        $sessionId = $_COOKIE['nexus_session'] ?? '';
        $token = $request->getHeader('x-csrf-token') ?? (string)$request->get('_token', '');

        if (!$this->validateToken($token, $sessionId)) {
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

