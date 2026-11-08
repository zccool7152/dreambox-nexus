<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * HTTP Middleware Pipeline Interface
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}

