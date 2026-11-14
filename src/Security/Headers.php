<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Core\MiddlewareInterface;
use Nexus\Core\Request;
use Nexus\Core\Response;

/**
 * Defense-in-Depth Security Headers Middleware
 */
class Headers implements MiddlewareInterface
{
    private static string $currentNonce = '';
    private array $customHeaders;

    public function __construct(array $customHeaders = [])
    {
        $this->customHeaders = $customHeaders;
    }

    public static function getNonce(): string
    {
        return self::$currentNonce;
    }

    public function handle(Request $request, callable $next): Response
    {
        // 1. Generate dynamic, cryptographically secure CSP nonce per request
        self::$currentNonce = base64_encode(random_bytes(16));
        $requestNonceHeader = 'nonce-' . self::$currentNonce;

        // 2. Execute inner pipeline
        /** @var Response $response */
        $response = $next($request);

        // 3. Inject Security Headers
        $isProduction = (string)\Nexus\Core\Env::get('APP_ENV', 'local') === 'production';
        $scriptSrc = $isProduction
            ? "script-src 'self' '{$requestNonceHeader}';"
            : "script-src 'self' 'unsafe-eval' '{$requestNonceHeader}';";

        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; {$scriptSrc} style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self';",
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-CSP-Nonce' => self::$currentNonce,
        ];

        // Enable HSTS if request arrived over HTTPS
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            ($_SERVER['SERVER_PORT'] ?? 80) == 443 ||
            ($request->getHeader('x-forwarded-proto') === 'https')
        ) {
            $headers['Strict-Transport-Security'] = 'max-age=63072000; includeSubDomains; preload';
        }

        foreach ($headers as $name => $val) {
            $response->setHeader($name, $val);
        }

        foreach ($this->customHeaders as $name => $val) {
            $response->setHeader($name, $val);
        }

        return $response;
    }
}

