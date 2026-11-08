<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Core\MiddlewareInterface;
use Nexus\Core\Request;
use Nexus\Core\Response;

/**
 * Strict CORS & Preflight Options Dispatcher
 */
class Cors implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private bool $allowCredentials;

    public function __construct(
        array $allowedOrigins = ['http://localhost'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token', 'X-Request-Id'],
        bool $allowCredentials = true
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
    }

    public function handle(Request $request, callable $next): Response
    {
        $origin = $request->getHeader('origin');

        // Check preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = Response::noContent();
            $this->applyCorsHeaders($response, $origin);
            return $response;
        }

        /** @var Response $response */
        $response = $next($request);
        $this->applyCorsHeaders($response, $origin);

        return $response;
    }

    private function applyCorsHeaders(Response $response, ?string $origin): void
    {
        if ($origin === null) {
            return;
        }

        $isAllowed = in_array('*', $this->allowedOrigins, true) || in_array($origin, $this->allowedOrigins, true);

        if ($isAllowed) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->setHeader('Access-Control-Max-Age', '86400');

            if ($this->allowCredentials) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }
        }
    }
}

